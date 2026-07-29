<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h4>Perbaiki Siswa Tanpa Kelas</h4>
<p class="text-muted">Tahun ajaran aktif: <strong><?= esc($tahunAktif['nama']) ?></strong></p>

<?php if (session()->getFlashdata('msg_success')): ?>
  <div class="alert alert-success alert-dismissible fade show" role="alert">
    <i class="bi bi-check-circle-fill me-2"></i>
    <?= session()->getFlashdata('msg_success') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<?php if (session()->getFlashdata('msg_error')): ?>
  <div class="alert alert-danger alert-dismissible fade show" role="alert">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    <?= session()->getFlashdata('msg_error') ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
  </div>
<?php endif; ?>

<a href="<?= site_url('admin/kenaikan') ?>" class="btn btn-sm btn-outline-secondary mb-3">
  &larr; Kembali ke Kenaikan
</a>

<?php if (empty($siswa)): ?>
  <div class="alert alert-success">
    <i class="bi bi-check-circle-fill me-2"></i>
    Tidak ada siswa aktif yang tanpa kelas. Semua data sudah benar.
  </div>
<?php else: ?>
  <div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Ditemukan <strong><?= count($siswa) ?> siswa aktif</strong> tanpa kelas di tahun ajaran <strong><?= esc($tahunAktif['nama']) ?></strong>.
    Pilih kelas yang benar untuk setiap siswa, lalu simpan.
  </div>

  <form method="post" action="<?= site_url('admin/kenaikan/perbaiki-simpan') ?>">
    <?= csrf_field() ?>
    <table class="table table-bordered">
      <thead class="table-light">
        <tr>
          <th>#</th>
          <th>NIS</th>
          <th>Nama</th>
          <th>Kelas Tujuan <span class="text-danger">*</span></th>
        </tr>
      </thead>
      <tbody>
        <?php $no = 1; foreach ($siswa as $s): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= esc($s['nis']) ?></td>
            <td><?= esc($s['nama']) ?></td>
            <td>
              <select name="perbaiki[<?= $s['id'] ?>]" class="form-select form-select-sm" required>
                <option value="">-- Pilih kelas --</option>
                <?php foreach ($kelas as $k): ?>
                  <option value="<?= $k['id'] ?>"><?= esc($k['nama_kelas']) ?></option>
                <?php endforeach ?>
              </select>
            </td>
          </tr>
        <?php endforeach ?>
      </tbody>
    </table>

    <button type="submit" class="btn btn-primary"
      onclick="return confirm('Simpan perubahan kelas untuk <?= count($siswa) ?> siswa?')">
      <i class="bi bi-save me-1"></i> Simpan Perbaikan
    </button>
  </form>
<?php endif ?>

<?= $this->endSection() ?>
