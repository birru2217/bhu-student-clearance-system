<?php
require_once __DIR__ . '/auth.php';
$user = $_SESSION['user'] ?? null;
$base = bhu_base();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= isset($PAGE_TITLE) ? e($PAGE_TITLE) . ' · ' : '' ?>BHU Clearance</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="<?= e($base) ?>assets/css/style.css">
<script>
  // Dark mode init
  if (localStorage.getItem('theme') === 'dark' || (!('theme' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark-theme');
  }
</script>
</head>
<body class="transition-colors">
<!-- EDUMA-style Top Bar -->
<div class="d-none d-lg-block text-white py-2" style="background-color: var(--topbar-bg, #1a1a1a); font-size: 13px; border-bottom: 1px solid rgba(255,255,255,0.05);">
  <div class="container d-flex justify-content-end align-items-center">
    <div class="d-flex align-items-center gap-4">
      <a href="<?= e($base) ?>login.php?action=register" class="text-white text-decoration-none fw-semibold hover-warning transition-colors">Register</a>
      <a href="<?= e($base) ?>login.php" class="text-white text-decoration-none fw-semibold hover-warning transition-colors">Login</a>
    </div>
  </div>
</div>

<!-- EDUMA-style Navbar -->
<nav class="navbar navbar-expand-lg bhu-nav sticky-top py-3 transition-colors" style="background-color: var(--nav-bg); border-bottom: 1px solid var(--border-color);">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center gap-2" href="<?= e($base) ?>index.php">
      <div class="d-flex align-items-center justify-content-center border border-warning transition-colors" style="width:40px; height:40px; border-radius:4px;">
        <i class="fa-solid fa-graduation-cap text-warning fs-5"></i>
      </div>
      <span class="text-white fs-4" style="font-weight: 800; letter-spacing: 1px;">BHU <span class="text-warning">CLEARANCE</span></span>
    </a>
    <button class="navbar-toggler border-secondary" data-bs-toggle="collapse" data-bs-target="#nav"><i class="fa-solid fa-bars text-white"></i></button>
    <div id="nav" class="collapse navbar-collapse justify-content-end">
      <ul class="navbar-nav align-items-lg-center gap-lg-4 text-uppercase" style="font-size: 13px; font-weight: 600; letter-spacing: 0.5px;">
        <li class="nav-item"><a class="nav-link text-warning hover-warning" href="<?= e($base) ?>index.php">HOME</a></li>
        <li class="nav-item"><a class="nav-link hover-warning" href="<?= e($base) ?>index.php#about">ABOUT</a></li>
        <li class="nav-item"><a class="nav-link hover-warning" href="<?= e($base) ?>index.php#process">PROCESS</a></li>
        <li class="nav-item"><a class="nav-link hover-warning" href="<?= e($base) ?>index.php#offices">OFFICES</a></li>
        <li class="nav-item"><a class="nav-link hover-warning" href="#contact">CONTACT</a></li>
        <?php if ($user): ?>
          <li class="nav-item"><a class="nav-link text-warning" href="<?= e($base) ?>dashboards/<?= e(role_dashboard($user['role'])) ?>">DASHBOARD</a></li>
          <li class="nav-item"><a class="nav-link text-white" href="<?= e($base) ?>logout.php">LOGOUT</a></li>
        <?php endif; ?>
        <li class="nav-item ms-2">
          <button id="themeToggleBtn" class="btn btn-link text-white hover-warning p-0" title="Toggle Dark/Light Mode">
            <i class="fa-solid fa-moon fs-5"></i>
          </button>
        </li>
      </ul>
    </div>
  </div>
</nav>
<main class="page-bg transition-colors pb-5<?= isset($bodyClass) && $bodyClass ? ' ' . e($bodyClass) : '' ?>">
