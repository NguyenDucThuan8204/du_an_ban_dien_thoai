<?php
// 1. KHỞI TẠO BIẾN TRƯỚC VÀ LẤY THAM SỐ
$page_title = "Quản lý Bình luận"; 
$thong_bao = ""; 
$thong_bao_loi = ""; 
$id_tin_tuc = (int)($_GET['id_tin_tuc'] ?? 0);

// 2. GỌI ĐẦU TRANG ADMIN
require 'dau_trang_quan_tri.php'; 

// 3. KIỂM TRA ID TIN TỨC (Bắt buộc)
if ($id_tin_tuc == 0) {
    echo "<h1>Lỗi: Không tìm thấy bài viết.</h1>";
    echo "<a href='quan_ly_tin_tuc.php' class='btn'>Quay lại</a>";
    require 'cuoi_trang_quan_tri.php';
    exit();
}

// 4. XỬ LÝ HÀNH ĐỘNG (XÓA BÌNH LUẬN)
$action = $_GET['action'] ?? '';
$id_bl = (int)($_GET['id_bl'] ?? 0);

if ($action == 'xoa' && $id_bl > 0) {
    $stmt_del = $conn->prepare("DELETE FROM binh_luan WHERE id_binh_luan = ?");
    $stmt_del->bind_param("i", $id_bl);
    if ($stmt_del->execute()) {
        $thong_bao = "Đã xóa bình luận thành công!";
    } else {
        $thong_bao_loi = "Lỗi khi xóa: " . $stmt_del->error;
    }
    // (Không cần redirect, vì trang sẽ tải lại danh sách mới)
}

// 5. LẤY DỮ LIỆU
// (MỚI) Lấy thông tin bài viết (tiêu đề VÀ người đăng)
$stmt_post_info = $conn->prepare("
    SELECT t.tieu_de, COALESCE(nd.ten, 'N/A') as ten_nguoi_dang
    FROM tin_tuc t
    LEFT JOIN nguoi_dung nd ON t.id_nguoi_dang = nd.id_nguoi_dung
    WHERE t.id_tin_tuc = ?
");
$stmt_post_info->bind_param("i", $id_tin_tuc);
$stmt_post_info->execute();
$post_info = $stmt_post_info->get_result()->fetch_assoc();

$tieu_de_tin_tuc = $post_info['tieu_de'] ?? 'Không rõ';
$ten_nguoi_dang = $post_info['ten_nguoi_dang'] ?? 'Không rõ'; // <-- Biến mới


// Lấy danh sách bình luận
$list_comments = [];
$sql_comments = "SELECT b.*, nd.ten, nd.email, nd.anh_dai_dien 
                 FROM binh_luan b
                 JOIN nguoi_dung nd ON b.id_nguoi_dung = nd.id_nguoi_dung
                 WHERE b.id_tin_tuc = ?
                 ORDER BY b.ngay_binh_luan DESC";
$stmt_comments = $conn->prepare($sql_comments);
$stmt_comments->bind_param("i", $id_tin_tuc);
$stmt_comments->execute();
$result_comments = $stmt_comments->get_result();
if ($result_comments) {
    while ($row = $result_comments->fetch_assoc()) {
        $list_comments[] = $row;
    }
}
?>

<style>
    .page-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .page-header h2 {
        margin: 0;
        font-size: 1.5rem;
        max-width: 80%;
    }
    /* (MỚI) CSS Cho Tên Người Đăng */
    .page-header .post-author {
        font-size: 1rem;
        color: #555;
        font-style: italic;
        margin: 5px 0 0 0;
    }
    .page-header .post-author i {
        margin-right: 5px;
    }

    .avatar-cell { width: 70px; text-align: center; }
    .avatar-cell img {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid #ddd;
    }
    .action-links a.delete {
        color: #dc3545;
        font-weight: bold;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
</style>

<div class="page-header">
    <div> <h2>Bình luận cho bài viết: "<?php echo htmlspecialchars($tieu_de_tin_tuc); ?>"</h2>
        <p class="post-author">
            <i class="fas fa-user-edit"></i> Đăng bởi: <strong><?php echo htmlspecialchars($ten_nguoi_dang); ?></strong>
        </p>
    </div>
    <a href="quan_ly_tin_tuc.php" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Quay Lại
    </a>
</div>

<?php if (!empty($thong_bao)): ?>
    <div class="message success"><?php echo $thong_bao; ?></div>
<?php endif; ?>
<?php if (!empty($thong_bao_loi)): ?>
    <div class="message error"><?php echo $thong_bao_loi; ?></div>
<?php endif; ?>

<table class="admin-table">
    <thead>
        <tr>
            <th>Người bình luận</th>
            <th>Email</th>
            <th>Nội dung</th>
            <th>Thời gian</th>
            <th>Hành động</th>
        </tr>
    </thead>
    <tbody>
        <?php if (empty($list_comments)): ?>
            <tr>
                <td colspan="5" style="text-align: center;">Chưa có bình luận nào cho bài viết này.</td>
            </tr>
        <?php else: ?>
            <?php foreach($list_comments as $comment): ?>
                <tr>
                    <td class="avatar-cell">
                        <?php 
                        $anh_path = '../tai_len/avatars/' . ($comment['anh_dai_dien'] ?? 'default-avatar.png');
                        if (empty($comment['anh_dai_dien']) || !file_exists($anh_path)) {
                            $anh_path = '../tai_len/avatars/default-avatar.png'; 
                        }
                        ?>
                        <img src="<?php echo $anh_path; ?>" alt="Avatar"><br>
                        <strong><?php echo htmlspecialchars($comment['ten']); ?></strong>
                    </td>
                    <td><?php echo htmlspecialchars($comment['email']); ?></td>
                    <td style="max-width: 400px;"><?php echo nl2br(htmlspecialchars($comment['noi_dung'])); ?></td>
                    <td><?php echo date('d-m-Y H:i', strtotime($comment['ngay_binh_luan'])); ?></td>
                    <td class="action-links">
                        <a href="?id_tin_tuc=<?php echo $id_tin_tuc; ?>&action=xoa&id_bl=<?php echo $comment['id_binh_luan']; ?>" 
                           class="delete" 
                           onclick="return confirm('Bạn có chắc chắn muốn XÓA bình luận này?');">
                           <i class="fas fa-trash-alt"></i> Xóa
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<?php require 'cuoi_trang_quan_tri.php'; ?>