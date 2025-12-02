<?php
session_start();
require_once 'inc/db.php';
require_once 'inc/helpers.php';
require_customer();

$cid = (int)$_SESSION['customer_id'];
// fetch customer
$stmt = $conn->prepare("SELECT name,phone FROM customers WHERE id=?");
$stmt->bind_param('i',$cid);
$stmt->execute();
$stmt->bind_result($cname,$cphone);
$stmt->fetch();
$stmt->close();

// handle order creation
$errors = [];
if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])){
    $service_id = (int)$_POST['service_id'];
    $qty = max(1,(int)$_POST['qty']);
    $details = trim($_POST['details']);
    $required_by = $_POST['required_by'];

    // handle file upload optional
    $sample_path = null;
    if(!empty($_FILES['sample']['name'])){
        $up = $_FILES['sample'];
        $allowed = ['image/jpeg','image/png','application/pdf','image/jpg'];
        if(!in_array($up['type'],$allowed)) $errors[] = 'Invalid file type';
        if($up['size'] > 5*1024*1024) $errors[] = 'Sample too large (max 5MB)';
        if(empty($errors)){
            $ext = pathinfo($up['name'], PATHINFO_EXTENSION);
            $fn = 'uploads/samples/'.time().'_'.bin2hex(random_bytes(6)).'.'.$ext;
            if(!is_dir('uploads/samples')) mkdir('uploads/samples',0755,true);
            if(move_uploaded_file($up['tmp_name'],$fn)) $sample_path = $fn;
            else $errors[] = 'Upload failed';
        }
    }

    if(empty($errors)){
        $ins = $conn->prepare("INSERT INTO orders (customer_id,service_id,qty,details,sample_file,required_by) VALUES (?,?,?,?,?,?)");
        $ins->bind_param('iiisss',$cid,$service_id,$qty,$details,$sample_path,$required_by);
        if($ins->execute()){
            header('Location: customer_dashboard.php'); exit;
        } else $errors[] = 'DB error: '.$conn->error;
    }
}

// fetch services for dropdown
$services = $conn->query("SELECT id,title,price FROM services ORDER BY id DESC");

// fetch customer orders
$stmt = $conn->prepare("SELECT o.id,o.qty,o.status,o.created_at,o.required_by,s.title,o.sample_file FROM orders o LEFT JOIN services s ON o.service_id = s.id WHERE o.customer_id=? ORDER BY o.created_at DESC");
$stmt->bind_param('i',$cid);
$stmt->execute();
$res = $stmt->get_result();

?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Customer Dashboard</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<div style="max-width:1000px;margin:30px auto">
  <header style="display:flex;justify-content:space-between;align-items:center">
    <h2>Welcome, <?=e($cname)?></h2>
    <div><a href="logout.php" class="btn">Logout</a></div>
  </header>

  <section style="display:flex;gap:20px;margin-top:18px">
    <div style="flex:2">
      <div class="form-box">
        <h3>Place New Order</h3>
        <?php foreach($errors as $er) echo "<p style='color:red;'>".e($er)."</p>"; ?>
        <form method="post" enctype="multipart/form-data">
          <label>Service</label>
          <select name="service_id" required>
            <?php while($s = $services->fetch_assoc()): ?>
              <option value="<?=e($s['id'])?>"><?=e($s['title'])?> — ₹<?=e($s['price'])?></option>
            <?php endwhile; ?>
          </select>
          <input name="qty" type="number" value="1" min="1" required>
          <textarea name="details" placeholder="Order details / material / finishing instructions"></textarea>
          <label>Upload sample (jpg/png/pdf) — optional</label>
          <input type="file" name="sample" accept=".jpg,.jpeg,.png,.pdf">
          <label>Required by (deadline)</label>
          <input type="datetime-local" name="required_by" required>
          <button type="submit" name="place_order">Place Order</button>
        </form>
      </div>

      <div style="margin-top:20px" class="form-box">
        <h3>Your Orders</h3>
        <table class="table">
          <tr><th>ID</th><th>Service</th><th>Qty</th><th>Status</th><th>Required By</th><th>Sample</th></tr>
          <?php while($o = $res->fetch_assoc()): ?>
          <tr>
            <td><?=e($o['id'])?></td>
            <td><?=e($o['title'])?></td>
            <td><?=e($o['qty'])?></td>
            <td><?=e($o['status'])?></td>
            <td><?=e($o['required_by'])?></td>
            <td><?php if($o['sample_file']): ?><a href="<?=e($o['sample_file'])?>" target="_blank">View</a><?php else: ?>—<?php endif; ?></td>
          </tr>
          <?php endwhile; ?>
        </table>
      </div>

      <div style="margin-top:20px" class="form-box">
        <h3>Feedback</h3>
        <form method="post" action="submit_feedback.php">
          <textarea name="message" placeholder="Tell us how we did" required></textarea>
          <label>Rating (1-5)</label>
          <select name="rating" required>
            <option value="5">5</option><option value="4">4</option><option value="3">3</option><option value="2">2</option><option value="1">1</option>
          </select>
          <button type="submit">Send Feedback</button>
        </form>
      </div>

    </div>

    <aside style="flex:1">
      <div class="form-box">
        <h4>Available Services</h4>
        <?php
        $sv = $conn->query("SELECT title,description,price,image FROM services ORDER BY id DESC LIMIT 6");
        while($s = $sv->fetch_assoc()):
        ?>
        <div style="margin-bottom:12px">
          <?php if($s['image']): ?><img src="<?=e($s['image'])?>" style="width:100%;border-radius:6px;margin-bottom:8px"><?php endif; ?>
          <strong><?=e($s['title'])?> — ₹<?=e($s['price'])?></strong>
          <p class="small"><?=e($s['description'])?></p>
        </div>
        <?php endwhile; ?>
      </div>

      <div class="form-box" style="margin-top:12px">
        <h4>Letter Cards</h4>
        <?php $lc = $conn->query("SELECT title,image,description FROM lettercards ORDER BY id DESC LIMIT 6");
        while($l = $lc->fetch_assoc()): ?>
          <div style="margin-bottom:12px">
            <?php if($l['image']): ?><img src="<?=e($l['image'])?>" style="width:100%;border-radius:6px;margin-bottom:8px"><?php endif; ?>
            <strong><?=e($l['title'])?></strong>
            <p class="small"><?=e($l['description'])?></p>
          </div>
        <?php endwhile; ?>
      </div>

      <div class="form-box" style="margin-top:12px">
        <h4>Contact</h4>
        <?php
        $cd = $conn->query("SELECT * FROM contact_details LIMIT 1")->fetch_assoc();
        ?>
        <p class="small"><?=e($cd['address'])?></p>
        <p class="small">Phone: <?=e($cd['phone'])?></p>
        <p class="small">Email: <?=e($cd['email'])?></p>
      </div>
    </aside>
  </section>
</div>
</body></html>
