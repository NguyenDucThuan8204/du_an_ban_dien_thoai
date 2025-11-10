<?php
// Đặt tiêu đề cho trang này
$page_title = "Đơn Hàng Của Tôi";

// 1. GỌI ĐẦU TRANG (Đã bao gồm session, CSDL, CSS, Menu và Turbolinks)
require 'dung_chung/dau_trang.php';
?>

<?php
// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
// (Toàn bộ logic PHP của trang đơn hàng giữ nguyên)

// KIỂM TRA ĐĂNG NHẬP (BẮT BUỘC)
if (!isset($_SESSION['id_nguoi_dung'])) {
    header("Location: dang_nhap.php"); 
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// HÀM HỖ TRỢ HOÀN KHO
function tra_hang_vao_kho($conn, $id_don_hang) {
    $sql_get_items = "SELECT id_san_pham, so_luong FROM chi_tiet_don_hang WHERE id_don_hang = ?";
    $stmt_get = $conn->prepare($sql_get_items);
    $stmt_get->bind_param("i", $id_don_hang);
    $stmt_get->execute();
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

$action = $_GET['action'] ?? 'danh_sach';
$id_don_hang_xem = (int)($_GET['id'] ?? 0);
$thong_bao_loi = "";
$thong_bao_thanh_cong = "";
$danh_sach_don_hang = [];
$don_hang_chi_tiet = null;
$items_in_order = [];

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
$icon_map = [
    'moi' => 'fas fa-box',
    'dang_xu_ly' => 'fas fa-sync-alt',
    'dang_giao' => 'fas fa-truck',
    'hoan_thanh' => 'fas fa-check-circle',
    'yeu_cau_tra_hang' => 'fas fa-undo',
    'da_hoan_tra' => 'fas fa-undo-alt',
    'yeu_cau_huy' => 'fas fa-exclamation-triangle',
    'da_huy' => 'fas fa-times-circle'
];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_don_hang_post = (int)($_POST['id_don_hang'] ?? 0);
    $action_post = $_POST['action'] ?? '';

    $sql_check_owner = "SELECT trang_thai_don_hang FROM don_hang WHERE id_don_hang = ? AND id_nguoi_dung = ?";
    $stmt_check = $conn->prepare($sql_check_owner);
    $stmt_check->bind_param("ii", $id_don_hang_post, $id_nguoi_dung);
    $stmt_check->execute();
    $don_hang_hien_tai = $stmt_check->get_result()->fetch_assoc();

    if ($don_hang_hien_tai) {
        $trang_thai_hien_tai = $don_hang_hien_tai['trang_thai_don_hang'];
        
        if ($action_post == 'cap_nhat_thong_tin') {
             if ($trang_thai_hien_tai == 'moi' || $trang_thai_hien_tai == 'dang_xu_ly') {
                $ten_nguoi_nhan = $conn->real_escape_string($_POST['ten_nguoi_nhan']);
                $so_dien_thoai_nhan = $conn->real_escape_string($_POST['so_dien_thoai_nhan']);
                $dia_chi_giao_hang = $conn->real_escape_string($_POST['dia_chi_giao_hang']);
                $ghi_chu = $conn->real_escape_string($_POST['ghi_chu']);
                
                $sql_update = "UPDATE don_hang 
                               SET ten_nguoi_nhan = ?, so_dien_thoai_nhan = ?, dia_chi_giao_hang = ?, ghi_chu = ? 
                               WHERE id_don_hang = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("ssssi", $ten_nguoi_nhan, $so_dien_thoai_nhan, $dia_chi_giao_hang, $ghi_chu, $id_don_hang_post);
                if($stmt_update->execute()) {
                    $thong_bao_thanh_cong = "Cập nhật thông tin đơn hàng thành công!";
                } else {
                    $thong_bao_loi = "Lỗi khi cập nhật thông tin.";
                }
            } else {
                $thong_bao_loi = "Không thể cập nhật thông tin khi đơn hàng đang được giao.";
            }
        }
        
        elseif ($action_post == 'huy_truc_tiep') {
            if ($trang_thai_hien_tai == 'moi' || $trang_thai_hien_tai == 'dang_xu_ly') {
                tra_hang_vao_kho($conn, $id_don_hang_post);
                $sql_update = "UPDATE don_hang SET trang_thai_don_hang = 'da_huy' WHERE id_don_hang = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $id_don_hang_post);
                $stmt_update->execute();
                $thong_bao_thanh_cong = "Đã hủy đơn hàng thành công. Tồn kho đã được cập nhật.";
            } else {
                $thong_bao_loi = "Không thể hủy đơn hàng ở trạng thái này.";
            }
        }
        
        elseif ($action_post == 'yeu_cau_huy') {
            if ($trang_thai_hien_tai == 'dang_giao') {
                $sql_update = "UPDATE don_hang SET trang_thai_don_hang = 'yeu_cau_huy' WHERE id_don_hang = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $id_don_hang_post);
                $stmt_update->execute();
                $thong_bao_thanh_cong = "Đã gửi yêu cầu hủy đơn. Vui lòng chờ admin xác nhận.";
            } else {
                $thong_bao_loi = "Không thể yêu cầu hủy đơn hàng ở trạng thái này.";
            }
        }
        
        elseif ($action_post == 'yeu_cau_tra_hang') {
            if ($trang_thai_hien_tai == 'hoan_thanh') {
                $sql_update = "UPDATE don_hang SET trang_thai_don_hang = 'yeu_cau_tra_hang' WHERE id_don_hang = ?";
                $stmt_update = $conn->prepare($sql_update);
                $stmt_update->bind_param("i", $id_don_hang_post);
                $stmt_update->execute();
                $thong_bao_thanh_cong = "Đã gửi yêu cầu trả hàng. Vui lòng chờ admin xác nhận.";
            } else { 
                $thong_bao_loi = "Không thể yêu cầu trả hàng cho đơn hàng này."; 
            }
        }
    } else { 
        $thong_bao_loi = "Bạn không có quyền thực hiện hành động này."; 
    }
    
    $action = 'xem';
    $id_don_hang_xem = $id_don_hang_post;
}

if ($action == 'danh_sach') {
    $trang_thai_hien_tai = $_GET['trang_thai'] ?? 'moi'; 
    if (!array_key_exists($trang_thai_hien_tai, $cac_trang_thai)) {
        $trang_thai_hien_tai = 'moi'; 
    }
    $sql_list = "SELECT id_don_hang, ma_don_hang, tong_tien, trang_thai_don_hang, ngay_dat 
                 FROM don_hang 
                 WHERE id_nguoi_dung = ? AND trang_thai_don_hang = ?
                 ORDER BY ngay_dat DESC";
    $stmt_list = $conn->prepare($sql_list);
    $stmt_list->bind_param("is", $id_nguoi_dung, $trang_thai_hien_tai); 
    $stmt_list->execute();
    $result = $stmt_list->get_result();
    while ($row = $result->fetch_assoc()) {
        $danh_sach_don_hang[] = $row;
    }
} 
elseif ($action == 'xem' && $id_don_hang_xem > 0) {
    $sql_dh = "SELECT * FROM don_hang WHERE id_don_hang = ? AND id_nguoi_dung = ?";
    $stmt_dh = $conn->prepare($sql_dh);
    $stmt_dh->bind_param("ii", $id_don_hang_xem, $id_nguoi_dung);
    $stmt_dh->execute();
    $don_hang_chi_tiet = $stmt_dh->get_result()->fetch_assoc();

    if ($don_hang_chi_tiet) {
        $sql_items = "SELECT ct.*, sp.anh_dai_dien 
                      FROM chi_tiet_don_hang ct
                      LEFT JOIN san_pham sp ON ct.id_san_pham = sp.id
                      WHERE ct.id_don_hang = ?";
        $stmt_items = $conn->prepare($sql_items);
        $stmt_items->bind_param("i", $id_don_hang_xem);
        $stmt_items->execute();
        $result_items = $stmt_items->get_result();
        while ($item = $result_items->fetch_assoc()) {
            $items_in_order[] = $item;
        }
    } else {
        $thong_bao_loi = "Không tìm thấy đơn hàng hoặc bạn không có quyền xem đơn hàng này.";
        $action = 'danh_sach'; 
    }
}
?>

<style>
    /* === CSS MỚI: TAB MENU CHO PHÉP XUỐNG HÀNG === */
    .tab-menu-container {
        width: 100%;
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 25px;
    }
    .tab-menu {
        display: flex;
        flex-wrap: wrap; /* CHO PHÉP XUỐNG HÀNG */
        padding: 10px;
        margin-bottom: 0;
        gap: 5px; /* Thêm khoảng cách giữa các tab */
    }
    .tab-menu a {
        padding: 10px 15px;
        text-decoration: none;
        color: #555;
        font-weight: 600;
        font-size: 0.95rem;
        border-radius: 8px;
        transition: all 0.2s;
        white-space: nowrap; 
        display: flex; 
        align-items: center; 
    }
    .tab-menu a i {
        margin-right: 8px;
        width: 1.2em;
        text-align: center;
        color: #888; 
        transition: color 0.2s;
    }
    .tab-menu a:hover { 
        background-color: #f0f0f0; 
        color: #000;
    }
    .tab-menu a.active {
        background-color: var(--primary-color);
        color: var(--white-color);
    }
    .tab-menu a.active i {
        color: var(--white-color); 
    }
    .tab-menu a[data-status="yeu_cau_huy"] i,
    .tab-menu a[data-status="yeu_cau_tra_hang"] i {
        color: #fd7e14;
    }
    .tab-menu a[data-status="yeu_cau_huy"].active,
    .tab-menu a[data-status="yeu_cau_tra_hang"].active {
        background-color: #fd7e14;
    }
    .tab-menu a[data-status="yeu_cau_huy"].active i,
    .tab-menu a[data-status="yeu_cau_tra_hang"].active i {
        color: var(--white-color);
    }
    .tab-menu a[data-status="da_huy"] i {
        color: #dc3545;
    }
    .tab-menu a[data-status="da_huy"].active {
        background-color: #dc3545;
    }
    
    /* === CSS CŨ CHO BẢNG VÀ CHI TIẾT === */
    table { 
        width: 100%; 
        border-collapse: collapse; 
        margin-top: 20px; 
        background: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden; 
    }
    table th, table td {
        border-bottom: 1px solid #ddd;
        padding: 16px;
        text-align: left;
        vertical-align: middle;
    }
    table th { background-color: #f9f9f9; }
    table tr:last-child td { border-bottom: none; }
    
    /* === CSS MỚI CHO LINK HÀNH ĐỘNG (ICON) === */
    .action-link a { 
        text-decoration: none; 
        color: #007bff; 
        font-weight: bold; 
        display: inline-flex; /* Căn icon */
        align-items: center; /* Căn icon */
        gap: 6px; /* Khoảng cách icon và chữ */
    }
    .btn {
        background-color: #007bff; color: white; padding: 10px 15px;
        text-decoration: none; border: none; border-radius: 4px;
        font-weight: bold; cursor: pointer; font-size: 14px;
        display: inline-flex; /* Căn icon */
        align-items: center; /* Căn icon */
        gap: 6px; /* Khoảng cách icon và chữ */
    }
    .btn-secondary { background-color: #6c757d; }
    .btn-rebuy {
        background-color: #ffffffff; color: white;
        padding: 5px 10px; text-decoration: none; border-radius: 4px;
        font-size: 13px; font-weight: bold;
        display: inline-flex; /* Căn icon */
        align-items: center; /* Căn icon */
        gap: 6px; /* Khoảng cách icon và chữ */
    }
    .btn-rebuy:hover { background-color: #a6b1c8ff; }
    
    .order-item-image {
        width: 60px; height: 60px; object-fit: contain;
        border: 1px solid #eee; border-radius: 8px;
        margin-right: 15px;
    }
    
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
    .info-box { 
        background: #fff; border-radius: var(--border-radius); padding: 25px; 
        box-shadow: var(--shadow);
    }
    .info-box h3 { 
        border-bottom: 1px solid #eee; padding-bottom: 10px; 
        margin-top: 0; margin-bottom: 20px; font-size: 1.5rem;
    }
    .form-group input:disabled, .form-group textarea:disabled {
        background-color: #f5f5f5; color: #888;
    }
    .btn-update { background-color: #28a745; margin-top: 10px; border-radius: 5px; }
    .btn-update:disabled { background-color: #aaa; }
    
    .btn-action {
        width: 100%; padding: 12px; font-size: 1em; font-weight: bold;
        border-radius: 5px; border: none; cursor: pointer; margin-top: 10px;
        display: inline-flex; justify-content: center; align-items: center; gap: 8px;
    }
    .btn-cancel { background-color: #dc3545; color: white; }
    .btn-request-cancel { background-color: #ffc107; color: #333; }
    .btn-return { background-color: #ffc107; color: #333; }
    .status-pending-text {
        font-weight: bold; color: #fd7e14; font-size: 1.1em; text-align: center;
        padding: 20px; background: #fff8f0; border-radius: 5px;
    }
</style>

<main class="container container-small">
    
    <?php if ($thong_bao_thanh_cong): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao_thanh_cong); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <?php if ($action == 'danh_sach'): ?>
        <h1>Lịch sử Đơn hàng của tôi</h1>

        <div class="tab-menu-container">
            <nav class="tab-menu">
                <?php 
                $trang_thai_hien_tai = $_GET['trang_thai'] ?? 'moi';
                foreach ($cac_trang_thai as $key_status => $ten_status): ?>
                    <a href="?action=danh_sach&trang_thai=<?php echo $key_status; ?>" 
                       class="<?php echo ($trang_thai_hien_tai == $key_status) ? 'active' : ''; ?>"
                       data-status="<?php echo $key_status; ?>">
                       <i class="<?php echo $icon_map[$key_status]; ?>"></i>
                       <?php echo $ten_status; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Mã ĐH</th>
                    <th>Ngày Đặt</th>
                    <th>Tổng Tiền</th>
                    <th>Trạng Thái</th>
                    <th>Hành Động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($danh_sach_don_hang)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center;">Bạn không có đơn hàng nào ở trạng thái này.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($danh_sach_don_hang as $don): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($don['ma_don_hang']); ?></strong></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($don['ngay_dat'])); ?></td>
                            <td><?php echo number_format($don['tong_tien'], 0, ',', '.'); ?>đ</td>
                            <td>
                                <span class="status-label status-<?php echo $don['trang_thai_don_hang']; ?>">
                                    <?php echo $cac_trang_thai[$don['trang_thai_don_hang']]; ?>
                                </span>
                            </td>
                            <td class="action-link">
                                <a href="?action=xem&id=<?php echo $don['id_don_hang']; ?>">
                                    <i class="fas fa-eye"></i> Xem chi tiết
                                </a>
                                <?php if (in_array($don['trang_thai_don_hang'], ['da_huy', 'da_hoan_tra', 'hoan_thanh'])): ?>
                                    <br>
                                    <a href="xu_ly_gio_hang.php?action=rebuy&id_don_hang=<?php echo $don['id_don_hang']; ?>" 
                                       class="btn-rebuy" style="margin-top: 5px;"
                                       onclick="return confirm('Thêm tất cả sản phẩm của đơn hàng này vào lại giỏ hàng?');"
                                       data-turbolinks="false">
                                        <i class="fas fa-redo"></i> Mua lại
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    
    <?php elseif ($action == 'xem' && $don_hang_chi_tiet): ?>
        <?php $status = $don_hang_chi_tiet['trang_thai_don_hang']; ?>
        <div class="page-header">
            <h1>Chi tiết Đơn hàng: <?php echo htmlspecialchars($don_hang_chi_tiet['ma_don_hang']); ?></h1>
            <a href="?action=danh_sach&trang_thai=<?php echo $status; ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Quay lại danh sách
            </a>
        </div>
        
        <div class="order-details-grid">
            
            <div>
                <form action="?action=xem&id=<?php echo $id_don_hang_xem; ?>" method="POST" class="info-box" data-turbolinks="false">
                    <input type="hidden" name="action" value="cap_nhat_thong_tin">
                    <input type="hidden" name="id_don_hang" value="<?php echo $id_don_hang_xem; ?>">
                    
                    <h3>Thông tin Người nhận</h3>
                    
                    <?php
                        $can_edit = ($status == 'moi' || $status == 'dang_xu_ly');
                    ?>
                    
                    <div class="form-group">
                        <label for="ten_nguoi_nhan">Tên người nhận:</label>
                        <input type="text" id="ten_nguoi_nhan" name="ten_nguoi_nhan" 
                               value="<?php echo htmlspecialchars($don_hang_chi_tiet['ten_nguoi_nhan']); ?>" 
                               <?php echo $can_edit ? '' : 'disabled'; ?>>
                    </div>
                    <div class="form-group">
                        <label for="so_dien_thoai_nhan">Số điện thoại:</label>
                        <input type="text" id="so_dien_thoai_nhan" name="so_dien_thoai_nhan" 
                               value="<?php echo htmlspecialchars($don_hang_chi_tiet['so_dien_thoai_nhan']); ?>" 
                               <?php echo $can_edit ? '' : 'disabled'; ?>>
                    </div>
                    <div class="form-group">
                        <label for="dia_chi_giao_hang">Địa chỉ giao hàng:</label>
                        <textarea id="dia_chi_giao_hang" name="dia_chi_giao_hang" 
                                  <?php echo $can_edit ? '' : 'disabled'; ?>><?php echo htmlspecialchars($don_hang_chi_tiet['dia_chi_giao_hang']); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label for="ghi_chu">Ghi chú:</label>
                        <textarea id="ghi_chu" name="ghi_chu" 
                                  <?php echo $can_edit ? '' : 'disabled'; ?>><?php echo htmlspecialchars($don_hang_chi_tiet['ghi_chu']); ?></textarea>
                    </div>
                    
                    <?php if ($can_edit): ?>
                        <button type="submit" class="btn btn-update"><i class="fas fa-save"></i> Cập nhật thông tin</button>
                    <?php endif; ?>
                </form>
                
                <div class="info-box" style="margin-top: 20px;">
                    <h3>Chi tiết Sản phẩm</h3>
                    <table style="box-shadow: none;">
                        <thead>
                            <tr>
                                <th colspan="2">Sản Phẩm</th>
                                <th>SL</th>
                                <th>Thành Tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $tong_tien_hang_goc = 0; ?>
                            <?php foreach ($items_in_order as $item): ?>
                                <tr>
                                    <td style="width: 70px;">
                                        <?php 
                                        $anh_path = 'tai_len/san_pham/' . ($item['anh_dai_dien'] ?? 'default.png');
                                        if (empty($item['anh_dai_dien']) || !file_exists($anh_path)) {
                                            $anh_path = 'tai_len/san_pham/default.png'; 
                                        }
                                        ?>
                                        <img src="<?php echo $anh_path; ?>" alt="" class="order-item-image">
                                    </td>
                                    <td>
                                        <?php echo htmlspecialchars($item['ten_san_pham_luc_mua']); ?><br>
                                        <small style="color: #555;">(Màu: <?php echo htmlspecialchars($item['mau_sac_luc_mua']); ?>)</small>
                                    </td>
                                    <td><?php echo $item['so_luong']; ?></td>
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
            </div>
            
            <div class="info-box">
                <h3>Trạng thái & Hành động</h3>
                <p><strong>Trạng thái hiện tại:</strong> 
                    <span class="status-label status-<?php echo $status; ?>">
                        <?php echo $cac_trang_thai[$status]; ?>
                    </span>
                </p>
                <hr style="margin: 20px 0;">
                
                <?php if ($status == 'moi' || $status == 'dang_xu_ly'): ?>
                    <p>Bạn có thể hủy đơn hàng ngay lập tức (hàng sẽ được hoàn lại kho).</p>
                    <form action="?action=xem&id=<?php echo $id_don_hang_xem; ?>" method="POST" 
                          onsubmit="return confirm('Bạn có chắc chắn muốn HỦY đơn hàng này?');" data-turbolinks="false">
                        <input type="hidden" name="action" value="huy_truc_tiep">
                        <input type="hidden" name="id_don_hang" value="<?php echo $id_don_hang_xem; ?>">
                        <button type="submit" class="btn-action btn-cancel"><i class="fas fa-times"></i> Hủy Đơn Hàng</button>
                    </form>
                
                <?php elseif ($status == 'dang_giao'): ?>
                    <p>Đơn hàng đang được giao. Nếu bạn hủy, đơn hàng sẽ chuyển sang trạng thái "Chờ duyệt hủy".</p>
                    <form action="?action=xem&id=<?php echo $id_don_hang_xem; ?>" method="POST" 
                          onsubmit="return confirm('Bạn có chắc chắn muốn gửi YÊU CẦU HỦY đơn hàng này?');" data-turbolinks="false">
                        <input type="hidden" name="action" value="yeu_cau_huy">
                        <input type="hidden" name="id_don_hang" value="<?php echo $id_don_hang_xem; ?>">
                        <button type="submit" class="btn-action btn-request-cancel"><i class="fas fa-exclamation-triangle"></i> Yêu Cầu Hủy</S>
                    </form>
                    
                <?php elseif ($status == 'hoan_thanh'): ?>
                    <p>Bạn có thể yêu cầu trả hàng/hoàn tiền nếu sản phẩm có vấn đề.</p>
                    <form action="?action=xem&id=<?php echo $id_don_hang_xem; ?>" method="POST"
                          onsubmit="return confirm('Bạn có chắc chắn muốn gửi yêu cầu TRẢ HÀNG/HOÀN TIỀN cho đơn hàng này?');" data-turbolinks="false">
                        <input type="hidden" name="action" value="yeu_cau_tra_hang">
                        <input type="hidden" name="id_don_hang" value="<?php echo $id_don_hang_xem; ?>">
                        <button type="submit" class="btn-action btn-return"><i class="fas fa-undo"></i> Yêu Cầu Trả Hàng</button>
                    </form>

                <?php elseif ($status == 'yeu_cau_huy' || $status == 'yeu_cau_tra_hang'): ?>
                    <div class="status-pending-text">
                        Yêu cầu của bạn đang chờ Admin duyệt.
                    </div>
                
                <?php else: ?>
                    <p>Đơn hàng này đã kết thúc.</p>
                <?php endif; ?>

            </div>
        </div>
        
    <?php endif; ?>
    
</main>

<?php
require 'dung_chung/cuoi_trang.php';
?>