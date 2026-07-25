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
  
  .table-card {
    background: white !important;
    border-radius: 12px !important;
    padding: 1.5rem !important;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08) !important;
    border: 1px solid #e9ecef !important;
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
  
  .btn-action {
    padding: 0.5rem 1rem !important;
    font-size: 0.875rem !important;
    border-radius: 6px !important;
    transition: all 0.2s !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
    white-space: nowrap !important;
  }
  
  .btn-action:hover {
    transform: translateY(-1px) !important;
  }
  
  .btn-primary-gradient {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
    color: white !important;
  }
  
  .btn-warning-gradient {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    color: white !important;
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
  
  .modal-modern .modal-content {
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 12px 32px rgba(0,0,0,0.15) !important;
  }
  
  .modal-modern .modal-header {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%) !important;
    color: white !important;
    border-radius: 12px 12px 0 0 !important;
    padding: 1.25rem !important;
  }
  
  .modal-modern .modal-title {
    font-weight: 600 !important;
    display: flex !important;
    align-items: center !important;
    gap: 0.5rem !important;
  }
  
  .modal-modern .btn-close {
    filter: brightness(0) invert(1) !important;
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
          <i class="fas fa-file-alt"></i>
        </div>
        <div>
          <h4>Laporan Jawaban Siswa</h4>
          <p>Input nilai esai dan cetak laporan hasil ujian siswa</p>
        </div>
      </div>
      <div class="page-header-actions">
        <button onclick="location.reload()" class="btn btn-modern btn-header" title="Refresh">
          <i class="fas fa-sync-alt"></i>
          Refresh
        </button>
        <a href="<?= site_url('admin/cbt/aktivitas') ?>" class="btn btn-modern btn-header">
          <i class="fas fa-arrow-left"></i>
          Kembali
        </a>
      </div>
    </div>
  </div>

  <!-- Table Card -->
  <div class="table-card">
    <div class="table-responsive">
      <table id="tableLaporan" class="table table-modern">
        <thead>
          <tr>
            <th width="50">No</th>
            <th width="120">No Ujian</th>
            <th>Nama Siswa</th>
            <th width="180" class="text-center">Nilai Esai</th>
            <th width="200" class="text-center">Laporan</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1; foreach ($sessions as $s): ?>
          <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td class="text-center"><strong><?= esc($s['exam_number'] ?? '-') ?></strong></td>
            <td><?= esc($s['student_name'] ?? '-') ?></td>
            <td class="text-center">
              <button 
                class="btn btn-action btn-warning-gradient btn-nilai-esai"
                data-test="<?= $test['id'] ?>"
                data-student="<?= $s['student_id'] ?>"
                title="Berikan Nilai Esai">
                <i class="fas fa-edit"></i>
                Nilai Esai
              </button>
            </td>
            <td class="text-center">
              <a href="<?= site_url('admin/cbt/aktivitas/laporan_pdf/'.$test['id'].'/'.$s['student_id']) ?>"
                 class="btn btn-action btn-primary-gradient" 
                 target="_blank"
                 title="Cetak Laporan PDF">
                <i class="fas fa-print"></i>
                Cetak Laporan
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Nilai Esai -->
<div class="modal fade modal-modern" id="modalNilaiEsai" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-edit"></i>
          Penilaian Esai
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="nilaiEsaiContent">
        <div class="text-center text-muted">
          <i class="fas fa-spinner fa-spin fa-2x mb-3"></i>
          <p>Memuat data...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
  // Initialize DataTable
  $('#tableLaporan').DataTable({
    responsive: true,
    order: [],
    pageLength: 25,
    language: {
      search: "Cari:",
      lengthMenu: "Tampilkan _MENU_ data",
      zeroRecords: "Tidak ada data ditemukan",
      info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
      paginate: {
        first: "Pertama",
        last: "Terakhir",
        next: "Selanjutnya",
        previous: "Sebelumnya"
      }
    }
  });

  // Klik tombol nilai esai
  $(document).on('click', '.btn-nilai-esai', function() {
    const testId = $(this).data('test');
    const studentId = $(this).data('student');

    $('#modalNilaiEsai').modal('show');
    $('#nilaiEsaiContent').html('<div class="text-center text-muted"><i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Memuat data...</p></div>');

    $.get('<?= site_url('admin/cbt/aktivitas/getSoalEsai/') ?>' + testId + '/' + studentId)
      .done(function(html) {
        $('#nilaiEsaiContent').html(html);
      })
      .fail(function() {
        $('#nilaiEsaiContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data.</div>');
      });
  });

  // Submit nilai esai
  $(document).on('submit', '#formNilaiEsai', function(e){
    e.preventDefault();
    
    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Menyimpan...');
    
    $.post('<?= site_url('admin/cbt/aktivitas/simpanNilaiEsaiDetail') ?>', $(this).serialize(), function(res){
      if(res.status === 'success'){
        Swal.fire({
          icon: 'success',
          title: 'Berhasil!',
          html: `
            <div class="text-start">
              <p><strong>Nilai rata-rata esai:</strong> ${res.nilai.rata_esai}</p>
              <p><strong>Bobot:</strong> ${res.nilai.bobot}%</p>
              <p><strong>Nilai akhir esai:</strong> ${res.nilai.essay_score}</p>
              <p><strong>Nilai PG:</strong> ${res.nilai.pg}</p>
              <p><strong>Total nilai:</strong> <span class="badge bg-success">${res.nilai.total}</span></p>
            </div>
          `,
          confirmButtonText: 'OK'
        }).then(() => {
          $('#modalNilaiEsai').modal('hide');
          location.reload();
        });
      } else {
        Swal.fire({
          icon: 'error',
          title: 'Gagal!',
          text: res.message,
          confirmButtonText: 'OK'
        });
        submitBtn.prop('disabled', false).html(originalText);
      }
    }).fail(function(){
      Swal.fire({
        icon: 'error',
        title: 'Gagal!',
        text: 'Gagal menyimpan nilai esai.',
        confirmButtonText: 'OK'
      });
      submitBtn.prop('disabled', false).html(originalText);
    });
  });
});
</script>
<?= $this->endSection() ?>
