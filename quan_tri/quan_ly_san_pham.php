<?php
// 1. ĐẶT TIÊU ĐỀ VÀ GỌI HEADER ADMIN
$page_title = "Quản lý Sản phẩm";
require 'dau_trang_quan_tri.php'; 
// (dau_trang_quan_tri.php đã gọi session_start(), kiem_tra_quan_tri.php, ket_noi_csdl.php,
// và định nghĩa ROOT_PATH, BASE_URL)

// 2. KHỞI TẠO BIẾN (Từ code gốc của bạn)
$thong_bao = "";       
$thong_bao_loi = "";   
$action = $_GET['action'] ?? 'danh_sach'; 
$product_data = null; 

// 3. HÀM HỖ TRỢ TẢI LÊN HÌNH ẢNH (Từ code gốc của bạn)
function xu_ly_tai_anh_san_pham($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ten_file_moi = uniqid('sp_', true) . '.' . $file_ext;
            $duong_dan_dich = $upload_dir . $ten_file_moi;
            
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}

// 4. XỬ LÝ LOGIC (CONTROLLER) (Từ code gốc của bạn)

// --- 4.1. XỬ LÝ POST (THÊM / SỬA) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $conn->begin_transaction();
    try {
        // --- Lấy dữ liệu Bảng 'san_pham' ---
        $id_san_pham = $_POST['id_san_pham'] ?? null;
        $ten_san_pham = $conn->real_escape_string($_POST['ten_san_pham']);
        $id_hang = (int)$_POST['id_hang'];
        $ma_san_pham = $conn->real_escape_string($_POST['ma_san_pham']);
        $mau_sac = $conn->real_escape_string($_POST['mau_sac']);
        
        $gia_ban = (float)$_POST['gia_ban'];
        $gia_goc = !empty($_POST['gia_goc']) ? (float)$_POST['gia_goc'] : null; 
        
        $phan_tram_giam_gia = !empty($_POST['phan_tram_giam_gia']) ? (int)$_POST['phan_tram_giam_gia'] : null;
        $ngay_bat_dau_giam = !empty($_POST['ngay_bat_dau_giam']) ? $conn->real_escape_string($_POST['ngay_bat_dau_giam']) : null;
        $ngay_ket_thuc_giam = !empty($_POST['ngay_ket_thuc_giam']) ? $conn->real_escape_string($_POST['ngay_ket_thuc_giam']) : null;
        
        $so_luong_ton = (int)$_POST['so_luong_ton'];
        $mo_ta_ngan = $conn->real_escape_string($_POST['mo_ta_ngan']);
        $mo_ta_chi_tiet = $conn->real_escape_string($_POST['mo_ta_chi_tiet']);
        $trang_thai = $conn->real_escape_string($_POST['trang_thai']);
        
        // --- Lấy dữ liệu Bảng 'thong_so_ky_thuat' ---
        $man_hinh = $conn->real_escape_string($_POST['man_hinh']);
        $do_phan_giai = $conn->real_escape_string($_POST['do_phan_giai']);
        $tan_so_quet = $conn->real_escape_string($_POST['tan_so_quet']);
        $chip_xu_ly = $conn->real_escape_string($_POST['chip_xu_ly']);
        $gpu = $conn->real_escape_string($_POST['gpu']);
        $ram = $conn->real_escape_string($_POST['ram']);
        $rom = $conn->real_escape_string($_POST['rom']);
        $he_dieu_hanh = $conn->real_escape_string($_POST['he_dieu_hanh']);
        $camera_sau = $conn->real_escape_string($_POST['camera_sau']);
        $camera_truoc = $conn->real_escape_string($_POST['camera_truoc']);
        $dung_luong_pin = $conn->real_escape_string($_POST['dung_luong_pin']);
        $sac = $conn->real_escape_string($_POST['sac']);
        $ket_noi = $conn->real_escape_string($_POST['ket_noi']);
        $sim = $conn->real_escape_string($_POST['sim']);
        $trong_luong = $conn->real_escape_string($_POST['trong_luong']);
        $chat_lieu = $conn->real_escape_string($_POST['chat_lieu']);
        $khang_nuoc_bui = $conn->real_escape_string($_POST['khang_nuoc_bui']);
        $bao_mat = $conn->real_escape_string($_POST['bao_mat']);

        // --- Xử lý 5 file ảnh ---
        $dir_main = ROOT_PATH . 'tai_len/san_pham/'; // (SỬA) Dùng ROOT_PATH
        $dir_gallery = ROOT_PATH . 'tai_len/san_pham/gallery/'; // (SỬA) Dùng ROOT_PATH
        
        $anh_dai_dien_hien_tai = $_POST['anh_dai_dien_hien_tai'] ?? '';
        $anh_phu_1_hien_tai = $_POST['anh_phu_1_hien_tai'] ?? '';
        $anh_phu_2_hien_tai = $_POST['anh_phu_2_hien_tai'] ?? '';
        $anh_phu_3_hien_tai = $_POST['anh_phu_3_hien_tai'] ?? '';
        $anh_phu_4_hien_tai = $_POST['anh_phu_4_hien_tai'] ?? '';
        
        $ten_anh_dai_dien = xu_ly_tai_anh_san_pham('anh_dai_dien', $dir_main) ?? $anh_dai_dien_hien_tai;
        $ten_anh_phu_1 = xu_ly_tai_anh_san_pham('anh_phu_1', $dir_gallery) ?? $anh_phu_1_hien_tai;
        $ten_anh_phu_2 = xu_ly_tai_anh_san_pham('anh_phu_2', $dir_gallery) ?? $anh_phu_2_hien_tai;
        $ten_anh_phu_3 = xu_ly_tai_anh_san_pham('anh_phu_3', $dir_gallery) ?? $anh_phu_3_hien_tai;
        $ten_anh_phu_4 = xu_ly_tai_anh_san_pham('anh_phu_4', $dir_gallery) ?? $anh_phu_4_hien_tai;
        
        if ($id_san_pham) {
            // --- LOGIC CẬP NHẬT (SỬA) ---
            $sql_sp = "UPDATE san_pham SET 
                ten_san_pham=?, id_hang=?, ma_san_pham=?, mau_sac=?, gia_ban=?, gia_goc=?, 
                phan_tram_giam_gia=?, ngay_bat_dau_giam=?, ngay_ket_thuc_giam=?,
                so_luong_ton=?, anh_dai_dien=?, mo_ta_ngan=?, mo_ta_chi_tiet=?, trang_thai=?
                WHERE id=?";
            $stmt_sp = $conn->prepare($sql_sp);
            $stmt_sp->bind_param("sisssdississssi", 
                $ten_san_pham, $id_hang, $ma_san_pham, $mau_sac, $gia_ban, $gia_goc,
                $phan_tram_giam_gia, $ngay_bat_dau_giam, $ngay_ket_thuc_giam,
                $so_luong_ton, $ten_anh_dai_dien, $mo_ta_ngan, $mo_ta_chi_tiet, $trang_thai, $id_san_pham
            );
            if (!$stmt_sp->execute()) throw new Exception("Lỗi cập nhật sản phẩm: " . $stmt_sp->error);

            $sql_ts = "UPDATE thong_so_ky_thuat SET
                man_hinh=?, do_phan_giai=?, tan_so_quet=?, chip_xu_ly=?, gpu=?, ram=?, rom=?, 
                he_dieu_hanh=?, camera_sau=?, camera_truoc=?, dung_luong_pin=?, sac=?, ket_noi=?, 
                sim=?, trong_luong=?, chat_lieu=?, khang_nuoc_bui=?, bao_mat=?,
                anh_phu_1=?, anh_phu_2=?, anh_phu_3=?, anh_phu_4=?
                WHERE id_san_pham=?";
            $stmt_ts = $conn->prepare($sql_ts);
            $stmt_ts->bind_param("ssssssssssssssssssssssi",
                $man_hinh, $do_phan_giai, $tan_so_quet, $chip_xu_ly, $gpu, $ram, $rom, 
                $he_dieu_hanh, $camera_sau, $camera_truoc, $dung_luong_pin, $sac, $ket_noi, 
                $sim, $trong_luong, $chat_lieu, $khang_nuoc_bui, $bao_mat,
                $ten_anh_phu_1, $ten_anh_phu_2, $ten_anh_phu_3, $ten_anh_phu_4, $id_san_pham
            );
            if (!$stmt_ts->execute()) throw new Exception("Lỗi cập nhật thông số: " . $stmt_ts->error);
            
            $thong_bao = "Cập nhật sản phẩm thành công!";
            
        } else {
            // --- LOGIC THÊM MỚI ---
            $sql_sp = "INSERT INTO san_pham (
                ten_san_pham, id_hang, ma_san_pham, mau_sac, gia_ban, gia_goc,
                phan_tram_giam_gia, ngay_bat_dau_giam, ngay_ket_thuc_giam,
                so_luong_ton, anh_dai_dien, mo_ta_ngan, mo_ta_chi_tiet, trang_thai
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_sp = $conn->prepare($sql_sp);
            $stmt_sp->bind_param("sisssdississss",
                $ten_san_pham, $id_hang, $ma_san_pham, $mau_sac, $gia_ban, $gia_goc,
                $phan_tram_giam_gia, $ngay_bat_dau_giam, $ngay_ket_thuc_giam,
                $so_luong_ton, $ten_anh_dai_dien, $mo_ta_ngan, $mo_ta_chi_tiet, $trang_thai
            );
            if (!$stmt_sp->execute()) throw new Exception("Lỗi thêm sản phẩm: " . $stmt_sp->error);
            
            $new_product_id = $conn->insert_id;
            
            $sql_ts = "INSERT INTO thong_so_ky_thuat (
                id_san_pham, man_hinh, do_phan_giai, tan_so_quet, chip_xu_ly, gpu, ram, rom, 
                he_dieu_hanh, camera_sau, camera_truoc, dung_luong_pin, sac, ket_noi, 
                sim, trong_luong, chat_lieu, khang_nuoc_bui, bao_mat,
                anh_phu_1, anh_phu_2, anh_phu_3, anh_phu_4
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_ts = $conn->prepare($sql_ts);
            $stmt_ts->bind_param("issssssssssssssssssssss",
                $new_product_id, $man_hinh, $do_phan_giai, $tan_so_quet, $chip_xu_ly, $gpu, $ram, $rom, 
                $he_dieu_hanh, $camera_sau, $camera_truoc, $dung_luong_pin, $sac, $ket_noi, 
                $sim, $trong_luong, $chat_lieu, $khang_nuoc_bui, $bao_mat,
                $ten_anh_phu_1, $ten_anh_phu_2, $ten_anh_phu_3, $ten_anh_phu_4
            );
            if (!$stmt_ts->execute()) throw new Exception("Lỗi thêm thông số: " . $stmt_ts->error);
            
            $thong_bao = "Thêm sản phẩm mới thành công!";
        }
        
        $conn->commit();
        $action = 'danh_sach';
        
    } catch (Exception $e) {
        $conn->rollback();
        $thong_bao_loi = "Đã xảy ra lỗi: " . $e->getMessage();
        $action = $id_san_pham ? 'sua' : 'them';
        $product_data = $_POST; 
    }
}

// --- 4.2. XỬ LÝ GET (Xóa) ---
if ($action == 'xoa' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    
    $sql_get_images = "SELECT s.anh_dai_dien, ts.anh_phu_1, ts.anh_phu_2, ts.anh_phu_3, ts.anh_phu_4
                       FROM san_pham s
                       LEFT JOIN thong_so_ky_thuat ts ON s.id = ts.id_san_pham
                       WHERE s.id = ?";
    $stmt_img = $conn->prepare($sql_get_images);
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $images = $stmt_img->get_result()->fetch_assoc();
    
    $sql_delete = "DELETE FROM san_pham WHERE id = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $thong_bao = "Đã xóa sản phẩm thành công!";
        if ($images) {
            // (SỬA) Dùng ROOT_PATH
            if ($images['anh_dai_dien']) @unlink(ROOT_PATH . 'tai_len/san_pham/' . $images['anh_dai_dien']);
            if ($images['anh_phu_1']) @unlink(ROOT_PATH . 'tai_len/san_pham/gallery/' . $images['anh_phu_1']);
            if ($images['anh_phu_2']) @unlink(ROOT_PATH . 'tai_len/san_pham/gallery/' . $images['anh_phu_2']);
            if ($images['anh_phu_3']) @unlink(ROOT_PATH . 'tai_len/san_pham/gallery/' . $images['anh_phu_3']);
            if ($images['anh_phu_4']) @unlink(ROOT_PATH . 'tai_len/san_pham/gallery/' . $images['anh_phu_4']);
        }
    } else {
        $thong_bao_loi = "Lỗi khi xóa: " . $stmt->error;
    }
    $action = 'danh_sach'; 
}

// --- 4.3. XỬ LÝ GET (Lấy dữ liệu cho form Sửa) ---
if ($action == 'sua' && isset($_GET['id'])) {
    if (!$product_data) { 
        $id = (int)$_GET['id'];
        $sql_get = "SELECT * FROM san_pham s
                    LEFT JOIN thong_so_ky_thuat ts ON s.id = ts.id_san_pham
                    WHERE s.id = ?";
        $stmt = $conn->prepare($sql_get);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $product_data = $result->fetch_assoc();
            $product_data['id_san_pham'] = $product_data['id']; 
        } else {
            $thong_bao_loi = "Không tìm thấy sản phẩm.";
            $action = 'danh_sach';
        }
    }
}

// --- 4.4. LẤY DỮ LIỆU CHO DANH SÁCH (action = 'danh_sach') ---
$list_san_pham = [];
$distinct_specs = [];
$search_params_get = []; 

if ($action == 'danh_sach') {
    
    // (Logic lấy dữ liệu lọc động giữ nguyên)
    $spec_columns = [
        'man_hinh', 'do_phan_giai', 'tan_so_quet', 'chip_xu_ly', 'gpu', 'ram', 'rom', 
        'he_dieu_hanh', 'camera_sau', 'camera_truoc', 'dung_luong_pin', 'sac', 'ket_noi', 
        'sim', 'trong_luong', 'chat_lieu', 'khang_nuoc_bui', 'bao_mat'
    ];
    foreach ($spec_columns as $col) {
        $distinct_specs[$col] = [];
        $sql_distinct = "SELECT DISTINCT `$col` FROM thong_so_ky_thuat WHERE `$col` IS NOT NULL AND `$col` != '' ORDER BY `$col` ASC";
        $result_distinct = $conn->query($sql_distinct);
        if ($result_distinct) {
            while ($row_distinct = $result_distinct->fetch_assoc()) {
                $distinct_specs[$col][] = $row_distinct[$col];
            }
        }
    }
    
    // (Logic xử lý dữ liệu tìm kiếm giữ nguyên)
    $sql_list = "SELECT s.id, s.ten_san_pham, s.anh_dai_dien, h.ten_hang, s.mau_sac, s.gia_ban, s.so_luong_ton, s.trang_thai 
                 FROM san_pham s 
                 LEFT JOIN hang_san_xuat h ON s.id_hang = h.id_hang 
                 LEFT JOIN thong_so_ky_thuat ts ON s.id = ts.id_san_pham";
                 
    $where_clauses = [];
    $params = [];
    $param_types = "";
    
    if (!empty($_GET['search_keyword'])) {
        $kw = "%" . $conn->real_escape_string($_GET['search_keyword']) . "%";
        $where_clauses[] = "(s.ten_san_pham LIKE ? OR s.ma_san_pham LIKE ?)";
        $params[] = $kw; $params[] = $kw; $param_types .= "ss";
        $search_params_get['search_keyword'] = $_GET['search_keyword'];
    }
    if (!empty($_GET['search_hang'])) {
        $where_clauses[] = "s.id_hang = ?";
        $params[] = (int)$_GET['search_hang']; $param_types .= "i";
        $search_params_get['search_hang'] = (int)$_GET['search_hang'];
    }
    if (!empty($_GET['search_trang_thai'])) {
        $where_clauses[] = "s.trang_thai = ?";
        $params[] = $conn->real_escape_string($_GET['search_trang_thai']); $param_types .= "s";
        $search_params_get['search_trang_thai'] = $_GET['search_trang_thai'];
    }
    if (!empty($_GET['search_min_price'])) {
        $where_clauses[] = "s.gia_ban >= ?";
        $params[] = (float)$_GET['search_min_price']; $param_types .= "d";
        $search_params_get['search_min_price'] = (float)$_GET['search_min_price'];
    }
    if (!empty($_GET['search_max_price'])) {
        $where_clauses[] = "s.gia_ban <= ?";
        $params[] = (float)$_GET['search_max_price']; $param_types .= "d";
        $search_params_get['search_max_price'] = (float)$_GET['search_max_price'];
    }
    foreach ($spec_columns as $col) {
        $get_key = "search_" . $col;
        if (!empty($_GET[$get_key])) {
            $value = $conn->real_escape_string($_GET[$get_key]);
            $where_clauses[] = "ts.`$col` = ?";
            $params[] = $value; $param_types .= "s";
            $search_params_get[$get_key] = $value;
        }
    }
    
    if (!empty($where_clauses)) {
        $sql_list .= " WHERE " . implode(" AND ", $where_clauses);
    }
    $sql_list .= " ORDER BY s.ngay_cap_nhat DESC";

    $stmt_list = $conn->prepare($sql_list);
    if (!empty($params)) {
        $stmt_list->bind_param($param_types, ...$params);
    }
    $stmt_list->execute();
    $result_data = $stmt_list->get_result();
    if ($result_data) {
        while ($row = $result_data->fetch_assoc()) {
            $list_san_pham[] = $row;
        }
    } else {
        $thong_bao_loi = "Lỗi khi tìm kiếm: " . $conn->error;
    }
}

// --- 4.5. LẤY DANH SÁCH HÃNG (cho form Thêm/Sửa) ---
$hang_list = [];
$result_hang = $conn->query("SELECT id_hang, ten_hang FROM hang_san_xuat WHERE trang_thai = 'hien_thi' ORDER BY ten_hang");
if ($result_hang) {
    while ($row = $result_hang->fetch_assoc()) {
        $hang_list[] = $row;
    }
}
?>

<style>
    /* Form chung */
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; color: #333; }
        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="file"],
        .form-group input[type="date"],
        .form-group input[type="number"], /* === THÊM DÒNG NÀY === */
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            box-sizing: border-box; /* Thêm box-sizing */
        }
        .form-group input[disabled], .form-group select[disabled] { background-color: #eee; }
    /* (Xóa CSS chung, chỉ giữ lại CSS riêng) */
    .search-form-container { 
        background-color: #fff; 
        padding: 20px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
    }
    .search-form { 
        display: flex; 
        flex-wrap: wrap; 
        gap: 15px 20px; 
    }
    .search-form h4 { 
        width: 100%; 
        margin: 0 0 5px 0; 
        color: #555; 
        font-size: 1rem; 
        border-bottom: 1px solid #eee; 
        padding-bottom: 5px; 
    }
    .search-group { 
        flex: 1 1 200px; 
        min-width: 200px; 
    }
    .search-group label { 
        display: block; 
        margin-bottom: 5px; 
        font-weight: 600; 
        font-size: 0.9rem; 
    }
    .search-group input[type="text"],
    .search-group input[type="number"],
    .search-group select { 
        width: 100%; 
        padding: 8px; 
        border: 1px solid #ccc; 
        border-radius: 4px; 
        font-size: 0.9rem; 
    }
    .search-actions { 
        width: 100%; 
        display: flex; 
        gap: 10px; 
        padding-top: 10px; 
        margin-top: 10px; 
        border-top: 1px solid #eee; 
    }

    /* CSS Bảng */
    .image-cell { width: 100px; text-align: center; }
    .image-cell img { 
        max-width: 80px; 
        max-height: 80px; 
        object-fit: contain; 
        border-radius: 5px; 
        border: 1px solid #eee;
    }
    .status-hiện { background-color: #28a745; }
    .status-ẩn { background-color: #6c757d; }
    .status-hết_hàng { background-color: #dc3545; }
    
    /* CSS Nút hành động (đã có trong CSS chung) */
    /* .action-links a { ... } */

    /* CSS Form Thêm/Sửa */
    .form-container { 
        background: #fff; 
        padding: 25px; 
        border-radius: 8px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.1); 
    }
    .form-fieldset { 
        border: 1px solid #ddd; 
        border-radius: 8px; 
        padding: 20px; 
        margin-bottom: 20px; 
    }
    .form-fieldset legend { 
        font-size: 1.1em; 
        font-weight: bold; 
        color: #2c3e50; 
        padding: 0 10px; 
    }
    
    .form-grid, .form-grid-price { 
        display: grid; 
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px; 
    }
    .form-grid-gallery {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
    }

    .form-group { margin-bottom: 0; } /* Bỏ margin-bottom vì đã có gap */
    .form-group.full-width { grid-column: 1 / -1; }
    .form-group label { 
        /* Label đã có trong CSS chung */
    }
    .form-group textarea { 
        min-height: 100px; 
        resize: vertical; 
    }
    .form-group textarea.large { 
        min-height: 250px; 
    }
    .form-actions { 
        margin-top: 20px; 
        display: flex; 
        gap: 10px; 
    }
    .image-preview { 
        max-width: 150px; 
        max-height: 150px; 
        object-fit: contain; 
        border: 1px solid #ddd; 
        margin-bottom: 10px; 
        display: block; 
        border-radius: 5px;
    }
</style>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<?php if ($action == 'danh_sach'): ?>
    
    <div class="page-header">
        <h1>Quản lý Sản phẩm</h1>
        <a href="?action=them" class="btn btn-success"><i class="fas fa-plus"></i> Thêm Sản Phẩm Mới</a>
    </div>

    <div class="search-form-container">
        <form action="quan_ly_san_pham.php" method="GET" class="search-form">
            <input type="hidden" name="action" value="danh_sach">
            
            <div class="search-group">
                <label for="search_keyword">Từ khóa (Tên, Mã SP):</label>
                <input type="text" id="search_keyword" name="search_keyword" value="<?php echo htmlspecialchars($search_params_get['search_keyword'] ?? ''); ?>">
            </div>
            <div class="search-group">
                <label for="search_hang">Hãng:</label>
                <select id="search_hang" name="search_hang">
                    <option value="">-- Tất cả hãng --</option>
                    <?php foreach ($hang_list as $hang): ?>
                        <option value="<?php echo $hang['id_hang']; ?>" <?php echo (($search_params_get['search_hang'] ?? 0) == $hang['id_hang']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($hang['ten_hang']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="search-group">
                <label for="search_trang_thai">Trạng thái:</label>
                <select id="search_trang_thai" name="search_trang_thai">
                    <option value="">-- Tất cả --</option>
                    <option value="hiện" <?php echo (($search_params_get['search_trang_thai'] ?? '') == 'hiện') ? 'selected' : ''; ?>>Hiện</option>
                    <option value="ẩn" <?php echo (($search_params_get['search_trang_thai'] ?? '') == 'ẩn') ? 'selected' : ''; ?>>Ẩn</option>
                    <option value="hết hàng" <?php echo (($search_params_get['search_trang_thai'] ?? '') == 'hết hàng') ? 'selected' : ''; ?>>Hết hàng</option>
                </select>
            </div>
            <div class="search-group">
                <label for="search_min_price">Giá từ (VNĐ):</label>
                <input type="number" id="search_min_price" name="search_min_price" value="<?php echo htmlspecialchars($search_params_get['search_min_price'] ?? ''); ?>" placeholder="0">
            </div>
            <div class="search-group">
                <label for="search_max_price">Giá đến (VNĐ):</label>
                <input type="number" id="search_max_price" name="search_max_price" value="<?php echo htmlspecialchars($search_params_get['search_max_price'] ?? ''); ?>" placeholder="100000000">
            </div>
            
            <h4>Lọc theo Thông số:</h4>
            
            <?php 
            $spec_labels = [
                'man_hinh' => 'Màn hình', 'do_phan_giai' => 'ĐPG', 'tan_so_quet' => 'TS Quét',
                'chip_xu_ly' => 'Chip', 'gpu' => 'GPU', 'ram' => 'RAM', 'rom' => 'ROM',
                'he_dieu_hanh' => 'HĐH', 'camera_sau' => 'Cam Sau', 'camera_truoc' => 'Cam Trước',
                'dung_luong_pin' => 'Pin', 'sac' => 'Sạc', 'ket_noi' => 'Kết nối',
                'sim' => 'SIM', 'trong_luong' => 'Tr.Lượng', 'chat_lieu' => 'Chất liệu',
                'khang_nuoc_bui' => 'Kháng nước', 'bao_mat' => 'Bảo mật'
            ];
            
            foreach ($spec_columns as $col): 
                $get_key = "search_" . $col;
                $current_val = $search_params_get[$get_key] ?? '';
                $label = $spec_labels[$col] ?? $col; 
            ?>
                <div class="search-group">
                    <label for="<?php echo $get_key; ?>"><?php echo $label; ?>:</label>
                    <select id="<?php echo $get_key; ?>" name="<?php echo $get_key; ?>">
                        <option value="">-- Chọn --</option>
                        <?php foreach ($distinct_specs[$col] as $value): ?>
                            <option value="<?php echo htmlspecialchars($value); ?>" <?php echo ($current_val == $value) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($value); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endforeach; ?>

            <div class="search-actions">
                <button type="submit" class="btn"><i class="fas fa-filter"></i> Tìm kiếm</button>
                <a href="quan_ly_san_pham.php?action=danh_sach" class="btn btn-secondary">Xóa lọc</a>
            </div>
        </form>
    </div>

    <table class="admin-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Ảnh</th>
                <th>Tên Sản Phẩm</th>
                <th>Hãng</th>
                <th>Giá Bán</th>
                <th>Tồn Kho</th>
                <th>Trạng Thái</th>
                <th>Hành Động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($list_san_pham)): ?>
                <tr>
                    <td colspan="8" style="text-align: center;">Không tìm thấy sản phẩm nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach($list_san_pham as $sp): ?>
                    <tr>
                        <td><?php echo $sp['id']; ?></td>
                        <td class="image-cell">
                            <?php 
                            // (SỬA) Dùng ROOT_PATH và BASE_URL
                            $anh_path_relative = 'tai_len/san_pham/' . ($sp['anh_dai_dien'] ?? 'default.png');
                            if (empty($sp['anh_dai_dien']) || !file_exists(ROOT_PATH . $anh_path_relative)) {
                                $anh_path_relative = 'tai_len/san_pham/default.png'; 
                            }
                            ?>
                            <img src="<?php echo BASE_URL . $anh_path_relative; ?>" alt="<?php echo htmlspecialchars($sp['ten_san_pham']); ?>">
                        </td>
                        <td><strong><?php echo htmlspecialchars($sp['ten_san_pham']); ?></strong></td>
                        <td><?php echo htmlspecialchars($sp['ten_hang']); ?></td>
                        <td><?php echo number_format($sp['gia_ban'], 0, ',', '.'); ?>đ</td>
                        <td><?php echo $sp['so_luong_ton']; ?></td>
                        <td>
                            <?php
                                $status_class = 'status-' . str_replace(' ', '_', $sp['trang_thai']);
                                echo "<span class='status-label $status_class'>" . htmlspecialchars($sp['trang_thai']) . "</span>";
                            ?>
                        </td>
                        <td class="action-links">
                            <a href="?action=sua&id=<?php echo $sp['id']; ?>" class="edit"><i class="fas fa-edit"></i> Sửa</a>
                            <a href="?action=xoa&id=<?php echo $sp['id']; ?>" 
                               class="delete" 
                               onclick="return confirm('Bạn có chắc chắn muốn XÓA sản phẩm này?');"><i class="fas fa-trash-alt"></i> Xóa</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

<?php elseif ($action == 'them' || $action == 'sua'): ?>
    
    <?php
        // (Logic đổ dữ liệu vào form giữ nguyên)
        $is_editing = ($action == 'sua' && $product_data);
        
        $edit_id = $product_data['id_san_pham'] ?? null;
        $edit_ten = $product_data['ten_san_pham'] ?? '';
        $edit_id_hang = $product_data['id_hang'] ?? 0;
        $edit_ma_sp = $product_data['ma_san_pham'] ?? '';
        $edit_mau_sac = $product_data['mau_sac'] ?? '';
        $edit_gia_ban = $product_data['gia_ban'] ?? '';
        $edit_gia_goc = $product_data['gia_goc'] ?? '';
        $edit_so_luong_ton = $product_data['so_luong_ton'] ?? 0;
        $edit_trang_thai = $product_data['trang_thai'] ?? 'hiện';
        $edit_mo_ta_ngan = $product_data['mo_ta_ngan'] ?? '';
        $edit_mo_ta_chi_tiet = $product_data['mo_ta_chi_tiet'] ?? '';
        $edit_anh_dai_dien = $product_data['anh_dai_dien'] ?? '';
        $edit_phan_tram_giam = $product_data['phan_tram_giam_gia'] ?? '';
        $edit_ngay_bat_dau = $product_data['ngay_bat_dau_giam'] ?? '';
        $edit_ngay_ket_thuc = $product_data['ngay_ket_thuc_giam'] ?? '';
        $edit_man_hinh = $product_data['man_hinh'] ?? '';
        $edit_do_phan_giai = $product_data['do_phan_giai'] ?? '';
        $edit_tan_so_quet = $product_data['tan_so_quet'] ?? '';
        $edit_chip_xu_ly = $product_data['chip_xu_ly'] ?? '';
        $edit_gpu = $product_data['gpu'] ?? '';
        $edit_ram = $product_data['ram'] ?? '';
        $edit_rom = $product_data['rom'] ?? '';
        $edit_he_dieu_hanh = $product_data['he_dieu_hanh'] ?? '';
        $edit_camera_sau = $product_data['camera_sau'] ?? '';
        $edit_camera_truoc = $product_data['camera_truoc'] ?? '';
        $edit_pin = $product_data['dung_luong_pin'] ?? '';
        $edit_sac = $product_data['sac'] ?? '';
        $edit_ket_noi = $product_data['ket_noi'] ?? '';
        $edit_sim = $product_data['sim'] ?? '';
        $edit_trong_luong = $product_data['trong_luong'] ?? '';
        $edit_chat_lieu = $product_data['chat_lieu'] ?? '';
        $edit_khang_nuoc_bui = $product_data['khang_nuoc_bui'] ?? '';
        $edit_bao_mat = $product_data['bao_mat'] ?? '';
        $edit_anh_phu_1 = $product_data['anh_phu_1'] ?? '';
        $edit_anh_phu_2 = $product_data['anh_phu_2'] ?? '';
        $edit_anh_phu_3 = $product_data['anh_phu_3'] ?? '';
        $edit_anh_phu_4 = $product_data['anh_phu_4'] ?? '';
    ?>

    <h1><?php echo $is_editing ? 'Sửa Sản Phẩm' : 'Thêm Sản Phẩm Mới'; ?></h1>
    
    <div class="form-container">
        <form action="quan_ly_san_pham.php" method="POST" enctype="multipart/form-data">
            
            <?php if ($is_editing): ?>
                <input type="hidden" name="id_san_pham" value="<?php echo $edit_id; ?>">
            <?php endif; ?>

            <fieldset class="form-fieldset">
                <legend>Thông tin cơ bản</legend>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ten_san_pham">Tên Sản Phẩm (*)</label>
                        <input type="text" id="ten_san_pham" name="ten_san_pham" value="<?php echo htmlspecialchars($edit_ten); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="id_hang">Hãng Sản Xuất (*)</label>
                        <select id="id_hang" name="id_hang" required>
                            <option value="">-- Chọn hãng --</option>
                            <?php foreach ($hang_list as $hang): ?>
                                <option value="<?php echo $hang['id_hang']; ?>" <?php echo ($hang['id_hang'] == $edit_id_hang) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($hang['ten_hang']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="ma_san_pham">Mã Sản Phẩm (SKU)</label>
                        <input type="text" id="ma_san_pham" name="ma_san_pham" value="<?php echo htmlspecialchars($edit_ma_sp); ?>">
                    </div>
                    <div class="form-group">
                        <label for="mau_sac">Màu Sắc (*)</label>
                        <input type="text" id="mau_sac" name="mau_sac" value="<?php echo htmlspecialchars($edit_mau_sac); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="so_luong_ton">Số Lượng Tồn Kho (*)</label>
                        <input type="number" id="so_luong_ton" name="so_luong_ton" value="<?php echo htmlspecialchars($edit_so_luong_ton); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="trang_thai">Trạng thái (*)</label>
                        <select id="trang_thai" name="trang_thai" required>
                            <option value="hiện" <?php echo ($edit_trang_thai == 'hiện') ? 'selected' : ''; ?>>Hiển thị</option>
                            <option value="ẩn" <?php echo ($edit_trang_thai == 'ẩn') ? 'selected' : ''; ?>>Ẩn</option>
                            <option value="hết hàng" <?php echo ($edit_trang_thai == 'hết hàng') ? 'selected' : ''; ?>>Hết hàng</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label for="anh_dai_dien">Ảnh Đại Diện</label>
                        <?php if ($is_editing && $edit_anh_dai_dien && file_exists(ROOT_PATH . 'tai_len/san_pham/' . $edit_anh_dai_dien)): ?>
                            <img src="<?php echo BASE_URL; ?>tai_len/san_pham/<?php echo $edit_anh_dai_dien; ?>" alt="Ảnh hiện tại" class="image-preview">
                        <?php endif; ?>
                        <input type="hidden" name="anh_dai_dien_hien_tai" value="<?php echo htmlspecialchars($edit_anh_dai_dien); ?>">
                        <input type="file" id="anh_dai_dien" name="anh_dai_dien">
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-fieldset">
                <legend>Giá & Khuyến mãi</legend>
                <div class="form-grid-price">
                    <div class="form-group">
                        <label for="gia_goc_display">Giá Gốc (Giá thị trường)</label>
                        <input type="text" id="gia_goc_display" 
                               value="<?php echo !empty($edit_gia_goc) ? number_format($edit_gia_goc, 0, ',', ',') : ''; ?>" 
                               placeholder="Bỏ trống nếu không có" inputmode="numeric">
                        <input type="hidden" id="gia_goc" name="gia_goc" value="<?php echo htmlspecialchars($edit_gia_goc); ?>">
                    </div>
                    <div class="form-group">
                        <label for="gia_ban_display">Giá Bán (*)</label>
                        <input type="text" id="gia_ban_display" 
                               value="<?php echo !empty($edit_gia_ban) ? number_format($edit_gia_ban, 0, ',', ',') : '0'; ?>" 
                               required inputmode="numeric">
                        <input type="hidden" id="gia_ban" name="gia_ban" value="<?php echo htmlspecialchars($edit_gia_ban); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phan_tram_giam_gia">Phần trăm giảm (%)</label>
                        <input type="number" id="phan_tram_giam_gia" name="phan_tram_giam_gia" value="<?php echo htmlspecialchars($edit_phan_tram_giam); ?>" min="0" max="100">
                    </div>
                </div>
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ngay_bat_dau_giam">Ngày bắt đầu giảm</label>
                        <input type="date" id="ngay_bat_dau_giam" name="ngay_bat_dau_giam" value="<?php echo htmlspecialchars($edit_ngay_bat_dau); ?>">
                    </div>
                    <div class="form-group">
                        <label for="ngay_ket_thuc_giam">Ngày kết thúc giảm</label>
                        <input type="date" id="ngay_ket_thuc_giam" name="ngay_ket_thuc_giam" value="<?php echo htmlspecialchars($edit_ngay_ket_thuc); ?>">
                    </div>
                </div>
            </fieldset>

            <fieldset class="form-fieldset">
                <legend>Thông số kỹ thuật</legend>
                <div class="form-grid">
                    <div class="form-group"><label for="chip_xu_ly">Chip Xử Lý (*)</label><input type="text" id="chip_xu_ly" name="chip_xu_ly" value="<?php echo htmlspecialchars($edit_chip_xu_ly); ?>" required></div>
                    <div class="form-group"><label for="he_dieu_hanh">Hệ Điều Hành (*)</label><input type="text" id="he_dieu_hanh" name="he_dieu_hanh" value="<?php echo htmlspecialchars($edit_he_dieu_hanh); ?>" required></div>
                    <div class="form-group"><label for="ram">RAM (*)</label><input type="text" id="ram" name="ram" value="<?php echo htmlspecialchars($edit_ram); ?>" required></div>
                    <div class="form-group"><label for="rom">ROM (*)</label><input type="text" id="rom" name="rom" value="<?php echo htmlspecialchars($edit_rom); ?>" required></div>
                    <div class="form-group"><label for="man_hinh">Màn hình</label><input type="text" id="man_hinh" name="man_hinh" value="<?php echo htmlspecialchars($edit_man_hinh); ?>"></div>
                    <div class="form-group"><label for="do_phan_giai">Độ phân giải</label><input type="text" id="do_phan_giai" name="do_phan_giai" value="<?php echo htmlspecialchars($edit_do_phan_giai); ?>"></div>
                    <div class="form-group"><label for="tan_so_quet">Tần số quét</label><input type="text" id="tan_so_quet" name="tan_so_quet" value="<?php echo htmlspecialchars($edit_tan_so_quet); ?>"></div>
                    <div class="form-group"><label for="gpu">GPU</label><input type="text" id="gpu" name="gpu" value="<?php echo htmlspecialchars($edit_gpu); ?>"></div>
                    <div class="form-group"><label for="camera_sau">Camera Sau</label><input type="text" id="camera_sau" name="camera_sau" value="<?php echo htmlspecialchars($edit_camera_sau); ?>"></div>
                    <div class="form-group"><label for="camera_truoc">Camera Trước</label><input type="text" id="camera_truoc" name="camera_truoc" value="<?php echo htmlspecialchars($edit_camera_truoc); ?>"></div>
                    <div class="form-group"><label for="dung_luong_pin">Dung lượng Pin</label><input type="text" id="dung_luong_pin" name="dung_luong_pin" value="<?php echo htmlspecialchars($edit_pin); ?>"></div>
                    <div class="form-group"><label for="sac">Sạc</label><input type="text" id="sac" name="sac" value="<?php echo htmlspecialchars($edit_sac); ?>"></div>
                    <div class="form-group"><label for="sim">SIM</label><input type="text" id="sim" name="sim" value="<?php echo htmlspecialchars($edit_sim); ?>"></div>
                    <div class="form-group"><label for="ket_noi">Kết nối</label><input type="text" id="ket_noi" name="ket_noi" value="<?php echo htmlspecialchars($edit_ket_noi); ?>"></div>
                    <div class="form-group"><label for="trong_luong">Trọng lượng</label><input type="text" id="trong_luong" name="trong_luong" value="<?php echo htmlspecialchars($edit_trong_luong); ?>"></div>
                    <div class="form-group"><label for="chat_lieu">Chất liệu</label><input type="text" id="chat_lieu" name="chat_lieu" value="<?php echo htmlspecialchars($edit_chat_lieu); ?>"></div>
                    <div class="form-group"><label for="khang_nuoc_bui">Kháng nước, bụi</label><input type="text" id="khang_nuoc_bui" name="khang_nuoc_bui" value="<?php echo htmlspecialchars($edit_khang_nuoc_bui); ?>"></div>
                    <div class="form-group"><label for="bao_mat">Bảo mật</label><input type="text" id="bao_mat" name="bao_mat" value="<?php echo htmlspecialchars($edit_bao_mat); ?>"></div>
                </div>
            </fieldset>

            <fieldset class="form-fieldset">
                <legend>Mô tả sản phẩm</legend>
                <div class="form-group">
                    <label for="mo_ta_ngan">Mô tả ngắn</label>
                    <textarea id="mo_ta_ngan" name="mo_ta_ngan"><?php echo htmlspecialchars($edit_mo_ta_ngan); ?></textarea>
                </div>
                <div class="form-group">
                    <label for="mo_ta_chi_tiet">Mô tả chi tiết</label>
                    <textarea id="mo_ta_chi_tiet" name="mo_ta_chi_tiet" class="large"><?php echo htmlspecialchars($edit_mo_ta_chi_tiet); ?></textarea>
                </div>
            </fieldset>

            <fieldset class="form-fieldset">
                <legend>Ảnh thư viện (Ảnh phụ)</legend>
                <div class="form-grid-gallery">
                    <div class="form-group">
                        <label for="anh_phu_1">Ảnh phụ 1</label>
                        <?php if ($is_editing && $edit_anh_phu_1 && file_exists(ROOT_PATH . 'tai_len/san_pham/gallery/' . $edit_anh_phu_1)): ?>
                            <img src="<?php echo BASE_URL; ?>tai_len/san_pham/gallery/<?php echo $edit_anh_phu_1; ?>" class="image-preview">
                        <?php endif; ?>
                        <input type="hidden" name="anh_phu_1_hien_tai" value="<?php echo htmlspecialchars($edit_anh_phu_1); ?>">
                        <input type="file" id="anh_phu_1" name="anh_phu_1">
                    </div>
                    <div class="form-group">
                        <label for="anh_phu_2">Ảnh phụ 2</label>
                        <?php if ($is_editing && $edit_anh_phu_2 && file_exists(ROOT_PATH . 'tai_len/san_pham/gallery/' . $edit_anh_phu_2)): ?>
                            <img src="<?php echo BASE_URL; ?>tai_len/san_pham/gallery/<?php echo $edit_anh_phu_2; ?>" class="image-preview">
                        <?php endif; ?>
                        <input type="hidden" name="anh_phu_2_hien_tai" value="<?php echo htmlspecialchars($edit_anh_phu_2); ?>">
                        <input type="file" id="anh_phu_2" name="anh_phu_2">
                    </div>
                    <div class="form-group">
                        <label for="anh_phu_3">Ảnh phụ 3</label>
                        <?php if ($is_editing && $edit_anh_phu_3 && file_exists(ROOT_PATH . 'tai_len/san_pham/gallery/' . $edit_anh_phu_3)): ?>
                            <img src="<?php echo BASE_URL; ?>tai_len/san_pham/gallery/<?php echo $edit_anh_phu_3; ?>" class="image-preview">
                        <?php endif; ?>
                        <input type="hidden" name="anh_phu_3_hien_tai" value="<?php echo htmlspecialchars($edit_anh_phu_3); ?>">
                        <input type="file" id="anh_phu_3" name="anh_phu_3">
                    </div>
                    <div class="form-group">
                        <label for="anh_phu_4">Ảnh phụ 4</label>
                        <?php if ($is_editing && $edit_anh_phu_4 && file_exists(ROOT_PATH . 'tai_len/san_pham/gallery/' . $edit_anh_phu_4)): ?>
                            <img src="<?php echo BASE_URL; ?>tai_len/san_pham/gallery/<?php echo $edit_anh_phu_4; ?>" class="image-preview">
                        <?php endif; ?>
                        <input type="hidden" name="anh_phu_4_hien_tai" value="<?php echo htmlspecialchars($edit_anh_phu_4); ?>">
                        <input type="file" id="anh_phu_4" name="anh_phu_4">
                    </div>
                </div>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-save"></i> <?php echo $is_editing ? 'Cập Nhật Sản Phẩm' : 'Lưu (Thêm mới)'; ?>
                </button>
                <a href="?action=danh_sach" class="btn btn-secondary">
                    <i class="fas fa-times"></i> Hủy Bỏ
                </a>
            </div>
        </form>
    </div>

<?php endif; // Đóng if ($action == '...') ?>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        
        // (Logic JS định dạng giá của bạn giữ nguyên)
        function unformatNumber(value) {
            return value.replace(/,/g, '');
        }
        
        function formatNumber(value) {
            let rawValue = value.replace(/[^0-9]/g, '');
            return rawValue.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        }
        
        function setupPriceInput(displayId, hiddenId) {
            const displayInput = document.getElementById(displayId);
            const hiddenInput = document.getElementById(hiddenId);
            
            if (displayInput && hiddenInput) {
                // (MỚI) Định dạng giá trị ban đầu khi tải trang
                let initialRawValue = hiddenInput.value;
                if (initialRawValue) {
                    displayInput.value = formatNumber(initialRawValue);
                }
                
                displayInput.addEventListener('input', function(e) {
                    let rawValue = unformatNumber(e.target.value);
                    hiddenInput.value = rawValue;
                    let formattedValue = formatNumber(rawValue);
                    e.target.value = formattedValue;
                });
            }
        }
        
        setupPriceInput('gia_goc_display', 'gia_goc');
        setupPriceInput('gia_ban_display', 'gia_ban');
    });
</script>

<?php require 'cuoi_trang_quan_tri.php'; ?>