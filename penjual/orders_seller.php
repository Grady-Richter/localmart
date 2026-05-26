<?php
// penjual/orders_seller.php
// Shows the seller's incoming active orders with an inline status dropdown,
// and a read-only order history section below.
//
// Status flow:
//   diantar  : pending → diproses → dikirim → selesai / dibatalkan
//   diambil  : pending → diproses → selesai / dibatalkan  (no dikirim)
//
// Active  = status IN (pending, diproses, dikirim)
// History = status IN (selesai, dibatalkan)
session_start();
require_once '../includes/koneksi.php';
require_once '../includes/pagination.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ../login_penjual.php');
    exit;
}

$ID_user = $_SESSION['ID_user'];

// Get seller's shop
$shopStmt = $pdo->prepare('SELECT ID_toko FROM profil_toko WHERE ID_user = ?');
$shopStmt->execute([$ID_user]);
$toko = $shopStmt->fetch();

if (!$toko) {
    header('Location: setup_shop.php');
    exit;
}

$ID_toko = $toko['ID_toko'];
$flash     = '';
$flashType = 'success';

if (isset($_GET['updated'])) {
    $flash = 'Status pesanan berhasil diperbarui.';
} elseif (isset($_GET['error'])) {
    $flashType = 'error';
    $flash = match($_GET['error']) {
        'notfound' => 'Pesanan tidak ditemukan.',
        'invalid'  => 'Status tidak valid.',
        'enum'     => 'Status tidak diperbarui.',
        default    => 'Terjadi kesalahan.',
    };
}

// ── Handle status update ──────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && !empty($_POST['id_pembelian'])
    && !empty($_POST['status_baru'])
) {
    $id_pembelian = (int)$_POST['id_pembelian'];
    $status_baru  = $_POST['status_baru'];
    $validStatus  = ['pending', 'diproses', 'dikirim', 'selesai', 'dibatalkan'];

    if (in_array($status_baru, $validStatus)) {
        // Verify this order belongs to this seller's shop
        $own = $pdo->prepare(
            'SELECT ID_pembelian FROM pembelian WHERE ID_pembelian = ? AND ID_toko = ?'
        );
        $own->execute([$id_pembelian, $ID_toko]);
        if ($own->fetch()) {
            $upd = $pdo->prepare(
                'UPDATE pembelian SET status_pembelian = ? WHERE ID_pembelian = ?'
            );
            $upd->execute([$status_baru, $id_pembelian]);

            if ($upd->rowCount() === 0) {
                header('Location: orders_seller.php?error=enum');
                exit;
            }

            header('Location: orders_seller.php?updated=1');
            exit;
        } else {
            header('Location: orders_seller.php?error=notfound');
            exit;
        }
    } else {
        header('Location: orders_seller.php?error=invalid');
        exit;
    }
}

// ── Pagination ───────────────────────────────────────────────
$perPage     = 10;
$pageActive  = max(1, (int)($_GET['page_active']  ?? 1));
$pageHistory = max(1, (int)($_GET['page_history'] ?? 1));

// ── Count + fetch active orders (paginated) ───────────────────
$cntA = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE ID_toko = ? AND status_pembelian IN ('pending','diproses','dikirim')");
$cntA->execute([$ID_toko]);
$totalActive      = (int)$cntA->fetchColumn();
$totalPagesActive = max(1, (int)ceil($totalActive / $perPage));
$pageActive       = min($pageActive, $totalPagesActive);
$offsetActive     = ($pageActive - 1) * $perPage;

$activeStmt = $pdo->prepare(
    "SELECT pb.*,
            u.username,
            pup.alamat       AS alamat_pembeli,
            pup.nomor_telepon
     FROM pembelian pb
     JOIN users u              ON u.ID_user  = pb.ID_user
     LEFT JOIN profil_user_pembeli pup ON pup.ID_user = pb.ID_user
     WHERE pb.ID_toko = ?
       AND pb.status_pembelian IN ('pending','diproses','dikirim')
     ORDER BY pb.tanggal_pembelian ASC
     LIMIT $perPage OFFSET $offsetActive"
);
$activeStmt->execute([$ID_toko]);
$activeOrders = $activeStmt->fetchAll();

// ── Count + fetch order history (paginated) ───────────────────
$cntH = $pdo->prepare("SELECT COUNT(*) FROM pembelian WHERE ID_toko = ? AND status_pembelian IN ('selesai','dibatalkan')");
$cntH->execute([$ID_toko]);
$totalHistory      = (int)$cntH->fetchColumn();
$totalPagesHistory = max(1, (int)ceil($totalHistory / $perPage));
$pageHistory       = min($pageHistory, $totalPagesHistory);
$offsetHistory     = ($pageHistory - 1) * $perPage;

$historyStmt = $pdo->prepare(
    "SELECT pb.*,
            u.username,
            pup.alamat       AS alamat_pembeli,
            pup.nomor_telepon
     FROM pembelian pb
     JOIN users u              ON u.ID_user  = pb.ID_user
     LEFT JOIN profil_user_pembeli pup ON pup.ID_user = pb.ID_user
     WHERE pb.ID_toko = ?
       AND pb.status_pembelian IN ('selesai','dibatalkan')
     ORDER BY pb.tanggal_pembelian DESC
     LIMIT $perPage OFFSET $offsetHistory"
);
$historyStmt->execute([$ID_toko]);
$historyOrders = $historyStmt->fetchAll();

// ── Status badge helper ───────────────────────────────────────
function statusBadge(string $status): string {
    $map = [
        'pending'    => ['label' => 'Menunggu',  'class' => 'badge-pending'],
        'diproses'   => ['label' => 'Diproses',  'class' => 'badge-processing'],
        'dikirim'    => ['label' => 'Dikirim',   'class' => 'badge-sent'],
        'selesai'    => ['label' => 'Selesai',   'class' => 'badge-done'],
        'dibatalkan' => ['label' => 'Dibatalkan','class' => 'badge-cancelled'],
    ];
    $s = $map[$status] ?? ['label' => ucfirst($status), 'class' => 'badge-pending'];
    return '<span class="badge ' . $s['class'] . '">' . $s['label'] . '</span>';
}

// ── Metode label helper ───────────────────────────────────────
function metodeLabel(?string $metode): string {
    return match($metode) {
        'diantar' => 'Diantar',
        'diambil' => 'Diambil Sendiri',
        default   => htmlspecialchars((string)$metode),
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Pesanan Pembeli</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/penjual.css" rel="stylesheet" />
  <style>

    .badge {
      display: inline-block;
      padding: 3px 12px;
      border-radius: 20px;
      font-size: 12px;
      font-weight: 600;
    }
    .badge-pending      { background: #fff3cd; color: #856404; }
    .badge-processing   { background: #ede9fe; color: #5b21b6; }
    .badge-sent         { background: #d4edda; color: #155724; }
    .badge-done         { background: #cce5ff; color: #004085; }
    .badge-cancelled    { background: #fee2e2; color: #991b1b; }

    .table-wrap {
      overflow: hidden;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 5px 10px rgba(0,0,0,.15);
      margin-bottom: 32px;
    }
    .table-scroll { overflow-x: auto; }

    .empty-row td {
      padding: 28px;
      color: #9ca3af;
      font-style: italic;
    }

    /* Inline status select */
    .status-select {
      background: rgba(240,237,237,0.5);
      border: 2px solid #000;
      border-radius: 8px;
      padding: 5px 24px 5px 8px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      color: rgba(32,32,32,0.8);
      box-shadow: 0 2px 6px rgba(0,0,0,0.2);
      outline: none;
      cursor: pointer;
      appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='7'%3E%3Cpath d='M0 0l5 7 5-7z' fill='%23000'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 8px center;
      background-color: rgba(240,237,237,0.5);
    }

    .btn-update {
      background: #78350f;
      color: #fff;
      border: none;
      border-radius: 8px;
      padding: 6px 14px;
      font-family: 'Poppins', sans-serif;
      font-size: 13px;
      font-weight: 600;
      cursor: pointer;
      transition: filter 0.15s;
      white-space: nowrap;
    }
    .btn-update:hover { filter: brightness(1.15); }
  </style>
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../index.html" class="site-header__logo">
        <div class="site-header__logo-box"><span>LocalMart</span></div>
      </a>
      <nav class="site-header__nav">
        <a href="dashboard_seller.php">Toko Anda</a>
        <a href="orders_seller.php" class="active">Pesanan</a>
        <a href="profile_settings_seller.php">Profil</a>
      </nav>
    </header>

    <main class="page-content">

      <?php if ($flash): ?>
        <div class="alert alert-<?= $flashType === 'error' ? 'error' : 'success' ?>"
             style="margin-bottom:16px;">
          <?= htmlspecialchars($flash) ?>
        </div>
      <?php endif; ?>

      <!-- ══════════════════════════════════════════════════════
           ACTIVE ORDERS
      ══════════════════════════════════════════════════════ -->
      <div class="page-title" style="margin-bottom:16px;">
        <h1>Pesanan Pembeli</h1>
      </div>

      <div class="table-wrap">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Nama Pembeli</th>
                <th>Alamat</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
                <th>Total Harga</th>
                <th>Metode Pengambilan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($activeOrders)): ?>
                <tr class="empty-row">
                  <td colspan="8">Tidak ada pesanan aktif saat ini.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($activeOrders as $order): ?>
                <tr>
                  <td><?= htmlspecialchars($order['username']) ?></td>
                  <td style="max-width:160px;white-space:normal;">
                    <?php if ($order['metode_pengambilan'] === 'diantar'): ?>
                      <?= htmlspecialchars($order['alamat_pengiriman'] ?? $order['alamat_pembeli'] ?? '-') ?>
                    <?php else: ?>
                      <span style="color:#9ca3af;font-style:italic;">Diambil sendiri</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($order['nama_produk']) ?></td>
                  <td><?= (int)$order['jumlah'] ?></td>
                  <td>Rp <?= number_format($order['harga_satuan'], 0, ',', '.') ?></td>
                  <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                  <td><?= metodeLabel($order['metode_pengambilan']) ?></td>
                  <td>
                    <!-- Inline status update form.
                         diantar : pending → diproses → dikirim → selesai / dibatalkan
                         diambil : pending → diproses → selesai / dibatalkan -->
                    <form method="POST" action="orders_seller.php"
                          style="display:flex;gap:6px;align-items:center;justify-content:center;">
                      <input type="hidden" name="id_pembelian"
                             value="<?= $order['ID_pembelian'] ?>" />
                      <?php
                        $isDiantar = ($order['metode_pengambilan'] === 'diantar');
                        $statusOptions = [
                            'pending'  => 'Menunggu',
                            'diproses' => 'Diproses',
                        ];
                        if ($isDiantar) {
                            $statusOptions['dikirim'] = 'Dikirim';
                        }
                        $statusOptions['selesai']    = 'Selesai';
                        $statusOptions['dibatalkan'] = 'Dibatalkan';
                      ?>
                      <select name="status_baru" class="status-select">
                        <?php foreach ($statusOptions as $val => $label): ?>
                          <option value="<?= $val ?>"
                            <?= $order['status_pembelian'] === $val ? 'selected' : '' ?>>
                            <?= $label ?>
                          </option>
                        <?php endforeach; ?>
                      </select>
                      <button type="submit" class="btn-update">Ubah</button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php echo renderPagination($pageActive, $totalPagesActive, function($o) {
        $p = array_filter(['page_active' => $o['page'] ?? '1', 'page_history' => (string)($_GET['page_history'] ?? '1')], fn($v) => $v !== '' && $v !== '1');
        return 'orders_seller.php' . ($p ? '?' . http_build_query($p) : '');
      }); ?>

      <!-- ══════════════════════════════════════════════════════
           ORDER HISTORY
      ══════════════════════════════════════════════════════ -->
      <div class="page-title" style="margin-bottom:16px;">
        <h1>Riwayat Pesanan</h1>
      </div>

      <div class="table-wrap">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal</th>
                <th>Nama Pembeli</th>
                <th>Alamat</th>
                <th>Produk</th>
                <th>Jumlah</th>
                <th>Harga Satuan</th>
                <th>Total Harga</th>
                <th>Metode Pengambilan</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($historyOrders)): ?>
                <tr class="empty-row">
                  <td colspan="9">Belum ada riwayat pesanan.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($historyOrders as $order): ?>
                <tr>
                  <td><?= date('d-m-Y H:i', strtotime($order['tanggal_pembelian'])) ?></td>
                  <td><?= htmlspecialchars($order['username']) ?></td>
                  <td style="max-width:160px;white-space:normal;">
                    <?php if ($order['metode_pengambilan'] === 'diantar'): ?>
                      <?= htmlspecialchars($order['alamat_pengiriman'] ?? $order['alamat_pembeli'] ?? '-') ?>
                    <?php else: ?>
                      <span style="color:#9ca3af;font-style:italic;">Diambil sendiri</span>
                    <?php endif; ?>
                  </td>
                  <td><?= htmlspecialchars($order['nama_produk']) ?></td>
                  <td><?= (int)$order['jumlah'] ?></td>
                  <td>Rp <?= number_format($order['harga_satuan'], 0, ',', '.') ?></td>
                  <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                  <td><?= metodeLabel($order['metode_pengambilan']) ?></td>
                  <td><?= statusBadge($order['status_pembelian']) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php echo renderPagination($pageHistory, $totalPagesHistory, function($o) {
        $p = array_filter(['page_active' => (string)($_GET['page_active'] ?? '1'), 'page_history' => $o['page'] ?? '1'], fn($v) => $v !== '' && $v !== '1');
        return 'orders_seller.php' . ($p ? '?' . http_build_query($p) : '');
      }); ?>

    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>
  </div>
</body>
</html>
