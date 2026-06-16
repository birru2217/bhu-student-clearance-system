<?php
require_once __DIR__ . '/../includes/auth.php';
require_login(['library','cafeteria','dormitory','finance','sports']);

$user   = $_SESSION['user'];
$office = $user['office'];
$PAGE_TITLE = OFFICE_LABELS[$office] ?? 'Office';

// ── 1. አዲስ ጥያቄዎችን በየቢሮው ለመቁጠር (Notification Count) ───────────────────
$count_stmt = $conn->prepare("SELECT COUNT(*) as new_requests FROM clearances WHERE office = ? AND status = 'pending'");
$count_stmt->bind_param("s", $office);
$count_stmt->execute();
$count_result = $count_stmt->get_result()->fetch_assoc();
$new_requests = $count_result['new_requests'] ?? 0;

// ── Update clearance status & Send Notification ──────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_status') {
    $sid     = (int)($_POST['student_id'] ?? 0);
    $status  = $_POST['status'] ?? 'pending';
    $remarks = trim($_POST['remarks'] ?? '');
    
    if (in_array($status, ['cleared','hold','pending'], true)) {
        // ሁኔታውን ማደስ
        $stmt = $conn->prepare(
            'UPDATE clearances SET status=?, remarks=?, updated_by=? WHERE student_id=? AND office=?'
        );
        $stmt->bind_param('ssiis', $status, $remarks, $user['id'], $sid, $office);
        $stmt->execute();

        // ── 2. ለተማሪው አውቶማቲክ የውስጥ መልእክት መላክ (Notification System) ──
        $office_name = OFFICE_LABELS[$office] ?? strtoupper($office);
        if ($status === 'cleared') {
            $msg_text = "🎉 Your clearance request for **" . $office_name . "** has been APPROVED by " . $user['name'] . ".";
        } elseif ($status === 'hold') {
            $msg_text = "⚠️ Your clearance for **" . $office_name . "** is on HOLD. Reason: " . ($remarks ?: 'No remarks given.');
        } else {
            $msg_text = "🔄 Your clearance status for **" . $office_name . "** has been set back to PENDING.";
        }

        // በዳታቤዝ ውስጥ ማስቀመጥ
        $table_check = $conn->query("SHOW TABLES LIKE 'notifications'");
        if (!$table_check || $table_check->num_rows === 0) {
            $conn->query("CREATE TABLE IF NOT EXISTS notifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                student_id INT NOT NULL,
                message TEXT NOT NULL,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                CONSTRAINT fk_notif_student FOREIGN KEY (student_id) REFERENCES students(id) ON DELETE CASCADE
            ) ENGINE=InnoDB");
        }

        $notif_stmt = $conn->prepare("INSERT INTO notifications (student_id, message) VALUES (?, ?)");
        if ($notif_stmt) {
            $notif_stmt->bind_param("is", $sid, $msg_text);
            $notif_stmt->execute();
        }

        flash('Status updated and student notified.');
    }
    header('Location: office_dashboard.php?view=' . $sid);
    exit;
}

$q        = trim($_GET['q'] ?? '');
$selected = null;

if (!empty($_GET['view'])) {
    $sid  = (int)$_GET['view'];
    $stmt = $conn->prepare(
        'SELECT s.*, d.name AS dept FROM students s JOIN departments d ON d.id=s.department_id WHERE s.id=?'
    );
    $stmt->bind_param('i', $sid);
    $stmt->execute();
    $selected = $stmt->get_result()->fetch_assoc();
    if ($selected) {
        $stmt = $conn->prepare('SELECT * FROM clearances WHERE student_id=? AND office=?');
        $stmt->bind_param('is', $sid, $office);
        $stmt->execute();
        $selected['clearance'] = $stmt->get_result()->fetch_assoc();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<div class="container">
  <?php flash(); ?>
  
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="fw-bold mb-0">
        <?= e(OFFICE_LABELS[$office] ?? $office) ?> · Dashboard
        <?php if ($new_requests > 0): ?>
            <span class="badge bg-danger ms-2" style="font-size: 0.55em; vertical-align: middle; animation: pulse 1.5s infinite;">
                🔔 <?= $new_requests ?> New
            </span>
        <?php endif; ?>
    </h3>
    <span class="badge bg-warning text-dark">Officer: <?= e($user['name']) ?></span>
  </div>

  <div class="row g-3">
    <div class="col-md-5">
      <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">Search students</div>
        <div class="card-body">
          <form class="row g-2" method="get">
            <div class="col-9"><input name="q" id="searchBox" class="form-control" placeholder="Search by ID or name..." value="<?= e($q) ?>"></div>
            <div class="col-3"><button class="btn btn-bhu-dark w-100">Go</button></div>
          </form>
          <hr>
          <div class="list-group list-group-flush small" style="max-height:430px;overflow:auto">
            <?php
            $sql = 'SELECT s.id, s.student_id, s.full_name, d.code AS dept, c.status
                    FROM students s
                    JOIN departments d ON d.id=s.department_id
                    JOIN clearances c ON c.student_id=s.id AND c.office=?';
            if ($q !== '') {
                $like = '%' . $conn->real_escape_string($q) . '%';
                $stmt = $conn->prepare($sql . ' WHERE s.student_id LIKE ? OR s.full_name LIKE ? ORDER BY s.id DESC');
                $stmt->bind_param('sss', $office, $like, $like);
            } else {
                $stmt = $conn->prepare($sql . ' ORDER BY s.id DESC LIMIT 50');
                $stmt->bind_param('s', $office);
            }
            $stmt->execute();
            $rs = $stmt->get_result();
            while ($r = $rs->fetch_assoc()):
              $cls = ['pending'=>'badge-pending','cleared'=>'badge-cleared','hold'=>'badge-hold'][$r['status']] ?? 'badge-pending';
            ?>
              <a href="?view=<?= (int)$r['id'] ?>" class="list-group-item list-group-item-action d-flex justify-content-between <?= (isset($_GET['view']) && $_GET['view'] == $r['id']) ? 'active bg-light text-dark' : '' ?>">
                <span><code><?= e($r['student_id']) ?></code> · <?= e($r['full_name']) ?> <small class="text-muted">(<?= e($r['dept']) ?>)</small></span>
                <span class="badge <?= $cls ?>"><?= e($r['status']) ?></span>
              </a>
            <?php endwhile; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="col-md-7">
      <?php if (!$selected): ?>
        <div class="card shadow-sm h-100"><div class="card-body text-center text-muted py-5">
          <h5>Select a student from the list →</h5>
          <p class="small">Use the search box to find a student by ID or name.</p>
        </div></div>
      <?php else: $c = $selected['clearance']; ?>
        <div class="card shadow-sm">
          <div class="card-header bg-warning">
            <strong><?= e($selected['full_name']) ?></strong> · <code><?= e($selected['student_id']) ?></code>
          </div>
          <div class="card-body">
            <dl class="row mb-3 small">
              <dt class="col-4">Department</dt><dd class="col-8"><?= e($selected['dept']) ?></dd>
              <dt class="col-4">Year / Program</dt><dd class="col-8"><?= e($selected['year']) ?> · <?= e($selected['program']) ?></dd>
              <dt class="col-4">Contact</dt><dd class="col-8"><?= e($selected['email']) ?> · <?= e($selected['phone']) ?></dd>
              <dt class="col-4">Current status</dt>
              <dd class="col-8"><span class="badge badge-<?= e($c['status']) ?>"><?= e($c['status']) ?></span></dd>
              <dt class="col-4">Last remark</dt><dd class="col-8 text-muted"><?= e($c['remarks'] ?: '—') ?></dd>
            </dl>

            <form method="post" class="border-top pt-3">
              <input type="hidden" name="action" value="update_status">
              <input type="hidden" name="student_id" value="<?= (int)$selected['id'] ?>">
              <div class="mb-2">
                <label class="small fw-bold">New status</label>
                <select name="status" class="form-select">
                  <option value="cleared" <?= $c['status']==='cleared'?'selected':'' ?>>Cleared</option>
                  <option value="hold"    <?= $c['status']==='hold'?'selected':'' ?>>Hold</option>
                  <option value="pending" <?= $c['status']==='pending'?'selected':'' ?>>Pending</option>
                </select>
              </div>
              <div class="mb-3">
                <label class="small fw-bold">Remarks</label>
                <textarea name="remarks" rows="3" class="form-control" placeholder="e.g. 1 Java Programming Book missing"><?= e($c['remarks'] ?? '') ?></textarea>
              </div>
              <button class="btn btn-bhu-dark">Save Status</button>
            </form>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
setInterval(function() {
    let searchBox = document.getElementById('searchBox');
    // የቢሮው ኃላፊ ተማሪ እየፈለገ ካልሆነ ብቻ ገጹን ራሱ ያድሰዋል
    if (!searchBox || searchBox.value.trim() === '') {
        // አሁን ያለበትን የ?view=id ሳይለቅ ገጹን ያድሳል
        window.location.reload();
    }
}, 30000); // በየ 30 ሰከንዱ ይፈትሻል
</script>

<style>
/* የደወል ምልክቱ ቀስ እያለ እንዲበራና እንዲጠፋ ማድረጊያ ውብ ስታይል */
@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.05); }
    100% { opacity: 1; transform: scale(1); }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>