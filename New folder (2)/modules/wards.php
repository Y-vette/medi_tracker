<?php
require_once '../includes/config.php';
requireLogin();
sessionTimeout();
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $name = clean($conn, $_POST['ward_name'] ?? '');
        $beds = (int)$_POST['total_beds'];
        $type = clean($conn, $_POST['ward_type'] ?? 'general');
        if (!$name || $beds < 1) { $msg = flash('danger', 'Ward name and at least 1 bed required.'); }
        else {
            $conn->query("INSERT INTO wards (ward_name,total_beds,ward_type) VALUES ('$name',$beds,'$type')");
            $msg = $conn->error ? flash('danger','Error: '.htmlspecialchars($conn->error)) : flash('success', "Ward '{$name}' added!");
        }
    } elseif ($action === 'update') {
        $wid = (int)$_POST['ward_id'];
        $occ = (int)$_POST['occupied_beds'];
        $row = $conn->query("SELECT total_beds FROM wards WHERE id=$wid")->fetch_assoc();
        if ($row) {
            $occ = max(0, min($occ, (int)$row['total_beds']));
            $conn->query("UPDATE wards SET occupied_beds=$occ WHERE id=$wid");
            $msg = flash('success', 'Ward updated!');
        }
    } elseif ($action === 'delete') {
        $wid = (int)$_POST['ward_id'];
        $conn->query("DELETE FROM wards WHERE id=$wid");
        $msg = flash('success', 'Ward removed.');
    }
}

$wards   = $conn->query("SELECT * FROM wards ORDER BY ward_name");
$totBeds = (int)($conn->query("SELECT SUM(total_beds) FROM wards")->fetch_row()[0] ?? 0);
$occBeds = (int)($conn->query("SELECT SUM(occupied_beds) FROM wards")->fetch_row()[0] ?? 0);
$freeBeds= $totBeds - $occBeds;
?>
<?php include '../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title"><i class="fas fa-bed"></i> Wards &amp; Bed Management</div>
  <div class="topbar-date"><i class="fas fa-calendar"></i> <?= date('d M Y') ?></div>
</div>

<div class="page-body">
<?= $msg ?>

<div class="stats-grid" style="grid-template-columns:repeat(3,1fr);max-width:580px;">
  <div class="stat-card blue">  <div class="stat-icon"><i class="fas fa-bed"></i></div>         <div class="stat-info"><h3><?= $totBeds ?></h3><p>Total Beds</p></div></div>
  <div class="stat-card amber"> <div class="stat-icon"><i class="fas fa-procedures"></i></div>   <div class="stat-info"><h3><?= $occBeds ?></h3><p>Occupied</p></div></div>
  <div class="stat-card teal">  <div class="stat-icon"><i class="fas fa-check-circle"></i></div> <div class="stat-info"><h3><?= $freeBeds ?></h3><p>Available</p></div></div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-plus-circle"></i> Add New Ward</div></div>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <div class="form-grid" style="grid-template-columns:1fr 1fr 1fr;">
      <div class="form-group">
        <label>Ward Name <span style="color:var(--red)">*</span></label>
        <input type="text" name="ward_name" placeholder="e.g. Female Surgical" required>
      </div>
      <div class="form-group">
        <label>Total Beds <span style="color:var(--red)">*</span></label>
        <input type="number" name="total_beds" min="1" placeholder="20" required>
      </div>
      <div class="form-group">
        <label>Ward Type</label>
        <select name="ward_type">
          <option value="general">General</option>
          <option value="icu">ICU</option>
          <option value="maternity">Maternity</option>
          <option value="paediatric">Paediatric</option>
          <option value="surgical">Surgical</option>
          <option value="tb">TB Ward</option>
          <option value="other">Other</option>
        </select>
      </div>
    </div>
    <div class="mt-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Ward</button></div>
  </form>
</div>

<div class="card">
  <div class="card-title" style="margin-bottom:18px;"><i class="fas fa-hospital"></i> Ward Overview</div>
  <div class="ward-grid">
  <?php
  $wards = $conn->query("SELECT * FROM wards ORDER BY ward_name");
  while ($w = $wards->fetch_assoc()):
    $pct    = $w['total_beds'] > 0 ? round($w['occupied_beds'] / $w['total_beds'] * 100) : 0;
    $color  = $pct >= 85 ? 'var(--red-mid)' : ($pct >= 65 ? 'var(--amber-mid)' : 'var(--teal-mid)');
    $badge  = $pct >= 85 ? 'badge-danger' : ($pct >= 65 ? 'badge-warning' : 'badge-success');
  ?>
  <div class="ward-card" style="border-top-color:<?= $color ?>;">
    <div class="wc-name"><i class="fas fa-door-open" style="color:var(--teal-mid);margin-right:5px;"></i><?= htmlspecialchars($w['ward_name']) ?></div>
    <div style="display:flex;align-items:baseline;gap:6px;margin:8px 0 2px;">
      <div class="wc-count" style="color:<?= $color ?>"><?= $w['occupied_beds'] ?></div>
      <span style="font-size:0.9rem;color:var(--text-muted);">/ <?= $w['total_beds'] ?> beds</span>
    </div>
    <span class="badge <?= $badge ?>"><?= $pct ?>% occupied</span>
    <div class="prob-bar" style="margin:10px 0;">
      <div class="prob-fill" style="width:<?= $pct ?>%;background:<?= $color ?>;"></div>
    </div>
    <form method="POST" class="flex-gap" style="margin-top:8px;">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="ward_id" value="<?= $w['id'] ?>">
      <input type="number" name="occupied_beds" value="<?= $w['occupied_beds'] ?>" min="0" max="<?= $w['total_beds'] ?>"
             style="width:65px;padding:5px 8px;border-radius:6px;border:1.5px solid var(--border);font-size:0.82rem;">
      <button type="submit" class="btn btn-teal btn-sm">Update</button>
    </form>
    <form method="POST" style="margin-top:6px;">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="ward_id" value="<?= $w['id'] ?>">
      <button type="submit" class="btn btn-danger btn-sm"
              onclick="return confirm('Remove ward <?= htmlspecialchars($w['ward_name'], ENT_QUOTES) ?>?')">
        <i class="fas fa-trash"></i> Remove
      </button>
    </form>
  </div>
  <?php endwhile; ?>
  </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
