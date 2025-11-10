<?php
if (isset($conn) && $conn) {
    $conn->close();
}
?>

<footer>
    <div class="footer-container">
        <div class="footer-col">
            <h3>Về PhoneStore</h3>
            <p>PhoneStore là cửa hàng chuyên cung cấp các sản phẩm điện thoại di động chính hãng, phụ kiện công nghệ và dịch vụ sửa chữa uy tín hàng đầu Việt Nam.</p>
        </div>
        <div class="footer-col">
            <h3>Liên kết nhanh</h3>
            <ul>
                <li><a href="index.php"><i class="fas fa-home"></i>Trang chủ</a></li>
                <li><a href="index.php#san-pham"><i class="fas fa-mobile-alt"></i>Sản phẩm</a></li>
                <li><a href="tin_tuc.php"><i class="fas fa-newspaper"></i>Tin tức</a></li>
                <li><a href="don_hang_cua_toi.php"><i class="fas fa-receipt"></i>Đơn hàng</a></li>
                <?php if (isset($_SESSION['id_nguoi_dung'])): ?>
                    <li><a href="bai_viet_cua_toi.php"><i class="fas fa-pen-square"></i>Bài viết của tôi</a></li>
                <?php endif; ?>
                <li><a href="phan_anh.php"><i class="fas fa-flag"></i>Phản ánh</a></li>
            </ul>
        </div>
        <div class="footer-col contact-info">
            <h3>Thông tin liên hệ</h3>
            <p><i class="fas fa-map-marker-alt"></i>123 Đường ABC, Phường XYZ, Quận 1, TP. HCM</p>
            <p><i class="fas fa-phone-alt"></i>0909.123.456</p>
            <p><i class="fas fa-envelope"></i>support@phonestore.vn</p>
        </div>
        <div class="footer-col">
            <h3>Kết nối với chúng tôi</h3>
            <p>Theo dõi chúng tôi trên các mạng xã hội để nhận thông tin khuyến mãi mới nhất.</p>
        </div>
    </div>
    <div class="footer-bottom-bar">
        <p>&copy; <?php echo date("Y"); ?> - PhoneStore. Đã đăng ký bản quyền.</p>
    </div>
</footer>

</body>
</html>