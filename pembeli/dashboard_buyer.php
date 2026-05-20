<?php
// pembeli/dashboard_buyer.php
// Main buyer dashboard. Lists verified shops from the DB with:
//  - Search by shop name or city
//  - Category filter (shows shops that carry that category)
//  - 10 shops per page with pagination
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login_pembeli.php');
    exit;
}

// ── Parameters ────────────────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;

$validKategori = ['makanan', 'minuman', 'perlengkapan mandi', 'perlengkapan dapur'];
$kategoriLabel = [
    'makanan'            => 'Makanan',
    'minuman'            => 'Minuman',
    'perlengkapan mandi' => 'Perlengkapan Mandi',
    'perlengkapan dapur' => 'Perlengkapan Dapur',
];

// ── Build WHERE ───────────────────────────────────────────────
$params = [];
$where  = ['pt.status_verifikasi = "diterima"'];

if ($search !== '') {
    $where[]  = '(pt.nama_toko LIKE ? OR pt.kota LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($kategori && in_array($kategori, $validKategori)) {
    $where[]  = 'EXISTS (SELECT 1 FROM produk p WHERE p.ID_toko = pt.ID_toko AND p.kategori = ?)';
    $params[] = $kategori;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ── Count total for pagination ────────────────────────────────
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM profil_toko pt $whereSql");
$countStmt->execute($params);
$totalShops = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalShops / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

// ── Fetch page of shops ───────────────────────────────────────
$shopStmt = $pdo->prepare(
    "SELECT pt.* FROM profil_toko pt $whereSql ORDER BY pt.nama_toko ASC LIMIT $perPage OFFSET $offset"
);
$shopStmt->execute($params);
$shops = $shopStmt->fetchAll();

// Fetch categories per shop in one query
$shopCategories = [];
if (!empty($shops)) {
    $shopIds      = array_column($shops, 'ID_toko');
    $placeholders = implode(',', array_fill(0, count($shopIds), '?'));
    $catStmt      = $pdo->prepare(
        "SELECT ID_toko, kategori FROM produk WHERE ID_toko IN ($placeholders) GROUP BY ID_toko, kategori"
    );
    $catStmt->execute($shopIds);
    foreach ($catStmt->fetchAll() as $row) {
        $shopCategories[$row['ID_toko']][] = $row['kategori'];
    }
}

// ── URL helper (preserves other params) ──────────────────────
function buildUrl(array $overrides = []): string {
    // Start from current GET params, then apply overrides.
    // Overrides are applied unconditionally — including empty strings —
    // so that passing ['kategori' => ''] correctly clears the filter.
    $base = [
        'search'   => $_GET['search']   ?? '',
        'kategori' => $_GET['kategori'] ?? '',
        'page'     => (string)($_GET['page'] ?? '1'),
    ];
    $merged = array_merge($base, array_map('strval', $overrides));
    // Remove keys with empty values so the URL stays clean
    $merged = array_filter($merged, fn($v) => $v !== '');
    // Drop page=1 from URL to keep it clean
    if (($merged['page'] ?? '') === '1') unset($merged['page']);
    return 'dashboard_buyer.php' . ($merged ? '?' . http_build_query($merged) : '');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Beranda Pembeli</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/pembeli.css" rel="stylesheet" />
  <style>
    .pagination {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
      margin-top: 24px;
      flex-wrap: wrap;
    }
    .pagination a, .pagination span {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 36px;
      height: 36px;
      border-radius: 8px;
      font-size: 14px;
      font-weight: 500;
      text-decoration: none;
      border: 2px solid transparent;
      transition: all 0.15s;
      padding: 0 10px;
    }
    .pagination a         { background:#fff; border-color:rgba(81,40,6,0.3); color:#78350f; }
    .pagination a:hover   { background:#f97316; border-color:#f97316; color:#fff; }
    .pagination .current  { background:#78350f; border-color:#78350f; color:#fff; font-weight:700; }
    .pagination .disabled { background:#f3f4f6; border-color:#e5e7eb; color:#9ca3af; cursor:default; }
  </style>
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../landing_page.html" class="site-header__logo">
        <div class="site-header__logo-box"><span>LocalMart</span></div>
      </a>
      <nav class="site-header__nav">
        <a href="dashboard_buyer.php" class="active">Beranda</a>
        <a href="orders_buyer.php">Pesanan</a>
        <a href="profile_buyer.php">Profil</a>
      </nav>
    </header>

    <main class="page-content">

      <!-- Search -->
      <form action="dashboard_buyer.php" method="GET">
        <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori) ?>" />
        <div class="search-bar">
          <input type="text" name="search"
                 placeholder="Cari nama toko atau kota..."
                 value="<?= htmlspecialchars($search) ?>" />
          <button type="submit"
                  style="background:none;border:none;cursor:pointer;font-size:18px;padding:0;">🔍</button>
        </div>
      </form>

      <!-- Category filter -->
      <div class="filter-row" style="margin-bottom:16px;">
        <span class="filter-row__label">Kategori:</span>
        <a href="<?= buildUrl(['kategori' => '', 'page' => '1']) ?>"
           class="pill <?= $kategori === '' ? 'active' : '' ?>"
           style="text-decoration:none;">Semua</a>
        <?php foreach ($validKategori as $k): ?>
          <a href="<?= buildUrl(['kategori' => $k, 'page' => '1']) ?>"
             class="pill <?= $kategori === $k ? 'active' : '' ?>"
             style="text-decoration:none;">
            <?= htmlspecialchars($kategoriLabel[$k]) ?>
          </a>
        <?php endforeach; ?>
      </div>

      <!-- Heading + count -->
      <div class="section-heading" style="margin-bottom:16px;
           display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <h2>Daftar Toko</h2>
        <span style="font-size:13px;color:#fde8c8;font-weight:300;">
          <?= $totalShops ?> toko ditemukan
        </span>
      </div>

      <!-- Shop list -->
      <?php if (empty($shops)): ?>
        <div style="text-align:center;padding:48px 20px;color:#9ca3af;">
          <div style="font-size:48px;margin-bottom:12px;">🏪</div>
          <p>Tidak ada toko yang ditemukan<?= $search ? ' untuk "' . htmlspecialchars($search) . '"' : '' ?>.</p>
          <?php if ($search || $kategori): ?>
            <a href="dashboard_buyer.php"
               style="display:inline-block;margin-top:12px;color:#f97316;font-weight:600;text-decoration:none;">
              Tampilkan semua toko
            </a>
          <?php endif; ?>
        </div>

      <?php else: ?>

        <?php foreach ($shops as $shop): ?>
        <div class="list-card">
          <?php if (!empty($shop['logo_toko']) && file_exists('../' . $shop['logo_toko'])): ?>
            <img class="list-card__thumb"
                 src="../<?= htmlspecialchars($shop['logo_toko']) ?>"
                 alt="<?= htmlspecialchars($shop['nama_toko']) ?>" />
          <?php else: ?>
            <img class="list-card__thumb" src="../images/assets/store-profile.png" alt="Default Store" />
          <?php endif; ?>

          <div class="list-card__info">
            <p class="list-card__name"><?= htmlspecialchars($shop['nama_toko']) ?></p>
            <p class="list-card__sub"><?= htmlspecialchars($shop['alamat_toko'] ?? '-') ?></p>
            <p class="list-card__sub"><?= htmlspecialchars($shop['kota'] ?? '-') ?></p>
            <?php if (!empty($shopCategories[$shop['ID_toko']])): ?>
            <div class="category-pills" style="margin-top:8px;">
              <?php foreach ($shopCategories[$shop['ID_toko']] as $kat): ?>
                <span class="pill"><?= htmlspecialchars($kategoriLabel[$kat] ?? $kat) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="list-card__action">
            <a href="view_shop.php?id=<?= $shop['ID_toko'] ?>" class="btn-view">Lihat</a>
          </div>
        </div>
        <?php endforeach; ?>

        <!-- Pagination — only shown when more than 10 shops -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination">

          <?php if ($page > 1): ?>
            <a href="<?= buildUrl(['page' => (string)($page - 1)]) ?>">← Prev</a>
          <?php else: ?>
            <span class="disabled">← Prev</span>
          <?php endif; ?>

          <?php
          $start = max(1, $page - 2);
          $end   = min($totalPages, $page + 2);
          if ($start > 1): ?>
            <a href="<?= buildUrl(['page' => '1']) ?>">1</a>
          <?php endif;
          if ($start > 2): ?>
            <span class="disabled">…</span>
          <?php endif;
          for ($i = $start; $i <= $end; $i++):
            if ($i === $page): ?>
              <span class="current"><?= $i ?></span>
            <?php else: ?>
              <a href="<?= buildUrl(['page' => (string)$i]) ?>"><?= $i ?></a>
            <?php endif;
          endfor;
          if ($end < $totalPages - 1): ?>
            <span class="disabled">…</span>
          <?php endif;
          if ($end < $totalPages): ?>
            <a href="<?= buildUrl(['page' => (string)$totalPages]) ?>"><?= $totalPages ?></a>
          <?php endif; ?>

          <?php if ($page < $totalPages): ?>
            <a href="<?= buildUrl(['page' => (string)($page + 1)]) ?>">Next →</a>
          <?php else: ?>
            <span class="disabled">Next →</span>
          <?php endif; ?>

        </div>
        <?php endif; ?>

      <?php endif; ?>

    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

</body>
</html>
