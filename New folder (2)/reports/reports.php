<?php
require_once '../includes/config.php';
requireLogin();
sessionTimeout();

$from = clean($conn, $_GET['from'] ?? date('Y-m-01'));
$to   = clean($conn, $_GET['to']   ?? date('Y-m-d'));
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');

// Summary KPIs
$totalAdm  = (int)($conn->query("SELECT SUM(day_admissions+night_admissions) FROM ward_daily_reports WHERE report_date BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0);
$totalDisc = (int)($conn->query("SELECT SUM(discharges) FROM ward_daily_reports WHERE report_date BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0);
$totalCrit = (int)($conn->query("SELECT SUM(critical_cases) FROM ward_daily_reports WHERE report_date BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0);
$totalDeaths=(int)($conn->query("SELECT SUM(deaths) FROM ward_daily_reports WHERE report_date BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0);
$totalOxy  = (int)($conn->query("SELECT SUM(on_oxygen) FROM ward_daily_reports WHERE report_date BETWEEN '$from' AND '$to'")->fetch_row()[0] ?? 0);
$activePatients=(int)($conn->query("SELECT COUNT(*) FROM patients WHERE status IN ('Admitted','Critical')")->fetch_row()[0] ?? 0);

// Per-ward breakdown
$wardBreak = $conn->query("
    SELECT w.ward_name,
           SUM(wr.day_admissions+wr.night_admissions) as total_adm,
           SUM(wr.discharges) as total_disc,
           SUM(wr.critical_cases) as total_crit,
           SUM(wr.deaths) as total_deaths,
           SUM(wr.on_oxygen) as total_oxy,
           w.total_beds, w.occupied_beds
    FROM wards w
    LEFT JOIN ward_daily_reports wr ON w.id=wr.ward_id AND wr.report_date BETWEEN '$from' AND '$to'
    GROUP BY w.id
    ORDER BY total_adm DESC
");

// Daily trend JSON for chart
$dailyTrend = [];
$res = $conn->query("SELECT report_date, SUM(day_admissions+night_admissions) as adm, SUM(discharges) as dis, SUM(critical_cases) as crit, SUM(deaths) as dths
    FROM ward_daily_reports WHERE report_date BETWEEN '$from' AND '$to'
    GROUP BY report_date ORDER BY report_date");
while ($r = $res->fetch_assoc()) $dailyTrend[] = $r;
$trendJson = json_encode($dailyTrend);
?>
<?php include '../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title"><i class="fas fa-chart-line"></i> Analytics &amp; Reports</div>
  <div class="topbar-right no-print">
    <button onclick="window.print()" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</button>
  </div>
</div>

<div class="page-body">

<!-- Date filter -->
<div class="card no-print">
  <div class="card-title" style="margin-bottom:14px;"><i class="fas fa-filter"></i> Date Range</div>
  <form method="GET" class="flex-gap">
    <div class="form-group"><label>From</label><input type="date" name="from" value="<?= $from ?>"></div>
    <div class="form-group"><label>To</label><input type="date" name="to" value="<?= $to ?>"></div>
    <div style="align-self:flex-end;">
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Apply</button>
      <a href="reports.php" class="btn btn-outline">Reset</a>
    </div>
  </form>
</div>

<p style="color:var(--text-muted);font-size:0.8rem;margin-bottom:16px;">
  Data from <strong><?= date('d M Y',strtotime($from)) ?></strong> to <strong><?= date('d M Y',strtotime($to)) ?></strong>
</p>

<!-- KPIs -->
<div class="stats-grid">
  <div class="stat-card teal">  <div class="stat-icon"><i class="fas fa-user-plus"></i></div>          <div class="stat-info"><h3><?= $totalAdm ?></h3>   <p>Total Admissions</p></div></div>
  <div class="stat-card green"> <div class="stat-icon"><i class="fas fa-door-open"></i></div>          <div class="stat-info"><h3><?= $totalDisc ?></h3>  <p>Discharges</p></div></div>
  <div class="stat-card amber"> <div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-info"><h3><?= $totalCrit ?></h3>  <p>Critical Cases</p></div></div>
  <div class="stat-card red">   <div class="stat-icon"><i class="fas fa-heart-broken"></i></div>       <div class="stat-info"><h3><?= $totalDeaths ?></h3><p>Deaths</p></div></div>
  <div class="stat-card blue">  <div class="stat-icon"><i class="fas fa-lungs"></i></div>              <div class="stat-info"><h3><?= $totalOxy ?></h3>   <p>Oxygen Patient Days</p></div></div>
  <div class="stat-card teal">  <div class="stat-icon"><i class="fas fa-bed"></i></div>                <div class="stat-info"><h3><?= $activePatients ?></h3><p>Currently Admitted</p></div></div>
</div>

<!-- Trend Chart -->
<?php if (count($dailyTrend) > 0): ?>
<div class="card">
  <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-chart-line"></i> Daily Trend</div>
  <div class="chart-wrap" style="height:240px;">
    <canvas id="reportChart" role="img" aria-label="Daily trend chart">Trend data.</canvas>
  </div>
</div>
<?php endif; ?>

<!-- Per-ward breakdown -->
<div class="card">
  <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-hospital"></i> Ward Breakdown</div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>Ward</th><th>Admissions</th><th>Discharges</th><th>Critical</th><th>Deaths</th><th>Oxygen Days</th><th>Current Occ.</th><th>Capacity</th></tr>
      </thead>
      <tbody>
      <?php while ($w = $wardBreak->fetch_assoc()):
        $pct = $w['total_beds'] > 0 ? round($w['occupied_beds'] / $w['total_beds'] * 100) : 0;
        $cc  = $pct>=85?'var(--red-mid)':($pct>=65?'var(--amber-mid)':'var(--teal-mid)');
      ?>
        <tr>
          <td><strong><?= htmlspecialchars($w['ward_name']) ?></strong></td>
          <td><span class="badge badge-teal"><?= (int)$w['total_adm'] ?></span></td>
          <td><span class="badge badge-success"><?= (int)$w['total_disc'] ?></span></td>
          <td><span class="badge <?= $w['total_crit']>3?'badge-danger':($w['total_crit']>0?'badge-warning':'badge-navy') ?>"><?= (int)$w['total_crit'] ?></span></td>
          <td><span class="badge <?= $w['total_deaths']>0?'badge-danger':'badge-navy' ?>"><?= (int)$w['total_deaths'] ?></span></td>
          <td><?= (int)$w['total_oxy'] ?></td>
          <td><?= $w['occupied_beds'] ?>/<?= $w['total_beds'] ?></td>
          <td>
            <div style="display:flex;align-items:center;gap:6px;">
              <div class="cap-bar" style="width:80px;"><div class="cap-fill" style="width:<?= $pct ?>%;background:<?= $cc ?>;"></div></div>
              <span style="font-size:11px;color:var(--text-muted);"><?= $pct ?>%</span>
            </div>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<script>
<?php if (count($dailyTrend) > 0): ?>
const td = <?= $trendJson ?>;
new Chart(document.getElementById('reportChart'), {
  type: 'bar',
  data: {
    labels: td.map(d => d.report_date),
    datasets: [
      { label:'Admissions', data:td.map(d=>d.adm),  backgroundColor:'rgba(29,158,117,0.7)', borderRadius:4 },
      { label:'Discharges', data:td.map(d=>d.dis),  backgroundColor:'rgba(55,138,221,0.7)', borderRadius:4 },
      { label:'Critical',   data:td.map(d=>d.crit), backgroundColor:'rgba(239,159,39,0.7)', borderRadius:4 },
      { label:'Deaths',     data:td.map(d=>d.dths), backgroundColor:'rgba(226,75,74,0.7)',  borderRadius:4 },
    ]
  },
  options: {
    responsive:true, maintainAspectRatio:false,
    plugins:{ legend:{position:'top',labels:{font:{size:11}}} },
    scales:{
      x:{ grid:{display:false}, ticks:{font:{size:10},color:'#9ca3af'} },
      y:{ grid:{color:'rgba(0,0,0,0.04)'}, ticks:{font:{size:11},color:'#9ca3af'}, beginAtZero:true }
    }
  }
});
<?php endif; ?>
</script>

<?php include '../includes/footer.php'; ?>
