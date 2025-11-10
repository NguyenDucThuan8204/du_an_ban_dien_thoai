<?php
// 1. KHỞI TẠO BIẾN TRƯỚC VÀ LẤY THAM SỐ
$page_title = "Quản lý Mã Giảm Giá"; 
$thong_bao = ""; 
$thong_bao_loi = ""; 
$action = $_GET['action'] ?? 'danh_sach'; // action: danh_sach, them, sua, xoa
$tab = $_GET['tab'] ?? 'danh_sach'; 
$coupon_data = null; 
$search_keyword = $_GET['search_keyword'] ?? '';

// 2. GỌI ĐẦU TRANG ADMIN (Bao gồm session, CSDL, CSS, Menu)
require 'dau_trang_quan_tri.php'; 

// 3. HÀM HỖ TRỢ (Tạo mã ngẫu nhiên)
function tao_ma_ngau_nhien($prefix = '', $length = 8) {
    $bytes = random_bytes(ceil($length / 2));
    return strtoupper($prefix . substr(bin2hex($bytes), 0, $length));
}

// 4. XỬ LÝ LOGIC (CONTROLLER)

// --- 4.1. XỬ LÝ POST (TẠO MÃ TỰ ĐỘNG) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'tao_tu_dong') {
    $tien_to = $conn->real_escape_string(strtoupper($_POST['tien_to']));
    $so_luong_tao = (int)$_POST['so_luong_tao'];
    $phan_tram_giam = (int)$_POST['phan_tram_giam'];
    $so_luong_su_dung = !empty($_POST['so_luong_su_dung']) ? (int)$_POST['so_luong_su_dung'] : null;
    $ngay_ket_thuc = $conn->real_escape_string($_POST['ngay_ket_thuc']);
    $trang_thai = 'hoat_dong';
    
    if ($so_luong_tao <= 0 || $phan_tram_giam <= 0 || empty($ngay_ket_thuc)) {
        $thong_bao_loi = "Số lượng mã, % giảm, và ngày hết hạn là bắt buộc.";
        $tab = 'tao_tu_dong';
    } else {
        $conn->begin_transaction();
        try {
            $sql_insert = "INSERT INTO ma_giam_gia (ma_code, phan_tram_giam, so_luong_tong, ngay_ket_thuc, trang_thai) VALUES (?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql_insert);
            
            $so_luong_thanh_cong = 0;
            for ($i = 0; $i < $so_luong_tao; $i++) {
                $ma_code = tao_ma_ngau_nhien($tien_to, 8); // Giới hạn 8 ký tự sau tiền tố
                // (Nên có vòng lặp kiểm tra mã trùng, nhưng tạm bỏ qua để đơn giản)
                $stmt->bind_param("sisss", $ma_code, $phan_tram_giam, $so_luong_su_dung, $ngay_ket_thuc, $trang_thai);
                if ($stmt->execute()) {
                    $so_luong_thanh_cong++;
                }
            }
            $conn->commit();
            $thong_bao = "Đã tạo thành công $so_luong_thanh_cong / $so_luong_tao mã.";
            $tab = 'danh_sach'; // Chuyển về tab danh sách
            
        } catch (Exception $e) {
            $conn->rollback();
            $thong_bao_loi = "Lỗi khi tạo mã: " . $e->getMessage();
            $tab = 'tao_tu_dong';
        }
    }
}

// --- 4.2. XỬ LÝ POST (THÊM / SỬA THỦ CÔNG) ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'luu_thu_cong') {
    $ma_code = $conn->real_escape_string(strtoupper($_POST['ma_code'])); 
    $phan_tram_giam = (int)$_POST['phan_tram_giam'];
    $ngay_ket_thuc = $conn->real_escape_string($_POST['ngay_ket_thuc']);
    $trang_thai = $conn->real_escape_string($_POST['trang_thai']);
    $id_giam_gia = $_POST['id_giam_gia'] ?? null;
    $so_luong_tong = !empty($_POST['so_luong_tong']) ? (int)$_POST['so_luong_tong'] : null;

    if (empty($ma_code) || empty($phan_tram_giam) || empty($ngay_ket_thuc)) {
        $thong_bao_loi = "Mã code, % giảm và ngày hết hạn là bắt buộc.";
        $action = $id_giam_gia ? 'sua' : 'them';
    } else {
        if ($id_giam_gia) {
            // --- CẬP NHẬT (SỬA) ---
            $sql_check = "SELECT id_giam_gia FROM ma_giam_gia WHERE ma_code = ? AND id_giam_gia != ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("si", $ma_code, $id_giam_gia);
            $stmt_check->execute();
            
            if ($stmt_check->get_result()->num_rows > 0) {
                $thong_bao_loi = "Mã code này đã tồn tại.";
                $action = 'sua'; $coupon_data = $_POST;
            } else {
                $sql_update = "UPDATE ma_giam_gia SET ma_code = ?, phan_tram_giam = ?, so_luong_tong = ?, ngay_ket_thuc = ?, trang_thai = ? WHERE id_giam_gia = ?";
                $stmt = $conn->prepare($sql_update);
                $stmt->bind_param("siissi", $ma_code, $phan_tram_giam, $so_luong_tong, $ngay_ket_thuc, $trang_thai, $id_giam_gia);
                if ($stmt->execute()) {
                    $thong_bao = "Cập nhật mã giảm giá thành công!";
                    $action = 'danh_sach';
                } else {
                    $thong_bao_loi = "Lỗi khi cập nhật: " . $stmt->error;
                    $action = 'sua';
                }
            }
        } else {
            // --- THÊM MỚI ---
            $sql_check = "SELECT id_giam_gia FROM ma_giam_gia WHERE ma_code = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $ma_code);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows > 0) {
                $thong_bao_loi = "Mã code này đã tồn tại.";
                $action = 'them'; $coupon_data = $_POST;
            } else {
                $sql_insert = "INSERT INTO ma_giam_gia (ma_code, phan_tram_giam, so_luong_tong, ngay_ket_thuc, trang_thai) VALUES (?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql_insert);
                $stmt->bind_param("siiss", $ma_code, $phan_tram_giam, $so_luong_tong, $ngay_ket_thuc, $trang_thai);
                if ($stmt->execute()) {
                    $thong_bao = "Thêm mã giảm giá mới thành công!";
                    $action = 'danh_sach';
                } else {
                    $thong_bao_loi = "Lỗi khi thêm mới: " . $stmt->error;
                    $action = 'them';
                }
            }
        }
    }
}

// --- 4.3. XỬ LÝ GET (Xóa) ---
if ($action == 'xoa' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $sql_delete = "DELETE FROM ma_giam_gia WHERE id_giam_gia = ?";
    $stmt = $conn->prepare($sql_delete);
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $thong_bao = "Đã xóa mã giảm giá thành công!";
    } else {
        $thong_bao_loi = "Lỗi khi xóa: " . $stmt->error;
    }
    $action = 'danh_sach'; 
}

// --- 4.4. XỬ LÝ GET (Lấy dữ liệu cho form Sửa) ---
if ($action == 'sua' && isset($_GET['id'])) {
    if (!$coupon_data) { 
        $id = (int)$_GET['id'];
        $sql_get = "SELECT * FROM ma_giam_gia WHERE id_giam_gia = ?";
        $stmt = $conn->prepare($sql_get);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows == 1) {
            $coupon_data = $result->fetch_assoc();
        } else {
            $thong_bao_loi = "Không tìm thấy mã giảm giá.";
            $action = 'danh_sach';
        }
    }
}

// --- 4.5. TRUY VẤN DANH SÁCH (kèm TÌM KIẾM) ---
$list_coupons = [];
if ($action == 'danh_sach' && $tab == 'danh_sach') {
    $sql_query = "SELECT * FROM ma_giam_gia";
    
    if (!empty($search_keyword)) {
        $sql_query .= " WHERE ma_code LIKE ?";
        $like_keyword = "%" . $conn->real_escape_string($search_keyword) . "%";
    }
    
    $sql_query .= " ORDER BY ngay_ket_thuc DESC";
    
    $stmt_list = $conn->prepare($sql_query);
    if (!empty($search_keyword)) {
        $stmt_list->bind_param("s", $like_keyword);
    }
    
    $stmt_list->execute();
    $result_data = $stmt_list->get_result();
    
    if ($result_data) {
        while($row = $result_data->fetch_assoc()) {
            $list_coupons[] = $row;
        }
    }
}
?>
<style>
    /* === (MỚI) CSS CHO HEADER TRANG (CHỨA NÚT THÊM MỚI) === */
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px; /* Giữ nguyên: Đã sửa */
        flex-wrap: wrap;
        gap: 15px;
    }
    .page-header h2 {
        margin: 0;
        font-size: 1.5rem;
    }
    
    /* CSS MỚI: Form Tìm kiếm */
    .search-form-container { 
        background-color: #fff; 
        padding: 20px; 
        border-radius: 8px; 
        margin-bottom: 20px; 
        box-shadow: 0 2px 5px rgba(0,0,0,0.05); 
    }
    .search-form { 
        display: flex; 
        gap: 30px; /* SỬA: Tăng khoảng cách lên 30px */
        align-items: flex-end; 
    }
    .search-group { 
        flex-grow: 1; 
    }
    .search-group label { 
        display: block; 
        margin-bottom: 5px; 
        font-weight: 600; 
        font-size: 0.9rem; 
    }
    .search-group input[type="text"] { 
        width: 100%; 
        padding: 10px; 
        border: 1px solid #ccc; 
        border-radius: 5px; 
        font-size: 1em; 
    }
    .search-actions { 
        display: flex; 
        gap: 10px; 
    }

    /* CSS MỚI: Form Thêm/Sửa/Tạo mã */
    .form-container { 
        background: #fff; 
        padding: 25px; 
        border-radius: 8px; 
        box-shadow: 0 2px 10px rgba(0,0,0,0.1); 
        max-width: 700px; 
        margin: 0 auto; 
    }
    .form-group label {
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
    .form-group input[type="number"],
    .form-group input[type="date"],
    .form-group select {
        width: 100%; 
        padding: 10px;
        border: 1px solid #ddd; 
        border-radius: 5px; 
        font-size: 14px;
        margin-top: 5px;
    }
    .form-group small { 
        font-size: 12px; 
        color: #777; 
        margin-top: 5px;
        display: inline-block;
    }
    .form-actions { 
        margin-top: 20px; 
        display: flex; 
        gap: 10px; 
    }
    
    /* CSS MỚI: Bảng (Trạng thái và Nút) */
    .status-hoat_dong { background-color: #28a745; }
    .status-tam_dung { background-color: #dc3545; }

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
    .action-links a:hover { text-decoration: underline; }
</style>

<h1>Quản lý Mã Giảm Giá</h1>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<nav class="tab-menu">
    <a href="?tab=danh_sach&action=danh_sach" 
       class="<?php echo ($tab == 'danh_sach') ? 'active' : ''; ?>">
       <i class="fas fa-list-ul"></i> Quản lý (Danh sách)
    </a>
    <a href="?tab=tao_tu_dong" 
       class="<?php echo ($tab == 'tao_tu_dong') ? 'active' : ''; ?>">
       <i class="fas fa-magic"></i> Tạo Mã Tự Động
    </a>
</nav>

<?php if ($tab == 'tao_tu_dong'): ?>
    <div class="form-container">
        <h3><i class="fas fa-magic"></i> Tạo Mã Giảm Giá Hàng Loạt</h3>
        <form action="quan_ly_ma_giam_gia.php?tab=tao_tu_dong" method="POST">
            <input type="hidden" name="action" value="tao_tu_dong">

            <div class="form-group">
                <label for="tien_to"><i class="fas fa-tag"></i> Tiền tố (Tùy chọn)</label>
                <input type="text" id="tien_to" name="tien_to" placeholder="Ví dụ: SALEHE (sẽ tạo ra SALEHE1A2B)">
            </div>
            <div class="form-group">
                <label for="so_luong_tao"><i class="fas fa-sort-numeric-up"></i> Số lượng mã cần tạo (*)</label>
                <input type="number" id="so_luong_tao" name="so_luong_tao" required min="1" max="1000" placeholder="Ví dụ: 50">
            </div>
            <div class="form-group">
                <label for="phan_tram_giam"><i class="fas fa-percent"></i> Giảm (%) (*)</label>
                <input type="number" id="phan_tram_giam" name="phan_tram_giam" required min="1" max="100" placeholder="Ví dụ: 10 (cho 10%)">
            </div>
            <div class="form-group">
                <label for="so_luong_su_dung"><i class="fas fa-check-double"></i> Số lần sử dụng cho mỗi mã</label>
                <input type="number" id="so_luong_su_dung" name="so_luong_su_dung" min="1" placeholder="Bỏ trống = vô hạn">
                <small>Mỗi mã được dùng bao nhiêu lần? (ví dụ: 1)</small>
            </div>
            <div class="form-group">
                <label for="ngay_ket_thuc"><i class="fas fa-calendar-times"></i> Ngày Hết Hạn (*)</label>
                <input type="date" id="ngay_ket_thuc" name="ngay_ket_thuc" required>
            </div>

            <div class="form-actions">
                <button type="submit" class="btn btn-success"><i class="fas fa-cogs"></i> Tạo Mã</button>
            </div>
        </form>
    </div>

<?php else: ?>

    <?php if ($action == 'danh_sach'): ?>
        
        <div class="page-header">
            <h2>Danh sách Mã</h2>
            <a href="?tab=danh_sach&action=them" class="btn btn-success">
                <i class="fas fa-plus"></i> Thêm Mã Thủ Công
            </a>
        </div>
        
        <div class="search-form-container">
            <form action="quan_ly_ma_giam_gia.php" method="GET" class="search-form">
                <input type="hidden" name="tab" value="danh_sach">
                <input type="hidden" name="action" value="danh_sach">
                <div class="search-group">
                    <label for="search_keyword">Tìm theo Mã Code:</label>
                    <input type="text" id="search_keyword" name="search_keyword" value="<?php echo htmlspecialchars($search_keyword); ?>">
                </div>
                <div class="search-actions">
                    <button type="submit" class="btn"><i class="fas fa-search"></i> Tìm</button>
                    <a href="?tab=danh_sach&action=danh_sach" class="btn btn-secondary">Xóa lọc</a>
                </div>
            </form>
        </div>

        <table class="admin-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Mã Code</th>
                    <th>Giảm (%)</th>
                    <th>Lượt sử dụng</th>
                    <th>Ngày Hết Hạn</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list_coupons)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center;">Không tìm thấy mã giảm giá nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($list_coupons as $coupon): ?>
                        <tr>
                            <td><?php echo $coupon['id_giam_gia']; ?></td>
                            <td><strong><?php echo htmlspecialchars($coupon['ma_code']); ?></strong></td>
                            <td><?php echo $coupon['phan_tram_giam']; ?>%</td>
                            <td>
                                <?php 
                                echo $coupon['so_luong_da_dung'] . ' / ';
                                echo $coupon['so_luong_tong'] ?? 'Vô hạn';
                                ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($coupon['ngay_ket_thuc'])); ?></td>
                            <td>
                                <?php
                                    $status_class = ($coupon['trang_thai'] == 'hoat_dong') ? 'status-hoat_dong' : 'status-tam_dung';
                                    $status_text = ($coupon['trang_thai'] == 'hoat_dong') ? 'Hoạt động' : 'Tạm dừng';
                                    echo "<span class='status-label $status_class'>$status_text</span>";
                                ?>
                            </td>
                            <td class="action-links">
                                <a href="?tab=danh_sach&action=sua&id=<?php echo $coupon['id_giam_gia']; ?>" class="edit">
                                    <i class="fas fa-edit"></i> Sửa
                                </a>
                                <a href="?tab=danh_sach&action=xoa&id=<?php echo $coupon['id_giam_gia']; ?>" 
                                   class="delete" 
                                   onclick="return confirm('Bạn có chắc chắn muốn XÓA mã này?');">
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
            $is_editing = ($action == 'sua' && $coupon_data);
            $edit_id = $coupon_data['id_giam_gia'] ?? null;
            $edit_code = $coupon_data['ma_code'] ?? '';
            $edit_percent = $coupon_data['phan_tram_giam'] ?? '';
            $edit_ngay_het = $coupon_data['ngay_ket_thuc'] ?? '';
            $edit_trang_thai = $coupon_data['trang_thai'] ?? 'hoat_dong';
            $edit_so_luong_tong = $coupon_data['so_luong_tong'] ?? '';
        ?>

        <h2><?php echo $is_editing ? 'Sửa Mã Giảm Giá (Thủ công)' : 'Thêm Mã Mới (Thủ công)'; ?></h2>
        
        <div class="form-container">
            <form action="quan_ly_ma_giam_gia.php?tab=danh_sach" method="POST">
                <input type="hidden" name="action" value="luu_thu_cong">
                <?php if ($is_editing): ?>
                    <input type="hidden" name="id_giam_gia" value="<?php echo $edit_id; ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="ma_code"><i class="fas fa-barcode"></i> Mã Code (*)</label>
                    <input type="text" id="ma_code" name="ma_code" value="<?php echo htmlspecialchars($edit_code); ?>" required placeholder="Ví dụ: GIAMDACTHIET">
                </div>
                
                <div class="form-group">
                    <label for="phan_tram_giam"><i class="fas fa-percent"></i> Giảm (%) (*)</label>
                    <input type="number" id="phan_tram_giam" name="phan_tram_giam" value="<?php echo htmlspecialchars($edit_percent); ?>" required min="1" max="100" placeholder="Ví dụ: 10 (cho 10%)">
                </div>
                
                <div class="form-group">
                    <label for="so_luong_tong"><i class="fas fa-check-double"></i> Tổng số lượt sử dụng</label>
                    <input type="number" id="so_luong_tong" name="so_luong_tong" value="<?php echo htmlspecialchars($edit_so_luong_tong); ?>" min="1" placeholder="Bỏ trống = vô hạn">
                </div>

                <div class="form-group">
                    <label for="ngay_ket_thuc"><i class="fas fa-calendar-times"></i> Ngày Hết Hạn (*)</label>
                    <input type="date" id="ngay_ket_thuc" name="ngay_ket_thuc" value="<?php echo htmlspecialchars($edit_ngay_het); ?>" required>
                </div>

                <div class="form-group">
                    <label for="trang_thai"><i class="fas fa-toggle-on"></i> Trạng thái (*)</label>
                    <select id="trang_thai" name="trang_thai">
                        <option value="hoat_dong" <?php echo ($edit_trang_thai == 'hoat_dong') ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="tam_dung" <?php echo ($edit_trang_thai == 'tam_dung') ? 'selected' : ''; ?>>Tạm dừng</option>
                    </select>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $is_editing ? 'Cập Nhật' : 'Lưu (Thêm mới)'; ?>
                    </button>
                    <a href="?tab=danh_sach&action=danh_sach" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Hủy Bỏ
                    </a>
                </div>
            </form>
        </div>

    <?php endif; // Đóng if $action ?>
        
<?php endif; // Đóng if $tab ?>

<?php require 'cuoi_trang_quan_tri.php'; ?>