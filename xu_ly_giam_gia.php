<?php
// Tắt mọi thông báo lỗi/warning
error_reporting(0);

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

header('Content-Type: application/json'); // Báo cho trình duyệt đây là JSON

// (SỬA LỖI) 1. Chuẩn bị $response
$response = ['success' => false, 'message' => 'Hành động không hợp lệ.'];

$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;
$items_to_buy_session = $_SESSION['checkout_items'] ?? [];
$phi_van_chuyen = 0; // 0đ
$hom_nay = date('Y-m-d');

if ($id_nguoi_dung == 0 || empty($items_to_buy_session)) {
    $response['message'] = 'Phiên làm việc hết hạn hoặc giỏ hàng trống.';
    echo json_encode($response); // Thoát sớm
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
        echo json_encode($response); // Thoát sớm
        $conn->close();
        exit();
    }
    
    // 5. (SỬA LỖI GIÁ) TÍNH TOÁN LẠI GIÁ
    try {
        $item_ids = array_keys($items_to_buy_session);
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        $sql_check_products = "SELECT id, gia_ban, gia_goc, phan_tram_giam_gia, ngay_bat_dau_giam, ngay_ket_thuc_giam 
                               FROM san_pham WHERE id IN ($placeholders)";
        $stmt_check = $conn->prepare($sql_check_products);
        $stmt_check->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
        $stmt_check->execute();
        $products_result = $stmt_check->get_result();
        
        $tong_tien_hang = 0;
        while ($row = $products_result->fetch_assoc()) {
            
            // (SỬA LỖI GIÁ) SAO CHÉP LOGIC TÍNH GIÁ TỪ GIO_HANG.PHP
            $gia_hien_thi = (float)$row['gia_ban'];
            $gia_cu = !empty($row['gia_goc']) ? (float)$row['gia_goc'] : null;
            $dang_giam_gia_theo_ngay = (
                !empty($row['ngay_bat_dau_giam']) && !empty($row['ngay_ket_thuc_giam']) &&
                $hom_nay >= $row['ngay_bat_dau_giam'] && $hom_nay <= $row['ngay_ket_thuc_giam']
            );
            if ($dang_giam_gia_theo_ngay && !empty($row['phan_tram_giam_gia'])) {
                $gia_cu = $row['gia_ban']; 
                $gia_hien_thi = $gia_cu * (1 - (float)$row['phan_tram_giam_gia'] / 100);
            } 
            else if (!empty($gia_cu) && $gia_cu > $gia_hien_thi) { /* Giảm theo giá gốc */ }
            else { $gia_cu = null; }
            
            // Lấy số lượng từ session
            $so_luong_value = $items_to_buy_session[$row['id']];
            if (is_array($so_luong_value)) {
                $so_luong = (int)($so_luong_value['so_luong'] ?? 0);
            } else {
                $so_luong = (int)$so_luong_value;
            }
            
            $tong_tien_hang += ($gia_hien_thi * $so_luong);
        }

        // 6. TÍNH TIỀN GIẢM (TỪ MÃ COUPON)
        $phan_tram_giam = (int)$coupon['phan_tram_giam'];
        $so_tien_giam_gia_coupon = ($tong_tien_hang * $phan_tram_giam) / 100;
        
        $tong_tien_final = $tong_tien_hang + $phi_van_chuyen - $so_tien_giam_gia_coupon;
        
        // 7. LƯU VÀO SESSION
        $_SESSION['checkout_discount_code'] = $ma_code;
        $_SESSION['checkout_discount_amount'] = $so_tien_giam_gia_coupon; // Lưu số tiền coupon
        $_SESSION['id_ma_giam_gia'] = $coupon['id_giam_gia']; 

        // 8. CẬP NHẬT $response (thay vì echo)
        $response['success'] = true;
        $response['message'] = 'Áp dụng mã giảm giá thành công!';
        $response['subtotal_formatted'] = number_format($tong_tien_hang, 0, ',', '.') . 'đ';
        $response['discount_formatted'] = '- ' . number_format($so_tien_giam_gia_coupon, 0, ',', '.') . 'đ';
        
        // *** ĐÂY LÀ DÒNG ĐÃ SỬA LỖI ***
        $response['total_formatted'] = number_format($tong_tien_final, 0, ',', '.') . 'đ';
        
        $response['new_total_raw'] = $tong_tien_final; 
        
    } catch (Exception $e) {
        $response['message'] = 'Lỗi máy chủ khi tính toán giá: ' . $e->getMessage();
    }
    
} // Hết (if $action == 'apply')

// (SỬA LỖI) 3. Chỉ echo MỘT LẦN ở cuối cùng
echo json_encode($response);
$conn->close();
exit();
?>