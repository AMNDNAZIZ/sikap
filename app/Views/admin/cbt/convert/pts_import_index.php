<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex align-items-center mb-3 gap-2">
    <a href="<?= site_url("admin/assessments/ptsList/{$classId}/{$subjectId}") ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Kembali
    </a>
    <div>
      <h5 class="mb-0 fw-bold">📥 Impor Nilai CBT ke PTS</h5>
      <small class="text-muted">
        Kelas: <strong><?= esc($class['name']) ?></strong> &bull;
        Mapel: <strong><?= esc($subject['name']) ?></strong> &bull;
        Semester: <strong><?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?></strong>
      </small>
    </div>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>

  <div class="row">
    <div class="col-md-7">
      <div class="card shadow-sm">
        <div class="card-header bg-white fw-bold">Pilih Bank Soal CBT</div>
        <div class="card-body">
          <?php if (empty($banks)): ?>
            <div class="alert alert-warning mb-0">
              ⚠️ Tidak ada bank soal aktif untuk mata pelajaran <strong><?= esc($subject['name']) ?></strong>.
              <br><small>Pastikan ujian CBT untuk mapel ini sudah dibuat dan diaktifkan.</small>
            </div>
          <?php else: ?>
            <form action="<?= site_url('admin/cbt/pts-import/preview') ?>" method="post">
              <?= csrf_field() ?>
              <input type="hidden" name="class_id"   value="<?= $classId ?>">
              <input type="hidden" name="subject_id" value="<?= $subjectId ?>">
              <input type="hidden" name="semester"   value="<?= esc($semester) ?>">

              <div class="mb-4">
                <label class="form-label fw-semibold">Bank Soal Ujian CBT</label>
                <div class="table-responsive">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width:40px;"></th>
                        <th>Kode Bank Soal</th>
                        <th>Mata Pelajaran</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($banks as $b): ?>
                        <tr>
                          <td class="text-center">
                            <input type="radio" name="bank_id" value="<?= $b['id'] ?>" required
                                   class="form-check-input" style="width:18px;height:18px;">
                          </td>
                          <td class="fw-semibold"><?= esc($b['code']) ?></td>
                          <td><?= esc($b['subject_name']) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <div class="alert alert-info small mb-4">
                <i class="bi bi-info-circle"></i>
                Nilai yang diambil adalah <strong>skor CBT apa adanya (0–100)</strong> tanpa konversi.
                Jika siswa punya beberapa sesi untuk bank soal yang sama, diambil nilai yang tersimpan.
                Siswa yang tidak mengikuti ujian tidak akan masuk ke PTS.
              </div>

              <button type="submit" class="btn btn-primary">
                <i class="bi bi-arrow-right-circle"></i> Lihat Preview Nilai
              </button>
            </form>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-5">
      <div class="card bg-light border-0 h-100">
        <div class="card-body">
          <h6><i class="bi bi-info-circle"></i> Tentang Impor CBT ke PTS</h6>
          <ul class="small text-muted ps-3">
            <li>Nilai CBT diambil <strong>apa adanya</strong> (skor mentah 0–100).</li>
            <li>Nilai hanya diperbarui jika nilai baru <strong>lebih besar</strong> dari yang sudah ada.</li>
            <li>Siswa yang <strong>tidak mengikuti ujian</strong> diabaikan (tidak mendapat nilai 0 otomatis).</li>
            <li>Nilai PTS <strong>tidak masuk</strong> perhitungan nilai rapor akhir.</li>
          </ul>
          <div class="alert alert-warning small mb-0">
            <i class="bi bi-exclamation-triangle"></i>
            Pastikan ujian CBT sudah selesai dilaksanakan sebelum mengimpor.
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>
