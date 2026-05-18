<?php
// penjual/dashboard_seller.php
// Shows the seller's shop info and their product list.
// Gates access behind shop verification status (menunggu / ditolak / diterima).
// Handles product deletion via POST action=hapus&id_produk=X.
session_start();
require_once '../includes/koneksi.php';

if (!isset($_SESSION['ID_user']) || $_SESSION['role'] !== 'penjual') {
    header('Location: ../login_penjual.php');
    exit;
}

$ID_user = $_SESSION['ID_user'];

// Fetch the seller's shop
$shopStmt = $pdo->prepare('SELECT * FROM profil_toko WHERE ID_user = ?');
$shopStmt->execute([$ID_user]);
$toko = $shopStmt->fetch();

// If no shop yet, redirect to setup
if (!$toko) {
    header('Location: setup_shop.php');
    exit;
}

$ID_toko = $toko['ID_toko'];
$status  = $toko['status_verifikasi']; // 'menunggu' | 'diterima' | 'ditolak'

$flash = '';

// ── Handle delete product (only allowed when verified) ───────
if ($status === 'diterima'
    && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'hapus'
    && !empty($_POST['id_produk'])
) {
    $id_produk = (int)$_POST['id_produk'];

    $own = $pdo->prepare('SELECT ID_produk, gambar_produk FROM produk WHERE ID_produk = ? AND ID_toko = ?');
    $own->execute([$id_produk, $ID_toko]);
    $produkRow = $own->fetch();

    if ($produkRow) {
        if ($produkRow['gambar_produk'] && file_exists('../' . $produkRow['gambar_produk'])) {
            unlink('../' . $produkRow['gambar_produk']);
        }
        $del = $pdo->prepare('DELETE FROM produk WHERE ID_produk = ? AND ID_toko = ?');
        $del->execute([$id_produk, $ID_toko]);
        $flash = 'Produk berhasil dihapus.';
    }
}

// Fetch products (only needed for verified sellers)
$daftarProduk  = [];
$kategoriLabel = [
    'makanan'            => 'Makanan',
    'minuman'            => 'Minuman',
    'perlengkapan mandi' => 'Perlengkapan Mandi',
    'perlengkapan dapur' => 'Perlengkapan Dapur',
];

if ($status === 'diterima') {
    $produkStmt = $pdo->prepare('SELECT * FROM produk WHERE ID_toko = ? ORDER BY created_at DESC');
    $produkStmt->execute([$ID_toko]);
    $daftarProduk = $produkStmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>LocalMart — Dashboard Penjual</title>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet" />
  <link href="../css/penjual.css" rel="stylesheet" />
</head>
<body>

  <div class="page-wrapper">

    <header class="site-header">
      <a href="../landing_page.html" class="site-header__logo">
        <div class="site-header__logo-box"><span>LocalMart</span></div>
      </a>
      <nav class="site-header__nav">
        <a href="dashboard_seller.php" class="active">Toko Anda</a>
        <a href="orders_seller.php">Pesanan</a>
        <a href="profile_settings_seller.php">Profil</a>
      </nav>
    </header>

    <?php if ($status === 'menunggu'): ?>
    <!-- ══════════════════════════════════════════════════════════
         WAITING SCREEN
    ══════════════════════════════════════════════════════════ -->
    <main class="verif-wrap">
      <div class="verif-card">
        <div class="spinner"></div>
        <p class="verif-card__title waiting">Toko Sedang Diverifikasi</p>
        <p class="verif-card__body">
          Permintaan setup toko <strong><?= htmlspecialchars($toko['nama_toko']) ?></strong>
          sedang ditinjau oleh admin LocalMart.<br><br>
          Halaman ini akan otomatis diperbarui setiap 30 detik.
          Kamu juga bisa muat ulang kapan saja.
        </p>
        <a href="profile_settings_seller.php" class="btn btn-secondary">Lihat Profil</a>
      </div>
    </main>
    <script>
      // Auto-refresh every 30 seconds so seller sees approval without manual reload
      setTimeout(() => location.reload(), 30000);
    </script>

    <?php elseif ($status === 'ditolak'): ?>
    <!-- ══════════════════════════════════════════════════════════
         REJECTED SCREEN
    ══════════════════════════════════════════════════════════ -->
    <main class="verif-wrap">
      <div class="verif-card">
        <div class="verif-card__icon">❌</div>
        <p class="verif-card__title rejected">Toko Ditolak</p>
        <p class="verif-card__body">
          Maaf, pengajuan toko <strong><?= htmlspecialchars($toko['nama_toko']) ?></strong>
          tidak disetujui oleh admin. Silakan perbaiki informasi toko dan ajukan kembali.
        </p>
        <?php if (!empty($toko['info_verifikasi'])): ?>
        <div class="verif-card__reason">
          <strong>Alasan penolakan:</strong>
          <?= htmlspecialchars($toko['info_verifikasi']) ?>
        </div>
        <?php endif; ?>
        <a href="shop_settings.php?resubmit=1" class="btn btn-primary" style="width:100%;margin-bottom:12px;">
          Perbaiki &amp; Ajukan Ulang
        </a>
        <a href="profile_settings_seller.php" class="btn btn-secondary">Lihat Profil</a>
      </div>
    </main>

    <?php else: ?>
    <!-- ══════════════════════════════════════════════════════════
         VERIFIED — NORMAL DASHBOARD
    ══════════════════════════════════════════════════════════ -->
    <main class="page-content">

      <?php if ($flash): ?>
        <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
      <?php endif; ?>

      <!-- Store Info Bar -->
      <div class="store-info-bar">
        <?php if ($toko['logo_toko'] && file_exists('../' . $toko['logo_toko'])): ?>
          <img class="store-info-bar__img"
               src="../<?= htmlspecialchars($toko['logo_toko']) ?>"
               alt="Logo Toko" />
        <?php else: ?>
          <div class="store-info-bar__img-placeholder">🏪</div>
        <?php endif; ?>
        <div class="store-info-bar__details">
          <p class="store-info-bar__name"><?= htmlspecialchars($toko['nama_toko']) ?></p>
          <p class="store-info-bar__address"><?= htmlspecialchars($toko['alamat_toko'] ?? '-') ?></p>
          <p class="store-info-bar__city"><?= htmlspecialchars($toko['kota'] ?? '-') ?></p>
        </div>
        <a href="shop_settings.php" class="btn btn-secondary" style="flex-shrink:0;">⚙ Edit Toko</a>
      </div>

      <!-- Product List Heading + Add Button -->
      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;flex-wrap:wrap;gap:10px;">
        <div class="section-heading" style="margin-bottom:0;flex:1;">
          <h2>Daftar Produk</h2>
        </div>
        <a href="add_product.php" class="btn btn-primary" style="white-space:nowrap;">+ Tambah Produk</a>
      </div>

      <!-- Product Table -->
      <div style="overflow:hidden;background:#fff;border-radius:20px;box-shadow:0 5px 10px rgba(0,0,0,.15);">
        <div style="overflow-x:auto;">
        <table class="data-table">
          <thead>
            <tr>
              <th>No</th>
              <th>Gambar</th>
              <th>Nama Produk</th>
              <th>Deskripsi</th>
              <th>Harga</th>
              <th>Stok</th>
              <th>Kategori</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($daftarProduk)): ?>
              <tr>
                <td colspan="8" style="padding:24px;color:#999;">
                  Belum ada produk. <a href="add_product.php">Tambah sekarang!</a>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($daftarProduk as $i => $produk): ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td>
                  <?php if ($produk['gambar_produk'] && file_exists('../' . $produk['gambar_produk'])): ?>
                    <img src="../<?= htmlspecialchars($produk['gambar_produk']) ?>"
                         alt="Produk"
                         style="width:56px;height:44px;object-fit:cover;border-radius:8px;border:1px solid #78350f;" />
                  <?php else: ?>
                    <div style="width:56px;height:44px;background:#e5c9a0;border-radius:8px;border:1px solid #78350f;display:inline-flex;align-items:center;justify-content:center;font-size:20px;">📦</div>
                  <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($produk['nama_produk']) ?></td>
                <td style="max-width:200px;white-space:normal;">
                  <?= htmlspecialchars($produk['deskripsi_produk'] ?? '-') ?>
                </td>
                <td>Rp <?= number_format($produk['harga_produk'], 0, ',', '.') ?></td>
                <td><?= (int)$produk['stok_produk'] ?></td>
                <td><?= htmlspecialchars($kategoriLabel[$produk['kategori']] ?? $produk['kategori']) ?></td>
                <td>
                  <div style="display:flex;gap:6px;justify-content:center;">
                    <a href="edit_product.php?id=<?= $produk['ID_produk'] ?>"
                       class="btn btn-secondary"
                       style="padding:6px 14px;font-size:13px;">Edit</a>
                    <button type="button"
                            onclick="confirmDelete(<?= $produk['ID_produk'] ?>)"
                            class="btn btn-danger"
                            style="padding:6px 14px;font-size:13px;">Hapus</button>
                  </div>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
        </div><!-- end overflow-x:auto -->
      </div>

      <!-- Hidden delete form -->
      <form id="deleteForm" action="dashboard_seller.php" method="POST" style="display:none;">
        <input type="hidden" name="action" value="hapus" />
        <input type="hidden" name="id_produk" id="deleteProductId" value="" />
      </form>

    </main>

    <script>
      function confirmDelete(id) {
        if (confirm('Yakin ingin menghapus produk ini? Tindakan ini tidak dapat dibatalkan.')) {
          document.getElementById('deleteProductId').value = id;
          document.getElementById('deleteForm').submit();
        }
      }
    </script>
    <?php endif; ?>

    <footer class="site-footer">
      <span>© LocalMart 2026</span>
    </footer>

  </div>

</body>
</html>
