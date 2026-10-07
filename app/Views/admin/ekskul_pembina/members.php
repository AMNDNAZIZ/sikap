<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Anggota Ekskul: <?= esc($ekskul['name']) ?></h1>
            <a href="<?= base_url('admin/ekskul-pembina') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')) : ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    <?php endif; ?>

    <div class="row">
        <!-- Tambah Anggota Manual -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tambah Anggota (Manual)</h6>
                </div>
                <div class="card-body">
                    <form action="<?= base_url('admin/ekskul-pembina/storeMember/' . $ekskul['id']) ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="form-group">
                            <label>Pilih Siswa</label>
                            <select name="student_id" class="form-control select2" required>
                                <option value="">-- Cari Nama Siswa --</option>
                                <?php foreach ($allStudents as $s) : ?>
                                    <option value="<?= $s['id'] ?>">
                                        <?= esc($s['name']) ?> (<?= esc($s['class_name'] ?? 'Tanpa Kelas') ?>) - NIS: <?= esc($s['nis']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary btn-block">Tambahkan ke Ekskul</button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Daftar Anggota -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Anggota Aktif</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered datatable">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>NIS</th>
                                    <th>Nama Siswa</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($members as $i => $m) : ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><?= esc($m['nis']) ?></td>
                                        <td><?= esc($m['student_name']) ?></td>
                                        <td>
                                            <?php if ($m['status'] == 'aktif'): ?>
                                                <span class="badge bg-success text-white">Aktif</span>
                                            <?php elseif ($m['status'] == 'pending'): ?>
                                                <span class="badge bg-warning text-dark">Pending</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary text-white"><?= esc($m['status']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form action="<?= base_url('admin/ekskul-pembina/removeMember/' . $m['id']) ?>" method="post" onsubmit="return confirm('Keluarkan siswa ini dari ekstrakurikuler?');">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-user-minus"></i> Keluarkan
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        if ($('.select2').length) {
            $('.select2').select2({
                theme: 'bootstrap4',
                placeholder: "-- Cari Nama Siswa --"
            });
        }
        if ($('.datatable').length) {
            $('.datatable').DataTable();
        }
    });
</script>
<?= $this->endSection() ?>
