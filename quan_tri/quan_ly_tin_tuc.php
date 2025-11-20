<?php
// 1. KHỞI TẠO BIẾN TRƯỚC VÀ LẤY THAM SỐ
$page_title = "Quản lý Tin Tức"; 
$thong_bao = ""; 
$thong_bao_loi = ""; 
$action = $_GET['action'] ?? 'danh_sach'; 
$edit_data = null; 

$tab = $_GET['tab'] ?? 'cho_duyet'; 
$search_keyword = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// 2. GỌI ĐẦU TRANG ADMIN
require 'dau_trang_quan_tri.php'; 

$upload_dir_tin_tuc = '../tai_len/tin_tuc/';

// 3. HÀM HỖ TRỢ (Giữ nguyên)
function xu_ly_tai_anh_tin_tuc($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ten_file_moi = 'news_' . uniqid() . time() . '.' . $file_ext;
            $duong_dan_dich = $upload_dir . $ten_file_moi;
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}

function xoa_anh_cu($ten_anh, $upload_dir) {
    if (!empty($ten_anh)) {
        $file_path = $upload_dir . $ten_anh;
        if (file_exists($file_path) && !is_dir($file_path)) {
            @unlink($file_path); 
            return true;
        }
    }
    return false;
}

// 4. XỬ LÝ LOGIC (CONTROLLER)

// --- 4.1. XỬ LÝ POST (THÊM / SỬA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // (Logic POST Thêm/Sửa giữ nguyên)
    $id_tin_tuc = $_POST['id_tin_tuc'] ?? null;
    $tieu_de = $conn->real_escape_string($_POST['tieu_de']);
    $trang_thai = $conn->real_escape_string($_POST['trang_thai']);
    $id_nguoi_dang = $_SESSION['id_nguoi_dung'];
    
    $noi_dung_1 = $_POST['noi_dung_1'] ?? '';
    $noi_dung_2 = $_POST['noi_dung_2'] ?? '';
    $noi_dung_3 = $_POST['noi_dung_3'] ?? '';

    $anh_dd_hien_tai = $_POST['anh_dai_dien_hien_tai'] ?? '';
    $anh_1_hien_tai = $_POST['anh_1_hien_tai'] ?? '';
    $anh_2_hien_tai = $_POST['anh_2_hien_tai'] ?? '';
    $anh_3_hien_tai = $_POST['anh_3_hien_tai'] ?? '';

    $anh_dd_moi = xu_ly_tai_anh_tin_tuc('anh_dai_dien', $upload_dir_tin_tuc);
    $anh_1_moi = xu_ly_tai_anh_tin_tuc('anh_1', $upload_dir_tin_tuc);
    $anh_2_moi = xu_ly_tai_anh_tin_tuc('anh_2', $upload_dir_tin_tuc);
    $anh_3_moi = xu_ly_tai_anh_tin_tuc('anh_3', $upload_dir_tin_tuc);
    
    $anh_dd_final = $anh_dd_moi ?? $anh_dd_hien_tai;
    $anh_1_final = $anh_1_moi ?? $anh_1_hien_tai;
    $anh_2_final = $anh_2_moi ?? $anh_2_hien_tai;
    $anh_3_final = $anh_3_moi ?? $anh_3_hien_tai;

    if ($id_tin_tuc) {
        $sql = "UPDATE tin_tuc SET 
                    tieu_de = ?, anh_dai_dien = ?, 
                    anh_1 = ?, noi_dung_1 = ?, 
                    anh_2 = ?, noi_dung_2 = ?, 
                    anh_3 = ?, noi_dung_3 = ?, 
                    trang_thai = ? 
                WHERE id_tin_tuc = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", 
            $tieu_de, $anh_dd_final, 
            $anh_1_final, $noi_dung_1, 
            $anh_2_final, $noi_dung_2, 
            $anh_3_final, $noi_dung_3, 
            $trang_thai, $id_tin_tuc);
    } else {
        $sql = "INSERT INTO tin_tuc 
                    (tieu_de, anh_dai_dien, anh_1, noi_dung_1, anh_2, noi_dung_2, anh_3, noi_dung_3, trang_thai, id_nguoi_dang) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", 
            $tieu_de, $anh_dd_final, 
            $anh_1_final, $noi_dung_1, 
            $anh_2_final, $noi_dung_2, 
            $anh_3_final, $noi_dung_3, 
            $trang_thai, $id_nguoi_dang);
    }
    
    if ($stmt->execute()) {
        $thong_bao = $id_tin_tuc ? "Cập nhật tin tức thành công!" : "Thêm tin tức mới thành công!";
        $action = 'danh_sach';
        if ($anh_dd_moi) { xoa_anh_cu($anh_dd_hien_tai, $upload_dir_tin_tuc); }
        if ($anh_1_moi) { xoa_anh_cu($anh_1_hien_tai, $upload_dir_tin_tuc); }
        if ($anh_2_moi) { xoa_anh_cu($anh_2_hien_tai, $upload_dir_tin_tuc); }
        if ($anh_3_moi) { xoa_anh_cu($anh_3_hien_tai, $upload_dir_tin_tuc); }
    } else {
        $thong_bao_loi = "Lỗi CSDL: " . $stmt->error;
        $action = $id_tin_tuc ? 'sua' : 'them';
        $edit_data = $_POST;
    }
}

// --- 4.2. XỬ LÝ GET (Xóa) ---
elseif ($action == 'xoa' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $stmt_get = $conn->prepare("SELECT anh_dai_dien, anh_1, anh_2, anh_3 FROM tin_tuc WHERE id_tin_tuc = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $images = $stmt_get->get_result()->fetch_assoc();
    
    $stmt_del = $conn->prepare("DELETE FROM tin_tuc WHERE id_tin_tuc = ?");
    $stmt_del->bind_param("i", $id);
    if ($stmt_del->execute()) {
        $thong_bao = "Đã xóa tin tức thành công!";
        if ($images) {
            xoa_anh_cu($images['anh_dai_dien'], $upload_dir_tin_tuc);
            xoa_anh_cu($images['anh_1'], $upload_dir_tin_tuc);
            xoa_anh_cu($images['anh_2'], $upload_dir_tin_tuc);
            xoa_anh_cu($images['anh_3'], $upload_dir_tin_tuc);
        }
    } else {
        $thong_bao_loi = "Lỗi khi xóa: " . $stmt_del->error;
    }
    $action = 'danh_sach';
}

// --- 4.3. XỬ LÝ GET (Lấy dữ liệu cho form Sửa) ---
if ($action == 'sua' && isset($_GET['id'])) {
    if (!$edit_data) { 
        $id = (int)$_GET['id'];
        $sql_get = "SELECT * FROM tin_tuc WHERE id_tin_tuc = ?";
        $stmt = $conn->prepare($sql_get);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $edit_data = $stmt->get_result()->fetch_assoc();
        
        if (!$edit_data) {
            $thong_bao_loi = "Không tìm thấy tin tức.";
            $action = 'danh_sach';
        }
    }
}

// --- 4.4. LOGIC LẤY DATA CHO DANH SÁCH (kèm TÌM KIẾM) ---
if ($action == 'danh_sach') {
    $result_list = null;
    $params = [];
    $types = "";
    
    // (MỚI) SQL Lấy thêm email và sdt
    $sql_list_base = "SELECT 
                        t.id_tin_tuc, t.tieu_de, t.anh_dai_dien, t.trang_thai, t.ngay_dang, 
                        COALESCE(nd.ten, 'N/A') as ten_nguoi_dang,
                        nd.email as email_nguoi_dang, 
                        nd.so_dien_thoai as sdt_nguoi_dang,
                        (SELECT COUNT(b.id_binh_luan) FROM binh_luan b WHERE b.id_tin_tuc = t.id_tin_tuc) as so_binh_luan
                    FROM tin_tuc t
                    LEFT JOIN nguoi_dung nd ON t.id_nguoi_dang = nd.id_nguoi_dung";
    
    $where_clauses = []; 
    
    $where_clauses[] = "t.trang_thai = ?";
    $params[] = $tab;
    $types .= "s";

    if (!empty($search_keyword)) { 
        $where_clauses[] = "t.tieu_de LIKE ?";
        $search_term = "%" . $search_keyword . "%";
        $params[] = $search_term;
        $types .= "s";
    }
    if (!empty($date_from)) {
        $where_clauses[] = "t.ngay_dang >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    if (!empty($date_to)) {
        $where_clauses[] = "t.ngay_dang <= ?";
        $params[] = $date_to . " 23:59:59"; 
        $types .= "s";
    }

    $sql_list = $sql_list_base . " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY t.ngay_dang DESC";
    
    $stmt_list = $conn->prepare($sql_list);
    if (!empty($params)) {
        $stmt_list->bind_param($types, ...$params);
    }
    $stmt_list->execute();
    $result_list = $stmt_list->get_result();
}
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    .filter-container {
        width: 100%;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: grid;
        grid-template-columns: 2fr 1fr 1fr; 
        gap: 20px;
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
    .filter-group input[type="text"],
    .filter-group input[type="date"] {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1em;
    }
    .filter-actions {
        grid-column: 1 / -1; 
        display: flex;
        gap: 10px;
    }
    .btn-add-new { background-color: #28a745; }

    .thumbnail-preview {
        width: 100px;
        height: 60px;
        object-fit: cover;
        border-radius: 5px;
        border: 1px solid #ddd;
        background: #f9f9f9;
    }
    .status-hien_thi { background-color: #28a745; }
    .status-an { background-color: #6c757d; }
    .status-cho_duyet { background-color: #ffc107; color: #333; }
    
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
    .action-links a.edit { color: #007bff; }
    .action-links a.delete { color: #dc3545; }
    .action-links a:hover { text-decoration: underline; }

    .comment-link {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background-color: #f0f0f0;
        padding: 5px 10px;
        border-radius: 5px;
        font-weight: bold;
        color: #333;
        text-decoration: none;
        font-size: 0.9em;
    }
    .comment-link:hover {
        background-color: #e0e0e0;
    }
    
    /* (MỚI) CSS Cột Người Đăng */
    .post-author-info {
        font-size: 0.9em;
        line-height: 1.5;
    }
    .post-author-info strong {
        color: #000;
        font-size: 1.1em;
    }
    .post-author-info small {
        display: block; /* Cho Email/SĐT xuống hàng */
        margin-top: 4px;
        color: #555;
    }
    .post-author-info small i {
        width: 1.2em;
        text-align: center;
        margin-right: 4px;
        color: #999;
    }

    /* CSS cho Form Thêm/Sửa */
    .form-container {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .form-grid-news {
        display: grid;
        grid-template-columns: 2fr 1fr; 
        gap: 30px;
    }
    .form-col-main {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .form-col-sidebar {
        display: flex;
        flex-direction: column;
        gap: 20px;
    }
    .form-group label {
        font-weight: bold;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-group label i { color: #888; width: 1.2em; text-align: center; }
    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        margin-top: 5px;
        box-sizing: border-box;
    }
    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }
    .form-actions {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }
    .image-preview {
        width: 100%;
        height: 150px;
        object-fit: cover;
        border: 2px dashed #ddd;
        border-radius: 5px;
        margin-top: 10px;
        background-color: #f9f9f9;
    }
    .sub-grid-3 {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }
    @media (max-width: 900px) {
        .form-grid-news {
            grid-template-columns: 1fr;
        }
        .form-col-sidebar {
            order: -1;
        }
        .sub-grid-3 {
            grid-template-columns: 1fr;
        }
        .filter-container {
            grid-template-columns: 1fr;
        }
    }
</style>


<h1>Quản lý Tin Tức</h1>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<?php if ($action == 'danh_sach'): ?>
    
    <div class="page-header">
        <h1></h1> 
        <a href="?action=them" class="btn btn-add-new">
            <i class="fas fa-plus"></i> Viết bài mới
        </a>
    </div>

    <nav class="tab-menu">
        <a href="?tab=cho_duyet" class="<?php echo ($tab == 'cho_duyet') ? 'active' : ''; ?>">
           <i class="fas fa-clock"></i> Chờ duyệt
        </a>
        <a href="?tab=hien_thi" class="<?php echo ($tab == 'hien_thi') ? 'active' : ''; ?>">
           <i class="fas fa-check-circle"></i> Đã hiển thị
        </a>
        <a href="?tab=an" class="<?php echo ($tab == 'an') ? 'active' : ''; ?>">
           <i class="fas fa-eye-slash"></i> Ẩn (Nháp)
        </a>
    </nav>
    
    <form action="quan_ly_tin_tuc.php" method="GET" class="filter-container">
        <input type="hidden" name="action" value="danh_sach">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab); ?>">
        
        <div class="filter-group">
            <label for="search">Tìm theo Tiêu đề:</label>
            <input type="text" id="search" name="search" placeholder="Nhập tiêu đề bài viết..." value="<?php echo htmlspecialchars($search_keyword); ?>">
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
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tiêu đề</th>
                <th>Người đăng (Email/SĐT)</th> <th>Bình luận</th>
                <th>Ngày đăng</th>
                <th>Trạng Thái</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result_list && $result_list->num_rows > 0): ?>
                <?php while($item = $result_list->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $item['id_tin_tuc']; ?></td>
                        <td>
                            <?php 
                            $anh_path = '../tai_len/tin_tuc/' . ($item['anh_dai_dien'] ?? 'default.png');
                            if (empty($item['anh_dai_dien']) || !file_exists($anh_path)) {
                                $anh_path = '../tai_len/san_pham/default.png'; 
                            }
                            ?>
                            <img src="<?php echo $anh_path; ?>" alt="Thumbnail" class="thumbnail-preview">
                        </td>
                        <td><?php echo htmlspecialchars($item['tieu_de']); ?></td>
                        
                        <td>
                            <div class="post-author-info">
                                <strong><?php echo htmlspecialchars($item['ten_nguoi_dang']); ?></strong>
                                <small><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($item['email_nguoi_dang'] ?? '...'); ?></small>
                                <small><i class="fas fa-phone"></i> <?php echo htmlspecialchars($item['sdt_nguoi_dang'] ?? '...'); ?></small>
                            </div>
                        </td>

                        <td>
                            <a href="quan_ly_binh_luan.php?id_tin_tuc=<?php echo $item['id_tin_tuc']; ?>" class="comment-link">
                                <?php echo $item['so_binh_luan']; ?> <i class="fas fa-comments"></i>
                            </a>
                        </td>
                        
                        <td><?php echo date('d-m-Y H:i', strtotime($item['ngay_dang'])); ?></td>
                        <td>
                            <span class="status-label status-<?php echo str_replace(' ', '_', $item['trang_thai']); ?>">
                                <?php echo htmlspecialchars($item['trang_thai']); ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <a href="?action=sua&id=<?php echo $item['id_tin_tuc']; ?>" class="edit" title="Sửa">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <a href="?action=xoa&id=<?php echo $item['id_tin_tuc']; ?>" 
                               class="delete" title="Xóa"
                               onclick="return confirm('Bạn có chắc chắn muốn XÓA tin tức này?');">
                               <i class="fas fa-trash-alt"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Không có bài viết nào trong mục này.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action == 'them' || $action == 'sua'): ?>
    <?php
        // Đổ dữ liệu
        $is_editing = ($action == 'sua' && $edit_data);
        $id_tin_tuc = $edit_data['id_tin_tuc'] ?? null;
        $tieu_de = $edit_data['tieu_de'] ?? '';
        $trang_thai = $edit_data['trang_thai'] ?? 'cho_duyet';
        $anh_dai_dien = $edit_data['anh_dai_dien'] ?? '';
        $anh_1 = $edit_data['anh_1'] ?? '';
        $anh_2 = $edit_data['anh_2'] ?? '';
        $anh_3 = $edit_data['anh_3'] ?? '';
        $noi_dung_1 = $edit_data['noi_dung_1'] ?? '';
        $noi_dung_2 = $edit_data['noi_dung_2'] ?? '';
        $noi_dung_3 = $edit_data['noi_dung_3'] ?? '';
    ?>
    <h1><?php echo $is_editing ? 'Sửa Tin Tức' : 'Viết Bài Mới'; ?></h1>
    
    <div class="form-container">
        <form action="quan_ly_tin_tuc.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_tin_tuc" value="<?php echo $id_tin_tuc; ?>">
            <input type="hidden" name="anh_dai_dien_hien_tai" value="<?php echo htmlspecialchars($anh_dai_dien); ?>">
            <input type="hidden" name="anh_1_hien_tai" value="<?php echo htmlspecialchars($anh_1); ?>">
            <input type="hidden" name="anh_2_hien_tai" value="<?php echo htmlspecialchars($anh_2); ?>">
            <input type="hidden" name="anh_3_hien_tai" value="<?php echo htmlspecialchars($anh_3); ?>">
            
            <div class="form-grid-news">
                
                <div class="form-col-main">
                    <div class="form-group">
                        <label for="tieu_de"><i class="fas fa-heading"></i> Tiêu đề (*)</label>
                        <input type="text" id="tieu_de" name="tieu_de" value="<?php echo htmlspecialchars($tieu_de); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="noi_dung_1"><i class="fas fa-paragraph"></i> Nội dung 1</label>
                        <textarea id="noi_dung_1" name="noi_dung_1" rows="6"><?php echo htmlspecialchars($noi_dung_1); ?></textarea>
                    </div>

                    <div class="form-group">
                        <label for="noi_dung_2"><i class="fas fa-paragraph"></i> Nội dung 2</label>
                        <textarea id="noi_dung_2" name="noi_dung_2" rows="6"><?php echo htmlspecialchars($noi_dung_2); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label for="noi_dung_3"><i class="fas fa-paragraph"></i> Nội dung 3</label>
                        <textarea id="noi_dung_3" name="noi_dung_3" rows="6"><?php echo htmlspecialchars($noi_dung_3); ?></textarea>
                    </div>
                </div>
                
                <div class="form-col-sidebar">
                    <div class="form-group">
                        <label for="trang_thai"><i class="fas fa-toggle-on"></i> Trạng thái</label>
                        <select id="trang_thai" name="trang_thai">
                            <option value="hien_thi" <?php echo ($trang_thai == 'hien_thi') ? 'selected' : ''; ?>>Đã hiển thị</option>
                            <option value="an" <?php echo ($trang_thai == 'an') ? 'selected' : ''; ?>>Ẩn (Nháp)</option>
                            <option value="cho_duyet" <?php echo ($trang_thai == 'cho_duyet') ? 'selected' : ''; ?>>Chờ duyệt</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="anh_dai_dien"><i class="fas fa-image"></i> Ảnh đại diện (Thumbnail)</label>
                        <?php 
                        $anh_path = '../tai_len/tin_tuc/' . ($anh_dai_dien ?? 'default.png');
                        if (empty($anh_dai_dien) || !file_exists($anh_path)) {
                            $anh_path = '../tai_len/san_pham/default.png'; 
                        }
                        ?>
                        <img src="<?php echo $anh_path; ?>" class="image-preview">
                        <input type="file" id="anh_dai_dien" name="anh_dai_dien" accept="image/*">
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-images"></i> Các ảnh nội dung</label>
                        <div class="sub-grid-3">
                            <div class="form-group">
                                <label for="anh_1">Ảnh 1</label>
                                <input type="file" id="anh_1" name="anh_1" accept="image/*">
                            </div>
                             <div class="form-group">
                                <label for="anh_2">Ảnh 2</label>
                                <input type="file" id="anh_2" name="anh_2" accept="image/*">
                            </div>
                             <div class="form-group">
                                <label for="anh_3">Ảnh 3</label>
                                <input type="file" id="anh_3" name="anh_3" accept="image/*">
                            </div>
                        </div>
                    </div>
                </div>
                
            </div> <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $is_editing ? 'Cập Nhật' : 'Đăng Bài'; ?>
                </button>
                <a href="?action=danh_sach&tab=<?php echo $tab; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy Bỏ
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require 'cuoi_trang_quan_tri.php'; ?>