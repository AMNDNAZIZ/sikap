<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between mb-3">
    <h4>📋 Pratinjau Guru dari Dapodik</h4>
    <a href="<?= base_url('admin/dapodik') ?>" class="btn btn-secondary btn-sm">⬅️ Kembali</a>
</div>

<div class="alert alert-info py-2">
    <i class="fas fa-info-circle"></i> Silakan pilih guru yang ingin disinkronkan ke sistem Siakad.
</div>

<form action="<?= base_url('admin/dapodik/syncTeachers') ?>" method="post">
    <?= csrf_field() ?>

    <div class="card mb-3">
        <div class="card-body py-2 text-end">
            <button type="submit" class="btn btn-info text-white">🚀 Mulai Sinkronisasi Guru</button>
        </div>
    </div>

    <div class="card">
        <div class="card-body table-responsive">
            <table class="table table-striped table-hover align-middle">
                <thead class="table-dark">
                    <tr>
                        <th width="40"><input type="checkbox" id="check-all" class="form-check-input"></th>
                        <th>NIP / NUPTK</th>
                        <th>Nama Lengkap</th>
                        <th>L/P</th>
                        <th>Email</th>
                        <th>Status Tugas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($teachers)): ?>
                        <?php foreach ($teachers as $index => $t): ?>
                            <tr>
                                <td>
                                    <input type="checkbox" name="selected_teachers[]" value="<?= $index ?>"
                                        class="form-check-input teacher-check">
                                    <input type="hidden" name="teachers_data[<?= $index ?>][nip]"
                                        value="<?= esc($t['nip'] ?? '') ?>">
                                    <input type="hidden" name="teachers_data[<?= $index ?>][nuptk]"
                                        value="<?= esc($t['nuptk'] ?? '') ?>">
                                    <input type="hidden" name="teachers_data[<?= $index ?>][nama]"
                                        value="<?= esc($t['nama']) ?>">
                                    <input type="hidden" name="teachers_data[<?= $index ?>][jenis_kelamin]"
                                        value="<?= esc($t['jenis_kelamin']) ?>">
                                    <input type="hidden" name="teachers_data[<?= $index ?>][email]"
                                        value="<?= esc($t['email'] ?? '') ?>">
                                    <input type="hidden" name="teachers_data[<?= $index ?>][tempat_lahir]"
                                        value="<?= esc($t['tempat_lahir'] ?? '') ?>">
                                    <input type="hidden" name="teachers_data[<?= $index ?>][tanggal_lahir]"
                                        value="<?= esc($t['tanggal_lahir'] ?? '') ?>">
                                </td>
                                <td>
                                    <small>NIP:
                                        <?= esc($t['nip'] ?? '-') ?>
                                    </small><br>
                                    <small>NUPTK:
                                        <?= esc($t['nuptk'] ?? '-') ?>
                                    </small>
                                </td>
                                <td class="fw-bold">
                                    <?= esc($t['nama'] ?? '-') ?>
                                </td>
                                <td>
                                    <?= esc($t['jenis_kelamin'] ?? '-') ?>
                                </td>
                                <td>
                                    <?= esc($t['email'] ?? '-') ?>
                                </td>
                                <td><small class="text-muted">
                                        <?= esc($t['tugas_tambahan'] ?? '-') ?>
                                    </small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Tidak ada data guru ditemukan di Dapodik
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
        const checks = document.querySelectorAll('.teacher-check');
        checks.forEach(c => c.checked = this.checked);
    });
</script>

<?= $this->endSection() ?>