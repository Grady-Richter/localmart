<?php
// admin/dashboard_admin.php
// Admin panel for LocalMart.
// Lists all shops by verification status and allows Accept / Reject with an optional reason.
//
// FOLDER STRUCTURE ASSUMPTION:
//   /admin/dashboard_admin.php   ← this file
//   /koneksi.php                 ← one level up
//
// SECURITY NOTE:
//   This page is protected by role === 'admin' session check.
//   Make sure at least one user in the `users` table has role = 'admin'.
session_start();
require_once '../includes/koneksi.php';

// ── Auth guard ────────────────────────────────────────────────
if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php');
    exit;
}

$flash      = '';
$flashType  = 'success';

// ── Handle Accept / Reject POST ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action   = $_POST['action']   ?? '';
    $id_toko  = (int)($_POST['id_toko'] ?? 0);
    $alasan   = trim($_POST['alasan'] ?? '');

    if ($id_toko > 0) {
        if ($action === 'terima') {
            $stmt = $pdo->prepare(
                'UPDATE profil_toko
                 SET status_verifikasi = "diterima", info_verifikasi = NULL
                 WHERE ID_toko = ?'
            );
            $stmt->execute([$id_toko]);
            $flash = 'Toko berhasil diterima.';

        } elseif ($action === 'tolak') {
            $stmt = $pdo->prepare(
                'UPDATE profil_toko
                 SET status_verifikasi = "ditolak", info_verifikasi = ?
                 WHERE ID_toko = ?'
            );
            $stmt->execute([$alasan ?: null, $id_toko]);
            $flash     = 'Toko berhasil ditolak.';
            $flashType = 'error';
        }
    }
}

// ── Fetch shops grouped by status ────────────────────────────
// We fetch all shops and their owner username in one query.
$shops = $pdo->query(
    'SELECT pt.*, u.username
     FROM profil_toko pt
     JOIN users u ON u.ID_user = pt.ID_user
     ORDER BY
       FIELD(pt.status_verifikasi, "menunggu", "ditolak", "diterima"),
       pt.created_at ASC'
)->fetchAll();

// Count per status for the summary badges
$counts = ['menunggu' => 0, 'diterima' => 0, 'ditolak' => 0];
foreach ($shops as $s) $counts[$s['status_verifikasi']]++;

// Active filter from GET (default: all)
$filter = $_GET['filter'] ?? 'semua';
$filtered = ($filter === 'semua')
    ? $shops
    : array_filter($shops, fn($s) => $s['status_verifikasi'] === $filter);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Admin Panel</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/admin.css" rel="stylesheet" />
</head>
<body>

  <!-- ── Admin Header ── -->
  <header class="admin-header">
    <div class="admin-header__brand">
      <span>LocalMart</span>
      <span class="admin-header__badge">ADMIN</span>
    </div>
    <div class="admin-header__right">
      <span><?= htmlspecialchars($_SESSION['username']) ?></span>
      <a href="../includes/logout.php"
         onclick="return confirm('Yakin ingin logout?')">Logout</a>
    </div>
  </header>

  <main class="admin-main">

    <h1 style="font-size:22px;font-weight:700;margin-bottom:20px;color:#1f2937;">
      Verifikasi Toko
    </h1>

    <?php if ($flash): ?>
      <div class="flash flash-<?= $flashType ?>">
        <?= htmlspecialchars($flash) ?>
      </div>
    <?php endif; ?>

    <!-- ── Summary Cards ── -->
    <div class="summary-row">
      <div class="summary-card summary-card--waiting">
        <div class="summary-card__icon">⏳</div>
        <div class="summary-card__info">
          <p><?= $counts['menunggu'] ?></p>
          <p>Menunggu Verifikasi</p>
        </div>
      </div>
      <div class="summary-card summary-card--accepted">
        <div class="summary-card__icon">✅</div>
        <div class="summary-card__info">
          <p><?= $counts['diterima'] ?></p>
          <p>Toko Diterima</p>
        </div>
      </div>
      <div class="summary-card summary-card--rejected">
        <div class="summary-card__icon">❌</div>
        <div class="summary-card__info">
          <p><?= $counts['ditolak'] ?></p>
          <p>Toko Ditolak</p>
        </div>
      </div>
    </div>

    <!-- ── Filter Tabs ── -->
    <div class="filter-tabs">
      <a href="?filter=semua"    class="filter-tab <?= $filter === 'semua'    ? 'active' : '' ?>">Semua (<?= count($shops) ?>)</a>
      <a href="?filter=menunggu" class="filter-tab <?= $filter === 'menunggu' ? 'active' : '' ?>">Menunggu (<?= $counts['menunggu'] ?>)</a>
      <a href="?filter=diterima" class="filter-tab <?= $filter === 'diterima' ? 'active' : '' ?>">Diterima (<?= $counts['diterima'] ?>)</a>
      <a href="?filter=ditolak"  class="filter-tab <?= $filter === 'ditolak'  ? 'active' : '' ?>">Ditolak (<?= $counts['ditolak'] ?>)</a>
    </div>

    <!-- ── Shop Cards ── -->
    <?php if (empty($filtered)): ?>
      <div class="empty-state">
        <span>🏪</span>
        Tidak ada toko dengan status ini.
      </div>
    <?php else: ?>
      <?php foreach ($filtered as $shop): ?>
        <?php
          $sid    = $shop['ID_toko'];
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
              <div class="shop-card__logo-placeholder">🏪</div>
            <?php endif; ?>
          </div>

          <!-- Info -->
          <div class="shop-card__info">
            <h3><?= htmlspecialchars($shop['nama_toko']) ?></h3>
            <p><strong>Penjual:</strong> <?= htmlspecialchars($shop['username']) ?></p>
            <p><strong>Alamat:</strong>  <?= htmlspecialchars($shop['alamat_toko'] ?? '-') ?></p>
            <p><strong>Kota:</strong>    <?= htmlspecialchars($shop['kota'] ?? '-') ?></p>
            <?php if (!empty($shop['deskripsi_toko'])): ?>
            <p><strong>Deskripsi:</strong> <?= htmlspecialchars($shop['deskripsi_toko']) ?></p>
            <?php endif; ?>
            <p><strong>Didaftarkan:</strong>
              <?= date('d M Y, H:i', strtotime($shop['created_at'])) ?>
            </p>

            <!-- Status badge -->
            <span class="shop-card__status status-<?= $sstatus ?>">
              <?php if ($sstatus === 'menunggu'):  ?>⏳ Menunggu
              <?php elseif ($sstatus === 'diterima'): ?>✅ Diterima
              <?php else: ?>❌ Ditolak
              <?php endif; ?>
            </span>

            <!-- Show rejection reason if available -->
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
              <!-- Accept -->
              <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>">
                <input type="hidden" name="action"  value="terima" />
                <input type="hidden" name="id_toko" value="<?= $sid ?>" />
                <button type="submit" class="btn btn-accept" style="width:100%;"
                        onclick="return confirm('Terima toko ini?')">
                  ✅ Terima
                </button>
              </form>

              <!-- Reject with reason -->
              <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>"
                    class="reject-form">
                <input type="hidden" name="action"  value="tolak" />
                <input type="hidden" name="id_toko" value="<?= $sid ?>" />
                <textarea name="alasan"
                          class="reject-textarea"
                          placeholder="Alasan penolakan (opsional)..."></textarea>
                <button type="submit" class="btn btn-reject"
                        onclick="return confirm('Tolak toko ini?')">
                  ❌ Tolak
                </button>
              </form>

            <?php elseif ($sstatus === 'diterima'): ?>
              <!-- Re-open to pending (in case admin made a mistake) -->
              <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>">
                <input type="hidden" name="action"  value="tolak" />
                <input type="hidden" name="id_toko" value="<?= $sid ?>" />
                <textarea name="alasan"
                          class="reject-textarea"
                          placeholder="Alasan pencabutan persetujuan..."></textarea>
                <button type="submit" class="btn btn-reopen" style="width:100%;"
                        onclick="return confirm('Cabut persetujuan toko ini?')">
                  🔄 Cabut &amp; Tolak
                </button>
              </form>

            <?php elseif ($sstatus === 'ditolak'): ?>
              <!-- Re-accept a previously rejected shop -->
              <form method="POST" action="dashboard_admin.php?filter=<?= htmlspecialchars($filter) ?>">
                <input type="hidden" name="action"  value="terima" />
                <input type="hidden" name="id_toko" value="<?= $sid ?>" />
                <button type="submit" class="btn btn-accept" style="width:100%;"
                        onclick="return confirm('Terima toko ini sekarang?')">
                  ✅ Terima Sekarang
                </button>
              </form>

            <?php endif; ?>
          </div>

        </div>
      <?php endforeach; ?>
    <?php endif; ?>

  </main>

</body>
</html>
