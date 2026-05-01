<?php
// ============================================================
// Meditracker — Core Configuration
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'meditracker_db');
define('APP_NAME', 'Meditracker');
define('APP_TAGLINE', 'AI-Powered Hospital Ward Monitoring');
define('APP_URL',  '/meditracker');
define('SESSION_TIMEOUT', 1800);

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($conn->connect_error) {
    die("
    <style>body{font-family:system-ui,sans-serif;background:#0d1f2d;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;}
    .err{background:#fff;border-radius:14px;padding:40px;max-width:520px;text-align:center;}
    .err h2{color:#e53935;margin-bottom:12px;} .err p{color:#555;line-height:1.6;}
    .err a{display:inline-block;margin-top:18px;padding:10px 24px;background:#1D9E75;color:#fff;border-radius:8px;text-decoration:none;}</style>
    <div class='err'>
      <h2>&#9888; Database Connection Failed</h2>
      <p><strong>Error:</strong> " . htmlspecialchars($conn->connect_error) . "</p>
      <p>Make sure XAMPP MySQL is running, then run the setup.</p>
      <a href='" . APP_URL . "/setup.php'>Run Setup</a>
    </div>");
}
$conn->set_charset("utf8mb4");

// ── Auth helpers ─────────────────────────────────────────────
function requireLogin(): void {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . APP_URL . "/index.php");
        exit();
    }
}
function requireRole(array|string $roles): void {
    if (!in_array($_SESSION['role'] ?? '', (array)$roles)) {
        header("Location: " . APP_URL . "/unauthorized.php");
        exit();
    }
}
function sessionTimeout(): void {
    if (isset($_SESSION['last_activity']) &&
        (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_unset(); session_destroy();
        header("Location: " . APP_URL . "/index.php?msg=timeout");
        exit();
    }
    $_SESSION['last_activity'] = time();
}
function clean(mysqli $conn, string $data): string {
    return $conn->real_escape_string(htmlspecialchars(strip_tags(trim($data))));
}
function flash(string $type, string $msg): string {
    $icons = ['success'=>'check-circle','danger'=>'times-circle','warning'=>'exclamation-triangle','info'=>'info-circle'];
    $icon  = $icons[$type] ?? 'info-circle';
    return "<div class='alert alert-{$type}'><i class='fas fa-{$icon}'></i> " . htmlspecialchars($msg) . "</div>";
}
// ── Dashboard summary helpers ────────────────────────────────
function getTodayStats(mysqli $conn): array {
    $totalAdm  = (int)($conn->query("SELECT SUM(day_admissions+night_admissions) FROM ward_daily_reports WHERE report_date=CURDATE()")->fetch_row()[0] ?? 0);
    $critical  = (int)($conn->query("SELECT SUM(critical_cases) FROM ward_daily_reports WHERE report_date=CURDATE()")->fetch_row()[0] ?? 0);
    $oxygen    = (int)($conn->query("SELECT SUM(on_oxygen) FROM ward_daily_reports WHERE report_date=CURDATE()")->fetch_row()[0] ?? 0);
    $deaths    = (int)($conn->query("SELECT SUM(deaths) FROM ward_daily_reports WHERE report_date=CURDATE()")->fetch_row()[0] ?? 0);
    $discharges= (int)($conn->query("SELECT SUM(discharges) FROM ward_daily_reports WHERE report_date=CURDATE()")->fetch_row()[0] ?? 0);
    return compact('totalAdm','critical','oxygen','deaths','discharges');
}
