<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Daftar Bank Soal</h4>
      <small class="text-muted">Halaman ini digunakan untuk membuat, mengedit, menghapus soal dalam kumpulan
        bank.</small>
    </div>
    <div class="d-flex gap-2">
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalRestoreBank">
        <i class="bi bi-upload"></i> Restore Bank Soal
      </button>
      <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddBank">
        <i class="bi bi-plus-circle"></i> Tambah Bank Soal
      </button>
    </div>
  </div>

  <form method="post" action="<?= site_url('admin/cbt/banksoal/bulkDelete') ?>">
    <?= csrf_field() ?>
    <div class="card shadow-sm">
      <div class="card-body table-responsive">
        <table id="datatable" class="table table-striped align-middle w-100">
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
                <td><input type="checkbox" name="ids[]" value="<?= $bank['id'] ?>"></td>
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
                  <div class="btn-group">
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
        <?php 
        // Tampilkan tombol hapus masal untuk semua user (admin dan guru)
        // Admin bisa hapus semua, guru hanya bisa hapus miliknya (sudah difilter di controller)
        ?>
        <button type="submit" class="btn btn-danger btn-sm"
          onclick="return confirm('Yakin ingin menghapus bank soal terpilih?')">
          <i class="bi bi-trash"></i> Hapus Terpilih
        </button>
      </div>
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

    $('#checkAll').on('click', function () {
      $('input[name="ids[]"]').prop('checked', this.checked);
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