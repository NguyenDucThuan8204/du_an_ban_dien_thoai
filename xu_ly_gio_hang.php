<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// (MỚI) XÓA SESSION CHECKOUT CŨ KHI GIỎ HÀNG THAY ĐỔI
// Điều này sửa lỗi "thanh toán cũ"
unset($_SESSION['checkout_items']);
unset($_SESSION['checkout_discount_amount']);
unset($_SESSION['checkout_discount_code']);
unset($_SESSION['id_ma_giam_gia']);
unset($_SESSION['ma_don_hang_tam']);
// ===================================================

require 'dung_chung/ket_noi_csdl.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';
$id_san_pham = (int)($_POST['id_san_pham'] ?? 0);
$so_luong = (int)($_POST['so_luong'] ?? 1);
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;

$response = ['success' => false, 'message' => 'Hành động không hợp lệ.'];

if ($id_san_pham == 0) {
    $response['message'] = 'ID sản phẩm không hợp lệ.';
    echo json_encode($response);
    exit();
}
if ($so_luong <= 0 && $action == 'update') {
    $action = 'remove'; // Nếu giảm về 0, coi như xóa
}

try {
    // Xử lý Cập nhật số lượng
    if ($action == 'update') {
        if ($id_nguoi_dung > 0) {
            $stmt = $conn->prepare("UPDATE gio_hang SET so_luong = ? WHERE id_nguoi_dung = ? AND id_san_pham = ?");
            $stmt->bind_param("iii", $so_luong, $id_nguoi_dung, $id_san_pham);
            $stmt->execute();
        } else {
            $_SESSION['cart'][$id_san_pham]['so_luong'] = $so_luong;
        }
    } 
    // Xử lý Xóa
    elseif ($action == 'remove') {
        if ($id_nguoi_dung > 0) {
            $stmt = $conn->prepare("DELETE FROM gio_hang WHERE id_nguoi_dung = ? AND id_san_pham = ?");
            $stmt->bind_param("ii", $id_nguoi_dung, $id_san_pham);
            $stmt->execute();
        } else {
            unset($_SESSION['cart'][$id_san_pham]);
        }
    } 
    // Xử lý Thêm (Giả sử file này cũng xử lý 'add')
    elseif ($action == 'add') {
         if ($id_nguoi_dung > 0) {
            $sql_check = "SELECT so_luong FROM gio_hang WHERE id_nguoi_dung = ? AND id_san_pham = ?";
            $stmt_check = $conn->prepare($sql_check);
            $stmt_check->bind_param("ii", $id_nguoi_dung, $id_san_pham);
            $stmt_check->execute();
            $result_check = $stmt_check->get_result();
            if ($result_check->num_rows > 0) {
                // Đã có -> Cập nhật
                $row = $result_check->fetch_assoc();
                $so_luong_moi = $row['so_luong'] + $so_luong;
                $stmt_update = $conn->prepare("UPDATE gio_hang SET so_luong = ? WHERE id_nguoi_dung = ? AND id_san_pham = ?");
                $stmt_update->bind_param("iii", $so_luong_moi, $id_nguoi_dung, $id_san_pham);
                $stmt_update->execute();
            } else {
                // Chưa có -> Thêm mới
                $stmt_insert = $conn->prepare("INSERT INTO gio_hang (id_nguoi_dung, id_san_pham, so_luong) VALUES (?, ?, ?)");
                $stmt_insert->bind_param("iii", $id_nguoi_dung, $id_san_pham, $so_luong);
                $stmt_insert->execute();
            }
         } else {
             // Xử lý session
             if (isset($_SESSION['cart'][$id_san_pham])) {
                 $_SESSION['cart'][$id_san_pham]['so_luong'] += $so_luong;
             } else {
                 $_SESSION['cart'][$id_san_pham] = ['so_luong' => $so_luong];
             }
         }
    }
    
    // TÍNH TOÁN LẠI TỔNG TIỀN ĐỂ TRẢ VỀ
    $tong_tien_hang = 0;
    $cart_count = 0;
    $item_total = 0;
    $phi_van_chuyen = 30000;
    
    if ($id_nguoi_dung > 0) {
        $sql_cart = "SELECT g.id_san_pham, g.so_luong, s.gia_ban 
                     FROM gio_hang g JOIN san_pham s ON g.id_san_pham = s.id 
                     WHERE g.id_nguoi_dung = ?";
        $stmt_cart = $conn->prepare($sql_cart);
        $stmt_cart->bind_param("i", $id_nguoi_dung);
        $stmt_cart->execute();
        $result_cart = $stmt_cart->get_result();
        while ($row = $result_cart->fetch_assoc()) {
            $thanh_tien = $row['gia_ban'] * $row['so_luong'];
            $tong_tien_hang += $thanh_tien;
            if ($row['id_san_pham'] == $id_san_pham) {
                $item_total = $thanh_tien;
            }
            $cart_count++;
        }
    } else {
        if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
            $item_ids = array_keys($_SESSION['cart']);
            $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
            $sql_products = "SELECT id, gia_ban FROM san_pham WHERE id IN ($placeholders)";
            $stmt_products = $conn->prepare($sql_products);
            $stmt_products->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
            $stmt_products->execute();
            $products_result = $stmt_products->get_result();
            
            $product_prices = [];
            while($row = $products_result->fetch_assoc()) {
                $product_prices[$row['id']] = $row['gia_ban'];
            }
            
            foreach($_SESSION['cart'] as $id_sp_session => $item) {
                if (isset($product_prices[$id_sp_session])) {
                    $so_luong_session = (int)$item['so_luong'];
                    $thanh_tien = $product_prices[$id_sp_session] * $so_luong_session;
                    $tong_tien_hang += $thanh_tien;
                    if ($id_sp_session == $id_san_pham) {
                        $item_total = $thanh_tien;
                    }
                    $cart_count++;
                }
            }
        }
    }
    
    $tong_cong = $tong_tien_hang + $phi_van_chuyen;
    
    $response['success'] = true;
    $response['message'] = 'Cập nhật giỏ hàng thành công.';
    $response['item_total_formatted'] = number_format($item_total, 0, ',', '.') . 'đ';
    $response['subtotal_formatted'] = number_format($tong_tien_hang, 0, ',', '.') . 'đ';
    $response['total_formatted'] = number_format($tong_cong, 0, ',', '.') . 'đ';
    $response['new_cart_count'] = $cart_count;

} catch (Exception $e) {
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
?>