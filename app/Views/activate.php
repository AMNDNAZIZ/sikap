<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aktivasi Aplikasi — SakaSalika</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
        }

        .card {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 480px;
            overflow: hidden;
        }

        .card-header {
            background: linear-gradient(135deg, #0f3460, #1a4a7a);
            padding: 2rem 2rem 1.5rem;
            text-align: center;
            color: #fff;
        }

        .card-header .lock-icon {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .card-header h2 {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .card-header p {
            font-size: 0.85rem;
            opacity: 0.75;
        }

        .card-body {
            padding: 1.75rem 2rem;
        }

        /* ── Alert ── */
        .alert {
            padding: 0.85rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
            line-height: 1.5;
        }
        .alert-icon { flex-shrink: 0; font-size: 1rem; margin-top: 1px; }
        .alert-danger  { background: #fff0f0; border: 1px solid #f5c6cb; color: #721c24; }
        .alert-success { background: #f0fff4; border: 1px solid #c3e6cb; color: #155724; }
        .alert-info    { background: #f0f8ff; border: 1px solid #bee5eb; color: #0c5460; }
        .alert-warning { background: #fffbf0; border: 1px solid #ffeeba; color: #856404; }

        /* ── License Info Box ── */
        .license-box {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: 1rem 1.25rem;
            margin-bottom: 1.25rem;
        }

        .license-box .lic-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.35rem 0;
            font-size: 0.875rem;
            border-bottom: 1px solid #e9ecef;
        }
        .license-box .lic-row:last-child { border-bottom: none; }
        .license-box .lic-label { color: #6c757d; font-weight: 500; }
        .license-box .lic-value { color: #212529; font-weight: 600; text-align: right; }
        .badge-active   { color: #155724; background: #d4edda; padding: 2px 8px; border-radius: 20px; font-size: 0.78rem; }
        .badge-expired  { color: #721c24; background: #f8d7da; padding: 2px 8px; border-radius: 20px; font-size: 0.78rem; }
        .badge-inactive { color: #856404; background: #fff3cd; padding: 2px 8px; border-radius: 20px; font-size: 0.78rem; }

        /* ── Divider ── */
        .divider {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin: 1.25rem 0;
            color: #adb5bd;
            font-size: 0.8rem;
        }
        .divider::before, .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #dee2e6;
        }

        /* ── Form ── */
        .form-group { margin-bottom: 1rem; }
        .form-group label {
            display: block;
            font-size: 0.875rem;
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.4rem;
        }
        .form-control {
            width: 100%;
            padding: 0.7rem 0.9rem;
            border: 1.5px solid #ced4da;
            border-radius: 8px;
            font-size: 0.95rem;
            color: #212529;
            transition: border-color 0.2s, box-shadow 0.2s;
            outline: none;
            letter-spacing: 0.05em;
        }
        .form-control:focus {
            border-color: #0f3460;
            box-shadow: 0 0 0 3px rgba(15,52,96,0.12);
        }
        .form-hint {
            font-size: 0.78rem;
            color: #6c757d;
            margin-top: 0.3rem;
        }

        /* ── Buttons ── */
        .btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
            margin-bottom: 0.6rem;
        }
        .btn:last-child { margin-bottom: 0; }
        .btn:disabled { opacity: 0.65; cursor: not-allowed; }

        .btn-primary {
            background: #0f3460;
            color: #fff;
        }
        .btn-primary:hover:not(:disabled) { background: #0a2540; }

        .btn-check-online {
            background: #fff;
            color: #0f3460;
            border: 2px solid #0f3460;
        }
        .btn-check-online:hover:not(:disabled) {
            background: #0f3460;
            color: #fff;
        }

        .btn-success {
            background: #28a745;
            color: #fff;
        }
        .btn-success:hover:not(:disabled) { background: #218838; }

        /* ── Online Check Result Panel ── */
        #onlineCheckResult {
            display: none;
            margin-top: 1rem;
        }

        /* ── Spinner ── */
        .spinner {
            display: inline-block;
            width: 16px;
            height: 16px;
            border: 2px solid currentColor;
            border-right-color: transparent;
            border-radius: 50%;
            animation: spin 0.65s linear infinite;
            flex-shrink: 0;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* ── Progress bar ── */
        .progress-wrap {
            display: none;
            height: 4px;
            background: #e9ecef;
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 1rem;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #0f3460, #1a7abf);
            border-radius: 2px;
            animation: progress 2s ease-in-out infinite;
        }
        @keyframes progress {
            0%   { width: 0%; margin-left: 0; }
            50%  { width: 70%; margin-left: 15%; }
            100% { width: 0%; margin-left: 100%; }
        }

        .footer-note {
            text-align: center;
            font-size: 0.78rem;
            color: #adb5bd;
            margin-top: 1.25rem;
        }
    </style>
</head>
<body>
<div class="card">
    <div class="card-header">
        <span class="lock-icon">🔐</span>
        <h2>Aktivasi Aplikasi</h2>
        <p>SakaSalika School Management System</p>
    </div>

    <div class="card-body">

        <?php
            $licenseError = session()->getFlashdata('license_error');
            $flashError   = session()->getFlashdata('error');
            $flashSuccess = session()->getFlashdata('success');
            $errorMsg     = $licenseError ?: $flashError;
        ?>

        <?php if ($errorMsg): ?>
            <div class="alert alert-danger">
                <span class="alert-icon">⚠️</span>
                <span><?= esc($errorMsg) ?></span>
            </div>
        <?php endif; ?>

        <?php if ($flashSuccess): ?>
            <div class="alert alert-success">
                <span class="alert-icon">✅</span>
                <span><?= esc($flashSuccess) ?></span>
            </div>
        <?php endif; ?>

        <?php
            $existing    = $existingLicense ?? null;
            $hasLicense  = !empty($existing);
            $isExpired   = $hasLicense && !empty($existing['expires_at']) && strtotime($existing['expires_at']) < time();
            $isActive    = $hasLicense && $existing['status'] === 'active' && !$isExpired;
        ?>

        <?php if ($hasLicense): ?>
            <!-- Info lisensi tersimpan -->
            <div class="license-box">
                <div class="lic-row">
                    <span class="lic-label">Status</span>
                    <span class="lic-value">
                        <?php if ($isActive): ?>
                            <span class="badge-active">✓ Aktif</span>
                        <?php elseif ($isExpired): ?>
                            <span class="badge-expired">✗ Kedaluwarsa</span>
                        <?php else: ?>
                            <span class="badge-inactive">⚠ Tidak Aktif</span>
                        <?php endif; ?>
                    </span>
                </div>
                <div class="lic-row">
                    <span class="lic-label">Kode Lisensi</span>
                    <span class="lic-value" style="font-family:monospace;">
                        <?= esc(substr($existing['license_key'], 0, 4)) ?>-••••-••••-<?= esc(substr($existing['license_key'], -4)) ?>
                    </span>
                </div>
                <?php if (!empty($existing['expires_at'])): ?>
                <div class="lic-row">
                    <span class="lic-label">Berlaku Hingga</span>
                    <span class="lic-value" style="color: <?= $isExpired ? '#dc3545' : '#28a745' ?>;">
                        <?= date('d M Y', strtotime($existing['expires_at'])) ?>
                        <?= $isExpired ? ' <small>(Kedaluwarsa)</small>' : '' ?>
                    </span>
                </div>
                <?php endif; ?>
                <?php if (!empty($existing['domain'])): ?>
                <div class="lic-row">
                    <span class="lic-label">Domain</span>
                    <span class="lic-value"><?= esc($existing['domain']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Tombol Cek Online -->
            <div class="progress-wrap" id="progressWrap">
                <div class="progress-bar"></div>
            </div>

            <button type="button" class="btn btn-check-online" id="btnCheckOnline" onclick="doCheckOnline()">
                <span id="checkIcon">🌐</span>
                <span id="checkLabel">Cek Pembaruan Online</span>
            </button>

            <div id="onlineCheckResult"></div>

            <div class="divider">atau masukkan kode lisensi baru</div>
        <?php endif; ?>

        <!-- Form input kode lisensi -->
        <form action="<?= base_url('activate/process') ?>" method="post" id="formActivate">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="license_key">
                    <?= $hasLicense ? 'Kode Lisensi Baru' : 'Kode Lisensi' ?>
                </label>
                <input
                    type="text"
                    class="form-control"
                    id="license_key"
                    name="license_key"
                    placeholder="XXXX-XXXX-XXXX-XXXX"
                    autocomplete="off"
                    spellcheck="false"
                    required
                >
                <div class="form-hint">Dapatkan kode lisensi dari administrator sistem</div>
            </div>
            <button type="submit" class="btn btn-primary">
                🔑 <?= $hasLicense ? 'Aktivasi dengan Kode Baru' : 'Aktivasi Sekarang' ?>
            </button>
        </form>

        <div class="footer-note">
            Butuh bantuan? Hubungi tim SakaSalika
        </div>
    </div><!-- /card-body -->
</div><!-- /card -->

<script>
const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
const CSRF_HASH      = '<?= csrf_hash() ?>';
const CHECK_URL      = '<?= base_url('activate/checkOnline') ?>';
const DASHBOARD_URL  = '<?= base_url('dashboard') ?>';

function doCheckOnline() {
    const btn       = document.getElementById('btnCheckOnline');
    const icon      = document.getElementById('checkIcon');
    const label     = document.getElementById('checkLabel');
    const result    = document.getElementById('onlineCheckResult');
    const progress  = document.getElementById('progressWrap');

    // Loading state
    btn.disabled = true;
    icon.innerHTML = '<span class="spinner"></span>';
    label.textContent = 'Menghubungi server lisensi…';
    progress.style.display = 'block';
    result.style.display = 'none';

    const formData = new FormData();
    formData.append(CSRF_TOKEN_NAME, CSRF_HASH);

    fetch(CHECK_URL, {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData,
    })
    .then(res => res.json())
    .then(data => {
        progress.style.display = 'none';
        result.style.display = 'block';

        if (data.status === 'renewed') {
            // Berhasil diperbarui — tampilkan sukses lalu redirect
            result.innerHTML = `
                <div class="alert alert-success">
                    <span class="alert-icon">✅</span>
                    <div>
                        <strong>Lisensi berhasil diperbarui!</strong><br>
                        Berlaku hingga <strong>${data.expires_at}</strong>.<br>
                        <small>Mengalihkan ke dashboard…</small>
                    </div>
                </div>`;
            btn.innerHTML = '✅ Berhasil Diperbarui';
            btn.style.background = '#28a745';
            btn.style.color = '#fff';
            btn.style.border = 'none';

            // Redirect setelah 2 detik
            setTimeout(() => { window.location.href = data.redirect || DASHBOARD_URL; }, 2000);

        } else if (data.status === 'valid') {
            result.innerHTML = `
                <div class="alert alert-info">
                    <span class="alert-icon">ℹ️</span>
                    <div>
                        ${escHtml(data.message)}
                        ${data.expires_at ? `<br><small>Berlaku hingga: <strong>${escHtml(data.expires_at)}</strong></small>` : ''}
                    </div>
                </div>`;
            resetBtn();

        } else if (data.status === 'expired') {
            result.innerHTML = `
                <div class="alert alert-warning">
                    <span class="alert-icon">⏰</span>
                    <span>${escHtml(data.message)}</span>
                </div>`;
            resetBtn();

        } else {
            // error / invalid
            result.innerHTML = `
                <div class="alert alert-danger">
                    <span class="alert-icon">⚠️</span>
                    <span>${escHtml(data.message)}</span>
                </div>`;
            resetBtn();
        }
    })
    .catch(err => {
        progress.style.display = 'none';
        result.style.display = 'block';
        result.innerHTML = `
            <div class="alert alert-danger">
                <span class="alert-icon">⚠️</span>
                <span>Gagal menghubungi server. Periksa koneksi internet Anda.</span>
            </div>`;
        resetBtn();
    });
}

function resetBtn() {
    const btn   = document.getElementById('btnCheckOnline');
    const icon  = document.getElementById('checkIcon');
    const label = document.getElementById('checkLabel');
    btn.disabled = false;
    icon.textContent = '🌐';
    label.textContent = 'Cek Pembaruan Online';
}

function escHtml(str) {
    const d = document.createElement('div');
    d.appendChild(document.createTextNode(str));
    return d.innerHTML;
}
</script>
</body>
</html>
