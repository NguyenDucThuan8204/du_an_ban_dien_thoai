<?php
// 1. BẮT ĐẦU SESSION (BẮT BUỘC PHẢI Ở DÒNG ĐẦU TIÊN)
session_start(); 
require 'dung_chung/ket_noi_csdl.php';

// Kiểm tra xem hành động là gì
$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Lấy URL để quay lại
$return_url = $_POST['return_url'] ?? $_GET['return_url'] ?? 'gio_hang.php';

// Kiểm tra xem người dùng đã đăng nhập chưa
$is_logged_in = isset($_SESSION['id_nguoi_dung']);
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;

try {
    switch ($action) {
        
        case 'add':
            // (Giữ nguyên logic 'add' từ file trước)
            $id_san_pham = (int)$_POST['id_san_pham'];
            $so_luong = (int)$_POST['so_luong'];
            if ($id_san_pham <= 0 || $so_luong <= 0) throw new Exception("Dữ liệu không hợp lệ.");

            if ($is_logged_in) {
                $sql = "INSERT INTO gio_hang (id_nguoi_dung, id_san_pham, so_luong) 
                        VALUES (?, ?, ?) 
                        ON DUPLICATE KEY UPDATE so_luong = so_luong + VALUES(so_luong)";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("iii", $id_nguoi_dung, $id_san_pham, $so_luong);
                $stmt->execute();
            } else {
                if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
                if (isset($_SESSION['cart'][$id_san_pham])) {
                    $_SESSION['cart'][$id_san_pham]['so_luong'] += $so_luong;
                } else {
                    $_SESSION['cart'][$id_san_pham] = ['id_san_pham' => $id_san_pham, 'so_luong' => $so_luong, 'selected' => true];
                }
            }
            $_SESSION['thong_bao_gio_hang'] = "Đã thêm sản phẩm vào giỏ hàng!";
            break;

        case 'update':
            // (Giữ nguyên logic 'update' từ file trước)
            $id_san_pham = (int)$_POST['id_san_pham'];
            $so_luong = (int)$_POST['so_luong'];
            if ($id_san_pham <= 0) throw new Exception("Sản phẩm không hợp lệ.");
            if ($so_luong <= 0) $action = 'remove';

            if ($action == 'update') {
                if ($is_logged_in) {
                    $sql = "UPDATE gio_hang SET so_luong = ? WHERE id_nguoi_dung = ? AND id_san_pham = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("iii", $so_luong, $id_nguoi_dung, $id_san_pham);
                    $stmt->execute();
                } else {
                    if (isset($_SESSION['cart'][$id_san_pham])) $_SESSION['cart'][$id_san_pham]['so_luong'] = $so_luong;
                }
                $_SESSION['thong_bao_gio_hang'] = "Cập nhật số lượng thành công!";
            }
            if ($action != 'remove') break;
        
        case 'remove':
            // (Giữ nguyên logic 'remove' từ file trước)
            $id_san_pham = (int)($_POST['id_san_pham'] ?? $_GET['id_san_pham'] ?? 0);
            if ($id_san_pham <= 0) throw new Exception("Sản phẩm không hợp lệ.");

            if ($is_logged_in) {
                $sql = "DELETE FROM gio_hang WHERE id_nguoi_dung = ? AND id_san_pham = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ii", $id_nguoi_dung, $id_san_pham);
                $stmt->execute();
            } else {
                if (isset($_SESSION['cart'][$id_san_pham])) unset($_SESSION['cart'][$id_san_pham]);
            }
            $_SESSION['thong_bao_gio_hang'] = "Đã xóa sản phẩm khỏi giỏ hàng.";
            break;
            
        case 'clear':
            // (Giữ nguyên logic 'clear' từ file trước)
            if ($is_logged_in) {
                $sql = "DELETE FROM gio_hang WHERE id_nguoi_dung = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $id_nguoi_dung);
                $stmt->execute();
            } else {
                unset($_SESSION['cart']);
            }
            $_SESSION['thong_bao_gio_hang'] = "Đã xóa toàn bộ giỏ hàng.";
            break;
        
        // --- LOGIC MỚI: MUA LẠI ---
        case 'rebuy':
            if (!$is_logged_in) {
                throw new Exception("Bạn phải đăng nhập để thực hiện chức năng này.");
            }
            
            $id_don_hang = (int)($_GET['id_don_hang'] ?? 0);
            if ($id_don_hang <= 0) {
                throw new Exception("Đơn hàng không hợp lệ.");
            }

            // 1. Kiểm tra đơn hàng này có phải của bạn không
            $sql_check_owner = "SELECT id_don_hang FROM don_hang WHERE id_don_hang = ? AND id_nguoi_dung = ?";
            $stmt_check = $conn->prepare($sql_check_owner);
            $stmt_check->bind_param("ii", $id_don_hang, $id_nguoi_dung);
            $stmt_check->execute();
            if ($stmt_check->get_result()->num_rows == 0) {
                throw new Exception("Lỗi bảo mật: Bạn không có quyền truy cập đơn hàng này.");
            }

            // 2. Lấy tất cả sản phẩm từ đơn hàng cũ
            $sql_get_items = "SELECT id_san_pham, so_luong FROM chi_tiet_don_hang WHERE id_don_hang = ?";
            $stmt_get = $conn->prepare($sql_get_items);
            $stmt_get->bind_param("i", $id_don_hang);
            $stmt_get->execute();
            $items = $stmt_get->get_result();

            // 3. Chuẩn bị câu lệnh thêm vào giỏ hàng
            $sql_insert = "INSERT INTO gio_hang (id_nguoi_dung, id_san_pham, so_luong) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE so_luong = so_luong + VALUES(so_luong)";
            $stmt_insert = $conn->prepare($sql_insert);

            // 4. Lặp và thêm vào giỏ
            while ($item = $items->fetch_assoc()) {
                if ($item['id_san_pham']) { // Chỉ thêm nếu sản phẩm vẫn còn tồn tại (chưa bị xóa)
                    $stmt_insert->bind_param("iii", $id_nguoi_dung, $item['id_san_pham'], $item['so_luong']);
                    $stmt_insert->execute();
                }
            }
            
            $_SESSION['thong_bao_gio_hang'] = "Đã thêm các sản phẩm từ đơn hàng cũ vào giỏ!";
            $return_url = 'gio_hang.php'; // Luôn chuyển về giỏ hàng
            break;

        default:
            throw new Exception("Hành động không hợp lệ.");
    }

} catch (Exception $e) {
    $_SESSION['thong_bao_loi_gio_hang'] = $e->getMessage();
}

// Chuyển hướng người dùng quay lại
header("Location: $return_url");
exit();
?>