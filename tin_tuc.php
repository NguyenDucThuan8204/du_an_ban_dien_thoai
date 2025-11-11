<?php
// 1. GỌI LOGIC TRƯỚC
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
$page_title = "Tin Tức - PhoneStore";

// Lấy tham số lọc
$search_keyword = $_GET['search'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

$news_list = [];
$thong_bao_loi = "";
$params = [];
$types = "";

try {
    // Lấy thêm tên người đăng
    $sql_base = "SELECT 
                    t.id_tin_tuc, t.tieu_de, t.noi_dung_1, t.anh_dai_dien, t.ngay_dang,
                    COALESCE(nd.ten, 'Admin') as ten_nguoi_dang
                 FROM tin_tuc t
                 LEFT JOIN nguoi_dung nd ON t.id_nguoi_dang = nd.id_nguoi_dung";
    
    $where_clauses = [];
    $where_clauses[] = "t.trang_thai = 'hien_thi'"; // Chỉ hiện bài đã duyệt
    
    if (!empty($search_keyword)) {
        $where_clauses[] = "t.tieu_de LIKE ?";
        $params[] = "%" . $search_keyword . "%";
        $types .= "s";
    }
    if (!empty($date_from)) {
        $where_clauses[] = "t.ngay_dang >= ?";
        $params[] = $date_from;
        $types .= "s";
    }
    if (!empty($date_to)) {
        $where_clauses[] = "t.ngay_dang <= ?";
        $params[] = $date_to . " 23:59:59";
        $types .= "s";
    }

    $sql_news = $sql_base . " WHERE " . implode(' AND ', $where_clauses) . " ORDER BY t.ngay_dang DESC";
    
    $stmt = $conn->prepare($sql_news);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result_news = $stmt->get_result();
    
    if ($result_news === false) {
        throw new Exception("Lỗi CSDL: " . $conn->error);
    }
    
    while($row = $result_news->fetch_assoc()) {
        $news_list[] = $row;
    }
} catch (Exception $e) {
    $thong_bao_loi = "Không thể tải tin tức. Lỗi: " . $e->getMessage();
}

// 3. GỌI ĐẦU TRANG (SAU KHI LOGIC ĐÃ XONG)
require 'dung_chung/dau_trang.php';
// (Lưu ý: dau_trang.php PHẢI được cập nhật ở Bước 2)
?>

<style>
    /* Tiêu đề trang */
    .page-header-news {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap; /* Cho responsive */
        gap: 15px;
    }
    
    /* (CSS cho .filter-container và .filter-group đã có trong dau_trang.php) */

    /* CSS Cho Lưới Tin Tức */
    .news-grid {
        display: grid;
        /* 3 cột, tự động fill */
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); 
        gap: 30px;
    }
    
    /* CSS Cho Thẻ Tin Tức (Card) */
    .news-card {
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        overflow: hidden; 
        transition: all 0.3s ease;
        text-decoration: none; 
        color: var(--dark-color);
        display: flex;
        flex-direction: column;
    }
    .news-card:hover {
        transform: translateY(-8px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.12);
    }
    
    /* Ảnh */
    .news-image {
        width: 100%;
        height: 220px;
        object-fit: cover; /* Cắt ảnh vừa khung */
        border-bottom: 1px solid #f0f0f0;
    }
    
    /* Nội dung */
    .news-content {
        padding: 25px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
    }
    .news-meta {
        font-size: 0.85rem;
        color: #777;
        margin-bottom: 10px;
    }
    .news-meta i {
        margin-right: 5px;
        color: var(--primary-color);
    }
    .news-meta .author {
        font-weight: 600;
        color: #555;
    }
    
    .news-title {
        font-size: 1.3rem;
        font-weight: 600;
        margin: 0;
        /* Giới hạn 2 dòng */
        height: 3.2em; 
        line-height: 1.6em;
        overflow: hidden;
    }
    
    .news-summary {
        font-size: 0.95rem;
        color: #555;
        /* Giới hạn 3 dòng */
        height: 4.5em; 
        line-height: 1.5em;
        overflow: hidden;
        margin-top: 15px;
        flex-grow: 1; /* Đẩy phần đọc thêm xuống dưới */
    }
    
    .news-read-more {
        margin-top: 20px;
        font-weight: bold;
        color: var(--primary-color);
    }
    .news-read-more i {
        margin-left: 5px;
        transition: margin-left 0.2s;
    }
    .news-card:hover .news-read-more i {
        margin-left: 10px;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .news-grid {
            grid-template-columns: 1fr; /* 1 cột */
        }
    }
</style>

<main class="container">
    
    <div class="page-header-news">
        <h1 class="section-title" style="margin-bottom: 0;">Tin Tức & Đánh Giá</h1>
        <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
            <a href="viet_bai.php" class="btn btn-success">
                <i class="fas fa-pen-alt"></i> Viết bài mới
            </a>
        <?php endif; ?>
    </div>
    
    <form action="tin_tuc.php" method="GET" class="filter-container">
        <div class="filter-group">
            <label for="search">Tìm theo Tiêu đề:</label>
            <input type="text" id="search" name="search" placeholder="Nhập tiêu đề bài viết..." value="<?php echo htmlspecialchars($search_keyword); ?>">
        </div>
        <div class="filter-group">
            <label for="date_from">Từ ngày:</label>
            <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
        </div>
        <div class="filter-group">
            <label for="date_to">Đến ngày:</label>
            <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn"><i class="fas fa-filter"></i> Lọc</button>
            <a href="tin_tuc.php" class="btn btn-secondary"><i class="fas fa-times"></i> Xóa Lọc</a>
        </div>
    </form>
    
    <?php if (!empty($thong_bao_loi)): ?>
        <div class="message error"><?php echo $thong_bao_loi; ?></div>
    <?php endif; ?>

    <div class="news-grid">
        
        <?php if (empty($news_list) && empty($thong_bao_loi)): ?>
            <p style="grid-column: 1 / -1; text-align: center; font-size: 1.2rem;">Không tìm thấy bài viết nào.</p>
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
                        <div class="news-meta">
                            <span class="author"><i class="fas fa-user"></i> <?php echo htmlspecialchars($news_item['ten_nguoi_dang']); ?></span>
                            &nbsp;&nbsp;&nbsp;
                            <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($news_item['ngay_dang'])); ?></span>
                        </div>
                        <h3 class="news-title"><?php echo htmlspecialchars($news_item['tieu_de']); ?></h3>
                        <p class="news-summary"><?php echo htmlspecialchars($news_item['noi_dung_1']); ?></p>
                        <div class="news-read-more">
                            Đọc thêm <i class="fas fa-arrow-right"></i>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

    </div> </main> <?php
require 'dung_chung/cuoi_trang.php';
?>