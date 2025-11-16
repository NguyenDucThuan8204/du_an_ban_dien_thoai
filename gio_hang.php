<?php
// 1. GỌI LOGIC TRƯỚC
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// (MỚI) XÓA SESSION CHECKOUT CŨ ĐỂ SỬA LỖI "THANH TOÁN CŨ"
unset($_SESSION['checkout_items']);
unset($_SESSION['checkout_discount_amount']);
unset($_SESSION['checkout_discount_code']);
unset($_SESSION['id_ma_giam_gia']);
unset($_SESSION['ma_don_hang_tam']);

$page_title = "Giỏ Hàng";

// 2. LẤY SẢN PHẨM TRONG GIỎ
$cart_items = [];
$tong_tien_hang = 0;
$phi_van_chuyen = 0; // (SỬA) ĐỔI THÀNH 0Đ
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;
$hom_nay = date('Y-m-d'); 

if ($id_nguoi_dung > 0) {
    // --- LẤY TỪ CSDL (ĐÃ ĐĂNG NHẬP) ---
    $sql_cart = "SELECT 
                    g.id_san_pham, g.so_luong, 
                    s.ten_san_pham, s.gia_ban, s.anh_dai_dien, s.so_luong_ton,
                    s.gia_goc, s.phan_tram_giam_gia, s.ngay_bat_dau_giam, s.ngay_ket_thuc_giam
                 FROM gio_hang g 
                 JOIN san_pham s ON g.id_san_pham = s.id 
                 WHERE g.id_nguoi_dung = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $id_nguoi_dung);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    
    while ($row = $result_cart->fetch_assoc()) {
        $gia_hien_thi = (float)$row['gia_ban'];
        $gia_cu = !empty($row['gia_goc']) ? (float)$row['gia_goc'] : null;
        $dang_giam_gia_theo_ngay = (
            !empty($row['ngay_bat_dau_giam']) && !empty($row['ngay_ket_thuc_giam']) &&
            $hom_nay >= $row['ngay_bat_dau_giam'] && $hom_nay <= $row['ngay_ket_thuc_giam']
        );
        if ($dang_giam_gia_theo_ngay && !empty($row['phan_tram_giam_gia'])) {
            $gia_cu = $row['gia_ban']; 
            $gia_hien_thi = $gia_cu * (1 - (float)$row['phan_tram_giam_gia'] / 100);
        } 
        else if (!empty($gia_cu) && $gia_cu > $gia_hien_thi) {
            // Giảm giá theo gia_ban vs gia_goc
        }
        else { $gia_cu = null; }
        
        $row['gia_hien_thi'] = $gia_hien_thi; 
        $row['gia_cu'] = $gia_cu; 
        $row['thanh_tien'] = $gia_hien_thi * $row['so_luong'];
        $tong_tien_hang += $row['thanh_tien'];
        $cart_items[] = $row;
    }
} else {
    // (SỬA LỖI) CHẶN KHÁCH VÃNG LAI
    // (Không lấy giỏ hàng từ SESSION nữa)
}

$tong_cong = $tong_tien_hang + $phi_van_chuyen;
?>

<?php
// 3. GỌI ĐẦU TRANG (GIAO DIỆN SÁNG)
require 'dung_chung/dau_trang.php';
// (dau_trang.php PHẢI chứa CSS cho .container, .btn, .form-group...)
?>

<style>
    .cart-page-title { 
        text-align: center; 
        font-size: 2.25rem; 
        font-weight: 700; 
        color: var(--dark-color);
        margin-bottom: 2rem; 
    }
    
    /* (SỬA) Layout 2 cột (Giao diện SÁNG) */
    .cart-layout { 
        display: grid; 
        grid-template-columns: 1fr; /* Mặc định mobile */
        gap: 30px; 
        align-items: flex-start; 
    }
    @media (min-width: 1024px) { /* lg: */
        .cart-layout { 
            grid-template-columns: 2fr 1fr; /* Sản phẩm (trái) - Tóm tắt (phải) */
        }
    }
    
    /* Cột 1: Danh sách sản phẩm */
    .cart-items-list {
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        padding: 25px;
        box-shadow: var(--shadow);
    }
    .cart-items-list h2 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 15px 0;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .cart-item-row {
        display: flex;
        flex-wrap: wrap; /* Cho phép xuống dòng trên mobile */
        align-items: center;
        gap: 15px;
        padding: 15px 0;
        border-bottom: 1px solid #eee;
    }
    .cart-item-row:last-child {
        border-bottom: none;
    }
    
    .cart-item-image img {
        width: 80px;
        height: 80px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid #eee;
    }
    
    .cart-item-info {
        flex-grow: 1; /* Đẩy các mục khác ra xa */
        min-width: 200px;
    }
    .cart-item-info a {
        font-weight: 600;
        font-size: 1.1rem;
        color: var(--dark-color);
        text-decoration: none;
    }
    .cart-item-info a:hover {
        color: var(--primary-color);
    }
    .item-price {
        color: var(--danger-color); /* Màu đỏ */
        font-weight: 600;
        font-size: 1.1rem;
    }
    .item-old-price {
        font-size: 0.9em;
        color: #999;
        text-decoration: line-through;
        margin-left: 8px;
    }
    
    /* Input số lượng (dùng class từ dau_trang.php) */
    .cart-item-qty input {
        text-align: center;
        width: 60px;
        padding: 5px 8px; /* Thu nhỏ padding */
    }
    
    .cart-item-subtotal {
        font-weight: 700;
        width: 120px;
        text-align: right;
        font-size: 1.1rem;
        color: var(--dark-color);
    }
    
    .cart-item-remove button {
        background: none;
        border: none;
        color: #999;
        font-size: 1.2rem;
        cursor: pointer;
        padding: 5px;
    }
    .cart-item-remove button:hover {
        color: var(--danger-color);
    }

    /* Cột 2: Tóm tắt đơn hàng */
    .cart-summary-box {
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        padding: 25px;
        box-shadow: var(--shadow);
        position: sticky;
        top: 90px; /* 70px (navbar) + 20px (khoảng cách) */
    }
    .cart-summary-box h3 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0 0 15px 0;
        padding-bottom: 15px;
        border-bottom: 1px solid #eee;
    }
    .total-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 0;
        font-size: 1.1rem;
        color: #555;
    }
    .total-row span:last-child {
        font-weight: 600;
        color: var(--dark-color);
    }
    .final-total {
        border-top: 2px solid #ddd;
        font-size: 1.3rem;
        font-weight: 700;
        color: var(--dark-color);
    }
    .final-total span:last-child {
        font-size: 1.5rem;
        color: var(--danger-color);
    }
    
    .cart-actions {
        margin-top: 20px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    .btn-checkout {
        background-color: var(--danger-color); /* Đổi màu nút thanh toán */
        font-size: 1.1rem;
        padding: 15px;
    }
    .btn-checkout:hover {
        background-color: #c0392b;
    }
    .btn-continue-shopping {
        background-color: var(--white-color);
        color: var(--primary-color);
        border: 1px solid var(--primary-color);
    }
    .btn-continue-shopping:hover {
        background: #f0f7ff;
    }

    /* Hiệu ứng loading */
    .cart-item-row.is-loading {
        opacity: 0.6;
        pointer-events: none;
        transition: opacity 0.3s ease;
    }
    
    /* (MỚI) Box thông báo chưa đăng nhập */
    .login-required-box {
        text-align: center; 
        padding: 50px; 
        background: #fff; 
        border-radius: 8px;
        max-width: 600px;
        margin: auto;
        box-shadow: var(--shadow);
    }
    .login-required-box i {
        font-size: 4rem; 
        color: #ccc; 
        margin-bottom: 1rem;
    }
    .login-required-box h2 {
        font-size: 1.5rem; 
        color: #333; 
        margin-bottom: 1rem;
    }
    .login-required-box p {
        color: #777; 
        margin-bottom: 1.5rem;
    }
</style>

<main class="container">
    
    <h1 class="cart-page-title">Giỏ Hàng Của Bạn</h1>
    
    <?php if ($id_nguoi_dung == 0): // (SỬA) NẾU CHƯA ĐĂNG NHẬP ?>
        <div class="login-required-box">
            <i class="fas fa-user-lock"></i>
            <h2>Bạn cần đăng nhập</h2>
            <p>Vui lòng đăng nhập để xem giỏ hàng và tiến hành thanh toán.</p>
            <a href="dang_nhap.php?return_url=gio_hang.php" class="btn" data-turbolinks="false">
                <i class="fas fa-sign-in-alt"></i> Đăng nhập ngay
            </a>
        </div>
        
    <?php elseif (empty($cart_items)): // (SỬA) NẾU ĐÃ ĐĂNG NHẬP NHƯNG GIỎ TRỐNG ?>
        <div class="login-required-box"> <i class="fas fa-shopping-cart"></i>
            <h2>Giỏ hàng của bạn đang trống</h2>
            <p>Hãy quay lại trang chủ để lựa chọn sản phẩm nhé.</p>
            <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Tiếp tục mua sắm</a>
        </div>
        
    <?php else: // ĐÃ ĐĂNG NHẬP VÀ CÓ HÀNG ?>
        
        <form id="cart-form" action="thanh_toan.php" method="POST" data-turbolinks="false">
        
            <div class="cart-layout">
                
                <div class="cart-items-list">
                    <h2>Các Sản Phẩm Trong Giỏ</h2>
                    
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item-row" id="cart-row-<?php echo $item['id_san_pham']; ?>">
                        
                        <div class="cart-item-image">
                            <?php 
                            $anh_path_cart = 'tai_len/san_pham/' . ($item['anh_dai_dien'] ?? 'default.png');
                            if (empty($item['anh_dai_dien']) || !file_exists($anh_path_cart)) {
                                $anh_path_cart = 'tai_len/san_pham/default.png'; 
                            }
                            ?>
                            <img src="<?php echo $anh_path_cart; ?>" 
                                 alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>">
                        </div>
                        
                        <div class="cart-item-info">
                            <a href="chi_tiet_san_pham.php?id=<?php echo $item['id_san_pham']; ?>">
                                <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                            </a>
                            <div class="item-price">
                                <?php echo number_format($item['gia_hien_thi'], 0, ',', '.'); ?>đ
                                <?php if ($item['gia_cu']): ?>
                                    <span class="item-old-price"><?php echo number_format($item['gia_cu'], 0, ',', '.'); ?>đ</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="cart-item-qty">
                            <div class="form-group" style="margin-bottom: 0;">
                                <input type="number" 
                                       value="<?php echo $item['so_luong']; ?>" 
                                       min="1" 
                                       max="<?php echo $item['so_luong_ton']; ?>"
                                       onchange="updateCart(<?php echo $item['id_san_pham']; ?>, this.value)"
                                       aria-label="Số lượng">
                            </div>
                        </div>
                        
                        <div class="cart-item-subtotal" id="subtotal-<?php echo $item['id_san_pham']; ?>">
                            <?php echo number_format($item['thanh_tien'], 0, ',', '.'); ?>đ
                        </div>
                        
                        <div class="cart-item-remove">
                            <button type="button" 
                                    onclick="removeCart(<?php echo $item['id_san_pham']; ?>)" 
                                    title="Xóa sản phẩm">
                                <i class="fas fa-trash-alt"></i>
                            </button>
                        </div>
                        
                        <input type="hidden" 
                               class="cart-item-hidden-input"
                               name="selected_items[<?php echo $item['id_san_pham']; ?>]" 
                               value="<?php echo $item['so_luong']; ?>">
                                
                    </div> <?php endforeach; ?>
                    
                </div> <div class="cart-summary-box">
                    <h3>Tóm tắt đơn hàng</h3>
                    
                    <div class="total-row">
                        <span>Tạm tính</span>
                        <span id="summary-subtotal"><?php echo number_format($tong_tien_hang, 0, ',', '.'); ?>đ</span>
                    </div>
                    <div class="total-row">
                        <span>Phí vận chuyển</span>
                        <span id="summary-shipping"><?php echo number_format($phi_van_chuyen, 0, ',', '.'); ?>đ</span>
                    </div>
                    
                    <div class="total-row final-total">
                        <span>Tổng cộng</span>
                        <span id="summary-total"><?php echo number_format($tong_cong, 0, ',', '.'); ?>đ</span>
                    </div>
                    
                    <div class="cart-actions">
                        <button type="submit" class="btn btn-checkout btn-submit">
                            <i class="fas fa-credit-card"></i> Tiến hành Thanh toán
                        </button>
                        <a href="index.php" class="btn btn-continue-shopping" style="justify-content: center;">
                            <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div> </div> </form> <?php endif; ?>
    
</main> <script>
    // Biến để theo dõi trạng thái tải
    let isUpdatingCart = false;

    // (SỬA LỖI) THÊM 2 HÀM BỊ THIẾU
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
        if (!toastContainer) return;
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
    // (HẾT PHẦN THÊM MỚI)

    // Hàm chung xử lý mọi thao tác giỏ hàng
    function handleCartUpdate(action, productId, newQty = 1) {
        if (isUpdatingCart) return; // Ngăn chặn request chồng chéo

        isUpdatingCart = true;

        // Thêm lớp mờ cho sản phẩm đang được xử lý
        const row = document.getElementById(`cart-row-${productId}`);
        if (row) {
            row.classList.add('is-loading');
        }

        fetch('xu_ly_gio_hang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&id_san_pham=${productId}&so_luong=${newQty}`
        })
        .then(response => response.json())
        .then(data => {
            isUpdatingCart = false; // Dừng loading
            if (row) {
                row.classList.remove('is-loading');
            }
            
            if (data.success) {
                // Cập nhật header (Hàm này đã có)
                updateCartBadge(data.new_cart_count); 
                
                // Cập nhật tóm tắt
                document.getElementById('summary-subtotal').textContent = data.subtotal_formatted;
                document.getElementById('summary-total').textContent = data.total_formatted;

                if (action === 'remove') {
                    // Xóa hàng
                    if (row) row.remove(); // (SỬA LỖI) Dòng này BÂY GIỜ sẽ chạy được
                    
                    if (data.new_cart_count === 0) {
                        // Tải lại trang nếu giỏ hàng trống (để hiển thị layout Giỏ hàng trống)
                        window.location.reload();
                    }
                } else if (action === 'update') {
                    // Cập nhật thành tiền
                    document.getElementById(`subtotal-${productId}`).textContent = data.item_total_formatted;
                    
                    // Cập nhật value của input ẩn để gửi đi thanh toán
                    const hiddenInput = document.querySelector(`.cart-item-hidden-input[name="selected_items[${productId}]"]`);
                    if (hiddenInput) {
                        hiddenInput.value = newQty;
                    }
                }
            } else {
                // (SỬA LỖI) Nếu thất bại (ví dụ: chưa đăng nhập), chỉ hiển thị thông báo
                showToast(data.message || 'Có lỗi xảy ra, vui lòng tải lại trang.', 'error');
                // Không tải lại trang, để người dùng thấy thông báo
            }
        })
        .catch(error => {
            isUpdatingCart = false; 
            if (row) {
                row.classList.remove('is-loading');
            }
            console.error('Lỗi Fetch:', error);
            showToast('Lỗi kết nối. Đang tải lại trang...', 'error');
            setTimeout(() => window.location.reload(), 2000);
        });
    }

    function updateCart(productId, newQty) {
        // Đảm bảo số lượng hợp lệ trước khi gửi
        const inputElement = document.querySelector(`#cart-row-${productId} input[type="number"]`);
        const maxQty = parseInt(inputElement.max);
        newQty = parseInt(newQty);

        if (isNaN(newQty) || newQty < 1) newQty = 1;
        if (newQty > maxQty) newQty = maxQty;
        
        // Cập nhật lại giá trị hiển thị nếu bị giới hạn
        inputElement.value = newQty;

        handleCartUpdate('update', productId, newQty);
    }

    function removeCart(productId) {
        // (SỬA) Thêm confirm() để người dùng xác nhận
        if (confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
            handleCartUpdate('remove', productId);
        }
    }
</script>

<?php
// 7. GỌI CUỐI TRANG (GIAO DIỆN SÁNG)
require 'dung_chung/cuoi_trang.php';
?>