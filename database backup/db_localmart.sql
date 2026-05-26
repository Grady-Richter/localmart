-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: May 25, 2026 at 03:32 PM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `db_localmart`
--

-- --------------------------------------------------------

--
-- Table structure for table `pembelian`
--

CREATE TABLE `pembelian` (
  `ID_pembelian` int(11) NOT NULL,
  `ID_user` int(11) NOT NULL,
  `ID_produk` int(11) NOT NULL,
  `ID_toko` int(11) NOT NULL,
  `tanggal_pembelian` datetime NOT NULL DEFAULT current_timestamp(),
  `nama_produk` varchar(150) NOT NULL,
  `jumlah` int(50) NOT NULL,
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `status_pembelian` enum('pending','diproses','dikirim','selesai','dibatalkan') NOT NULL DEFAULT 'pending',
  `metode_pengambilan` varchar(50) DEFAULT NULL,
  `total_harga` decimal(15,2) NOT NULL DEFAULT 0.00,
  `alamat_pengiriman` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pembelian`
--

INSERT INTO `pembelian` (`ID_pembelian`, `ID_user`, `ID_produk`, `ID_toko`, `tanggal_pembelian`, `nama_produk`, `jumlah`, `harga_satuan`, `status_pembelian`, `metode_pengambilan`, `total_harga`, `alamat_pengiriman`) VALUES
(1, 11, 10, 7, '2026-05-16 09:43:55', 'Cupcake', 3, 30000.00, 'selesai', 'diambil', 90000.00, NULL),
(4, 1, 16, 10, '2026-05-21 07:52:56', 'beras 1kg', 3, 16000.00, 'dibatalkan', 'diantar', 48000.00, 'boro, tanggulangin'),
(5, 1, 16, 10, '2026-05-21 07:53:20', 'beras 1kg', 1, 16000.00, 'selesai', 'diantar', 16000.00, 'boro, tanggulangin'),
(6, 1, 16, 10, '2026-05-21 07:53:25', 'beras 1kg', 1, 16000.00, 'dibatalkan', 'diantar', 16000.00, 'boro, tanggulangin'),
(7, 11, 10, 7, '2026-05-21 11:12:04', 'Cupcake', 2, 30000.00, 'selesai', 'diantar', 60000.00, 'Pondok Sidokare Asri, Blok H-7'),
(8, 11, 10, 7, '2026-05-21 11:12:43', 'Cupcake', 1, 30000.00, 'selesai', 'diantar', 30000.00, 'Pondok Sidokare Asri, Blok H-7'),
(9, 11, 16, 10, '2026-05-21 11:16:22', 'beras 1kg', 5, 16000.00, 'selesai', 'diambil', 80000.00, NULL),
(10, 1, 9, 7, '2026-05-21 13:25:46', 'Teh Matcha', 5, 70000.00, 'selesai', 'diambil', 350000.00, NULL),
(11, 1, 17, 11, '2026-05-21 13:34:34', 'BERAS ASLI NGAWUR', 6, 67000.00, 'selesai', 'diantar', 402000.00, 'Mars'),
(12, 1, 16, 10, '2026-05-21 14:34:42', 'beras 1kg', 3, 16000.00, 'selesai', 'diambil', 48000.00, NULL),
(13, 1, 18, 12, '2026-05-21 14:43:32', 'Air Mineral 200L', 4, 3500.00, 'selesai', 'diambil', 14000.00, NULL),
(14, 1, 17, 11, '2026-05-23 08:22:07', 'BERAS ASLI NGAWUR', 3, 67000.00, 'pending', 'diantar', 201000.00, 'Bekasi'),
(15, 1, 16, 10, '2026-05-23 08:59:21', 'beras 1kg', 6, 16000.00, 'selesai', 'diantar', 96000.00, 'Bekasi'),
(16, 1, 16, 10, '2026-05-23 09:03:58', 'beras 1kg', 6, 16000.00, 'selesai', 'diantar', 96000.00, 'Bekasi'),
(17, 1, 16, 10, '2026-05-23 09:04:07', 'beras 1kg', 3, 16000.00, 'selesai', 'diambil', 48000.00, NULL),
(18, 1, 16, 10, '2026-05-23 09:04:11', 'beras 1kg', 3, 16000.00, 'selesai', 'diambil', 48000.00, NULL),
(19, 25, 10, 7, '2026-05-25 11:12:07', 'Cupcake', 1, 30000.00, 'selesai', 'diantar', 30000.00, 'rumah cacak asep jl joko anjay mabar'),
(20, 1, 20, 1, '2026-05-25 12:40:30', 'Beras SInatola', 2, 15950.00, 'selesai', 'diantar', 31900.00, NULL),
(21, 1, 16, 10, '2026-05-25 12:42:11', 'beras 1kg', 1, 16000.00, 'selesai', 'diambil', 16000.00, NULL),
(22, 1, 16, 10, '2026-05-25 12:42:12', 'beras 1kg', 1, 16000.00, 'selesai', 'diantar', 16000.00, NULL);

-- --------------------------------------------------------

--
-- Table structure for table `produk`
--

CREATE TABLE `produk` (
  `ID_produk` int(11) NOT NULL,
  `ID_toko` int(11) NOT NULL,
  `nama_produk` varchar(150) NOT NULL,
  `deskripsi_produk` text DEFAULT NULL,
  `gambar_produk` varchar(255) DEFAULT NULL,
  `stok_produk` int(11) NOT NULL DEFAULT 0,
  `harga_produk` decimal(15,2) NOT NULL,
  `kategori` varchar(100) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `produk`
--

INSERT INTO `produk` (`ID_produk`, `ID_toko`, `nama_produk`, `deskripsi_produk`, `gambar_produk`, `stok_produk`, `harga_produk`, `kategori`, `created_at`) VALUES
(1, 1, 'Aqua Botol', 'Air Mineral 600ml', 'uploads/produk/produk_1_1776425626.jpg', 36, 3500.00, 'minuman', '2026-04-17 18:33:46'),
(6, 6, 'Anak gw', 'Susah bet dibesarin kakinya patah 3 kali', 'uploads/produk/produk_6_1777863663.jpg', 1, 2999999900.00, 'perlengkapan mandi', '2026-05-04 10:00:48'),
(9, 7, 'Teh Matcha', 'Minuman yang pahit, namun menyegarkanmu sampai bugar!', 'uploads/produk/produk_7_1778467843.png', 5, 70000.00, 'minuman', '2026-05-11 09:50:33'),
(10, 7, 'Cupcake', 'Cupcake yang manis, membuatmu bahagia!', 'uploads/produk/produk_7_1778467891.jpg', 998, 30000.00, 'makanan', '2026-05-11 09:51:31'),
(11, 7, 'Vita 20', 'Minuman suplemen yang membangkitkan semangatmu!', 'uploads/produk/produk_7_1778467988.png', 14, 35000.00, 'minuman', '2026-05-11 09:53:08'),
(13, 6, 'Anak gw yang satunya', 'Minusnya punya asma', 'uploads/produk/produk_6_1778484224.jpg', 1, 4000000.00, 'perlengkapan dapur', '2026-05-11 14:23:44'),
(16, 10, 'beras 1kg', 'Beras Sintanola 1kg', 'uploads/produk/produk_10_1779324630.jpeg', 17, 16000.00, 'makanan', '2026-05-21 07:47:49'),
(17, 11, 'BERAS ASLI NGAWUR', 'Beras Sangat Wangi, Bisa Buat Cuci, Jemur, Gosok Gigi, Gooning, Makan', 'uploads/produk/produk_11_1779345236.jpeg', 60, 67000.00, 'makanan', '2026-05-21 13:33:56'),
(18, 12, 'Air Mineral 200L', 'Air Mineral', NULL, 56, 3500.00, 'minuman', '2026-05-21 14:41:47'),
(20, 1, 'Beras SInatola', 'Beras per 1kg', 'uploads/produk/produk_1_1779687065.jpeg', 21, 15950.00, 'Beras', '2026-05-25 12:31:05'),
(21, 10, 'Oppy', 'muheehehe', 'uploads/produk/produk_10_1779715369.jpg', 60, 6000.00, 'Spesies Baru', '2026-05-25 20:22:49');

-- --------------------------------------------------------

--
-- Table structure for table `profil_toko`
--

CREATE TABLE `profil_toko` (
  `ID_toko` int(11) NOT NULL,
  `ID_user` int(11) NOT NULL,
  `nama_toko` varchar(100) NOT NULL,
  `deskripsi_toko` text DEFAULT NULL,
  `logo_toko` varchar(255) DEFAULT NULL,
  `alamat_toko` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp(),
  `status_verifikasi` enum('menunggu','diterima','ditolak') NOT NULL DEFAULT 'menunggu',
  `info_verifikasi` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_toko`
--

INSERT INTO `profil_toko` (`ID_toko`, `ID_user`, `nama_toko`, `deskripsi_toko`, `logo_toko`, `alamat_toko`, `kota`, `created_at`, `status_verifikasi`, `info_verifikasi`) VALUES
(1, 2, 'Toko Madura Stego', 'Testing', 'uploads/logo_toko/shop_2_1776425434.jpg', 'Sidokare Asri Blok I-3', 'Sidoarjo', '2026-04-17 18:30:34', 'diterima', NULL),
(6, 9, 'Umamart', 'Ecwipse Firts The Rezt Nower', 'uploads/logo_toko/shop_9_1777876290.png', 'Jalan Jaran No-9', 'Tokyo', '2026-05-04 09:56:56', 'diterima', NULL),
(7, 12, 'Fukukitaru Lucky Shop', 'hungya', 'uploads/logo_toko/shop_12_1778467475.png', 'Jalan Jaran No-12', 'Tokyo', '2026-05-11 09:44:35', 'diterima', NULL),
(9, 21, 'Test', NULL, NULL, 'Test', 'Test', '2026-05-21 07:34:13', 'diterima', NULL),
(10, 22, 'Toko madura barokah', NULL, NULL, 'Jalan raya, Ngaban, Tanggulangin', 'Sidoarjo', '2026-05-21 07:43:39', 'diterima', NULL),
(11, 23, 'Toko', 'Toko Sangat GG Dan Sangat Gokil', 'uploads/logo_toko/shop_23_1779345052.jpg', 'Bulan', 'Ngawur', '2026-05-21 13:30:52', 'diterima', NULL),
(12, 24, 'Toko Madura', 'Toko Madura', NULL, 'Sidokare Asri Blok I-3', 'Sidoarjo', '2026-05-21 14:39:11', 'diterima', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `profil_user_pembeli`
--

CREATE TABLE `profil_user_pembeli` (
  `ID_user` int(11) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `kota` varchar(100) DEFAULT NULL,
  `kode_pos` varchar(10) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_user_pembeli`
--

INSERT INTO `profil_user_pembeli` (`ID_user`, `foto_profil`, `nomor_telepon`, `alamat`, `kota`, `kode_pos`) VALUES
(1, 'uploads/profil_pembeli/buyer_1_1779279380.jpg', '81237869270', 'Sidokare Asri Blok H-7', 'Sidoarjo', '61214'),
(11, 'uploads/profil_pembeli/buyer_11_1778466207.jpg', '+6281237869270', 'Pondok Sidokare Asri, Blok H-7, RT 46 RW 13', 'Sidoarjo', '61214'),
(13, NULL, '+62 888-999-000', 'Tokyo Tracen Academy', 'Tokyo', '69420'),
(15, 'uploads/profil_pembeli/buyer_15_1779161770.jpg', '0404-1996-2005', 'Tokyo Tracen Academy', 'Tokyo', '12345'),
(17, 'uploads/profil_pembeli/buyer_17_1779250644.jpg', '+6281237869270', 'Pondok Sidokare Asri, Blok H-7, RT 46 RW 13', 'SIDOARJO', '61214'),
(18, NULL, '+6281237869270', 'Pondok Sidokare Asri, Blok H-7, RT 46 RW 13', 'SIDOARJO', '61214'),
(20, NULL, '6787656898667896', 'ngawi', 'ngawi', '6567865678'),
(25, NULL, '0821181818181818', 'jl joko anjay mabar', 'Lumina', '676767');

-- --------------------------------------------------------

--
-- Table structure for table `profil_user_penjual`
--

CREATE TABLE `profil_user_penjual` (
  `ID_user` int(11) NOT NULL,
  `foto_profil` varchar(255) DEFAULT NULL,
  `nomor_telepon` varchar(20) DEFAULT NULL,
  `alamat` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `profil_user_penjual`
--

INSERT INTO `profil_user_penjual` (`ID_user`, `foto_profil`, `nomor_telepon`, `alamat`) VALUES
(2, 'uploads/profil_penjual/seller_2_1777874622.png', '81237869270', 'Sidokare Asri Blok H-7'),
(4, 'uploads/profil_penjual/seller_4_1776640261.jpg', '081237869270', 'Pondok Sidokare Asri Blok H-7'),
(9, 'uploads/profil_penjual/seller_9_1777863308.jpg', '81166667777', 'Tokyo Tracen Academy'),
(12, 'uploads/profil_penjual/seller_12_1778467315.jpg', '77777777777', 'Tokyo Tracen Academy'),
(14, NULL, '+62-123-1996-2004', 'Tokyo Tracen Academy'),
(19, NULL, '81237869270', 'babsbbs'),
(21, NULL, '777777', 'abcd'),
(22, NULL, '085136844584', 'Jalan Raya Ngaban, Tanggulangin'),
(23, 'uploads/profil_penjual/seller_23_1779344957.webp', '85772840578', 'Planet Namek'),
(24, NULL, '9039023904932049', 'Sidoarjo');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `ID_user` int(11) NOT NULL,
  `role` enum('pembeli','penjual','admin') NOT NULL DEFAULT 'pembeli',
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`ID_user`, `role`, `username`, `password`, `created_at`) VALUES
(1, 'pembeli', 'UserPembeli', '$2y$10$XV4OMV0a.n9KXgrUHP709ux6.shhaN3zj2mdJ6pEp0C8Cqt7kLGpu', '2026-04-17 14:16:55'),
(2, 'penjual', 'UserPenjual', '$2y$10$c0E683kK7r0v9uS4/UKBpu3Yizbhu0REpwsL3OHsRjq2aAl6jLMu2', '2026-04-17 14:17:30'),
(4, 'penjual', 'helloagain', '$2y$10$ikH9L.Yn2MQFrdkDgdfxK.u1F9b7gwUkzfklyDgOw1blD26.pu7Pu', '2026-04-20 06:09:37'),
(8, 'admin', 'UserAdmin', '$2y$10$2GeiDwnvp/xGtSQx8C2olOeFCc.q2MD9oTuP5gsZsFi0jteGt1eG.', '2026-05-04 08:19:26'),
(9, 'penjual', 'Bapak_Rudolf', '$2y$10$Ezwa6pccBqKrshNkKT42R.Vtr0wJjsL.JUi9kEhN6qH6RVlPpOvG2', '2026-05-04 09:53:18'),
(11, 'pembeli', 'TMOperaO', '$2y$10$mdMO.um/Tzfb/GrjJM3w/u79zLG7R2HY7nAldszkReb2/eClzMzXm', '2026-05-11 09:22:58'),
(12, 'penjual', 'Matikanefukukitaru', '$2y$10$g5WceN4fvU156XIKeBhL2.M.7.JT0FssPuDr2q/JHrO8o0rHyl8pa', '2026-05-11 09:41:05'),
(13, 'pembeli', 'MeishoDoto', '$2y$10$pz/C8viRSI/8w/3DHjKTy.We/1rB26/8KOD3C/E7NMpv5l8m7IrYq', '2026-05-16 09:47:05'),
(14, 'penjual', 'AdmireVega', '$2y$10$KOvXEtkg8.yxh8zu2HPfN.mEJSVhfJ/BIDjWpS0ft0aIIJfkrnzCO', '2026-05-16 09:49:10'),
(15, 'pembeli', 'TopRoad', '$2y$10$tmu02zl9f0cPEkHnw1wx6eZmei4ZbQO2scPu11TxMsC0yISLk8MHi', '2026-05-19 10:25:18'),
(16, 'penjual', 'TwinTurbo', '$2y$10$VGX4HHQa9WGVGF7djtWV4uV1izIouP4qDjbU5Y5HZ/5YAaG0qXlgW', '2026-05-20 05:32:57'),
(17, 'pembeli', 'March 7th', '$2y$10$8FN8.25cW9AlBiloLt7DxO1tK.VS8wo9QgNDYbThs/KV3FEGEwBFG', '2026-05-20 11:16:13'),
(18, 'pembeli', 'penyuka_sawit', '$2y$10$J595NXddrZmbtTu.DrRqp.NP6/yCgj1e0ifTbbDIFUu3cH5Eu/bwC', '2026-05-20 11:24:34'),
(19, 'penjual', 'penyuka_gorong_gorong', '$2y$10$7OMoFR0mfuwt7M1jElXMhea3tORgMREZfo3Kh8jmp5KZDr/FPEtnm', '2026-05-20 11:27:33'),
(20, 'pembeli', 'zidhan', '$2y$10$wEt/6N.W7umjJ2LM6FyD3u1AfN1LWsxNE8NxycAtWS88N6FIeTe5a', '2026-05-21 07:05:58'),
(21, 'penjual', 'PenjualHandal', '$2y$10$r/7gKf4kdYhNeyHnFxyj8u7F1a/ys8S7KjISGv/tsIg9TJ7zLRSse', '2026-05-21 07:07:14'),
(22, 'penjual', 'ZidhanDaDann', '$2y$10$.vTbF9txTlB7GY3EXWJJc.s5Hfg9J5XAruabm8katTHntxniuVV2W', '2026-05-21 07:40:09'),
(23, 'penjual', 'Username', '$2y$10$gzVQpXliRF43TTMDTx1w/eqUf8UJkAe/sPMjbPbAa9GmFLwUo3sIu', '2026-05-21 13:28:17'),
(24, 'penjual', 'Penjualtoko', '$2y$10$T/Zmx.wIS.LOA4WoSX3aIe.wOJv5JoKpHg5xpAWZYJaU9iDGCvWte', '2026-05-21 14:37:52'),
(25, 'pembeli', 'Keyvin', '$2y$10$/zIH/vG61A/DXy0ARG6S6.sg8Ik6wrT1g4Hkrtu3OBRJa7Gaw2Q/m', '2026-05-25 11:10:32');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD PRIMARY KEY (`ID_pembelian`),
  ADD KEY `fk_pembelian_produk` (`ID_produk`),
  ADD KEY `fk_pembelian_toko` (`ID_toko`);

--
-- Indexes for table `produk`
--
ALTER TABLE `produk`
  ADD PRIMARY KEY (`ID_produk`),
  ADD KEY `fk_produk_toko` (`ID_toko`);

--
-- Indexes for table `profil_toko`
--
ALTER TABLE `profil_toko`
  ADD PRIMARY KEY (`ID_toko`),
  ADD UNIQUE KEY `ID_user` (`ID_user`);

--
-- Indexes for table `profil_user_pembeli`
--
ALTER TABLE `profil_user_pembeli`
  ADD PRIMARY KEY (`ID_user`);

--
-- Indexes for table `profil_user_penjual`
--
ALTER TABLE `profil_user_penjual`
  ADD PRIMARY KEY (`ID_user`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`ID_user`),
  ADD UNIQUE KEY `username` (`username`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `pembelian`
--
ALTER TABLE `pembelian`
  MODIFY `ID_pembelian` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `produk`
--
ALTER TABLE `produk`
  MODIFY `ID_produk` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=22;

--
-- AUTO_INCREMENT for table `profil_toko`
--
ALTER TABLE `profil_toko`
  MODIFY `ID_toko` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=14;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `ID_user` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=27;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `pembelian`
--
ALTER TABLE `pembelian`
  ADD CONSTRAINT `fk_pembelian_produk` FOREIGN KEY (`ID_produk`) REFERENCES `produk` (`ID_produk`) ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_pembelian_toko` FOREIGN KEY (`ID_toko`) REFERENCES `profil_toko` (`ID_toko`) ON UPDATE CASCADE;

--
-- Constraints for table `produk`
--
ALTER TABLE `produk`
  ADD CONSTRAINT `fk_produk_toko` FOREIGN KEY (`ID_toko`) REFERENCES `profil_toko` (`ID_toko`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `profil_toko`
--
ALTER TABLE `profil_toko`
  ADD CONSTRAINT `fk_toko_user` FOREIGN KEY (`ID_user`) REFERENCES `users` (`ID_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `profil_user_pembeli`
--
ALTER TABLE `profil_user_pembeli`
  ADD CONSTRAINT `fk_pembeli_user` FOREIGN KEY (`ID_user`) REFERENCES `users` (`ID_user`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Constraints for table `profil_user_penjual`
--
ALTER TABLE `profil_user_penjual`
  ADD CONSTRAINT `fk_penjual_user` FOREIGN KEY (`ID_user`) REFERENCES `users` (`ID_user`) ON DELETE CASCADE ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
