<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h2>📋 Input Nilai (<?= ucfirst($type) ?>)</h2>

<form method="post" action="<?= base_url('admin/assessments/store') ?>">
  <?= csrf_field() ?>
  <input type="hidden" name="class_id" value="<?= esc($classId) ?>">
  <input type="hidden" name="subject_id" value="<?= esc($subjectId) ?>">
  <input type="hidden" name="type" value="<?= esc($type) ?>">

  <?php if ($type === 'formatif'): ?>
    <!-- === FORMATIF === -->
    <div class="mb-3">
      <label class="form-label">Lingkup Materi (ATP)</label>
      <select name="material_id" class="form-select"
        onchange="if(this.value){ window.location='<?= base_url("admin/assessments/input/{$classId}/{$subjectId}/formatif") ?>?material_id='+this.value }">
        <option value="">-- Pilih Lingkup Materi --</option>
        <?php foreach ($materials as $m): ?>
          <option value="<?= $m['id'] ?>" <?= (int) $materialId === (int) $m['id'] ? 'selected' : '' ?>>
            <?= esc($m['title']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <?php if ($materialId): ?>
      <div class="mb-3">
        <label class="form-label">Metode</label>
        <select name="method" class="form-select"
          onchange="if(this.value){ window.location='<?= base_url("admin/assessments/input/{$classId}/{$subjectId}/formatif") ?>?material_id=<?= $materialId ?>&method='+this.value }"
          required>
          <option value="">-- Pilih Metode --</option>
          <?php foreach (['tulis', 'lisan', 'projek', 'observasi'] as $mtd): ?>
            <?php
            $status = $methodStatus[$mtd] ?? null;
            $disabled = ($status === 'full' && $method !== $mtd) ? 'disabled' : '';
            $label = ucfirst($mtd);
            if ($status === 'full' && $method !== $mtd)
              $label .= ' (sudah ada)';
            elseif ($status === 'partial')
              $label .= ' (belum lengkap)';
            ?>
            <option value="<?= $mtd ?>" <?= $method === $mtd ? 'selected' : '' ?>       <?= $disabled ?>>
              <?= $label ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endif; ?>

  <?php elseif ($type === 'sumatif'): ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= esc(session()->getFlashdata('error')) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <?php if (empty($activeYear['id'])): ?>
      <div class="alert alert-warning">
        ⚠️ Tahun ajaran aktif tidak ditemukan. Silakan atur tahun ajaran terlebih dahulu di menu
        <a href="<?= site_url('admin/academic-years') ?>">Pengaturan Tahun Ajaran</a>.
      </div>
    <?php else: ?>
      <input type="hidden" name="year_id" value="<?= esc((int) $activeYear['id']) ?>">

      <!-- Semester -->
      <?php if (!empty($semester)): ?>
        <!-- Jika semester sudah dibawa dari sumatifList -->
        <input type="hidden" name="semester" value="<?= esc($semester) ?>">
        <div class="mb-3">
          <label class="form-label">Semester</label>
          <input type="text" class="form-control" value="Semester <?= esc($semester) ?>" readonly>
        </div>
      <?php endif; ?>

      <!-- Metode -->
      <div class="mb-3">
        <label class="form-label">Metode</label>
        <select name="method" class="form-select" required>
          <option value="">-- Pilih Metode --</option>
          <?php foreach (['tulis', 'penugasan'] as $mtd): ?>
            <?php
            $status = $methodStatus[$mtd] ?? null;
            $disabled = ($status === 'full' && $method !== $mtd) ? 'disabled' : '';
            $label = ucfirst($mtd);
            if ($status === 'full' && $method !== $mtd)
              $label .= ' (sudah ada)';
            elseif ($status === 'partial')
              $label .= ' (belum lengkap)';
            ?>
            <option value="<?= $mtd ?>" <?= $method === $mtd ? 'selected' : '' ?>       <?= $disabled ?>>
              <?= $label ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="alert alert-info">
        📅 Tahun Ajaran: <?= esc($activeYear['name'] ?? 'N/A') ?>
      </div>
    <?php endif; ?>


  <?php elseif ($type === 'final'): ?>
    <!-- === FINAL === -->
    <input type="hidden" name="year_id" value="<?= esc($activeYear['id'] ?? '') ?>">
    <div class="alert alert-info">
      📊 Penilaian Final hanya dilakukan sekali di kelas akhir (tidak terkait semester).
    </div>

  <?php elseif ($type === 'pts'): ?>
    <!-- === PTS (Penilaian Tengah Semester) === -->
    <input type="hidden" name="year_id" value="<?= esc($activeYear['id'] ?? '') ?>">
    <div class="mb-3">
      <label class="form-label">Semester</label>
      <select name="semester" class="form-select" required
        onchange="if(this.value){ window.location='<?= base_url("admin/assessments/input/{$classId}/{$subjectId}/pts") ?>?semester='+this.value }">
        <option value="">-- Pilih Semester --</option>
        <option value="1" <?= (string)($semester ?? '') === '1' ? 'selected' : '' ?>>Semester 1 (Ganjil)</option>
        <option value="2" <?= (string)($semester ?? '') === '2' ? 'selected' : '' ?>>Semester 2 (Genap)</option>
      </select>
    </div>
    <div class="alert alert-info">
      📝 Nilai PTS disimpan terpisah dan <strong>tidak</strong> ikut dalam perhitungan nilai rapor akhir.
    </div>
    <?php if (!empty($semester)): ?>
      <div class="alert alert-light border small">
        💡 Untuk mengisi dari hasil CBT, gunakan tombol <strong>📥 Impor dari CBT</strong>
        di halaman <a href="<?= base_url("admin/assessments/ptsList/{$classId}/{$subjectId}") ?>">Daftar PTS</a>.
      </div>
    <?php endif; ?>
  <?php endif; ?>


  <!-- === Tabel Nilai === -->
  <?php
    $showTable = true;
    if ($type === 'formatif') {
        $showTable = ($materialId && $method);
    } elseif ($type === 'pts') {
        $showTable = !empty($semester);
    }
  ?>
  <?php if ($showTable): ?>
    <div class="table-responsive">
      <table class="table table-bordered align-middle">
        <thead class="table-light">
          <tr>
            <th style="width:50px;">#</th>
            <th>Nama Siswa</th>
            <th>Agama</th>
            <th style="width:150px;">Nilai</th>
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($students)): ?>
            <?php $no = 1;
            foreach ($students as $s): ?>
              <tr>
                <td><?= $no++ ?></td>
                <td><?= esc($s['name']) ?></td>
                <td><span class="badge bg-info text-dark"><?= esc($s['religion'] ?? '-') ?></span></td>
                <td>
                  <input type="number" name="scores[<?= $s['id'] ?>]" class="form-control text-center" min="0" max="100"
                    step="1" required>
                </td>
              </tr>
            <?php endforeach ?>
          <?php else: ?>
            <tr>
              <td colspan="3" class="text-center text-muted">
                ✅ Semua siswa sudah memiliki nilai untuk kombinasi ini.<br>
                <small>Jika ada nilai dihapus, siswa tersebut akan muncul kembali di sini.</small>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>

  <div class="mt-3">
    <?php if (!empty($students)): ?>
      <button type="submit" class="btn btn-success">💾 Simpan Nilai</button>
    <?php endif; ?>
    <?php if ($type === 'formatif'): ?>
      <a href="<?= site_url("admin/assessments/formatifList/{$classId}/{$subjectId}") ?>" class="btn btn-secondary">⬅️
        Kembali</a>
    <?php elseif ($type === 'sumatif'): ?>
      <a href="<?= site_url("admin/assessments/sumatifList/{$classId}/{$subjectId}") ?>" class="btn btn-secondary">⬅️
        Kembali</a>
    <?php elseif ($type === 'pts'): ?>
      <a href="<?= site_url("admin/assessments/ptsList/{$classId}/{$subjectId}") ?>" class="btn btn-secondary">⬅️
        Kembali</a>
    <?php else: ?>
      <a href="<?= site_url("admin/assessments/finalList/{$classId}/{$subjectId}") ?>" class="btn btn-secondary">⬅️
        Kembali</a>
    <?php endif; ?>
  </div>
</form>

<?= $this->endSection() ?>