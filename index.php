<?php
// 1. GỌI LOGIC TRƯỚC TIÊN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
$page_title = "Trang Chủ - PhoneStore";

// LẤY HÃNG (CHO BỘ LỌC)
$sql_hang = "SELECT id_hang, ten_hang FROM hang_san_xuat WHERE trang_thai = 'hien_thi' ORDER BY ten_hang ASC";
$result_hang = $conn->query($sql_hang);
$hang_list = [];
if ($result_hang) {
    while ($row = $result_hang->fetch_assoc()) {
        $hang_list[] = $row;
    }
}

// LẤY CÁC THAM SỐ LỌC TỪ URL
$current_id_hang = (int)($_GET['id_hang'] ?? 0);
$current_min_price = (int)($_GET['min_price'] ?? 0);
$current_max_price = (int)($_GET['max_price'] ?? 0);
$current_sort_by = $_GET['sort_by'] ?? 'moi_nhat';

// LỌC SẢN PHẨM
$sql_products = "SELECT 
                    s.id, s.ten_san_pham, s.anh_dai_dien, s.gia_ban, 
                    s.gia_goc, s.phan_tram_giam_gia, 
                    s.ngay_bat_dau_giam, s.ngay_ket_thuc_giam,
                    h.ten_hang
                FROM 
                    san_pham s
                JOIN 
                    hang_san_xuat h ON s.id_hang = h.id_hang";
$where_clauses = ["s.trang_thai = 'hiện'", "h.trang_thai = 'hien_thi'"];
$params = [];
$param_types = "";
if ($current_id_hang > 0) {
    $where_clauses[] = "s.id_hang = ?";
    $params[] = $current_id_hang;
    $param_types .= "i";
}
if ($current_min_price > 0) {
    $where_clauses[] = "s.gia_ban >= ?";
    $params[] = $current_min_price;
    $param_types .= "i";
}
if ($current_max_price > 0) {
    $where_clauses[] = "s.gia_ban <= ?";
    $params[] = $current_max_price;
    $param_types .= "i";
}
$sql_products .= " WHERE " . implode(" AND ", $where_clauses);
switch ($current_sort_by) {
    case 'gia_thap_cao': $sql_products .= " ORDER BY s.gia_ban ASC"; break;
    case 'gia_cao_thap': $sql_products .= " ORDER BY s.gia_ban DESC"; break;
    default: $sql_products .= " ORDER BY s.ngay_cap_nhat DESC"; break;
}
$stmt_products = $conn->prepare($sql_products);
if (!empty($params)) {
    $stmt_products->bind_param($param_types, ...$params);
}
$stmt_products->execute();
$result_products = $stmt_products->get_result();
$hom_nay = date('Y-m-d'); 

// LẤY TIN TỨC (CHO CUỐI TRANG)
$sql_news = "SELECT id_tin_tuc, tieu_de, noi_dung_1, anh_dai_dien, ngay_dang 
             FROM tin_tuc 
             WHERE trang_thai = 'hien_thi' 
             ORDER BY ngay_dang DESC 
             LIMIT 3"; 
$result_news = $conn->query($sql_news);
$news_list = [];
if ($result_news) {
    while($row = $result_news->fetch_assoc()) {
        $news_list[] = $row;
    }
}
?>

<?php
// 3. GỌI ĐẦU TRANG 
require 'dung_chung/dau_trang.php';
// (dau_trang.php đã được sửa để có <div> toast và ID cho giỏ hàng)
?>

<style>
    /* CSS riêng của trang Index */
    .hero {
        height: 50vh;
        background-image: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://images.unsplash.com/photo-1510557880182-3d4d3cba35a5?q=80&w=2070&auto=format&fit=crop');
        background-size: cover; background-position: center;
        display: flex; flex-direction: column;
        justify-content: center; align-items: center;
        text-align: center; color: var(--white-color);
        padding: 0 20px;
    }
    .hero h1 { font-size: 3rem; margin: 0; }
    .hero p { font-size: 1.25rem; margin: 10px 0 20px 0; }
    .hero .hero-cta {
        background-color: var(--primary-color); color: var(--white-color);
        padding: 12px 25px; font-size: 1rem; font-weight: bold;
        text-decoration: none; border-radius: 5px;
        transition: background-color 0.3s ease;
    }
    .hero .hero-cta:hover { background-color: #0056b3; }
    
    /* CSS Cho Bộ Lọc */
    .filter-controls { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
    .btn-filter-toggle { background-color: #6c757d; color: white; border: none; padding: 10px 15px; font-size: 1rem; font-weight: 500; border-radius: 5px; cursor: pointer; }
    .filter-bar {
        background-color: var(--white-color); padding: 20px;
        border-radius: var(--border-radius); box-shadow: var(--shadow);
        margin-bottom: 25px; display: none; 
        grid-template-columns: 1fr 1fr 1fr 150px;
        gap: 20px; align-items: flex-end;
    }
    .filter-bar.active { display: grid; }
    .filter-group { display: flex; flex-direction: column; }
    .filter-group label { font-size: 0.9rem; font-weight: 500; color: #555; margin-bottom: 5px; }
    .btn-filter-submit { background-color: var(--primary-color); color: white; border: none; padding: 12px; border-radius: 5px; cursor: pointer; width: 100%; font-size: 1rem; }
    .brand-filter-bar { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 30px; }
    .brand-link { text-decoration: none; color: var(--dark-color); background-color: var(--white-color); border: 1px solid #ddd; padding: 8px 15px; border-radius: 20px; font-size: 0.9rem; font-weight: 500; transition: all 0.2s; }
    .brand-link:hover { border-color: var(--primary-color); color: var(--primary-color); }
    .brand-link.active { background-color: var(--primary-color); color: var(--white-color); border-color: var(--primary-color); }
    
    /* CSS Sản phẩm */
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
    .product-card { background-color: var(--white-color); border: 1px solid #eee; border-radius: var(--border-radius); box-shadow: var(--shadow); transition: all 0.3s ease; position: relative; display: flex; flex-direction: column; overflow: hidden; }
    .product-card:hover { transform: translateY(-8px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); }
    .product-image-link { display: block; background-color: #f9f9f9; padding: 10px; }
    .product-image { width: 100%; height: 250px; object-fit: contain; }
    .product-details { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; text-align: left; }
    .product-brand { font-size: 0.85rem; color: #777; margin-bottom: 5px; }
    .product-title { font-size: 1.1rem; font-weight: 600; color: var(--dark-color); margin: 0; height: 44px; overflow: hidden; text-decoration: none; }
    .product-title a { text-decoration: none; color: inherit; }
    .product-price { margin-top: auto; padding-top: 10px; }
    .product-price .current-price { font-size: 1.3rem; color: var(--danger-color); font-weight: bold; }
    .product-price .old-price { font-size: 0.9rem; color: #999; text-decoration: line-through; margin-left: 8px; }
    .sale-badge { position: absolute; top: 15px; right: 15px; background-color: var(--danger-color); color: #fff; padding: 4px 8px; font-size: 0.8rem; border-radius: 5px; font-weight: bold; }
    .add-to-cart-form { margin-top: 15px; }
    .btn-add-to-cart { background-color: var(--primary-color); color: white; border: none; border-radius: 5px; padding: 12px 15px; width: 100%; font-size: 1rem; font-weight: bold; cursor: pointer; transition: background-color 0.2s; }
    .btn-add-to-cart:hover { background-color: #0056b3; }
    
    /* CSS Tin Tức (Cho cuối trang) */
    .news-section { margin-top: 40px; }
    .news-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 25px; }
    .news-card { background-color: var(--white-color); border-radius: var(--border-radius); box-shadow: var(--shadow); overflow: hidden; transition: all 0.3s ease; text-decoration: none; color: var(--dark-color); display: flex; flex-direction: column; }
    .news-card:hover { transform: translateY(-8px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); }
    .news-image { width: 100%; height: 200px; object-fit: cover; }
    .news-content { padding: 20px; }
    .news-date { font-size: 0.85rem; color: #777; margin-bottom: 10px; }
    .news-title { font-size: 1.2rem; font-weight: 600; margin: 0; height: 50px; overflow: hidden; }
    .news-summary { font-size: 0.95rem; color: #555; height: 60px; overflow: hidden; margin-top: 10px; }
    .section-footer { text-align: center; margin-top: 30px; }
</style>

<header class="hero">
    <h1>Khuyến Mãi Khủng</h1>
    <p>Giảm giá sập sàn. Mua ngay kẻo lỡ!</p>
    <a href="#san-pham" class="hero-cta">Xem Ngay</a>
</header>

<main class="container" id="san-pham">
    
    <div class="filter-controls">
        <h1 class="section-title" style="margin-bottom: 0;">Danh sách Sản phẩm</h1>
        <button id="filter-toggle-btn" class="btn-filter-toggle">Lọc Sản Phẩm</button>
    </div>
    
    <form action="index.php" method="GET" id="filter-bar" class="filter-bar">
        <?php if ($current_id_hang > 0): ?>
            <input type="hidden" name="id_hang" value="<?php echo $current_id_hang; ?>">
        <?php endif; ?>
        <div class="filter-group">
            <label for="min_price">Giá Từ (VNĐ):</label>
            <input type="number" id="min_price" name="min_price" placeholder="0" 
                   value="<?php echo $current_min_price > 0 ? $current_min_price : ''; ?>">
        </div>
        <div class="filter-group">
            <label for="max_price">Giá Đến (VNĐ):</label>
            <input type="number" id="max_price" name="max_price" placeholder="100000000"
                   value="<?php echo $current_max_price > 0 ? $current_max_price : ''; ?>">
        </div>
        <div class="filter-group">
            <label for="sort_by">Sắp xếp theo:</label>
            <select id="sort_by" name="sort_by">
                <option value="moi_nhat" <?php echo ($current_sort_by == 'moi_nhat') ? 'selected' : ''; ?>>Mới nhất</option>
                <option value="gia_thap_cao" <?php echo ($current_sort_by == 'gia_thap_cao') ? 'selected' : ''; ?>>Giá: Thấp đến Cao</option>
                <option value="gia_cao_thap" <?php echo ($current_sort_by == 'gia_cao_thap') ? 'selected' : ''; ?>>Giá: Cao đến Thấp</option>
            </select>
        </div>
        <button type="submit" class="btn-filter-submit">Áp dụng</button>
    </form>
    
    <div class="brand-filter-bar">
        <?php
        $filter_params = $_GET;
        unset($filter_params['id_hang']);
        $filter_query_string = http_build_query($filter_params);
        ?>
        <a href="?<?php echo $filter_query_string; ?>" 
           class="brand-link <?php echo ($current_id_hang == 0) ? 'active' : ''; ?>">
           Tất cả
        </a>
        <?php foreach ($hang_list as $hang): ?>
            <a href="?id_hang=<?php echo $hang['id_hang']; ?>&<?php echo $filter_query_string; ?>" 
               class="brand-link <?php echo ($current_id_hang == $hang['id_hang']) ? 'active' : ''; ?>">
               <?php echo htmlspecialchars($hang['ten_hang']); ?>
            </a>
        <?php endforeach; ?>
    </div>
    
    <div class="product-grid">
        <?php if ($result_products && $result_products->num_rows > 0): ?>
            <?php while($sp = $result_products->fetch_assoc()): ?>
                <?php
                // (Logic giá giữ nguyên)
                $gia_hien_thi = (float)$sp['gia_ban'];
                $gia_cu = !empty($sp['gia_goc']) ? (float)$sp['gia_goc'] : null;
                $phan_tram_hien_thi = null;
                $dang_giam_gia_theo_ngay = (
                    !empty($sp['ngay_bat_dau_giam']) &&
                    !empty($sp['ngay_ket_thuc_giam']) &&
                    $hom_nay >= $sp['ngay_bat_dau_giam'] &&
                    $hom_nay <= $sp['ngay_ket_thuc_giam']
                );
                if ($dang_giam_gia_theo_ngay && !empty($sp['phan_tram_giam_gia'])) {
                    $gia_cu = $sp['gia_ban']; 
                    $gia_hien_thi = $gia_cu * (1 - (float)$sp['phan_tram_giam_gia'] / 100);
                    $phan_tram_hien_thi = (int)$sp['phan_tram_giam_gia'];
                } 
                else if (!empty($gia_cu) && $gia_cu > $gia_hien_thi) {
                    $phan_tram_hien_thi = round((($gia_cu - $gia_hien_thi) / $gia_cu) * 100);
                }
                else {
                    $gia_cu = null; 
                }
                ?>
                <div class="product-card">
                    <?php if ($phan_tram_hien_thi): ?>
                        <div class="sale-badge">-<?php echo $phan_tram_hien_thi; ?>%</div>
                    <?php endif; ?>
                    
                    <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="product-image-link">
                        <?php 
                        $anh_path = 'tai_len/san_pham/' . ($sp['anh_dai_dien'] ?? 'default.png');
                        if (empty($sp['anh_dai_dien']) || !file_exists($anh_path)) {
                            $anh_path = 'tai_len/san_pham/default.png'; 
                        }
                        ?>
                        <img src="<?php echo $anh_path; ?>" alt="<?php echo htmlspecialchars($sp['ten_san_pham']); ?>" class="product-image">
                    </a>
                    <div class="product-details">
                        <div class="product-brand"><?php echo htmlspecialchars($sp['ten_hang']); ?></div>
                        <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="product-title">
                            <?php echo htmlspecialchars($sp['ten_san_pham']); ?>
                        </a>
                        <div class="product-price">
                            <span class="current-price"><?php echo number_format($gia_hien_thi, 0, ',', '.'); ?>đ</span>
                            <?php if ($gia_cu): ?>
                                <span class="old-price"><?php echo number_format($gia_cu, 0, ',', '.'); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        
                        <form action="xu_ly_gio_hang.php" method="POST" class="add-to-cart-form ajax-add-to-cart-form" data-turbolinks="false">
                            <input type="hidden" name="action" value="add">
                            <input type="hidden" name="id_san_pham" value="<?php echo $sp['id']; ?>">
                            <input type="hidden" name="so_luong" value="1">
                            <button type="submit" class="btn-add-to-cart"><i class="fas fa-cart-plus"></i> Thêm vào giỏ</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p style="text-align: center; grid-column: 1 / -1; font-size: 1.2rem;">Không tìm thấy sản phẩm nào khớp với bộ lọc.</p>
        <?php endif; ?>
    </div>
    
    <div class="news-section">
        <h1 class="section-title">Tin Tức Công Nghệ</h1>
        <div class="news-grid">
            <?php if (empty($news_list)): ?>
                <p>Chưa có tin tức nào.</p>
            <?php else: ?>
                <?php foreach($news_list as $news_item): ?>
                    <a href="chi_tiet_tin_tuc.php?id=<?php echo $news_item['id_tin_tuc']; ?>" class="news-card">
                        <?php 
                        $anh_path_news = 'tai_len/tin_tuc/' . ($news_item['anh_dai_dien'] ?? 'default.png');
                        if (empty($news_item['anh_dai_dien']) || !file_exists($anh_path_news)) {
                            $anh_path_news = 'tai_len/san_pham/default.png'; 
                        }
                        ?>
                        <img src="<?php echo $anh_path_news; ?>" alt="<?php echo htmlspecialchars($news_item['tieu_de']); ?>" class="news-image">
                        <div class="news-content">
                            <div class="news-date"><?php echo date('d/m/Y', strtotime($news_item['ngay_dang'])); ?></div>
                            <h3 class="news-title"><?php echo htmlspecialchars($news_item['tieu_de']); ?></h3>
                            <p class="news-summary"><?php echo htmlspecialchars($news_item['noi_dung_1']); ?></p>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="section-footer">
            <a href="tin_tuc.php" class="btn" style="gap: 8px;">
                <i class="fas fa-newspaper"></i> Xem tất cả tin tức
            </a>
        </div>
    </div>
    
</main> <script>
    document.addEventListener("turbolinks:load", function() {
        const toggleBtn = document.getElementById('filter-toggle-btn');
        const filterBar = document.getElementById('filter-bar');
        
        if (toggleBtn && filterBar) { // Chỉ chạy nếu có nút này
            toggleBtn.addEventListener('click', function() {
                filterBar.classList.toggle('active');
                
                if (filterBar.classList.contains('active')) {
                    toggleBtn.textContent = 'Đóng Lọc';
                } else {
                    toggleBtn.textContent = 'Lọc Sản Phẩm';
                }
            });

            <?php if ($current_min_price > 0 || $current_max_price > 0 || $current_sort_by != 'moi_nhat'): ?>
                if (filterBar) filterBar.classList.add('active');
                if (toggleBtn) toggleBtn.textContent = 'Đóng Lọc';
            <?php endif; ?>
        }
    });
</script>


<script>
    // Hàm này được gọi bởi Turbolinks mỗi khi trang tải
    document.addEventListener("turbolinks:load", function() {
        
        // 1. Tìm tất cả các form "Thêm vào giỏ"
        const allForms = document.querySelectorAll('.ajax-add-to-cart-form');
        
        allForms.forEach(form => {
            // Gán sự kiện submit cho từng form
            form.addEventListener('submit', function(event) {
                // 2. Ngăn chặn form gửi đi (ngăn lỗi trang trắng)
                event.preventDefault(); 
                
                const formData = new FormData(this);
                
                // 3. Gửi yêu cầu ngầm (Fetch)
                fetch('xu_ly_gio_hang.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    // 4. Xử lý kết quả JSON
                    if (data.success) {
                        // Cập nhật số lượng trên icon
                        updateCartBadge(data.new_cart_count);
                        // Hiển thị thông báo thành công
                        showToast('Đã thêm vào giỏ hàng!', 'success');
                    } else {
                        // Báo lỗi (nếu có)
                        showToast(data.message || 'Lỗi: Không thể thêm vào giỏ.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Lỗi Fetch:', error);
                    showToast('Lỗi kết nối. Vui lòng thử lại.', 'error');
                });
            });
        });
    });

    // 5. Hàm cập nhật icon giỏ hàng
    function updateCartBadge(count) {
        const badge = document.getElementById('cart-badge');
        if (badge) {
            if (count > 0) {
                badge.textContent = count;
                badge.style.display = 'flex';
            } else {
                badge.style.display = 'none';
            }
        }
    }

    // 6. Hàm hiển thị thông báo "Toast"
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return;

        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${message}`;
        
        toastContainer.appendChild(toast);

        // Hiển thị
        setTimeout(() => {
            toast.classList.add('show');
        }, 100); 

        // Tự động ẩn sau 3 giây
        setTimeout(() => {
            toast.classList.remove('show');
            // Xóa khỏi DOM sau khi mờ đi
            setTimeout(() => {
                if(toast.parentNode) {
                    toast.parentNode.removeChild(toast);
                }
            }, 500); 
        }, 3000);
    }
</script>


<?php
require 'dung_chung/cuoi_trang.php';
?>