<?php
// 1. (SỬA) GỌI CONFIG ADMIN ĐẦU TIÊN
require_once 'config_admin.php'; 

// 2. KHỞI TẠO BIẾN
$page_title = "Quản lý Quảng Cáo Slider"; 
$thong_bao = $_SESSION['thong_bao'] ?? "";
$thong_bao_loi = $_SESSION['thong_bao_loi'] ?? "";
unset($_SESSION['thong_bao'], $_SESSION['thong_bao_loi']); 

$action = $_GET['action'] ?? 'danh_sach'; 
$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';

// Thư mục upload (tính từ gốc)
$upload_dir_ads = 'tai_len/quang_cao/';

// 3. HÀM HỖ TRỢ UPLOAD ẢNH
function xu_ly_tai_anh($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_name = $file['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            if (!file_exists(ROOT_PATH . $upload_dir)) { 
                mkdir(ROOT_PATH . $upload_dir, 0777, true);
            }
            $ten_file_moi = 'ad_' . uniqid() . time() . '.' . $file_ext;
            $duong_dan_dich = ROOT_PATH . $upload_dir . $ten_file_moi;
            
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}
function xoa_anh_cu($ten_anh, $upload_dir) {
    if (!empty($ten_anh)) {
        $file_path = ROOT_PATH . $upload_dir . $ten_anh;
        if (file_exists($file_path)) {
            @unlink($file_path); 
            return true;
        }
    }
    return false;
}

// 4. XỬ LÝ LOGIC (POST VÀ GET)
// --- 4.1: XỬ LÝ POST (THÊM / SỬA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_qc = (int)($_POST['id_qc'] ?? 0);
    $link_dich = $conn->real_escape_string($_POST['link_dich']);
    $noi_dung_ghi_chu = $conn->real_escape_string($_POST['noi_dung_ghi_chu']);
    $trang_thai = $conn->real_escape_string($_POST['trang_thai']);
    $ngay_bat_dau = $conn->real_escape_string($_POST['ngay_bat_dau']);
    $ngay_ket_thuc = $conn->real_escape_string($_POST['ngay_ket_thuc']);
    $vi_tri = (int)($_POST['vi_tri'] ?? 0);
    $anh_hien_tai = $_POST['anh_hien_tai'] ?? '';
    
    $ten_file_anh_moi = xu_ly_tai_anh('hinh_anh', $upload_dir_ads);
    $ten_file_anh = $ten_file_anh_moi ?? $anh_hien_tai;
    
    if ($id_qc > 0) {
        // --- CẬP NHẬT (SỬA) ---
        $stmt = $conn->prepare("UPDATE quang_cao_slider SET 
                                hinh_anh = ?, link_dich = ?, noi_dung_ghi_chu = ?, 
                                trang_thai = ?, ngay_bat_dau = ?, ngay_ket_thuc = ?, vi_tri = ?
                                WHERE id_qc = ?");
        $stmt->bind_param("ssssssii", 
            $ten_file_anh, $link_dich, $noi_dung_ghi_chu, 
            $trang_thai, $ngay_bat_dau, $ngay_ket_thuc, $vi_tri, $id_qc);
        
        if ($stmt->execute()) {
            $_SESSION['thong_bao'] = "Cập nhật quảng cáo thành công!";
            if ($ten_file_anh_moi && $anh_hien_tai) {
                xoa_anh_cu($anh_hien_tai, $upload_dir_ads);
            }
        } else {
            $_SESSION['thong_bao_loi'] = "Lỗi khi cập nhật: " . $stmt->error;
        }
        
    } else {
        // --- THÊM MỚI ---
        if (empty($ten_file_anh)) {
            $_SESSION['thong_bao_loi'] = "Hình ảnh là bắt buộc khi thêm mới.";
            $_SESSION['form_data'] = $_POST;
            header("Location: quan_ly_quang_cao.php?action=them");
            exit();
        }
        
        $stmt = $conn->prepare("INSERT INTO quang_cao_slider 
                               (hinh_anh, link_dich, noi_dung_ghi_chu, trang_thai, ngay_bat_dau, ngay_ket_thuc, vi_tri) 
                               VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssi", 
            $ten_file_anh, $link_dich, $noi_dung_ghi_chu, 
            $trang_thai, $ngay_bat_dau, $ngay_ket_thuc, $vi_tri);
        
        if ($stmt->execute()) {
            $_SESSION['thong_bao'] = "Thêm quảng cáo mới thành công!";
        } else {
            $_SESSION['thong_bao_loi'] = "Lỗi khi thêm mới: " . $stmt->error;
        }
    }
    
    header("Location: quan_ly_quang_cao.php");
    exit();
}

// --- 4.2: XỬ LÝ GET (Xóa / Đổi Trạng Thái) ---
if ($action == 'xoa' && $id > 0) {
    $stmt_get_img = $conn->prepare("SELECT hinh_anh FROM quang_cao_slider WHERE id_qc = ?");
    $stmt_get_img->bind_param("i", $id);
    $stmt_get_img->execute();
    $img = $stmt_get_img->get_result()->fetch_assoc();
    
    $stmt = $conn->prepare("DELETE FROM quang_cao_slider WHERE id_qc = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['thong_bao'] = "Đã xóa quảng cáo thành công!";
        if ($img) {
            xoa_anh_cu($img['hinh_anh'], $upload_dir_ads);
        }
    }
    header("Location: quan_ly_quang_cao.php");
    exit();
}
if ($action == 'thay_doi_trang_thai' && $id > 0 && in_array($status, ['hien_thi', 'bi_an'])) {
    $stmt = $conn->prepare("UPDATE quang_cao_slider SET trang_thai = ? WHERE id_qc = ?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();
    $_SESSION['thong_bao'] = "Cập nhật trạng thái thành công!";
    header("Location: quan_ly_quang_cao.php");
    exit();
}

// 5. GỌI ĐẦU TRANG ADMIN
require 'dau_trang_quan_tri.php'; 

// 6. LOGIC LẤY DỮ LIỆU ĐỂ HIỂN THỊ
$data_list = [];
$form_data = null; 

if ($action == 'danh_sach') {
    $sql = "SELECT * FROM quang_cao_slider ORDER BY vi_tri ASC, ngay_bat_dau DESC";
    $result = $conn->query($sql);
    if ($result) { while ($row = $result->fetch_assoc()) $data_list[] = $row; }
    
} elseif ($action == 'sua' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM quang_cao_slider WHERE id_qc = ?");
    $stmt->bind_param("i", $id); $stmt->execute();
    $form_data = $stmt->get_result()->fetch_assoc();
    
} elseif ($action == 'them') {
    $form_data = $_SESSION['form_data'] ?? [];
    unset($_SESSION['form_data']);
}
?>

<style>
    .banner-preview {
        width: 200px;
        height: auto;
        object-fit: cover;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .form-container {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        max-width: 900px; 
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 30px;
    }
    .form-group-full {
        grid-column: 1 / -1;
    }
    .form-actions {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }
    .image-preview-box img {
        width: 150px;
        height: auto;
        border-radius: 5px;
        border: 1px solid #ddd;
    }
</style>


<?php if ($action == 'danh_sach'): ?>
    <div class="page-header">
        <h1>Quản lý Quảng Cáo Slider</h1>
        <a href="?action=them" class="btn btn-success">
            <i class="fas fa-plus"></i> Thêm Quảng Cáo Mới
        </a>
    </div>

    <?php if (!empty($thong_bao)): ?>
        <div class="message success"><?php echo $thong_bao; ?></div>
    <?php endif; ?>
    <?php if (!empty($thong_bao_loi)): ?>
        <div class="message error"><?php echo $thong_bao_loi; ?></div>
    <?php endif; ?>
    
    <table class="admin-table">
        <thead>
            <tr>
                <th>Banner</th>
                <th>Ghi chú (Admin)</th>
                <th>Thời gian</th>
                <th>Thứ tự</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($data_list)): ?>
                <tr><td colspan="6" style="text-align: center;">Chưa có quảng cáo nào.</td></tr>
            <?php else: ?>
                <?php foreach ($data_list as $item): ?>
                <tr>
                    <td>
                        <?php 
                        $anh_path = BASE_URL . $upload_dir_ads . ($item['hinh_anh'] ?? 'default.png');
                        $anh_check_path = ROOT_PATH . $upload_dir_ads . ($item['hinh_anh'] ?? 'default.png');
                        if (empty($item['hinh_anh']) || !file_exists($anh_check_path)) {
                            $anh_path = BASE_URL . 'tai_len/san_pham/default.png'; 
                        }
                        ?>
                        <img src="<?php echo $anh_path; ?>" alt="Banner" class="banner-preview">
                    </td>
                    <td><?php echo htmlspecialchars($item['noi_dung_ghi_chu']); ?></td>
                    <td>
                        Từ: <?php echo date('d-m-Y', strtotime($item['ngay_bat_dau'])); ?><br>
                        Đến: <?php echo date('d-m-Y', strtotime($item['ngay_ket_thuc'])); ?>
                    </td>
                    <td><?php echo $item['vi_tri']; ?></td>
                    <td>
                        <span class="status-label status-<?php echo str_replace('_', '-', $item['trang_thai']); ?>">
                            <?php echo ($item['trang_thai'] == 'hien_thi') ? 'Đang hiện' : 'Đang ẩn'; ?>
                        </span>
                    </td>
                    <td class="action-links">
                        <a href="?action=sua&id=<?php echo $item['id_qc']; ?>" class="edit">
                            <i class="fas fa-edit"></i> Sửa
                        </a>
                        <br>
                        <?php if ($item['trang_thai'] == 'hien_thi'): ?>
                            <a href="?action=thay_doi_trang_thai&status=bi_an&id=<?php echo $item['id_qc']; ?>" style="color: #ffc107;">
                                <i class="fas fa-eye-slash"></i> Ẩn
                            </a>
                        <?php else: ?>
                            <a href="?action=thay_doi_trang_thai&status=hien_thi&id=<?php echo $item['id_qc']; ?>" style="color: green;">
                                <i class="fas fa-eye"></i> Hiện
                            </a>
                        <?php endif; ?>
                        <br>
                        <a href="?action=xoa&id=<?php echo $item['id_qc']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa vĩnh viễn quảng cáo này?');">
                           <i class="fas fa-trash-alt"></i> Xóa
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action == 'them' || $action == 'sua'): ?>
    <?php
        $is_editing = ($action == 'sua' && $form_data);
        $edit_id = $form_data['id_qc'] ?? null;
        $edit_link = $form_data['link_dich'] ?? '';
        $edit_ghi_chu = $form_data['noi_dung_ghi_chu'] ?? '';
        $edit_anh = $form_data['hinh_anh'] ?? '';
        $edit_trang_thai = $form_data['trang_thai'] ?? 'bi_an';
        $edit_ngay_bat_dau = $form_data['ngay_bat_dau'] ?? '';
        $edit_ngay_ket_thuc = $form_data['ngay_ket_thuc'] ?? '';
        $edit_vi_tri = $form_data['vi_tri'] ?? 0;
    ?>
    <h1><?php echo $is_editing ? 'Sửa Quảng Cáo' : 'Thêm Quảng Cáo Mới'; ?></h1>

    <?php if (!empty($thong_bao_loi)): ?>
        <div class="message error"><?php echo $thong_bao_loi; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form action="quan_ly_quang_cao.php" method="POST" enctype="multipart/form-data">
            <?php if ($is_editing): ?>
                <input type="hidden" name="id_qc" value="<?php echo htmlspecialchars($edit_id); ?>">
                <input type="hidden" name="anh_hien_tai" value="<?php echo htmlspecialchars($edit_anh); ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div>
                    <div class="form-group">
                        <label for="hinh_anh">Ảnh Banner (16:7) (*)</label>
                        <?php if ($is_editing && $edit_anh): ?>
                            <div class="image-preview-box">
                                <p>Ảnh hiện tại:</p>
                                <img src="<?php echo BASE_URL . $upload_dir_ads . $edit_anh; ?>" alt="Ảnh hiện tại" style="max-width: 200px;">
                            </div>
                            <label style="margin-top: 10px;">Chọn ảnh mới (nếu muốn thay đổi):</label>
                        <?php endif; ?>
                        <input type="file" id="hinh_anh" name="hinh_anh" <?php echo $is_editing ? '' : 'required'; ?>>
                    </div>
                    <div class="form-group">
                        <label for="link_dich">Link đích (Khi bấm vào)</label>
                        <input type="text" id="link_dich" name="link_dich" placeholder="VD: /chi_tiet_san_pham.php?id=1" value="<?php echo htmlspecialchars($edit_link); ?>">
                    </div>
                    <div class="form-group">
                        <label for="noi_dung_ghi_chu">Ghi chú (Admin xem)</label>
                        <textarea id="noi_dung_ghi_chu" name="noi_dung_ghi_chu" rows="2"><?php echo htmlspecialchars($edit_ghi_chu); ?></textarea>
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label for="trang_thai">Trạng thái (*)</label>
                        <select id="trang_thai" name="trang_thai">
                            <option value="bi_an" <?php echo ($edit_trang_thai == 'bi_an') ? 'selected' : ''; ?>>Đang ẩn</option>
                            <option value="hien_thi" <?php echo ($edit_trang_thai == 'hien_thi') ? 'selected' : ''; ?>>Hiển thị</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="vi_tri">Thứ tự ưu tiên (Sắp xếp)</label>
                        <input type="number" id="vi_tri" name="vi_tri" value="<?php echo htmlspecialchars($edit_vi_tri); ?>">
                        <small>Số nhỏ hơn sẽ hiện trước.</small>
                    </div>
                    <div class="form-group">
                        <label>Thời gian diễn ra (*)</label>
                        <div class="form-group">
                            <label for="ngay_bat_dau" style="font-weight: normal;">Từ ngày:</label>
                            <input type="date" id="ngay_bat_dau" name="ngay_bat_dau" value="<?php echo htmlspecialchars($edit_ngay_bat_dau); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="ngay_ket_thuc" style="font-weight: normal;">Đến ngày:</label>
                            <input type="date" id="ngay_ket_thuc" name="ngay_ket_thuc" value="<?php echo htmlspecialchars($edit_ngay_ket_thuc); ?>" required>
                        </div>
                    </div>
                </div>
            </div> 
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $is_editing ? 'Cập Nhật' : 'Lưu Quảng Cáo'; ?>
                </button>
                <a href="quan_ly_quang_cao.php" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy Bỏ
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require 'cuoi_trang_quan_tri.php'; ?>