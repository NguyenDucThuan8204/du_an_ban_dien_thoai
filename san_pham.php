<?php
session_start();
require 'dung_chung/ket_noi_csdl.php'; // File kết nối CSDL của bạn

// --- 1. CẤU HÌNH CƠ BẢN ---
$limit = 15; // SỐ SẢN PHẨM TRÊN 1 TRANG (ĐÃ CHỈNH LÝ)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;
$hom_nay = date('Y-m-d');

// --- 2. LẤY DANH SÁCH HÃNG VÀ THÔNG SỐ KỸ THUẬT DUY NHẤT (CHO BỘ LỌC) ---
// Danh sách các cột kỹ thuật muốn lấy giá trị duy nhất
$tech_cols = [
    'ram' => 'RAM',
    'rom' => 'Bộ nhớ trong',
    'man_hinh' => 'Loại màn hình',
    'chip_xu_ly' => 'Chip xử lý',
    'dung_luong_pin' => 'Pin (mAh)',
    'camera_sau' => 'Camera sau',
];

$tech_filters_data = [];
foreach ($tech_cols as $col => $label) {
    // Lấy tất cả giá trị DUY NHẤT và không rỗng
    $sql_tech = "SELECT DISTINCT $col FROM thong_so_ky_thuat WHERE $col IS NOT NULL AND $col != '' ORDER BY $col ASC";
    $result_tech = $conn->query($sql_tech);
    $tech_filters_data[$col] = [];
    if ($result_tech) {
        while($row = $result_tech->fetch_assoc()) {
            $tech_filters_data[$col][] = $row[$col];
        }
    }
}

// Lấy danh sách Hãng sản xuất
$brands = $conn->query("SELECT * FROM hang_san_xuat WHERE trang_thai = 'hien_thi'");


// --- 3. LẤY THAM SỐ LỌC TỪ URL ---
$search = $_GET['k'] ?? '';
$brand_filter = $_GET['brand'] ?? []; 
$min_price = isset($_GET['min']) && $_GET['min'] !== '' ? (int)$_GET['min'] : 0;
$max_price = isset($_GET['max']) && $_GET['max'] !== '' ? (int)$_GET['max'] : 0;
// Lấy các tham số lọc thông số kỹ thuật
$ram_filter = $_GET['ram'] ?? []; 
$rom_filter = $_GET['rom'] ?? [];
$man_hinh_filter = $_GET['man_hinh'] ?? [];
$chip_xu_ly_filter = $_GET['chip_xu_ly'] ?? [];
$pin_filter = $_GET['pin'] ?? [];
$cam_sau_filter = $_GET['cam_sau'] ?? [];
$sort = $_GET['sort'] ?? 'new';

// --- 4. XÂY DỰNG CÂU SQL VỚI CÁC BỘ LỌC ---

// Khởi tạo câu lệnh cơ bản (chọn thêm các cột tech specs để hiển thị nhanh)
$sql_base = " FROM san_pham s 
              JOIN hang_san_xuat h ON s.id_hang = h.id_hang 
              LEFT JOIN thong_so_ky_thuat t ON s.id = t.id_san_pham 
              WHERE s.trang_thai = 'hiện' AND h.trang_thai = 'hien_thi'";

$params = [];
$types = "";

// Lọc Từ khóa
if ($search) {
    $sql_base .= " AND (s.ten_san_pham LIKE ? OR h.ten_hang LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $types .= "ss";
}
// Lọc Hãng (Dùng IN)
if (!empty($brand_filter)) {
    $placeholders = implode(',', array_fill(0, count($brand_filter), '?'));
    $sql_base .= " AND s.id_hang IN ($placeholders)";
    foreach ($brand_filter as $id) { $params[] = $id; $types .= "i"; }
}
// Lọc Giá
if ($min_price > 0) { $sql_base .= " AND s.gia_ban >= ?"; $params[] = $min_price; $types .= "i"; }
if ($max_price > 0) { $sql_base .= " AND s.gia_ban <= ?"; $params[] = $max_price; $types .= "i"; }

// Hàm chung để xử lý lọc LIKE cho các thông số kỹ thuật (VARCHAR)
function applyTechFilter(&$sql_base, &$params, &$types, $filter_array, $col_name) {
    if (!empty($filter_array)) {
        $sql_arr = [];
        foreach($filter_array as $val) { 
            $sql_arr[] = "t.{$col_name} LIKE ?"; 
            $params[] = "%$val%"; 
            $types .= "s"; 
        }
        $sql_base .= " AND (" . implode(" OR ", $sql_arr) . ")";
    }
}

// Áp dụng lọc cho các thông số kỹ thuật
applyTechFilter($sql_base, $params, $types, $ram_filter, 'ram');
applyTechFilter($sql_base, $params, $types, $rom_filter, 'rom');
applyTechFilter($sql_base, $params, $types, $man_hinh_filter, 'man_hinh');
applyTechFilter($sql_base, $params, $types, $chip_xu_ly_filter, 'chip_xu_ly');
applyTechFilter($sql_base, $params, $types, $pin_filter, 'dung_luong_pin'); 
applyTechFilter($sql_base, $params, $types, $cam_sau_filter, 'camera_sau'); 

// --- 5. SẮP XẾP ---
$sql_order = " ORDER BY s.ngay_cap_nhat DESC"; 
if ($sort === 'price_asc') $sql_order = " ORDER BY s.gia_ban ASC";
if ($sort === 'price_desc') $sql_order = " ORDER BY s.gia_ban DESC";
if ($sort === 'name_asc') $sql_order = " ORDER BY s.ten_san_pham ASC";

// --- 6. THỰC THI SQL ---

// A. Đếm tổng số sản phẩm (Dùng params và types đã xây dựng)
$stmt_count = $conn->prepare("SELECT COUNT(DISTINCT s.id) as total" . $sql_base);
if (!empty($params)) {
    $bound_params = array_slice($params, 0, strlen($types)); // Chỉ lấy params tương ứng với types
    $stmt_count->bind_param($types, ...$bound_params);
}
$stmt_count->execute();
$total_records = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_records / $limit);

// B. Lấy dữ liệu sản phẩm (Cần reset params và types cho LIMIT/OFFSET)
$select_cols = "s.*, h.ten_hang, t.ram, t.rom, t.man_hinh, t.chip_xu_ly, t.dung_luong_pin, t.camera_sau";
$sql_final = "SELECT {$select_cols} " . $sql_base . $sql_order . " LIMIT ? OFFSET ?";

// Reset params/types để thêm LIMIT/OFFSET
$final_params = array_slice($params, 0, strlen($types)); // Lấy lại params lọc ban đầu
$final_types = $types;

$final_params[] = $limit;
$final_params[] = $offset;
$final_types .= "ii";

$stmt = $conn->prepare($sql_final);
$stmt->bind_param($final_types, ...$final_params);
$stmt->execute();
$result = $stmt->get_result();

// --- 7. HÀM HỖ TRỢ ---
function getUrl($new_params = []) {
    $params = $_GET;
    // Xử lý các filter dạng mảng
    foreach ($params as $key => $value) {
        if (is_array($value) && empty($value)) {
            unset($params[$key]);
        }
    }
    foreach ($new_params as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
        } else {
            $params[$key] = $value;
        }
    }
    if(isset($params['page']) && $new_params['page'] === 1) unset($params['page']);

    $current_url_params = array_keys($_GET);
    foreach($current_url_params as $key) {
        if (!isset($params[$key]) && !isset($new_params[$key])) {
             if (!in_array($key, ['k', 'min', 'max', 'sort'])) {
                // Giữ lại các tham số lọc, chỉ reset page khi chuyển trang
             }
        }
    }

    return '?' . http_build_query($params);
}

// Thay thế dòng này bằng file header chung của bạn
require 'dung_chung/dau_trang.php'; 
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Danh sách sản phẩm</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .chk-group input:checked + div { background-color: #4F46E5; color: white; border-color: #4F46E5; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800">

<div class="container mx-auto px-4 py-8">
    
    <div class="flex flex-col md:flex-row gap-8">
        
        <aside class="w-full md:w-1/4 shrink-0">
            <form action="" method="GET" class="bg-white p-5 rounded-xl shadow-md sticky top-20">
                <h3 class="font-bold text-xl mb-4 text-indigo-600 border-b pb-2">
                    <i class="fas fa-sliders-h"></i> Bộ Lọc Nâng Cao
                </h3>
                
                <?php if($search): ?><input type="hidden" name="k" value="<?php echo htmlspecialchars($search); ?>"><?php endif; ?>

                <div class="mb-6">
                    <h4 class="font-semibold mb-2 text-sm uppercase text-gray-500">Hãng sản xuất</h4>
                    <div class="space-y-2 max-h-40 overflow-y-auto">
                        <?php while($b = $brands->fetch_assoc()): ?>
                            <label class="flex items-center gap-2 cursor-pointer hover:text-indigo-600">
                                <input type="checkbox" name="brand[]" value="<?php echo $b['id_hang']; ?>" 
                                       class="rounded text-indigo-600"
                                       <?php echo in_array($b['id_hang'], $brand_filter) ? 'checked' : ''; ?>>
                                <span><?php echo htmlspecialchars($b['ten_hang']); ?></span>
                            </label>
                        <?php endwhile; ?>
                    </div>
                </div>

                <div class="mb-6">
                    <h4 class="font-semibold mb-2 text-sm uppercase text-gray-500 border-t pt-4 mt-4">Khoảng giá (VNĐ)</h4>
                    <div class="flex gap-2">
                        <input type="number" name="min" value="<?php echo $min_price ?: ''; ?>" placeholder="Từ" class="w-1/2 p-2 border rounded text-sm">
                        <input type="number" name="max" value="<?php echo $max_price ?: ''; ?>" placeholder="Đến" class="w-1/2 p-2 border rounded text-sm">
                    </div>
                </div>

                <?php 
                // Danh sách các cột kỹ thuật muốn hiển thị và tên tham số
                $tech_filters_render = [
                    'ram' => 'RAM',
                    'rom' => 'Bộ nhớ trong',
                    'man_hinh' => 'Loại màn hình',
                    'chip_xu_ly' => 'Chip xử lý',
                    'dung_luong_pin' => 'Pin', // Dùng pin[] cho URL
                    'camera_sau' => 'Camera sau', // Dùng cam_sau[] cho URL
                ];
                
                foreach ($tech_filters_render as $col_name => $label):
                    $param_name = ($col_name == 'dung_luong_pin') ? 'pin' : (($col_name == 'camera_sau') ? 'cam_sau' : $col_name);
                    $current_filter_array = $_GET[$param_name] ?? [];
                ?>
                <?php if (!empty($tech_filters_data[$col_name])): ?>
                    <div class="mb-6">
                        <h4 class="font-semibold mb-2 text-sm uppercase text-gray-500 border-t pt-4 mt-4"><?php echo $label; ?></h4>
                        <div class="grid grid-cols-2 gap-2 max-h-40 overflow-y-auto chk-group">
                            <?php foreach ($tech_filters_data[$col_name] as $value): 
                                $display_value = htmlspecialchars($value);
                                // Cắt bớt nếu quá dài (như camera_sau)
                                if (strlen($display_value) > 25) {
                                    $display_value = substr($display_value, 0, 22) . '...';
                                }
                            ?>
                                <label class="cursor-pointer">
                                    <input type="checkbox" name="<?php echo $param_name; ?>[]" value="<?php echo htmlspecialchars($value); ?>" class="hidden" 
                                           <?php echo in_array($value, $current_filter_array) ? 'checked' : ''; ?>>
                                    <div class="text-center py-1 border rounded text-xs hover:bg-gray-50" title="<?php echo htmlspecialchars($value); ?>"><?php echo $display_value; ?></div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php endforeach; ?>

                <button type="submit" class="w-full bg-indigo-600 text-white py-2 rounded-lg hover:bg-indigo-700 transition font-bold mt-4">
                    Áp dụng lọc
                </button>
                <a href="san_pham.php" class="block text-center mt-2 text-sm text-gray-500 hover:underline">Xóa tất cả bộ lọc</a>
            </form>
        </aside>

        <div class="w-full md:w-3/4">
            
            <div class="flex flex-col md:flex-row justify-between items-center mb-6 bg-white p-4 rounded-lg shadow-sm">
                <p class="text-gray-600 mb-2 md:mb-0">
                    Tìm thấy <strong class="text-indigo-600"><?php echo $total_records; ?></strong> sản phẩm
                    <?php if(!empty($search) || !empty($brand_filter) || $min_price > 0): ?>
                        <span class="text-sm text-gray-400 ml-2">(Đã áp dụng bộ lọc)</span>
                    <?php endif; ?>
                </p>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-gray-500">Sắp xếp:</span>
                    <select onchange="location = this.value;" class="border border-gray-300 rounded p-1 text-sm focus:outline-none focus:border-indigo-500">
                        <option value="<?php echo getUrl(['sort' => 'new', 'page' => 1]); ?>" <?php echo $sort=='new'?'selected':''; ?>>Mới nhất</option>
                        <option value="<?php echo getUrl(['sort' => 'price_asc', 'page' => 1]); ?>" <?php echo $sort=='price_asc'?'selected':''; ?>>Giá tăng dần</option>
                        <option value="<?php echo getUrl(['sort' => 'price_desc', 'page' => 1]); ?>" <?php echo $sort=='price_desc'?'selected':''; ?>>Giá giảm dần</option>
                    </select>
                </div>
            </div>

            <?php if ($result->num_rows > 0): ?>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-3 gap-4"> 
                    <?php while ($sp = $result->fetch_assoc()): 
                        // Logic tính giá
                        $gia_hien_thi = $sp['gia_ban'];
                        $gia_goc = $sp['gia_goc'];
                        $giam_gia_ngay = ($sp['ngay_bat_dau_giam'] <= $hom_nay && $sp['ngay_ket_thuc_giam'] >= $hom_nay && $sp['phan_tram_giam_gia'] > 0);
                        if ($giam_gia_ngay) {
                            $gia_goc = $sp['gia_ban'];
                            $gia_hien_thi = $gia_goc * (1 - $sp['phan_tram_giam_gia'] / 100);
                        }
                    ?>
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition border border-gray-100 overflow-hidden group flex flex-col">
                        <div class="relative h-36 p-2 flex items-center justify-center bg-gray-100">
                            <?php if($gia_goc && $gia_goc > $gia_hien_thi): ?>
                                <span class="absolute top-1 left-1 bg-red-500 text-white text-[10px] font-bold px-1.5 py-0.5 rounded-md">GIẢM SỐC</span>
                            <?php endif; ?>
                            
                            <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="w-full h-full flex justify-center">
                                <img src="tai_len/san_pham/<?php echo !empty($sp['anh_dai_dien']) ? $sp['anh_dai_dien'] : 'default.png'; ?>" 
                                     alt="<?php echo htmlspecialchars($sp['ten_san_pham']); ?>" 
                                     class="object-contain max-h-full transition duration-300 group-hover:scale-110">
                            </a>
                        </div>
                        
                        <div class="p-3 flex flex-col flex-grow">
                            <div class="text-xs text-gray-400 uppercase mb-1"><?php echo htmlspecialchars($sp['ten_hang']); ?></div>
                            <h3 class="font-bold text-gray-800 mb-2 text-sm truncate">
                                <a href="chi_tiet_san_pham.php?id=<?php echo $sp['id']; ?>" class="hover:text-indigo-600">
                                    <?php echo htmlspecialchars($sp['ten_san_pham']); ?>
                                </a>
                            </h3>
                            
                            <div class="flex flex-wrap gap-1 mb-3 text-[10px] text-gray-500">
                                <?php if($sp['ram']): ?><span class="bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded">RAM: <?php echo $sp['ram']; ?></span><?php endif; ?>
                                <?php if($sp['rom']): ?><span class="bg-indigo-50 text-indigo-600 px-1.5 py-0.5 rounded">ROM: <?php echo $sp['rom']; ?></span><?php endif; ?>
                            </div>

                            <div class="mt-auto flex items-center justify-between border-t pt-3">
                                <div>
                                    <span class="block text-md font-bold text-indigo-600"><?php echo number_format($gia_hien_thi, 0, ',', '.'); ?>đ</span>
                                    <?php if($gia_goc && $gia_goc > $gia_hien_thi): ?>
                                        <span class="text-xs text-gray-400 line-through"><?php echo number_format($gia_goc, 0, ',', '.'); ?>đ</span>
                                    <?php endif; ?>
                                </div>
                                
                                <form action="xu_ly_gio_hang.php" method="POST" class="add-to-cart-form">
                                    <input type="hidden" name="action" value="add">
                                    <input type="hidden" name="id_san_pham" value="<?php echo $sp['id']; ?>">
                                    <button type="submit" class="w-8 h-8 bg-indigo-50 text-indigo-600 rounded-full hover:bg-indigo-600 hover:text-white transition flex items-center justify-center">
                                        <i class="fas fa-cart-plus text-sm"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-10 gap-2">
                    <?php if ($page > 1): ?>
                        <a href="<?php echo getUrl(['page' => $page - 1]); ?>" class="px-4 py-2 bg-white border border-gray-300 rounded hover:bg-gray-100">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="<?php echo getUrl(['page' => $i]); ?>" 
                           class="px-4 py-2 border rounded <?php echo ($i == $page) ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white border-gray-300 hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="<?php echo getUrl(['page' => $page + 1]); ?>" class="px-4 py-2 bg-white border border-gray-300 rounded hover:bg-gray-100">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

            <?php else: ?>
                <div class="bg-white p-10 rounded-lg text-center shadow-sm">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486744.png" alt="Empty" class="w-24 mx-auto mb-4 opacity-50">
                    <p class="text-xl text-gray-500">Không tìm thấy sản phẩm nào phù hợp.</p>
                    <a href="san_pham.php" class="inline-block mt-4 text-indigo-600 font-bold hover:underline">Xóa tất cả bộ lọc</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Xử lý Ajax cho Form Thêm vào giỏ hàng
    $(document).ready(function() {
        $('.add-to-cart-form').on('submit', function(e) {
            e.preventDefault(); // Ngăn chặn form gửi đi và chuyển hướng trang

            var form = $(this);
            var url = form.attr('action');
            var data = form.serialize();
            var $button = form.find('button[type="submit"]');

            // Hiển thị trạng thái đang xử lý
            $button.prop('disabled', true).html('<i class="fas fa-spinner fa-spin text-sm"></i>');

            $.ajax({
                type: 'POST',
                url: url,
                data: data,
                dataType: 'json', 
                success: function(response) {
                    if (response.success) {
                        // Hiển thị thông báo thành công
                        alert('✅ Đã thêm sản phẩm vào giỏ hàng thành công! Tổng số lượng: ' + response.new_cart_count);
                        
                        // Nếu bạn có một phần tử hiển thị tổng số lượng giỏ hàng, hãy cập nhật nó ở đây
                        // Ví dụ: $('#cart-count').text(response.new_cart_count);

                    } else {
                        alert('❌ Lỗi: ' + response.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('Lỗi kết nối máy chủ hoặc lỗi không xác định. Vui lòng thử lại.');
                    console.log(xhr.responseText);
                },
                complete: function() {
                    // Trở lại trạng thái ban đầu của nút
                    $button.prop('disabled', false).html('<i class="fas fa-cart-plus text-sm"></i>');
                }
            });
        });
    });
</script>

</body>
</html>
<?php require 'dung_chung/cuoi_trang.php'; ?>