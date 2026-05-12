<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="row align-items-center mb-3">
        <div class="col-md-6">
            <h5 class="mb-0">Preview Konversi Nilai</h5>
            <p class="text-muted small mb-0">
                <?= esc($test['subject_name']) ?> |
                <?= esc($test['bank_code']) ?> | Kelas:
                <?= esc($class_name) ?>
            </p>
        </div>
        <div class="col-md-6 text-end">
            <a href="<?= site_url('admin/cbt/convertnilai') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">Daftar Hasil Konversi</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 50px;" class="text-center">No</th>
                                    <th>Nama Siswa</th>
                                    <th class="text-center">Nilai CBT (NX)</th>
                                    <th class="text-center bg-light">Nilai Konversi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($results as $i => $r): ?>
                                    <tr>
                                        <td class="text-center">
                                            <?= $i + 1 ?>
                                        </td>
                                        <td>
                                            <?= esc($r['student_name']) ?>
                                            <div class="text-muted extra-small">
                                                <?= esc($r['nis']) ?>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?= $r['raw_score'] ?>
                                        </td>
                                        <td class="text-center fw-bold text-primary bg-light">
                                            <?= $r['converted_score'] ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card shadow-sm border-primary">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0 fw-bold">3. Simpan Nilai ke Raport</h6>
                </div>
                <div class="card-body">
                    <div class="alert alert-info small py-2 mb-3">
                        <i class="bi bi-info-circle"></i> Nilai hanya akan diperbarui jika hasil konversi <strong>lebih
                            besar</strong> dari nilai yang sudah ada di raport.
                    </div>

                    <form action="<?= site_url('admin/cbt/convertnilai/save') ?>" method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="subject_id" value="<?= $test['subject_id'] ?>">
                        <input type="hidden" name="year_id" value="<?= $activeYear['id'] ?? 0 ?>">

                        <!-- Hidden inputs for scores mapping -->
                        <?php foreach ($results as $r): ?>
                            <input type="hidden" name="student_scores[<?= $r['student_id'] ?>]"
                                value="<?= $r['converted_score'] ?>">
                        <?php endforeach; ?>

                        <div class="mb-3">
                            <label class="form-label">Tujuan Penyimpanan</label>
                            <select name="dest_type" id="dest_type" class="form-select" required>
                                <option value="">-- Pilih Tujuan --</option>
                                <option value="formatif">Nilai Formatif (Materi)</option>
                                <option value="sumatif">Nilai Sumatif (STS/SAS)</option>
                                <option value="final">Nilai Akhir (Semester)</option>
                            </select>
                        </div>

                        <div id="section_semester" class="mb-3 d-none">
                            <label class="form-label">Semester</label>
                            <select name="semester" class="form-select">
                                <option value="1" <?= ($activeYear['semester'] ?? '') == '1' ? 'selected' : '' ?>>Semester
                                    1 (Ganjil)</option>
                                <option value="2" <?= ($activeYear['semester'] ?? '') == '2' ? 'selected' : '' ?>>Semester
                                    2 (Genap)</option>
                            </select>
                        </div>

                        <!-- Formatif Only -->
                        <div id="section_formatif" class="d-none">
                            <div class="mb-3">
                                <label class="form-label">Pilih Lingkup Materi (ATP)</label>
                                <select name="material_id" class="form-select">
                                    <option value="">-- Pilih Lingkup Materi --</option>
                                    <?php foreach ($materials as $m): ?>
                                        <option value="<?= $m['id'] ?>">
                                            SM <?= $m['semester'] ?>: <?= esc($m['title']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Metode/Jenis</label>
                                <input type="text" name="material_method" class="form-control" value="Tulis"
                                    placeholder="Contoh: Tulis, Lisan, Praktek">
                            </div>
                        </div>

                        <!-- Sumatif Only -->
                        <div id="section_sumatif" class="mb-3 d-none">
                            <label class="form-label">Jenis Sumatif</label>
                            <select name="sumatif_method" class="form-select">
                                <option value="STS">Sumatif Tengah Semester (STS)</option>
                                <option value="SAS">Sumatif Akhir Semester (SAS)</option>
                            </select>
                        </div>

                        <hr>
                        <div class="text-muted small mb-3">
                            <strong>Parameter Konversi:</strong><br>
                            Range Target:
                            <?= $yb ?> -
                            <?= $ya ?><br>
                            Range Asli:
                            <?= $xb ?> -
                            <?= $xa ?>
                        </div>

                        <button type="submit" class="btn btn-success w-100 py-2 fw-bold"
                            onclick="return confirm('Apakah Anda yakin ingin menyimpan nilai konversi ini ke raport?')">
                            <i class="bi bi-save"></i> Simpan ke Raport
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('dest_type').addEventListener('change', function () {
        const type = this.value;
        const s_sem = document.getElementById('section_semester');
        const s_for = document.getElementById('section_formatif');
        const s_sum = document.getElementById('section_sumatif');

        s_sem.classList.add('d-none');
        s_for.classList.add('d-none');
        s_sum.classList.add('d-none');

        if (type === 'formatif') {
            s_for.classList.remove('d-none');
        } else if (type === 'sumatif') {
            s_sem.classList.remove('d-none');
            s_sum.classList.remove('d-none');
        } else if (type === 'final') {
            s_sem.classList.remove('d-none');
        }
    });
</script>

<?= $this->endSection() ?>