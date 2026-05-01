<?php
require_once 'includes/config.php';
if (isset($_SESSION['user_id'])) {
    header("Location: " . APP_URL . "/dashboard.php"); exit();
}
$error   = '';
$timeout = isset($_GET['msg']) && $_GET['msg'] === 'timeout';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = clean($conn, $_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $stmt = $conn->prepare("SELECT * FROM users WHERE username=? LIMIT 1");
    $stmt->bind_param('s', $username);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']       = $user['id'];
            $_SESSION['username']      = $user['username'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['full_name']     = $user['full_name'];
            $_SESSION['last_activity'] = time();
            $stmt->close();
            header("Location: " . APP_URL . "/dashboard.php"); exit();
        }
    }
    $stmt->close();
    $error = "Incorrect username or password.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meditracker — Login</title>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/css/style.css">
</head>
<body class="login-page">

<div class="login-card">
  <div class="login-logo">
    <div class="logo-icon"><i class="fas fa-heartbeat"></i></div>
    <h1>Meditracker</h1>
    <p>AI-Powered Hospital Ward Monitoring</p>
  </div>

  <?php if ($timeout): ?>
  <div class="alert alert-warning"><i class="fas fa-clock"></i> Your session expired. Please log in again.</div>
  <?php endif; ?>
  <?php if ($error): ?>
  <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <form method="POST">
    <div class="form-group" style="margin-bottom:14px;">
      <label><i class="fas fa-user" style="color:var(--teal-mid);margin-right:4px;"></i> Username</label>
      <input type="text" name="username" placeholder="Enter your username" required autocomplete="username"
             value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
    </div>
    <div class="form-group" style="margin-bottom:22px;">
      <label><i class="fas fa-lock" style="color:var(--teal-mid);margin-right:4px;"></i> Password</label>
      <input type="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
    </div>
    <button type="submit" class="btn btn-primary w-100" style="padding:11px;font-size:0.9rem;">
      <i class="fas fa-sign-in-alt"></i> Sign In to Meditracker
    </button>
  </form>

  <div class="login-divider">First time?</div>
  <a href="<?= APP_URL ?>/setup.php" class="btn btn-outline w-100">
    <i class="fas fa-cog"></i> Run Initial Setup
  </a>
  <p style="text-align:center;margin-top:22px;font-size:0.72rem;color:var(--text-muted);">
    &copy; <?= date('Y') ?> Meditracker &mdash; Namibia
  </p>
</div>

</body>
</html>
