<?php
// 1. LOGIC PHP (PHẢI CHẠY TRƯỚC)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2.1. KIỂM TRA ĐĂNG NHẬP (BẮT BUỘC)
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['redirect_url'] = 'viet_bai.php'; 
    header("Location: dang_nhap.php");
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// 2.2. KHỞI TẠO BIẾN
$thong_bao_loi = "";
$upload_dir_tin_tuc = 'tai_len/tin_tuc/'; 
$is_editing = false;
$edit_data = [];

// 2.3. HÀM HỖ TRỢ UPLOAD & XÓA ẢNH
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
            $ten_file_moi = 'news_' . $_SESSION['id_nguoi_dung'] . '_' . uniqid() . time() . '.' . $file_ext;
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

// 2.4. (MỚI) XỬ LÝ GET (CHẾ ĐỘ SỬA)
// Kiểm tra xem đây có phải là SỬA BÀI không
if (isset($_GET['action']) && $_GET['action'] == 'sua' && isset($_GET['id'])) {
    $id_tin_tuc = (int)$_GET['id'];
    
    // Lấy dữ liệu bài viết VÀ kiểm tra xem có đúng là của người này không
    $sql_get = "SELECT * FROM tin_tuc WHERE id_tin_tuc = ? AND id_nguoi_dang = ?";
    $stmt_get = $conn->prepare($sql_get);
    $stmt_get->bind_param("ii", $id_tin_tuc, $id_nguoi_dung);
    $stmt_get->execute();
    $result_get = $stmt_get->get_result();
    
    if ($result_get->num_rows == 1) {
        $is_editing = true;
        $edit_data = $result_get->fetch_assoc();
    } else {
        // Không tìm thấy bài viết hoặc không phải của bạn
        header("Location: bai_viet_cua_toi.php");
        exit();
    }
}

// 2.5. XỬ LÝ POST (KHI NGƯỜI DÙNG GỬI BÀI)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $id_tin_tuc = (int)($_POST['id_tin_tuc'] ?? 0); // Lấy ID (nếu là Sửa)
    
    $tieu_de = $conn->real_escape_string($_POST['tieu_de']);
    $noi_dung_1 = $_POST['noi_dung_1'] ?? '';
    $noi_dung_2 = $_POST['noi_dung_2'] ?? '';
    $noi_dung_3 = $_POST['noi_dung_3'] ?? '';
    
    // YÊU CẦU: Luôn set "Chờ duyệt" khi Thêm hoặc Sửa
    $trang_thai = 'cho_duyet'; 
    
    if (empty($tieu_de) || empty($noi_dung_1)) {
        $thong_bao_loi = "Vui lòng nhập Tiêu đề và Nội dung 1.";
    } else {
        // Lấy tên ảnh cũ (nếu có)
        $anh_dd_hien_tai = $_POST['anh_dai_dien_hien_tai'] ?? '';
        $anh_1_hien_tai = $_POST['anh_1_hien_tai'] ?? '';
        $anh_2_hien_tai = $_POST['anh_2_hien_tai'] ?? '';
        $anh_3_hien_tai = $_POST['anh_3_hien_tai'] ?? '';

        // Upload ảnh mới
        $anh_dd_moi = xu_ly_tai_anh_tin_tuc('anh_dai_dien', $upload_dir_tin_tuc);
        $anh_1_moi = xu_ly_tai_anh_tin_tuc('anh_1', $upload_dir_tin_tuc);
        $anh_2_moi = xu_ly_tai_anh_tin_tuc('anh_2', $upload_dir_tin_tuc);
        $anh_3_moi = xu_ly_tai_anh_tin_tuc('anh_3', $upload_dir_tin_tuc);
        
        $anh_dd_final = $anh_dd_moi ?? $anh_dd_hien_tai;
        $anh_1_final = $anh_1_moi ?? $anh_1_hien_tai;
        $anh_2_final = $anh_2_moi ?? $anh_2_hien_tai;
        $anh_3_final = $anh_3_moi ?? $anh_3_hien_tai;
        
        if ($id_tin_tuc > 0) {
            // --- XỬ LÝ SỬA ---
            $sql_update = "UPDATE tin_tuc SET 
                                tieu_de = ?, anh_dai_dien = ?, 
                                anh_1 = ?, noi_dung_1 = ?, 
                                anh_2 = ?, noi_dung_2 = ?, 
                                anh_3 = ?, noi_dung_3 = ?, 
                                trang_thai = ? 
                           WHERE id_tin_tuc = ? AND id_nguoi_dang = ?"; // Kiểm tra quyền
            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param("sssssssssii", 
                $tieu_de, $anh_dd_final, 
                $anh_1_final, $noi_dung_1, 
                $anh_2_final, $noi_dung_2, 
                $anh_3_final, $noi_dung_3, 
                $trang_thai, $id_tin_tuc, $id_nguoi_dung);
            
            if ($stmt->execute()) {
                // Xóa ảnh cũ nếu upload ảnh mới
                if ($anh_dd_moi) { xoa_anh_cu($anh_dd_hien_tai, $upload_dir_tin_tuc); }
                if ($anh_1_moi) { xoa_anh_cu($anh_1_hien_tai, $upload_dir_tin_tuc); }
                if ($anh_2_moi) { xoa_anh_cu($anh_2_hien_tai, $upload_dir_tin_tuc); }
                if ($anh_3_moi) { xoa_anh_cu($anh_3_hien_tai, $upload_dir_tin_tuc); }
                header("Location: bai_viet_cua_toi.php");
                exit();
            } else {
                $thong_bao_loi = "Lỗi khi cập nhật: " . $conn->error;
            }

        } else {
            // --- XỬ LÝ THÊM MỚI ---
            $sql_insert = "INSERT INTO tin_tuc 
                            (tieu_de, anh_dai_dien, anh_1, noi_dung_1, anh_2, noi_dung_2, anh_3, noi_dung_3, trang_thai, id_nguoi_dang) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("sssssssssi", 
                $tieu_de, $anh_dd_final, 
                $anh_1_final, $noi_dung_1, 
                $anh_2_final, $noi_dung_2, 
                $anh_3_final, $noi_dung_3, 
                $trang_thai, $id_nguoi_dung);
            
            if ($stmt->execute()) {
                header("Location: bai_viet_cua_toi.php");
                exit();
            } else {
                $thong_bao_loi = "Lỗi CSDL: Không thể gửi bài. " . $conn->error;
            }
        }
    }
}
?>

<?php
// 3. GỌI ĐẦU TRANG
$page_title = $is_editing ? "Chỉnh sửa bài viết" : "Viết bài mới";
require 'dung_chung/dau_trang.php';
?>

<style>
    .image-preview {
        width: 100%;
        max-width: 200px; /* Giới hạn kích thước */
        height: auto;
        object-fit: cover;
        border: 2px dashed #ddd;
        border-radius: 5px;
        margin-top: 10px;
        background-color: #f9f9f9;
    }
</style>

<main class="container container-small"> 
    
    <h1><i class="fas <?php echo $is_editing ? 'fa-edit' : 'fa-pen-alt'; ?>"></i> <?php echo $page_title; ?></h1>
    
    <?php if ($is_editing): ?>
        <p>Bài viết của bạn sẽ được gửi lại cho quản trị viên để xem xét và chuyển về trạng thái "Chờ duyệt".</p>
    <?php else: ?>
        <p>Bài viết của bạn sẽ được gửi đến quản trị viên để xem xét trước khi hiển thị.</p>
    <?php endif; ?>

    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <form action="viet_bai.php<?php echo $is_editing ? '?action=sua&id='.$edit_data['id_tin_tuc'] : ''; ?>" method="POST" enctype="multipart/form-data" data-turbolinks="false">
        
        <?php if ($is_editing): ?>
            <input type="hidden" name="id_tin_tuc" value="<?php echo $edit_data['id_tin_tuc']; ?>">
            <input type="hidden" name="anh_dai_dien_hien_tai" value="<?php echo htmlspecialchars($edit_data['anh_dai_dien'] ?? ''); ?>">
            <input type="hidden" name="anh_1_hien_tai" value="<?php echo htmlspecialchars($edit_data['anh_1'] ?? ''); ?>">
            <input type="hidden" name="anh_2_hien_tai" value="<?php echo htmlspecialchars($edit_data['anh_2'] ?? ''); ?>">
            <input type="hidden" name="anh_3_hien_tai" value="<?php echo htmlspecialchars($edit_data['anh_3'] ?? ''); ?>">
        <?php endif; ?>

        <div class="form-group full-width">
            <label for="tieu_de">Tiêu đề (*)</label>
            <input type="text" id="tieu_de" name="tieu_de" value="<?php echo htmlspecialchars($edit_data['tieu_de'] ?? ''); ?>" required>
        </div>

        <div class="form-group full-width">
            <label for="anh_dai_dien">Ảnh đại diện (Thumbnail)</label>
            <?php if ($is_editing && !empty($edit_data['anh_dai_dien'])): ?>
                <img src="<?php echo $upload_dir_tin_tuc . $edit_data['anh_dai_dien']; ?>" class="image-preview">
            <?php endif; ?>
            <input type="file" id="anh_dai_dien" name="anh_dai_dien" accept="image/*">
        </div>
        
        <div class="form-group full-width">
            <label for="noi_dung_1">Nội dung 1 (*)</label>
            <textarea id="noi_dung_1" name="noi_dung_1" rows="8" required><?php echo htmlspecialchars($edit_data['noi_dung_1'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="anh_1">Ảnh 1 (Chèn sau Nội dung 1)</label>
            <?php if ($is_editing && !empty($edit_data['anh_1'])): ?>
                <img src="<?php echo $upload_dir_tin_tuc . $edit_data['anh_1']; ?>" class="image-preview">
            <?php endif; ?>
            <input type="file" id="anh_1" name="anh_1" accept="image/*">
        </div>

        <div class="form-group full-width">
            <label for="noi_dung_2">Nội dung 2</label>
            <textarea id="noi_dung_2" name="noi_dung_2" rows="6"><?php echo htmlspecialchars($edit_data['noi_dung_2'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="anh_2">Ảnh 2 (Chèn sau Nội dung 2)</label>
            <?php if ($is_editing && !empty($edit_data['anh_2'])): ?>
                <img src="<?php echo $upload_dir_tin_tuc . $edit_data['anh_2']; ?>" class="image-preview">
            <?php endif; ?>
            <input type="file" id="anh_2" name="anh_2" accept="image/*">
        </div>
        
        <div class="form-group full-width">
            <label for="noi_dung_3">Nội dung 3</label>
            <textarea id="noi_dung_3" name="noi_dung_3" rows="6"><?php echo htmlspecialchars($edit_data['noi_dung_3'] ?? ''); ?></textarea>
        </div>

        <div class="form-group full-width">
            <label for="anh_3">Ảnh 3 (Chèn sau Nội dung 3)</label>
            <?php if ($is_editing && !empty($edit_data['anh_3'])): ?>
                <img src="<?php echo $upload_dir_tin_tuc . $edit_data['anh_3']; ?>" class="image-preview">
            <?php endif; ?>
            <input type="file" id="anh_3" name="anh_3" accept="image/*">
        </div>
        
        <button type="submit" class="btn-submit">
            <i class="fas fa-paper-plane"></i> 
            <?php echo $is_editing ? 'Lưu và Gửi duyệt lại' : 'Gửi bài chờ duyệt'; ?>
        </button>
    </form>
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>