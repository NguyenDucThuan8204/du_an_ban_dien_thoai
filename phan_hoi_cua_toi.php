<?php
// 1. GỌI LOGIC TRƯỚC
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require 'dung_chung/ket_noi_csdl.php';

// 2. KIỂM TRA ĐĂNG NHẬP
if (!isset($_SESSION['id_nguoi_dung'])) {
    $_SESSION['redirect_url'] = 'phan_hoi_cua_toi.php'; 
    header("Location: dang_nhap.php");
    exit();
}
$id_nguoi_dung = $_SESSION['id_nguoi_dung'];

// (SỬA LỖI) LẤY EMAIL TỪ CSDL DỰA TRÊN ID (KHÔNG LẤY TỪ SESSION)
$email_nguoi_dung = '';
$stmt_get_email = $conn->prepare("SELECT email FROM nguoi_dung WHERE id_nguoi_dung = ?");
$stmt_get_email->bind_param("i", $id_nguoi_dung);
$stmt_get_email->execute();
$result_email = $stmt_get_email->get_result();
if ($result_email->num_rows > 0) {
    $email_nguoi_dung = $result_email->fetch_assoc()['email'];
}
// ==========================================================

$page_title = "Phản Hồi Của Tôi";

// 3. LẤY DỮ LIỆU
$list_lien_he = [];
$list_phan_anh = [];

// Lấy lịch sử Liên hệ (theo email)
if (!empty($email_nguoi_dung)) { // Chỉ chạy nếu có email
    $stmt_lh = $conn->prepare("SELECT * FROM lien_he WHERE email = ? ORDER BY ngay_gui DESC");
    $stmt_lh->bind_param("s", $email_nguoi_dung);
    $stmt_lh->execute();
    $result_lh = $stmt_lh->get_result();
    while($row = $result_lh->fetch_assoc()) $list_lien_he[] = $row;
}

// Lấy lịch sử Phản ánh (theo id)
$stmt_pa = $conn->prepare("SELECT * FROM phan_anh WHERE id_nguoi_dung = ? ORDER BY ngay_gui DESC");
$stmt_pa->bind_param("i", $id_nguoi_dung);
$stmt_pa->execute();
$result_pa = $stmt_pa->get_result();
while($row = $result_pa->fetch_assoc()) $list_phan_anh[] = $row;
?>

<?php
// 4. GỌI ĐẦU TRANG
require 'dung_chung/dau_trang.php';
// (File dau_trang.php của bạn PHẢI là file đầy đủ CSS tôi đã gửi)
?>

<style>
    /* (MỚI) CSS Cho Bố Cục Lưới 2 Cột */
    .feedback-grid-layout {
        display: grid;
        grid-template-columns: 1fr 1fr; /* 2 cột bằng nhau */
        gap: 30px;
        align-items: flex-start; /* Căn các cột lên trên */
    }
    
    /* CSS cho mỗi hộp (Liên hệ / Phản ánh) */
    .feedback-section {
        background: #fff;
        border-radius: 8px;
        box-shadow: var(--shadow);
        padding: 25px;
        margin-bottom: 30px; /* Giữ margin bottom cho responsive */
    }
    .feedback-section h2 {
        margin-top: 0;
        font-size: 1.5rem;
        color: var(--dark-color);
        border-bottom: 1px solid #eee;
        padding-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    
    /* CSS cho thẻ <details> (để ẩn/hiện) */
    .feedback-item {
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        margin-bottom: 15px;
        overflow: hidden; /* Giữ bo góc */
    }
    .feedback-item summary {
        padding: 15px;
        cursor: pointer;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9f9f9;
        font-weight: bold;
    }
    .feedback-item summary:hover {
        background: #f1f1f1;
    }
    .feedback-item-title {
        color: #333;
        /* (MỚI) Giúp tiêu đề dài xuống dòng */
        word-break: break-word;
        margin-right: 10px;
    }
    .feedback-item-meta {
        font-size: 0.9em;
        font-weight: normal;
        color: #555;
        flex-shrink: 0; /* Không co lại */
        padding-left: 10px; /* Khoảng cách */
        text-align: right;
    }
    .feedback-item-meta .status-label {
        margin-left: 10px;
        font-size: 0.8em; /* Nhỏ hơn 1 chút */
        margin-top: 5px; /* Xuống hàng trên mobile */
        display: inline-block;
    }
    
    /* Nội dung chi tiết */
    .feedback-content {
        padding: 20px;
        border-top: 1px solid #e0e0e0;
    }
    .content-box {
        margin-bottom: 15px;
    }
    .content-box h4 {
        margin: 0 0 5px 0;
        color: var(--primary-color);
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .content-box p {
        margin: 0;
        padding-left: 20px;
        border-left: 3px solid #eee;
        white-space: pre-wrap; /* Giữ nguyên xuống dòng */
        color: #555;
    }
    
    /* CSS cho phần trả lời của Admin */
    .admin-response {
        background: #f0f7ff; /* Nền xanh nhạt */
        border: 1px solid #b3d7ff;
        border-radius: 5px;
        padding: 15px;
        margin-top: 20px;
    }
    .admin-response h4 {
        color: #0056b3; /* Xanh đậm */
    }
    .admin-response p {
        border-left-color: #007bff;
    }

    /* (MỚI) CSS Responsive */
    @media (max-width: 900px) {
        .feedback-grid-layout {
            grid-template-columns: 1fr; /* Xếp chồng 2 cột */
        }
    }
</style>

<main class="container">
    <h1 class="section-title">Phản Hồi Của Tôi</h1>

    <div class="feedback-grid-layout">
        
        <div class="feedback-section">
            <h2><i class="fas fa-envelope"></i> Lịch sử Liên hệ</h2>
            <?php if (empty($list_lien_he)): ?>
                <p>Bạn chưa gửi liên hệ nào (tính theo email: <?php echo htmlspecialchars($email_nguoi_dung); ?>).</p>
                <a href="lien_he.php" class="btn">Gửi liên hệ mới</a>
            <?php else: ?>
                <?php foreach ($list_lien_he as $item): ?>
                    <details class="feedback-item">
                        <summary>
                            <span class="feedback-item-title"><?php echo htmlspecialchars($item['tieu_de']); ?></span>
                            <span class="feedback-item-meta">
                                <?php echo date('d/m/Y', strtotime($item['ngay_gui'])); ?>
                                <span class="status-label status-<?php echo $item['trang_thai']; ?>">
                                    <?php echo str_replace('_', ' ', $item['trang_thai']); ?>
                                </span>
                            </span>
                        </summary>
                        <div class="feedback-content">
                            <div class="content-box">
                                <h4><i class="fas fa-user"></i> Nội dung bạn gửi:</h4>
                                <p><?php echo nl2br(htmlspecialchars($item['noi_dung'])); ?></p>
                            </div>
                            
                            <?php if (!empty($item['noi_dung_xu_ly'])): ?>
                                <div class="content-box admin-response">
                                    <h4><i class="fas fa-reply"></i> Phản hồi từ Admin: <?php echo htmlspecialchars($item['tieu_de_xu_ly']); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars($item['noi_dung_xu_ly'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div> <div class="feedback-section">
            <h2><i class="fas fa-flag"></i> Lịch sử Phản ánh</h2>
            <?php if (empty($list_phan_anh)): ?>
                <p>Bạn chưa gửi phản ánh nào.</p>
                <a href="phan_anh.php" class="btn">Gửi phản ánh mới</a>
            <?php else: ?>
                <?php foreach ($list_phan_anh as $item): ?>
                    <details class="feedback-item">
                        <summary>
                            <span class="feedback-item-title"><?php echo htmlspecialchars($item['chu_de']); ?></span>
                            <span class="feedback-item-meta">
                                <?php echo date('d/m/Y', strtotime($item['ngay_gui'])); ?>
                                <span class="status-label status-<?php echo str_replace('_', '-', $item['trang_thai']); ?>">
                                    <?php echo str_replace('_', ' ', $item['trang_thai']); ?>
                                </span>
                            </span>
                        </summary>
                        <div class="feedback-content">
                            <div class="content-box">
                                <h4><i class="fas fa-user"></i> Nội dung bạn gửi:</h4>
                                <p><?php echo nl2br(htmlspecialchars($item['noi_dung'])); ?></p>
                                </div>
                            
                            <?php if (!empty($item['noi_dung_xu_ly'])): ?>
                                <div class="content-box admin-response">
                                    <h4><i class="fas fa-reply"></i> Nội dung xử lý: <?php echo htmlspecialchars($item['tieu_de_xu_ly']); ?></h4>
                                    <p><?php echo nl2br(htmlspecialchars($item['noi_dung_xu_ly'])); ?></p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </details>
                <?php endforeach; ?>
            <?php endif; ?>
        </div> </div> </main>

<?php
require 'dung_chung/cuoi_trang.php';
?>