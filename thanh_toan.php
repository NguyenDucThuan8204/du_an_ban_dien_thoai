<?php
// 1. GỌI SESSION VÀ CSDL TRƯỚC TIÊN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. PHẦN LOGIC PHP CỦA RIÊNG TRANG NÀY
// (Toàn bộ logic đã được chuyển lên đây)

// 2.1. BẮT BUỘC ĐĂNG NHẬP
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['redirect_url'] = 'thanh_toan.php';
    header("Location: dang_nhap.php"); 
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// 2.2. KHỞI TẠO BIẾN
$thong_bao_loi = "";
$thong_bao_thanh_cong = "";
$items_to_buy = []; // Mảng chứa thông tin SP sẽ mua
$tong_tien_hang = 0;
$user_info = null;

// === SỬA LỖI LOGIC: NHẬN DỮ LIỆU TỪ GIỎ HÀNG ===
// Khi giỏ hàng POST qua, nó sẽ gửi 'selected_items'
if (isset($_POST['selected_items']) && is_array($_POST['selected_items'])) {
    // Lấy danh sách sản phẩm từ POST và lưu vào SESSION
    $_SESSION['checkout_items'] = $_POST['selected_items'];
    
    // Xóa mã giảm giá cũ (nếu có) vì giỏ hàng đã thay đổi
    unset($_SESSION['checkout_discount_code'], $_SESSION['checkout_discount_amount'], $_SESSION['id_ma_giam_gia']);
}
// =============================================

// 2.3. XỬ LÝ POST (KHI NGƯỜI DÙNG BẤM NÚT "ĐẶT HÀNG")
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action']) && $_POST['action'] == 'dat_hang') {
    
    $ten_nguoi_nhan = $conn->real_escape_string($_POST['ten_nguoi_nhan']);
    $so_dien_thoai_nhan = $conn->real_escape_string($_POST['so_dien_thoai_nhan']);
    $tinh_thanh_pho = $conn->real_escape_string($_POST['tinh_thanh_pho']);
    $phuong_xa = $conn->real_escape_string($_POST['phuong_xa']);
    $dia_chi_chi_tiet = $conn->real_escape_string($_POST['dia_chi_chi_tiet']);
    $ghi_chu = $conn->real_escape_string($_POST['ghi_chu']);
    $dia_chi_giao_hang = "$dia_chi_chi_tiet, $phuong_xa, $tinh_thanh_pho";

    $items_to_buy_session = $_SESSION['checkout_items'] ?? [];
    if (empty($items_to_buy_session)) {
        header("Location: gio_hang.php"); exit(); 
    }
    
    // --- BẮT ĐẦU GIAO DỊCH (TRANSACTION) ---
    $conn->begin_transaction();
    try {
        
        // 2.4. KIỂM TRA LẠI TỒN KHO VÀ GIÁ
        $item_ids = array_keys($items_to_buy_session);
        $placeholders = implode(',', array_fill(0, count($item_ids), '?'));
        $sql_check_products = "SELECT id, ten_san_pham, gia_ban, mau_sac, so_luong_ton FROM san_pham WHERE id IN ($placeholders)";
        $stmt_check = $conn->prepare($sql_check_products);
        $stmt_check->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
        $stmt_check->execute();
        $products_result = $stmt_check->get_result();
        
        $products_in_db = [];
        while ($row = $products_result->fetch_assoc()) {
            $products_in_db[$row['id']] = $row;
        }

        $tong_tien_hang_final = 0;
        $items_for_detail = []; 

        foreach ($items_to_buy_session as $id_sp => $so_luong_mua) {
            if (!isset($products_in_db[$id_sp])) {
                throw new Exception("Sản phẩm có ID $id_sp không còn tồn tại.");
            }
            $product = $products_in_db[$id_sp];
            
            if ($product['so_luong_ton'] < $so_luong_mua) {
                throw new Exception("Sản phẩm '" . $product['ten_san_pham'] . "' không đủ số lượng tồn kho (chỉ còn " . $product['so_luong_ton'] . ").");
            }
            
            $gia_hien_tai = $product['gia_ban'];
            $tong_tien_hang_final += ($gia_hien_tai * $so_luong_mua);
            
            $items_for_detail[] = [
                'id' => $id_sp,
                'ten' => $product['ten_san_pham'],
                'mau' => $product['mau_sac'],
                'sl' => $so_luong_mua,
                'gia' => $gia_hien_tai
            ];
        }
        
        // 2.5. XỬ LÝ MÃ GIẢM GIÁ
        $so_tien_giam_gia = $_SESSION['checkout_discount_amount'] ?? 0;
        $ma_giam_gia_da_ap = $_SESSION['checkout_discount_code'] ?? null;
        $tong_tien_final = $tong_tien_hang_final - $so_tien_giam_gia;

        // 2.6. TẠO MÃ ĐƠN HÀNG
        $ma_don_hang = "DH-" . strtoupper(uniqid());

        // 2.7. TẠO ĐƠN HÀNG (INSERT vào `don_hang`)
        $sql_insert_order = "INSERT INTO don_hang 
            (id_nguoi_dung, ma_don_hang, ten_nguoi_nhan, so_dien_thoai_nhan, email_nguoi_nhan, dia_chi_giao_hang, ghi_chu, tong_tien, phuong_thuc_thanh_toan, ma_giam_gia_da_ap, so_tien_giam_gia, trang_thai_don_hang)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'cod', ?, ?, 'moi')";
        $stmt_order = $conn->prepare($sql_insert_order);
        $email_nguoi_nhan = $_SESSION['email']; 
        $stmt_order->bind_param("issssssdss",
            $id_nguoi_dung, $ma_don_hang, $ten_nguoi_nhan, $so_dien_thoai_nhan, $email_nguoi_nhan,
            $dia_chi_giao_hang, $ghi_chu, $tong_tien_final, $ma_giam_gia_da_ap, $so_tien_giam_gia
        );
        $stmt_order->execute();
        $id_don_hang_moi = $conn->insert_id; 

        // 2.8. INSERT CHI TIẾT ĐƠN HÀNG VÀ TRỪ KHO
        $sql_insert_detail = "INSERT INTO chi_tiet_don_hang (id_don_hang, id_san_pham, ten_san_pham_luc_mua, mau_sac_luc_mua, so_luong, gia_luc_mua) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_detail = $conn->prepare($sql_insert_detail);
        
        $sql_update_stock = "UPDATE san_pham SET so_luong_ton = so_luong_ton - ? WHERE id = ?";
        $stmt_stock = $conn->prepare($sql_update_stock);

        foreach ($items_for_detail as $item) {
            $stmt_detail->bind_param("iisssd", 
                $id_don_hang_moi, $item['id'], $item['ten'], $item['mau'], $item['sl'], $item['gia']
            );
            $stmt_detail->execute();
            
            $stmt_stock->bind_param("ii", $item['sl'], $item['id']);
            $stmt_stock->execute();
        }

        // 2.9. XÓA SẢN PHẨM ĐÃ MUA KHỎI GIỎ HÀNG
        $sql_delete_cart = "DELETE FROM gio_hang WHERE id_nguoi_dung = ? AND id_san_pham IN ($placeholders)";
        $stmt_delete = $conn->prepare($sql_delete_cart);
        $stmt_delete->bind_param("i" . str_repeat('i', count($item_ids)), $id_nguoi_dung, ...$item_ids);
        $stmt_delete->execute();
        
        // 2.10. HOÀN TẤT GIAO DỊCH
        $conn->commit();
        
        // 2.11. DỌN DẸP SESSION VÀ CHUYỂN HƯỚNG
        unset($_SESSION['checkout_items'], $_SESSION['checkout_discount_amount'], $_SESSION['checkout_discount_code']);
        $_SESSION['dat_hang_thanh_cong'] = $ma_don_hang;
        header("Location: dat_hang_thanh_cong.php"); 
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        $thong_bao_loi = $e->getMessage();
    }
}

// 2.12. XỬ LÝ GET (HIỂN THỊ TRANG)
// Lấy thông tin giỏ hàng từ SESSION (đã được lưu ở đầu file)
$items_to_buy_session = $_SESSION['checkout_items'] ?? [];
if (empty($items_to_buy_session) && empty($thong_bao_loi)) { 
    header("Location: gio_hang.php"); 
    exit();
}
$item_ids = array_keys($items_to_buy_session);

// Lấy thông tin đầy đủ của sản phẩm
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));
$sql_get_items = "SELECT s.id, s.ten_san_pham, s.anh_dai_dien, s.gia_ban, s.so_luong_ton 
                  FROM san_pham s
                  WHERE s.id IN ($placeholders)";
$stmt_get = $conn->prepare($sql_get_items);
$stmt_get->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
$stmt_get->execute();
$result_items = $stmt_get->get_result();

$tong_tien_hang = 0;
while ($row = $result_items->fetch_assoc()) {
    $so_luong_mua = $items_to_buy_session[$row['id']];
    
    if ($row['so_luong_ton'] < $so_luong_mua) {
        $_SESSION['thong_bao_loi_gio_hang'] = "Sản phẩm '" . $row['ten_san_pham'] . "' không đủ số lượng. Vui lòng kiểm tra lại giỏ hàng.";
        header("Location: gio_hang.php"); 
        exit();
    }
    
    $row['so_luong_mua'] = $so_luong_mua;
    $row['thanh_tien'] = $row['gia_ban'] * $so_luong_mua;
    $items_to_buy[] = $row;
    $tong_tien_hang += $row['thanh_tien'];
}

// Lấy thông tin người dùng để điền form
$sql_get_user = "SELECT * FROM nguoi_dung WHERE id_nguoi_dung = ?";
$stmt_get_user = $conn->prepare($sql_get_user);
$stmt_get_user->bind_param("i", $id_nguoi_dung);
$stmt_get_user->execute();
$user_info = $stmt_get_user->get_result()->fetch_assoc();

// Xử lý mã giảm giá (nếu có)
$so_tien_giam_gia = $_SESSION['checkout_discount_amount'] ?? 0;
$ma_giam_gia_da_ap = $_SESSION['checkout_discount_code'] ?? null;
$tong_tien_final = $tong_tien_hang - $so_tien_giam_gia;

// --- TẤT CẢ LOGIC ĐÃ XONG, BÂY GIỜ MỚI GỌI HTML ---

?>

<?php
// Đặt tiêu đề cho trang này
$page_title = "Thanh toán";

// 3. GỌI ĐẦU TRANG
require 'dung_chung/dau_trang.php';
?>

<style>
    .checkout-container {
        display: grid;
        grid-template-columns: 3fr 2fr; 
        gap: 30px;
        align-items: flex-start;
    }
    .checkout-form, .order-summary {
        background-color: var(--white-color);
        border-radius: var(--border-radius);
        box-shadow: var(--shadow);
        padding: 30px;
    }
    .order-summary h3, .checkout-form h3 {
        font-size: 1.5rem;
        margin-top: 0;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
        margin-bottom: 20px;
    }
    .checkout-form h3 i, .order-summary h3 i {
        margin-right: 10px;
        color: var(--primary-color);
    }
    .form-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
    }
    .form-group.full-width {
        grid-column: 1 / -1;
    }
    .form-group label i {
        margin-right: 8px;
        width: 1.2em;
        text-align: center;
        color: #888;
    }
    .summary-item-list {
        max-height: 300px;
        overflow-y: auto;
        padding-right: 10px;
    }
    .summary-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding-bottom: 15px;
        border-bottom: 1px solid #f0f0f0;
    }
    .summary-item:last-child { border-bottom: none; }
    .summary-item img {
        width: 60px; height: 60px;
        object-fit: contain;
        border-radius: 8px;
        border: 1px solid #eee;
        margin-right: 15px;
    }
    .summary-item-info {
        flex-grow: 1;
    }
    .summary-item-info .name {
        font-weight: 600;
        display: block;
    }
    .summary-item-info .qty {
        font-size: 0.9rem;
        color: #555;
    }
    .summary-item-price {
        font-weight: 600;
        font-size: 0.95rem;
    }
    .coupon-form {
        display: flex;
        margin-top: 20px;
        gap: 10px;
    }
    .coupon-form input {
        flex-grow: 1;
    }
    .btn-apply-coupon {
        background-color: var(--primary-color);
        color: white; border: none; border-radius: 5px;
        padding: 0 15px; font-weight: bold; cursor: pointer;
    }
    .summary-totals {
        border-top: 2px solid #f0f0f0;
        margin-top: 20px;
        padding-top: 15px;
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 10px;
        font-size: 1rem;
    }
    .summary-row.discount {
        color: var(--danger-color);
    }
    .summary-total {
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
        margin-top: 20px;
        transition: background-color 0.2s;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
    }
    .btn-checkout:hover { background-color: #c0392b; }
</style>

<main class="container">
    
    <h1><i class="fas fa-money-check-alt"></i> Thanh toán</h1>

    <?php if ($thong_bao_loi): ?>
        <div class="message error"><?php echo htmlspecialchars($thong_bao_loi); ?></div>
    <?php endif; ?>

    <div class="checkout-container">
        
        <div class="checkout-form">
            <h3><i class="fas fa-shipping-fast"></i> Thông tin giao hàng</h3>
            
            <form action="thanh_toan.php" method="POST" id="checkout-form" data-turbolinks="false">
                <input type="hidden" name="action" value="dat_hang">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="ten_nguoi_nhan"><i class="fas fa-user"></i> Tên người nhận (*)</label>
                        <input type="text" id="ten_nguoi_nhan" name="ten_nguoi_nhan" 
                               value="<?php echo htmlspecialchars($user_info['ten'] ? ($user_info['ho'] . ' ' . $user_info['ten']) : ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="so_dien_thoai_nhan"><i class="fas fa-phone"></i> Số điện thoại (*)</label>
                        <input type="text" id="so_dien_thoai_nhan" name="so_dien_thoai_nhan" 
                               value="<?php echo htmlspecialchars($user_info['so_dien_thoai'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-grid">
                    <div class="form-group">
                        <label for="tinh_thanh_pho"><i class="fas fa-map-marked-alt"></i> Tỉnh/Thành phố (*)</label>
                        <input type="text" id="tinh_thanh_pho" name="tinh_thanh_pho" 
                               value="<?php echo htmlspecialchars($user_info['tinh_thanh_pho'] ?? ''); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="phuong_xa"><i class="fas fa-map-marker-alt"></i> Phường/Xã (*)</label>
                        <input type="text" id="phuong_xa" name="phuong_xa" 
                               value="<?php echo htmlspecialchars($user_info['phuong_xa'] ?? ''); ?>" required>
                    </div>
                </div>

                <div class="form-group full-width">
                    <label for="dia_chi_chi_tiet"><i class="fas fa-home"></i> Địa chỉ chi tiết (*)</label>
                    <input type="text" id="dia_chi_chi_tiet" name="dia_chi_chi_tiet" 
                           placeholder="Số nhà, tên đường..." 
                           value="<?php echo htmlspecialchars($user_info['dia_chi_chi_tiet'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group full-width">
                    <label for="ghi_chu"><i class="fas fa-pencil-alt"></i> Ghi chú (Tùy chọn)</label>
                    <textarea id="ghi_chu" name="ghi_chu" rows="3" placeholder="Ghi chú cho người giao hàng..."></textarea>
                </div>
                
            </form>
        </div>
        
        <div class="order-summary">
            <h3><i class="fas fa-receipt"></i> Đơn hàng của bạn</h3>
            
            <div class="summary-item-list">
                <?php foreach ($items_to_buy as $item): ?>
                <div class="summary-item">
                    <?php 
                    $anh_path = 'tai_len/san_pham/' . ($item['anh_dai_dien'] ?? 'default.png');
                    if (empty($item['anh_dai_dien']) || !file_exists($anh_path)) {
                        $anh_path = 'tai_len/san_pham/default.png'; 
                    }
                    ?>
                    <img src="<?php echo $anh_path; ?>" alt="">
                    <div class="summary-item-info">
                        <span class="name"><?php echo htmlspecialchars($item['ten_san_pham']); ?></span>
                        <span class="qty">Số lượng: <?php echo $item['so_luong_mua']; ?></span>
                    </div>
                    <span class="summary-item-price"><?php echo number_format($item['thanh_tien'], 0, ',', '.'); ?>đ</span>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="coupon-form form-group">
                <input type="text" id="ma_giam_gia" name="ma_giam_gia" placeholder="Nhập mã giảm giá" 
                       value="<?php echo htmlspecialchars($ma_giam_gia_da_ap ?? ''); ?>" 
                       <?php echo $ma_giam_gia_da_ap ? 'disabled' : ''; ?>>
                <button type="button" id="btn-apply-coupon" class="btn-apply-coupon" <?php echo $ma_giam_gia_da_ap ? 'disabled' : ''; ?>>
                    <?php echo $ma_giam_gia_da_ap ? 'Đã Áp dụng' : 'Áp dụng'; ?>
                </button>
            </div>
            <div id="coupon-message"></div> 

            <div class="summary-totals">
                <div class="summary-row">
                    <span>Tạm tính</span>
                    <span id="subtotal-display"><?php echo number_format($tong_tien_hang, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="summary-row discount" id="discount-row" style="<?php echo $so_tien_giam_gia > 0 ? '' : 'display: none;'; ?>">
                    <span>Giảm giá</span>
                    <span id="discount-display">- <?php echo number_format($so_tien_giam_gia, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="summary-row summary-total">
                    <span>Tổng cộng</span>
                    <span id="total-display"><?php echo number_format($tong_tien_final, 0, ',', '.'); ?>đ</span>
                </div>
            </div>
            
            <button type="submit" class="btn-checkout" form="checkout-form">
                <i class="fas fa-check-circle"></i> ĐẶT HÀNG
            </button>
        </div>

    </div>
</main>

<script>
document.addEventListener("turbolinks:load", function() {
    const btnApplyCoupon = document.getElementById('btn-apply-coupon');
    const couponInput = document.getElementById('ma_giam_gia');
    const couponMessage = document.getElementById('coupon-message');
    
    if (btnApplyCoupon) {
        btnApplyCoupon.addEventListener('click', async function() {
            const code = couponInput.value;
            if (!code) {
                couponMessage.innerHTML = '<div class="message error">Vui lòng nhập mã.</div>';
                return;
            }
            
            try {
                const response = await fetch('xu_ly_giam_gia.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=apply&ma_code=${code}`
                });
                
                const data = await response.json();
                
                if (data.success) {
                    couponMessage.innerHTML = `<div class="message success">${data.message}</div>`;
                    // Cập nhật giá
                    document.getElementById('subtotal-display').textContent = data.subtotal_formatted;
                    document.getElementById('discount-display').textContent = data.discount_formatted;
                    document.getElementById('total-display').textContent = data.total_formatted;
                    document.getElementById('discount-row').style.display = 'flex';
                    // Khóa nút
                    btnApplyCoupon.disabled = true;
                    couponInput.disabled = true;
                    btnApplyCoupon.textContent = 'Đã Áp dụng';
                } else {
                    couponMessage.innerHTML = `<div class="message error">${data.message}</div>`;
                }
            } catch (error) {
                couponMessage.innerHTML = '<div class="message error">Lỗi kết nối. Vui lòng thử lại.</div>';
            }
        });
    }
});
</script>

<?php
require 'dung_chung/cuoi_trang.php';
?>