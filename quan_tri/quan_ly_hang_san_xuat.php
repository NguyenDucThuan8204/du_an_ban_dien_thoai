<?php
// 1. KẾT NỐI CSDL VÀ LOGIC XỬ LÝ (PHẢI NẰM TRƯỚC KHI GỌI HEADER)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../dung_chung/ket_noi_csdl.php'; // $conn sẽ được tạo ở đây

// 2. HÀM HỖ TRỢ UPLOAD (Cho logo hãng)
$upload_dir_hang = '../tai_len/hang_san_xuat/';
function xu_ly_tai_anh_hang($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_ten = basename($file['name']);
        $file_ext = strtolower(pathinfo($file_ten, PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ten_file_moi = 'hang_' . uniqid() . time() . '.' . $file_ext;
            $duong_dan_dich = $upload_dir . $ten_file_moi;
            
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}

// 3. XỬ LÝ FORM (THÊM / SỬA)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_hang = $conn->real_escape_string($_POST['ten_hang']);
    $trang_thai = $conn->real_escape_string($_POST['trang_thai']);
    $id_hang = (int)($_POST['id_hang'] ?? 0);
    $logo_hien_tai = $_POST['logo_hien_tai'] ?? '';

    $ten_logo_moi = xu_ly_tai_anh_hang('logo_hang', $upload_dir_hang);
    $logo_hang = $ten_logo_moi ?? $logo_hien_tai; // Ưu tiên ảnh mới

    if ($id_hang > 0) {
        // --- CẬP NHẬT ---
        $sql = "UPDATE hang_san_xuat SET ten_hang = ?, logo_hang = ?, trang_thai = ? WHERE id_hang = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $ten_hang, $logo_hang, $trang_thai, $id_hang);
        $stmt->execute();
        // Xóa logo cũ nếu upload logo mới thành công
        if ($ten_logo_moi && !empty($logo_hien_tai) && file_exists($upload_dir_hang . $logo_hien_tai)) {
            @unlink($upload_dir_hang . $logo_hien_tai);
        }
    } else {
        // --- THÊM MỚI ---
        $sql = "INSERT INTO hang_san_xuat (ten_hang, logo_hang, trang_thai) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $ten_hang, $logo_hang, $trang_thai);
        $stmt->execute();
    }
    
    header("Location: quan_ly_hang_san_xuat.php"); // Chuyển hướng về trang danh sách
    exit();
}

// 4. XỬ LÝ HÀNH ĐỘNG (XÓA)
$action = $_GET['action'] ?? 'danh_sach';
$id = (int)($_GET['id'] ?? 0);

if ($action == 'xoa' && $id > 0) {
    // Lấy tên logo để xóa file
    $stmt_get = $conn->prepare("SELECT logo_hang FROM hang_san_xuat WHERE id_hang = ?");
    $stmt_get->bind_param("i", $id);
    $stmt_get->execute();
    $logo = $stmt_get->get_result()->fetch_assoc();
    if ($logo && !empty($logo['logo_hang']) && file_exists($upload_dir_hang . $logo['logo_hang'])) {
        @unlink($upload_dir_hang . $logo['logo_hang']);
    }
    // Xóa trong CSDL
    $stmt_del = $conn->prepare("DELETE FROM hang_san_xuat WHERE id_hang = ?");
    $stmt_del->bind_param("i", $id);
    $stmt_del->execute();
    
    header("Location: quan_ly_hang_san_xuat.php");
    exit();
}

// 5. ĐẶT TIÊU ĐỀ VÀ GỌI HEADER
$page_title = "Quản lý Hãng Sản Xuất";
require 'dau_trang_quan_tri.php'; 

// 6. LẤY DỮ LIỆU ĐỂ HIỂN THỊ (Sau khi gọi Header)
$hang_can_sua = null;
if ($action == 'sua' && $id > 0) {
    $stmt = $conn->prepare("SELECT * FROM hang_san_xuat WHERE id_hang = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $hang_can_sua = $stmt->get_result()->fetch_assoc();
}

// Lấy danh sách
$sql_list = "SELECT * FROM hang_san_xuat ORDER BY id_hang DESC";
$result_list = $conn->query($sql_list);
?>

<style>
    .form-container {
        background-color: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 150px;
        gap: 20px;
        align-items: flex-end; /* Căn nút "Lưu" thẳng hàng */
    }
    .form-group {
        margin-bottom: 0;
    }
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .form-group input[type="text"],
    .form-group input[type="file"],
    .form-group select {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        box-sizing: border-box; /* Quan trọng */
    }
    .btn {
        background-color: #007bff;
        color: white;
        padding: 10px 15px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-size: 1em;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }
    .btn-success {
        background-color: #28a745;
    }
    .btn-add-new {
        margin-bottom: 20px;
    }
    .logo-preview {
        width: 60px;
        height: 60px;
        object-fit: contain;
        border: 1px solid #ddd;
        border-radius: 5px;
        background: #f9f9f9;
    }
</style>

<h1>Quản lý Hãng Sản Xuất</h1>

<?php if ($action == 'danh_sach'): ?>
    <a href="?action=them" class="btn btn-add-new"><i class="fas fa-plus"></i> Thêm Hãng Mới</a>
<?php endif; ?>

<?php if ($action == 'them' || ($action == 'sua' && $hang_can_sua)): ?>
    <div class="form-container">
        <h2><?php echo $action == 'them' ? 'Thêm Hãng Mới' : 'Cập nhật Hãng'; ?></h2>
        
        <form action="quan_ly_hang_san_xuat.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="id_hang" value="<?php echo $hang_can_sua['id_hang'] ?? 0; ?>">
            <input type="hidden" name="logo_hien_tai" value="<?php echo $hang_can_sua['logo_hang'] ?? ''; ?>">
            
            <div class="form-grid">
                <div class="form-group">
                    <label for="ten_hang">Tên hãng</label>
                    <input type="text" id="ten_hang" name="ten_hang" 
                           value="<?php echo htmlspecialchars($hang_can_sua['ten_hang'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="logo_hang">Logo (Tùy chọn)</label>
                    <input type="file" id="logo_hang" name="logo_hang" accept="image/*">
                </div>

                <div class="form-group">
                    <label for="trang_thai">Trạng thái</label>
                    <select id="trang_thai" name="trang_thai">
                        <option value="hien_thi" <?php echo (isset($hang_can_sua) && $hang_can_sua['trang_thai'] == 'hien_thi') ? 'selected' : ''; ?>>
                            Hiển thị
                        </option>
                        <option value="an" <?php echo (isset($hang_can_sua) && $hang_can_sua['trang_thai'] == 'an') ? 'selected' : ''; ?>>
                            Ẩn
                        </option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> Lưu
                </button>
            </div>
            
            <?php if (isset($hang_can_sua['logo_hang']) && !empty($hang_can_sua['logo_hang'])): ?>
                <div style="margin-top: 10px;">
                    <label>Logo hiện tại:</label><br>
                    <img src="../tai_len/hang_san_xuat/<?php echo $hang_can_sua['logo_hang']; ?>" class="logo-preview">
                </div>
            <?php endif; ?>
        </form>
    </div>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>ID</th>
            <th>Logo</th>
            <th>Tên Hãng</th>
            <th>Trạng thái</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($result_list->num_rows > 0): ?>
            <?php while($hang = $result_list->fetch_assoc()): ?>
            <tr>
                <td><?php echo $hang['id_hang']; ?></td>
                <td>
                    <?php 
                    $anh_path = '../tai_len/hang_san_xuat/' . ($hang['logo_hang'] ?? 'default.png');
                    if (empty($hang['logo_hang']) || !file_exists($anh_path)) {
                        $anh_path = '../tai_len/san_pham/default.png'; // Dùng 1 ảnh default
                    }
                    ?>
                    <img src="<?php echo $anh_path; ?>" alt="Logo" class="logo-preview">
                </td>
                <td><?php echo htmlspecialchars($hang['ten_hang']); ?></td>
                <td>
                    <?php if ($hang['trang_thai'] == 'hien_thi'): ?>
                        <span class="status-label" style="background-color: #28a745;">Hiển thị</span>
                    <?php else: ?>
                        <span class="status-label" style="background-color: #6c757d;">Ẩn</span>
                    <?php endif; ?>
                </td>
                <td class="action-links">
                    <a href="?action=sua&id=<?php echo $hang['id_hang']; ?>"><i class="fas fa-edit"></i> Sửa</a>
                    <a href="?action=xoa&id=<?php echo $hang['id_hang']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa hãng này? (Toàn bộ sản phẩm thuộc hãng này cũng sẽ bị xóa!)');">
                        <i class="fas fa-trash-alt"></i> Xóa
                    </a>
                </td>
            </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr>
                <td colspan="5" style="text-align: center;">Chưa có hãng sản xuất nào.</td>
            </tr>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'cuoi_trang_quan_tri.php'; ?>