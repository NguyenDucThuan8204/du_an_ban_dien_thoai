<?php
// 1. GỌI LOGIC TRƯỚC
// (Bắt đầu session và kết nối CSDL)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
// (Toàn bộ logic PHP phải được xử lý trước khi gọi dau_trang.php)

// 2.1. KIỂM TRA ĐĂNG NHẬP (ĐÂY LÀ DÒNG 16 GÂY LỖI CŨ)
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['redirect_url'] = 'doi_mat_khau.php'; 
    header("Location: dang_nhap.php"); // OK: Bây giờ nó chạy trước khi có HTML
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// 2.2. KHỞI TẠO BIẾN
$thong_bao_loi = "";
$thong_bao_thanh_cong = "";

// 2.3. XỬ LÝ POST (KHI NGƯỜI DÙNG BẤM "ĐỔI MẬT KHẨU")
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $mat_khau_cu = $_POST['mat_khau_cu'];
    $mat_khau_moi = $_POST['mat_khau_moi'];
    $xac_nhan_mat_khau = $_POST['xac_nhan_mat_khau'];

    // 1. Kiểm tra xác nhận
    if ($mat_khau_moi !== $xac_nhan_mat_khau) {
        $thong_bao_loi = "Mật khẩu mới và xác nhận mật khẩu không khớp!";
    } 
    // 2. Kiểm tra độ dài mật khẩu mới
    elseif (strlen($mat_khau_moi) < 6) {
        $thong_bao_loi = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    }
    // 3. Nếu không có lỗi, kiểm tra mật khẩu cũ
    else {
        // Lấy mật khẩu đã băm trong CSDL
        $sql_get_pass = "SELECT mat_khau FROM nguoi_dung WHERE id_nguoi_dung = ?";
        $stmt_get = $conn->prepare($sql_get_pass);
        $stmt_get->bind_param("i", $id_nguoi_dung);
        $stmt_get->execute();
        $result_pass = $stmt_get->get_result();
        $user = $result_pass->fetch_assoc();
        
        // Dùng password_verify để so sánh
        if ($user && password_verify($mat_khau_cu, $user['mat_khau'])) {
            // Mật khẩu cũ ĐÚNG
            
            // 4. Băm mật khẩu mới
            $mat_khau_moi_bam = password_hash($mat_khau_moi, PASSWORD_DEFAULT);
            
            // 5. Cập nhật CSDL
            $sql_update_pass = "UPDATE nguoi_dung SET mat_khau = ? WHERE id_nguoi_dung = ?";
            $stmt_update = $conn->prepare($sql_update_pass);
            $stmt_update->bind_param("si", $mat_khau_moi_bam, $id_nguoi_dung);
            
            if ($stmt_update->execute()) {
                $thong_bao_thanh_cong = "Đổi mật khẩu thành công!";
                // (Tùy chọn) Gửi thông báo về trang tài khoản
                $_SESSION['thong_bao_thanh_cong_tk'] = "Đổi mật khẩu thành công!";
                header("Location: thong_tin_tai_khoan.php");
                exit();
            } else {
                $thong_bao_loi = "Lỗi CSDL: Không thể cập nhật mật khẩu.";
            }
            
        } else {
            // Mật khẩu cũ SAI
            $thong_bao_loi = "Mật khẩu cũ không chính xác!";
        }
    }
}

// --- TẤT CẢ LOGIC ĐÃ XONG, BÂY GIỜ MỚI GỌI HTML ---
?>

<?php
// Đặt tiêu đề cho trang này
$page_title = "Đổi mật khẩu";

// 3. GỌI ĐẦU TRANG (Đã bao gồm CSS, Menu và Turbolinks)
// (Biến $conn đã được tạo ở trên, nên dau_trang.php sẽ dùng nó)
require 'dung_chung/dau_trang.php';
?>

<main class="container container-mini"> <h1 style="text-align: center;"><i class="fas fa-key"></i> Đổi Mật Khẩu</h1>

    <?php if ($thong_bao_thanh_cong): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao_thanh_cong); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <form action="doi_mat_khau.php" method="POST" data-turbolinks="false">
        
        <div class="form-group">
            <label for="mat_khau_cu">Mật khẩu cũ (*)</label>
            <input type="password" id="mat_khau_cu" name="mat_khau_cu" required>
        </div>
        
        <div class="form-group">
            <label for="mat_khau_moi">Mật khẩu mới (*)</label>
            <input type="password" id="mat_khau_moi" name="mat_khau_moi" required minlength="6">
        </div>

        <div class="form-group">
            <label for="xac_nhan_mat_khau">Xác nhận mật khẩu mới (*)</label>
            <input type="password" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau" required minlength="6">
        </div>
        
        <button type="submit" class="btn-submit">
            <i class="fas fa-save"></i> Cập Nhật Mật Khẩu
        </button>
    </form>
    
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>