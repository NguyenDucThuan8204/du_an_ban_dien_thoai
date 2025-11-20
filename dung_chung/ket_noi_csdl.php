
<?php
// Thông tin kết nối CSDL (Cơ sở dữ liệu)
$ten_may_chu = "localhost"; // Hầu hết XAMPP dùng "localhost"
$ten_dang_nhap = "root";    // Tên đăng nhập CSDL mặc định của XAMPP
$mat_khau_csdl = "";        // Mật khẩu CSDL mặc định của XAMPP là rỗng
$ten_csdl = "du_an_ban_dien_thoai"; // Tên CSDL bạn đã tạo

// Tạo kết nối bằng MySQLi (i = improved)
$conn = new mysqli($ten_may_chu, $ten_dang_nhap, $mat_khau_csdl, $ten_csdl);

// Kiểm tra kết nối
if ($conn->connect_error) {
    // Nếu kết nối thất bại, dừng chương trình và báo lỗi
    die("Kết nối CSDL thất bại: " . $conn->connect_error);
}

// Thiết lập mã hóa UTF-8 (Rất quan trọng để hiển thị tiếng Việt)
$conn->set_charset("utf8mb4");

// Bạn có thể bỏ dòng echo này sau khi đã test thành công
// echo "Kết nối CSDL thành công!"; 

?>