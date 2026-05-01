<?php
define('DB_HOST','localhost'); define('DB_USER','root'); define('DB_PASS',''); define('DB_NAME','meditracker_db'); define('APP_URL','/meditracker');
$msg = ''; $type = 'info';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sql = file_get_contents(__DIR__ . '/meditracker_db.sql');
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS);
    if ($conn->connect_error) { $msg = 'DB connection failed: ' . $conn->connect_error; $type='danger'; }
    else {
        $conn->multi_query($sql);
        do { if ($res = $conn->store_result()) $res->free(); } while ($conn->more_results() && $conn->next_result());
        if ($conn->errno) { $msg = 'Setup error: ' . $conn->error; $type='danger'; }
        else { $msg = 'Setup complete! Database created. Default login: admin / Admin@123'; $type='success'; }
        $conn->close();
    }
}
?>
<!DOCTYPE html><html lang="en"><head><meta charset="UTF-8"><title>Meditracker Setup</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600&family=Space+Mono:wght@700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/css/style.css"></head>
<body class="login-page">
<div class="login-card">
  <div class="login-logo">
    <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
    <h1>Meditracker</h1><p>Initial Setup</p>
  </div>
  <?php if ($msg): ?>
  <div class="alert alert-<?= $type ?>"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($msg) ?></div>
  <?php endif; ?>
  <p style="font-size:0.83rem;color:#555;margin-bottom:20px;line-height:1.6;">
    This will create the <strong>meditracker_db</strong> database and seed all required tables.
    Make sure XAMPP MySQL is running before proceeding.
  </p>
  <form method="POST">
    <button type="submit" class="btn btn-primary w-100" style="padding:11px;">
      <i class="fas fa-database"></i> Run Setup &amp; Create Database
    </button>
  </form>
  <div class="login-divider">Already set up?</div>
  <a href="<?= APP_URL ?>/index.php" class="btn btn-outline w-100"><i class="fas fa-sign-in-alt"></i> Go to Login</a>
</div>
</body></html>
