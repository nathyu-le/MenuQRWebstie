<?php
session_start();
require_once __DIR__ . '/../../app/helpers/auth.php';
require_admin_login();
header('Location: ' . role_home());
exit;
