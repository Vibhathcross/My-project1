<?php
session_start();
require_once 'inc/db.php';
require_once 'inc/helpers.php';
require_admin();

$tab = $_GET['tab'] ?? 'orders';
$msg = '';

// handle actions
if($_SERVER['REQUEST_METHOD'] === 'POST'){
    // Mark order complete or update status
    if(isset($_POST['mark_complete']) && isset($_POST['order_id'])){
        $oid = (int)$_POST['order_id'];
        $u = $conn->prepare("UPDATE orders SET status='completed' WHERE id=?");
        $u->bind_param('i',$oid); $u->execute(); $msg = 'Order marked completed';
    }

    // Create or update services
    if(isset($_POST['save_service'])){
        $sid = (int)($_POST['service_id'] ?? 0);
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $imgPath = null;
        if(isset($_FILES['image']) && $_FILES['image']['name']){
            $f = $_FILES['image'];
            $ext = pathinfo($f['name'],PATHINFO_EXTENSION);
            $path = 'uploads/services/'.time().'_'.bin2hex(random_bytes(5)).'.'.$ext;
            if(!is_dir('uploads/services')) mkdir('uploads/services',0755,true);
            move_uploaded_file($f['tmp_name'],$path);
            $imgPath = $path;
        }
        if($sid > 0){
            if($imgPath){
                $q = $conn->prepare("UPDATE services SET title=?,description=?,price=?,image=? WHERE id=?");
                $q->bind_param('ssdsi',$title,$desc,$price,$imgPath,$sid);
            } else {
                $q = $conn->prepare("UPDATE services SET title=?,description=?,price=? WHERE id=?");
                $q->bind_param('sdsi',$title,$desc,$price,$sid);
            }
            $q->execute(); $msg = 'Service updated';
        } else {
            $q = $conn->prepare("INSERT INTO services (title,description,price,image) VALUES (?,?,?,?)");
            $q->bind_param('sdss',$title,$desc,$price,$imgPath);
            $q->execute(); $msg = 'Service created';
        }
    }

    // create lettercard
    if(isset($_POST['save_card'])){
        $title = trim($_POST['title']);
        $desc = trim($_POST['description']);
        $imgPath = '';
        if(isset($_FILES['card_image']) && $_FILES['card_image']['name']){
            $f = $_FILES['card_image'];
            $ext = pathinfo($f['name'],PATHINFO_EXTENSION);
            $path = 'uploads/cards/'.time().'_'.bin2hex(random_bytes(5)).'.'.$ext;
            if(!is_dir('uploads/cards')) mkdir('uploads/cards',0755,true);
            move_uploaded_file($f['tmp_name'],$path);
            $imgPath = $path;
        }
        $q = $conn->prepare("INSERT INTO lettercards (title,description,image) VALUES (?,?,?)");
        $q->bind_param('sss',$title,$desc,$imgPath);
        $q->execute(); $msg = 'Card created';
    }

    // edit contact details
    if(isset($_POST['save_contact'])){
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);
        $map = trim($_POST['map']);
        // single row update
        $exists = $conn->query("SELECT id FROM contact_details LIMIT 1")->fetch_assoc();
        if($exists){
            $u = $conn->prepare("UPDATE contact_details SET address=?,phone=?,email=?,google_map_embed=? WHERE id=?");
            $u->bind_param('ssssi',$address,$phone,$email,$map,$exists['id']); $u->execute();
        } else {
            $i = $conn->prepare("INSERT INTO contact_details (address,phone,email,google_map_embed) VALUES (?,?,?,?)");
            $i->bind_param('ssss',$address,$phone,$email,$map); $i->execute();
        }
        $msg = 'Contact saved';
    }

    // mark admin cannot change credentials via UI: no handler for admin password change
}

// fetch data for tabs
$orders = $conn->query("SELECT o.*, c.name AS customer_name, s.title AS service_title FROM orders o LEFT JOIN customers c ON o.customer_id=c.id LEFT JOIN services s ON o.service_id=s.id ORDER BY o.created_at DESC");
$customers = $conn->query("SELECT * FROM customers ORDER BY created_at DESC");
$services = $conn->query("SELECT * FROM services ORDER BY id DESC");
$cards = $conn->query("SELECT * FROM lettercards ORDER BY id DESC");
$feedbacks = $conn->query("SELECT f.*, c.name FROM feedbacks f LEFT JOIN customers c ON f.customer_id = c.id ORDER BY f.created_at DESC");
$contact = $conn->query("SELECT * FROM contact_details LIMIT 1")->fetch_assoc();
$aboutImages = $conn->query("SELECT * FROM about_images ORDER BY id DESC");

?>
<!doctype html>
<html><head><meta charset="utf-8"><title>Admin Dashboard</title><link rel="stylesheet" href="assets/style.css"></head>
<body>
<div style="max-width:1200px;margin:30px auto">
  <header style="display:flex;justify-content:space-between;align-items:center">
    <h2>Admin Dashboard</h2>
    <div><a href="logout.php" class="btn">Logout</a></div>
  </header>

  <nav style="margin-top:12px">
    <a href="?tab=orders" class="btn outline">Orders</a>
    <a href="?tab=customers" class="btn outline">Customers</a>
    <a href="?tab=services" class="btn outline">Services</a>
    <a href="?tab=lettercards" class="btn outline">Letter Cards</a>
    <a href="?tab=about-images" class="btn outline">About Images</a>
    <a href="?tab=contact" class="btn outline">Contact</a>
    <a href="?tab=feedback" class="btn outline">Feedback</a>
  </nav>

  <?php if($msg) echo "<p style='color:green;'>".e($msg)."</p>"; ?>

  <main style="margin-top:20px">
  <?php if($tab === 'orders'): ?>
    <h3>Orders</h3>
    <table class="table"><tr><th>ID</th><th>Customer</th><th>Service</th><th>Qty</th><th>Required By</th><th>Status</th><th>Sample</th><th>Action</th></tr>
    <?php while($o = $orders->fetch_assoc()): ?>
      <tr>
        <td><?=e($o['id'])?></td>
        <td><?=e($o['customer_name'])?></td>
        <td><?=e($o['service_title'])?></td>
        <td><?=e($o['qty'])?></td>
        <td><?=e($o['required_by'])?></td>
        <td><?=e($o['status'])?></td>
        <td><?php if($o['sample_file']): ?><a href="<?=e($o['sample_file'])?>" target="_blank">View</a><?php else: ?>—<?php endif; ?></td>
        <td>
          <?php if($o['status'] !== 'completed'): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="order_id" value="<?=e($o['id'])?>">
              <button type="submit" name="mark_complete">Mark Completed</button>
            </form>
          <?php else: ?>Completed<?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
    </table>

  <?php elseif($tab === 'customers'): ?>
    <h3>Customers</h3>
    <table class="table"><tr><th>ID</th><th>Name</th><th>Phone</th><th>Joined</th></tr>
    <?php while($c = $customers->fetch_assoc()): ?>
      <tr><td><?=e($c['id'])?></td><td><?=e($c['name'])?></td><td><?=e($c['phone'])?></td><td><?=e($c['created_at'])?></td></tr>
    <?php endwhile; ?>
    </table>

  <?php elseif($tab === 'services'): ?>
    <h3>Services — Add / Edit</h3>
    <div class="form-box">
      <form method="post" enctype="multipart/form-data">
        <input type="hidden" name="service_id" value="0">
        <input name="title" placeholder="Title" required>
        <textarea name="description" placeholder="Description"></textarea>
        <input name="price" placeholder="Price" required>
        <label>Image</label>
        <input type="file" name="image" accept=".jpg,.jpeg,.png">
        <button type="submit" name="save_service">Save Service</button>
      </form>
    </div>

    <h4 style="margin-top:12px">Existing Services</h4>
    <table class="table"><tr><th>ID</th><th>Title</th><th>Price</th><th>Image</th></tr>
      <?php while($s = $services->fetch_assoc()): ?>
        <tr>
          <td><?=e($s['id'])?></td><td><?=e($s['title'])?></td><td>₹<?=e($s['price'])?></td><td><?php if($s['image']): ?><img src="<?=e($s['image'])?>" style="width:90px"><?php endif; ?></td>
        </tr>
      <?php endwhile; ?>
    </table>

  <?php elseif($tab === 'lettercards'): ?>
    <h3>Letter Cards — Add</h3>
    <div class="form-box">
      <form method="post" enctype="multipart/form-data">
        <input name="title" placeholder="Title" required>
        <textarea name="description" placeholder="Description"></textarea>
        <input type="file" name="card_image" accept=".jpg,.jpeg,.png">
        <button type="submit" name="save_card">Create Card</button>
      </form>
    </div>

    <h4 style="margin-top:12px">Existing Cards</h4>
    <table class="table"><tr><th>ID</th><th>Title</th><th>Image</th></tr>
      <?php while($l = $cards->fetch_assoc()): ?>
        <tr><td><?=e($l['id'])?></td><td><?=e($l['title'])?></td><td><?php if($l['image']): ?><img src="<?=e($l['image'])?>" style="width:90px"><?php endif; ?></td></tr>
      <?php endwhile; ?>
    </table>

  <?php elseif($tab === 'about-images'): ?>
    <h3>About Page Images</h3>
    <div class="form-box">
      <form method="post" action="admin_upload_about.php" enctype="multipart/form-data">
        <input name="title" placeholder="Title">
        <input type="file" name="about_image" accept=".jpg,.jpeg,.png" required>
        <input name="caption" placeholder="Caption">
        <button type="submit">Upload</button>
      </form>
    </div>
    <h4 style="margin-top:12px">Existing Images</h4>
    <table class="table"><tr><th>ID</th><th>Title</th><th>Image</th><th>Caption</th></tr>
      <?php while($a = $aboutImages->fetch_assoc()): ?>
        <tr><td><?=e($a['id'])?></td><td><?=e($a['title'])?></td><td><?php if($a['image']): ?><img src="<?=e($a['image'])?>" style="width:90px"><?php endif; ?></td><td><?=e($a['caption'])?></td></tr>
      <?php endwhile; ?>
    </table>

  <?php elseif($tab === 'contact'): ?>
    <h3>Contact Details</h3>
    <div class="form-box">
      <form method="post">
        <textarea name="address" placeholder="Address"><?=e($contact['address'] ?? '')?></textarea>
        <input name="phone" placeholder="Phone" value="<?=e($contact['phone'] ?? '')?>">
        <input name="email" placeholder="Email" value="<?=e($contact['email'] ?? '')?>">
        <textarea name="map" placeholder="Google map embed (iframe)"><?=e($contact['google_map_embed'] ?? '')?></textarea>
        <button type="submit" name="save_contact">Save Contact</button>
      </form>
    </div>

  <?php elseif($tab === 'feedback'): ?>
    <h3>Customer Feedback</h3>
    <table class="table"><tr><th>ID</th><th>Customer</th><th>Message</th><th>Rating</th><th>When</th></tr>
      <?php while($f = $feedbacks->fetch_assoc()): ?>
        <tr><td><?=e($f['id'])?></td><td><?=e($f['name'])?></td><td><?=e($f['message'])?></td><td><?=e($f['rating'])?></td><td><?=e($f['created_at'])?></td></tr>
      <?php endwhile; ?>
    </table>

  <?php endif; ?>
  </main>

</div>
</body></html>
