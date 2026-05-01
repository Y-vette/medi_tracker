<?php require_once 'includes/config.php'; ?>
<?php include 'includes/header.php'; ?>
<div class="topbar"><div class="topbar-title"><i class="fas fa-ban"></i> Access Denied</div></div>
<div class="page-body">
  <div class="card" style="max-width:520px;margin:40px auto;text-align:center;padding:40px;">
    <i class="fas fa-lock" style="font-size:3rem;color:var(--amber-mid);margin-bottom:16px;display:block;"></i>
    <h2 style="margin-bottom:10px;">Unauthorized Access</h2>
    <p style="color:var(--text-muted);margin-bottom:22px;">You do not have permission to view this page. Please contact your system administrator.</p>
    <a href="<?= APP_URL ?>/dashboard.php" class="btn btn-primary"><i class="fas fa-home"></i> Back to Dashboard</a>
  </div>
</div>
<?php include 'includes/footer.php'; ?>
