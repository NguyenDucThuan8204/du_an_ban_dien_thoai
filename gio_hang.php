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
$phi_van_chuyen = 30000; // Mặc định
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;

if ($id_nguoi_dung > 0) {
    // --- LẤY TỪ CSDL (ĐÃ ĐĂNG NHẬP) ---
    $sql_cart = "SELECT 
                    g.id_san_pham, g.so_luong, 
                    s.ten_san_pham, s.gia_ban, s.anh_dai_dien, s.so_luong_ton
                 FROM gio_hang g 
                 JOIN san_pham s ON g.id_san_pham = s.id 
                 WHERE g.id_nguoi_dung = ?";
    $stmt_cart = $conn->prepare($sql_cart);
    $stmt_cart->bind_param("i", $id_nguoi_dung);
    $stmt_cart->execute();
    $result_cart = $stmt_cart->get_result();
    
    while ($row = $result_cart->fetch_assoc()) {
        $row['thanh_tien'] = $row['gia_ban'] * $row['so_luong'];
        $tong_tien_hang += $row['thanh_tien'];
        $cart_items[] = $row;
    }
} else {
    // --- LẤY TỪ SESSION (KHÁCH) ---
    if (isset($_SESSION['cart']) && !empty($_SESSION['cart'])) {
        $item_ids = array_keys($_SESSION['cart']);
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        
        $sql_check_products = "SELECT id, ten_san_pham, gia_ban, anh_dai_dien, so_luong_ton FROM san_pham WHERE id IN ($placeholders)";
        $stmt_check = $conn->prepare($sql_check_products);
        $types = str_repeat('i', count($item_ids));
        $stmt_check->bind_param($types, ...$item_ids);
        $stmt_check->execute();
        $products_result = $stmt_check->get_result();
        
        while ($row = $products_result->fetch_assoc()) {
            $id_sp = $row['id'];
            $so_luong = (int)($_SESSION['cart'][$id_sp]['so_luong'] ?? 0);
            $row['so_luong'] = $so_luong;
            $row['thanh_tien'] = $row['gia_ban'] * $so_luong;
            $tong_tien_hang += $row['thanh_tien'];
            $cart_items[] = $row;
        }
    }
}

$tong_cong = $tong_tien_hang + $phi_van_chuyen;
?>

<?php
// 3. GỌI ĐẦU TRANG (SAU KHI LOGIC ĐÃ XONG)
require 'dung_chung/dau_trang.php';
?>

<style>
    /* (CSS của bạn giữ nguyên) */
    .cart-page-title { text-align: center; font-size: 2rem; color: #333; margin-bottom: 20px; }
    .cart-layout { display: grid; grid-template-columns: 2.5fr 1.5fr; gap: 30px; align-items: flex-start; }
    .cart-items-list { background: #fff; border-radius: 8px; padding: 15px; box-shadow: var(--shadow); }
    .cart-item-row { display: flex; align-items: center; gap: 15px; padding: 15px 0; border-bottom: 1px solid #f0f0f0; }
    .cart-item-row:last-child { border-bottom: none; }
    .cart-item-image img { width: 80px; height: 80px; object-fit: contain; border: 1px solid #eee; border-radius: 5px; }
    .cart-item-info { flex-grow: 1; }
    .cart-item-info a { font-weight: bold; color: #333; text-decoration: none; font-size: 1.1em; }
    .cart-item-info a:hover { color: var(--primary-color); }
    .cart-item-info .item-price { color: var(--danger-color); font-weight: 500; margin-top: 5px; }
    .cart-item-qty { width: 120px; }
    .cart-item-qty input { width: 60px; text-align: center; padding: 8px; font-size: 1em; border: 1px solid #ccc; border-radius: 4px; }
    .cart-item-subtotal { font-weight: bold; width: 120px; text-align: right; }
    .cart-item-remove button { background: none; border: none; color: #999; font-size: 1.2rem; cursor: pointer; }
    .cart-item-remove button:hover { color: var(--danger-color); }
    .cart-summary { background: #fff; padding: 25px; border-radius: 8px; box-shadow: var(--shadow); position: sticky; top: 90px; }
    .cart-summary h3 { margin-top: 0; font-size: 1.5rem; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .total-row { display: flex; justify-content: space-between; padding: 12px 0; font-size: 1.1em; }
    .total-row.final-total { font-size: 1.4em; font-weight: bold; color: var(--danger-color); border-top: 2px solid #333; margin-top: 10px; }
    .cart-actions { margin-top: 20px; display: flex; flex-direction: column; align-items: stretch; gap: 15px; }
    .btn-checkout { text-align: center; font-size: 1.2rem; padding: 15px; }
    .btn-continue-shopping { align-self: flex-end; background: none; border: 1px solid var(--primary-color); color: var(--primary-color); padding: 10px 15px; }
    .btn-continue-shopping:hover { background: #f0f7ff; }
    @media (max-width: 900px) { .cart-layout { grid-template-columns: 1fr; } }
</style>

<main class="container">
    
    <h1 class="cart-page-title">Giỏ Hàng Của Bạn</h1>
    
    <?php if (empty($cart_items)): ?>
        <div style="text-align: center; padding: 50px; background: #fff; border-radius: 8px;">
            <i class="fas fa-shopping-cart" style="font-size: 4rem; color: #ccc;"></i>
            <h2 style="margin: 15px 0;">Giỏ hàng của bạn đang trống</h2>
            <p>Hãy quay lại trang chủ để lựa chọn sản phẩm nhé.</p>
            <a href="index.php" class="btn"><i class="fas fa-arrow-left"></i> Tiếp tục mua sắm</a>
        </div>
        
    <?php else: ?>
        
        <form id="cart-form" action="thanh_toan.php" method="POST" data-turbolinks="false">
        
            <div class="cart-layout">
                
                <div class="cart-items-list">
                    
                    <?php foreach ($cart_items as $item): ?>
                    <div class="cart-item-row" id="cart-row-<?php echo $item['id_san_pham']; ?>">
                        
                        <div class="cart-item-image">
                            <?php 
                            $anh_path_cart = 'tai_len/san_pham/' . ($item['anh_dai_dien'] ?? 'default.png');
                            if (empty($item['anh_dai_dien']) || !file_exists($anh_path_cart)) {
                                $anh_path_cart = 'tai_len/san_pham/default.png'; 
                            }
                            ?>
                            <img src="<?php echo $anh_path_cart; ?>" alt="<?php echo htmlspecialchars($item['ten_san_pham']); ?>">
                        </div>
                        
                        <div class="cart-item-info">
                            <a href="chi_tiet_san_pham.php?id=<?php echo $item['id_san_pham']; ?>">
                                <?php echo htmlspecialchars($item['ten_san_pham']); ?>
                            </a>
                            <div class="item-price"><?php echo number_format($item['gia_ban'], 0, ',', '.'); ?>đ</div>
                        </div>
                        
                        <div class="cart-item-qty">
                            <input type="number" 
                                   value="<?php echo $item['so_luong']; ?>" 
                                   min="1" 
                                   max="<?php echo $item['so_luong_ton']; ?>"
                                   onchange="updateCart(<?php echo $item['id_san_pham']; ?>, this.value)"
                                   aria-label="Số lượng">
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
                    
                </div> <div class="cart-summary">
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
                        <button type="submit" class="btn btn-checkout">
                            <i class="fas fa-credit-card"></i> Tiến hành Thanh toán
                        </button>
                        <a href="index.php" class="btn btn-continue-shopping">
                            <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                    </div>
                </div> </div> </form> <?php endif; ?>
    
</main> <script>
    function formatCurrency(number) {
        return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
    }
    
    function handleCartUpdate(action, productId, newQty = 1) {
        
        fetch('xu_ly_gio_hang.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `action=${action}&id_san_pham=${productId}&so_luong=${newQty}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Cập nhật header
                const cartBadge = document.querySelector('.cart-badge');
                if (cartBadge) {
                    if (data.new_cart_count > 0) {
                        cartBadge.textContent = data.new_cart_count;
                        cartBadge.style.display = 'flex';
                    } else {
                        cartBadge.style.display = 'none';
                    }
                }
                
                // Cập nhật tóm tắt
                document.getElementById('summary-subtotal').textContent = data.subtotal_formatted;
                document.getElementById('summary-total').textContent = data.total_formatted;

                if (action === 'remove') {
                    // Xóa hàng
                    const row = document.getElementById(`cart-row-${productId}`);
                    if (row) row.remove();
                    
                    if (data.new_cart_count === 0) {
                        Turbolinks.visit(window.location.href); 
                    }
                } else if (action === 'update') {
                    // Cập nhật thành tiền
                    document.getElementById(`subtotal-${productId}`).textContent = data.item_total_formatted;
                    
                    // (MỚI) Cập nhật value của input ẩn để gửi đi
                    const hiddenInput = document.querySelector(`.cart-item-hidden-input[name="selected_items[${productId}]"]`);
                    if (hiddenInput) {
                        hiddenInput.value = newQty;
                    }
                }
            } else {
                alert(data.message || 'Có lỗi xảy ra, vui lòng tải lại trang.');
                Turbolinks.visit(window.location.href);
            }
        })
        .catch(error => {
            console.error('Lỗi Fetch:', error);
            alert('Lỗi kết nối. Vui lòng tải lại trang.');
            Turbolinks.visit(window.location.href);
        });
    }

    function updateCart(productId, newQty) {
        handleCartUpdate('update', productId, newQty);
    }

    function removeCart(productId) {
        if (confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
            handleCartUpdate('remove', productId);
        }
    }
</script>

<?php
require 'dung_chung/cuoi_trang.php';
?>