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
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
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

  .stats-card {
    background: white !important;
    border-radius: 12px !important;
    padding: 1.25rem !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
    border: 1px solid #e9ecef !important;
    margin-bottom: 1.5rem !important;
  }

  .table-card {
    background: white !important;
    border-radius: 12px !important;
    padding: 1.5rem !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
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
    padding: 0.375rem 0.75rem !important;
    font-size: 0.875rem !important;
    border-radius: 6px !important;
    transition: all 0.2s !important;
    border: none !important;
    display: inline-flex !important;
    align-items: center !important;
    gap: 0.375rem !important;
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

  .btn-danger-gradient {
    background: linear-gradient(135deg, #eb3349 0%, #f45c43 100%) !important;
    color: white !important;
  }

  .btn-success-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
    color: white !important;
  }

  .btn-info-gradient {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%) !important;
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

  .badge-modern {
    padding: 0.375rem 0.75rem !important;
    border-radius: 50px !important;
    font-weight: 500 !important;
    font-size: 0.8rem !important;
  }

  .modal-modern .modal-content {
    border: none !important;
    border-radius: 12px !important;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.15) !important;
  }

  .modal-modern .modal-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
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
          <i class="fas fa-chart-line"></i>
        </div>
        <div>
          <h4>Aktivitas User</h4>
          <p>Monitoring aktivitas siswa pada ujian ini secara real-time</p>
        </div>
      </div>
      <div class="page-header-actions">
        <button id="btnResetMassal" class="btn btn-modern btn-danger-gradient" title="Reset Semua Siswa">
          <i class="fas fa-redo-alt"></i>
          Reset Masal
        </button>
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

  <!-- Info Card -->
  <div class="info-card">
    <div class="info-card-text">
      <strong>Kode Ujian:</strong> <?= esc($test['code']) ?> —
      <strong><?= esc($test['subject_name']) ?></strong>
    </div>
    <div class="text-muted small">
      <i class="fas fa-clock"></i> Data dimuat: <?= date('Y-m-d H:i:s') ?>
    </div>
  </div>

  <!-- Stats Card -->
  <div class="stats-card d-flex gap-2">
    <div class="badge badge-modern bg-dark py-2 px-3 fs-6">
      <i class="fas fa-users me-1"></i> Total Peserta: <?= is_array($sessions) ? count($sessions) : 0 ?>
    </div>
    <button class="btn btn-modern btn-warning-gradient" id="btnBelumSelesai"
      title="Selesaikan Massal Siswa yang Belum Selesai">
      <i class="fas fa-check-double"></i>
      <span id="countBelum"><?= $count_belum ?? 0 ?></span> user belum selesai
    </button>
  </div>

  <!-- Table Card -->
  <div class="table-card">
    <div class="table-responsive">
      <table id="tableAktivitasUser" class="table table-modern">
        <thead>
          <tr>
            <th width="40" class="text-center">
              <input type="checkbox" id="checkAll" class="form-check-input">
            </th>
            <th width="30">No</th>
            <th>Nomor Ujian</th>
            <th>Nama</th>
            <th>Waktu Mulai</th>
            <th width="120">Soal Terjawab</th>
            <th width="120">Sisa Waktu</th>
            <th width="80">Nilai</th>
            <th width="100">Pelanggaran</th>
            <th width="120">Status</th>
            <th width="280">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($sessions as $i => $s): ?>
            <tr class="row-student" data-id="<?= $s['id'] ?>">
              <td class="text-center">
                <input type="checkbox" class="checkStudent form-check-input" value="<?= $s['id'] ?>">
              </td>
              <td class="text-center"><?= $i + 1 ?></td>
              <td class="text-center"><strong><?= esc($s['username']) ?></strong></td>
              <td><?= esc($s['student_name']) ?></td>
              <td class="text-center">
                <?= !empty($s['started_at']) ? date('Y-m-d H:i:s', $s['started_at']) : '<span class="text-muted small">Belum Mulai</span>' ?>
              </td>
              <td class="text-center">
                <button class="btn btn-action btn-warning-gradient btn-detail-jawaban" data-id="<?= $s['id'] ?>"
                  title="Lihat Detail Jawaban">
                  <i class="fas fa-list"></i>
                </button>
              </td>
              <td class="text-center">
                <span class="badge badge-modern bg-info"><?= $s['remaining_minutes'] ?? 0 ?> Menit</span>
              </td>
              <td class="text-center">
                <?= $s['status'] === 'finished' ? '<strong class="text-success">' . $s['display_score'] . '</strong>' : '<span class="badge badge-modern bg-secondary">Belum</span>' ?>
              </td>
              <td class="text-center">
                <?php if ($s['violation_count'] > 0): ?>
                  <span class="badge badge-modern bg-danger"><?= $s['violation_count'] ?>x</span>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <?php if ($s['status'] === 'active'): ?>
                  <span class="badge badge-modern bg-success">Sedang Ujian</span>
                <?php elseif ($s['status'] === 'finished'): ?>
                  <span class="badge badge-modern bg-primary">Selesai</span>
                <?php else: ?>
                  <span class="badge badge-modern bg-danger">Waktu Habis</span>
                <?php endif; ?>
              </td>
              <td class="text-center">
                <div class="btn-group">
                  <?php if ($s['status'] === 'active'): ?>
                    <button class="btn btn-action btn-primary-gradient btn-force-finish" data-id="<?= $s['id'] ?>"
                      title="Selesaikan Ujian">
                      <i class="fas fa-check-circle"></i>
                    </button>
                  <?php else: ?>
                    <button class="btn btn-action btn-outline-secondary" disabled title="Sudah Selesai">
                      <i class="fas fa-check"></i>
                    </button>
                  <?php endif; ?>
                  <button class="btn btn-action btn-info-gradient btn-tambah-waktu" data-id="<?= $s['id'] ?>"
                    title="Tambah Waktu">
                    <i class="fas fa-clock"></i>
                  </button>
                  <button class="btn btn-action btn-danger-gradient btn-reset" data-id="<?= $s['id'] ?>"
                    title="Reset Ujian">
                    <i class="fas fa-redo"></i>
                  </button>
                  <button class="btn btn-action btn-warning-gradient btn-reset-login bg-warning text-dark border-0"
                    data-student-id="<?= $s['student_id'] ?>" data-name="<?= esc($s['student_name']) ?>"
                    title="Buka Blokir Login (Reset Sesi Perangkat)">
                    <i class="fas fa-unlock"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal detail jawaban -->
<div class="modal fade modal-modern" id="modalDetailJawaban" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="fas fa-list-alt"></i>
          Detail Jawaban Siswa
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="detailContent" class="text-center text-muted">
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
  $(function () {
    const table = $('#tableAktivitasUser').DataTable({
      responsive: true,
      order: [[3, 'asc']], // Default sort by Nama
      pageLength: 25,
      columnDefs: [
        { orderable: false, targets: [0, 10] } // Checkbox & Action non-sortable
      ],
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

    // Check All functionality
    $('#checkAll').on('change', function () {
      $('.checkStudent').prop('checked', $(this).is(':checked'));
      updateResetButton();
    });

    $(document).on('change', '.checkStudent', function () {
      updateResetButton();
    });

    function updateResetButton() {
      const selected = $('.checkStudent:checked').length;
      if (selected > 0) {
        $('#btnResetMassal').html('<i class="fas fa-redo-alt"></i> Reset Terpilih (' + selected + ')');
        $('#btnResetMassal').removeClass('btn-danger-gradient').addClass('btn-warning-gradient');
      } else {
        $('#btnResetMassal').html('<i class="fas fa-redo-alt"></i> Reset Masal');
        $('#btnResetMassal').removeClass('btn-warning-gradient').addClass('btn-danger-gradient');
      }
    }

    // Detail jawaban
    $(document).on('click', '.btn-detail-jawaban', function () {
      const id = $(this).data('id');
      $('#detailContent').html('<i class="fas fa-spinner fa-spin fa-2x mb-3"></i><p>Memuat data...</p>');
      $('#modalDetailJawaban').modal('show');
      $.get("<?= site_url('admin/cbt/aktivitas/detail_jawaban/') ?>" + id, function (html) {
        $('#detailContent').html(html);
      }).fail(() => $('#detailContent').html('<div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Gagal memuat data</div>'));
    });

    // Helper for CSRF
    function getCsrfData() {
      const name = "<?= csrf_token() ?>";
      const value = $("meta[name='" + name + "']").attr("content") || "<?= csrf_hash() ?>";
      console.log('CSRF Token:', name, '=', value);
      return { [name]: value };
    }

    // Paksa selesai per siswa
    $(document).on('click', '.btn-force-finish', function () {
      const id = $(this).data('id');
      const btn = $(this);

      Swal.fire({
        title: 'Paksa Selesaikan?',
        text: 'Ujian siswa ini akan diselesaikan secara paksa dan nilai akan dihitung otomatis.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Selesaikan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#667eea'
      }).then((r) => {
        if (!r.isConfirmed) return;

        // Disable button to prevent double click
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

        const payload = getCsrfData();
        $.post("<?= site_url('admin/cbt/aktivitas/forceFinish/') ?>" + id, payload)
          .done(function (res) {
            Swal.fire({
              title: 'Berhasil!',
              html: 'Ujian telah diselesaikan.<br>Nilai: <strong>' + (res.score || 0) + '</strong>',
              icon: 'success',
              confirmButtonColor: '#667eea'
            }).then(() => location.reload());
          })
          .fail(function (xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-check-circle"></i>');
            const msg = xhr.responseJSON?.error || 'Gagal tersambung ke server';
            Swal.fire({
              title: 'Gagal',
              text: msg,
              icon: 'error',
              confirmButtonColor: '#dc3545'
            });
          });
      });
    });

    // Tambah waktu
    $(document).on('click', '.btn-tambah-waktu', function () {
      const id = $(this).data('id');
      Swal.fire({
        title: 'Tambah Waktu',
        input: 'number',
        inputLabel: 'Berapa menit tambahan?',
        inputPlaceholder: 'Masukkan jumlah menit',
        showCancelButton: true,
        confirmButtonText: 'Tambah',
        cancelButtonText: 'Batal',
        inputValidator: (value) => {
          if (!value || value <= 0) {
            return 'Masukkan jumlah menit yang valid!';
          }
        }
      }).then(res => {
        if (!res.isConfirmed || !res.value) return;

        const payload = getCsrfData();
        payload.minutes = res.value;

        $.post("<?= site_url('admin/cbt/aktivitas/addTime') ?>/" + id, payload)
          .done(function () {
            Swal.fire('Berhasil', 'Waktu ditambahkan', 'success').then(() => location.reload());
          })
          .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal tersambung ke server';
            Swal.fire('Gagal', msg, 'error');
          });
      });
    });

    // Reset Sesi Login / Buka Blokir
    $(document).on('click', '.btn-reset-login', function () {
      const studentId = $(this).data('student-id');
      const studentName = $(this).data('name');

      Swal.fire({
        title: 'Buka Blokir Login?',
        html: `Apakah Anda yakin ingin mereset sesi login untuk <b>${studentName}</b>?<br><small class='text-muted'>Ini memungkinkan siswa untuk langsung login browser/perangkat lain tanpa harus menunggu 3 menit. Jawaban ujian tidak akan dihapus.</small>`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#ffc107',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Reset Login!',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          $.post(`<?= site_url('admin/cbt/aktivitas/resetLogin') ?>/${studentId}`, getCsrfData())
            .done((res) => Swal.fire('Berhasil!', res.message, 'success'))
            .fail((xhr) => Swal.fire('Gagal!', xhr.responseJSON?.error || 'Terjadi kesalahan sistem', 'error'));
        }
      });
    });

    // Reset ujian
    $(document).on('click', '.btn-reset', function () {
      const id = $(this).data('id');
      Swal.fire({
        title: 'Reset Sesi Ujian?',
        text: 'Siswa harus mengulang ujian dari awal.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reset',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545'
      }).then((r) => {
        if (!r.isConfirmed) return;

        const payload = getCsrfData();
        $.post("<?= site_url('admin/cbt/aktivitas/resetSession') ?>/" + id, payload)
          .done(function () {
            Swal.fire('Sesi Direset', 'Siswa dapat mengulang ujian', 'success').then(() => location.reload());
          })
          .fail(function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal tersambung ke server';
            Swal.fire('Gagal', msg, 'error');
          });
      });
    });

    // Reset Masal
    $(document).on('click', '#btnResetMassal', function () {
      const selectedIds = $('.checkStudent:checked').map(function () {
        return $(this).val();
      }).get();

      let title = 'Reset SELURUH Sesi?';
      let text = 'SEMUA siswa dalam ujian ini akan direset. Jawaban yang sudah ada akan DIHAPUS PERMANEN. Tindakan ini tidak dapat dibatalkan!';

      if (selectedIds.length > 0) {
        title = 'Reset ' + selectedIds.length + ' Sesi Terpilih?';
        text = 'Siswa yang dipilih akan direset dari awal. Jawaban mereka akan DIHAPUS PERMANEN.';
      }

      Swal.fire({
        title: title,
        text: text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Reset!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#dc3545'
      }).then((r) => {
        if (!r.isConfirmed) return;

        const payload = getCsrfData();
        if (selectedIds.length > 0) {
          // 🔹 FIX: Send array properly using traditional: true
          payload['session_ids[]'] = selectedIds;
        }

        $.ajax({
          url: "<?= site_url('admin/cbt/aktivitas/resetMassal/' . $test['id']) ?>",
          type: 'POST',
          data: payload,
          traditional: true, // 🔹 This ensures arrays are sent as session_ids[]=1&session_ids[]=2
          success: function (res) {
            Swal.fire('Berhasil', res.message, 'success').then(() => location.reload());
          },
          error: function (xhr) {
            const msg = xhr.responseJSON?.error || 'Gagal tersambung ke server';
            Swal.fire('Gagal', msg, 'error');
          }
        });
      });
    });

    // Selesaikan Massal (Belum Selesai)
    $('#btnBelumSelesai').on('click', function () {
      const count = parseInt($('#countBelum').text());
      if (count <= 0) {
        Swal.fire({
          title: 'Info',
          text: 'Tidak ada siswa yang sedang aktif ujian.',
          icon: 'info',
          confirmButtonColor: '#667eea'
        });
        return;
      }

      const btn = $(this);

      Swal.fire({
        title: 'Selesaikan Massal?',
        html: 'Seluruh siswa (<strong>' + count + '</strong>) yang sedang aktif ujian akan dipaksa selesai.<br><br>Nilai akan dihitung otomatis berdasarkan jawaban yang sudah tersimpan.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Selesaikan Semua!',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#f5576c'
      }).then((r) => {
        if (!r.isConfirmed) return;

        // Disable button and show loading
        btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Memproses...');

        const payload = getCsrfData();
        $.post("<?= site_url('admin/cbt/aktivitas/forceFinishMassal/' . $test['id']) ?>", payload)
          .done(function (res) {
            let message = res.message || 'Berhasil menyelesaikan ujian siswa.';
            if (res.errors && res.errors.length > 0) {
              message += '<br><br><small class="text-danger">Beberapa error:<br>' + res.errors.join('<br>') + '</small>';
            }

            Swal.fire({
              title: 'Berhasil!',
              html: message,
              icon: 'success',
              confirmButtonColor: '#667eea'
            }).then(() => location.reload());
          })
          .fail(function (xhr) {
            btn.prop('disabled', false).html('<i class="fas fa-check-double"></i> <span id="countBelum">' + count + '</span> user belum selesai');
            const msg = xhr.responseJSON?.error || 'Gagal tersambung ke server';
            Swal.fire({
              title: 'Gagal',
              text: msg,
              icon: 'error',
              confirmButtonColor: '#dc3545'
            });
          });
      });
    });
  });
</script>
<?= $this->endSection() ?>