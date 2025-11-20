<?php
// 1. NẠP CÁC THƯ VIỆN CẦN THIẾT
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require 'vendor/autoload.php';
require 'dung_chung/ket_noi_csdl.php';

$thong_bao = "";
$thong_bao_loi = "";

// 2. KIỂM TRA NẾU NGƯỜI DÙNG NHẤN NÚT "GỬI"
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $email = $conn->real_escape_string($_POST['email']);

    // 3. KIỂM TRA EMAIL CÓ TỒN TẠI TRONG CSDL KHÔNG
    $sql_check = "SELECT id_nguoi_dung FROM nguoi_dung WHERE email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result_check = $stmt_check->get_result();

    if ($result_check->num_rows == 0) {
        $thong_bao_loi = "Email không tồn tại trong hệ thống.";
    } else {
        // 4. EMAIL TỒN TẠI -> TẠO MẬT KHẨU MỚI
        
        $mat_khau_moi_ngau_nhien = substr(bin2hex(random_bytes(10)), 0, 8);
        $mat_khau_moi_bam = password_hash($mat_khau_moi_ngau_nhien, PASSWORD_DEFAULT);

        // Cập nhật mật khẩu mới vào CSDL
        $sql_update = "UPDATE nguoi_dung SET mat_khau = ? WHERE email = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("ss", $mat_khau_moi_bam, $email);
        
        if ($stmt_update->execute()) {
            
            // 5. GỬI EMAIL CHỨA MẬT KHẨU MỚI
            $mail = new PHPMailer(true);
            try {
                // === TẮT GỠ LỖI ===
                $mail->SMTPDebug = 0; 
                // ===================

                // Cấu hình SMTP (giống file đăng ký)
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = '20222027@eaut.edu.vn'; 
                
                // === MẬT KHẨU MỚI ===
                $mail->Password   = 'nzof znds lbba qkxv'; // MẬT KHẨU MỚI
                // ======================
                
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
                $mail->CharSet    = 'UTF-8';

                // Người gửi và người nhận
                $mail->setFrom('20222027@eaut.edu.vn', 'Web Ban Dien Thoai');
                $mail->addAddress($email); // Gửi đến email người dùng

                // Nội dung
                $mail->isHTML(true);
                $mail->Subject = 'Yeu cau dat lai mat khau';
                $mail->Body    = "Bạn hoặc ai đó đã yêu cầu đặt lại mật khẩu cho tài khoản của bạn.<br><br>"
                               . "Mật khẩu mới của bạn là: <b>" . $mat_khau_moi_ngau_nhien . "</b><br><br>"
                               . "Vui lòng đăng nhập bằng mật khẩu này và đổi lại mật khẩu ngay lập tức để đảm bảo an toàn.<br>"
                               . "Nếu bạn không yêu cầu, vui lòng bỏ qua email này.<br>"
                               . "Trân trọng.";

                $mail->send();
                $thong_bao = "Mật khẩu mới đã được gửi đến email của bạn. Vui lòng kiểm tra.";
            
            } catch (Exception $e) {
                $thong_bao_loi = "Cập nhật mật khẩu thành công (nhưng không thể gửi email). Lỗi: " . $mail->ErrorInfo;
            }
        } else {
            $thong_bao_loi = "Đã xảy ra lỗi khi cập nhật mật khẩu. Vui lòng thử lại.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        :root {
            --primary-color: #dc3545;
            --primary-hover: #c82333;
            --success-color: #28a745;
            --error-color: #f8d7da;
            --text-error: #721c24;
            --text-success: #155724;
            --bg-light: #f4f4f4;
            --bg-white: #ffffff;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --radius: 10px;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: var(--bg-light);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background-color: var(--bg-white);
            padding: 30px;
            border-radius: var(--radius);
            box-shadow: var(--shadow);
            width: 100%;
            max-width: 400px;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
        }

        .form-group {
            margin-bottom: 20px;
        }

        label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }

        input[type="email"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: var(--radius);
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus {
            border-color: var(--primary-color);
            outline: none;
        }

        button {
            background-color: var(--primary-color);
            color: white;
            padding: 12px;
            border: none;
            border-radius: var(--radius);
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        button:hover {
            background-color: var(--primary-hover);
        }

        .message {
            padding: 12px;
            border-radius: var(--radius);
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.success {
            background-color: #d4edda;
            color: var(--text-success);
        }

        .message.error {
            background-color: var(--error-color);
            color: var(--text-error);
            word-wrap: break-word;
        }

        p {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }

        p a {
            color: var(--primary-color);
            text-decoration: none;
        }

        p a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>🔐 Quên Mật Khẩu</h2>

        <?php if (!empty($thong_bao)): ?>
            <div class="message success">
                <?php echo $thong_bao; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($thong_bao_loi)): ?>
            <div class="message error">
                <?php echo $thong_bao_loi; ?>
            </div>
        <?php endif; ?>

        <form action="quen_mat_khau.php" method="POST">
            <div class="form-group">
                <label for="email">📧 Nhập Email của bạn:</label>
                <input type="email" id="email" name="email" placeholder="example@email.com" required>
            </div>
            <button type="submit">Gửi mật khẩu mới</button>
        </form>

        <p><a href="dang_nhap.php">← Quay lại Đăng nhập</a></p>
    </div>
</body>
</html>