<?php
require_once '../includes/config.php';
requireLogin();
sessionTimeout();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add') {
    $n   = clean($conn, $_POST['full_name']     ?? '');
    $id  = clean($conn, $_POST['id_number']     ?? '');
    $dob = clean($conn, $_POST['date_of_birth'] ?? '');
    $g   = clean($conn, $_POST['gender']        ?? 'Male');
    $ph  = clean($conn, $_POST['phone']         ?? '');
    $adr = clean($conn, $_POST['address']       ?? '');
    $wid = (int)$_POST['ward_id'];
    $adm = clean($conn, $_POST['admission_date'] ?? date('Y-m-d'));
    $sta = clean($conn, $_POST['status']        ?? 'Admitted');
    $oxy = isset($_POST['on_oxygen']) ? 1 : 0;
    $dia = clean($conn, $_POST['diagnosis']     ?? '');

    if (!$n) { $msg = flash('danger','Full name is required.'); }
    else {
        $conn->query("INSERT INTO patients (full_name,id_number,date_of_birth,gender,phone,address,ward_id,admission_date,status,on_oxygen,diagnosis)
            VALUES ('$n','$id','$dob','$g','$ph','$adr'," . ($wid?$wid:'NULL') . ",'$adm','$sta',$oxy,'$dia')");
        // Update ward occupied count
        if ($wid && in_array($sta, ['Admitted','Critical'])) {
            $conn->query("UPDATE wards SET occupied_beds = LEAST(total_beds, occupied_beds+1) WHERE id=$wid");
        }
        $msg = $conn->error ? flash('danger','Error: '.htmlspecialchars($conn->error)) : flash('success',"Patient '{$n}' admitted successfully!");
    }
}

if (isset($_GET['discharge']) && is_numeric($_GET['discharge'])) {
    $pid = (int)$_GET['discharge'];
    $p   = $conn->query("SELECT ward_id FROM patients WHERE id=$pid")->fetch_assoc();
    $conn->query("UPDATE patients SET status='Discharged' WHERE id=$pid");
    if ($p && $p['ward_id']) $conn->query("UPDATE wards SET occupied_beds=GREATEST(0,occupied_beds-1) WHERE id={$p['ward_id']}");
    header("Location: patients.php?msg=discharged"); exit();
}
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $pid = (int)$_GET['delete'];
    $p   = $conn->query("SELECT ward_id,status FROM patients WHERE id=$pid")->fetch_assoc();
    $conn->query("DELETE FROM patients WHERE id=$pid");
    if ($p && $p['ward_id'] && in_array($p['status'],['Admitted','Critical'])) $conn->query("UPDATE wards SET occupied_beds=GREATEST(0,occupied_beds-1) WHERE id={$p['ward_id']}");
    header("Location: patients.php?msg=deleted"); exit();
}

$search = clean($conn, $_GET['search'] ?? '');
$filter = clean($conn, $_GET['filter'] ?? '');
$sql = "SELECT p.*, w.ward_name FROM patients p LEFT JOIN wards w ON p.ward_id=w.id WHERE 1=1";
if ($search) $sql .= " AND (p.full_name LIKE '%$search%' OR p.id_number LIKE '%$search%')";
if ($filter) $sql .= " AND p.status='$filter'";
$sql .= " ORDER BY p.created_at DESC";
$patients = $conn->query($sql);
$wards    = $conn->query("SELECT * FROM wards ORDER BY ward_name");

$totalPat  = (int)$conn->query("SELECT COUNT(*) FROM patients")->fetch_row()[0];
$admitted  = (int)$conn->query("SELECT COUNT(*) FROM patients WHERE status='Admitted'")->fetch_row()[0];
$critical  = (int)$conn->query("SELECT COUNT(*) FROM patients WHERE status='Critical'")->fetch_row()[0];
$onOxy     = (int)$conn->query("SELECT COUNT(*) FROM patients WHERE on_oxygen=1 AND status IN ('Admitted','Critical')")->fetch_row()[0];
?>
<?php include '../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title"><i class="fas fa-procedures"></i> Patient Management</div>
  <div class="topbar-date"><i class="fas fa-calendar"></i> <?= date('d M Y') ?></div>
</div>

<div class="page-body">
<?= $msg ?>
<?php if (isset($_GET['msg'])): echo flash($_GET['msg']==='discharged'?'success':'success', $_GET['msg']==='discharged'?'Patient discharged.':'Patient deleted.'); endif; ?>

<div class="stats-grid" style="grid-template-columns:repeat(4,1fr);">
  <div class="stat-card teal"> <div class="stat-icon"><i class="fas fa-users"></i></div>        <div class="stat-info"><h3><?= $totalPat ?></h3><p>Total Patients</p></div></div>
  <div class="stat-card blue"> <div class="stat-icon"><i class="fas fa-bed"></i></div>           <div class="stat-info"><h3><?= $admitted ?></h3><p>Admitted</p></div></div>
  <div class="stat-card amber"><div class="stat-icon"><i class="fas fa-exclamation-triangle"></i></div><div class="stat-info"><h3><?= $critical ?></h3><p>Critical</p></div></div>
  <div class="stat-card blue"> <div class="stat-icon"><i class="fas fa-lungs"></i></div>         <div class="stat-info"><h3><?= $onOxy ?></h3><p>On Oxygen</p></div></div>
</div>

<!-- Add Patient -->
<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-user-plus"></i> Record New Admission</div></div>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name <span style="color:var(--red)">*</span></label>
        <input type="text" name="full_name" placeholder="e.g. Anna Shipanga" required>
      </div>
      <div class="form-group">
        <label>ID Number</label>
        <input type="text" name="id_number" placeholder="e.g. 98030400123">
      </div>
      <div class="form-group">
        <label>Date of Birth</label>
        <input type="date" name="date_of_birth">
      </div>
      <div class="form-group">
        <label>Gender</label>
        <select name="gender"><option>Male</option><option>Female</option><option>Other</option></select>
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" placeholder="+264 81 000 0000">
      </div>
      <div class="form-group">
        <label>Ward</label>
        <select name="ward_id">
          <option value="">-- Unassigned --</option>
          <?php $wards->data_seek(0); while ($w = $wards->fetch_assoc()): ?>
          <option value="<?= $w['id'] ?>"><?= htmlspecialchars($w['ward_name']) ?></option>
          <?php endwhile; ?>
        </select>
      </div>
      <div class="form-group">
        <label>Admission Date</label>
        <input type="date" name="admission_date" value="<?= date('Y-m-d') ?>">
      </div>
      <div class="form-group">
        <label>Status</label>
        <select name="status">
          <option value="Admitted">Admitted</option>
          <option value="Critical">Critical</option>
        </select>
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label>Diagnosis / Presenting Complaint</label>
        <input type="text" name="diagnosis" placeholder="e.g. Malaria, Pneumonia, Hypertension…">
      </div>
      <div class="form-group" style="grid-column:1/-1;">
        <label>Address</label>
        <textarea name="address" placeholder="Street, suburb, town…" style="min-height:60px;"></textarea>
      </div>
      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
          <input type="checkbox" name="on_oxygen" value="1" style="width:16px;height:16px;accent-color:var(--teal-mid);">
          Patient on Oxygen
        </label>
      </div>
    </div>
    <div class="mt-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Record Admission</button></div>
  </form>
</div>

<!-- Patient List -->
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-list"></i> Patient Records (<?= $patients->num_rows ?>)</div>
    <form method="GET" class="flex-gap">
      <input type="text" name="search" class="search-bar" placeholder="Search name or ID…" value="<?= htmlspecialchars($search) ?>">
      <select name="filter" style="padding:7px 11px;border-radius:7px;border:1.5px solid var(--border);font-size:0.83rem;font-family:inherit;" onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="Admitted"  <?= $filter==='Admitted'  ?'selected':'' ?>>Admitted</option>
        <option value="Critical"  <?= $filter==='Critical'  ?'selected':'' ?>>Critical</option>
        <option value="Discharged"<?= $filter==='Discharged'?'selected':'' ?>>Discharged</option>
        <option value="Deceased"  <?= $filter==='Deceased'  ?'selected':'' ?>>Deceased</option>
      </select>
      <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
      <?php if ($search||$filter): ?><a href="patients.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Clear</a><?php endif; ?>
    </form>
  </div>
  <div class="table-responsive">
    <table>
      <thead>
        <tr><th>#</th><th>Name</th><th>ID No.</th><th>Gender</th><th>Ward</th><th>Diagnosis</th><th>Status</th><th>Oxygen</th><th>Admitted</th><th>Actions</th></tr>
      </thead>
      <tbody>
      <?php if ($patients->num_rows > 0): while ($p = $patients->fetch_assoc()):
        $sbadge = match($p['status']) { 'Critical'=>'badge-danger','Discharged'=>'badge-success','Deceased'=>'badge-navy', default=>'badge-info' };
      ?>
        <tr>
          <td><?= $p['id'] ?></td>
          <td><strong><?= htmlspecialchars($p['full_name']) ?></strong></td>
          <td><?= htmlspecialchars($p['id_number'] ?? '—') ?></td>
          <td><?= $p['gender'] ?></td>
          <td><?= htmlspecialchars($p['ward_name'] ?? '—') ?></td>
          <td><?= htmlspecialchars($p['diagnosis'] ?? '—') ?></td>
          <td><span class="badge <?= $sbadge ?>"><?= $p['status'] ?></span></td>
          <td><?= $p['on_oxygen'] ? '<span class="badge badge-info"><i class="fas fa-lungs"></i> Yes</span>' : '—' ?></td>
          <td><?= $p['admission_date'] ? date('d M Y', strtotime($p['admission_date'])) : '—' ?></td>
          <td>
            <div class="flex-gap">
              <?php if ($p['status']==='Admitted'||$p['status']==='Critical'): ?>
              <a href="?discharge=<?= $p['id'] ?>" class="btn btn-success btn-sm btn-icon" title="Discharge" onclick="return confirm('Discharge this patient?')"><i class="fas fa-door-open"></i></a>
              <?php endif; ?>
              <a href="?delete=<?= $p['id'] ?>" class="btn btn-danger btn-sm btn-icon" title="Delete" onclick="return confirm('Delete patient record?')"><i class="fas fa-trash"></i></a>
            </div>
          </td>
        </tr>
      <?php endwhile; else: ?>
        <tr><td colspan="10"><div class="empty-state"><i class="fas fa-procedures"></i><h3>No patients found</h3><p>Register a patient using the form above.</p></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
