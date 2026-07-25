<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
/* Inline styles untuk Profile page */
.page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2rem !important;
    border-radius: 12px !important;
    margin-bottom: 1.5rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
}

.page-header h1 {
    font-size: 1.75rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.5rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.75rem !important;
}

.page-header p {
    margin: 0 !important;
    opacity: 0.95 !important;
    font-size: 0.95rem !important;
}

.modern-card {
    background: white !important;
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    overflow: hidden !important;
}

.modern-card-header {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    padding: 1.25rem !important;
    border-bottom: 2px solid #dee2e6 !important;
}

.modern-card-title {
    font-size: 1.1rem !important;
    font-weight: 600 !important;
    margin: 0 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    color: #495057 !important;
}

.modern-card-body {
    padding: 2rem !important;
}

.alert-modern {
    border: none !important;
    border-radius: 10px !important;
    padding: 1rem 1.25rem !important;
    display: flex !important;
    align-items: flex-start !important;
    gap: 0.75rem !important;
    margin-bottom: 1.5rem !important;
}

.alert-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
}

.alert-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
}

.form-control {
    border: 1px solid #dee2e6 !important;
    border-radius: 8px !important;
    padding: 0.625rem 0.875rem !important;
}

.form-control:focus {
    border-color: #667eea !important;
    box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.15) !important;
}

.form-label {
    font-weight: 500 !important;
    color: #495057 !important;
    margin-bottom: 0.5rem !important;
}

.btn-modern {
    padding: 0.625rem 1.5rem !important;
    font-weight: 500 !important;
    border-radius: 8px !important;
    transition: all 0.3s ease !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
}

.btn-modern:hover {
    transform: translateY(-2px) !important;
}

.btn-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
}

.btn-primary-gradient:hover {
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3) !important;
    color: white !important;
}

.info-box {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-left: 4px solid #667eea !important;
    padding: 1rem !important;
    border-radius: 8px !important;
    margin-bottom: 1.5rem !important;
}

.user-info {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 1.5rem !important;
    border-radius: 10px !important;
    margin-bottom: 1.5rem !important;
    text-align: center !important;
}

.user-info-icon {
    font-size: 3rem !important;
    margin-bottom: 0.5rem !important;
}

.user-info-name {
    font-size: 1.25rem !important;
    font-weight: 600 !important;
    margin-bottom: 0.25rem !important;
}

.user-info-role {
    font-size: 0.9rem !important;
    opacity: 0.9 !important;
}

.password-strength {
    height: 4px !important;
    background: #e9ecef !important;
    border-radius: 2px !important;
    margin-top: 0.5rem !important;
    overflow: hidden !important;
}

.password-strength-bar {
    height: 100% !important;
    transition: all 0.3s ease !important;
    border-radius: 2px !important;
}
</style>

<div class="container-fluid px-4">
    <!-- Page Header -->
    <div class="page-header mt-4">
        <h1><i class="fas fa-key"></i> Ganti Password</h1>
        <p>Ubah password akun Anda untuk keamanan yang lebih baik</p>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">
            <!-- Main Card -->
            <div class="modern-card">
                <div class="modern-card-header">
                    <h5 class="modern-card-title">
                        <i class="fas fa-user-circle"></i> Informasi Akun
                    </h5>
                </div>
                <div class="modern-card-body">
                    <!-- User Info -->
                    <div class="user-info">
                        <div class="user-info-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <div class="user-info-name"><?= esc($user['fullname']) ?></div>
                        <div class="user-info-role">
                            <?php
                            $roleNames = [1 => 'Administrator', 2 => 'Guru', 3 => 'Siswa'];
                            echo $roleNames[$user['role_id']] ?? 'User';
                            ?>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-modern alert-success-gradient">
                            <i class="fas fa-check-circle"></i>
                            <div><?= session()->getFlashdata('success') ?></div>
                        </div>
                    <?php elseif (session()->getFlashdata('error')): ?>
                        <div class="alert alert-modern alert-danger-gradient">
                            <i class="fas fa-times-circle"></i>
                            <div><?= session()->getFlashdata('error') ?></div>
                        </div>
                    <?php endif; ?>

                    <!-- Info Box -->
                    <div class="info-box">
                        <small>
                            <i class="fas fa-info-circle"></i> <strong>Tips Keamanan:</strong><br>
                            • Gunakan minimal 8 karakter<br>
                            • Kombinasikan huruf besar, kecil, dan angka<br>
                            • Jangan gunakan password yang mudah ditebak
                        </small>
                    </div>

                    <!-- Form -->
                    <form method="post" action="<?= site_url('profile/update-password') ?>" id="passwordForm">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-lock text-primary"></i> Password Baru
                            </label>
                            <input type="password" name="new_password" id="new_password" class="form-control" required minlength="6">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="strengthBar"></div>
                            </div>
                            <small class="text-muted" id="strengthText"></small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-lock text-success"></i> Konfirmasi Password Baru
                            </label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control" required minlength="6">
                            <small class="text-muted" id="matchText"></small>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-modern btn-primary-gradient btn-lg">
                                <i class="fas fa-save"></i> Ubah Password
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Password strength checker
document.getElementById('new_password').addEventListener('input', function() {
    const password = this.value;
    const strengthBar = document.getElementById('strengthBar');
    const strengthText = document.getElementById('strengthText');
    
    let strength = 0;
    if (password.length >= 6) strength++;
    if (password.length >= 8) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;
    
    const colors = ['#dc3545', '#ffc107', '#17a2b8', '#28a745'];
    const texts = ['Lemah', 'Sedang', 'Kuat', 'Sangat Kuat'];
    const widths = ['25%', '50%', '75%', '100%'];
    
    if (strength > 0) {
        const index = Math.min(strength - 1, 3);
        strengthBar.style.width = widths[index];
        strengthBar.style.backgroundColor = colors[index];
        strengthText.textContent = 'Kekuatan password: ' + texts[index];
        strengthText.style.color = colors[index];
    } else {
        strengthBar.style.width = '0%';
        strengthText.textContent = '';
    }
});

// Password match checker
document.getElementById('confirm_password').addEventListener('input', function() {
    const password = document.getElementById('new_password').value;
    const confirm = this.value;
    const matchText = document.getElementById('matchText');
    
    if (confirm.length > 0) {
        if (password === confirm) {
            matchText.textContent = '✓ Password cocok';
            matchText.style.color = '#28a745';
        } else {
            matchText.textContent = '✗ Password tidak cocok';
            matchText.style.color = '#dc3545';
        }
    } else {
        matchText.textContent = '';
    }
});

// Form validation
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    const password = document.getElementById('new_password').value;
    const confirm = document.getElementById('confirm_password').value;
    
    if (password !== confirm) {
        e.preventDefault();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Password dan konfirmasi password tidak cocok!'
        });
    }
});
</script>
<?= $this->endSection() ?>
