<?php session_start(); ?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Printify Press — Welcome</title>
<link rel="stylesheet" href="assets/style.css">
</head>
<body>
<header class="hero">
  <div class="hero-content">
    <h1 class="logo">Printify Press</h1>
    <p class="tag">High-quality printing — fast & beautiful</p>

    <div class="buttons">
      <a href="register.php" class="btn">Register</a>
      <a href="login.php" class="btn outline">Login</a>
    </div>

    <div class="admin-note animated-card">
      <strong>Admin demo credentials (predefined):</strong>
      <div>Username: <code>admin@printingpress.com</code></div>
      <div>Password: <code>Admin@123</code></div>
      <small>These credentials are stored in DB and cannot be changed through admin UI.</small>
    </div>

  </div>
  <div class="hero-visual">
    <!-- animated SVG / images -->
    <img src="assets/hero-print.svg" alt="printing" />
  </div>
</header>

<section class="features">
  <div class="card">
    <h3>Fast Orders</h3>
    <p>Upload your sample, choose service, and specify deadline — we’ll handle the rest.</p>
  </div>
  <div class="card">
    <h3>Custom Letter Cards</h3>
    <p>Choose from available sample lettercards or upload your own art.</p>
  </div>
  <div class="card">
    <h3>Admin Managed</h3>
    <p>Admin can update services, pricing, about images, contact details and manage orders.</p>
  </div>
</section>

<footer class="site-footer">
  <small>&copy; <?=date('Y')?> Printify Press</small>
</footer>

<script>
  // small JS for micro interactions if needed
</script>
</body>
</html>
