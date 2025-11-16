-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Máy chủ: 127.0.0.1
-- Thời gian đã tạo: Th10 15, 2025 lúc 04:22 AM
-- Phiên bản máy phục vụ: 10.4.32-MariaDB
-- Phiên bản PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Cơ sở dữ liệu: `du_an_ban_dien_thoai`
--

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `binh_luan`
--

CREATE TABLE `binh_luan` (
  `id_binh_luan` int(10) UNSIGNED NOT NULL,
  `id_tin_tuc` int(10) UNSIGNED NOT NULL COMMENT 'Khóa ngoại, liên kết với bài tin tức',
  `id_nguoi_dung` int(10) UNSIGNED NOT NULL COMMENT 'Khóa ngoại, người đã viết bình luận',
  `noi_dung` text NOT NULL,
  `trang_thai` enum('cho_duyet','da_duyet','an') NOT NULL DEFAULT 'da_duyet',
  `ngay_binh_luan` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `chi_tiet_don_hang`
--

CREATE TABLE `chi_tiet_don_hang` (
  `id_chi_tiet` int(10) UNSIGNED NOT NULL,
  `id_don_hang` int(10) UNSIGNED NOT NULL COMMENT 'Liên kết với bang don_hang',
  `id_san_pham` int(11) DEFAULT NULL COMMENT 'Liên kết với bang san_pham',
  `ten_san_pham_luc_mua` varchar(255) NOT NULL,
  `so_luong` int(5) NOT NULL,
  `gia_luc_mua` decimal(12,0) NOT NULL,
  `mau_sac_luc_mua` varchar(50) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `chi_tiet_don_hang`
--

INSERT INTO `chi_tiet_don_hang` (`id_chi_tiet`, `id_don_hang`, `id_san_pham`, `ten_san_pham_luc_mua`, `so_luong`, `gia_luc_mua`, `mau_sac_luc_mua`) VALUES
(1, 1, NULL, 'Điện thoại iPhone 17 Pro Max (8GB/1T)', 1, 40000000, 'cam'),
(2, 2, NULL, 'Điện thoại iPhone 17 Pro Max (12GB/2T)', 3, 64000000, 'Đen'),
(3, 3, NULL, 'Điện thoại iPhone 17 Pro Max (8GB/1T)', 1, 40000000, 'cam'),
(4, 4, NULL, 'Điện thoại iPhone 17 Pro Max (8GB/1T)', 1, 40000000, 'cam'),
(5, 5, NULL, 'Điện thoại iPhone 17 Pro Max (12GB/2T)', 1, 64000000, 'Đen'),
(6, 6, NULL, 'Điện thoại iPhone 17 Pro Max (12GB/2T)', 1, 64000000, 'Đen'),
(14, 11, 25, 'Samsung Galaxy A55', 1, 9990000, 'Xanh dương'),
(16, 13, 24, 'Samsung Galaxy S24 Ultra', 1, 29990000, 'Đen'),
(17, 13, 25, 'Samsung Galaxy A55', 2, 9990000, 'Xanh dương'),
(18, 14, 24, 'Samsung Galaxy S24 Ultra', 1, 29990000, 'Đen'),
(19, 15, 24, 'Samsung Galaxy S24 Ultra', 1, 29990000, 'Đen'),
(20, 17, 25, 'Samsung Galaxy A55', 1, 9990000, 'Mặc định'),
(21, 18, 24, 'Samsung Galaxy S24 Ultra', 1, 29990000, 'Mặc định'),
(22, 18, 25, 'Samsung Galaxy A55', 2, 9990000, 'Mặc định'),
(23, 19, 25, 'Samsung Galaxy A55', 2, 9990000, 'Mặc định'),
(24, 20, 24, 'Samsung Galaxy S24 Ultra', 1, 29990000, 'Mặc định'),
(25, 20, 38, 'Google Pixel 9 Pro', 1, 25990000, 'Mặc định'),
(26, 21, 24, 'Samsung Galaxy S24 Ultra', 1, 29990000, 'Mặc định'),
(27, 21, 25, 'Samsung Galaxy A55', 1, 9990000, 'Mặc định'),
(28, 22, 25, 'Samsung Galaxy A55', 1, 8991000, 'Mặc định'),
(29, 23, 24, 'Samsung Galaxy S24 Ultra', 1, 0, 'Mặc định'),
(30, 24, 24, 'Samsung Galaxy S24 Ultra', 1, 0, 'Mặc định'),
(31, 24, 25, 'Samsung Galaxy A55', 1, 8991000, 'Mặc định');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `danh_gia_san_pham`
--

CREATE TABLE `danh_gia_san_pham` (
  `id_danh_gia` int(11) NOT NULL,
  `id_san_pham` int(11) NOT NULL,
  `id_nguoi_dung` int(11) NOT NULL,
  `so_sao` tinyint(1) NOT NULL DEFAULT 5,
  `noi_dung` text DEFAULT NULL,
  `ngay_danh_gia` timestamp NOT NULL DEFAULT current_timestamp(),
  `trang_thai` enum('cho_duyet','da_duyet','bi_an') NOT NULL DEFAULT 'cho_duyet',
  `id_admin_tra_loi` int(11) DEFAULT NULL,
  `noi_dung_tra_loi` text DEFAULT NULL,
  `ngay_tra_loi` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `danh_gia_san_pham`
--

INSERT INTO `danh_gia_san_pham` (`id_danh_gia`, `id_san_pham`, `id_nguoi_dung`, `so_sao`, `noi_dung`, `ngay_danh_gia`, `trang_thai`, `id_admin_tra_loi`, `noi_dung_tra_loi`, `ngay_tra_loi`) VALUES
(1, 25, 16, 5, 'sản phẩm đẹp', '2025-11-13 02:55:45', 'da_duyet', 16, 'cảm ơn đại ka', '2025-11-13 09:56:55'),
(2, 24, 16, 5, 'sản phẩm như cc', '2025-11-13 02:59:34', 'da_duyet', NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `don_hang`
--

CREATE TABLE `don_hang` (
  `id_don_hang` int(10) UNSIGNED NOT NULL,
  `id_nguoi_dung` int(10) UNSIGNED DEFAULT NULL COMMENT 'NULL nếu là khách vãng lai',
  `ma_don_hang` varchar(20) NOT NULL COMMENT 'Mã đơn hàng duy nhất (tự tạo)',
  `ten_nguoi_nhan` varchar(255) NOT NULL,
  `so_dien_thoai_nhan` varchar(20) NOT NULL,
  `dia_chi_giao_hang` text NOT NULL,
  `email_nguoi_nhan` varchar(255) DEFAULT NULL,
  `tong_tien` decimal(12,0) NOT NULL DEFAULT 0,
  `id_ma_giam_gia` int(11) DEFAULT NULL COMMENT 'ID liên kết đến bảng ma_giam_gia',
  `ghi_chu` text DEFAULT NULL,
  `ma_giam_gia_da_ap` varchar(50) DEFAULT NULL COMMENT 'Mã code đã áp dụng (ví dụ: GIAM10)',
  `so_tien_giam_gia` decimal(12,0) NOT NULL DEFAULT 0 COMMENT 'Số tiền thực tế đã được giảm',
  `phuong_thuc_thanh_toan` enum('cod','vnpay','momo','chuyen_khoan') NOT NULL DEFAULT 'cod',
  `phuong_thuc_van_chuyen` varchar(100) DEFAULT NULL COMMENT 'Phương thức vận chuyển',
  `anh_bill_thanh_toan` varchar(255) DEFAULT NULL COMMENT 'Ảnh bill khách upload',
  `trang_thai_thanh_toan` enum('chua_thanh_toan','da_thanh_toan') NOT NULL DEFAULT 'chua_thanh_toan',
  `trang_thai_don_hang` enum('moi','dang_xu_ly','dang_giao','hoan_thanh','da_huy','yeu_cau_huy','yeu_cau_tra_hang','da_hoan_tra','cho_xac_nhan_thanh_toan') NOT NULL DEFAULT 'moi',
  `ngay_dat` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `don_hang`
--

INSERT INTO `don_hang` (`id_don_hang`, `id_nguoi_dung`, `ma_don_hang`, `ten_nguoi_nhan`, `so_dien_thoai_nhan`, `dia_chi_giao_hang`, `email_nguoi_nhan`, `tong_tien`, `id_ma_giam_gia`, `ghi_chu`, `ma_giam_gia_da_ap`, `so_tien_giam_gia`, `phuong_thuc_thanh_toan`, `phuong_thuc_van_chuyen`, `anh_bill_thanh_toan`, `trang_thai_thanh_toan`, `trang_thai_don_hang`, `ngay_dat`) VALUES
(1, 16, 'DH176196621864', 'Thuận', '0386408024', '22', '20222027@eaut.edu.vn', 32000000, NULL, 'ko', 'DA663C7D31', 8000000, 'cod', NULL, NULL, 'chua_thanh_toan', 'da_hoan_tra', '2025-11-01 03:03:38'),
(2, 16, 'DH176196736755', 'Thuận', '0386408024', '45', '20222027@eaut.edu.vn', 153600000, NULL, '45', 'DA663C7D31', 38400000, 'cod', NULL, NULL, 'chua_thanh_toan', 'da_huy', '2025-11-01 03:22:47'),
(3, 16, 'DH176196791760', 'Thuận', '0386408024', '1', '20222027@eaut.edu.vn', 40000000, NULL, '', NULL, 0, 'cod', NULL, NULL, 'chua_thanh_toan', 'da_huy', '2025-11-01 03:31:57'),
(4, NULL, 'DH176209873714', 'Thuận', '0386408029', '12', '20222012@eaut.edu.vn', 32000000, NULL, '12', 'DA663C7D31', 8000000, 'cod', NULL, NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-02 15:52:17'),
(5, 16, 'DH176209950273', 'Thuận', '0386408024', '123', '20222027@eaut.edu.vn', 64000000, NULL, 'có', NULL, 0, 'cod', NULL, NULL, 'chua_thanh_toan', 'da_huy', '2025-11-02 16:05:02'),
(6, 16, 'DH176212889755', 'Thuận', '0386408024', '541', '20222027@eaut.edu.vn', 51200000, NULL, '1', 'DA663C7D31', 12800000, 'cod', NULL, NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-03 00:14:57'),
(11, 16, 'DH-690E10A71F1DE', 'Nguyễn Đức Thuận', '0386408024', 'số nhà 2, Phương Liễu, Bắc Ninh', '20222027@eaut.edu.vn', 9990000, NULL, '', NULL, 0, 'cod', NULL, NULL, 'chua_thanh_toan', 'da_huy', '2025-11-07 15:30:47'),
(13, 19, 'DH-690E146B53674', 'Nguyễn Đức Thuận', '03864080241', '2, Phương Liễu, Bắc Ninh', 'thuannguyen822004@gmail.com', 44973000, NULL, '', 'DA!2222222', 4997000, 'cod', NULL, NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-07 15:46:51'),
(14, 16, 'DH-69113AF477A46', 'Nguyễn Đức Thuận đại ka', '0386408024', 'số nhà 2, Phương Liễu, Bắc Ninh', '20222027@eaut.edu.vn', 28490500, NULL, '', 'DA12HGGGG', 1499500, 'cod', NULL, NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-10 01:08:04'),
(15, 16, 'DH-69120E9F9B2F3', 'Nguyễn Đức Thuận đại ka', '0386408024', 'số nhà 2, Phương Liễu, Bắc Ninh', '20222027@eaut.edu.vn', 29990000, NULL, 'ok', NULL, 0, 'cod', NULL, NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-10 16:11:11'),
(17, 16, 'DH176287404116', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 10020000, NULL, '', NULL, 0, 'cod', 'Giao hàng tiêu chuẩn', NULL, 'chua_thanh_toan', 'moi', '2025-11-11 15:14:03'),
(18, 16, 'DH176287427216', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 50000000, NULL, '', NULL, 0, 'cod', 'Giao hàng tiêu chuẩn', NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-11 15:17:55'),
(19, 16, 'DH176287544116', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 20010000, NULL, '', NULL, 0, '', 'Giao hàng tiêu chuẩn', 'bill_16_691358fcac06f1762875644.png', 'chua_thanh_toan', 'hoan_thanh', '2025-11-11 15:40:44'),
(20, 16, 'DH176287579116', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 56010000, NULL, '', NULL, 0, 'cod', 'Giao hàng tiêu chuẩn', NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-11 15:43:13'),
(21, 16, 'DH176290803916', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 40010000, NULL, '', NULL, 0, 'cod', 'Giao hàng tiêu chuẩn', NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-12 00:50:14'),
(22, 16, 'DH176291905416', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 8091900, 113, '', 'DA1669248B2', 899100, 'cod', 'Giao hàng tiêu chuẩn', NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-12 03:45:21'),
(23, 16, 'DH176295689016', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 0, 113, '', 'DA1669248B2', 0, 'cod', 'Giao hàng tiêu chuẩn', NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-12 14:14:57'),
(24, 16, 'DH176295693916', 'Nguyễn Đức Thuận đại ka', '0386408024', ', Phương Liễu, Bắc Ninh', NULL, 8991000, NULL, '', NULL, 0, 'cod', 'Giao hàng tiêu chuẩn', NULL, 'chua_thanh_toan', 'hoan_thanh', '2025-11-12 14:15:57');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `gio_hang`
--

CREATE TABLE `gio_hang` (
  `id_gio_hang` int(10) UNSIGNED NOT NULL,
  `id_nguoi_dung` int(10) UNSIGNED NOT NULL,
  `id_san_pham` int(11) NOT NULL,
  `so_luong` int(5) NOT NULL DEFAULT 1,
  `ngay_them_vao` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `gio_hang`
--

INSERT INTO `gio_hang` (`id_gio_hang`, `id_nguoi_dung`, `id_san_pham`, `so_luong`, `ngay_them_vao`) VALUES
(84, 16, 24, 1, '2025-11-14 15:44:09'),
(87, 19, 25, 2, '2025-11-14 16:07:02'),
(88, 16, 25, 1, '2025-11-14 16:16:42'),
(89, 16, 38, 1, '2025-11-14 16:16:43');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `hang_san_xuat`
--

CREATE TABLE `hang_san_xuat` (
  `id_hang` int(10) UNSIGNED NOT NULL,
  `ten_hang` varchar(100) NOT NULL,
  `anh_dai_dien` varchar(255) DEFAULT NULL COMMENT 'Ảnh logo của hãng',
  `logo_hang` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn tới file logo',
  `quoc_gia` varchar(100) DEFAULT NULL,
  `trang_thai` enum('hien_thi','an') NOT NULL DEFAULT 'hien_thi',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `hang_san_xuat`
--

INSERT INTO `hang_san_xuat` (`id_hang`, `ten_hang`, `anh_dai_dien`, `logo_hang`, `quoc_gia`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(1, 'SamSung', NULL, 'hang_690ebca7935df1762573479.png', 'Hàn quốc', 'hien_thi', '2025-10-30 14:42:32', '2025-11-08 03:44:39'),
(2, 'Iphone', NULL, 'hang_690ebc9f14d0a1762573471.png', 'Mỹ', 'hien_thi', '2025-10-30 15:20:42', '2025-11-08 03:44:31'),
(3, 'Xiaomi', NULL, 'hang_690ebc8e61a641762573454.png', 'Trung Quốc', 'hien_thi', '2025-11-04 14:42:23', '2025-11-08 03:44:14'),
(4, 'OPPO', NULL, 'hang_690ebc5f117cd1762573407.jpg', 'Trung Quốc', 'hien_thi', '2025-11-04 14:42:23', '2025-11-08 03:43:27'),
(5, 'Vivo', NULL, 'hang_690ebc57aa1b41762573399.png', 'Trung Quốc', 'hien_thi', '2025-11-04 14:42:23', '2025-11-08 03:43:19'),
(6, 'OnePlus', NULL, 'hang_690ebc51424ba1762573393.png', 'Trung Quốc', 'hien_thi', '2025-11-04 14:42:23', '2025-11-08 03:43:13'),
(7, 'Realme', NULL, 'hang_690ebc967fde91762573462.jpg', 'Trung Quốc', 'hien_thi', '2025-11-04 14:42:23', '2025-11-08 03:44:22'),
(8, 'Google Pixel', NULL, 'hang_690ebc3a27a331762573370.jpg', 'Mỹ', 'hien_thi', '2025-11-04 14:42:23', '2025-11-08 03:42:50'),
(9, 'Sony', NULL, 'hang_690ebc2b7bf211762573355.png', 'Nhật Bản', 'hien_thi', '2025-11-04 14:42:23', '2025-11-08 03:42:35'),
(12, 'Huawei', NULL, 'hang_690ebc24d65061762573348.png', 'Trung Quốc', 'hien_thi', '2025-11-04 14:42:23', '2025-11-13 16:03:38');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `lien_he`
--

CREATE TABLE `lien_he` (
  `id_lien_he` int(10) UNSIGNED NOT NULL,
  `ten_nguoi_gui` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `tieu_de` varchar(255) DEFAULT NULL,
  `noi_dung` text NOT NULL,
  `ngay_gui` timestamp NOT NULL DEFAULT current_timestamp(),
  `trang_thai` enum('moi','da_doc','da_tra_loi') NOT NULL DEFAULT 'moi',
  `tieu_de_xu_ly` varchar(255) DEFAULT NULL COMMENT 'Tiêu đề admin trả lời',
  `noi_dung_xu_ly` text DEFAULT NULL COMMENT 'Nội dung admin trả lời',
  `id_nguoi_dung` int(10) UNSIGNED DEFAULT NULL COMMENT 'Nếu người dùng đã đăng nhập khi gửi'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `lien_he`
--

INSERT INTO `lien_he` (`id_lien_he`, `ten_nguoi_gui`, `email`, `so_dien_thoai`, `tieu_de`, `noi_dung`, `ngay_gui`, `trang_thai`, `tieu_de_xu_ly`, `noi_dung_xu_ly`, `id_nguoi_dung`) VALUES
(2, 'Thuận đại ka', '20222027@eaut.edu.vn', '0386408024', 'Galaxy S26 sẽ có loạt tính năng camera cực đỉnh nhờ chip Exynos 2600', 'retfd', '2025-11-11 15:06:55', 'da_tra_loi', 'thằng chó thắng', 'đcm', 16);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `ma_giam_gia`
--

CREATE TABLE `ma_giam_gia` (
  `id_giam_gia` int(10) UNSIGNED NOT NULL,
  `ma_code` varchar(50) NOT NULL COMMENT 'Mã người dùng nhập (ví dụ: GIAM10)',
  `phan_tram_giam` tinyint(3) NOT NULL COMMENT 'Số % giảm (ví dụ: 10 cho 10%)',
  `so_luong_tong` int(11) DEFAULT NULL COMMENT 'Số lượt sử dụng tối đa (NULL = vô hạn)',
  `so_luong_da_dung` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượt đã sử dụng',
  `ngay_ket_thuc` date NOT NULL COMMENT 'Ngày mã hết hiệu lực',
  `trang_thai` enum('hoat_dong','da_khoa') NOT NULL DEFAULT 'hoat_dong'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `ma_giam_gia`
--

INSERT INTO `ma_giam_gia` (`id_giam_gia`, `ma_code`, `phan_tram_giam`, `so_luong_tong`, `so_luong_da_dung`, `ngay_ket_thuc`, `trang_thai`) VALUES
(54, 'DA1FD87A507', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(55, 'DA169252546', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(56, 'DA18A1FD549', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(57, 'DA109F2E331', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(58, 'DA1DCADC7EE', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(59, 'DA125461123', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(60, 'DA19E2026DA', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(61, 'DA189C1F776', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(62, 'DA1642AEA18', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(63, 'DA1EA3CF942', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(64, 'DA12AD69351', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(65, 'DA1549BDD64', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(66, 'DA1D9477AE0', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(67, 'DA1CD65D59E', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(68, 'DA1BE1F805A', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(69, 'DA138B406A0', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(70, 'DA13FE7E293', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(71, 'DA1C3FC996C', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(72, 'DA1B30A31F8', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(73, 'DA100FFD7AA', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(74, 'DA152B83587', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(75, 'DA1FCB32560', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(76, 'DA1FD9EDC88', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(77, 'DA1E6B8AE3E', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(78, 'DA169C258FC', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(79, 'DA1C8643C72', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(80, 'DA18D6CCC79', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(81, 'DA150B008EE', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(82, 'DA1E9FD5047', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(83, 'DA1505244FC', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(84, 'DA1A135CFE2', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(85, 'DA1E254B578', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(86, 'DA116AEAA9C', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(87, 'DA104B2C049', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(88, 'DA14CBCB2CE', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(89, 'DA1377E99E7', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(90, 'DA1E077AC9B', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(91, 'DA1AA68F4B5', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(92, 'DA1ACA2CFAC', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(93, 'DA17ADD4876', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(114, 'DA115750406', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(115, 'DA1B2BB6A78', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(116, 'DA19596F512', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(117, 'DA1CC3E0572', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(118, 'DA1375B2113', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(119, 'DA1F8D6427A', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(120, 'DA18CA5E21B', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(121, 'DA1AC21DAE9', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(122, 'DA1929AB756', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(123, 'DA171D3DBCD', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(124, 'DA10E2B37E6', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(125, 'DA11ECB261D', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(126, 'DA1565EA18F', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(127, 'DA1FF0A127D', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(128, 'DA14F1B060F', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(129, 'DA14DD82E46', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(130, 'DA10D716A76', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(131, 'DA1D8B78715', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(132, 'DA1601EDAAC', 10, NULL, 0, '2025-11-29', 'hoat_dong'),
(133, 'DA166C7721A', 10, NULL, 0, '2025-11-29', 'hoat_dong');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `nguoi_dung`
--

CREATE TABLE `nguoi_dung` (
  `id_nguoi_dung` int(10) UNSIGNED NOT NULL,
  `ho` varchar(100) DEFAULT NULL,
  `ten` varchar(100) DEFAULT NULL,
  `email` varchar(255) NOT NULL,
  `so_dien_thoai` varchar(20) DEFAULT NULL,
  `so_cccd` varchar(20) DEFAULT NULL COMMENT 'Số Căn cước công dân',
  `tinh_thanh_pho` varchar(100) DEFAULT NULL,
  `phuong_xa` varchar(100) DEFAULT NULL,
  `dia_chi_chi_tiet` varchar(255) DEFAULT NULL,
  `anh_dai_dien` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn tới file ảnh đại diện',
  `mat_khau` varchar(255) NOT NULL,
  `vai_tro` enum('khach_hang','quan_tri') NOT NULL DEFAULT 'khach_hang',
  `trang_thai_tai_khoan` enum('cho_xac_minh','hoat_dong','bi_cam') NOT NULL DEFAULT 'cho_xac_minh',
  `token_xac_minh` varchar(100) DEFAULT NULL,
  `token_het_han` datetime DEFAULT NULL,
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `nguoi_dung`
--

INSERT INTO `nguoi_dung` (`id_nguoi_dung`, `ho`, `ten`, `email`, `so_dien_thoai`, `so_cccd`, `tinh_thanh_pho`, `phuong_xa`, `dia_chi_chi_tiet`, `anh_dai_dien`, `mat_khau`, `vai_tro`, `trang_thai_tai_khoan`, `token_xac_minh`, `token_het_han`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(16, 'Nguyễn Đức', 'Thuận đại ka', '20222027@eaut.edu.vn', '0386408024', '027204009022', 'Bắc Ninh', 'Phương Liễu', '', 'user_16_1762820124.jpg', '$2y$10$Xk.yZCKo.OCXGrbjCmDAv.yTVwsvcYQW6CZF05eIkNggapkXxiDZa', 'quan_tri', 'hoat_dong', NULL, NULL, '2025-11-01 01:40:19', '2025-11-11 00:15:28'),
(19, 'Nguyễn Đức', 'Thuận', 'thuannguyen822004@gmail.com', '03864080241', '0272040090221', 'Bắc Ninh', 'Phương Liễu', 'số nhà 2', 'avatar_690c1f240f48e2.26259675.jpg', '$2y$10$IpsK0hTWFGeFBFiE1LfNZu9tNQ2h/YMnJZINS7711CWjvGlMePL1u', 'khach_hang', 'hoat_dong', NULL, NULL, '2025-11-06 04:07:38', '2025-11-11 00:17:32');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `phan_anh`
--

CREATE TABLE `phan_anh` (
  `id_phan_anh` int(10) UNSIGNED NOT NULL,
  `id_nguoi_dung` int(10) UNSIGNED NOT NULL COMMENT 'Người gửi phản ánh, BẮT BUỘC',
  `id_don_hang` int(10) UNSIGNED DEFAULT NULL COMMENT 'Phản ánh này liên quan đến đơn hàng nào (nếu có)',
  `chu_de` varchar(255) NOT NULL COMMENT 'VD: Báo lỗi sản phẩm, Giao hàng chậm, Góp ý...',
  `noi_dung` text NOT NULL,
  `anh_1` varchar(255) DEFAULT NULL,
  `anh_2` varchar(255) DEFAULT NULL,
  `anh_3` varchar(255) DEFAULT NULL,
  `ngay_gui` timestamp NOT NULL DEFAULT current_timestamp(),
  `trang_thai` enum('moi','dang_xu_ly','da_giai_quyet') NOT NULL DEFAULT 'moi',
  `tieu_de_xu_ly` varchar(255) DEFAULT NULL COMMENT 'Tiêu đề admin xử lý',
  `noi_dung_xu_ly` text DEFAULT NULL COMMENT 'Nội dung admin xử lý'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Đang đổ dữ liệu cho bảng `phan_anh`
--

INSERT INTO `phan_anh` (`id_phan_anh`, `id_nguoi_dung`, `id_don_hang`, `chu_de`, `noi_dung`, `anh_1`, `anh_2`, `anh_3`, `ngay_gui`, `trang_thai`, `tieu_de_xu_ly`, `noi_dung_xu_ly`) VALUES
(6, 16, 6, 'sản phẩm lỗi', 'ewrwrw', NULL, NULL, NULL, '2025-11-11 15:07:00', 'da_giai_quyet', 'thằng chó thắng', 'lỗi cc');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `quang_cao_slider`
--

CREATE TABLE `quang_cao_slider` (
  `id_qc` int(11) NOT NULL,
  `hinh_anh` varchar(255) NOT NULL,
  `link_dich` varchar(500) DEFAULT NULL,
  `noi_dung_ghi_chu` varchar(500) DEFAULT NULL,
  `trang_thai` enum('hien_thi','bi_an') NOT NULL DEFAULT 'bi_an',
  `ngay_bat_dau` date NOT NULL,
  `ngay_ket_thuc` date NOT NULL,
  `vi_tri` int(11) NOT NULL DEFAULT 0 COMMENT 'Thứ tự sắp xếp'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `san_pham`
--

CREATE TABLE `san_pham` (
  `id` int(11) NOT NULL,
  `ten_san_pham` varchar(255) NOT NULL,
  `id_hang` int(10) UNSIGNED NOT NULL COMMENT 'Khóa ngoại liên kết với hang_san_xuat',
  `ma_san_pham` varchar(100) DEFAULT NULL,
  `mau_sac` varchar(50) NOT NULL COMMENT 'Màu sắc chính của sản phẩm',
  `gia_ban` decimal(12,0) NOT NULL DEFAULT 0,
  `gia_goc` decimal(12,0) DEFAULT NULL,
  `phan_tram_giam_gia` tinyint(3) UNSIGNED DEFAULT NULL,
  `ngay_bat_dau_giam` date DEFAULT NULL,
  `ngay_ket_thuc_giam` date DEFAULT NULL,
  `so_luong_ton` int(11) NOT NULL DEFAULT 0,
  `so_luong_da_ban` int(11) NOT NULL DEFAULT 0 COMMENT 'Số lượng đã bán',
  `avg_rating` decimal(3,2) NOT NULL DEFAULT 0.00 COMMENT 'Điểm sao trung bình',
  `total_reviews` int(11) NOT NULL DEFAULT 0 COMMENT 'Tổng số lượt đánh giá',
  `anh_dai_dien` varchar(255) DEFAULT NULL COMMENT 'Đường dẫn ảnh đại diện SP',
  `mo_ta_ngan` text DEFAULT NULL,
  `mo_ta_chi_tiet` longtext DEFAULT NULL,
  `trang_thai` enum('hiện','ẩn','hết hàng') NOT NULL DEFAULT 'hiện',
  `ngay_tao` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `san_pham`
--

INSERT INTO `san_pham` (`id`, `ten_san_pham`, `id_hang`, `ma_san_pham`, `mau_sac`, `gia_ban`, `gia_goc`, `phan_tram_giam_gia`, `ngay_bat_dau_giam`, `ngay_ket_thuc_giam`, `so_luong_ton`, `so_luong_da_ban`, `avg_rating`, `total_reviews`, `anh_dai_dien`, `mo_ta_ngan`, `mo_ta_chi_tiet`, `trang_thai`, `ngay_tao`, `ngay_cap_nhat`) VALUES
(24, 'Samsung Galaxy S24 Ultra', 1, 'SSS24U', 'Đen', 40000000, 32990000, 10, '2025-11-13', '2025-11-28', 40, 5, 5.00, 1, 'sp_690d50a89528d1.83160844.webp', 'Flagship mới nhất của Samsung', 'Màn hình Dynamic AMOLED 2X, chip Snapdragon 8 Gen 3, camera 200MP', 'hiện', '2025-11-07 01:42:47', '2025-11-13 03:00:00'),
(25, 'Samsung Galaxy A55', 1, 'SSA55', 'Xanh dương', 9990000, 10990000, 10, '2025-11-01', '2025-11-15', 69, 8, 5.00, 1, 'sp_690d518a033305.96398371.webp', 'Tầm trung mạnh mẽ', 'Màn hình 120Hz, chip Exynos 1480, pin 5000mAh', 'hiện', '2025-11-07 01:42:47', '2025-11-13 02:56:10'),
(26, 'iPhone 16 Pro Max', 2, 'IP16PM', 'Titan Tự nhiên', 35990000, 38990000, 8, '2025-11-01', '2025-11-15', 60, 0, 0.00, 0, NULL, 'Flagship cao cấp nhất của Apple', 'Chip A18 Pro, camera cải tiến, màn hình 120Hz ProMotion', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(27, 'iPhone 15', 2, 'IP15', 'Hồng', 21990000, 23990000, 8, '2025-11-01', '2025-11-15', 100, 0, 0.00, 0, NULL, 'Phiên bản phổ thông mạnh mẽ', 'Chip A16 Bionic, camera kép, USB-C', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(28, 'Xiaomi 14 Ultra', 3, 'XM14U', 'Trắng', 25990000, 27990000, 7, '2025-11-01', '2025-11-15', 40, 0, 0.00, 0, NULL, 'Camera Leica cực đỉnh', 'Snapdragon 8 Gen 3, màn AMOLED 2K 120Hz', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(29, 'Redmi Note 13 Pro+', 3, 'RN13P+', 'Tím', 9990000, 10990000, 9, '2025-11-01', '2025-11-10', 120, 0, 0.00, 0, NULL, 'Điện thoại tầm trung đáng mua', 'Camera 200MP, màn AMOLED 120Hz', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(30, 'OPPO Find X7 Pro', 4, 'OFX7P', 'Đen nhám', 24990000, 26990000, 7, '2025-11-01', '2025-11-15', 35, 0, 0.00, 0, NULL, 'Flagship chụp ảnh đẹp', 'Camera Hasselblad, chip Snapdragon 8 Gen 3', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(31, 'OPPO Reno11', 4, 'OR11', 'Xanh bạc hà', 8990000, 9990000, 10, '2025-11-01', '2025-11-10', 100, 0, 0.00, 0, NULL, 'Điện thoại tầm trung thời trang', 'Chip Dimensity 7050, pin 5000mAh sạc 67W', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(32, 'Vivo X100 Pro', 5, 'VX100P', 'Đen', 23990000, 25990000, 8, '2025-11-01', '2025-11-15', 40, 0, 0.00, 0, NULL, 'Camera Zeiss hàng đầu', 'Dimensity 9300, màn AMOLED 2K 120Hz', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(33, 'Vivo V30', 5, 'VV30', 'Xanh lục bảo', 8990000, 9990000, 10, '2025-11-01', '2025-11-10', 90, 0, 0.00, 0, NULL, 'Điện thoại tầm trung mỏng nhẹ', 'Snapdragon 7 Gen 3, sạc nhanh 80W', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(34, 'OnePlus 12', 6, 'OP12', 'Xanh', 21990000, 23990000, 8, '2025-11-01', '2025-11-15', 55, 0, 0.00, 0, NULL, 'Flagship hiệu năng cao', 'Snapdragon 8 Gen 3, màn AMOLED LTPO', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(35, 'OnePlus Nord 4', 6, 'OPN4', 'Bạc', 9990000, 10990000, 9, '2025-11-01', '2025-11-10', 85, 0, 0.00, 0, NULL, 'Dòng Nord mới nhất', 'Snapdragon 7 Gen 3, pin 5000mAh', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(36, 'Realme GT6 Pro', 7, 'RGT6P', 'Vàng', 14990000, 15990000, 6, '2025-11-01', '2025-11-10', 90, 0, 0.00, 0, NULL, 'Hiệu năng vượt trội', 'Snapdragon 8s Gen 3, màn 144Hz', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(37, 'Realme 12 Pro+', 7, 'R12P+', 'Xanh nước biển', 9990000, 10990000, 9, '2025-11-01', '2025-11-10', 120, 0, 0.00, 0, NULL, 'Thiết kế sang trọng, camera tele', 'Snapdragon 7s Gen 2, pin 5000mAh', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(38, 'Google Pixel 9 Pro', 8, 'GP9P', 'Đen', 25990000, 27990000, 7, '2025-11-01', '2025-11-15', 44, 1, 0.00, 0, NULL, 'Điện thoại AI tiên tiến', 'Tensor G4, camera cực tốt, Android gốc', 'hiện', '2025-11-07 01:42:47', '2025-11-11 15:43:13'),
(39, 'Google Pixel 8a', 8, 'GP8A', 'Xanh nhạt', 13990000, 14990000, 6, '2025-11-01', '2025-11-10', 75, 0, 0.00, 0, NULL, 'Bản giá rẻ dòng Pixel', 'Tensor G3, camera 64MP', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(40, 'Sony Xperia 1 VI', 9, 'SX1VI', 'Tím', 29990000, 31990000, 6, '2025-11-01', '2025-11-15', 30, 0, 0.00, 0, NULL, 'Điện thoại quay phim chuyên nghiệp', 'Màn 4K HDR OLED, Snapdragon 8 Gen 3', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(41, 'Sony Xperia 10 VI', 9, 'SX10VI', 'Trắng', 10990000, 11990000, 8, '2025-11-01', '2025-11-15', 50, 0, 0.00, 0, NULL, 'Tầm trung chất lượng Nhật', 'Màn OLED, Snapdragon 6 Gen 1', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(42, 'Huawei P70 Pro', 12, 'HP70P', 'Xanh lam', 24990000, 26990000, 7, '2025-11-01', '2025-11-15', 40, 0, 0.00, 0, NULL, 'Flagship với camera Leica', 'Kirin 9010, màn OLED 120Hz', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47'),
(43, 'Huawei Nova 13', 12, 'HN13', 'Tím pastel', 9990000, 10990000, 9, '2025-11-01', '2025-11-10', 80, 0, 0.00, 0, NULL, 'Dòng Nova thời trang', 'Kirin 820E, camera 108MP', 'hiện', '2025-11-07 01:42:47', '2025-11-07 01:42:47');

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `thong_so_ky_thuat`
--

CREATE TABLE `thong_so_ky_thuat` (
  `id` int(11) NOT NULL,
  `id_san_pham` int(11) NOT NULL COMMENT 'Khóa ngoại liên kết với san_pham',
  `man_hinh` varchar(100) DEFAULT NULL,
  `do_phan_giai` varchar(100) DEFAULT NULL,
  `tan_so_quet` varchar(50) DEFAULT NULL,
  `chip_xu_ly` varchar(100) DEFAULT NULL,
  `gpu` varchar(100) DEFAULT NULL,
  `ram` varchar(50) DEFAULT NULL,
  `rom` varchar(50) DEFAULT NULL,
  `he_dieu_hanh` varchar(100) DEFAULT NULL,
  `camera_sau` varchar(255) DEFAULT NULL,
  `camera_truoc` varchar(100) DEFAULT NULL,
  `dung_luong_pin` varchar(50) DEFAULT NULL,
  `sac` varchar(50) DEFAULT NULL,
  `ket_noi` varchar(255) DEFAULT NULL,
  `sim` varchar(100) DEFAULT NULL,
  `trong_luong` varchar(50) DEFAULT NULL,
  `chat_lieu` varchar(100) DEFAULT NULL,
  `khang_nuoc_bui` varchar(50) DEFAULT NULL,
  `bao_mat` varchar(100) DEFAULT NULL,
  `anh_phu_1` varchar(255) DEFAULT NULL,
  `anh_phu_2` varchar(255) DEFAULT NULL,
  `anh_phu_3` varchar(255) DEFAULT NULL,
  `anh_phu_4` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `thong_so_ky_thuat`
--

INSERT INTO `thong_so_ky_thuat` (`id`, `id_san_pham`, `man_hinh`, `do_phan_giai`, `tan_so_quet`, `chip_xu_ly`, `gpu`, `ram`, `rom`, `he_dieu_hanh`, `camera_sau`, `camera_truoc`, `dung_luong_pin`, `sac`, `ket_noi`, `sim`, `trong_luong`, `chat_lieu`, `khang_nuoc_bui`, `bao_mat`, `anh_phu_1`, `anh_phu_2`, `anh_phu_3`, `anh_phu_4`) VALUES
(24, 24, '6.8', 'QHD+', '120Hz', 'Snapdragon 8 Gen 3', 'Adreno 750', '12GB', '256GB', 'Android 14', '200MP + 12MP + 10MP', '12MP', '5000mAh', '45W', '5G, Wi-Fi 7', '2 SIM', '233g', 'Titanium', 'IP68', 'Vân tay, FaceID', 'sp_690d50a8955004.83800267.webp', 'sp_690d50a8956d13.43086757.webp', 'sp_690d50a8958954.84819333.webp', 'sp_690d50a895acc8.31113186.webp'),
(25, 25, '6.6\\\" Super AMOLED', 'FHD+', '120Hz', 'Exynos 1480', 'Xclipse 530', '8GB', '128GB', 'Android 14', '50MP + 12MP', '32MP', '5000mAh', '25W', '5G', '2 SIM', '189g', 'Nhôm + Kính', 'IP67', 'Vân tay dưới màn', 'sp_690d518a034e82.57693427.webp', 'sp_690d518a0362c6.42597552.webp', 'sp_690d518a037572.81292048.webp', 'sp_690d518a038742.88704665.webp'),
(26, 26, '6.9\" OLED', 'Super Retina XDR', '120Hz', 'A18 Pro', 'Apple GPU', '8GB', '256GB', 'iOS 18', '48MP + 12MP + 12MP', '12MP', '4500mAh', '20W', '5G, Wi-Fi 6E', '1 SIM', '221g', 'Titanium', 'IP68', 'FaceID', NULL, NULL, NULL, NULL),
(27, 27, '6.1\" OLED', 'Super Retina XDR', '60Hz', 'A16 Bionic', 'Apple GPU', '6GB', '128GB', 'iOS 17', '48MP + 12MP', '12MP', '3500mAh', '20W', '5G', '1 SIM', '187g', 'Nhôm', 'IP68', 'FaceID', NULL, NULL, NULL, NULL),
(28, 28, '6.73\" AMOLED', '2K', '120Hz', 'Snapdragon 8 Gen 3', 'Adreno 750', '16GB', '512GB', 'Android 14', '50MP + 50MP + 50MP', '32MP', '5000mAh', '90W', '5G', '2 SIM', '223g', 'Da + Nhôm', 'IP68', 'Vân tay', NULL, NULL, NULL, NULL),
(29, 29, '6.67\" AMOLED', 'FHD+', '120Hz', 'Dimensity 7200 Ultra', 'Mali-G610', '8GB', '256GB', 'Android 14', '200MP + 8MP + 2MP', '16MP', '5100mAh', '67W', '5G', '2 SIM', '187g', 'Nhựa', 'Không', 'Vân tay cạnh', NULL, NULL, NULL, NULL),
(30, 30, '6.82\" AMOLED', 'QHD+', '120Hz', 'Snapdragon 8 Gen 3', 'Adreno 750', '12GB', '256GB', 'Android 14', '50MP + 50MP + 50MP', '32MP', '5000mAh', '100W', '5G', '2 SIM', '215g', 'Nhôm + Kính', 'IP68', 'Vân tay quang học', NULL, NULL, NULL, NULL),
(31, 31, '6.7\" AMOLED', 'FHD+', '120Hz', 'Dimensity 7050', 'Mali-G68', '8GB', '256GB', 'Android 14', '64MP + 8MP + 2MP', '32MP', '5000mAh', '67W', '5G', '2 SIM', '181g', 'Nhôm', 'IP54', 'Vân tay dưới màn', NULL, NULL, NULL, NULL),
(32, 32, '6.78\" AMOLED', '2K', '120Hz', 'Dimensity 9300', 'Immortalis-G720', '16GB', '512GB', 'Android 14', '50MP + 50MP + 64MP', '32MP', '5400mAh', '100W', '5G', '2 SIM', '225g', 'Kính', 'IP68', 'Vân tay dưới màn', NULL, NULL, NULL, NULL),
(33, 33, '6.78\" AMOLED', 'FHD+', '120Hz', 'Snapdragon 7 Gen 3', 'Adreno 720', '8GB', '256GB', 'Android 14', '50MP + 8MP + 2MP', '32MP', '5000mAh', '80W', '5G', '2 SIM', '185g', 'Nhôm', 'Không', 'Vân tay quang học', NULL, NULL, NULL, NULL),
(34, 34, '6.82\" AMOLED', 'QHD+', '120Hz', 'Snapdragon 8 Gen 3', 'Adreno 750', '16GB', '512GB', 'Android 14', '50MP + 48MP + 64MP', '32MP', '5400mAh', '100W', '5G', '2 SIM', '220g', 'Nhôm', 'IP68', 'Vân tay dưới màn', NULL, NULL, NULL, NULL),
(35, 35, '6.74\" AMOLED', 'FHD+', '120Hz', 'Snapdragon 7 Gen 3', 'Adreno 720', '8GB', '256GB', 'Android 14', '50MP + 8MP + 2MP', '16MP', '5000mAh', '80W', '5G', '2 SIM', '195g', 'Nhôm', 'Không', 'Vân tay cạnh', NULL, NULL, NULL, NULL),
(36, 36, '6.78\" AMOLED', 'FHD+', '144Hz', 'Snapdragon 8s Gen 3', 'Adreno 735', '12GB', '256GB', 'Android 14', '50MP + 8MP + 2MP', '32MP', '5500mAh', '120W', '5G', '2 SIM', '210g', 'Nhôm', 'Không', 'Vân tay dưới màn', NULL, NULL, NULL, NULL),
(37, 37, '6.7\" AMOLED', 'FHD+', '120Hz', 'Snapdragon 7s Gen 2', 'Adreno 710', '8GB', '256GB', 'Android 14', '50MP + 8MP + 32MP', '32MP', '5000mAh', '67W', '5G', '2 SIM', '190g', 'Nhôm', 'Không', 'Vân tay dưới màn', NULL, NULL, NULL, NULL),
(38, 38, '6.8\" OLED', 'QHD+', '120Hz', 'Tensor G4', 'Mali-G715', '12GB', '256GB', 'Android 15', '50MP + 48MP + 64MP', '32MP', '5050mAh', '30W', '5G', '1 SIM', '223g', 'Nhôm', 'IP68', 'FaceID, Vân tay', NULL, NULL, NULL, NULL),
(39, 39, '6.1\" OLED', 'FHD+', '120Hz', 'Tensor G3', 'Mali-G710', '8GB', '128GB', 'Android 14', '64MP + 13MP', '13MP', '4492mAh', '27W', '5G', '1 SIM', '187g', 'Nhựa', 'IP67', 'Vân tay', NULL, NULL, NULL, NULL),
(40, 40, '6.5\" OLED', '4K HDR', '120Hz', 'Snapdragon 8 Gen 3', 'Adreno 750', '12GB', '256GB', 'Android 14', '48MP + 12MP + 12MP', '12MP', '5000mAh', '30W', '5G', '2 SIM', '187g', 'Nhôm', 'IP68', 'Vân tay cạnh', NULL, NULL, NULL, NULL),
(41, 41, '6.1\" OLED', 'FHD+', '60Hz', 'Snapdragon 6 Gen 1', 'Adreno 710', '8GB', '128GB', 'Android 14', '48MP + 8MP + 2MP', '12MP', '5000mAh', '30W', '5G', '2 SIM', '177g', 'Nhựa', 'Không', 'Vân tay', NULL, NULL, NULL, NULL),
(42, 42, '6.7\" OLED', '2K', '120Hz', 'Kirin 9010', 'Mali-G720', '12GB', '512GB', 'HarmonyOS 4', '50MP + 48MP + 12MP', '32MP', '5000mAh', '88W', '5G', '2 SIM', '218g', 'Kính + Nhôm', 'IP68', 'Vân tay quang học', NULL, NULL, NULL, NULL),
(43, 43, '6.7\" OLED', 'FHD+', '120Hz', 'Kirin 820E', 'Mali-G57', '8GB', '256GB', 'HarmonyOS 4', '108MP + 8MP + 2MP', '32MP', '5000mAh', '66W', '5G', '2 SIM', '190g', 'Nhựa', 'Không', 'Vân tay cạnh', NULL, NULL, NULL, NULL);

-- --------------------------------------------------------

--
-- Cấu trúc bảng cho bảng `tin_tuc`
--

CREATE TABLE `tin_tuc` (
  `id_tin_tuc` int(10) UNSIGNED NOT NULL,
  `tieu_de` varchar(255) NOT NULL,
  `anh_dai_dien` varchar(255) DEFAULT NULL COMMENT 'Hình ảnh chính của bài viết',
  `anh_1` varchar(255) DEFAULT NULL,
  `noi_dung_1` text DEFAULT NULL,
  `anh_2` varchar(255) DEFAULT NULL,
  `noi_dung_2` text DEFAULT NULL,
  `anh_3` varchar(255) DEFAULT NULL,
  `noi_dung_3` text DEFAULT NULL,
  `id_nguoi_dang` int(10) UNSIGNED DEFAULT NULL COMMENT 'Khóa ngoại, liên kết với admin đã đăng bài',
  `trang_thai` enum('hien_thi','an','cho_duyet') NOT NULL DEFAULT 'hien_thi',
  `ngay_dang` timestamp NOT NULL DEFAULT current_timestamp(),
  `ngay_cap_nhat` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Đang đổ dữ liệu cho bảng `tin_tuc`
--

INSERT INTO `tin_tuc` (`id_tin_tuc`, `tieu_de`, `anh_dai_dien`, `anh_1`, `noi_dung_1`, `anh_2`, `noi_dung_2`, `anh_3`, `noi_dung_3`, `id_nguoi_dang`, `trang_thai`, `ngay_dang`, `ngay_cap_nhat`) VALUES
(2, 'Chân dung bộ tứ iPhone 17 ra mắt đêm nay', 'tt_690b75f9b60fc0.25351003.png', 'tt_690b75f9b642e9.97481543.png', 'Theo tin đồn, iPhone 17 sẽ có sự thay đổi về thiết kế với cụm camera lớn hơn, chứa cả đèn flash và micro bên cạnh hai ống kính. Màn hình dự kiến tăng từ 6,1 inch lên 6,3 inch – ngang với bản Pro. Apple cũng được cho là sẽ trang bị tấm nền 120Hz, dù chưa rõ có phải loại ProMotion 1–120Hz hay không.\\\\r\\\\n\\\\r\\\\nVề hiệu năng, máy dùng chip A19 sản xuất trên tiến trình 3nm thế hệ ba của TSMC, RAM dự kiến 8GB và có tùy chọn bộ nhớ 128GB.\\\\r\\\\n\\\\r\\\\nCamera sau vẫn giữ cụm 48MP + 12MP, nhưng camera trước nâng lên 24MP. Pin ở mức 3.692 mAh, hỗ trợ sạc nhanh không dây Qi2.2 với tốc độ 25W. Giá khởi điểm được kỳ vọng giữ ở mức 799 USD.', 'tt_690b75f9b69eb0.36723765.png', 'iPhone 17 Air gây chú ý nhờ độ mỏng chỉ 5,5mm ở điểm mỏng nhất cùng màn hình 6.6 inch. Máy được cho là sở hữu chip A19 Pro nhưng bị giảm một lõi GPU, RAM 8GB và bộ nhớ khởi điểm 256GB.\\\\r\\\\n\\\\r\\\\nMột số tin đồn khẳng định iPhone 17 Air là iPhone mới duy nhất dùng khung titan để đạt độ nhẹ mong muốn, đồng thời cải thiện độ bền so với khung nhôm trên iPhone 17 và 17 Pro.\\\\r\\\\n\\\\r\\\\nKhác biệt lớn tiếp theo nằm ở camera: chỉ có một cảm biến 48MP duy nhất ở mặt sau. Phía trước là camera selfie 24MP giống các bản iPhone 17 khác.\\\\r\\\\n\\\\r\\\\nPin dao động 3.036 - 3.148 mAh tùy phiên bản SIM/eSIM, sử dụng công nghệ pin mới để bù cho dung lượng thấp. iPhone 17 Air có thể dùng modem C1 mới của Apple thay vì Qualcomm để kết nối mạng.\\\\r\\\\n\\\\r\\\\nGiá bán dự kiến 1.099 USD, cao hơn 300 USD so với iPhone 17.', 'tt_690b75f9b6d090.52558068.webp', 'iPhone 17 Pro và 17 Pro Max được cho là thay đổi lớn ở thiết kế với thanh camera chạy ngang lưng máy. Cả hai bổ sung camera tele 48MP, nâng tổng số lên ba ống kính. Zoom quang học có thể thay đổi, từ giảm xuống 3,5x đến tăng lên 8x tùy phiên bản. Kích thước màn hình lần lượt 6.3 và 6.9 inch, hỗ trợ ProMotion 1- 120Hz. Chip A19 Pro đi kèm RAM 12GB và hệ thống tản nhiệt buồng hơi lần đầu xuất hiện trên iPhone, giúp duy trì hiệu suất AI.\\\\r\\\\n\\\\r\\\\nPin cũng được cải thiện đáng kể: iPhone 17 Pro có thể đạt 4.252mAh ở bản eSIM, còn 17 Pro Max chạm mốc 5.088mAh - lần đầu iPhone vượt ngưỡng 5.000mAh. Cả hai được đồn sẽ nâng tốc độ sạc có dây lên 35W, hỗ trợ Qi2.2 không dây 15W. Giá khởi điểm iPhone 17 Pro dự kiến từ 1.099 USD, tăng tối thiểu 100 USD, với bộ nhớ bắt đầu từ 256GB.\\\\r\\\\n\\\\r\\\\nSự xuất hiện của iPhone 17 Air cùng những nâng cấp mạnh mẽ trên bản Pro khiến dòng sản phẩm năm nay được kỳ vọng sẽ tạo khác biệt rõ rệt, thay vì những cải tiến nhỏ lẻ như các thế hệ gần đây.', 16, 'hien_thi', '2025-11-05 16:06:17', '2025-11-10 01:29:49'),
(3, 'Galaxy S26 sẽ có loạt tính năng camera cực đỉnh nhờ chip Exynos 2600', 'news_16_691142e862b271762738920.jpg', 'news_69114454a70c41762739284.jpg', 'Rò rỉ mới vừa tiếp tục tiết lộ cho chúng ta những thông tin liên quan đến camera các flagship tiếp theo đến từ Samsung thuộc dòng Galaxy S26.\r\n\r\nTrong những năm qua, bộ xử lý hình ảnh (ISP) trên các chipset Exynos thường không được đánh giá cao bằng Snapdragon, và điều này đã được kiểm chứng bằng các bài so sánh camera. Tuy nhiên, theo rò rỉ mới nhất thì chip Exynos 2600 dự kiến sẽ nâng tầm trải nghiệm nhiếp ảnh của người dùng trên dòng Galaxy S26 ra mắt vào năm sau.', 'news_16_691144291ee9f1762739241.jpg', 'Nguồn tin hôm nay cho biết Samsung hiện đang phát triển lại hoàn toàn hệ thống xử lý hình ảnh cho chip Exynos 2600, hứa hẹn mang đến trải nghiệm chơi game ở chất lượng console cùng loạt tính năng camera chuyên nghiệp và chất lượng ảnh/video vượt trội cho dòng Galaxy S26.', 'news_16_691144291f0a71762739241.jpg', 'Cụ thể, ISP của Exynos 2600 được cho là có thể xử lý cảm biến 320MP đơn hoặc ba cảm biến 108MP cùng lúc, hỗ trợ pipeline RAW 14-bit với HDR ghép khung 5 lần (frame fusion). Chip này có thể quay video 8K ở chất lượng 4K60 HDR10+, hoặc 4K@120fps. Ở chế độ chụp liên tiếp (burst), ISP đạt tới 30 khung hình/giây ở chất lượng RAW 108MP. Băng thông giữa ISP và NPU được ước tính lên tới 1.8 TB/s.\r\nCác tính năng khác bao gồm chống rung lai (Hybrid OIS), ổn định hình ảnh AI-EIS thời gian thực, cùng phân tích cảnh bằng AI và zoom siêu phân giải (super-res zoom). Đặc biệt, ISP của Exynos 2600 được cho là tiết kiệm điện hơn 30% so với ISP trên Exynos 2400. Cùng chờ xem các flagship thế hệ mới của Samsung sẽ cho trải nghiệm nhiếp ảnh như thế nào nhé.\r\n\r\nNguồn: notebookcheck', 16, 'hien_thi', '2025-11-10 01:42:00', '2025-11-10 01:48:04');

--
-- Chỉ mục cho các bảng đã đổ
--

--
-- Chỉ mục cho bảng `binh_luan`
--
ALTER TABLE `binh_luan`
  ADD PRIMARY KEY (`id_binh_luan`),
  ADD KEY `fk_binh_luan_tin_tuc` (`id_tin_tuc`),
  ADD KEY `fk_binh_luan_nguoi_dung` (`id_nguoi_dung`);

--
-- Chỉ mục cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD PRIMARY KEY (`id_chi_tiet`),
  ADD KEY `fk_chi_tiet_don_hang` (`id_don_hang`),
  ADD KEY `fk_chi_tiet_san_pham` (`id_san_pham`);

--
-- Chỉ mục cho bảng `danh_gia_san_pham`
--
ALTER TABLE `danh_gia_san_pham`
  ADD PRIMARY KEY (`id_danh_gia`),
  ADD KEY `id_san_pham` (`id_san_pham`),
  ADD KEY `id_nguoi_dung` (`id_nguoi_dung`);

--
-- Chỉ mục cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD PRIMARY KEY (`id_don_hang`),
  ADD UNIQUE KEY `idx_ma_don_hang` (`ma_don_hang`),
  ADD KEY `fk_don_hang_nguoi_dung` (`id_nguoi_dung`);

--
-- Chỉ mục cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD PRIMARY KEY (`id_gio_hang`),
  ADD UNIQUE KEY `idx_nguoi_dung_san_pham` (`id_nguoi_dung`,`id_san_pham`),
  ADD KEY `fk_gio_hang_san_pham` (`id_san_pham`);

--
-- Chỉ mục cho bảng `hang_san_xuat`
--
ALTER TABLE `hang_san_xuat`
  ADD PRIMARY KEY (`id_hang`),
  ADD UNIQUE KEY `idx_ten_hang_duy_nhat` (`ten_hang`);

--
-- Chỉ mục cho bảng `lien_he`
--
ALTER TABLE `lien_he`
  ADD PRIMARY KEY (`id_lien_he`),
  ADD KEY `fk_lien_he_nguoi_dung` (`id_nguoi_dung`);

--
-- Chỉ mục cho bảng `ma_giam_gia`
--
ALTER TABLE `ma_giam_gia`
  ADD PRIMARY KEY (`id_giam_gia`),
  ADD UNIQUE KEY `idx_ma_code_duy_nhat` (`ma_code`);

--
-- Chỉ mục cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  ADD PRIMARY KEY (`id_nguoi_dung`),
  ADD UNIQUE KEY `idx_email_duy_nhat` (`email`),
  ADD UNIQUE KEY `idx_sdt_duy_nhat` (`so_dien_thoai`),
  ADD UNIQUE KEY `idx_cccd_duy_nhat` (`so_cccd`);

--
-- Chỉ mục cho bảng `phan_anh`
--
ALTER TABLE `phan_anh`
  ADD PRIMARY KEY (`id_phan_anh`),
  ADD KEY `fk_phan_anh_nguoi_dung` (`id_nguoi_dung`),
  ADD KEY `fk_phan_anh_don_hang` (`id_don_hang`);

--
-- Chỉ mục cho bảng `quang_cao_slider`
--
ALTER TABLE `quang_cao_slider`
  ADD PRIMARY KEY (`id_qc`),
  ADD KEY `idx_trang_thai_ngay` (`trang_thai`,`ngay_bat_dau`,`ngay_ket_thuc`);

--
-- Chỉ mục cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_san_pham_hang` (`id_hang`);

--
-- Chỉ mục cho bảng `thong_so_ky_thuat`
--
ALTER TABLE `thong_so_ky_thuat`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `idx_id_san_pham_duy_nhat` (`id_san_pham`);

--
-- Chỉ mục cho bảng `tin_tuc`
--
ALTER TABLE `tin_tuc`
  ADD PRIMARY KEY (`id_tin_tuc`),
  ADD KEY `fk_tin_tuc_nguoi_dang` (`id_nguoi_dang`);

--
-- AUTO_INCREMENT cho các bảng đã đổ
--

--
-- AUTO_INCREMENT cho bảng `binh_luan`
--
ALTER TABLE `binh_luan`
  MODIFY `id_binh_luan` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  MODIFY `id_chi_tiet` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=32;

--
-- AUTO_INCREMENT cho bảng `danh_gia_san_pham`
--
ALTER TABLE `danh_gia_san_pham`
  MODIFY `id_danh_gia` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  MODIFY `id_don_hang` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  MODIFY `id_gio_hang` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=90;

--
-- AUTO_INCREMENT cho bảng `hang_san_xuat`
--
ALTER TABLE `hang_san_xuat`
  MODIFY `id_hang` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT cho bảng `lien_he`
--
ALTER TABLE `lien_he`
  MODIFY `id_lien_he` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT cho bảng `ma_giam_gia`
--
ALTER TABLE `ma_giam_gia`
  MODIFY `id_giam_gia` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=155;

--
-- AUTO_INCREMENT cho bảng `nguoi_dung`
--
ALTER TABLE `nguoi_dung`
  MODIFY `id_nguoi_dung` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=20;

--
-- AUTO_INCREMENT cho bảng `phan_anh`
--
ALTER TABLE `phan_anh`
  MODIFY `id_phan_anh` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT cho bảng `quang_cao_slider`
--
ALTER TABLE `quang_cao_slider`
  MODIFY `id_qc` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT cho bảng `thong_so_ky_thuat`
--
ALTER TABLE `thong_so_ky_thuat`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- AUTO_INCREMENT cho bảng `tin_tuc`
--
ALTER TABLE `tin_tuc`
  MODIFY `id_tin_tuc` int(10) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- Các ràng buộc cho các bảng đã đổ
--

--
-- Các ràng buộc cho bảng `binh_luan`
--
ALTER TABLE `binh_luan`
  ADD CONSTRAINT `fk_binh_luan_nguoi_dung` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id_nguoi_dung`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_binh_luan_tin_tuc` FOREIGN KEY (`id_tin_tuc`) REFERENCES `tin_tuc` (`id_tin_tuc`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `chi_tiet_don_hang`
--
ALTER TABLE `chi_tiet_don_hang`
  ADD CONSTRAINT `fk_chi_tiet_don_hang` FOREIGN KEY (`id_don_hang`) REFERENCES `don_hang` (`id_don_hang`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_chi_tiet_san_pham` FOREIGN KEY (`id_san_pham`) REFERENCES `san_pham` (`id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `don_hang`
--
ALTER TABLE `don_hang`
  ADD CONSTRAINT `fk_don_hang_nguoi_dung` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id_nguoi_dung`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `gio_hang`
--
ALTER TABLE `gio_hang`
  ADD CONSTRAINT `fk_gio_hang_nguoi_dung` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id_nguoi_dung`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_gio_hang_san_pham` FOREIGN KEY (`id_san_pham`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `lien_he`
--
ALTER TABLE `lien_he`
  ADD CONSTRAINT `fk_lien_he_nguoi_dung` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id_nguoi_dung`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `phan_anh`
--
ALTER TABLE `phan_anh`
  ADD CONSTRAINT `fk_phan_anh_don_hang` FOREIGN KEY (`id_don_hang`) REFERENCES `don_hang` (`id_don_hang`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `fk_phan_anh_nguoi_dung` FOREIGN KEY (`id_nguoi_dung`) REFERENCES `nguoi_dung` (`id_nguoi_dung`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `san_pham`
--
ALTER TABLE `san_pham`
  ADD CONSTRAINT `fk_san_pham_hang` FOREIGN KEY (`id_hang`) REFERENCES `hang_san_xuat` (`id_hang`) ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `thong_so_ky_thuat`
--
ALTER TABLE `thong_so_ky_thuat`
  ADD CONSTRAINT `fk_thong_so_san_pham` FOREIGN KEY (`id_san_pham`) REFERENCES `san_pham` (`id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Các ràng buộc cho bảng `tin_tuc`
--
ALTER TABLE `tin_tuc`
  ADD CONSTRAINT `fk_tin_tuc_nguoi_dang` FOREIGN KEY (`id_nguoi_dang`) REFERENCES `nguoi_dung` (`id_nguoi_dung`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
