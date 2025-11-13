<?php
// 1. GỌI LOGIC TRƯỚC TIÊN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. LOGIC LẤY SỐ LƯỢNG GIỎ HÀNG (CHO HEADER)
$cart_count = 0;
if (isset($_SESSION['id_nguoi_dung'])) {
    $id_nguoi_dung = $_SESSION['id_nguoi_dung'];
    $sql_count = "SELECT SUM(so_luong) as total FROM gio_hang WHERE id_nguoi_dung = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $id_nguoi_dung);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $cart_count = $result_count->fetch_assoc()['total'] ?? 0;
} elseif (isset($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += ($item['so_luong'] ?? 1);
    }
}

// 3. LOGIC LẤY DỮ LIỆU TRANG
$hom_nay = date('Y-m-d'); 

// --- 3.1. LẤY BANNER SỰ KIỆN ---
$active_event = null;
$sql_event = "SELECT * FROM quang_cao_slider 
              WHERE trang_thai = 'hien_thi' 
              AND ? BETWEEN ngay_bat_dau AND ngay_ket_thuc
              ORDER BY vi_tri ASC, id_qc DESC 
              LIMIT 1"; // Lấy 1 banner mới nhất
$stmt_event = $conn->prepare($sql_event);
$stmt_event->bind_param("s", $hom_nay);
$stmt_event->execute();
$result_event = $stmt_event->get_result();
if ($result_event->num_rows > 0) {
    $active_event = $result_event->fetch_assoc();
}

// Chuẩn bị biến cho HERO BANNER (Dựa trên template index.html)
$hero_title = "Sức Mạnh <span class='text-accent block lg:inline'>Trong Tay Bạn.</span>";
$hero_subtitle = "Khám phá những chiếc điện thoại thông minh mới nhất với thiết kế đột phá và hiệu năng vượt trội.";
$hero_cta_link = "#featured";
$hero_image_html = '
<div class="w-64 h-96 md:w-80 md:h-112 bg-gray-800 rounded-[3rem] shadow-[0_20px_50px_rgba(0,0,0,0.5)] border-8 border-gray-700 overflow-hidden transform rotate-3 hover:rotate-0 transition duration-500 ease-in-out animate-float">
    <div class="bg-accent h-2 w-16 mx-auto mt-4 rounded-full"></div>
    <div class="w-full h-full p-2">
        <div class="bg-primary-dark/50 text-center flex flex-col items-center justify-center h-full text-gray-400 text-sm">
            <svg class="w-12 h-12 text-accent mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            <p class="font-bold">Màn Hình Vô Cực</p>
            <p>Tận hưởng trải nghiệm tuyệt đỉnh.</p>
        </div>
    </div>
</div>'; // HTML Mockup mặc định

if ($active_event) {
    // Nếu có sự kiện, ghi đè
    $hero_title = $active_event['noi_dung_ghi_chu'] ?? 'Sự Kiện Đặc Biệt'; 
    $hero_subtitle = 'Ưu đãi có hạn. Khám phá ngay!';
    $hero_cta_link = $active_event['link_dich'] ?? '#featured';
    
    $anh_path_event = 'tai_len/quang_cao/' . $active_event['hinh_anh'];
    if (file_exists($anh_path_event)) {
        // Thay thế HTML mockup bằng ảnh thật
        $hero_image_html = '<img src="'.$anh_path_event.'" alt="'.htmlspecialchars($hero_title).'" class="w-auto h-96 md:h-112 object-contain animate-float transform rotate-3">';
    }
}

// --- 3.2. LẤY SẢN PHẨM NỔI BẬT (MỚI NHẤT) ---
$featured_products = [];
$sql_featured = "SELECT s.*, h.ten_hang FROM san_pham s
                 JOIN hang_san_xuat h ON s.id_hang = h.id_hang
                 WHERE s.trang_thai = 'hiện'
                 ORDER BY s.ngay_cap_nhat DESC
                 LIMIT 3"; // Template chỉ có 3
$result_featured = $conn->query($sql_featured);
if ($result_featured) {
    while($row = $result_featured->fetch_assoc()) $featured_products[] = $row;
}

// --- 3.3. HÀM TÍNH GIÁ (QUAN TRỌNG) ---
function tinhGiaHienThi($sp, $hom_nay) {
    $gia_hien_thi = (float)$sp['gia_ban'];
    $gia_cu = !empty($sp['gia_goc']) ? (float)$sp['gia_goc'] : null;
    $phan_tram_hien_thi = null;
    $dang_giam_gia_theo_ngay = (
        !empty($sp['ngay_bat_dau_giam']) &&
        !empty($sp['ngay_ket_thuc_giam']) &&
        $hom_nay >= $sp['ngay_bat_dau_giam'] &&
        $hom_nay <= $sp['ngay_ket_thuc_giam']
    );
    if ($dang_giam_gia_theo_ngay && !empty($sp['phan_tram_giam_gia'])) {
        $gia_cu = $sp['gia_ban']; 
        $gia_hien_thi = $gia_cu * (1 - (float)$sp['phan_tram_giam_gia'] / 100);
        $phan_tram_hien_thi = (int)$sp['phan_tram_giam_gia'];
    } 
    else if (!empty($gia_cu) && $gia_cu > $gia_hien_thi) {
        $phan_tram_hien_thi = round((($gia_cu - $gia_hien_thi) / $gia_cu) * 100);
    }
    else {
        $gia_cu = null; 
    }
    return ['gia_hien_thi' => $gia_hien_thi, 'gia_cu' => $gia_cu, 'phan_tram' => $phan_tram_hien_thi];
}
?>

<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }

        /* 1. Custom Keyframe for Floating Effect */
        @keyframes float {
            0% {
                transform: translatey(0px) rotate(3deg);
            }
            50% {
                transform: translatey(-10px) rotate(1deg);
            }
            100% {
                transform: translatey(0px) rotate(3deg);
            }
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }

        /* 2. Custom Keyframe for subtle heartbeat on logo */
        @keyframes heartbeat {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        .animate-heartbeat {
            animation: heartbeat 1.5s ease-in-out infinite;
        }

        /* 3. Custom Keyframe for FadeInUp */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translate3d(0, 30px, 0);
            }
            to {
                opacity: 1;
                transform: translate3d(0, 0, 0);
            }
        }
        
        /* 4. IntersectionObserver helper classes */
        .animate-on-scroll {
            opacity: 0;
            will-change: opacity, transform;
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            transition-delay: 0.1s; /* Thêm 1 chút delay */
            transform: translateY(20px);
        }
        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* 5. Navbar scroll effect */
        #main-header {
            position: sticky;
            top: 0;
            z-index: 50;
            transition: background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            background-color: rgba(31, 41, 55, 0.95); /* bg-secondary-dark/95 */
            backdrop-filter: blur(4px);
        }
        /* (MỚI) CSS Cho giỏ hàng trong Header */
        .cart-badge {
            position: absolute;
            top: -8px;
            right: -10px;
            background-color: #EF4444; /* bg-red-500 */
            color: white;
            font-size: 0.75rem; /* 12px */
            font-weight: 700;
            border-radius: 9999px;
            width: 1.25rem; /* 20px */
            height: 1.25rem; /* 20px */
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* (MỚI) CSS cho Toast Notification */
        #toast-notification {
            position: fixed;
            top: 5rem; /* 80px */
            right: 1.5rem; /* 24px */
            z-index: 9999;
            max-width: 320px;
            background-color: #111827; /* bg-gray-900 */
            color: #ffffff;
            border-radius: 0.5rem; /* rounded-lg */
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); /* shadow-lg */
            padding: 1rem; /* p-4 */
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 0.3s ease, transform 0.3s ease;
        }
        #toast-notification.success {
             background-color: #059669; /* bg-emerald-600 */
        }
         #toast-notification.error {
             background-color: #DC2626; /* bg-red-600 */
        }
        #toast-notification.show {
            opacity: 1;
            transform: translateX(0);
        }
        #toast-icon {
            width: 1.25rem; /* w-5 */
            height: 1.25rem; /* h-5 */
            margin-right: 0.75rem; /* mr-3 */
        }
        #toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: #9CA3AF; /* text-gray-400 */
            cursor: pointer;
            padding: 0.25rem;
        }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'primary-dark': '#111827', // Gray 900
                        'secondary-dark': '#1F2937', // Gray 800
                        'accent': '#4F46E5', // Indigo 600
                        'accent-hover': '#4338CA', // Indigo 700
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-primary-dark text-white min-h-screen">

    <header id="main-header" class="shadow-lg">
        <nav class="container mx-auto px-4 sm:px-6 lg:px-8 py-4 flex justify-between items-center">
            <a href="index.php" class="text-2xl font-extrabold tracking-tight text-white animate-heartbeat">
                DIEN THOAI<span class="text-accent">.STORE</span>
            </a>

            <div class="hidden md:flex items-center space-x-8 font-medium">
                <a href="index.php" class="text-white hover:text-accent transition duration-200">Trang Chủ</a>
                <a href="#featured" class="text-gray-300 hover:text-accent transition duration-200">Sản Phẩm</a>
                <a href="tin_tuc.php" class="text-gray-300 hover:text-accent transition duration-200">Tin Tức</a>
                
                <a href="gio_hang.php" class="text-gray-300 hover:text-accent relative" id="cart-link" title="Giỏ hàng">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    <span id="cart-badge" class="cart-badge" style="<?php echo ($cart_count > 0) ? 'display: flex;' : 'display: none;'; ?>">
                        <?php echo $cart_count; ?>
                    </span>
                </a>
                
                <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                    <a href="thong_tin_tai_khoan.php" class="text-gray-300 hover:text-accent" title="Tài khoản">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    </a>
                    <a href="dang_xuat.php" class="text-gray-300 hover:text-accent" title="Đăng xuất" data-turbolinks="false">
                         <svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
                    </a>
                <?php else: ?>
                    <a href="dang_nhap.php" class="text-sm font-medium text-gray-300 hover:text-accent" data-turbolinks="false">Đăng nhập</a>
                    <a href="dang_ky.php" class="text-sm font-medium text-white bg-accent px-4 py-2 rounded-lg hover:bg-accent-hover" data-turbolinks="false">Đăng ký</a>
                <?php endif; ?>

                <a href="lien_he.php" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent-hover transition duration-300 shadow-md text-sm">Liên Hệ</a>
            </div>

            <button id="mobile-menu-btn" class="md:hidden text-white focus:outline-none">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
            </button>
        </nav>

        <div id="mobile-menu" class="hidden md:hidden bg-secondary-dark shadow-xl">
            <a href="index.php" class="block px-4 py-2 text-sm hover:bg-gray-700">Trang Chủ</a>
            <a href="#featured" class="block px-4 py-2 text-sm hover:bg-gray-700">Sản Phẩm</a>
            <a href="tin_tuc.php" class="block px-4 py-2 text-sm hover:bg-gray-700">Tin Tức</a>
            <a href="#features" class="block px-4 py-2 text-sm hover:bg-gray-700">Đặc Điểm</a>
            <a href="gio_hang.php" class="block px-4 py-2 text-sm hover:bg-gray-700">Giỏ Hàng (<?php echo $cart_count; ?>)</a>
            
            <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                 <a href="thong_tin_tai_khoan.php" class="block px-4 py-2 text-sm hover:bg-gray-700">Tài khoản</a>
                 <a href="dang_xuat.php" class="block px-4 py-2 text-sm hover:bg-gray-700">Đăng xuất</a>
            <?php else: ?>
                 <a href="dang_nhap.php" class="block px-4 py-2 text-sm hover:bg-gray-700">Đăng nhập</a>
                 <a href="dang_ky.php" class="block px-4 py-2 text-sm hover:bg-gray-700">Đăng ký</a>
            <?php endif; ?>

            <a href="lien_he.php" class="block px-4 py-2 text-sm hover:bg-gray-700 bg-accent m-2 rounded-lg text-center">Liên Hệ</a>
        </div>
    </header>

    <main>
        <section id="hero" class="relative overflow-hidden pt-16 md:pt-24 pb-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center justify-between">
                <div class="lg:w-1/2 text-center lg:text-left mb-10 lg:mb-0 animate-on-scroll">
                    <span class="text-accent text-lg font-semibold uppercase tracking-wider mb-3 block">
                        Công Nghệ Đỉnh Cao
                    </span>
                    <h1 class="text-5xl md:text-7xl font-extrabold leading-tight mb-6">
                        <?php echo $hero_title; ?>
                    </h1>
                    <p class="text-gray-400 text-lg mb-8 max-w-lg mx-auto lg:mx-0">
                        <?php echo htmlspecialchars($hero_subtitle); ?>
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center lg:justify-start space-y-4 sm:space-y-0 sm:space-x-4">
                        <a href="<?php echo htmlspecialchars($hero_cta_link); ?>" class="bg-accent text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:bg-accent-hover transition duration-300 transform hover:scale-105">
                            Xem Sản Phẩm Ngay
                        </a>
                    </div>
                </div>

                <div class="lg:w-1/2 relative flex justify-center lg:justify-end animate-on-scroll" style="transition-delay: 200ms;">
                    <?php echo $hero_image_html; // In ra HTML của ảnh (hoặc mockup) ?>
                </div>
            </div>
        </section>

        <section id="featured" class="py-16 md:py-24 bg-secondary-dark">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-center mb-4 animate-on-scroll">Sản Phẩm Nổi Bật</h2>
                <p class="text-xl text-center text-gray-400 mb-12 animate-on-scroll">Những chiếc điện thoại được săn đón nhất hiện nay.</p>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <?php if (empty($featured_products)): ?>
                        <p class="text-center col-span-3 text-gray-400">Chưa có sản phẩm nổi bật nào.</p>
                    <?php else: ?>
                        <?php foreach ($featured_products as $index => $sp): ?>
                            <?php $gia_data = tinhGiaHienThi($sp, $hom_nay); ?>
                            
                            <div class="bg-primary-dark p-6 rounded-2xl shadow-2xl hover:shadow-accent/50 transition duration-300 transform hover:-translate-y-2 border border-gray-700 animate-on-scroll"
                                 style="transition-delay: <?php echo $index * 100; ?>ms;">
                                
                                <div class="w-full h-48 mb-4 flex items-center justify-center bg-gray-700/50 rounded-xl overflow-hidden group">
                                    <?php 
                                    $anh_path = 'tai_len/san_pham/' . ($sp['anh_dai_dien'] ?? 'default.png');
                                    if (empty($sp['anh_dai_dien']) || !file_exists($anh_path)) {
                                        $anh_path = 'https://placehold.co/300x300/1F2937/4F46E5?text=No+Image';
                                    }
                                    ?>
                                    <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="block w-full h-full">
                                        <img src="<?php echo $anh_path; ?>" alt="<?php echo htmlspecialchars($sp['ten_san_pham']); ?>" 
                                             class="object-contain w-full h-full p-2 transition duration-300 group-hover:scale-105">
                                    </a>
                                </div>
                                <h3 class="text-2xl font-bold mb-2 truncate"><?php echo htmlspecialchars($sp['ten_san_pham']); ?></h3>
                                <p class="text-gray-400 mb-4 h-20 overflow-hidden">
                                    <?php echo htmlspecialchars($sp['ten_hang']); ?> - 
                                    <?php // Lấy mô tả ngắn, nếu không có thì dùng tên
                                    echo htmlspecialchars($sp['mo_ta_ngan'] ?? $sp['ten_san_pham']); 
                                    ?>
                                </p>
                                <div class="flex justify-between items-center">
                                    <span class="text-3xl font-extrabold text-accent">
                                        <?php echo number_format($gia_data['gia_hien_thi'], 0, ',', '.'); ?>đ
                                        <?php if ($gia_data['gia_cu']): ?>
                                            <span class="text-gray-500 text-lg line-through ml-2"><?php echo number_format($gia_data['gia_cu'], 0, ',', '.'); ?>đ</span>
                                        <?php endif; ?>
                                    </span>
                                    
                                    <form action="xu_ly_gio_hang.php" method="POST" class="add-to-cart-form" data-turbolinks="false">
                                        <input type="hidden" name="action" value="add">
                                        <input type="hidden" name="id_san_pham" value="<?php echo $sp['id']; ?>">
                                        <input type="hidden" name="so_luong" value="1">
                                        <button type="submit" class="bg-accent text-white py-2 px-4 rounded-lg font-semibold hover:bg-accent-hover transition duration-300">
                                            Mua Ngay
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                </div>
            </div>
        </section>

        <section id="features" class="py-16 md:py-24">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-center mb-4 animate-on-scroll">Tại Sao Chọn Chúng Tôi?</h2>
                <p class="text-xl text-center text-gray-400 mb-12 animate-on-scroll">Cam kết mang lại trải nghiệm mua sắm an tâm và hài lòng nhất.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="text-center p-6 bg-secondary-dark rounded-xl shadow-lg border border-gray-700 animate-on-scroll" style="transition-delay: 100ms;">
                        <div class="p-4 inline-block bg-accent/20 rounded-full mb-4">
                            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.008 12.008 0 0012 21.018a12.008 12.008 0 008.618-18.082z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Giao Hàng Siêu Tốc</h3>
                        <p class="text-gray-400">Nhận hàng chỉ trong 24h nội thành. Miễn phí vận chuyển toàn quốc.</p>
                    </div>

                    <div class="text-center p-6 bg-secondary-dark rounded-xl shadow-lg border border-gray-700 animate-on-scroll" style="transition-delay: 200ms;">
                        <div class="p-4 inline-block bg-accent/20 rounded-full mb-4">
                            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3 .895 3 2s-1.343 2-3 2h-1v6m0-6H9m1 10a2 2 0 11-4 0 2 2 0 014 0zM12 4v4m-3 0h6m1 4h2m-2 4h2m-2 4h2"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Bảo Hành Chính Hãng</h3>
                        <p class="text-gray-400">Bảo hành 1 đổi 1 lên đến 24 tháng. Yên tâm sử dụng dài lâu.</p>
                    </div>

                    <div class="text-center p-6 bg-secondary-dark rounded-xl shadow-lg border border-gray-700 animate-on-scroll" style="transition-delay: 300ms;">
                        <div class="p-4 inline-block bg-accent/20 rounded-full mb-4">
                            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m4 2h10a2 2 0 002-2v-6a2 2 0 00-2-2H9.5L9 9l4-4M7 21a2 2 0 100-4 2 2 0 000 4zm12 0a2 2 0 100-4 2 2 0 000 4z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2">Giá Tốt Nhất Thị Trường</h3>
                        <p class="text-gray-400">Cam kết giá cạnh tranh. Luôn có ưu đãi độc quyền dành cho bạn.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 bg-accent rounded-2xl shadow-lg animate-on-scroll">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-4">Đừng Bỏ Lỡ Cơ Hội!</h2>
                <p class="text-lg text-indigo-100 mb-8">Đăng ký nhận bản tin để được giảm giá 10% cho đơn hàng đầu tiên.</p>
                
                <form class="flex flex-col sm:flex-row justify-center max-w-lg mx-auto space-y-4 sm:space-y-0 sm:space-x-4">
                    <input type="email" placeholder="Nhập email của bạn..." class="w-full sm:w-2/3 p-3 rounded-xl border-2 border-white focus:outline-none focus:ring-2 focus:ring-indigo-300 text-gray-900" required>
                    <button type="submit" class="w-full sm:w-1/3 bg-white text-accent font-bold py-3 px-6 rounded-xl hover:bg-indigo-100 transition duration-300 transform hover:scale-105">
                        Đăng Ký
                    </button>
                </form>
            </div>
        </section>

    </main>

    <footer class="bg-primary-dark border-t border-gray-700 py-12 mt-20">
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 grid grid-cols-2 md:grid-cols-4 gap-8 text-gray-400">
            <div>
                <a href="#" class="text-xl font-extrabold tracking-tight text-white mb-4 block">
                    DIEN THOAI<span class="text-accent">.STORE</span>
                </a>
                <p class="text-sm">Chuyên cung cấp điện thoại thông minh chính hãng, giá tốt nhất.</p>
            </div>
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Sản Phẩm</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="#featured" class="hover:text-accent">Sản Phẩm Mới</a></li>
                    <li><a href="#" class="hover:text-accent">Khuyến mãi</a></li>
                    <li><a href="#" class="hover:text-accent">Phụ Kiện</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Hỗ Trợ</h4>
                <ul class="space-y-2 text-sm">
                    <li><a href="lien_he.php" class="hover:text-accent">Liên hệ</a></li>
                    <li><a href="phan_anh.php" class="hover:text-accent">Phản ánh</a></li>
                    <li><a href="don_hang_cua_toi.php" class="hover:text-accent">Tra cứu đơn hàng</a></li>
                </ul>
            </div>
            <div>
                <h4 class="text-lg font-semibold text-white mb-4">Liên Hệ</h4>
                <p class="text-sm">
                    Email: support@dienthoai.store<br>
                    Điện thoại: (090) 123 4567
                </p>
                <div class="flex space-x-4 mt-4">
                    <a href="#" class="text-gray-400 hover:text-accent transition duration-200">FB</a>
                    <a href="#" class="text-gray-400 hover:text-accent transition duration-200">IG</a>
                    <a href="#" class="text-gray-400 hover:text-accent transition duration-200">Zalo</a>
                </div>
            </div>
        </div>
        <div class="container mx-auto px-4 sm:px-6 lg:px-8 mt-12 pt-6 border-t border-gray-800 text-center text-sm text-gray-500">
            &copy; <?php echo date("Y"); ?> DIEN THOAI STORE. Bảo lưu mọi quyền.
        </div>
    </footer>

    <div id="toast-notification" class="">
        <div id="toast-icon">
            </div>
        <div class="text-sm font-normal" id="toast-message">
            </div>
        <button type="button" id="toast-close" class="-mx-1.5 -my-1.5 ml-auto">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
            </svg>
        </button>
    </div>

    <script>
        let toastTimer;

        // 1. Hàm hiển thị Toast (Lấy từ template)
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast-notification');
            const toastMessage = document.getElementById('toast-message');
            const toastIcon = document.getElementById('toast-icon');

            if (!toast || !toastMessage || !toastIcon) return;
            clearTimeout(toastTimer);

            toastMessage.textContent = message;
            toast.classList.remove('success', 'error'); 

            if (type === 'success') {
                toastIcon.innerHTML = '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';
                toast.classList.add('success');
            } else {
                toastIcon.innerHTML = '<svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>';
                toast.classList.add('error');
            }
            
            toast.classList.add('show');
            
            toastTimer = setTimeout(function () {
                toast.classList.remove('show');
            }, 3000);
        }

        // 2. Hàm cập nhật icon giỏ hàng
        function updateCartBadge(count) {
            const badge = document.getElementById('cart-badge');
            if (badge) {
                if (count > 0) {
                    badge.textContent = count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
        
        // 3. Logic chính
        document.addEventListener("DOMContentLoaded", function () {
            
            // --- Xử lý AJAX "Thêm vào giỏ" ---
            const allForms = document.querySelectorAll('.add-to-cart-form');
            allForms.forEach(form => {
                form.addEventListener('submit', function(event) {
                    event.preventDefault(); 
                    const formData = new FormData(this);
                    
                    // Thêm hiệu ứng loading cho nút (tùy chọn)
                    const button = form.querySelector('button');
                    const originalText = button.innerHTML;
                    button.innerHTML = 'Đang thêm...';
                    button.disabled = true;
                    
                    fetch('xu_ly_gio_hang.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            updateCartBadge(data.new_cart_count);
                            showToast('Đã thêm vào giỏ hàng!', 'success');
                        } else {
                            showToast(data.message || 'Lỗi: Không thể thêm vào giỏ.', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Lỗi Fetch:', error);
                        showToast('Lỗi kết nối. Vui lòng thử lại.', 'error');
                    })
                    .finally(() => {
                        // Trả lại trạng thái nút
                        button.innerHTML = originalText;
                        button.disabled = false;
                    });
                });
            });
            
            // --- Xử lý Nút đóng Toast ---
            const toastCloseBtn = document.getElementById('toast-close');
            const toast = document.getElementById('toast-notification');
            if (toastCloseBtn && toast) {
                toastCloseBtn.addEventListener('click', function () {
                    toast.classList.remove('show');
                    clearTimeout(toastTimer);
                });
            }

            // --- Xử lý Mobile Menu Toggle ---
            const mobileMenuBtn = document.getElementById('mobile-menu-btn');
            const mobileMenu = document.getElementById('mobile-menu');
            if(mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                });
            }

            // --- Xử lý Hiệu ứng cuộn (IntersectionObserver) ---
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-visible');
                        observer.unobserve(entry.target); // Chỉ animate một lần
                    }
                });
            }, { 
                threshold: 0.1 // Kích hoạt khi 10% phần tử hiển thị
            });
            // Gắn observer
            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                observer.observe(el);
            });
        });
    </script>
</body>
</html>