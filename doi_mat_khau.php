<?php
// Đặt tiêu đề cho trang này
$page_title = "Đặt Hàng Thành Công";

// 1. GỌI ĐẦU TRANG
require 'dung_chung/dau_trang.php';
?>

<?php
// 2. PHẦN LOGIC PHP
// Kiểm tra xem có mã đơn hàng từ session không
$ma_don_hang = $_SESSION['dat_hang_thanh_cong'] ?? null;

if (!$ma_don_hang) {
    // Nếu không có, có thể người dùng F5, đá về trang chủ
    header("Location: index.php");
    exit();
}

// Xóa session để tránh F5
unset($_SESSION['dat_hang_thanh_cong']);
?>

<style>
    .success-container {
        max-width: 600px;
        margin: 50px auto;
        background: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 40px;
        text-align: center;
    }
    .success-icon {
        font-size: 5rem;
        color: #28a745;
        line-height: 1;
    }
    .success-container h1 {
        color: #28a745;
        margin-top: 15px;
        margin-bottom: 10px;
    }
    .success-container p {
        font-size: 1.1rem;
        color: #555;
    }
    .order-code {
        font-size: 1.3rem;
        font-weight: bold;
        color: var(--dark-color);
        background-color: #f0f0f0;
        padding: 10px 15px;
        border-radius: 8px;
        display: inline-block;
        margin: 20px 0;
    }
    .btn-group {
        margin-top: 30px;
        display: flex;
        gap: 15px;
        justify-content: center;
    }
    .btn-group a {
        text-decoration: none;
    }
    .btn-primary {
        background-color: var(--primary-color);
        color: white;
        padding: 12px 20px;
        border-radius: 5px;
        font-weight: bold;
    }
    .btn-outline {
        background-color: var(--white-color);
        color: var(--primary-color);
        padding: 12px 20px;
        border-radius: 5px;
        font-weight: bold;
        border: 2px solid var(--primary-color);
    }
</style>

<main class="container">
    <div class="success-container">
        <div class="success-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <h1>Đặt hàng thành công!</h1>
        <p>Cảm ơn bạn đã mua sắm tại PhoneStore.</p>
        <p>Mã đơn hàng của bạn là:</p>
        <div class="order-code"><?php echo htmlspecialchars($ma_don_hang); ?></div>
        <p>Bạn có thể theo dõi đơn hàng của mình tại trang "Đơn Hàng Của Tôi".</p>
        
        <div class="btn-group">
            <a href="don_hang_cua_toi.php" class="btn-primary">
                <i class="fas fa-receipt"></i> Xem Đơn Hàng
            </a>
            <a href="index.php" class="btn-outline">
                <i class="fas fa-home"></i> Tiếp Tục Mua Sắm
            </a>
        </div>
    </div>
</main>

<?php
require 'dung_chung/cuoi_trang.php';
?>