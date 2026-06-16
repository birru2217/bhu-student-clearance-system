<?php
/**
 * Renders the clearance certificate as a printable HTML page.
 * Browsers can "Save as PDF" via Ctrl+P. (Avoids requiring dompdf install.)
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// የ TinyQR ላይብረሪ መኖሩን እያረጋገጥን ኤረር እንዳይፈጥር በደህንነት እንጭነዋለን
if (file_exists(__DIR__ . '/../vendor/tinyqr.php')) {
    require_once __DIR__ . '/../vendor/tinyqr.php';
}

require_login(['student','registrar','admin','depthead']);

$sid = (int)($_GET['sid'] ?? 0);
// Students can only see their own
if ($_SESSION['user']['role'] === 'student' && $sid !== (int)$_SESSION['user']['id']) {
    http_response_code(403); die('Access denied.');
}

$stmt = $conn->prepare("
  SELECT s.*, d.name AS dept, d.college, fa.*, u.full_name AS registrar_name
  FROM students s
  JOIN departments d ON d.id=s.department_id
  JOIN final_approval fa ON fa.student_id=s.id
  LEFT JOIN users u ON u.id = fa.registrar_approved_by
  WHERE s.id=?");
$stmt->bind_param('i',$sid); $stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
if (!$r) die('Student not found.');
$r['registrar_name'] = $r['registrar_name'] ?: 'Registrar';

if ($r['registrar_status'] !== 'approved') {
    die('Certificate is not yet available — registrar approval pending.');
}

$verify_url = (isset($_SERVER['HTTPS'])?'https':'http').'://'.$_SERVER['HTTP_HOST']
            . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/verify.php?code=' . urlencode($r['certificate_code']);

// TinyQR ፋይል ከተገኘ QR ይሠራል፣ ከሌለ ግን በምስል እንዳይበላሽ አማራጭ ሎጂክ
$qr = '';
if (class_exists('TinyQR')) {
    $qr = TinyQR::dataUri($verify_url, 160);
}

/**
 * ── ኤረር መከላከያ (FALLBACK FUNCTION) ──
 * ethiopian_date_string() በሌላ ፋይል ላይ ካልተገኘ ሲስተሙ ክራሽ እንዳያደርግ እዚሁ እንሰራዋለን
 */
if (!function_exists('ethiopian_date_string')) {
    function ethiopian_date_string($gregorian_date) {
        if (!$gregorian_date) return date('F d, Y');
        // ዋናው የሃበሻ ቀን መቀየሪያህ እስኪመጣ ድረስ እንደ አማራጭ መደበኛውን ቀን ያሳያል
        return date('F d, Y', strtotime($gregorian_date));
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>Clearance Certificate · <?= e($r['student_id']) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Cinzel:wght@600;800&family=Poppins:wght@300;400;600&family=Reenie+Beanie&display=swap" rel="stylesheet">
<style>
body{font-family:'Poppins',sans-serif;background:#f3f3f3;margin:0;padding:30px}
.cert{max-width:880px;margin:auto;background:#fff;padding:60px;border:14px double #b1822a;position:relative;box-shadow:0 8px 30px rgba(0,0,0,.1)}
.cert:before{content:"";position:absolute;inset:14px;border:1px solid #b1822a}
h1{font-family:'Cinzel',serif;color:#b1822a;text-align:center;margin:0;font-size:34px;letter-spacing:3px}
h2{font-family:'Cinzel',serif;text-align:center;font-size:22px;color:#111;margin:8px 0 36px;letter-spacing:1px}
.name{font-family:'Cinzel',serif;font-size:32px;font-weight:800;text-align:center;border-bottom:2px dotted #b1822a;display:inline-block;padding:6px 30px;margin:18px 0;color:#111}
.center{text-align:center}
.body p{font-size:16px;line-height:1.9;text-align:center;color:#333;max-width:700px;margin:0 auto}
.foot{display:flex;justify-content:space-between;margin-top:50px;align-items:end;padding:0 20px}
.seal{border:4px double #dc3545;color:#dc3545;border-radius:50%;width:130px;height:130px;display:flex;align-items:center;justify-content:center;font-family:'Cinzel',serif;font-weight:800;text-align:center;font-size:11px;line-height:1.3;transform:rotate(-12deg);letter-spacing:1px;opacity:0.85;background:rgba(220,53,69,0.02)}
.sign{text-align:center;min-width:200px}
.sign .scribble{font-family:'Reenie Beanie',cursive;font-size:42px;color:#00249c;line-height:0.5;margin-bottom:10px;transform:rotate(-3deg);font-weight:bold}
.sign hr{border:0;border-top:1px solid #333;margin:4px 0}
.qr{text-align:center}
.qr img{width:125px;height:125px;border:4px solid #fff;outline:1px solid #ddd;background:#fff}
.print-btn{display:block;width:220px;margin:20px auto;padding:12px;background:#111;color:#ffc107;text-align:center;border:none;font-weight:700;border-radius:30px;cursor:pointer;text-decoration:none;box-shadow:0 4px 12px rgba(0,0,0,0.15);transition:all 0.2s}
.print-btn:hover{background:#ffc107;color:#111}
@media print {.print-btn{display:none} body{background:#fff;padding:0} .cert{box-shadow:none;border-color:#b1822a}}
</style>
</head>
<body>

<a class="print-btn" href="javascript:window.print()">🖨️ Print / Save as PDF</a>

<div class="cert">
  <h1>BULE HORA UNIVERSITY</h1>
  <h2>Certificate of Clearance</h2>
  
  <div class="center body">
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
      <div class="scribble"><?= e($r['registrar_name']) ?></div>
      <hr>
      <small><strong>Registrar Office Authorized</strong><br>Bule Hora University</small>
    </div>
    
    <div class="qr">
      <?php if (!empty($qr)): ?>
        <img src="<?= $qr ?>" alt="Verification QR Code">
      <?php else: ?>
        <div style="width:125px;height:125px;border:1px dashed #ccc;display:flex;align-items:center;justify-content:center;font-size:10px;color:#999">QR Code Pending</div>
      <?php endif; ?>
      <div style="font-size:11px;font-family:monospace;color:#444;margin-top:6px;font-weight:bold;"><?= e($r['certificate_code']) ?></div>
    </div>
  </div>
  
  <div style="text-align:center;margin-top:35px;font-size:12px;color:#666;border-top:1px dashed #ddd;padding-top:15px;">
    Issued on <?= e(ethiopian_date_string($r['registrar_approved_at'])) ?> · Verify online at: <span style="color:#0d6efd;text-decoration:underline;"><?= e($verify_url) ?></span>
  </div>
</div>

</body>
</html>