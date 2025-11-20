<?php
// 1. KHỞI TẠO BIẾN
$page_title = "Quản lý Đánh Giá"; 
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
$thong_bao = $_SESSION['thong_bao'] ?? "";
$thong_bao_loi = $_SESSION['thong_bao_loi'] ?? "";
unset($_SESSION['thong_bao'], $_SESSION['thong_bao_loi']); 

$current_tab = $_GET['tab'] ?? 'cho_duyet';
$action = $_GET['action'] ?? 'danh_sach';
$id_danh_gia = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

// 2. GỌI CSDL VÀ KIỂM TRA ADMIN
require '../dung_chung/ket_noi_csdl.php'; 
require 'kiem_tra_quan_tri.php'; 

// (MỚI) 3. HÀM QUAN TRỌNG: TÍNH LẠI SAO TRUNG BÌNH
function cap_nhat_diem_trung_binh($conn, $id_san_pham) {
    if (empty($id_san_pham)) return;
    
    // 1. Tính toán
    $sql_calc = "SELECT 
                    AVG(so_sao) as avg_rating, 
                    COUNT(id_danh_gia) as total_reviews 
                 FROM danh_gia_san_pham 
                 WHERE id_san_pham = ? AND trang_thai = 'da_duyet'";
    $stmt_calc = $conn->prepare($sql_calc);
    $stmt_calc->bind_param("i", $id_san_pham);
    $stmt_calc->execute();
    $result_calc = $stmt_calc->get_result()->fetch_assoc();
    
    $avg = $result_calc['avg_rating'] ?? 0;
    $total = $result_calc['total_reviews'] ?? 0;
    
    // 2. Cập nhật lại bảng san_pham
    $sql_update = "UPDATE san_pham SET avg_rating = ?, total_reviews = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dii", $avg, $total, $id_san_pham);
    $stmt_update->execute();
}

// 4. XỬ LÝ LOGIC (POST TRẢ LỜI, GET DUYỆT/ẨN/XÓA)
// --- 4.1: XỬ LÝ POST (GỬI TRẢ LỜI) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tra_loi') {
    $id_tra_loi = (int)($_POST['id_danh_gia'] ?? 0);
    $noi_dung_tra_loi = $conn->real_escape_string($_POST['noi_dung_tra_loi']);
    $id_admin = $_SESSION['id_nguoi_dung']; 

    if ($id_tra_loi > 0 && !empty($noi_dung_tra_loi)) {
        $stmt = $conn->prepare("UPDATE danh_gia_san_pham SET 
                                    noi_dung_tra_loi = ?, id_admin_tra_loi = ?, ngay_tra_loi = NOW() 
                                WHERE id_danh_gia = ?");
        $stmt->bind_param("sii", $noi_dung_tra_loi, $id_admin, $id_tra_loi);
        $stmt->execute();
        $_SESSION['thong_bao'] = "Gửi trả lời thành công!";
    }
    header("Location: quan_ly_danh_gia.php?action=tra_loi&id=" . $id_tra_loi . "&tab=" . $current_tab);
    exit();
}

// --- 4.2: XỬ LÝ GET (CẬP NHẬT TRẠNG THÁI / XÓA) ---
if (($action == 'update_status' || $action == 'xoa') && $id_danh_gia > 0) {
    
    // Lấy id_san_pham TRƯỚC khi xóa/ẩn
    $stmt_get_sp = $conn->prepare("SELECT id_san_pham FROM danh_gia_san_pham WHERE id_danh_gia = ?");
    $stmt_get_sp->bind_param("i", $id_danh_gia);
    $stmt_get_sp->execute();
    $id_sp_affected = $stmt_get_sp->get_result()->fetch_assoc()['id_san_pham'];
    
    if ($action == 'update_status' && in_array($status, ['da_duyet', 'bi_an'])) {
        $stmt = $conn->prepare("UPDATE danh_gia_san_pham SET trang_thai = ? WHERE id_danh_gia = ?");
        $stmt->bind_param("si", $status, $id_danh_gia);
        $stmt->execute();
        $_SESSION['thong_bao'] = "Cập nhật trạng thái thành công!";
    } elseif ($action == 'xoa') {
        $stmt = $conn->prepare("DELETE FROM danh_gia_san_pham WHERE id_danh_gia = ?");
        $stmt->bind_param("i", $id_danh_gia);
        $stmt->execute();
        $_SESSION['thong_bao'] = "Đã xóa đánh giá!";
    }
    
    // (MỚI) Tự động tính lại điểm sao
    cap_nhat_diem_trung_binh($conn, $id_sp_affected);
    
    header("Location: quan_ly_danh_gia.php?tab=" . $current_tab);
    exit();
}

// 5. GỌI ĐẦU TRANG ADMIN
require 'dau_trang_quan_tri.php'; 

// 6. LOGIC LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$data_list = [];
$detail_data = null;

$cac_trang_thai = [
    'cho_duyet' => 'Chờ duyệt',
    'da_duyet' => 'Đã duyệt',
    'bi_an' => 'Bị ẩn'
];
$icon_map = [
    'cho_duyet' => 'fas fa-clock',
    'da_duyet' => 'fas fa-check-circle',
    'bi_an' => 'fas fa-eye-slash'
];

if ($action == 'danh_sach') {
    $sql = "SELECT d.*, nd.ten as ten_nguoi_dung, sp.ten_san_pham 
            FROM danh_gia_san_pham d
            JOIN nguoi_dung nd ON d.id_nguoi_dung = nd.id_nguoi_dung
            JOIN san_pham sp ON d.id_san_pham = sp.id
            WHERE d.trang_thai = ?
            ORDER BY d.ngay_danh_gia DESC";
    $stmt_list = $conn->prepare($sql);
    $stmt_list->bind_param("s", $current_tab);
    $stmt_list->execute();
    $result = $stmt_list->get_result();
    if ($result) { while ($row = $result->fetch_assoc()) $data_list[] = $row; }
    
} elseif (($action == 'tra_loi') && $id_danh_gia > 0) {
    $sql = "SELECT d.*, nd.ten as ten_nguoi_dung, nd.anh_dai_dien, sp.ten_san_pham 
            FROM danh_gia_san_pham d
            JOIN nguoi_dung nd ON d.id_nguoi_dung = nd.id_nguoi_dung
            JOIN san_pham sp ON d.id_san_pham = sp.id
            WHERE d.id_danh_gia = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_danh_gia); $stmt->execute();
    $detail_data = $stmt->get_result()->fetch_assoc();
}
?>

<style>
    /* CSS cho 5 sao */
    .star-rating {
        color: #f39c12;
        font-size: 1.2em;
    }
    .star-rating .fas { /* Sao đầy */ }
    .star-rating .far { /* Sao rỗng */ }
    
    /* CSS Cho Trang Trả Lời */
    .reply-layout {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
    }
    .review-card, .reply-form-card {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .review-card h3 { margin-top: 0; }
    .review-header {
        display: flex;
        gap: 15px;
        align-items: center;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 15px;
    }
    .review-avatar img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
    }
    .review-author { font-weight: bold; font-size: 1.1em; }
    .review-meta { font-size: 0.9em; color: #777; }
    .review-content { line-height: 1.6; }
    
    .admin-reply-box {
        background: #f0f7ff;
        border: 1px solid #b3d7ff;
        padding: 15px;
        margin-top: 20px;
        border-radius: 5px;
    }
    .admin-reply-box h4 { margin-top: 0; color: #0056b3; }
</style>


<h1>Quản lý Đánh Giá</h1>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<nav class="tab-menu">
    <?php foreach ($cac_trang_thai as $key_status => $ten_status): ?>
        <a href="?tab=<?php echo $key_status; ?>" 
           class="<?php echo ($current_tab == $key_status) ? 'active' : ''; ?>">
           <i class="<?php echo $icon_map[$key_status]; ?>"></i>
           <?php echo $ten_status; ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($action == 'danh_sach'): ?>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Sản phẩm</th>
                <th>Người đánh giá</th>
                <th>Xếp hạng</th>
                <th>Nội dung</th>
                <th>Ngày</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data_list)): ?>
                <tr><td colspan="6" style="text-align: center;">Không có đánh giá nào trong mục này.</td></tr>
            <?php else: ?>
                <?php foreach ($data_list as $item): ?>
                <tr>
                    <td><?php echo htmlspecialchars($item['ten_san_pham']); ?></td>
                    <td><?php echo htmlspecialchars($item['ten_nguoi_dung']); ?></td>
                    <td class="star-rating">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="<?php echo ($i <= $item['so_sao']) ? 'fas' : 'far'; ?> fa-star"></i>
                        <?php endfor; ?>
                    </td>
                    <td style="max-width: 300px;"><?php echo nl2br(htmlspecialchars($item['noi_dung'])); ?></td>
                    <td><?php echo date('d-m-Y', strtotime($item['ngay_danh_gia'])); ?></td>
                    <td class="action-links">
                        <a href="?action=tra_loi&id=<?php echo $item['id_danh_gia']; ?>&tab=<?php echo $current_tab; ?>" class="edit">
                            <i class="fas fa-reply"></i> <?php echo empty($item['noi_dung_tra_loi']) ? 'Trả lời' : 'Sửa trả lời'; ?>
                        </a>
                        
                        <?php if ($current_tab == 'cho_duyet'): ?>
                            <a href="?action=update_status&status=da_duyet&id=<?php echo $item['id_danh_gia']; ?>&tab=<?php echo $current_tab; ?>" class="edit" style="color: green;">Duyệt</a>
                            <a href="?action=update_status&status=bi_an&id=<?php echo $item['id_danh_gia']; ?>&tab=<?php echo $current_tab; ?>" class="delete">Ẩn</a>
                        <?php elseif ($current_tab == 'bi_an'): ?>
                             <a href="?action=update_status&status=da_duyet&id=<?php echo $item['id_danh_gia']; ?>&tab=<?php echo $current_tab; ?>" class="edit" style="color: green;">Duyệt lại</a>
                        <?php elseif ($current_tab == 'da_duyet'): ?>
                             <a href="?action=update_status&status=bi_an&id=<?php echo $item['id_danh_gia']; ?>&tab=<?php echo $current_tab; ?>" class="delete">Ẩn</a>
                        <?php endif; ?>
                        
                        <a href="?action=xoa&id=<?php echo $item['id_danh_gia']; ?>&tab=<?php echo $current_tab; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa vĩnh viễn đánh giá này?');">
                            <i class="fas fa-trash-alt"></i> Xóa
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action == 'tra_loi' && $detail_data): ?>

    <div class="page-header">
        <h2>Trả lời Đánh giá</h2>
        <a href="?tab=<?php echo $current_tab; ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay Lại Danh Sách
        </a>
    </div>
    
    <div class="reply-layout">
        <div class="review-card">
            <h3>Đánh giá của khách hàng</h3>
            <div class="review-header">
                <div class="review-avatar">
                    <?php 
                    $anh_path_avatar = BASE_URL . 'tai_len/avatars/' . ($detail_data['anh_dai_dien'] ?? 'default-avatar.png');
                    $anh_path_check = ROOT_PATH . 'tai_len/avatars/' . ($detail_data['anh_dai_dien'] ?? 'default-avatar.png');
                    if (empty($detail_data['anh_dai_dien']) || !file_exists($anh_path_check)) {
                        $anh_path_avatar = BASE_URL . 'tai_len/avatars/default-avatar.png'; 
                    }
                    ?>
                    <img src="<?php echo $anh_path_avatar; ?>" alt="Avatar">
                </div>
                <div>
                    <div class="review-author"><?php echo htmlspecialchars($detail_data['ten_nguoi_dung']); ?></div>
                    <div class="review-meta">
                        <?php echo date('d-m-Y H:i', strtotime($detail_data['ngay_danh_gia'])); ?>
                    </div>
                </div>
            </div>
            
            <div class="star-rating">
                <strong>Xếp hạng: </strong>
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="<?php echo ($i <= $detail_data['so_sao']) ? 'fas' : 'far'; ?> fa-star"></i>
                <?php endfor; ?>
                (<?php echo $detail_data['so_sao']; ?>/5)
            </div>
            
            <p class="review-content">
                <?php echo nl2br(htmlspecialchars($detail_data['noi_dung'])); ?>
            </p>
            
            <?php if (!empty($detail_data['noi_dung_tra_loi'])): ?>
                <div class="admin-reply-box">
                    <h4><i class="fas fa-check-circle"></i> Bạn đã trả lời:</h4>
                    <p><?php echo nl2br(htmlspecialchars($detail_data['noi_dung_tra_loi'])); ?></p>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="reply-form-card">
            <h3>Gửi trả lời</h3>
            <form action="quan_ly_danh_gia.php?action=tra_loi&id=<?php echo $id_danh_gia; ?>&tab=<?php echo $current_tab; ?>" method="POST">
                <input type="hidden" name="action" value="tra_loi">
                <input type="hidden" name="id_danh_gia" value="<?php echo $id_danh_gia; ?>">
                <input type="hidden" name="tab" value="<?php echo $current_tab; ?>">
                
                <div class="form-group">
                    <label for="noi_dung_tra_loi">Nội dung trả lời (*)</label>
                    <textarea id="noi_dung_tra_loi" name="noi_dung_tra_loi" rows="8" required><?php echo htmlspecialchars($detail_data['noi_dung_tra_loi'] ?? ''); ?></textarea>
                </div>
                <button type="submit" class="btn btn-success" style="width: 100%;">
                    <i class="fas fa-paper-plane"></i> Gửi Trả Lời
                </button>
            </form>
        </div>
    </div>
    
<?php endif; ?>


<?php require 'cuoi_trang_quan_tri.php'; ?>