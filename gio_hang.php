<?php
// Đặt tiêu đề cho trang này
$page_title = "Giỏ Hàng Của Bạn";

// 1. GỌI ĐẦU TRANG
require 'dung_chung/dau_trang.php';
?>

<?php
// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
$thong_bao = $_SESSION['thong_bao_gio_hang'] ?? null;
$thong_bao_loi = $_SESSION['thong_bao_loi_gio_hang'] ?? null;
unset($_SESSION['thong_bao_gio_hang'], $_SESSION['thong_bao_loi_gio_hang']); 

$items_in_cart = []; 
$tong_tien_da_chon = 0; 
$is_logged_in = isset($_SESSION['id_nguoi_dung']);
$id_nguoi_dung = $_SESSION['id_nguoi_dung'] ?? 0;

if ($is_logged_in) {
    // LẤY GIỎ HÀNG TỪ CSDL
    $sql = "SELECT 
                s.id, s.ten_san_pham, s.anh_dai_dien, s.gia_ban, s.mau_sac,
                g.so_luong, 
                ts.ram, ts.rom
            FROM 
                gio_hang g
            JOIN 
                san_pham s ON g.id_san_pham = s.id
            LEFT JOIN 
                thong_so_ky_thuat ts ON s.id = ts.id_san_pham
            WHERE 
                g.id_nguoi_dung = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id_nguoi_dung);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $row['selected'] = $_SESSION['cart_selection'][$row['id']] ?? true;
        $row['thanh_tien'] = $row['gia_ban'] * $row['so_luong'];
        $items_in_cart[] = $row;
        if ($row['selected']) {
            $tong_tien_da_chon += $row['thanh_tien'];
        }
    }
} else {
    // LẤY GIỎ HÀNG TỪ SESSION
    if (!empty($_SESSION['cart'])) {
        $ids = array_keys($_SESSION['cart']);
        if (empty($ids)) {
             $items_in_cart = [];
        } else {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = "SELECT 
                        s.id, s.ten_san_pham, s.anh_dai_dien, s.gia_ban, s.mau_sac,
                        ts.ram, ts.rom 
                    FROM 
                        san_pham s
                    LEFT JOIN 
                        thong_so_ky_thuat ts ON s.id = ts.id_san_pham
                    WHERE 
                        s.id IN ($placeholders)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
            $stmt->execute();
            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $id_sp = $row['id'];
                $cart_item = $_SESSION['cart'][$id_sp];
                $row['so_luong'] = $cart_item['so_luong'];
                $row['selected'] = $cart_item['selected'] ?? true; 
                $row['thanh_tien'] = $row['gia_ban'] * $row['so_luong'];
                $items_in_cart[] = $row;
                if ($row['selected']) {
                    $tong_tien_da_chon += $row['thanh_tien'];
                }
            }
        }
    }
}
?>

<style>
    .cart-container {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 25px;
        align-items: flex-start;
    }
    .cart-items-box, .cart-summary-box {
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 25px;
    }
    .cart-summary-box h3 {
        font-size: 1.5rem;
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    .cart-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 10px;
    }
    .cart-table th {
        text-align: left;
        padding-bottom: 15px;
        color: #555;
        font-size: 0.9rem;
        text-transform: uppercase;
        border-bottom: 2px solid #f0f0f0;
    }
    .cart-table td {
        padding: 20px 5px;
        border-bottom: 1px solid #f0f0f0;
        vertical-align: middle;
    }
    .cart-table tr:last-child td {
        border-bottom: none;
    }
    .cart-item-info {
        display: flex;
        align-items: center;
    }
    .cart-item-image {
        width: 80px; height: 80px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid #eee;
        margin-right: 15px;
    }
    .product-name {
        font-weight: 600;
        font-size: 1.1em;
        color: var(--dark-color);
    }
    .product-specs {
        font-size: 0.85rem;
        color: #666;
        margin-top: 5px;
    }
    .quantity-input {
        width: 50px;
        padding: 8px;
        text-align: center;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 1rem;
    }
    .btn-remove {
        color: var(--danger-color);
        background: none;
        border: none;
        cursor: pointer;
        font-size: 1.2rem;
        padding: 5px;
    }
    .btn-remove:hover { color: #a02013; }
    .cart-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        font-size: 0.9rem;
    }
    .cart-actions label { font-weight: 500; }
    .btn-clear-cart {
        color: var(--danger-color);
        background: none;
        border: none;
        cursor: pointer;
        text-decoration: underline;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 1rem;
    }
    .summary-total {
        border-top: 2px solid #eee;
        padding-top: 15px;
        margin-top: 15px;
    }
    .summary-total .summary-row {
        font-size: 1.5rem;
        font-weight: bold;
        color: var(--danger-color);
    }
    .btn-checkout {
        background-color: var(--danger-color);
        color: white;
        padding: 15px;
        text-decoration: none;
        border-radius: 5px;
        font-size: 1.1em;
        font-weight: bold;
        border: none;
        cursor: pointer;
        width: 100%;
        margin-top: 15px;
        transition: background-color 0.2s;
    }
    .btn-checkout:hover { background-color: #c0392b; }
</style>

<main class="container">
    <h1>Giỏ Hàng Của Bạn</h1>
    
    <?php if ($thong_bao): ?>
        <div class="message success"><?php echo htmlspecialchars($thong_bao); ?></div>
    <?php endif; ?>
    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <?php if (empty($items_in_cart)): ?>
        <div class="cart-items-box">
            <p>Giỏ hàng của bạn đang trống. <a href="index.php">Tiếp tục mua sắm</a>.</p>
        </div>
    <?php else: ?>
        <div class="cart-container">
            <div class="cart-items-box">
                <div class="cart-actions">
                    <label>
                        <input type="checkbox" id="check-all" checked> 
                        Chọn tất cả (<?php echo count($items_in_cart); ?> sản phẩm)
                    </label>
                    <form action="xu_ly_gio_hang.php" method="POST" style="display: inline;" data-turbolinks="false">
                        <input type="hidden" name="action" value="clear">
                        <button type="submit" class="btn-clear-cart" 
                                onclick="return confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?');">
                            Xóa tất cả
                        </button>
                    </form>
                </div>
                <table class="cart-table">
                    <tbody id="cart-body">
                        <?php foreach ($items_in_cart as $item): ?>
                            <tr class="cart-item-row" data-price="<?php echo $item['gia_ban']; ?>">
                                <td style="width: 5%;">
                                    <input type="checkbox" class="item-select" 
                                           data-id="<?php echo $item['id']; ?>" 
                                           <?php echo $item['selected'] ? 'checked' : ''; ?>>
                                </td>
                                <td style="width: 45%;">
                                    <div class="cart-item-info">
                                        <?php 
                                        $anh_path = 'tai_len/san_pham/' . ($item['anh_dai_dien'] ?? 'default.png');
                                        if (empty($item['anh_dai_dien']) || !file_exists($anh_path)) {
                                            $anh_path = 'tai_len/san_pham/default.png'; 
                                        }
                                        ?>
                                        <img src="<?php echo $anh_path; ?>" alt="" class="cart-item-image">
                                        <div>
                                            <a href="chi_tiet_san_pham.php?id=<?php echo $item['id']; ?>" style="text-decoration:none;">
                                                <div class="product-name"><?php echo htmlspecialchars($item['ten_san_pham']); ?></div>
                                            </a>
                                            <div class="product-specs">
                                                Màu: <?php echo htmlspecialchars($item['mau_sac']); ?> | 
                                                <?php echo htmlspecialchars($item['ram']); ?> / 
                                                <?php echo htmlspecialchars($item['rom']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td style="width: 15%;"><?php echo number_format($item['gia_ban'], 0, ',', '.'); ?>đ</td>
                                <td style="width: 15%;">
                                    <input type="number" name="so_luong_<?php echo $item['id']; ?>" 
                                           value="<?php echo $item['so_luong']; ?>" 
                                           min="1" class="quantity-input item-quantity">
                                </td>
                                <td style="width: 10%; text-align: right;">
                                    <form action="xu_ly_gio_hang.php" method="POST" onsubmit="return confirm('Bạn có muốn xóa sản phẩm này?');" data-turbolinks="false">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="id_san_pham" value="<?php echo $item['id']; ?>">
                                        <button type="submit" class="btn-remove">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div> 
            
            <div class="cart-summary-box">
                <h3>Tóm tắt đơn hàng</h3>
                <div class="summary-row">
                    <span>Tạm tính</span>
                    <span id="subtotal-display"><?php echo number_format($tong_tien_da_chon, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="summary-row">
                    <span>Phí vận chuyển</span>
                    <span>Miễn phí</span>
                </div>
                <div class="summary-total">
                    <div class="summary-row">
                        <span>Tổng cộng</span>
                        <span id="total-display"><?php echo number_format($tong_tien_da_chon, 0, ',', '.'); ?>đ</span>
                    </div>
                </div>
                <form action="thanh_toan.php" method="POST" id="checkout-form" data-turbolinks="false">
                    <button type="submit" class="btn-checkout">Tiến hành Thanh toán</button>
                </form>
            </div>
        </div> 
    <?php endif; ?>
</main>

<script>
document.addEventListener("turbolinks:load", function() {
    
    const cartBody = document.getElementById('cart-body');
    const subtotalDisplay = document.getElementById('subtotal-display');
    const totalDisplay = document.getElementById('total-display');
    const checkoutForm = document.getElementById('checkout-form');
    const checkAll = document.getElementById('check-all');
    
    if (cartBody && checkoutForm && checkAll) {
    
        function formatCurrency(number) {
            return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(number);
        }

        function updateCartTotals() {
            let grandTotal = 0;
            let allChecked = true;
            
            cartBody.querySelectorAll('.cart-item-row').forEach(function(row) {
                const checkbox = row.querySelector('.item-select');
                const quantityInput = row.querySelector('.item-quantity');
                const price = parseFloat(row.getAttribute('data-price'));
                const quantity = parseInt(quantityInput.value) || 0;
                const subtotal = price * quantity;
                
                if (checkbox.checked) {
                    grandTotal += subtotal;
                } else {
                    allChecked = false; 
                }
            });
            
            subtotalDisplay.textContent = formatCurrency(grandTotal);
            totalDisplay.textContent = formatCurrency(grandTotal);
            checkAll.checked = allChecked;
        }

        cartBody.addEventListener('change', function(e) {
            if (e.target.classList.contains('item-select') || e.target.classList.contains('item-quantity')) {
                updateCartTotals();
                
                if (e.target.classList.contains('item-select')) {
                    const id = e.target.getAttribute('data-id');
                    const selected = e.target.checked;
                    fetch('xu_ly_gio_hang.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `action=toggle_select&id_san_pham=${id}&selected=${selected}`
                    });
                }
                
                if (e.target.classList.contains('item-quantity')) {
                    const row = e.target.closest('.cart-item-row');
                    const id = row.querySelector('.item-select').getAttribute('data-id');
                    const quantity = e.target.value;
                    
                    if (quantity > 0) {
                         fetch('xu_ly_gio_hang.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: `action=update&id_san_pham=${id}&so_luong=${quantity}&return_url=gio_hang.php`
                        });
                    }
                }
            }
        });
        
        cartBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('item-quantity')) {
                updateCartTotals();
            }
        });

        checkAll.addEventListener('change', function() {
            const isChecked = checkAll.checked;
            cartBody.querySelectorAll('.item-select').forEach(function(checkbox) {
                checkbox.checked = isChecked;
                checkbox.dispatchEvent(new Event('change', { bubbles: true }));
            });
            updateCartTotals();
        });

        // SỬA LỖI: LOGIC GỬI DỮ LIỆU ĐẾN THANH_TOAN.PHP
        checkoutForm.addEventListener('submit', function(e) {
            // Xóa các input ẩn cũ (nếu có)
            checkoutForm.querySelectorAll('input[type=hidden]').forEach(input => input.remove());
            
            let hasSelectedItems = false;
            
            cartBody.querySelectorAll('.cart-item-row').forEach(function(row) {
                const checkbox = row.querySelector('.item-select');
                
                if (checkbox.checked) {
                    hasSelectedItems = true;
                    const id = checkbox.getAttribute('data-id');
                    const quantity = row.querySelector('.item-quantity').value;
                    
                    // Tạo input ẩn
                    const hiddenInput = document.createElement('input');
                    hiddenInput.type = 'hidden';
                    // Tên input phải khớp với logic của thanh_toan.php
                    hiddenInput.name = `selected_items[${id}]`; 
                    hiddenInput.value = quantity;
                    
                    checkoutForm.appendChild(hiddenInput);
                }
            });
            
            if (!hasSelectedItems) {
                e.preventDefault(); 
                alert('Bạn chưa chọn sản phẩm nào để thanh toán.');
            }
            // Nếu có sản phẩm, form sẽ tự động submit (vì chúng ta đã thêm data-turbolinks="false")
        });

        updateCartTotals();
    }
});
</script>

<?php
require 'dung_chung/cuoi_trang.php';
?>