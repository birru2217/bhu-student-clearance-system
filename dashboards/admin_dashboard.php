<?php
require_once __DIR__ . '/../includes/auth.php';
require_login('admin');
$PAGE_TITLE = 'Admin';

// Ensure `experience_years` exists (fix for databases missing the column)
$colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'experience_years'");
if ($colCheck && $colCheck->num_rows === 0) {
    $conn->query("ALTER TABLE users ADD COLUMN experience_years INT NOT NULL DEFAULT 0");
}

// ── Handle POST actions ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Student: Add / Edit ──────────────────────────────────────────────────
    if ($action === 'add_student' || $action === 'edit_student') {
        $student_id_raw = trim($_POST['student_id'] ?? '');
        $student_id = preg_replace('/\s+/', '', $student_id_raw);
        if ($student_id !== '') {
            $parts = explode('/', $student_id);
            if (count($parts) > 1) {
                $last = array_pop($parts);
                $student_id = implode('', $parts) . '/' . $last;
            }
        }

        if (!preg_match('/^[A-Za-z0-9]+\/[A-Za-z0-9]+$/', $student_id)) {
            flash('Student ID must use exactly one slash, for example RU1332/12.', 'danger');
            header('Location: admin_dashboard.php?tab=students');
            exit;
        }

        $name    = trim($_POST['full_name']    ?? '');
        $gender  = (($_POST['gender'] ?? 'M') === 'F') ? 'F' : 'M';
        $email   = trim($_POST['email']        ?? '');
        $phone   = trim($_POST['phone']        ?? '');
        $program = trim($_POST['program']      ?? 'Regular');
        $year    = (int)($_POST['year']        ?? 0);
        $dept    = (int)($_POST['department_id'] ?? 0);

        if ($year < 1 || $year > 4) {
            flash('Year must be between 1 and 4.', 'danger');
            header('Location: admin_dashboard.php?tab=students');
            exit;
        }

        if ($action === 'add_student') {
            // FIX: password_hash uses PASSWORD_BCRYPT (not PASSWORD_DEFAULT) for consistency
            //      with the sample data in the SQL dump.
            $raw  = $_POST['password'] ?? '';
            $hash = password_hash($raw !== '' ? $raw : 'password', PASSWORD_BCRYPT);

            // FIX: Column order in INSERT matches bind_param type string exactly:
            //   student_id(s), password_hash(s), full_name(s), gender(s),
            //   email(s), phone(s), program(s), year(i), department_id(i)
            //   → type string = 'sssssssii'  ✓
            $stmt = $conn->prepare(
                'INSERT INTO students
                    (student_id, password_hash, full_name, gender, email, phone, program, year, department_id)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssssssii',
                $student_id, $hash, $name, $gender, $email, $phone, $program, $year, $dept
            );

            if ($stmt->execute()) {
                $new_id = $stmt->insert_id;
                // Seed one clearance row per office + one final_approval row
                foreach (BHU_OFFICES as $o) {
                    $ins = $conn->prepare(
                        'INSERT IGNORE INTO clearances (student_id, office, status) VALUES (?, ?, ?)'
                    );
                    $pending = 'pending';
                    $ins->bind_param('iss', $new_id, $o, $pending);
                    $ins->execute();
                }
                $fa = $conn->prepare('INSERT IGNORE INTO final_approval (student_id) VALUES (?)');
                $fa->bind_param('i', $new_id);
                $fa->execute();
                flash('Student created successfully.');
            } else {
                flash('Error creating student: ' . $conn->error, 'danger');
            }

        } else {
            // edit_student
            $id = (int)($_POST['id'] ?? 0);

            // FIX: UPDATE sets 8 columns; WHERE uses id → 9 placeholders total.
            //   student_id(s), full_name(s), gender(s), email(s), phone(s),
            //   program(s), year(i), department_id(i), id(i)
            //   → type string = 'ssssssiii'  ✓
            $stmt = $conn->prepare(
                'UPDATE students
                 SET student_id=?, full_name=?, gender=?, email=?, phone=?, program=?, year=?, department_id=?
                 WHERE id=?'
            );
            $stmt->bind_param('ssssssiii',
                $student_id, $name, $gender, $email, $phone, $program, $year, $dept, $id
            );
            if ($stmt->execute()) {
                flash('Student updated successfully.');
            } else {
                flash('Error updating student: ' . $conn->error, 'danger');
            }
        }

    // ── Student: Toggle Active / Inactive ────────────────────────────────────
    } elseif ($action === 'toggle_student_status') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('UPDATE students SET active = !active WHERE id=?');
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $status = $conn->query("SELECT active FROM students WHERE id=$id")->fetch_assoc();
            flash($status['active'] ? 'Student activated.' : 'Student deactivated.', $status['active'] ? 'success' : 'warning');
        } else {
            flash('Unable to update student status: ' . $conn->error, 'danger');
        }

    // ── Staff: Add / Edit ────────────────────────────────────────────────────
    } elseif ($action === 'add_staff' || $action === 'edit_staff') {
        $username = trim($_POST['username']  ?? '');
        $name     = trim($_POST['full_name'] ?? '');
        $role     = $_POST['role']           ?? 'library';
        $office   = in_array($role, BHU_OFFICES, true) ? $role : null;
        // FIX: department_id must be NULL (not empty string) if not a depthead
        $dept     = (!empty($_POST['department_id']) && $role === 'depthead')
                    ? (int)$_POST['department_id']
                    : null;
        $experience_years = max(0, min(80, (int)($_POST['experience_years'] ?? 0)));

        if ($action === 'add_staff') {
            $raw  = $_POST['password'] ?? '';
            $hash = password_hash($raw !== '' ? $raw : 'password', PASSWORD_BCRYPT);

            // FIX: 7 columns → type string 'sssssii' where department_id is int|null.
            //   username(s), password_hash(s), full_name(s), role(s), office(s|null→s), department_id(i|null→i), experience_years(i)
            $stmt = $conn->prepare(
                'INSERT INTO users (username, password_hash, full_name, role, office, department_id, experience_years)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param('sssssii', $username, $hash, $name, $role, $office, $dept, $experience_years);
            if ($stmt->execute()) {
                flash('Staff account created.');
            } else {
                flash('Error creating staff: ' . $conn->error, 'danger');
            }

        } else {
            // edit_staff
            $id = (int)($_POST['id'] ?? 0);

            // FIX: 5 SET columns + WHERE id → 6 placeholders.
            //   username(s), full_name(s), role(s), office(s|null), department_id(i|null), experience_years(i), id(i)
            //   → type string = 'ssssiii'  ✓
            $stmt = $conn->prepare(
                'UPDATE users
                 SET username=?, full_name=?, role=?, office=?, department_id=?, experience_years=?
                 WHERE id=?'
            );
            $stmt->bind_param('ssssiii', $username, $name, $role, $office, $dept, $experience_years, $id);
            if ($stmt->execute()) {
                flash('Staff updated.');
            } else {
                flash('Error updating staff: ' . $conn->error, 'danger');
            }
        }

    // ── Staff: Delete ────────────────────────────────────────────────────────
    } elseif ($action === 'delete_staff') {
        $id = (int)($_POST['id'] ?? 0);
        $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        flash('Staff account deleted.', 'warning');
    }

    header('Location: admin_dashboard.php?tab=' . urlencode($_POST['tab'] ?? 'students'));
    exit;
}

// ── Page setup ───────────────────────────────────────────────────────────────
$tab = $_GET['tab'] ?? 'students';
$q   = trim($_GET['q'] ?? '');

require_once __DIR__ . '/../includes/header.php';

$depts = $conn->query('SELECT * FROM departments ORDER BY name')->fetch_all(MYSQLI_ASSOC);
?>
<div class="container">
  <?php flash(); ?>
  <h3 class="fw-bold mb-3">Admin Control Panel</h3>
  <ul class="nav nav-pills mb-4">
    <li class="nav-item"><a class="nav-link <?= $tab==='students'?'active':'' ?>" href="?tab=students">Students</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='staff'?'active':'' ?>" href="?tab=staff">Staff Accounts</a></li>
    <li class="nav-item"><a class="nav-link <?= $tab==='overview'?'active':'' ?>" href="?tab=overview">Overview</a></li>
  </ul>

<?php if ($tab === 'students'): ?>
  <!-- Search bar + Add button -->
  <form class="row g-2 mb-3" method="get">
    <input type="hidden" name="tab" value="students">
    <div class="col-md-6"><input name="q" class="form-control" placeholder="Search by ID or name..." value="<?= e($q) ?>"></div>
    <div class="col-md-2"><button class="btn btn-bhu-dark w-100">Search</button></div>
    <div class="col-md-4 text-end"><button type="button" class="btn btn-bhu-yellow" data-bs-toggle="modal" data-bs-target="#mAddStudent">+ Add Student</button></div>
  </form>

  <div class="card shadow-sm"><div class="table-responsive">
  <table class="table table-hover mb-0 align-middle">
    <thead class="table-dark"><tr><th>ID</th><th>Name</th><th>Dept</th><th>Year</th><th>Gender</th><th>Email</th><th>Status</th><th></th></tr></thead>
    <tbody>
    <?php
    $baseSql = 'SELECT s.*, d.code AS dept_code FROM students s JOIN departments d ON d.id = s.department_id';
    if ($q !== '') {
        $like = '%' . $conn->real_escape_string($q) . '%';
        $stmt = $conn->prepare($baseSql . ' WHERE s.student_id LIKE ? OR s.full_name LIKE ? ORDER BY s.id DESC');
        $stmt->bind_param('ss', $like, $like);
        $stmt->execute();
        $rs = $stmt->get_result();
    } else {
        $rs = $conn->query($baseSql . ' ORDER BY s.id DESC');
    }
    while ($r = $rs->fetch_assoc()): ?>
      <tr class="<?= !$r['active'] ? 'table-light opacity-75' : '' ?>">
        <td><code><?= e($r['student_id']) ?></code></td>
        <td><?= e($r['full_name']) ?></td>
        <td><?= e($r['dept_code']) ?></td>
        <td><?= e($r['year']) ?></td>
        <td><?= e($r['gender']) ?></td>
        <td class="small"><?= e($r['email']) ?></td>
        <td><span class="badge <?= $r['active'] ? 'bg-success' : 'bg-secondary' ?>"><?= $r['active'] ? 'Active' : 'Inactive' ?></span></td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-dark"
            data-bs-toggle="modal" data-bs-target="#mEditStudent"
            data-row='<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>Edit</button>
          <form class="d-inline" method="post" onsubmit="return confirm('<?= $r['active'] ? 'Deactivate' : 'Activate' ?> this student?')">
            <input type="hidden" name="action" value="toggle_student_status">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="tab" value="students">
            <button class="btn btn-sm btn-outline-<?= $r['active'] ? 'danger' : 'success' ?>"><?= $r['active'] ? '❌ Deactivate' : '✓ Activate' ?></button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div></div>

<?php elseif ($tab === 'staff'): ?>
  <div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-bhu-yellow" data-bs-toggle="modal" data-bs-target="#mAddStaff">+ Add Staff</button>
  </div>
  <div class="card shadow-sm"><div class="table-responsive">
  <table class="table table-hover mb-0 align-middle">
    <thead class="table-dark"><tr><th>Username</th><th>Full name</th><th>Role</th><th>Dept</th><th>Experience</th><th></th></tr></thead>
    <tbody>
    <?php
    $rs = $conn->query('SELECT u.*, d.code AS dept_code FROM users u LEFT JOIN departments d ON d.id=u.department_id ORDER BY role');
    while ($r = $rs->fetch_assoc()): ?>
      <tr>
        <td><code><?= e($r['username']) ?></code></td>
        <td><?= e($r['full_name']) ?></td>
        <td><span class="badge bg-secondary"><?= e($r['role']) ?></span></td>
        <td><?= e($r['dept_code'] ?? '—') ?></td>
        <td><?= e($r['experience_years'] ?? 0) ?> yrs</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-dark" data-bs-toggle="modal" data-bs-target="#mEditStaff"
            data-row='<?= json_encode($r, JSON_HEX_APOS|JSON_HEX_QUOT) ?>'>Edit</button>
          <form class="d-inline" method="post" onsubmit="return confirm('Delete this staff account?')">
            <input type="hidden" name="action" value="delete_staff">
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <input type="hidden" name="tab" value="staff">
            <button class="btn btn-sm btn-outline-danger">Del</button>
          </form>
        </td>
      </tr>
    <?php endwhile; ?>
    </tbody>
  </table>
  </div></div>

<?php else: /* overview */ ?>
  <div class="row g-3">
    <?php
    $totals = [
      'Students'   => $conn->query('SELECT COUNT(*) c FROM students WHERE active=TRUE')->fetch_assoc()['c'],
      'Cleared'    => $conn->query("SELECT COUNT(DISTINCT student_id) c FROM final_approval WHERE registrar_status='approved'")->fetch_assoc()['c'],
      'On Hold'    => $conn->query("SELECT COUNT(DISTINCT student_id) c FROM clearances WHERE status='hold'")->fetch_assoc()['c'],
      'Staff'      => $conn->query('SELECT COUNT(*) c FROM users')->fetch_assoc()['c'],
    ];
    foreach ($totals as $k => $v): ?>
      <div class="col-md-3"><div class="card module-card text-center p-4">
        <div class="display-6 fw-bold text-warning"><?= (int)$v ?></div>
        <div class="text-muted"><?= e($k) ?></div>
      </div></div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</div>

<!-- ═══════════════════════════ MODALS ════════════════════════════════════ -->

<!-- Add Student -->
<div class="modal fade" id="mAddStudent" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post">
    <input type="hidden" name="action" value="add_student">
    <input type="hidden" name="tab" value="students">
    <div class="modal-header bg-dark text-white">
      <h5 class="modal-title">Add Student</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body row g-2">
      <div class="col-6"><label class="small">Student ID</label><input name="student_id" class="form-control" required pattern="^[A-Za-z0-9]+\/[A-Za-z0-9]+$" title="Use exactly one slash, e.g. RU1332/12"></div>
      <div class="col-6"><label class="small">Full name</label><input name="full_name" class="form-control" required></div>
      <div class="col-3"><label class="small">Gender</label>
        <select name="gender" class="form-select"><option value="M">M</option><option value="F">F</option></select>
      </div>
      <div class="col-3"><label class="small">Year</label>
        <select name="year" class="form-select">
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
        </select>
      </div>
      <div class="col-6"><label class="small">Department</label>
        <select name="department_id" class="form-select">
          <?php foreach($depts as $d) echo '<option value="'.(int)$d['id'].'">'.e($d['name']).'</option>'; ?>
        </select>
      </div>
      <div class="col-6"><label class="small">Email</label><input name="email" type="email" class="form-control"></div>
      <div class="col-6"><label class="small">Phone</label><input name="phone" class="form-control"></div>
      <div class="col-6"><label class="small">Program</label><input name="program" class="form-control" value="Regular"></div>
      <div class="col-6"><label class="small">Password</label><input type="password" name="password" class="form-control" placeholder="default: password"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-bhu-dark">Create</button></div>
  </form>
</div></div></div>

<!-- Edit Student -->
<div class="modal fade" id="mEditStudent" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post" id="fEditStudent">
    <input type="hidden" name="action" value="edit_student">
    <input type="hidden" name="tab" value="students">
    <input type="hidden" name="id">
    <div class="modal-header bg-dark text-white">
      <h5 class="modal-title">Edit Student</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body row g-2">
      <div class="col-6"><label class="small">Student ID</label><input name="student_id" class="form-control" required pattern="^[A-Za-z0-9]+\/[A-Za-z0-9]+$" title="Use exactly one slash, e.g. RU1332/12"></div>
      <div class="col-6"><label class="small">Full name</label><input name="full_name" class="form-control" required></div>
      <div class="col-3"><label class="small">Gender</label>
        <select name="gender" class="form-select"><option value="M">M</option><option value="F">F</option></select>
      </div>
      <div class="col-3"><label class="small">Year</label>
        <select name="year" class="form-select" id="editYearSelect">
          <option value="1">1</option>
          <option value="2">2</option>
          <option value="3">3</option>
          <option value="4">4</option>
        </select>
      </div>
      <div class="col-6"><label class="small">Department</label>
        <select name="department_id" class="form-select">
          <?php foreach($depts as $d) echo '<option value="'.(int)$d['id'].'">'.e($d['name']).'</option>'; ?>
        </select>
      </div>
      <div class="col-6"><label class="small">Email</label><input name="email" type="email" class="form-control"></div>
      <div class="col-6"><label class="small">Phone</label><input name="phone" class="form-control"></div>
      <div class="col-6"><label class="small">Program</label><input name="program" class="form-control"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-bhu-dark">Save</button></div>
  </form>
</div></div></div>

<!-- Add Staff -->
<div class="modal fade" id="mAddStaff" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post">
    <input type="hidden" name="action" value="add_staff">
    <input type="hidden" name="tab" value="staff">
    <div class="modal-header bg-dark text-white">
      <h5 class="modal-title">Add Staff</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body row g-2">
      <div class="col-6"><label class="small">Username</label><input name="username" class="form-control" required></div>
      <div class="col-6"><label class="small">Full name</label><input name="full_name" class="form-control" required></div>
      <div class="col-6"><label class="small">Role</label>
        <select name="role" class="form-select" id="addStaffRole">
          <option>admin</option><option>library</option><option>cafeteria</option>
          <option>dormitory</option><option>finance</option><option>sports</option>
          <option>depthead</option><option>registrar</option>
        </select>
      </div>
      <div class="col-6" id="addDeptWrapper">
        <label class="small">Department <small class="text-muted">(depthead only)</small></label>
        <select name="department_id" class="form-select">
          <option value="">—</option>
          <?php foreach($depts as $d) echo '<option value="'.(int)$d['id'].'">'.e($d['name']).'</option>'; ?>
        </select>
      </div>
      <div class="col-6"><label class="small">Experience (years)</label><input type="number" min="0" max="80" name="experience_years" class="form-control" value="0"></div>
      <div class="col-12"><label class="small">Password</label><input type="password" name="password" class="form-control" placeholder="default: password"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-bhu-dark">Create</button></div>
  </form>
</div></div></div>

<!-- Edit Staff -->
<div class="modal fade" id="mEditStaff" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
  <form method="post" id="fEditStaff">
    <input type="hidden" name="action" value="edit_staff">
    <input type="hidden" name="tab" value="staff">
    <input type="hidden" name="id">
    <div class="modal-header bg-dark text-white">
      <h5 class="modal-title">Edit Staff</h5>
      <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body row g-2">
      <div class="col-6"><label class="small">Username</label><input name="username" class="form-control" required></div>
      <div class="col-6"><label class="small">Full name</label><input name="full_name" class="form-control" required></div>
      <div class="col-6"><label class="small">Role</label>
        <select name="role" class="form-select">
          <option>admin</option><option>library</option><option>cafeteria</option>
          <option>dormitory</option><option>finance</option><option>sports</option>
          <option>depthead</option><option>registrar</option>
        </select>
      </div>
      <div class="col-6"><label class="small">Department</label>
        <select name="department_id" class="form-select">
          <option value="">—</option>
          <?php foreach($depts as $d) echo '<option value="'.(int)$d['id'].'">'.e($d['name']).'</option>'; ?>
        </select>
      </div>
      <div class="col-6"><label class="small">Experience (years)</label><input type="number" min="0" max="80" name="experience_years" class="form-control" value="0"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-bhu-dark">Save</button></div>
  </form>
</div></div></div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Populate Edit Student modal
document.querySelectorAll('[data-bs-target="#mEditStudent"]').forEach(btn => {
  btn.addEventListener('click', () => {
    const r = JSON.parse(btn.dataset.row);
    const f = document.getElementById('fEditStudent');
    ['id','student_id','full_name','gender','email','phone','program','year','department_id']
      .forEach(k => {
        const el = f.querySelector(`[name="${k}"]`);
        if (!el) return;
        const value = r[k] ?? '';
        if (k === 'year' && el.tagName === 'SELECT') {
          if (![...el.options].some(opt => opt.value === String(value))) {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value;
            el.appendChild(opt);
          }
        }
        el.value = value;
      });
  });
});

// Populate Edit Staff modal
document.querySelectorAll('[data-bs-target="#mEditStaff"]').forEach(btn => {
  btn.addEventListener('click', () => {
    const r = JSON.parse(btn.dataset.row);
    const f = document.getElementById('fEditStaff');
    ['id','username','full_name','role','department_id','experience_years']
      .forEach(k => {
        const el = f.querySelector(`[name="${k}"]`);
        if (el) el.value = r[k] ?? '';
      });
  });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
