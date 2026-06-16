<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('depthead');
$user = $_SESSION['user'];
$PAGE_TITLE = 'Department Head';
$dept = (int)$user['department_id'];

// ── Approve / Reject ─────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'approve') {
    $sid    = (int)($_POST['student_id'] ?? 0);
    $status = (($_POST['decision'] ?? '') === 'reject') ? 'rejected' : 'approved';
    $rem    = trim($_POST['remarks'] ?? '');

    // Safety check: all 5 offices must be cleared before approving
    $check = $conn->prepare('SELECT COUNT(*) c FROM clearances WHERE student_id=? AND status="cleared"');
    $check->bind_param('i', $sid);
    $check->execute();
    $cleared = (int)$check->get_result()->fetch_assoc()['c'];

    if ($cleared === 5 || $status === 'rejected') {
        // FIX: bind_param type string corrected.
        //   dept_status(s), dept_remarks(s), dept_approved_by(i), student_id(i) → 'ssii' ✓
        $stmt = $conn->prepare(
            'UPDATE final_approval
             SET dept_status=?, dept_remarks=?, dept_approved_by=?, dept_approved_at=NOW()
             WHERE student_id=?'
        );
        $stmt->bind_param('ssii', $status, $rem, $user['id'], $sid);
        if ($stmt->execute()) {
            flash('Decision saved.');
        } else {
            flash('Error saving decision: ' . $conn->error, 'danger');
        }
    } else {
        flash('Cannot approve: student is not cleared by all 5 offices yet.', 'danger');
    }
    header('Location: depthead_dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php flash(); ?>
  <h3 class="fw-bold mb-3">Department Head Dashboard</h3>
  <p class="text-muted">Showing students from your department only.</p>

  <div class="card shadow-sm"><div class="table-responsive">
  <table class="table table-hover align-middle mb-0">
    <thead class="table-dark">
      <tr><th>Student</th><th>Cleared offices</th><th>Dept decision</th><th></th></tr>
    </thead>
    <tbody>
    <?php
    // FIX: dept guard added to WHERE clause so only this dept head's students show
    $stmt = $conn->prepare("
      SELECT s.id, s.student_id, s.full_name,
             COALESCE(SUM(c.status='cleared'), 0) AS cleared_count,
             fa.dept_status, fa.registrar_status
      FROM students s
      LEFT JOIN clearances c      ON c.student_id = s.id
      LEFT JOIN final_approval fa ON fa.student_id = s.id
      WHERE s.department_id = ?
      GROUP BY s.id, fa.dept_status, fa.registrar_status
      ORDER BY s.id");
    $stmt->bind_param('i', $dept);
    $stmt->execute();
    $rs = $stmt->get_result();
    while ($r = $rs->fetch_assoc()):
      $deptBadge = match($r['dept_status']) {
        'approved' => 'badge-cleared',
        'rejected' => 'badge-hold',
        default    => 'badge-pending',
      };
    ?>
      <tr>
        <td><code><?= e($r['student_id']) ?></code> · <?= e($r['full_name']) ?></td>
        <td>
          <span class="badge bg-<?= (int)$r['cleared_count']===5 ? 'success' : 'secondary' ?>">
            <?= (int)$r['cleared_count'] ?> / 5
          </span>
        </td>
        <td>
          <span class="badge <?= $deptBadge ?>"><?= e($r['dept_status']) ?></span>
          <?php if ($r['registrar_status'] === 'approved'): ?>
            <span class="badge badge-approved ms-1">REGISTRAR ✓</span>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-bhu-dark"
            <?= (int)$r['cleared_count'] < 5 ? 'disabled title="All 5 offices must clear first"' : '' ?>
            data-bs-toggle="modal" data-bs-target="#mDecide"
            data-id="<?= (int)$r['id'] ?>"
            data-name="<?= e($r['full_name']) ?>">Decide</button>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div></div>
</div>

<!-- Decision modal -->
<div class="modal fade" id="mDecide" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="student_id" id="dec_id">
    <div class="modal-header bg-dark text-white">
      <h5 class="modal-title">Departmental Decision</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body">
      <p>Student: <strong id="dec_name"></strong></p>
      <label class="small fw-bold">Decision</label>
      <select name="decision" class="form-select mb-2">
        <option value="approve">Approve</option>
        <option value="reject">Reject</option>
      </select>
      <label class="small fw-bold">Remarks</label>
      <textarea name="remarks" class="form-control" rows="3" placeholder="Optional"></textarea>
    </div>
    <div class="modal-footer"><button class="btn btn-bhu-yellow">Submit</button></div>
  </form>
</div></div></div>

<script>
document.querySelectorAll('[data-bs-target="#mDecide"]').forEach(b => b.addEventListener('click', () => {
  document.getElementById('dec_id').value   = b.dataset.id;
  document.getElementById('dec_name').textContent = b.dataset.name;
}));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
