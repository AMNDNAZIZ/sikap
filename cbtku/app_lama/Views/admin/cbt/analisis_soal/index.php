<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .page-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
    padding: 2rem !important;
    border-radius: 15px !important;
    margin-bottom: 1.5rem !important;
    box-shadow: 0 8px 16px rgba(102, 126, 234, 0.2) !important;
  }
  
  .page-header-content {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
  }
  
  .page-header-left {
    display: flex !important;
    align-items: center !important;
    gap: 1rem !important;
  }
  
  .page-header .icon-wrapper {
    width: 56px !important;
    height: 56px !important;
    background: rgba(255, 255, 255, 0.2) !important;
    border-radius: 12px !important;
    display: flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1.75rem !important;
  }
  
  .page-header h4 {
    margin: 0 0 0.25rem 0 !important;
    font-weight: 600 !important;
    font-size: 1.5rem !important;
  }
  
  .page-header p {
    margin: 0 !important;
    opacity: 0.9 !important;
    font-size: 0.95rem !important;
  }
  
  .page-header-actions {
    display: flex !important;
    gap: 0.5rem !important;
  }
  
  .info-card {
    background: white !important;
    border-radius: 12px !important;
    padding: 1.25rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    border: 1px solid #e9ecef !important;
    margin-bottom: 1.5rem !important;
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
  }
  
  .info-card-text {
    font-size: 1rem !important;
    color: #495057 !important;
  }
  
  .info-card-text strong {
    color: #212529 !important;
  }
  
  .table-card {
    background: white !important;
    border-radius: 12px !important;
    padding: 1.5rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    border: 1px solid #e9ecef !important;
    margin-bottom: 1.5rem !important;
  }
  
  .table-card-header {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
    padding: 1rem 1.5rem !important;
    border-radius: 12px 12px 0 0 !important;
    margin: -1.5rem -1.5rem 1.5rem -1.5rem !important;
    font-weight: 600 !important;
    font-size: 1.1rem !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
  }
  
  .table-card-header.info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
  }
  
  .btn-modern {
    padding: 0.5rem 1rem !important;
    border-radius: 8px !important;
    font-weight: 500 !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    transition: all 0.2s !important;
    font-size: 0.9rem !important;
  }
  
  .btn-modern:hover {
    transform: translateY(-1px) !important;
  }
  
  .btn-header {
    background: rgba(255, 255, 255, 0.2) !important;
    color: white !important;
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
  }
  
  .btn-header:hover {
    background: rgba(255, 255, 255, 0.3) !important;
    color: white !important;
  }
  
  .table-modern {
    margin-bottom: 0 !important;
  }
  
  .table-modern thead th {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%) !important;
    border-bottom: 2px solid #dee2e6 !important;
    font-weight: 600 !important;
    font-size: 0.85rem !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    color: #495057 !important;
    padding: 0.875rem !important;
  }
  
  .table-modern tbody tr {
    transition: background-color 0.2s ease !important;
  }
  
  .table-modern tbody tr:hover {
    background-color: #f8f9fa !important;
  }
  
  .table-modern tbody td {
    padding: 0.875rem !important;
    vertical-align: middle !important;
    border-bottom: 1px solid #f1f3f5 !important;
  }
  
  .soal-text {
    line-height: 1.6 !important;
    font-size: 0.95rem !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
    overflow-wrap: break-word !important;
    white-space: normal !important;
    max-width: 100% !important;
  }

  .soal-text img {
    display: block !important;
    margin: 10px 0 !important;
    max-width: 100% !important;
    height: auto !important;
    border-radius: 4px !important;
  }

  .soal-text p {
    margin-bottom: 0.5rem !important;
    word-wrap: break-word !important;
    word-break: break-word !important;
  }
  
  .badge-modern {
    padding: 0.5rem 1rem !important;
    border-radius: 50px !important;
    font-weight: 500 !important;
    font-size: 0.85rem !important;
  }
  
  @media (max-width: 768px) {
    .page-header-content {
      flex-direction: column !important;
      gap: 1rem !important;
    }
    
    .page-header-actions {
      width: 100% !important;
      justify-content: stretch !important;
    }
    
    .btn-modern {
      flex: 1 !important;
      justify-content: center !important;
    }
  }
</style>

<div class="container-fluid px-4 py-4">
  <!-- Page Header -->
  <div class="page-header">
    <div class="page-header-content">
      <div class="page-header-left">
        <div class="icon-wrapper">
          <i class="fas fa-chart-bar"></i>
        </div>
        <div>
          <h4>Analisis Soal</h4>
          <p>Tingkat kesulitan soal berdasarkan hasil jawaban siswa</p>
        </div>
      </div>
      <div class="page-header-actions">
        <a href="<?= site_url('admin/cbt/aktivitas/analisis/download/' . $test['id']) ?>" 
           class="btn btn-modern btn-header"
           title="Download Analisis">
          <i class="fas fa-download"></i>
          Download
        </a>
        <a href="<?= site_url('admin/cbt/aktivitas') ?>" class="btn btn-modern btn-header">
          <i class="fas fa-arrow-left"></i>
          Kembali
        </a>
      </div>
    </div>
  </div>

  <!-- Info Card -->
  <div class="info-card">
    <div class="info-card-text">
      <strong><?= esc($test['subject_name']) ?></strong> — 
      <?= esc($test['exam_name']) ?> 
      [<strong><?= esc($test['bank_code']) ?></strong>]
    </div>
  </div>

  <!-- Tabel Pilihan Ganda -->
  <div class="table-card">
    <div class="table-card-header">
      <i class="fas fa-list-ul"></i>
      Analisis Soal Pilihan Ganda & PG Kompleks
    </div>
    <div class="table-responsive">
      <table id="tableAnalisisPg" class="table table-modern">
        <thead>
          <tr>
            <th width="50">No</th>
            <th>Soal</th>
            <th width="100">Partisipan</th>
            <th width="100">Benar</th>
            <th width="120">Analisis</th>
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
                <td class="text-center"><strong><?= $d['total'] ?></strong></td>
                <td class="text-center"><strong><?= $d['benar'] ?></strong></td>
                <td class="text-center">
                  <?php if ($d['analisis'] === 'Mudah'): ?>
                    <span class="badge badge-modern bg-success">Mudah</span>
                  <?php elseif ($d['analisis'] === 'Sedang'): ?>
                    <span class="badge badge-modern bg-warning text-dark">Sedang</span>
                  <?php elseif ($d['analisis'] === 'Susah'): ?>
                    <span class="badge badge-modern bg-danger">Susah</span>
                  <?php else: ?>
                    <span class="badge badge-modern bg-secondary">-</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endif; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- Tabel Esai -->
  <div class="table-card">
    <div class="table-card-header info">
      <i class="fas fa-pen"></i>
      Daftar Soal Esai
    </div>
    <div class="table-responsive">
      <table id="tableAnalisisEsai" class="table table-modern">
        <thead>
          <tr>
            <th width="50">No</th>
            <th>Soal</th>
            <th width="150">Jenis</th>
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
                  <span class="badge badge-modern bg-info text-dark">Esai</span>
                </td>
              </tr>
            <?php endif; endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $(function () {
    $('#tableAnalisisPg, #tableAnalisisEsai').DataTable({
      responsive: true,
      ordering: false,
      pageLength: 25,
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data",
        zeroRecords: "Tidak ada data ditemukan",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data tersedia",
        paginate: {
          first: "Pertama",
          last: "Terakhir",
          next: "Selanjutnya",
          previous: "Sebelumnya"
        }
      }
    });
  });
</script>
<?= $this->endSection() ?>
