<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login | <?= esc($school['name'] ?? 'SIKAP') ?></title>
  <link rel="icon" type="image/png" href="<?= base_url('uploads/logo/' . ($school['logo'] ?? '')) ?>">
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      min-height: 100dvh;
      background: #f1f5f9;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    /* ═══════════════════════════════
       DESKTOP: two-column card
    ═══════════════════════════════ */
    .wrap {
      width: 100%;
      max-width: 960px;
      display: grid;
      grid-template-columns: 1fr 1fr;
      background: #fff;
      border-radius: 28px;
      overflow: hidden;
      box-shadow: 0 24px 64px rgba(0,0,0,0.14);
      margin: 24px;
    }

    /* Left gradient panel */
    .panel-brand {
      background: linear-gradient(145deg, #1a56db 0%, #06b6d4 100%);
      padding: 3rem 2.5rem;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      color: #fff;
      position: relative;
      overflow: hidden;
    }
    .panel-brand::before {
      content: '';
      position: absolute;
      width: 320px; height: 320px;
      background: rgba(255,255,255,0.08);
      border-radius: 50%;
      top: -140px; right: -140px;
    }
    .panel-brand::after {
      content: '';
      position: absolute;
      width: 220px; height: 220px;
      background: rgba(255,255,255,0.06);
      border-radius: 50%;
      bottom: -90px; left: -90px;
    }
    .brand-inner { position: relative; z-index: 1; text-align: center; width: 100%; }

    .logo-box {
      width: 110px; height: 110px;
      background: #fff;
      border-radius: 24px;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 1.5rem;
      box-shadow: 0 10px 30px rgba(0,0,0,0.2);
      padding: 12px; overflow: hidden;
    }
    .logo-box img { width: 100%; height: 100%; object-fit: contain; }
    .logo-box .bi { font-size: 3rem; color: #1a56db; }

    .brand-school {
      font-size: 1.5rem; font-weight: 800;
      line-height: 1.3; margin-bottom: 0.5rem;
      text-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }
    .brand-sub {
      font-size: 0.9rem; opacity: 0.88;
      line-height: 1.6; margin-bottom: 2rem;
    }
    .features { display: flex; flex-direction: column; gap: 0.7rem; width: 100%; }
    .feat {
      display: flex; align-items: center; gap: 0.85rem;
      background: rgba(255,255,255,0.13);
      border-radius: 14px; padding: 0.7rem 1rem;
      text-align: left;
    }
    .feat-ic {
      width: 36px; height: 36px; flex-shrink: 0;
      background: rgba(255,255,255,0.2);
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1rem;
    }
    .feat-tx strong { display: block; font-size: 0.85rem; }
    .feat-tx small  { opacity: 0.8; font-size: 0.76rem; }

    /* Right form panel */
    .panel-form {
      padding: 3rem 2.75rem;
      display: flex; flex-direction: column; justify-content: center;
    }
    .form-head { margin-bottom: 2rem; }
    .form-head h2 { font-size: 1.7rem; font-weight: 800; color: #0f172a; margin-bottom: 0.35rem; }
    .form-head p  { color: #64748b; font-size: 0.92rem; }

    .field { margin-bottom: 1.35rem; }
    .field label {
      display: block; font-weight: 600; font-size: 0.88rem;
      color: #1e293b; margin-bottom: 0.5rem;
    }
    .field label i { margin-right: 4px; }
    .inp-wrap { position: relative; }
    .inp-wrap .ico {
      position: absolute; left: 1rem; top: 50%;
      transform: translateY(-50%);
      color: #94a3b8; font-size: 1rem; pointer-events: none;
    }
    .inp-wrap input {
      width: 100%;
      padding: 0.85rem 1rem 0.85rem 2.8rem;
      border: 2px solid #e2e8f0;
      border-radius: 14px;
      font-size: 0.95rem; font-family: inherit;
      background: #f8fafc; color: #0f172a;
      transition: border-color .25s, box-shadow .25s, background .25s;
    }
    .inp-wrap input:focus {
      outline: none; border-color: #1a56db;
      background: #fff; box-shadow: 0 0 0 4px rgba(26,86,219,.1);
    }
    .inp-wrap input::placeholder { color: #cbd5e1; }
    .toggle-pw {
      position: absolute; right: 0.9rem; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      color: #94a3b8; cursor: pointer; font-size: 1rem;
      padding: 4px; transition: color .2s;
    }
    .toggle-pw:hover { color: #1a56db; }

    .extras {
      display: flex; justify-content: space-between; align-items: center;
      margin-bottom: 1.5rem;
    }
    .extras label { display: flex; align-items: center; gap: 0.4rem; font-size: 0.85rem; color: #64748b; cursor: pointer; }
    .extras input[type=checkbox] { accent-color: #1a56db; width: 15px; height: 15px; }
    .extras a { font-size: 0.85rem; color: #1a56db; text-decoration: none; font-weight: 600; }
    .extras a:hover { text-decoration: underline; }

    .btn-login {
      width: 100%; padding: 0.95rem;
      background: linear-gradient(135deg, #1a56db 0%, #06b6d4 100%);
      border: none; border-radius: 14px;
      color: #fff; font-size: 1rem; font-weight: 700;
      font-family: inherit; cursor: pointer;
      display: flex; align-items: center; justify-content: center; gap: 0.5rem;
      transition: transform .2s, box-shadow .2s;
      box-shadow: 0 4px 18px rgba(26,86,219,.3);
    }
    .btn-login:hover { transform: translateY(-2px); box-shadow: 0 8px 24px rgba(26,86,219,.4); }
    .btn-login:active { transform: translateY(0); }
    .btn-login.loading { opacity: .75; pointer-events: none; }

    .alert-err {
      background: #fef2f2; border-left: 4px solid #ef4444;
      border-radius: 12px; padding: .9rem 1rem;
      display: flex; align-items: flex-start; gap: .75rem;
      color: #b91c1c; font-size: .9rem; margin-bottom: 1.5rem;
    }
    .alert-err .bi { font-size: 1.1rem; flex-shrink: 0; margin-top: 1px; }

    .back-link {
      display: flex; align-items: center; gap: .4rem;
      color: #64748b; text-decoration: none; font-size: .85rem;
      font-weight: 600; margin-top: 1.75rem; transition: color .2s;
    }
    .back-link:hover { color: #1a56db; }

    /* ═══════════════════════════════
       MOBILE: stacked layout
       Header gradient → white form card
    ═══════════════════════════════ */
    @media (max-width: 680px) {
      body {
        background: linear-gradient(160deg, #1a56db 0%, #06b6d4 100%);
        align-items: flex-start;
        padding: 0;
      }

      .wrap {
        grid-template-columns: 1fr;
        border-radius: 0;
        box-shadow: none;
        margin: 0;
        min-height: 100dvh;
        background: transparent;
      }

      /* Gradient header section */
      .panel-brand {
        background: transparent;
        padding: 2.5rem 1.5rem 2rem;
        min-height: auto;
      }
      .panel-brand::before,
      .panel-brand::after { display: none; }

      .logo-box {
        width: 88px; height: 88px;
        border-radius: 20px;
        margin-bottom: 1.25rem;
      }
      .brand-school { font-size: 1.3rem; }
      .brand-sub    { font-size: 0.85rem; margin-bottom: 0; }
      .features     { display: none; }

      /* White card for form */
      .panel-form {
        background: #fff;
        border-radius: 0;
        padding: 2rem 1.5rem 2.5rem;
        flex: 1;
        box-shadow: none;
      }

      .form-head h2 { font-size: 1.4rem; }

      .inp-wrap input { font-size: 1rem; padding: 0.9rem 1rem 0.9rem 2.9rem; }

      .btn-login { padding: 1rem; font-size: 1.05rem; border-radius: 16px; }

      .extras { flex-direction: column; align-items: flex-start; gap: 0.75rem; }
    }

    /* Modal */
    .modal-bg {
      display: none; position: fixed; inset: 0;
      background: rgba(0,0,0,.5); z-index: 999;
      align-items: center; justify-content: center; padding: 1rem;
    }
    .modal-bg.open { display: flex; }
    .modal-card {
      background: #fff; border-radius: 20px;
      padding: 2rem; max-width: 380px; width: 100%;
      text-align: center;
      box-shadow: 0 20px 50px rgba(0,0,0,.2);
    }
    .modal-card .bi-info-circle-fill { font-size: 2.5rem; color: #1a56db; margin-bottom: 1rem; display: block; }
    .modal-card h5 { font-weight: 700; margin-bottom: .75rem; color: #0f172a; }
    .modal-card p  { color: #64748b; font-size: .92rem; line-height: 1.6; }
    .modal-card button {
      margin-top: 1.5rem; padding: .65rem 2rem;
      background: #1a56db; color: #fff; border: none;
      border-radius: 10px; font-weight: 600; cursor: pointer;
      font-family: inherit; font-size: .95rem;
    }
  </style>
</head>
<body>

<div class="wrap">

  <!-- Branding panel -->
  <div class="panel-brand">
    <div class="brand-inner">
      <div class="logo-box">
        <?php if (!empty($school['logo'])): ?>
          <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo">
        <?php else: ?>
          <i class="bi bi-mortarboard-fill"></i>
        <?php endif; ?>
      </div>
      <div class="brand-school"><?= esc($school['name'] ?? 'SIKAP') ?></div>
      <div class="brand-sub">Sistem Informasi Kelas<br>Absensi dan Penilaian</div>

      <div class="features" style="margin-top:2rem;">
        <div class="feat">
          <div class="feat-ic"><i class="bi bi-people-fill"></i></div>
          <div class="feat-tx"><strong>Manajemen Siswa & Guru</strong><small>Data lengkap dalam satu sistem</small></div>
        </div>
        <div class="feat">
          <div class="feat-ic"><i class="bi bi-clipboard2-check-fill"></i></div>
          <div class="feat-tx"><strong>Penilaian & Rapor</strong><small>Nilai formatif, sumatif, dan rapor</small></div>
        </div>
        <div class="feat">
          <div class="feat-ic"><i class="bi bi-calendar2-check-fill"></i></div>
          <div class="feat-tx"><strong>Absensi Real-time</strong><small>Pantau kehadiran setiap hari</small></div>
        </div>
      </div>
    </div>
  </div>

  <!-- Form panel -->
  <div class="panel-form">
    <div class="form-head">
      <h2>Selamat Datang! 👋</h2>
      <p>Masuk dengan akun Anda untuk melanjutkan</p>
    </div>

    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert-err">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div><?= session()->getFlashdata('error') ?></div>
      </div>
    <?php endif; ?>

    <form method="post" action="<?= base_url('auth/attemptLogin') ?>" id="loginForm" novalidate>
      <?= csrf_field() ?>

      <div class="field">
        <label for="username"><i class="bi bi-person"></i> Username atau Email</label>
        <div class="inp-wrap">
          <i class="bi bi-person-circle ico"></i>
          <input type="text" id="username" name="username"
                 placeholder="Masukkan username atau email"
                 required autofocus autocomplete="username">
        </div>
      </div>

      <div class="field">
        <label for="password"><i class="bi bi-lock"></i> Password</label>
        <div class="inp-wrap">
          <i class="bi bi-lock-fill ico"></i>
          <input type="password" id="password" name="password"
                 placeholder="Masukkan password"
                 required autocomplete="current-password">
          <button type="button" class="toggle-pw" id="togglePw" aria-label="Tampilkan password">
            <i class="bi bi-eye"></i>
          </button>
        </div>
      </div>

      <div class="extras">
        <label><input type="checkbox" name="remember" id="remember"> Ingat saya</label>
        <a href="#" id="forgotLink">Lupa password?</a>
      </div>

      <button type="submit" class="btn-login" id="btnLogin">
        <i class="bi bi-box-arrow-in-right"></i> Masuk ke Sistem
      </button>
    </form>

    <a href="<?= base_url('/') ?>" class="back-link">
      <i class="bi bi-arrow-left"></i> Kembali ke Beranda
    </a>
  </div>
</div>

<!-- Forgot Password Modal -->
<div class="modal-bg" id="forgotModal">
  <div class="modal-card">
    <i class="bi bi-info-circle-fill"></i>
    <h5>Lupa Password?</h5>
    <p>Silakan hubungi <strong>Admin</strong> atau <strong>Wali Kelas</strong> untuk melakukan reset password.</p>
    <button onclick="document.getElementById('forgotModal').classList.remove('open')">Tutup</button>
  </div>
</div>

<script>
  // Toggle password
  document.getElementById('togglePw').addEventListener('click', function () {
    const pw = document.getElementById('password');
    const ic = this.querySelector('i');
    if (pw.type === 'password') {
      pw.type = 'text'; ic.className = 'bi bi-eye-slash';
    } else {
      pw.type = 'password'; ic.className = 'bi bi-eye';
    }
  });

  // Forgot modal
  document.getElementById('forgotLink').addEventListener('click', function (e) {
    e.preventDefault();
    document.getElementById('forgotModal').classList.add('open');
  });
  document.getElementById('forgotModal').addEventListener('click', function (e) {
    if (e.target === this) this.classList.remove('open');
  });

  // Loading on submit
  document.getElementById('loginForm').addEventListener('submit', function () {
    const btn = document.getElementById('btnLogin');
    btn.classList.add('loading');
    btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memproses...';
  });
</script>
</body>
</html>
