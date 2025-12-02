<?php
session_start();
require_once 'inc/db.php';
require_once 'inc/helpers.php';
require_customer();

$cid = (int)$_SESSION['customer_id'];
$msg = trim($_POST['message'] ?? '');
$rating = (int)($_POST['rating'] ?? 5);
if($msg){
    $stmt = $conn->prepare("INSERT INTO feedbacks (customer_id,message,rating) VALUES (?,?,?)");
    $stmt->bind_param('isi',$cid,$msg,$rating);
    $stmt->execute();
}
header('Location: customer_dashboard.php');
exit;
