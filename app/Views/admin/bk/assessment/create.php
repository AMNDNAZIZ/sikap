<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">📝 Buat Instrumen Asesmen</h1>
        <a href="<?= base_url('admin/bk/pemetaan') ?>" class="btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Informasi Instrumen</h6>
        </div>
        <div class="card-body">
            <form action="<?= base_url('admin/bk/pemetaan/instrumen/store') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Judul Instrumen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="title" required placeholder="Contoh: AKPD SMP Kelas 7, Asesmen Minat Bakat">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3" placeholder="Jelaskan tujuan dan cara pengisian asesmen ini..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Tujuan Asesmen</label>
                            <textarea class="form-control" name="purpose" rows="2" placeholder="Tujuan asesmen..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Jenis Asesmen <span class="text-danger">*</span></label>
                            <select class="form-select" name="assessment_type" required>
                                <option value="AKPD">AKPD (Angket Kebutuhan Peserta Didik)</option>
                                <option value="Asesmen Minat">Asesmen Minat & Bakat</option>
                                <option value="Asesmen Pribadi">Asesmen Pribadi</option>
                                <option value="Asesmen Sosial">Asesmen Sosial</option>
                                <option value="Asesmen Belajar">Asesmen Belajar</option>
                                <option value="Asesmen Karir">Asesmen Karir</option>
                                <option value="Custom">Custom / Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Jenjang Sasaran</label>
                            <select class="form-select" name="target_level">
                                <option value="All">Semua Jenjang</option>
                                <option value="SD">SD/Sederajat</option>
                                <option value="SMP">SMP/Sederajat</option>
                                <option value="SMA">SMA/SMK/Sederajat</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Format Skala Default</label>
                            <select class="form-select" name="default_scale_type">
                                <option value="kebutuhan">Kebutuhan (Sangat Membutuhkan - Tidak)</option>
                                <option value="kesesuaian">Kesesuaian (Sangat Sesuai - Tidak Sesuai)</option>
                                <option value="frekuensi">Frekuensi (Selalu - Tidak Pernah)</option>
                                <option value="custom">Custom Scale</option>
                            </select>
                        </div>
                    </div>
                </div>
                <hr>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan & Lanjut Konfigurasi</button>
            </form>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
