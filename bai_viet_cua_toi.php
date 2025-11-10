<?php
// 1. LOGIC PHP (PHẢI CHẠY TRƯỚC)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2.1. KIỂM TRA ĐĂNG NHẬP (BẮT BUỘC)
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['redirect_url'] = 'bai_viet_cua_toi.php'; 
    header("Location: dang_nhap.php");
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// 2.2. KHỞI TẠO BIẾN
$thong_bao = "";
$thong_bao_loi = "";
$upload_dir_tin_tuc = 'tai_len/tin_tuc/';

// 2.3. HÀM HỖ TRỢ XÓA ẢNH CŨ
function xoa_anh_cu($ten_anh, $upload_dir) {
    if (!empty($ten_anh)) {
        $file_path = $upload_dir . $ten_anh;
        if (file_exists($file_path) && !is_dir($file_path)) {
            @unlink($file_path); 
            return true;
        }
    }
    return false;
}

// 2.4. (MỚI) XỬ LÝ HÀNH ĐỘNG (XÓA BÀI)
$action = $_GET['action'] ?? '';
$id_tin_tuc_xoa = (int)($_GET['id'] ?? 0);

if ($action == 'xoa' && $id_tin_tuc_xoa > 0) {
    
    // Lấy tên 4 ảnh để xóa file (CHỈ XÓA BÀI CỦA MÌNH)
    $stmt_get = $conn->prepare("SELECT anh_dai_dien, anh_1, anh_2, anh_3 FROM tin_tuc WHERE id_tin_tuc = ? AND id_nguoi_dang = ?");
    $stmt_get->bind_param("ii", $id_tin_tuc_xoa, $id_nguoi_dung);
    $stmt_get->execute();
    $images = $stmt_get->get_result()->fetch_assoc();
    
    if ($images) {
        // Xóa bình luận (bảng con) trước
        $stmt_del_bl = $conn->prepare("DELETE FROM binh_luan WHERE id_tin_tuc = ?");
        $stmt_del_bl->bind_param("i", $id_tin_tuc_xoa);
        $stmt_del_bl->execute();
        
        // Xóa bài viết (bảng cha)
        $stmt_del = $conn->prepare("DELETE FROM tin_tuc WHERE id_tin_tuc = ? AND id_nguoi_dang = ?");
        $stmt_del->bind_param("ii", $id_tin_tuc_xoa, $id_nguoi_dung);
        
        if ($stmt_del->execute()) {
            $thong_bao = "Đã xóa bài viết thành công!";
            // Xóa file ảnh
            xoa_anh_cu($images['anh_dai_dien'], $upload_dir_tin_tuc);
            xoa_anh_cu($images['anh_1'], $upload_dir_tin_tuc);
            xoa_anh_cu($images['anh_2'], $upload_dir_tin_tuc);
            xoa_anh_cu($images['anh_3'], $upload_dir_tin_tuc);
        } else {
            $thong_bao_loi = "Lỗi khi xóa: " . $stmt_del->error;
        }
    } else {
        $thong_bao_loi = "Bạn không có quyền xóa bài viết này.";
    }
}

// 2.5. LẤY DANH SÁCH BÀI VIẾT CỦA NGƯỜI NÀY
$my_posts = [];
$sql = "SELECT id_tin_tuc, tieu_de, ngay_dang, trang_thai 
        FROM tin_tuc 
        WHERE id_nguoi_dang = ? 
        ORDER BY ngay_dang DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id_nguoi_dung);
$stmt->execute();
$result = $stmt->get_result();
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $my_posts[] = $row;
    }
}
?>

<?php
// 3. GỌI ĐẦU TRANG
$page_title = "Bài viết của tôi";
require 'dung_chung/dau_trang.php';
?>

<style>
    .action-links a {
        text-decoration: none;
        margin-right: 12px;
        font-weight: bold;
        font-size: 0.9em;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .action-links a i { width: 1.2em; text-align: center; }
    .action-links a.edit { color: #007bff; }
    .action-links a.delete { color: #dc3545; }
    .action-links a:hover { text-decoration: underline; }
</style>

<main class="container container-small"> 
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h1 class="section-title" style="margin-bottom: 0;"><i class="fas fa-pen-square"></i> Bài viết của tôi</h1>
        <a href="viet_bai.php" class="btn btn-success">
            <i class="fas fa-pen-alt"></i> Viết bài mới
        </a>
    </div>

    <?php if (!empty($thong_bao)): ?>
        <div class="message success"><?php echo $thong_bao; ?></div>
    <?php endif; ?>
    <?php if (!empty($thong_bao_loi)): ?>
        <div class="message error"><?php echo $thong_bao_loi; ?></div>
    <?php endif; ?>

    <table class="styled-table">
        <thead>
            <tr>
                <th>Tiêu đề</th>
                <th>Ngày gửi</th>
                <th>Trạng thái</th>
                <th>Hành động</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($my_posts)): ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Bạn chưa đăng bài viết nào.</td>
                </tr>
            <?php else: ?>
                <?php foreach($my_posts as $post): ?>
                    <tr>
                        <td>
                            <?php if ($post['trang_thai'] == 'hien_thi'): ?>
                                <a href="chi_tiet_tin_tuc.php?id=<?php echo $post['id_tin_tuc']; ?>" style="font-weight: bold; text-decoration: none;">
                                    <?php echo htmlspecialchars($post['tieu_de']); ?>
                                </a>
                            <?php else: ?>
                                <?php echo htmlspecialchars($post['tieu_de']); ?>
                            <?php endif; ?>
                        </td>
                        <td><?php echo date('d-m-Y H:i', strtotime($post['ngay_dang'])); ?></td>
                        <td>
                            <span class="status-label status-<?php echo str_replace(' ', '_', $post['trang_thai']); ?>">
                                <?php echo htmlspecialchars($post['trang_thai']); ?>
                            </span>
                        </td>
                        <td class="action-links">
                            <a href="viet_bai.php?action=sua&id=<?php echo $post['id_tin_tuc']; ?>" class="edit" title="Sửa">
                                <i class="fas fa-edit"></i> Sửa
                            </a>
                            <a href="?action=xoa&id=<?php echo $post['id_tin_tuc']; ?>" 
                               class="delete" title="Xóa"
                               onclick="return confirm('Bạn có chắc chắn muốn XÓA bài viết này?');">
                               <i class="fas fa-trash-alt"></i> Xóa
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
</main> <?php
require 'dung_chung/cuoi_trang.php';
?>