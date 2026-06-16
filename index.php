<?php
$PAGE_TITLE = 'Home';
require_once __DIR__ . '/includes/header.php';
?>
<section class="hero d-flex flex-column justify-content-center" style="min-height: 85vh; background:linear-gradient(rgba(0,0,0,.6),rgba(0,0,0,.7)),url('assets/img/campus.jpg') center/cover; background-attachment: fixed;">
  <div class="container text-center text-lg-start" style="margin-top: -50px;">
    <p class="text-white fw-bold mb-2 text-uppercase" style="font-size: 1.2rem; letter-spacing: 2px;">THE BEST DIGITAL SYSTEM FOR</p>
    <h1 class="text-white mb-4" style="font-size: clamp(3rem, 6vw, 5.5rem); font-weight: 800; font-family: 'Times New Roman', Times, serif; letter-spacing: 1px; text-shadow: 0 4px 10px rgba(0,0,0,0.5);">CLEARANCE</h1>
    <div class="mt-4">
      <a href="login.php" class="btn fw-bold px-5 py-3 rounded-0 shadow-lg eduma-btn transition-colors" style="background-color: #ffc107; color: #111; font-size: 14px; letter-spacing: 1px;">LOGIN TO START</a>
    </div>
  </div>
</section>

<div id="process" class="container position-relative" style="margin-top: -110px; z-index: 10;">
  <div class="row g-0 shadow-lg">
    <div class="col-md-4 p-4 p-lg-5 text-white border-end border-secondary d-flex flex-column justify-content-center eduma-feature-box transition-all" style="background: rgba(26,26,26,0.95); min-height: 220px;">
      <div class="d-flex align-items-center gap-4">
        <i class="fa-solid fa-bolt text-warning" style="font-size: 2.5rem;"></i>
        <div>
          <h5 class="fw-bold mb-1" style="letter-spacing: 1px;">FAST<br>PROCESSING</h5>
        </div>
      </div>
      <div class="mt-4 ms-5 ps-3">
          <a href="#" class="text-warning text-decoration-none small fw-bold text-uppercase" style="letter-spacing: 1px;">View More <i class="fa-solid fa-angle-right ms-1"></i></a>
      </div>
    </div>
    
    <div class="col-md-4 p-4 p-lg-5 text-white border-end border-secondary d-flex flex-column justify-content-center eduma-feature-box transition-all" style="background: rgba(26,26,26,0.95); min-height: 220px;">
      <div class="d-flex align-items-center gap-4">
        <i class="fa-solid fa-shield-halved text-warning" style="font-size: 2.5rem;"></i>
        <div>
          <h5 class="fw-bold mb-1" style="letter-spacing: 1px;">SECURE DIGITAL<br>CLEARANCE</h5>
        </div>
      </div>
      <div class="mt-4 ms-5 ps-3">
          <a href="#" class="text-warning text-decoration-none small fw-bold text-uppercase" style="letter-spacing: 1px;">View More <i class="fa-solid fa-angle-right ms-1"></i></a>
      </div>
    </div>
    
    <div class="col-md-4 p-4 p-lg-5 text-white d-flex flex-column justify-content-center eduma-feature-box transition-all" style="background: rgba(26,26,26,0.95); min-height: 220px;">
      <div class="d-flex align-items-center gap-4">
        <i class="fa-solid fa-building-columns text-warning" style="font-size: 2.5rem;"></i>
        <div>
          <h5 class="fw-bold mb-1" style="letter-spacing: 1px;">MULTIPLE OFFICES<br>SUPPORT</h5>
        </div>
      </div>
      <div class="mt-4 ms-5 ps-3">
          <a href="#" class="text-warning text-decoration-none small fw-bold text-uppercase" style="letter-spacing: 1px;">View More <i class="fa-solid fa-angle-right ms-1"></i></a>
      </div>
    </div>
  </div>
</div>

<section id="about" class="container py-5 mt-4">
  <div class="row align-items-center g-5">
    <div class="col-md-6">
      <h2 class="fw-bold">Bule Hora University</h2>
      <p class="text-muted">Bule Hora University (BHU) — established in 2008 E.C. — is one of Ethiopia's fastest-growing public universities, hosting over 20,000 students across multiple colleges in West Guji Zone, Oromia.</p>
      <p class="text-muted">The BHU Digital Clearance System modernises the graduation and withdrawal process by connecting every office a student must pass through into a single live dashboard.</p>
      <span class="signature">Bule Hora University</span>
    </div>
    <div class="col-md-6 text-center">
      <img src="assets/img/campus.jpg" class="img-fluid rounded-3 shadow" style="max-width:100%" alt="Bule Hora University Main Gate">
      <p class="text-muted small mt-2">Bule Hora University — Main Entrance</p>
    </div>
  </div>
</section>

<section class="stats-bar">
  <div class="container">
    <div class="row">
      <div class="col-md-3 col-6"><h2>20,000+</h2><small>ACTIVE STUDENTS</small></div>
      <div class="col-md-3 col-6"><h2>5+</h2><small>CONNECTED OFFICES</small></div>
      <div class="col-md-3 col-6"><h2>100%</h2><small>PAPERLESS</small></div>
      <div class="col-md-3 col-6"><h2>24/7</h2><small>LIVE TRACKING</small></div>
    </div>
  </div>
</section>

<section id="offices" class="container pt-4 pb-5">
  <h3 class="section-title">System Modules</h3>
  <div class="row g-4">
    <?php
    $mods = [
      ['📚','Library Office','Track borrowed books and library fines in real time.'],
      ['🍽️','Cafeteria Office','Verify meal cards and returned cafeteria assets.'],
      ['🛏️','Dormitory Office','Confirm room keys, blankets and dorm property.'],
      ['💰','Finance Office','Settle tuition, cost-sharing and outstanding penalties.'],
      ['⚽','Sports / Store','Return sports kits, lab gear and university equipment.'],
      ['🎓','Registrar','Final digital seal & signature on cleared records.'],
    ];
    foreach ($mods as $m): ?>
      <div class="col-md-4">
        <div class="card module-card h-100">
          <div class="card-top"></div>
          <div class="card-body text-center p-4">
            <div class="icon"><?= $m[0] ?></div>
            <h5 class="fw-bold mt-2"><?= e($m[1]) ?></h5>
            <p class="text-muted small mb-0"><?= e($m[2]) ?></p>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="text-center mt-5">
    <a href="login.php" class="btn btn-bhu-dark">Access Portal →</a>
  </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
