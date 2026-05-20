<?php
// penjual/first_time_seller.php
// First-time profile setup for sellers.
// Inserts a row into profil_user_penjual, then sends to setup_shop.php.
session_start();
require_once '../includes/koneksi.php';

// Only accessible to logged-in sellers
if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ../login_penjual.php');
    exit;
}

// If profile already exists, skip
$check = $pdo->prepare('SELECT ID_user FROM profil_user_penjual WHERE ID_user = ?');
$check->execute([$_SESSION['ID_user']]);
if ($check->fetch()) {
    header('Location: dashboard_seller.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telepon = trim($_POST['telepon'] ?? '');
    $alamat  = trim($_POST['alamat']  ?? '');
    $foto    = null;

    // Handle profile photo upload
    if (!empty($_FILES['foto_profil']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (!in_array($ext, $allowed)) {
            $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 2MB.';
        } else {
            $uploadDir = '../uploads/profil_penjual/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'seller_' . $_SESSION['ID_user'] . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['foto_profil']['tmp_name'], $uploadDir . $filename);
            $foto = 'uploads/profil_penjual/' . $filename;
        }
    }

    if ($error === '') {
        $stmt = $pdo->prepare(
            'INSERT INTO profil_user_penjual (ID_user, foto_profil, nomor_telepon, alamat)
             VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([$_SESSION['ID_user'], $foto, $telepon, $alamat]);
        // Next step: set up the shop
        header('Location: setup_shop.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Setup Profil Penjual</title>
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
      <div class="content-card">
        <p style="text-align:center;font-size:clamp(14px,1.6vw,18px);text-shadow:0 0 4px rgba(0,0,0,.25);">Sebelumnya,</p>
        <h2 style="font-size:clamp(18px,2.5vw,26px);font-weight:600;color:#f97316;text-align:center;margin-bottom:24px;text-shadow:0 0 4px rgba(0,0,0,.25);">
          Beritahu Kami Lebih Lanjut!
        </h2>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form action="first_time_seller.php" method="POST" enctype="multipart/form-data">

          <div class="profile-photo-wrap">
            <span class="profile-photo-label">Foto Profil</span>
            <label class="photo-upload">
              <input type="file" name="foto_profil" accept="image/*" onchange="previewPhoto(this)" />
              <img id="photoPreview" src="../images/assets/default-profile.png" alt="Default Profile" style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;" />
            </label>
          </div>

          <div class="form-group">
            <label class="form-label" for="telepon">Masukkan No. Telepon</label>
            <input class="form-input" type="tel" id="telepon" name="telepon"
                   placeholder="+62-xxx-xxx-xxx"
                   value="<?= htmlspecialchars($_POST['telepon'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label" for="alamat">Alamat</label>
            <input class="form-input" type="text" id="alamat" name="alamat"
                   placeholder="Masukkan Alamat"
                   value="<?= htmlspecialchars($_POST['alamat'] ?? '') ?>" />
          </div>

          <button type="submit" class="btn btn-primary btn-full" style="margin-top:8px;">
            Lanjut!
          </button>
        </form>
      </div>
    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

  <script>
    function previewPhoto(input) {
      if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
          const old = document.getElementById('photoPreview');
          const img = document.createElement('img');
          img.id = 'photoPreview';
          img.src = e.target.result;
          img.style.cssText = 'width:100%;height:100%;object-fit:cover;position:absolute;inset:0;';
          old.replaceWith(img);
        };
        reader.readAsDataURL(input.files[0]);
      }
    }
  </script>

</body>
</html>
