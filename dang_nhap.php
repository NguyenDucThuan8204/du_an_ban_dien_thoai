<?php
// 1. BẮT ĐẦU PHIÊN LÀM VIỆC (SESSION)
session_start(); 

// Nếu người dùng đã đăng nhập rồi, tự động chuyển hướng
if (isset($_SESSION['id_nguoi_dung'])) {
    if ($_SESSION['vai_tro'] == 'quan_tri') {
        header("Location: quan_tri/index.php");
    } else {
        header("Location: index.php");
    }
    exit();
}

// 2. NẠP FILE KẾT NỐI CSDL
require 'dung_chung/ket_noi_csdl.php';

// 3. KIỂM TRA THÔNG BÁO TỪ TRANG ĐĂNG KÝ (MỚI)
$thong_bao_thanh_cong = "";
if (isset($_SESSION['dang_ky_thanh_cong'])) {
    $thong_bao_thanh_cong = $_SESSION['dang_ky_thanh_cong'];
    // Xóa session ngay sau khi lấy, để nó không hiện lại
    unset($_SESSION['dang_ky_thanh_cong']); 
}
// =============================================

// Biến lưu trữ thông báo lỗi
$thong_bao_loi = "";

// 4. XỬ LÝ KHI NGƯỜI DÙNG NHẤN NÚT "ĐĂNG NHẬP"
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $email = $conn->real_escape_string($_POST['email']);
    $mat_khau_nhap = $_POST['mat_khau']; 

    // 5. TRUY VẤN CSDL
    $sql_check = "SELECT id_nguoi_dung, ho, ten, email, mat_khau, vai_tro, trang_thai_tai_khoan 
                  FROM nguoi_dung 
                  WHERE email = ?";
                  
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows == 1) {
        $nguoi_dung = $result->fetch_assoc();

        // 6. KIỂM TRA MẬT KHẨU
        if (password_verify($mat_khau_nhap, $nguoi_dung['mat_khau'])) {
            
            // 7. KIỂM TRA TRẠNG THÁI TÀI KHOẢN
            if ($nguoi_dung['trang_thai_tai_khoan'] == 'bi_cam') {
                $thong_bao_loi = "Tài khoản của bạn đã bị khóa.";
            } elseif ($nguoi_dung['trang_thai_tai_khoan'] == 'cho_xac_minh') {
                $thong_bao_loi = "Tài khoản của bạn chưa được kích hoạt.";
            } else {
                // 8. ĐĂNG NHẬP THÀNH CÔNG!
                $_SESSION['id_nguoi_dung'] = $nguoi_dung['id_nguoi_dung'];
                $_SESSION['email'] = $nguoi_dung['email'];
                $_SESSION['ten'] = $nguoi_dung['ten'];
                $_SESSION['vai_tro'] = $nguoi_dung['vai_tro'];

                // 9. PHÂN QUYỀN VÀ ĐIỀU HƯỚNG
                if ($nguoi_dung['vai_tro'] == 'quan_tri') {
                    header("Location: quan_tri/index.php");
                } else {
                    header("Location: index.php");
                }
                exit(); 
            }
        } else {
            $thong_bao_loi = "Sai email hoặc mật khẩu.";
        }
    } else {
        $thong_bao_loi = "Sai email hoặc mật khẩu.";
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng nhập</title>
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
            max-width: 400px;
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

        label {
            font-weight: 600;
            margin-bottom: 8px;
            display: block;
            color: #555;
        }

        input[type="email"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 15px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus,
        input[type="password"]:focus {
            border-color: #007bff;
            outline: none;
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
        }

        .links {
            display: flex;
            justify-content: space-between;
            font-size: 14px;
            margin-top: 20px;
        }

        .links a {
            color: #007bff;
            text-decoration: none;
        }

        .links a:hover {
            text-decoration: underline;
        }

        /* === CSS MỚI CHO NÚT QUAY LẠI === */
        .back-to-home {
            text-align: center;
            margin-top: 20px;
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
        <h2>🔐 Đăng Nhập</h2>

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

        <form action="dang_nhap.php" method="POST">
            <div class="form-group">
                <label for="email">📧 Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="mat_khau">🔑 Mật khẩu:</label>
                <input type="password" id="mat_khau" name="mat_khau" required>
            </div>
            <button type="submit">Đăng Nhập</button>
        </form>

        <div class="links">
            <a href="quen_mat_khau.php">Quên mật khẩu?</a>
            <a href="dang_ky.php">Tạo tài khoản mới</a>
        </div>

        <!-- === NÚT QUAY LẠI ĐÃ THÊM === -->
        <div class="back-to-home">
            <a href="index.php">&larr; Quay lại trang chủ</a>
        </div>

    </div>
</body>
</html>