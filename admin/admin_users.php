<?php
// admin/admin_users.php
// Admin panel — registered users split by role (penjual / pembeli).
// Supports tab switching: all / penjual / pembeli via ?tab= query param.
session_start();
require_once '../includes/koneksi.php';
require_once '../includes/pagination.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../login_admin.php');
    exit;
}

// ── Fetch all non-admin users with their profile photos ───────
$perPage      = 10;
$pagePenjual  = max(1, (int)($_GET['page_penjual'] ?? 1));
$pagePembeli  = max(1, (int)($_GET['page_pembeli'] ?? 1));

// Count totals
$totalPenjual = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'penjual'")->fetchColumn();
$totalPembeli = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'pembeli'")->fetchColumn();
$totalUsers   = $totalPenjual + $totalPembeli;

$totalPagesPenjual = max(1, (int)ceil($totalPenjual / $perPage));
$totalPagesPembeli = max(1, (int)ceil($totalPembeli / $perPage));
$pagePenjual = min($pagePenjual, $totalPagesPenjual);
$pagePembeli = min($pagePembeli, $totalPagesPembeli);
$offsetPenjual = ($pagePenjual - 1) * $perPage;
$offsetPembeli = ($pagePembeli - 1) * $perPage;

$penjualStmt = $pdo->query(
    "SELECT u.ID_user, u.username, u.created_at,
            pup.foto_profil
     FROM users u
     LEFT JOIN profil_user_penjual pup ON pup.ID_user = u.ID_user
     WHERE u.role = 'penjual'
     ORDER BY u.created_at DESC
     LIMIT $perPage OFFSET $offsetPenjual"
);
$penjualUsers = $penjualStmt->fetchAll();

$pembeliStmt = $pdo->query(
    "SELECT u.ID_user, u.username, u.created_at,
            pub.foto_profil
     FROM users u
     LEFT JOIN profil_user_pembeli pub ON pub.ID_user = u.ID_user
     WHERE u.role = 'pembeli'
     ORDER BY u.created_at DESC
     LIMIT $perPage OFFSET $offsetPembeli"
);
$pembeliUsers = $pembeliStmt->fetchAll();

// ── Active tab ────────────────────────────────────────────────
$validTabs = ['all', 'penjual', 'pembeli'];
$activeTab = in_array($_GET['tab'] ?? '', $validTabs) ? $_GET['tab'] : 'all';

// ── Helper: render a single user row ─────────────────────────
function renderUserRow(array $user, string $basePath = '..'): void {
    $photo = (!empty($user['foto_profil']) && file_exists($basePath . '/' . $user['foto_profil']))
        ? $basePath . '/' . htmlspecialchars($user['foto_profil'])
        : $basePath . '/images/assets/default-profile.png';
    echo '<div class="user-row">';
    echo '<img class="user-row__avatar" src="' . $photo . '" alt="' . htmlspecialchars($user['username']) . '" />';
    echo '<div>';
    echo '<p class="user-row__name">' . htmlspecialchars($user['username']) . '</p>';
    echo '<p style="font-size:11px;color:#9ca3af;">' . date('d M Y', strtotime($user['created_at'])) . '</p>';
    echo '</div>';
    echo '</div>';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Admin: Users</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/admin.css" rel="stylesheet" />
</head>
<body>

  <header class="admin-header">
    <div class="admin-header__brand">
      <div class="admin-header__logo-box"><span>LocalMart</span></div>
    </div>
    <nav class="admin-header__nav">
      <a href="dashboard_admin.php">Verifikasi</a>
      <a href="admin_users.php" class="active">Users</a>
      <a href="../includes/logout.php" class="logout"
         onclick="return confirm('Yakin ingin keluar?')">Keluar</a>
    </nav>
  </header>

  <main class="admin-main">

    <div class="admin-page-heading">
      <h1>Pengguna Terdaftar</h1>
    </div>

    <!-- Summary cards — total + per role -->
    <div class="summary-row">
      <div class="summary-card summary-card--total">
        <div>
          <div class="summary-card__count"><?= $totalUsers ?></div>
          <div class="summary-card__label">Total Pengguna</div>
        </div>
      </div>
      <div class="summary-card summary-card--penjual">
        <div>
          <div class="summary-card__count"><?= $totalPenjual ?></div>
          <div class="summary-card__label">Penjual</div>
        </div>
      </div>
      <div class="summary-card summary-card--pembeli">
        <div>
          <div class="summary-card__count"><?= $totalPembeli ?></div>
          <div class="summary-card__label">Pembeli</div>
        </div>
      </div>
    </div>

    <!-- Filter tabs -->
    <div class="filter-tabs">
      <a href="?tab=all"
         class="filter-tab <?= $activeTab === 'all'     ? 'active' : '' ?>">
        Semua
      </a>
      <a href="?tab=penjual"
         class="filter-tab <?= $activeTab === 'penjual' ? 'active' : '' ?>">
        Penjual
      </a>
      <a href="?tab=pembeli"
         class="filter-tab <?= $activeTab === 'pembeli' ? 'active' : '' ?>">
        Pembeli
      </a>
    </div>

    <?php if ($activeTab === 'all'): ?>
    <!-- ── ALL: two-column split view ───────────────────────── -->
    <div class="users-table-wrap">
      <div class="users-table-header">
        <div class="users-table-header__penjual">Penjual</div>
        <div class="users-table-header__pembeli">Pembeli</div>
      </div>
      <div class="users-columns">
        <div class="users-column">
          <?php if (empty($penjualUsers)): ?>
            <p class="users-empty">Belum ada penjual terdaftar.</p>
          <?php else: ?>
            <?php foreach ($penjualUsers as $u): ?><?php renderUserRow($u); ?><?php endforeach; ?>
          <?php endif; ?>
        </div>
        <div class="users-column">
          <?php if (empty($pembeliUsers)): ?>
            <p class="users-empty">Belum ada pembeli terdaftar.</p>
          <?php else: ?>
            <?php foreach ($pembeliUsers as $u): ?><?php renderUserRow($u); ?><?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php elseif ($activeTab === 'penjual'): ?>
    <!-- ── PENJUAL only: single full-width list ─────────────── -->
    <div class="users-table-wrap">
      <div class="users-table-header" style="grid-template-columns: 1fr;">
        <div class="users-table-header__penjual" style="border-radius: 25px 25px 0 0;">
          Penjual
        </div>
      </div>
      <div class="users-columns" style="grid-template-columns: 1fr;">
        <div class="users-column">
          <?php if (empty($penjualUsers)): ?>
            <p class="users-empty">Belum ada penjual terdaftar.</p>
          <?php else: ?>
            <?php foreach ($penjualUsers as $u): ?><?php renderUserRow($u); ?><?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <?php elseif ($activeTab === 'pembeli'): ?>
    <!-- ── PEMBELI only: single full-width list ─────────────── -->
    <div class="users-table-wrap">
      <div class="users-table-header" style="grid-template-columns: 1fr;">
        <div class="users-table-header__pembeli" style="border-radius: 25px 25px 0 0;">
          Pembeli
        </div>
      </div>
      <div class="users-columns" style="grid-template-columns: 1fr;">
        <div class="users-column">
          <?php if (empty($pembeliUsers)): ?>
            <p class="users-empty">Belum ada pembeli terdaftar.</p>
          <?php else: ?>
            <?php foreach ($pembeliUsers as $u): ?><?php renderUserRow($u); ?><?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </main>

  <footer class="admin-footer">
    <span>© LocalMart 2026</span>
  </footer>

</body>
</html>
