<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <!-- Header -->
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0"><i class="bi bi-calendar-check"></i> Pengaturan Ujian</h4>
      <small class="text-muted">Kelola jadwal, token, dan status ujian siswa.</small>
    </div>
    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modalAddTest">
      <i class="bi bi-plus-circle"></i> Tambah Jadwal
    </button>
  </div>

  <!-- Table -->
  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table id="tableTests" class="table table-striped align-middle w-100">
        <thead class="table-light">
          <tr>
            <th width="5%">No</th>
            <th>Bank Soal</th>
            <th>Mapel</th>
            <th>Jenis Ujian</th>
            <th>Kelas</th>
            <th>Waktu Mulai</th>
            <th>Waktu Selesai</th>
            <th>Durasi</th>
            <th>Status</th>
            <th width="18%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($testStatuses as $i => $test): ?>
            <?php
            $classList = json_decode($test['class_codes'] ?? '[]', true);
            $classNames = is_array($classList) ? implode(', ', $classList) : '-';
            $status = $test['status_label'] ?? '<span class="badge bg-secondary">Tidak Diketahui</span>';
            $visibility = $test['is_visible'] ? '<i class="bi bi-eye text-success" title="Tampil di siswa"></i>' : '<i class="bi bi-eye-slash text-danger" title="Tersembunyi"></i>';
            ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><strong><?= esc($test['bank_code']) ?></strong></td>
              <td><?= esc($test['subject_name'] ?? '-') ?></td>
              <td><?= esc($test['exam_name'] ?? '-') ?></td>
              <td><?= esc($classNames) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($test['start_time'])) ?></td>
              <td><?= date('d/m/Y H:i', strtotime($test['end_time'])) ?></td>
              <td><?= esc($test['duration']) ?> menit</td>
              <td><?= $status ?></td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-outline-primary btn-sm btn-detail" data-id="<?= $test['id'] ?>">
                    <i class="bi bi-info-circle"></i>
                  </button>
                  <button class="btn btn-outline-warning btn-sm btn-edit" data-id="<?= $test['id'] ?>">
                    <i class="bi bi-pencil"></i>
                  </button>
                  <a href="<?= site_url('admin/cbt/teststatus/togglePause/' . $test['id']) ?>"
                    class="btn btn-outline-secondary btn-sm" title="Jeda / Lanjut">
                    <i class="bi bi-pause-fill"></i>
                  </a>
                  <a href="<?= site_url('admin/cbt/teststatus/toggleVisible/' . $test['id']) ?>"
                    class="btn btn-outline-info btn-sm" title="Tampilkan / Sembunyikan">
                    <?= $visibility ?>
                  </a>
                  <button class="btn btn-outline-danger btn-sm btn-delete" data-id="<?= $test['id'] ?>">
                    <i class="bi bi-trash"></i>
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

<!-- ==========================================================
     MODAL: TAMBAH JADWAL UJIAN (UPDATED)
========================================================== -->
<div class="modal fade" id="modalAddTest" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-plus-circle"></i> Tambah Jadwal Ujian</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="formAddTest">
          <?= csrf_field() ?>
          <div class="row">
            <!-- BANK SOAL -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Bank Soal <span class="text-danger">*</span></label>
              <select name="bank_id" id="bankSelect" class="form-select" required>
                <option value="">-- Pilih Bank Soal Aktif --</option>
                <?php foreach ($banks as $b): ?>
                  <option value="<?= $b['id'] ?>" data-total-pg="<?= $b['total_pg'] ?>"
                    data-total-pg-kompleks="<?= $b['total_pg_kompleks'] ?>" data-total-bs="<?= $b['total_bs'] ?? 0 ?>"
                    data-total-esai="<?= $b['total_esai'] ?>">
                    <?= esc($b['code']) ?> — <?= esc($b['subject_name'] ?? '-') ?> (<?= esc($b['level']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- JENIS UJIAN -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Jenis Ujian <span class="text-danger">*</span></label>
              <select name="exam_name_id" class="form-select" required>
                <option value="">-- Pilih Jenis Ujian --</option>
                <?php foreach ($examNames as $e): ?>
                  <option value="<?= $e['id'] ?>"><?= esc($e['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- KELAS PESERTA -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Kelas Peserta</label>
              <select name="class_codes[]" class="form-select select2-add" multiple style="width:100%;">
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['name'] ?>"><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- SEMESTER -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Semester</label>
              <select name="semester" class="form-select">
                <option value="ganjil">Ganjil</option>
                <option value="genap">Genap</option>
              </select>
            </div>

            <!-- JENIS MAPEL -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Jenis Mapel</label>
              <select name="subject_type" id="subjectType" class="form-select">
                <option value="umum">Umum</option>
                <option value="agama">Pilihan Agama</option>
              </select>
            </div>

            <!-- AGAMA PILIHAN -->
            <div class="col-md-3 mb-3 d-none" id="agamaWrapper">
              <label class="form-label">Agama</label>
              <select name="religion" class="form-select">
                <option value="">-- Pilih Agama --</option>
                <option value="Islam">Islam</option>
                <option value="Kristen">Kristen</option>
                <option value="Katolik">Katolik</option>
                <option value="Hindu">Hindu</option>
                <option value="Budha">Budha</option>
                <option value="Konghucu">Konghucu</option>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal PG</label>
              <input type="number" name="show_pg_count" id="pgCount" class="form-control" placeholder="0">
              <small class="text-muted" id="pgInfo"></small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal PG Kompleks</label>
              <input type="number" name="show_pg_kompleks_count" id="pgkCount" class="form-control" placeholder="0">
              <small class="text-muted" id="pgkInfo"></small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal BS</label>
              <input type="number" name="show_bs_count" id="bsCount" class="form-control" placeholder="0">
              <small class="text-muted" id="bsInfo"></small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal Esai</label>
              <input type="number" name="show_esai_count" id="esaiCount" class="form-control" placeholder="0">
              <small class="text-muted" id="esaiInfo"></small>
            </div>

            <!-- BOBOT -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot PG (%)</label>
              <input type="number" name="bobot_pg" id="bobotPg" class="form-control" value="0" min="0" max="100">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot PGK (%)</label>
              <input type="number" name="bobot_pg_kompleks" id="bobotPgk" class="form-control" value="0" min="0"
                max="100">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot BS (%)</label>
              <input type="number" name="bobot_bs" id="bobotBs" class="form-control" value="0" min="0" max="100">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot Esai (%)</label>
              <input type="number" name="bobot_esai" id="bobotEsai" class="form-control" value="0" min="0" max="100">
            </div>
            <div class="col-md-12 mb-2">
              <div class="p-2 border rounded bg-light text-center">
                <strong>Total Bobot: <span id="weightTotal" class="text-primary">0</span>%</strong>
                <span id="weightWarn" class="text-danger ms-2 d-none"><i class="bi bi-exclamation-triangle"></i> Total
                  harus 100%!</span>
              </div>
            </div>

            <!-- PENGATURAN LAIN -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Acak Soal</label>
              <select name="shuffle_question" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Acak Opsi</label>
              <select name="shuffle_option" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Atur Tombol Selesai</label>
              <select name="finish_button_lock" class="form-select">
                <option value="0" selected>Tidak</option>
                <option value="0.25">¼ waktu</option>
                <option value="0.5">½ waktu</option>
                <option value="0.75">¾ waktu</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Deteksi Kecurangan</label>
              <select name="anti_cheat" class="form-select">
                <option value="tidak">Tidak</option>
                <option value="kuat">Kuat</option>
                <option value="sangat_kuat">Sangat Kuat</option>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Batas Putar Audio</label>
              <input type="number" name="audio_limit" class="form-control" placeholder="0 = Unlimited" min="0">
              <small class="text-muted" style="font-size: 0.75rem;">0 = Tidak terbatas</small>
            </div>

            <!-- WAKTU -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Waktu Mulai</label>
              <input type="datetime-local" name="start_time" class="form-control" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Durasi Tes (menit)</label>
              <input type="number" name="duration" class="form-control" placeholder="90" required>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Ujian Ditutup</label>
              <input type="datetime-local" name="end_time" class="form-control" required>
            </div>

            <!-- TOKEN -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Tampilkan Token?</label>
              <select name="show_token" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Tampilkan Nilai?</label>
              <select name="show_score" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Token</label>
              <div class="input-group">
                <input type="text" name="token" id="tokenField" class="form-control" maxlength="6"
                  placeholder="Generate otomatis">
                <button type="button" class="btn btn-outline-secondary" id="btnGenerateToken"><i
                    class="bi bi-shuffle"></i></button>
              </div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button id="btnSaveTest" class="btn btn-success"><i class="bi bi-save"></i> Simpan Jadwal</button>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================================
     MODAL: RINCIAN UJIAN
========================================================== -->
<div class="modal fade" id="modalDetailTest" tabindex="-1">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title"><i class="bi bi-info-circle"></i> Rincian Jadwal Ujian</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div id="detailContent">
          <div class="text-center text-muted py-4">
            <i class="bi bi-hourglass-split fs-2"></i><br>
            Memuat data ujian...
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- ==========================================================
     MODAL: EDIT JADWAL UJIAN (UPDATED & MATCHS ADD MODAL)
========================================================== -->
<div class="modal fade" id="modalEditTest" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title"><i class="bi bi-pencil-square"></i> Edit Jadwal Ujian</h5>
        <button class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <form id="formEditTest">
          <?= csrf_field() ?>
          <input type="hidden" name="id">

          <div class="row">

            <!-- BANK SOAL -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Bank Soal <span class="text-danger">*</span></label>
              <select name="bank_id" id="bankSelectEdit" class="form-select" required>
                <option value="">-- Pilih Bank Soal Aktif --</option>
                <?php foreach ($banks as $b): ?>
                  <option value="<?= $b['id'] ?>" data-total-pg="<?= $b['total_pg'] ?>"
                    data-total-pg-kompleks="<?= $b['total_pg_kompleks'] ?>" data-total-bs="<?= $b['total_bs'] ?? 0 ?>"
                    data-total-esai="<?= $b['total_esai'] ?>">
                    <?= esc($b['code']) ?> — <?= esc($b['subject_name'] ?? '-') ?> (<?= esc($b['level']) ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- JENIS UJIAN -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Jenis Ujian <span class="text-danger">*</span></label>
              <select name="exam_name_id" class="form-select" required>
                <?php foreach ($examNames as $e): ?>
                  <option value="<?= $e['id'] ?>"><?= esc($e['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- KELAS -->
            <div class="col-md-6 mb-3">
              <label class="form-label">Kelas Peserta</label>
              <select name="class_codes[]" class="form-select select2Edit" multiple style="width:100%;">
                <?php foreach ($classes as $c): ?>
                  <option value="<?= $c['name'] ?>"><?= esc($c['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <!-- SEMESTER -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Semester</label>
              <select name="semester" class="form-select">
                <option value="ganjil">Ganjil</option>
                <option value="genap">Genap</option>
              </select>
            </div>

            <!-- SUBJECT TYPE -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Jenis Mapel</label>
              <select name="subject_type" id="subjectTypeEdit" class="form-select">
                <option value="umum">Umum</option>
                <option value="agama">Pilihan Agama</option>
              </select>
            </div>

            <!-- AGAMA -->
            <div class="col-md-3 mb-3 d-none" id="agamaWrapperEdit">
              <label class="form-label">Agama</label>
              <select name="religion" class="form-select">
                <?php foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu'] as $r): ?>
                  <option value="<?= $r ?>"><?= $r ?></option>
                <?php endforeach; ?>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal PG</label>
              <input type="number" name="show_pg_count" id="pgCountEdit" class="form-control">
              <small class="text-muted" id="pgInfoEdit"></small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal PG Kompleks</label>
              <input type="number" name="show_pg_kompleks_count" id="pgkCountEdit" class="form-control">
              <small class="text-muted" id="pgkInfoEdit"></small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal BS</label>
              <input type="number" name="show_bs_count" id="bsCountEdit" class="form-control" placeholder="0">
              <small class="text-muted" id="bsInfoEdit"></small>
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Jumlah Soal Esai</label>
              <input type="number" name="show_esai_count" id="esaiCountEdit" class="form-control">
              <small class="text-muted" id="esaiInfoEdit"></small>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot PG (%)</label>
              <input type="number" name="bobot_pg" id="bobotPgEdit" class="form-control" min="0" max="100">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot PGK (%)</label>
              <input type="number" name="bobot_pg_kompleks" id="bobotPgkEdit" class="form-control" min="0" max="100">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot BS (%)</label>
              <input type="number" name="bobot_bs" id="bobotBsEdit" class="form-control" min="0" max="100">
            </div>
            <div class="col-md-3 mb-3">
              <label class="form-label">Bobot Esai (%)</label>
              <input type="number" name="bobot_esai" id="bobotEsaiEdit" class="form-control" min="0" max="100">
            </div>
            <div class="col-md-12 mb-2">
              <div class="p-2 border rounded bg-light text-center">
                <strong>Total Bobot: <span id="weightTotalEdit" class="text-primary">0</span>%</strong>
                <span id="weightWarnEdit" class="text-danger ms-2 d-none"><i class="bi bi-exclamation-triangle"></i>
                  Total harus 100%!</span>
              </div>
            </div>

            <!-- SHUFFLE -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Acak Soal</label>
              <select name="shuffle_question" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Acak Opsi</label>
              <select name="shuffle_option" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>

            <!-- FINISH BUTTON LOCK -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Atur Tombol Selesai</label>
              <select name="finish_button_lock" class="form-select">
                <option value="0">Tidak</option>
                <option value="0.25">¼ waktu</option>
                <option value="0.5">½ waktu</option>
                <option value="0.75">¾ waktu</option>
              </select>
            </div>

            <!-- ANTI CHEAT -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Deteksi Kecurangan</label>
              <select name="anti_cheat" class="form-select">
                <option value="tidak">Tidak</option>
                <option value="kuat">Kuat</option>
                <option value="sangat_kuat">Sangat Kuat</option>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Batas Putar Audio</label>
              <input type="number" name="audio_limit" class="form-control" placeholder="0 = Unlimited" min="0">
              <small class="text-muted" style="font-size: 0.75rem;">0 = Tidak terbatas</small>
            </div>

            <!-- WAKTU -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Waktu Mulai</label>
              <input type="datetime-local" name="start_time" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Durasi Tes (menit)</label>
              <input type="number" name="duration" class="form-control">
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Ujian Ditutup</label>
              <input type="datetime-local" name="end_time" class="form-control">
            </div>

            <!-- TOKEN -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Tampilkan Token?</label>
              <select name="show_token" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>

            <div class="col-md-3 mb-3">
              <label class="form-label">Tampilkan Nilai?</label>
              <select name="show_score" class="form-select">
                <option value="ya">Ya</option>
                <option value="tidak">Tidak</option>
              </select>
            </div>

            <!-- TOKEN -->
            <div class="col-md-3 mb-3">
              <label class="form-label">Token</label>
              <div class="input-group">
                <input type="text" name="token" id="tokenFieldEdit" class="form-control" maxlength="6">
                <button type="button" class="btn btn-outline-secondary" id="btnGenerateTokenEdit">
                  <i class="bi bi-shuffle"></i>
                </button>
              </div>
            </div>

          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button id="btnUpdateTest" class="btn btn-warning">
          <i class="bi bi-save"></i> Update Jadwal
        </button>
      </div>
    </div>
  </div>
</div>



<?= $this->endSection() ?>


<?= $this->section('scripts') ?>
<script>
  $(function () {
    // ======================================================
    // Load Bank Soal dengan Filter Guru / Admin
    // ======================================================
    // AJAX Bank Loading removed - handled by PHP Controller
    // Keeping simple init



    /* ======================================================
     * 1️⃣ Inisialisasi Select2 (lebar penuh)
     * ====================================================== */
    // Initialize specific select2 for Add Modal
    $('.select2-add').select2({
      dropdownParent: $('#modalAddTest'),
      width: '100%',
      placeholder: "Pilih kelas peserta..."
    });

    /* ======================================================
     * 2️⃣ Generate Token Acak (Tambah)
     * ====================================================== */
    $('#btnGenerateToken').on('click', function () {
      const token = Math.random().toString(36).substring(2, 8).toUpperCase();
      $('#tokenField').val(token);
    });

    /* ======================================================
     * 3️⃣ Tampilkan / Sembunyikan Pilihan Agama (Tambah)
     * ====================================================== */
    $('#subjectType').on('change', function () {
      if ($(this).val() === 'agama') {
        $('#agamaWrapper').removeClass('d-none');
      } else {
        $('#agamaWrapper').addClass('d-none');
      }
    });

    /* ======================================================
     * 4️⃣ Saat Pilih Bank Soal → tampilkan total PG & Esai (Tambah)
     * ====================================================== */
    $('#bankSelect').on('change', function () {
      const selected = $(this).find(':selected');
      const totalPg = selected.data('total-pg') || 0;
      const totalPgk = selected.data('total-pg-kompleks') || 0;
      const totalBs = selected.data('total-bs') || 0;
      const totalEsai = selected.data('total-esai') || 0;

      // Fix template literals mechanism
      $('#pgInfo').text(`Tersedia ${totalPg} soal PG`);
      $('#pgkInfo').text(`Tersedia ${totalPgk} soal PG Kompleks`);
      $('#bsInfo').text(`Tersedia ${totalBs} soal BS`);
      $('#esaiInfo').text(`Tersedia ${totalEsai} soal Esai`);

      $('#pgCount').attr('max', totalPg);
      $('#pgkCount').attr('max', totalPgk);
      $('#bsCount').attr('max', totalBs);
      $('#esaiCount').attr('max', totalEsai);
    });


    /* ======================================================
     * 5️⃣ Validasi Jumlah Soal PG & Esai (Tambah)
     * ====================================================== */
    $('#pgCount, #pgkCount, #bsCount, #esaiCount').on('input', function () {
      const max = parseInt($(this).attr('max') || 0);
      const val = parseInt($(this).val() || 0);
      if (val > max && max > 0) {
        Swal.fire('Jumlah Soal Melebihi Batas',
          'Jumlah soal yang dimasukkan melebihi total soal di bank soal!',
          'warning'
        );
        $(this).val(max);
      }
    });

    /* ======================================================
     * 6️⃣ Otomatis Hubungkan Semua Bobot (Tambah)
     * ====================================================== */
    $('#bobotPg, #bobotPgk, #bobotBs, #bobotEsai').on('input', function () {
      let pg = parseInt($('#bobotPg').val()) || 0;
      let pgk = parseInt($('#bobotPgk').val()) || 0;
      let bs = parseInt($('#bobotBs').val()) || 0;
      let esai = parseInt($('#bobotEsai').val()) || 0;

      let total = pg + pgk + bs + esai;

      // Soft clamp: Jika total melebihi 100, kurangi nilai input terakhir
      if (total > 100) {
        let over = total - 100;
        let currentVal = parseInt($(this).val()) || 0;
        $(this).val(Math.max(0, currentVal - over));

        // Re-calculate after clamp
        pg = parseInt($('#bobotPg').val()) || 0;
        pgk = parseInt($('#bobotPgk').val()) || 0;
        bs = parseInt($('#bobotBs').val()) || 0;
        esai = parseInt($('#bobotEsai').val()) || 0;
        total = pg + pgk + bs + esai;
      }

      $('#weightTotal').text(total);
      if (total === 100) {
        $('#weightTotal').removeClass('text-danger').addClass('text-success');
        $('#weightWarn').addClass('d-none');
      } else {
        $('#weightTotal').removeClass('text-success').addClass('text-danger');
        $('#weightWarn').removeClass('d-none');
      }
    });


    /* ======================================================
     * 7️⃣ Tombol Simpan Jadwal (Tambah via AJAX)
     * ====================================================== */
    $('#btnSaveTest').on('click', function () {
      const form = $('#formAddTest');
      $.ajax({
        url: "<?= site_url('admin/cbt/teststatus/store') ?>",
        type: "POST",
        data: form.serialize(),
        dataType: "json",
        beforeSend: () => {
          $('#btnSaveTest')
            .prop('disabled', true)
            .html('<i class="bi bi-hourglass-split"></i> Menyimpan...');
        },
        success: function (res) {
          if (res.success) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message,
              timer: 1800,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            if (res.errors) {
              let txt = '';
              for (let field in res.errors) {
                txt += `• ${res.errors[field]}<br>`;
              }
              Swal.fire({
                icon: 'error',
                title: res.message || 'Gagal Menyimpan',
                html: txt
              });
            } else {
              Swal.fire('Gagal', res.message || 'Gagal menyimpan.', 'error');
            }

          }
        },
        error: function (xhr) {
          Swal.fire('Error', 'Terjadi kesalahan server. Periksa koneksi.', 'error');
          console.error(xhr.responseText);
        },
        complete: () => {
          $('#btnSaveTest')
            .prop('disabled', false)
            .html('<i class="bi bi-save"></i> Simpan Jadwal');
        }
      });
    });

    /* ======================================================
     * 8️⃣ Hapus Jadwal Ujian
     * ====================================================== */
    $(document).on('click', '.btn-delete', function () {
      const id = $(this).data('id');
      Swal.fire({
        title: 'Hapus Jadwal?',
        text: 'Semua data hasil ujian yang terkait juga akan terhapus!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
      }).then((r) => {
        if (r.isConfirmed) {
          window.location.href = "<?= site_url('admin/cbt/teststatus/delete/') ?>" + id;
        }
      });
    });

    /* ======================================================
     * 9️⃣ Detail Jadwal (AJAX)
     * ====================================================== */
    $(document).on('click', '.btn-detail', function () {

      const id = $(this).data('id');
      $('#modalDetailTest').modal('show');

      $('#detailContent').html(`
      <div class="text-center text-muted py-4">
        <i class="bi bi-hourglass-split fs-2"></i><br>Memuat data ujian...
      </div>
    `);

      $.get("<?= site_url('admin/cbt/teststatus/detail/') ?>" + id, function (res) {

        if (!res.success) {
          $('#detailContent').html(`<div class="alert alert-danger">Gagal memuat data ujian.</div>`);
          return;
        }

        const t = res.data;
        let kelas = Array.isArray(t.class_codes) ? t.class_codes.join(', ') : t.class_codes;

        /* ============================
         * Konversi finish_button_lock
         * ============================ */
        let lock_label = '-';
        switch (t.finish_button_lock) {
          case '0': lock_label = 'Tidak dikunci (Sewaktu-waktu dapat selesai)'; break;
          case '0.25': lock_label = '1/4 waktu berjalan'; break;
          case '0.5': lock_label = '1/2 waktu berjalan'; break;
          case '0.75': lock_label = '3/4 waktu berjalan'; break;
          default: lock_label = t.finish_button_lock;
        }

        $('#detailContent').html(`
      <table class="table table-bordered table-sm">

            <tr><th width="30%">Bank Soal</th><td>${t.bank_code || '-'}</td></tr>
            <tr><th>Mapel</th><td>${t.subject_name || '-'}</td></tr>
            <tr><th>Jenis Ujian</th><td>${t.exam_name || '-'}</td></tr>
            <tr><th>Kelas Peserta</th><td>${kelas || '-'}</td></tr>
            <tr><th>Semester</th><td>${t.semester}</td></tr>
            <tr><th>Jenis Mapel</th><td>${t.subject_type}</td></tr>

            <tr><th>Jumlah Soal PG</th><td>${t.show_pg_count}</td></tr>
            <tr><th>Jumlah Soal PG Kompleks</th><td>${t.show_pg_kompleks_count || 0}</td></tr>
            <tr><th>Jumlah Soal BS</th><td>${t.show_bs_count || 0}</td></tr>
            <tr><th>Jumlah Soal Esai</th><td>${t.show_esai_count}</td></tr>

            <tr><th>Bobot</th><td>PG: ${t.bobot_pg}% | PGK: ${t.bobot_pg_kompleks || 0}% | BS: ${t.bobot_bs || 0}% | Esai: ${t.bobot_esai}%</td></tr>
            <tr><th>Durasi Tes</th><td>${t.duration} menit</td></tr>

            <tr><th>Waktu Mulai</th><td>${t.start_time}</td></tr>
            <tr><th>Ujian Ditutup</th><td>${t.end_time}</td></tr>

            <tr><th>Token</th><td>${t.token || '-'}</td></tr>
            <tr><th>Tampilkan Token</th><td>${t.show_token === 'ya' ? 'Ya' : 'Tidak'}</td></tr>
            <tr><th>Tampilkan Nilai</th><td>${t.show_score === 'ya' ? 'Ya' : 'Tidak'}</td></tr>

            <tr><th>Acak Soal</th><td>${t.shuffle_question === 'ya' ? 'Ya' : 'Tidak'}</td></tr>
            <tr><th>Acak Opsi</th><td>${t.shuffle_option === 'ya' ? 'Ya' : 'Tidak'}</td></tr>

            <tr><th>Deteksi Kecurangan</th><td>${t.anti_cheat}</td></tr>
            <tr><th>Batas Putar Audio</th><td>${t.audio_limit == 0 ? 'Tidak Terbatas' : t.audio_limit + ' kali'}</td></tr>

            <!-- ⭐ TAMBAHAN PENTING: FINISH BUTTON LOCK -->
            <tr><th>Atur Tombol Selesai</th><td>${lock_label}</td></tr>

          </table>
        `);

      }, 'json');
    });


    /* ======================================================
     * 🔟 Edit Jadwal Ujian (AJAX + Sinkron Modal Tambah)
     * ====================================================== */

    $(document).on('click', '.btn-edit', function () {

      const id = $(this).data('id');
      $('#modalEditTest').modal('show');

      $('#formEditTest')[0].reset();

      /* Reset Info PG, PGK, BS & Esai */
      $('#pgInfoEdit').text('');
      $('#pgkInfoEdit').text('');
      $('#bsInfoEdit').text('');
      $('#esaiInfoEdit').text('');

      /* Hancurkan Select2 lama agar tidak bentrok */
      if ($('#formEditTest select[name="class_codes[]"]').hasClass("select2-hidden-accessible")) {
        $('#formEditTest select[name="class_codes[]"]').select2("destroy");
      }

      /* Inisialisasi Select2 lagi untuk modal edit */
      $('#formEditTest select[name="class_codes[]"]').select2({
        dropdownParent: $('#modalEditTest'),
        width: "100%"
      });

      $.get("<?= site_url('admin/cbt/teststatus/edit/') ?>" + id, function (res) {

        if (!res.success) {
          Swal.fire('Gagal', 'Data ujian tidak ditemukan.', 'error');
          return;
        }

        const t = res.data;

        /* ======================================================
         * Filter dropdown bank soal + hak edit bank soal
         * ====================================================== */
        let bankSelect = $('#bankSelectEdit');

        // Kosongkan dulu
        bankSelect.empty();

        // Tambahkan daftar bank sesuai endpoint
        res.banks.forEach(b => {
          bankSelect.append(`<option value="${b.id}" 
                            data-total-pg="${b.total_pg}" 
                            data-total-pg-kompleks="${b.total_pg_kompleks}" 
                            data-total-bs="${b.total_bs || 0}"
                            data-total-esai="${b.total_esai}">${b.code}</option>`);
        });

        // Set bank yang sedang digunakan
        bankSelect.val(t.bank_id).trigger('change');

        // Jika tidak boleh ubah bank soal → disable
        if (!res.can_edit_bank) {
          bankSelect.prop('disabled', true);
        } else {
          bankSelect.prop('disabled', false);
        }

        // Populate Audio Limit
        $('#formEditTest input[name="audio_limit"]').val(t.audio_limit);


        /* ================================================
         * SET FIELD FORM EDIT
         * ================================================ */
        $('#formEditTest input[name=id]').val(t.id);
        $('#formEditTest select[name=bank_id]').val(t.bank_id).trigger('change');
        $('#formEditTest select[name=exam_name_id]').val(t.exam_name_id);
        $('#formEditTest select[name=semester]').val(t.semester);

        // jenis mapel & agama
        $('#formEditTest select[name=subject_type]').val(t.subject_type).trigger('change');
        $('#formEditTest select[name=religion]').val(t.religion);

        // jumlah soal
        $('#pgCountEdit').val(t.show_pg_count);
        $('#pgkCountEdit').val(t.show_pg_kompleks_count);
        $('#bsCountEdit').val(t.show_bs_count); // Tambahkan ini
        $('#esaiCountEdit').val(t.show_esai_count);

        // bobot
        $('#bobotPgEdit').val(t.bobot_pg);
        $('#bobotPgkEdit').val(t.bobot_pg_kompleks);
        $('#bobotBsEdit').val(t.bobot_bs);
        $('#bobotEsaiEdit').val(t.bobot_esai);

        // Trigger input event to update total
        $('#bobotPgEdit').trigger('input');

        // opsi
        $('#formEditTest select[name=shuffle_question]').val(t.shuffle_question);
        $('#formEditTest select[name=shuffle_option]').val(t.shuffle_option);
        $('#formEditTest select[name=finish_button_lock]').val(t.finish_button_lock);
        $('#formEditTest select[name=anti_cheat]').val(t.anti_cheat);
        $('#formEditTest select[name=show_token]').val(t.show_token);
        $('#formEditTest select[name=show_score]').val(t.show_score);

        // waktu
        $('#formEditTest input[name=start_time]').val(t.start_time.replace(' ', 'T'));
        $('#formEditTest input[name=end_time]').val(t.end_time.replace(' ', 'T'));
        $('#formEditTest input[name=duration]').val(t.duration);

        // token
        $('#formEditTest input[name=token]').val(t.token);

        /* ================================================
         * KELAS (MULTIPLE SELECT2)
         * ================================================ */
        const selected = JSON.parse(t.class_codes || "[]");
        $('#formEditTest select[name="class_codes[]"]').val(selected).trigger('change');

        /* ================================================
         * UPDATE TOTAL PG & ESAI DARI BANK TERPILIH
         * ================================================ */
        const bankOption = $('#bankSelectEdit').find(':selected');
        const totalPg = bankOption.data('total-pg') || 0;
        const totalPgk = bankOption.data('total-pg-kompleks') || 0;
        const totalBs = bankOption.data('total-bs') || 0;
        const totalEsai = bankOption.data('total-esai') || 0;

        $('#pgInfoEdit').text(`Tersedia ${totalPg} soal PG`);
        $('#pgkInfoEdit').text(`Tersedia ${totalPgk} soal PG Kompleks`);
        $('#bsInfoEdit').text(`Tersedia ${totalBs} soal BS`);
        $('#esaiInfoEdit').text(`Tersedia ${totalEsai} soal Esai`);

        $('#pgCountEdit').attr('max', totalPg);
        $('#pgkCountEdit').attr('max', totalPgk);
        $('#bsCountEdit').attr('max', totalBs);
        $('#esaiCountEdit').attr('max', totalEsai);

      }, 'json');

    });

    /* ======================================================
     * Saat Pilih Bank Soal → update total PG & Esai (EDIT)
     * ====================================================== */
    $('#bankSelectEdit').on('change', function () {
      const selected = $(this).find(':selected');
      const totalPg = selected.data('total-pg') || 0;
      const totalPgk = selected.data('total-pg-kompleks') || 0;
      const totalBs = selected.data('total-bs') || 0;
      const totalEsai = selected.data('total-esai') || 0;

      $('#pgInfoEdit').text(`Tersedia ${totalPg} soal PG`);
      $('#pgkInfoEdit').text(`Tersedia ${totalPgk} soal PG Kompleks`);
      $('#bsInfoEdit').text(`Tersedia ${totalBs} soal BS`);
      $('#esaiInfoEdit').text(`Tersedia ${totalEsai} soal Esai`);

      $('#pgCountEdit').attr('max', totalPg);
      $('#pgkCountEdit').attr('max', totalPgk);
      $('#bsCountEdit').attr('max', totalBs);
      $('#esaiCountEdit').attr('max', totalEsai);
    });

    /* ======================================================
     * Validasi jumlah PG, PGK, BS & Esai (EDIT)
     * ====================================================== */
    $('#pgCountEdit, #pgkCountEdit, #bsCountEdit, #esaiCountEdit').on('input', function () {
      const max = parseInt($(this).attr('max') || 0);
      const val = parseInt($(this).val() || 0);

      if (val > max && max > 0) {
        Swal.fire('Jumlah Soal Melebihi Batas',
          'Jumlah soal melebihi total soal pada bank!',
          'warning'
        );
        $(this).val(max);
      }
    });

    /* ======================================================
     * Bobot Saling Terkait (EDIT)
     * ====================================================== */
    $('#bobotPgEdit, #bobotPgkEdit, #bobotBsEdit, #bobotEsaiEdit').on('input', function () {
      let pg = parseInt($('#bobotPgEdit').val()) || 0;
      let pgk = parseInt($('#bobotPgkEdit').val()) || 0;
      let bs = parseInt($('#bobotBsEdit').val()) || 0;
      let esai = parseInt($('#bobotEsaiEdit').val()) || 0;

      let total = pg + pgk + bs + esai;

      // Soft clamp: Jika total melebihi 100, kurangi nilai input terakhir
      if (total > 100) {
        let over = total - 100;
        let currentVal = parseInt($(this).val()) || 0;
        $(this).val(Math.max(0, currentVal - over));

        // Re-calculate after clamp
        pg = parseInt($('#bobotPgEdit').val()) || 0;
        pgk = parseInt($('#bobotPgkEdit').val()) || 0;
        bs = parseInt($('#bobotBsEdit').val()) || 0;
        esai = parseInt($('#bobotEsaiEdit').val()) || 0;
        total = pg + pgk + bs + esai;
      }

      $('#weightTotalEdit').text(total);
      if (total === 100) {
        $('#weightTotalEdit').removeClass('text-danger').addClass('text-success');
        $('#weightWarnEdit').addClass('d-none');
      } else {
        $('#weightTotalEdit').removeClass('text-success').addClass('text-danger');
        $('#weightWarnEdit').removeClass('d-none');
      }
    });

    /* ======================================================
     * Saat ubah jenis mapel (agama)
     * ====================================================== */
    $('#subjectTypeEdit').on('change', function () {
      if ($(this).val() === 'agama') {
        $('#agamaWrapperEdit').removeClass('d-none');
      } else {
        $('#agamaWrapperEdit').addClass('d-none');
      }
    });

    /* ======================================================
     * 11️⃣ Update Jadwal (AJAX)
     * ====================================================== */
    $('#btnUpdateTest').on('click', function () {
      const id = $('#formEditTest input[name=id]').val();
      const form = $('#formEditTest');

      $.ajax({
        url: "<?= site_url('admin/cbt/teststatus/update/') ?>" + id,
        type: "POST",
        data: form.serialize(),
        dataType: "json",
        beforeSend: () => {
          $('#btnUpdateTest').prop('disabled', true)
            .html('<i class="bi bi-hourglass-split"></i> Menyimpan...');
        },
        success: function (res) {
          if (res.success) {
            Swal.fire({
              icon: 'success',
              title: 'Berhasil',
              text: res.message,
              timer: 1800,
              showConfirmButton: false
            }).then(() => location.reload());
          } else {
            if (res.errors) {
              let txt = '';
              for (let field in res.errors) {
                txt += `• ${res.errors[field]}<br>`;
              }
              Swal.fire({
                icon: 'error',
                title: res.message || 'Gagal Memperbarui',
                html: txt
              });
            } else {
              Swal.fire('Gagal', res.message || 'Gagal memperbarui jadwal.', 'error');
            }

          }
        },
        error: function (xhr) {
          console.error('Error:', xhr.responseText);
          Swal.fire('Error', 'Terjadi kesalahan server.', 'error');
        },
        complete: () => {
          $('#btnUpdateTest').prop('disabled', false)
            .html('<i class="bi bi-save"></i> Update Jadwal');
        }
      });
    });

  });
</script>


<?= $this->endSection() ?>