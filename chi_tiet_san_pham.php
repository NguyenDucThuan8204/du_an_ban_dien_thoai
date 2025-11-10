<?php
// 1. BẮT ĐẦU SESSION (BẮT BUỘC PHẢI Ở DÒNG ĐẦU TIÊN)
session_start(); 
require 'dung_chung/ket_noi_csdl.php';

// 2. LẤY ID SẢN PHẨM TỪ URL
$id_san_pham = (int)($_GET['id'] ?? 0);

if ($id_san_pham <= 0) {
    header("Location: index.php"); // Nếu ID không hợp lệ, về trang chủ
    exit();
}

// 3. TRUY VẤN CSDL (NỐI 3 BẢNG)
$sql = "SELECT 
            s.*, 
            ts.man_hinh, ts.do_phan_giai, ts.tan_so_quet, ts.chip_xu_ly, ts.gpu, 
            ts.ram, ts.rom, ts.he_dieu_hanh, ts.camera_sau, ts.camera_truoc, 
            ts.dung_luong_pin, ts.sac, ts.ket_noi, ts.sim, ts.trong_luong, 
            ts.chat_lieu, ts.khang_nuoc_bui, ts.bao_mat,
            ts.anh_phu_1, ts.anh_phu_2, ts.anh_phu_3, ts.anh_phu_4,
            h.ten_hang
        FROM 
            san_pham s
        LEFT JOIN 
            thong_so_ky_thuat ts ON s.id = ts.id_san_pham
        LEFT JOIN 
            hang_san_xuat h ON s.id_hang = h.id_hang
        WHERE 
            s.id = ? AND s.trang_thai = 'hiện' AND h.trang_thai = 'hien_thi'";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_san_pham);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Không tìm thấy sản phẩm
    $product = null;
} else {
    $product = $result->fetch_assoc();
}

// 4. LẤY SỐ LƯỢNG GIỎ HÀNG (CHO MENU)
$cart_count = 0;
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
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product ? htmlspecialchars($product['ten_san_pham']) : 'Không tìm thấy sản phẩm'; ?></title>

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
            margin: 0;
            padding: 0;
            background-color: var(--light-color);
            line-height: 1.6;
        }
        
        /* Menu điều hướng (Copy từ index.php) */
        .navbar {
            background-color: var(--white-color);
            padding: 10px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: sticky;
            top: 0;
            z-index: 1000;
        }
        .navbar .logo {
            font-size: 1.6em;
            font-weight: bold;
            color: var(--dark-color);
            text-decoration: none;
        }
        .navbar .nav-links a {
            color: #555;
            text-decoration: none;
            padding: 8px 12px;
            position: relative;
            display: inline-block;
            font-weight: 500;
        }
        .navbar .nav-links a:hover {
            color: var(--primary-color);
        }
        .navbar .nav-links a.admin-link {
            color: var(--danger-color);
            font-weight: bold;
        }
        .cart-badge {
            position: absolute; top: 0px; right: 0px;
            background-color: var(--danger-color); color: white;
            font-size: 11px; font-weight: bold; border-radius: 50%;
            width: 18px; height: 18px;
            display: flex; justify-content: center; align-items: center;
        }

        /* Container chính */
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 20px;
            background-color: var(--white-color);
            border-radius: var(--border-radius);
            box-shadow: var(--shadow);
        }
        
        /* Bố cục 2 cột (Ảnh / Thông tin) */
        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        /* Cột 1: Thư viện ảnh */
        .product-gallery {
            display: flex;
            flex-direction: column;
        }
        .main-image {
            width: 100%;
            height: 450px;
            object-fit: contain;
            border: 1px solid #eee;
            border-radius: var(--border-radius);
            margin-bottom: 15px;
        }
        .thumbnail-list {
            display: flex;
            gap: 10px;
            justify-content: center;
        }
        .thumbnail-item {
            width: 80px;
            height: 80px;
            object-fit: contain;
            border: 2px solid #eee;
            border-radius: 8px;
            cursor: pointer;
            transition: border-color 0.2s;
        }
        .thumbnail-item:hover {
            border-color: #aaa;
        }
        .thumbnail-item.active {
            border-color: var(--primary-color);
        }
        
        /* Cột 2: Thông tin sản phẩm */
        .product-info-col .brand {
            font-size: 1rem;
            color: #777;
            font-weight: 500;
            margin-bottom: 5px;
        }
        .product-info-col h1 {
            font-size: 2.2rem;
            margin: 0 0 15px 0;
            color: var(--dark-color);
        }
        .price-box {
            margin-bottom: 20px;
        }
        .price-box .current-price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--danger-color);
        }
        .price-box .old-price {
            font-size: 1.2rem;
            color: #999;
            text-decoration: line-through;
            margin-left: 15px;
        }
        .short-description {
            font-size: 1rem;
            line-height: 1.7;
            color: #555;
            margin-bottom: 20px;
        }
        
        /* Form Thêm vào giỏ */
        .cart-form {
            background-color: #f9f9f9;
            padding: 20px;
            border-radius: var(--border-radius);
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .quantity-group {
            display: flex;
            align-items: center;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        .quantity-group button {
            background-color: #eee;
            border: none;
            width: 40px;
            height: 40px;
            font-size: 1.5rem;
            cursor: pointer;
        }
        .quantity-group input {
            width: 50px;
            height: 40px;
            text-align: center;
            border: none;
            font-size: 1.1rem;
            font-weight: bold;
        }
        .btn-add-to-cart {
            background-color: var(--danger-color);
            color: white;
            border: none;
            border-radius: 5px;
            padding: 12px 15px;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: background-color 0.2s;
            flex-grow: 1; /* Nút chiếm hết phần còn lại */
        }
        .btn-add-to-cart:hover {
            background-color: #c0392b;
        }
        
        /* Phần mô tả và thông số (Full-width) */
        .product-details-full {
            margin-top: 40px;
        }
        .product-details-full h2 {
            font-size: 1.8rem;
            color: var(--dark-color);
            border-bottom: 2px solid #eee;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .long-description {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #444;
        }
        
        /* Bảng thông số */
        .specs-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 1rem;
        }
        .specs-table tr:nth-child(odd) {
            background-color: #f9f9f9;
        }
        .specs-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
        }
        .specs-table td:first-child {
            font-weight: 600;
            color: #555;
            width: 30%;
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

    <?php if ($product): // CHỈ HIỂN THỊ NẾU TÌM THẤY SẢN PHẨM ?>
        
        <?php
        // --- LOGIC GIÁ (Copy từ index.php) ---
        $hom_nay = date('Y-m-d');
        $gia_hien_thi = (float)$product['gia_ban'];
        $gia_cu = !empty($product['gia_goc']) ? (float)$product['gia_goc'] : null;
        
        $dang_giam_gia_theo_ngay = (
            !empty($product['ngay_bat_dau_giam']) &&
            !empty($product['ngay_ket_thuc_giam']) &&
            $hom_nay >= $product['ngay_bat_dau_giam'] &&
            $hom_nay <= $product['ngay_ket_thuc_giam']
        );
        
        if ($dang_giam_gia_theo_ngay && !empty($product['phan_tram_giam_gia'])) {
            $gia_cu = $product['gia_ban']; 
            $gia_hien_thi = $gia_cu * (1 - (float)$product['phan_tram_giam_gia'] / 100);
        } 
        else if (empty($gia_cu) || $gia_cu <= $gia_hien_thi) {
            $gia_cu = null; 
        }
        ?>

        <div class="detail-grid">
            
            <div class="product-gallery">
                <?php 
                $anh_chinh_path = 'tai_len/san_pham/' . ($product['anh_dai_dien'] ?? 'default.png');
                if (empty($product['anh_dai_dien']) || !file_exists($anh_chinh_path)) {
                    $anh_chinh_path = 'tai_len/san_pham/default.png'; 
                }
                ?>
                <img src="<?php echo $anh_chinh_path; ?>" alt="<?php echo htmlspecialchars($product['ten_san_pham']); ?>" id="main-image" class="main-image">
                
                <div class="thumbnail-list">
                    <img src="<?php echo $anh_chinh_path; ?>" alt="Thumbnail 1" class="thumbnail-item active" 
                         onclick="changeImage('<?php echo $anh_chinh_path; ?>', this)">
                    
                    <?php for ($i = 1; $i <= 4; $i++): ?>
                        <?php $key_anh_phu = 'anh_phu_' . $i; ?>
                        <?php if (!empty($product[$key_anh_phu])): ?>
                            <?php $anh_phu_path = 'tai_len/san_pham/gallery/' . $product[$key_anh_phu]; ?>
                            <?php if (file_exists($anh_phu_path)): ?>
                                <img src="<?php echo $anh_phu_path; ?>" alt="Thumbnail <?php echo $i + 1; ?>" class="thumbnail-item"
                                     onclick="changeImage('<?php echo $anh_phu_path; ?>', this)">
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endfor; ?>
                </div>
            </div>
            
            <div class="product-info-col">
                <h3 class="brand"><?php echo htmlspecialchars($product['ten_hang']); ?></h3>
                <h1><?php echo htmlspecialchars($product['ten_san_pham']); ?></h1>
                
                <div class="price-box">
                    <span class="current-price"><?php echo number_format($gia_hien_thi, 0, ',', '.'); ?>đ</span>
                    <?php if ($gia_cu): ?>
                        <span class="old-price"><?php echo number_format($gia_cu, 0, ',', '.'); ?>đ</span>
                    <?php endif; ?>
                </div>
                
                <div class="short-description">
                    <?php echo nl2br(htmlspecialchars($product['mo_ta_ngan'])); ?>
                </div>
                
                <form action="xu_ly_gio_hang.php" method="POST" class="cart-form">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="id_san_pham" value="<?php echo $product['id']; ?>">
                    <input type="hidden" name="return_url" value="chi_tiet_san_pham.php?id=<?php echo $product['id']; ?>">
                    
                    <div class="quantity-group">
                        <button type="button" onclick="adjustQuantity(-1)">-</button>
                        <input type="text" id="quantity" name="so_luong" value="1" readonly>
                        <button type="button" onclick="adjustQuantity(1)">+</button>
                    </div>
                    
                    <button type="submit" class="btn-add-to-cart">Thêm vào giỏ hàng</button>
                </form>
            </div>
        </div>
        
        <div class="product-details-full">
            
            <h2>Thông số kỹ thuật</h2>
            <table class="specs-table">
                <tbody>
                    <tr><td>Màn hình</td><td><?php echo htmlspecialchars($product['man_hinh']); ?></td></tr>
                    <tr><td>Độ phân giải</td><td><?php echo htmlspecialchars($product['do_phan_giai']); ?></td></tr>
                    <tr><td>Tần số quét</td><td><?php echo htmlspecialchars($product['tan_so_quet']); ?></td></tr>
                    <tr><td>Chip xử lý</td><td><?php echo htmlspecialchars($product['chip_xu_ly']); ?></td></tr>
                    <tr><td>GPU</td><td><?php echo htmlspecialchars($product['gpu']); ?></td></tr>
                    <tr><td>RAM</td><td><?php echo htmlspecialchars($product['ram']); ?></td></tr>
                    <tr><td>Dung lượng</td><td><?php echo htmlspecialchars($product['rom']); ?></td></tr>
                    <tr><td>Hệ điều hành</td><td><?php echo htmlspecialchars($product['he_dieu_hanh']); ?></td></tr>
                    <tr><td>Camera Sau</td><td><?php echo htmlspecialchars($product['camera_sau']); ?></td></tr>
                    <tr><td>Camera Trước</td><td><?php echo htmlspecialchars($product['camera_truoc']); ?></td></tr>
                    <tr><td>Pin</td><td><?php echo htmlspecialchars($product['dung_luong_pin']); ?></td></tr>
                    <tr><td>Sạc</td><td><?php echo htmlspecialchars($product['sac']); ?></td></tr>
                    <tr><td>Kết nối</td><td><?php echo htmlspecialchars($product['ket_noi']); ?></td></tr>
                    <tr><td>SIM</td><td><?php echo htmlspecialchars($product['sim']); ?></td></tr>
                    <tr><td>Trọng lượng</td><td><?php echo htmlspecialchars($product['trong_luong']); ?></td></tr>
                    <tr><td>Chất liệu</td><td><?php echo htmlspecialchars($product['chat_lieu']); ?></td></tr>
                    <tr><td>Kháng nước, bụi</td><td><?php echo htmlspecialchars($product['khang_nuoc_bui']); ?></td></tr>
                    <tr><td>Bảo mật</td><td><?php echo htmlspecialchars($product['bao_mat']); ?></td></tr>
                </tbody>
            </table>
            
            <h2 style="margin-top: 30px;">Mô tả chi tiết sản phẩm</h2>
            <div class="long-description">
                <?php echo nl2br(htmlspecialchars($product['mo_ta_chi_tiet'])); ?>
            </div>
            
        </div>
        
    <?php else: // NẾU KHÔNG TÌM THẤY SẢN PHẨM ?>
        
        <h1>Không tìm thấy sản phẩm</h1>
        <p>Sản phẩm bạn đang tìm kiếm không tồn tại hoặc đã bị ẩn. Vui lòng quay lại trang chủ.</p>
        <a href="index.php" style="text-decoration: none; color: var(--primary-color); font-weight: bold;">&larr; Quay lại Trang chủ</a>
        
    <?php endif; ?>
</main>

<footer>
    <p>&copy; <?php echo date("Y"); ?> - PhoneStore. Đã đăng ký bản quyền.</p>
</footer>

<script>
    // Hàm thay đổi ảnh chính khi bấm thumbnail
    function changeImage(newSrc, clickedThumbnail) {
        // Thay ảnh chính
        document.getElementById('main-image').src = newSrc;
        
        // Xóa viền 'active' khỏi tất cả
        var thumbnails = document.querySelectorAll('.thumbnail-item');
        thumbnails.forEach(function(thumb) {
            thumb.classList.remove('active');
        });
        
        // Thêm viền 'active' cho thumbnail vừa bấm
        clickedThumbnail.classList.add('active');
    }

    // Hàm tăng giảm số lượng
    function adjustQuantity(amount) {
        var quantityInput = document.getElementById('quantity');
        var currentValue = parseInt(quantityInput.value);
        
        var newValue = currentValue + amount;
        
        // Đảm bảo số lượng luôn >= 1
        if (newValue < 1) {
            newValue = 1;
        }
        
        // (Bạn có thể thêm logic kiểm tra tồn kho ở đây nếu muốn)
        
        quantityInput.value = newValue;
    }
</script>

</body>
</html>

<?php
$conn->close();
?>