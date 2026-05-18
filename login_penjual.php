<?php
// login_penjual.php — Login page for Penjual (sellers) only.
// Rejects any account that is not role === 'penjual'.
session_start();
require_once 'includes/koneksi.php';

if (isset($_SESSION['ID_user'])) {
    header('Location: ' . ($_SESSION['role'] === 'penjual' ? 'penjual/dashboard_seller.php' : 'pembeli/dashboard_buyer.php'));
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Username dan password tidak boleh kosong.';
    } else {
        $stmt = $pdo->prepare('SELECT ID_user, role, password FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            // Role gate — this page is for penjual only
            if ($user['role'] !== 'penjual') {
                $error = 'Akun ini bukan akun Penjual. Silakan gunakan halaman login yang sesuai.';
            } else {
                $_SESSION['ID_user']  = $user['ID_user'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['username'] = $username;

                // Check profile completion
                $check = $pdo->prepare('SELECT ID_user FROM profil_user_penjual WHERE ID_user = ?');
                $check->execute([$user['ID_user']]);
                if (!$check->fetch()) {
                    header('Location: penjual/first_time_seller.php');
                    exit;
                }

                // Check shop setup
                $shopCheck = $pdo->prepare('SELECT ID_toko FROM profil_toko WHERE ID_user = ?');
                $shopCheck->execute([$user['ID_user']]);
                if (!$shopCheck->fetch()) {
                    header('Location: penjual/setup_shop.php');
                    exit;
                }

                header('Location: penjual/dashboard_seller.php');
                exit;
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Login Penjual</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="css/login_screen.css" rel="stylesheet" />
</head>
<body>

  <div class="page-bg"></div>

  <header class="site-header">
    <a href="landing_page.html" class="logo-box" style="text-decoration:none;">
      <span>LocalMart</span>
    </a>
  </header>

  <main class="login-wrap">
    <div class="login-card">

      <!-- Role indicator -->
      <p style="text-align:center;font-size:12px;font-weight:600;color:#065f46;
                background:#d1fae5;border-radius:20px;padding:4px 14px;
                display:inline-block;margin:0 auto 12px;width:auto;">
        Login sebagai Penjual
      </p>

      <p class="login-card__title">Selamat Datang!</p>

      <?php if ($error): ?>
        <p style="color:#dc2626;font-size:14px;text-align:center;margin-bottom:12px;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <form action="login_penjual.php" method="POST">
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-input" type="text" id="username" name="username"
                 placeholder="Masukkan Username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="Masukkan Password" required />
        </div>

        <button type="submit" class="btn-login">Login ke LocalMart</button>
      </form>

      <p class="login-footer">
        Belum memiliki akun? <a href="register_penjual.php">Daftar sebagai Penjual!</a>
      </p>

      <!-- Switch role hint -->
      <p style="text-align:center;font-size:12px;color:#9ca3af;margin-top:10px;">
        Ingin berbelanja? <a href="login_pembeli.php" style="color:#78350f;">Login sebagai Pembeli</a>
      </p>

    </div>
  </main>

  <footer class="site-footer">
    <span>© LocalMart, 2026</span>
  </footer>

</body>
</html>
