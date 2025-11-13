<?php
// 1. BẮT ĐẦU SESSION VÀ KẾT NỐI CSDL
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($conn) || !$conn) {
    require 'ket_noi_csdl.php';
}

// 2. LẤY SỐ LƯỢNG GIỎ HÀNG (SỬA LẠI CÁCH ĐẾM)
$cart_count = 0;
if (isset($_SESSION['id_nguoi_dung'])) {
    $id_nguoi_dung = $_SESSION['id_nguoi_dung'];
    // Đếm TỔNG SỐ LƯỢNG sản phẩm, không phải số dòng
    $sql_count = "SELECT SUM(so_luong) as total FROM gio_hang WHERE id_nguoi_dung = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $id_nguoi_dung);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $cart_count = $result_count->fetch_assoc()['total'] ?? 0;
} elseif (isset($_SESSION['cart'])) {
    // Đếm TỔNG SỐ LƯỢNG trong session
    foreach ($_SESSION['cart'] as $item) {
        $cart_count += ($item['so_luong'] ?? 1); // Thêm ?? 1 để an toàn
    }
}

// 3. LẤY TÊN FILE HIỆN TẠI (ĐỂ ACTIVE MENU)
$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $page_title ?? 'Web Bán Điện Thoại'; ?></title> 

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/turbolinks/5.2.0/turbolinks.js" defer></script>
    
    <?php if (isset($page_meta_tags)) { echo $page_meta_tags; } ?>
    
    <style>
        /* === BIẾN CHUNG === */
        :root {
            --primary-color: #007bff;
            --danger-color: #e74c3c;
            --dark-color: #333;
            --light-color: #f4f4f4;
            --white-color: #ffffff;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --border-radius: 12px;
        }
        
        /* === THIẾT LẬP CƠ BẢN === */
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0; padding: 0;
            background-color: var(--light-color);
            line-height: 1.6;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        
        /* === HEADER / NAVBAR === */
        .navbar {
            background-color: var(--white-color); padding: 0 40px; 
            display: flex; justify-content: space-between; align-items: center;
            height: 70px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky; top: 0; z-index: 1000;
        }
        .navbar .logo {
            font-size: 1.8em; font-weight: 800;
            color: var(--dark-color); text-decoration: none;
            display: flex; align-items: center;
        }
        .navbar .logo i { margin-right: 10px; color: var(--primary-color); }
        .nav-links-main { flex-grow: 1; margin-left: 40px; }
        .nav-links-user { display: flex; align-items: center; }
        .nav-links-main a,
        .nav-links-user a {
            color: #555; text-decoration: none; padding: 26px 15px; 
            position: relative; display: inline-block; 
            font-weight: 500; font-size: 0.95rem;
            transition: all 0.2s ease;
        }
        .nav-links-main a i,
        .nav-links-user a i {
            margin-right: 8px; width: 1.2em; text-align: center;
        }
        .nav-links-main a:hover {
            color: var(--primary-color); background-color: #f7f7f7;
        }
        .nav-links-user a:hover { color: var(--primary-color); }
        .nav-links-main a.active {
             color: var(--primary-color);
             box-shadow: inset 0 -3px 0 0 var(--primary-color);
        }
        .nav-links-user a.admin-link { 
            color: var(--danger-color); font-weight: bold; 
        }
        .nav-cart { font-size: 1.2rem; }
        .cart-badge {
            position: absolute; top: 15px; right: 5px; 
            background-color: var(--danger-color); color: white;
            font-size: 11px; font-weight: bold; border-radius: 50%;
            width: 18px; height: 18px;
            display: flex; justify-content: center; align-items: center;
        }
        
        /* === BỐ CỤC CHUNG === */
        .container {
            max-width: 1300px;
            margin: 30px auto;
            padding: 0 20px;
            flex-grow: 1;
        }
        .container-small { max-width: 900px; }
        .container-mini { max-width: 700px; }
        
        /* === (SỬA LỖI) FORM CHUNG (ĐỦ TẤT CẢ INPUTS) === */
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: 600; }
        
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="number"],
        .form-group input[type="file"],
        .form-group input[type="date"],
        .form-group select,
        .form-group textarea,
        /* CSS CHO BỘ LỌC (TIN TỨC, ĐƠN HÀNG) */
        .filter-group input[type="text"],
        .filter-group input[type="date"],
        .filter-group input[type="number"],
        .filter-group select,
        /* CSS CHO MÃ GIẢM GIÁ (THANH TOÁN) */
        .discount-code-form input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            box-sizing: border-box; 
        }
        
        /* === NÚT BẤM CHUNG === */
        .btn {
            background-color: var(--primary-color); color: white; padding: 10px 15px;
            text-decoration: none; border: none; border-radius: 4px;
            font-weight: bold; cursor: pointer; font-size: 14px;
            display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-secondary { background-color: #6c757d; }
        .btn-success { background-color: #28a745; }
        .btn-submit {
            background-color: var(--primary-color);
            color: white; border: none;
            padding: 12px 20px;
            font-size: 1rem; font-weight: bold;
            border-radius: 5px; cursor: pointer;
            width: 100%; margin-top: 10px;
        }
        .btn-submit:hover { background-color: #0056b3; }
        
        /* === THÔNG BÁO CHUNG === */
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }

        /* (MỚI) CSS CHO THÔNG BÁO AJAX (TOAST) */
        #toast-container {
            position: fixed;
            top: 90px; /* Dưới navbar */
            right: 20px;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .toast-notification {
            background-color: #2c3e50; /* Màu tối */
            color: white;
            padding: 15px 20px;
            border-radius: 5px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
            font-size: 1rem;
            display: flex;
            align-items: center;
            gap: 10px;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.5s ease;
        }
        .toast-notification.success { /* Thêm class success */
             background-color: #28a745;
        }
        .toast-notification.error {
            background-color: var(--danger-color);
        }
        .toast-notification.show {
            opacity: 1;
            transform: translateX(0);
        }

        /* === CÁC THÀNH PHẦN CHUNG KHÁC === */
        .section-title {
            font-size: 2rem;
            color: var(--dark-color);
            border-bottom: 3px solid var(--primary-color);
            padding-bottom: 10px;
            margin-bottom: 30px;
            display: inline-block;
        }
        
        /* (MỚI) CSS BỘ LỌC (DÙNG CHO TIN TỨC) */
        .filter-container {
            width: 100%;
            background: var(--white-color);
            padding: 20px;
            border-radius: 8px;
            box-shadow: var(--shadow);
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .filter-group { display: flex; flex-direction: column; }
        .filter-group label { font-size: 0.85em; color: #555; margin-bottom: 5px; font-weight: bold; }
        .filter-actions { grid-column: 1 / -1; display: flex; gap: 10px; }
        
        /* (MỚI) CSS BẢNG (DÙNG CHO bai_viet_cua_toi.php, don_hang_cua_toi.php) */
        .styled-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: var(--shadow);
            border-radius: 8px;
            overflow: hidden;
        }
        .styled-table th, .styled-table td { padding: 12px 15px; border-bottom: 1px solid #ddd; text-align: left; }
        .styled-table th { background-color: #f1f1f1; }
        .styled-table tr:hover { background-color: #f9f9f9; }
        .status-label {
            padding: 5px 10px; border-radius: 4px; color: white;
            font-size: 12px; font-weight: bold; text-transform: uppercase;
        }
        /* Nhãn trạng thái (User) */
        .status-hien_thi { background-color: #28a745; }
        .status-an { background-color: #6c757d; }
        .status-cho_duyet { background-color: #ffc107; color: #333; }
        /* Nhãn trạng thái đơn hàng (User) */
        .status-moi { background-color: #007bff; }
        .status-dang_xu_ly { background-color: #17a2b8; }
        .status-dang_giao { background-color: #ffc107; color: #333; }
        .status-hoan_thanh { background-color: #28a745; }
        .status-da_huy { background-color: #dc3545; }
        .status-yeu_cau_huy { background-color: #fd7e14; }
        .status-yeu_cau_tra_hang { background-color: #fd7e14; }
        .status-da_hoan_tra { background-color: #6c757d; }
        .status-cho_xac_nhan_thanh_toan { background-color: #fd7e14; }
        
        /* === FOOTER === */
        footer {
            background-color: #222;
            color: #aaa;
            padding: 40px 20px;
            margin-top: 40px; 
        }
        .footer-container {
            max-width: 1300px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 30px;
        }
        .footer-col h3 {
            color: var(--white-color);
            font-size: 1.2rem;
            margin-bottom: 15px;
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 5px;
            display: inline-block;
        }
        .footer-col p { font-size: 0.9rem; line-height: 1.7; }
        .footer-col ul { list-style-type: none; padding: 0; }
        .footer-col ul li { margin-bottom: 10px; }
        .footer-col ul li a { text-decoration: none; color: #aaa; transition: color 0.2s; }
        .footer-col ul li a:hover { color: var(--white-color); }
        .footer-col ul li i,
        .footer-col .contact-info p i {
            margin-right: 10px;
            width: 1.2em;
            color: var(--primary-color);
        }
        .footer-bottom-bar {
            text-align: center;
            padding-top: 20px;
            margin-top: 20px;
            border-top: 1px solid #444;
            font-size: 0.9rem;
        }
    </style>
</head>
<body>

<div id="toast-container"></div>

<nav class="navbar">
    <a href="index.php" class="logo"><i class="fas fa-mobile-alt"></i>PhoneStore</a>
    
    <div class="nav-links-main">
        <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
            <i class="fas fa-home"></i>Trang Chủ
        </a>
        <a href="tin_tuc.php" class="<?php echo ($current_page == 'tin_tuc.php' || $current_page == 'chi_tiet_tin_tuc.php') ? 'active' : ''; ?>">
            <i class="fas fa-newspaper"></i>Tin Tức
        </a>
        <a href="lien_he.php" class="<?php echo ($current_page == 'lien_he.php') ? 'active' : ''; ?>">
            <i class="fas fa-envelope"></i>Liên Hệ
        </a>
        
        <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
            <a href="phan_anh.php" class="<?php echo ($current_page == 'phan_anh.php') ? 'active' : ''; ?>">
                <i class="fas fa-flag"></i>Gửi Phản Ánh
            </a>
        <?php endif; ?>
    </div>

    <div class="nav-links-user">
        
        <a href="gio_hang.php" class="nav-cart <?php echo ($current_page == 'gio_hang.php') ? 'active' : ''; ?>" title="Giỏ Hàng" id="cart-link">
            <i class="fas fa-shopping-cart"></i>
            
            <span class="cart-badge" id="cart-badge" 
                  style="<?php echo ($cart_count > 0) ? 'display: flex;' : 'display: none;'; ?>">
                <?php echo $cart_count; ?>
            </span>
        </a>
        
        <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
            <a href="phan_hoi_cua_toi.php" title="Phản hồi của tôi" class="<?php echo ($current_page == 'phan_hoi_cua_toi.php') ? 'active' : ''; ?>">
                <i class="fas fa-inbox"></i>
            </a>
            <a href="bai_viet_cua_toi.php" title="Bài viết của tôi" class="<?php echo ($current_page == 'bai_viet_cua_toi.php' || $current_page == 'viet_bai.php') ? 'active' : ''; ?>">
                <i class="fas fa-pen-square"></i>
            </a> 
            <a href="don_hang_cua_toi.php" title="Đơn hàng của tôi" class="<?php echo ($current_page == 'don_hang_cua_toi.php') ? 'active' : ''; ?>">
                <i class="fas fa-receipt"></i>
            </a> 
            <a href="thong_tin_tai_khoan.php" title="Tài khoản" class="<?php echo ($current_page == 'thong_tin_tai_khoan.php' || $current_page == 'doi_mat_khau.php') ? 'active' : ''; ?>">
                <i class="fas fa-user-circle"></i>
            </a>
            
            <?php if ($_SESSION['vai_tro'] == 'quan_tri'): ?>
                <a href="quan_tri/index.php" class="admin-link" title="Trang Quản Trị" data-turbolinks="false">
                    <i class="fas fa-cogs"></i>
                </a>
            <?php endif; ?>
            
            <a href="dang_xuat.php" title="Đăng Xuất" data-turbolinks="false"><i class="fas fa-sign-out-alt"></i></a>
            
        <?php else: ?>
            <a href="dang_nhap.php" class="<?php echo ($current_page == 'dang_nhap.php') ? 'active' : ''; ?>" data-turbolinks="false">
                <i class="fas fa-sign-in-alt"></i>Đăng Nhập
            </a>
            <a href="dang_ky.php" class="<?php echo ($current_page == 'dang_ky.php') ? 'active' : ''; ?>" data-turbolinks="false">
                <i class="fas fa-user-plus"></i>Đăng Ký
            </a>
        <?php endif; ?>
    </div>
</nav>