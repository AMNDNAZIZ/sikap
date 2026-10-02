<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <div>
            <h3 class="fw-bold mb-1"><i class="bi bi-person-workspace text-purple me-2"></i> Kolaborasi Multi-Stakeholder BK</h3>
            <p class="text-muted mb-0">Rujukan Internal Wali Kelas/Guru Mapel, Konsultasi Orang Tua & Laporan Kepala Sekolah</p>
        </div>
    </div>

    <div class="row g-4">
        <!-- Rujukan Internal Wali Kelas -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-transparent border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0"><i class="bi bi-arrow-return-right me-2 text-primary"></i> Daftar Rujukan Internal (Wali Kelas & Guru Mapel)</h5>
                    <small class="text-muted">Laporan perkembangan atau keluhan siswa yang dilimpahkan ke Guru BK</small>
                </div>
                <div class="card-body px-4 pb-4">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Pelapor (Wali Kelas/Guru)</th>
                                    <th>Nama Siswa</th>
                                    <th>Kelas</th>
                                    <th>Kategori</th>
                                    <th>Status BK</th>
                                    <th class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($referrals)) : ?>
                                    <?php foreach ($referrals as $ref) : ?>
                                        <tr>
                                            <td class="fw-bold text-dark"><?= esc($ref['reporter_name'] ?: 'Wali Kelas') ?></td>
                                            <td><?= esc($ref['student_name']) ?></td>
                                            <td><span class="badge bg-secondary"><?= esc($ref['class_name'] ?: '-') ?></span></td>
                                            <td><span class="badge bg-primary-subtle text-primary"><?= esc($ref['category']) ?></span></td>
                                            <td><span class="badge bg-warning text-dark"><?= esc($ref['status']) ?></span></td>
                                            <td class="text-center">
                                                <button class="btn btn-sm btn-outline-primary rounded-pill px-3">Respon & Ambil Alih</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else : ?>
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">
                                            <i class="bi bi-inbox fs-3 d-block mb-2"></i>
                                            Belum ada rujukan internal baru dari Wali Kelas / Guru.
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stakeholder Roles Card -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 bg-light">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3"><i class="bi bi-people-fill text-success me-2"></i> Peran Stakeholder BK</h5>
                    <ul class="list-group list-group-flush bg-transparent">
                        <li class="list-group-item bg-transparent px-0 py-2">
                            <strong class="text-dark d-block">Guru BK</strong>
                            <small class="text-muted">Pengelola utama program, asesmen, konseling & penanganan kasus.</small>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-2">
                            <strong class="text-dark d-block">Wali Kelas</strong>
                            <small class="text-muted">Merujuk siswa bermasalah ke BK & memantau perkembangan umum.</small>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-2">
                            <strong class="text-dark d-block">Guru Mata Pelajaran</strong>
                            <small class="text-muted">Melaporkan kendala akademik / perilaku di kelas saat pembelajaran.</small>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-2">
                            <strong class="text-dark d-block">Orang Tua / Wali</strong>
                            <small class="text-muted">Menerima surat panggilan, konfirmasi janji temu & konsultasi.</small>
                        </li>
                        <li class="list-group-item bg-transparent px-0 py-2">
                            <strong class="text-dark d-block">Kepala Sekolah</strong>
                            <small class="text-muted">Verifikasi & approval laporan kinerja BK serta penanganan kasus berat.</small>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
