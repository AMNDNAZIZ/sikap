<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= site_url("admin/cbt/pts-import?class_id={$classId}&subject_id={$subjectId}&semester={$semester}") ?>"
       class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <div>
      <h5 class="mb-0 fw-bold">📋 Preview Nilai CBT untuk PTS</h5>
      <small class="text-muted">
        Bank Soal: <strong><?= esc($test['bank_code']) ?></strong> &bull;
        Mapel: <strong><?= esc($subject['name']) ?></strong> &bull;
        Kelas: <strong><?= esc($class['name']) ?></strong> &bull;
        Semester: <strong><?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></strong>
      </small>
    </div>
  </div>

  <div class="row">
    <div class="col-md-8">
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <span class="fw-bold">Daftar Nilai Siswa</span>
          <span class="badge bg-info text-dark">
            <?= count(array_filter($results, fn($r) => $r['ikut_ujian'])) ?> dari <?= count($results) ?> siswa ikut ujian
          </span>
        </div>
        <div class="card-body p-0">
          <div class="table-responsive">
            <table class="table table-hover table-striped mb-0 align-middle">
              <thead class="table-light">
                <tr>
                  <th class="text-center" style="width:50px;">No</th>
                  <th>Nama Siswa</th>
                  <th class="text-center" style="width:80px;">NIS</th>
                  <th class="text-center" style="width:120px;">Nilai CBT</th>
                  <th class="text-center" style="width:80px;">Status</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($results as $i => $r): ?>
                  <tr class="<?= !$r['ikut_ujian'] ? 'table-warning' : '' ?>">
                    <td class="text-center"><?= $i + 1 ?></td>
                    <td><?= esc($r['student_name']) ?></td>
                    <td class="text-center text-muted small"><?= esc($r['nis'] ?? '-') ?></td>
                    <td class="text-center fw-bold <?= !$r['ikut_ujian'] ? 'text-muted' : 'text-primary' ?>">
                      <?= $r['ikut_ujian'] ? number_format($r['raw_score'], 1) : '-' ?>
                    </td>
                    <td class="text-center">
                      <?php if ($r['ikut_ujian']): ?>
                        <span class="badge bg-success">Ikut Ujian</span>
                      <?php else: ?>
                        <span class="badge bg-warning text-dark">Tidak Ikut</span>
                      <?php endif; ?>
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
      <div class="card shadow-sm border-success">
        <div class="card-header bg-success text-white fw-bold">
          <i class="bi bi-save"></i> Simpan Nilai ke PTS
        </div>
        <div class="card-body">
          <div class="alert alert-info small py-2 mb-3">
            <i class="bi bi-info-circle"></i>
            Hanya siswa yang <strong>ikut ujian</strong> yang akan disimpan.
            Nilai hanya diperbarui jika nilai baru <strong>lebih besar</strong> dari yang sudah ada.
          </div>

          <div class="mb-3 small">
            <table class="table table-sm table-bordered mb-0">
              <tr><th>Kelas</th><td><?= esc($class['name']) ?></td></tr>
              <tr><th>Mapel</th><td><?= esc($subject['name']) ?></td></tr>
              <tr><th>Bank Soal</th><td><?= esc($test['bank_code']) ?></td></tr>
              <tr><th>Semester</th><td><?= $semester == '1' ? 'Semester 1 (Ganjil)' : 'Semester 2 (Genap)' ?></td></tr>
              <tr><th>Tahun Ajaran</th><td><?= esc($activeYear['year'] ?? '-') ?></td></tr>
              <tr><th>Siswa Ikut Ujian</th><td><?= count(array_filter($results, fn($r) => $r['ikut_ujian'])) ?> siswa</td></tr>
            </table>
          </div>

          <form action="<?= site_url('admin/cbt/pts-import/save') ?>" method="post"
                onsubmit="return confirm('Yakin simpan nilai CBT ini ke PTS?\nNilai lama hanya diganti jika nilai baru lebih besar.')">
            <?= csrf_field() ?>
            <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
            <input type="hidden" name="class_id"   value="<?= $classId ?>">
            <input type="hidden" name="year_id"    value="<?= $activeYear['id'] ?? 0 ?>">
            <input type="hidden" name="semester"   value="<?= esc($semester) ?>">

            <?php foreach ($results as $r): ?>
              <?php if ($r['ikut_ujian'] && $r['raw_score'] !== null): ?>
                <input type="hidden"
                       name="student_scores[<?= $r['student_id'] ?>]"
                       value="<?= round($r['raw_score'], 2) ?>">
              <?php endif; ?>
            <?php endforeach; ?>

            <button type="submit" class="btn btn-success w-100 py-2 fw-bold">
              <i class="bi bi-save"></i> Simpan Nilai PTS
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
