<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3>Buat Surat Panggilan Baru</h3>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/counseling-summon') ?>">Surat Panggilan</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Buat Baru</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Formulir Pemanggilan Orang Tua / Wali</h4>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/counseling-summon/store') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Siswa yang Bersangkutan <span class="text-danger">*</span></label>
                            <select name="student_id" class="form-select select2" required>
                                <option value="">-- Pilih Siswa --</option>
                                <?php foreach($students as $s): ?>
                                    <option value="<?= $s['id'] ?>"><?= esc($s['name']) ?> (<?= esc($s['class_name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Jadwal Menghadap <span class="text-danger">*</span></label>
                            <input type="date" name="summon_date" class="form-control" required>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Tingkat SP <span class="text-danger">*</span></label>
                            <select name="level" class="form-select" required>
                                <option value="SP1">SP1 (Surat Peringatan 1)</option>
                                <option value="SP2">SP2 (Surat Peringatan 2)</option>
                                <option value="SP3">SP3 (Surat Peringatan 3)</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">Alasan Pemanggilan <span class="text-danger">*</span></label>
                            <textarea name="reason" class="form-control" rows="3" required placeholder="Contoh: Terlalu sering bolos sekolah / Terlibat perkelahian / dll."></textarea>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <a href="<?= base_url('admin/counseling-summon') ?>" class="btn btn-secondary me-2">Batal</a>
                        <button type="submit" class="btn btn-primary">Simpan & Buat Surat</button>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>
<?= $this->endSection() ?>
