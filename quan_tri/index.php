<?php
// 1. GỌI CONFIG ADMIN ĐẦU TIÊN
require_once 'config_admin.php'; 
// (ROOT_PATH, BASE_URL, $conn, session, kiem_tra_quan_tri đã chạy)

// 2. KHỞI TẠO BIẾN
$page_title = "Tổng Quan"; 
$current_page = 'index.php'; // (Quan trọng cho menu.php)

// 3. (MỚI) TRUY VẤN DỮ LIỆU THỐNG KÊ
$hom_nay = date('Y-m-d');
$hom_nay_bat_dau = $hom_nay . ' 00:00:00';
$hom_nay_ket_thuc = $hom_nay . ' 23:59:59';
$trang_thai_hoan_thanh = 'hoan_thanh';

$stats = [
    'doanh_thu_hom_nay' => 0,
    'don_hang_moi_hom_nay' => 0,
    'nguoi_dung_moi_hom_nay' => 0,
    'tong_san_pham_hien' => 0
];
$recent_orders = [];
$thong_bao_loi = '';

try {
    // Stat 1: Doanh thu hôm nay (Chỉ tính đơn HOÀN THÀNH)
    $sql_doanh_thu = "SELECT SUM(tong_tien) as total 
                     FROM don_hang 
                     WHERE trang_thai_don_hang = ? 
                     AND ngay_dat BETWEEN ? AND ?"; // (SỬA LỖI) Dùng CỘT ĐÚNG: ngay_dat
    $stmt_dt = $conn->prepare($sql_doanh_thu);
    $stmt_dt->bind_param("sss", $trang_thai_hoan_thanh, $hom_nay_bat_dau, $hom_nay_ket_thuc);
    $stmt_dt->execute();
    $stats['doanh_thu_hom_nay'] = $stmt_dt->get_result()->fetch_assoc()['total'] ?? 0;

    // Stat 2: Đơn hàng mới hôm nay (Tất cả trạng thái)
    $sql_don_moi = "SELECT COUNT(id_don_hang) as total 
                    FROM don_hang 
                    WHERE ngay_dat BETWEEN ? AND ?"; // (SỬA LỖI) Dùng CỘT ĐÚNG: ngay_dat
    $stmt_dm = $conn->prepare($sql_don_moi);
    $stmt_dm->bind_param("ss", $hom_nay_bat_dau, $hom_nay_ket_thuc);
    $stmt_dm->execute();
    $stats['don_hang_moi_hom_nay'] = $stmt_dm->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Stat 3: Người dùng mới hôm nay (Giả định cột là 'ngay_tao')
    $sql_nd_moi = "SELECT COUNT(id_nguoi_dung) as total 
                   FROM nguoi_dung 
                   WHERE ngay_tao BETWEEN ? AND ?"; 
    $stmt_nd = $conn->prepare($sql_nd_moi);
    $stmt_nd->bind_param("ss", $hom_nay_bat_dau, $hom_nay_ket_thuc);
    $stmt_nd->execute();
    $stats['nguoi_dung_moi_hom_nay'] = $stmt_nd->get_result()->fetch_assoc()['total'] ?? 0;

    // Stat 4: Tổng sản phẩm (Đang bán)
    $sql_sp = "SELECT COUNT(id) as total FROM san_pham WHERE trang_thai = 'hiện'";
    $stats['tong_san_pham_hien'] = $conn->query($sql_sp)->fetch_assoc()['total'] ?? 0;

    // Bảng: 5 Đơn hàng mới nhất
    $sql_recent = "SELECT id_don_hang, ma_don_hang, ten_nguoi_nhan, tong_tien, trang_thai_don_hang, ngay_dat 
                   FROM don_hang 
                   ORDER BY ngay_dat DESC 
                   LIMIT 5"; // (SỬA LỖI) Dùng CỘT ĐÚNG: ngay_dat
    $result_recent = $conn->query($sql_recent);
    while($row = $result_recent->fetch_assoc()) {
        $recent_orders[] = $row;
    }

} catch (Exception $e) {
    $thong_bao_loi = "Lỗi CSDL: " . $e->getMessage();
}

// 4. GỌI ĐẦU TRANG ADMIN (SẼ TỰ GỌI MENU DỌC)
require 'dau_trang_quan_tri.php'; 
?>

<style>
    /* CSS cho 4 Thẻ thống kê */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    .stat-card {
        background-color: var(--secondary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 25px;
        display: flex;
        align-items: center;
        gap: 20px;
        transition: transform 0.2s ease, box-shadow 0.2s ease;
    }
    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.2);
    }
    .stat-card-icon {
        font-size: 2.5rem;
        padding: 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        width: 70px;
        height: 70px;
    }
    .stat-card-info .stat-value {
        font-size: 2rem;
        font-weight: 700;
        color: var(--text-white);
        margin: 0;
    }
    .stat-card-info .stat-label {
        font-size: 0.9rem;
        color: var(--text-gray-light);
        margin: 0;
    }
    
    /* Màu sắc cho Icon */
    .icon-revenue { color: #34D399; background-color: rgba(16, 185, 129, 0.1); } /* Emerald */
    .icon-orders { color: #FBBF24; background-color: rgba(251, 191, 36, 0.1); } /* Amber */
    .icon-users { color: #60A5FA; background-color: rgba(59, 130, 246, 0.1); } /* Blue */
    .icon-products { color: #F472B6; background-color: rgba(244, 114, 182, 0.1); } /* Pink */
    
    /* CSS cho Bảng (Đã có trong dau_trang_quan_tri.php) */
    .content-box {
        background-color: var(--secondary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 25px;
    }
    .content-box h3 {
        margin-top: 0;
        font-size: 1.5rem;
        color: var(--text-white);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
</style>

<h1><?php echo $page_title; ?></h1>
<p class="page-description">
    Chào mừng trở lại, <strong><?php echo htmlspecialchars($_SESSION['ten'] ?? 'Admin'); ?></strong>!
</p>

<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-icon icon-revenue">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['doanh_thu_hom_nay'], 0, ',', '.'); ?>đ</p>
            <p class="stat-label">Doanh thu hôm nay</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-icon icon-orders">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['don_hang_moi_hom_nay']); ?></p>
            <p class="stat-label">Đơn hàng mới hôm nay</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-icon icon-users">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['nguoi_dung_moi_hom_nay']); ?></p>
            <p class="stat-label">Người dùng mới hôm nay</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-icon icon-products">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['tong_san_pham_hien']); ?></p>
            <p class="stat-label">Sản phẩm đang bán</p>
        </div>
    </div>
</div>

<div class="content-box">
    <h3>Đơn hàng mới nhất</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>Mã Đơn</th>
                <th>Người Nhận</th>
                <th>Tổng Tiền</th>
                <th>Ngày Đặt</th>
                <th>Trạng Thái</th>
                <th>Chi tiết</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($recent_orders)): ?>
                <tr><td colspan="6" style="text-align: center;">Không có đơn hàng nào.</td></tr>
            <?php else: ?>
                <?php foreach ($recent_orders as $order): ?>
                <tr>
                    <td><strong><?php echo htmlspecialchars($order['ma_don_hang']); ?></strong></td>
                    <td><?php echo htmlspecialchars($order['ten_nguoi_nhan']); ?></td>
                    <td style="font-weight: bold; color: var(--accent-color-success);"><?php echo number_format($order['tong_tien'], 0, ',', '.'); ?>đ</td>
                    <td><?php echo date('d-m-Y H:i', strtotime($order['ngay_dat'])); ?></td>
                    <td>
                        <span class="status-label status-<?php echo str_replace('_', '-', $order['trang_thai_don_hang']); ?>">
                            <?php echo str_replace('_', ' ', $order['trang_thai_don_hang']); ?>
                        </span>
                    </td>
                    <td class="action-links">
                        <a href="quan_ly_don_hang.php?action=xem&id=<?php echo $order['id_don_hang']; ?>" class="edit">
                            <i class="fas fa-eye"></i> Xem
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require 'cuoi_trang_quan_tri.php'; ?>