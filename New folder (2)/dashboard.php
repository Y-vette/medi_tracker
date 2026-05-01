<?php
require_once 'includes/config.php';
requireLogin();
sessionTimeout();

$stats     = getTodayStats($conn);
$wards     = $conn->query("SELECT * FROM wards ORDER BY ward_name");
$insights  = $conn->query("SELECT * FROM ai_insights ORDER BY generated_at DESC LIMIT 5");

// 7-day trend data
$trend = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-{$i} days"));
    $row  = $conn->query("SELECT SUM(day_admissions+night_admissions) as adm, SUM(discharges) as dis, SUM(critical_cases) as crit FROM ward_daily_reports WHERE report_date='$date'")->fetch_assoc();
    $trend[] = [
        'label'     => date('D', strtotime($date)),
        'adm'       => (int)($row['adm'] ?? 0),
        'dis'       => (int)($row['dis'] ?? 0),
        'crit'      => (int)($row['crit'] ?? 0),
    ];
}
$trendJson = json_encode($trend);

// Ward report rows for today
$wardReports = $conn->query("
    SELECT w.ward_name, w.total_beds, w.occupied_beds, wr.*
    FROM wards w
    LEFT JOIN ward_daily_reports wr ON w.id=wr.ward_id AND wr.report_date=CURDATE()
    ORDER BY w.ward_name
");
?>
<?php include 'includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-left" style="display:flex;align-items:center;gap:14px;">
    <div>
      <div class="topbar-title"><i class="fas fa-th-large"></i> Dashboard</div>
      <div class="topbar-sub">Welcome back, <?= htmlspecialchars($_SESSION['full_name'] ?? '') ?></div>
    </div>
  </div>
  <div class="topbar-right">
    <div class="live-badge"><div class="live-dot"></div> Live</div>
    <div class="topbar-date"><i class="fas fa-calendar-day"></i> <?= date('l, d F Y') ?></div>
  </div>
</div>

<div class="page-body">

<!-- ── KPI Cards ──────────────────────────────────── -->
<div class="stats-grid">
  <div class="stat-card teal">
    <div class="stat-icon"><i class="fas fa-user-plus"></i></div>
    <div class="stat-info"><h3><?= $stats['totalAdm'] ?></h3><p>Total Admissions Today</p></div>
  </div>
  <div class="stat-card amber">
    <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div>
    <div class="stat-info"><h3><?= $stats['critical'] ?></h3><p>Critical Patients</p></div>
  </div>
  <div class="stat-card blue">
    <div class="stat-icon"><i class="fas fa-lungs"></i></div>
    <div class="stat-info"><h3><?= $stats['oxygen'] ?></h3><p>Patients on Oxygen</p></div>
  </div>
  <div class="stat-card green">
    <div class="stat-icon"><i class="fas fa-door-open"></i></div>
    <div class="stat-info"><h3><?= $stats['discharges'] ?></h3><p>Discharges Today</p></div>
  </div>
  <div class="stat-card red">
    <div class="stat-icon"><i class="fas fa-heart-broken"></i></div>
    <div class="stat-info"><h3><?= $stats['deaths'] ?></h3><p>Deaths Today</p></div>
  </div>
</div>

<!-- ── Two-column grid: Ward Activity + Sidebar ─── -->
<div style="display:grid;grid-template-columns:1fr 280px;gap:18px;align-items:start;">

  <div>
    <!-- Ward Activity Table -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-hospital"></i> Ward Activity &mdash; Today</div>
        <div style="display:flex;gap:8px;align-items:center;">
          <span class="badge badge-teal">Last 7 Days</span>
          <a href="<?= APP_URL ?>/ward_reports/report.php" class="btn btn-teal btn-sm"><i class="fas fa-plus"></i> Add Report</a>
        </div>
      </div>
      <div class="table-responsive">
        <table>
          <thead>
            <tr>
              <th>Ward</th>
              <th>Day Adm.</th>
              <th>Night Adm.</th>
              <th>Critical</th>
              <th>Oxygen</th>
              <th>Discharges</th>
              <th>Deaths</th>
              <th>Capacity</th>
            </tr>
          </thead>
          <tbody>
          <?php while ($wr = $wardReports->fetch_assoc()):
            $pct = $wr['total_beds'] > 0 ? round($wr['occupied_beds'] / $wr['total_beds'] * 100) : 0;
            $capColor = $pct >= 85 ? '#E24B4A' : ($pct >= 65 ? '#EF9F27' : '#1D9E75');
          ?>
          <tr>
            <td><strong><?= htmlspecialchars($wr['ward_name']) ?></strong></td>
            <td><?= $wr['day_admissions'] !== null ? "<span class='badge badge-teal'>{$wr['day_admissions']}</span>" : '<span style="color:#ccc">—</span>' ?></td>
            <td><?= $wr['night_admissions'] !== null ? "<span class='badge badge-info'>{$wr['night_admissions']}</span>" : '<span style="color:#ccc">—</span>' ?></td>
            <td><?php
              if ($wr['critical_cases'] !== null):
                $cls = $wr['critical_cases'] > 3 ? 'badge-danger' : ($wr['critical_cases'] > 0 ? 'badge-warning' : 'badge-navy');
                echo "<span class='badge $cls'>{$wr['critical_cases']}</span>";
              else: echo '<span style="color:#ccc">—</span>'; endif; ?></td>
            <td><?= $wr['on_oxygen'] !== null ? "<span class='badge badge-info'>{$wr['on_oxygen']}</span>" : '<span style="color:#ccc">—</span>' ?></td>
            <td><?= $wr['discharges'] !== null ? "<span class='badge badge-success'>{$wr['discharges']}</span>" : '<span style="color:#ccc">—</span>' ?></td>
            <td><?= $wr['deaths'] !== null ? "<span class='badge ".($wr['deaths']>0?'badge-danger':'badge-navy')."'>{$wr['deaths']}</span>" : '<span style="color:#ccc">—</span>' ?></td>
            <td>
              <div style="display:flex;align-items:center;gap:6px;">
                <div class="cap-bar" style="width:56px;"><div class="cap-fill" style="width:<?= $pct ?>%;background:<?= $capColor ?>;"></div></div>
                <span style="font-size:11px;color:var(--text-muted);min-width:28px;"><?= $pct ?>%</span>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Activity Trends Chart -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-chart-line"></i> Activity Trends &mdash; Last 7 Days</div>
        <div style="display:flex;gap:14px;font-size:11px;align-items:center;color:var(--text-muted);">
          <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:3px;background:#1D9E75;display:inline-block;border-radius:2px;"></span> Admissions</span>
          <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:3px;background:#378ADD;display:inline-block;border-radius:2px;"></span> Discharges</span>
          <span style="display:flex;align-items:center;gap:4px;"><span style="width:10px;height:3px;background:#E24B4A;display:inline-block;border-radius:2px;"></span> Critical</span>
        </div>
      </div>
      <div class="chart-wrap">
        <canvas id="trendChart" role="img" aria-label="Line chart showing 7-day ward activity trends">Activity trend data.</canvas>
      </div>
    </div>
  </div>

  <!-- Right sidebar -->
  <div style="display:flex;flex-direction:column;gap:16px;">

    <!-- AI Insights -->
    <div class="card">
      <div class="card-header">
        <div class="card-title"><i class="fas fa-brain"></i> AI Insights</div>
        <a href="<?= APP_URL ?>/ai/ai_insights.php" class="btn btn-outline btn-sm">View All</a>
      </div>
      <?php if ($insights->num_rows > 0): while ($ins = $insights->fetch_assoc()):
        $iclass = match($ins['severity']) { 'critical'=>'critical', 'warning'=>'warn', default=>'ok' };
        $iicon  = match($ins['severity']) { 'critical'=>'exclamation-circle','warning'=>'exclamation-triangle', default=>'check-circle' };
        $icolor = match($ins['severity']) { 'critical'=>'var(--red)','warning'=>'var(--amber)', default=>'var(--green)' };
      ?>
      <div class="insight-item">
        <div class="insight-icon <?= $iclass ?>">
          <i class="fas fa-<?= $iicon ?>" style="font-size:12px;color:<?= $icolor ?>;"></i>
        </div>
        <div class="insight-text"><?= htmlspecialchars($ins['insight']) ?></div>
      </div>
      <?php endwhile; else: ?>
      <p style="font-size:0.82rem;color:var(--text-muted);padding:10px 0;">No insights yet. Submit daily ward reports to enable AI analysis.</p>
      <?php endif; ?>
    </div>

    <!-- Quick Actions -->
    <div class="card">
      <div class="card-title" style="margin-bottom:14px;"><i class="fas fa-bolt"></i> Quick Actions</div>
      <div style="display:flex;flex-direction:column;gap:8px;">
        <a href="<?= APP_URL ?>/ward_reports/report.php" class="btn btn-primary"><i class="fas fa-file-medical-alt"></i> Add Ward Report</a>
        <a href="<?= APP_URL ?>/modules/patients.php"    class="btn btn-teal"><i class="fas fa-procedures"></i> Record Admission</a>
        <a href="<?= APP_URL ?>/modules/wards.php"       class="btn btn-warning"><i class="fas fa-bed"></i> Update Ward Beds</a>
        <a href="<?= APP_URL ?>/reports/reports.php"     class="btn btn-outline"><i class="fas fa-chart-bar"></i> Generate Report</a>
        <a href="<?= APP_URL ?>/ai/symptom_checker.php"  class="btn btn-outline"><i class="fas fa-robot"></i> AI Symptom Check</a>
      </div>
    </div>

  </div>
</div><!-- end grid -->

</div><!-- end .page-body -->

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
const trendData = <?= $trendJson ?>;
new Chart(document.getElementById('trendChart'), {
  type: 'line',
  data: {
    labels: trendData.map(d => d.label),
    datasets: [
      { label:'Admissions', data:trendData.map(d=>d.adm), borderColor:'#1D9E75', backgroundColor:'rgba(29,158,117,0.08)', tension:0.4, fill:true, pointRadius:3, pointBackgroundColor:'#1D9E75', borderWidth:2 },
      { label:'Discharges', data:trendData.map(d=>d.dis), borderColor:'#378ADD', backgroundColor:'rgba(55,138,221,0.05)', tension:0.4, fill:true, pointRadius:3, pointBackgroundColor:'#378ADD', borderWidth:2, borderDash:[4,3] },
      { label:'Critical',   data:trendData.map(d=>d.crit),borderColor:'#E24B4A', backgroundColor:'rgba(226,75,74,0.05)',   tension:0.4, fill:false,pointRadius:3, pointBackgroundColor:'#E24B4A', borderWidth:2, borderDash:[6,3] }
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{display:false}, tooltip:{mode:'index',intersect:false} },
    scales:{
      x:{ grid:{color:'rgba(0,0,0,0.04)'}, ticks:{font:{size:11},color:'#9ca3af'} },
      y:{ grid:{color:'rgba(0,0,0,0.04)'}, ticks:{font:{size:11},color:'#9ca3af'}, beginAtZero:true }
    }
  }
});
</script>

<?php include 'includes/footer.php'; ?>
