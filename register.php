<?php
session_start();
require_once 'inc/db.php';
require_once 'inc/helpers.php';

$errors = [];
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    if(!$name || !$phone || !$password) $errors[] = 'Please fill all fields';
    if($password !== $password2) $errors[] = 'Passwords do not match';
    if(empty($errors)){
        // simple duplicate phone check
        $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ?");
        $stmt->bind_param('s',$phone);
        $stmt->execute();
        $stmt->store_result();
        if($stmt->num_rows > 0){
            $errors[] = 'Phone already registered. Please login.';
            $stmt->close();
        } else {
            $stmt->close();
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $ins = $conn->prepare("INSERT INTO customers (name,phone,password) VALUES (?,?,?)");
            $ins->bind_param('sss', $name,$phone,$hash);
            if($ins->execute()){
                $_SESSION['customer_id'] = $ins->insert_id;
                header('Location: customer_dashboard.php'); exit;
            } else {
                $errors[] = 'DB error: '.$conn->error;
            }
            $ins->close();
        }
    }
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Register</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<div class="form-box">
  <h2>Customer Registration</h2>
  <?php foreach($errors as $e) echo "<p style='color:red;'>".e($e)."</p>"; ?>
  <form method="post" action="">
    <input name="name" placeholder="Full name" required>
    <input name="phone" placeholder="Phone number" required>
    <input name="password" type="password" placeholder="Password" required>
    <input name="password2" type="password" placeholder="Confirm Password" required>
    <button type="submit">Register</button>
  </form>
  <p class="small">Already have account? <a href="login.php">Login here</a></p>
</div>
</body></html>
