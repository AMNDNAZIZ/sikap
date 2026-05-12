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

<form action="<?= base_url('admin/dapodik/syncStudents') ?>" method="post">
    <?= csrf_field() ?>

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
                    <button type="submit" class="btn btn-success">🚀 Mulai Sinkronisasi</button>
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
                                    <input type="checkbox" name="selected_students[]" value="<?= $index ?>"
                                        class="form-check-input student-check">
                                    <!-- Hidden fields untuk data dasar -->
                                    <input type="hidden" name="students_data[<?= $index ?>][nisn]"
                                        value="<?= esc($s['nisn'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][nis]"
                                        value="<?= esc($s['nis'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][nama]"
                                        value="<?= esc($s['nama'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][nik]"
                                        value="<?= esc($s['nik'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][jenis_kelamin]"
                                        value="<?= esc($s['jenis_kelamin'] ?? 'L') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][tempat_lahir]"
                                        value="<?= esc($s['tempat_lahir'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][tanggal_lahir]"
                                        value="<?= esc($s['tanggal_lahir'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][anak_keberapa]"
                                        value="<?= esc($s['anak_keberapa'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][agama]"
                                        value="<?= esc($s['agama'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][kewarganegaraan]"
                                        value="<?= esc($s['kewarganegaraan'] ?? 'WNI') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][nama_rombel]"
                                        value="<?= esc($s['nama_rombel'] ?? '') ?>">
                                    
                                    <!-- Hidden fields untuk alamat -->
                                    <input type="hidden" name="students_data[<?= $index ?>][alamat_jalan]"
                                        value="<?= esc($s['alamat_jalan'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][jenis_tinggal]"
                                        value="<?= esc($s['jenis_tinggal'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][moda_transportasi]"
                                        value="<?= esc($s['moda_transportasi'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][jarak_rumah_ke_sekolah]"
                                        value="<?= esc($s['jarak_rumah_ke_sekolah'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][latitude]"
                                        value="<?= esc($s['latitude'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][longitude]"
                                        value="<?= esc($s['longitude'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][kebutuhan_khusus]"
                                        value="<?= esc($s['kebutuhan_khusus'] ?? '') ?>">
                                    
                                    <!-- Hidden fields untuk data ayah -->
                                    <input type="hidden" name="students_data[<?= $index ?>][nama_ayah]"
                                        value="<?= esc($s['nama_ayah'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][nik_ayah]"
                                        value="<?= esc($s['nik_ayah'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][tahun_lahir_ayah]"
                                        value="<?= esc($s['tahun_lahir_ayah'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][pendidikan_ayah]"
                                        value="<?= esc($s['pendidikan_ayah'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][pekerjaan_ayah]"
                                        value="<?= esc($s['pekerjaan_ayah'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][penghasilan_ayah]"
                                        value="<?= esc($s['penghasilan_ayah'] ?? '') ?>">
                                    
                                    <!-- Hidden fields untuk data ibu -->
                                    <input type="hidden" name="students_data[<?= $index ?>][nama_ibu_kandung]"
                                        value="<?= esc($s['nama_ibu_kandung'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][nik_ibu]"
                                        value="<?= esc($s['nik_ibu'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][tahun_lahir_ibu]"
                                        value="<?= esc($s['tahun_lahir_ibu'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][pendidikan_ibu]"
                                        value="<?= esc($s['pendidikan_ibu'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][pekerjaan_ibu]"
                                        value="<?= esc($s['pekerjaan_ibu'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][penghasilan_ibu]"
                                        value="<?= esc($s['penghasilan_ibu'] ?? '') ?>">
                                    
                                    <!-- Hidden fields untuk data wali -->
                                    <input type="hidden" name="students_data[<?= $index ?>][nama_wali]"
                                        value="<?= esc($s['nama_wali'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][pendidikan_wali]"
                                        value="<?= esc($s['pendidikan_wali'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][pekerjaan_wali]"
                                        value="<?= esc($s['pekerjaan_wali'] ?? '') ?>">
                                    <input type="hidden" name="students_data[<?= $index ?>][penghasilan_wali]"
                                        value="<?= esc($s['penghasilan_wali'] ?? '') ?>">
                                </td>
                                <td><code><?= esc($s['nisn'] ?? '-') ?></code></td>
                                <td class="fw-bold">
                                    <?= esc($s['nama'] ?? '-') ?>
                                </td>
                                <td>
                                    <?= esc($s['jenis_kelamin'] ?? '-') ?>
                                </td>
                                <td>
                                    <?= esc($s['tempat_lahir'] ?? '-') ?>,
                                    <?= esc($s['tanggal_lahir'] ?? '-') ?>
                                </td>
                                <td><small class="text-muted">
                                        <?= esc($s['nama_rombel'] ?? '-') ?>
                                    </small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada data siswa ditemukan di Dapodik
                                untuk NPSN ini.</td>
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
</script>

<?= $this->endSection() ?>