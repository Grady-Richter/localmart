<?php
// penjual/shop_settings.php
// Displays and updates the seller's shop info (profil_toko).
// When accessed with ?resubmit=1 (from the rejection screen), saving the form
// also resets status_verifikasi back to 'menunggu' so the admin queue is updated.
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ../login_penjual.php');
    exit;
}

$ID_user    = $_SESSION['ID_user'];
// Carry the resubmit flag through GET and POST so it survives form submission
$isResubmit = isset($_GET['resubmit']) || isset($_POST['resubmit']);

// Fetch current shop data
$shopStmt = $pdo->prepare('SELECT * FROM profil_toko WHERE ID_user = ?');
$shopStmt->execute([$ID_user]);
$toko = $shopStmt->fetch();

if (!$toko) {
    header('Location: setup_shop.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_toko   = trim($_POST['nama_toko']    ?? '');
    $alamat_toko = trim($_POST['alamat_toko']  ?? '');
    $kota_toko   = trim($_POST['kota_toko']    ?? '');
    $deskripsi   = trim($_POST['deskripsi_toko'] ?? '');
    $logo        = $toko['logo_toko'];   // Keep existing logo by default

    if ($nama_toko === '') {
        $error = 'Nama toko tidak boleh kosong.';
    } else {
        // Handle new logo upload
        if (!empty($_FILES['logo_toko']['name'])) {
            $ext     = strtolower(pathinfo($_FILES['logo_toko']['name'], PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
            if (!in_array($ext, $allowed)) {
                $error = 'Format logo tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['logo_toko']['size'] > 2 * 1024 * 1024) {
                $error = 'Ukuran logo maksimal 2MB.';
            } else {
                // Delete old logo
                if ($logo && file_exists('../' . $logo)) unlink('../' . $logo);

                $uploadDir = '../uploads/logo_toko/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $filename = 'shop_' . $ID_user . '_' . time() . '.' . $ext;
                move_uploaded_file($_FILES['logo_toko']['tmp_name'], $uploadDir . $filename);
                $logo = 'uploads/logo_toko/' . $filename;
            }
        }

        if ($error === '') {
            if ($isResubmit) {
                // Resubmit after rejection: reset verification status back to menunggu
                $stmt = $pdo->prepare(
                    'UPDATE profil_toko
                     SET nama_toko = ?, deskripsi_toko = ?, logo_toko = ?,
                         alamat_toko = ?, kota = ?,
                         status_verifikasi = "menunggu", info_verifikasi = NULL
                     WHERE ID_user = ?'
                );
            } else {
                // Normal edit: leave verification status untouched
                $stmt = $pdo->prepare(
                    'UPDATE profil_toko
                     SET nama_toko = ?, deskripsi_toko = ?, logo_toko = ?,
                         alamat_toko = ?, kota = ?
                     WHERE ID_user = ?'
                );
            }

            $stmt->execute([
                $nama_toko,
                $deskripsi ?: null,
                $logo,
                $alamat_toko ?: null,
                $kota_toko   ?: null,
                $ID_user,
            ]);

            // After a resubmit, redirect straight to dashboard (waiting screen will show)
            if ($isResubmit) {
                header('Location: dashboard_seller.php');
                exit;
            }

            $success = 'Informasi toko berhasil diperbarui!';

            // Reload fresh data for normal edit
            $shopStmt->execute([$ID_user]);
            $toko = $shopStmt->fetch();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — <?= $isResubmit ? 'Ajukan Ulang Toko' : 'Pengaturan Toko' ?></title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/penjual.css" rel="stylesheet" />
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../landing_page.php" class="site-header__logo">
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
          <?= $isResubmit ? 'Perbaiki &amp; Ajukan Ulang' : 'Edit Informasi Toko' ?>
        </h2>

        <?php if ($isResubmit): ?>
          <div class="alert alert-error" style="margin-bottom:20px;">
            Toko kamu sebelumnya ditolak. Perbaiki informasi di bawah, lalu klik
            <strong>Ajukan Ulang</strong> untuk mengirim kembali ke admin.
          </div>
        <?php endif; ?>

        <?php if ($error):   ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <form action="shop_settings.php<?= $isResubmit ? '?resubmit=1' : '' ?>" method="POST" enctype="multipart/form-data">
          <?php if ($isResubmit): ?>
            <input type="hidden" name="resubmit" value="1" />
          <?php endif; ?>

          <!-- Shop Name -->
          <div class="form-group">
            <label class="form-label" for="nama">Nama</label>
            <input class="form-input" type="text" id="nama" name="nama_toko"
                   placeholder="Masukkan Nama Toko"
                   value="<?= htmlspecialchars($_POST['nama_toko'] ?? $toko['nama_toko']) ?>" required />
          </div>

          <!-- Address -->
          <div class="form-group">
            <label class="form-label" for="alamat">Alamat</label>
            <input class="form-input" type="text" id="alamat" name="alamat_toko"
                   placeholder="Masukkan Alamat Toko"
                   value="<?= htmlspecialchars($_POST['alamat_toko'] ?? $toko['alamat_toko'] ?? '') ?>" />
          </div>

          <!-- City -->
          <div class="form-group">
            <label class="form-label" for="kota">Kota</label>
            <input class="form-input" type="text" id="kota" name="kota_toko"
                   placeholder="Masukkan Kota Letak Toko"
                   value="<?= htmlspecialchars($_POST['kota_toko'] ?? $toko['kota'] ?? '') ?>" />
          </div>

          <!-- Description -->
          <div class="form-group">
            <label class="form-label" for="deskripsi">Deskripsi</label>
            <textarea class="form-input form-textarea" id="deskripsi" name="deskripsi_toko"
                      placeholder="Masukkan Deskripsi Toko"><?= htmlspecialchars($_POST['deskripsi_toko'] ?? $toko['deskripsi_toko'] ?? '') ?></textarea>
          </div>

          <!-- Logo -->
          <div class="form-group">
            <label class="form-label">Logo Toko</label>
            <label class="img-upload-box">
              <input type="file" name="logo_toko" accept="image/*"
                     onchange="previewLogo(this)" />
              <?php if (!empty($toko['logo_toko']) && file_exists('../' . $toko['logo_toko'])): ?>
                <span id="logoPreview">
                  <img src="../<?= htmlspecialchars($toko['logo_toko']) ?>"
                       class="preview-img" alt="Logo Toko" />
                </span>
              <?php else: ?>
                <img src="../images/assets/store-profile.png" alt="Default Store" class="preview-img" id="logoPreview" />
              <?php endif; ?>
              <span class="img-upload-box__label">Klik untuk ganti logo</span>
            </label>
          </div>

          <div style="display:flex;gap:12px;margin-top:8px;">
            <button type="submit" class="btn btn-primary" style="flex:1;">
              <?= $isResubmit ? 'Ajukan Ulang!' : 'Perbarui!' ?>
            </button>
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
    function previewLogo(input) {
      const target = document.getElementById('logoPreview');
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
  </script>

</body>
</html>
