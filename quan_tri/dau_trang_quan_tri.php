<?php
// 1. BẮT ĐẦU SESSION VÀ KẾT NỐI CSDL
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Nếu $conn chưa được tạo (ở file gọi), thì tạo nó
if (!isset($conn) || !$conn) {
    require '../dung_chung/ket_noi_csdl.php';
}

// 2. LẤY SỐ LƯỢNG GIỎ HÀNG (CHO MENU)
// (Chạy sau khi đã chắc chắn có session và $conn)
$cart_count = 0;
if (isset($_SESSION['id_nguoi_dung'])) {
    $id_nguoi_dung = $_SESSION['id_nguoi_dung'];
    $sql_count = "SELECT COUNT(id_gio_hang) as total FROM gio_hang WHERE id_nguoi_dung = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $id_nguoi_dung);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $cart_count = $result_count->fetch_assoc()['total'];
} elseif (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// 3. LẤY TÊN FILE HIỆN TẠI (ĐỂ ACTIVE MENU)
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo $page_title ?? 'Admin Panel'; ?></title>
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            background-color: #f4f7f6;
        }
        .admin-layout {
            display: flex;
            min-height: 100vh;
        }
        
        /* === CSS CỦA MENU === */
        .sidebar {
            width: 250px;
            background-color: #2c3e50;
            color: white;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
        }
        .sidebar-header {
            padding: 20px;
            font-size: 1.5em;
            font-weight: bold;
            text-align: center;
            background-color: #34495e;
            border-bottom: 1px solid #4a627a;
        }
        .sidebar-header i {
            margin-right: 10px;
        }
        .sidebar-menu {
            list-style-type: none;
            padding: 0;
            margin: 0;
            flex-grow: 1;
        }
        .sidebar-menu li a {
            display: flex; 
            align-items: center;
            padding: 15px 20px;
            color: #ecf0f1;
            text-decoration: none;
            border-bottom: 1px solid #34495e;
            transition: background-color 0.2s;
        }
        .sidebar-menu li a:hover {
            background-color: #34495e;
        }
        .sidebar-menu li.active a {
            background-color: #007bff;
            color: white;
            font-weight: bold;
        }
        .sidebar-menu li a i {
            margin-right: 12px;
            width: 20px;
            text-align: center;
        }
        .sidebar-menu li.back-to-site {
            margin-top: auto; /* Đẩy xuống dưới cùng */
        }
        .sidebar-menu li.back-to-site a {
            background-color: #1abc9c;
            color: #fff;
        }
        .sidebar-menu li.logout a {
            background-color: #e74c3c;
            color: #fff;
        }
        
        /* === CSS CHUNG CHO NỘI DUNG ADMIN === */
        .main-content {
            flex-grow: 1;
            padding: 30px;
            background-color: #f4f7f6;
        }
        h1 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
        }
        
        /* CSS Bảng chung */
        .admin-table {
            width: 100%;
            border-collapse: collapse;
            background-color: #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }
        .admin-table th, .admin-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ddd;
            text-align: left;
            vertical-align: middle;
        }
        .admin-table th {
            background-color: #f1f1f1;
        }
        .admin-table tr:hover {
            background-color: #f9f9f9;
        }
        
        /* CSS Tab menu chung */
        .tab-menu {
            display: flex;
            border-bottom: 2px solid #ccc;
            margin-bottom: 20px;
        }
        .tab-menu a {
            padding: 12px 20px;
            text-decoration: none;
            color: #555;
            font-weight: bold;
            font-size: 1.1em;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px; 
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .tab-menu a:hover {
            background-color: #eee;
        }
        .tab-menu a.active {
            color: #007bff;
            border-bottom-color: #007bff;
        }
        
        /* Nhãn Trạng Thái */
        .status-label {
            padding: 5px 10px; border-radius: 4px; color: white;
            font-size: 12px; font-weight: bold; text-transform: uppercase;
        }
        .status-moi { background-color: #007bff; }
        .status-da_doc { background-color: #17a2b8; }
        .status-da_tra_loi { background-color: #28a745; }
        .status-dang_xu_ly { background-color: #ffc107; color: #333; }
        .status-da_giai_quyet { background-color: #28a745; }
        /* Thêm CSS trạng thái người dùng (từ file cũ của bạn) */
        .status-hoat_dong { background-color: #28a745; }
        .status-bi_cam { background-color: #dc3545; }
        .status-cho_xac_minh { background-color: #ffc107; color: #333; }

        /* Hành Động */
        .action-links a {
            color: #007bff;
            text-decoration: none;
            margin-right: 10px;
            font-size: 0.9em;
            font-weight: bold;
        }
        .action-links a.delete {
            color: #dc3545;
        }
        .action-links a:hover {
            text-decoration: underline;
        }
        
        /* Form chung */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
        }
        .form-group input[disabled], .form-group select[disabled] { background-color: #eee; }
        
        /* Thông báo chung */
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message.error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        /* Nút bấm chung */
        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 15px;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            font-size: 14px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-success { background-color: #28a745; }
        .btn-secondary { background-color: #6c757d; }
        .btn:hover { opacity: 0.9; }

    </style>
</head>
<body>

<div class="admin-layout">
    
    <?php require 'menu_quan_tri.php'; ?>
    
    <div class="main-content">