<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Status Lisensi</h1>
    
    <?php if (session()->getFlashdata('reactivation_reason')): ?>
    <div class="alert alert-warning alert-dismissible fade show mt-3" role="alert">
        <h5 class="alert-heading"><i class="bi bi-exclamation-triangle"></i> Reactivation Required</h5>
        <p><?= session()->getFlashdata('reactivation_reason') ?></p>
        <hr>
        <p class="mb-0">Silakan klik tombol "Request Reactivation" di bawah untuk mengaktifkan kembali lisensi Anda.</p>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php 
    // Check if in reactivation mode
    $reactivationMode = isset($_GET['mode']) && $_GET['mode'] === 'reactivation';
    ?>

    <?php if ($reactivationMode): ?>
    <!-- Reactivation Alert -->
    <div class="alert alert-info mt-3">
        <h5 class="alert-heading"><i class="bi bi-info-circle"></i> Domain/Hosting Berubah</h5>
        <p>Sistem mendeteksi bahwa aplikasi telah dipindahkan ke domain atau hosting baru.</p>
        <p class="mb-0">Silakan klik tombol "Request Reactivation" di bawah untuk mengaktifkan kembali lisensi Anda tanpa perlu memasukkan kode lisensi baru.</p>
    </div>
    <?php endif; ?>
    
    <div class="row mt-4">
        <!-- Status Card -->
        <div class="col-xl-6 col-md-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header <?= $isExpired ? 'bg-danger' : ($isNearExpiry ? 'bg-warning' : 'bg-success') ?> text-white">
                    <h5 class="mb-0">
                        <i class="bi bi-shield-check"></i> 
                        Status: <?= $isExpired ? 'EXPIRED' : 'AKTIF' ?>
                    </h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td width="40%"><strong>Kode Lisensi</strong></td>
                            <td>: <code><?= esc(strtoupper($license['license_key'])) ?></code></td>
                        </tr>
                        <tr>
                            <td><strong>Domain</strong></td>
                            <td>: <?= esc($license['domain']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Machine ID</strong></td>
                            <td>: <code><?= esc(substr($license['machine_id'], 0, 16)) ?>...</code></td>
                        </tr>
                        <tr>
                            <td><strong>Tanggal Aktivasi</strong></td>
                            <td>: <?= date('d M Y H:i', strtotime($license['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Terakhir Dicek</strong></td>
                            <td>: <?= date('d M Y H:i', strtotime($license['last_check'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Masa Berlaku</strong></td>
                            <td>: 
                                <?php if ($license['expires_at']): ?>
                                    <?= date('d M Y H:i', strtotime($license['expires_at'])) ?>
                                <?php else: ?>
                                    <span class="badge bg-primary">LIFETIME</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php if ($daysRemaining !== null): ?>
                        <tr>
                            <td><strong>Sisa Waktu</strong></td>
                            <td>: 
                                <?php if ($isExpired): ?>
                                    <span class="badge bg-danger">Sudah Expired <?= abs($daysRemaining) ?> hari yang lalu</span>
                                <?php elseif ($isNearExpiry): ?>
                                    <span class="badge bg-warning text-dark"><?= $daysRemaining ?> hari lagi</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $daysRemaining ?> hari lagi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </table>

                    <?php if ($isExpired || $isNearExpiry): ?>
                    <div class="alert alert-<?= $isExpired ? 'danger' : 'warning' ?> mt-3">
                        <i class="bi bi-exclamation-triangle"></i>
                        <?php if ($isExpired): ?>
                            <strong>Lisensi Anda telah kedaluwarsa!</strong><br>
                            Hubungi pengembang untuk perpanjangan, kemudian klik tombol "Periksa Pembaruan" di bawah.
                        <?php else: ?>
                            <strong>Lisensi akan segera berakhir!</strong><br>
                            Segera hubungi pengembang untuk perpanjangan lisensi.
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Actions Card -->
        <div class="col-xl-6 col-md-12 mb-4">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-gear"></i> Aksi</h5>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <!-- Check Status Button -->
                        <button type="button" class="btn btn-lg btn-info" id="btnCheckStatus">
                            <i class="bi bi-search"></i> Cek Status Lisensi
                        </button>

                        <!-- Reactivation Button (shown if domain/machine changed) -->
                        <button type="button" class="btn btn-lg btn-warning d-none" id="btnRequestReactivation">
                            <i class="bi bi-arrow-repeat"></i> Request Reactivation
                        </button>

                        <button type="button" class="btn btn-lg btn-success" id="btnCheckRenewal">
                            <i class="bi bi-arrow-clockwise"></i> Periksa Pembaruan Lisensi
                        </button>
                        
                        <div class="alert alert-info mb-0">
                            <i class="bi bi-info-circle"></i>
                            <strong>Panduan:</strong>
                            <ul class="mb-0 mt-2">
                                <li><strong>Perpanjang Lisensi:</strong> Hubungi pengembang → Klik "Periksa Pembaruan"</li>
                                <li><strong>Pindah Domain/Hosting:</strong> Klik "Cek Status" → Klik "Request Reactivation"</li>
                                <li><strong>Tidak perlu input kode baru</strong> untuk kedua proses di atas</li>
                            </ul>
                        </div>

                        <hr>

                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deactivateModal">
                            <i class="bi bi-x-circle"></i> Nonaktifkan Lisensi
                        </button>
                        <small class="text-muted">
                            <i class="bi bi-exclamation-triangle"></i> 
                            Hanya gunakan jika ingin memindahkan lisensi ke server lain
                        </small>
                    </div>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card shadow-sm border-0 mt-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="bi bi-telephone"></i> Kontak Pengembang</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2"><strong>Email:</strong> support@sakasalika.com</p>
                    <p class="mb-2"><strong>WhatsApp:</strong> +62 xxx-xxxx-xxxx</p>
                    <p class="mb-0"><strong>Website:</strong> https://sakasalika.com</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Deactivate Modal -->
<div class="modal fade" id="deactivateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Konfirmasi Nonaktifkan Lisensi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Peringatan!</strong></p>
                <p>Tindakan ini akan menghapus lisensi dari aplikasi ini. Anda harus mengaktifkan ulang dengan kode lisensi yang sama untuk menggunakan aplikasi kembali.</p>
                <p class="mb-0">Apakah Anda yakin ingin melanjutkan?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <form action="<?= site_url('admin/license/deactivate') ?>" method="post" class="d-inline">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger">Ya, Nonaktifkan</button>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function() {
    // Auto-check status on page load if in reactivation mode
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('mode') === 'reactivation') {
        setTimeout(() => {
            $('#btnCheckStatus').click();
        }, 500);
    }

    // Check Status Button
    $('#btnCheckStatus').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Memeriksa...');

        $.ajax({
            url: '<?= site_url('admin/license/detectChange') ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function(response) {
                if (response.success) {
                    if (response.changed) {
                        // Show reactivation button
                        $('#btnRequestReactivation').removeClass('d-none');
                        
                        let changeDetails = '';
                        if (response.domain_changed) {
                            changeDetails += `<p><strong>Domain berubah:</strong><br>
                                Terdaftar: ${response.registered_domain}<br>
                                Saat ini: ${response.current_domain}</p>`;
                        }
                        if (response.machine_changed) {
                            changeDetails += `<p><strong>Machine ID berubah</strong> (hosting/server berbeda)</p>`;
                        }

                        Swal.fire({
                            icon: 'warning',
                            title: 'Perubahan Terdeteksi',
                            html: `
                                <p>Sistem mendeteksi perubahan pada instalasi Anda:</p>
                                ${changeDetails}
                                <hr>
                                <p class="mb-0">Silakan klik tombol <strong>"Request Reactivation"</strong> untuk mengaktifkan kembali lisensi.</p>
                            `,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        // Hide reactivation button
                        $('#btnRequestReactivation').addClass('d-none');
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Status Normal',
                            text: 'Lisensi Anda aktif dan tidak ada perubahan terdeteksi.',
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message || 'Gagal memeriksa status',
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat memeriksa status.',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });

    // Request Reactivation Button
    $('#btnRequestReactivation').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();

        Swal.fire({
            title: 'Request Reactivation?',
            html: `
                <p>Sistem akan menghubungi server lisensi untuk mengaktifkan kembali lisensi Anda di domain/hosting baru.</p>
                <p class="mb-0"><strong>Tidak perlu memasukkan kode lisensi baru.</strong></p>
            `,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Request Reactivation',
            cancelButtonText: 'Batal',
            confirmButtonColor: '#ffc107'
        }).then((result) => {
            if (!result.isConfirmed) return;

            btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Memproses...');

            $.ajax({
                url: '<?= site_url('admin/license/requestReactivation') ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    <?= csrf_token() ?>: '<?= csrf_hash() ?>'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Reactivation Berhasil!',
                            html: `
                                <p>${response.message}</p>
                                <hr>
                                <p><strong>Domain:</strong> ${response.data.domain}</p>
                                ${response.data.expires_at ? `<p><strong>Berlaku hingga:</strong> ${response.data.expires_at}</p>` : ''}
                            `,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            // Reload page to clear reactivation mode
                            window.location.href = '<?= site_url('admin/license') ?>';
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Reactivation Gagal',
                            html: `
                                <p>${response.message}</p>
                                <hr>
                                <p class="mb-0"><small>Silakan hubungi pengembang jika masalah berlanjut.</small></p>
                            `,
                            confirmButtonText: 'OK'
                        });
                        btn.prop('disabled', false).html(originalHtml);
                    }
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Terjadi kesalahan saat menghubungi server.',
                        confirmButtonText: 'OK'
                    });
                    btn.prop('disabled', false).html(originalHtml);
                }
            });
        });
    });

    // Check Renewal Button (existing)
    $('#btnCheckRenewal').on('click', function() {
        const btn = $(this);
        const originalHtml = btn.html();
        
        btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Memeriksa...');

        $.ajax({
            url: '<?= site_url('admin/license/checkRenewal') ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>'
            },
            success: function(response) {
                if (response.success) {
                    if (response.renewed) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Lisensi Diperpanjang!',
                            html: `
                                <p>Lisensi Anda berhasil diperpanjang.</p>
                                <hr>
                                <p><strong>Masa Berlaku Baru:</strong><br>${response.data.new_expiry}</p>
                                <p><strong>Sisa Waktu:</strong> ${response.data.days_remaining} hari</p>
                            `,
                            confirmButtonText: 'OK'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            icon: 'info',
                            title: 'Tidak Ada Pembaruan',
                            text: response.message,
                            confirmButtonText: 'OK'
                        });
                    }
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Gagal',
                        text: response.message,
                        confirmButtonText: 'OK'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Terjadi kesalahan saat menghubungi server.',
                    confirmButtonText: 'OK'
                });
            },
            complete: function() {
                btn.prop('disabled', false).html(originalHtml);
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
