<?php
// 1. KHỞI TẠO BIẾN TRƯỚC
$page_title = "Quản lý Phản Hồi"; // Đặt tiêu đề
$current_tab = $_GET['tab'] ?? 'lien_he'; // Lấy tab hiện tại

// 2. GỌI ĐẦU TRANG ADMIN (Bao gồm session, CSDL, CSS, Menu)
// (Biến $conn đã được tạo trong dau_trang_quan_tri.php)
require 'dau_trang_quan_tri.php'; 
?>

<?php
// 3. XỬ LÝ HÀNH ĐỘNG (XÓA, CẬP NHẬT TRẠNG THÁI)
$action = $_GET['action'] ?? '';
$id = (int)($_GET['id'] ?? 0);
$status = $_GET['status'] ?? '';
$tab_redirect = $current_tab; 

if ($action && $id > 0) {
    if ($current_tab == 'lien_he') {
        if ($action == 'update_status' && in_array($status, ['da_doc', 'da_tra_loi'])) {
            $stmt = $conn->prepare("UPDATE lien_he SET trang_thai = ? WHERE id_lien_he = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
        } elseif ($action == 'delete') {
            $stmt = $conn->prepare("DELETE FROM lien_he WHERE id_lien_he = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    } else {
        if ($action == 'update_status' && in_array($status, ['dang_xu_ly', 'da_giai_quyet'])) {
            $stmt = $conn->prepare("UPDATE phan_anh SET trang_thai = ? WHERE id_phan_anh = ?");
            $stmt->bind_param("si", $status, $id);
            $stmt->execute();
        } elseif ($action == 'delete') {
            $stmt_get_img = $conn->prepare("SELECT anh_1, anh_2, anh_3 FROM phan_anh WHERE id_phan_anh = ?");
            $stmt_get_img->bind_param("i", $id);
            $stmt_get_img->execute();
            $images = $stmt_get_img->get_result()->fetch_assoc();
            if ($images) {
                foreach ($images as $img) {
                    if ($img && file_exists('../tai_len/phan_anh/' . $img)) {
                        @unlink('../tai_len/phan_anh/' . $img);
                    }
                }
            }
            $stmt = $conn->prepare("DELETE FROM phan_anh WHERE id_phan_anh = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
        }
    }
    
    // Chuyển hướng về đúng tab đó để làm mới
    echo "<script>window.location.href = 'quan_ly_phan_hoi.php?tab=" . $tab_redirect . "';</script>";
    exit();
}

// 4. LẤY DỮ LIỆU TỪ CSDL (TÙY THEO TAB)
$data_list = [];
if ($current_tab == 'lien_he') {
    $sql = "SELECT * FROM lien_he ORDER BY 
            CASE trang_thai WHEN 'moi' THEN 1 ELSE 2 END, ngay_gui DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data_list[] = $row;
        }
    }
} else {
    $sql = "SELECT p.*, nd.email 
            FROM phan_anh p
            JOIN nguoi_dung nd ON p.id_nguoi_dung = nd.id_nguoi_dung
            ORDER BY 
            CASE p.trang_thai WHEN 'moi' THEN 1 WHEN 'dang_xu_ly' THEN 2 ELSE 3 END, p.ngay_gui DESC";
    $result = $conn->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $data_list[] = $row;
        }
    }
}
?>

<h1>Quản lý Phản Hồi</h1>

<nav class="tab-menu">
    <a href="?tab=lien_he" class="<?php echo ($current_tab == 'lien_he') ? 'active' : ''; ?>">
        <i class="fas fa-envelope"></i> Quản lý Liên hệ
    </a>
    <a href="?tab=phan_anh" class="<?php echo ($current_tab == 'phan_anh') ? 'active' : ''; ?>">
        <i class="fas fa-flag"></i> Quản lý Phản ánh
    </a>
</nav>

<div class="content-body">
    
    <?php if ($current_tab == 'lien_he'): ?>
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Người gửi</th>
                    <th>Email / SĐT</th>
                    <th>Tiêu đề</th>
                    <th>Nội dung</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data_list)): ?>
                    <tr><td colspan="7" style="text-align: center;">Không có tin nhắn liên hệ nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($data_list as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['ten_nguoi_gui']); ?></td>
                        <td>
                            <?php echo htmlspecialchars($item['email']); ?><br>
                            <small><?php echo htmlspecialchars($item['so_dien_thoai']); ?></small>
                        </td>
                        <td><?php echo htmlspecialchars($item['tieu_de']); ?></td>
                        <td style="max-width: 300px;"><?php echo nl2br(htmlspecialchars($item['noi_dung'])); ?></td>
                        <td><?php echo date('d-m-Y H:i', strtotime($item['ngay_gui'])); ?></td>
                        <td>
                            <span class="status-label status-<?php echo $item['trang_thai']; ?>">
                                <?php echo str_replace('_', ' ', $item['trang_thai']); ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <?php if ($item['trang_thai'] == 'moi'): ?>
                                <a href="?tab=lien_he&action=update_status&status=da_doc&id=<?php echo $item['id_lien_he']; ?>">Đã đọc</a>
                            <?php endif; ?>
                            <?php if ($item['trang_thai'] != 'da_tra_loi'): ?>
                                <a href="?tab=lien_he&action=update_status&status=da_tra_loi&id=<?php echo $item['id_lien_he']; ?>">Đã trả lời</a>
                            <?php endif; ?>
                            <a href="?tab=lien_he&action=delete&id=<?php echo $item['id_lien_he']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa?');">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    
    <?php else: ?>
        <style>
             .attached-images img {
                width: 60px;
                height: 60px;
                object-fit: cover;
                border-radius: 4px;
                margin-right: 5px;
                border: 1px solid #ccc;
                cursor: pointer;
                transition: transform 0.2s;
            }
            .attached-images img:hover {
                transform: scale(1.1);
            }
            .modal {
                display: none; 
                position: fixed; 
                z-index: 1001; 
                padding-top: 50px; 
                left: 0; top: 0;
                width: 100%; height: 100%; 
                overflow: auto; 
                background-color: rgba(0,0,0,0.9);
            }
            .modal-content {
                margin: auto;
                display: block;
                width: 80%;
                max-width: 700px;
            }
            .close {
                position: absolute;
                top: 15px;
                right: 35px;
                color: #f1f1f1;
                font-size: 40px;
                font-weight: bold;
                transition: 0.3s;
                cursor: pointer;
            }
            .close:hover,
            .close:focus {
                color: #bbb;
            }
        </style>
        
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Người gửi (Email)</th>
                    <th>Đơn hàng liên quan</th>
                    <th>Chủ đề</th>
                    <th>Nội dung</th>
                    <th>Đính kèm</th>
                    <th>Ngày gửi</th>
                    <th>Trạng thái</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data_list)): ?>
                    <tr><td colspan="8" style="text-align: center;">Không có phản ánh nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($data_list as $item): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['email']); ?></td>
                        <td><?php echo htmlspecialchars($item['id_don_hang'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($item['chu_de']); ?></td>
                        <td style="max-width: 300px;"><?php echo nl2br(htmlspecialchars($item['noi_dung'])); ?></td>
                        <td class="attached-images">
                            <?php
                            $anh_path = '../tai_len/phan_anh/';
                            if ($item['anh_1'] && file_exists($anh_path . $item['anh_1'])) {
                                echo '<img src="' . $anh_path . $item['anh_1'] . '" onclick="openModal(this)">';
                            }
                            if ($item['anh_2'] && file_exists($anh_path . $item['anh_2'])) {
                                echo '<img src="' . $anh_path . $item['anh_2'] . '" onclick="openModal(this)">';
                            }
                            if ($item['anh_3'] && file_exists($anh_path . $item['anh_3'])) {
                                echo '<img src="' . $anh_path . $item['anh_3'] . '" onclick="openModal(this)">';
                            }
                            ?>
                        </td>
                        <td><?php echo date('d-m-Y H:i', strtotime($item['ngay_gui'])); ?></td>
                        <td>
                            <span class="status-label status-<?php echo str_replace('_', '-', $item['trang_thai']); ?>">
                                <?php echo str_replace('_', ' ', $item['trang_thai']); ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <?php if ($item['trang_thai'] == 'moi'): ?>
                                <a href="?tab=phan_anh&action=update_status&status=dang_xu_ly&id=<?php echo $item['id_phan_anh']; ?>">Đang xử lý</a>
                            <?php endif; ?>
                            <?php if ($item['trang_thai'] != 'da_giai_quyet'): ?>
                                <a href="?tab=phan_anh&action=update_status&status=da_giai_quyet&id=<?php echo $item['id_phan_anh']; ?>">Đã giải quyết</a>
                            <?php endif; ?>
                            <a href="?tab=phan_anh&action=delete&id=<?php echo $item['id_phan_anh']; ?>" class="delete" onclick="return confirm('Bạn có chắc muốn xóa?');">Xóa</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    <?php endif; ?>
    
</div>

<div id="imageModal" class="modal">
  <span class="close" onclick="closeModal()">&times;</span>
  <img class="modal-content" id="img01">
</div>

<script>
    var modal = document.getElementById("imageModal");
    var modalImg = document.getElementById("img01");
    
    function openModal(imgElement) {
        modal.style.display = "block";
        modalImg.src = imgElement.src;
    }
    
    function closeModal() {
        modal.style.display = "none";
    }
    modal.onclick = function(event) {
        if (event.target == modal) {
            closeModal();
        }
    }
</script>


<?php require 'cuoi_trang_quan_tri.php'; ?>