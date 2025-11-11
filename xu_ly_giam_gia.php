<?php
// Tắt mọi thông báo lỗi/warning (như 'session_start already')
error_reporting(0); 

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

header('Content-Type: application/json'); // Báo cho trình duyệt đây là JSON

// 1. CHUẨN BỊ
$response = ['success' => false, 'message' => ''];
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;
$items_to_buy_session = $_SESSION['checkout_items'] ?? [];
$phi_van_chuyen = 30000; // Phải giống với phí ở trang thanh_toan.php

if ($id_nguoi_dung == 0 || empty($items_to_buy_session)) {
    $response['message'] = 'Phiên làm việc hết hạn hoặc giỏ hàng trống.';
    echo json_encode($response);
    $conn->close();
    exit();
}

// 2. LẤY HÀNH ĐỘNG VÀ MÃ
$action = $_POST['action'] ?? '';
$ma_code = $conn->real_escape_string($_POST['ma_code'] ?? '');

if ($action == 'apply' && !empty($ma_code)) {
    
    // 3. KIỂM TRA MÃ TRONG CSDL
    $sql = "SELECT * FROM ma_giam_gia 
            WHERE ma_code = ? AND trang_thai = 'hoat_dong' AND ngay_ket_thuc >= CURDATE()";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $ma_code);
    $stmt->execute();
    $coupon = $stmt->get_result()->fetch_assoc();

    if (!$coupon) {
        $response['message'] = 'Mã giảm giá không hợp lệ hoặc đã hết hạn.';
        echo json_encode($response);
        $conn->close();
        exit();
    }
    
    // (Kiểm tra số lượng, v.v... nếu cần)
    
    // 5. TÍNH TOÁN LẠI GIÁ
    try {
        $item_ids = array_keys($items_to_buy_session);
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        $sql_check_products = "SELECT id, gia_ban FROM san_pham WHERE id IN ($placeholders)";
        $stmt_check = $conn->prepare($sql_check_products);
        $stmt_check->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
        $stmt_check->execute();
        $products_result = $stmt_check->get_result();
        
        $tong_tien_hang = 0;
        while ($row = $products_result->fetch_assoc()) {
            $so_luong = (int)($items_to_buy_session[$row['id']] ?? 0);
            $tong_tien_hang += ($row['gia_ban'] * $so_luong);
        }

        // 6. TÍNH TIỀN GIẢM
        $phan_tram_giam = (int)$coupon['phan_tram_giam'];
        $so_tien_giam_gia = ($tong_tien_hang * $phan_tram_giam) / 100;
        
        // (MỚI) Tính tổng tiền cuối cùng (bao gồm cả phí ship)
        $tong_tien_final = $tong_tien_hang + $phi_van_chuyen - $so_tien_giam_gia;
        
        // 7. LƯU VÀO SESSION
        $_SESSION['checkout_discount_code'] = $ma_code;
        $_SESSION['checkout_discount_amount'] = $so_tien_giam_gia;
        $_SESSION['id_ma_giam_gia'] = $coupon['id_giam_gia']; 

        // 8. TRẢ KẾT QUẢ VỀ
        $response['success'] = true;
        $response['message'] = 'Áp dụng mã giảm giá thành công!';
        $response['subtotal_formatted'] = number_format($tong_tien_hang, 0, ',', '.') . 'đ';
        $response['discount_formatted'] = '- ' . number_format($so_tien_giam_gia, 0, ',', '.') . 'đ';
        $response['total_formatted'] = number_format($tong_tien_final, 0, ',', '.') . 'đ';
        
        // === (DÒNG QUAN TRỌNG NHẤT) ===
        $response['new_total_raw'] = $tong_tien_final; // Gửi số tiền (dạng số)
        
        echo json_encode($response);

    } catch (Exception $e) {
        $response['message'] = 'Lỗi máy chủ khi tính toán giá: ' . $e->getMessage();
        echo json_encode($response);
    }
    
} else {
    $response['message'] = 'Hành động không hợp lệ.';
    echo json_encode($response);
}

$conn->close();
exit(); // Đảm bảo không có gì khác được in ra
?>