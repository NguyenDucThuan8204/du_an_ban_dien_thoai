<?php
// (MỚI) DÒNG NÀY SỬA LỖI CACHE TURBOLINKS
$page_meta_tags = '<meta name="turbolinks-cache-control" content="no-cache">';

// 1. GỌI LOGIC TRƯỚC
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

$thong_bao_loi_dat_hang = $_SESSION['thong_bao_loi_thanh_toan'] ?? "";
unset($_SESSION['thong_bao_loi_thanh_toan']); 

// 2. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['redirect_url'] = 'thanh_toan.php'; 
    header("Location: dang_nhap.php");
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// 3. (SỬA LỖI "TRANG TRẮNG") LẤY DỮ LIỆU TỪ POST
if (isset($_POST['selected_items']) && !empty($_POST['selected_items'])) {
    $_SESSION['checkout_items'] = $_POST['selected_items'];
    unset($_SESSION['checkout_discount_amount'], $_SESSION['checkout_discount_code'], $_SESSION['id_ma_giam_gia']);
} 
elseif (!isset($_SESSION['checkout_items']) || empty($_SESSION['checkout_items'])) {
    header("Location: gio_hang.php");
    exit();
}
$items_to_buy = $_SESSION['checkout_items'];

// 4. LOGIC TÍNH TOÁN
$tong_tien_hang = 0;
$phi_van_chuyen = 0; // (SỬA) ĐỔI THÀNH 0
$so_tien_giam_gia = $_SESSION['checkout_discount_amount'] ?? 0;
$ma_giam_gia = $_SESSION['checkout_discount_code'] ?? null;
$id_ma_giam_gia = $_SESSION['id_ma_giam_gia'] ?? null;
$hom_nay = date('Y-m-d');

$item_ids = array_keys($items_to_buy);
$placeholders = implode(',', array_fill(0, count($item_ids), '?'));
// (SỬA LỖI GIÁ) Thêm các cột giảm giá
$sql_check_products = "SELECT id, ten_san_pham, gia_ban, anh_dai_dien, 
                              gia_goc, phan_tram_giam_gia, ngay_bat_dau_giam, ngay_ket_thuc_giam
                       FROM san_pham WHERE id IN ($placeholders)";
$stmt_check = $conn->prepare($sql_check_products);
$stmt_check->bind_param(str_repeat('i', count($item_ids)), ...$item_ids);
$stmt_check->execute();
$products_result = $stmt_check->get_result();

$products_in_cart = [];
while ($row = $products_result->fetch_assoc()) {
    $so_luong_value = $items_to_buy[$row['id']];
    if (is_array($so_luong_value)) {
        $so_luong = (int)($so_luong_value['so_luong'] ?? 0);
    } else {
        $so_luong = (int)$so_luong_value;
    }
    
    // (SỬA LỖI GIÁ) LOGIC TÍNH GIÁ GIẢM
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
    else if (!empty($gia_cu) && $gia_cu > $gia_hien_thi) { }
    else { $gia_cu = null; }

    $row['so_luong'] = $so_luong;
    $row['gia_hien_thi'] = $gia_hien_thi; // (MỚI) Lưu giá đúng
    $row['thanh_tien'] = $gia_hien_thi * $so_luong; // Dùng giá đã giảm
    $tong_tien_hang += $row['thanh_tien'];
    $products_in_cart[] = $row;
}
if (empty($products_in_cart)) {
     header("Location: gio_hang.php"); 
     exit();
}
$tong_tien_final = $tong_tien_hang + $phi_van_chuyen - $so_tien_giam_gia;

// 5. LOGIC TẠO QR
$bank = "BIDV"; 
$account = "2601657088"; 
$account_name = "NGUYEN DUC THUAN"; 
if (!isset($_SESSION['ma_don_hang_tam'])) {
    $_SESSION['ma_don_hang_tam'] = "DH" . time() . $id_nguoi_dung;
}
$ma_don_hang_tam = $_SESSION['ma_don_hang_tam'];
$payment_info = "TT " . $ma_don_hang_tam; 
$qr_url = "https://img.vietqr.io/image/{$bank}-{$account}-compact2.png?amount={$tong_tien_final}&addInfo=" . urlencode($payment_info) . "&accountName=" . urlencode($account_name);

// 6. LOGIC LẤY THÔNG TIN USER
$stmt_user = $conn->prepare("SELECT ho, ten, email, so_dien_thoai, dia_chi_chi_tiet, phuong_xa, tinh_thanh_pho FROM nguoi_dung WHERE id_nguoi_dung = ?");
$stmt_user->bind_param("i", $id_nguoi_dung);
$stmt_user->execute();
$user_info = $stmt_user->get_result()->fetch_assoc();
$dia_chi_day_du = $user_info['dia_chi_chi_tiet'] . ', ' . $user_info['phuong_xa'] . ', ' . $user_info['tinh_thanh_pho'];
?>

<?php
// 7. GỌI ĐẦU TRANG
$page_title = "Thanh Toán Đơn Hàng";
require 'dung_chung/dau_trang.php';
?>

<style>
    /* Bố cục 2 cột */
    .checkout-layout { 
        display: grid; 
        grid-template-columns: 2fr 1fr; /* 66% bên trái, 33% bên phải */
        gap: 30px; 
        align-items: flex-start; /* Quan trọng: để cột phải có thể sticky */
    }
    
    /* Box chung */
    .checkout-box {
        background: #fff; 
        padding: 25px; 
        border-radius: 8px;
        box-shadow: var(--shadow);
    }

    /* CỘT PHẢI (TÓM TẮT ĐƠN HÀNG) */
    .order-summary {
        position: sticky;
        top: 90px; /* 70px (navbar) + 20px (khoảng cách) */
    }
    .order-summary-item { display: flex; align-items: center; margin-bottom: 15px; }
    .order-summary-item img { width: 50px; height: 50px; object-fit: contain; border: 1px solid #eee; border-radius: 4px; margin-right: 15px; }
    .item-details { flex-grow: 1; }
    .item-details span { display: block; }
    .item-details .item-name { font-weight: bold; }
    .item-details .item-qty { font-size: 0.9em; color: #555; }
    .item-price { font-weight: bold; }
    
    .total-row { display: flex; justify-content: space-between; padding: 10px 0; border-top: 1px solid #eee; font-size: 1.1em; }
    .total-row.final-total { font-size: 1.3em; font-weight: bold; color: var(--danger-color); }
    
    /* Mã giảm giá (đã có CSS chung) */
    .discount-code-form { display: flex; gap: 10px; margin-top: 15px; }
    .btn-apply-discount { background: #555; padding: 0 15px; }
    .message-sm { font-size: 0.9em; margin-top: 10px; }
    .message-sm.success { color: green; }
    .message-sm.error { color: red; }

    /* CỘT TRÁI (THÔNG TIN & THANH TOÁN) */
    .payment-method { 
        margin-top: 20px; 
    }
    .payment-option {
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: pointer;
    }
    .payment-option input[type="radio"] { margin-right: 10px; }
    .payment-option label { font-weight: bold; font-size: 1.1em; }
    
    /* Khối QR và Upload */
    #qr-payment-details {
        display: none; 
        text-align: center;
        margin-top: 15px;
        padding: 20px;
        background: #f9fafb;
        border-radius: 5px;
    }
    #qr-payment-details img {
        width: 250px;
        margin: 10px 0;
        border-radius: 10px;
        border: 1px solid #ccc;
    }
    #upload-bill-section {
        display: none; 
        margin-top: 15px;
    }
    #upload-bill-section.error { /* CSS cho lỗi upload */
        border: 2px dashed red;
        padding: 10px;
        border-radius: 5px;
        background: #fff8f8;
    }
</style>

<main class="container">
    <h1 style="text-align: center;">Xác Nhận Thanh Toán</h1>
    
    <?php if (!empty($thong_bao_loi_dat_hang)): ?>
        <div class="message error" style="text-align: center; font-size: 1.1em;">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($thong_bao_loi_dat_hang); ?>
        </div>
    <?php endif; ?>

    <form id="checkout-form" action="dat_hang.php" method="POST" enctype="multipart/form-data" data-turbolinks="false">
        
        <div class="checkout-layout">
            
            <div>
                <div class="customer-info checkout-box">
                    <h3><i class="fas fa-user-circle"></i> Thông tin Người nhận</h3>
                    <div class="form-group">
                        <label for="ten_nguoi_nhan">Họ tên người nhận (*)</label>
                        <input type="text" id="ten_nguoi_nhan" name="ten_nguoi_nhan" value="<?php echo htmlspecialchars($user_info['ho'] . ' ' . $user_info['ten']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="so_dien_thoai_nhan">Số điện thoại nhận (*)</label>
                        <input type="text" id="so_dien_thoai_nhan" name="so_dien_thoai_nhan" value="<?php echo htmlspecialchars($user_info['so_dien_thoai']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="dia_chi_giao_hang">Địa chỉ giao hàng (*)</label>
                        <input type="text" id="dia_chi_giao_hang" name="dia_chi_giao_hang" value="<?php echo htmlspecialchars($dia_chi_day_du); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="ghi_chu">Ghi chú (Tùy chọn)</label>
                        <textarea id="ghi_chu" name="ghi_chu" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="payment-method checkout-box">
                    <h3><i class="fas fa-credit-card"></i> Chọn Phương thức Thanh toán</h3>
                    
                    <div class="payment-option">
                        <input type="radio" id="payment_cod" name="phuong_thuc_thanh_toan" value="cod" checked onchange="togglePaymentDetails()">
                        <label for="payment_cod"><i class="fas fa-truck"></i> Thanh toán khi nhận hàng (COD)</label>
                    </div>
                    
                    <div class="payment-option">
                        <input type="radio" id="payment_online" name="phuong_thuc_thanh_toan" value="online" onchange="togglePaymentDetails()">
                        <label for="payment_online"><i class="fas fa-qrcode"></i> Thanh toán Online (Quét QR)</label>
                    </div>
                    
                    <div id="qr-payment-details">
                        <p>Quét mã QR dưới đây để thanh toán 
                           <strong id="qr-amount-text"><?php echo number_format($tong_tien_final, 0, ',', '.'); ?>đ</strong>
                        </p>
                        <p>Nội dung chuyển khoản: <strong><?php echo $payment_info; ?></strong></p>
                        <img id="qr-image" src="<?php echo $qr_url; ?>" alt="QR Thanh toán">
                        <p><strong>Ngân hàng:</strong> <?php echo $bank; ?><br>
                           <strong>STK:</strong> <?php echo $account; ?><br>
                           <strong>Chủ TK:</strong> <?php echo $account_name; ?></p>
                    </div>
                    
                    <div id="upload-bill-section" class="form-group">
                        <label for="anh_bill_thanh_toan">Tải lên Bill thanh toán (*)</label>
                        <input type="file" id="anh_bill_thanh_toan" name="anh_bill_thanh_toan" accept="image/*">
                        <small>Vui lòng chụp lại màn hình thanh toán thành công và tải lên đây để được xác nhận.</small>
                    </div>
                </div>
                
            </div> <div class="order-summary checkout-box">
                <h3><i class="fas fa-shopping-bag"></i> Tóm tắt Đơn hàng</h3>
                
                <?php foreach ($products_in_cart as $item): ?>
                <div class="order-summary-item">
                    <img src="tai_len/san_pham/<?php echo htmlspecialchars($item['anh_dai_dien'] ?? 'default.png'); ?>" alt="">
                    <div class="item-details">
                        <span class="item-name"><?php echo htmlspecialchars($item['ten_san_pham']); ?></span>
                        <span class="item-qty">Số lượng: <?php echo $item['so_luong']; ?></span>
                    </div>
                    <span class="item-price"><?php echo number_format($item['thanh_tien'], 0, ',', '.'); ?>đ</span>
                </div>
                <?php endforeach; ?>
                
                <hr>
                
                <div class="form-group">
                    <label>Mã giảm giá</label>
                    <div class="discount-code-form">
                        <input type="text" id="ma_giam_gia_input" value="<?php echo htmlspecialchars($ma_giam_gia ?? ''); ?>" placeholder="Nhập mã...">
                        <button type="button" class="btn btn-apply-discount" onclick="applyDiscount()">Áp dụng</button>
                    </div>
                    <div id="discount-message" class="message-sm"></div>
                </div>
                
                <div class="total-row">
                    <span>Tạm tính</span>
                    <span id="subtotal-amount"><?php echo number_format($tong_tien_hang, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="total-row">
                    <span>Phí vận chuyển</span>
                    <span><?php echo number_format($phi_van_chuyen, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="total-row">
                    <span>Giảm giá</span>
                    <span id="discount-amount" style="color: green;"><?php echo number_format($so_tien_giam_gia, 0, ',', '.'); ?>đ</span>
                </div>
                <div class="total-row final-total">
                    <span>Tổng cộng</span>
                    <span id="total-amount"><?php echo number_format($tong_tien_final, 0, ',', '.'); ?>đ</span>
                </div>
                
                <button type="submit" class="btn-submit" style="width: 100%; margin-top: 20px;">
                    <i class="fas fa-check-circle"></i> Đặt Hàng Ngay
                </button>
            </div> </div> </form>
    
</main> <script>
    const QR_BANK = '<?php echo $bank; ?>';
    const QR_ACCOUNT = '<?php echo $account; ?>';
    const QR_ACC_NAME = '<?php echo urlencode($account_name); ?>';
    const QR_INFO = '<?php echo urlencode($payment_info); ?>';

    function togglePaymentDetails() {
        var onlineRadio = document.getElementById('payment_online');
        var qrDetails = document.getElementById('qr-payment-details');
        var uploadSection = document.getElementById('upload-bill-section');
        var uploadInput = document.getElementById('anh_bill_thanh_toan');

        if (onlineRadio.checked) {
            qrDetails.style.display = 'block';
            uploadSection.style.display = 'block';
            uploadInput.required = true; // Bật required của HTML5
        } else {
            qrDetails.style.display = 'none';
            uploadSection.style.display = 'none';
            uploadInput.required = false; // Tắt required của HTML5
        }
    }

    function applyDiscount() {
        let code = document.getElementById('ma_giam_gia_input').value;
        let messageEl = document.getElementById('discount-message');
        messageEl.textContent = 'Đang kiểm tra...';
        messageEl.className = 'message-sm';

        fetch('xu_ly_giam_gia.php', { 
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=apply&ma_code=' + encodeURIComponent(code)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                messageEl.textContent = data.message;
                messageEl.classList.add('success');
                document.getElementById('subtotal-amount').textContent = data.subtotal_formatted;
                document.getElementById('discount-amount').textContent = data.discount_formatted;
                document.getElementById('total-amount').textContent = data.total_formatted;
                if (data.new_total_raw) {
                    let newAmount = data.new_total_raw;
                    let newQrUrl = `https://img.vietqr.io/image/${QR_BANK}-${QR_ACCOUNT}-compact2.png?amount=${newAmount}&addInfo=${QR_INFO}&accountName=${QR_ACC_NAME}`;
                    let qrImage = document.getElementById('qr-image');
                    if (qrImage) {
                        qrImage.src = newQrUrl;
                    }
                    let qrAmountText = document.getElementById('qr-amount-text');
                    if (qrAmountText) {
                        qrAmountText.textContent = data.total_formatted; 
                    }
                }
            } else {
                messageEl.textContent = data.message;
                messageEl.classList.add('error');
            }
        })
        .catch(error => {
            messageEl.textContent = 'Lỗi kết nối. Vui lòng thử lại.';
            messageEl.classList.add('error');
        });
    }

    // (MỚI) Thêm JavaScript Validation (Chặn gửi form nếu thiếu bill)
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById('checkout-form');
        if (form) {
            form.addEventListener('submit', function(event) {
                const isOnlinePayment = document.getElementById('payment_online').checked;
                const fileInput = document.getElementById('anh_bill_thanh_toan');
                const uploadSection = document.getElementById('upload-bill-section');
                
                if (uploadSection) uploadSection.classList.remove('error');

                if (isOnlinePayment && (!fileInput || fileInput.files.length === 0)) {
                    event.preventDefault(); 
                    alert('Vui lòng tải lên ảnh chụp Bill thanh toán trước khi Đặt Hàng.');
                    if (uploadSection) {
                        uploadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        uploadSection.classList.add('error');
                    }
                }
            });
        }
        
        // (MỚI) Nếu có lỗi từ server, tô đỏ khung upload
        <?php if (!empty($thong_bao_loi_dat_hang) && strpos($thong_bao_loi_dat_hang, 'bill') !== false): ?>
            const uploadSection = document.getElementById('upload-bill-section');
            if(uploadSection) {
                uploadSection.style.display = 'block';
                uploadSection.classList.add('error');
                document.getElementById('payment_online').checked = true;
                // Cuộn đến chỗ lỗi
                uploadSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        <?php endif; ?>
    });
</script>

<?php
require 'dung_chung/cuoi_trang.php';
?>