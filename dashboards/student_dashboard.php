<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('student');
$user = $_SESSION['user'];
$PAGE_TITLE = 'My Clearance';
$sid = (int)$user['id'];

// Fetch office clearances using prepared statements
$stmt = $conn->prepare("
    SELECT c.office, c.status, c.remarks, c.updated_at, u.full_name AS officer
    FROM clearances c
    LEFT JOIN users u ON u.id = c.updated_by
    WHERE c.student_id = ?
    ORDER BY FIELD(c.office, 'library','cafeteria','dormitory','finance','sports')");
$stmt->bind_param('i', $sid);
$stmt->execute();
$stuff = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Fetch final approval status
$fa_stmt = $conn->prepare('SELECT * FROM final_approval WHERE student_id = ?');
$fa_stmt->bind_param('i', $sid);
$fa_stmt->execute();
$fa = $fa_stmt->get_result()->fetch_assoc();

// Calculate progress
$cleared_count = array_reduce($stuff, fn($a, $c) => $a + ($c['status'] === 'cleared' ? 1 : 0), 0);
$fully_done    = $cleared_count === 5
                 && ($fa['dept_status']      ?? '') === 'approved'
                 && ($fa['registrar_status'] ?? '') === 'approved';

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php flash(); ?>
  <div class="row g-3 align-items-center mb-3">
    <div class="col-md-7">
      <h3 class="fw-bold mb-0">Hello, <?= e($user['name']) ?> 👋</h3>
      <p class="text-muted mb-0">Live tracking of your clearance across all BHU offices.</p>
    </div>
    
    <div class="col-md-5 text-md-end d-flex justify-content-md-end align-items-center gap-2">
      
      <?php if (!$fully_done): ?>
        <form action="../actions/request_clearance.php" method="POST" class="m-0">
            <input type="hidden" name="student_id" value="<?= $sid ?>">
            <div class="mb-2">
              <label for="request_reason" class="form-label small mb-1">Reason for this clearance request</label>
              <textarea id="request_reason" name="request_reason" class="form-control" rows="2" placeholder="Explain why you are requesting clearance" required maxlength="255"></textarea>
            </div>
            <button type="submit" class="btn btn-primary shadow-sm" onclick="return confirm('Are you sure you want to send a clearance request to all offices?');">
                🚀 Request Clearance
            </button>
        </form>
      <?php endif; ?>

      <?php if ($fully_done): ?>
        <a class="btn btn-success shadow-sm" target="_blank" href="../actions/certificate.php?sid=<?= $sid ?>">⬇ Download Certificate</a>
      <?php else: ?>
        <button class="btn btn-secondary shadow-sm" disabled title="Available after full clearance">⬇ Certificate</button>
      <?php endif; ?>
      
    </div>
  </div>

  <div class="card shadow-sm mb-4">
    <div class="card-body">
      <div class="progress" role="progressbar" style="height:14px; background-color: #e9ecef;">
        <div class="progress-bar bg-warning text-dark fw-bold"
             style="width: <?= ($cleared_count / 5) * 100 ?>%">
          <?= $cleared_count ?>/5 offices cleared
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <?php foreach ($stuff as $c):
      $badge = ['pending'=>'badge-pending','cleared'=>'badge-cleared','hold'=>'badge-hold'][$c['status']] ?? 'badge-pending';
    ?>
      <div class="col-md-6">
        <div class="card module-card h-100 shadow-sm border-0">
          <div class="card-top" style="height: 4px; background-color: #ffc107;"></div>
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-center">
              <h5 class="fw-bold mb-0"><?= e(OFFICE_LABELS[$c['office']] ?? $c['office']) ?></h5>
              <span class="badge <?= $badge ?>"><?= strtoupper($c['status']) ?></span>
            </div>
            <p class="text-muted small mt-2 mb-1"><strong>Remarks:</strong> <?= e($c['remarks'] ?: '—') ?></p>
            <small class="text-muted">Updated <?= e($c['updated_at'] ?? '—') ?> · <?= e($c['officer'] ?? 'system') ?></small>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>

  <div class="card shadow-sm mt-4 border-0">
    <div class="card-header bg-dark text-white fw-bold">Departmental & Registrar Status</div>
    <div class="card-body bg-light">
      <div class="row text-center">
        <div class="col-md-6 border-end">
          <h6 class="fw-bold text-secondary">Department Head</h6>
          <?php
          $ds = $fa['dept_status'] ?? 'pending';
          $dBadge = match($ds) { 'approved' => 'badge-cleared', 'rejected' => 'badge-hold', default => 'badge-pending' };
          ?>
          <span class="badge <?= $dBadge ?> fs-6 mt-1"><?= e(strtoupper($ds)) ?></span>
          <p class="text-muted small mt-2 mb-0"><?= e($fa['dept_remarks'] ?: '—') ?></p>
        </div>
        <div class="col-12 mt-3">
          <p class="text-muted small mb-0"><strong>Request reason:</strong> <?= e($fa['request_reason'] ?? '—') ?></p>
        </div>
        <div class="col-md-6">
          <h6 class="fw-bold text-secondary">Registrar</h6>
          <?php 
          $rs2 = $fa['registrar_status'] ?? 'pending'; 
          // Fixed the badge class here to match the rest of the system
          $rBadge = match($rs2) { 'approved' => 'badge-cleared', 'rejected' => 'badge-hold', default => 'badge-pending' };
          ?>
          <span class="badge <?= $rBadge ?> fs-6 mt-1">
            <?= e(strtoupper($rs2)) ?>
          </span>
          <p class="text-muted small mt-2 mb-0"><?= e($fa['certificate_code'] ?: '—') ?></p>
        </div>
      </div>
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>