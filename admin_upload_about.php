<?php
session_start();
require_once 'inc/db.php';
require_once 'inc/helpers.php';
require_admin();

if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $title = trim($_POST['title']);
    $caption = trim($_POST['caption']);
    if(isset($_FILES['about_image']) && $_FILES['about_image']['name']){
        $f = $_FILES['about_image'];
        $ext = pathinfo($f['name'],PATHINFO_EXTENSION);
        $path = 'uploads/about/'.time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
        if(!is_dir('uploads/about')) mkdir('uploads/about',0755,true);
        if(move_uploaded_file($f['tmp_name'],$path)){
            $ins = $conn->prepare("INSERT INTO about_images (title,image,caption) VALUES (?,?,?)");
            $ins->bind_param('sss',$title,$path,$caption);
            $ins->execute();
        }
    }
}
header('Location: admin_dashboard.php?tab=about-images');
exit;
