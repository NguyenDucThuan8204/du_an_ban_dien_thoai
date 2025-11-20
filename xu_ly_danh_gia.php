<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

header('Content-Type: application/json');

// (MỚI) HÀM QUAN TRỌNG: TÍNH LẠI SAO TRUNG BÌNH
// (Hàm này cũng được dùng trong /quan_tri/quan_ly_danh_gia.php)
function cap_nhat_diem_trung_binh($conn, $id_san_pham) {
    if (empty($id_san_pham)) return;
    
    // 1. Tính toán
    $sql_calc = "SELECT 
                    AVG(so_sao) as avg_rating, 
                    COUNT(id_danh_gia) as total_reviews 
                 FROM danh_gia_san_pham 
                 WHERE id_san_pham = ? AND trang_thai = 'da_duyet'";
    $stmt_calc = $conn->prepare($sql_calc);
    $stmt_calc->bind_param("i", $id_san_pham);
    $stmt_calc->execute();
    $result_calc = $stmt_calc->get_result()->fetch_assoc();
    
    $avg = $result_calc['avg_rating'] ?? 0;
    $total = $result_calc['total_reviews'] ?? 0;
    
    // 2. Cập nhật lại bảng san_pham
    $sql_update = "UPDATE san_pham SET avg_rating = ?, total_reviews = ? WHERE id = ?";
    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param("dii", $avg, $total, $id_san_pham);
    $stmt_update->execute();
}

// 1. Chuẩn bị $response
$response = ['success' => false, 'message' => 'Hành động không hợp lệ.'];
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;

if ($_SERVER["REQUEST_METHOD"] != "POST" || !isset($_POST['action']) || $_POST['action'] != 'submit_review') {
    echo json_encode($response);
    exit();
}

// 2. Kiểm tra đăng nhập
if ($id_nguoi_dung == 0) {
    $response['message'] = 'Vui lòng đăng nhập để đánh giá.';
    echo json_encode($response);
    exit();
}

// 3. Lấy dữ liệu
$id_san_pham = (int)($_POST['id_san_pham'] ?? 0);
$so_sao = (int)($_POST['so_sao'] ?? 0);
$noi_dung = $conn->real_escape_string($_POST['noi_dung'] ?? '');

if ($id_san_pham == 0 || $so_sao < 1 || $so_sao > 5) {
     $response['message'] = 'Dữ liệu không hợp lệ. Vui lòng chọn số sao.';
    echo json_encode($response);
    exit();
}

try {
    // 4. KIỂM TRA QUYỀN (ĐÃ MUA HÀNG CHƯA?)
    $sql_check_purchase = "SELECT 1 FROM don_hang d
                           JOIN chi_tiet_don_hang ct ON d.id_don_hang = ct.id_don_hang
                           WHERE d.id_nguoi_dung = ? AND ct.id_san_pham = ? AND d.trang_thai_don_hang = 'hoan_thanh'
                           LIMIT 1";
    $stmt_check_purchase = $conn->prepare($sql_check_purchase);
    $stmt_check_purchase->bind_param("ii", $id_nguoi_dung, $id_san_pham);
    $stmt_check_purchase->execute();
    if ($stmt_check_purchase->get_result()->num_rows == 0) {
        $response['message'] = 'Bạn phải mua sản phẩm này trước khi đánh giá.';
        echo json_encode($response);
        exit();
    }
    
    // 5. KIỂM TRA XEM ĐÃ ĐÁNH GIÁ CHƯA?
    $sql_check_review = "SELECT 1 FROM danh_gia_san_pham WHERE id_nguoi_dung = ? AND id_san_pham = ? LIMIT 1";
    $stmt_check_review = $conn->prepare($sql_check_review);
    $stmt_check_review->bind_param("ii", $id_nguoi_dung, $id_san_pham);
    $stmt_check_review->execute();
    if ($stmt_check_review->get_result()->num_rows > 0) {
        $response['message'] = 'Bạn đã đánh giá sản phẩm này rồi.';
        echo json_encode($response);
        exit();
    }

    // 6. LƯU ĐÁNH GIÁ (Mặc định là 'chờ duyệt')
    $sql_insert = "INSERT INTO danh_gia_san_pham (id_san_pham, id_nguoi_dung, so_sao, noi_dung, trang_thai) 
                   VALUES (?, ?, ?, ?, 'cho_duyet')";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("iiis", $id_san_pham, $id_nguoi_dung, $so_sao, $noi_dung);
    
    if ($stmt_insert->execute()) {
        $response['success'] = true;
        $response['message'] = 'Gửi đánh giá thành công! Vui lòng chờ admin duyệt.';
        // Lưu ý: Chúng ta KHÔNG gọi cap_nhat_diem_trung_binh() ở đây.
        // Chúng ta chỉ gọi nó khi Admin nhấn "Duyệt" trong trang /quan_tri/quan_ly_danh_gia.php
        $_SESSION['review_message'] = 'Gửi đánh giá thành công! Vui lòng chờ admin duyệt.';
    } else {
        $response['message'] = 'Lỗi CSDL: Không thể lưu đánh giá.';
    }

} catch (Exception $e) {
    $response['message'] = 'Lỗi máy chủ: ' . $e->getMessage();
}

echo json_encode($response);
$conn->close();
exit();
?>