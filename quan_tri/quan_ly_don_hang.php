<?php
// 1. KHỞI TẠO BIẾN
$page_title = "Quản lý Đơn Hàng"; 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$thong_bao = $_SESSION['thong_bao'] ?? "";
$thong_bao_loi = $_SESSION['thong_bao_loi'] ?? "";
unset($_SESSION['thong_bao'], $_SESSION['thong_bao_loi']); 

$action = $_GET['action'] ?? 'danh_sach'; 
$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

$tab = $_GET['tab'] ?? 'cho_xac_nhan_thanh_toan'; // (MỚI) Ưu tiên tab này
$search_keyword = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? ''; 
$date_to = $_GET['date_to'] ?? ''; 

$query_params = ['tab' => $tab, 'search' => $search_keyword, 'date_from' => $date_from, 'date_to' => $date_to];
$query_params = array_filter($query_params); 
$query_string = http_build_query($query_params);

// 2. GỌI CSDL VÀ KIỂM TRA ADMIN
require '../dung_chung/ket_noi_csdl.php'; 
require 'kiem_tra_quan_tri.php'; 

// 3. HÀM HỖ TRỢ (HOÀN KHO & TRỪ KHO)
function tra_hang_vao_kho($conn, $id_don_hang) {
    // (Code hàm này giữ nguyên)
    $sql_get_items = "SELECT id_san_pham, so_luong FROM chi_tiet_don_hang WHERE id_don_hang = ?";
    $stmt_get = $conn->prepare($sql_get_items); $stmt_get->bind_param("i", $id_don_hang); $stmt_get->execute();
    $items = $stmt_get->get_result();
    $sql_update_stock = "UPDATE san_pham SET so_luong_ton = so_luong_ton + ? WHERE id = ?";
    $stmt_stock = $conn->prepare($sql_update_stock);
    while ($item = $items->fetch_assoc()) {
        if ($item['id_san_pham']) {
            $stmt_stock->bind_param("ii", $item['so_luong'], $item['id_san_pham']);
            $stmt_stock->execute();
        }
    }
}
// (MỚI) HÀM TRỪ KHO (KHI DUYỆT THANH TOÁN)
function tru_hang_khoi_kho($conn, $id_don_hang) {
    $sql_get_items = "SELECT id_san_pham, so_luong FROM chi_tiet_don_hang WHERE id_don_hang = ?";
    $stmt_get = $conn->prepare($sql_get_items); $stmt_get->bind_param("i", $id_don_hang); $stmt_get->execute();
    $items = $stmt_get->get_result();
    
    $sql_update_stock = "UPDATE san_pham SET so_luong_ton = so_luong_ton - ?, so_luong_da_ban = so_luong_da_ban + ? WHERE id = ?";
    $stmt_stock = $conn->prepare($sql_update_stock);
    
    while ($item = $items->fetch_assoc()) {
        if ($item['id_san_pham']) {
            $stmt_stock->bind_param("iii", $item['so_luong'], $item['so_luong'], $item['id_san_pham']);
            $stmt_stock->execute();
        }
    }
}


// 4. XỬ LÝ LOGIC (CẬP NHẬT/XÓA)
if (($action == 'update_status' && $id > 0 && !empty($status)) || ($action == 'xoa' && $id > 0)) {
    
    $redirect_url = "quan_ly_don_hang.php?action=danh_sach&" . $query_string;

    if ($action == 'update_status') {
        $thong_bao_redirect = "";
        
        // (MỚI) KHI DUYỆT THANH TOÁN (từ 'chờ' -> 'đang xử lý')
        if ($status == 'dang_xu_ly') {
            $stmt_check = $conn->prepare("SELECT trang_thai_don_hang FROM don_hang WHERE id_don_hang = ?");
            $stmt_check->bind_param("i", $id);
            $stmt_check->execute();
            $current_status = $stmt_check->get_result()->fetch_assoc()['trang_thai_don_hang'];
            
            // Chỉ trừ kho nếu đơn hàng đang ở trạng thái "chờ xác nhận"
            if ($current_status == 'cho_xac_nhan_thanh_toan') {
                tru_hang_khoi_kho($conn, $id);
                $thong_bao_redirect = "Đã xác nhận thanh toán và trừ hàng khỏi kho.";
            }
        }
        // KHI HỦY/TRẢ HÀNG
        elseif ($status == 'da_huy' || $status == 'da_hoan_tra') {
            $stmt_check = $conn->prepare("SELECT trang_thai_don_hang FROM don_hang WHERE id_don_hang = ?");
            $stmt_check->bind_param("i", $id); $stmt_check->execute();
            $current_status = $stmt_check->get_result()->fetch_assoc()['trang_thai_don_hang'];
            
            // Chỉ hoàn kho nếu trạng thái trước đó KHÔNG PHẢI là "đã hủy/hoàn"
            if ($current_status != 'da_huy' && $current_status != 'da_hoan_tra') {
                tra_hang_vao_kho($conn, $id);
                $thong_bao_redirect = "Cập nhật trạng thái và đã hoàn trả hàng về kho.";
            }
        }
        
        // Cập nhật trạng thái
        $stmt_update = $conn->prepare("UPDATE don_hang SET trang_thai_don_hang = ? WHERE id_don_hang = ?");
        $stmt_update->bind_param("si", $status, $id);
        $stmt_update->execute();
        
        if (empty($thong_bao_redirect)) {
            $thong_bao_redirect = "Cập nhật trạng thái đơn hàng thành công!";
        }
        $_SESSION['thong_bao'] = $thong_bao_redirect;
    
    } 
    elseif ($action == 'xoa') {
        // (Logic Xóa giữ nguyên)
        $conn->begin_transaction();
        try {
            $stmt_del_details = $conn->prepare("DELETE FROM chi_tiet_don_hang WHERE id_don_hang = ?");
            $stmt_del_details->bind_param("i", $id); $stmt_del_details->execute();
            $stmt_del_order = $conn->prepare("DELETE FROM don_hang WHERE id_don_hang = ?");
            $stmt_del_order->bind_param("i", $id); $stmt_del_order->execute();
            $conn->commit();
            $_SESSION['thong_bao'] = "Đã xóa vĩnh viễn đơn hàng (ID: $id) thành công!";
        } catch (Exception $e) {
            $conn->rollback();
            $_SESSION['thong_bao_loi'] = "Lỗi khi xóa đơn hàng: " . $e->getMessage();
        }
    }
    
    header("Location: $redirect_url");
    exit();
}

// 5. GỌI ĐẦU TRANG ADMIN
require 'dau_trang_quan_tri.php'; 

// 6. LOGIC LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$danh_sach_don_hang = [];
$don_hang_chi_tiet = null;
$items_in_order = [];

// (MỚI) Thêm trạng thái 'cho_xac_nhan_thanh_toan'
$cac_trang_thai = [
    'cho_xac_nhan_thanh_toan' => 'Chờ xác nhận TT',
    'moi' => 'Đơn hàng mới',
    'dang_xu_ly' => 'Đang chuẩn bị',
    'dang_giao' => 'Đang giao hàng',
    'hoan_thanh' => 'Đã giao hàng',
    'yeu_cau_tra_hang' => 'Chờ duyệt trả hàng',
    'da_hoan_tra' => 'Đã hoàn trả',
    'yeu_cau_huy' => 'Chờ duyệt hủy',
    'da_huy' => 'Đã hủy'
];
$icon_map = [
    'cho_xac_nhan_thanh_toan' => 'fas fa-hourglass-half',
    'moi' => 'fas fa-box',
    'dang_xu_ly' => 'fas fa-sync-alt',
    'dang_giao' => 'fas fa-truck',
    'hoan_thanh' => 'fas fa-check-circle',
    'yeu_cau_tra_hang' => 'fas fa-undo',
    'da_hoan_tra' => 'fas fa-undo-alt',
    'yeu_cau_huy' => 'fas fa-exclamation-triangle',
    'da_huy' => 'fas fa-times-circle'
];


if ($action == 'danh_sach') {
    // (Logic SELECT cho danh sách giữ nguyên)
    $params = [];
    $types = "";
    $sql_list_base = "SELECT d.id_don_hang, d.ma_don_hang, d.ten_nguoi_nhan, d.so_dien_thoai_nhan, d.tong_tien, d.trang_thai_don_hang, d.ngay_dat,
                        COALESCE(nd.ten, 'Khách vãng lai') as ten_nguoi_dat
                      FROM don_hang d
                      LEFT JOIN nguoi_dung nd ON d.id_nguoi_dung = nd.id_nguoi_dung";
    $where_clauses = []; 
    $where_clauses[] = "d.trang_thai_don_hang = ?";
    $params[] = $tab;
    $types .= "s";
    if (!empty($search_keyword)) { 
        $where_clauses[] = "(d.ma_don_hang LIKE ? OR d.ten_nguoi_nhan LIKE ? OR d.so_dien_thoai_nhan LIKE ? OR d.dia_chi_giao_hang LIKE ?)";
        $search_term = "%" . $search_keyword . "%";
        $params[] = $search_term; $params[] = $search_term; $params[] = $search_term; $params[] = $search_term; 
        $types .= "ssss";
    }
    if (!empty($date_from)) {
        $where_clauses[] = "DATE(d.ngay_dat) >= ?";
        $params[] = $date_from; $types .= "s";
    }
    if (!empty($date_to)) {
        $where_clauses[] = "DATE(d.ngay_dat) <= ?";
        $params[] = $date_to; $types .= "s";
    }
    $sql_list = $sql_list_base . " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY d.ngay_dat DESC";
    $stmt_list = $conn->prepare($sql_list);
    if (!empty($params)) {
        $stmt_list->bind_param($types, ...$params);
    }
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
    if ($result_list) {
        while ($row = $result_list->fetch_assoc()) {
            $danh_sach_don_hang[] = $row;
        }
    }

} elseif ($action == 'xem' && $id > 0) {
    // (Logic SELECT cho chi tiết giữ nguyên)
    $sql_dh = "SELECT d.*, COALESCE(nd.ten, 'Khách vãng lai') as ten_nguoi_dat, nd.email as email_nguoi_dat
               FROM don_hang d
               LEFT JOIN nguoi_dung nd ON d.id_nguoi_dung = nd.id_nguoi_dung
               WHERE d.id_don_hang = ?";
    $stmt_dh = $conn->prepare($sql_dh);
    $stmt_dh->bind_param("i", $id);
    $stmt_dh->execute();
    $don_hang_chi_tiet = $stmt_dh->get_result()->fetch_assoc();

    if ($don_hang_chi_tiet) {
        $sql_items = "SELECT ct.*, sp.anh_dai_dien 
                      FROM chi_tiet_don_hang ct
                      LEFT JOIN san_pham sp ON ct.id_san_pham = sp.id
                      WHERE ct.id_don_hang = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $id);
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

<style>
    /* (CSS Bộ lọc) */
    .filter-container {
        width: 100%;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: grid;
        grid-template-columns: 2fr 1fr 1fr; 
        gap: 20px;
        flex-wrap: wrap; 
        align-items: flex-end; 
        margin-bottom: 20px;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
    }
    .filter-group label {
        font-size: 0.85em;
        color: #555;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .filter-actions {
        grid-column: 1 / -1; 
        display: flex;
        gap: 10px;
    }
    
    /* (CSS Bảng) */
    .action-links a {
        text-decoration: none;
        margin-right: 12px;
        font-weight: bold;
        font-size: 0.9em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .action-links a i { width: 1.2em; text-align: center; }
    .action-links a.view { color: #007bff; }
    .action-links a.delete { color: #dc3545; }
    .action-links a:hover { text-decoration: underline; }

    /* (CSS Trang Chi Tiết) */
    .order-details-grid {
        display: grid;
        grid-template-columns: 2fr 1fr; 
        gap: 25px;
    }
    .info-box { 
        background: #fff; 
        border-radius: 8px; 
        padding: 25px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .info-box h3 { 
        border-bottom: 1px solid #eee; 
        padding-bottom: 10px; 
        margin-top: 0; 
        margin-bottom: 20px; 
        font-size: 1.5rem;
    }
    .info-box h3 i { margin-right: 10px; color: #007bff; }
    .customer-info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    .info-item label {
        font-weight: bold;
        color: #333;
        display: block;
        font-size: 0.9em;
    }
    .info-item p { margin: 0; font-size: 1em; color: #555; }
    
    .order-items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .order-items-table th, .order-items-table td {
        border-bottom: 1px solid #ddd;
        padding: 10px;
        font-size: 0.9em;
        text-align: left;
    }
    .order-items-table th { background-color: #f9f9f9; }
    .order-items-table img {
        width: 40px; height: 40px;
        object-fit: contain; border-radius: 4px;
        border: 1px solid #eee;
    }
    .order-total-summary {
        margin-top: 15px;
        padding-top: 15px;
        border-top: 2px solid #000;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 5px;
    }
    .summary-row.total {
        font-size: 1.3em;
        font-weight: bold;
        color: var(--danger-color);
    }
    
    /* (MỚI) CSS Cho Bill Thanh Toán */
    .bill-image-container {
        margin-top: 20px;
    }
    .bill-image-container h3 {
        color: #e67e22; /* Màu cam */
    }
    .bill-image-container img {
        width: 100%;
        max-width: 400px;
        border-radius: 8px;
        border: 2px dashed #e67e22;
        cursor: pointer;
    }
    
    .action-box .btn {
        width: 100%;
        margin-bottom: 10px;
        justify-content: center; 
    }
    .btn-xac-nhan { background-color: #007bff; }
    .btn-giao-hang { background-color: #ffc107; color: #333; }
    .btn-hoan-thanh { background-color: #28a745; }
    .btn-huy { background-color: #dc3545; }
    .btn-tu-choi { background-color: #6c757d; }
    
    .status-note {
        text-align: center;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 5px;
        font-weight: bold;
        color: #333;
    }
    .status-note.warn {
        background-color: #fff8e1;
        color: #e67e22;
    }
    
    /* (MỚI) CSS Cho Tab Menu (Thêm cho trạng thái mới) */
    .status-cho_xac_nhan_thanh_toan {
        background-color: #fd7e14; /* Màu cam */
        color: #fff !important;
    }
</style>


<h1>Quản lý Đơn Hàng</h1>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<?php if ($action == 'danh_sach'): ?>
    
    <nav class="tab-menu" style="background-color: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); padding: 10px; margin-bottom: 20px;">
        <?php foreach ($cac_trang_thai as $key_status => $ten_status): ?>
            <a href="?tab=<?php echo $key_status; ?>" 
               class="<?php echo ($tab == $key_status) ? 'active' : ''; ?>"
               data-status="<?php echo $key_status; ?>">
               <i class="<?php echo $icon_map[$key_status]; ?>"></i>
               <?php echo $ten_status; ?>
            </a>
        <?php endforeach; ?>
    </nav>
    
    <form action="quan_ly_don_hang.php" method="GET" class="filter-container">
        <input type="hidden" name="action" value="danh_sach">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
        
        <div class="filter-group">
            <label for="search">Tìm theo Mã ĐH, Tên, SĐT, Địa chỉ...</label>
            <input type="text" id="search" name="search" placeholder="Nhập Mã ĐH, Tên, SĐT, Tỉnh, Phường..." value="<?php echo htmlspecialchars($search_keyword); ?>">
        </div>
        
        <div class="filter-group">
            <label for="date_from">Từ ngày:</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        
        <div class="filter-group">
            <label for="date_to">Đến ngày:</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn"><i class="fas fa-filter"></i> Lọc</button>
            <a href="?tab=<?php echo htmlspecialchars($tab); ?>" class="btn btn-secondary"><i class="fas fa-times"></i> Xóa Lọc</a>
        </div>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã ĐH</th>
                <th>Khách hàng</th>
                <th>SĐT Nhận</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Ngày đặt</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($danh_sach_don_hang)): ?>
                <tr>
                    <td colspan="7" style="text-align: center;">Không có đơn hàng nào trong mục này.</td>
                </tr>
            <?php else: ?>
                <?php foreach($danh_sach_don_hang as $order): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['ma_don_hang']); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['ten_nguoi_dat']); ?></td>
                        <td><?php echo htmlspecialchars($order['so_dien_thoai_nhan']); ?></td>
                        <td><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?>đ</td>
                        <td>
                            <span class="status-label status-<?php echo str_replace('_', '-', $order['trang_thai_don_hang']); ?>">
                                <?php echo $cac_trang_thai[$order['trang_thai_don_hang']]; ?>
                            </span>
                        </td>
                        <td><?php echo date('d-m-Y H:i', strtotime($order['ngay_dat'])); ?></td>
                        <td class="action-links">
                            <a href="?action=xem&id=<?php echo $order['id_don_hang']; ?>&<?php echo $query_string; ?>" class="view" title="Xem chi tiết">
                                <i class="fas fa-eye"></i> Xem
                            </a>
                            <a href="?action=xoa&id=<?php echo $order['id_don_hang']; ?>&<?php echo $query_string; ?>" class="delete" title="Xóa vĩnh viễn"
                               onclick="return confirm('CẢNH BÁO:\nBạn có chắc muốn XÓA VĨNH VIỄN đơn hàng này không?\n\nHành động này không thể hoàn tác và sẽ xóa cả chi tiết đơn hàng.\n\n(Lưu ý: Nếu chỉ muốn hủy, hãy dùng nút \"Hủy đơn\" trong Xem Chi Tiết)');">
                                <i class="fas fa-trash-alt"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action == 'xem' && $don_hang_chi_tiet): ?>
    
    <div class="page-header">
        <h2>Chi tiết Đơn hàng: <?php echo htmlspecialchars($don_hang_chi_tiet['ma_don_hang']); ?></h2>
        <a href="?action=danh_sach&<?php echo $query_string; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay Lại Danh Sách
        </a>
    </div>
    
    <div class="order-details-grid">
        
        <div>
            <div class="info-box">
                <h3><i class="fas fa-user-circle"></i> Thông tin Khách hàng & Nhận hàng</h3>
                <div class="customer-info-grid">
                    <div class="info-item"><label>Tên người đặt:</label><p><?php echo htmlspecialchars($don_hang_chi_tiet['ten_nguoi_dat']); ?></p></div>
                    <div class="info-item"><label>Email đặt hàng:</label><p><?php echo htmlspecialchars($don_hang_chi_tiet['email_nguoi_dat']); ?></p></div>
                    <div class="info-item"><label>Tên người nhận:</label><p><?php echo htmlspecialchars($don_hang_chi_tiet['ten_nguoi_nhan']); ?></p></div>
                    <div class="info-item"><label>SĐT nhận hàng:</label><p><?php echo htmlspecialchars($don_hang_chi_tiet['so_dien_thoai_nhan']); ?></p></div>
                    <div class="info-item" style="grid-column: 1 / -1;"><label>Địa chỉ giao hàng:</label><p><?php echo htmlspecialchars($don_hang_chi_tiet['dia_chi_giao_hang']); ?></p></div>
                    <div class="info-item" style="grid-column: 1 / -1;"><label>Ghi chú của khách:</label><p><?php echo nl2br(htmlspecialchars($don_hang_chi_tiet['ghi_chu'] ?? '(Không có)')); ?></p></div>
                </div>
            </div>
            
            <?php if (!empty($don_hang_chi_tiet['anh_bill_thanh_toan'])): ?>
                <div class="info-box bill-image-container" style="margin-top: 20px;">
                    <h3><i class="fas fa-receipt"></i> Bill Thanh Toán Của Khách</h3>
                    <?php
                        $bill_path = 'tai_len/bills/' . $don_hang_chi_tiet['anh_bill_thanh_toan'];
                        if (file_exists(ROOT_PATH . $bill_path)) {
                            echo '<img src="' . BASE_URL . $bill_path . '" alt="Bill Thanh Toán" onclick="openModal(this)">';
                        } else {
                            echo "<p style='color: red;'>Không tìm thấy file ảnh bill.</p>";
                        }
                    ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box" style="margin-top: 20px;">
                <h3><i class="fas fa-cubes"></i> Danh sách Sản phẩm</h3>
                <table class="order-items-table">
                    <thead>
                        <tr><th colspan="2">Sản phẩm</th><th>Giá</th><th>Số lượng</th><th>Thành tiền</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items_in_order as $item): ?>
                            <tr>
                                <td style="width: 50px;">
                                    <?php 
                                    $anh_path = 'tai_len/san_pham/' . ($item['anh_dai_dien'] ?? 'default.png');
                                    if (empty($item['anh_dai_dien']) || !file_exists(ROOT_PATH . $anh_path)) {
                                        $anh_path = 'tai_len/san_pham/default.png'; 
                                    }
                                    ?>
                                    <img src="<?php echo BASE_URL . $anh_path; ?>" alt="">
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($item['ten_san_pham_luc_mua']); ?><br>
                                    <small>(Màu: <?php echo htmlspecialchars($item['mau_sac_luc_mua']); ?>)</small>
                                </td>
                                <td><?php echo number_format($item['gia_luc_mua'], 0, ',', '.'); ?>đ</td>
                                <td>x <?php echo $item['so_luong']; ?></td>
                                <td><?php echo number_format($item['gia_luc_mua'] * $item['so_luong'], 0, ',', '.'); ?>đ</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <div class="order-total-summary">
                    <div class="summary-row">
                        <span>Tiền hàng</span>
                        <span><?php echo number_format($don_hang_chi_tiet['tong_tien'] + $don_hang_chi_tiet['so_tien_giam_gia'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="summary-row">
                        <span>Giảm giá (<?php echo htmlspecialchars($don_hang_chi_tiet['ma_giam_gia_da_ap'] ?? '...'); ?>)</span>
                        <span>- <?php echo number_format($don_hang_chi_tiet['so_tien_giam_gia'], 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="summary-row total">
                        <span>Tổng cộng</span>
                        <span><?php echo number_format($don_hang_chi_tiet['tong_tien'], 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="action-box">
            <div class="info-box">
                <h3><i class="fas fa-tasks"></i> Trạng thái & Hành động</h3>
                <p>Trạng thái hiện tại:</p>
                <span class="status-label status-<?php echo str_replace('_', '-', $don_hang_chi_tiet['trang_thai_don_hang']); ?>" style="font-size: 1.1em; padding: 10px;">
                    <?php echo $cac_trang_thai[$don_hang_chi_tiet['trang_thai_don_hang']]; ?>
                </span>
                
                <hr style="margin: 20px 0;">
                
                <?php $current_status = $don_hang_chi_tiet['trang_thai_don_hang']; ?>
                
                <?php if ($current_status == 'cho_xac_nhan_thanh_toan'): ?>
                    <p class="status-note warn">Khách hàng đã upload bill. Vui lòng kiểm tra tài khoản.</p>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=dang_xu_ly&<?php echo $query_string; ?>" class="btn btn-xac-nhan" onclick="return confirm('Xác nhận ĐÃ NHẬN TIỀN? (Hàng sẽ bị trừ kho)');">
                        <i class="fas fa-check"></i> Xác nhận đã thanh toán
                    </a>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=da_huy&<?php echo $query_string; ?>" class="btn btn-huy" onclick="return confirm('Bạn có chắc muốn HỦY đơn hàng này? (Bill lỗi, chưa nhận tiền, KHÔNG hoàn kho)');">
                        <i class="fas fa-times"></i> Hủy (Thanh toán lỗi)
                    </a>
                
                <?php elseif ($current_status == 'moi'): ?>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=dang_xu_ly&<?php echo $query_string; ?>" class="btn btn-xac-nhan" onclick="return confirm('Xác nhận đơn hàng này?');">
                        <i class="fas fa-check"></i> Xác nhận (Đang xử lý)
                    </a>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=da_huy&<?php echo $query_string; ?>" class="btn btn-huy" onclick="return confirm('Bạn có chắc muốn HỦY đơn hàng này? (Hàng sẽ được hoàn kho)');">
                        <i class="fas fa-times"></i> Hủy đơn hàng
                    </a>
                <?php elseif ($current_status == 'dang_xu_ly'): ?>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=dang_giao&<?php echo $query_string; ?>" class="btn btn-giao-hang" onclick="return confirm('Xác nhận giao hàng cho đơn này?');">
                        <i class="fas fa-truck"></i> Bắt đầu Giao hàng
                    </a>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=da_huy&<?php echo $query_string; ?>" class="btn btn-huy" onclick="return confirm('Bạn có chắc muốn HỦY đơn hàng này? (Hàng sẽ được hoàn kho)');">
                        <i class="fas fa-times"></i> Hủy đơn hàng
                    </a>
                <?php elseif ($current_status == 'dang_giao'): ?>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=hoan_thanh&<?php echo $query_string; ?>" class="btn btn-hoan-thanh" onclick="return confirm('Xác nhận đơn hàng đã giao thành công?');">
                        <i class="fas fa-check-double"></i> Đã giao (Hoàn thành)
                    </a>
                <?php elseif ($current_status == 'yeu_cau_huy'): ?>
                    <p class="status-note warn">Khách hàng đang yêu cầu hủy đơn!</p>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=da_huy&<?php echo $query_string; ?>" class="btn btn-huy" onclick="return confirm('DUYỆT HỦY đơn hàng này? (Hàng sẽ được hoàn kho)');">
                        <i class="fas fa-check"></i> Duyệt Hủy Đơn
                    </a>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=dang_giao&<?php echo $query_string; ?>" class="btn btn-secondary" onclick="return confirm('TỪ CHỐI hủy? (Đơn hàng sẽ quay về \'Đang giao\')');">
                        <i class="fas fa-times"></i> Từ chối
                    </a>
                <?php elseif ($current_status == 'yeu_cau_tra_hang'): ?>
                    <p class="status-note warn">Khách hàng đang yêu cầu trả hàng!</p>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=da_hoan_tra&<?php echo $query_string; ?>" class="btn btn-huy" onclick="return confirm('DUYỆT TRẢ HÀNG? (Hàng sẽ được hoàn kho)');">
                        <i class="fas fa-check"></i> Duyệt Trả Hàng
                    </a>
                    <a href="?action=update_status&id=<?php echo $id; ?>&status=hoan_thanh&<?php echo $query_string; ?>" class="btn btn-secondary" onclick="return confirm('TỪ CHỐI trả hàng? (Đơn hàng sẽ quay về \'Hoàn thành\')');">
                        <i class="fas fa-times"></i> Từ chối
                    </a>
                <?php else: ?>
                    <p class="status-note">Đơn hàng đã ở trạng thái cuối cùng, không thể thao tác.</p>
                <?php endif; ?>
                
            </div>
        </div>
        
    </div>

<?php else: ?>
    <div class="message error">Không tìm thấy dữ liệu.</div>
<?php endif; ?>

<div id="imageModal" class="modal">
  <span class="close" onclick="closeModal()">&times;</span>
  <img class="modal-content" id="img01">
</div>

<script>
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("img01");
    
    function openModal(imgElement) {
        modal.style.display = "block";
        modalImg.src = imgElement.src;
    }
    
    function closeModal() {
        modal.style.display = "none";
    }
    modal.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>

<?php require 'cuoi_trang_quan_tri.php'; ?>