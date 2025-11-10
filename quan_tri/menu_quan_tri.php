<?php
// Lấy tên file hiện tại
$ten_file_hien_tai = basename($_SERVER['PHP_SELF']);

// Hàm gán class 'active'
function laTrangActive($ten_file_menu, $ten_file_hien_tai) {
    if ($ten_file_menu == $ten_file_hien_tai) {
        return ' class="active"';
    }
    return '';
}
?>

<nav class="sidebar">
    <div class="sidebar-header">
        <i class="fas fa-user-shield"></i> Admin Panel
    </div>
    <ul class="sidebar-menu">
        
        <li<?php echo laTrangActive('index.php', $ten_file_hien_tai); ?>>
            <a href="index.php"><i class="fas fa-tachometer-alt"></i>Tổng Quan</a>
        </li>
        <li<?php echo laTrangActive('quan_ly_san_pham.php', $ten_file_hien_tai); ?>>
            <a href="quan_ly_san_pham.php"><i class="fas fa-box-open"></i>Quản lý Sản phẩm</a>
        </li>
        <li<?php echo laTrangActive('quan_ly_hang_san_xuat.php', $ten_file_hien_tai); ?>>
            <a href="quan_ly_hang_san_xuat.php"><i class="fas fa-tags"></i>Quản lý Hãng</a>
        </li>
        <li<?php echo laTrangActive('quan_ly_nguoi_dung.php', $ten_file_hien_tai); ?>>
            <a href="quan_ly_nguoi_dung.php"><i class="fas fa-users"></i>Quản lý Người dùng</a>
        </li>
        <li<?php echo laTrangActive('quan_ly_don_hang.php', $ten_file_hien_tai); ?>>
            <a href="quan_ly_don_hang.php"><i class="fas fa-receipt"></i>Quản lý Đơn hàng</a>
        </li>
        <li<?php echo laTrangActive('quan_ly_ma_giam_gia.php', $ten_file_hien_tai); ?>>
            <a href="quan_ly_ma_giam_gia.php"><i class="fas fa-percent"></i>Quản lý Mã Giảm Giá</a>
        </li>
        <li<?php echo laTrangActive('quan_ly_tin_tuc.php', $ten_file_hien_tai); ?>>
            <a href="quan_ly_tin_tuc.php"><i class="fas fa-newspaper"></i>Quản lý Tin tức</a>
        </li>
        <li<?php echo laTrangActive('quan_ly_phan_hoi.php', $ten_file_hien_tai); ?>>
            <a href="quan_ly_phan_hoi.php"><i class="fas fa-inbox"></i>Quản lý Phản hồi</a>
        </li>
        
        <li class="back-to-site">
            <a href="../index.php" target="_blank"><i class="fas fa-globe"></i>Xem Trang chủ</a>
        </li>
        <li class="logout">
            <a href="../dang_xuat.php"><i class="fas fa-sign-out-alt"></i>Đăng Xuất</a>
        </li>
    </ul>
</nav>