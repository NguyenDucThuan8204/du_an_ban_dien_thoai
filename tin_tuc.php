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
    // (MỚI) SQL Lấy thêm tên người đăng
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
?>

<main class="container">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
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
                        <div class="news-date">
                            Đăng bởi <strong><?php echo htmlspecialchars($news_item['ten_nguoi_dang']); ?></strong> 
                            ngày <?php echo date('d/m/Y', strtotime($news_item['ngay_dang'])); ?>
                        </div>
                        <h3 class="news-title"><?php echo htmlspecialchars($news_item['tieu_de']); ?></h3>
                        <p class="news-summary"><?php echo htmlspecialchars($news_item['noi_dung_1']); ?></p>
                    </div>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>

    </div> </main> <?php
require 'dung_chung/cuoi_trang.php';
?>