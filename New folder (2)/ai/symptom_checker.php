<?php
require_once '../includes/config.php';
requireLogin();
sessionTimeout();

$disease_rules = [
    "Malaria" => [
        "symptoms" => ["fever","chills","headache","sweating","nausea","vomiting","muscle pain","fatigue"],
        "weight"   => [3,3,2,3,2,2,2,1],
        "info"     => "A mosquito-borne infectious disease common in Namibia. Seek immediate medical testing (RDT or microscopy).",
        "urgency"  => "HIGH"
    ],
    "Tuberculosis (TB)" => [
        "symptoms" => ["cough","night sweats","weight loss","fatigue","fever","chest pain","coughing blood"],
        "weight"   => [3,3,3,2,2,2,4],
        "info"     => "A bacterial infection common in Southern Africa. TB is treatable — arrange sputum testing immediately.",
        "urgency"  => "HIGH"
    ],
    "Pneumonia" => [
        "symptoms" => ["cough","fever","chest pain","shortness of breath","fatigue","chills","confusion"],
        "weight"   => [3,3,3,3,2,2,3],
        "info"     => "A serious lung infection requiring antibiotics. Admit patient and begin oxygen monitoring.",
        "urgency"  => "HIGH"
    ],
    "HIV/AIDS" => [
        "symptoms" => ["fatigue","fever","weight loss","night sweats","swollen glands","recurring infections","rash"],
        "weight"   => [2,2,3,3,3,3,2],
        "info"     => "HIV testing is recommended. Antiretroviral therapy (ART) is available at all state facilities in Namibia.",
        "urgency"  => "HIGH"
    ],
    "Hypertension" => [
        "symptoms" => ["headache","dizziness","blurred vision","chest pain","shortness of breath","nosebleed"],
        "weight"   => [2,2,3,3,2,2],
        "info"     => "High blood pressure. Measure BP immediately. Refer for medication review if uncontrolled.",
        "urgency"  => "MEDIUM"
    ],
    "Diabetes" => [
        "symptoms" => ["frequent urination","excessive thirst","fatigue","blurred vision","slow healing","weight loss","tingling"],
        "weight"   => [3,3,2,2,3,2,2],
        "info"     => "Check blood glucose immediately. Both Type 1 and Type 2 are managed at Namibian state facilities.",
        "urgency"  => "MEDIUM"
    ],
    "Gastroenteritis" => [
        "symptoms" => ["diarrhea","vomiting","nausea","stomach pain","fever","cramping","dehydration"],
        "weight"   => [3,3,2,3,1,2,2],
        "info"     => "Rehydrate with ORS. Monitor for signs of severe dehydration, especially in children.",
        "urgency"  => "MEDIUM"
    ],
    "Anemia" => [
        "symptoms" => ["fatigue","weakness","pale skin","shortness of breath","dizziness","cold hands","headache"],
        "weight"   => [3,3,3,2,2,2,1],
        "info"     => "Check Hb levels. Common in pregnant women and children. Iron supplementation may be needed.",
        "urgency"  => "MEDIUM"
    ],
    "Asthma" => [
        "symptoms" => ["shortness of breath","wheezing","chest tightness","cough","fatigue"],
        "weight"   => [4,4,3,3,1],
        "info"     => "Administer bronchodilator (Salbutamol). Avoid known triggers. Monitor oxygen saturation.",
        "urgency"  => "MEDIUM"
    ],
    "Typhoid Fever" => [
        "symptoms" => ["fever","headache","stomach pain","constipation","diarrhea","weakness","rash"],
        "weight"   => [3,2,3,2,2,2,2],
        "info"     => "A bacterial infection from contaminated water/food. Blood culture and antibiotics needed.",
        "urgency"  => "HIGH"
    ],
    "Meningitis" => [
        "symptoms" => ["severe headache","fever","stiff neck","confusion","nausea","vomiting","light sensitivity"],
        "weight"   => [4,3,4,3,2,2,3],
        "info"     => "Medical emergency. Isolate patient and initiate IV antibiotics immediately. Notify doctor.",
        "urgency"  => "HIGH"
    ],
    "Common Cold" => [
        "symptoms" => ["runny nose","sore throat","sneezing","cough","headache","fatigue","congestion"],
        "weight"   => [3,2,3,2,1,1,2],
        "info"     => "Viral URTI. Supportive care — rest, fluids, paracetamol. No antibiotics required.",
        "urgency"  => "LOW"
    ],
    "Urinary Tract Infection" => [
        "symptoms" => ["frequent urination","burning urination","cloudy urine","pelvic pain","fever","back pain"],
        "weight"   => [3,4,3,3,2,2],
        "info"     => "Collect midstream urine for MC&S. Trimethoprim or nitrofurantoin commonly used.",
        "urgency"  => "MEDIUM"
    ],
    "Sepsis" => [
        "symptoms" => ["fever","confusion","rapid breathing","rapid heart rate","weakness","chills","low blood pressure"],
        "weight"   => [3,4,3,3,2,2,4],
        "info"     => "Life-threatening emergency. Initiate sepsis protocol — IV fluids, blood cultures, broad-spectrum antibiotics immediately.",
        "urgency"  => "HIGH"
    ],
];

$all_symptoms = [];
foreach ($disease_rules as $rule) {
    foreach ($rule['symptoms'] as $s) $all_symptoms[$s] = true;
}
ksort($all_symptoms);
$all_symptoms = array_keys($all_symptoms);

$results  = [];
$selected = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected = array_filter($_POST['symptoms'] ?? [], fn($s) => in_array($s, $all_symptoms));
    if (count($selected) > 0) {
        foreach ($disease_rules as $disease => $rule) {
            $score = 0; $maxScore = array_sum($rule['weight']); $matched = [];
            foreach ($rule['symptoms'] as $i => $symptom) {
                if (in_array($symptom, $selected)) { $score += $rule['weight'][$i]; $matched[] = $symptom; }
            }
            if ($score > 0) $results[] = ['disease'=>$disease,'score'=>$score,'pct'=>round($score/$maxScore*100),'info'=>$rule['info'],'urgency'=>$rule['urgency'],'matched'=>$matched];
        }
        usort($results, fn($a,$b) => $b['score']-$a['score']);
        $results = array_slice($results, 0, 5);
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="topbar">
  <div class="topbar-title"><i class="fas fa-robot"></i> AI Symptom Checker</div>
</div>

<div class="page-body">

<div class="alert alert-warning">
  <i class="fas fa-exclamation-triangle"></i>
  <strong>Clinical Tool — Not a Diagnosis:</strong> This rule-based AI assists clinical staff in identifying likely conditions. Always confirm with examination, history, and appropriate diagnostics. Not a substitute for clinical judgment.
</div>

<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-stethoscope"></i> Select Patient Symptoms</div>
    <span class="badge badge-teal" id="count-badge">0 selected</span>
  </div>
  <form method="POST" id="checker-form">
    <p style="font-size:0.83rem;color:var(--text-muted);margin-bottom:12px;">
      Select all symptoms the patient is currently presenting, then click <strong>Analyse</strong>.
    </p>
    <div id="chips-container">
      <?php foreach ($all_symptoms as $symptom): ?>
      <label>
        <input type="checkbox" name="symptoms[]" value="<?= htmlspecialchars($symptom) ?>"
               <?= in_array($symptom,$selected)?'checked':'' ?> style="display:none;" onchange="updateCount()">
        <span class="symptom-chip <?= in_array($symptom,$selected)?'active':'' ?>"><?= htmlspecialchars(ucfirst($symptom)) ?></span>
      </label>
      <?php endforeach; ?>
    </div>
    <div class="mt-2 flex-gap">
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Analyse Symptoms</button>
      <a href="symptom_checker.php" class="btn btn-outline"><i class="fas fa-redo"></i> Reset</a>
    </div>
  </form>
</div>

<?php if ($_SERVER['REQUEST_METHOD'] === 'POST'): ?>
<?php if (empty($selected)): ?>
<div class="alert alert-warning"><i class="fas fa-exclamation-circle"></i> Please select at least one symptom.</div>
<?php elseif (empty($results)): ?>
<div class="alert alert-info"><i class="fas fa-info-circle"></i> No matching conditions found. Refer patient to doctor directly.</div>
<?php else: ?>
<div class="card">
  <div class="card-header">
    <div class="card-title"><i class="fas fa-diagnoses"></i> Analysis Results</div>
    <span style="font-size:0.79rem;color:var(--text-muted);"><?= count($selected) ?> symptom(s) analysed</span>
  </div>
  <?php foreach ($results as $i => $r):
    $urgClass = match($r['urgency']) { 'HIGH'=>'result-high','MEDIUM'=>'result-medium', default=>'result-low' };
    $urgColor = match($r['urgency']) { 'HIGH'=>'var(--red-mid)','MEDIUM'=>'var(--amber-mid)', default=>'var(--green-mid)' };
    $urgBadge = match($r['urgency']) { 'HIGH'=>'badge-danger','MEDIUM'=>'badge-warning', default=>'badge-success' };
  ?>
  <div class="result-card <?= $urgClass ?>" style="<?= $i>0?'margin-top:10px;':'' ?>">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px;flex-wrap:wrap;gap:8px;">
      <div style="display:flex;align-items:center;gap:8px;">
        <strong style="font-size:1rem;"><?= htmlspecialchars($r['disease']) ?></strong>
        <?php if ($i===0): ?><span style="background:var(--teal-mid);color:#fff;font-size:0.62rem;padding:2px 8px;border-radius:20px;font-weight:700;">BEST MATCH</span><?php endif; ?>
      </div>
      <div style="display:flex;align-items:center;gap:10px;">
        <span class="badge <?= $urgBadge ?>"><?= $r['urgency'] ?> URGENCY</span>
        <strong style="font-family:'Space Mono',monospace;color:<?= $urgColor ?>;"><?= $r['pct'] ?>%</strong>
      </div>
    </div>
    <div class="prob-bar" style="margin-bottom:8px;"><div class="prob-fill" style="width:<?= $r['pct'] ?>%;background:<?= $urgColor ?>;"></div></div>
    <p style="font-size:0.84rem;margin-bottom:8px;"><?= htmlspecialchars($r['info']) ?></p>
    <div style="font-size:0.76rem;color:var(--text-muted);">
      <strong>Matched:</strong>
      <?php foreach ($r['matched'] as $m): ?>
      <span class="badge badge-info" style="margin:2px;"><?= htmlspecialchars(ucfirst($m)) ?></span>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endforeach; ?>
  <div class="alert alert-info mt-2">
    <i class="fas fa-user-md"></i> These are AI-assisted suggestions only. Refer the patient to a qualified clinician for confirmed diagnosis and treatment.
  </div>
</div>
<?php endif; ?>
<?php endif; ?>

</div>

<script>
function updateCount() {
  const n = document.querySelectorAll('input[type=checkbox]:checked').length;
  document.getElementById('count-badge').textContent = n + ' selected';
  document.querySelectorAll('label').forEach(label => {
    const cb = label.querySelector('input[type=checkbox]');
    const chip = label.querySelector('.symptom-chip');
    if (cb && chip) chip.classList.toggle('active', cb.checked);
  });
}

// Attach click to chip span only — prevent label double-firing
document.querySelectorAll('.symptom-chip').forEach(chip => {
  chip.addEventListener('click', (e) => {
    e.preventDefault();
    e.stopPropagation();
    const cb = chip.closest('label').querySelector('input[type=checkbox]');
    if (cb) {
      cb.checked = !cb.checked;
      updateCount();
    }
  });
});

updateCount();
</script>

<?php include '../includes/footer.php'; ?>
