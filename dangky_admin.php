<?php
// Kết nối MySQL
$servername = "localhost";
$username = "root"; // đổi nếu cần
$password = "";     // đổi nếu có mật khẩu
$dbname = "du_an_ban_dien_thoai"; // đổi thành tên CSDL thật

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Kết nối thất bại: " . $conn->connect_error);
}

// Xử lý khi người dùng nhấn nút Đăng ký
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $ho = trim($_POST["ho"]);
    $ten = trim($_POST["ten"]);
    $email = trim($_POST["email"]);
    $so_dien_thoai = trim($_POST["so_dien_thoai"]);
    $so_cccd = trim($_POST["so_cccd"]);
    $mat_khau = trim($_POST["mat_khau"]);

    // Kiểm tra dữ liệu bắt buộc
    if (empty($email) || empty($mat_khau)) {
        echo "<p style='color:red'>Email và mật khẩu là bắt buộc!</p>";
    } else {
        // Mã hóa mật khẩu an toàn hơn MD5
        $mat_khau_mahoa = password_hash($mat_khau, PASSWORD_DEFAULT);

        // Kiểm tra email có tồn tại chưa
        $check_sql = "SELECT * FROM nguoi_dung WHERE email = ?";
        $stmt = $conn->prepare($check_sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo "<p style='color:red'>Email này đã tồn tại!</p>";
        } else {
            // Thêm tài khoản quản trị
            $insert_sql = "INSERT INTO nguoi_dung (ho, ten, email, so_dien_thoai, so_cccd, mat_khau, vai_tro, trang_thai_tai_khoan)
                           VALUES (?, ?, ?, ?, ?, ?, 'quan_tri', 'hoat_dong')";
            $stmt = $conn->prepare($insert_sql);
            $stmt->bind_param("ssssss", $ho, $ten, $email, $so_dien_thoai, $so_cccd, $mat_khau_mahoa);

            if ($stmt->execute()) {
                echo "<p style='color:green'>✅ Tạo tài khoản quản trị thành công!</p>";
            } else {
                echo "<p style='color:red'>❌ Lỗi: " . $conn->error . "</p>";
            }
        }

        $stmt->close();
    }
}
?>

<!-- Giao diện HTML đơn giản -->
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đăng ký Quản trị</title>
</head>
<body>
    <h2>Đăng ký tài khoản quản trị</h2>
    <form method="POST" action="">
        <label>Họ:</label><br>
        <input type="text" name="ho"><br><br>

        <label>Tên:</label><br>
        <input type="text" name="ten"><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Số điện thoại:</label><br>
        <input type="text" name="so_dien_thoai"><br><br>

        <label>Số CCCD:</label><br>
        <input type="text" name="so_cccd"><br><br>

        <label>Mật khẩu:</label><br>
        <input type="password" name="mat_khau" required><br><br>

        <button type="submit">Đăng ký quản trị</button>
    </form>
</body>
</html>
