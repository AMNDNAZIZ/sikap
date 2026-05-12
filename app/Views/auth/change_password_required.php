<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $title ?> - <?= $school['name'] ?? 'SIAKAD' ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .change-password-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            overflow: hidden;
            max-width: 500px;
            width: 100%;
        }
        
        .card-header-custom {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        
        .card-header-custom i {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .card-body-custom {
            padding: 2rem;
        }
        
        .alert-info-custom {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        
        .form-label {
            font-weight: 600;
            color: #495057;
            margin-bottom: 0.5rem;
        }
        
        .form-control {
            border-radius: 10px;
            border: 2px solid #e0e0e0;
            padding: 0.75rem 1rem;
            transition: all 0.3s ease;
        }
        
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        
        .input-group-text {
            background: white;
            border: 2px solid #e0e0e0;
            border-left: none;
            border-radius: 0 10px 10px 0;
            cursor: pointer;
        }
        
        .input-group .form-control {
            border-right: none;
            border-radius: 10px 0 0 10px;
        }
        
        .btn-change-password {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 0.75rem 2rem;
            font-weight: 600;
            color: white;
            width: 100%;
            transition: all 0.3s ease;
        }
        
        .btn-change-password:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn-change-password:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        
        .password-requirements {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1rem;
            margin-top: 1rem;
        }
        
        .password-requirements ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        
        .password-requirements li {
            font-size: 0.875rem;
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        
        .password-requirements li.valid {
            color: #28a745;
        }
        
        .password-requirements li.valid::marker {
            content: "✓ ";
        }
        
        .invalid-feedback {
            display: none;
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .form-control.is-invalid {
            border-color: #dc3545;
        }
        
        .form-control.is-invalid:focus {
            border-color: #dc3545;
            box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
        }
    </style>
</head>
<body>
    <div class="change-password-card">
        <div class="card-header-custom">
            <i class="bi bi-shield-lock"></i>
            <h4 class="mb-0">Ganti Password Wajib</h4>
            <p class="mb-0 mt-2" style="font-size: 0.9rem; opacity: 0.9;">
                Untuk keamanan akun Anda
            </p>
        </div>
        
        <div class="card-body-custom">
            <?php if (session()->getFlashdata('info')): ?>
                <div class="alert-info-custom">
                    <i class="bi bi-info-circle me-2"></i>
                    <?= session()->getFlashdata('info') ?>
                </div>
            <?php endif; ?>
            
            <div class="alert alert-warning border-0 mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Perhatian!</strong> Anda menggunakan password default. Untuk keamanan, silakan ganti password Anda sekarang.
            </div>
            
            <form id="changePasswordForm">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label for="current_password" class="form-label">
                        <i class="bi bi-key me-1"></i> Password Lama
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="current_password" name="current_password" required>
                        <span class="input-group-text" onclick="togglePassword('current_password')">
                            <i class="bi bi-eye" id="current_password_icon"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="mb-3">
                    <label for="new_password" class="form-label">
                        <i class="bi bi-lock me-1"></i> Password Baru
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="new_password" name="new_password" required minlength="6">
                        <span class="input-group-text" onclick="togglePassword('new_password')">
                            <i class="bi bi-eye" id="new_password_icon"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="mb-3">
                    <label for="confirm_password" class="form-label">
                        <i class="bi bi-lock-fill me-1"></i> Konfirmasi Password Baru
                    </label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="6">
                        <span class="input-group-text" onclick="togglePassword('confirm_password')">
                            <i class="bi bi-eye" id="confirm_password_icon"></i>
                        </span>
                    </div>
                    <div class="invalid-feedback"></div>
                </div>
                
                <div class="password-requirements">
                    <strong class="d-block mb-2">
                        <i class="bi bi-info-circle me-1"></i> Persyaratan Password:
                    </strong>
                    <ul id="password-requirements-list">
                        <li id="req-length">Minimal 6 karakter</li>
                        <li id="req-match">Password dan konfirmasi harus sama</li>
                        <li id="req-different">Berbeda dari password lama</li>
                    </ul>
                </div>
                
                <button type="submit" class="btn btn-change-password mt-4" id="submitBtn">
                    <i class="bi bi-check-circle me-2"></i> Ganti Password
                </button>
            </form>
            
            <div class="text-center mt-3">
                <small class="text-muted">
                    <i class="bi bi-shield-check me-1"></i>
                    Password Anda akan dienkripsi dengan aman
                </small>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <script>
        function togglePassword(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = document.getElementById(fieldId + '_icon');
            
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.remove('bi-eye');
                icon.classList.add('bi-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.remove('bi-eye-slash');
                icon.classList.add('bi-eye');
            }
        }
        
        // Real-time password validation
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const currentPassword = document.getElementById('current_password');
        
        function validatePassword() {
            const newPwd = newPassword.value;
            const confirmPwd = confirmPassword.value;
            const currentPwd = currentPassword.value;
            
            // Check length
            const reqLength = document.getElementById('req-length');
            if (newPwd.length >= 6) {
                reqLength.classList.add('valid');
            } else {
                reqLength.classList.remove('valid');
            }
            
            // Check match
            const reqMatch = document.getElementById('req-match');
            if (newPwd && confirmPwd && newPwd === confirmPwd) {
                reqMatch.classList.add('valid');
            } else {
                reqMatch.classList.remove('valid');
            }
            
            // Check different from current
            const reqDifferent = document.getElementById('req-different');
            if (newPwd && currentPwd && newPwd !== currentPwd) {
                reqDifferent.classList.add('valid');
            } else {
                reqDifferent.classList.remove('valid');
            }
        }
        
        newPassword.addEventListener('input', validatePassword);
        confirmPassword.addEventListener('input', validatePassword);
        currentPassword.addEventListener('input', validatePassword);
        
        // Form submission
        document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const submitBtn = document.getElementById('submitBtn');
            const formData = new FormData(this);
            
            // Disable button
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Memproses...';
            
            // Clear previous errors
            document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
            document.querySelectorAll('.invalid-feedback').forEach(el => {
                el.textContent = '';
                el.style.display = 'none';
            });
            
            fetch('<?= site_url('auth/update-password-required') ?>', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        showConfirmButton: false,
                        timer: 2000
                    }).then(() => {
                        window.location.href = '<?= site_url('dashboard') ?>';
                    });
                } else {
                    // Show errors
                    if (data.errors) {
                        for (const [field, message] of Object.entries(data.errors)) {
                            const input = document.getElementById(field);
                            if (input) {
                                input.classList.add('is-invalid');
                                // Find the invalid-feedback div after the input-group
                                const inputGroup = input.closest('.input-group');
                                const feedbackDiv = inputGroup.nextElementSibling;
                                if (feedbackDiv && feedbackDiv.classList.contains('invalid-feedback')) {
                                    feedbackDiv.textContent = message;
                                    feedbackDiv.style.display = 'block';
                                }
                            }
                        }
                    }
                    
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal!',
                        text: data.message
                    });
                    
                    // Re-enable button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Ganti Password';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: 'Terjadi kesalahan. Silakan coba lagi.'
                });
                
                // Re-enable button
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-check-circle me-2"></i> Ganti Password';
            });
        });
        
        // Prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
    </script>
</body>
</html>
