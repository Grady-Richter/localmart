<?php
// admin/dashboard_admin.php
// Admin panel — shop verification.
// Design: Figma admin_dashboard export.
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php');
    exit;
}

$flash     = '';
$flashType = 'success';

// ── Handle Accept / Reject POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action  = $_POST['action']  ?? '';
    $id_toko = (int)($_POST['id_toko'] ?? 0);
    $alasan  = trim($_POST['alasan'] ?? '');

    if ($id_toko > 0) {
        if ($action === 'terima') {
            $pdo->prepare(
                'UPDATE profil_toko SET status_verifikasi = "diterima", info_verifikasi = NULL WHERE ID_toko = ?'
            )->execute([$id_toko]);
            $flash = 'Toko berhasil diterima.';
        } elseif ($action === 'tolak') {
            $pdo->prepare(
                'UPDATE profil_toko SET status_verifikasi = "ditolak", info_verifikasi = ? WHERE ID_toko = ?'
            )->execute([$alasan ?: null, $id_toko]);
            $flash     = 'Toko berhasil ditolak.';
            $flashType = 'error';
        }
    }
}

// ── Fetch shops ───────────────────────────────────────────────
$shops = $pdo->query(
    'SELECT pt.*, u.username
     FROM profil_toko pt
     JOIN users u ON u.ID_user = pt.ID_user
     ORDER BY FIELD(pt.status_verifikasi,"menunggu","ditolak","diterima"), pt.created_at ASC'
)->fetchAll();

$counts = ['menunggu' => 0, 'diterima' => 0, 'ditolak' => 0];
foreach ($shops as $s) $counts[$s['status_verifikasi']]++;

$filter   = $_GET['filter'] ?? 'semua';
$filtered = ($filter === 'semua')
    ? $shops
    : array_filter($shops, fn($s) => $s['status_verifikasi'] === $filter);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Admin: Verifikasi</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/admin.css" rel="stylesheet" />
</head>
<body>

  <header class="admin-header">
    <div class="admin-header__brand">
      <div class="admin-header__logo-box"><span>LocalMart</span></div>
    </div>
    <nav class="admin-header__nav">
      <a href="dashboard_admin.php" class="active">Verifikasi</a>
      <a href="admin_users.php">Users</a>
      <a href="../includes/logout.php" class="logout"
         onclick="return confirm('Yakin ingin keluar?')">Keluar</a>
    </nav>
  </header>

  <main class="admin-main">

    <?php if ($flash): ?>
      <div class="flash flash-<?= $flashType ?>"><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <div class="admin-page-heading">
      <h1>Verifikasi Toko</h1>
    </div>

    <!-- Summary cards -->
    <div class="summary-row">
      <div class="summary-card summary-card--waiting">
        <div>
          <div class="summary-card__count"><?= $counts['menunggu'] ?></div>
          <div class="summary-card__label">Menunggu Verifikasi</div>
        </div>
      </div>
      <div class="summary-card summary-card--accepted">
        <div>
          <div class="summary-card__count"><?= $counts['diterima'] ?></div>
          <div class="summary-card__label">Terverifikasi</div>
        </div>
      </div>
      <div class="summary-card summary-card--rejected">
        <div>
          <div class="summary-card__count"><?= $counts['ditolak'] ?></div>
          <div class="summary-card__label">Tidak Diverifikasi</div>
        </div>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="filter-tabs">
      <a href="?filter=semua"    class="filter-tab <?= $filter==='semua'    ?'active':'' ?>">Semua (<?= count($shops) ?>)</a>
      <a href="?filter=menunggu" class="filter-tab <?= $filter==='menunggu' ?'active':'' ?>">Menunggu (<?= $counts['menunggu'] ?>)</a>
      <a href="?filter=diterima" class="filter-tab <?= $filter==='diterima' ?'active':'' ?>">Diterima (<?= $counts['diterima'] ?>)</a>
      <a href="?filter=ditolak"  class="filter-tab <?= $filter==='ditolak'  ?'active':'' ?>">Ditolak (<?= $counts['ditolak'] ?>)</a>
    </div>

    <!-- Shop cards -->
    <?php if (empty($filtered)): ?>
      <div class="empty-state">
        <span>🏪</span>
        Tidak ada toko dengan status ini.
      </div>
    <?php else: ?>
      <?php foreach ($filtered as $shop):
        $sid     = $shop['ID_toko'];
        $sstatus = $shop['status_verifikasi'];
      ?>
      <div class="shop-card">

        <!-- Logo -->
        <div>
          <?php if (!empty($shop['logo_toko']) && file_exists('../' . $shop['logo_toko'])): ?>
            <img class="shop-card__logo"
                 src="../<?= htmlspecialchars($shop['logo_toko']) ?>"
                 alt="Logo <?= htmlspecialchars($shop['nama_toko']) ?>" />
          <?php else: ?>
            <img class="shop-card__logo"
                 src="../images/assets/store-profile.png"
                 alt="Default Store" />
          <?php endif; ?>
        </div>

        <!-- Info — matches Figma text layout -->
        <div class="shop-card__info">
          <h3><?= htmlspecialchars($shop['nama_toko']) ?></h3>
          <p>Penjual: <?= htmlspecialchars($shop['username']) ?>
Alamat: <?= htmlspecialchars($shop['alamat_toko'] ?? '-') ?>
Kota: <?= htmlspecialchars($shop['kota'] ?? '-') ?>
<?php if (!empty($shop['deskripsi_toko'])): ?>Deskripsi: <?= htmlspecialchars($shop['deskripsi_toko']) ?>
<?php endif; ?>Didaftarkan: <?= date('d M Y, H:i', strtotime($shop['created_at'])) ?></p>

          <span class="shop-card__status status-<?= $sstatus ?>">
            <?php if ($sstatus === 'menunggu'): ?>⏳ Menunggu
            <?php elseif ($sstatus === 'diterima'): ?>✅ Diterima
            <?php else: ?>❌ Ditolak<?php endif; ?>
          </span>

          <?php if ($sstatus === 'ditolak' && !empty($shop['info_verifikasi'])): ?>
            <div class="shop-card__reject-reason">
              <strong>Alasan penolakan:</strong>
              <?= htmlspecialchars($shop['info_verifikasi']) ?>
            </div>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="shop-card__actions">
          <?php if ($sstatus === 'menunggu'): ?>
            <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>">
              <input type="hidden" name="action"  value="terima" />
              <input type="hidden" name="id_toko" value="<?= $sid ?>" />
              <button type="submit" class="btn btn-accept" style="width:100%;"
                      onclick="return confirm('Terima toko ini?')">✅ Terima</button>
            </form>
            <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>"
                  class="reject-form">
              <input type="hidden" name="action"  value="tolak" />
              <input type="hidden" name="id_toko" value="<?= $sid ?>" />
              <textarea name="alasan" class="reject-textarea"
                        placeholder="Alasan penolakan (opsional)..."></textarea>
              <button type="submit" class="btn btn-reject"
                      onclick="return confirm('Tolak toko ini?')">❌ Tolak</button>
            </form>

          <?php elseif ($sstatus === 'diterima'): ?>
            <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>"
                  class="reject-form">
              <input type="hidden" name="action"  value="tolak" />
              <input type="hidden" name="id_toko" value="<?= $sid ?>" />
              <textarea name="alasan" class="reject-textarea"
                        placeholder="Alasan pencabutan persetujuan..."></textarea>
              <button type="submit" class="btn btn-reopen" style="width:100%;"
                      onclick="return confirm('Cabut persetujuan toko ini?')">🔄 Cabut &amp; Tolak</button>
            </form>

          <?php elseif ($sstatus === 'ditolak'): ?>
            <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>">
              <input type="hidden" name="action"  value="terima" />
              <input type="hidden" name="id_toko" value="<?= $sid ?>" />
              <button type="submit" class="btn btn-accept" style="width:100%;"
                      onclick="return confirm('Terima toko ini sekarang?')">✅ Terima Sekarang</button>
            </form>
          <?php endif; ?>
        </div>

      </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </main>

  <footer class="admin-footer">
    <span>© LocalMart 2026</span>
  </footer>

</body>
</html>
