<?php
session_start();
require_once 'inc/db.php';
require_once 'inc/helpers.php';

$error = '';
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    $role = $_POST['role'] ?? '';
    $user = trim($_POST['user'] ?? '');
    $pass = $_POST['password'] ?? '';

    if($role === 'admin'){
        $stmt = $conn->prepare("SELECT id,password FROM admin_users WHERE username = ?");
        $stmt->bind_param('s',$user);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id,$hash);
        if($stmt->num_rows === 1){
            $stmt->fetch();
            if(password_verify($pass,$hash)){
                $_SESSION['admin_id'] = $id;
                header('Location: admin_dashboard.php'); exit;
            } else $error = 'Invalid admin credentials';
        } else $error = 'Admin not found';
        $stmt->close();
    } elseif($role === 'customer'){
        // login by phone (or email)
        $stmt = $conn->prepare("SELECT id,password FROM customers WHERE phone = ?");
        $stmt->bind_param('s',$user);
        $stmt->execute();
        $stmt->store_result();
        $stmt->bind_result($id,$hash);
        if($stmt->num_rows === 1){
            $stmt->fetch();
            if(password_verify($pass,$hash)){
                $_SESSION['customer_id'] = $id;
                header('Location: customer_dashboard.php'); exit;
            } else $error = 'Wrong customer password';
        } else $error = 'Customer not found';
        $stmt->close();
    } else $error = 'Select role';
}
?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Login</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<div class="form-box">
  <h2>Login</h2>
  <?php if($error) echo "<p style='color:red;'>".e($error)."</p>"; ?>
  <form method="post" action="">
    <label>Role</label>
    <select name="role" required>
      <option value="">Select</option>
      <option value="customer">Customer</option>
      <option value="admin">Admin</option>
    </select>

    <input name="user" placeholder="Phone (customer) or Admin username" required>
    <input name="password" type="password" placeholder="Password" required>
    <button type="submit">Login</button>
  </form>
  <p class="small">No account? <a href="register.php">Register</a></p>
</div>
</body></html>
