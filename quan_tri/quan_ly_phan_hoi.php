<?php
// 1. KHỞI TẠO BIẾN TRƯỚC VÀ LẤY THAM SỐ
$page_title = "Quản lý Phản Hồi"; 

// (MỚI) BẮT ĐẦU SESSION TRƯỚC
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// (MỚI) ĐỌC THÔNG BÁO TỪ REDIRECT (NẾU CÓ)
$thong_bao = $_SESSION['thong_bao'] ?? "";
$thong_bao_loi = $_SESSION['thong_bao_loi'] ?? "";
unset($_SESSION['thong_bao'], $_SESSION['thong_bao_loi']); // Xóa sau khi đọc

$current_tab = $_GET['tab'] ?? 'lien_he';
$action = $_GET['action'] ?? 'danh_sach';
$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

// 2. GỌI CSDL VÀ KIỂM TRA ADMIN (BẮT BUỘC TRƯỚC KHI XỬ LÝ)
require '../dung_chung/ket_noi_csdl.php'; 
require 'kiem_tra_quan_tri.php'; // Chạy kiểm tra bảo mật trước

// 3. XỬ LÝ LOGIC (CẬP NHẬT/XÓA) - PHẢI CHẠY TRƯỚC KHI GỌI HEADER
if (($action == 'update_status' || $action == 'xoa') && $id > 0) {
    $tab_redirect = $current_tab; 

    if ($current_tab == 'lien_he') {
        // --- Xử lý cho Liên Hệ ---
        if ($action == 'update_status' && in_array($status, ['da_doc', 'da_tra_loi'])) {
            $stmt = $conn->prepare("UPDATE lien_he SET trang_thai = ? WHERE id_lien_he = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $_SESSION['thong_bao'] = "Cập nhật trạng thái thành công!";
        } elseif ($action == 'xoa') {
            $stmt = $conn->prepare("DELETE FROM lien_he WHERE id_lien_he = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['thong_bao'] = "Đã xóa liên hệ!";
        }
    } else {
        // --- Xử lý cho Phản Ánh ---
        if ($action == 'update_status' && in_array($status, ['dang_xu_ly', 'da_giai_quyet'])) {
            $stmt = $conn->prepare("UPDATE phan_anh SET trang_thai = ? WHERE id_phan_anh = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
            $_SESSION['thong_bao'] = "Cập nhật trạng thái thành công!";
        } elseif ($action == 'xoa') {
            // Lấy ảnh để xóa file
            $stmt_get_img = $conn->prepare("SELECT anh_1, anh_2, anh_3 FROM phan_anh WHERE id_phan_anh = ?");
            $stmt_get_img->bind_param("i", $id);
            $stmt_get_img->execute();
            $images = $stmt_get_img->get_result()->fetch_assoc();
            if ($images) {
                foreach ($images as $img) {
                    if ($img && file_exists('../tai_len/phan_anh/' . $img)) {
                        @unlink('../tai_len/phan_anh/' . $img);
                    }
                }
            }
            // Xóa CSDL
            $stmt = $conn->prepare("DELETE FROM phan_anh WHERE id_phan_anh = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $_SESSION['thong_bao'] = "Đã xóa phản ánh!";
        }
    }
    
    // Chuyển hướng về đúng tab đó để làm mới
    header("Location: quan_ly_phan_hoi.php?tab=" . $tab_redirect);
    exit();
}

// 4. GỌI ĐẦU TRANG ADMIN
// (Tất cả logic redirect đã chạy xong)
require 'dau_trang_quan_tri.php'; 

// 5. LOGIC LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$data_list = [];
$detail_data = null;
$order_items = []; // Dành cho chi tiết đơn hàng

if ($action == 'danh_sach') {
    // --- Lấy dữ liệu cho Bảng Danh Sách ---
    if ($current_tab == 'lien_he') {
        $sql = "SELECT * FROM lien_he ORDER BY 
                CASE trang_thai WHEN 'moi' THEN 1 ELSE 2 END, ngay_gui DESC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data_list[] = $row;
            }
        }
    } else {
        $sql = "SELECT p.*, nd.email 
                FROM phan_anh p
                JOIN nguoi_dung nd ON p.id_nguoi_dung = nd.id_nguoi_dung
                ORDER BY 
                CASE p.trang_thai WHEN 'moi' THEN 1 WHEN 'dang_xu_ly' THEN 2 ELSE 3 END, p.ngay_gui DESC";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $data_list[] = $row;
            }
        }
    }
} elseif ($action == 'xem' && $id > 0) {
    // --- Lấy dữ liệu cho Trang Chi Tiết ---
    if ($current_tab == 'lien_he') {
        $stmt = $conn->prepare("SELECT * FROM lien_he WHERE id_lien_he = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $detail_data = $stmt->get_result()->fetch_assoc();
    } else {
        // Lấy chi tiết Phản Ánh
        $stmt = $conn->prepare("SELECT p.*, nd.email, nd.ten 
                                FROM phan_anh p
                                JOIN nguoi_dung nd ON p.id_nguoi_dung = nd.id_nguoi_dung
                                WHERE p.id_phan_anh = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $detail_data = $stmt->get_result()->fetch_assoc();

        // Nếu có id_don_hang, lấy chi tiết đơn hàng đó
        if ($detail_data && !empty($detail_data['id_don_hang'])) {
            $stmt_order = $conn->prepare("SELECT * FROM chi_tiet_don_hang WHERE id_don_hang = ?");
            $stmt_order->bind_param("i", $detail_data['id_don_hang']);
            $stmt_order->execute();
            $result_items = $stmt_order->get_result();
            while($item = $result_items->fetch_assoc()) {
                $order_items[] = $item;
            }
        }
    }
}
?>

<style>
    /* CSS cho Ảnh đính kèm (Bảng) */
    .attached-images-preview img {
        width: 50px;
        height: 50px;
        object-fit: cover;
        border-radius: 4px;
        margin-right: 5px;
        border: 1px solid #ccc;
        cursor: pointer;
        transition: transform 0.2s;
    }
    .attached-images-preview img:hover {
        transform: scale(1.1);
    }
    
    /* CSS Cho Trang Chi Tiết */
    .detail-container {
        display: grid;
        grid-template-columns: 2fr 1fr; 
        gap: 25px;
    }
    .detail-box {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .detail-box h3 {
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        margin-bottom: 15px;
    }
    .detail-info p {
        margin: 0 0 12px 0;
        font-size: 1em;
    }
    .detail-info p strong {
        color: #333;
        display: inline-block;
        min-width: 120px;
    }
    .detail-info p i {
        margin-right: 8px;
        color: #007bff;
        width: 1.2em;
        text-align: center;
    }
    .detail-content {
        font-size: 1.1em;
        line-height: 1.6;
        white-space: pre-wrap; 
        padding: 15px;
        background: #fdfdfd;
        border: 1px solid #f0f0f0;
        border-radius: 5px;
    }
    
    /* CSS Cho ảnh chi tiết */
    .detail-images {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 15px;
    }
    .detail-images img {
        width: 100%;
        height: 120px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #ccc;
        cursor: pointer;
    }
    
    /* CSS Bảng chi tiết đơn hàng */
    .order-items-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .order-items-table th, .order-items-table td {
        border: 1px solid #ddd;
        padding: 8px;
        font-size: 0.9em;
    }
    .order-items-table th { background-color: #f9f9f9; }

    /* CSS Modal xem ảnh */
    .modal {
        display: none; 
        position: fixed; 
        z-index: 1001; 
        padding-top: 50px; 
        left: 0; top: 0;
        width: 100%; height: 100%; 
        overflow: auto; 
        background-color: rgba(0,0,0,0.9);
    }
    .modal-content {
        margin: auto;
        display: block;
        width: 80%;
        max-width: 700px;
    }
    .close {
        position: absolute;
        top: 15px;
        right: 35px;
        color: #f1f1f1;
        font-size: 40px;
        font-weight: bold;
        transition: 0.3s;
        cursor: pointer;
    }
</style>


<h1>Quản lý Phản Hồi</h1>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<nav class="tab-menu">
    <a href="?tab=lien_he" class="<?php echo ($current_tab == 'lien_he') ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i> Quản lý Liên hệ
    </a>
    <a href="?tab=phan_anh" class="<?php echo ($current_tab == 'phan_anh') ? 'active' : ''; ?>">
        <i class="fas fa-flag"></i> Quản lý Phản ánh
    </a>
</nav>

<?php if ($action == 'danh_sach'): ?>
    <div class="content-body">
        <?php if ($current_tab == 'lien_he'): ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Người gửi</th>
                        <th>Email / SĐT</th>
                        <th>Tiêu đề</th>
                        <th>Nội dung</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="7" style="text-align: center;">Không có tin nhắn liên hệ nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['ten_nguoi_gui']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($item['email']); ?><br>
                                <small><?php echo htmlspecialchars($item['so_dien_thoai']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($item['tieu_de']); ?></td>
                            <td style="max-width: 250px;"><?php echo nl2br(htmlspecialchars(mb_substr($item['noi_dung'], 0, 100) . '...')); ?></td>
                            <td><?php echo date('d-m-Y H:i', strtotime($item['ngay_gui'])); ?></td>
                            <td>
                                <span class="status-label status-<?php echo $item['trang_thai']; ?>">
                                    <?php echo str_replace('_', ' ', $item['trang_thai']); ?>
                                </span>
                            </td>
                            <td class="action-links">
                                <a href="?tab=lien_he&action=xem&id=<?php echo $item['id_lien_he']; ?>" class="edit">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                                <?php if ($item['trang_thai'] == 'moi'): ?>
                                    <a href="?tab=lien_he&action=update_status&status=da_doc&id=<?php echo $item['id_lien_he']; ?>">Đã đọc</a>
                                <?php endif; ?>
                                <a href="?tab=lien_he&action=xoa&id=<?php echo $item['id_lien_he']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa?');">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        
        <?php else: ?>
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Người gửi (Email)</th>
                        <th>Đơn hàng</th>
                        <th>Chủ đề</th>
                        <th>Ảnh đính kèm</th>
                        <th>Ngày gửi</th>
                        <th>Trạng thái</th>
                        <th>Hành động</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data_list)): ?>
                        <tr><td colspan="7" style="text-align: center;">Không có phản ánh nào.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data_list as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['email']); ?></td>
                            <td><?php echo htmlspecialchars($item['id_don_hang'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($item['chu_de']); ?></td>
                            <td class="attached-images-preview">
                                <?php
                                $anh_path_html = BASE_URL . 'tai_len/phan_anh/';
                                $anh_path_php = ROOT_PATH . 'tai_len/phan_anh/';
                                if ($item['anh_1'] && file_exists($anh_path_php . $item['anh_1'])) {
                                    echo '<img src="' . $anh_path_html . $item['anh_1'] . '" onclick="openModal(this)">';
                                }
                                if ($item['anh_2'] && file_exists($anh_path_php . $item['anh_2'])) {
                                    echo '<img src="' . $anh_path_html . $item['anh_2'] . '" onclick="openModal(this)">';
                                }
                                if ($item['anh_3'] && file_exists($anh_path_php . $item['anh_3'])) {
                                    echo '<img src="' . $anh_path_html . $item['anh_3'] . '" onclick="openModal(this)">';
                                }
                                ?>
                            </td>
                            <td><?php echo date('d-m-Y H:i', strtotime($item['ngay_gui'])); ?></td>
                            <td>
                                <span class="status-label status-<?php echo str_replace('_', '-', $item['trang_thai']); ?>">
                                    <?php echo str_replace('_', ' ', $item['trang_thai']); ?>
                                </span>
                            </td>
                            <td class="action-links">
                                <a href="?tab=phan_anh&action=xem&id=<?php echo $item['id_phan_anh']; ?>" class="edit">
                                    <i class="fas fa-eye"></i> Xem
                                </a>
                                <?php if ($item['trang_thai'] == 'moi'): ?>
                                    <a href="?tab=phan_anh&action=update_status&status=dang_xu_ly&id=<?php echo $item['id_phan_anh']; ?>">Đang xử lý</a>
                                <?php endif; ?>
                                <a href="?tab=phan_anh&action=xoa&id=<?php echo $item['id_phan_anh']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa?');">
                                    <i class="fas fa-trash-alt"></i> Xóa
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

<?php elseif ($action == 'xem' && $detail_data): ?>
    
    <div class="page-header">
        <h2>Chi tiết Phản hồi</h2>
        <a href="?tab=<?php echo $current_tab; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay Lại Danh Sách
        </a>
    </div>

    <div class="detail-container">
        
        <div class="detail-box">
            <?php if ($current_tab == 'lien_he'): ?>
                <h3><i class="fas fa-envelope-open-text"></i> Nội dung Liên hệ</h3>
                <div class="detail-content">
                    <?php echo nl2br(htmlspecialchars($detail_data['noi_dung'])); ?>
                </div>
            <?php else: ?>
                <h3><i class="fas fa-flag"></i> Nội dung Phản ánh</h3>
                <div class="detail-content">
                    <?php echo nl2br(htmlspecialchars($detail_data['noi_dung'])); ?>
                </div>
                
                <h3 style="margin-top: 20px;">Ảnh đính kèm</h3>
                <div class="detail-images">
                    <?php
                    $anh_path_html = BASE_URL . 'tai_len/phan_anh/';
                    $anh_path_php = ROOT_PATH . 'tai_len/phan_anh/';
                    $has_images = false;
                    if ($detail_data['anh_1'] && file_exists($anh_path_php . $detail_data['anh_1'])) {
                        echo '<img src="' . $anh_path_html . $detail_data['anh_1'] . '" onclick="openModal(this)">'; $has_images = true;
                    }
                    if ($detail_data['anh_2'] && file_exists($anh_path_php . $detail_data['anh_2'])) {
                        echo '<img src="' . $anh_path_html . $detail_data['anh_2'] . '" onclick="openModal(this)">'; $has_images = true;
                    }
                    if ($detail_data['anh_3'] && file_exists($anh_path_php . $detail_data['anh_3'])) {
                        echo '<img src="' . $anh_path_html . $detail_data['anh_3'] . '" onclick="openModal(this)">'; $has_images = true;
                    }
                    if (!$has_images) {
                        echo "<p>Không có ảnh đính kèm.</p>";
                    }
                    ?>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="detail-box">
            <h3><i class="fas fa-info-circle"></i> Thông tin</h3>
            <?php if ($current_tab == 'lien_he'): ?>
                <div class="detail-info">
                    <p><strong><i class="fas fa-user"></i>Người gửi:</strong> <?php echo htmlspecialchars($detail_data['ten_nguoi_gui']); ?></p>
                    <p><strong><i class="fas fa-envelope"></i>Email:</strong> <?php echo htmlspecialchars($detail_data['email']); ?></p>
                    <p><strong><i class="fas fa-phone"></i>SĐT:</strong> <?php echo htmlspecialchars($detail_data['so_dien_thoai']); ?></p>
                    <p><strong><i class="fas fa-calendar-alt"></i>Ngày gửi:</strong> <?php echo date('d-m-Y H:i', strtotime($detail_data['ngay_gui'])); ?></p>
                    <p><strong><i class="fas fa-tag"></i>Tiêu đề:</strong> <?php echo htmlspecialchars($detail_data['tieu_de']); ?></p>
                </div>
            <?php else: ?>
                <div class="detail-info">
                    <p><strong><i class="fas fa-user"></i>Người gửi:</strong> <?php echo htmlspecialchars($detail_data['ten']); ?></p>
                    <p><strong><i class="fas fa-envelope"></i>Email:</strong> <?php echo htmlspecialchars($detail_data['email']); ?></p>
                    <p><strong><i class="fas fa-calendar-alt"></i>Ngày gửi:</strong> <?php echo date('d-m-Y H:i', strtotime($detail_data['ngay_gui'])); ?></p>
                    <p><strong><i class="fas fa-tag"></i>Chủ đề:</strong> <?php echo htmlspecialchars($detail_data['chu_de']); ?></p>
                    <p><strong><i class="fas fa-receipt"></i>Đơn hàng:</strong> <?php echo htmlspecialchars($detail_data['id_don_hang'] ?? 'N/A'); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($order_items)): ?>
                <h3 style="margin-top: 20px;"><i class="fas fa-receipt"></i> Chi tiết Đơn hàng liên quan</h3>
                <table class="order-items-table">
                    <thead>
                        <tr>
                            <th>Sản phẩm</th>
                            <th>SL</th>
                            <th>Giá</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $tong_tien_don = 0; ?>
                        <?php foreach ($order_items as $item): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['ten_san_pham_luc_mua']); ?></td>
                                <td><?php echo $item['so_luong']; ?></td>
                                <td><?php echo number_format($item['gia_luc_mua'], 0, ',', '.'); ?>đ</td>
                            </tr>
                            <?php $tong_tien_don += $item['gia_luc_mua'] * $item['so_luong']; ?>
                        <?php endforeach; ?>
                        <tr style="font-weight: bold;">
                            <td colspan="2">Tổng cộng</td>
                            <td><?php echo number_format($tong_tien_don, 0, ',', '.'); ?>đ</td>
                        </tr>
                    </tbody>
                </table>
            <?php endif; ?>
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