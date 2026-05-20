<?php
// pembeli/view_product.php
// Shows product details and handles order placement.
// Inserts into `pembelian` on POST.
// Requires ?id=<ID_produk> in the URL.
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login_pembeli.php');
    exit;
}

$ID_user   = $_SESSION['ID_user'];
$ID_produk = (int)($_GET['id'] ?? $_POST['ID_produk'] ?? 0);

if (!$ID_produk) {
    header('Location: dashboard_buyer.php');
    exit;
}

// Fetch product + shop in one query (only from verified shops)
$stmt = $pdo->prepare(
    'SELECT p.*, pt.nama_toko, pt.alamat_toko, pt.kota, pt.logo_toko, pt.ID_toko
     FROM produk p
     JOIN profil_toko pt ON pt.ID_toko = p.ID_toko
     WHERE p.ID_produk = ? AND pt.status_verifikasi = "diterima"'
);
$stmt->execute([$ID_produk]);
$produk = $stmt->fetch();

if (!$produk) {
    header('Location: dashboard_buyer.php');
    exit;
}

$kategoriLabel = [
    'makanan'            => 'Makanan',
    'minuman'            => 'Minuman',
    'perlengkapan mandi' => 'Perlengkapan Mandi',
    'perlengkapan dapur' => 'Perlengkapan Dapur',
];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jumlah           = (int)($_POST['jumlah']           ?? 0);
    $metode           = trim($_POST['metode_pengambilan'] ?? '');
    $alamat_pengiriman = trim($_POST['alamat_pengiriman'] ?? '');

    if ($jumlah < 1) {
        $error = 'Jumlah pesanan minimal 1.';
    } elseif ($jumlah > (int)$produk['stok_produk']) {
        $error = 'Jumlah melebihi stok yang tersedia (' . (int)$produk['stok_produk'] . ').';
    } elseif (!in_array($metode, ['diantar', 'diambil'])) {
        $error = 'Metode pengambilan tidak valid.';
    } else {
        $total = $jumlah * (float)$produk['harga_produk'];

        // Insert order
        $ins = $pdo->prepare(
            'INSERT INTO pembelian
               (ID_user, ID_produk, ID_toko, nama_produk, jumlah, harga_satuan,
                total_harga, metode_pengambilan, alamat_pengiriman, status_pembelian)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "pending")'
        );
        $ins->execute([
            $ID_user,
            $ID_produk,
            $produk['ID_toko'],
            $produk['nama_produk'],
            $jumlah,
            $produk['harga_produk'],
            $total,
            $metode,
            $metode === 'diantar' ? ($alamat_pengiriman ?: null) : null,
        ]);

        // Reduce stock
        $upd = $pdo->prepare(
            'UPDATE produk SET stok_produk = stok_produk - ? WHERE ID_produk = ?'
        );
        $upd->execute([$jumlah, $ID_produk]);

        $success = 'Pesanan berhasil dibuat! Total: Rp ' . number_format($total, 0, ',', '.');

        // Reload product to reflect updated stock
        $stmt->execute([$ID_produk]);
        $produk = $stmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — <?= htmlspecialchars($produk['nama_produk']) ?></title>
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

      <a href="view_shop.php?id=<?= $produk['ID_toko'] ?>" class="btn-back">Kembali</a>

      <!-- Shop Info Bar -->
      <div class="store-info-bar">
        <?php if (!empty($produk['logo_toko']) && file_exists('../' . $produk['logo_toko'])): ?>
          <img class="store-info-bar__img"
               src="../<?= htmlspecialchars($produk['logo_toko']) ?>"
               alt="Logo Toko" />
        <?php else: ?>
          <img class="store-info-bar__img" src="../images/assets/store-profile.png" alt="Default Store" />
        <?php endif; ?>
        <div class="store-info-bar__details">
          <p class="store-info-bar__name"><?= htmlspecialchars($produk['nama_toko']) ?></p>
          <p class="store-info-bar__address"><?= htmlspecialchars($produk['alamat_toko'] ?? '-') ?></p>
          <p class="store-info-bar__city"><?= htmlspecialchars($produk['kota'] ?? '-') ?></p>
        </div>
      </div>

      <!-- Product Detail -->
      <div class="content-header-bar">
        <h2>Informasi Produk</h2>
      </div>
      <div class="content-panel">

        <?php if ($error):   ?><div class="alert alert-error"  style="margin-bottom:16px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success" style="margin-bottom:16px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div style="display:flex;gap:24px;flex-wrap:wrap;align-items:flex-start;">

          <!-- Product image -->
          <?php if (!empty($produk['gambar_produk']) && file_exists('../' . $produk['gambar_produk'])): ?>
            <img src="../<?= htmlspecialchars($produk['gambar_produk']) ?>"
                 alt="<?= htmlspecialchars($produk['nama_produk']) ?>"
                 style="width:clamp(160px,30%,260px);border-radius:20px;border:2px solid #561f00;
                        box-shadow:0 0 15px rgba(0,0,0,.15);flex-shrink:0;object-fit:cover;" />
          <?php else: ?>
            <div style="width:clamp(160px,30%,260px);min-height:180px;background:#e5c9a0;
                        border-radius:20px;border:2px solid #561f00;display:flex;
                        align-items:center;justify-content:center;font-size:56px;flex-shrink:0;">
              📦
            </div>
          <?php endif; ?>

          <!-- Product info + order form -->
          <div style="flex:1;min-width:200px;">
            <p style="font-size:clamp(16px,2vw,24px);font-weight:400;margin-bottom:6px;">
              <?= htmlspecialchars($produk['nama_produk']) ?>
            </p>
            <?php if (!empty($produk['deskripsi_produk'])): ?>
              <p style="font-size:clamp(13px,1.6vw,18px);font-weight:200;margin-bottom:4px;">
                <?= htmlspecialchars($produk['deskripsi_produk']) ?>
              </p>
            <?php endif; ?>
            <p style="font-size:clamp(13px,1.6vw,18px);font-weight:200;margin-bottom:4px;">
              Rp <?= number_format($produk['harga_produk'], 0, ',', '.') ?>
            </p>
            <p style="font-size:clamp(12px,1.4vw,16px);font-weight:300;margin-bottom:16px;">
              Stok tersedia: <?= (int)$produk['stok_produk'] ?>
            </p>
            <span class="pill" style="margin-bottom:16px;display:inline-block;">
              <?= htmlspecialchars($kategoriLabel[$produk['kategori']] ?? $produk['kategori']) ?>
            </span>

            <?php if ((int)$produk['stok_produk'] > 0): ?>
            <form action="view_product.php?id=<?= $ID_produk ?>" method="POST" id="order-form">
              <input type="hidden" name="ID_produk" value="<?= $ID_produk ?>" />

              <div class="form-group">
                <label class="form-label">Beli:</label>
                <div class="qty-control">
                  <button type="button" onclick="changeQty(-1)">−</button>
                  <input type="number" id="qty" name="jumlah"
                         value="1" min="1" max="<?= (int)$produk['stok_produk'] ?>" />
                  <button type="button" onclick="changeQty(1)">+</button>
                </div>
              </div>

              <div class="form-group">
                <label class="form-label" for="metode">Metode Pengambilan:</label>
                <select class="custom-select" id="metode" name="metode_pengambilan"
                        onchange="toggleAlamat(this.value)">
                  <option value="diantar">Diantar</option>
                  <option value="diambil">Diambil Sendiri</option>
                </select>
              </div>

              <!-- Delivery address — shown only when diantar -->
              <div class="form-group" id="alamatGroup">
                <label class="form-label" for="alamat_pengiriman">Alamat Pengiriman:</label>
                <input class="form-input" type="text" id="alamat_pengiriman"
                       name="alamat_pengiriman"
                       placeholder="Masukkan alamat pengiriman"
                       value="<?= htmlspecialchars($_POST['alamat_pengiriman'] ?? '') ?>" />
              </div>

              <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
                Pesan Sekarang!
              </button>
            </form>
            <?php else: ?>
              <p style="color:#dc2626;font-weight:600;font-size:15px;">
                Stok habis. Produk ini tidak tersedia untuk dipesan.
              </p>
            <?php endif; ?>

          </div>
        </div>
      </div>

    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

  <script>
    const maxStock = <?= (int)$produk['stok_produk'] ?>;

    function changeQty(delta) {
      const input = document.getElementById('qty');
      const newVal = Math.max(1, Math.min(maxStock, parseInt(input.value || 1) + delta));
      input.value = newVal;
    }

    function toggleAlamat(val) {
      document.getElementById('alamatGroup').style.display = (val === 'diantar') ? 'block' : 'none';
    }

    // Set initial state
    toggleAlamat(document.getElementById('metode').value);
  </script>

</body>
</html>
