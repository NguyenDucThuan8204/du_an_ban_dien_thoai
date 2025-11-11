<?php
// 1. ĐẶT TIÊU ĐỀ VÀ GỌI HEADER ADMIN
$page_title = "Tổng Quan - Admin Panel";
require 'dau_trang_quan_tri.php'; 
// (dau_trang_quan_tri.php đã gọi session_start(), kiem_tra_quan_tri.php, ket_noi_csdl.php,
// và định nghĩa ROOT_PATH, BASE_URL)
?>

<?php
// 2. LOGIC LẤY DỮ LIỆU CHO DASHBOARD

// === 2.1. Lấy 4 Thẻ Thống Kê (Stat Cards) ===
$hom_nay = date('Y-m-d');

// 1. Doanh thu hôm nay (Chỉ đơn 'hoan_thanh')
$stmt_rev = $conn->prepare("SELECT SUM(tong_tien) as total_revenue FROM don_hang WHERE trang_thai_don_hang = 'hoan_thanh' AND DATE(ngay_dat) = ?");
$stmt_rev->bind_param("s", $hom_nay);
$stmt_rev->execute();
$revenue_today = $stmt_rev->get_result()->fetch_assoc()['total_revenue'] ?? 0;

// 2. Đơn hàng mới (Chờ xử lý)
$result_new_orders = $conn->query("SELECT COUNT(id_don_hang) as total_new_orders FROM don_hang WHERE trang_thai_don_hang = 'moi'");
$new_orders_count = $result_new_orders->fetch_assoc()['total_new_orders'] ?? 0;

// 3. Khách hàng mới hôm nay
$stmt_users = $conn->prepare("SELECT COUNT(id_nguoi_dung) as total_new_users FROM nguoi_dung WHERE vai_tro = 'khach_hang' AND DATE(ngay_tao) = ?");
$stmt_users->bind_param("s", $hom_nay);
$stmt_users->execute();
$new_users_count = $stmt_users->get_result()->fetch_assoc()['total_new_users'] ?? 0;

// 4. Phản hồi mới (Liên hệ + Phản ánh)
$result_feedback = $conn->query("
    SELECT 
    ( (SELECT COUNT(id_lien_he) FROM lien_he WHERE trang_thai = 'moi')
      +
      (SELECT COUNT(id_phan_anh) FROM phan_anh WHERE trang_thai = 'moi') 
    )
    AS total_new_feedback
");
$new_feedback_count = $result_feedback->fetch_assoc()['total_new_feedback'] ?? 0;


// === 2.2. Lấy 5 Đơn hàng mới nhất ===
$sql_recent_orders = "SELECT id_don_hang, ma_don_hang, ten_nguoi_nhan, tong_tien, ngay_dat 
                      FROM don_hang 
                      WHERE trang_thai_don_hang = 'moi'
                      ORDER BY ngay_dat DESC 
                      LIMIT 5";
$result_recent_orders = $conn->query($sql_recent_orders);


// === 2.3. Lấy 5 Phản hồi mới nhất (SỬA LỖI SQL Ở ĐÂY) ===
$sql_recent_feedback = "
    (SELECT id_lien_he as id, 'lien_he' as loai, 
            ten_nguoi_gui COLLATE utf8mb4_unicode_ci as ten, 
            tieu_de COLLATE utf8mb4_unicode_ci as noi_dung, 
            ngay_gui 
     FROM lien_he WHERE trang_thai = 'moi')
    UNION
    (SELECT p.id_phan_anh as id, 'phan_anh' as loai, 
            nd.ten COLLATE utf8mb4_unicode_ci as ten, 
            p.chu_de COLLATE utf8mb4_unicode_ci as noi_dung, 
            p.ngay_gui 
     FROM phan_anh p JOIN nguoi_dung nd ON p.id_nguoi_dung = nd.id_nguoi_dung WHERE p.trang_thai = 'moi')
    ORDER BY ngay_gui DESC
    LIMIT 5
";
$result_recent_feedback = $conn->query($sql_recent_feedback);
// ========================================================


// === 2.4. Lấy dữ liệu Biểu đồ Doanh thu (7 ngày qua) ===
$chart_labels = [];
$chart_data = [];
$date_array = [];
$data_map = []; 
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $date_array[] = $date;
    $chart_labels[] = date('d/m', strtotime("-$i days"));
    $data_map[$date] = 0; 
}
$ngay_bat_dau = $date_array[0]; 

$stmt_chart = $conn->prepare("
    SELECT DATE(ngay_dat) as ngay, SUM(tong_tien) as doanh_thu_ngay
    FROM don_hang
    WHERE trang_thai_don_hang = 'hoan_thanh' AND ngay_dat >= ?
    GROUP BY DATE(ngay_dat)
");
$stmt_chart->bind_param("s", $ngay_bat_dau);
$stmt_chart->execute();
$result_chart = $stmt_chart->get_result();

if ($result_chart) {
    while($row = $result_chart->fetch_assoc()) {
        if (isset($data_map[$row['ngay']])) {
            $data_map[$row['ngay']] = (float)$row['doanh_thu_ngay'];
        }
    }
}
foreach ($date_array as $date) {
    $chart_data[] = $data_map[$date];
}

?>

<style>
    /* (CSS Giữ nguyên như lần trước, không thay đổi) */
    .stat-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
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
        font-size: 2.5rem; 
        width: 70px;
        height: 70px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }
    .stat-card .info .title {
        font-size: 0.9rem;
        color: #666;
        text-transform: uppercase;
        font-weight: bold;
    }
    .stat-card .info .value {
        font-size: 1.8rem;
        color: #333;
        font-weight: bold;
    }
    .stat-card.revenue .icon { background-color: #e0fbf6; color: #1abc9c; }
    .stat-card.orders .icon { background-color: #e6f7ff; color: #3498db; }
    .stat-card.users .icon { background-color: #fceef5; color: #e91e63; }
    .stat-card.feedback .icon { background-color: #fff0e6; color: #e67e22; }

    .dashboard-grid {
        display: grid;
        grid-template-columns: 2fr 1fr; 
        gap: 25px;
    }
    .dashboard-box {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 25px;
    }
    .dashboard-box h2 {
        margin-top: 0;
        margin-bottom: 20px;
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        font-size: 1.3rem;
    }
    
    .recent-activity-table {
        width: 100%;
        border-collapse: collapse;
    }
    .recent-activity-table td {
        padding: 10px 5px;
        border-bottom: 1px solid #f0f0f0;
        font-size: 0.9rem;
        vertical-align: middle;
    }
    .recent-activity-table tr:last-child td {
        border-bottom: none;
    }
    .recent-activity-table .item-name {
        font-weight: 500;
        color: #333;
        display: block;
    }
    .recent-activity-table .item-meta {
        font-size: 0.85rem;
        color: #777;
    }
    .recent-activity-table .item-link a {
        background-color: #f1f1f1;
        color: #333;
        padding: 5px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        font-size: 0.8rem;
    }
    .recent-activity-table .item-link a:hover {
        background-color: #e0e0e0;
    }
    
    @media (max-width: 1200px) {
        .dashboard-grid {
            grid-template-columns: 1fr; 
        }
    }
</style>

<h1>Tổng Quan</h1>

<div class="stat-grid">
    <div class="stat-card revenue">
        <div class="icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="info">
            <div class="title">Doanh Thu Hôm Nay</div>
            <div class="value"><?php echo number_format($revenue_today, 0, ',', '.'); ?>đ</div>
        </div>
    </div>
    
    <div class="stat-card orders">
        <div class="icon">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="info">
            <div class="title">Đơn Hàng Mới</div>
            <div class="value"><?php echo $new_orders_count; ?></div>
        </div>
    </div>
    
    <div class="stat-card users">
        <div class="icon">
            <i class="fas fa-user-plus"></i>
        </div>
        <div class="info">
            <div class="title">Khách Hàng Mới</div>
            <div class="value"><?php echo $new_users_count; ?></div>
        </div>
    </div>
    
    <div class="stat-card feedback">
        <div class="icon">
            <i class="fas fa-inbox"></i>
        </div>
        <div class="info">
            <div class="title">Phản Hồi Chờ</div>
            <div class="value"><?php echo $new_feedback_count; ?></div>
        </div>
    </div>
</div>

<div class="dashboard-grid">

    <div class="dashboard-box chart-container">
        <h2>Doanh thu 7 ngày qua (VNĐ)</h2>
        <canvas id="revenueChart"></canvas>
    </div>

    <div class="dashboard-box recent-activity">
        <h2>Đơn hàng mới nhất</h2>
        <table class="recent-activity-table">
            <tbody>
                <?php if ($result_recent_orders->num_rows > 0): ?>
                    <?php while($order = $result_recent_orders->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <span class="item-name"><?php echo htmlspecialchars($order['ma_don_hang']); ?></span>
                                <span class="item-meta"><?php echo htmlspecialchars($order['ten_nguoi_nhan']); ?></span>
                            </td>
                            <td>
                                <span class="item-name" style="color: var(--danger-color); text-align: right;">
                                    <?php echo number_format($order['tong_tien'], 0, ',', '.'); ?>đ
                                </span>
                            </td>
                            <td class="item-link" style="text-align: right;">
                                <a href="quan_ly_don_hang.php?action=xem&id=<?php echo $order['id_don_hang']; ?>&tab=moi">
                                    Xem
                                </a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center;">Không có đơn hàng mới nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
        
        <h2 style="margin-top: 30px;">Phản hồi mới nhất</h2>
        <table class="recent-activity-table">
            <tbody>
                <?php if ($result_recent_feedback && $result_recent_feedback->num_rows > 0): ?>
                    <?php while($fb = $result_recent_feedback->fetch_assoc()): ?>
                        <?php
                            $icon = ($fb['loai'] == 'lien_he') ? 'fas fa-envelope' : 'fas fa-flag';
                            $link = "quan_ly_phan_hoi.php?action=xem&id=" . $fb['id'] . "&tab=" . $fb['loai'];
                        ?>
                        <tr>
                            <td>
                                <span class="item-name"><i class="<?php echo $icon; ?>"></i> <?php echo htmlspecialchars($fb['noi_dung']); ?></span>
                                <span class="item-meta"><?php echo htmlspecialchars($fb['ten']); ?></span>
                            </td>
                            <td class="item-link" style="text-align: right;">
                                <a href="<?php echo $link; ?>">Xem</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align: center;">Không có phản hồi mới nào.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    document.addEventListener("DOMContentLoaded", function() {
        const ctx = document.getElementById('revenueChart');
        if (ctx) {
            
            const labels = <?php echo json_encode($chart_labels); ?>;
            const data = <?php echo json_encode($chart_data); ?>;
            
            new Chart(ctx, {
                type: 'line', 
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Doanh thu (VNĐ)',
                        data: data,
                        fill: true, 
                        backgroundColor: 'rgba(54, 162, 235, 0.2)', 
                        borderColor: 'rgba(54, 162, 235, 1)', 
                        tension: 0.1, 
                        pointBackgroundColor: 'rgba(54, 162, 235, 1)'
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value, index, values) {
                                    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        }
    });
</script>

<?php require 'cuoi_trang_quan_tri.php'; ?>