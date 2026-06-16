<?php
// መጀመሪያ ሴሽኑ መጀመሩን እና የ auth ፋይል መጫኑን እናረጋግጣለን (ይህ የ e() ፈንክሽን ኤረርን ይከላከላል)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$code = trim($_GET['code'] ?? '');
$r = null;

if ($code !== '') {
    $stmt = $conn->prepare("SELECT s.full_name, s.student_id, d.name AS dept, fa.registrar_approved_at, fa.certificate_code
        FROM final_approval fa 
        JOIN students s ON s.id=fa.student_id 
        JOIN departments d ON d.id=s.department_id
        WHERE fa.certificate_code=? AND fa.registrar_status='approved'");
    $stmt->bind_param('s',$code); 
    $stmt->execute();
    $r = $stmt->get_result()->fetch_assoc();
}

/**
 * ── ኤረር መከላከያ (FALLBACK FUNCTION) ──
 * ethiopian_date_string() በሌላ ፋይል ላይ ካልተገኘ ሲስተሙ ክራሽ እንዳያደርግ እዚሁ እንሰራዋለን
 */
if (!function_exists('ethiopian_date_string')) {
    function ethiopian_date_string($gregorian_date) {
        if (!$gregorian_date) return date('F d, Y');
        return date('F d, Y', strtotime($gregorian_date));
    }
}

$PAGE_TITLE = 'Verify Certificate';
require_once __DIR__ . '/includes/header.php';
?>
<div class="container my-5">
  <div class="card shadow-sm mx-auto border-0" style="max-width:580px; border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-dark text-white py-3 border-0">
      <h5 class="fw-bold mb-0 text-center">🎓 BHU Certificate Verification</h5>
    </div>
    
    <div class="card-body p-4 bg-light">
      <form class="row g-2 mb-4" method="get">
        <div class="col-9">
          <input class="form-control form-control-lg border-2 shadow-none" 
                 name="code" 
                 value="<?= e($code) ?>" 
                 placeholder="Enter Certificate Code (e.g., BHU-XXXXXX)"
                 required
                 style="font-family: monospace; font-size: 1rem;">
        </div>
        <div class="col-3">
          <button class="btn btn-warning btn-lg w-100 fw-bold text-dark shadow-sm">Verify</button>
        </div>
      </form>
      
      <?php if ($code === ''): ?>
        <div class="text-center py-4">
          <p class="text-muted mb-0">🔒 Enter a valid university certificate code above to instantly verify its authenticity and student credentials.</p>
        </div>
        
      <?php elseif ($r): ?>
        <div class="alert alert-success border-0 shadow-sm p-4 animate__animated animate__fadeIn" style="border-left: 5px solid #198754 !important; background-color: #fff;">
          <div class="d-flex align-items-center mb-3">
            <span class="fs-3 me-2">✅</span>
            <h5 class="fw-bold text-success mb-0">Valid & Authentic Certificate</h5>
          </div>
          
          <table class="table table-sm table-borderless my-2 text-dark" style="font-size: 0.95rem;">
            <tr>
              <td class="text-muted" style="width: 30%;">Full Name:</td>
              <td class="fw-bold"><?= e($r['full_name']) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Student ID:</td>
              <td class="fw-bold"><code><?= e($r['student_id']) ?></code></td>
            </tr>
            <tr>
              <td class="text-muted">Department:</td>
              <td><?= e($r['dept']) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Issued Date:</td>
              <td class="text-secondary"><?= e(ethiopian_date_string($r['registrar_approved_at'])) ?></td>
            </tr>
            <tr>
              <td class="text-muted">Cert. Code:</td>
              <td><span class="badge bg-secondary font-monospace"><?= e($r['certificate_code']) ?></span></td>
            </tr>
          </table>
          
          <hr class="text-success opacity-25">
          <p class="small text-success mb-0 text-center fw-bold">✓ This student has officially cleared from Bule Hora University.</p>
        </div>
        
      <?php else: ?>
        <div class="alert alert-danger border-0 shadow-sm p-4 text-center animate__animated animate__shakeX" style="border-left: 5px solid #dc3545 !important; background-color: #fff;">
          <div class="fs-1 mb-2">❌</div>
          <h5 class="fw-bold text-danger">Invalid Certificate</h5>
          <p class="text-muted small mb-0">The certificate code <strong>"<?= e($code) ?>"</strong> was not found in our registry, or it is currently pending official approval from the Office of the Registrar.</p>
        </div>
      <?php endif; ?>
      
    </div>
  </div>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>