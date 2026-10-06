<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">Rincian Pertemuan: <?= esc($pembina['fullname']) ?></h1>
            <p class="text-muted mb-0">
                Ekstrakurikuler: <strong><?= esc($ekskul['name']) ?></strong> | 
                Periode: <strong><?= date('F Y', mktime(0, 0, 0, $month, 1, $year)) ?></strong>
            </p>
        </div>
        <div>
            <a href="<?= base_url('admin/ekskul-absensi?month=' . $month . '&year=' . $year . '&ekskul_id=' . $ekskul['id']) ?>" 
               class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-sm mr-1"></i> Kembali ke Rekap
            </a>
        </div>
    </div>

    <!-- Tabel Rincian Pertemuan -->
    <div class="card shadow-sm mb-4">
        <div class="card-header py-3 bg-white d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Riwayat Jurnal & Verifikasi Kehadiran</h6>
            <span class="badge bg-primary text-white"><?= count($jurnals) ?> Pertemuan</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th width="5%" class="text-center">No</th>
                            <th width="15%">Tanggal</th>
                            <th width="40%">Materi Pelatihan / Agenda</th>
                            <th width="20%" class="text-center">Status Verifikasi</th>
                            <th width="20%">Catatan Admin</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($jurnals)): ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">
                                    Belum ada jurnal kegiatan yang disubmit pada bulan ini.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($jurnals as $i => $j) : 
                                $vStatus = $j['verification_status'] ?? 'pending';
                            ?>
                                <tr>
                                    <td class="text-center font-weight-bold"><?= $i + 1 ?></td>
                                    <td>
                                        <strong><?= date('d M Y', strtotime($j['date'])) ?></strong>
                                        <div class="small text-muted"><?= date('l', strtotime($j['date'])) ?></div>
                                    </td>
                                    <td>
                                        <?= esc($j['materi']) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($vStatus === 'verified'): ?>
                                            <span class="badge bg-success px-2 py-1">
                                                <i class="fas fa-check-circle"></i> Disetujui
                                            </span>
                                            <?php if (!empty($j['verifier_name'])): ?>
                                                <div class="small text-muted mt-1" style="font-size:0.75rem;">
                                                    Oleh: <?= esc($j['verifier_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        <?php elseif ($vStatus === 'rejected'): ?>
                                            <span class="badge bg-danger px-2 py-1">
                                                <i class="fas fa-times-circle"></i> Ditolak
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark px-2 py-1">
                                                <i class="fas fa-clock"></i> Pending
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= esc($j['verification_notes'] ?: '-') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
