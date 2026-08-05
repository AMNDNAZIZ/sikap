<?php
$db = \Config\Database::connect();
$query = $db->query("SELECT logo, name FROM school_profile LIMIT 1");
$row = $query->getRow();

// Siapkan fallback logo jika data kosong
$logo = base_url('uploads/logo/default.png');
if ($row && !empty($row->logo)) {
  $logo = base_url('uploads/logo/' . $row->logo);
}
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <title>Login - CBT System</title>
  <link rel="icon" type="image/png" href="<?= esc($logo) ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css"
    crossorigin="anonymous" referrerpolicy="no-referrer">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #0833f4ff 0%, #04eb74ff 100%);
      --secondary-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
      --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
      background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 20px;
      position: relative;
      overflow: hidden;
    }

    /* Animated Background Elements */
    body::before,
    body::after {
      content: '';
      position: absolute;
      border-radius: 50%;
      opacity: 0.1;
      animation: float 20s infinite ease-in-out;
    }

    body::before {
      width: 600px;
      height: 600px;
      background: var(--primary-gradient);
      top: -300px;
      left: -300px;
      animation-delay: 0s;
    }

    body::after {
      width: 400px;
      height: 400px;
      background: var(--secondary-gradient);
      bottom: -200px;
      right: -200px;
      animation-delay: 5s;
    }

    @keyframes float {

      0%,
      100% {
        transform: translate(0, 0) rotate(0deg);
      }

      33% {
        transform: translate(30px, -30px) rotate(120deg);
      }

      66% {
        transform: translate(-20px, 20px) rotate(240deg);
      }
    }

    .login-container {
      width: 100%;
      max-width: 1000px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: white;
      border-radius: 25px;
      overflow: hidden;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.15);
      position: relative;
      z-index: 1;
    }

    /* Left Panel - Branding */
    .login-brand {
      background: var(--primary-gradient);
      padding: 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .login-brand::before {
      content: '';
      position: absolute;
      width: 300px;
      height: 300px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      top: -150px;
      right: -150px;
    }

    .login-brand::after {
      content: '';
      position: absolute;
      width: 200px;
      height: 200px;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      bottom: -100px;
      left: -100px;
    }

    .brand-content {
      position: relative;
      z-index: 1;
      text-align: center;
    }

    .logo-wrapper {
      width: 120px;
      height: 120px;
      margin: 0 auto 2rem;
      background: white;
      border-radius: 25px;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      padding: 15px;
    }

    .logo-wrapper img {
      width: 100%;
      height: 100%;
      object-fit: contain;
    }

    .logo-wrapper.no-image {
      font-size: 3rem;
      font-weight: 700;
      background: var(--primary-gradient);
      color: white;
    }

    .brand-title {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 0.5rem;
      text-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    }

    .brand-subtitle {
      font-size: 1.1rem;
      opacity: 0.95;
      margin-bottom: 2rem;
      line-height: 1.6;
    }

    .brand-features {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      text-align: left;
      margin-top: 2rem;
    }

    .feature-item {
      display: flex;
      align-items: center;
      gap: 1rem;
      padding: 0.75rem;
      background: rgba(255, 255, 255, 0.1);
      border-radius: 12px;
      backdrop-filter: blur(10px);
    }

    .feature-icon {
      width: 40px;
      height: 40px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 10px;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 1.2rem;
    }

    /* Right Panel - Login Form */
    .login-form-panel {
      padding: 3rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
    }

    .form-header {
      margin-bottom: 2rem;
    }

    .form-header h2 {
      font-size: 1.75rem;
      font-weight: 700;
      color: #2d3748;
      margin-bottom: 0.5rem;
    }

    .form-header p {
      color: #718096;
      font-size: 0.95rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-label {
      display: block;
      font-weight: 600;
      color: #2d3748;
      margin-bottom: 0.5rem;
      font-size: 0.95rem;
    }

    .input-wrapper {
      position: relative;
    }

    .input-icon {
      position: absolute;
      left: 1rem;
      top: 50%;
      transform: translateY(-50%);
      color: #a0aec0;
      font-size: 1.1rem;
    }

    .form-control {
      width: 100%;
      padding: 0.875rem 1rem 0.875rem 3rem;
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #f7fafc;
    }

    .form-control:focus {
      outline: none;
      border-color: #667eea;
      background: white;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .form-control::placeholder {
      color: #cbd5e0;
    }

    .password-toggle {
      position: absolute;
      right: 1rem;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #a0aec0;
      cursor: pointer;
      font-size: 1.1rem;
      padding: 0.25rem;
      transition: color 0.3s ease;
    }

    .password-toggle:hover {
      color: #667eea;
    }

    .btn-login {
      width: 100%;
      padding: 1rem;
      background: var(--primary-gradient);
      border: none;
      border-radius: 12px;
      color: white;
      font-size: 1.05rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    }

    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
    }

    .btn-login:active {
      transform: translateY(0);
    }

    .alert {
      border-radius: 12px;
      border: none;
      padding: 1rem;
      margin-bottom: 1.5rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .alert-danger {
      background: #fff5f5;
      color: #c53030;
    }

    .alert i {
      font-size: 1.2rem;
    }

    .login-footer {
      margin-top: 2rem;
      text-align: center;
      color: #718096;
      font-size: 0.9rem;
    }

    .login-footer a {
      color: #667eea;
      text-decoration: none;
      font-weight: 600;
    }

    .login-footer a:hover {
      text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      body {
        padding: 0;
        background: white;
      }

      body {
        overflow-y: auto;
        align-items: flex-start;
      }


      body::before,
      body::after {
        display: none;
      }

      .login-container {
        grid-template-columns: 1fr;
        border-radius: 0;
        box-shadow: none;
        min-height: 100vh;
      }

      .login-brand {
        padding: 2rem 1.5rem;
        min-height: auto;
      }

      .logo-wrapper {
        width: 90px;
        height: 90px;
        margin-bottom: 1.5rem;
      }

      .brand-title {
        font-size: 1.5rem;
      }

      .brand-subtitle {
        font-size: 1rem;
        margin-bottom: 1rem;
      }

      .brand-features {
        display: none;
      }

      .login-form-panel {
        padding: 2rem 1.5rem;
      }

      .form-header h2 {
        font-size: 1.5rem;
      }
    }

    /* Loading Animation */
    .btn-login.loading {
      pointer-events: none;
      opacity: 0.7;
    }

    .btn-login.loading::after {
      content: '';
      width: 16px;
      height: 16px;
      border: 2px solid white;
      border-top-color: transparent;
      border-radius: 50%;
      display: inline-block;
      margin-left: 10px;
      animation: spin 0.6s linear infinite;
    }

    @keyframes spin {
      to {
        transform: rotate(360deg);
      }
    }
  </style>
</head>

<body>
  <div class="login-container">
    <!-- Left Panel - Branding -->
    <div class="login-brand">
      <div class="brand-content">
        <div class="logo-wrapper <?= ($row && !empty($row->logo)) ? '' : 'no-image' ?>">
          <?php if ($row && !empty($row->logo)): ?>
            <img src="<?= base_url('uploads/logo/' . $row->logo) ?>" alt="Logo Sekolah">
          <?php else: ?>
            CBT
          <?php endif; ?>
        </div>

        <h1 class="brand-title">
          <?= $row && !empty($row->name) ? strtoupper(htmlspecialchars($row->name)) : 'CBT System' ?>
        </h1>

        <p class="brand-subtitle">
          Computer Based Test<br>
          Portal Ujian Online Modern
        </p>

        <div class="brand-features">
          <div class="feature-item">
            <div class="feature-icon">
              <i class="bi bi-shield-check"></i>
            </div>
            <div>
              <strong>Aman & Terpercaya</strong><br>
              <small>Sistem keamanan berlapis</small>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">
              <i class="bi bi-lightning-charge"></i>
            </div>
            <div>
              <strong>Cepat & Responsif</strong><br>
              <small>Akses dari berbagai perangkat</small>
            </div>
          </div>
          <div class="feature-item">
            <div class="feature-icon">
              <i class="bi bi-graph-up"></i>
            </div>
            <div>
              <strong>Real-time Monitoring</strong><br>
              <small>Pantau progress ujian langsung</small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Right Panel - Login Form -->
    <div class="login-form-panel">
      <div class="form-header">
        <h2>Selamat Datang!</h2>
        <p>Silakan masuk dengan akun Anda untuk melanjutkan</p>
      </div>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
          <i class="bi bi-exclamation-circle"></i>
          <div>
            <strong>Login Gagal!</strong><br>
            <?= session()->getFlashdata('error') ?>
          </div>
        </div>
        
        <?php if (session()->getFlashdata('show_force_login')): ?>
          <div class="alert" style="background: #fff3cd; color: #856404; border-left: 4px solid #ffc107;">
            <i class="bi bi-info-circle"></i>
            <div style="flex: 1;">
              <strong>Paksa Login?</strong><br>
              Jika Anda yakin tidak sedang login di perangkat lain, klik tombol di bawah untuk paksa login.
            </div>
          </div>
          <button type="button" class="btn-login" id="btnForceLogin" style="background: linear-gradient(135deg, #f5576c 0%, #f093fb 100%); margin-bottom: 1rem;">
            <i class="bi bi-shield-exclamation"></i> Paksa Login (Force Login)
          </button>
        <?php endif ?>
      <?php endif ?>

      <form method="post" action="<?= base_url('auth/attemptLogin') ?>" id="loginForm">
        <?= csrf_field() ?>
        <input type="hidden" name="force_login" id="forceLoginInput" value="0">

        <div class="form-group">
          <label for="username" class="form-label">
            <i class="bi bi-person"></i> Username
          </label>
          <div class="input-wrapper">
            <i class="bi bi-person-circle input-icon"></i>
            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username Anda"
              required autocomplete="username" value="<?= old('username') ?>">
          </div>
        </div>

        <div class="form-group">
          <label for="password" class="form-label">
            <i class="bi bi-lock"></i> Password
          </label>
          <div class="input-wrapper">
            <i class="bi bi-lock-fill input-icon"></i>
            <input type="password" name="password" id="password" class="form-control"
              placeholder="Masukkan password Anda" required autocomplete="current-password">
            <button type="button" class="password-toggle" id="togglePassword">
              <i class="bi bi-eye"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-login" id="btnLogin">
          <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
        </button>
      </form>

      <div class="login-footer">
        &copy; <?= date('Y'); ?> <strong>SakaSalika CBT 2.1.1</strong><br>
        <small>Powered by SakaSalika Host VPS</small>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
  <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
  <script>
    // Password Toggle
    document.getElementById('togglePassword').addEventListener('click', function () {
      const passwordInput = document.getElementById('password');
      const icon = this.querySelector('i');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
      } else {
        passwordInput.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
      }
    });

    // Force Login Button
    const btnForceLogin = document.getElementById('btnForceLogin');
    if (btnForceLogin) {
      btnForceLogin.addEventListener('click', function() {
        // Set force_login flag
        document.getElementById('forceLoginInput').value = '1';
        
        // Submit form
        const btn = document.getElementById('btnLogin');
        btn.classList.add('loading');
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses Force Login...';
        
        document.getElementById('loginForm').submit();
      });
    }

    // Form Submit Loading
    document.getElementById('loginForm').addEventListener('submit', function () {
      const btn = document.getElementById('btnLogin');
      if (!btn.classList.contains('loading')) {
        btn.classList.add('loading');
        btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
      }
    });

    // Auto focus on username
    document.getElementById('username').focus();
  </script>
</body>

</html>