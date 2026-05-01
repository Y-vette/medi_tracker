<?php requireLogin(); sessionTimeout(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Meditracker — AI-Powered Ward Monitoring</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=Space+Mono:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<link rel="stylesheet" href="<?= APP_URL ?>/css/style.css">
</head>
<body>
<div class="layout">

<!-- ══ SIDEBAR ══════════════════════════════════════ -->
<aside class="sidebar">
  <div class="sidebar-brand">
    <div class="brand-icon"><i class="fas fa-heartbeat"></i></div>
    <div class="brand-text">
      <strong>Meditracker</strong>
      <small>Ward Monitoring</small>
    </div>
  </div>

  <nav class="sidebar-nav">
    <?php
    $cur  = basename($_SERVER['PHP_SELF']);
    $role = $_SESSION['role'] ?? '';
    function navItem(string $href, string $icon, string $label, string $cur, string $badge=''): void {
        $file   = basename($href);
        $active = ($file === $cur) ? 'active' : '';
        $b = $badge ? "<span class='pill'>{$badge}</span>" : '';
        echo "<a href='{$href}' class='nav-item {$active}'><i class='fas fa-{$icon}'></i><span>{$label}</span>{$b}</a>";
    }
    ?>
    <div class="nav-section">Overview</div>
    <?php navItem(APP_URL.'/dashboard.php', 'th-large', 'Dashboard', $cur); ?>

    <div class="nav-section">Ward Management</div>
    <?php navItem(APP_URL.'/modules/wards.php',        'bed',          'Wards & Beds',    $cur); ?>
    <?php navItem(APP_URL.'/ward_reports/report.php',  'file-medical', 'Daily Reports',   $cur); ?>
    <?php navItem(APP_URL.'/modules/patients.php',     'procedures',   'Patients',        $cur); ?>

    <div class="nav-section">Insights</div>
    <?php navItem(APP_URL.'/reports/reports.php',      'chart-line',   'Analytics',       $cur); ?>
    <?php navItem(APP_URL.'/ai/symptom_checker.php',   'robot',        'AI Symptom Check',$cur); ?>
    <?php navItem(APP_URL.'/ai/ai_insights.php',       'brain',        'AI Insights',     $cur); ?>

    <?php if (in_array($role, ['admin'])): ?>
    <div class="nav-section">Administration</div>
    <?php navItem(APP_URL.'/modules/staff.php', 'user-md', 'Staff', $cur); ?>
    <?php endif; ?>

    <div class="nav-section">Support</div>
    <?php navItem(APP_URL.'/contact.php', 'headset', 'Help & Contact', $cur); ?>
  </nav>

  <div class="sidebar-footer">
    <?php $initials = strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
    <div class="s-avatar"><?= $initials ?></div>
    <div class="s-meta">
      <strong><?= htmlspecialchars($_SESSION['full_name'] ?? 'User') ?></strong>
      <small><?= ucfirst($role) ?></small>
    </div>
    <a href="<?= APP_URL ?>/logout.php" class="logout-btn" title="Logout">
      <i class="fas fa-sign-out-alt"></i>
    </a>
  </div>
</aside>

<!-- ══ MAIN CONTENT ══════════════════════════════════ -->
<main class="main-content">
