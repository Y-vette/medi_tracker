<?php
require_once '../includes/config.php';
requireLogin();
sessionTimeout();

// Auto-generate AI insights from latest data
if (isset($_GET['generate'])) {
    // Clear old auto insights
    $conn->query("DELETE FROM ai_insights WHERE generated_at < DATE_SUB(NOW(), INTERVAL 1 DAY)");

    $wards = $conn->query("SELECT w.*, wr.critical_cases, wr.on_oxygen, wr.day_admissions, wr.night_admissions, wr.discharges, wr.deaths
        FROM wards w LEFT JOIN ward_daily_reports wr ON w.id=wr.ward_id AND wr.report_date=CURDATE() ORDER BY w.ward_name");

    while ($w = $wards->fetch_assoc()) {
        $pct = $w['total_beds'] > 0 ? round($w['occupied_beds'] / $w['total_beds'] * 100) : 0;
        $wn  = $conn->real_escape_string($w['ward_name']);

        if ($pct >= 90) {
            $ins = "{$w['ward_name']} is at {$pct}% capacity — critically full. Immediate patient transfer or bed expansion needed.";
            $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','critical',{$w['id']})");
        } elseif ($pct >= 75) {
            $ins = "{$w['ward_name']} is at {$pct}% capacity — approaching saturation. Monitor closely.";
            $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','warning',{$w['id']})");
        }
        if ((int)$w['critical_cases'] >= 5) {
            $ins = "High critical case count ({$w['critical_cases']}) in {$w['ward_name']} today. Recommend senior clinician review.";
            $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','critical',{$w['id']})");
        }
        if ((int)$w['deaths'] >= 2) {
            $ins = "Mortality alert: {$w['deaths']} deaths recorded in {$w['ward_name']} today. Clinical audit recommended.";
            $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','critical',{$w['id']})");
        }
        if ((int)$w['on_oxygen'] >= 6) {
            $ins = "{$w['ward_name']} has {$w['on_oxygen']} patients on oxygen. Verify oxygen supply sufficiency.";
            $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','warning',{$w['id']})");
        }
    }

    // Hospital-wide checks
    $stats = getTodayStats($conn);
    if ($stats['totalAdm'] > 60) {
        $ins = "High admission volume today ({$stats['totalAdm']} admissions). Ensure adequate staffing across all wards.";
        $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','warning',NULL)");
    }
    if ($stats['discharges'] > $stats['totalAdm']) {
        $ins = "Discharge rate exceeds admissions today — positive patient flow. Bed availability improving.";
        $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','info',NULL)");
    }

    header("Location: ai_insights.php?generated=1"); exit();
}

if (isset($_GET['mark_read'])) {
    $conn->query("UPDATE ai_insights SET is_read=1");
    header("Location: ai_insights.php"); exit();
}

$insights = $conn->query("
    SELECT ai.*, w.ward_name
    FROM ai_insights ai
    LEFT JOIN wards w ON ai.ward_id=w.id
    ORDER BY ai.generated_at DESC
    LIMIT 100
");
$unread = (int)$conn->query("SELECT COUNT(*) FROM ai_insights WHERE is_read=0")->fetch_row()[0];
?>
<?php include '../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title"><i class="fas fa-brain"></i> AI Insights</div>
  <div class="topbar-right flex-gap">
    <?php if ($unread > 0): ?>
    <a href="?mark_read=1" class="btn btn-outline btn-sm"><i class="fas fa-check-double"></i> Mark All Read</a>
    <?php endif; ?>
    <a href="?generate=1" class="btn btn-teal btn-sm"><i class="fas fa-sync-alt"></i> Generate Insights</a>
  </div>
</div>

<div class="page-body">

<?php if (isset($_GET['generated'])): ?>
<div class="alert alert-success"><i class="fas fa-check-circle"></i> AI insights generated from today's ward data.</div>
<?php endif; ?>

<div class="alert alert-info">
  <i class="fas fa-robot"></i>
  <strong>About AI Insights:</strong> These insights are automatically generated from ward report data submitted daily. Click <strong>Generate Insights</strong> after ward reports are submitted to refresh analysis.
</div>

<?php if ($insights->num_rows > 0): while ($ins = $insights->fetch_assoc()):
  $iclass = match($ins['severity']) { 'critical'=>'critical','warning'=>'warn', default=>'ok' };
  $iicon  = match($ins['severity']) { 'critical'=>'fa-exclamation-circle','warning'=>'fa-exclamation-triangle', default=>'fa-check-circle' };
  $icolor = match($ins['severity']) { 'critical'=>'var(--red)','warning'=>'var(--amber)', default=>'var(--green)' };
  $ibg    = match($ins['severity']) { 'critical'=>'var(--red-lt)','warning'=>'var(--amber-lt)', default=>'var(--green-lt)' };
  $sbadge = match($ins['severity']) { 'critical'=>'badge-danger','warning'=>'badge-warning', default=>'badge-success' };
?>
<div class="card" style="padding:16px 20px;margin-bottom:12px;border-left:4px solid <?= $icolor ?>;<?= !$ins['is_read']?'background:#fafcff;':'' ?>">
  <div style="display:flex;align-items:flex-start;gap:14px;">
    <div style="width:36px;height:36px;border-radius:9px;background:<?= $ibg ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
      <i class="fas <?= $iicon ?>" style="color:<?= $icolor ?>;font-size:14px;"></i>
    </div>
    <div style="flex:1;">
      <div style="display:flex;align-items:center;gap:8px;margin-bottom:5px;flex-wrap:wrap;">
        <span class="badge <?= $sbadge ?>"><?= strtoupper($ins['severity']) ?></span>
        <?php if ($ins['ward_name']): ?>
        <span class="badge badge-navy"><?= htmlspecialchars($ins['ward_name']) ?></span>
        <?php endif; ?>
        <?php if (!$ins['is_read']): ?>
        <span class="badge badge-teal" style="font-size:0.6rem;">NEW</span>
        <?php endif; ?>
        <span style="font-size:0.72rem;color:var(--text-muted);margin-left:auto;">
          <?= date('d M Y H:i', strtotime($ins['generated_at'])) ?>
        </span>
      </div>
      <p style="font-size:0.87rem;color:var(--text);line-height:1.55;"><?= htmlspecialchars($ins['insight']) ?></p>
    </div>
  </div>
</div>
<?php endwhile; else: ?>
<div class="card">
  <div class="empty-state">
    <i class="fas fa-brain"></i>
    <h3>No AI insights yet</h3>
    <p>Submit daily ward reports and then click <strong>Generate Insights</strong> to run AI analysis.</p>
  </div>
</div>
<?php endif; ?>

</div>
<?php include '../includes/footer.php'; ?>
