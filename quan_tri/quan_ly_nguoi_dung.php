<?php
// 1. KHỞI TẠO BIẾN TRƯỚC VÀ LẤY THAM SỐ
$page_title = "Quản lý Người Dùng"; 
$thong_bao = ""; 
$thong_bao_loi = ""; 
$action = $_GET['action'] ?? 'danh_sach'; 
$tab_hien_tai = $_GET['tab'] ?? 'khach_hang';
$user_data = null; 

// (MỚI) Lấy các tham số lọc
$tu_khoa_tim_kiem = $_GET['search'] ?? ''; 
$search_tinh = $_GET['search_tinh'] ?? '';
$search_phuong = $_GET['search_phuong'] ?? '';
$search_trang_thai = $_GET['search_trang_thai'] ?? '';

// 2. GỌI SESSION, CSDL, VÀ KIỂM TRA ADMIN (BẮT BUỘC TRƯỚC KHI XỬ LÝ)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require '../dung_chung/ket_noi_csdl.php'; 
require 'kiem_tra_quan_tri.php'; // Chạy kiểm tra bảo mật trước

// Thư mục tải lên
$upload_dir_avatar = '../tai_len/avatars/';

// 3. HÀM HỖ TRỢ TẢI LÊN HÌNH ẢNH
function xu_ly_tai_anh($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            $ten_file_moi = uniqid('avatar_', true) . '.' . $file_ext;
            $duong_dan_dich = $upload_dir . $ten_file_moi;
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}

// Hàm hỗ trợ xóa file ảnh
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
// --- XỬ LÝ FORM SUBMIT (THÊM/SỬA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Lọc và làm sạch dữ liệu đầu vào
    $id_nguoi_dung = $_POST['id_nguoi_dung'] ?? null;
    $anh_dai_dien_hien_tai = $_POST['anh_dai_dien_hien_tai'] ?? null;
    
    $email = $conn->real_escape_string(trim($_POST['email']));
    $ho = $conn->real_escape_string(trim($_POST['ho']));
    $ten = $conn->real_escape_string(trim($_POST['ten']));
    $so_dien_thoai = $conn->real_escape_string(trim($_POST['so_dien_thoai']));
    $so_cccd = $conn->real_escape_string(trim($_POST['so_cccd']));
    $tinh_thanh_pho = $conn->real_escape_string(trim($_POST['tinh_thanh_pho']));
    $phuong_xa = $conn->real_escape_string(trim($_POST['phuong_xa']));
    $dia_chi_chi_tiet = $conn->real_escape_string(trim($_POST['dia_chi_chi_tiet']));
    $vai_tro = $conn->real_escape_string(trim($_POST['vai_tro']));
    $trang_thai = $conn->real_escape_string(trim($_POST['trang_thai']));
    $mat_khau = $_POST['mat_khau'];
    
    $ten_file_anh_moi = xu_ly_tai_anh('anh_dai_dien', $upload_dir_avatar);
    $ten_file_anh = $ten_file_anh_moi ?? $anh_dai_dien_hien_tai; 
    
    $user_data = $_POST; 
    $user_data['anh_dai_dien'] = $ten_file_anh; 

    // 1. KIỂM TRA EMAIL TỒN TẠI
    $sql_check_email = "SELECT id_nguoi_dung FROM nguoi_dung WHERE email = ?";
    $types_check_email = "s";
    $params_check_email = [$email];
    if ($id_nguoi_dung) {
        $sql_check_email .= " AND id_nguoi_dung != ?";
        $types_check_email .= "i";
        $params_check_email[] = $id_nguoi_dung;
    }
    $stmt_check_email = $conn->prepare($sql_check_email);
    $stmt_check_email->bind_param($types_check_email, ...$params_check_email);
    $stmt_check_email->execute();
    $email_exists = $stmt_check_email->get_result()->num_rows > 0;

    // (MỚI) 2. KIỂM TRA CCCD TỒN TẠI (CHỈ KHI CÓ NHẬP)
    $cccd_exists = false;
    if (!empty($so_cccd)) {
        $sql_check_cccd = "SELECT id_nguoi_dung FROM nguoi_dung WHERE so_cccd = ?";
        $types_check_cccd = "s";
        $params_check_cccd = [$so_cccd];
        if ($id_nguoi_dung) {
            $sql_check_cccd .= " AND id_nguoi_dung != ?";
            $types_check_cccd .= "i";
            $params_check_cccd[] = $id_nguoi_dung;
        }
        $stmt_check_cccd = $conn->prepare($sql_check_cccd);
        $stmt_check_cccd->bind_param($types_check_cccd, ...$params_check_cccd);
        $stmt_check_cccd->execute();
        $cccd_exists = $stmt_check_cccd->get_result()->num_rows > 0;
    }

    // 3. XỬ LÝ LỖI
    if ($email_exists) {
        $thong_bao_loi = "Email này đã tồn tại.";
        $action = $id_nguoi_dung ? 'sua' : 'them';
    } elseif ($cccd_exists) {
        $thong_bao_loi = "Số CCCD này đã tồn tại.";
        $action = $id_nguoi_dung ? 'sua' : 'them';
    } elseif (!$id_nguoi_dung && empty($mat_khau)) {
        $thong_bao_loi = "Mật khẩu là bắt buộc khi thêm người dùng mới.";
        $action = 'them'; 
    }
    // 4. THỰC THI THÊM/SỬA
    else {
        if ($id_nguoi_dung) {
            // --- CẬP NHẬT (SỬA) ---
            $sql_update = "UPDATE nguoi_dung SET 
                email = ?, ho = ?, ten = ?, so_dien_thoai = ?, so_cccd = ?, 
                tinh_thanh_pho = ?, phuong_xa = ?, dia_chi_chi_tiet = ?, 
                anh_dai_dien = ?, vai_tro = ?, trang_thai_tai_khoan = ?";
            
            $types = "sssssssssss"; 
            $params = [$email, $ho, $ten, $so_dien_thoai, $so_cccd, $tinh_thanh_pho, $phuong_xa, $dia_chi_chi_tiet, $ten_file_anh, $vai_tro, $trang_thai];

            if (!empty($mat_khau)) {
                $mat_khau_bam = password_hash($mat_khau, PASSWORD_DEFAULT);
                $sql_update .= ", mat_khau = ?";
                $types .= "s";
                $params[] = $mat_khau_bam;
            }
            
            $sql_update .= " WHERE id_nguoi_dung = ?";
            $types .= "i";
            $params[] = $id_nguoi_dung;

            $stmt = $conn->prepare($sql_update);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $thong_bao = "Cập nhật người dùng thành công!";
                $action = 'danh_sach';
                if ($ten_file_anh_moi && $anh_dai_dien_hien_tai) {
                    xoa_anh_cu($anh_dai_dien_hien_tai, $upload_dir_avatar);
                }
            } else {
                $thong_bao_loi = "Lỗi khi cập nhật: " . $stmt->error;
                $action = 'sua';
            }
        } else {
            // --- THÊM MỚI ---
            $mat_khau_bam = password_hash($mat_khau, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO nguoi_dung (
                                 email, ho, ten, so_dien_thoai, so_cccd, 
                                 tinh_thanh_pho, phuong_xa, dia_chi_chi_tiet, 
                                 anh_dai_dien, mat_khau, vai_tro, trang_thai_tai_khoan
                               ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql_insert);
            $stmt->bind_param("ssssssssssss", 
                $email, $ho, $ten, $so_dien_thoai, $so_cccd, 
                $tinh_thanh_pho, $phuong_xa, $dia_chi_chi_tiet, 
                $ten_file_anh, $mat_khau_bam, $vai_tro, $trang_thai);
            
            if ($stmt->execute()) {
                $thong_bao = "Thêm người dùng mới thành công!";
                $action = 'danh_sach';
            } else {
                $thong_bao_loi = "Lỗi khi thêm mới: " . $stmt->error;
                $action = 'them';
            }
        }
    }
}
// --- XỬ LÝ XÓA ---
elseif ($action == 'xoa' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    if ($id == $_SESSION['id_nguoi_dung']) {
        $thong_bao_loi = "Bạn không thể tự xóa chính mình!";
    } else {
        $sql_get_img = "SELECT anh_dai_dien FROM nguoi_dung WHERE id_nguoi_dung = ?";
        $stmt_img = $conn->prepare($sql_get_img);
        $stmt_img->bind_param("i", $id);
        $stmt_img->execute();
        $img_result = $stmt_img->get_result()->fetch_assoc();
        
        $sql_delete = "DELETE FROM nguoi_dung WHERE id_nguoi_dung = ?";
        $stmt = $conn->prepare($sql_delete);
        $stmt->bind_param("i", $id);
        
        if ($stmt->execute()) {
            $thong_bao = "Đã xóa người dùng thành công!";
            if ($img_result) {
                xoa_anh_cu($img_result['anh_dai_dien'], $upload_dir_avatar);
            }
        } else {
            $thong_bao_loi = "Lỗi khi xóa: " . $stmt->error;
        }
    }
    $action = 'danh_sach'; 
}

// --- XỬ LÝ CẤM/MỞ KHÓA (THAY ĐỔI TRẠNG THÁI) ---
elseif ($action == 'thay_doi_trang_thai' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $status = $_GET['status'] ?? '';
    
    // Đảm bảo không tự cấm/mở khóa chính mình và trạng thái hợp lệ
    if ($id != $_SESSION['id_nguoi_dung'] && in_array($status, ['hoat_dong', 'bi_cam'])) { 
        $sql = "UPDATE nguoi_dung SET trang_thai_tai_khoan = ? WHERE id_nguoi_dung = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
    }
    // Chuyển hướng về đúng tab và giữ nguyên bộ lọc
    $redirect_url = "quan_ly_nguoi_dung.php?action=danh_sach&tab=" . urlencode($tab_hien_tai) 
                  . "&search=" . urlencode($tu_khoa_tim_kiem) 
                  . "&search_tinh=" . urlencode($search_tinh) 
                  . "&search_phuong=" . urlencode($search_phuong) 
                  . "&search_trang_thai=" . urlencode($search_trang_thai);
                  
    header("Location: $redirect_url"); // <--- LỖI XẢY RA Ở ĐÂY
    exit();
}

// --- LOGIC LẤY DATA CHO FORM SỬA ---
elseif ($action == 'sua' && isset($_GET['id'])) {
    if (!$user_data) { // Chỉ load data từ DB nếu chưa có dữ liệu POST (tức là không bị lỗi form trước đó)
        $id = (int)$_GET['id'];
        $sql_get = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung = ?";
        $stmt = $conn->prepare($sql_get);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 1) {
            $user_data = $result->fetch_assoc();
        } else {
            $thong_bao_loi = "Không tìm thấy người dùng.";
            $action = 'danh_sach';
        }
    }
}

// 5. GỌI ĐẦU TRANG ADMIN
// (Tất cả logic có thể gây redirect đã chạy xong)
require 'dau_trang_quan_tri.php'; 

// 6. LOGIC LẤY DATA CHO DANH SÁCH (CHỈ SELECT)
if ($action == 'danh_sach') {
    $result_data = null;
    $params = [];
    $types = "";
    
    $sql_query_base = "SELECT id_nguoi_dung, ho, ten, email, so_dien_thoai, anh_dai_dien, trang_thai_tai_khoan, vai_tro, ngay_tao FROM nguoi_dung";
    
    $where_clauses = []; 
    if ($tab_hien_tai == 'quan_tri') {
        $where_clauses[] = "vai_tro = 'quan_tri'";
    } else {
        $where_clauses[] = "vai_tro = 'khach_hang'";
    }
    
    // (MỚI) Thêm logic tìm kiếm SĐT
    if (!empty($tu_khoa_tim_kiem)) {
        $where_clauses[] = "(CONCAT(ho, ' ', ten) LIKE ? OR email LIKE ? OR so_dien_thoai LIKE ?)";
        $search_term = "%" . $tu_khoa_tim_kiem . "%";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term; // Thêm 1
        $types .= "sss"; // Sửa thành "sss"
    }
    if (!empty($search_tinh)) {
        $where_clauses[] = "tinh_thanh_pho LIKE ?";
        $params[] = "%" . $search_tinh . "%";
        $types .= "s";
    }
    if (!empty($search_phuong)) {
        $where_clauses[] = "phuong_xa LIKE ?";
        $params[] = "%" . $search_phuong . "%";
        $types .= "s";
    }
    if (!empty($search_trang_thai)) {
        $where_clauses[] = "trang_thai_tai_khoan = ?";
        $params[] = $search_trang_thai;
        $types .= "s";
    }

    $sql_query = $sql_query_base . " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY ngay_tao DESC";
    
    $stmt_list = $conn->prepare($sql_query);
    if (!empty($params)) {
        $stmt_list->bind_param($types, ...$params);
    }
    $stmt_list->execute();
    $result_data = $stmt_list->get_result();
}

$vai_tro_mac_dinh = ($tab_hien_tai == 'quan_tri') ? 'quan_tri' : 'khach_hang';
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
    /* (MỚI) CSS Bộ lọc */
    .filter-container {
        width: 100%;
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        display: flex;
        gap: 15px;
        flex-wrap: wrap; 
        align-items: flex-end; 
        margin-bottom: 20px;
    }
    .filter-group {
        display: flex;
        flex-direction: column;
        min-width: 150px;
        flex-grow: 1;
    }
    .filter-group label {
        font-size: 0.85em;
        color: #555;
        margin-bottom: 5px;
        font-weight: bold;
    }
    .filter-group input[type="text"],
    .filter-group select {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1em;
    }
    .filter-actions {
        display: flex;
        align-items: flex-end; 
    }
    
    .btn-add-new { background-color: #28a745; }
    
    /* CSS cho bảng */
    .avatar-cell { width: 70px; text-align: center; }
    .avatar-cell img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #ddd;
    }
    .role-label { font-weight: bold; }
    .role-quan_tri { color: #e74c3c; } 
    .role-khach_hang { color: #3498db; } 
    /* === TRẠNG THÁI TÀI KHOẢN === */
.status-label {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: bold;
    font-size: 0.9em;
    text-transform: capitalize;
    letter-spacing: 0.3px;
}

/* Hoạt động */
.status-hoat-dong {
    background-color: #d4edda; /* xanh nhạt */
    color: #155724;            /* xanh đậm */
    border: 1px solid #c3e6cb;
}

/* Bị cấm */
.status-bi-cam {
    background-color: #f8d7da; /* đỏ nhạt */
    color: #721c24;            /* đỏ đậm */
    border: 1px solid #f5c6cb;
}

/* Chờ xác minh */
.status-cho-xac-minh {
    background-color: #fff3cd; /* vàng nhạt */
    color: #856404;            /* vàng đậm */
    border: 1px solid #ffeeba;
}

    /* (SỬA) CSS Cho Nút Sửa/Xóa (Giống hệt quản lý hãng) */
    .action-links a {
        text-decoration: none;
        margin-right: 12px;
        font-weight: bold;
        font-size: 0.9em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .action-links a i {
        width: 1.2em;
        text-align: center;
    }
    .action-links a.edit { color: #007bff; }
    .action-links a.delete { color: #dc3545; }
    .action-links a.ban { color: #dc3545; }
    .action-links a.unban { color: #28a745; } 
    .action-links a:hover { text-decoration: underline; }
    
    /* CSS cho Form */
    .form-container {
        background: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        max-width: 900px; 
        margin: 0 auto; 
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px 30px;
    }
    .form-group { margin-bottom: 0; }
    /* (MỚI) CSS Cho icon trong form */
    .form-group label {
        font-weight: bold;
        color: #333;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .form-group label i {
        color: #888;
        width: 1.2em;
        text-align: center;
    }
    .form-group input[type="text"],
    .form-group input[type="email"],
    .form-group input[type="password"],
    .form-group input[type="file"],
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        margin-top: 5px;
    }
    .form-group input[disabled], .form-group select[disabled] {
        background-color: #eee;
    }
    .form-group small { color: #777; font-size: 12px; }
    .form-actions {
        margin-top: 25px;
        padding-top: 20px;
        border-top: 1px solid #eee;
        display: flex;
        gap: 10px;
    }
    .avatar-preview {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #ddd;
        margin-bottom: 10px;
        display: block;
    }
</style>


<h1>Quản lý Người Dùng</h1>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<?php if ($action == 'danh_sach'): ?>
    
    <div class="page-header">
        <nav class="tab-menu" style="margin-bottom: 0;">
            <a href="?action=danh_sach&tab=khach_hang" class="<?php echo ($tab_hien_tai == 'khach_hang') ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> Khách hàng
            </a>
            <a href="?action=danh_sach&tab=quan_tri" class="<?php echo ($tab_hien_tai == 'quan_tri') ? 'active' : ''; ?>">
                <i class="fas fa-user-shield"></i> Quản trị
            </a>
        </nav>
        <a href="?action=them&tab=<?php echo $tab_hien_tai; ?>" class="btn btn-add-new">
            <i class="fas fa-plus"></i> Thêm Người Dùng
        </a>
    </div>
    
    <form action="quan_ly_nguoi_dung.php" method="GET" class="filter-container">
        <input type="hidden" name="action" value="danh_sach">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($tab_hien_tai); ?>">
        
        <div class="filter-group" style="flex-grow: 2;">
            <label for="search">Tìm theo Tên/Email/SĐT</label>
            <input type="text" id="search" name="search" placeholder="Tên, Email hoặc SĐT..." value="<?php echo htmlspecialchars($tu_khoa_tim_kiem); ?>">
        </div>
        <div class="filter-group">
            <label for="search_tinh">Tỉnh/Thành</label>
            <input type="text" id="search_tinh" name="search_tinh" placeholder="VD: Hà Nội" value="<?php echo htmlspecialchars($search_tinh); ?>">
        </div>
        <div class="filter-group">
            <label for="search_phuong">Phường/Xã</label>
            <input type="text" id="search_phuong" name="search_phuong" placeholder="VD: Bến Nghé" value="<?php echo htmlspecialchars($search_phuong); ?>">
        </div>
        <div class="filter-group">
            <label for="search_trang_thai">Trạng thái</label>
            <select id="search_trang_thai" name="search_trang_thai">
                <option value="">Tất cả</option>
                <option value="hoat_dong" <?php echo ($search_trang_thai == 'hoat_dong') ? 'selected' : ''; ?>>Hoạt động</option>
                <option value="bi_cam" <?php echo ($search_trang_thai == 'bi_cam') ? 'selected' : ''; ?>>Bị cấm</option>
                <option value="cho_xac_minh" <?php echo ($search_trang_thai == 'cho_xac_minh') ? 'selected' : ''; ?>>Chờ xác minh</option>
            </select>
        </div>
        <div class="filter-actions">
            <button type="submit" class="btn"><i class="fas fa-filter"></i> Lọc</button>
        </div>
    </form>

    <table class="admin-table">
        <thead>
            <tr>
                <th>Ảnh</th>
                <th>Họ Tên</th>
                <th>Email</th>
                <th>Số điện thoại</th>
                <th>Vai trò</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result_data && $result_data->num_rows > 0): ?>
                <?php while($user = $result_data->fetch_assoc()): ?>
                    <tr>
                        <td class="avatar-cell">
                            <?php 
                            $anh_path = '../tai_len/avatars/' . ($user['anh_dai_dien'] ?? 'default-avatar.png');
                            if (empty($user['anh_dai_dien']) || !file_exists($anh_path)) {
                                $anh_path = '../tai_len/avatars/default-avatar.png'; 
                            }
                            ?>
                            <img src="<?php echo $anh_path; ?>" alt="Avatar">
                        </td>
                        <td><?php echo htmlspecialchars($user['ho'] . ' ' . $user['ten']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['so_dien_thoai']); ?></td>
                        <td>
                            <span class="role-label role-<?php echo $user['vai_tro']; ?>">
                                <?php echo ($user['vai_tro'] == 'quan_tri') ? 'Quản Trị' : 'Khách Hàng'; ?>
                            </span>
                        </td>
                        <td>
                            <span class="status-label status-<?php echo str_replace('_', '-', $user['trang_thai_tai_khoan']); ?>">
                                <?php echo str_replace('_', ' ', $user['trang_thai_tai_khoan']); ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <a href="?action=sua&id=<?php echo $user['id_nguoi_dung']; ?>" class="edit" title="Sửa">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <?php if ($user['id_nguoi_dung'] != $_SESSION['id_nguoi_dung']): ?>
                                
                                <?php 
                                // Tạo chuỗi query cho redirect để giữ lại bộ lọc
                                $query_params = http_build_query([
                                    'search' => $tu_khoa_tim_kiem, 
                                    'search_tinh' => $search_tinh, 
                                    'search_phuong' => $search_phuong, 
                                    'search_trang_thai' => $search_trang_thai
                                ]);
                                ?>
                                
                                <?php if ($user['trang_thai_tai_khoan'] == 'hoat_dong' || $user['trang_thai_tai_khoan'] == 'cho_xac_minh'): ?>
                                    <a href="quan_ly_nguoi_dung.php?action=thay_doi_trang_thai&status=bi_cam&id=<?php echo $user['id_nguoi_dung']; ?>&tab=<?php echo $tab_hien_tai; ?>&<?php echo $query_params; ?>" 
                                       class="ban" title="Cấm tài khoản"
                                       onclick="return confirm('Bạn có chắc muốn CẤM tài khoản này?');">
                                        <i class="fas fa-user-lock"></i> Cấm
                                    </a>
                                <?php elseif ($user['trang_thai_tai_khoan'] == 'bi_cam'): ?>
                                    <a href="quan_ly_nguoi_dung.php?action=thay_doi_trang_thai&status=hoat_dong&id=<?php echo $user['id_nguoi_dung']; ?>&tab=<?php echo $tab_hien_tai; ?>&<?php echo $query_params; ?>" 
                                       class="unban" title="Mở khóa tài khoản"
                                       onclick="return confirm('Bạn có chắc muốn MỞ KHÓA tài khoản này?');">
                                        <i class="fas fa-user-check"></i> Mở khóa
                                    </a>
                                <?php endif; ?>

                                <a href="?action=xoa&id=<?php echo $user['id_nguoi_dung']; ?>&tab=<?php echo $tab_hien_tai; ?>" 
                                   class="delete" title="Xóa vĩnh viễn"
                                   onclick="return confirm('Bạn có chắc chắn muốn XÓA vĩnh viễn người dùng này?');">
                                   <i class="fas fa-trash-alt"></i> Xóa
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="7" style="text-align: center;">
                        <?php echo !empty($tu_khoa_tim_kiem) || !empty($search_tinh) || !empty($search_phuong) || !empty($search_trang_thai) ? 'Không tìm thấy người dùng nào khớp.' : 'Không có dữ liệu.'; ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action == 'them' || $action == 'sua'): ?>
    <?php
        // Đổ dữ liệu từ $user_data (nếu có lỗi POST hoặc là chế độ Sửa)
        $is_editing = ($action == 'sua' && $user_data);
        $edit_id = $user_data['id_nguoi_dung'] ?? null;
        $edit_ho = $user_data['ho'] ?? '';
        $edit_ten = $user_data['ten'] ?? '';
        $edit_email = $user_data['email'] ?? '';
        $edit_sdt = $user_data['so_dien_thoai'] ?? '';
        $edit_cccd = $user_data['so_cccd'] ?? '';
        $edit_tinh = $user_data['tinh_thanh_pho'] ?? '';
        $edit_phuong = $user_data['phuong_xa'] ?? '';
        $edit_dia_chi = $user_data['dia_chi_chi_tiet'] ?? '';
        $edit_anh = $user_data['anh_dai_dien'] ?? '';
        $edit_vai_tro = $user_data['vai_tro'] ?? $vai_tro_mac_dinh;
        $edit_trang_thai = $user_data['trang_thai_tai_khoan'] ?? 'hoat_dong';
        $is_editing_self = ($is_editing && $edit_id == $_SESSION['id_nguoi_dung']);
    ?>
    <h1><?php echo $is_editing ? 'Sửa Người Dùng' : 'Thêm Người Dùng Mới'; ?></h1>
    <div class="form-container">
        <form action="quan_ly_nguoi_dung.php?tab=<?php echo $tab_hien_tai; ?>" method="POST" enctype="multipart/form-data">
            <?php if ($is_editing): ?>
                <input type="hidden" name="id_nguoi_dung" value="<?php echo htmlspecialchars($edit_id); ?>">
                <input type="hidden" name="anh_dai_dien_hien_tai" value="<?php echo htmlspecialchars($edit_anh); ?>">
            <?php endif; ?>
            
            <div class="form-grid">
                <div>
                    <div class="form-group">
                        <label for="email"><i class="fas fa-envelope"></i> Email (*)</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($edit_email); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="mat_khau"><i class="fas fa-key"></i> Mật khẩu</label>
                        <input type="password" id="mat_khau" name="mat_khau" placeholder="<?php echo $is_editing ? 'Bỏ trống nếu không đổi' : 'Bắt buộc'; ?>">
                    </div>
                    <div class="form-group">
                        <label for="ho"><i class="fas fa-user"></i> Họ</label>
                        <input type="text" id="ho" name="ho" value="<?php echo htmlspecialchars($edit_ho); ?>">
                    </div>
                    <div class="form-group">
                        <label for="ten"><i class="fas fa-user"></i> Tên (*)</label>
                        <input type="text" id="ten" name="ten" value="<?php echo htmlspecialchars($edit_ten); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="so_dien_thoai"><i class="fas fa-phone"></i> Số điện thoại</label>
                        <input type="text" id="so_dien_thoai" name="so_dien_thoai" value="<?php echo htmlspecialchars($edit_sdt); ?>">
                    </div>
                    <div class="form-group">
                        <label for="so_cccd"><i class="fas fa-id-card"></i> Số CCCD</label>
                        <input type="text" id="so_cccd" name="so_cccd" value="<?php echo htmlspecialchars($edit_cccd); ?>">
                    </div>
                </div>
                <div>
                    <div class="form-group">
                        <label for="anh_dai_dien"><i class="fas fa-image"></i> Ảnh đại diện</label>
                        <?php 
                            $anh_preview_path = (
                                $is_editing && !empty($edit_anh) && file_exists('../tai_len/avatars/' . $edit_anh)
                            ) 
                            ? '../tai_len/avatars/' . $edit_anh 
                            : '../tai_len/avatars/default-avatar.png';
                        ?>
                        <img src="<?php echo $anh_preview_path; ?>" alt="Ảnh hiện tại" class="avatar-preview">
                        <input type="file" id="anh_dai_dien" name="anh_dai_dien">
                    </div>
                    <div class="form-group">
                        <label for="vai_tro"><i class="fas fa-user-shield"></i> Vai trò (*)</label>
                        <select id="vai_tro" name="vai_tro" <?php echo $is_editing_self ? 'disabled' : ''; ?>>
                            <option value="khach_hang" <?php echo ($edit_vai_tro == 'khach_hang') ? 'selected' : ''; ?>>Khách hàng</option>
                            <option value="quan_tri" <?php echo ($edit_vai_tro == 'quan_tri') ? 'selected' : ''; ?>>Quản trị</option>
                        </select>
                        <?php if ($is_editing_self): ?>
                            <input type="hidden" name="vai_tro" value="<?php echo htmlspecialchars($edit_vai_tro); ?>">
                        <?php endif; ?>
                    </div>
                    <div class="form-group">
                        <label for="trang_thai"><i class="fas fa-toggle-on"></i> Trạng thái (*)</label>
                        <select id="trang_thai" name="trang_thai" <?php echo $is_editing_self ? 'disabled' : ''; ?>>
                            <option value="hoat_dong" <?php echo ($edit_trang_thai == 'hoat_dong') ? 'selected' : ''; ?>>Hoạt động</option>
                            <option value="cho_xac_minh" <?php echo ($edit_trang_thai == 'cho_xac_minh') ? 'selected' : ''; ?>>Chờ xác minh</option>
                            <option value="bi_cam" <?php echo ($edit_trang_thai == 'bi_cam') ? 'selected' : ''; ?>>Bị cấm</option>
                        </select>
                         <?php if ($is_editing_self): ?>
                            <input type="hidden" name="trang_thai" value="<?php echo htmlspecialchars($edit_trang_thai); ?>">
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-group" style="grid-column: 1 / -1;">
                    <label><i class="fas fa-map-marker-alt"></i> Địa chỉ</label>
                    <div class="form-grid" style="grid-template-columns: 1fr 1fr 2fr; gap: 10px;">
                        <input type="text" name="tinh_thanh_pho" placeholder="Tỉnh/Thành phố" value="<?php echo htmlspecialchars($edit_tinh); ?>">
                        <input type="text" name="phuong_xa" placeholder="Phường/Xã" value="<?php echo htmlspecialchars($edit_phuong); ?>">
                        <input type="text" name="dia_chi_chi_tiet" placeholder="Số nhà, tên đường" value="<?php echo htmlspecialchars($edit_dia_chi); ?>">
                    </div>
                </div>
            </div> 
            
            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $is_editing ? 'Cập Nhật' : 'Lưu (Thêm mới)'; ?>
                </button>
                <a href="?action=danh_sach&tab=<?php echo $tab_hien_tai; ?>" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy Bỏ
                </a>
            </div>
        </form>
    </div>
<?php endif; ?>

<?php require 'cuoi_trang_quan_tri.php'; ?>