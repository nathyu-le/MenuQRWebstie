<?php
session_start();
require_once __DIR__ . '/../../app/config/database.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
$code=trim($_GET['ma_don']??'');
if($code===''){echo json_encode(['success'=>false]);exit;}
$stmt=$pdo->prepare('SELECT trang_thai FROM don_hang WHERE ma_don=? LIMIT 1');
$stmt->execute([$code]);$status=$stmt->fetchColumn();
echo json_encode($status===false?['success'=>false]:['success'=>true,'status'=>$status],JSON_UNESCAPED_UNICODE);
