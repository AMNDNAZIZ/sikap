<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .cbt-header-responsive {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 1.25rem;
  }

  .cbt-header-actions {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
  }

  .btn-group-bank {
    display: inline-flex;
    flex-wrap: nowrap;
  }

  @media (max-width: 768px) {
    .container-fluid {
      padding-left: 10px !important;
      padding-right: 10px !important;
    }

    .cbt-header-responsive {
      flex-direction: column;
      align-items: stretch;
      gap: 10px;
    }

    .cbt-header-actions {
      flex-direction: column;
      width: 100%;
    }

    .cbt-header-actions .btn {
      width: 100%;
    }

    .btn-group-bank {
      display: flex !important;
      flex-wrap: wrap !important;
      gap: 3px !important;
    }

    .btn-group-bank .btn {
      border-radius: 6px !important;
      min-width: 34px !important;
      height: 34px !important;
      padding: 0 !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    .table-responsive {
      border-radius: 12px;
      -webkit-overflow-scrolling: touch;
    }

    #datatable {
      font-size: 0.84rem;
    }

    #datatable th, #datatable td {
      padding: 8px 10px;
      white-space: nowrap;
    }

    .card-footer button {
      width: 100%;
    }

    .modal-dialog {
      margin: 0.5rem;
      max-width: calc(100% - 1rem) !important;
    }
  }

  /* Styling Khusus Kartu Mobile */
  .bank-card-mobile {
    background: #ffffff;
    border-radius: 14px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0,0,0,0.04);
    padding: 1rem;
    margin-bottom: 0.85rem;
    transition: all 0.2s ease;
  }
  .bank-card-mobile:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.08);
  }
  .bank-btn-action {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 0.5rem 0.25rem;
    border-radius: 8px;
    font-size: 0.68rem;
    font-weight: 500;
    gap: 0.2rem;
    min-height: 48px;
  }
</style>

<div class="container-fluid py-2">
  <div class="cbt-header-responsive">
    <div>
      <h4 class="mb-0 fw-bold"><i class="bi bi-folder2-open text-primary me-1"></i> Daftar Bank Soal</h4>
      <small class="text-muted">Kelola bank soal untuk ujian CBT — buat, edit, dan atur butir soal.</small>
    </div>
    <div class="cbt-header-actions">
      <button class="btn btn-primary rounded-pill px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalRestoreBank">
        <i class="bi bi-upload me-1"></i> Restore Bank Soal
      </button>
      <button class="btn btn-success rounded-pill px-3 py-2 fw-semibold shadow-sm" data-bs-toggle="modal" data-bs-target="#modalAddBank">
        <i class="bi bi-plus-circle me-1"></i> Tambah Bank Soal
      </button>
    </div>
  </div>

  <form method="post" action="<?= site_url('admin/cbt/banksoal/bulkDelete') ?>" id="formBulkBank">
    <?= csrf_field() ?>

    <!-- 📱 TAMPILAN MOBILE: KARTU BANK SOAL (Hanya tampil di HP / Layar < 768px) -->
    <div class="d-block d-md-none mb-3">
      <!-- Search & Check All Toolbar Mobile -->
      <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 12px;">
        <div class="input-group mb-2">
          <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
          <input type="text" id="searchBankMobile" class="form-control border-start-0" placeholder="Cari kode bank atau mapel...">
        </div>
        <div class="d-flex justify-content-between align-items-center pt-1">
          <div class="form-check mb-0">
            <input type="checkbox" id="checkAllMobile" class="form-check-input" style="width: 18px; height: 18px;">
            <label class="form-check-label small fw-semibold ms-1" for="checkAllMobile">Pilih Semua</label>
          </div>
          <span class="badge bg-primary bg-opacity-10 text-primary px-2 py-1 rounded-pill small">
            Total: <?= count($banks) ?> Bank
          </span>
        </div>
      </div>

      <div id="mobileBankCardsContainer">
        <?php foreach ($banks as $i => $bank): ?>
          <div class="bank-card-mobile bank-card-item" data-search="<?= strtolower(esc($bank['code'] . ' ' . $bank['subject_name'] . ' ' . ($bank['creator_name'] ?? ''))) ?>">
            <!-- Header Kartu: Checkbox, Kode, Status -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="d-flex align-items-center gap-2">
                <input type="checkbox" name="ids[]" value="<?= $bank['id'] ?>" class="form-check-input bank-checkbox-item" style="width: 20px; height: 20px;">
                <span class="badge bg-primary bg-opacity-10 text-primary font-monospace px-2 py-1 border border-primary border-opacity-25" style="font-size: 0.88rem;">
                  <?= esc($bank['code']) ?>
                </span>
              </div>
              <div>
                <?php if ($bank['is_active']): ?>
                  <a href="<?= site_url('admin/cbt/banksoal/toggle/' . $bank['id']) ?>"
                    class="badge bg-success text-decoration-none py-1 px-2 rounded-pill small">
                    <i class="bi bi-check-circle me-1"></i> Aktif
                  </a>
                <?php else: ?>
                  <a href="<?= site_url('admin/cbt/banksoal/toggle/' . $bank['id']) ?>"
                    class="badge bg-secondary text-decoration-none py-1 px-2 rounded-pill small">
                    <i class="bi bi-dash-circle me-1"></i> Nonaktif
                  </a>
                <?php endif; ?>
              </div>
            </div>

            <!-- Subject & Creator -->
            <h6 class="fw-bold mb-1 text-dark"><?= esc($bank['subject_name']) ?></h6>
            <div class="text-muted small mb-2 d-flex align-items-center gap-1">
              <i class="bi bi-person text-secondary"></i>
              <span>Guru: <strong><?= esc($bank['creator_name'] ?? 'Admin') ?></strong></span>
            </div>

            <!-- Stats Badges -->
            <div class="d-flex flex-wrap gap-1 mb-3">
              <span class="badge bg-primary px-2 py-1"><i class="bi bi-file-earmark-text me-1"></i><?= esc($bank['total_questions']) ?> Soal</span>
              <span class="badge bg-light text-dark border px-2 py-1"><?= esc($bank['option_count']) ?> Opsi</span>
              <span class="badge bg-light text-dark border px-2 py-1"><?= esc($bank['total_pg']) ?> PG</span>
              <?php if (!empty($bank['total_pg_kompleks'])): ?>
                <span class="badge bg-light text-dark border px-2 py-1"><?= esc($bank['total_pg_kompleks']) ?> PGK</span>
              <?php endif; ?>
              <?php if (!empty($bank['total_bs'])): ?>
                <span class="badge bg-light text-dark border px-2 py-1"><?= esc($bank['total_bs']) ?> BS</span>
              <?php endif; ?>
              <?php if (!empty($bank['total_esai'])): ?>
                <span class="badge bg-light text-dark border px-2 py-1"><?= esc($bank['total_esai']) ?> Esai</span>
              <?php endif; ?>
            </div>

            <!-- Action Utama: Kelola Soal -->
            <a href="<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>" class="btn btn-primary w-100 py-2 fw-semibold mb-2 shadow-sm d-flex align-items-center justify-content-center gap-2" style="border-radius: 10px;">
              <i class="bi bi-list-check fs-5"></i> Buka &amp; Kelola Soal
            </a>

            <!-- Grid 5 Aksi Cepat (Mobile Touch Targets) -->
            <div class="row g-1">
              <div class="col">
                <button type="button" class="btn btn-outline-warning w-100 bank-btn-action btn-edit-bank"
                  data-id="<?= $bank['id'] ?>" data-code="<?= esc($bank['code']) ?>"
                  data-subject="<?= esc($bank['subject_id']) ?>" data-option="<?= esc($bank['option_count']) ?>"
                  title="Edit Bank Soal">
                  <i class="bi bi-pencil fs-6"></i>
                  <span>Edit</span>
                </button>
              </div>
              <div class="col">
                <a href="<?= site_url('admin/cbt/banksoal/copy/' . $bank['id']) ?>" class="btn btn-outline-primary w-100 bank-btn-action" title="Salin Bank Soal">
                  <i class="bi bi-files fs-6"></i>
                  <span>Salin</span>
                </a>
              </div>
              <div class="col">
                <a href="<?= site_url('admin/cbt/banksoal/print/' . $bank['id']) ?>" class="btn btn-outline-secondary w-100 bank-btn-action" title="Cetak PDF">
                  <i class="bi bi-printer fs-6"></i>
                  <span>Cetak</span>
                </a>
              </div>
              <div class="col">
                <a href="<?= site_url('admin/cbt/banksoal/backup/' . $bank['id']) ?>" class="btn btn-outline-dark w-100 bank-btn-action" title="Backup Bank Soal">
                  <i class="bi bi-download fs-6"></i>
                  <span>Backup</span>
                </a>
              </div>
              <div class="col">
                <a href="<?= site_url('admin/cbt/banksoal/delete/' . $bank['id']) ?>" class="btn btn-outline-danger w-100 bank-btn-action"
                  onclick="return confirm('Hapus bank soal ini beserta semua soalnya?')" title="Hapus Bank Soal">
                  <i class="bi bi-trash fs-6"></i>
                  <span>Hapus</span>
                </a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
        <?php if (empty($banks)): ?>
          <div class="text-center py-5 text-muted bg-white rounded-3 border">
            <i class="bi bi-folder-x fs-1 text-secondary mb-2 d-block"></i>
            <h6>Belum ada data bank soal</h6>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- 💻 TAMPILAN DESKTOP: TABEL DATA TABLE (Hanya tampil di layar >= 768px) -->
    <div class="card shadow-sm border-0 rounded-3 d-none d-md-block">
      <div class="card-body table-responsive p-2 p-md-3">
        <table id="datatable" class="table table-striped align-middle w-100 mb-0">
          <thead class="table-light">
            <tr>
              <th><input type="checkbox" id="checkAll"></th>
              <th>No</th>
              <th>Kode Bank Soal</th>
              <th>Mapel</th>
              <th>Jumlah Soal</th>
              <th>Pembuat</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($banks as $i => $bank): ?>
              <tr>
                <td><input type="checkbox" name="ids[]" value="<?= $bank['id'] ?>" class="bank-checkbox-item"></td>
                <td><?= $i + 1 ?></td>
                <td><strong><?= esc($bank['code']) ?></strong></td>
                <td><?= esc($bank['subject_name']) ?></td>
                <td>
                  <?= esc($bank['total_questions']) ?> soal
                  (<?= esc($bank['option_count']) ?> opsi |
                  <?= esc($bank['total_pg']) ?> PG |
                  <?= esc($bank['total_pg_kompleks']) ?> PGK |
                  <?= esc($bank['total_bs']) ?> BS |
                  <?= esc($bank['total_esai']) ?> Esai)
                </td>
                <td><?= esc($bank['creator_name'] ?? '-') ?></td>
                <td>
                  <?php if ($bank['is_active']): ?>
                    <a href="<?= site_url('admin/cbt/banksoal/toggle/' . $bank['id']) ?>"
                      class="btn btn-success btn-sm">Aktif</a>
                  <?php else: ?>
                    <a href="<?= site_url('admin/cbt/banksoal/toggle/' . $bank['id']) ?>"
                      class="btn btn-outline-secondary btn-sm">Nonaktif</a>
                  <?php endif; ?>
                </td>
                <td>
                  <div class="btn-group btn-group-bank">
                    <button type="button" class="btn btn-outline-warning btn-sm btn-edit-bank"
                      data-id="<?= $bank['id'] ?>" data-code="<?= esc($bank['code']) ?>"
                      data-subject="<?= esc($bank['subject_id']) ?>" data-option="<?= esc($bank['option_count']) ?>"
                      title="Edit Bank Soal">
                      <i class="bi bi-pencil"></i>
                    </button>

                    <a href="<?= site_url('admin/cbt/banksoal/copy/' . $bank['id']) ?>"
                      class="btn btn-outline-primary btn-sm" title="Salin Bank Soal"><i class="bi bi-files"></i></a>
                    <a href="<?= site_url('admin/cbt/banksoal/detail/' . $bank['id']) ?>"
                      class="btn btn-outline-info btn-sm" title="Rincian Bank Soal"><i class="bi bi-list-ul"></i></a>
                    <a href="<?= site_url('admin/cbt/banksoal/print/' . $bank['id']) ?>"
                      class="btn btn-outline-secondary btn-sm" title="Cetak PDF"><i class="bi bi-printer"></i></a>
                    <a href="<?= site_url('admin/cbt/banksoal/backup/' . $bank['id']) ?>"
                      class="btn btn-outline-dark btn-sm" title="Backup Bank Soal"><i class="bi bi-download"></i></a>
                    <a href="<?= site_url('admin/cbt/banksoal/delete/' . $bank['id']) ?>"
                      class="btn btn-outline-danger btn-sm"
                      onclick="return confirm('Hapus bank soal ini beserta semua soalnya?')" title="Hapus Bank Soal"><i
                        class="bi bi-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach ?>
          </tbody>

        </table>
      </div>
      <div class="card-footer">
        <button type="submit" class="btn btn-danger btn-sm"
          onclick="return confirm('Yakin ingin menghapus bank soal terpilih?')">
          <i class="bi bi-trash"></i> Hapus Terpilih
        </button>
      </div>
    </div>

    <!-- Tombol Hapus Terpilih untuk Mobile -->
    <div class="d-block d-md-none mt-2">
      <button type="submit" class="btn btn-danger w-100 py-2 shadow-sm fw-semibold"
        onclick="return confirm('Yakin ingin menghapus bank soal terpilih?')">
        <i class="bi bi-trash me-1"></i> Hapus Bank Soal Terpilih
      </button>
    </div>
  </form>
</div>

<!-- MODAL TAMBAH BANK SOAL -->
<div class="modal fade" id="modalAddBank" tabindex="-1" aria-labelledby="modalAddBankLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Bank Soal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formAddBank" autocomplete="off" onsubmit="return false;">
          <?= csrf_field() ?>
          <div class="mb-3">
            <label class="form-label">Kode Bank Soal</label>
            <input type="text" name="code" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mata Pelajaran</label>
            <select name="subject_id" class="form-select" required>
              <option value="">-- Pilih Mapel --</option>
              <?php foreach ($subjects as $m): ?>
                <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Jumlah Opsi Jawaban</label>
            <select name="option_count" class="form-select">
              <option value="3">3</option>
              <option value="4" selected>4</option>
              <option value="5">5</option>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
        <button type="button" id="btnSaveBank" class="btn btn-success">Simpan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Edit Bank Soal -->
<div class="modal fade" id="modalEditBank" tabindex="-1" aria-labelledby="modalEditBankLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Edit Bank Soal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formEditBank" autocomplete="off">
          <?= csrf_field() ?>
          <input type="hidden" name="id" id="edit_id">
          <div class="mb-3">
            <label class="form-label">Kode Bank Soal</label>
            <input type="text" name="code" id="edit_code" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Mata Pelajaran</label>
            <select name="subject_id" id="edit_subject" class="form-select" required>
              <option value="">-- Pilih Mapel --</option>
              <?php foreach ($subjects as $m): ?>
                <option value="<?= $m['id'] ?>"><?= esc($m['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-3">
            <label class="form-label">Jumlah Opsi Jawaban</label>
            <select name="option_count" id="edit_option" class="form-select">
              <?php foreach ([3, 4, 5] as $opt): ?>
                <option value="<?= $opt ?>"><?= $opt ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
        <button type="button" id="btnUpdateBank" class="btn btn-success">Simpan Perubahan</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal Restore Bank Soal -->
<div class="modal fade" id="modalRestoreBank" tabindex="-1" aria-labelledby="modalRestoreBankLabel">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Restore Bank Soal</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= site_url('admin/cbt/banksoal/restore') ?>" method="post" enctype="multipart/form-data">
        <div class="modal-body">
          <?= csrf_field() ?>
          <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> Pilih file ZIP hasil backup bank soal untuk mengembalikan data dan file pendukungnya.
          </div>
          <div class="mb-3">
            <label class="form-label">File Backup (.zip)</label>
            <input type="file" name="backup_file" class="form-control" accept=".zip" required>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-warning" data-bs-dismiss="modal">Tutup</button>
          <button type="submit" class="btn btn-primary">Mulai Restore</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  $(function () {

    /** ================================================================
     * 1️⃣ Setup DataTable
     * ================================================================ */
    const table = $('#datatable').DataTable({
      responsive: true,
      autoWidth: false,
      order: [],
      language: {
        search: "Cari:",
        lengthMenu: "Tampilkan _MENU_ data per halaman",
        zeroRecords: "Tidak ada data ditemukan",
        info: "Menampilkan _START_ - _END_ dari _TOTAL_ data",
        infoEmpty: "Tidak ada data tersedia",
        infoFiltered: "(disaring dari _MAX_ total data)"
      }
    });

    $('#checkAll, #checkAllMobile').on('click', function () {
      const isChecked = this.checked;
      $('#checkAll, #checkAllMobile').prop('checked', isChecked);
      $('input[name="ids[]"]').prop('checked', isChecked);
    });

    // Live search mobile cards
    $('#searchBankMobile').on('input', function () {
      const term = $(this).val().toLowerCase().trim();
      $('.bank-card-item').each(function () {
        const text = $(this).data('search') || '';
        if (text.indexOf(term) !== -1 || term === '') {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

    // Pastikan semua tombol non-submit
    $(document).on('click', 'button', function (e) {
      if ($(this).attr('type') === undefined) $(this).attr('type', 'button');
    });

    /** ================================================================
     * 2️⃣ Tambah Bank Soal (AJAX)
     * ================================================================ */
    $('#btnSaveBank').on('click', function (e) {
      e.preventDefault();
      const form = $('#formAddBank');
      const formData = form.serialize();

      $.ajax({
        url: '<?= site_url('admin/cbt/banksoal/storeAjax') ?>',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: function () {
          $('#btnSaveBank').prop('disabled', true).text('Menyimpan...');
        },
        success: function (res) {
          if (res.success) {
            const d = res.data;

            // Tutup modal (Bootstrap 5 API)
            const modalEl = document.getElementById('modalAddBank');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            form[0].reset();

            // Tambah baris baru di tabel
            const newRow = table.row.add([
              `<input type="checkbox" name="ids[]" value="${d.id}">`,
              table.data().count() + 1,
              `<strong>${d.code}</strong>`,
              d.subject_name,
              `0 soal (${d.option_count} opsi | 0 PG | 0 PGK | 0 BS | 0 Esai)`,
              d.creator_name,
              `<a href="<?= site_url('admin/cbt/banksoal/toggle/') ?>${d.id}" class="btn btn-outline-secondary btn-sm">Nonaktif</a>`,
              `<div class="btn-group">
               <button type="button" class="btn btn-outline-warning btn-sm btn-edit-bank"
                 data-id="${d.id}" data-code="${d.code}" data-subject="${d.subject_id}"
                 data-option="${d.option_count}" title="Edit Bank Soal">
                 <i class="bi bi-pencil"></i>
               </button>
               <a href="<?= site_url('admin/cbt/banksoal/copy/') ?>${d.id}" class="btn btn-outline-primary btn-sm" title="Salin"><i class="bi bi-files"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/detail/') ?>${d.id}" class="btn btn-outline-info btn-sm" title="Rincian"><i class="bi bi-list-ul"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/print/') ?>${d.id}" class="btn btn-outline-secondary btn-sm" title="Cetak"><i class="bi bi-printer"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/backup/') ?>${d.id}" class="btn btn-outline-dark btn-sm" title="Backup"><i class="bi bi-download"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/delete/') ?>${d.id}" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Hapus bank soal ini beserta semua soalnya?')"><i class="bi bi-trash"></i></a>
             </div>`
            ]).draw().node();

            $(newRow).addClass('table-success');
            setTimeout(() => $(newRow).removeClass('table-success'), 2000);

            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message,
              timer: 1500,
              showConfirmButton: false
            });
          } else {
            Swal.fire('Gagal', res.error || 'Terjadi kesalahan saat menyimpan', 'error');
          }
        },
        error: function (xhr) {
          console.error(xhr.responseText);
          Swal.fire('Gagal', 'Server tidak merespon (500)', 'error');
        },
        complete: function () {
          $('#btnSaveBank').prop('disabled', false).text('Simpan');
        }
      });
    });

    /** ================================================================
     * 3️⃣ Tombol Edit Bank Soal (event delegation)
     * ================================================================ */
    $(document).on('click', '.btn-edit-bank', function (e) {
      e.preventDefault();
      const btn = $(this);

      $('#edit_id').val(btn.data('id'));
      $('#edit_code').val(btn.data('code'));
      $('#edit_subject').val(btn.data('subject'));
      $('#edit_option').val(btn.data('option'));

      const modalEdit = new bootstrap.Modal(document.getElementById('modalEditBank'));
      modalEdit.show();
    });

    /** ================================================================
     * 4️⃣ Simpan Perubahan (AJAX Edit)
     * ================================================================ */
    $('#btnUpdateBank').on('click', function (e) {
      e.preventDefault();
      const formData = $('#formEditBank').serialize();

      $.ajax({
        url: '<?= site_url('admin/cbt/banksoal/updateAjax') ?>',
        type: 'POST',
        data: formData,
        dataType: 'json',
        beforeSend: () => $('#btnUpdateBank').prop('disabled', true).text('Menyimpan...'),
        success: function (res) {
          if (res.success) {
            const d = res.data;
            const rowEl = $('#datatable').find(`input[value="${d.id}"]`).closest('tr');
            const rowIndex = table.row(rowEl).index();

            // Update isi baris di tabel
            table.row(rowIndex).data([
              `<input type="checkbox" name="ids[]" value="${d.id}">`,
              rowIndex + 1,
              `<strong>${d.code}</strong>`,
              d.subject_name,
              `${d.total_questions ?? 0} soal (${d.option_count} opsi | ${d.total_pg ?? 0} PG | ${d.total_pg_kompleks ?? 0} PGK | ${d.total_bs ?? 0} BS | ${d.total_esai ?? 0} Esai)`,
              d.teacher_name ?? "Admin",
              d.is_active == 1
                ? `<a href="<?= site_url('admin/cbt/banksoal/toggle/') ?>${d.id}" class="btn btn-success btn-sm">Aktif</a>`
                : `<a href="<?= site_url('admin/cbt/banksoal/toggle/') ?>${d.id}" class="btn btn-outline-secondary btn-sm">Nonaktif</a>`,
              `<div class="btn-group">
               <button type="button" class="btn btn-outline-warning btn-sm btn-edit-bank"
                 data-id="${d.id}" data-code="${d.code}" data-subject="${d.subject_id}"
                 data-option="${d.option_count}" title="Edit Bank Soal">
                 <i class="bi bi-pencil"></i>
               </button>
               <a href="<?= site_url('admin/cbt/banksoal/copy/') ?>${d.id}" class="btn btn-outline-primary btn-sm" title="Salin"><i class="bi bi-files"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/detail/') ?>${d.id}" class="btn btn-outline-info btn-sm" title="Rincian"><i class="bi bi-list-ul"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/print/') ?>${d.id}" class="btn btn-outline-secondary btn-sm" title="Cetak"><i class="bi bi-printer"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/backup/') ?>${d.id}" class="btn btn-outline-dark btn-sm" title="Backup"><i class="bi bi-download"></i></a>
               <a href="<?= site_url('admin/cbt/banksoal/delete/') ?>${d.id}" class="btn btn-outline-danger btn-sm" title="Hapus" onclick="return confirm('Hapus bank soal ini beserta semua soalnya?')"><i class="bi bi-trash"></i></a>
             </div>`
            ]).draw(false);

            // Tutup modal edit
            const modalEl = document.getElementById('modalEditBank');
            const modal = bootstrap.Modal.getInstance(modalEl);
            if (modal) modal.hide();

            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message,
              timer: 1500,
              showConfirmButton: false
            });
          } else {
            Swal.fire('Gagal', res.error || 'Gagal memperbarui data', 'error');
          }
        },
        error: () => Swal.fire('Gagal', 'Server tidak merespon', 'error'),
        complete: () => $('#btnUpdateBank').prop('disabled', false).text('Simpan Perubahan')
      });
    });

    /** ================================================================
     * 5️⃣ Reset Form Saat Modal Ditutup
     * ================================================================ */
    $('#modalAddBank, #modalEditBank').on('hidden.bs.modal', function () {
      $(this).find('form')[0].reset();
    });

  });
</script>
<?= $this->endSection() ?>