<?php
// penjual/dashboard_seller.php
// Shows the seller's shop info and their product list.
// Gates access behind shop verification status (menunggu / ditolak / diterima).
// Handles product deletion via POST action=hapus&id_produk=X.
session_start();
require_once '../includes/koneksi.php';
require_once '../includes/pagination.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ../login_penjual.php');
    exit;
}

$ID_user = $_SESSION['ID_user'];

// Fetch the seller's shop
$shopStmt = $pdo->prepare('SELECT * FROM profil_toko WHERE ID_user = ?');
$shopStmt->execute([$ID_user]);
$toko = $shopStmt->fetch();

// If no shop yet, redirect to setup
if (!$toko) {
    header('Location: setup_shop.php');
    exit;
}

$ID_toko = $toko['ID_toko'];
$status  = $toko['status_verifikasi']; // 'menunggu' | 'diterima' | 'ditolak'

$flash = '';

// ── Handle delete product (only allowed when verified) ───────
if ($status === 'diterima'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'hapus'
    && !empty($_POST['id_produk'])
) {
    $id_produk = (int)$_POST['id_produk'];

    $own = $pdo->prepare('SELECT ID_produk, gambar_produk FROM produk WHERE ID_produk = ? AND ID_toko = ?');
    $own->execute([$id_produk, $ID_toko]);
    $produkRow = $own->fetch();

    if ($produkRow) {
        // Delete image file if it exists
        if ($produkRow['gambar_produk'] && file_exists('../' . $produkRow['gambar_produk'])) {
            unlink('../' . $produkRow['gambar_produk']);
        }

        // Must delete related pembelian rows first — foreign key fk_pembelian_produk
        // prevents deleting a product that has order history referencing it.
        $delOrders = $pdo->prepare('DELETE FROM pembelian WHERE ID_produk = ?');
        $delOrders->execute([$id_produk]);

        // Now safe to delete the product itself
        $del = $pdo->prepare('DELETE FROM produk WHERE ID_produk = ? AND ID_toko = ?');
        $del->execute([$id_produk, $ID_toko]);
        $flash = 'Produk berhasil dihapus.';
    }
}

// Fetch products (only needed for verified sellers)
$daftarProduk  = [];
$totalProduk   = 0;
$totalPages    = 1;
$page          = max(1, (int)($_GET['page'] ?? 1));
$perPage       = 10;
$kategoriLabel = [
    'makanan'            => 'Makanan',
    'minuman'            => 'Minuman',
    'perlengkapan mandi' => 'Perlengkapan Mandi',
    'perlengkapan dapur' => 'Perlengkapan Dapur',
];

// Sort order from GET param
$sortOptions = [
    'terbaru'   => 'p.created_at DESC',
    'stok'      => 'p.stok_produk DESC',
    'terjual'   => 'total_terjual DESC',
    'harga_asc' => 'p.harga_produk ASC',
    'harga_desc'=> 'p.harga_produk DESC',
];
$sort    = $_GET['sort'] ?? 'terbaru';
$orderBy = $sortOptions[$sort] ?? $sortOptions['terbaru'];

if ($status === 'diterima') {
    // Count total for pagination
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM produk WHERE ID_toko = ?');
    $countStmt->execute([$ID_toko]);
    $totalProduk = (int)$countStmt->fetchColumn();
    $totalPages  = max(1, (int)ceil($totalProduk / $perPage));
    $page        = min($page, $totalPages);
    $offset      = ($page - 1) * $perPage;

    $produkStmt = $pdo->prepare(
        "SELECT p.*,
                COALESCE(SUM(pb.jumlah), 0) AS total_terjual
         FROM produk p
         LEFT JOIN pembelian pb ON pb.ID_produk = p.ID_produk
         WHERE p.ID_toko = ?
         GROUP BY p.ID_produk
         ORDER BY $orderBy
         LIMIT $perPage OFFSET $offset"
    );
    $produkStmt->execute([$ID_toko]);
    $daftarProduk = $produkStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Dashboard Penjual</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/penjual.css" rel="stylesheet" />
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../index.html" class="site-header__logo">
        <div class="site-header__logo-box"><span>LocalMart</span></div>
      </a>
      <nav class="site-header__nav">
        <a href="dashboard_seller.php" class="active">Toko Anda</a>
        <a href="orders_seller.php">Pesanan</a>
        <a href="profile_settings_seller.php">Profil</a>
      </nav>
    </header>

    <?php if ($status === 'menunggu'): ?>
    <!-- ══════════════════════════════════════════════════════════
         WAITING SCREEN
    ══════════════════════════════════════════════════════════ -->
    <main class="verif-wrap">
      <div class="verif-card">
        <div class="spinner"></div>
        <p class="verif-card__title waiting">Toko Sedang Diverifikasi</p>
        <p class="verif-card__body">
          Permintaan setup toko <strong><?= htmlspecialchars($toko['nama_toko']) ?></strong>
          sedang ditinjau oleh admin LocalMart.<br><br>
          Halaman ini akan otomatis diperbarui setiap 30 detik.
          Kamu juga bisa muat ulang kapan saja.
        </p>
        <a href="profile_settings_seller.php" class="btn btn-secondary">Lihat Profil</a>
      </div>
    </main>
    <script>
      // Auto-refresh every 30 seconds so seller sees approval without manual reload
      setTimeout(() => location.reload(), 30000);
    </script>

    <?php elseif ($status === 'ditolak'): ?>
    <!-- ══════════════════════════════════════════════════════════
         REJECTED SCREEN
    ══════════════════════════════════════════════════════════ -->
    <main class="verif-wrap">
      <div class="verif-card">
        <div class="verif-card__icon">❌</div>
        <p class="verif-card__title rejected">Toko Ditolak</p>
        <p class="verif-card__body">
          Maaf, pengajuan toko <strong><?= htmlspecialchars($toko['nama_toko']) ?></strong>
          tidak disetujui oleh admin. Silakan perbaiki informasi toko dan ajukan kembali.
        </p>
        <?php if (!empty($toko['info_verifikasi'])): ?>
        <div class="verif-card__reason">
          <strong>Alasan penolakan:</strong>
          <?= htmlspecialchars($toko['info_verifikasi']) ?>
        </div>
        <?php endif; ?>
        <a href="shop_settings.php?resubmit=1" class="btn btn-primary" style="width:100%;margin-bottom:12px;">
          Perbaiki &amp; Ajukan Ulang
        </a>
        <a href="profile_settings_seller.php" class="btn btn-secondary">Lihat Profil</a>
      </div>
    </main>

    <?php else: ?>
    <!-- ══════════════════════════════════════════════════════════
         VERIFIED — NORMAL DASHBOARD
    ══════════════════════════════════════════════════════════ -->
    <main class="page-content">

      <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <!-- Store Info Bar -->
      <div class="store-info-bar">
        <?php if ($toko['logo_toko'] && file_exists('../' . $toko['logo_toko'])): ?>
          <img class="store-info-bar__img"
               src="../<?= htmlspecialchars($toko['logo_toko']) ?>"
               alt="Logo Toko" />
        <?php else: ?>
          <img class="store-info-bar__img" src="../images/assets/store-profile.png" alt="Default Store" />
        <?php endif; ?>
        <div class="store-info-bar__details">
          <p class="store-info-bar__name"><?= htmlspecialchars($toko['nama_toko']) ?></p>
          <p class="store-info-bar__address"><?= htmlspecialchars($toko['alamat_toko'] ?? '-') ?></p>
          <p class="store-info-bar__city"><?= htmlspecialchars($toko['kota'] ?? '-') ?></p>
        </div>
        <a href="shop_settings.php" class="btn btn-secondary" style="flex-shrink:0;">⚙ Edit Toko</a>
      </div>

      <!-- Product List Heading + Sort + Add Button -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
        <div class="section-heading" style="margin-bottom:0;flex:1;">
          <h2>Daftar Produk</h2>
        </div>
        <!-- Sort dropdown -->
        <form method="GET" action="dashboard_seller.php" style="margin:0;">
          <select name="sort" class="filter-select" onchange="this.form.submit()">
            <option value="terbaru"    <?= $sort==='terbaru'    ?'selected':'' ?>>Urut berdasarkan..</option>
            <option value="terjual"    <?= $sort==='terjual'    ?'selected':'' ?>>Penjualan Tertinggi</option>
            <option value="stok"       <?= $sort==='stok'       ?'selected':'' ?>>Stok Terbanyak</option>
            <option value="harga_desc" <?= $sort==='harga_desc' ?'selected':'' ?>>Harga Tertinggi</option>
            <option value="harga_asc"  <?= $sort==='harga_asc'  ?'selected':'' ?>>Harga Terendah</option>
          </select>
        </form>
        <a href="add_product.php" class="btn btn-primary" style="white-space:nowrap;">+ Tambah Produk</a>
      </div>

      <!-- Product Table -->
      <div style="overflow:hidden;background:#fff;border-radius:20px;box-shadow:0 5px 10px rgba(0,0,0,.15);">
        <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Nama Produk</th>
              <th>Deskripsi</th>
              <th>Harga</th>
              <th>Stok</th>
              <th>Total Terjual</th>
              <th>Kategori</th>
              <th>Gambar</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($daftarProduk)): ?>
              <tr>
                <td colspan="9" style="padding:24px;color:#999;">
                  Belum ada produk. <a href="add_product.php">Tambah sekarang!</a>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($daftarProduk as $i => $produk): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
                <td style="max-width:180px;white-space:normal;">
                  <?= htmlspecialchars($produk['deskripsi_produk'] ?? '-') ?>
                </td>
                <td>Rp. <?= number_format($produk['harga_produk'], 0, ',', '.') ?></td>
                <td><?= (int)$produk['stok_produk'] ?></td>
                <td style="font-weight:600;color:<?= (int)$produk['total_terjual'] > 0 ? '#065f46' : '#9ca3af' ?>;">
                  <?= (int)$produk['total_terjual'] ?>
                </td>
                <td><?= htmlspecialchars($kategoriLabel[$produk['kategori']] ?? $produk['kategori']) ?></td>
                <td>
                  <?php if ($produk['gambar_produk'] && file_exists('../' . $produk['gambar_produk'])): ?>
                    <img src="../<?= htmlspecialchars($produk['gambar_produk']) ?>"
                         alt="Produk"
                         style="width:56px;height:56px;object-fit:cover;border-radius:10px;border:2px solid #78350f;" />
                  <?php else: ?>
                    <img src="../images/assets/store-profile.png" alt="Produk"
                         style="width:56px;height:56px;object-fit:cover;border-radius:10px;border:2px solid #78350f;" />
                  <?php endif; ?>
                </td>
                <td>
                  <div style="display:flex;gap:6px;justify-content:center;">
                    <a href="edit_product.php?id=<?= $produk['ID_produk'] ?>"
                       class="btn btn-secondary"
                       style="padding:6px 14px;font-size:13px;">Edit</a>
                    <button type="button"
                            onclick="confirmDelete(<?= $produk['ID_produk'] ?>)"
                            class="btn btn-danger"
                            style="padding:6px 14px;font-size:13px;">Hapus</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        </div><!-- end overflow-x:auto -->
      </div>

      <!-- Hidden delete form -->
      <form id="deleteForm" action="dashboard_seller.php" method="POST" style="display:none;">
        <input type="hidden" name="action" value="hapus" />
        <input type="hidden" name="id_produk" id="deleteProductId" value="" />
      </form>

      <!-- Pagination -->
      <?php
        echo renderPagination($page, $totalPages, function($overrides) use ($sort) {
            $params = array_filter(['sort' => $sort, 'page' => $overrides['page'] ?? '1'],
                fn($v) => $v !== '' && $v !== '1' && $v !== 'terbaru');
            return 'dashboard_seller.php' . ($params ? '?' . http_build_query($params) : '');
        });
      ?>

    </main>

    <script>
      function confirmDelete(id) {
        if (confirm('Yakin ingin menghapus produk ini?\nRiwayat pesanan untuk produk ini juga akan ikut dihapus.\nTindakan ini tidak dapat dibatalkan.')) {
          document.getElementById('deleteProductId').value = id;
          document.getElementById('deleteForm').submit();
        }
      }
    </script>
    <?php endif; ?>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

</body>
</html>
