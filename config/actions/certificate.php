<?php
/**
 * Renders the clearance certificate as a printable HTML page.
 * Browsers can "Save as PDF" via Ctrl+P. (Avoids requiring dompdf install.)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../vendor/tinyqr.php';
require_login(['student','registrar','admin','depthead']);

$sid = (int)($_GET['sid'] ?? 0);
// Students can only see their own
if ($_SESSION['user']['role'] === 'student' && $sid !== (int)$_SESSION['user']['id']) {
    http_response_code(403); die('Access denied.');
}

$stmt = $conn->prepare("
  SELECT s.*, d.name AS dept, d.college, fa.*
  FROM students s
  JOIN departments d ON d.id=s.department_id
  JOIN final_approval fa ON fa.student_id=s.id
  WHERE s.id=?");
$stmt->bind_param('i',$sid); $stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
if (!$r) die('Student not found.');

if ($r['registrar_status'] !== 'approved') {
    die('Certificate is not yet available — registrar approval pending.');
}

$verify_url = (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST']
            . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/verify.php?code=' . urlencode($r['certificate_code']);
$qr = TinyQR::dataUri($verify_url, 160);
?>
<!doctype html><html><head>
<meta charset="utf-8"><title>Clearance Certificate · <?= e($r['student_id']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800&family=Poppins&display=swap" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f3f3f3;margin:0;padding:30px}
.cert{max-width:880px;margin:auto;background:#fff;padding:60px;border:14px double #b1822a;position:relative;box-shadow:0 8px 30px rgba(0,0,0,.1)}
.cert:before{content:"";position:absolute;inset:14px;border:1px solid #b1822a}
h1{font-family:'Cinzel',serif;color:#b1822a;text-align:center;margin:0;font-size:34px;letter-spacing:3px}
h2{font-family:'Cinzel',serif;text-align:center;font-size:22px;color:#111;margin:8px 0 36px}
.name{font-family:'Cinzel',serif;font-size:34px;text-align:center;border-bottom:2px solid #111;display:inline-block;padding:6px 30px;margin:18px 0}
.center{text-align:center}
.body p{font-size:15px;line-height:1.8;text-align:center;color:#333;max-width:680px;margin:0 auto}
.foot{display:flex;justify-content:space-between;margin-top:60px;align-items:end}
.seal{border:4px double #b1822a;color:#b1822a;border-radius:50%;width:130px;height:130px;display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-weight:800;text-align:center;font-size:11px;line-height:1.3;transform:rotate(-10deg);letter-spacing:1px}
.sign{text-align:center}
.sign .scribble{font-family:'Brush Script MT',cursive;font-size:30px;color:#111}
.sign hr{border:0;border-top:1px solid #111;margin:4px 0}
.qr{text-align:center}
.qr img{width:140px;border:6px solid #fff;outline:1px solid #ddd}
.print-btn{display:block;width:200px;margin:20px auto;padding:10px;background:#f5b921;color:#111;text-align:center;border:none;font-weight:700;border-radius:30px;cursor:pointer;text-decoration:none}
@media print {.print-btn{display:none} body{background:#fff;padding:0} .cert{box-shadow:none;border-color:#b1822a}}
</style></head><body>
<a class="print-btn" href="javascript:window.print()">🖨 Print / Save as PDF</a>
<div class="cert">
  <h1>BULE HORA UNIVERSITY</h1>
  <h2>Certificate of Clearance</h2>
  <div class="center">
    <p>This is to certify that</p>
    <div class="name"><?= e($r['full_name']) ?></div>
    <p>bearing Student ID <strong><?= e($r['student_id']) ?></strong>, of the
       <strong><?= e($r['dept']) ?></strong> Department, <em><?= e($r['college']) ?></em>,
       has duly completed all institutional obligations and has been cleared by the
       Library, Cafeteria, Dormitory, Finance and Sports/Store offices, and approved
       by the Department Head and the Office of the Registrar of Bule Hora University.</p>
  </div>
  <div class="foot">
    <div class="seal">BHU<br>OFFICIAL<br>SEAL<br><?= date('Y') ?></div>
    <div class="sign">
      <div class="scribble">S. Worku</div>
      <hr>
      <small><strong>Registrar</strong><br>Bule Hora University</small>
    </div>
    <div class="qr">
      <img src="<?= $qr ?>" alt="QR">
      <div style="font-size:10px;color:#666;margin-top:4px"><?= e($r['certificate_code']) ?></div>
    </div>
  </div>
  <div style="text-align:center;margin-top:24px;font-size:11px;color:#888">
    Issued on <?= e(ethiopian_date_string($r['registrar_approved_at'])) ?> · Verify at: <?= e($verify_url) ?>
  </div>
</div>
</body></html>
