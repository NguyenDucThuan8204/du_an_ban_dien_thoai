<?php
// Đóng kết nối CSDL (nếu $conn còn tồn tại)
if (isset($conn) && $conn) {
    $conn->close();
}
?>

    </div> </div> </body>
</html>