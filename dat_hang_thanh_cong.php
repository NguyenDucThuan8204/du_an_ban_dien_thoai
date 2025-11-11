<?php
// 1. GỌI LOGIC TRƯỚC
// (Bắt đầu session và kết nối CSDL)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY

// 2.1. KIỂM TRA BẢO MẬT (LỖI CŨ CỦA BẠN LÀ DO ĐỂ DÒNG NÀY SAU)
// Phải kiểm tra xem người dùng có vừa đặt hàng thật không
if (!isset($_SESSION['order_success_id'])) {
    // Nếu không, (ví dụ: F5, hoặc gõ URL trực tiếp)
    // chuyển hướng ngay lập tức VỀ TRANG CHỦ
    header("Location: index.php"); 
    exit(); // Dừng chạy ngay
}

// 2.2. LẤY THÔNG TIN ĐƠN HÀNG ĐỂ HIỂN THỊ
$id_don_hang_moi = (int)$_SESSION['order_success_id'];
$order_details = null;
$thong_bao_loi = "";

$stmt = $conn->prepare("
    SELECT d.ma_don_hang, d.tong_tien, d.ngay_dat, d.ten_nguoi_nhan, 
           d.phuong_thuc_thanh_toan, d.trang_thai_don_hang, 
           COALESCE(nd.ten, 'Khách') as ten_khach_hang
    FROM don_hang d
    LEFT JOIN nguoi_dung nd ON d.id_nguoi_dung = nd.id_nguoi_dung
    WHERE d.id_don_hang = ?
");
$stmt->bind_param("i", $id_don_hang_moi);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 1) {
    $order_details = $result->fetch_assoc();
} else {
    $thong_bao_loi = "Không thể tìm thấy chi tiết đơn hàng của bạn.";
}

// 2.3. (QUAN TRỌNG) XÓA SESSION SAU KHI ĐÃ ĐỌC XONG
// Việc này ngăn người dùng F5 (tải lại) trang này
unset($_SESSION['order_success_id']);

// (Cũng nên xóa các session checkout khác)
unset($_SESSION['checkout_items']);
unset($_SESSION['checkout_discount_amount']);
unset($_SESSION['checkout_discount_code']);
unset($_SESSION['id_ma_giam_gia']);
unset($_SESSION['ma_don_hang_tam']);

// --- TẤT CẢ LOGIC ĐÃ XONG, BÂY GIỜ MỚI GỌI HTML ---
?>

<?php
// Đặt tiêu đề cho trang này
$page_title = "Đặt Hàng Thành Công";

// 3. GỌI ĐẦU TRANG (Đã bao gồm CSS, Menu và Turbolinks)
require 'dung_chung/dau_trang.php';
?>

<style>
    .success-container {
        max-width: 600px;
        margin: 40px auto;
        padding: 30px;
        background: #fff;
        border-radius: 8px;
        box-shadow: var(--shadow);
        text-align: center;
    }
    .success-icon {
        font-size: 4rem;
        color: #28a745;
        width: 100px;
        height: 100px;
        line-height: 100px;
        border-radius: 50%;
        background: #eafaf1;
        margin: 0 auto 20px auto;
    }
    .success-container h1 {
        font-size: 1.8rem;
        color: #333;
        margin-bottom: 10px;
    }
    .success-message {
        font-size: 1.1em;
        color: #555;
        line-height: 1.6;
    }
    .order-summary-box {
        text-align: left;
        background: #f9f9f9;
        border: 1px solid #eee;
        border-radius: 5px;
        padding: 20px;
        margin: 25px 0;
    }
    .order-summary-box p {
        margin: 0 0 10px 0;
        display: flex;
        justify-content: space-between;
        font-size: 1em;
    }
    .order-summary-box p strong {
        color: #333;
    }
    .order-summary-box .total {
        font-size: 1.2em;
        font-weight: bold;
        color: var(--danger-color);
        border-top: 1px solid #ddd;
        padding-top: 10px;
    }
    .success-actions {
        display: flex;
        gap: 15px;
        justify-content: center;
        margin-top: 25px;
    }
</style>

<main class="container">

    <div class="success-container">

        <?php if ($thong_bao_loi): ?>
            <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
        <?php elseif ($order_details): ?>
        
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            
            <h1>Đặt hàng thành công!</h1>
            <p class="success-message">
                Cảm ơn bạn, <strong><?php echo htmlspecialchars($order_details['ten_khach_hang']); ?></strong>! 
                Chúng tôi đã nhận được đơn hàng của bạn.
            </p>

            <div class="order-summary-box">
                <p><span>Mã đơn hàng:</span> <strong><?php echo htmlspecialchars($order_details['ma_don_hang']); ?></strong></p>
                <p><span>Ngày đặt:</span> <strong><?php echo date('d/m/Y H:i', strtotime($order_details['ngay_dat'])); ?></strong></p>
                <p><span>Người nhận:</span> <strong><?php echo htmlspecialchars($order_details['ten_nguoi_nhan']); ?></strong></p>
                
                <?php if ($order_details['trang_thai_don_hang'] == 'cho_xac_nhan_thanh_toan'): ?>
                    <p><span>Trạng thái:</span> <strong style="color: #e67e22;">Chờ xác nhận thanh toán</strong></p>
                    <p style="font-size: 0.9em; color: #555; display: block; margin-top: 15px;">
                        (Bạn đã chọn thanh toán Online. Đơn hàng sẽ được xử lý ngay sau khi admin xác nhận đã nhận được tiền.)
                    </p>
                <?php else: ?>
                    <p><span>Trạng thái:</span> <strong style="color: #007bff;">Đơn hàng mới (COD)</strong></p>
                <?php endif; ?>
                
                <p class="total"><span>Tổng cộng:</span> <strong><?php echo number_format($order_details['tong_tien'], 0, ',', '.'); ?>đ</strong></p>
            </div>
            
            <div class="success-actions">
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-home"></i> Về Trang Chủ</a>
                <a href="don_hang_cua_toi.php" class="btn"><i class="fas fa-receipt"></i> Xem Đơn Hàng</a>
            </div>
            
        <?php else: ?>
             <div class="message error">Không thể tải thông tin đơn hàng.</div>
        <?php endif; ?>

    </div>
    
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>