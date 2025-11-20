<?php
// 1. BẮT ĐẦU SESSION
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// 2. ĐỊNH NGHĨA ĐƯỜNG DẪN GỐC (SỬA LỖI ROOT_PATH)
if (!defined('ROOT_PATH')) {
    // __DIR__ là C:...\du_an_ban_dien_thoai\quan_tri
    // ROOT_PATH sẽ là C:...\du_an_ban_dien_thoai/
    define('ROOT_PATH', dirname(__DIR__) . '/');
}

// 3. ĐỊNH NGHĨA URL GỐC (CHO ẢNH VÀ LINK)
if (!defined('BASE_URL')) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];
    // Lấy thư mục gốc của dự án
    $script_name = str_replace('\\', '/', $_SERVER['SCRIPT_NAME']);
    // dirname($script_name) sẽ là /du_an_ban_dien_thoai/quan_tri
    $project_folder = dirname(dirname($script_name)); 
    if ($project_folder == '/' || $project_folder == '\\') {
        $project_folder = ''; // Nếu ở gốc
    }
    define('BASE_URL', $protocol . '://' . $host . $project_folder . '/');
}

// 4. KẾT NỐI CSDL (Dùng ROOT_PATH)
if (!isset($conn) || !$conn) {
    // Dùng require_once để đảm bảo chỉ gọi 1 lần
    require_once ROOT_PATH . 'dung_chung/ket_noi_csdl.php';
}

// 5. GỌI FILE BẢO VỆ ADMIN (Dùng ROOT_PATH)
require_once ROOT_PATH . 'quan_tri/kiem_tra_quan_tri.php'; 

// (Biến $conn, $_SESSION, và các hằng số đã sẵn sàng)
?>