</main>
<footer id="contact" class="bhu-footer text-center py-5 mt-5 transition-colors" style="background-color: var(--footer-bg, #111); border-top: 4px solid var(--bhu-yellow);">
  <div class="container">
    <div class="row justify-content-center mb-4">
      <div class="col-md-8 d-flex flex-column flex-md-row justify-content-center gap-3 gap-md-5">
        <div class="text-muted"><i class="fa-solid fa-phone text-warning me-2"></i> <span style="color: var(--text-color)">(00) 123 456 789</span></div>
        <div class="text-muted"><i class="fa-solid fa-envelope text-warning me-2"></i> <span style="color: var(--text-color)">hello@bhu.edu.et</span></div>
        <div class="text-muted"><i class="fa-solid fa-location-dot text-warning me-2"></i> <span style="color: var(--text-color)">West Guji Zone, Oromia, Ethiopia</span></div>
      </div>
    </div>
    <p class="mb-1" style="color: var(--text-color)"><strong>Bule Hora University</strong> · Digital Clearance System</p>
    <small class="text-muted">© <?= date('Y') ?> BHU. Paperless · Secure · Transparent.</small>
  </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', () => {
    const toggleBtn = document.getElementById('themeToggleBtn');
    const icon = toggleBtn.querySelector('i');
    
    // Set initial icon state
    if (document.documentElement.classList.contains('dark-theme')) {
      icon.classList.remove('fa-moon');
      icon.classList.add('fa-sun');
    }

    toggleBtn.addEventListener('click', () => {
      document.documentElement.classList.toggle('dark-theme');
      
      if (document.documentElement.classList.contains('dark-theme')) {
        localStorage.setItem('theme', 'dark');
        icon.classList.remove('fa-moon');
        icon.classList.add('fa-sun');
      } else {
        localStorage.setItem('theme', 'light');
        icon.classList.remove('fa-sun');
        icon.classList.add('fa-moon');
      }
    });
  });
</script>
</body>
</html>
