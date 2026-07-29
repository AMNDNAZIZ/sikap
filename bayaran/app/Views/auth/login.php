<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Login Admin</title>

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --primary-gradient: linear-gradient(135deg, #10b981 0%, #059669 100%);
      --bg-gradient: linear-gradient(180deg, #10b981 0%, #ecfdf5 100%);
      --glass-bg: rgba(255, 255, 255, 0.9);
      --glass-border: rgba(255, 255, 255, 0.4);
      --accent-color: #059669;
    }

    body {
      font-family: 'Outfit', sans-serif;
      background: var(--bg-gradient);
      background-attachment: fixed;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      color: #1e293b;
      margin: 0;
      padding: 0 15px;
    }

    .login-container {
      width: 100%;
      max-width: 440px;
      padding: 2rem 0;
    }

    .login-card {
      background: var(--glass-bg);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border: 1px solid var(--glass-border);
      padding: 2.5rem;
      border-radius: 2rem;
      box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.08);
      text-align: center;
      animation: slideUp 0.6s cubic-bezier(0.22, 1, 0.36, 1);
    }

    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .login-card img {
      max-height: 90px;
      margin-bottom: 1.5rem;
      filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.1));
    }

    .app-title {
      font-weight: 700;
      font-size: 1.35rem;
      color: #065f46;
      letter-spacing: 0.5px;
    }

    .school-title {
      font-weight: 500;
      font-size: 0.95rem;
      color: #166534;
      margin-bottom: 2rem;
      opacity: 0.8;
    }

    h6 {
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 1.5rem;
      position: relative;
      display: inline-block;
      text-transform: uppercase;
      font-size: 0.85rem;
      letter-spacing: 1px;
    }

    h6::after {
      content: '';
      position: absolute;
      bottom: -8px;
      left: 50%;
      transform: translateX(-50%);
      width: 30px;
      height: 3px;
      background: var(--accent-color);
      border-radius: 2px;
    }

    .form-label {
      font-weight: 600;
      font-size: 0.8rem;
      color: #475569;
      margin-bottom: 0.5rem;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .input-group {
      border-radius: 1rem;
      overflow: hidden;
      background: #ffffff;
      transition: all 0.3s ease;
      border: 1px solid #e2e8f0;
    }

    .input-group:focus-within {
      border-color: #10b981;
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
      transform: translateY(-1px);
    }

    .input-group-text {
      background: transparent;
      border: none;
      color: #94a3b8;
      padding-left: 1.25rem;
    }

    .form-control {
      border: none;
      padding: 0.85rem 1.25rem 0.85rem 0.75rem;
      font-size: 0.95rem;
      background: transparent;
    }

    .form-control:focus {
      box-shadow: none;
      background: transparent;
    }

    .btn-primary {
      background: var(--primary-gradient);
      border: none;
      border-radius: 1rem;
      padding: 0.9rem;
      font-weight: 700;
      font-size: 1rem;
      margin-top: 1rem;
      transition: all 0.3s ease;
      box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.3);
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 20px 25px -5px rgba(16, 185, 129, 0.4);
      filter: brightness(1.05);
    }

    .alert {
      border-radius: 1rem;
      font-size: 0.85rem;
      padding: 1rem;
      border: none;
      background: #fee2e2;
      color: #991b1b;
      margin-bottom: 1.5rem;
    }
  </style>
</head>

<body>

  <div class="login-container">
    <div class="login-card">
      <!-- Logo + Info Aplikasi -->
      <img src="<?= base_url($logo) ?>" alt="Logo" class="img-fluid">
      <div class="app-title"><?= esc(strtoupper($app_name)) ?></div>
      <div class="school-title"><?= esc(strtoupper($school_name)) ?></div>

      <h6>Login Administrator</h6>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger shadow-sm"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>

      <form method="post" action="<?= site_url('login') ?>" novalidate>
        <?= csrf_field() ?>
        <div class="mb-3 text-start">
          <label for="username" class="form-label">Username</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="username" id="username" class="form-control" placeholder="Masukkan username"
              required>
          </div>
        </div>
        <div class="mb-4 text-start">
          <label for="password" class="form-label">Password</label>
          <div class="input-group">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password"
              required>
          </div>
        </div>
        <button type="submit" class="btn btn-primary w-100">Masuk ke Panel</button>
      </form>

      <div class="mt-4 pt-3 border-top">
        <small class="text-muted">&copy; <?= date('Y') ?> SakaSalika™</small>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>