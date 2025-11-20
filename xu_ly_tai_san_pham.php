<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Giả định file này tồn tại
require 'dung_chung/ket_noi_csdl.php'; 

// 1. HÀM TÍNH GIÁ (Phải định nghĩa lại ở đây vì đây là file độc lập)
$hom_nay = date('Y-m-d'); 
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

// 2. LẤY THAM SỐ TỪ REQUEST
$limit = (int)($_GET['limit'] ?? 12);
$offset = (int)($_GET['offset'] ?? 0);
$keyword = trim($_GET['keyword'] ?? '');
$id_hang = (int)($_GET['id_hang'] ?? 0);
$min_price = (int)($_GET['min_price'] ?? 0);
$max_price = (int)($_GET['max_price'] ?? 0);
$sort_by = $_GET['sort_by'] ?? 'moi_nhat';

// 3. XÂY DỰNG CÂU TRUY VẤN
$sql = "SELECT s.*, h.ten_hang FROM san_pham s
        JOIN hang_san_xuat h ON s.id_hang = h.id_hang";
$where_clauses = ["s.trang_thai = 'hiện'"]; // Chỉ lấy sản phẩm đang "hiện"

$params = [];
$param_types = "";

// 3.1. Lọc theo Từ khóa
if (!empty($keyword)) {
    $where_clauses[] = "(s.ten_san_pham LIKE ? OR s.mo_ta_ngan LIKE ?)";
    $params[] = "%{$keyword}%";
    $params[] = "%{$keyword}%";
    $param_types .= "ss";
}

// 3.2. Lọc theo Hãng Sản Xuất
if ($id_hang > 0) {
    $where_clauses[] = "s.id_hang = ?";
    $params[] = $id_hang;
    $param_types .= "i";
}

// 3.3. Lọc theo Khoảng Giá
if ($min_price > 0 || $max_price > 0) {
    if ($max_price > 0) {
        // Lọc trong khoảng [min_price, max_price]
        $where_clauses[] = "s.gia_ban BETWEEN ? AND ?";
        $params[] = $min_price;
        $params[] = $max_price;
        $param_types .= "ii";
    } else {
        // Lọc trên min_price (ví dụ: Trên 20 triệu)
        $where_clauses[] = "s.gia_ban >= ?";
        $params[] = $min_price;
        $param_types .= "i";
    }
}

// Gộp các điều kiện WHERE
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// 3.4. Sắp xếp
$order_by = "s.ngay_cap_nhat DESC"; // Mặc định: Mới nhất
switch ($sort_by) {
    case 'gia_thap':
        $order_by = "s.gia_ban ASC";
        break;
    case 'gia_cao':
        $order_by = "s.gia_ban DESC";
        break;
    case 'ban_chay':
        // Cần giả định cột 'luot_ban' hoặc 'so_luong_da_ban'
        $order_by = "s.so_luong_da_ban DESC, s.ngay_cap_nhat DESC"; 
        break;
    case 'moi_nhat':
    default:
        $order_by = "s.ngay_cap_nhat DESC";
        break;
}
$sql .= " ORDER BY " . $order_by;

// 3.5. Phân trang LIMIT/OFFSET
$sql .= " LIMIT ? OFFSET ?";
$params[] = $limit;
$params[] = $offset;
$param_types .= "ii";

// 4. CHẠY TRUY VẤN
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    // Sử dụng call_user_func_array để bind_param
    $stmt->bind_param($param_types, ...$params);
}

if (!$stmt->execute()) {
    // Trả về lỗi nếu truy vấn thất bại
    echo '<p class="text-red-500 col-span-full text-center">Lỗi truy vấn: ' . htmlspecialchars($stmt->error) . '</p>';
    exit;
}

$result = $stmt->get_result();
$output_html = '';
$count = 0;

// 5. TẠO HTML CHO SẢN PHẨM
while($sp = $result->fetch_assoc()):
    $gia_data = tinhGiaHienThi($sp, $hom_nay);
    $count++;
    
    // Tạo HTML cho mỗi sản phẩm
    $output_html .= '
    <div class="bg-white p-6 rounded-2xl shadow-lg border border-gray-200 product-card">
        <div class="w-full h-48 mb-4 flex items-center justify-center bg-gray-100 rounded-xl overflow-hidden group">
            <a href="chi_tiet_san_pham.php?id=' . htmlspecialchars($sp['id']) . '" class="block w-full h-full">
                <img src="' . htmlspecialchars($sp['anh_dai_dien']) . '" 
                     alt="' . htmlspecialchars($sp['ten_san_pham']) . '" 
                     class="object-contain w-full h-full p-2 transition duration-300 group-hover:scale-105">
            </a>
        </div>
        <h3 class="text-xl font-bold mb-1 truncate text-gray-900">' . htmlspecialchars($sp['ten_san_pham']) . '</h3>
        <p class="text-gray-500 mb-3 text-sm">' . htmlspecialchars($sp['ten_hang']) . '</p>
        
        <div class="flex items-center space-x-2 mb-4">
            <span class="text-2xl font-extrabold text-accent">
                ' . number_format($gia_data['gia_hien_thi'], 0, ',', '.') . 'đ
            </span>';
            
    if ($gia_data['gia_cu']):
        $output_html .= '
            <span class="text-gray-500 text-sm line-through ml-2">
                ' . number_format($gia_data['gia_cu'], 0, ',', '.') . 'đ
            </span>';
    endif;
    
    if ($gia_data['phan_tram']):
        $output_html .= '
            <span class="bg-red-500 text-white text-xs font-semibold px-2 py-0.5 rounded-full">
                - ' . $gia_data['phan_tram'] . '%
            </span>';
    endif;
    
    $output_html .= '
        </div>
        
        <form action="xu_ly_gio_hang.php" method="POST" class="add-to-cart-form" data-turbolinks="false">
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="id_san_pham" value="' . htmlspecialchars($sp['id']) . '">
            <input type="hidden" name="so_luong" value="1">
            <button type="submit" class="w-full bg-accent text-white py-2.5 rounded-lg font-semibold hover:bg-accent-hover transition duration-300">
                Thêm vào Giỏ
            </button>
        </form>
    </div>
    ';
endwhile;

// 6. TRẢ VỀ KẾT QUẢ
if ($count > 0) {
    // Nếu số lượng sản phẩm trả về nhỏ hơn limit, tức là đã hết sản phẩm để tải thêm
    if ($count < $limit) {
        $output_html .= '<div style="display:none;" class="no-more-products"></div>';
    }
    echo $output_html;
} else {
    // Trả về thông báo hết sản phẩm (cho lần load tiếp theo) hoặc không có kết quả (cho lần load đầu)
    if ($offset == 0) {
        // Lần load đầu tiên không có kết quả
        echo ''; // Để JS hiển thị thông báo "Không tìm thấy"
    } else {
        // Lần load tiếp theo không có kết quả
        echo '<div style="display:none;" class="no-more-products"></div>';
    }
}
?>