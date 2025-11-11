<?php
// 1. GỌI LOGIC TRƯỚC TIÊN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
$page_title = "Chi tiết Sản phẩm";
$product = null;
$id_san_pham = (int)($_GET['id'] ?? 0);

if ($id_san_pham == 0) {
    header("Location: index.php"); // Nếu không có ID, về trang chủ
    exit();
}

// (MỚI) Câu SQL "KHỔNG LỒ"
// Lấy tất cả thông tin từ 3 bảng: san_pham, hang_san_xuat, thong_so_ky_thuat
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
    $page_title = $product['ten_san_pham']; // Cập nhật tiêu đề
} else {
    // Không tìm thấy sản phẩm
    header("Location: index.php"); 
    exit();
}

// Logic tính giá (Giống index.php)
$hom_nay = date('Y-m-d');
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

?>

<?php
// 3. GỌI ĐẦU TRANG 
require 'dung_chung/dau_trang.php';
?>

<style>
    /* Bố cục chính */
    .product-detail-layout {
        display: grid;
        grid-template-columns: 1fr 1fr; /* Chia 2 cột 50%-50% */
        gap: 30px;
        background: #fff;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
    }
    
    /* Cột 1: Thư viện ảnh */
    .product-gallery {
        display: flex;
        flex-direction: column;
        gap: 15px;
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
    .thumbnail-images img:hover {
        border-color: var(--primary-color);
    }
    
    /* Cột 2: Thông tin sản phẩm */
    .product-info h1 {
        font-size: 2rem;
        margin-top: 0;
        margin-bottom: 10px;
        color: #333;
    }
    .product-info .brand {
        font-size: 1.1em;
        color: #555;
        margin-bottom: 15px;
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
    }
    
    /* Form Mua hàng (Thêm vào giỏ) */
    .product-actions {
        display: flex;
        gap: 15px;
    }
    .product-actions .form-group {
        flex: 0 1 120px; /* Chỉ rộng 120px */
        margin-bottom: 0;
    }
    .product-actions .btn-submit {
        flex: 1; /* Nút chiếm hết phần còn lại */
        margin-top: 0;
        font-size: 1.1rem;
        padding: 15px;
    }

    /* Phần Mô tả và Thông số (Full-width) */
    .description-section {
        background: #fff;
        padding: 30px;
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        margin-top: 30px;
    }
    .description-section h2 {
        font-size: 1.5rem;
        color: #333;
        border-bottom: 2px solid #eee;
        padding-bottom: 10px;
        margin-top: 0;
    }
    .description-content {
        line-height: 1.7;
        color: #444;
    }
    .specs-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 20px;
    }
    .specs-table tr {
        border-bottom: 1px solid #f0f0f0;
    }
    .specs-table tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    .specs-table td {
        padding: 12px 10px;
    }
    .specs-table td:first-child {
        font-weight: bold;
        color: #333;
        width: 30%; /* Tên thông số chiếm 30% */
    }

    /* Responsive */
    @media (max-width: 900px) {
        .product-detail-layout {
            grid-template-columns: 1fr; /* Xếp chồng 2 cột */
        }
    }
</style>

<main class="container">

    <div class="product-detail-layout">
        
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
                <img src="<?php echo $main_img_path; ?>" alt="Thumbnail 1" onclick="changeImage('<?php echo $main_img_path; ?>')">
                
                <?php 
                $sub_img_1 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_1'] ?? '');
                if ($product['anh_phu_1'] && file_exists($sub_img_1)): ?>
                    <img src="<?php echo $sub_img_1; ?>" alt="Thumbnail 2" onclick="changeImage('<?php echo $sub_img_1; ?>')">
                <?php endif; ?>

                <?php 
                $sub_img_2 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_2'] ?? '');
                if ($product['anh_phu_2'] && file_exists($sub_img_2)): ?>
                    <img src="<?php echo $sub_img_2; ?>" alt="Thumbnail 3" onclick="changeImage('<?php echo $sub_img_2; ?>')">
                <?php endif; ?>

                <?php 
                $sub_img_3 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_3'] ?? '');
                if ($product['anh_phu_3'] && file_exists($sub_img_3)): ?>
                    <img src="<?php echo $sub_img_3; ?>" alt="Thumbnail 4" onclick="changeImage('<?php echo $sub_img_3; ?>')">
                <?php endif; ?>

                <?php 
                $sub_img_4 = 'tai_len/san_pham/gallery/' . ($product['anh_phu_4'] ?? '');
                if ($product['anh_phu_4'] && file_exists($sub_img_4)): ?>
                    <img src="<?php echo $sub_img_4; ?>" alt="Thumbnail 5" onclick="changeImage('<?php echo $sub_img_4; ?>')">
                <?php endif; ?>
            </div>
        </div>
        
        <div class="product-info">
            <h1><?php echo htmlspecialchars($product['ten_san_pham']); ?></h1>
            <p class="brand">Thương hiệu: <strong><?php echo htmlspecialchars($product['ten_hang']); ?></strong></p>
            
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
            
        </div> </div> <div class="description-section">
        <h2>Mô tả chi tiết</h2>
        <div class="description-content">
            <?php echo nl2br(htmlspecialchars($product['mo_ta_chi_tiet'])); ?>
        </div>

        <h2 style="margin-top: 30px;">Thông số kỹ thuật</h2>
        <table class="specs-table">
            <tbody>
                <tr><td>Màn hình</td><td><?php echo htmlspecialchars($product['man_hinh']); ?></td></tr>
                <tr><td>Độ phân giải</td><td><?php echo htmlspecialchars($product['do_phan_giai']); ?></td></tr>
                <tr><td>Tần số quét</td><td><?php echo htmlspecialchars($product['tan_so_quet']); ?></td></tr>
                <tr><td>Chip xử lý (CPU)</td><td><?php echo htmlspecialchars($product['chip_xu_ly']); ?></td></tr>
                <tr><td>Chip đồ họa (GPU)</td><td><?php echo htmlspecialchars($product['gpu']); ?></td></tr>
                <tr><td>RAM</td><td><?php echo htmlspecialchars($product['ram']); ?></td></tr>
                <tr><td>ROM (Bộ nhớ trong)</td><td><?php echo htmlspecialchars($product['rom']); ?></td></tr>
                <tr><td>Hệ điều hành</td><td><?php echo htmlspecialchars($product['he_dieu_hanh']); ?></td></tr>
                <tr><td>Camera sau</td><td><?php echo htmlspecialchars($product['camera_sau']); ?></td></tr>
                <tr><td>Camera trước</td><td><?php echo htmlspecialchars($product['camera_truoc']); ?></td></tr>
                <tr><td>Dung lượng Pin</td><td><?php echo htmlspecialchars($product['dung_luong_pin']); ?></td></tr>
                <tr><td>Công nghệ sạc</td><td><?php echo htmlspecialchars($product['sac']); ?></td></tr>
                <tr><td>Kết nối</td><td><?php echo htmlspecialchars($product['ket_noi']); ?></td></tr>
                <tr><td>Thẻ SIM</td><td><?php echo htmlspecialchars($product['sim']); ?></td></tr>
                <tr><td>Trọng lượng</td><td><?php echo htmlspecialchars($product['trong_luong']); ?></td></tr>
                <tr><td>Chất liệu</td><td><?php echo htmlspecialchars($product['chat_lieu']); ?></td></tr>
                <tr><td>Kháng nước, bụi</td><td><?php echo htmlspecialchars($product['khang_nuoc_bui']); ?></td></tr>
                <tr><td>Bảo mật</td><td><?php echo htmlspecialchars($product['bao_mat']); ?></td></tr>
            </tbody>
        </table>
        
    </div> </main> <script>
    // 1. JAVASCRIPT CHO GALLERY ẢNH
    function changeImage(newSrc) {
        const mainImage = document.getElementById('main-product-image');
        if (mainImage) {
            mainImage.src = newSrc;
        }
    }

    // 2. JAVASCRIPT CHO AJAX (Y HỆT FILE INDEX.PHP)
    document.addEventListener("turbolinks:load", function() {
        
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
    });

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