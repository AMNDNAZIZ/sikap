<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-3 px-md-4 pb-5">
  <div class="d-flex align-items-center gap-2 mt-3 mb-3 flex-wrap">
    <a href="<?= site_url('admin/tugas') ?>" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left"></i>
    </a>
    <div class="flex-grow-1">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <h4 class="fw-bold mb-0"><?= esc($tugas['judul']) ?></h4>
        <?php if (!empty($tugas['material_id'])): ?>
          <span class="badge bg-info text-dark">
            <i class="bi bi-diagram-3 me-1"></i>Formatif (Segmen Materi)
          </span>
        <?php else: ?>
          <span class="badge bg-secondary">
            <i class="bi bi-journal-text me-1"></i>Sumatif (Umum)
          </span>
        <?php endif; ?>
      </div>
    </div>
    
    <?php if (empty($isViewOnly)): ?>
      <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#modalKirimBukuNilai">
        <i class="bi bi-send-check me-1"></i>Kirim ke Buku Nilai
      </button>
      <a href="<?= site_url('admin/tugas/edit/' . $tugas['id']) ?>" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-pencil me-1"></i>Edit
      </a>
    <?php endif; ?>
  </div>

  <?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success border-0 shadow-sm alert-dismissible fade show">
      <i class="bi bi-check-circle-fill me-2"></i><?= session()->getFlashdata('success') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger border-0 shadow-sm alert-dismissible fade show">
      <i class="bi bi-exclamation-triangle-fill me-2"></i><?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
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
          <div class="text-muted small">Waktu Pelaksanaan</div>
          <div class="fw-semibold small">
            <?= date('d/m/Y H:i', strtotime($tugas['mulai_at'])) ?>
            → <?= date('d/m/Y H:i', strtotime($tugas['selesai_at'])) ?>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="text-muted small">Status & Sasaran Nilai</div>
          <div>
            <span class="badge bg-<?= $statusClass[$tugas['status']] ?> me-1">
              <?= $statusLabel[$tugas['status']] ?>
            </span>
            <span class="badge bg-light text-dark border">
              <?= !empty($tugas['material_id']) ? 'Buku Nilai: Formatif' : 'Buku Nilai: Sumatif' ?>
            </span>
          </div>
        </div>
      </div>

      <?php if (!empty($material)): ?>
      <div class="mt-3 p-2 bg-light rounded border small">
        <span class="text-muted"><i class="bi bi-link-45deg me-1"></i>Terkait Materi:</span>
        <strong class="ms-1"><?= esc($material['title']) ?></strong>
        <?php if (!empty($segment)): ?>
          <span class="text-muted"> / Segmen:</span> <strong><?= esc($segment['title']) ?></strong>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($tugas['deskripsi'])): ?>
      <hr>
      <div class="text-muted small mb-1 fw-semibold">Deskripsi / Petunjuk Tugas:</div>
      <div class="border rounded p-3 bg-light" style="max-height:300px;overflow-y:auto;">
        <?= $tugas['deskripsi'] ?>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Daftar siswa & status pengumpulan -->
  <div class="card shadow-sm border-0">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <span class="fw-semibold">Daftar Pengumpulan Siswa</span>
        <span class="badge bg-light text-dark ms-1"><?= count($students) ?> siswa</span>
      </div>
      <?php if (empty($isViewOnly)): ?>
      <button type="button" class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalKirimBukuNilai">
        <i class="bi bi-send-check me-1"></i>Kirim ke Buku Nilai
      </button>
      <?php endif; ?>
    </div>
    <div class="card-body p-0">

      <!-- DESKTOP -->
      <div class="d-none d-md-block table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th style="width: 45px;">#</th>
              <th>NIS</th>
              <th>Nama Siswa</th>
              <th>Dikumpulkan</th>
              <th>Nilai Angka</th>
              <th>Predikat</th>
              <th style="width: 140px;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php $no = 1; foreach ($students as $siswa):
              $sub = $submissionMap[$siswa['id']] ?? null;
              $hasNilaiAngka = ($sub && $sub['nilai_angka'] !== null && $sub['nilai_angka'] !== '');
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
                <?php if ($hasNilaiAngka): ?>
                  <span class="fw-bold fs-6 text-primary"><?= number_format((float)$sub['nilai_angka'], 1) ?></span>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td>
                <?php if ($sub && !empty($sub['nilai'])): ?>
                  <span class="badge bg-<?= $nilaiInfo[$sub['nilai']]['class'] ?? 'secondary' ?>">
                    <?= $nilaiInfo[$sub['nilai']]['label'] ?? esc($sub['nilai']) ?>
                  </span>
                  <?php if (!empty($sub['catatan_guru'])): ?>
                    <div class="small text-muted mt-1 text-truncate" style="max-width:200px;" title="<?= esc($sub['catatan_guru']) ?>">
                      <i class="bi bi-chat-left-text me-1"></i><?= esc($sub['catatan_guru']) ?>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <span class="text-muted small">Belum Dinilai</span>
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
                          data-nilai-angka="<?= $hasNilaiAngka ? (float)$sub['nilai_angka'] : '' ?>"
                          data-nilai="<?= $sub['nilai'] ?? '' ?>"
                          data-catatan="<?= esc($sub['catatan_guru'] ?? '') ?>">
                    <i class="bi bi-eye me-1"></i><?= empty($isViewOnly) ? 'Lihat & Nilai' : 'Lihat Jawaban' ?>
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
          $hasNilaiAngka = ($sub && $sub['nilai_angka'] !== null && $sub['nilai_angka'] !== '');
        ?>
        <div class="border rounded p-3 mb-2">
          <div class="d-flex justify-content-between align-items-start">
            <div>
              <div class="fw-semibold"><?= esc($siswa['name']) ?></div>
              <div class="text-muted small"><?= esc($siswa['nis']) ?></div>
            </div>
            <div>
              <?php if ($hasNilaiAngka): ?>
                <span class="badge bg-primary fs-6"><?= number_format((float)$sub['nilai_angka'], 1) ?></span>
              <?php endif; ?>
              <?php if ($sub && !empty($sub['nilai'])): ?>
                <span class="badge bg-<?= $nilaiInfo[$sub['nilai']]['class'] ?? 'secondary' ?>"><?= $nilaiInfo[$sub['nilai']]['label'] ?? esc($sub['nilai']) ?></span>
              <?php elseif ($sub): ?>
                <span class="badge bg-light text-dark">Belum Dinilai</span>
              <?php else: ?>
                <span class="badge bg-secondary">Belum Kumpul</span>
              <?php endif; ?>
            </div>
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
                    data-nilai-angka="<?= $hasNilaiAngka ? (float)$sub['nilai_angka'] : '' ?>"
                    data-nilai="<?= $sub['nilai'] ?? '' ?>"
                    data-catatan="<?= esc($sub['catatan_guru'] ?? '') ?>">
              <i class="bi bi-eye me-1"></i><?= empty($isViewOnly) ? 'Lihat & Nilai' : 'Lihat Jawaban' ?>
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
        <h5 class="modal-title">Jawaban Siswa: <span id="modalNama" class="fw-bold"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="text-muted small fw-semibold mb-1">Isi / Lampiran Jawaban:</div>
        <div class="border rounded p-3 bg-light mb-4" id="modalJawabanContent" style="min-height:100px; max-height: 250px; overflow-y: auto;"></div>

        <form id="formNilai" method="post">
          <?= csrf_field() ?>
          <?php if (empty($isViewOnly)): ?>
          <div class="row g-3 mb-3">
            <div class="col-md-5">
              <label class="form-label fw-semibold">Nilai Angka (0 - 100) <span class="text-danger">*</span></label>
              <div class="input-group">
                <input type="number" step="0.5" min="0" max="100" name="nilai_angka" id="inputNilaiAngka" class="form-control form-control-lg fw-bold text-primary" required placeholder="85">
                <span class="input-group-text"><i class="bi bi-calculator"></i></span>
              </div>
              <div class="form-text small">Masukkan skor angka rentang 0 sampai 100.</div>
            </div>
            <div class="col-md-7">
              <label class="form-label fw-semibold">Predikat Otomatis</label>
              <div class="p-2 border rounded bg-white" id="boxPredikatInfo">
                <span class="badge bg-secondary" id="badgePredikatPreview">— Belum diinput —</span>
                <span class="small text-muted ms-2" id="textPredikatDesc"></span>
              </div>
              <div class="form-text small">
                86-100: Sangat Bagus &bull; 71-85: Bagus &bull; 56-70: Kurang &bull; &lt;56: Belajar Lagi
              </div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan / Umpan Balik Guru <small class="text-muted">(opsional)</small></label>
            <textarea name="catatan_guru" id="catatanGuru" class="form-control" rows="2"
                      placeholder="Tulis catatan apresiasi atau masukan perbaikan untuk siswa..."></textarea>
          </div>
          <div class="d-flex justify-content-end gap-2">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i>Simpan Penilaian
            </button>
          </div>
          <?php else: ?>
          <div class="alert alert-info mb-0 mt-2 py-2 small">
            <i class="bi bi-info-circle me-1"></i>Kepala Sekolah hanya dapat melihat jawaban siswa.
          </div>
          <?php endif; ?>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Modal Konfirmasi Kirim ke Buku Nilai -->
<div class="modal fade" id="modalKirimBukuNilai" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?= site_url('admin/tugas/' . $tugas['id'] . '/kirim-ke-buku-nilai') ?>" method="post">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title fw-bold">
            <i class="bi bi-send-check text-success me-1"></i>Kirim Nilai ke Buku Nilai
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="mb-3">
            Apakah Anda yakin ingin mengirimkan seluruh nilai tugas yang telah dinilai ini ke 
            <strong><?= !empty($tugas['material_id']) ? 'Buku Nilai Formatif (Tipe: Penugasan)' : 'Buku Nilai Sumatif (Tipe: Penugasan)' ?></strong>?
          </p>

          <div class="alert alert-info border-0 small">
            <div class="fw-semibold mb-1"><i class="bi bi-info-circle me-1"></i>Ketentuan Sinkronisasi:</div>
            <ul class="mb-0 ps-3">
              <?php if (!empty($tugas['material_id'])): ?>
                <li>Nilai akan masuk ke <strong>Formatif</strong> terkait lingkup materi sub/bab ini.</li>
                <li>Hanya siswa yang telah memiliki <strong>nilai angka</strong> yang akan disinkronkan.</li>
                <li>Menerapkan prinsip <strong>Nilai Tertinggi (Best Score)</strong>: Jika siswa telah memiliki nilai tugas pada lingkup materi ini sebelumnya, sistem tidak akan menurunkannya.</li>
              <?php else: ?>
                <li>Nilai akan masuk ke <strong>Sumatif</strong> semester aktif untuk mata pelajaran ini.</li>
                <li>Hanya siswa yang telah dinilai angka yang akan disinkronkan.</li>
                <li>Menerapkan prinsip <strong>Nilai Tertinggi (Best Score)</strong> jika terdapat entri penugasan sumatif sebelumnya.</li>
              <?php endif; ?>
            </ul>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-success">
            <i class="bi bi-check2-circle me-1"></i>Ya, Kirim Sekarang
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function hitungPredikat(val) {
  const badge = document.getElementById('badgePredikatPreview');
  const desc = document.getElementById('textPredikatDesc');
  if (!badge) return;

  if (val === '' || isNaN(val)) {
    badge.className = 'badge bg-secondary';
    badge.textContent = '— Belum diinput —';
    if (desc) desc.textContent = '';
    return;
  }

  const num = parseFloat(val);
  if (num >= 86) {
    badge.className = 'badge bg-success';
    badge.textContent = '⭐ Sangat Bagus';
    if (desc) desc.textContent = '(86 - 100)';
  } else if (num >= 71) {
    badge.className = 'badge bg-primary';
    badge.textContent = '👍 Bagus';
    if (desc) desc.textContent = '(71 - 85)';
  } else if (num >= 56) {
    badge.className = 'badge bg-warning text-dark';
    badge.textContent = '⚠️ Kurang';
    if (desc) desc.textContent = '(56 - 70)';
  } else {
    badge.className = 'badge bg-danger';
    badge.textContent = '🔄 Belajar Lagi';
    if (desc) desc.textContent = '(< 56)';
  }
}

document.getElementById('inputNilaiAngka')?.addEventListener('input', function() {
  hitungPredikat(this.value);
});

document.getElementById('modalJawaban').addEventListener('show.bs.modal', function(e) {
  const btn = e.relatedTarget;
  document.getElementById('modalNama').textContent = btn.dataset.nama;
  document.getElementById('modalJawabanContent').innerHTML = btn.dataset.jawaban || '<em class="text-muted">Tidak ada jawaban</em>';

  const tugasId    = btn.dataset.tugasId;
  const studentId  = btn.dataset.studentId;
  const nilaiAngka = btn.dataset.nilaiAngka;
  const catatan    = btn.dataset.catatan;

  // Set action form
  const formNilai = document.getElementById('formNilai');
  if (formNilai) {
    formNilai.action = '<?= site_url('admin/tugas') ?>/' + tugasId + '/nilai/' + studentId;
  }

  const inputAngka = document.getElementById('inputNilaiAngka');
  if (inputAngka) {
    inputAngka.value = nilaiAngka || '';
    hitungPredikat(nilaiAngka || '');
  }

  const inputCatatan = document.getElementById('catatanGuru');
  if (inputCatatan) {
    inputCatatan.value = catatan || '';
  }
});
</script>

<?= $this->endSection() ?>
