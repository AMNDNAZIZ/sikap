<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="card shadow-sm mb-3">
    <div class="card-body d-flex align-items-center">
      <div class="me-3">
        <div class="icon bg-light rounded-circle p-3">
          <i class="bi bi-bar-chart-line fs-3 text-success"></i>
        </div>
      </div>
      <div>
        <h5 class="mb-1">Analisis Soal</h5>
        <p class="text-muted mb-0">
          Halaman ini menampilkan tingkat kesulitan soal berdasarkan hasil jawaban siswa.
          <br>Soal esai ditampilkan di bagian akhir dan tidak dianalisis otomatis.
        </p>
      </div>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
      <strong><?= esc($test['subject_name']) ?> — <?= esc($test['exam_name']) ?>
        [<?= esc($test['bank_code']) ?>]</strong>
      <div>
        <a href="<?= site_url('admin/cbt/aktivitas') ?>" class="btn btn-success btn-sm me-2">
          <i class="bi bi-arrow-left"></i>
        </a>
        <a href="<?= site_url('admin/cbt/aktivitas/analisis/download/' . $test['id']) ?>"
          class="btn btn-primary btn-sm">
          <i class="bi bi-download"></i>
        </a>
      </div>
    </div>

    <!-- =========================
       TABEL PILIHAN GANDA (PG)
  ========================== -->
    <div class="card shadow-sm mb-4">
      <div class="card-header bg-success text-white">
        <strong>Analisis Soal Pilihan Ganda & PG Kompleks</strong>
      </div>
      <div class="card-body table-responsive">
        <table id="tableAnalisisPg" class="table table-bordered align-middle w-100">
          <thead class="table-success text-center">
            <tr>
              <th width="5%">No</th>
              <th>Soal</th>
              <th width="10%">Partisipan</th>
              <th width="10%">Benar</th>
              <th width="15%">Analisis</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            foreach ($data as $d):
              if (in_array($d['type'], ['pg', 'pilihan_ganda', 'pg_kompleks', 'benar_salah'])): ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td>
                    <div class="soal-text">
                      <?= $d['question'] ?>
                    </div>
                  </td>
                  <td class="text-center"><?= $d['total'] ?></td>
                  <td class="text-center"><?= $d['benar'] ?></td>
                  <td class="text-center">
                    <?php if ($d['analisis'] === 'Mudah'): ?>
                      <span class="badge bg-success px-3 py-2">Mudah</span>
                    <?php elseif ($d['analisis'] === 'Sedang'): ?>
                      <span class="badge bg-warning text-dark px-3 py-2">Sedang</span>
                    <?php elseif ($d['analisis'] === 'Susah'): ?>
                      <span class="badge bg-danger px-3 py-2">Susah</span>
                    <?php else: ?>
                      <span class="badge bg-secondary px-3 py-2">-</span>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endif; endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- =========================
       TABEL ESAI
  ========================== -->
    <div class="card shadow-sm">
      <div class="card-header bg-info text-dark">
        <strong>Daftar Soal Esai</strong>
      </div>
      <div class="card-body table-responsive">
        <table id="tableAnalisisEsai" class="table table-bordered align-middle w-100">
          <thead class="table-info text-center">
            <tr>
              <th width="5%">No</th>
              <th>Soal</th>
              <th width="20%">Jenis</th>
            </tr>
          </thead>
          <tbody>
            <?php
            $no = 1;
            foreach ($data as $d):
              if (in_array($d['type'], ['esai', 'essay'])): ?>
                <tr>
                  <td class="text-center"><?= $no++ ?></td>
                  <td>
                    <div class="soal-text">
                      <?= $d['question'] ?>
                    </div>
                  </td>
                  <td class="text-center">
                    <span class="badge bg-info text-dark px-3 py-2">Esai</span>
                  </td>
                </tr>
              <?php endif; endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<style>
  .soal-text {
    white-space: pre-line;
    line-height: 1.6;
    font-size: 0.95rem;
  }

  .soal-text img {
    display: block;
    margin: 10px 0;
    max-width: 100%;
    height: auto;
    border-radius: 4px;
  }

  .soal-text p {
    margin-bottom: 0;
  }
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $(function () {
    $('#tableAnalisisPg, #tableAnalisisEsai').DataTable({
      responsive: true,
      ordering: false,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data per halaman",
        zeroRecords: "Tidak ada data ditemukan",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data tersedia"
      }
    });
  });
</script>
<?= $this->endSection() ?>