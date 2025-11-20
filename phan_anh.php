<?php
// 1. BẮT ĐẦU SESSION VÀ KẾT NỐI CSDL (BẮT BUỘC Ở ĐẦU)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php'; // $conn sẽ được tạo ở đây

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
// (Toàn bộ logic PHP phải được xử lý trước khi gọi dau_trang.php)

// 2.1. KIỂM TRA ĐĂNG NHẬP (ĐÂY LÀ DÒNG 15 GÂY LỖI CŨ)
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['redirect_url'] = 'phan_anh.php'; 
    header("Location: dang_nhap.php"); // OK: Bây giờ nó chạy trước khi có HTML
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// 2.2. KHỞI TẠO BIẾN
$thong_bao_loi = "";
$thong_bao_thanh_cong = "";
$upload_dir_pa = 'tai_len/phan_anh/'; // Thư mục lưu ảnh phản ánh

// 2.3. HÀM HỖ TRỢ UPLOAD ẢNH
function xu_ly_tai_anh_phan_anh($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_ten = $file['name'];
        $file_ext = strtolower(pathinfo($file_ten, PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ten_file_moi = 'pa_' . $_SESSION['id_nguoi_dung'] . '_' . uniqid() . time() . '.' . $file_ext;
            $duong_dan_dich = $upload_dir . $ten_file_moi;
            
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}

// 2.4. LẤY DANH SÁCH ĐƠN HÀNG (CHO DROPDOWN)
$list_don_hang = [];
$sql_orders = "SELECT id_don_hang, ma_don_hang, ngay_dat FROM don_hang WHERE id_nguoi_dung = ? ORDER BY ngay_dat DESC LIMIT 20";
$stmt_orders = $conn->prepare($sql_orders);
$stmt_orders->bind_param("i", $id_nguoi_dung);
$stmt_orders->execute();
$result_orders = $stmt_orders->get_result();
while ($row = $result_orders->fetch_assoc()) {
    $list_don_hang[] = $row;
}

// 2.5. XỬ LÝ POST (KHI NGƯỜI DÙNG GỬI PHẢN ÁNH)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $chu_de = $conn->real_escape_string($_POST['chu_de']);
    $noi_dung = $conn->real_escape_string($_POST['noi_dung']);
    $id_don_hang = !empty($_POST['id_don_hang']) ? (int)$_POST['id_don_hang'] : null;

    if (empty($chu_de) || empty($noi_dung)) {
        $thong_bao_loi = "Vui lòng nhập Chủ đề và Nội dung phản ánh.";
    } else {
        
        $ten_anh_1 = xu_ly_tai_anh_phan_anh('anh_1', $upload_dir_pa);
        $ten_anh_2 = xu_ly_tai_anh_phan_anh('anh_2', $upload_dir_pa);
        $ten_anh_3 = xu_ly_tai_anh_phan_anh('anh_3', $upload_dir_pa);
        
        $sql_insert = "INSERT INTO phan_anh (id_nguoi_dung, id_don_hang, chu_de, noi_dung, anh_1, anh_2, anh_3, trang_thai)
                       VALUES (?, ?, ?, ?, ?, ?, ?, 'moi')";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("iisssss", 
            $id_nguoi_dung, $id_don_hang, $chu_de, $noi_dung,
            $ten_anh_1, $ten_anh_2, $ten_anh_3
        );
        
        if ($stmt->execute()) {
            $thong_bao_thanh_cong = "Gửi phản ánh thành công! Chúng tôi sẽ xem xét và phản hồi sớm nhất.";
        } else {
            $thong_bao_loi = "Lỗi CSDL: Không thể gửi phản ánh. " . $conn->error;
        }
    }
}

// --- TẤT CẢ LOGIC ĐÃ XONG, BÂY GIỜ MỚI GỌI HTML ---
?>

<?php
// Đặt tiêu đề cho trang này
$page_title = "Gửi Phản Ánh";

// 3. GỌI ĐẦU TRANG (Đã bao gồm CSS, Menu và Turbolinks)
// (Biến $conn đã được tạo ở trên, nên dau_trang.php sẽ dùng nó)
require 'dung_chung/dau_trang.php';
?>

<style>
    /* CSS cho 3 cột upload ảnh */
    .upload-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr;
        gap: 20px;
    }
    .upload-grid .form-group {
        margin-bottom: 0;
    }
</style>

<main class="container container-mini"> <h1 style="text-align: center;"><i class="fas fa-flag"></i> Gửi Phản Ánh / Khiếu Nại</h1>

    <?php if ($thong_bao_thanh_cong): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao_thanh_cong); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <?php if (empty($thong_bao_thanh_cong)): // Ẩn form đi nếu đã gửi thành công ?>
        <form action="phan_anh.php" method="POST" enctype="multipart/form-data" data-turbolinks="false">
            
            <div class="form-group">
                <label for="id_don_hang">Đơn hàng liên quan (Tùy chọn)</label>
                <select id="id_don_hang" name="id_don_hang">
                    <option value="">-- Không liên quan đến đơn hàng nào --</option>
                    <?php foreach ($list_don_hang as $don): ?>
                        <option value="<?php echo $don['id_don_hang']; ?>">
                            Mã ĐH: <?php echo htmlspecialchars($don['ma_don_hang']); ?> (Ngày: <?php echo date('d/m/Y', strtotime($don['ngay_dat'])); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label for="chu_de">Chủ đề (*)</label>
                <input type="text" id="chu_de" name="chu_de" required placeholder="Ví dụ: Báo lỗi sản phẩm, Góp ý giao diện...">
            </div>
            
            <div class="form-group">
                <label for="noi_dung">Nội dung phản ánh (*)</label>
                <textarea id="noi_dung" name="noi_dung" rows="6" required placeholder="Vui lòng mô tả chi tiết vấn đề của bạn..."></textarea>
            </div>
            
            <div class="form-group">
                <label>Đính kèm hình ảnh (Tùy chọn)</label>
                <div class="upload-grid">
                    <div class="form-group">
                        <input type="file" id="anh_1" name="anh_1" accept="image/*">
                    </div>
                     <div class="form-group">
                        <input type="file" id="anh_2" name="anh_2" accept="image/*">
                    </div>
                     <div class="form-group">
                        <input type="file" id="anh_3" name="anh_3" accept="image/*">
                    </div>
                </div>
            </div>
            
            <button type="submit" class="btn-submit">
                <i class="fas fa-paper-plane"></i> Gửi Phản Ánh
            </button>
        </form>
    <?php endif; ?>
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>