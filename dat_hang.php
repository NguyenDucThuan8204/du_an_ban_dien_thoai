<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 1. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['id_nguoi_dung'])) {
    header("Location: dang_nhap.php");
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];
$upload_dir_bills = 'tai_len/bills/'; // Thư mục lưu bill

// 2. HÀM HỖ TRỢ UPLOAD ẢNH (Cho Bill)
function xu_ly_tai_anh_bill($file_input_name, $upload_dir) {
    if (isset($_FILES[$file_input_name]) && $_FILES[$file_input_name]['error'] == 0) {
        $file = $_FILES[$file_input_name];
        $file_tmp = $file['tmp_name'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $cac_dinh_dang_cho_phep = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        
        if (in_array($file_ext, $cac_dinh_dang_cho_phep)) {
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            $ten_file_moi = 'bill_' . $_SESSION['id_nguoi_dung'] . '_' . uniqid() . time() . '.' . $file_ext;
            $duong_dan_dich = $upload_dir . $ten_file_moi;
            
            if (move_uploaded_file($file_tmp, $duong_dan_dich)) {
                return $ten_file_moi; 
            }
        }
    }
    return null; 
}

// 3. LẤY DỮ LIỆU TỪ FORM VÀ SESSION
$items_to_buy = $_SESSION['checkout_items'] ?? [];
if (empty($items_to_buy)) {
    header("Location: gio_hang.php");
    exit();
}

$ten_nguoi_nhan = $conn->real_escape_string($_POST['ten_nguoi_nhan']);
$so_dien_thoai_nhan = $conn->real_escape_string($_POST['so_dien_thoai_nhan']);
$dia_chi_giao_hang = $conn->real_escape_string($_POST['dia_chi_giao_hang']);
$ghi_chu = $conn->real_escape_string($_POST['ghi_chu']);
$phuong_thuc_thanh_toan = $conn->real_escape_string($_POST['phuong_thuc_thanh_toan']);
$phuong_thuc_van_chuyen = "Giao hàng tiêu chuẩn"; // Giả sử

// Lấy mã đơn hàng từ session (đã tạo ở trang thanh toán)
$ma_don_hang = $_SESSION['ma_don_hang_tam'] ?? ('DH_LOI' . time());

// 4. (SỬA) XỬ LÝ THANH TOÁN ONLINE VÀ UPLOAD BILL
$anh_bill_filename = null;
$trang_thai_moi = 'moi'; // Mặc định cho COD

if ($phuong_thuc_thanh_toan == 'online') {
    $anh_bill_filename = xu_ly_tai_anh_bill('anh_bill_thanh_toan', $upload_dir_bills);
    
    // Nếu chọn Online mà không upload bill -> Báo lỗi
    if (empty($anh_bill_filename)) {
        $_SESSION['thong_bao_loi_thanh_toan'] = "Vui lòng tải lên ảnh chụp bill thanh toán để hoàn tất.";
        header("Location: thanh_toan.php"); // "Đá về"
        exit();
    }
    
    // Nếu upload thành công -> Đặt trạng thái chờ
    $trang_thai_moi = 'cho_xac_nhan_thanh_toan';
}

// 5. TÍNH TOÁN LẠI TỔNG TIỀN (Bảo mật)
$item_ids = array_keys($items_to_buy);
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));
$sql_check_products = "SELECT id, ten_san_pham, gia_ban FROM san_pham WHERE id IN ($placeholders)";
$stmt_check = $conn->prepare($sql_check_products);
$stmt_check->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
$stmt_check->execute();
$products_result = $stmt_check->get_result();

$products_in_cart = [];
$tong_tien_hang = 0;
while ($row = $products_result->fetch_assoc()) {
    $so_luong_value = $items_to_buy[$row['id']];
    // (SỬA) Kiểm tra xem $items_to_buy[$row['id']] là mảng hay số
    if (is_array($so_luong_value)) {
        $so_luong = (int)($so_luong_value['so_luong'] ?? 0);
    } else {
        $so_luong = (int)$so_luong_value;
    }
    
    $row['so_luong'] = $so_luong;
    $row['thanh_tien'] = $row['gia_ban'] * $so_luong;
    $tong_tien_hang += $row['thanh_tien'];
    $products_in_cart[$row['id']] = $row; // Lưu lại để dùng ở bước 7
}
$phi_van_chuyen = 30000;
$so_tien_giam_gia = $_SESSION['checkout_discount_amount'] ?? 0;
$ma_giam_gia = $_SESSION['checkout_discount_code'] ?? null;
$id_ma_giam_gia = $_SESSION['id_ma_giam_gia'] ?? null;
$tong_tien_final = $tong_tien_hang + $phi_van_chuyen - $so_tien_giam_gia;


// 6. GHI VÀO CSDL (TRANSACTION)
$conn->begin_transaction();
try {
    // 6.1. (SỬA) Thêm `trang_thai_don_hang` và `anh_bill_thanh_toan`
    $sql_don_hang = "INSERT INTO don_hang 
        (id_nguoi_dung, ma_don_hang, ten_nguoi_nhan, so_dien_thoai_nhan, dia_chi_giao_hang, ghi_chu, 
         tong_tien, id_ma_giam_gia, so_tien_giam_gia, ma_giam_gia_da_ap, 
         phuong_thuc_thanh_toan, phuong_thuc_van_chuyen, 
         trang_thai_don_hang, anh_bill_thanh_toan) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_don_hang = $conn->prepare($sql_don_hang);
    $stmt_don_hang->bind_param("isssssdiisssss", 
        $id_nguoi_dung, $ma_don_hang, $ten_nguoi_nhan, $so_dien_thoai_nhan, $dia_chi_giao_hang, $ghi_chu,
        $tong_tien_final, $id_ma_giam_gia, $so_tien_giam_gia, $ma_giam_gia,
        $phuong_thuc_thanh_toan, $phuong_thuc_van_chuyen,
        $trang_thai_moi, $anh_bill_filename 
    );
    $stmt_don_hang->execute();
    $id_don_hang_moi = $conn->insert_id;

    // 6.2. Thêm chi tiết đơn hàng
    $sql_chi_tiet = "INSERT INTO chi_tiet_don_hang 
        (id_don_hang, id_san_pham, ten_san_pham_luc_mua, mau_sac_luc_mua, gia_luc_mua, so_luong) 
        VALUES (?, ?, ?, ?, ?, ?)";
    $stmt_chi_tiet = $conn->prepare($sql_chi_tiet);

    $sql_update_stock = "UPDATE san_pham SET so_luong_ton = so_luong_ton - ?, so_luong_da_ban = so_luong_da_ban + ? WHERE id = ?";
    $stmt_stock = $conn->prepare($sql_update_stock);

    foreach ($products_in_cart as $id_sp => $item) {
        $mau_sac = 'Mặc định'; // (Bạn nên thêm logic lấy màu sắc nếu có)
        $stmt_chi_tiet->bind_param("iissdi", 
            $id_don_hang_moi, $id_sp, $item['ten_san_pham'], $mau_sac, $item['gia_ban'], $item['so_luong']
        );
        $stmt_chi_tiet->execute();
        
        // 6.3. Trừ kho (Chỉ trừ kho nếu không phải chờ xác nhận thanh toán)
        if ($trang_thai_moi != 'cho_xac_nhan_thanh_toan') {
            $stmt_stock->bind_param("iii", $item['so_luong'], $item['so_luong'], $id_sp);
            $stmt_stock->execute();
        }
    }
    
    // 6.4. Cập nhật lượt dùng mã giảm giá (nếu có)
    if ($id_ma_giam_gia) {
        $conn->query("UPDATE ma_giam_gia SET so_luong_da_dung = so_luong_da_dung + 1 WHERE id_giam_gia = $id_ma_giam_gia");
    }
    
    // 6.5. Xóa các sản phẩm đã mua khỏi giỏ hàng
    $sql_delete_cart = "DELETE FROM gio_hang WHERE id_nguoi_dung = ? AND id_san_pham = ?";
    $stmt_delete = $conn->prepare($sql_delete_cart);
    foreach ($item_ids as $id_sp) {
        $stmt_delete->bind_param("ii", $id_nguoi_dung, $id_sp);
        $stmt_delete->execute();
    }
    
    // 7. XÓA SESSION CHECKOUT
    unset($_SESSION['checkout_items']);
    unset($_SESSION['checkout_discount_amount']);
    unset($_SESSION['checkout_discount_code']);
    unset($_SESSION['id_ma_giam_gia']);
    unset($_SESSION['ma_don_hang_tam']);
    
    $conn->commit();
    
    // 8. Chuyển hướng
    $_SESSION['order_success_id'] = $id_don_hang_moi;
    header("Location: dat_hang_thanh_cong.php");
    exit();
    
} catch (Exception $e) {
    $conn->rollback();
    // (Xóa file bill nếu lỡ upload mà bị lỗi CSDL)
    if ($anh_bill_filename && file_exists($upload_dir_bills . $anh_bill_filename)) {
        @unlink($upload_dir_bills . $anh_bill_filename);
    }
    $_SESSION['thong_bao_loi_thanh_toan'] = "Lỗi nghiêm trọng khi đặt hàng: " . $e->getMessage();
    header("Location: thanh_toan.php");
    exit();
}
?>