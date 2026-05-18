<?php
// pembeli/profile_buyer.php
// Displays and updates the buyer's personal profile (profil_user_pembeli).
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login_pembeli.php');
    exit;
}

$ID_user = $_SESSION['ID_user'];

// Fetch current profile
$profStmt = $pdo->prepare('SELECT * FROM profil_user_pembeli WHERE ID_user = ?');
$profStmt->execute([$ID_user]);
$profil = $profStmt->fetch();

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $telepon = trim($_POST['telepon'] ?? '');
    $alamat  = trim($_POST['alamat']  ?? '');
    $kota    = trim($_POST['kota']    ?? '');
    $kodepos = trim($_POST['kodepos'] ?? '');
    $foto    = $profil['foto_profil'] ?? null;

    // Handle new photo upload
    if (!empty($_FILES['foto_profil']['name'])) {
        $ext     = strtolower(pathinfo($_FILES['foto_profil']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','webp','gif'];
        if (!in_array($ext, $allowed)) {
            $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
        } elseif ($_FILES['foto_profil']['size'] > 2 * 1024 * 1024) {
            $error = 'Ukuran foto maksimal 2MB.';
        } else {
            if ($foto && file_exists('../' . $foto)) unlink('../' . $foto);
            $uploadDir = '../uploads/profil_pembeli/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'buyer_' . $ID_user . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['foto_profil']['tmp_name'], $uploadDir . $filename);
            $foto = 'uploads/profil_pembeli/' . $filename;
        }
    }

    if ($error === '') {
        if ($profil) {
            $stmt = $pdo->prepare(
                'UPDATE profil_user_pembeli
                 SET foto_profil = ?, nomor_telepon = ?, alamat = ?, kota = ?, kode_pos = ?
                 WHERE ID_user = ?'
            );
            $stmt->execute([$foto, $telepon ?: null, $alamat ?: null,
                            $kota ?: null, $kodepos ?: null, $ID_user]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO profil_user_pembeli (ID_user, foto_profil, nomor_telepon, alamat, kota, kode_pos)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$ID_user, $foto, $telepon ?: null,
                            $alamat ?: null, $kota ?: null, $kodepos ?: null]);
        }
        $success = 'Profil berhasil disimpan!';
        $profStmt->execute([$ID_user]);
        $profil = $profStmt->fetch();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Profil Pembeli</title>
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
        <a href="profile_buyer.php" class="active">Profil</a>
      </nav>
    </header>

    <main class="page-content">
      <div class="content-card">
        <h2 style="font-size:clamp(18px,2.5vw,26px);font-weight:600;color:#f97316;text-align:center;margin-bottom:24px;text-shadow:0 4px 4px rgba(0,0,0,.25);">
          Pengaturan Profil
        </h2>

        <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form action="profile_buyer.php" method="POST" enctype="multipart/form-data">

          <div class="profile-photo-wrap">
            <span class="profile-photo-label">Foto Profil</span>
            <label class="photo-upload">
              <input type="file" name="foto_profil" accept="image/*"
                     onchange="previewPhoto(this)" />
              <?php if (!empty($profil['foto_profil']) && file_exists('../' . $profil['foto_profil'])): ?>
                <img id="photoPreview"
                     src="../<?= htmlspecialchars($profil['foto_profil']) ?>"
                     style="width:100%;height:100%;object-fit:cover;position:absolute;inset:0;" />
              <?php else: ?>
                <div class="photo-upload__icon" id="photoPreview">📷</div>
              <?php endif; ?>
            </label>
          </div>

          <div class="form-group">
            <label class="form-label" for="telepon">Edit No. Telepon</label>
            <input class="form-input" type="tel" id="telepon" name="telepon"
                   placeholder="+62-xxx-xxx-xxx"
                   value="<?= htmlspecialchars($_POST['telepon'] ?? $profil['nomor_telepon'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label" for="alamat">Alamat</label>
            <input class="form-input" type="text" id="alamat" name="alamat"
                   placeholder="Edit Alamat"
                   value="<?= htmlspecialchars($_POST['alamat'] ?? $profil['alamat'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label" for="kota">Nama Kota</label>
            <input class="form-input" type="text" id="kota" name="kota"
                   placeholder="Edit Nama Kota"
                   value="<?= htmlspecialchars($_POST['kota'] ?? $profil['kota'] ?? '') ?>" />
          </div>

          <div class="form-group">
            <label class="form-label" for="kodepos">Kode Pos</label>
            <input class="form-input" type="text" id="kodepos" name="kodepos"
                   placeholder="Edit Kode Pos"
                   value="<?= htmlspecialchars($_POST['kodepos'] ?? $profil['kode_pos'] ?? '') ?>" />
          </div>

          <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">Simpan</button>
            <a href="../includes/logout.php"
               class="btn btn-danger"
               style="flex:0 0 auto;padding:12px 20px;font-size:clamp(14px,1.8vw,18px);"
               onclick="return confirm('Yakin ingin logout?')">Logout</a>
          </div>

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
