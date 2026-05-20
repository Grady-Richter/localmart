<?php
// pembeli/view_shop.php
// Displays a specific shop's info and its product list.
// Requires ?id=<ID_toko> in the URL.
// Products can be filtered by category via ?kategori=<value>.
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login_pembeli.php');
    exit;
}

$ID_toko = (int)($_GET['id'] ?? 0);
if (!$ID_toko) {
    header('Location: dashboard_buyer.php');
    exit;
}

// Fetch shop — only show verified shops
$shopStmt = $pdo->prepare(
    'SELECT * FROM profil_toko WHERE ID_toko = ? AND status_verifikasi = "diterima"'
);
$shopStmt->execute([$ID_toko]);
$toko = $shopStmt->fetch();

if (!$toko) {
    header('Location: dashboard_buyer.php');
    exit;
}

// Category filter
$kategoriFilter = $_GET['kategori'] ?? '';
$validKategori  = ['makanan', 'minuman', 'perlengkapan mandi', 'perlengkapan dapur'];
$kategoriLabel  = [
    'makanan'            => 'Makanan',
    'minuman'            => 'Minuman',
    'perlengkapan mandi' => 'Perlengkapan Mandi',
    'perlengkapan dapur' => 'Perlengkapan Dapur',
];

// Fetch distinct categories this shop actually has
$catStmt = $pdo->prepare('SELECT DISTINCT kategori FROM produk WHERE ID_toko = ? ORDER BY kategori');
$catStmt->execute([$ID_toko]);
$shopKategori = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// Build product query
if ($kategoriFilter && in_array($kategoriFilter, $validKategori)) {
    $produkStmt = $pdo->prepare(
        'SELECT * FROM produk WHERE ID_toko = ? AND kategori = ? AND stok_produk > 0
         ORDER BY nama_produk ASC'
    );
    $produkStmt->execute([$ID_toko, $kategoriFilter]);
} else {
    $produkStmt = $pdo->prepare(
        'SELECT * FROM produk WHERE ID_toko = ? AND stok_produk > 0
         ORDER BY nama_produk ASC'
    );
    $produkStmt->execute([$ID_toko]);
}
$produk = $produkStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — <?= htmlspecialchars($toko['nama_toko']) ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/pembeli.css" rel="stylesheet" />
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../landing_page.html" class="site-header__logo">
        <div class="site-header__logo-box"><span>LocalMart</span></div>
      </a>
      <nav class="site-header__nav">
        <a href="dashboard_buyer.php">Beranda</a>
        <a href="orders_buyer.php">Pesanan</a>
        <a href="profile_buyer.php">Profil</a>
      </nav>
    </header>

    <main class="page-content">

      <a href="dashboard_buyer.php" class="btn-back">Kembali</a>

      <!-- Shop Info Bar -->
      <div class="store-info-bar">
        <?php if (!empty($toko['logo_toko']) && file_exists('../' . $toko['logo_toko'])): ?>
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
      </div>

      <!-- Category pills — only show categories this shop has products in -->
      <?php if (!empty($shopKategori)): ?>
      <div class="category-pills">
        <a href="view_shop.php?id=<?= $ID_toko ?>"
           class="pill <?= $kategoriFilter === '' ? 'active' : '' ?>"
           style="text-decoration:none;">Semua</a>
        <?php foreach ($shopKategori as $kat): ?>
          <a href="view_shop.php?id=<?= $ID_toko ?>&kategori=<?= urlencode($kat) ?>"
             class="pill <?= $kategoriFilter === $kat ? 'active' : '' ?>"
             style="text-decoration:none;">
            <?= htmlspecialchars($kategoriLabel[$kat] ?? $kat) ?>
          </a>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- Product list -->
      <div class="content-header-bar">
        <h2>Daftar Produk</h2>
      </div>
      <div class="content-panel">

        <?php if (empty($produk)): ?>
          <p style="text-align:center;color:#9ca3af;padding:32px 0;">
            Belum ada produk<?= $kategoriFilter ? ' dalam kategori ini' : '' ?>.
          </p>
        <?php else: ?>
          <?php foreach ($produk as $p): ?>
          <div class="list-card">
            <?php if (!empty($p['gambar_produk']) && file_exists('../' . $p['gambar_produk'])): ?>
              <img class="list-card__thumb"
                   src="../<?= htmlspecialchars($p['gambar_produk']) ?>"
                   alt="<?= htmlspecialchars($p['nama_produk']) ?>" />
            <?php else: ?>
              <div class="list-card__thumb-placeholder">📦</div>
            <?php endif; ?>

            <div class="list-card__info">
              <p class="list-card__name"><?= htmlspecialchars($p['nama_produk']) ?></p>
              <?php if (!empty($p['deskripsi_produk'])): ?>
                <p class="list-card__sub"><?= htmlspecialchars($p['deskripsi_produk']) ?></p>
              <?php endif; ?>
              <p class="list-card__price">Rp <?= number_format($p['harga_produk'], 0, ',', '.') ?></p>
            </div>

            <div class="list-card__action">
              <span class="pill" style="margin-bottom:6px;">
                <?= htmlspecialchars($kategoriLabel[$p['kategori']] ?? $p['kategori']) ?>
              </span>
              <a href="view_product.php?id=<?= $p['ID_produk'] ?>" class="btn-view">Lihat</a>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>
    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

</body>
</html>
