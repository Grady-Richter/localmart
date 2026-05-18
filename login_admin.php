<?php
// login_admin.php — Login page for Admin only.
// Not linked from the landing page — accessed directly via URL.
// Rejects any account that is not role === 'admin'.
session_start();
require_once 'includes/koneksi.php';

if (isset($_SESSION['ID_user']) && $_SESSION['role'] === 'admin') {
    header('Location: admin/dashboard_admin.php');
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
            // Role gate — admin only
            if ($user['role'] !== 'admin') {
                $error = 'Akses ditolak. Halaman ini hanya untuk Admin.';
            } else {
                $_SESSION['ID_user']  = $user['ID_user'];
                $_SESSION['role']     = $user['role'];
                $_SESSION['username'] = $username;
                header('Location: admin/dashboard_admin.php');
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
  <title>LocalMart — Login Admin</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="css/login_screen.css" rel="stylesheet" />
  <link href="css/admin.css" rel="stylesheet" />
</head>
<body>

  <div class="page-bg"></div>

  <header class="site-header">
    <a href="landing_page.php" class="logo-box" style="text-decoration:none;">
      <span>LocalMart</span>
    </a>
  </header>

  <main class="login-wrap">
    <div class="login-card">

      <!-- Role indicator -->
      <p style="text-align:center;font-size:12px;font-weight:600;color:#374151;
                background:#e5e7eb;border-radius:20px;padding:4px 14px;
                display:inline-block;margin:0 auto 12px;width:auto;">
        🔒 Login sebagai Admin
      </p>

      <p class="login-card__title">Admin Panel</p>

      <?php if ($error): ?>
        <p style="color:#dc2626;font-size:14px;text-align:center;margin-bottom:12px;">
          <?= htmlspecialchars($error) ?>
        </p>
      <?php endif; ?>

      <form action="login_admin.php" method="POST">
        <div class="form-group">
          <label class="form-label" for="username">Username</label>
          <input class="form-input" type="text" id="username" name="username"
                 placeholder="Masukkan Username Admin"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required />
        </div>

        <div class="form-group">
          <label class="form-label" for="password">Password</label>
          <input class="form-input" type="password" id="password" name="password"
                 placeholder="Masukkan Password" required />
        </div>

        <button type="submit" class="btn-login">Masuk ke Panel Admin</button>
      </form>

    </div>
  </main>

  <footer class="site-footer">
    <span>© LocalMart, 2026</span>
  </footer>

</body>
</html>
