<?php
// 1. GỌI LOGIC TRƯỚC TIÊN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
$product = null;
$related_products = []; // (MỚI)
$reviews = []; // (MỚI)
$id_san_pham = (int)($_GET['id'] ?? 0);
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;
$thong_bao_loi = "";
$thong_bao_danh_gia = $_SESSION['review_message'] ?? "";
unset($_SESSION['review_message']);
$hom_nay = date('Y-m-d'); 

if ($id_san_pham == 0) {
    header("Location: index.php"); 
    exit();
}

// Biến kiểm tra
$user_da_mua_hang = false;
$user_da_danh_gia = false;

// --- TRUY VẤN 1: LẤY SẢN PHẨM CHÍNH ---
try {
    $sql = "SELECT 
                s.*, 
                h.ten_hang, 
                ts.man_hinh, ts.do_phan_giai, ts.tan_so_quet, ts.chip_xu_ly, ts.gpu, 
                ts.ram, ts.rom, ts.he_dieu_hanh, ts.camera_sau, ts.camera_truoc, 
                ts.dung_luong_pin, ts.sac, ts.ket_noi, ts.sim, ts.trong_luong, 
                ts.chat_lieu, ts.khang_nuoc_bui, ts.bao_mat, 
                ts.anh_phu_1, ts.anh_phu_2, ts.anh_phu_3, ts.anh_phu_4
            FROM san_pham s
            LEFT JOIN hang_san_xuat h ON s.id_hang = h.id_hang
            LEFT JOIN thong_so_ky_thuat ts ON s.id = ts.id_san_pham
            WHERE s.id = ? AND s.trang_thai = 'hiện'";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_san_pham);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 1) {
        $product = $result->fetch_assoc();
        $page_title = $product['ten_san_pham']; 
    } else {
        $thong_bao_loi = "Không tìm thấy sản phẩm này hoặc sản phẩm đã bị ẩn.";
    }
    
    // --- (MỚI) TRUY VẤN 2: LẤY SẢN PHẨM LIÊN QUAN (CÙNG HÃNG) ---
    if ($product) {
        $id_hang_hien_tai = $product['id_hang'];
        $sql_related = "SELECT s.id, s.ten_san_pham, s.anh_dai_dien, s.gia_ban, 
                               s.gia_goc, s.phan_tram_giam_gia, s.ngay_bat_dau_giam, s.ngay_ket_thuc_giam
                        FROM san_pham s
                        WHERE s.id_hang = ? AND s.id != ? AND s.trang_thai = 'hiện'
                        ORDER BY RAND() 
                        LIMIT 4"; 
        
        $stmt_related = $conn->prepare($sql_related);
        $stmt_related->bind_param("ii", $id_hang_hien_tai, $id_san_pham);
        $stmt_related->execute();
        $result_related = $stmt_related->get_result();
        while($row = $result_related->fetch_assoc()) {
            $related_products[] = $row;
        }
        
        // --- (MỚI) TRUY VẤN 3: LẤY ĐÁNH GIÁ (REVIEW) ---
        $sql_reviews = "SELECT d.*, nd.ten, nd.anh_dai_dien 
                        FROM danh_gia_san_pham d
                        JOIN nguoi_dung nd ON d.id_nguoi_dung = nd.id_nguoi_dung
                        WHERE d.id_san_pham = ? AND d.trang_thai = 'da_duyet'
                        ORDER BY d.ngay_danh_gia DESC";
        $stmt_reviews = $conn->prepare($sql_reviews);
        $stmt_reviews->bind_param("i", $id_san_pham);
        $stmt_reviews->execute();
        $result_reviews = $stmt_reviews->get_result();
        while($row = $result_reviews->fetch_assoc()) {
            $reviews[] = $row;
        }
        
        // --- (MỚI) TRUY VẤN 4: KIỂM TRA QUYỀN ĐÁNH GIÁ ---
        if ($id_nguoi_dung > 0) {
            // 4a. Kiểm tra xem đã mua hàng chưa
            $sql_check_purchase = "SELECT 1 FROM don_hang d
                                   JOIN chi_tiet_don_hang ct ON d.id_don_hang = ct.id_don_hang
                                   WHERE d.id_nguoi_dung = ? AND ct.id_san_pham = ? AND d.trang_thai_don_hang = 'hoan_thanh'
                                   LIMIT 1";
            $stmt_check_purchase = $conn->prepare($sql_check_purchase);
            $stmt_check_purchase->bind_param("ii", $id_nguoi_dung, $id_san_pham);
            $stmt_check_purchase->execute();
            if ($stmt_check_purchase->get_result()->num_rows > 0) {
                $user_da_mua_hang = true;
            }
            
            // 4b. Kiểm tra xem đã đánh giá chưa
            $sql_check_review = "SELECT 1 FROM danh_gia_san_pham WHERE id_nguoi_dung = ? AND id_san_pham = ? LIMIT 1";
            $stmt_check_review = $conn->prepare($sql_check_review);
            $stmt_check_review->bind_param("ii", $id_nguoi_dung, $id_san_pham);
            $stmt_check_review->execute();
            if ($stmt_check_review->get_result()->num_rows > 0) {
                $user_da_danh_gia = true;
            }
        }
    }

} catch (Exception $e) {
    $thong_bao_loi = "Lỗi CSDL: " . $e->getMessage();
}

// (SỬA LỖI GIÁ) Logic tính giá
if ($product) {
    $gia_hien_thi = (float)$product['gia_ban'];
    $gia_cu = !empty($product['gia_goc']) ? (float)$product['gia_goc'] : null;
    $phan_tram_hien_thi = null;
    $dang_giam_gia_theo_ngay = (
        !empty($product['ngay_bat_dau_giam']) &&
        !empty($product['ngay_ket_thuc_giam']) &&
        $hom_nay >= $product['ngay_bat_dau_giam'] &&
        $hom_nay <= $product['ngay_ket_thuc_giam']
    );
    if ($dang_giam_gia_theo_ngay && !empty($product['phan_tram_giam_gia'])) {
        $gia_cu = $product['gia_ban']; 
        $gia_hien_thi = $gia_cu * (1 - (float)$product['phan_tram_giam_gia'] / 100);
        $phan_tram_hien_thi = (int)$product['phan_tram_giam_gia'];
    } 
    else if (!empty($gia_cu) && $gia_cu > $gia_hien_thi) {
        $phan_tram_hien_thi = round((($gia_cu - $gia_hien_thi) / $gia_cu) * 100);
    }
    else {
        $gia_cu = null; 
    }
}
?>

<?php
// 3. GỌI ĐẦU TRANG 
require 'dung_chung/dau_trang.php';
?>

<style>
    /* (SỬA LỖI "Trống trải") Tăng chiều rộng container cho trang này */
    .container-product-detail {
        max-width: 1400px; /* Rộng hơn 1300px */
        margin: 30px auto;
        padding: 0 20px;
        flex-grow: 1;
    }

    /* (SỬA LỖI "Ảnh cuộn") Bố cục 2 cột MỚI (Không sticky) */
    .product-detail-layout {
        display: grid;
        grid-template-columns: 1.2fr 1fr; /* Cột 1: 55%, Cột 2: 45% */
        gap: 40px;
        align-items: flex-start;
        background: #fff;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-bottom: 30px;
    }
    
    /* Box chung */
    .detail-box {
        background: #fff;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }

    /* --- CỘT 1: BÊN TRÁI (Tên, Ảnh, Mô tả) --- */
    .product-main-content {
        display: flex;
        flex-direction: column;
        gap: 20px; /* Khoảng cách giữa Tên và Ảnh */
    }
    
    .product-main-content h1 {
        font-size: 2.2rem;
        margin-top: 0;
        margin-bottom: 5px;
        color: #333;
    }
    .product-main-content .brand {
        font-size: 1.1em;
        color: #555;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }

    /* Box Thư viện ảnh */
    .product-gallery {
        /* Bỏ sticky */
    }
    .main-image-container {
        border: 1px solid #eee;
        border-radius: 8px;
        padding: 10px;
        background: #fdfdfd;
        text-align: center;
    }
    .main-image-container img {
        max-width: 100%;
        height: 450px;
        object-fit: contain;
    }
    .thumbnail-images {
        display: grid;
        grid-template-columns: repeat(5, 1fr); /* 5 ảnh nhỏ 1 hàng */
        gap: 10px;
        margin-top: 15px;
    }
    .thumbnail-images img {
        width: 100%;
        height: 70px;
        object-fit: contain;
        border: 2px solid #ddd;
        border-radius: 5px;
        cursor: pointer;
        transition: border-color 0.2s;
    }
    .thumbnail-images img:hover,
    .thumbnail-images img.active {
        border-color: var(--primary-color);
    }
    
    /* Box Mô tả chi tiết (Nằm ở Cột 1) */
    .description-section {
        margin-top: 20px; /* Nằm dưới ảnh */
    }
    .description-section h2 {
        font-size: 1.5rem;
        color: #333;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-top: 0;
        margin-bottom: 20px;
    }
    .description-content {
        font-size: 1.1rem;
        line-height: 1.7;
        color: #444;
    }
    .description-content p {
        margin-bottom: 20px;
    }

    /* --- CỘT 2: BÊN PHẢI (Giá, Mua, Thông số) --- */
    .product-sidebar-content {
        display: flex;
        flex-direction: column;
        gap: 25px; /* Khoảng cách giữa các box */
        /* (SỬA LỖI "Ảnh cuộn") LÀM CỘT NÀY STICKY */
        position: sticky;
        top: 90px;
    }
    
    /* Box Thông tin giá/mua */
    .product-info-box {
        border: 1px solid #eee;
        padding: 20px;
        border-radius: 8px;
    }
    .product-price-box {
        background: #f9f9f9;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
    }
    .product-price-box .current-price {
        font-size: 2.2rem;
        font-weight: bold;
        color: var(--danger-color);
    }
    .product-price-box .old-price {
        font-size: 1.2rem;
        color: #999;
        text-decoration: line-through;
        margin-left: 15px;
    }
    .product-short-description {
        font-size: 1rem;
        color: #555;
        line-height: 1.6;
        margin-bottom: 20px;
        padding-left: 15px;
        border-left: 3px solid #eee;
    }
    .product-actions {
        display: flex;
        gap: 15px;
        margin-top: 25px;
    }
    .product-actions .form-group {
        flex: 0 1 120px; 
        margin-bottom: 0;
    }
    .product-actions .btn-submit {
        flex: 1; 
        margin-top: 0;
        font-size: 1.1rem;
        padding: 15px;
    }
    
    /* Box Thông số kỹ thuật (Accordion) */
    .specs-section {
        border: 1px solid #eee;
        padding: 20px;
        border-radius: 8px;
    }
    .specs-section h2 {
        font-size: 1.5rem;
        color: #333;
        margin-top: 0;
        margin-bottom: 15px;
    }
    /* CSS cho Accordion */
    .specs-accordion details {
        border-bottom: 1px solid #f0f0f0;
    }
    .specs-accordion details:last-child {
        border-bottom: none;
    }
    .specs-accordion summary {
        padding: 15px 5px;
        cursor: pointer;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--dark-color);
        display: flex;
        justify-content: space-between;
        align-items: center;
        list-style: none; /* Xóa mũi tên mặc định */
    }
    .specs-accordion summary::-webkit-details-marker {
        display: none; /* Xóa mũi tên mặc định (Chrome) */
    }
    .specs-accordion summary:hover {
        color: var(--primary-color);
    }
    /* Icon + / - */
    .specs-accordion summary::after {
        content: '\f067'; /* Icon Plus */
        font-family: 'Font Awesome 5 Free';
        font-weight: 900;
        font-size: 0.8em;
        color: var(--primary-color);
        transition: transform 0.2s ease;
    }
    .specs-accordion details[open] > summary::after {
        content: '\f068'; /* Icon Minus */
        transform: rotate(180deg);
    }
    
    .specs-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 10px;
    }
    .specs-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .specs-table td {
        padding: 10px 5px;
        font-size: 0.95rem;
        border-bottom: 1px solid #f0f0f0;
    }
    .specs-table td:first-child {
        font-weight: 500;
        color: #555;
        width: 40%; 
    }

    /* (MỚI) Box Đánh giá & Bình luận (Nằm DƯỚI cùng, full-width) */
    .reviews-section {
        margin-bottom: 30px;
    }
    .reviews-section h2 {
        font-size: 1.5rem;
        color: #333;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-top: 0;
        margin-bottom: 20px;
    }
    /* Tóm tắt sao */
    .reviews-summary {
        display: flex;
        align-items: center;
        gap: 20px;
        background: #f9f9f9;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    .average-rating-display {
        text-align: center;
        padding-right: 20px;
        border-right: 1px solid #ddd;
    }
    .average-rating-display .rating-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: var(--dark-color);
    }
    .average-rating-display .star-rating {
        color: #f39c12;
        font-size: 1em; /* Nhỏ hơn số */
    }
    .average-rating-display .total-reviews {
        font-size: 0.9em;
        font-weight: normal;
        color: #777;
    }
    .write-review-area {
        margin-left: auto;
    }
    
    /* Form Đánh giá */
    .review-form-box {
        border: 1px dashed #ccc;
        border-radius: 8px;
        padding: 20px;
    }
    .review-form-box p {
        font-weight: bold;
    }
    .star-rating-input {
        display: flex;
        flex-direction: row-reverse; /* Đảo ngược để css :hover */
        justify-content: flex-end;
        gap: 5px;
        margin-bottom: 15px;
    }
    .star-rating-input input[type="radio"] {
        display: none;
    }
    .star-rating-input label {
        font-size: 2rem;
        color: #ccc;
        cursor: pointer;
        transition: color 0.2s;
    }
    /* Khi hover hoặc check, tất cả sao BÊN TRÁI nó sẽ sáng */
    .star-rating-input input:checked ~ label,
    .star-rating-input label:hover,
    .star-rating-input label:hover ~ label {
        color: #f39c12;
    }
    
    /* Danh sách bình luận */
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
    .comment-stars {
        color: #f39c12;
        margin-top: 5px;
        font-size: 0.9em;
    }
    .comment-text {
        margin-top: 5px;
        white-space: pre-wrap; /* Giữ xuống dòng */
    }
    .admin-reply-box {
        background: #f0f7ff;
        border: 1px solid #b3d7ff;
        padding: 15px;
        margin-top: 15px;
        border-radius: 5px;
    }
    .admin-reply-box h4 { margin: 0 0 5px 0; color: #0056b3; }


    /* (SỬA LỖI CSS) CSS Sản phẩm liên quan (Giống index.php) */
    .related-products-section { margin-top: 40px; }
    .product-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 25px; }
    .product-card { background-color: var(--white-color); border: 1px solid #eee; border-radius: var(--border-radius); box-shadow: var(--shadow); transition: all 0.3s ease; position: relative; display: flex; flex-direction: column; overflow: hidden; }
    .product-card:hover { transform: translateY(-8px); box-shadow: 0 10px 20px rgba(0,0,0,0.12); }
    .product-image-link { display: block; background-color: #f9f9f9; padding: 10px; }
    .product-image { width: 100%; height: 250px; object-fit: contain; }
    .product-details { padding: 20px; flex-grow: 1; display: flex; flex-direction: column; text-align: left; }
    .product-brand { font-size: 0.85rem; color: #777; margin-bottom: 5px; }
    .product-title { font-size: 1.1rem; font-weight: 600; color: var(--dark-color); margin: 0; height: 44px; overflow: hidden; text-decoration: none; }
    .product-price { margin-top: auto; padding-top: 10px; }
    .product-price .current-price { font-size: 1.3rem; color: var(--danger-color); font-weight: bold; }
    .product-price .old-price { font-size: 0.9rem; color: #999; text-decoration: line-through; margin-left: 8px; }
    .sale-badge { position: absolute; top: 15px; right: 15px; background-color: var(--danger-color); color: #fff; padding: 4px 8px; font-size: 0.8rem; border-radius: 5px; font-weight: bold; }
    .add-to-cart-form { margin-top: 15px; }
    .btn-add-to-cart { background-color: var(--primary-color); color: white; border: none; border-radius: 5px; padding: 12px 15px; width: 100%; font-size: 1rem; font-weight: bold; cursor: pointer; transition: background-color 0.2s; }
    .btn-add-to-cart:hover { background-color: #0056b3; }

    /* Responsive */
    @media (max-width: 900px) {
        .product-detail-layout {
            grid-template-columns: 1fr; /* Xếp chồng 2 cột */
        }
    }
</style>

<main class="container-product-detail">

    <?php if (!empty($thong_bao_loi)): ?>
        <div class="message error"><?php echo $thong_bao_loi; ?></div>
    
    <?php elseif ($product): ?>
    
        <div class="product-detail-layout">
            
            <div class="product-main-content">
            
                <div>
                    <h1><?php echo htmlspecialchars($product['ten_san_pham']); ?></h1>
                    <p class="brand">Thương hiệu: <strong><?php echo htmlspecialchars($product['ten_hang']); ?></strong></p>
                </div>
            
                <div class="product-gallery">
                    <div class="main-image-container">
                        <?php 
                        $main_img_path = 'tai_len/san_pham/' . ($product['anh_dai_dien'] ?? 'default.png');
                        if (empty($product['anh_dai_dien']) || !file_exists($main_img_path)) {
                            $main_img_path = 'tai_len/san_pham/default.png'; 
                        }
                        ?>
                        <img src="<?php echo $main_img_path; ?>" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>" id="main-product-image">
                    </div>
                    
                    <div class="thumbnail-images">
                        <img src="<?php echo $main_img_path; ?>" alt="Thumbnail 1" class="active" onclick="changeImage(this, '<?php echo $main_img_path; ?>')">
                        <?php 
                        $sub_img_1 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_1'] ?? '');
                        if ($product['anh_phu_1'] && file_exists($sub_img_1)): ?>
                            <img src="<?php echo $sub_img_1; ?>" alt="Thumbnail 2" onclick="changeImage(this, '<?php echo $sub_img_1; ?>')">
                        <?php endif; ?>
                        <?php 
                        $sub_img_2 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_2'] ?? '');
                        if ($product['anh_phu_2'] && file_exists($sub_img_2)): ?>
                            <img src="<?php echo $sub_img_2; ?>" alt="Thumbnail 3" onclick="changeImage(this, '<?php echo $sub_img_2; ?>')">
                        <?php endif; ?>
                        <?php 
                        $sub_img_3 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_3'] ?? '');
                        if ($product['anh_phu_3'] && file_exists($sub_img_3)): ?>
                            <img src="<?php echo $sub_img_3; ?>" alt="Thumbnail 4" onclick="changeImage(this, '<?php echo $sub_img_3; ?>')">
                        <?php endif; ?>
                        <?php 
                        $sub_img_4 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_4'] ?? '');
                        if ($product['anh_phu_4'] && file_exists($sub_img_4)): ?>
                            <img src="<?php echo $sub_img_4; ?>" alt="Thumbnail 5" onclick="changeImage(this, '<?php echo $sub_img_4; ?>')">
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="description-section">
                    <h2>Mô tả chi tiết sản phẩm</h2>
                    <div class="description-content">
                        <?php echo nl2br(htmlspecialchars($product['mo_ta_chi_tiet'])); ?>
                    </div>
                </div> </div> <div class="product-sidebar-content">
                
                <div class="product-info-box">
                    <div class="product-price-box">
                        <span class="current-price"><?php echo number_format($gia_hien_thi, 0, ',', '.'); ?>đ</span>
                        <?php if ($gia_cu): ?>
                            <span class="old-price"><?php echo number_format($gia_cu, 0, ',', '.'); ?>đ</span>
                        <?php endif; ?>
                        <?php if ($phan_tram_hien_thi): ?>
                            <span class="sale-badge" style="position: static; margin-left: 10px; font-size: 1rem;">-<?php echo $phan_tram_hien_thi; ?>%</span>
                        <?php endif; ?>
                    </div>
                    
                    <p class="product-short-description">
                        <?php echo nl2br(htmlspecialchars($product['mo_ta_ngan'])); ?>
                    </p>
                    
                    <form action="xu_ly_gio_hang.php" method="POST" class="product-actions ajax-add-to-cart-form" data-turbolinks="false">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id_san_pham" value="<?php echo $product['id']; ?>">
                        
                        <div class="form-group">
                            <label for="so_luong">Số lượng:</label>
                            <input type="number" id="so_luong" name="so_luong" value="1" min="1" max="<?php echo $product['so_luong_ton']; ?>">
                        </div>
                        
                        <button type="submit" class="btn-submit"><i class="fas fa-cart-plus"></i> Thêm vào giỏ</button>
                    </form>
                </div> <div class="specs-section">
                    <h2>Thông số kỹ thuật</h2>
                    <div class="specs-accordion">
                        
                        <details>
                            <summary>Cấu hình & Bộ nhớ</summary>
                            <table class="specs-table">
                                <tbody>
                                    <tr><td>Chip xử lý (CPU)</td><td><?php echo htmlspecialchars($product['chip_xu_ly']); ?></td></tr>
                                    <tr><td>Chip đồ họa (GPU)</td><td><?php echo htmlspecialchars($product['gpu']); ?></td></tr>
                                    <tr><td>RAM</td><td><?php echo htmlspecialchars($product['ram']); ?></td></tr>
                                    <tr><td>ROM (Bộ nhớ trong)</td><td><?php echo htmlspecialchars($product['rom']); ?></td></tr>
                                    <tr><td>Hệ điều hành</td><td><?php echo htmlspecialchars($product['he_dieu_hanh']); ?></td></tr>
                                </tbody>
                            </table>
                        </details>
                        
                        <details>
                            <summary>Camera & Màn hình</summary>
                             <table class="specs-table">
                                <tbody>
                                    <tr><td>Màn hình</td><td><?php echo htmlspecialchars($product['man_hinh']); ?></td></tr>
                                    <tr><td>Độ phân giải</td><td><?php echo htmlspecialchars($product['do_phan_giai']); ?></td></tr>
                                    <tr><td>Tần số quét</td><td><?php echo htmlspecialchars($product['tan_so_quet']); ?></td></tr>
                                    <tr><td>Camera sau</td><td><?php echo htmlspecialchars($product['camera_sau']); ?></td></tr>
                                    <tr><td>Camera trước</td><td><?php echo htmlspecialchars($product['camera_truoc']); ?></td></tr>
                                </tbody>
                            </table>
                        </details>
                        
                        <details>
                            <summary>Pin & Sạc</summary>
                             <table class="specs-table">
                                <tbody>
                                    <tr><td>Dung lượng Pin</td><td><?php echo htmlspecialchars($product['dung_luong_pin']); ?></td></tr>
                                    <tr><td>Công nghệ sạc</td><td><?php echo htmlspecialchars($product['sac']); ?></td></tr>
                                </tbody>
                            </table>
                        </details>
                        
                        <details>
                            <summary>Kết nối & Tiện ích</summary>
                             <table class="specs-table">
                                <tbody>
                                    <tr><td>Thẻ SIM</td><td><?php echo htmlspecialchars($product['sim']); ?></td></tr>
                                    <tr><td>Kết nối</td><td><?php echo htmlspecialchars($product['ket_noi']); ?></td></tr>
                                    <tr><td>Bảo mật</td><td><?php echo htmlspecialchars($product['bao_mat']); ?></td></tr>
                                    <tr><td>Kháng nước, bụi</td><td><?php echo htmlspecialchars($product['khang_nuoc_bui']); ?></td></tr>
                                </tbody>
                            </table>
                        </details>
                        
                        <details>
                            <summary>Thiết kế & Chất liệu</summary>
                             <table class="specs-table">
                                <tbody>
                                    <tr><td>Trọng lượng</td><td><?php echo htmlspecialchars($product['trong_luong']); ?></td></tr>
                                    <tr><td>Chất liệu</td><td><?php echo htmlspecialchars($product['chat_lieu']); ?></td></tr>
                                </tbody>
                            </table>
                        </details>
                        
                    </div>
                </div> </div> </div> <div class="reviews-section detail-box">
            <h2>Đánh giá & Nhận xét</h2>
            
            <?php if (!empty($thong_bao_danh_gia)): ?>
                <div class="message success"><?php echo $thong_bao_danh_gia; ?></div>
            <?php endif; ?>

            <div class="reviews-summary">
                <div class="average-rating-display">
                    <div class="rating-number"><?php echo number_format($product['avg_rating'], 1); ?></div>
                    <div class="star-rating">
                        <?php 
                        $avg_rounded = round($product['avg_rating'] * 2) / 2; // Làm tròn 0.5
                        for ($i = 1; $i <= 5; $i++): 
                            if ($i <= $avg_rounded) {
                                echo '<i class="fas fa-star"></i>'; // Sao đầy
                            } elseif ($i - 0.5 == $avg_rounded) {
                                echo '<i class="fas fa-star-half-alt"></i>'; // Sao nửa
                            } else {
                                echo '<i class="far fa-star"></i>'; // Sao rỗng
                            }
                        endfor; 
                        ?>
                    </div>
                    <div class="total-reviews"><?php echo $product['total_reviews']; ?> đánh giá</div>
                </div>
                <div class="write-review-area">
                    <?php if ($id_nguoi_dung > 0): ?>
                        <?php if ($user_da_mua_hang && !$user_da_danh_gia): ?>
                            <button class="btn" onclick="document.getElementById('review-form-box').style.display='block'; this.style.display='none';">
                                <i class="fas fa-pen"></i> Viết đánh giá
                            </button>
                        <?php elseif ($user_da_danh_gia): ?>
                            <p><em>Bạn đã đánh giá sản phẩm này.</em></p>
                        <?php else: ?>
                            <p><em>Bạn chỉ có thể đánh giá sau khi đã mua sản phẩm này.</em></p>
                        <?php endif; ?>
                    <?php else: ?>
                        <p>Vui lòng <a href="dang_nhap.php">đăng nhập</a> và <a href="#">mua hàng</a> để đánh giá.</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="review-form-box" id="review-form-box" style="display: <?php echo ($user_da_mua_hang && !$user_da_danh_gia && !empty($thong_bao_danh_gia)) ? 'block' : 'none'; ?>;">
                <form id="review-form" action="xu_ly_danh_gia.php" method="POST" data-turbolinks="false">
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="id_san_pham" value="<?php echo $product['id']; ?>">
                    <p>Bạn thấy sản phẩm này thế nào?</p>
                    <div class="star-rating-input">
                        <input type="radio" id="star5" name="so_sao" value="5" required><label for="star5" title="5 sao"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star4" name="so_sao" value="4"><label for="star4" title="4 sao"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star3" name="so_sao" value="3"><label for="star3" title="3 sao"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star2" name="so_sao" value="2"><label for="star2" title="2 sao"><i class="fas fa-star"></i></label>
                        <input type="radio" id="star1" name="so_sao" value="1"><label for="star1" title="1 sao"><i class="fas fa-star"></i></label>
                    </div>
                    <div class="form-group">
                        <label for="noi_dung_danh_gia">Viết nhận xét của bạn (tùy chọn):</label>
                        <textarea id="noi_dung_danh_gia" name="noi_dung" rows="4"></textarea>
                    </div>
                    <button type="submit" class="btn btn-submit-comment">Gửi đánh giá</button>
                </form>
            </div>
            
            <ul class="comment-list">
                <?php if (empty($reviews)): ?>
                    <p>Chưa có đánh giá nào cho sản phẩm này.</p>
                <?php else: ?>
                    <?php foreach ($reviews as $review): ?>
                        <li class="comment-item">
                            <div class="comment-avatar">
                                <?php 
                                $anh_path_avatar = 'tai_len/avatars/' . ($review['anh_dai_dien'] ?? 'default-avatar.png');
                                if (empty($review['anh_dai_dien']) || !file_exists($anh_path_avatar)) {
                                    $anh_path_avatar = 'tai_len/avatars/default-avatar.png'; 
                                }
                                ?>
                                <img src="<?php echo $anh_path_avatar; ?>" alt="Avatar">
                            </div>
                            <div class="comment-body">
                                <span class="comment-author"><?php echo htmlspecialchars($review['ten']); ?></span>
                                <span class="comment-date"><?php echo date('d/m/Y H:i', strtotime($review['ngay_danh_gia'])); ?></span>
                                <div class="comment-stars">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <i class="<?php echo ($i <= $review['so_sao']) ? 'fas' : 'far'; ?> fa-star"></i>
                                    <?php endfor; ?>
                                </div>
                                <p class="comment-text"><?php echo nl2br(htmlspecialchars($review['noi_dung'])); ?></p>
                                
                                <?php if (!empty($review['noi_dung_tra_loi'])): ?>
                                    <div class="admin-reply-box">
                                        <h4><i class="fas fa-reply"></i> Phản hồi từ PhoneStore:</h4>
                                        <p><?php echo nl2br(htmlspecialchars($review['noi_dung_tra_loi'])); ?></p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <?php if (!empty($related_products)): ?>
            <section class="related-products-section">
                <h2 class="section-title">Sản phẩm liên quan</h2>
                <div class="product-grid">
                    <?php foreach($related_products as $sp): ?>
                        <?php
                        // Logic tính giá cho sản phẩm liên quan
                        $gia_hien_thi_rel = (float)$sp['gia_ban'];
                        $gia_cu_rel = !empty($sp['gia_goc']) ? (float)$sp['gia_goc'] : null;
                        $phan_tram_hien_thi_rel = null;
                        $dang_giam_gia_theo_ngay_rel = (
                            !empty($sp['ngay_bat_dau_giam']) && !empty($sp['ngay_ket_thuc_giam']) &&
                            $hom_nay >= $sp['ngay_bat_dau_giam'] && $hom_nay <= $sp['ngay_ket_thuc_giam']
                        );
                        if ($dang_giam_gia_theo_ngay_rel && !empty($sp['phan_tram_giam_gia'])) {
                            $gia_cu_rel = $sp['gia_ban']; 
                            $gia_hien_thi_rel = $gia_cu_rel * (1 - (float)$sp['phan_tram_giam_gia'] / 100);
                            $phan_tram_hien_thi_rel = (int)$sp['phan_tram_giam_gia'];
                        } 
                        else if (!empty($gia_cu_rel) && $gia_cu_rel > $gia_hien_thi_rel) {
                            $phan_tram_hien_thi_rel = round((($gia_cu_rel - $gia_hien_thi_rel) / $gia_cu_rel) * 100);
                        }
                        else { $gia_cu_rel = null; }
                        ?>
                        <div class="product-card">
                            <?php if ($phan_tram_hien_thi_rel): ?>
                                <div class="sale-badge">-<?php echo $phan_tram_hien_thi_rel; ?>%</div>
                            <?php endif; ?>
                            
                            <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="product-image-link">
                                <?php 
                                $anh_path_rel = 'tai_len/san_pham/' . ($sp['anh_dai_dien'] ?? 'default.png');
                                if (empty($sp['anh_dai_dien']) || !file_exists($anh_path_rel)) {
                                    $anh_path_rel = 'tai_len/san_pham/default.png'; 
                                }
                                ?>
                                <img src="<?php echo $anh_path_rel; ?>" alt="<?php echo htmlspecialchars($sp['ten_san_pham']); ?>" class="product-image">
                            </a>
                            <div class="product-details">
                                <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="product-title">
                                    <?php echo htmlspecialchars($sp['ten_san_pham']); ?>
                                </a>
                                <div class="product-price">
                                    <span class="current-price"><?php echo number_format($gia_hien_thi_rel, 0, ',', '.'); ?>đ</span>
                                    <?php if ($gia_cu_rel): ?>
                                        <span class="old-price"><?php echo number_format($gia_cu_rel, 0, ',', '.'); ?>đ</span>
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
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
        
    <?php endif; ?>
    
</main> <script>
    // 1. JAVASCRIPT CHO GALLERY ẢNH
    function changeImage(thumbnailElement, newSrc) {
        // Đổi ảnh chính
        const mainImage = document.getElementById('main-product-image');
        if (mainImage) {
            mainImage.src = newSrc;
        }
        
        // (MỚI) Xóa class 'active' khỏi tất cả thumbnail
        const allThumbnails = document.querySelectorAll('.thumbnail-images img');
        allThumbnails.forEach(img => img.classList.remove('active'));
        
        // Thêm class 'active' cho thumbnail được bấm
        thumbnailElement.classList.add('active');
    }

    // 2. JAVASCRIPT CHO AJAX (GIỎ HÀNG & ĐÁNH GIÁ)
    document.addEventListener("turbolinks:load", function() {
        
        // --- Xử lý "Thêm vào giỏ" ---
        const allForms = document.querySelectorAll('.ajax-add-to-cart-form');
        allForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault(); 
                const formData = new FormData(this);
                
                fetch('xu_ly_gio_hang.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        updateCartBadge(data.new_cart_count);
                        showToast('Đã thêm vào giỏ hàng!', 'success');
                    } else {
                        showToast(data.message || 'Lỗi: Không thể thêm vào giỏ.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Lỗi Fetch:', error);
                    showToast('Lỗi kết nối. Vui lòng thử lại.', 'error');
                });
            });
        });
        
        // --- (MỚI) Xử lý "Gửi Đánh giá" ---
        const reviewForm = document.getElementById('review-form');
        if (reviewForm) {
            reviewForm.addEventListener('submit', function(event) {
                event.preventDefault();
                const formData = new FormData(this);
                const submitButton = this.querySelector('button[type="submit"]');
                submitButton.disabled = true;
                submitButton.textContent = 'Đang gửi...';

                fetch('xu_ly_danh_gia.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Tải lại trang để xem đánh giá mới (hoặc thông báo chờ duyệt)
                        Turbolinks.visit(window.location.href);
                    } else {
                        showToast(data.message || 'Lỗi: Không thể gửi đánh giá.', 'error');
                        submitButton.disabled = false;
                        submitButton.textContent = 'Gửi đánh giá';
                    }
                })
                .catch(error => {
                    showToast('Lỗi kết nối. Vui lòng thử lại.', 'error');
                    submitButton.disabled = false;
                    submitButton.textContent = 'Gửi đánh giá';
                });
            });
        }
    });

    // Hàm cập nhật icon giỏ hàng
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

    // Hàm hiển thị thông báo "Toast"
    function showToast(message, type = 'success') {
        const toastContainer = document.getElementById('toast-container');
        if (!toastContainer) return; // dau_trang.php đã tạo

        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `<i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-triangle'}"></i> ${message}`;
        
        toastContainer.appendChild(toast);

        setTimeout(() => {
            toast.classList.add('show');
        }, 100); 

        setTimeout(() => {
            toast.classList.remove('show');
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