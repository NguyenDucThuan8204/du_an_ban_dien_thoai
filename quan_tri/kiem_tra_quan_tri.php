<?php
// (Giả sử file gọi nó (quan_ly_phan_hoi.php) đã gọi session_start())

// 1. KIỂM TRA XEM NGƯỜI DÙNG ĐÃ ĐĂNG NHẬP CHƯA
if (!isset($_SESSION['id_nguoi_dung'])) {
    // Nếu chưa đăng nhập, đá về trang đăng nhập
    header("Location: ../dang_nhap.php");
    exit();
}

// 2. KIỂM TRA XEM HỌ CÓ PHẢI LÀ ADMIN KHÔNG
if ($_SESSION['vai_tro'] != 'quan_tri') {
    // Nếu là 'khach_hang', đá họ về trang chủ
    header("Location: ../index.php");
    exit();
}

// Nếu code chạy qua được đây, nghĩa là họ LÀ admin.
// File này không cần làm gì thêm.
?>