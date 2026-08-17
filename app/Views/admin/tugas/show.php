<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-3 px-md-4 pb-5">
  <div class="d-flex align-items-center gap-2 mt-3 mb-3 flex-wrap">
    <a href="<?= site_url('admin/tugas') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <h4 class="fw-bold mb-0 flex-grow-1"><?= esc($tugas['judul']) ?></h4>
    <a href="<?= site_url('admin/tugas/edit/' . $tugas['id']) ?>" class="btn btn-sm btn-outline-primary">
      <i class="bi bi-pencil me-1"></i>Edit
    </a>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0"><?= session()->getFlashdata('success') ?></div>
  <?php endif; ?>

  <?php
    $statusLabel = ['belum' => 'Belum Dimulai', 'aktif' => 'Aktif', 'terlewat' => 'Berakhir'];
    $statusClass = ['belum' => 'secondary', 'aktif' => 'success', 'terlewat' => 'danger'];
    $nilaiInfo = [
      'sangat_bagus'  => ['label' => '⭐ Sangat Bagus', 'class' => 'success'],
      'bagus'         => ['label' => '👍 Bagus',        'class' => 'primary'],
      'kurang'        => ['label' => '⚠️ Kurang',       'class' => 'warning'],
      'belajar_lagi'  => ['label' => '🔄 Belajar Lagi', 'class' => 'danger'],
    ];
  ?>

  <!-- Info tugas -->
  <div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-sm-6 col-md-3">
          <div class="text-muted small">Kelas</div>
          <div class="fw-semibold"><?= esc($class['name'] ?? '-') ?></div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="text-muted small">Mata Pelajaran</div>
          <div class="fw-semibold"><?= esc($subject['name'] ?? '-') ?></div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="text-muted small">Waktu</div>
          <div class="fw-semibold small">
            <?= date('d/m/Y H:i', strtotime($tugas['mulai_at'])) ?>
            → <?= date('d/m/Y H:i', strtotime($tugas['selesai_at'])) ?>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="text-muted small">Status</div>
          <span class="badge bg-<?= $statusClass[$tugas['status']] ?>">
            <?= $statusLabel[$tugas['status']] ?>
          </span>
        </div>
      </div>
      <?php if (!empty($tugas['deskripsi'])): ?>
      <hr>
      <div class="text-muted small mb-1 fw-semibold">Deskripsi Tugas:</div>
      <div class="border rounded p-3 bg-light" style="max-height:300px;overflow-y:auto;">
        <?= $tugas['deskripsi'] ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Daftar siswa & status pengumpulan -->
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3 fw-semibold">
      Daftar Pengumpulan Siswa
      <span class="badge bg-light text-dark ms-1"><?= count($students) ?> siswa</span>
    </div>
    <div class="card-body p-0">

      <!-- DESKTOP -->
      <div class="d-none d-md-block table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>#</th>
              <th>NIS</th>
              <th>Nama Siswa</th>
              <th>Dikumpulkan</th>
              <th>Nilai</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($students as $siswa):
              $sub = $submissionMap[$siswa['id']] ?? null;
            ?>
            <tr>
              <td><?= $no++ ?></td>
              <td><?= esc($siswa['nis']) ?></td>
              <td><?= esc($siswa['name']) ?></td>
              <td>
                <?php if ($sub && $sub['dikumpul_at']): ?>
                  <span class="text-success small">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    <?= date('d/m/Y H:i', strtotime($sub['dikumpul_at'])) ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted small"><i class="bi bi-dash-circle me-1"></i>Belum</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($sub && $sub['nilai']): ?>
                  <span class="badge bg-<?= $nilaiInfo[$sub['nilai']]['class'] ?>">
                    <?= $nilaiInfo[$sub['nilai']]['label'] ?>
                  </span>
                  <?php if ($sub['catatan_guru']): ?>
                    <div class="small text-muted mt-1"><?= esc($sub['catatan_guru']) ?></div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($sub && $sub['dikumpul_at']): ?>
                  <!-- Lihat jawaban + beri nilai -->
                  <button class="btn btn-sm btn-outline-info"
                          data-bs-toggle="modal"
                          data-bs-target="#modalJawaban"
                          data-nama="<?= esc($siswa['name']) ?>"
                          data-jawaban="<?= esc($sub['jawaban']) ?>"
                          data-student-id="<?= $siswa['id'] ?>"
                          data-tugas-id="<?= $tugas['id'] ?>"
                          data-nilai="<?= $sub['nilai'] ?? '' ?>"
                          data-catatan="<?= esc($sub['catatan_guru'] ?? '') ?>">
                    <i class="bi bi-eye me-1"></i>Lihat & Nilai
                  </button>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- MOBILE -->
      <div class="d-md-none px-3 py-2">
        <?php foreach ($students as $siswa):
          $sub = $submissionMap[$siswa['id']] ?? null;
        ?>
        <div class="border rounded p-3 mb-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-semibold"><?= esc($siswa['name']) ?></div>
              <div class="text-muted small"><?= esc($siswa['nis']) ?></div>
            </div>
            <?php if ($sub && $sub['nilai']): ?>
              <span class="badge bg-<?= $nilaiInfo[$sub['nilai']]['class'] ?>"><?= $nilaiInfo[$sub['nilai']]['label'] ?></span>
            <?php elseif ($sub): ?>
              <span class="badge bg-light text-dark">Belum Dinilai</span>
            <?php else: ?>
              <span class="badge bg-secondary">Belum Kumpul</span>
            <?php endif; ?>
          </div>
          <?php if ($sub && $sub['dikumpul_at']): ?>
          <div class="mt-2">
            <div class="small text-muted">
              <i class="bi bi-clock me-1"></i><?= date('d/m/Y H:i', strtotime($sub['dikumpul_at'])) ?>
            </div>
            <button class="btn btn-sm btn-outline-info mt-1 w-100"
                    data-bs-toggle="modal"
                    data-bs-target="#modalJawaban"
                    data-nama="<?= esc($siswa['name']) ?>"
                    data-jawaban="<?= esc($sub['jawaban']) ?>"
                    data-student-id="<?= $siswa['id'] ?>"
                    data-tugas-id="<?= $tugas['id'] ?>"
                    data-nilai="<?= $sub['nilai'] ?? '' ?>"
                    data-catatan="<?= esc($sub['catatan_guru'] ?? '') ?>">
              <i class="bi bi-eye me-1"></i>Lihat & Nilai
            </button>
          </div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

<!-- Modal Lihat Jawaban & Beri Nilai -->
<div class="modal fade" id="modalJawaban" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Jawaban: <span id="modalNama"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="border rounded p-3 bg-light mb-4" id="modalJawabanContent" style="min-height:100px;"></div>

        <form id="formNilai" method="post">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label fw-semibold">Penilaian</label>
            <div class="d-flex flex-wrap gap-2">
              <?php
                $nilaiOpts = [
                  'sangat_bagus' => ['⭐ Sangat Bagus', 'success'],
                  'bagus'        => ['👍 Bagus',        'primary'],
                  'kurang'       => ['⚠️ Kurang',       'warning'],
                  'belajar_lagi' => ['🔄 Belajar Lagi', 'danger'],
                ];
                foreach ($nilaiOpts as $val => [$lbl, $cls]):
              ?>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="nilai"
                       id="nilai_<?= $val ?>" value="<?= $val ?>">
                <label class="form-check-label" for="nilai_<?= $val ?>">
                  <span class="badge bg-<?= $cls ?>"><?= $lbl ?></span>
                </label>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan untuk Siswa <small class="text-muted">(opsional)</small></label>
            <textarea name="catatan_guru" id="catatanGuru" class="form-control" rows="2"
                      placeholder="Tulis catatan atau masukan untuk siswa..."></textarea>
          </div>
          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Simpan Penilaian
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<script>
document.getElementById('modalJawaban').addEventListener('show.bs.modal', function(e) {
  const btn = e.relatedTarget;
  document.getElementById('modalNama').textContent = btn.dataset.nama;
  document.getElementById('modalJawabanContent').innerHTML = btn.dataset.jawaban || '<em class="text-muted">Tidak ada jawaban</em>';

  const tugasId    = btn.dataset.tugasId;
  const studentId  = btn.dataset.studentId;
  const nilaiSaat  = btn.dataset.nilai;
  const catatan    = btn.dataset.catatan;

  // Set action form
  document.getElementById('formNilai').action =
    '<?= site_url('admin/tugas') ?>/' + tugasId + '/nilai/' + studentId;

  // Preselect nilai jika sudah ada
  document.querySelectorAll('input[name="nilai"]').forEach(r => {
    r.checked = (r.value === nilaiSaat);
  });

  document.getElementById('catatanGuru').value = catatan || '';
});
</script>

<?= $this->endSection() ?>
