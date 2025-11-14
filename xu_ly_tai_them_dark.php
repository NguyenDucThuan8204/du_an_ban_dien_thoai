<?php
// 1. GỌI LOGIC TRƯỚC TIÊN
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. KHỞI TẠO BIẾN
$limit = (int)($_GET['limit'] ?? 6); // Lấy 6 sản phẩm
$offset = (int)($_GET['offset'] ?? 0); // Lấy offset (số SP đã tải)
$hom_nay = date('Y-m-d'); 
$output_html = ''; // Chuỗi HTML trả về

// (MỚI) HÀM TÍNH GIÁ (SAO CHÉP TỪ INDEX.PHP)
function tinhGiaHienThi($sp, $hom_nay) {
    $gia_hien_thi = (float)$sp['gia_ban'];
    $gia_cu = !empty($sp['gia_goc']) ? (float)$sp['gia_goc'] : null;
    $phan_tram_hien_thi = null;
    $dang_giam_gia_theo_ngay = (
        !empty($sp['ngay_bat_dau_giam']) &&
        !empty($sp['ngay_ket_thuc_giam']) &&
        $hom_nay >= $sp['ngay_bat_dau_giam'] &&
        $hom_nay <= $sp['ngay_ket_thuc_giam']
    );
    if ($dang_giam_gia_theo_ngay && !empty($sp['phan_tram_giam_gia'])) {
        $gia_cu = $sp['gia_ban']; 
        $gia_hien_thi = $gia_cu * (1 - (float)$sp['phan_tram_giam_gia'] / 100);
        $phan_tram_hien_thi = (int)$sp['phan_tram_giam_gia'];
    } 
    else if (!empty($gia_cu) && $gia_cu > $gia_hien_thi) {
        $phan_tram_hien_thi = round((($gia_cu - $gia_hien_thi) / $gia_cu) * 100);
    }
    else {
        $gia_cu = null; 
    }
    return ['gia_hien_thi' => $gia_hien_thi, 'gia_cu' => $gia_cu, 'phan_tram' => $phan_tram_hien_thi];
}

// 3. (SỬA LỖI LOGIC) LỌC SẢN PHẨM (TRANG TIẾP THEO)
try {
    // LẤY CÁC THAM SỐ LỌC TỪ URL (Giống hệt index.php)
    $current_id_hang = (int)($_GET['id_hang'] ?? 0);
    $current_min_price = (int)($_GET['min_price'] ?? 0);
    $current_max_price = (int)($_GET['max_price'] ?? 0);
    $current_sort_by = $_GET['sort_by'] ?? 'moi_nhat';
    
    $sql_products = "SELECT s.*, h.ten_hang FROM san_pham s
                     JOIN hang_san_xuat h ON s.id_hang = h.id_hang";
    // Áp dụng filter
    $where_clauses = ["s.trang_thai = 'hiện'", "h.trang_thai = 'hien_thi'"];
    $params = [];
    $param_types = "";
    if ($current_id_hang > 0) {
        $where_clauses[] = "s.id_hang = ?";
        $params[] = $current_id_hang;
        $param_types .= "i";
    }
    if ($current_min_price > 0) {
        $where_clauses[] = "s.gia_ban >= ?";
        $params[] = $current_min_price;
        $param_types .= "i";
    }
    if ($current_max_price > 0) {
        $where_clauses[] = "s.gia_ban <= ?";
        $params[] = $current_max_price;
        $param_types .= "i";
    }
    $sql_products .= " WHERE " . implode(" AND ", $where_clauses);
    // Áp dụng sắp xếp
    switch ($current_sort_by) {
        case 'gia_thap_cao': $sql_products .= " ORDER BY s.gia_ban ASC"; break;
        case 'gia_cao_thap': $sql_products .= " ORDER BY s.gia_ban DESC"; break;
        default: $sql_products .= " ORDER BY s.ngay_cap_nhat DESC"; break;
    }
    
    // Thêm LIMIT VÀ OFFSET
    $sql_products .= " LIMIT ? OFFSET ?"; 
    $params[] = $limit;
    $params[] = $offset;
    $param_types .= "ii";
    
    $stmt_products = $conn->prepare($sql_products);
    if (!empty($params)) {
        $stmt_products->bind_param($param_types, ...$params);
    }
    $stmt_products->execute();
    $result_products = $stmt_products->get_result();

    // 4. TẠO CHUỖI HTML (PHẢI KHỚP VỚI LAYOUT DARK MODE)
    if ($result_products && $result_products->num_rows > 0) {
        while($sp = $result_products->fetch_assoc()) {
            $gia_data = tinhGiaHienThi($sp, $hom_nay);
            $gia_hien_thi_f = number_format($gia_data['gia_hien_thi'], 0, ',', '.');
            $gia_cu_f = $gia_data['gia_cu'] ? number_format($gia_data['gia_cu'], 0, ',', '.') . 'đ' : '';
            $phan_tram_f = $gia_data['phan_tram'];
            
            // (SỬA LỖI ẢNH) Kiểm tra file
            $anh_path = 'tai_len/san_pham/' . ($sp['anh_dai_dien'] ?? 'default.png');
            if (empty($sp['anh_dai_dien']) || !file_exists($anh_path)) {
                $anh_path = 'https://placehold.co/300x300/1F2937/4F46E5?text=No+Image';
            }
            
            // (SỬA LỖI VÔ HÌNH) Thêm class 'animate-on-scroll' và 'loaded-more-item'
            // (SỬA LỖI VÔ HÌNH) Bỏ inline style="opacity: 0;"
            $output_html .= '
            <div class="bg-primary-dark p-6 rounded-2xl shadow-2xl hover:shadow-accent/50 transition duration-300 transform hover:-translate-y-2 border border-gray-700 animate-on-scroll loaded-more-item"> 
                
                <div class="w-full h-48 mb-4 flex items-center justify-center bg-gray-700/50 rounded-xl overflow-hidden group">
                    <a href="chi_tiet_san_pham.php?id=' . $sp['id'] . '" class="block w-full h-full">
                        <img src="' . $anh_path . '" alt="' . htmlspecialchars($sp['ten_san_pham']) . '" 
                             class="object-contain w-full h-full p-2 transition duration-300 group-hover:scale-105">
                    </a>
                </div>
                <h3 class="text-2xl font-bold mb-2 truncate">' . htmlspecialchars($sp['ten_san_pham']) . '</h3>
                <p class="text-gray-400 mb-4 h-14 overflow-hidden">
                    ' . htmlspecialchars($sp['ten_hang']) . ' - 
                    ' . htmlspecialchars($sp['mo_ta_ngan'] ?? $sp['ten_san_pham']) . '
                </p>
                <div class="flex justify-between items-center">
                    <span class="text-2xl font-extrabold text-accent">
                        ' . $gia_hien_thi_f . 'đ
                        ' . ($gia_cu_f ? '<span class="text-gray-500 text-lg line-through ml-2">' . $gia_cu_f . '</span>' : '') . '
                    </span>
                    
                    <form action="xu_ly_gio_hang.php" method="POST" class="add-to-cart-form" data-turbolinks="false">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="id_san_pham" value="' . $sp['id'] . '">
                        <input type="hidden" name="so_luong" value="1">
                        <button type="submit" class="bg-accent text-white py-2 px-4 rounded-lg font-semibold hover:bg-accent-hover transition duration-300">
                            Mua Ngay
                        </button>
                    </form>
                </div>
            </div>';
        }
    }
    
} catch (Exception $e) {
    // Nếu lỗi, không trả về gì
}

// 5. Trả về HTML (trống hoặc có nội dung)
echo $output_html;
$conn->close();
exit();
?>