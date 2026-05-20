<?php
// penjual/setup_shop.php
// Creates the seller's shop for the first time.
// Inserts a row into profil_toko, then redirects to dashboard_seller.php.
session_start();
require_once '../includes/koneksi.php';

// Only accessible to logged-in sellers
if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ../login_penjual.php');
    exit;
}

// If shop already exists, skip to dashboard
$check = $pdo->prepare('SELECT ID_toko FROM profil_toko WHERE ID_user = ?');
$check->execute([$_SESSION['ID_user']]);
if ($check->fetch()) {
    header('Location: dashboard_seller.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_toko    = trim($_POST['nama_toko']    ?? '');
    $alamat_toko  = trim($_POST['alamat_toko']  ?? '');
    $kota_toko    = trim($_POST['kota_toko']    ?? '');
    $deskripsi    = trim($_POST['deskripsi_toko'] ?? '');
    $logo         = null;

    if ($nama_toko === '') {
        $error = 'Nama toko tidak boleh kosong.';
    } else {
        // Handle logo upload
        if (!empty($_FILES['logo_toko']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['logo_toko']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                $error = 'Format logo tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['logo_toko']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran logo maksimal 2MB.';
            } else {
                $uploadDir = '../uploads/logo_toko/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'shop_' . $_SESSION['ID_user'] . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['logo_toko']['tmp_name'], $uploadDir . $filename);
                $logo = 'uploads/logo_toko/' . $filename;
            }
        }

        if ($error === '') {
            $stmt = $pdo->prepare(
                'INSERT INTO profil_toko (ID_user, nama_toko, deskripsi_toko, logo_toko, alamat_toko, kota)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $_SESSION['ID_user'],
                $nama_toko,
                $deskripsi ?: null,
                $logo,
                $alamat_toko ?: null,
                $kota_toko ?: null,
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
  <title>LocalMart — Setup Toko</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/penjual.css" rel="stylesheet" />
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../landing_page.html" class="site-header__logo">
        <div class="site-header__logo-box"><span>LocalMart</span></div>
      </a>
    </header>

    <main class="page-content">
      <div class="content-card" style="max-width:680px;">
        <h2 style="font-size:clamp(18px,2.5vw,26px);font-weight:600;color:#f97316;text-align:center;margin-bottom:24px;text-shadow:0 0 4px rgba(0,0,0,.25);">
          Mari Setup Toko Milikmu!
        </h2>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="setup_shop.php" method="POST" enctype="multipart/form-data">

          <div class="form-group">
            <label class="form-label" for="nama">Nama</label>
            <input class="form-input" type="text" id="nama" name="nama_toko"
                   placeholder="Masukkan Nama Toko"
                   value="<?= htmlspecialchars($_POST['nama_toko'] ?? '') ?>" required />
          </div>

          <div class="form-group">
            <label class="form-label" for="alamat">Alamat</label>
            <input class="form-input" type="text" id="alamat" name="alamat_toko"
                   placeholder="Masukkan Alamat Toko"
                   value="<?= htmlspecialchars($_POST['alamat_toko'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label" for="kota">Kota</label>
            <input class="form-input" type="text" id="kota" name="kota_toko"
                   placeholder="Masukkan Kota Letak Toko"
                   value="<?= htmlspecialchars($_POST['kota_toko'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label" for="deskripsi">Deskripsi</label>
            <textarea class="form-input form-textarea" id="deskripsi" name="deskripsi_toko"
                      placeholder="Masukkan Deskripsi Toko"><?= htmlspecialchars($_POST['deskripsi_toko'] ?? '') ?></textarea>
          </div>

          <div class="form-group">
            <label class="form-label">Logo Toko</label>
            <label class="img-upload-box">
              <input type="file" name="logo_toko" accept="image/*" />
              <img src="../images/assets/store-profile.png" alt="Default Store" class="preview-img" id="logoPreview" />
              <span class="img-upload-box__label">Tambahkan Gambar!</span>
            </label>
          </div>

          <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
            Selesai!
          </button>
        </form>
      </div>
    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

</body>
</html>
