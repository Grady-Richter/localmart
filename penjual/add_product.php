<?php
// penjual/add_product.php
// Adds a new product to the seller's shop. Inserts into `produk`.
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ../login_penjual.php');
    exit;
}

$ID_user = $_SESSION['ID_user'];

// Get the seller's shop ID
$shopStmt = $pdo->prepare('SELECT ID_toko FROM profil_toko WHERE ID_user = ?');
$shopStmt->execute([$ID_user]);
$toko = $shopStmt->fetch();

if (!$toko) {
    header('Location: setup_shop.php');
    exit;
}

$ID_toko = $toko['ID_toko'];
$error   = '';

// Fixed suggestions shown in the dropdown
$defaultKategori = ['makanan', 'minuman', 'perlengkapan mandi', 'perlengkapan dapur'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga       = trim($_POST['harga']       ?? '');
    $stok        = trim($_POST['stok']        ?? '');
    $deskripsi   = trim($_POST['deskripsi']   ?? '');
    $gambar      = null;

    // Hybrid category: use custom input if "lainnya" was chosen
    $kategori_select = trim($_POST['kategori_select'] ?? '');
    $kategori_custom = trim($_POST['kategori_custom'] ?? '');
    $kategori = ($kategori_select === 'lainnya') ? $kategori_custom : $kategori_select;
    $kategori = ucwords($kategori);

    // Validation
    if ($nama_produk === '') {
        $error = 'Nama produk tidak boleh kosong.';
    } elseif (!is_numeric($harga) || (float)$harga < 0) {
        $error = 'Harga tidak valid.';
    } elseif (!is_numeric($stok) || (int)$stok < 0) {
        $error = 'Stok tidak valid.';
    } elseif ($kategori === '') {
        $error = 'Kategori tidak boleh kosong.';
    } else {
        // Handle product image upload
        if (!empty($_FILES['foto_produk']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['foto_produk']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto_produk']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 2MB.';
            } else {
                $uploadDir = '../uploads/produk/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'produk_' . $ID_toko . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['foto_produk']['tmp_name'], $uploadDir . $filename);
                $gambar = 'uploads/produk/' . $filename;
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                'INSERT INTO produk (ID_toko, nama_produk, deskripsi_produk, gambar_produk, stok_produk, harga_produk, kategori)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $ID_toko,
                $nama_produk,
                $deskripsi ?: null,
                $gambar,
                (int)$stok,
                (float)$harga,
                $kategori,
            ]);
            header('Location: dashboard_seller.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Tambah Produk</title>
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
        <a href="dashboard_seller.php">Toko Anda</a>
        <a href="orders_seller.php">Pesanan</a>
        <a href="profile_settings_seller.php">Profil</a>
      </nav>
    </header>

    <main class="page-content">
      <div class="content-card" style="max-width:680px;">
        <h2 style="font-size:clamp(18px,2.5vw,26px);font-weight:600;color:#f97316;text-align:center;margin-bottom:24px;text-shadow:0 0 4px rgba(0,0,0,.25);">
          Tambahkan Produkmu!
        </h2>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="add_product.php" method="POST" enctype="multipart/form-data">

          <div class="form-group">
            <label class="form-label">Foto Produk</label>
            <label class="img-upload-box">
              <input type="file" name="foto_produk" accept="image/*"
                     onchange="previewImage(this, 'previewAdd')" />
              <span class="img-upload-box__icon" id="previewAdd">🖼️</span>
              <span class="img-upload-box__label">Tambahkan Gambar!</span>
            </label>
          </div>

          <div class="form-group">
            <label class="form-label" for="nama">Nama</label>
            <input class="form-input" type="text" id="nama" name="nama_produk"
                   placeholder="Masukkan Nama Produk"
                   value="<?= htmlspecialchars($_POST['nama_produk'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label" for="harga">Harga (Rp)</label>
            <input class="form-input" type="number" id="harga" name="harga"
                   placeholder="Masukkan Harga" min="0" step="50"
                   value="<?= htmlspecialchars($_POST['harga'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label" for="stok">Stok</label>
            <input class="form-input" type="number" id="stok" name="stok"
                   placeholder="Masukkan Stok" min="0"
                   value="<?= htmlspecialchars($_POST['stok'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label" for="kategori_select">Kategori</label>
            <?php
              $postedSelect = $_POST['kategori_select'] ?? '';
              $postedCustom = $_POST['kategori_custom'] ?? '';
            ?>
            <select class="form-input custom-select" id="kategori_select"
                    name="kategori_select" onchange="toggleKategori(this.value)">
              <option value="" disabled <?= $postedSelect === '' ? 'selected' : '' ?>>Pilih Kategori</option>
              <option value="makanan"            <?= $postedSelect === 'makanan'            ? 'selected' : '' ?>>Makanan</option>
              <option value="minuman"            <?= $postedSelect === 'minuman'            ? 'selected' : '' ?>>Minuman</option>
              <option value="perlengkapan mandi" <?= $postedSelect === 'perlengkapan mandi' ? 'selected' : '' ?>>Perlengkapan Mandi</option>
              <option value="perlengkapan dapur" <?= $postedSelect === 'perlengkapan dapur' ? 'selected' : '' ?>>Perlengkapan Dapur</option>
              <option value="lainnya"            <?= $postedSelect === 'lainnya'            ? 'selected' : '' ?>>Lainnya...</option>
            </select>
            <input class="form-input" type="text" id="kategori_custom"
                   name="kategori_custom"
                   placeholder="Masukkan kategori kustom"
                   value="<?= htmlspecialchars($postedCustom) ?>"
                   style="margin-top:8px;display:<?= $postedSelect === 'lainnya' ? 'block' : 'none' ?>;" />
          </div>

          <div class="form-group">
            <label class="form-label" for="deskripsi">Deskripsi</label>
            <textarea class="form-input form-textarea" id="deskripsi" name="deskripsi"
                      placeholder="Masukkan Deskripsi Produk"><?= htmlspecialchars($_POST['deskripsi'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">Tambahkan!</button>
            <a href="dashboard_seller.php" class="btn btn-secondary" style="flex:0 0 auto;">Kembali</a>
          </div>

        </form>
      </div>
    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

  <script>
    function previewImage(input, targetId) {
      const target = document.getElementById(targetId);
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          target.innerHTML = '';
          const img = document.createElement('img');
          img.src = e.target.result;
          img.className = 'preview-img';
          target.appendChild(img);
        };
        reader.readAsDataURL(input.files[0]);
      }
    }

    function toggleKategori(val) {
      const custom = document.getElementById('kategori_custom');
      custom.style.display = (val === 'lainnya') ? 'block' : 'none';
      if (val !== 'lainnya') custom.value = '';
    }
  </script>

</body>
</html>