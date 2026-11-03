<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-3">

    <!-- Top Action Bar -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="<?= base_url('admin/cbt/bap') ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar BAP
        </a>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-primary btn-sm">
                <i class="bi bi-printer me-1"></i> Cetak Dokumen Sah
            </button>
        </div>
    </div>

    <!-- Official BAP Paper Format -->
    <div class="card border-0 shadow-sm p-4 mx-auto" style="max-width: 900px; border-radius: 12px; background: #fff;">
        <div class="text-center pb-3 border-bottom mb-4">
            <h5 class="fw-bold text-uppercase mb-1">BERITA ACARA PELAKSANAAN UJIAN CBT</h5>
            <h6 class="text-muted fw-normal mb-0"><?= esc($exam['name'] ?? 'UJIAN BERBASIS KOMPUTER') ?></h6>
            <small class="text-muted font-monospace">Nomor: <?= esc($document['bap_number']) ?></small>
        </div>

        <div class="row g-3 mb-4 small">
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td style="width: 35%;" class="text-muted">Mata Pelajaran</td>
                        <td style="width: 5%;">:</td>
                        <td class="fw-bold"><?= esc($exam['subject'] ?? 'CBT') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Ruang / Kelas</td>
                        <td>:</td>
                        <td class="fw-bold"><?= esc($document['room_code']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Waktu Pelaksanaan</td>
                        <td>:</td>
                        <td><?= esc($document['created_at']) ?></td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td style="width: 35%;" class="text-muted">Pengawas Utama</td>
                        <td style="width: 5%;">:</td>
                        <td class="fw-bold"><?= esc($document['proctor_name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Pengawas Pendamping</td>
                        <td>:</td>
                        <td><?= esc($document['supervisor_name'] ?: '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Siswa</td>
                        <td>:</td>
                        <td>
                            <strong class="text-success"><?= esc($document['total_present']) ?> Hadir</strong> / 
                            <strong class="text-danger"><?= esc($document['total_absent']) ?> Tidak Hadir</strong> 
                            (Total: <?= esc($document['total_registered']) ?>)
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Student Attendance List -->
        <h6 class="fw-bold text-dark mb-2 small text-uppercase"><i class="bi bi-people me-1"></i> Daftar Kehadiran Peserta Ujian</h6>
        <div class="table-responsive mb-4">
            <table class="table table-sm table-bordered small align-middle mb-0">
                <thead class="table-light text-center">
                    <tr>
                        <th style="width: 5%;">No</th>
                        <th style="width: 35%;">Nama Siswa</th>
                        <th style="width: 20%;">NISN / NIS</th>
                        <th style="width: 15%;">Status Kehadiran</th>
                        <th style="width: 25%;">Catatan Pengawas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-3">Tidak ada catatan siswa.</td></tr>
                    <?php else: ?>
                        <?php $no = 1; foreach ($students as $st): ?>
                            <tr>
                                <td class="text-center"><?= $no++ ?></td>
                                <td>
                                    <strong class="text-dark d-block"><?= esc($st['student_name'] ?? ($st['nama_lengkap'] ?? ('Siswa #' . $st['student_id']))) ?></strong>
                                    <span class="text-muted font-monospace" style="font-size: 0.7rem;">ID Siswa: <?= esc($st['student_id']) ?></span>
                                </td>
                                <td class="text-center font-monospace small">
                                    <?= esc(!empty($st['nisn']) ? $st['nisn'] : (!empty($st['nis']) ? $st['nis'] : '-')) ?>
                                </td>
                                <td class="text-center">
                                    <?php if ($st['reported_status'] === 'HADIR'): ?>
                                        <span class="badge bg-success">HADIR</span>
                                    <?php elseif ($st['reported_status'] === 'TIDAK_HADIR'): ?>
                                        <span class="badge bg-danger">TIDAK HADIR</span>
                                    <?php elseif ($st['reported_status'] === 'SAKIT'): ?>
                                        <span class="badge bg-warning text-dark">SAKIT</span>
                                    <?php elseif ($st['reported_status'] === 'IZIN'): ?>
                                        <span class="badge bg-info text-dark">IZIN</span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary"><?= esc($st['reported_status']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= esc($st['proctor_notes'] ?: '-') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Signatures & Verification Seal -->
        <div class="row pt-3 border-top mt-2">
            <div class="col-7">
                <div class="p-3 bg-light rounded border small">
                    <span class="fw-bold text-dark d-block mb-1"><i class="bi bi-shield-check text-success me-1"></i> Tanda Tangan &amp; Segel Integritas Digital BAP</span>
                    <span class="text-muted d-block" style="font-size: 0.72rem;">Status Pengesahan: <strong class="text-success"><?= esc($document['verification_status']) ?></strong></span>
                    <span class="text-muted d-block" style="font-size: 0.72rem;">Waktu Pengesahan: <?= esc($document['signed_at']) ?></span>
                    <span class="text-muted d-block font-monospace text-truncate" style="font-size: 0.7rem;" title="<?= esc($document['document_sha256']) ?>">
                        Kode Bukti Pengesahan (Hash SHA-256): <?= esc($document['document_sha256']) ?>
                    </span>
                    <span class="text-muted d-block" style="font-size: 0.7rem;">
                        Identitas penandatangan berdasarkan akun terautentikasi: <strong><?= esc($document['signer_identity'] ?: $document['proctor_name']) ?></strong>
                    </span>
                    <small class="text-muted d-block mt-1 fst-italic" style="font-size: 0.68rem;">
                        Dokumen ini sah dan tercatat secara resmi pada sistem ujian sekolah (Riwayat penyegelan dan audit trail terlindungi).
                    </small>
                </div>
            </div>
            <div class="col-5 text-center">
                <small class="text-muted d-block mb-4">Pengawas Ruang Ujian,</small>
                <div style="height: 45px;"></div>
                <strong class="text-dark d-block text-decoration-underline"><?= esc($document['proctor_name']) ?></strong>
                <small class="text-muted d-block font-monospace" style="font-size: 0.72rem;">Akun Terautentikasi: <?= esc($document['signer_identity']) ?></small>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

