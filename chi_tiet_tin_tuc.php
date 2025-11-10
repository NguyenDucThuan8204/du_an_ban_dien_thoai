<?php
// 1. BẮT ĐẦU SESSION
session_start(); 
require 'dung_chung/ket_noi_csdl.php';

// 2. LẤY SỐ LƯỢNG GIỎ HÀNG (CHO MENU)
$cart_count = 0;
// (Code lấy $cart_count giữ nguyên...)
if (isset($_SESSION['id_nguoi_dung'])) {
    $id_nguoi_dung = $_SESSION['id_nguoi_dung'];
    $sql_count = "SELECT COUNT(id_gio_hang) as total FROM gio_hang WHERE id_nguoi_dung = ?";
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->bind_param("i", $id_nguoi_dung);
    $stmt_count->execute();
    $result_count = $stmt_count->get_result();
    $cart_count = $result_count->fetch_assoc()['total'];
} elseif (isset($_SESSION['cart'])) {
    $cart_count = count($_SESSION['cart']);
}

// 3. LẤY ID BÀI VIẾT TỪ URL
$id_tin_tuc = (int)($_GET['id'] ?? 0);
if ($id_tin_tuc <= 0) {
    header("Location: tin_tuc.php"); 
    exit();
}

// 4. TRUY VẤN BÀI VIẾT VÀ BÌNH LUẬN (ĐÃ SỬA LỖI)
$article = null;
$comments_list = [];
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


try {
    // 4.1. TRUY VẤN BÀI VIẾT (ĐÃ SỬA LẠI CÁC CỘT)
    $sql_article = "SELECT tt.*, nd.ten 
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
    } else {
        $thong_bao_loi = "Không tìm thấy bài viết này hoặc bài viết đã bị ẩn.";
    }
} catch (Exception $e) {
    $thong_bao_loi = $e->getMessage();
    $article = null; 
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $article ? htmlspecialchars($article['tieu_de']) : 'Không tìm thấy'; ?></title>
    <style>
        :root {
            --primary-color: #007bff;
            --danger-color: #e74c3c;
            --dark-color: #333;
            --light-color: #f4f4f4;
            --white-color: #ffffff;
            --shadow: 0 4px 12px rgba(0,0,0,0.08);
            --border-radius: 12px;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            margin: 0; padding: 0;
            background-color: var(--light-color);
            line-height: 1.6;
        }
        
        /* Navbar */
        .navbar {
            background-color: var(--white-color);
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky; top: 0; z-index: 1000;
        }
        .navbar .logo {
            font-size: 1.6em; font-weight: bold;
            color: var(--dark-color); text-decoration: none;
        }
        .navbar .nav-links a {
            color: #555; text-decoration: none; padding: 8px 12px;
            position: relative; display: inline-block; font-weight: 500;
        }
        .navbar .nav-links a:hover { color: var(--primary-color); }
        .navbar .nav-links a.admin-link { color: var(--danger-color); font-weight: bold; }
        .cart-badge {
            position: absolute; top: 0px; right: 0px;
            background-color: var(--danger-color); color: white;
            font-size: 11px; font-weight: bold; border-radius: 50%;
            width: 18px; height: 18px;
            display: flex; justify-content: center; align-items: center;
        }
        
        /* Thông báo */
        .message { padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .message.success { background-color: #d4edda; color: #155724; }
        .message.error { background-color: #f8d7da; color: #721c24; }

        /* Container */
        .container {
            max-width: 900px;
            margin: 30px auto;
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
        
        .comment-form textarea {
            width: 95%;
            min-height: 100px;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
            margin-bottom: 10px;
        }
        .btn-submit-comment {
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 10px 20px;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 5px;
            cursor: pointer;
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
        }
        
        footer {
            text-align: center; padding: 30px;
            background-color: #222; color: #aaa;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<nav class="navbar">
    <a href="index.php" class="logo">PhoneStore</a>
    <div class="nav-links">
        <a href="gio_hang.php">
            Giỏ Hàng
            <?php if ($cart_count > 0): ?>
                <span class="cart-badge"><?php echo $cart_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="tin_tuc.php">Tin Tức</a>
        
        <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
            <a href="don_hang_cua_toi.php">Đơn Hàng</a> 
            <a href="thong_tin_tai_khoan.php">
                <?php echo htmlspecialchars($_SESSION['ten'] ?? $_SESSION['email']); ?>
            </a>
            <?php if ($_SESSION['vai_tro'] == 'quan_tri'): ?>
                <a href="quan_tri/index.php" class="admin-link">Trang Quản Trị</a>
            <?php endif; ?>
            <a href="dang_xuat.php">Đăng Xuất</a>
        <?php else: ?>
            <a href="dang_nhap.php">Đăng Nhập</a>
            <a href="dang_ky.php">Đăng Ký</a>
        <?php endif; ?>
        
    </div>
</nav>

<main class="container">

    <?php if ($thong_bao_thanh_cong): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao_thanh_cong); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <?php if ($article): // CHỈ HIỂN THỊ NẾU TÌM THẤY BÀI VIẾT ?>
        
        <div class="article-header">
            <h1><?php echo htmlspecialchars($article['tieu_de']); ?></h1>
            <div class="article-meta">
                Đăng bởi <strong><?php echo htmlspecialchars($article['ten'] ?? 'Admin'); ?></strong> 
                vào ngày <?php echo date('d/m/Y', strtotime($article['ngay_dang'])); ?>
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
                <p><?php echo nl2br(htmlspecialchars($article['noi_dung_1'])); ?></p>
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
                    <form action="xu_ly_binh_luan.php" method="POST"> 
                        <input type="hidden" name="id_tin_tuc" value="<?php echo $id_tin_tuc; ?>">
                        <div class="form-group">
                            <label for="noi_dung_binh_luan">Viết bình luận của bạn:</label>
                            <textarea id="noi_dung_binh_luan" name="noi_dung" rows="4" required></textarea>
                        </div>
                        <button type="submit" class="btn-submit-comment">Gửi bình luận</button>
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

    <?php elseif (empty($thong_bao_loi)): ?>
        <h1>Không tìm thấy bài viết</h1>
        <p>Bài viết bạn đang tìm kiếm không tồn tại hoặc đã bị ẩn. Vui lòng quay lại.</p>
        <a href="tin_tuc.php" style="text-decoration: none; color: var(--primary-color); font-weight: bold;">&larr; Quay lại trang tin tức</a>
    <?php endif; ?>
</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> - PhoneStore. Đã đăng ký bản quyền.</p>
</footer>

</body>
</html>