<?php
// === CẤU HÌNH AN TOÀN ===
error_reporting(0);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require 'dung_chung/ket_noi_csdl.php';
header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

// === KIỂM TRA PHIÊN NGƯỜI DÙNG ===
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;
$items_to_buy_session = $_SESSION['checkout_items'] ?? [];

if ($id_nguoi_dung == 0 || empty($items_to_buy_session)) {
    $response['message'] = 'Phiên làm việc hết hạn hoặc giỏ hàng trống.';
    echo json_encode($response);
    $conn->close();
    exit;
}

// === NHẬN DỮ LIỆU GỬI LÊN ===
$action = $_POST['action'] ?? '';
$ma_code = trim($_POST['ma_code'] ?? '');

if ($action !== 'apply' || $ma_code === '') {
    $response['message'] = 'Hành động không hợp lệ hoặc thiếu mã.';
    echo json_encode($response);
    $conn->close();
    exit;
}

// === KIỂM TRA MÃ GIẢM GIÁ ===
$sql = "SELECT * FROM ma_giam_gia 
        WHERE ma_code = ? 
        AND trang_thai = 'hoat_dong'
        AND ngay_ket_thuc >= CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $ma_code);
$stmt->execute();
$coupon = $stmt->get_result()->fetch_assoc();

if (!$coupon) {
    $response['message'] = 'Mã giảm giá không hợp lệ hoặc đã hết hạn.';
    echo json_encode($response);
    $conn->close();
    exit;
}

// === KIỂM TRA SỐ LƯỢNG CÒN LẠI ===
if (!is_null($coupon['so_luong_tong']) && $coupon['so_luong_da_dung'] >= $coupon['so_luong_tong']) {
    $response['message'] = 'Mã giảm giá này đã hết lượt sử dụng.';
    echo json_encode($response);
    $conn->close();
    exit;
}

// === TÍNH TỔNG GIÁ TRỊ GIỎ HÀNG ===
$item_ids = array_keys($items_to_buy_session);
if (empty($item_ids)) {
    $response['message'] = 'Giỏ hàng trống.';
    echo json_encode($response);
    $conn->close();
    exit;
}

$placeholders = implode(',', array_fill(0, count($item_ids), '?'));
$sql_products = "SELECT id, gia_ban FROM san_pham WHERE id IN ($placeholders)";
$stmt_products = $conn->prepare($sql_products);
$stmt_products->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
$stmt_products->execute();
$result = $stmt_products->get_result();

$tong_tien_hang = 0;
while ($row = $result->fetch_assoc()) {
    $so_luong = (int)($items_to_buy_session[$row['id']] ?? 0);
    $tong_tien_hang += $row['gia_ban'] * $so_luong;
}

// === TÍNH GIẢM GIÁ ===
$phan_tram_giam = (int)$coupon['phan_tram_giam'];
$so_tien_giam = ($tong_tien_hang * $phan_tram_giam) / 100;
$tong_tien_sau_giam = max(0, $tong_tien_hang - $so_tien_giam);

// === LƯU SESSION ĐỂ THANH TOÁN ===
$_SESSION['checkout_discount_code'] = $ma_code;
$_SESSION['checkout_discount_amount'] = $so_tien_giam;
$_SESSION['id_ma_giam_gia'] = $coupon['id_giam_gia'];

// === TRẢ KẾT QUẢ JSON ===
$response = [
    'success' => true,
    'message' => 'Áp dụng mã giảm giá thành công!',
    'subtotal_formatted' => number_format($tong_tien_hang, 0, ',', '.') . 'đ',
    'discount_formatted' => '- ' . number_format($so_tien_giam, 0, ',', '.') . 'đ',
    'total_formatted' => number_format($tong_tien_sau_giam, 0, ',', '.') . 'đ'
];

echo json_encode($response);
$conn->close();
exit;
?>
