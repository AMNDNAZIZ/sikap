<!doctype html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Aktivasi Aplikasi - CBT</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="<?= base_url('assets/css/bootstrap.min.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <style>
        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: #f0f2f5;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
        }

        .activate-wrapper {
            width: 100%;
            max-width: 500px;
            background: #fff;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin: 20px;
        }

        .activate-header {
            background: linear-gradient(135deg, #1a237e 0%, #3949ab 100%);
            color: white;
            text-align: center;
            padding: 40px 20px;
        }

        .activate-header h3 {
            font-weight: 700;
            margin-bottom: 10px;
        }

        .activate-header p {
            opacity: 0.8;
            font-size: 0.95rem;
            margin-bottom: 0;
        }

        .activate-body {
            padding: 40px;
        }

        .form-label {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }

        .form-control {
            border-radius: 12px;
            padding: 15px;
            font-size: 1.1rem;
            border: 2px solid #e1e4e8;
            transition: all 0.3s ease;
            text-align: center;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .form-control:focus {
            border-color: #3949ab;
            box-shadow: 0 0 0 4px rgba(57, 73, 171, 0.1);
        }

        .btn-activate {
            background: #1a237e;
            border: none;
            border-radius: 12px;
            padding: 15px;
            font-size: 1.1rem;
            font-weight: 6400;
            transition: all 0.3s ease;
            margin-top: 10px;
            color: #fff;
        }

        .btn-activate:hover {
            background: #3949ab;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(26, 35, 126, 0.3);
            color: #fff;
        }

        .instruction-box {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            margin-top: 30px;
            font-size: 0.9rem;
            color: #555;
            border-left: 4px solid #3949ab;
        }

        .activate-footer {
            text-align: center;
            padding: 20px;
            color: #888;
            font-size: 0.85rem;
            border-top: 1px solid #eee;
        }

        .domain-info {
            font-weight: 600;
            color: #1a237e;
        }
    </style>
</head>

<body>
    <div class="activate-wrapper">
        <div class="activate-header">
            <h3>Aktivasi Aplikasi</h3>
            <p>Masukkan token lisensi untuk melanjutkan</p>
        </div>

        <div class="activate-body">
            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger d-flex align-items-center mb-4" role="alert">
                    <div>
                        <?= session()->getFlashdata('error') ?>
                    </div>
                </div>
            <?php endif ?>

            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success d-flex align-items-center mb-4" role="alert">
                    <div>
                        <?= session()->getFlashdata('success') ?>
                    </div>
                </div>
            <?php endif ?>

            <form method="post" action="<?= base_url('activate/process') ?>">
                <?= csrf_field() ?>
                <div class="mb-4">
                    <label for="license_key" class="form-label">Token Lisensi</label>
                    <input type="text" name="license_key" id="license_key" class="form-control"
                        placeholder="XXXX-XXXX-XXXX-XXXX" required autocomplete="off">
                </div>
                <button type="submit" class="btn btn-activate w-100">Aktivasi Sekarang</button>
            </form>

            <div class="text-center my-3">
                <span class="text-muted">atau</span>
            </div>

            <button type="button" class="btn btn-outline-primary w-100" id="btnCheckRenewal">
                <i class="bi bi-arrow-clockwise"></i> Periksa Pembaruan Lisensi
            </button>
            <small class="text-muted d-block text-center mt-2">
                Jika lisensi sudah diperpanjang oleh pengembang
            </small>

            <div class="instruction-box">
                <strong>Informasi:</strong><br>
                Lisensi ini akan dikunci pada domain: <span class="domain-info">
                    <?= $_SERVER['HTTP_HOST'] ?>
                </span>.
                Satu lisensi hanya berlaku untuk satu domain dan satu perangkat.
            </div>
        </div>

        <div class="activate-footer">
            &copy;
            <?= date('Y'); ?> SakaSalika CBT System
        </div>
    </div>

    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/jquery.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/sweetalert2.all.min.js') ?>"></script>
    <script>
        document.getElementById('btnCheckRenewal')?.addEventListener('click', function() {
            const btn = this;
            const originalHtml = btn.innerHTML;
            
            btn.disabled = true;
            btn.innerHTML = '<i class="bi bi-hourglass-split"></i> Memeriksa...';

            // Get CSRF token
            const csrfName = '<?= csrf_token() ?>';
            const csrfHash = '<?= csrf_hash() ?>';

            fetch('<?= base_url('activate/checkRenewal') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: csrfName + '=' + csrfHash
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                // Response received
                
                if (data.success) {
                    if (data.renewed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Lisensi Diperpanjang!',
                            html: `<p>${data.message}</p><p>Halaman akan dimuat ulang...</p>`,
                            timer: 2000,
                            showConfirmButton: false
                        }).then(() => {
                            window.location.href = '<?= base_url('dashboard') ?>';
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Tidak Ada Pembaruan',
                            text: data.message
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: data.message || 'Terjadi kesalahan'
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error); // Debug
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat menghubungi server: ' + error.message
                });
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalHtml;
            });
        });
    </script>
</body>

</html>