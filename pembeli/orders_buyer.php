<?php
// pembeli/orders_buyer.php
// Shows the buyer's active orders and order history.
// Active   = status IN (pending, dibayar, dikirim)
// History  = status IN (selesai, dibatalkan)
// Clicking a shop name redirects to view_shop.php?id=<ID_toko>
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login_pembeli.php');
    exit;
}

$ID_user = $_SESSION['ID_user'];

// ── Fetch active orders ───────────────────────────────────────
// Joins profil_toko so we can show shop name as a link
$activeStmt = $pdo->prepare(
    "SELECT pb.*, pt.nama_toko, pt.ID_toko
     FROM pembelian pb
     JOIN profil_toko pt ON pt.ID_toko = pb.ID_toko
     WHERE pb.ID_user = ?
       AND pb.status_pembelian IN ('pending','diproses','dikirim')
     ORDER BY pb.tanggal_pembelian DESC"
);
$activeStmt->execute([$ID_user]);
$activeOrders = $activeStmt->fetchAll();

// ── Fetch order history ───────────────────────────────────────
$historyStmt = $pdo->prepare(
    "SELECT pb.*, pt.nama_toko, pt.ID_toko
     FROM pembelian pb
     JOIN profil_toko pt ON pt.ID_toko = pb.ID_toko
     WHERE pb.ID_user = ?
       AND pb.status_pembelian IN ('selesai','dibatalkan')
     ORDER BY pb.tanggal_pembelian DESC"
);
$historyStmt->execute([$ID_user]);
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
function metodeLabel(string $metode): string {
    return match($metode) {
        'diantar' => 'Diantar',
        'diambil' => 'Diambil Sendiri',
        default   => htmlspecialchars($metode),
    };
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Pesanan Saya</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/pembeli.css" rel="stylesheet" />
  <style>
    /* Extra badge colours not in pembeli.css */
    .badge-processing { background: #ede9fe; color: #5b21b6; }
    .badge-cancelled  { background: #fee2e2; color: #991b1b; }

    .table-wrap {
      overflow: hidden;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 5px 10px rgba(0,0,0,.15);
      margin-bottom: 32px;
    }
    .table-scroll { overflow-x: auto; }

    .shop-link {
      color: #78350f;
      font-weight: 600;
      text-decoration: none;
    }
    .shop-link:hover { text-decoration: underline; }

    .empty-row td {
      padding: 28px;
      color: #9ca3af;
      font-style: italic;
    }
  </style>
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../landing_page.php" class="site-header__logo">
        <div class="site-header__logo-box"><span>LocalMart</span></div>
      </a>
      <nav class="site-header__nav">
        <a href="dashboard_buyer.php">Beranda</a>
        <a href="orders_buyer.php" class="active">Pesanan</a>
        <a href="profile_buyer.php">Profil</a>
      </nav>
    </header>

    <main class="page-content">

      <!-- ══════════════════════════════════════════════════════
           ACTIVE ORDERS
      ══════════════════════════════════════════════════════ -->
      <div class="page-title" style="margin-bottom:16px;">
        <h1>Pesanan Anda</h1>
      </div>

      <div class="table-wrap">
        <div class="table-scroll">
          <table class="data-table">
            <thead>
              <tr>
                <th>Tanggal Pembelian</th>
                <th>Nama Toko</th>
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
                  <td colspan="8">Belum ada pesanan aktif.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($activeOrders as $order): ?>
                <tr>
                  <td><?= date('d-m-Y H:i', strtotime($order['tanggal_pembelian'])) ?></td>
                  <td>
                    <a href="view_shop.php?id=<?= $order['ID_toko'] ?>" class="shop-link">
                      <?= htmlspecialchars($order['nama_toko']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($order['nama_produk']) ?></td>
                  <td><?= (int)$order['jumlah'] ?></td>
                  <td>Rp <?= number_format($order['harga_satuan'], 0, ',', '.') ?></td>
                  <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                  <td><?= metodeLabel($order['metode_pengambilan'] ?? '-') ?></td>
                  <td><?= statusBadge($order['status_pembelian']) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

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
                <th>Tanggal Pembelian</th>
                <th>Nama Toko</th>
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
                  <td colspan="8">Belum ada riwayat pesanan.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($historyOrders as $order): ?>
                <tr>
                  <td><?= date('d-m-Y H:i', strtotime($order['tanggal_pembelian'])) ?></td>
                  <td>
                    <a href="view_shop.php?id=<?= $order['ID_toko'] ?>" class="shop-link">
                      <?= htmlspecialchars($order['nama_toko']) ?>
                    </a>
                  </td>
                  <td><?= htmlspecialchars($order['nama_produk']) ?></td>
                  <td><?= (int)$order['jumlah'] ?></td>
                  <td>Rp <?= number_format($order['harga_satuan'], 0, ',', '.') ?></td>
                  <td>Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
                  <td><?= metodeLabel($order['metode_pengambilan'] ?? '-') ?></td>
                  <td><?= statusBadge($order['status_pembelian']) ?></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

</body>
</html>
