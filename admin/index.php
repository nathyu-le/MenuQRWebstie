<?php
// /admin/ luôn chuyển về trang đăng nhập. Nếu đã có session,
// login.php sẽ chuyển tiếp tới đúng workspace theo role.
header('Location: /admin/login.php');
exit;
