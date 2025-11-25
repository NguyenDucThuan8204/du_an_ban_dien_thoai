<?php
// 1. GỌI LOGIC TRƯỚC TIÊN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Giả định file này tồn tại
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
$page_title = "DIEN THOAI STORE | Sức Mạnh Trong Tay Bạn"; // Đặt tiêu đề

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

// Chuẩn bị biến cho HERO BANNER
$hero_title = "Sức Mạnh <span class='text-accent block lg:inline'>Trong Tay Bạn.</span>";
$hero_subtitle = "Khám phá những chiếc điện thoại thông minh mới nhất với thiết kế đột phá và hiệu năng vượt trội.";
$hero_cta_link = "#featured";
$hero_image_html = '
<div class="w-64 h-96 md:w-80 md:h-112 bg-white rounded-[3rem] shadow-[0_20px_50px_rgba(0,0,0,0.1)] border-8 border-gray-100 overflow-hidden transform rotate-3 hover:rotate-0 transition duration-500 ease-in-out animate-float">
    <div class="bg-accent h-2 w-16 mx-auto mt-4 rounded-full"></div>
    <div class="w-full h-full p-2">
        <div class="bg-gray-100/50 text-center flex flex-col items-center justify-center h-full text-gray-500 text-sm">
            <svg class="w-12 h-12 text-accent mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
            <p class="font-bold">Màn Hình Vô Cực</p>
            <p>Tận hưởng trải nghiệm tuyệt đỉnh.</p>
        </div>
    </div>
</div>'; 

if ($active_event) {
    $hero_title = $active_event['noi_dung_ghi_chu'] ?? 'Sự Kiện Đặc Biệt'; 
    $hero_subtitle = $active_event['mo_ta_ngan'] ?? 'Ưu đãi có hạn. Khám phá ngay!'; 
    $hero_cta_link = $active_event['link_dich'] ?? '#featured';
    
    $anh_path_event = 'tai_len/quang_cao/' . $active_event['hinh_anh'];
    if (!empty($active_event['hinh_anh']) && file_exists($anh_path_event)) {
        $hero_image_html = '<img src="'.$anh_path_event.'" alt="'.htmlspecialchars($hero_title).'" class="w-auto h-96 md:h-112 object-contain animate-float transform rotate-3">';
    }
}

// LẤY CÁC THAM SỐ LỌC TỪ URL
$current_id_hang = (int)($_GET['id_hang'] ?? 0);
$current_min_price = (int)($_GET['min_price'] ?? 0);
$current_max_price = (int)($_GET['max_price'] ?? 0);
$current_sort_by = $_GET['sort_by'] ?? 'moi_nhat';

// Tạo query string cho AJAX 
$filter_query_string = http_build_query([
    'id_hang' => $current_id_hang,
    'min_price' => $current_min_price,
    'max_price' => $current_max_price,
    'sort_by' => $current_sort_by
]);

// --- 3.2. LẤY SẢN PHẨM NỔI BẬT (TẢI BAN ĐẦU) ---
$featured_products = [];
$limit_initial = 3; 
$sql_featured = "SELECT s.*, h.ten_hang FROM san_pham s
                 JOIN hang_san_xuat h ON s.id_hang = h.id_hang";
$where_clauses = ["s.trang_thai = 'hiện'", "h.trang_thai = 'hien_thi'"];
$params = [];
$param_types = "";
if ($current_id_hang > 0) {
    $where_clauses[] = "s.id_hang = ?";
    $params[] = $current_id_hang;
    $param_types .= "i";
}
$sql_featured .= " WHERE " . implode(" AND ", $where_clauses);
$sql_featured .= " ORDER BY s.ngay_cap_nhat DESC LIMIT ?";
$params[] = $limit_initial;
$param_types .= "i";

$stmt_featured = $conn->prepare($sql_featured);
if (!empty($params)) {
    $stmt_featured->bind_param($param_types, ...$params);
}
$stmt_featured->execute();
$result_featured = $stmt_featured->get_result();
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
require 'dung_chung/dau_trang.php';

?>

<!DOCTYPE html>
<html lang="vi" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@100..900&display=swap');
        body {
            font-family: 'Inter', sans-serif;
        }

        /* 1. Custom Keyframe for Floating Effect */
        @keyframes float {
            0% { transform: translatey(0px) rotate(3deg); }
            50% { transform: translatey(-10px) rotate(1deg); }
            100% { transform: translatey(0px) rotate(3deg); }
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
            from { opacity: 0; transform: translate3d(0, 30px, 0); }
            to { opacity: 1; transform: translate3d(0, 0, 0); }
        }
        
        /* 4. IntersectionObserver helper classes */
        .animate-on-scroll {
            opacity: 0;
            will-change: opacity, transform;
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
            transition-delay: 0.1s;
            transform: translateY(20px);
        }
        .animate-on-scroll.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* 5. Navbar scroll effect (Giao diện sáng) */
        #main-header {
            position: sticky;
            top: 0;
            z-index: 50;
            transition: background-color 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            background-color: rgba(255, 255, 255, 0.95); /* bg-white/95 */
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(4px);
        }
        

        /* 7. CSS cho Toast Notification (Giao diện sáng) */
        #toast-container { 
            position: fixed;
            top: 5rem; 
            right: 1.5rem; 
            z-index: 9999;
        }
        #toast-notification {
            max-width: 320px;
            background-color: #ffffff; /* bg-white */
            color: #111827; /* text-gray-900 */
            border-radius: 0.5rem; 
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            padding: 1rem;
            display: flex;
            align-items: center;
            opacity: 0;
            transform: translateX(100%);
            transition: opacity 0.3s ease, transform 0.3s ease;
            border: 1px solid #E5E7EB; /* gray-200 */
        }
        #toast-notification.success {
             background-color: #D1FAE5; /* bg-emerald-100 */
             color: #065F46; /* text-emerald-800 */
        }
         #toast-notification.error {
             background-color: #FEE2E2; /* bg-red-100 */
             color: #991B1B; /* text-red-800 */
        }
        #toast-icon {
            width: 1.25rem; 
            height: 1.25rem; 
            margin-right: 0.75rem; 
        }
        #toast-close {
            margin-left: auto;
            background: none;
            border: none;
            color: #9CA3AF; /* text-gray-400 */
            cursor: pointer;
            padding: 0.25rem;
        }
        
        /* 8. CSS Cho Nút "Xem thêm" & "Ẩn bớt" */
        .load-more-controls {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 3rem; 
        }
        #load-more-btn, #hide-products-btn {
            background-color: #4F46E5; /* accent */
            color: white;
            font-weight: 700;
            padding: 0.75rem 2rem; 
            border-radius: 0.75rem; 
            border: none;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            transition: all 0.3s;
            transform-origin: center;
            cursor: pointer;
        }
        #load-more-btn:hover, #hide-products-btn:hover {
            background-color: #4338CA; /* accent-hover */
            transform: scale(1.05);
        }
        #hide-products-btn {
            background-color: #9CA3AF; /* gray-400 */
            color: white;
        }
        #hide-products-btn:hover {
            background-color: #6B7280; /* gray-500 */
        }
        #load-more-btn:disabled {
            background-color: #9CA3AF;
            opacity: 0.5;
            cursor: wait;
        }
        
        /* 9. Class để đánh dấu sản phẩm được tải thêm */
        .loaded-more-item.animate-on-scroll {
            opacity: 0;
            animation: fadeInUp 0.5s ease forwards;
        }
        /* 1. Nút tròn để bật/tắt chat */
    #toggle-chat-btn {
        position: fixed;
        bottom: 30px;
        right: 30px;
        width: 60px;
        height: 60px;
        background-color: #007bff; /* Màu xanh giống chatbot */
        color: white;
        border: none;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        z-index: 9999;
        font-size: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: transform 0.3s;
    }
    #toggle-chat-btn:hover {
        transform: scale(1.1);
    }

    /* 2. Khung chứa Iframe (Mặc định ẩn) */
    #chatbot-frame-container {
        display: none; /* Ẩn đi ban đầu */
        position: fixed;
        bottom: 100px; /* Cách nút bấm 1 chút */
        right: 30px;
        width: 400px; /* Chiều rộng khung chat */
        height: 600px; /* Chiều cao khung chat */
        background: white;
        border-radius: 20px;
        box-shadow: 0 5px 20px rgba(0,0,0,0.2);
        z-index: 9999;
        overflow: hidden;
        border: 1px solid #ddd;
    }

    /* 3. Thẻ Iframe load file index.html */
    #chatbot-iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    /* Responsive cho điện thoại */
    @media (max-width: 480px) {
        #chatbot-frame-container {
            width: 90%;
            right: 5%;
            bottom: 100px;
            height: 70vh;
        }
    }
    </style>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        // Cấu hình màu cho giao diện sáng
                        'primary-dark': '#ffffff', // White
                        'secondary-dark': '#F3F4F6', // Gray 100
                        'accent': '#4F46E5', // Indigo 600
                        'accent-hover': '#4338CA', // Indigo 700
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-primary-dark text-gray-900 min-h-screen">


    <main>
        <section id="hero" class="relative overflow-hidden pt-16 md:pt-24 pb-16">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 flex flex-col lg:flex-row items-center justify-between">
                <div class="lg:w-1/2 text-center lg:text-left mb-10 lg:mb-0 animate-on-scroll">
                    <span class="text-accent text-lg font-semibold uppercase tracking-wider mb-3 block">
                        Công Nghệ Đỉnh Cao
                    </span>
                    <h1 class="text-5xl md:text-7xl font-extrabold leading-tight mb-6 text-gray-900">
                        <?php echo $hero_title; // Tiêu đề động ?>
                    </h1>
                    <p class="text-gray-600 text-lg mb-8 max-w-lg mx-auto lg:mx-0">
                        <?php echo htmlspecialchars($hero_subtitle); // Mô tả động ?>
                    </p>
                    <div class="flex flex-col sm:flex-row justify-center lg:justify-start space-y-4 sm:space-y-0 sm:space-x-4">
                        <a href="<?php echo htmlspecialchars($hero_cta_link); ?>" class="bg-accent text-white font-bold py-3 px-8 rounded-xl shadow-lg hover:bg-accent-hover transition duration-300 transform hover:scale-105">
                            Xem Ngay
                        </a>
                    </div>
                </div>

                <div class="lg:w-1/2 relative flex justify-center lg:justify-end animate-on-scroll" style="transition-delay: 200ms;">
                    <?php echo $hero_image_html; // In ra HTML của ảnh (hoặc mockup) ?>
                </div>
            </div>
        </section>

        <section id="featured" class="py-16 md:py-24 bg-gray-100"> 
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-center mb-4 text-gray-900 animate-on-scroll">Sản Phẩm Nổi Bật</h2> 
                <p class="text-xl text-center text-gray-600 mb-12 animate-on-scroll">Những chiếc điện thoại được săn đón nhất hiện nay.</p>

                <div id="product-grid-container" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    
                    <?php if (empty($featured_products)): ?>
                        <p class="text-center col-span-3 text-gray-600">Chưa có sản phẩm nổi bật nào.</p>
                    <?php else: ?>
                        <?php foreach ($featured_products as $index => $sp): ?>
                            <?php $gia_data = tinhGiaHienThi($sp, $hom_nay); ?>
                            
                            <div class="bg-white p-6 rounded-2xl shadow-xl hover:shadow-accent/30 transition duration-300 transform hover:-translate-y-2 border border-gray-200 animate-on-scroll" 
                                 style="transition-delay: <?php echo $index * 100; ?>ms;">
                                
                                <div class="w-full h-48 mb-4 flex items-center justify-center bg-gray-200 rounded-xl overflow-hidden group">
                                    <?php 
                                    $anh_path = 'tai_len/san_pham/' . ($sp['anh_dai_dien'] ?? 'default.png');
                                    if (empty($sp['anh_dai_dien']) || !file_exists($anh_path)) {
                                        $anh_path = 'https://placehold.co/300x300/F3F4F6/4F46E5?text=No+Image';
                                    }
                                    ?>
                                    <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="block w-full h-full">
                                        <img src="<?php echo $anh_path; ?>" alt="<?php echo htmlspecialchars($sp['ten_san_pham']); ?>" 
                                             class="object-contain w-full h-full p-2 transition duration-300 group-hover:scale-105">
                                    </a>
                                </div>
                                <h3 class="text-2xl font-bold mb-2 truncate text-gray-900"><?php echo htmlspecialchars($sp['ten_san_pham']); ?></h3>
                                <p class="text-gray-600 mb-4 h-14 overflow-hidden">
                                    <?php echo htmlspecialchars($sp['ten_hang']); ?> - 
                                    <?php echo htmlspecialchars($sp['mo_ta_ngan'] ?? $sp['ten_san_pham']); ?>
                                </p>
                                <div class="flex justify-between items-center">
                                    <span class="text-2xl font-extrabold text-accent">
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

                </div> <div id="load-more-container" class="load-more-controls animate-on-scroll">
                    <button id="load-more-btn" data-page="2" data-initial-load="<?php echo $limit_initial; ?>" data-load-count="6">
                        Xem Thêm 6 Sản Phẩm
                    </button>
                    <button id="hide-products-btn" style="display:none;" class="hover:bg-gray-500">
                        Ẩn Bớt
                    </button>
                </div>
                
            </div>
        </section>

        <section id="features" class="py-16 md:py-24">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8">
                <h2 class="text-4xl font-bold text-center mb-4 text-gray-900 animate-on-scroll">Tại Sao Chọn Chúng Tôi?</h2>
                <p class="text-xl text-center text-gray-600 mb-12 animate-on-scroll">Cam kết mang lại trải nghiệm mua sắm an tâm và hài lòng nhất.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">
                    <div class="text-center p-6 bg-gray-100 rounded-xl shadow-lg border border-gray-200 animate-on-scroll" style="transition-delay: 100ms;">
                        <div class="p-4 inline-block bg-accent/20 rounded-full mb-4">
                            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.008 12.008 0 0012 21.018a12.008 12.008 0 008.618-18.082z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-gray-900">Giao Hàng Siêu Tốc</h3>
                        <p class="text-gray-600">Nhận hàng chỉ trong 24h nội thành. Miễn phí vận chuyển toàn quốc.</p>
                    </div>
                    <div class="text-center p-6 bg-gray-100 rounded-xl shadow-lg border border-gray-200 animate-on-scroll" style="transition-delay: 200ms;">
                        <div class="p-4 inline-block bg-accent/20 rounded-full mb-4">
                            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c1.657 0 3 .895 3 2s-1.343 2-3 2h-1v6m0-6H9m1 10a2 2 0 11-4 0 2 2 0 014 0zM12 4v4m-3 0h6m1 4h2m-2 4h2m-2 4h2"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-gray-900">Bảo Hành Chính Hãng</h3>
                        <p class="text-gray-600">Bảo hành 1 đổi 1 lên đến 24 tháng. Yên tâm sử dụng dài lâu.</p>
                    </div>
                    <div class="text-center p-6 bg-gray-100 rounded-xl shadow-lg border border-gray-200 animate-on-scroll" style="transition-delay: 300ms;">
                        <div class="p-4 inline-block bg-accent/20 rounded-full mb-4">
                            <svg class="w-8 h-8 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m4 2h10a2 2 0 002-2v-6a2 2 0 00-2-2H9.5L9 9l4-4M7 21a2 2 0 100-4 2 2 0 000 4zm12 0a2 2 0 100-4 2 2 0 000 4z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold mb-2 text-gray-900">Giá Tốt Nhất Thị Trường</h3>
                        <p class="text-gray-600">Cam kết giá cạnh tranh. Luôn có ưu đãi độc quyền dành cho bạn.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="py-16 bg-accent rounded-2xl shadow-lg animate-on-scroll">
            <div class="container mx-auto px-4 sm:px-6 lg:px-8 text-center">
                <h2 class="text-3xl md:text-4xl font-bold mb-4 text-white">Đừng Bỏ Lỡ Cơ Hội!</h2>
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



    <div id="toast-container" class="fixed top-20 right-6 z-[9999]">
        </div>

    <script>
        let toastTimer;

        // 1. Hàm hiển thị Toast
        function showToast(message, type = 'success') {
            const toastContainer = document.getElementById('toast-container');
            if (!toastContainer) return;
            
            // Xóa toast cũ nếu còn
            clearTimeout(toastTimer);
            toastContainer.innerHTML = '';
            
            const toast = document.createElement('div');
            toast.id = 'toast-notification'; 
            
            const toastIcon = document.createElement('div');
            toastIcon.id = 'toast-icon';
            
            const toastMessage = document.createElement('div');
            toastMessage.className = 'text-sm font-normal';
            toastMessage.textContent = message;
            
            const toastCloseBtn = document.createElement('button');
            toastCloseBtn.type = 'button';
            toastCloseBtn.id = 'toast-close';
            toastCloseBtn.className = '-mx-1.5 -my-1.5 ml-auto';
            toastCloseBtn.innerHTML = '<svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14"><path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/></svg>';
            
            toast.appendChild(toastIcon);
            toast.appendChild(toastMessage);
            toast.appendChild(toastCloseBtn);
            
            toast.classList.remove('success', 'error'); 
            // Cập nhật màu sắc cho icon và toast message tương ứng với nền sáng
            if (type === 'success') {
                toastIcon.innerHTML = '<svg class="w-5 h-5 text-emerald-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';
                toast.classList.add('success');
            } else {
                toastIcon.innerHTML = '<svg class="w-5 h-5 text-red-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>';
                toast.classList.add('error');
            }
            
            toastCloseBtn.addEventListener('click', () => {
                 toast.classList.remove('show');
                 clearTimeout(toastTimer);
                 setTimeout(() => toast.remove(), 300);
            });
            
            toastContainer.appendChild(toast);
            
            setTimeout(() => {
                toast.classList.add('show');
                toastTimer = setTimeout(function () {
                    toast.classList.remove('show');
                    setTimeout(() => toast.remove(), 300); 
                }, 3000);
            }, 50);
        }

        // 2. Hàm cập nhật icon giỏ hàng
        function updateCartBadge(count) {
            const badge = document.getElementById('cart-badge');
            if (badge) {
                const totalCount = parseInt(count) || 0;
                if (totalCount > 0) {
                    badge.textContent = totalCount;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        }
        
        // 3. Logic chính (Chạy khi DOM sẵn sàng)
        document.addEventListener("DOMContentLoaded", function () {
            
            // --- Xử lý AJAX "Thêm vào giỏ" ---
            const productGrid = document.getElementById('product-grid-container');
            if (productGrid) {
                productGrid.addEventListener('submit', function(event) {
                    if (event.target.classList.contains('add-to-cart-form')) {
                        event.preventDefault(); 
                        const form = event.target;
                        const formData = new FormData(form);
                        
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
                            button.innerHTML = originalText;
                            button.disabled = false;
                        });
                    }
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
                        observer.unobserve(entry.target); 
                    }
                });
            }, { 
                threshold: 0.1 
            });
            document.querySelectorAll('.animate-on-scroll').forEach(el => {
                observer.observe(el);
            });
            
            // --- Xử lý Nút "Xem thêm" & "Ẩn bớt" ---
            const loadMoreBtn = document.getElementById('load-more-btn');
            const hideBtn = document.getElementById('hide-products-btn');
            
            if (loadMoreBtn && hideBtn && productGrid) {
                loadMoreBtn.addEventListener('click', function() {
                    let currentPage = parseInt(this.dataset.page);
                    let loadCount = parseInt(this.dataset.loadCount);
                    // SỬA LỖI: truy cập data attribute bằng camelCase
                    let initialLoad = parseInt(this.dataset.initialLoad); 
                    let offset = initialLoad + (currentPage - 2) * loadCount;
                    
                    let filterParams = '<?php echo $filter_query_string; ?>'; 
                    
                    loadMoreBtn.disabled = true;
                    loadMoreBtn.innerHTML = 'Đang tải...';

                    fetch(`xu_ly_tai_them_dark.php?limit=${loadCount}&offset=${offset}&${filterParams}`)
                        .then(response => response.text())
                        .then(html => {
                            if (html.trim() === "") {
                                loadMoreBtn.innerHTML = 'Đã tải hết sản phẩm';
                                loadMoreBtn.style.display = 'none';
                            } else {
                                productGrid.insertAdjacentHTML('beforeend', html);
                                // Kích hoạt animation cho các item mới
                                productGrid.querySelectorAll('.loaded-more-item.animate-on-scroll').forEach(el => {
                                    observer.observe(el);
                                });
                                this.dataset.page = currentPage + 1;
                                loadMoreBtn.disabled = false;
                                loadMoreBtn.innerHTML = 'Xem Thêm 6 Sản Phẩm';
                                hideBtn.style.display = 'inline-block';
                            }
                        })
                        .catch(err => {
                            console.error('Lỗi tải thêm:', err);
                            loadMoreBtn.disabled = false;
                            loadMoreBtn.innerHTML = 'Lỗi! Thử lại';
                        });
                });
                
                hideBtn.addEventListener('click', function() {
                    const loadedItems = productGrid.querySelectorAll('.loaded-more-item');
                    loadedItems.forEach(item => item.remove());
                    
                    loadMoreBtn.style.display = 'inline-block';
                    loadMoreBtn.innerHTML = 'Xem Thêm 6 Sản Phẩm';
                    loadMoreBtn.dataset.page = "2"; 
                    loadMoreBtn.disabled = false;
                    
                    hideBtn.style.display = 'none';
                });
            }
            
        });
    </script>
    <button id="toggle-chat-btn" onclick="toggleChatbot()">
    <i class="fas fa-comment-dots"></i> </button>

<div id="chatbot-frame-container">
    <iframe id="chatbot-iframe" src="index.html"></iframe>
</div>

<script>
    function toggleChatbot() {
        var container = document.getElementById('chatbot-frame-container');
        var btn = document.getElementById('toggle-chat-btn');
        
        if (container.style.display === 'none' || container.style.display === '') {
            container.style.display = 'block';
            btn.innerHTML = '<i class="fas fa-times"></i>'; // Đổi icon thành dấu X
        } else {
            container.style.display = 'none';
            btn.innerHTML = '<i class="fas fa-comment-dots"></i>'; // Đổi lại icon chat
        }
    }
</script>
</body>
</html>
<?php
require 'dung_chung/cuoi_trang.php';
?>