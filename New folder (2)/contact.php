<?php require_once 'includes/config.php'; requireLogin(); sessionTimeout(); ?>
<?php include 'includes/header.php'; ?>
<div class="topbar"><div class="topbar-title"><i class="fas fa-headset"></i> Help &amp; Contact</div></div>
<div class="page-body">

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px;">
  <div class="card">
    <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-book"></i> Quick Guide</div>
    <div style="font-size:0.86rem;color:var(--text-muted);line-height:1.9;">
      <p style="margin-bottom:12px;"><strong style="color:var(--text);">1. Submit Daily Ward Reports</strong><br>
        Go to <em>Ward Reports &rarr; Daily Reports</em> and fill in admissions, discharges, critical cases, oxygen count and deaths for each ward every shift.</p>
      <p style="margin-bottom:12px;"><strong style="color:var(--text);">2. Monitor the Dashboard</strong><br>
        The dashboard auto-refreshes ward stats and shows live capacity, trends and AI alerts.</p>
      <p style="margin-bottom:12px;"><strong style="color:var(--text);">3. Run AI Insights</strong><br>
        After submitting reports, go to <em>AI Insights</em> and click <em>Generate Insights</em> to get AI-powered alerts and recommendations.</p>
      <p style="margin-bottom:12px;"><strong style="color:var(--text);">4. Use the Symptom Checker</strong><br>
        Go to <em>AI Symptom Check</em> and select a patient's symptoms to get likely condition suggestions and urgency levels.</p>
      <p><strong style="color:var(--text);">5. Generate Reports</strong><br>
        Go to <em>Analytics</em>, set a date range, and print or review ward performance statistics.</p>
    </div>
  </div>

  <div class="card">
    <div class="card-title" style="margin-bottom:16px;"><i class="fas fa-envelope"></i> Support Contact</div>
    <div style="font-size:0.86rem;line-height:2;">
      <p><i class="fas fa-user" style="color:var(--teal-mid);width:18px;"></i> <strong>System Administrator</strong></p>
      <p><i class="fas fa-envelope" style="color:var(--teal-mid);width:18px;"></i> admin@meditracker.na</p>
      <p><i class="fas fa-phone" style="color:var(--teal-mid);width:18px;"></i> +264 61 000 0000</p>
      <p><i class="fas fa-map-marker-alt" style="color:var(--teal-mid);width:18px;"></i> Windhoek, Namibia</p>
      <hr style="border:none;border-top:1px solid var(--border);margin:16px 0;">
      <p style="color:var(--text-muted);font-size:0.8rem;">
        Meditracker is an AI-powered hospital ward monitoring system designed for Namibian healthcare facilities.<br><br>
        For technical issues, please contact your hospital's Health Information Officer (HIO) or IT department.
      </p>
    </div>
    <div style="margin-top:16px;">
      <div class="alert alert-info" style="margin-bottom:0;">
        <i class="fas fa-info-circle"></i>
        <strong>Default credentials:</strong> Username: <code>admin</code> &nbsp;|&nbsp; Password: <code>Admin@123</code> — change after first login.
      </div>
    </div>
  </div>
</div>

</div>
<?php include 'includes/footer.php'; ?>
