<?php
// 1. ĐẶT TIÊU ĐỀ (Biến $page_title sẽ được dùng bởi dau_trang_quan_tri.php)
$page_title = "Tổng Quan - Admin Panel";

// 2. GỌI ĐẦU TRANG ADMIN (Bao gồm session, CSDL, CSS chung, Menu)
// (Biến $conn đã được tạo trong dau_trang_quan_tri.php)
require 'dau_trang_quan_tri.php'; 
?>

<?php
// 3. LOGIC LẤY THỐNG KÊ (ĐỂ LÀM ĐẸP)
// (Biến $conn đã được tạo trong dau_trang_quan_tri.php)

// 1. Đếm Doanh thu (Chỉ đơn hàng đã hoàn thành)
$result_revenue = $conn->query("SELECT SUM(tong_tien) as total_revenue FROM don_hang WHERE trang_thai_don_hang = 'hoan_thanh'");
$revenue = $result_revenue->fetch_assoc()['total_revenue'] ?? 0;

// 2. Đếm Đơn hàng mới
$result_new_orders = $conn->query("SELECT COUNT(id_don_hang) as total_new_orders FROM don_hang WHERE trang_thai_don_hang = 'moi'");
$new_orders = $result_new_orders->fetch_assoc()['total_new_orders'] ?? 0;

// 3. Đếm Sản phẩm
$result_products = $conn->query("SELECT COUNT(id) as total_products FROM san_pham");
$products = $result_products->fetch_assoc()['total_products'] ?? 0;

// 4. Đếm Khách hàng
$result_users = $conn->query("SELECT COUNT(id_nguoi_dung) as total_users FROM nguoi_dung WHERE vai_tro = 'khach_hang'");
$users = $result_users->fetch_assoc()['total_users'] ?? 0;

// 5. Lấy 5 đơn hàng mới nhất
$sql_recent_orders = "SELECT id_don_hang, ma_don_hang, ten_nguoi_nhan, tong_tien, trang_thai_don_hang, ngay_dat 
                      FROM don_hang 
                      ORDER BY ngay_dat DESC 
                      LIMIT 5";
$result_recent_orders = $conn->query($sql_recent_orders);

// Mảng map trạng thái (để hiển thị nhãn)
$trang_thai_map = [
    'moi' => 'Mới',
    'dang_xu_ly' => 'Đang xử lý',
    'dang_giao' => 'Đang giao',
    'hoan_thanh' => 'Hoàn thành',
    'da_huy' => 'Đã hủy',
    'yeu_cau_huy' => 'Yêu cầu hủy',
    'yeu_cau_tra_hang' => 'Y/C Trả hàng',
    'da_hoan_tra' => 'Đã hoàn trả'
];
?>

<style>
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); /* Tự động chia cột */
        gap: 25px;
        margin-bottom: 30px;
    }
    .stat-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,0,0,0.15);
    }
    .stat-card .icon {
        font-size: 3rem;
        width: 80px;
        height: 80px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-card .info .title {
        font-size: 0.95rem;
        color: #666;
        text-transform: uppercase;
        font-weight: bold;
    }
    .stat-card .info .value {
        font-size: 2rem;
        color: #333;
        font-weight: bold;
    }
    /* Màu sắc riêng cho từng thẻ */
    .stat-card.revenue .icon { background-color: #e0fbf6; color: #1abc9c; }
    .stat-card.orders .icon { background-color: #fff0e6; color: #e67e22; }
    .stat-card.products .icon { background-color: #e6f7ff; color: #3498db; }
    .stat-card.users .icon { background-color: #fceef5; color: #e91e63; }

    /* Bảng Đơn hàng gần đây */
    .recent-orders {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 25px;
    }
    .recent-orders h2 { 
        margin-top: 0;
        margin-bottom: 15px; 
    }
    /* Tái sử dụng class .admin-table nhưng bỏ shadow */
    .recent-orders .admin-table {
        box-shadow: none;
        border-radius: 0;
        border: 1px solid #ddd;
    }
</style>

<h1>Tổng Quan</h1>

<div class="stat-grid">
    <div class="stat-card revenue">
        <div class="icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="info">
            <div class="title">Tổng Doanh Thu</div>
            <div class="value"><?php echo number_format($revenue, 0, ',', '.'); ?>đ</div>
        </div>
    </div>
    
    <div class="stat-card orders">
        <div class="icon">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="info">
            <div class="title">Đơn Hàng Mới</div>
            <div class="value"><?php echo $new_orders; ?></div>
        </div>
    </div>
    
    <div class="stat-card products">
        <div class="icon">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="info">
            <div class="title">Sản Phẩm</div>
            <div class="value"><?php echo $products; ?></div>
        </div>
    </div>
    
    <div class="stat-card users">
        <div class="icon">
            <i class="fas fa-users"></i>
        </div>
        <div class="info">
            <div class="title">Khách Hàng</div>
            <div class="value"><?php echo $users; ?></div>
        </div>
    </div>
</div>

<div class="recent-orders">
    <h2><i class="fas fa-history"></i> 5 Đơn Hàng Gần Nhất</h2>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã ĐH</th>
                <th>Người nhận</th>
                <th>Tổng tiền</th>
                <th>Trạng thái</th>
                <th>Ngày đặt</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($result_recent_orders->num_rows > 0): ?>
                <?php while($order = $result_recent_orders->fetch_assoc()): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($order['ma_don_hang']); ?></strong></td>
                        <td><?php echo htmlspecialchars($order['ten_nguoi_nhan']); ?></td>
                        <td><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?>đ</td>
                        <td>
                            <?php 
                                $status_class = str_replace('_', '-', $order['trang_thai_don_hang']);
                                $status_text = $trang_thai_map[$order['trang_thai_don_hang']] ?? 'Không rõ';
                            ?>
                            <span class="status-label status-<?php echo $status_class; ?>">
                                <?php echo $status_text; ?>
                            </span>
                        </td>
                        <td><?php echo date('d-m-Y H:i', strtotime($order['ngay_dat'])); ?></td>
                        <td class="action-links">
                            <a href="quan_ly_don_hang.php?action=xem&id=<?php echo $order['id_don_hang']; ?>">
                                Xem chi tiết
                            </a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" style="text-align: center;">Chưa có đơn hàng nào.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'cuoi_trang_quan_tri.php'; ?>