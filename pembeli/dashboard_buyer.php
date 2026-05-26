<?php
// pembeli/dashboard_buyer.php
// Redesigned buyer dashboard with:
//  - Top 4 popular products (by total orders)
//  - Top 4 best stores (by total transaction amount)
//  - Shop list with search + dynamic category dropdown + pagination
session_start();
require_once '../includes/koneksi.php';
require_once '../includes/pagination.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'pembeli') {
    header('Location: ../login_pembeli.php');
    exit;
}

// ── Parameters ────────────────────────────────────────────────
$search   = trim($_GET['search']   ?? '');
$kategori = trim($_GET['kategori'] ?? '');
$page     = max(1, (int)($_GET['page'] ?? 1));
$perPage  = 10;

// ── Top 4 Popular Products ────────────────────────────────────
$popularStmt = $pdo->query(
    "SELECT p.ID_produk, p.nama_produk, p.gambar_produk, p.harga_produk,
            COALESCE(SUM(pb.jumlah), 0) AS total_terjual
     FROM produk p
     JOIN profil_toko pt ON pt.ID_toko = p.ID_toko
     LEFT JOIN pembelian pb ON pb.ID_produk = p.ID_produk
     WHERE pt.status_verifikasi = 'diterima'
     GROUP BY p.ID_produk
     ORDER BY total_terjual DESC
     LIMIT 4"
);
$popularProducts = $popularStmt->fetchAll();

// ── Top 4 Best Stores by transaction total ────────────────────
$bestStoreStmt = $pdo->query(
    "SELECT pt.ID_toko, pt.nama_toko, pt.logo_toko,
            COALESCE(SUM(pb.total_harga), 0) AS total_transaksi
     FROM profil_toko pt
     LEFT JOIN pembelian pb ON pb.ID_toko = pt.ID_toko
     WHERE pt.status_verifikasi = 'diterima'
     GROUP BY pt.ID_toko
     ORDER BY total_transaksi DESC
     LIMIT 4"
);
$bestStores = $bestStoreStmt->fetchAll();

// ── Dynamic categories for dropdown ──────────────────────────
$catStmt = $pdo->query(
    "SELECT DISTINCT p.kategori
     FROM produk p
     JOIN profil_toko pt ON pt.ID_toko = p.ID_toko
     WHERE pt.status_verifikasi = 'diterima'
     ORDER BY p.kategori ASC"
);
$allKategori = $catStmt->fetchAll(PDO::FETCH_COLUMN);

// ── Build WHERE for shop list ─────────────────────────────────
$params = [];
$where  = ['pt.status_verifikasi = "diterima"'];

if ($search !== '') {
    $where[]  = '(pt.nama_toko LIKE ? OR pt.kota LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

if ($kategori !== '') {
    $where[]  = 'EXISTS (SELECT 1 FROM produk p WHERE p.ID_toko = pt.ID_toko AND p.kategori = ?)';
    $params[] = $kategori;
}

$whereSql = 'WHERE ' . implode(' AND ', $where);

// ── Count + paginate ──────────────────────────────────────────
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM profil_toko pt $whereSql");
$countStmt->execute($params);
$totalShops = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalShops / $perPage));
$page       = min($page, $totalPages);
$offset     = ($page - 1) * $perPage;

$shopStmt = $pdo->prepare(
    "SELECT pt.* FROM profil_toko pt $whereSql ORDER BY pt.nama_toko ASC LIMIT $perPage OFFSET $offset"
);
$shopStmt->execute($params);
$shops = $shopStmt->fetchAll();

// Fetch categories per shop
$shopCategories = [];
if (!empty($shops)) {
    $shopIds      = array_column($shops, 'ID_toko');
    $placeholders = implode(',', array_fill(0, count($shopIds), '?'));
    $catRows      = $pdo->prepare(
        "SELECT ID_toko, kategori FROM produk WHERE ID_toko IN ($placeholders) GROUP BY ID_toko, kategori"
    );
    $catRows->execute($shopIds);
    foreach ($catRows->fetchAll() as $row) {
        $shopCategories[$row['ID_toko']][] = $row['kategori'];
    }
}

// ── URL helper ────────────────────────────────────────────────
function buildUrl(array $overrides = []): string {
    $base = [
        'search'   => $_GET['search']   ?? '',
        'kategori' => $_GET['kategori'] ?? '',
        'page'     => (string)($_GET['page'] ?? '1'),
    ];
    $merged = array_merge($base, array_map('strval', $overrides));
    $merged = array_filter($merged, fn($v) => $v !== '');
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
    /* ── Popular / Best stores row ── */
    .featured-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      margin-bottom: 24px;
    }

    .featured-panel {
      background: #fff;
      border: 5px solid rgba(81,40,6,0.6);
      border-radius: 0 0 20px 20px;
      padding: 16px;
    }

    .featured-items {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 10px;
    }

    .featured-item {
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 6px;
      text-decoration: none;
      position: relative;
    }

    .featured-item__rank {
      position: absolute;
      top: -4px; left: -4px;
      background: var(--brown-mid);
      color: #fff;
      font-size: 11px;
      font-weight: 700;
      padding: 1px 6px;
      border-radius: 6px;
      z-index: 1;
    }

    .featured-item__img {
      width: 100%;
      aspect-ratio: 1;
      object-fit: cover;
      border-radius: 14px;
      border: 2px solid rgba(81,40,6,0.4);
    }

    .featured-item__name {
      font-size: clamp(10px, 1.1vw, 13px);
      font-weight: 400;
      color: #374151;
      text-align: center;
      line-height: 1.3;
    }

    /* ── Daftar Toko heading with dropdown ── */
    .daftar-heading {
      background: var(--brown-mid);
      border: 5px solid rgba(81,40,6,0.6);
      border-radius: 30px;
      padding: 16px 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 12px;
      margin-bottom: 16px;
      box-shadow: var(--shadow-md);
    }

    .daftar-heading h2 {
      font-size: clamp(18px, 2vw, 26px);
      font-weight: 600;
      color: #fff;
    }

    .kategori-select {
      background: rgba(255,255,255,0.85);
      border: 3px solid rgba(255,255,255,0.6);
      border-radius: 30px;
      padding: 8px 36px 8px 18px;
      font-family: 'Poppins', sans-serif;
      font-size: clamp(12px, 1.3vw, 15px);
      font-weight: 500;
      color: #374151;
      outline: none;
      cursor: pointer;
      appearance: none;
      -webkit-appearance: none;
      background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8'%3E%3Cpath d='M0 0l6 8 6-8z' fill='%23374151'/%3E%3C/svg%3E");
      background-repeat: no-repeat;
      background-position: right 12px center;
      background-color: rgba(255,255,255,0.85);
      min-width: 160px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    /* ── btn-view arrow style from Figma ── */
    .btn-view-arrow {
      background: var(--orange-btn);
      border: 3px solid rgba(81,40,6,0.4);
      border-radius: 30px;
      color: var(--brown-dark);
      padding: 10px 22px;
      font-size: clamp(13px, 1.5vw, 16px);
      font-weight: 600;
      cursor: pointer;
      box-shadow: var(--shadow-lg);
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 6px;
      white-space: nowrap;
    }
    .btn-view-arrow:hover { filter: brightness(1.08); }

    @media (max-width: 640px) {
      .featured-row { grid-template-columns: 1fr; }
      .featured-items { grid-template-columns: repeat(2, 1fr); }
    }
  </style>
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../index.html" class="site-header__logo">
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
        <div class="search-bar" style="margin-bottom:20px;">
          <input type="text" name="search"
                 placeholder="Cari nama toko atau kota..."
                 value="<?= htmlspecialchars($search) ?>" />
          <button type="submit"
                  style="background:none;border:none;cursor:pointer;padding:0;">
            <img src="../images/assets/search-icon.png" alt="Search" style="width:2px;height:23px;" />
          </button>
        </div>
      </form>

      <!-- ══ Popular Products + Best Stores ══ -->
      <div class="featured-row">

        <!-- Produk Terpopuler -->
        <div>
          <div class="section-heading" style="border-radius:30px 30px 0 0;margin-bottom:0;">
            <h2>Produk Terpopuler</h2>
          </div>
          <div class="featured-panel">
            <?php if (empty($popularProducts)): ?>
              <p style="color:#9ca3af;font-size:13px;text-align:center;padding:16px 0;">
                Belum ada data produk.
              </p>
            <?php else: ?>
            <div class="featured-items">
              <?php foreach ($popularProducts as $i => $prod): ?>
              <a href="view_product.php?id=<?= $prod['ID_produk'] ?>" class="featured-item">
                <span class="featured-item__rank">#<?= $i + 1 ?></span>
                <?php if (!empty($prod['gambar_produk']) && file_exists('../' . $prod['gambar_produk'])): ?>
                  <img class="featured-item__img"
                       src="../<?= htmlspecialchars($prod['gambar_produk']) ?>"
                       alt="<?= htmlspecialchars($prod['nama_produk']) ?>" />
                <?php else: ?>
                  <img class="featured-item__img"
                       src="../images/assets/store-profile.png"
                       alt="Produk" />
                <?php endif; ?>
                <span class="featured-item__name">
                  <?= htmlspecialchars($prod['nama_produk']) ?>
                </span>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Toko Terbaik -->
        <div>
          <div class="section-heading" style="border-radius:30px 30px 0 0;margin-bottom:0;">
            <h2>Toko Terbaik</h2>
          </div>
          <div class="featured-panel">
            <?php if (empty($bestStores)): ?>
              <p style="color:#9ca3af;font-size:13px;text-align:center;padding:16px 0;">
                Belum ada data toko.
              </p>
            <?php else: ?>
            <div class="featured-items">
              <?php foreach ($bestStores as $i => $store): ?>
              <a href="view_shop.php?id=<?= $store['ID_toko'] ?>" class="featured-item">
                <span class="featured-item__rank">#<?= $i + 1 ?></span>
                <?php if (!empty($store['logo_toko']) && file_exists('../' . $store['logo_toko'])): ?>
                  <img class="featured-item__img"
                       src="../<?= htmlspecialchars($store['logo_toko']) ?>"
                       alt="<?= htmlspecialchars($store['nama_toko']) ?>" />
                <?php else: ?>
                  <img class="featured-item__img"
                       src="../images/assets/store-profile.png"
                       alt="Toko" />
                <?php endif; ?>
                <span class="featured-item__name">
                  <?= htmlspecialchars($store['nama_toko']) ?>
                </span>
              </a>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>
        </div>

      </div><!-- end featured-row -->

      <!-- ══ Daftar Toko heading + category dropdown ══ -->
      <div class="daftar-heading">
        <h2>Daftar Toko</h2>
        <form method="GET" action="dashboard_buyer.php" style="margin:0;">
          <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>" />
          <select name="kategori" class="kategori-select" onchange="this.form.submit()">
            <option value="">Kategori</option>
            <?php foreach ($allKategori as $k): ?>
              <option value="<?= htmlspecialchars($k) ?>"
                      <?= $kategori === $k ? 'selected' : '' ?>>
                <?= htmlspecialchars(ucfirst($k)) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </form>
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
            <img class="list-card__thumb"
                 src="../images/assets/store-profile.png"
                 alt="Default Store" />
          <?php endif; ?>

          <div class="list-card__info">
            <p class="list-card__name"><?= htmlspecialchars($shop['nama_toko']) ?></p>
            <p class="list-card__sub"><?= htmlspecialchars($shop['alamat_toko'] ?? '-') ?></p>
            <p class="list-card__sub"><?= htmlspecialchars($shop['kota'] ?? '-') ?></p>
            <?php if (!empty($shopCategories[$shop['ID_toko']])): ?>
            <div class="category-pills" style="margin-top:8px;">
              <?php foreach ($shopCategories[$shop['ID_toko']] as $kat): ?>
                <span class="pill"><?= htmlspecialchars(ucfirst($kat)) ?></span>
              <?php endforeach; ?>
            </div>
            <?php endif; ?>
          </div>

          <div class="list-card__action">
            <a href="view_shop.php?id=<?= $shop['ID_toko'] ?>" class="btn-view-arrow">
              Lihat →
            </a>
          </div>
        </div>
        <?php endforeach; ?>

        <?php echo renderPagination($page, $totalPages, 'buildUrl'); ?>

        </div>
        <?php endif; ?>
    </main>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

</body>
</html>
