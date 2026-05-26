<?php
// register_pembeli.php — Registration page for Pembeli (buyers) only.
// Role is hardcoded to 'pembeli' — no role selector shown.
session_start();
require_once 'includes/koneksi.php';

if (isset($_SESSION['ID_user'])) {
    header('Location: pembeli/dashboard_buyer.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password tidak boleh kosong.';
    } elseif (strlen($username) < 3) {
        $error = 'Username minimal 3 karakter.';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter.';
    } else {
        $check = $pdo->prepare('SELECT ID_user FROM users WHERE username = ?');
        $check->execute([$username]);
        if ($check->fetch()) {
            $error = 'Username sudah digunakan. Silakan pilih username lain.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('INSERT INTO users (role, username, password) VALUES ("pembeli", ?, ?)');
            $stmt->execute([$username, $hash]);
            header('Location: login_pembeli.php?registered=1');
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
  <title>LocalMart — Daftar Pembeli</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="css/registration_screen.css" rel="stylesheet" />
</head>
<body>

  <div class="page-bg"></div>

  <header class="site-header">
    <a href="index.html" class="logo-box" style="text-decoration:none;">
      <span>LocalMart</span>
    </a>
  </header>

  <main class="reg-wrap">
    <div class="reg-card">

      <p class="reg-card__title">Buat Akun</p>

      <?php if ($error): ?>
        <p style="color:#dc2626;font-size:14px;text-align:center;margin-bottom:12px;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <?php if ($success): ?>
        <p style="color:#16a34a;font-size:14px;text-align:center;margin-bottom:12px;">
          <?= htmlspecialchars($success) ?>
          <a href="login_pembeli.php">Login di sini</a>.
        </p>
      <?php endif; ?>

      <form action="register_pembeli.php" method="POST" autocomplete="off">
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-input" type="text" id="username" name="username" autocomplete="username"
                 placeholder="Masukkan Username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="Masukkan Password (min. 6 karakter)" required />
        </div>

        <button type="submit" class="btn-register">Daftar sebagai Pembeli</button>
      </form>

      <p class="reg-footer">
        Sudah memiliki akun? <a href="login_pembeli.php">Login!</a>
      </p>

      <!-- Switch role hint -->
      <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:10px;">
        Ingin menjadi Mitra? <a href="register_penjual.php" style="color:#78350f;">Daftar sekarang!</a>
      </p>

    </div>
  </main>

  <footer class="site-footer">
    <span>© LocalMart, 2026</span>
  </footer>

</body>
</html>
