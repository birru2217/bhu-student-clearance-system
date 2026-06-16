<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('registrar');
$user = $_SESSION['user'];
$PAGE_TITLE = 'Registrar';

// ── Final approval ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'final_approve') {
    $sid = (int)($_POST['student_id'] ?? 0);

    // FIX: use prepared statement instead of raw interpolation for safety
    $chk = $conn->prepare("
        SELECT
            (SELECT COUNT(*) FROM clearances WHERE student_id=? AND status='cleared') AS c,
            fa.dept_status
        FROM final_approval fa
        WHERE fa.student_id=?");
    $chk->bind_param('ii', $sid, $sid);
    $chk->execute();
    $r = $chk->get_result()->fetch_assoc();

    if ((int)$r['c'] === 5 && $r['dept_status'] === 'approved') {
        $code = 'BHU-CERT-' . strtoupper(bin2hex(random_bytes(5)));
        $stmt = $conn->prepare("
            UPDATE final_approval
            SET registrar_status='approved',
                registrar_approved_by=?,
                registrar_approved_at=NOW(),
                certificate_code=?
            WHERE student_id=?");
        $stmt->bind_param('isi', $user['id'], $code, $sid);
        $stmt->execute();
        flash('Final approval granted. Certificate code: ' . $code);
    } else {
        flash('Cannot approve: prerequisites not met.', 'danger');
    }
    header('Location: registrar_dashboard.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php flash(); ?>
  <h3 class="fw-bold mb-3">Registrar Dashboard</h3>
  <p class="text-muted">Students cleared by 5 offices + dept head — ready for final seal.</p>

  <div class="card shadow-sm"><div class="table-responsive">
  <table class="table table-hover mb-0 align-middle">
    <thead class="table-dark">
      <tr><th>Student</th><th>Dept</th><th>Offices</th><th>Dept</th><th>Registrar</th><th></th></tr>
    </thead>
    <tbody>
    <?php
    $rs = $conn->query("
        SELECT s.id, s.student_id, s.full_name, d.code AS dept,
               COALESCE(SUM(c.status='cleared'), 0) AS cleared_count,
               fa.dept_status, fa.registrar_status, fa.certificate_code
        FROM students s
        JOIN departments d        ON d.id = s.department_id
        LEFT JOIN clearances c    ON c.student_id = s.id
        LEFT JOIN final_approval fa ON fa.student_id = s.id
        GROUP BY s.id, d.code, fa.dept_status, fa.registrar_status, fa.certificate_code
        ORDER BY fa.registrar_status, s.id");
    while ($r = $rs->fetch_assoc()):
    ?>
      <tr>
        <td><code><?= e($r['student_id']) ?></code> · <?= e($r['full_name']) ?></td>
        <td><?= e($r['dept']) ?></td>
        <td>
          <span class="badge bg-<?= (int)$r['cleared_count']===5 ? 'success' : 'secondary' ?>">
            <?= (int)$r['cleared_count'] ?>/5
          </span>
        </td>
        <td>
          <span class="badge badge-<?= $r['dept_status']==='approved'?'cleared':'pending' ?>">
            <?= e($r['dept_status']) ?>
          </span>
        </td>
        <td>
          <?php if ($r['registrar_status'] === 'approved'): ?>
            <span class="badge badge-approved">APPROVED</span><br>
            <small class="text-muted"><?= e($r['certificate_code']) ?></small>
          <?php else: ?>
            <span class="badge badge-pending">pending</span>
          <?php endif; ?>
        </td>
        <td class="text-end">
          <?php if ($r['registrar_status'] === 'approved'): ?>
            <a class="btn btn-sm btn-outline-dark" href="../actions/certificate.php?sid=<?= (int)$r['id'] ?>" target="_blank">View Certificate</a>
          <?php elseif ((int)$r['cleared_count'] === 5 && $r['dept_status'] === 'approved'): ?>
            <form method="post" onsubmit="return confirm('Apply the digital seal & registrar signature?')">
              <input type="hidden" name="action" value="final_approve">
              <input type="hidden" name="student_id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-sm btn-bhu-yellow">Final Approve</button>
            </form>
          <?php else: ?>
            <span class="text-muted small">Not ready</span>
          <?php endif; ?>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
