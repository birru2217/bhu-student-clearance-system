<?php
// FIX: auth.php (which includes db.php + defines role_dashboard) must be loaded first.
// Previously role_dashboard() was only defined inside header.php, which was included
// AFTER the redirect logic — causing a fatal "Call to undefined function" error.
require_once __DIR__ . '/includes/auth.php';

// Redirect already-logged-in users straight to their dashboard
if (!empty($_SESSION['user'])) {
    header('Location: dashboards/' . role_dashboard($_SESSION['user']['role']));
    exit;
}

$action = $_GET['action'] ?? '';
$showRegister = $action === 'register';
$bodyClass = $showRegister ? 'register-page' : '';
$error = '';

$depts = $conn->query('SELECT id, name FROM departments ORDER BY name')->fetch_all(MYSQLI_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($showRegister) {
        $student_id = trim($_POST['student_id'] ?? '');
        $full_name = trim($_POST['full_name'] ?? '');
        $gender = (($_POST['gender'] ?? 'M') === 'F') ? 'F' : 'M';
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $program = trim($_POST['program'] ?? 'Regular');
        $year = (int)($_POST['year'] ?? 1);
        $department_id = (int)($_POST['department_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if ($student_id === '' || $full_name === '' || $department_id === 0 || $password === '' || $confirm_password === '') {
            $error = 'Please fill all required registration fields.';
        } elseif ($password !== $confirm_password) {
            $error = 'Password and confirmation do not match.';
        } elseif (!preg_match('/^[A-Za-z0-9]+\/[A-Za-z0-9]+$/', $student_id)) {
            $error = 'Student ID must include exactly one slash, for example RU1332/12.';
        } elseif ($year < 1 || $year > 4) {
            $error = 'Year must be between 1 and 4.';
        } else {
            $deptStmt = $conn->prepare('SELECT id FROM departments WHERE id = ? LIMIT 1');
            $deptStmt->bind_param('i', $department_id);
            $deptStmt->execute();
            $deptResult = $deptStmt->get_result();
            if ($deptResult->num_rows === 0) {
                $error = 'Please select a valid department.';
            } else {
                $exists = $conn->prepare('SELECT id FROM students WHERE student_id = ? LIMIT 1');
                $exists->bind_param('s', $student_id);
                $exists->execute();
                if ($exists->get_result()->num_rows > 0) {
                    $error = 'A student with that Student ID already exists.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_BCRYPT);
                    $insert = $conn->prepare(
                        'INSERT INTO students (student_id, password_hash, full_name, gender, email, phone, program, year, department_id)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                    );
                    $insert->bind_param('sssssssii', $student_id, $password_hash, $full_name, $gender, $email, $phone, $program, $year, $department_id);

                    if ($insert->execute()) {
                        $new_id = $insert->insert_id;
                        foreach (BHU_OFFICES as $office) {
                            $ins = $conn->prepare('INSERT IGNORE INTO clearances (student_id, office, status) VALUES (?, ?, ?)');
                            $pending = 'pending';
                            $ins->bind_param('iss', $new_id, $office, $pending);
                            $ins->execute();
                        }
                        $fa = $conn->prepare('INSERT IGNORE INTO final_approval (student_id) VALUES (?)');
                        $fa->bind_param('i', $new_id);
                        $fa->execute();

                        $_SESSION['flash'] = [
                            'message' => 'Registration completed successfully. You can now log in.',
                            'type' => 'success'
                        ];
                        header('Location: login.php');
                        exit;
                    }
                    $error = 'Unable to register your account: ' . $conn->error;
                }
            }
        }
    } else {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // Try staff first
        $stmt = $conn->prepare('SELECT id, username, password_hash, full_name, role, office, department_id FROM users WHERE username = ? LIMIT 1');
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        $authed = null;
        if ($res && password_verify($password, $res['password_hash'])) {
            $authed = [
                'id'            => (int)$res['id'],
                'name'          => $res['full_name'],
                'role'          => $res['role'],
                'office'        => $res['office'],
                'department_id' => $res['department_id'] ? (int)$res['department_id'] : null,
                'username'      => $res['username'],
            ];
        } else {
            // Try student (by student_id as username)
            $stmt = $conn->prepare('SELECT id, student_id, password_hash, full_name, department_id, active FROM students WHERE student_id = ? LIMIT 1');
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $s = $stmt->get_result()->fetch_assoc();
            if ($s && !$s['active']) {
                $error = 'Your account has been deactivated. Contact admin.';
            } elseif ($s && password_verify($password, $s['password_hash'])) {
                $authed = [
                    'id'            => (int)$s['id'],
                    'name'          => $s['full_name'],
                    'role'          => 'student',
                    'office'        => null,
                    'department_id' => (int)$s['department_id'],
                    'username'      => $s['student_id'],
                ];
            }
        }

        if ($authed) {
            $_SESSION['user'] = $authed;
            header('Location: dashboards/' . role_dashboard($authed['role']));
            exit;
        }
        $error = 'Invalid username or password.';
    }
}

$PAGE_TITLE = $showRegister ? 'Register' : 'Login';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container">
  <?php if ($showRegister): ?>
    <div class="login-card register-card">
      <div class="image-panel">
        <div class="panel-content text-white">
          <h3 class="fw-bold">Join BHU Clearance</h3>
          <p class="small text-white-75">Register to track your clearance progress, submit requests, and monitor approvals from every office.</p>
          <div class="mt-4">
            <a href="login.php" class="btn btn-bhu-yellow">Back to Login</a>
          </div>
        </div>
      </div>
      <div class="form-panel p-4">
        <div class="head">
          <h4>Create your BHU account</h4>
          <small class="text-muted">Register using your university details</small>
        </div>
        <div class="p-4">
          <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
          <form method="post" action="login.php?action=register">
            <div class="row g-3">
              <div class="col-12"><label class="form-label small fw-bold">Student ID</label><input name="student_id" class="form-control" required autofocus></div>
              <div class="col-12"><label class="form-label small fw-bold">Full Name</label><input name="full_name" class="form-control" required></div>
              <div class="col-6"><label class="form-label small fw-bold">Gender</label>
                <select name="gender" class="form-select"><option value="M">Male</option><option value="F">Female</option></select>
              </div>
              <div class="col-6"><label class="form-label small fw-bold">Year</label>
                <select name="year" class="form-select"><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select>
              </div>
              <div class="col-12"><label class="form-label small fw-bold">Department</label>
                <select name="department_id" class="form-select" required>
                  <option value="">Select department</option>
                  <?php foreach ($depts as $dept): ?>
                    <option value="<?= (int)$dept['id'] ?>"><?= e($dept['name']) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-12"><label class="form-label small fw-bold">Email</label><input name="email" type="email" class="form-control"></div>
              <div class="col-12"><label class="form-label small fw-bold">Phone</label><input name="phone" class="form-control"></div>
              <div class="col-12"><label class="form-label small fw-bold">Program</label><input name="program" class="form-control" value="Regular"></div>
              <div class="col-6"><label class="form-label small fw-bold">Password</label><input type="password" name="password" class="form-control" required></div>
              <div class="col-6"><label class="form-label small fw-bold">Confirm Password</label><input type="password" name="confirm_password" class="form-control" required></div>
            </div>
            <button class="btn btn-bhu-dark w-100 mt-3">Register</button>
          </form>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="login-card bg-white">
      <div class="head">
        <h4>BHU <span>Clearance</span> Portal</h4>
        <small class="text-muted">Sign in with your university credentials</small>
      </div>
      <div class="p-4">
        <?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
        <form method="post">
          <div class="mb-3">
            <label class="form-label small fw-bold">Username / Student ID</label>
            <input name="username" class="form-control" required autofocus>
          </div>
          <div class="mb-3">
            <label class="form-label small fw-bold">Password</label>
            <input type="password" name="password" class="form-control" required>
          </div>
          <button class="btn btn-bhu-dark w-100">Sign In</button>
        </form>
        <hr>
        <details class="small text-muted">
          <summary>Sample logins (password: <code>password</code>)</summary>
          <ul class="mt-2 mb-0">
            <li><strong>admin</strong> · admin</li>
            <li><strong>office</strong> · library / cafeteria / dormitory / finance / sports</li>
            <li><strong>dept head</strong> · depthead_cse</li>
            <li><strong>registrar</strong> · registrar</li>
            <li><strong>student</strong> · BHU/0001/16</li>
          </ul>
        </details>
      </div>
    </div>
  <?php endif; ?>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
