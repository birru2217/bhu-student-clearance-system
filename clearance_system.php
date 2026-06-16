<?php
/**
 * clearance_system.php — Lightweight read-only summary that any staff/admin
 * role can use to view the entire clearance pipeline at a glance.
 */
require_once __DIR__ . '/includes/auth.php';
require_login(['admin','registrar','depthead','library','cafeteria','dormitory','finance','sports']);
$PAGE_TITLE = 'Clearance Overview';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <h3 class="fw-bold mb-3">Clearance System · Pipeline View</h3>
  <div class="card shadow-sm"><div class="table-responsive">
  <table class="table table-sm table-hover align-middle mb-0">
    <thead class="table-dark">
      <tr><th>Student</th><th>Dept</th>
        <?php foreach (BHU_OFFICES as $o) echo '<th class="text-center">'.strtoupper(substr($o,0,3)).'</th>'; ?>
        <th class="text-center">Dept</th><th class="text-center">Reg</th>
      </tr>
    </thead>
    <tbody>
    <?php
    $rs = $conn->query("
      SELECT s.id, s.student_id, s.full_name, d.code AS dept,
        MAX(CASE WHEN c.office='library'   THEN c.status END) library_s,
        MAX(CASE WHEN c.office='cafeteria' THEN c.status END) cafeteria_s,
        MAX(CASE WHEN c.office='dormitory' THEN c.status END) dormitory_s,
        MAX(CASE WHEN c.office='finance'   THEN c.status END) finance_s,
        MAX(CASE WHEN c.office='sports'    THEN c.status END) sports_s,
        fa.dept_status, fa.registrar_status
      FROM students s JOIN departments d ON d.id=s.department_id
      LEFT JOIN clearances c ON c.student_id=s.id
      LEFT JOIN final_approval fa ON fa.student_id=s.id
      GROUP BY s.id ORDER BY s.id");
    while ($r = $rs->fetch_assoc()): ?>
      <tr>
        <td><code><?= e($r['student_id']) ?></code> · <?= e($r['full_name']) ?></td>
        <td><?= e($r['dept']) ?></td>
        <?php foreach (BHU_OFFICES as $o):
          $s = $r[$o.'_s'] ?? 'pending';
          $cls = ['pending'=>'badge-pending','cleared'=>'badge-cleared','hold'=>'badge-hold'][$s]; ?>
          <td class="text-center"><span class="badge <?= $cls ?>"><?= $s[0] ?></span></td>
        <?php endforeach; ?>
        <td class="text-center"><span class="badge badge-<?= $r['dept_status']==='approved'?'cleared':'pending' ?>"><?= ($r['dept_status'][0] ?? 'p') ?></span></td>
        <td class="text-center"><span class="badge badge-<?= $r['registrar_status']==='approved'?'approved':'pending' ?>"><?= ($r['registrar_status'][0] ?? 'p') ?></span></td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div></div>
  <p class="text-muted small mt-2">Legend — <span class="badge badge-pending">p</span> pending · <span class="badge badge-cleared">c</span> cleared · <span class="badge badge-hold">h</span> hold · <span class="badge badge-approved">a</span> approved</p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
