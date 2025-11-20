<?php
// 1. GỌI CONFIG ADMIN ĐẦU TIÊN
require_once 'config_admin.php'; 
// (ROOT_PATH, BASE_URL, $conn, session, kiem_tra_quan_tri đã chạy)

// 2. KHỞI TẠO BIẾN
$page_title = "Báo Cáo Thống Kê"; 
$current_page = 'bao_cao_thong_ke.php'; // (Quan trọng cho menu.php)

// 3. (MỚI) XỬ LÝ LỌC NGÀY
// Mặc định là 30 ngày qua
$ngay_bat_dau = $_GET['ngay_bat_dau'] ?? date('Y-m-d', strtotime('-30 days'));
$ngay_ket_thuc = $_GET['ngay_ket_thuc'] ?? date('Y-m-d');

// Thêm giờ:phút:giây để truy vấn CSDL cho chính xác
$ngay_bat_dau_sql = $ngay_bat_dau . ' 00:00:00';
$ngay_ket_thuc_sql = $ngay_ket_thuc . ' 23:59:59';
$trang_thai_hoan_thanh = 'hoan_thanh';

// 4. (MỚI) TRUY VẤN DỮ LIỆU
$stats = [
    'tong_doanh_thu' => 0,
    'tong_loi_nhuan_uoc_tinh' => 0,
    'tong_don_hang_hoan_thanh' => 0,
    'tong_san_pham_ban' => 0
];
$best_sellers_table = [];
$chart_revenue_data = [];
$chart_status_data = [];
$thong_bao_loi = '';

try {
    // --- TRUY VẤN 1: LẤY Doanh thu, Lợi nhuận (Ước tính), Tổng SP bán (CHỈ TÍNH ĐƠN HOÀN THÀNH) ---
    $sql_main = "SELECT 
                    SUM(ct.so_luong * ct.gia_luc_mua) AS tong_doanh_thu,
                    SUM(ct.so_luong) AS tong_san_pham_ban,
                    -- ĐÃ SỬA THEO YÊU CẦU: Đảo ngược thành (Giá gốc - Giá bán)
                    -- LƯU Ý: Công thức này thực tế tính tổng GIẢM GIÁ (Discount) đã cấp.
                    SUM(ct.so_luong * (sp.gia_goc - ct.gia_luc_mua)) AS tong_loi_nhuan_uoc_tinh
                  FROM chi_tiet_don_hang ct
                  JOIN don_hang d ON ct.id_don_hang = d.id_don_hang
                  LEFT JOIN san_pham sp ON ct.id_san_pham = sp.id 
                  WHERE 
                    d.trang_thai_don_hang = ? 
                    AND d.ngay_dat BETWEEN ? AND ?"; // (SỬA LỖI) Dùng CỘT ĐÚNG: ngay_dat
    
    $stmt_main = $conn->prepare($sql_main);
    $stmt_main->bind_param("sss", $trang_thai_hoan_thanh, $ngay_bat_dau_sql, $ngay_ket_thuc_sql);
    $stmt_main->execute();
    $result_main = $stmt_main->get_result()->fetch_assoc();
    
    if ($result_main) {
        $stats['tong_doanh_thu'] = $result_main['tong_doanh_thu'] ?? 0;
        $stats['tong_loi_nhuan_uoc_tinh'] = $result_main['tong_loi_nhuan_uoc_tinh'] ?? 0;
        $stats['tong_san_pham_ban'] = $result_main['tong_san_pham_ban'] ?? 0;
    }
    
    // --- TRUY VẤN 2: LẤY TỔNG ĐƠN HÀNG HOÀN THÀNH ---
    $sql_orders = "SELECT COUNT(id_don_hang) AS tong_don_hang
                  FROM don_hang d
                  WHERE 
                    d.trang_thai_don_hang = ? 
                    AND d.ngay_dat BETWEEN ? AND ?"; // (SỬA LỖI) Dùng CỘT ĐÚNG: ngay_dat
    $stmt_orders = $conn->prepare($sql_orders);
    $stmt_orders->bind_param("sss", $trang_thai_hoan_thanh, $ngay_bat_dau_sql, $ngay_ket_thuc_sql);
    $stmt_orders->execute();
    $result_orders = $stmt_orders->get_result()->fetch_assoc();
    if($result_orders) {
        $stats['tong_don_hang_hoan_thanh'] = $result_orders['tong_don_hang'] ?? 0;
    }

    // --- TRUY VẤN 3: LẤY SẢN PHẨM BÁN CHẠY (CHO BẢNG & BIỂU ĐỒ) ---
    $sql_bestsellers = "SELECT 
                            ct.ten_san_pham_luc_mua, 
                            SUM(ct.so_luong) as tong_so_luong_ban
                        FROM chi_tiet_don_hang ct
                        JOIN don_hang d ON ct.id_don_hang = d.id_don_hang
                        WHERE 
                            d.trang_thai_don_hang = ? 
                            AND d.ngay_dat BETWEEN ? AND ?
                        GROUP BY 
                            ct.id_san_pham, ct.ten_san_pham_luc_mua
                        ORDER BY 
                            tong_so_luong_ban DESC
                        LIMIT 10"; // (SỬA LỖI) Dùng CỘT ĐÚNG: ngay_dat
    
    $stmt_bestsellers = $conn->prepare($sql_bestsellers);
    $stmt_bestsellers->bind_param("sss", $trang_thai_hoan_thanh, $ngay_bat_dau_sql, $ngay_ket_thuc_sql);
    $stmt_bestsellers->execute();
    $result_bestsellers = $stmt_bestsellers->get_result();
    while($row = $result_bestsellers->fetch_assoc()) {
        $best_sellers_table[] = $row;
    }

    // --- (MỚI) TRUY VẤN 4: DOANH THU THEO NGÀY (CHO BIỂU ĐỒ ĐƯỜNG) ---
    $sql_revenue_by_day = "SELECT 
                                DATE_FORMAT(d.ngay_dat, '%Y-%m-%d') as ngay,
                                SUM(d.tong_tien) as doanh_thu_ngay
                           FROM don_hang d
                           WHERE 
                                d.trang_thai_don_hang = ?
                                AND d.ngay_dat BETWEEN ? AND ?
                           GROUP BY ngay
                           ORDER BY ngay ASC";
    $stmt_revenue = $conn->prepare($sql_revenue_by_day);
    $stmt_revenue->bind_param("sss", $trang_thai_hoan_thanh, $ngay_bat_dau_sql, $ngay_ket_thuc_sql);
    $stmt_revenue->execute();
    $result_revenue = $stmt_revenue->get_result();
    while($row = $result_revenue->fetch_assoc()) {
        $chart_revenue_data['labels'][] = date('d/m', strtotime($row['ngay']));
        $chart_revenue_data['data'][] = $row['doanh_thu_ngay'];
    }

    // --- (MỚI) TRUY VẤN 5: TRẠNG THÁI ĐƠN HÀNG (CHO BIỂU ĐỒ TRÒN) ---
    // (Lấy tất cả trạng thái, không chỉ 'hoan_thanh')
    $sql_status_pie = "SELECT 
                            trang_thai_don_hang,
                            COUNT(id_don_hang) as so_luong
                       FROM don_hang
                       WHERE 
                            ngay_dat BETWEEN ? AND ?
                       GROUP BY trang_thai_don_hang";
    $stmt_status = $conn->prepare($sql_status_pie);
    $stmt_status->bind_param("ss", $ngay_bat_dau_sql, $ngay_ket_thuc_sql);
    $stmt_status->execute();
    $result_status = $stmt_status->get_result();
    while($row = $result_status->fetch_assoc()) {
        $chart_status_data['labels'][] = str_replace('_', ' ', $row['trang_thai_don_hang']);
        $chart_status_data['data'][] = $row['so_luong'];
    }

} catch (Exception $e) {
    // (Ghi log lỗi nếu cần)
    $thong_bao_loi = "Lỗi truy vấn CSDL: " . $e->getMessage();
}

// 5. GỌI ĐẦU TRANG ADMIN
require 'dau_trang_quan_tri.php'; 
?>

<style>
    /* CSS cho Bộ lọc ngày */
    .date-filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        padding: 20px;
        background-color: var(--secondary-dark);
        border-radius: 8px;
        margin-bottom: 25px;
        border: 1px solid var(--border-color);
    }
    
    /* CSS cho Cảnh báo */
    .message.warning {
        background-color: #3B3121; /* Màu nền vàng đậm */
        color: #FBBF24; /* Màu text vàng */
        border: 1px solid #78350F; /* Màu viền vàng đậm */
    }
    
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
    }
    .stat-card-icon {
        font-size: 2.5rem;
        padding: 15px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
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
    .icon-profit { color: #60A5FA; background-color: rgba(59, 130, 246, 0.1); } /* Blue */
    .icon-orders { color: #F472B6; background-color: rgba(244, 114, 182, 0.1); } /* Pink */
    .icon-products { color: #FBBF24; background-color: rgba(251, 191, 36, 0.1); } /* Amber */

    /* (MỚI) CSS CHO BIỂU ĐỒ */
    .chart-container {
        background-color: var(--secondary-dark);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 25px;
    }
    .chart-container h3 {
        margin-top: 0;
        font-size: 1.5rem;
        color: var(--text-white);
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    .chart-grid-container {
        display: grid;
        grid-template-columns: 2fr 1fr; /* 2/3 cho biểu đồ đường, 1/3 cho biểu đồ tròn */
        gap: 20px;
        margin-bottom: 20px;
    }
    /* Responsive cho biểu đồ */
    @media (max-width: 992px) {
        .chart-grid-container {
            grid-template-columns: 1fr; /* Xếp chồng lên nhau */
        }
    }
</style>

<h1><?php echo $page_title; ?></h1>
<p class="page-description">
    Thống kê doanh thu, lợi nhuận và sản phẩm (chỉ tính các đơn hàng đã 'Hoàn thành').
</p>

<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<div class="message warning">
    <i class="fas fa-exclamation-triangle"></i>
    <strong>Lưu ý:</strong> Tiền lãi (lợi nhuận) được tính dựa trên **giá gốc hiện tại** của sản phẩm (từ bảng `san_pham`), không phải giá gốc tại thời điểm bán. Số liệu này chỉ là **ước tính**.
</div>

<form action="bao_cao_thong_ke.php" method="GET" class="date-filter-form">
    <div class="form-group" style="margin-bottom: 0;">
        <label for="ngay_bat_dau">Từ ngày</label>
        <input type="date" id="ngay_bat_dau" name="ngay_bat_dau" value="<?php echo htmlspecialchars($ngay_bat_dau); ?>">
    </div>
    <div class="form-group" style="margin-bottom: 0;">
        <label for="ngay_ket_thuc">Đến ngày</label>
        <input type="date" id="ngay_ket_thuc" name="ngay_ket_thuc" value="<?php echo htmlspecialchars($ngay_ket_thuc); ?>">
    </div>
    <button type="submit" class="btn btn-success" style="padding-top: 10px; padding-bottom: 10px;">
        <i class="fas fa-filter"></i> Lọc
    </button>
</form>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-icon icon-revenue">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['tong_doanh_thu'], 0, ',', '.'); ?>đ</p>
            <p class="stat-label">Tổng Doanh Thu</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-icon icon-profit">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['tong_loi_nhuan_uoc_tinh'], 0, ',', '.'); ?>đ</p>
            <p class="stat-label">Tổng Lợi Nhuận (Ước tính)</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-icon icon-orders">
            <i class="fas fa-receipt"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['tong_don_hang_hoan_thanh']); ?></p>
            <p class="stat-label">Đơn Hàng Hoàn Thành</p>
        </div>
    </div>
    
    <div class="stat-card">
        <div class="stat-card-icon icon-products">
            <i class="fas fa-box-open"></i>
        </div>
        <div class="stat-card-info">
            <p class="stat-value"><?php echo number_format($stats['tong_san_pham_ban']); ?></p>
            <p class="stat-label">Sản Phẩm Đã Bán</p>
        </div>
    </div>
</div>

<div class="chart-grid-container">
    <div class="chart-container">
        <h3>Doanh thu theo ngày (Đơn hoàn thành)</h3>
        <canvas id="doanhThuChart"></canvas>
    </div>
    
    <div class="chart-container">
        <h3>Tình trạng Đơn hàng (Tất cả)</h3>
        <canvas id="trangThaiChart" style="max-height: 300px;"></canvas>
    </div>
</div>

<div class="chart-container" style="margin-bottom: 20px;">
    <h3>Top 5 Sản phẩm bán chạy (Số lượng)</h3>
    <canvas id="banChayChart"></canvas>
</div>

<div class="chart-container"> <h3>Top 10 Sản Phẩm Bán Chạy Nhất (Chi tiết)</h3>
    <table class="admin-table">
        <thead>
            <tr>
                <th>STT</th>
                <th>Tên Sản Phẩm</th>
                <th>Tổng Số Lượng Bán</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($best_sellers_table)): ?>
                <tr><td colspan="3" style="text-align: center;">Không có dữ liệu trong khoảng thời gian này.</td></tr>
            <?php else: ?>
                <?php foreach ($best_sellers_table as $index => $item): ?>
                <tr>
                    <td><?php echo $index + 1; ?></td>
                    <td><?php echo htmlspecialchars($item['ten_san_pham_luc_mua']); ?></td>
                    <td><strong><?php echo number_format($item['tong_so_luong_ban']); ?></strong></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // --- CÀI ĐẶT MẶC ĐỊNH CHO DARK MODE ---
        Chart.defaults.color = '#E5E7EB'; // Màu chữ (text-gray-200)
        Chart.defaults.borderColor = '#374151'; // Màu lưới (gray-700)

        // --- 1. BIỂU ĐỒ ĐƯỜNG (DOANH THU) ---
        const ctxLine = document.getElementById('doanhThuChart');
        if (ctxLine) {
            new Chart(ctxLine, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($chart_revenue_data['labels'] ?? []); ?>,
                    datasets: [{
                        label: 'Doanh Thu (VNĐ)',
                        data: <?php echo json_encode($chart_revenue_data['data'] ?? []); ?>,
                        borderColor: '#34D399', // icon-revenue (Emerald)
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        fill: true,
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: {
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('vi-VN').format(value) + 'đ';
                                }
                            }
                        }
                    }
                }
            });
        }

        // --- 2. BIỂU ĐỒ TRÒN (TRẠNG THÁI) ---
        const ctxDoughnut = document.getElementById('trangThaiChart');
        if (ctxDoughnut) {
            new Chart(ctxDoughnut, {
                type: 'doughnut',
                data: {
                    labels: <?php echo json_encode($chart_status_data['labels'] ?? []); ?>,
                    datasets: [{
                        label: 'Số lượng đơn',
                        data: <?php echo json_encode($chart_status_data['data'] ?? []); ?>,
                        backgroundColor: [
                            '#28a745', // hoan_thanh
                            '#007bff', // moi
                            '#dc3545', // da_huy
                            '#17a2b8', // dang_xu_ly
                            '#ffc107', // dang_giao
                            '#fd7e14'  // yeu_cau_huy
                        ],
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { padding: 15 }
                        }
                    }
                }
            });
        }
        
        // --- 3. BIỂU ĐỒ CỘT (BÁN CHẠY) ---
        const ctxBar = document.getElementById('banChayChart');
        if (ctxBar) {
            // Chỉ lấy 5 sản phẩm đầu cho biểu đồ
            const top5Sellers = <?php echo json_encode(array_slice($best_sellers_table, 0, 5)); ?>;
            const barLabels = top5Sellers.map(item => item.ten_san_pham_luc_mua);
            const barData = top5Sellers.map(item => item.tong_so_luong_ban);
            
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: barLabels,
                    datasets: [{
                        label: 'Số Lượng Bán',
                        data: barData,
                        backgroundColor: '#60A5FA', // icon-profit (Blue)
                        borderColor: '#60A5FA',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // (MỚI) Xoay biểu đồ thành ngang
                    responsive: true,
                    plugins: {
                        legend: { display: false }
                    },
                    scales: {
                        x: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }
    });
</script>

<?php require 'cuoi_trang_quan_tri.php'; ?>