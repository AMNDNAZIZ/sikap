<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0 text-gray-800">🛤️ Alur Tujuan Pembelajaran (ATP)</h1>
    <a href="<?= base_url('admin/administrasi-guru') ?>" class="btn btn-sm btn-secondary">Kembali</a>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('success') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="card shadow mb-4">
    <div class="card-body">
        <form action="<?= base_url('admin/administrasi-guru/atp') ?>" method="get" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Pilih Kelas:</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()" <?= $auto_class ? 'disabled' : '' ?>>
                    <?php if (!$auto_class): ?><option value="">-- Pilih Kelas --</option><?php endif; ?>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($auto_class): ?>
                    <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                <?php endif; ?>
            </div>
            <div class="col-md-5">
                <label class="form-label font-weight-bold">Pilih Mata Pelajaran:</label>
                <select name="subject_id" class="form-select" onchange="this.form.submit()" <?= empty($selected_class) ? 'disabled' : '' ?>>
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_subject == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>
</div>

<?php if ($selected_subject && $selected_class): ?>
    <?php if ($subject_not_mapped): ?>
        <div class="alert alert-warning border-left-warning shadow">
            <div class="d-flex align-items-start">
                <i class="bi bi-exclamation-triangle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">Mata Pelajaran Belum Di-mapping</h5>
                    <p class="mb-2">
                        Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> belum dihubungkan dengan Mapel Master (CP). 
                        Alur Tujuan Pembelajaran (ATP) tidak dapat ditampilkan karena belum ada pemetaan kurikulum.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan lakukan <strong>Pemetaan Pembelajaran/Mapping Kurikulum</strong> terlebih dahulu.
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="btn btn-warning">
                            <i class="bi bi-link-45deg me-1"></i> Ke Halaman Mapping
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif ($mapping_mismatch): ?>
        <div class="alert alert-danger border-left-danger shadow">
            <div class="d-flex align-items-start">
                <i class="bi bi-x-circle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">Mapping Tidak Sesuai dengan Level Sekolah</h5>
                    <p class="mb-2">
                        Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> di-mapping ke Mapel Master dengan jenjang 
                        <strong><?= esc($old_jenjang) ?></strong>, tetapi level sekolah saat ini adalah 
                        <strong><?= esc($current_jenjang) ?></strong>.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan lakukan <strong>Pemetaan Ulang</strong> sesuai level sekolah saat ini.
                    </p>
                    <div class="mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/mapping') ?>" class="btn btn-danger">
                            <i class="bi bi-arrow-repeat me-1"></i> Pemetaan Ulang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif (empty($atp_list)): ?>
        <div class="alert alert-info border-left-info shadow mb-4">
            <div class="d-flex align-items-start">
                <i class="bi bi-info-circle-fill me-3" style="font-size: 2rem;"></i>
                <div>
                    <h5 class="alert-heading mb-2">Belum Ada Data ATP</h5>
                    <p class="mb-2">
                        Mata pelajaran <strong>"<?= esc($subject_name) ?>"</strong> sudah di-mapping, 
                        tetapi belum ada Alur Tujuan Pembelajaran (ATP) yang disusun.
                    </p>
                    <hr>
                    <p class="mb-0">
                        <i class="bi bi-info-circle me-1"></i>
                        Silakan mulai menyusun ATP menggunakan form di bawah.
                    </p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <!-- Form dan Daftar ATP - Tampilkan selalu jika sudah mapping -->
    <?php if (!$subject_not_mapped && !$mapping_mismatch): ?>
    <div class="row">
        <?php if (!$readonly): ?>
        <!-- Form Analisis Terpadu -->
        <div class="col-md-4">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success">Analisis CP -> TP -> ATP</h6>
                </div>
                <!-- [Rest of the form as is...] -->
                <div class="card-body">
                    <form action="<?= base_url('admin/administrasi-guru/atp/store') ?>" method="post" id="atpForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                        <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                        <input type="hidden" name="id" id="atp_id">
                        
                        <div class="mb-3" <?= $auto_fase ? 'style="display:none;"' : '' ?>>
                            <label class="form-label font-weight-bold">1. Pilih Fase:</label>
                            <select id="filter_fase" class="form-select border-primary" <?= $auto_fase ? 'disabled' : '' ?>>
                                <option value="">-- Semua Fase --</option>
                                <?php foreach ($available_phases as $fase): ?>
                                    <option value="<?= $fase ?>" <?= $auto_fase == $fase ? 'selected' : '' ?>>Fase <?= $fase ?></option>
                                <?php endforeach; ?>
                            </select>
                            <input type="hidden" id="hidden_fase" value="<?= $auto_fase ?>">
                            <small class="text-muted">Pilih fase terlebih dahulu untuk menyaring elemen.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">2. Pilih CP (Elemen):</label>
                            <select name="cp_master_id" id="cp_master_id" class="form-select" required disabled>
                                <option value="">-- Pilih Elemen CP --</option>
                                <?php foreach ($cp_master_list as $cp): ?>
                                    <option value="<?= $cp['id'] ?>" 
                                            data-fase="<?= $cp['fase'] ?>" 
                                            data-desc="<?= esc($cp['deskripsi'], 'attr') ?>"
                                            class="cp-option">
                                        [<?= esc($cp['elemen']) ?>] Fase <?= $cp['fase'] ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div id="cp_preview" class="small text-muted mt-1 p-2 bg-light rounded border" style="display:none; max-height: 100px; overflow-y: auto;"></div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">3. Lingkup Materi:</label>
                            <input type="text" name="lingkup_materi" class="form-control" placeholder="E.g: Bilangan Cacah sampai 1.000" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">4. Tujuan Pembelajaran (TP):</label>
                            <div id="tp_container">
                                <div class="tp-row bg-light p-2 border rounded mb-2">
                                    <div class="row g-2">
                                        <div class="col-3">
                                            <input type="text" name="tp_kode[]" class="form-control form-control-sm" placeholder="1.1" required>
                                        </div>
                                        <div class="col-9">
                                            <textarea name="tp_deskripsi[]" class="form-control form-control-sm" rows="2" placeholder="Deskripsi TP..." required></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-1" id="add_tp">
                                <i class="bi bi-plus-circle"></i> Tambah TP
                            </button>
                        </div>

                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label font-weight-bold">Tingkat/Kelas:</label>
                                <input type="text" name="kelas" class="form-control" placeholder="E.g: 1" required>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label font-weight-bold">Semester:</label>
                                <select name="semester" class="form-select" required>
                                    <option value="1">1 (Ganjil)</option>
                                    <option value="2">2 (Genap)</option>
                                </select>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label font-weight-bold">Alokasi (JP):</label>
                                <input type="number" name="alokasi_waktu" class="form-control" placeholder="4" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label font-weight-bold">No. Urut:</label>
                            <input type="number" name="urutan" class="form-control" value="<?= count($atp_list) + 1 ?>" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-grow-1" id="submit_btn">Simpan Analisis</button>
                            <button type="button" class="btn btn-secondary" id="cancel_edit" style="display:none;">Batal Edit</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Daftar ATP -->
        <div class="<?= $readonly ? 'col-md-12' : 'col-md-8' ?>">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Alur Tujuan Pembelajaran</h6>
                    <a href="<?= base_url('admin/administrasi-guru/atp/print/' . $selected_class . '/' . $selected_subject) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak ATP
                    </a>
                </div>
                <div class="card-body">
                        <table class="table table-bordered table-hover align-middle">
                            <thead class="table-info">
                                <tr class="text-center">
                                    <th width="40">No</th>
                                    <th>Elemen CP</th>
                                    <th>Capaian Pembelajaran (CP)</th>
                                    <th>Lingkup Materi</th>
                                    <th>Tujuan Pembelajaran (TP)</th>
                                    <th width="60">Smtr</th>
                                    <th width="100">Alokasi Waktu</th>
                                    <th width="50">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($atp_list)): ?>
                                    <tr><td colspan="7" class="text-center">Belum ada alur yang disusun.</td></tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($atp_list as $atp): ?>
                                        <?php 
                                        $tps = $atp['tps'] ?? [];
                                        $rowCount = count($tps) ?: 1;
                                        ?>
                                        <tr>
                                            <td rowspan="<?= $rowCount ?>" class="text-center fw-bold"><?= $no++ ?></td>
                                            <td rowspan="<?= $rowCount ?>" class="fw-bold text-success"><?= esc($atp['elemen']) ?></td>
                                            <td rowspan="<?= $rowCount ?>">
                                                <small class="text-muted"><?= nl2br(esc($atp['cp_deskripsi'])) ?></small>
                                            </td>
                                            <td rowspan="<?= $rowCount ?>" class="text-center"><?= esc($atp['lingkup_materi']) ?></td>
                                            
                                            <?php if (!empty($tps)): ?>
                                                <td class="p-0" <?= $rowCount > 1 ? 'style="border-bottom:none;"' : '' ?>>
                                                    <div class="p-2">
                                                        <strong><?= esc($tps[0]['kode_tp']) ?></strong> <?= esc($tps[0]['deskripsi']) ?>
                                                    </div>
                                                </td>
                                            <?php else: ?>
                                                <td>-</td>
                                            <?php endif; ?>
                                            
                                            <td rowspan="<?= $rowCount ?>" class="text-center font-weight-bold"><?= $atp['semester'] ?></td>
                                            <td rowspan="<?= $rowCount ?>" class="text-center fw-bold"><?= esc($atp['alokasi_waktu']) ?> JP</td>
                                            <td rowspan="<?= $rowCount ?>" class="text-center">
                                                <div class="d-flex flex-column gap-1">
                                                    <?php if (!$readonly): ?>
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            onclick='editAtp(<?= json_encode([
                                                                "id" => $atp["id"],
                                                                "cp_master_id" => $atp["cp_master_id"],
                                                                "lingkup_materi" => $atp["lingkup_materi"],
                                                                "urutan" => $atp["urutan"],
                                                                "semester" => $atp["semester"],
                                                                "alokasi_waktu" => $atp["alokasi_waktu"],
                                                                "kelas" => !empty($tps) ? $tps[0]["kelas"] : "",
                                                                "fase" => !empty($cp_master_list) ? (array_values(array_filter($cp_master_list, fn($c) => $c["id"] == $atp["cp_master_id"]))[0]["fase"] ?? "") : "",
                                                                "tps" => $tps
                                                            ]) ?>)'>
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" onclick="confirmDeleteAtp(<?= $atp['id'] ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                    <?php else: ?>
                                                        <span class="badge bg-light text-dark border">View Only</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <?php for ($i = 1; $i < $rowCount; $i++): ?>
                                            <tr style="border-top:none;">
                                                <td class="p-0" style="border-top:none; <?= ($i < $rowCount - 1) ? 'border-bottom:none;' : '' ?>">
                                                    <div class="p-2">
                                                        <strong><?= esc($tps[$i]['kode_tp']) ?></strong> <?= esc($tps[$i]['deskripsi']) ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endfor; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; // End of !$subject_not_mapped && !$mapping_mismatch ?>
<?php else: ?>
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="bi bi-signpost-split text-gray-300" style="font-size: 5rem;"></i>
        </div>
        <h5 class="text-gray-500">Silakan pilih mata pelajaran untuk menyusun Alur Pembelajaran.</h5>
    </div>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    // Phase filtering logic
    $('#filter_fase').on('change', function() {
        const selectedFase = $(this).val();
        const cpSelect = $('#cp_master_id');
        
        // Reset CP selection
        cpSelect.val('').trigger('change');
        
        if (selectedFase) {
            cpSelect.prop('disabled', false);
            $('.cp-option').hide();
            $(`.cp-option[data-fase="${selectedFase}"]`).show();
        } else {
            cpSelect.prop('disabled', true);
            $('.cp-option').hide();
        }
    });

    // Auto-trigger phase filter if auto_fase is set
    const initialFase = $('#hidden_fase').val();
    if (initialFase) {
        $('#filter_fase').trigger('change');
    }

    $('#cp_master_id').on('change', function() {
        const desc = $(this).find('option:selected').data('desc');
        if (desc) {
            $('#cp_preview').html(desc).show();
        } else {
            $('#cp_preview').hide();
        }
    });

    // Dynamic TP rows
    $('#add_tp').on('click', function() {
        const row = `
            <div class="tp-row bg-light p-2 border rounded mb-2">
                <div class="d-flex justify-content-between mb-1">
                    <span class="small fw-bold">TP Lainnya</span>
                    <button type="button" class="btn-close btn-sm remove-tp" style="font-size: 0.7rem;"></button>
                </div>
                <div class="row g-2">
                    <div class="col-3">
                        <input type="text" name="tp_kode[]" class="form-control form-control-sm" placeholder="1.2" required>
                    </div>
                    <div class="col-9">
                        <textarea name="tp_deskripsi[]" class="form-control form-control-sm" rows="2" placeholder="Deskripsi TP..." required></textarea>
                    </div>
                </div>
            </div>`;
        $('#tp_container').append(row);
    });

    $(document).on('click', '.remove-tp', function() {
        $(this).closest('.tp-row').remove();
    });

    $('#cancel_edit').on('click', function() {
        $('#atpForm')[0].reset();
        $('#atp_id').val('');
        $('#tp_container').empty();
        // Add one empty row
        $('#add_tp').trigger('click');
        $('#submit_btn').text('Simpan Analisis');
        $(this).hide();
        $('#cp_preview').hide();
        $('#cp_master_id').prop('disabled', true);
    });
});

function editAtp(data) {
    // 1. Populate basic fields
    $('#atp_id').val(data.id);
    $('#filter_fase').val(data.fase).trigger('change');
    
    // 2. Set CP Master (must be done after phase filter re-enables it)
    setTimeout(() => {
        $('#cp_master_id').val(data.cp_master_id).trigger('change');
    }, 100);

    $('input[name="lingkup_materi"]').val(data.lingkup_materi);
    $('input[name="kelas"]').val(data.kelas);
    $('select[name="semester"]').val(data.semester);
    $('input[name="alokasi_waktu"]').val(data.alokasi_waktu);
    $('input[name="urutan"]').val(data.urutan);

    // 3. Populate TPs
    $('#tp_container').empty();
    if (data.tps && data.tps.length > 0) {
        data.tps.forEach((tp, index) => {
            const isFirst = index === 0;
            const row = `
                <div class="tp-row bg-light p-2 border rounded mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small fw-bold">${isFirst ? 'TP Utama' : 'TP Lainnya'}</span>
                        ${!isFirst ? '<button type="button" class="btn-close btn-sm remove-tp" style="font-size: 0.7rem;"></button>' : ''}
                    </div>
                    <div class="row g-2">
                        <div class="col-3">
                            <input type="text" name="tp_kode[]" class="form-control form-control-sm" value="${tp.kode_tp}" required>
                        </div>
                        <div class="col-9">
                            <textarea name="tp_deskripsi[]" class="form-control form-control-sm" rows="2" required>${tp.deskripsi}</textarea>
                        </div>
                    </div>
                </div>`;
            $('#tp_container').append(row);
        });
    }

    // 4. UI changes
    $('#submit_btn').text('Update Analisis');
    $('#cancel_edit').show();
    
    // Scroll to form
    window.scrollTo({ top: $('#atpForm').offset().top - 100, behavior: 'smooth' });
}

function confirmDeleteAtp(id) {
    if (confirm('Hapus alur ini?')) {
        window.location.href = "<?= base_url('admin/administrasi-guru/atp/delete/') ?>" + id;
    }
}
</script>
<?= $this->endSection() ?>
