<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex align-items-center">
            <div class="me-3">
                <div class="icon bg-light rounded-circle p-3">
                    <i class="bi bi-calculator fs-3 text-primary"></i>
                </div>
            </div>
            <div>
                <h5 class="mb-1">Konversi & Import Nilai CBT</h5>
                <p class="mb-0 text-muted">Konversi nilai CBT ESPATA ke rapor, atau import nilai dari CBT Mandiri.</p>
            </div>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
    <?php endif; ?>

    <!-- Tab Nav -->
    <ul class="nav nav-tabs mb-4" id="convertTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= (!isset($_GET['tab']) || $_GET['tab'] !== 'mandiri') ? 'active' : '' ?>"
                    id="tab-konversi" data-bs-toggle="tab" data-bs-target="#panel-konversi"
                    type="button" role="tab">
                <i class="bi bi-calculator me-1"></i> Konversi Nilai CBT
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link <?= (isset($_GET['tab']) && $_GET['tab'] === 'mandiri') ? 'active' : '' ?>"
                    id="tab-mandiri" data-bs-toggle="tab" data-bs-target="#panel-mandiri"
                    type="button" role="tab">
                <i class="bi bi-box-arrow-in-down me-1"></i> Import dari Aplikasi CBTku
            </button>
        </li>
    </ul>

    <div class="tab-content" id="convertTabContent">

        <!-- ═══ TAB 1: KONVERSI NILAI CBT (existing) ═══ -->
        <div class="tab-pane fade <?= (!isset($_GET['tab']) || $_GET['tab'] !== 'mandiri') ? 'show active' : '' ?>"
             id="panel-konversi" role="tabpanel">
    <div class="row">
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header bg-white">
                    <h6 class="mb-0 fw-bold">1. Tentukan Nilai yang akan dikonversi</h6>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('admin/cbt/convertnilai/preview') ?>" method="post">
                        <?= csrf_field() ?>

                        <div class="mb-3">
                            <label class="form-label">Pilih Bank Soal</label>
                            <select name="bank_id" class="form-select select2" required>
                                <option value="">-- Pilih Bank Soal --</option>
                                <?php foreach ($banks as $b): ?>
                                    <option value="<?= $b['id'] ?>">
                                        <?= esc($b['code']) ?> -
                                        <?= esc($b['subject_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Pilih Kelas</label>
                            <select name="class_id" class="form-select select2" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>">
                                        <?= esc($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <hr>
                        <h6 class="fw-bold mb-3">Target Konversi</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nilai Terbesar (YA)</label>
                                <input type="number" name="ya" class="form-control" value="100" min="0" max="100"
                                    required>
                                <div class="form-text">Nilai maksimal yang diinginkan</div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Nilai Terkecil (YB)</label>
                                <input type="number" name="yb" class="form-control" value="75" min="0" max="100"
                                    required>
                                <div class="form-text">Nilai minimal yang diinginkan</div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-arrow-right-circle"></i> Generate Daftar Nilai
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card h-100 bg-light border-0">
                <div class="card-body">
                    <h6><i class="bi bi-info-circle"></i> Cara Kerja Konversi</h6>
                    <p class="small text-muted">
                        Sistem akan mengambil nilai asli (CBT) dari semua siswa dalam kelas yang dipilih, kemudian
                        melakukan penskalaan linear menggunakan rumus:
                    </p>
                    <div class="bg-white p-3 rounded text-center font-monospace small border mb-3">
                        ((YA - YB) / (XA - XB)) x (NX - XB) + YB
                    </div>
                    <ul class="small text-muted ps-3">
                        <li><strong>YA</strong>: Nilai Tertinggi yang Anda targetkan.</li>
                        <li><strong>YB</strong>: Nilai Terendah yang Anda targetkan.</li>
                        <li><strong>XA</strong>: Nilai Asli Tertinggi di kelas tersebut.</li>
                        <li><strong>XB</strong>: Nilai Asli Terendah di kelas tersebut.</li>
                        <li><strong>NX</strong>: Nilai Asli Siswa saat ini.</li>
                    </ul>
                    <div class="alert alert-warning small mb-0">
                        <i class="bi bi-exclamation-triangle"></i> Pastikan ujian sudah selesai dilakukan agar data
                        nilai yang ditarik adalah data final.
                    </div>
                </div>
            </div>
        </div>
    </div>
        </div><!-- /panel-konversi -->

        <!-- ═══ TAB 2: IMPORT DARI CBT MANDIRI ═══ -->
        <div class="tab-pane fade <?= (isset($_GET['tab']) && $_GET['tab'] === 'mandiri') ? 'show active' : '' ?>"
             id="panel-mandiri" role="tabpanel">
            <div class="row">
                <div class="col-md-7">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white fw-bold">
                            <i class="bi bi-box-arrow-in-down me-2 text-primary"></i>
                            Upload File Export dari CBTku
                        </div>
                        <div class="card-body">
                            <form action="<?= site_url('admin/cbt/import-mandiri/preview') ?>"
                                  method="post" enctype="multipart/form-data">
                                <?= csrf_field() ?>

                                <!-- Upload file JSON -->
                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        File JSON Export CBTku
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="file" name="nilai_file" class="form-control"
                                           accept=".json" required>
                                    <div class="form-text">
                                        File diperoleh dari CBTku : menu
                                        <strong>Aktivitas → Unduh Nilai → Export JSON untuk ESPATA</strong>
                                    </div>
                                </div>

                                <!-- Parameter konversi -->
                                <div class="card bg-light border-0 mb-4 p-3">
                                    <h6 class="fw-bold mb-3">Parameter Konversi (opsional)</h6>
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <label class="form-label small">Nilai Terbesar Target (YA)</label>
                                            <input type="number" name="ya" class="form-control form-control-sm"
                                                   value="100" min="0" max="100">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small">Nilai Terkecil Target (YB)</label>
                                            <input type="number" name="yb" class="form-control form-control-sm"
                                                   value="75" min="0" max="100">
                                        </div>
                                    </div>
                                    <div class="form-text mt-2">
                                        Biarkan YA=100, YB=0 untuk menyimpan nilai apa adanya tanpa konversi.
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="bi bi-arrow-right-circle me-1"></i>
                                    Proses File &amp; Tampilkan Preview
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-md-5">
                    <div class="card bg-light border-0 h-100">
                        <div class="card-body">
                            <h6 class="fw-bold"><i class="bi bi-question-circle me-1"></i> Cara Menggunakan</h6>
                            <ol class="small text-muted ps-3">
                                <li class="mb-2">
                                    Buka aplikasi <strong>CBTku</strong>, masuk ke menu
                                    <strong>Aktivitas</strong>.
                                </li>
                                <li class="mb-2">
                                    Klik tombol <strong>Unduh Nilai</strong> pada ujian yang sudah selesai,
                                    lalu pilih <strong>"Export JSON untuk ESPATA"</strong>.
                                </li>
                                <li class="mb-2">
                                    Upload file <code>.json</code> hasil export di form ini.
                                </li>
                                <li class="mb-2">
                                    Sistem akan <strong>mencocokkan siswa via NIS</strong>,
                                    menampilkan preview nilai, dan meminta Anda memilih
                                    <strong>mapel tujuan</strong> di ESPATA serta
                                    <strong>tipe penyimpanan</strong>
                                    (Formatif / Sumatif / PTS / Final).
                                </li>
                                <li>Klik <strong>Simpan</strong> untuk menyimpan ke rapor.</li>
                            </ol>
                            <div class="alert alert-warning small mb-0">
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Siswa yang NIS-nya tidak ditemukan di ESPATA akan
                                <strong>dilewati</strong> (tidak disimpan).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div><!-- /panel-mandiri -->

    </div><!-- /tab-content -->
</div>

<?= $this->endSection() ?>