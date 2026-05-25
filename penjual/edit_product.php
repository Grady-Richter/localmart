<?php
// penjual/edit_product.php
// Loads existing product data (READ) and saves changes (UPDATE) to `produk`.
// Requires ?id=<ID_produk> in the URL.
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

// Get the product ID from URL — must belong to this shop
$id_produk = (int)($_GET['id'] ?? $_POST['id_produk'] ?? 0);
if (!$id_produk) {
    header('Location: dashboard_seller.php');
    exit;
}

$produkStmt = $pdo->prepare('SELECT * FROM produk WHERE ID_produk = ? AND ID_toko = ?');
$produkStmt->execute([$id_produk, $ID_toko]);
$produk = $produkStmt->fetch();

if (!$produk) {
    // Product not found or doesn't belong to this seller
    header('Location: dashboard_seller.php');
    exit;
}

$defaultKategori = ['makanan', 'minuman', 'perlengkapan mandi', 'perlengkapan dapur'];
$error           = '';
$success         = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_produk = trim($_POST['nama_produk'] ?? '');
    $harga       = trim($_POST['harga']       ?? '');
    $stok        = trim($_POST['stok']        ?? '');
    $deskripsi   = trim($_POST['deskripsi']   ?? '');
    $gambar      = $produk['gambar_produk'];

    // Hybrid category
    $kategori_select = trim($_POST['kategori_select'] ?? '');
    $kategori_custom = trim($_POST['kategori_custom'] ?? '');
    $kategori = ($kategori_select === 'lainnya') ? $kategori_custom : $kategori_select;
    $kategori = ucwords($kategori);

    if ($nama_produk === '') {
        $error = 'Nama produk tidak boleh kosong.';
    } elseif (!is_numeric($harga) || (float)$harga < 0) {
        $error = 'Harga tidak valid.';
    } elseif (!is_numeric($stok) || (int)$stok < 0) {
        $error = 'Stok tidak valid.';
    } elseif ($kategori === '') {
        $error = 'Kategori tidak boleh kosong.';
    } else {
        // Handle new image upload (optional — only if a file was submitted)
        if (!empty($_FILES['foto_produk']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['foto_produk']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto_produk']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran gambar maksimal 2MB.';
            } else {
                // Delete old image file if it exists
                if ($gambar && file_exists('../' . $gambar)) {
                    unlink('../' . $gambar);
                }
                $uploadDir = '../uploads/produk/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'produk_' . $ID_toko . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['foto_produk']['tmp_name'], $uploadDir . $filename);
                $gambar = 'uploads/produk/' . $filename;
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                'UPDATE produk
                 SET nama_produk = ?, deskripsi_produk = ?, gambar_produk = ?,
                     stok_produk = ?, harga_produk = ?, kategori = ?
                 WHERE ID_produk = ? AND ID_toko = ?'
            );
            $stmt->execute([
                $nama_produk,
                $deskripsi ?: null,
                $gambar,
                (int)$stok,
                (float)$harga,
                $kategori,
                $id_produk,
                $ID_toko,
            ]);
            $success = 'Produk berhasil diperbarui!';

            // Reload updated data
            $produkStmt->execute([$id_produk, $ID_toko]);
            $produk = $produkStmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Edit Produk</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/penjual.css" rel="stylesheet" />
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../landing_page.html" class="site-header__logo">
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
          Edit Produk
        </h2>

        <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form action="edit_product.php?id=<?= $id_produk ?>" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="id_produk" value="<?= $id_produk ?>" />

          <div class="form-group">
            <label class="form-label">Ganti Foto</label>
            <label class="img-upload-box">
              <input type="file" name="foto_produk" accept="image/*"
                     onchange="previewImage(this, 'previewEdit')" />
              <?php if ($produk['gambar_produk'] && file_exists('../' . $produk['gambar_produk'])): ?>
                <span id="previewEdit">
                  <img src="../<?= htmlspecialchars($produk['gambar_produk']) ?>"
                       class="preview-img" alt="Foto Produk" />
                </span>
              <?php else: ?>
                <span class="img-upload-box__icon" id="previewEdit">🖼️</span>
              <?php endif; ?>
              <span class="img-upload-box__label">Klik untuk ganti gambar</span>
            </label>
          </div>

          <div class="form-group">
            <label class="form-label" for="nama">Nama</label>
            <input class="form-input" type="text" id="nama" name="nama_produk"
                   placeholder="Masukkan Nama Produk"
                   value="<?= htmlspecialchars($_POST['nama_produk'] ?? $produk['nama_produk']) ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label" for="harga">Harga (Rp)</label>
            <input class="form-input" type="number" id="harga" name="harga"
                   placeholder="Masukkan Harga" min="0" step="50"
                   value="<?= htmlspecialchars($_POST['harga'] ?? $produk['harga_produk']) ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label" for="stok">Stok</label>
            <input class="form-input" type="number" id="stok" name="stok"
                   placeholder="Masukkan Stok" min="0"
                   value="<?= htmlspecialchars($_POST['stok'] ?? $produk['stok_produk']) ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label" for="kategori_select">Kategori</label>
            <?php
              // Determine if existing category is one of the defaults or custom
              $currentKategori = $_POST['kategori_select'] ?? $produk['kategori'];
              $currentCustom   = $_POST['kategori_custom'] ?? '';
              $isCustom        = !in_array($currentKategori, $defaultKategori);
              if ($isCustom && $currentCustom === '') {
                  $currentCustom   = $currentKategori;
                  $currentKategori = 'lainnya';
              }
            ?>
            <select class="form-input custom-select" id="kategori_select"
                    name="kategori_select" onchange="toggleKategori(this.value)">
              <option value="makanan"            <?= $currentKategori === 'makanan'            ? 'selected' : '' ?>>Makanan</option>
              <option value="minuman"            <?= $currentKategori === 'minuman'            ? 'selected' : '' ?>>Minuman</option>
              <option value="perlengkapan mandi" <?= $currentKategori === 'perlengkapan mandi' ? 'selected' : '' ?>>Perlengkapan Mandi</option>
              <option value="perlengkapan dapur" <?= $currentKategori === 'perlengkapan dapur' ? 'selected' : '' ?>>Perlengkapan Dapur</option>
              <option value="lainnya"            <?= $currentKategori === 'lainnya'            ? 'selected' : '' ?>>Lainnya...</option>
            </select>
            <input class="form-input" type="text" id="kategori_custom"
                   name="kategori_custom"
                   placeholder="Masukkan kategori kustom"
                   value="<?= htmlspecialchars($currentCustom) ?>"
                   style="margin-top:8px;display:<?= $currentKategori === 'lainnya' ? 'block' : 'none' ?>;" />
          </div>

          <div class="form-group">
            <label class="form-label" for="deskripsi">Deskripsi</label>
            <textarea class="form-input form-textarea" id="deskripsi" name="deskripsi"
                      placeholder="Masukkan Deskripsi Produk"><?= htmlspecialchars($_POST['deskripsi'] ?? $produk['deskripsi_produk'] ?? '') ?></textarea>
          </div>

          <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">Perbarui!</button>
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