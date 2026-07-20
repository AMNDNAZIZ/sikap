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
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
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

    <?php if (!$subject_not_mapped && !$mapping_mismatch): ?>

    <?php if (!empty($atp_list)): ?>
    <!-- Info: ATP sudah ada, tampilkan tahun pembuatan jika data lama -->
    <?php
    $atpCreatedAt = $atp_list[0]['created_at'] ?? null;
    $atpYear = $atpCreatedAt ? date('Y', strtotime($atpCreatedAt)) : null;
    $currentYear = date('Y');
    ?>
    <?php if ($atpYear && $atpYear < $currentYear): ?>
    <div class="alert alert-success border-left-success d-flex align-items-center mb-3 py-2">
        <i class="bi bi-calendar-check-fill me-2 text-success fs-5"></i>
        <div>
            <strong>ATP tersedia dari tahun <?= $atpYear ?>.</strong>
            ATP ini masih berlaku untuk tahun ajaran saat ini selama kurikulum tidak berubah.
            Anda dapat langsung menggunakannya atau memperbarui jika diperlukan.
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <div class="row">
        <?php if (!$readonly): ?>
        <!-- ===== FORM INPUT ATP ===== -->
        <div class="col-md-4">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success" id="form_title">Tambah ATP Baru</h6>
                </div>
                <div class="card-body">

                    <?php if ($is_multi_guru && !$has_own_atp && !empty($classes_with_atp)): ?>
                    <!-- Notif: kelas ini belum punya ATP, ada kelas lain yang bisa disalin -->
                    <div class="alert alert-info py-2 mb-3">
                        <i class="bi bi-info-circle me-1"></i>
                        <strong>Kelas ini belum punya ATP.</strong><br>
                        Salin ATP dari kelas lain se-level secara permanen, atau buat sendiri dari awal:
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <?php foreach ($classes_with_atp as $cwc): ?>
                                <form method="post" action="<?= base_url('admin/administrasi-guru/atp/copy-from-source') ?>" style="display:inline;">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="source_class_id" value="<?= $cwc['id'] ?>">
                                    <input type="hidden" name="target_class_id" value="<?= $selected_class ?>">
                                    <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                                    <button type="submit" class="btn btn-sm btn-success"
                                        onclick="return confirm('Salin ATP dari <?= esc($cwc['name']) ?> ke kelas ini secara permanen?\n\nData asli tidak akan terhapus.')">
                                        <i class="bi bi-copy"></i> Salin ATP <?= esc($cwc['name']) ?>
                                    </button>
                                </form>
                            <?php endforeach; ?>
                        </div>
                        <small class="text-muted mt-1 d-block">
                            <i class="bi bi-info-circle"></i> ATP yang disalin menjadi milik kelas ini dan bisa diedit secara independen.
                        </small>
                    </div>
                    <?php elseif ($is_multi_guru && !$has_own_atp && empty($classes_with_atp)): ?>
                    <div class="alert alert-warning py-2 mb-3">
                        <i class="bi bi-exclamation-triangle me-1"></i>
                        Belum ada kelas lain se-level yang memiliki ATP untuk mapel ini.
                    </div>
                    <?php elseif ($source_class_id ?? false): ?>
                    <!-- Tidak lagi dipakai karena salin sekarang permanen -->
                    <?php endif; ?>
                    <form action="<?= base_url('admin/administrasi-guru/atp/store') ?>" method="post" id="atpForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="class_id" value="<?= $selected_class ?>">
                        <input type="hidden" name="subject_id" value="<?= $selected_subject ?>">
                        <input type="hidden" name="id" id="atp_id">

                        <!-- 1. Lingkup Materi -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">1. Lingkup Materi:</label>
                            <input type="text" name="lingkup_materi" id="lingkup_materi" class="form-control"
                                   placeholder="Contoh: Bilangan Cacah sampai 1.000" required>
                        </div>

                        <!-- 2. Elemen CP (dinamis) -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">2. Elemen CP:</label>
                            <div id="elemen_container">
                                <!-- Template elemen akan di-inject via JS -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-success w-100 mt-2" id="btn_add_elemen">
                                <i class="bi bi-plus-circle"></i> Tambah Elemen CP
                            </button>
                        </div>

                        <!-- 3. Kelas, Semester, Alokasi -->
                        <div class="row">
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Kelas:</label>
                                <input type="text" name="kelas" id="kelas_input" class="form-control"
                                       placeholder="Contoh: 7" required>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Semester:</label>
                                <select name="semester" id="semester_input" class="form-select" required>
                                    <option value="1">1 (Ganjil)</option>
                                    <option value="2">2 (Genap)</option>
                                </select>
                            </div>
                            <div class="col-4 mb-3">
                                <label class="form-label fw-bold">Alokasi (JP):</label>
                                <input type="number" name="alokasi_waktu" id="alokasi_input" class="form-control"
                                       placeholder="4" required>
                            </div>
                        </div>

                        <!-- 4. No. Urut -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">No. Urut:</label>
                            <input type="number" name="urutan" id="urutan_input" class="form-control"
                                   value="<?= count($atp_list) + 1 ?>" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-success flex-grow-1" id="submit_btn">Simpan ATP</button>
                            <button type="button" class="btn btn-secondary" id="cancel_edit" style="display:none;">Batal</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ===== TABEL DAFTAR ATP ===== -->
        <div class="<?= $readonly ? 'col-md-12' : 'col-md-8' ?>">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">Alur Tujuan Pembelajaran</h6>
                    <a href="<?= base_url('admin/administrasi-guru/atp/print/' . $selected_class . '/' . $selected_subject) ?>"
                       target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf"></i> Cetak ATP
                    </a>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle mb-0">
                            <thead class="table-info">
                                <tr class="text-center">
                                    <th width="40">No</th>
                                    <th>Lingkup Materi</th>
                                    <th>Elemen CP</th>
                                    <th>Tujuan Pembelajaran (TP)</th>
                                    <th width="60">Smtr</th>
                                    <th width="80">Alokasi</th>
                                    <th width="80">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($atp_list)): ?>
                                    <tr><td colspan="7" class="text-center py-3">Belum ada alur yang disusun.</td></tr>
                                <?php else: ?>
                                    <?php $no = 1; foreach ($atp_list as $atp): ?>
                                        <?php
                                        $elemenList = $atp['elemen_list'] ?? [];
                                        // Fallback data lama: buat pseudo elemen dari cp_master_id di header ATP
                                        if (empty($elemenList)) {
                                            $pseudoElemen = [
                                                'id'          => 0,
                                                'cp_master_id'=> $atp['cp_master_id'] ?? '',
                                                'elemen'      => $atp['elemen'] ?? '-',
                                                'cp_deskripsi'=> $atp['cp_deskripsi'] ?? '',
                                                'fase'        => '',
                                                'tps'         => $atp['tps'] ?? [],
                                            ];
                                            $elemenList = [$pseudoElemen];
                                        }
                                        $jumlahElemen = count($elemenList) ?: 1;
                                        $firstElemen  = $elemenList[0];
                                        // Siapkan data edit
                                        $editData = [
                                            "id"             => $atp["id"],
                                            "lingkup_materi" => $atp["lingkup_materi"],
                                            "urutan"         => $atp["urutan"],
                                            "semester"       => $atp["semester"],
                                            "alokasi_waktu"  => $atp["alokasi_waktu"],
                                            "kelas"          => !empty($atp['tps']) ? ($atp['tps'][0]['kelas'] ?? '') : '',
                                            "elemen_list"    => array_map(fn($el) => [
                                                "cp_master_id" => $el["cp_master_id"],
                                                "elemen"       => $el["elemen"],
                                                "fase"         => $el["fase"] ?? '',
                                                "tps"          => $el["tps"] ?? [],
                                            ], $elemenList),
                                        ];
                                        ?>
                                        <?php foreach ($elemenList as $ei => $el): ?>
                                            <tr>
                                                <?php if ($ei === 0): ?>
                                                    <td class="text-center fw-bold align-middle" rowspan="<?= $jumlahElemen ?>"><?= $no++ ?></td>
                                                    <td class="align-middle fw-bold" rowspan="<?= $jumlahElemen ?>"><?= esc($atp['lingkup_materi']) ?></td>
                                                <?php endif; ?>

                                                <td class="align-middle fw-bold text-success"><?= esc($el['elemen']) ?></td>
                                                <td class="align-middle">
                                                    <?php if (!empty($el['tps'])): ?>
                                                        <ul class="mb-0 ps-3">
                                                            <?php foreach ($el['tps'] as $tp): ?>
                                                                <li><strong><?= esc($tp['kode_tp']) ?></strong> <?= esc($tp['deskripsi']) ?></li>
                                                            <?php endforeach; ?>
                                                        </ul>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <?php if ($ei === 0): ?>
                                                    <td class="text-center fw-bold align-middle" rowspan="<?= $jumlahElemen ?>"><?= $atp['semester'] ?></td>
                                                    <td class="text-center fw-bold align-middle" rowspan="<?= $jumlahElemen ?>"><?= esc($atp['alokasi_waktu']) ?> JP</td>
                                                    <td class="text-center align-middle" rowspan="<?= $jumlahElemen ?>">
                                                        <div class="d-flex flex-column gap-1">
                                                            <?php if (!$readonly): ?>
                                                            <button class="btn btn-sm btn-outline-primary"
                                                                    onclick='editAtp(<?= json_encode($editData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
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
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div><!-- /.row -->
    <?php endif; ?>
<?php else: ?>
    <div class="text-center py-5">
        <div class="mb-3">
            <i class="bi bi-signpost-split text-gray-300" style="font-size: 5rem;"></i>
        </div>
        <h5 class="text-gray-500">Silakan pilih mata pelajaran untuk menyusun Alur Pembelajaran.</h5>
    </div>
<?php endif; ?>

<!-- ===== TEMPLATE ELEMEN CP (hidden) ===== -->
<template id="tpl_elemen">
    <div class="elemen-block border rounded p-2 mb-3 bg-white">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="fw-bold text-success elemen-label">Elemen CP #<span class="elemen-num">1</span></span>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-elemen">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <div class="mb-2">
            <label class="form-label form-label-sm mb-1">Pilih CP (Elemen):</label>
            <select name="elemen_cp[]" class="form-select form-select-sm cp-select" required>
                <option value="">-- Pilih Elemen CP --</option>
                <?php foreach ($cp_master_list as $cp): ?>
                    <option value="<?= $cp['id'] ?>"
                            data-fase="<?= esc($cp['fase']) ?>"
                            data-elemen="<?= esc($cp['elemen'], 'attr') ?>"
                            data-desc="<?= esc($cp['deskripsi'], 'attr') ?>">
                        [<?= esc($cp['elemen']) ?>] Fase <?= esc($cp['fase']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <div class="cp-preview small text-muted mt-1 p-2 bg-light rounded border" style="display:none; max-height:80px; overflow-y:auto;"></div>
        </div>
        <div class="tp-wrapper">
            <label class="form-label form-label-sm mb-1 fw-semibold">Tujuan Pembelajaran:</label>
            <div class="tp-list">
                <!-- TP rows injected here -->
            </div>
            <button type="button" class="btn btn-sm btn-outline-primary w-100 mt-1 btn-add-tp">
                <i class="bi bi-plus-circle"></i> Tambah TP
            </button>
        </div>
    </div>
</template>

<!-- ===== TEMPLATE BARIS TP (hidden) ===== -->
<template id="tpl_tp_row">
    <div class="tp-row bg-light p-2 border rounded mb-2">
        <div class="d-flex justify-content-end mb-1">
            <button type="button" class="btn-close btn-sm btn-remove-tp" style="font-size:0.7rem;"></button>
        </div>
        <div class="row g-2">
            <div class="col-3">
                <input type="text" class="form-control form-control-sm tp-kode-input" placeholder="1.1" required>
            </div>
            <div class="col-9">
                <textarea class="form-control form-control-sm tp-deskripsi-input" rows="2" placeholder="Deskripsi TP..." required></textarea>
            </div>
        </div>
    </div>
</template>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function () {

    // ─── RENDER NAMA field TP berdasarkan indeks elemen ─────────────────────
    // reindexElemen, createTpRow, createElemenBlock didefinisikan sebagai fungsi global di bawah    // ─── TAMBAH ELEMEN BARU ───────────────────────────────────────────────
    $('#btn_add_elemen').on('click', function () {
        const $block = createElemenBlock(null, null);
        $('#elemen_container').append($block);
        reindexElemen();
    });

    // ─── HAPUS ELEMEN ─────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove-elemen', function () {
        if ($('#elemen_container .elemen-block').length <= 1) {
            alert('Minimal harus ada satu elemen CP.');
            return;
        }
        $(this).closest('.elemen-block').remove();
        reindexElemen();
    });

    // ─── TAMBAH TP ────────────────────────────────────────────────────────
    $(document).on('click', '.btn-add-tp', function () {
        const $tpList = $(this).closest('.elemen-block').find('.tp-list');
        $tpList.append(createTpRow());
        reindexElemen();
    });

    // ─── HAPUS TP ─────────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove-tp', function () {
        const $tpList = $(this).closest('.tp-list');
        if ($tpList.find('.tp-row').length <= 1) {
            alert('Minimal harus ada satu TP per elemen.');
            return;
        }
        $(this).closest('.tp-row').remove();
        reindexElemen();
    });

    // ─── PREVIEW DESKRIPSI CP ─────────────────────────────────────────────
    $(document).on('change', '.cp-select', function () {
        const $preview = $(this).closest('.elemen-block').find('.cp-preview');
        const desc = $(this).find('option:selected').data('desc');
        if (desc) {
            $preview.html(desc).show();
        } else {
            $preview.hide();
        }
    });

    // ─── BATAL EDIT ───────────────────────────────────────────────────────
    $('#cancel_edit').on('click', function () {
        resetForm();
    });

    function resetForm() {
        $('#atpForm')[0].reset();
        $('#atp_id').val('');
        $('#form_title').text('Tambah ATP Baru');
        $('#submit_btn').text('Simpan ATP');
        $('#cancel_edit').hide();
        // Reset elemen container — satu elemen kosong
        $('#elemen_container').empty();
        const $block = createElemenBlock(null, null);
        $('#elemen_container').append($block);
        reindexElemen();
    }

    // ─── INISIALISASI: satu elemen kosong ─────────────────────────────────
    (function init() {
        const $block = createElemenBlock(null, null);
        $('#elemen_container').append($block);
        reindexElemen();
    })();
});

// ─── FUNGSI GLOBAL — bisa dipanggil dari mana saja ──────────────────────────

function createTpRow(kode, deskripsi) {
    const tplNode = document.getElementById('tpl_tp_row').content.cloneNode(true);
    const $row    = $(tplNode);
    if (kode)      $row.find('.tp-kode-input').val(kode);
    if (deskripsi) $row.find('.tp-deskripsi-input').val(deskripsi);
    return $row;
}

function createElemenBlock(cpMasterId, tps) {
    const tplNode = document.getElementById('tpl_elemen').content.cloneNode(true);
    const $block  = $(tplNode);

    // Pilih CP jika ada
    if (cpMasterId) {
        $block.find('.cp-select').val(cpMasterId);
        const desc = $block.find('.cp-select option[value="' + cpMasterId + '"]').data('desc');
        if (desc) {
            $block.find('.cp-preview').html(desc).show();
        }
    }

    // Tambah TP rows
    const $tpList = $block.find('.tp-list');
    if (tps && tps.length > 0) {
        tps.forEach(function (tp) {
            $tpList.append(createTpRow(tp.kode_tp || tp.kode, tp.deskripsi));
        });
    } else {
        $tpList.append(createTpRow());
    }

    return $block;
}

// ─── FUNGSI EDIT ATP ──────────────────────────────────────────────────────────
function editAtp(data) {
    $('#atp_id').val(data.id);
    $('#lingkup_materi').val(data.lingkup_materi);
    $('#kelas_input').val(data.kelas);
    $('#semester_input').val(data.semester);
    $('#alokasi_input').val(data.alokasi_waktu);
    $('#urutan_input').val(data.urutan);
    $('#form_title').text('Edit ATP #' + data.id);
    $('#submit_btn').text('Update ATP');
    $('#cancel_edit').show();

    // Bersihkan elemen container
    $('#elemen_container').empty();

    const elemenList = data.elemen_list || [];

    if (elemenList.length === 0) {
        // Fallback: satu elemen kosong
        const $block = createElemenBlock(null, null);
        $('#elemen_container').append($block);
    } else {
        elemenList.forEach(function (el) {
            // Gunakan createElemenBlock supaya block sudah sepenuhnya siap sebelum di-append
            const $block = createElemenBlock(el.cp_master_id || null, el.tps || []);
            $('#elemen_container').append($block);

            // Set nilai select SETELAH block masuk ke DOM agar .val() bekerja
            // (createElemenBlock sudah handle ini, tapi set ulang untuk memastikan)
            if (el.cp_master_id) {
                $block.find('.cp-select').val(el.cp_master_id);
                const desc = $block.find('.cp-select option[value="' + el.cp_master_id + '"]').data('desc');
                if (desc) {
                    $block.find('.cp-preview').html(desc).show();
                }
            }
        });
    }

    // Re-index nama field setelah semua elemen ditambahkan
    reindexElemen();

    // Scroll ke form
    $('html, body').animate({ scrollTop: $('#atpForm').offset().top - 80 }, 400);
}

// Ekspor reindexElemen ke scope global agar bisa dipanggil dari editAtp
function reindexElemen() {
    $('#elemen_container .elemen-block').each(function (ei) {
        $(this).find('.elemen-num').text(ei + 1);
        $(this).find('.cp-select').attr('name', 'elemen_cp[]');
        $(this).find('.tp-kode-input').each(function () {
            $(this).attr('name', 'tp_kode[' + ei + '][]');
        });
        $(this).find('.tp-deskripsi-input').each(function () {
            $(this).attr('name', 'tp_deskripsi[' + ei + '][]');
        });
    });
}

function confirmDeleteAtp(id) {
    if (confirm('Hapus alur ini beserta semua Elemen CP dan TP-nya?')) {
        window.location.href = "<?= base_url('admin/administrasi-guru/atp/delete/') ?>" + id;
    }
}
</script>
<?= $this->endSection() ?>
