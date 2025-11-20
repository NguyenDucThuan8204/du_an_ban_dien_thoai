<?php
// Đặt tiêu đề cho trang này (biến $page_title sẽ được dùng bởi dau_trang.php)
$page_title = "Thông tin tài khoản";

// 1. GỌI ĐẦU TRANG (Đã bao gồm session, CSDL, CSS, Menu và Turbolinks)
require 'dung_chung/dau_trang.php';
?>

<?php
// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY

// KIỂM TRA ĐĂNG NHẬP (BẮT BUỘC)
if (!isset($_SESSION['id_nguoi_dung'])) {
    // Turbolinks sẽ tự động xử lý chuyển hướng này
    header("Location: dang_nhap.php"); 
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// KHỞI TẠO BIẾN
$thong_bao_loi = "";
$thong_bao_thanh_cong = "";
$upload_dir = 'tai_len/avatars/'; 

// HÀM HỖ TRỢ TẢI LÊN AVATAR
function xu_ly_tai_avatar($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ten_file_moi = 'user_' . $_SESSION['id_nguoi_dung'] . '_' . time() . '.' . $file_ext;
            $duong_dan_dich = $upload_dir . $ten_file_moi;
            
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}

// XỬ LÝ POST (CẬP NHẬT THÔNG TIN)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $ho = $conn->real_escape_string($_POST['ho']);
    $ten = $conn->real_escape_string($_POST['ten']);
    $so_dien_thoai = $conn->real_escape_string($_POST['so_dien_thoai']);
    $dia_chi_chi_tiet = $conn->real_escape_string($_POST['dia_chi_chi_tiet']);
    $tinh_thanh_pho = $conn->real_escape_string($_POST['tinh_thanh_pho']);
    $phuong_xa = $conn->real_escape_string($_POST['phuong_xa']);
    $anh_hien_tai = $_POST['anh_dai_dien_hien_tai'] ?? '';
    
    $ten_anh_moi = xu_ly_tai_avatar('anh_dai_dien', $upload_dir);
    $anh_dai_dien = $ten_anh_moi ?? $anh_hien_tai; 

    $sql_update = "UPDATE nguoi_dung SET 
                        ho = ?, 
                        ten = ?, 
                        so_dien_thoai = ?, 
                        dia_chi_chi_tiet = ?, 
                        tinh_thanh_pho = ?,
                        phuong_xa = ?,
                        anh_dai_dien = ?
                   WHERE id_nguoi_dung = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("sssssssi", 
        $ho, $ten, $so_dien_thoai, 
        $dia_chi_chi_tiet, $tinh_thanh_pho, $phuong_xa, 
        $anh_dai_dien, $id_nguoi_dung
    );
    
    if ($stmt_update->execute()) {
        $thong_bao_thanh_cong = "Cập nhật thông tin thành công!";
        $_SESSION['ten'] = $ten;
        
        if ($ten_anh_moi && !empty($anh_hien_tai) && file_exists($upload_dir . $anh_hien_tai)) {
            @unlink($upload_dir . $anh_hien_tai);
        }
    } else {
        $thong_bao_loi = "Lỗi khi cập nhật thông tin: " . $conn->error;
    }
}

// LẤY THÔNG TIN HIỆN TẠI CỦA NGƯỜI DÙNG (LUÔN LẤY MỚI)
$sql_get_user = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung = ?";
$stmt_get_user = $conn->prepare($sql_get_user);
$stmt_get_user->bind_param("i", $id_nguoi_dung);
$stmt_get_user->execute();
$user_info = $stmt_get_user->get_result()->fetch_assoc();
?>

<style>
    .profile-container {
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 30px;
    }
    .profile-grid {
        display: grid;
        grid-template-columns: 200px 1fr; /* Cột avatar và cột thông tin */
        gap: 30px;
    }
    .avatar-uploader {
        display: flex;
        flex-direction: column;
        align-items: center;
    }
    .avatar-preview {
        width: 150px;
        height: 150px;
        border-radius: 50%; /* Bo tròn */
        object-fit: cover;
        border: 4px solid #eee;
        margin-bottom: 15px;
    }
    .avatar-uploader input[type="file"] {
        font-size: 0.9rem;
        max-width: 200px;
    }
    
    .profile-form {
        display: grid;
        grid-template-columns: 1fr 1fr; /* 2 cột cho Họ/Tên, Tỉnh/Phường */
        gap: 20px;
    }
    .profile-form .form-group {
        margin-bottom: 0; /* Đã có gap */
    }
    
    .btn-submit {
        grid-column: 2 / 3; /* Nằm ở cột 2 */
        justify-self: end; /* Đẩy về bên phải */
    }
    .password-link {
        grid-column: 1 / -1;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
</style>

<main class="container container-small"> <h1>Thông tin tài khoản</h1>

    <?php if ($thong_bao_thanh_cong): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao_thanh_cong); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <div class="profile-container">
        <form action="thong_tin_tai_khoan.php" method="POST" enctype="multipart/form-data" data-turbolinks="false">
            
            <div class="profile-grid">
                
                <div class="avatar-uploader">
                    <?php 
                    $anh_path_avatar = 'tai_len/avatars/' . ($user_info['anh_dai_dien'] ?? 'default-avatar.png');
                    if (empty($user_info['anh_dai_dien']) || !file_exists($anh_path_avatar)) {
                        $anh_path_avatar = 'tai_len/avatars/default-avatar.png'; 
                    }
                    ?>
                    <img src="<?php echo $anh_path_avatar; ?>" alt="Avatar" class="avatar-preview">
                    
                    <div class="form-group">
                        <label for="anh_dai_dien">Thay đổi ảnh đại diện</label>
                        <input type="file" id="anh_dai_dien" name="anh_dai_dien" accept="image/*">
                        <input type="hidden" name="anh_dai_dien_hien_tai" value="<?php echo htmlspecialchars($user_info['anh_dai_dien'] ?? ''); ?>">
                    </div>
                </div>
                
                <div class="profile-form">
                    <div class="form-group full-width">
                        <label for="email">Email (Không thể thay đổi)</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email']); ?>" disabled>
                    </div>
                    
                    <div class="form-group">
                        <label for="ho">Họ</label>
                        <input type="text" id="ho" name="ho" value="<?php echo htmlspecialchars($user_info['ho'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="ten">Tên (*)</label>
                        <input type="text" id="ten" name="ten" value="<?php echo htmlspecialchars($user_info['ten'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group full-width">
                        <label for="so_dien_thoai">Số điện thoại</label>
                        <input type="text" id="so_dien_thoai" name="so_dien_thoai" value="<?php echo htmlspecialchars($user_info['so_dien_thoai'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="tinh_thanh_pho">Tỉnh/Thành phố</label>
                        <input type="text" id="tinh_thanh_pho" name="tinh_thanh_pho" value="<?php echo htmlspecialchars($user_info['tinh_thanh_pho'] ?? ''); ?>">
                    </div>
                    <div class="form-group">
                        <label for="phuong_xa">Phường/Xã</label>
                        <input type="text" id="phuong_xa" name="phuong_xa" value="<?php echo htmlspecialchars($user_info['phuong_xa'] ?? ''); ?>">
                    </div>
                    <div class="form-group full-width">
                        <label for="dia_chi_chi_tiet">Địa chỉ chi tiết (Số nhà, tên đường)</label>
                        <input type="text" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet" value="<?php echo htmlspecialchars($user_info['dia_chi_chi_tiet'] ?? ''); ?>">
                    </div>

                    <div class="password-link">
                        <a href="doi_mat_khau.php">Bạn muốn đổi mật khẩu?</a>
                    </div>
                    
                    <button type="submit" class="btn btn-submit">
                        <i class="fas fa-save"></i> Lưu Thay Đổi
                    </button>
                </div>
                
            </div>
        </form>
    </div>
    
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>