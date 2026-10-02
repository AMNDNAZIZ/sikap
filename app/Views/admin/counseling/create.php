<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3><?= $title ?></h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/counseling') ?>">Jurnal Konseling</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Tambah</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Form Jurnal Bimbingan Konseling</h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/counseling/store') ?>" method="post">
                    <?= csrf_field() ?>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="student_id" class="form-label">Nama Siswa <span class="text-danger">*</span></label>
                                <select name="student_id" id="student_id" class="form-select select2" required>
                                    <option value="">Pilih Siswa</option>
                                    <?php foreach ($students as $s): ?>
                                        <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> (<?= esc($s['class_name']) ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label for="session_date" class="form-label">Tanggal Konseling <span class="text-danger">*</span></label>
                                <input type="date" name="session_date" id="session_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                            </div>

                            <div class="form-group mb-3">
                                <label for="counseling_type" class="form-label">Jenis Bimbingan <span class="text-danger">*</span></label>
                                <select name="counseling_type" id="counseling_type" class="form-select" required>
                                    <?php foreach (bk_counseling_types() as $value => $label): ?>
                                        <option value="<?= $value ?>"><?= $label ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group mb-3">
                                <label class="form-label d-block">Sifat Kerahasiaan</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_confidential" id="rahasia1" value="1" checked>
                                    <label class="form-check-label" for="rahasia1">Sangat Rahasia</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_confidential" id="rahasia0" value="0">
                                    <label class="form-check-label" for="rahasia0">Biasa / Terbuka</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="problem_description" class="form-label">Deskripsi Masalah / Keluhan <span class="text-danger">*</span></label>
                                <textarea name="problem_description" id="problem_description" rows="3" class="form-control" required></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="diagnosis" class="form-label">Diagnosa (Akar Masalah)</label>
                                <textarea name="diagnosis" id="diagnosis" rows="2" class="form-control"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="treatment" class="form-label">Penanganan (Treatment)</label>
                                <textarea name="treatment" id="treatment" rows="2" class="form-control"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="follow_up" class="form-label">Tindak Lanjut (Follow Up)</label>
                                <textarea name="follow_up" id="follow_up" rows="2" class="form-control"></textarea>
                            </div>

                            <div class="form-group mb-3">
                                <label for="status" class="form-label">Status Kasus</label>
                                <select name="status" id="status" class="form-select">
                                    <option value="Open">Masih Berjalan (Open)</option>
                                    <option value="Closed">Selesai (Closed)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-3">
                        <div class="col-12 text-end">
                            <a href="<?= base_url('admin/counseling') ?>" class="btn btn-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">Simpan Jurnal</button>
                        </div>
                    </div>

                </form>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
