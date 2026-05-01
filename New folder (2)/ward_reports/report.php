<?php
require_once '../includes/config.php';
requireLogin();
sessionTimeout();

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wid    = (int)$_POST['ward_id'];
    $date   = clean($conn, $_POST['report_date'] ?? date('Y-m-d'));
    $dayAdm = (int)$_POST['day_admissions'];
    $nightAdm=(int)$_POST['night_admissions'];
    $crit   = (int)$_POST['critical_cases'];
    $oxy    = (int)$_POST['on_oxygen'];
    $disc   = (int)$_POST['discharges'];
    $deaths = (int)$_POST['deaths'];
    $notes  = clean($conn, $_POST['notes'] ?? '');
    $uid    = (int)$_SESSION['user_id'];

    if (!$wid) {
        $msg = flash('danger', 'Please select a ward.');
    } else {
        // Update ward occupied beds based on admissions minus discharges
        $conn->query("UPDATE wards SET occupied_beds = GREATEST(0, occupied_beds + $dayAdm + $nightAdm - $disc - $deaths) WHERE id=$wid");

        $stmt = $conn->prepare("INSERT INTO ward_daily_reports (ward_id,report_date,day_admissions,night_admissions,critical_cases,on_oxygen,discharges,deaths,notes,submitted_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE day_admissions=VALUES(day_admissions),night_admissions=VALUES(night_admissions),
            critical_cases=VALUES(critical_cases),on_oxygen=VALUES(on_oxygen),discharges=VALUES(discharges),
            deaths=VALUES(deaths),notes=VALUES(notes),submitted_by=VALUES(submitted_by)");
        $stmt->bind_param('isiiiiiiisi', $wid, $date, $dayAdm, $nightAdm, $crit, $oxy, $disc, $deaths, $notes, $uid);
        // fix bind: needs 10 params
        $stmt->close();

        $stmt2 = $conn->prepare("INSERT INTO ward_daily_reports (ward_id,report_date,day_admissions,night_admissions,critical_cases,on_oxygen,discharges,deaths,notes,submitted_by)
            VALUES (?,?,?,?,?,?,?,?,?,?)
            ON DUPLICATE KEY UPDATE day_admissions=VALUES(day_admissions),night_admissions=VALUES(night_admissions),
            critical_cases=VALUES(critical_cases),on_oxygen=VALUES(on_oxygen),discharges=VALUES(discharges),
            deaths=VALUES(deaths),notes=VALUES(notes),submitted_by=VALUES(submitted_by)");
        $stmt2->bind_param('isiiiiiiis', $wid, $date, $dayAdm, $nightAdm, $crit, $oxy, $disc, $deaths, $notes, $uid);
        // Correct param string
        $stmt2->close();

        // Correct approach
        $q = "INSERT INTO ward_daily_reports (ward_id,report_date,day_admissions,night_admissions,critical_cases,on_oxygen,discharges,deaths,notes,submitted_by)
              VALUES ($wid,'$date',$dayAdm,$nightAdm,$crit,$oxy,$disc,$deaths,'$notes',$uid)
              ON DUPLICATE KEY UPDATE
              day_admissions=$dayAdm, night_admissions=$nightAdm, critical_cases=$crit,
              on_oxygen=$oxy, discharges=$disc, deaths=$deaths, notes='$notes', submitted_by=$uid";

        if ($conn->query($q)) {
            // Auto-generate AI insight if critical or high mortality
            if ($crit >= 5) {
                $wName = $conn->query("SELECT ward_name FROM wards WHERE id=$wid")->fetch_row()[0];
                $ins = "High critical case load detected in {$wName} ({$crit} cases on {$date}). Immediate staffing review recommended.";
                $conn->query("INSERT INTO ai_insights (insight,severity,ward_id) VALUES ('$ins','critical',$wid)");
            }
            $msg = flash('success', "Ward report saved successfully for $date.");
        } else {
            $msg = flash('danger', 'Error: ' . htmlspecialchars($conn->error));
        }
    }
}

$wards   = $conn->query("SELECT * FROM wards ORDER BY ward_name");
$history = $conn->query("
    SELECT wr.*, w.ward_name, u.full_name AS submitted_by_name
    FROM ward_daily_reports wr
    JOIN wards w ON wr.ward_id=w.id
    LEFT JOIN users u ON wr.submitted_by=u.id
    ORDER BY wr.report_date DESC, w.ward_name
    LIMIT 50
");
?>
<?php include '../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title"><i class="fas fa-file-medical"></i> Daily Ward Reports</div>
  <div class="topbar-date"><i class="fas fa-calendar"></i> <?= date('d M Y') ?></div>
</div>

<div class="page-body">
<?= $msg ?>

<!-- ── Submit Form ─────────────────────────────── -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-plus-circle"></i> Submit Ward Report</div>
  </div>
  <form method="POST">
    <div class="form-grid">
      <div class="form-group">
        <label>Ward <span style="color:var(--red)">*</span></label>
        <select name="ward_id" required>
          <option value="">-- Select Ward --</option>
          <?php while ($w = $wards->fetch_assoc()): ?>
          <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['ward_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Report Date</label>
        <input type="date" name="report_date" value="<?= date('Y-m-d') ?>" max="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label>Day Admissions</label>
        <input type="number" name="day_admissions" min="0" value="0" required>
      </div>
      <div class="form-group">
        <label>Night Admissions</label>
        <input type="number" name="night_admissions" min="0" value="0" required>
      </div>
      <div class="form-group">
        <label>Critical Cases</label>
        <input type="number" name="critical_cases" min="0" value="0" required>
      </div>
      <div class="form-group">
        <label>Patients on Oxygen</label>
        <input type="number" name="on_oxygen" min="0" value="0" required>
      </div>
      <div class="form-group">
        <label>Discharges</label>
        <input type="number" name="discharges" min="0" value="0" required>
      </div>
      <div class="form-group">
        <label>Deaths</label>
        <input type="number" name="deaths" min="0" value="0" required>
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label>Notes / Observations</label>
        <textarea name="notes" placeholder="Any relevant clinical observations, equipment issues, staffing concerns…"></textarea>
      </div>
    </div>
    <div class="mt-2">
      <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Report</button>
    </div>
  </form>
</div>

<!-- ── Report History ──────────────────────────── -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-history"></i> Recent Reports</div>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>Date</th><th>Ward</th><th>Day Adm.</th><th>Night Adm.</th><th>Critical</th><th>Oxygen</th><th>Discharges</th><th>Deaths</th><th>Submitted By</th></tr>
      </thead>
      <tbody>
      <?php if ($history->num_rows > 0): while ($h = $history->fetch_assoc()): ?>
        <tr>
          <td><?= date('d M Y', strtotime($h['report_date'])) ?></td>
          <td><strong><?= htmlspecialchars($h['ward_name']) ?></strong></td>
          <td><span class="badge badge-teal"><?= $h['day_admissions'] ?></span></td>
          <td><span class="badge badge-info"><?= $h['night_admissions'] ?></span></td>
          <td><span class="badge <?= $h['critical_cases']>3?'badge-danger':($h['critical_cases']>0?'badge-warning':'badge-navy') ?>"><?= $h['critical_cases'] ?></span></td>
          <td><?= $h['on_oxygen'] ?></td>
          <td><span class="badge badge-success"><?= $h['discharges'] ?></span></td>
          <td><span class="badge <?= $h['deaths']>0?'badge-danger':'badge-navy' ?>"><?= $h['deaths'] ?></span></td>
          <td><?= htmlspecialchars($h['submitted_by_name'] ?? '—') ?></td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="9"><div class="empty-state"><i class="fas fa-file-medical"></i><h3>No reports yet</h3><p>Submit the first ward report using the form above.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
