<?php
// register_penjual.php — Registration page for Penjual (sellers) only.
// Role is hardcoded to 'penjual' — no role selector shown.
session_start();
require_once 'includes/koneksi.php';

if (isset($_SESSION['ID_user'])) {
    header('Location: penjual/dashboard_seller.php');
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
            $stmt = $pdo->prepare('INSERT INTO users (role, username, password) VALUES ("penjual", ?, ?)');
            $stmt->execute([$username, $hash]);
            header('Location: login_penjual.php?registered=1');
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
  <title>LocalMart — Daftar Penjual</title>
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
          <a href="login_penjual.php">Login di sini</a>.
        </p>
      <?php endif; ?>

      <form action="register_penjual.php" method="POST" autocomplete="off">
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

        <button type="submit" class="btn-register">Daftar sebagai Penjual</button>
      </form>

      <p class="reg-footer">
        Sudah memiliki akun? <a href="login_penjual.php">Login disini!</a>
      </p>
    </div>
  </main>

  <footer class="site-footer">
    <span>© LocalMart, 2026</span>
  </footer>

</body>
</html>
