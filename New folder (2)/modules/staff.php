<?php
require_once '../includes/config.php';
requireLogin();
sessionTimeout();
requireRole('admin');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $un   = clean($conn, $_POST['username']  ?? '');
        $fn   = clean($conn, $_POST['full_name'] ?? '');
        $em   = clean($conn, $_POST['email']     ?? '');
        $ph   = clean($conn, $_POST['phone']     ?? '');
        $role = clean($conn, $_POST['role']      ?? 'nurse');
        $pw   = $_POST['password'] ?? '';
        if (!$un || !$fn || !$pw) { $msg = flash('danger','Username, name and password are required.'); }
        else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $conn->query("INSERT INTO users (username,password,role,full_name,email,phone) VALUES ('$un','$hash','$role','$fn','$em','$ph')");
            $msg = $conn->error ? flash('danger','Error: '.htmlspecialchars($conn->error)) : flash('success',"Staff member '{$fn}' added!");
        }
    } elseif ($action === 'delete') {
        $uid = (int)$_POST['user_id'];
        if ($uid === (int)$_SESSION['user_id']) { $msg = flash('danger','You cannot delete your own account.'); }
        else { $conn->query("DELETE FROM users WHERE id=$uid"); $msg = flash('success','Staff member removed.'); }
    }
}

$staff = $conn->query("SELECT * FROM users ORDER BY role, full_name");
?>
<?php include '../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title"><i class="fas fa-user-md"></i> Staff Management</div>
  <div class="topbar-date"><i class="fas fa-calendar"></i> <?= date('d M Y') ?></div>
</div>

<div class="page-body">
<?= $msg ?>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-user-plus"></i> Add Staff Member</div></div>
  <form method="POST">
    <input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="form-group">
        <label>Full Name <span style="color:var(--red)">*</span></label>
        <input type="text" name="full_name" placeholder="Dr. Anna Nghipundeka" required>
      </div>
      <div class="form-group">
        <label>Username <span style="color:var(--red)">*</span></label>
        <input type="text" name="username" placeholder="anna.nghipundeka" required>
      </div>
      <div class="form-group">
        <label>Role</label>
        <select name="role">
          <option value="nurse">Nurse</option>
          <option value="doctor">Doctor</option>
          <option value="hio">Health Information Officer</option>
          <option value="receptionist">Receptionist</option>
          <option value="admin">Administrator</option>
        </select>
      </div>
      <div class="form-group">
        <label>Password <span style="color:var(--red)">*</span></label>
        <input type="password" name="password" placeholder="Minimum 8 characters" required>
      </div>
      <div class="form-group">
        <label>Email</label>
        <input type="email" name="email" placeholder="staff@hospital.na">
      </div>
      <div class="form-group">
        <label>Phone</label>
        <input type="tel" name="phone" placeholder="+264 81 000 0000">
      </div>
    </div>
    <div class="mt-2"><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Add Staff</button></div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-users"></i> All Staff (<?= $staff->num_rows ?>)</div>
  </div>
  <div class="table-responsive">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Username</th><th>Role</th><th>Email</th><th>Phone</th><th>Added</th><th>Action</th></tr></thead>
      <tbody>
      <?php while ($u = $staff->fetch_assoc()):
        $rbadge = match($u['role']) { 'admin'=>'badge-danger','doctor'=>'badge-info','nurse'=>'badge-teal','hio'=>'badge-warning', default=>'badge-navy' };
      ?>
        <tr>
          <td><?= $u['id'] ?></td>
          <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
          <td><code style="font-size:0.78rem;background:#f3f4f6;padding:2px 6px;border-radius:4px;"><?= htmlspecialchars($u['username']) ?></code></td>
          <td><span class="badge <?= $rbadge ?>"><?= ucfirst($u['role']) ?></span></td>
          <td><?= htmlspecialchars($u['email'] ?? '—') ?></td>
          <td><?= htmlspecialchars($u['phone'] ?? '—') ?></td>
          <td><?= date('d M Y', strtotime($u['created_at'])) ?></td>
          <td>
            <?php if ($u['id'] !== (int)$_SESSION['user_id']): ?>
            <form method="POST" style="display:inline;">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm btn-icon" title="Remove"
                      onclick="return confirm('Remove <?= htmlspecialchars($u['full_name'],ENT_QUOTES) ?>?')">
                <i class="fas fa-trash"></i>
              </button>
            </form>
            <?php else: ?><span style="font-size:0.74rem;color:var(--text-muted);">You</span><?php endif; ?>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

</div>
<?php include '../includes/footer.php'; ?>
