<?php
// 1. BẮT ĐẦU SESSION VÀ KẾT NỐI CSDL
session_start();
require 'dung_chung/ket_noi_csdl.php';

// 2. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['comment_error'] = "Bạn phải đăng nhập để bình luận.";
    header("Location: index.php"); 
    exit();
}

// 3. KIỂM TRA PHƯƠNG THỨC POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 4. LẤY DỮ LIỆU TỪ FORM
    $id_tin_tuc = (int)($_POST['id_tin_tuc'] ?? 0);
    $noi_dung = trim($_POST['noi_dung'] ?? ''); 
    $id_nguoi_dung = (int)$_SESSION['id_nguoi_dung'];
    
    $return_url = "chi_tiet_tin_tuc.php?id=" . $id_tin_tuc;

    // 5. KIỂM TRA DỮ LIỆU
    if ($id_tin_tuc <= 0 || empty($noi_dung) || $id_nguoi_dung <= 0) {
        $_SESSION['comment_error'] = "Dữ liệu bình luận không hợp lệ.";
        header("Location: $return_url");
        exit();
    }

    // 6. INSERT VÀO CSDL
    try {
        
        // --- SỬA ĐỔI DUY NHẤT Ở ĐÂY ---
        // Đổi từ 'cho_duyet' thành 'da_duyet'
        $sql_insert = "INSERT INTO binh_luan (id_tin_tuc, id_nguoi_dung, noi_dung, trang_thai) 
                       VALUES (?, ?, ?, 'da_duyet')";
        // --- KẾT THÚC SỬA ĐỔI ---
        
        $stmt = $conn->prepare($sql_insert);
        $stmt->bind_param("iis", $id_tin_tuc, $id_nguoi_dung, $noi_dung);
        
        if ($stmt->execute()) {
            // --- SỬA LẠI CÂU THÔNG BÁO ---
            $_SESSION['comment_message'] = "Bình luận của bạn đã được đăng thành công!";
        } else {
            $_SESSION['comment_error'] = "Lỗi: Không thể gửi bình luận. " . $conn->error;
        }
    } catch (Exception $e) {
        $_SESSION['comment_error'] = "Lỗi CSDL: " . $e->getMessage();
    }
    
    // 7. ĐÓNG KẾT NỐI VÀ CHUYỂN HƯỚNG
    $conn->close();
    header("Location: $return_url");
    exit();
    
} else {
    // Nếu không phải POST, đá về trang chủ
    header("Location: index.php");
    exit();
}
?>