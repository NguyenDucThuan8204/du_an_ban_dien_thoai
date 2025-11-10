<?php
// 1. NẠP CÁC THƯ VIỆN CẦN THIẾT
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP; 

require 'vendor/autoload.php';
require 'dung_chung/ket_noi_csdl.php';

// Biến lưu trữ thông báo
$thong_bao_loi = ""; 
$thong_bao_thanh_cong = "";

// --- LOGIC XỬ LÝ CHUYỂN BƯỚC ---

// Nếu người dùng muốn đổi email (quay lại bước 1)
if (isset($_GET['action']) && $_GET['action'] == 'doi_email') {
    unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
    header("Location: dang_ky.php");
    exit();
}

// Xác định bước hiện tại
$step = 1;
$email_da_gui = '';
if (isset($_SESSION['otp_code']) && isset($_SESSION['otp_email']) && isset($_SESSION['otp_expires'])) {
    // Nếu đã có mã OTP trong session, chuyển sang bước 2
    if (time() > $_SESSION['otp_expires']) {
        // Nếu mã hết hạn, xóa session và quay lại bước 1
        unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
        $thong_bao_loi = "Mã OTP đã hết hạn. Vui lòng yêu cầu mã mới.";
    } else {
        $step = 2;
        $email_da_gui = $_SESSION['otp_email'];
    }
}


// --- LOGIC XỬ LÝ FORM SUBMIT ---

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ------------------------------------
    // --- BƯỚC 1: XỬ LÝ GỬI MÃ OTP ---
    // ------------------------------------
    if (isset($_POST['action']) && $_POST['action'] == 'gui_ma') {
        $email = $conn->real_escape_string($_POST['email']);
        
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $thong_bao_loi = "Định dạng email không hợp lệ!";
        } else {
            // Kiểm tra email đã tồn tại chưa
            $sql_check = "SELECT id_nguoi_dung FROM nguoi_dung WHERE email = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("s", $email);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();

            if ($result_check->num_rows > 0) {
                $thong_bao_loi = "Email này đã được sử dụng. Vui lòng chọn email khác.";
            } else {
                // Email hợp lệ -> Tạo mã OTP và gửi mail
                $otp = rand(100000, 999999); // Tạo mã 6 số
                
                $mail = new PHPMailer(true);
                try {
                    $mail->SMTPDebug = 0; // Tắt gỡ lỗi
                    $mail->isSMTP();
                    $mail->Host       = 'smtp.gmail.com';
                    $mail->SMTPAuth   = true;
                    $mail->Username   = '20222027@eaut.edu.vn'; 
                    $mail->Password   = 'nzof znds lbba qkxv'; // Mật khẩu ứng dụng
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                    $mail->Port       = 465;
                    $mail->CharSet    = 'UTF-8';

                    $mail->setFrom('20222027@eaut.edu.vn', 'Web Ban Dien Thoai');
                    $mail->addAddress($email); 

                    $mail->isHTML(true);
                    $mail->Subject = 'Ma xac minh dang ky tai khoan';
                    $mail->Body    = "Mã xác minh (OTP) của bạn là: <b>$otp</b>.<br>"
                                   . "Mã này có hiệu lực trong 5 phút.<br>"
                                   . "Vui lòng không chia sẻ mã này cho bất kỳ ai.";
                    
                    $mail->send();

                    // Lưu OTP vào session và chuyển sang bước 2
                    $_SESSION['otp_email'] = $email;
                    $_SESSION['otp_code'] = $otp;
                    $_SESSION['otp_expires'] = time() + (5 * 60); // 5 phút
                    
                    $step = 2; // Chuyển sang bước 2
                    $email_da_gui = $email;
                    $thong_bao_thanh_cong = "Một mã OTP đã được gửi đến $email. Vui lòng kiểm tra email (cả mục Spam).";

                } catch (Exception $e) {
                    $thong_bao_loi = "Không thể gửi email. Lỗi: " . $mail->ErrorInfo;
                }
            }
        }

    } 
    // -----------------------------------------------
    // --- BƯỚC 2: XỬ LÝ XÁC MINH & TẠO TÀI KHOẢN ---
    // -----------------------------------------------
    elseif (isset($_POST['action']) && $_POST['action'] == 'xac_minh') {
        
        $email = $conn->real_escape_string($_POST['email']);
        $otp_nhap = $_POST['otp'];
        $mat_khau_nhap = $_POST['mat_khau'];
        $xac_nhan_mat_khau = $_POST['xac_nhan_mat_khau'];

        // Kiểm tra lại dữ liệu session
        if ($step != 2 || $_SESSION['otp_email'] != $email) {
            $thong_bao_loi = "Email không khớp hoặc phiên làm việc đã hết hạn. Vui lòng thử lại.";
            $step = 1; 
            unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
        } 
        // Kiểm tra mã OTP
        elseif ($_SESSION['otp_code'] != $otp_nhap) {
            $thong_bao_loi = "Mã OTP không chính xác. Vui lòng thử lại.";
            $step = 2; // Giữ ở bước 2
            $email_da_gui = $email;
        } 
        // Kiểm tra mật khẩu
        elseif ($mat_khau_nhap != $xac_nhan_mat_khau) {
            $thong_bao_loi = "Mật khẩu xác nhận không khớp.";
            $step = 2; // Giữ ở bước 2
            $email_da_gui = $email;
        } 
        // Mọi thứ hợp lệ -> Tạo tài khoản
        else {
            
            $mat_khau_bam = password_hash($mat_khau_nhap, PASSWORD_DEFAULT);
            $sql_insert = "INSERT INTO nguoi_dung (email, mat_khau, trang_thai_tai_khoan) 
                           VALUES (?, ?, 'hoat_dong')"; // Kích hoạt luôn
            
            $stmt_insert = $conn->prepare($sql_insert);
            $stmt_insert->bind_param("ss", $email, $mat_khau_bam);

            if ($stmt_insert->execute()) {
                // Lấy ID người dùng mới tạo
                $new_user_id = $conn->insert_id;

                // --- LOGIC ĐỒNG BỘ GIỎ HÀNG (SESSION -> CSDL) ---
                if (!empty($_SESSION['cart'])) {
                    $sql_merge_cart = "INSERT INTO gio_hang (id_nguoi_dung, id_san_pham, so_luong) 
                                       VALUES (?, ?, ?) 
                                       ON DUPLICATE KEY UPDATE so_luong = so_luong + VALUES(so_luong)";
                    $stmt_merge = $conn->prepare($sql_merge_cart);
                    
                    foreach ($_SESSION['cart'] as $item) {
                        $stmt_merge->bind_param("iii", $new_user_id, $item['id_san_pham'], $item['so_luong']);
                        $stmt_merge->execute();
                    }
                    unset($_SESSION['cart']); // Xóa giỏ hàng session
                }
                // --- KẾT THÚC ĐỒNG BỘ ---

                // Xóa session OTP
                unset($_SESSION['otp_code'], $_SESSION['otp_email'], $_SESSION['otp_expires']);
                
                // Đặt thông báo thành công và chuyển hướng đến trang ĐĂNG NHẬP
                $_SESSION['dang_ky_thanh_cong'] = "Đăng ký thành công! Giỏ hàng (nếu có) đã được lưu. Vui lòng đăng nhập.";
                header("Location: dang_nhap.php");
                exit();
            } else {
                $thong_bao_loi = "Lỗi CSDL: Không thể tạo tài khoản. Vui lòng thử lại.";
                $step = 2;
                $email_da_gui = $email;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký tài khoản</title>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Segoe UI', sans-serif;
            background: linear-gradient(to right, #74ebd5, #ACB6E5);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            background: #ffffff;
            padding: 35px 30px;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
            width: 100%;
            max-width: 420px;
            animation: fadeIn 0.6s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #333;
            font-size: 24px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }

        .form-group input[type="email"],
        .form-group input[type="password"],
        .form-group input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        .form-group input[type="email"]:focus,
        .form-group input[type="password"]:focus,
        .form-group input[type="text"]:focus {
            border-color: #007bff;
            outline: none;
        }

        .form-group input[readonly] {
            background-color: #f1f1f1;
            cursor: not-allowed;
        }

        .form-group small {
            font-size: 12px;
            display: block;
            margin-top: 5px;
        }

        .form-group small a {
            color: #007bff;
            text-decoration: none;
        }

        .form-group small a:hover {
            text-decoration: underline;
        }

        button {
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            width: 100%;
        }

        button:hover {
            background-color: #0056b3;
        }

        .message {
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
        }

        .message.error {
            background-color: #f8d7da;
            color: #721c24;
            word-wrap: break-word;
        }

        .links-container {
            text-align: center;
            margin-top: 20px;
            font-size: 14px;
        }
        
        .links-container a {
            color: #007bff;
            text-decoration: none;
        }
        
        .links-container a:hover {
            text-decoration: underline;
        }

        /* === CSS MỚI CHO NÚT QUAY LẠI === */
        .back-to-home {
            text-align: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
            font-size: 14px;
        }
        .back-to-home a {
            color: #555;
            text-decoration: none;
            font-weight: 500;
        }
        .back-to-home a:hover {
            text-decoration: underline;
        }
        /* === HẾT CSS MỚI === */
    </style>
</head>
<body>
    <div class="container">

        <?php if (!empty($thong_bao_thanh_cong)): ?>
            <div class="message success">
                <?php echo $thong_bao_thanh_cong; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($thong_bao_loi)): ?>
            <div class="message error">
                <?php echo $thong_bao_loi; ?>
            </div>
        <?php endif; ?>

        <?php if ($step == 1): ?>
            <h2>📧 Bước 1: Nhập Email</h2>
            <form action="dang_ky.php" method="POST">
                <input type="hidden" name="action" value="gui_ma">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" placeholder="Nhập email của bạn..." required>
                </div>
                <button type="submit">Gửi Mã Xác Minh</button>
            </form>

        <?php else: ?>
            <h2>🔐 Bước 2: Xác Minh & Đăng Ký</h2>
            <form action="dang_ky.php" method="POST">
                <input type="hidden" name="action" value="xac_minh">
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email_da_gui); ?>" readonly>
                    <small><a href="dang_ky.php?action=doi_email">Đổi email khác</a></small>
                </div>
                <div class="form-group">
                    <label for="otp">Mã OTP:</label>
                    <input type="text" id="otp" name="otp" placeholder="Kiểm tra email của bạn..." required>
                </div>
                <div class="form-group">
                    <label for="mat_khau">Mật khẩu mới:</label>
                    <input type="password" id="mat_khau" name="mat_khau" required>
                </div>
                <div class="form-group">
                    <label for="xac_nhan_mat_khau">Xác nhận mật khẩu:</label>
                    <input type="password" id="xac_nhan_mat_khau" name="xac_nhan_mat_khau" required>
                </div>
                <button type="submit">Hoàn Tất Đăng Ký</button>
            </form>
        <?php endif; ?>

        <div class="links-container">
            <p>Đã có tài khoản? <a href="dang_nhap.php">Đăng nhập ngay</a></p>
        </div>
        
        <div class="back-to-home">
            <a href="index.php">&larr; Quay lại trang chủ</a>
        </div>

    </div>
</body>
</html>