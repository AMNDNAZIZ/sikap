<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between mb-3">
    <h4>📋 Pratinjau Siswa dari Dapodik</h4>
    <a href="<?= base_url('admin/dapodik') ?>" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
</div>

<div class="alert alert-info py-2">
    <i class="fas fa-info-circle"></i> <strong>Sistem akan mendeteksi kelas secara otomatis</strong> berdasarkan nama
    rombel di Dapodik. Pastikan <strong>Nama Kelas</strong> di Siakad sama dengan di Dapodik. Jika tidak ditemukan,
    siswa akan dimasukkan ke kelas cadangan yang Anda pilih di bawah.
</div>

<div class="alert alert-warning py-2">
    <i class="fas fa-exclamation-triangle"></i> <strong>Data yang Sudah Ada:</strong> Pilih bagaimana menangani siswa yang NISN-nya sudah terdaftar di sistem.
</div>

<form action="<?= base_url('admin/dapodik/syncStudents') ?>" method="post" id="sync-form">
    <?= csrf_field() ?>

    <?php
    // Encode semua data siswa sebagai satu JSON field
    // Ini menghindari masalah max_input_vars (default 1000) yang terlampaui
    // ketika mengirim ratusan siswa dengan puluhan hidden fields masing-masing
    $studentsJson = json_encode($students, JSON_UNESCAPED_UNICODE);
    ?>
    <input type="hidden" name="students_json" value="<?= esc($studentsJson, 'attr') ?>">

    <div class="card mb-3">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Pilih Kelas Tujuan:</label>
                    <select name="class_id" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($classes as $c): ?>
                            <option value="<?= $c['id'] ?>">
                                <?= esc($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="text-muted">Kelas cadangan jika auto-detect gagal</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Jika Data Sudah Ada:</label>
                    <select name="sync_mode" class="form-select" required>
                        <option value="skip">Skip - Lewati (tidak diubah)</option>
                        <option value="update">Update - Timpa dengan data Dapodik</option>
                        <option value="merge">Merge - Isi yang kosong saja</option>
                    </select>
                    <small class="text-muted">Cara menangani data duplikat</small>
                </div>
                <div class="col-md-4 text-end pt-4">
                    <button type="submit" class="btn btn-success" id="sync-btn">🚀 Mulai Sinkronisasi</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="40"><input type="checkbox" id="check-all" class="form-check-input"></th>
                        <th>NISN</th>
                        <th>Nama Lengkap</th>
                        <th>L/P</th>
                        <th>Tempat, Tgl Lahir</th>
                        <th>Rombel (Dapodik)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($students)): ?>
                        <?php foreach ($students as $index => $s): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_students[]"
                                        value="<?= $index ?>"
                                        class="form-check-input student-check" checked>
                                </td>
                                <td><code><?= esc($s['nisn'] ?? '-') ?></code></td>
                                <td class="fw-bold"><?= esc($s['nama'] ?? '-') ?></td>
                                <td><?= esc($s['jenis_kelamin'] ?? '-') ?></td>
                                <td>
                                    <?= esc($s['tempat_lahir'] ?? '-') ?>,
                                    <?= esc($s['tanggal_lahir'] ?? '-') ?>
                                </td>
                                <td><small class="text-muted"><?= esc($s['nama_rombel'] ?? '-') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">
                                Tidak ada data siswa ditemukan di Dapodik untuk NPSN ini.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<script>
    document.getElementById('check-all').addEventListener('change', function () {
        const checks = document.querySelectorAll('.student-check');
        checks.forEach(c => c.checked = this.checked);
    });

    document.getElementById('sync-form').addEventListener('submit', function (e) {
        const btn = document.getElementById('sync-btn');
        btn.disabled = true;
        btn.innerHTML = '⏳ Memproses...';
    });
</script>

<?= $this->endSection() ?>
