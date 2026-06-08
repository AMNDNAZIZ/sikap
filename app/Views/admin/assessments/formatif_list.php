<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📋 Daftar Penilaian Formatif</h3>

<div class="mb-3">
  <table class="table table-sm">
    <tr><th style="width:150px;">Kelas</th><td><?= esc($class['name'] ?? '-') ?></td></tr>
    <tr><th>Mata Pelajaran</th><td><?= esc($subject['name'] ?? '-') ?></td></tr>
  </table>

  <!-- Mengarahkan ulang tambah materi ke laman Administrasi Guru (ATP) -->
  <a href="<?= site_url("admin/administrasi-guru/atp?class_id={$classId}&subject_id={$subjectId}") ?>"
   class="btn btn-success mb-2">➕ Tambah Lingkup Materi (ATP)</a>
   
   <p class="text-muted small"><strong>Catatan:</strong> "Lingkup Materi" yang Anda inputkan di halaman capaian pembelajaran (ATP) akan otomatis terdaftar sebagai sasaran Penilaian Formatif di sini.</p>

</div>

<div class="table-responsive">
  <table class="table table-bordered align-middle">
    <thead class="table-light">
      <tr>
        <th style="width:50px;">#</th>
        <th>Lingkup Materi (ATP)</th>
        <th>Semester</th>
        <th>Status</th>
        <th style="width:320px;">Aksi</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!empty($materials)): ?>
        <?php $no = 1; foreach ($materials as $m): ?>
          <tr>
            <td><?= $no++ ?></td>
            <td><?= esc($m['title']) ?></td>
            <td><?= ucfirst($m['semester']) ?></td>
            <td>
              <?php if (!empty($m['jumlah_nilai']) && $m['jumlah_nilai'] > 0): ?>
                <span class="badge bg-success">Sudah (<?= esc($m['metode_terpakai']) ?>)</span>
              <?php else: ?>
                <span class="badge bg-secondary">Belum</span>
              <?php endif; ?>
            </td>
            <td>
              <!-- Input Nilai (kembalikan ke page input, material dikirim via query) -->
              <a href="<?= base_url("admin/assessments/input/{$classId}/{$subjectId}/formatif?material_id={$m['id']}") ?>"
                 class="btn btn-sm btn-primary">✏️ Input Nilai</a>

              <!-- Jika sudah ada nilai, tampilkan tombol Lihat Nilai per metode -->
              <?php
                $methods = [];
                if (!empty($m['metode_terpakai'])) {
                  // GROUP_CONCAT biasanya menghasilkan "tulis,lisan", pisah dan trim
                  $methods = array_map('trim', explode(',', $m['metode_terpakai']));
                }
              ?>

              <?php if (!empty($methods)): ?>
                <?php foreach ($methods as $mt): ?>
                  <a href="<?= base_url("admin/assessments/viewScores/formatif/{$m['id']}/{$mt}") ?>"
                     class="btn btn-sm btn-info ms-1"><?= ucfirst($mt) ?></a>
                <?php endforeach; ?>
                <!-- Opsi lihat semua metode sekaligus -->
                <a href="<?= base_url("admin/assessments/viewScores/formatif/{$m['id']}/all") ?>"
                   class="btn btn-sm btn-secondary ms-1">Lihat Semua</a>
              <?php else: ?>
                <span class="text-muted ms-2">—</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      <?php else: ?>
        <tr><td colspan="5" class="text-center text-muted">Belum ada materi</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<a href="<?= base_url('admin/assessments') ?>" class="btn btn-secondary mt-3">⬅️ Kembali</a>

<?= $this->endSection() ?>
