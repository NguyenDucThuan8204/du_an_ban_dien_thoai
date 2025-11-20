<?php
// 1. GỌI LOGIC TRƯỚC (SESSION, CSDL)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. LẤY ID BÀI VIẾT TỪ URL
$id_tin_tuc = (int)($_GET['id'] ?? 0);
if ($id_tin_tuc <= 0) {
    header("Location: tin_tuc.php"); 
    exit();
}

// 3. KHỞI TẠO BIẾN
$article = null;
$comments_list = [];
$related_news = []; // (MỚI)
$thong_bao_loi = "";
$thong_bao_thanh_cong = "";

// Lấy thông báo nếu có (từ trang xử lý bình luận)
if (isset($_SESSION['comment_message'])) {
    $thong_bao_thanh_cong = $_SESSION['comment_message'];
    unset($_SESSION['comment_message']);
}
if (isset($_SESSION['comment_error'])) {
    $thong_bao_loi = $_SESSION['comment_error'];
    unset($_SESSION['comment_error']);
}

// 4. TRUY VẤN DỮ LIỆU
try {
    // 4.1. TRUY VẤN BÀI VIẾT CHÍNH
    $sql_article = "SELECT tt.*, COALESCE(nd.ten, 'Admin') as ten_nguoi_dang
                    FROM tin_tuc tt
                    LEFT JOIN nguoi_dung nd ON tt.id_nguoi_dang = nd.id_nguoi_dung
                    WHERE tt.id_tin_tuc = ? AND tt.trang_thai = 'hien_thi'";
    $stmt_article = $conn->prepare($sql_article);
    if ($stmt_article === false) throw new Exception("Lỗi CSDL: Không thể chuẩn bị truy vấn bài viết.");
    
    $stmt_article->bind_param("i", $id_tin_tuc);
    $stmt_article->execute();
    $result_article = $stmt_article->get_result();
    $article = $result_article->fetch_assoc();

    if ($article) {
        $page_title = $article['tieu_de']; // Đặt tiêu đề trang

        // 4.2. TRUY VẤN BÌNH LUẬN
        $sql_comments = "SELECT bl.*, nd.ten, nd.anh_dai_dien 
                         FROM binh_luan bl
                         JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id_nguoi_dung
                         WHERE bl.id_tin_tuc = ? AND bl.trang_thai = 'da_duyet'
                         ORDER BY bl.ngay_binh_luan DESC";
        $stmt_comments = $conn->prepare($sql_comments);
        if ($stmt_comments === false) throw new Exception("Lỗi CSDL: Không thể chuẩn bị truy vấn bình luận.");

        $stmt_comments->bind_param("i", $id_tin_tuc);
        $stmt_comments->execute();
        $result_comments = $stmt_comments->get_result();
        
        while ($row = $result_comments->fetch_assoc()) {
            $comments_list[] = $row;
        }
        
        // 4.3. (MỚI) TRUY VẤN TIN TỨC MỚI KHÁC
        $sql_related = "SELECT id_tin_tuc, tieu_de, anh_dai_dien, ngay_dang 
                        FROM tin_tuc 
                        WHERE trang_thai = 'hien_thi' AND id_tin_tuc != ? 
                        ORDER BY ngay_dang DESC 
                        LIMIT 4";
                        
        $stmt_related = $conn->prepare($sql_related);
        if ($stmt_related === false) throw new Exception("Lỗi CSDL: Không thể chuẩn bị truy vấn tin liên quan.");
        
        $stmt_related->bind_param("i", $id_tin_tuc);
        $stmt_related->execute();
        $result_related = $stmt_related->get_result();
        
        while($row = $result_related->fetch_assoc()) {
            $related_news[] = $row;
        }
        
    } else {
        $thong_bao_loi = "Không tìm thấy bài viết này hoặc bài viết đã bị ẩn.";
    }
} catch (Exception $e) {
    $thong_bao_loi = $e->getMessage();
    $article = null; 
}

?>

<?php
// 5. GỌI ĐẦU TRANG (THAY THẾ TOÀN BỘ HEADER CŨ)
require 'dung_chung/dau_trang.php';
// (File dau_trang.php của bạn PHẢI là file đầy đủ CSS tôi đã gửi)
?>

<style>
    /* Container cho bài viết */
    .article-container {
        max-width: 900px; /* Chiều rộng tối ưu để đọc */
        margin: 0 auto;
        padding: 30px;
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }
    
    /* Bài viết */
    .article-header {
        border-bottom: 2px solid #eee;
        margin-bottom: 25px;
    }
    .article-header h1 {
        font-size: 2.5rem;
        color: var(--dark-color);
        margin: 0 0 10px 0;
    }
    .article-meta {
        font-size: 0.9rem;
        color: #777;
        margin-bottom: 20px;
    }
    .article-meta span {
        margin-right: 15px;
    }
    .article-meta i {
        margin-right: 5px;
        color: var(--primary-color);
    }
    .article-meta .author {
        font-weight: 600;
        color: #555;
    }
    
    .article-image {
        width: 100%;
        max-height: 500px;
        object-fit: cover;
        border-radius: var(--border-radius);
        margin-bottom: 25px;
    }
    
    /* CSS CHO NỘI DUNG CẤU TRÚC (MỚI) */
    .article-content {
        font-size: 1.1rem;
        line-height: 1.8;
        color: #444;
    }
    .article-content p {
        margin-bottom: 20px;
    }
    .article-content img {
        max-width: 100%;
        height: auto;
        border-radius: 8px;
        margin: 15px 0;
    }
    
    /* Khu vực Bình luận */
    .comments-section {
        margin-top: 40px;
        padding-top: 20px;
        border-top: 1px solid #eee;
    }
    .comments-section h2 {
        font-size: 1.8rem;
        color: var(--dark-color);
        margin-bottom: 20px;
    }
    
    /* (SỬA LỖI) SỬ DỤNG CSS CHUNG CỦA .form-group */
    .comment-form .form-group {
        margin-bottom: 10px;
    }
    .btn-submit-comment {
        /* Đã được style bởi .btn (hoặc .btn-submit) */
    }
    
    .comment-list {
        margin-top: 30px;
        list-style-type: none;
        padding: 0;
    }
    .comment-item {
        display: flex;
        gap: 15px;
        margin-bottom: 25px;
        border-bottom: 1px solid #f0f0f0;
        padding-bottom: 20px;
    }
    .comment-avatar img {
        width: 45px;
        height: 45px;
        border-radius: 50%;
        object-fit: cover;
    }
    .comment-body {
        flex-grow: 1;
    }
    .comment-author {
        font-weight: bold;
        color: var(--dark-color);
    }
    .comment-date {
        font-size: 0.8rem;
        color: #999;
        margin-left: 10px;
    }
    .comment-text {
        margin-top: 5px;
        white-space: pre-wrap; /* Giữ xuống dòng */
    }
    
    /* (MỚI) CSS Cho Tin Tức Mới Khác */
    .related-news-section {
        max-width: 1300px; /* Rộng hơn bài viết chính */
        margin: 40px auto 0 auto;
    }
    .news-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); 
        gap: 25px;
    }
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
    .news-image {
        width: 100%;
        height: 180px; /* Thấp hơn tin chính */
        object-fit: cover; 
    }
    .news-content {
        padding: 20px;
    }
    .news-date {
        font-size: 0.85rem;
        color: #777;
        margin-bottom: 10px;
    }
    .news-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin: 0;
        height: 3.2em; 
        line-height: 1.6em;
        overflow: hidden;
    }
</style>

<main class="container">

    <?php if ($thong_bao_thanh_cong): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao_thanh_cong); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <?php if ($article): // CHỈ HIỂN THỊ NẾU TÌM THẤY BÀI VIẾT ?>
        
        <article class="article-container">
            <div class="article-header">
                <h1><?php echo htmlspecialchars($article['tieu_de']); ?></h1>
                <div class="article-meta">
                    <span><i class="fas fa-user"></i> <span class="author"><?php echo htmlspecialchars($article['ten_nguoi_dang'] ?? 'Admin'); ?></span></span>
                    <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($article['ngay_dang'])); ?></span>
                </div>
            </div>

            <?php 
            $anh_path_bai_viet = 'tai_len/tin_tuc/' . ($article['anh_dai_dien'] ?? 'default.png');
            if (!empty($article['anh_dai_dien']) && file_exists($anh_path_bai_viet)): 
            ?>
                <img src="<?php echo $anh_path_bai_viet; ?>" alt="<?php echo htmlspecialchars($article['tieu_de']); ?>" class="article-image">
            <?php endif; ?>
            
            <div class="article-content">
                
                <?php if (!empty($article['noi_dung_1'])): ?>
                    <p style="font-weight: bold; font-size: 1.2em; color: #333;"><?php echo nl2br(htmlspecialchars($article['noi_dung_1'])); ?></p>
                <?php endif; ?>
                
                <?php 
                $anh_path_1 = 'tai_len/tin_tuc/' . ($article['anh_1'] ?? '');
                if (!empty($article['anh_1']) && file_exists($anh_path_1)): 
                ?>
                    <img src="<?php echo $anh_path_1; ?>" alt="Ảnh nội dung 1">
                <?php endif; ?>
                
                <?php if (!empty($article['noi_dung_2'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($article['noi_dung_2'])); ?></p>
                <?php endif; ?>
                
                <?php 
                $anh_path_2 = 'tai_len/tin_tuc/' . ($article['anh_2'] ?? '');
                if (!empty($article['anh_2']) && file_exists($anh_path_2)): 
                ?>
                    <img src="<?php echo $anh_path_2; ?>" alt="Ảnh nội dung 2">
                <?php endif; ?>
                
                <?php if (!empty($article['noi_dung_3'])): ?>
                    <p><?php echo nl2br(htmlspecialchars($article['noi_dung_3'])); ?></p>
                <?php endif; ?>
                
                <?php 
                $anh_path_3 = 'tai_len/tin_tuc/' . ($article['anh_3'] ?? '');
                if (!empty($article['anh_3']) && file_exists($anh_path_3)): 
                ?>
                    <img src="<?php echo $anh_path_3; ?>" alt="Ảnh nội dung 3">
                <?php endif; ?>

            </div>
            
            <div class="comments-section">
                <h2>Bình luận (<?php echo count($comments_list); ?>)</h2>
                
                <div class="comment-form">
                    <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                        <form action="xu_ly_binh_luan.php" method="POST" data-turbolinks="false"> 
                            <input type="hidden" name="id_tin_tuc" value="<?php echo $id_tin_tuc; ?>">
                            <div class="form-group">
                                <label for="noi_dung_binh_luan">Viết bình luận của bạn:</label>
                                <textarea id="noi_dung_binh_luan" name="noi_dung" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-submit-comment">Gửi bình luận</button>
                        </form>
                    <?php else: ?>
                        <p>Vui lòng <a href="dang_nhap.php?return_url=chi_tiet_tin_tuc.php?id=<?php echo $id_tin_tuc; ?>">đăng nhập</a> để bình luận.</p>
                    <?php endif; ?>
                </div>
                
                <ul class="comment-list">
                    <?php if (empty($comments_list)): ?>
                        <p>Chưa có bình luận nào. Hãy là người đầu tiên!</p>
                    <?php else: ?>
                        <?php foreach ($comments_list as $comment): ?>
                            <li class="comment-item">
                                <div class="comment-avatar">
                                    <?php 
                                    $anh_path_avatar = 'tai_len/avatars/' . ($comment['anh_dai_dien'] ?? 'default-avatar.png');
                                    if (empty($comment['anh_dai_dien']) || !file_exists($anh_path_avatar)) {
                                        $anh_path_avatar = 'tai_len/avatars/default-avatar.png'; 
                                    }
                                    ?>
                                    <img src="<?php echo $anh_path_avatar; ?>" alt="Avatar">
                                </div>
                                <div class="comment-body">
                                    <span class="comment-author"><?php echo htmlspecialchars($comment['ten']); ?></span>
                                    <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($comment['ngay_binh_luan'])); ?></span>
                                    <p class="comment-text"><?php echo nl2br(htmlspecialchars($comment['noi_dung'])); ?></p>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </article>
        
        <?php if (!empty($related_news)): ?>
            <section class="related-news-section">
                <h2 class="section-title">Tin tức mới khác</h2>
                
                <div class="news-grid">
                    <?php foreach($related_news as $news_item): ?>
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
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>

    <?php elseif (empty($thong_bao_loi)): ?>
        <div class="article-container" style="text-align: center;">
             <h1>Không tìm thấy bài viết</h1>
            <p>Bài viết bạn đang tìm kiếm không tồn tại hoặc đã bị ẩn. Vui lòng quay lại.</p>
            <a href="tin_tuc.php" class="btn">&larr; Quay lại trang tin tức</a>
        </div>
    <?php endif; ?>
    
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>