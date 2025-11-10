<?php
// 1. BẮT ĐẦU SESSION VÀ KIỂM TRA BẢO MẬT
session_start();
if (!isset($_SESSION['id_nguoi_dung'])) {
    header("Location: ../dang_nhap.php");
    exit();
}
if ($_SESSION['vai_tro'] !== 'quan_tri') {
    echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Lỗi</title></head><body>";
    echo "<h1>Lỗi 403: Bạn không có quyền truy cập.</h1><a href='../index.php'>Quay về</a>";
    echo "</body></html>";
    exit();
}

// 2. KẾT NỐI CSDL
require '../dung_chung/ket_noi_csdl.php';

// 3. KHỞI TẠO BIẾN
$thong_bao = "";
$thong_bao_loi = "";
$action = $_GET['action'] ?? 'danh_sach'; 
$id_don_hang_xem = (int)($_GET['id'] ?? 0);

// === THỨ TỰ TAB ĐÃ ĐƯỢC SỬA LẠI THEO YÊU CẦU CỦA BẠN ===
$cac_trang_thai = [
    'moi' => 'Đơn hàng mới',
    'dang_xu_ly' => 'Đang chuẩn bị',
    'dang_giao' => 'Đang giao hàng',
    'hoan_thanh' => 'Đã giao hàng',
    'yeu_cau_tra_hang' => 'Chờ duyệt trả hàng',
    'da_hoan_tra' => 'Đã hoàn trả',
    'yeu_cau_huy' => 'Chờ duyệt hủy',
    'da_huy' => 'Đã hủy'
];
// =======================================================

// 4. XỬ LÝ POST (CẬP NHẬT TRẠNG THÁI)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action_post = $_POST['action'] ?? '';
    $id_don_hang_cap_nhat = (int)$_POST['id_don_hang'];

    // --- 4.1. XỬ LÝ DUYỆT (Đồng ý / Từ chối) ---
    if ($action_post == 'xu_ly_yeu_cau') {
        $quyet_dinh = $_POST['quyet_dinh']; 
        $trang_thai_hien_tai = $_POST['trang_thai_hien_tai'];
        $trang_thai_moi = '';

        if ($trang_thai_hien_tai == 'yeu_cau_huy') {
            $trang_thai_moi = ($quyet_dinh == 'dong_y') ? 'da_huy' : 'dang_xu_ly';
        } elseif ($trang_thai_hien_tai == 'yeu_cau_tra_hang') {
            $trang_thai_moi = ($quyet_dinh == 'dong_y') ? 'da_hoan_tra' : 'hoan_thanh';
        }

        if ($trang_thai_moi) {
            $sql_update = "UPDATE don_hang SET trang_thai_don_hang = ? WHERE id_don_hang = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $trang_thai_moi, $id_don_hang_cap_nhat);
            if ($stmt_update->execute()) {
                $thong_bao = "Duyệt yêu cầu thành công!";
            } else {
                $thong_bao_loi = "Lỗi khi duyệt yêu cầu.";
            }
        }
    } 
    // --- 4.2. XỬ LÝ CẬP NHẬT THỦ CÔNG (Dropdown) ---
    elseif ($action_post == 'cap_nhat_trang_thai') {
        $trang_thai_moi = $conn->real_escape_string($_POST['trang_thai_moi']);

        if (array_key_exists($trang_thai_moi, $cac_trang_thai)) {
            $sql_update = "UPDATE don_hang SET trang_thai_don_hang = ? WHERE id_don_hang = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("si", $trang_thai_moi, $id_don_hang_cap_nhat);
            
            if ($stmt_update->execute()) {
                $thong_bao = "Cập nhật trạng thái thủ công thành công!";
            } else {
                $thong_bao_loi = "Lỗi khi cập nhật trạng thái.";
            }
        } else {
            $thong_bao_loi = "Trạng thái mới không hợp lệ.";
        }
    }
    
    $action = 'xem';
    $id_don_hang_xem = $id_don_hang_cap_nhat;
}

// 5. TRUY VẤN DỮ LIỆU ĐỂ HIỂN THỊ (GET)
$danh_sach_don_hang = [];
$don_hang_chi_tiet = null;
$items_in_order = [];
$search_params_get = []; 

if ($action == 'danh_sach') {
    // 5.1. Lấy tham số
    $trang_thai_hien_tai = $_GET['trang_thai'] ?? 'moi'; 
    if (!array_key_exists($trang_thai_hien_tai, $cac_trang_thai)) {
        $trang_thai_hien_tai = 'moi'; 
    }
    
    $search_keyword = $_GET['search_keyword'] ?? '';
    $search_ngay_bat_dau = $_GET['search_ngay_bat_dau'] ?? '';
    $search_ngay_ket_thuc = $_GET['search_ngay_ket_thuc'] ?? '';
    
    $search_params_get = [
        'search_keyword' => $search_keyword,
        'search_ngay_bat_dau' => $search_ngay_bat_dau,
        'search_ngay_ket_thuc' => $search_ngay_ket_thuc
    ];

    // 5.2. Xây dựng câu SQL động
    $sql = "SELECT id_don_hang, ma_don_hang, ten_nguoi_nhan, so_dien_thoai_nhan, tong_tien, trang_thai_don_hang, ngay_dat 
            FROM don_hang";
    
    $where_clauses = [];
    $params = [];
    $param_types = "";
    
    $where_clauses[] = "trang_thai_don_hang = ?";
    $params[] = $trang_thai_hien_tai;
    $param_types .= "s";
    
    if (!empty($search_keyword)) {
        $kw = "%" . $conn->real_escape_string($search_keyword) . "%";
        $where_clauses[] = "(ma_don_hang LIKE ? OR ten_nguoi_nhan LIKE ? OR so_dien_thoai_nhan LIKE ? OR email_nguoi_nhan LIKE ?)";
        $params[] = $kw; $params[] = $kw; $params[] = $kw; $params[] = $kw;
        $param_types .= "ssss";
    }
    
    if (!empty($search_ngay_bat_dau)) {
        $where_clauses[] = "ngay_dat >= ?";
        $params[] = $search_ngay_bat_dau . " 00:00:00";
        $param_types .= "s";
    }
    if (!empty($search_ngay_ket_thuc)) {
        $where_clauses[] = "ngay_dat <= ?";
        $params[] = $search_ngay_ket_thuc . " 23:59:59";
        $param_types .= "s";
    }
    
    // 5.3. Ghép SQL và thực thi
    if (!empty($where_clauses)) {
        $sql .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $sql .= " ORDER BY ngay_dat DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    $stmt->execute();
    $result_don_hang = $stmt->get_result();

    if ($result_don_hang) {
        while ($row = $result_don_hang->fetch_assoc()) {
            $danh_sach_don_hang[] = $row;
        }
    } else {
        $thong_bao_loi = "Lỗi khi truy vấn đơn hàng: " . $conn->error;
    }
} 
elseif ($action == 'xem' && $id_don_hang_xem > 0) {
    // Lấy chi tiết 1 đơn hàng
    $sql_dh = "SELECT * FROM don_hang WHERE id_don_hang = ?";
    $stmt_dh = $conn->prepare($sql_dh);
    $stmt_dh->bind_param("i", $id_don_hang_xem);
    $stmt_dh->execute();
    $don_hang_chi_tiet = $stmt_dh->get_result()->fetch_assoc();

    if ($don_hang_chi_tiet) {
        $sql_items = "SELECT * FROM chi_tiet_don_hang WHERE id_don_hang = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $id_don_hang_xem);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($item = $result_items->fetch_assoc()) {
            $items_in_order[] = $item;
        }
    } else {
        $thong_bao_loi = "Không tìm thấy đơn hàng.";
        $action = 'danh_sach';
    }
}

?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quản lý Đơn hàng</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body, html { font-family: Arial, sans-serif; background-color: #f4f7f6; height: 100%; }
        .admin-wrapper { display: flex; min-height: 100vh; }

        .sidebar { width: 240px; background-color: #2c3e50; color: white; flex-shrink: 0; }
        .sidebar-header { padding: 20px; text-align: center; font-size: 20px; font-weight: bold; border-bottom: 1px solid #34495e; }
        .sidebar-menu { list-style-type: none; }
        .sidebar-menu li a { display: block; padding: 18px 25px; color: #ecf0f1; text-decoration: none; transition: background-color 0.3s; }
        .sidebar-menu li.active a { background-color: #34495e; border-left: 3px solid #e74c3c; }
        .sidebar-menu li a:hover { background-color: #34495e; }
        .sidebar-menu .back-to-site { margin-top: 30px; }
        .sidebar-menu .back-to-site a { background-color: #3498db; color: white; font-weight: bold; }
        .sidebar-menu .back-to-site a:hover { background-color: #2980b9; }
        .sidebar-menu .logout { margin-top: 10px; }
        .sidebar-menu .logout a { background-color: #e74c3c; color: white; }

        .main-content { flex-grow: 1; padding: 30px; overflow-y: auto; }
        .main-content h1 { color: #333; margin-bottom: 20px; }
        .message { padding: 10px 15px; margin-bottom: 15px; border-radius: 4px; }
        .message-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .message-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        
        .btn { background-color: #007bff; color: white; padding: 10px 15px; text-decoration: none; border: none; border-radius: 4px; font-weight: bold; cursor: pointer; font-size: 14px; }
        .btn:hover { background-color: #0056b3; }
        .btn-secondary { background-color: #6c757d; }
        .btn-secondary:hover { background-color: #5a6268; }
        .btn-success { background-color: #28a745; }
        .btn-success:hover { background-color: #218838; }
        .btn-danger { background-color: #dc3545; }
        .btn-danger:hover { background-color: #c82333; }

        .search-form-container { background-color: #fff; padding: 20px; border-radius: 8px; margin-bottom: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .search-form { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px 20px; align-items: flex-end; }
        .search-group { flex-grow: 1; }
        .search-group.keyword-group { grid-column: 1 / 3; }
        .search-group label { display: block; margin-bottom: 5px; font-weight: 600; font-size: 0.9rem; }
        .search-group input[type="text"],
        .search-group input[type="date"] { width: 100%; padding: 9px; border: 1px solid #ccc; border-radius: 4px; font-size: 0.9rem; }
        .search-group input[disabled] { background-color: #eee; }
        .search-actions { display: flex; gap: 10px; }

        .tab-menu { display: flex; flex-wrap: wrap; border-bottom: 2px solid #ddd; margin-bottom: 20px; }
        .tab-menu a {
            padding: 12px 18px; text-decoration: none; color: #555;
            font-weight: bold; font-size: 15px; border-bottom: 3px solid transparent;
            margin-bottom: -2px; 
        }
        .tab-menu a:hover { color: #000; }
        .tab-menu a.active { color: #e74c3c; border-bottom-color: #e74c3c; }
        .tab-menu a[data-status="yeu_cau_huy"],
        .tab-menu a[data-status="yeu_cau_tra_hang"] { color: #fd7e14; }
        .tab-menu a[data-status="yeu_cau_huy"].active,
        .tab-menu a[data-status="yeu_cau_tra_hang"].active { border-bottom-color: #fd7e14; }

        table { width: 100%; border-collapse: collapse; box-shadow: 0 2px 5px rgba(0,0,0,0.1); background: #fff; }
        table th, table td { border: 1px solid #ddd; padding: 12px 15px; text-align: left; vertical-align: middle; }
        table th { background-color: #f2f2ff; font-weight: bold; color: #333; }
        .action-links a { text-decoration: none; color: #007bff; font-weight: bold; }
        
        .status-label {
            padding: 5px 10px; border-radius: 4px; color: white;
            font-size: 12px; font-weight: bold; text-transform: uppercase;
        }
        .status-moi { background-color: #007bff; }
        .status-dang_xu_ly { background-color: #17a2b8; }
        .status-dang_giao { background-color: #ffc107; color: #333; }
        .status-hoan_thanh { background-color: #28a745; }
        .status-da_huy { background-color: #dc3545; }
        .status-da_hoan_tra { background-color: #6c757d; }
        .status-yeu_cau_huy { background-color: #fd7e14; }
        .status-yeu_cau_tra_hang { background-color: #fd7e14; }

        .page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .order-details-grid { display: grid; grid-template-columns: 2fr 1fr; gap: 25px; }
        .info-box { background: #fff; border-radius: 8px; padding: 20px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .info-box h3 { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-top: 0; margin-bottom: 15px; }
        .info-box p { margin-bottom: 10px; line-height: 1.6; }
        .info-box p strong { display: inline-block; width: 130px; color: #555; }
        .order-items-table { margin-top: 20px; }
        
        .update-form .form-group { margin-bottom: 15px; }
        .update-form label { display: block; margin-bottom: 5px; font-weight: bold; }
        .update-form select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 16px; }
        .approval-actions { display: flex; gap: 10px; }
        .approval-actions .btn { flex: 1; }
        .status-pending-text {
            font-weight: bold; color: #fd7e14; font-size: 1.1em; text-align: center;
            padding: 20px; background: #fff8f0; border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="admin-wrapper">

        <?php require 'menu_quan_tri.php'; ?>

        <main class="main-content">
            
            <?php if (!empty($thong_bao)): ?>
                <div class="message message-success"><?php echo $thong_bao; ?></div>
            <?php endif; ?>
            <?php if (!empty($thong_bao_loi)): ?>
                <div class="message message-error"><?php echo $thong_bao_loi; ?></div>
            <?php endif; ?>

            <?php if ($action == 'danh_sach'): ?>
                <h1>Quản lý Đơn hàng</h1>

                <div class="search-form-container">
                    <form action="quan_ly_don_hang.php" method="GET" class="search-form">
                        <input type="hidden" name="action" value="danh_sach">
                        <input type="hidden" name="trang_thai" value="<?php echo htmlspecialchars($trang_thai_hien_tai); ?>">
                        
                        <div class="search-group keyword-group">
                            <label for="search_keyword">Tìm kiếm (Mã ĐH, Tên, SĐT, Email):</label>
                            <input type="text" id="search_keyword" name="search_keyword" 
                                   value="<?php echo htmlspecialchars($search_params_get['search_keyword']); ?>">
                        </div>
                        
                        <div class="search-group">
                            <label>Trạng thái đang xem:</label>
                            <input type="text" value="<?php echo $cac_trang_thai[$trang_thai_hien_tai]; ?>" disabled>
                        </div>

                        <div class="search-group">
                            <label for="search_ngay_bat_dau">Từ ngày:</label>
                            <input type="date" id="search_ngay_bat_dau" name="search_ngay_bat_dau"
                                   value="<?php echo htmlspecialchars($search_params_get['search_ngay_bat_dau']); ?>">
                        </div>
                        
                        <div class="search-group">
                            <label for="search_ngay_ket_thuc">Đến ngày:</label>
                            <input type="date" id="search_ngay_ket_thuc" name="search_ngay_ket_thuc"
                                   value="<?php echo htmlspecialchars($search_params_get['search_ngay_ket_thuc']); ?>">
                        </div>
                        
                        <div class="search-actions">
                            <button type="submit" class="btn">Lọc</button>
                            <a href="?trang_thai=<?php echo $trang_thai_hien_tai; ?>" class="btn btn-secondary">Xóa Lọc</a>
                        </div>
                    </form>
                </div>

                <nav class="tab-menu">
                    <?php 
                    $search_query_string = http_build_query($search_params_get);
                    ?>
                    <?php foreach ($cac_trang_thai as $key_status => $ten_status): ?>
                        <a href="?action=danh_sach&trang_thai=<?php echo $key_status; ?>&<?php echo $search_query_string; ?>" 
                           class="<?php echo ($trang_thai_hien_tai == $key_status) ? 'active' : ''; ?>"
                           data-status="<?php echo $key_status; ?>">
                           <?php echo $ten_status; ?>
                        </a>
                    <?php endforeach; ?>
                </nav>

                <table>
                    <thead>
                        <tr>
                            <th>Mã ĐH</th>
                            <th>Ngày Đặt</th>
                            <th>Khách Hàng</th>
                            <th>Tổng Tiền</th>
                            <th>Trạng Thái</th>
                            <th>Hành Động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($danh_sach_don_hang)): ?>
                            <tr>
                                <td colspan="6" style="text-align: center;">Không có đơn hàng nào khớp với tìm kiếm.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($danh_sach_don_hang as $don): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($don['ma_don_hang']); ?></strong></td>
                                    <td><?php echo date('d-m-Y H:i', strtotime($don['ngay_dat'])); ?></td>
                                    <td><?php echo htmlspecialchars($don['ten_nguoi_nhan']); ?></td>
                                    <td><?php echo number_format($don['tong_tien'], 0, ',', '.'); ?>đ</td>
                                    <td>
                                        <span class="status-label status-<?php echo $don['trang_thai_don_hang']; ?>">
                                            <?php echo $cac_trang_thai[$don['trang_thai_don_hang']]; ?>
                                        </span>
                                    </td>
                                    <td class="action-links">
                                        <a href="?action=xem&id=<?php echo $don['id_don_hang']; ?>">
                                            Xem & Cập nhật
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            
            <?php elseif ($action == 'xem' && $don_hang_chi_tiet): ?>
                <div class="page-header">
                    <h1>Chi tiết Đơn hàng: <?php echo htmlspecialchars($don_hang_chi_tiet['ma_don_hang']); ?></h1>
                    <a href="?action=danh_sach&trang_thai=<?php echo $don_hang_chi_tiet['trang_thai_don_hang']; ?>" class="btn btn-secondary">
                        &larr; Quay lại danh sách
                    </a>
                </div>

                <div class="order-details-grid">
                    
                    <div class="info-box">
                        <h3>Thông tin Người nhận</h3>
                        <p><strong>Tên người nhận:</strong> <?php echo htmlspecialchars($don_hang_chi_tiet['ten_nguoi_nhan']); ?></p>
                        <p><strong>Số điện thoại:</strong> <?php echo htmlspecialchars($don_hang_chi_tiet['so_dien_thoai_nhan']); ?></p>
                        <p><strong>Địa chỉ:</strong> <?php echo nl2br(htmlspecialchars($don_hang_chi_tiet['dia_chi_giao_hang'])); ?></p>
                        <p><strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($don_hang_chi_tiet['ghi_chu'] ?? 'Không có')); ?></p>
                        
                        <h3 style="margin-top: 20px;">Các sản phẩm đã đặt</h3>
                        <table class="order-items-table">
                            <thead>
                                <tr>
                                    <th>Tên Sản Phẩm</th>
                                    <th>SL</th>
                                    <th>Đơn Giá</th>
                                    <th>Thành Tiền</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $tong_tien_hang_goc = 0; ?>
                                <?php foreach ($items_in_order as $item): ?>
                                    <tr>
                                        <td>
                                            <?php echo htmlspecialchars($item['ten_san_pham_luc_mua']); ?><br>
                                            <small style="color: #555;">(Màu: <?php echo htmlspecialchars($item['mau_sac_luc_mua']); ?>)</small>
                                        </td>
                                        <td><?php echo $item['so_luong']; ?></td>
                                        <td><?php echo number_format($item['gia_luc_mua'], 0, ',', '.'); ?>đ</td>
                                        <td><?php echo number_format($item['gia_luc_mua'] * $item['so_luong'], 0, ',', '.'); ?>đ</td>
                                    </tr>
                                    <?php $tong_tien_hang_goc += ($item['gia_luc_mua'] * $item['so_luong']); ?>
                                <?php endforeach; ?>
                                <tr style="border-top: 2px solid #000; font-weight: bold;">
                                    <td colspan="3" style="text-align: right;">Tiền hàng:</td>
                                    <td><?php echo number_format($tong_tien_hang_goc, 0, ',', '.'); ?>đ</td>
                                </tr>
                                <tr style="font-weight: bold;">
                                    <td colspan="3" style="text-align: right;">Giảm giá (<?php echo htmlspecialchars($don_hang_chi_tiet['ma_giam_gia_da_ap'] ?? '...'); ?>):</td>
                                    <td>- <?php echo number_format($don_hang_chi_tiet['so_tien_giam_gia'], 0, ',', '.'); ?>đ</td>
                                </tr>
                                <tr style="font-weight: bold; font-size: 1.2em;">
                                    <td colspan="3" style="text-align: right;">Tổng Tiền (cuối cùng):</td>
                                    <td><?php echo number_format($don_hang_chi_tiet['tong_tien'], 0, ',', '.'); ?>đ</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="info-box update-form">
                        <h3>Thông tin Đơn hàng</h3>
                        <p><strong>Ngày đặt:</strong> <?php echo date('d-m-Y H:i', strtotime($don_hang_chi_tiet['ngay_dat'])); ?></p>
                        <p><strong>Hình thức TT:</strong> <?php echo strtoupper($don_hang_chi_tiet['phuong_thuc_thanh_toan']); ?></p>
                        
                        <?php $status = $don_hang_chi_tiet['trang_thai_don_hang']; ?>
                        <p><strong>Trạng thái:</strong> 
                            <span class="status-label status-<?php echo $status; ?>">
                                <?php echo $cac_trang_thai[$status]; ?>
                            </span>
                        </p>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Duyệt Yêu Cầu Của Khách</h3>
                        <?php if ($status == 'yeu_cau_huy'): ?>
                            <div class="status-pending-text" style="margin-bottom: 15px;">
                                Khách hàng đang yêu cầu HỦY đơn này.
                            </div>
                            <form action="?action=xem&id=<?php echo $id_don_hang_xem; ?>" method="POST" class="approval-actions">
                                <input type="hidden" name="action" value="xu_ly_yeu_cau">
                                <input type="hidden" name="id_don_hang" value="<?php echo $id_don_hang_xem; ?>">
                                <input type="hidden" name="trang_thai_hien_tai" value="yeu_cau_huy">
                                <button type="submit" name="quyet_dinh" value="dong_y" class="btn btn-danger" onclick="return confirm('Đồng ý HỦY đơn hàng này?');">Đồng ý Hủy</button>
                                <button type="submit" name="quyet_dinh" value="tu_choi" class="btn btn-secondary" onclick="return confirm('Từ chối hủy đơn? Đơn hàng sẽ quay lại trạng thái Đang chuẩn bị.');">Từ chối</button>
                            </form>
                            
                        <?php elseif ($status == 'yeu_cau_tra_hang'): ?>
                            <div class="status-pending-text" style="margin-bottom: 15px;">
                                Khách hàng đang yêu cầu TRẢ HÀNG/HOÀN TIỀN.
                            </div>
                            <form action="?action=xem&id=<?php echo $id_don_hang_xem; ?>" method="POST" class="approval-actions">
                                <input type="hidden" name="action" value="xu_ly_yeu_cau">
                                <input type="hidden" name="id_don_hang" value="<?php echo $id_don_hang_xem; ?>">
                                <input type="hidden" name="trang_thai_hien_tai" value="yeu_cau_tra_hang">
                                <button type="submit" name="quyet_dinh" value="dong_y" class="btn btn-success" onclick="return confirm('Đồng ý cho TRẢ HÀNG? Đơn sẽ chuyển sang Đã hoàn trả.');">Đồng ý Trả hàng</button>
                                <button type="submit" name="quyet_dinh" value="tu_choi" class="btn btn-secondary" onclick="return confirm('Từ chối trả hàng? Đơn sẽ quay lại trạng thái Đã giao hàng.');">Từ chối</button>
                            </form>
                        
                        <?php else: ?>
                            <p style="color: #555; text-align: center;">Không có yêu cầu nào chờ duyệt.</p>
                        <?php endif; ?>
                        
                        <hr style="margin: 20px 0;">
                        
                        <h3>Cập nhật thủ công</h3>
                        <form action="?action=xem&id=<?php echo $id_don_hang_xem; ?>" method="POST">
                            <input type="hidden" name="action" value="cap_nhat_trang_thai">
                            <input type="hidden" name="id_don_hang" value="<?php echo $id_don_hang_xem; ?>">
                            
                            <div class="form-group">
                                <label for="trang_thai_moi">Chọn trạng thái:</label>
                                <select id="trang_thai_moi" name="trang_thai_moi">
                                    <?php foreach ($cac_trang_thai as $key_status => $ten_status): ?>
                                        <option value="<?php echo $key_status; ?>" 
                                            <?php echo ($status == $key_status) ? 'selected' : ''; ?>>
                                            <?php echo $ten_status; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn" style="background-color: #28a745; width: 100%;" onclick="return confirm('Bạn có chắc chắn muốn cập nhật trạng thái này?');">
                                Cập Nhật Thủ Công
                            </button>
                        </form>
                    </div>

                </div>

            <?php endif; // Đóng if ($action == '...') ?>
        </main>
        
    </div> </body>
</html>