
<?php
// 1. Luôn bắt đầu session ở đầu file
// Dù là hủy session, bạn vẫn phải "start" nó để có thể truy cập
session_start();

// 2. Xóa tất cả các biến đã lưu trong session
// Ghi đè mảng $_SESSION thành một mảng rỗng
$_SESSION = array();

// 3. (Tùy chọn - Nâng cao) Hủy cookie session
// Nếu hệ thống của bạn dùng session cookie (mặc định),
// đoạn code này sẽ xóa cookie đó khỏi trình duyệt người dùng.
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// 4. Hủy hoàn toàn session trên server
// Đây là hàm quan trọng nhất
session_destroy();

// 5. Chuyển hướng người dùng về trang đăng nhập
header("Location: index.php");

// 6. Luôn exit() sau khi chuyển hướng để đảm bảo script dừng chạy
exit();
?>