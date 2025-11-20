<?php
// Đặt tiêu đề cho trang này
$page_title = "Liên Hệ - PhoneStore";

// 1. GỌI ĐẦU TRANG (Đã bao gồm session, CSDL, CSS, Menu và Turbolinks)
require 'dung_chung/dau_trang.php';
?>

<?php
// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
$thong_bao_loi = "";
$thong_bao_thanh_cong = "";
$user_info = []; 

// LẤY THÔNG TIN NẾU ĐÃ ĐĂNG NHẬP (ĐỂ TỰ ĐIỀN FORM)
if (isset($_SESSION['id_nguoi_dung'])) {
    $sql_user = "SELECT ten, email, so_dien_thoai FROM nguoi_dung WHERE id_nguoi_dung = ?";
    $stmt_user = $conn->prepare($sql_user);
    $stmt_user->bind_param("i", $_SESSION['id_nguoi_dung']);
    $stmt_user->execute();
    $user_info = $stmt_user->get_result()->fetch_assoc();
}

// XỬ LÝ POST (KHI NGƯỜI DÙNG GỬI LIÊN HỆ)
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ten_nguoi_gui = $conn->real_escape_string($_POST['ten_nguoi_gui']);
    $email = $conn->real_escape_string($_POST['email']);
    $so_dien_thoai = $conn->real_escape_string($_POST['so_dien_thoai']);
    $tieu_de = $conn->real_escape_string($_POST['tieu_de']);
    $noi_dung = $conn->real_escape_string($_POST['noi_dung']);
    
    $id_nguoi_dung_gui = $_SESSION['id_nguoi_dung'] ?? null;

    if (empty($ten_nguoi_gui) || empty($email) || empty($noi_dung)) {
        $thong_bao_loi = "Vui lòng nhập Tên, Email và Nội dung.";
    } else {
        $sql_insert = "INSERT INTO lien_he (ten_nguoi_gui, email, so_dien_thoai, tieu_de, noi_dung, id_nguoi_dung)
                       VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("sssssi", 
            $ten_nguoi_gui, $email, $so_dien_thoai, 
            $tieu_de, $noi_dung, $id_nguoi_dung_gui
        );
        
        if ($stmt->execute()) {
            $thong_bao_thanh_cong = "Gửi liên hệ thành công! Chúng tôi sẽ phản hồi bạn sớm nhất qua email.";
        } else {
            $thong_bao_loi = "Lỗi CSDL: Không thể gửi liên hệ. " . $conn->error;
        }
    }
}
?>

<main class="container container-mini"> <h1 style="text-align: center;"><i class="fas fa-envelope"></i> Liên Hệ Với Chúng Tôi</h1>
    <p style="text-align: center; margin-bottom: 25px;">Có câu hỏi? Gửi tin nhắn cho chúng tôi và chúng tôi sẽ phản hồi sớm nhất.</p>

    <?php if ($thong_bao_thanh_cong): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao_thanh_cong); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <?php if (empty($thong_bao_thanh_cong)): // Ẩn form đi nếu đã gửi thành công ?>
        <form action="lien_he.php" method="POST" data-turbolinks="false">
            <div class="form-grid">
                <div class="form-group">
                    <label for="ten_nguoi_gui">Họ và Tên (*)</label>
                    <input type="text" id="ten_nguoi_gui" name="ten_nguoi_gui" 
                           value="<?php echo htmlspecialchars($user_info['ten'] ?? ''); ?>" required>
                </div>
                <div class="form-group">
                    <label for="email">Email (*)</label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                </div>
            </div>
            
            <div class="form-group full-width">
                <label for="so_dien_thoai">Số điện thoại</label>
                <input type="text" id="so_dien_thoai" name="so_dien_thoai" 
                       value="<?php echo htmlspecialchars($user_info['so_dien_thoai'] ?? ''); ?>">
            </div>
            
            <div class="form-group full-width">
                <label for="tieu_de">Tiêu đề</label>
                <input type="text" id="tieu_de" name="tieu_de">
            </div>
            
            <div class="form-group full-width">
                <label for="noi_dung">Nội dung (*)</label>
                <textarea id="noi_dung" name="noi_dung" rows="6" required></textarea>
            </div>
            
            <button type="submit" class="btn-submit full-width">
                <i class="fas fa-paper-plane"></i> Gửi Tin Nhắn
            </button>
        </form>
    <?php endif; ?>
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>