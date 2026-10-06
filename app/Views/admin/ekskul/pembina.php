<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Kelola Pembina: <?= esc($ekskul['name']) ?></h1>
            <a href="<?= base_url('admin/ekskul') ?>" class="btn btn-secondary">
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
        <!-- Form Tambah Pembina -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tugaskan Pembina/Pelatih</h6>
                </div>
                <div class="card-body">
                    <?php if (!$activeYear): ?>
                        <div class="alert alert-warning">Tahun ajaran aktif belum diset.</div>
                    <?php else: ?>
                        <form action="<?= base_url('admin/ekskul/pembina/store/' . $ekskul['id']) ?>" method="post">
                            <?= csrf_field() ?>
                            <div class="form-group">
                                <label>Pilih Guru / Pelatih Luar</label>
                                <select name="user_id" class="form-control select2" required>
                                    <option value="">-- Pilih --</option>
                                    <?php foreach ($potentialPembina as $user) : ?>
                                        <option value="<?= $user['id'] ?>">
                                            <?= esc($user['fullname']) ?> 
                                            <?= $user['role_id'] != 3 ? '(Pelatih Luar)' : '(Guru)' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary btn-block">Tugaskan</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Daftar Pembina -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Pembina (TA: <?= $activeYear ? esc($activeYear['year']) : '-' ?>)</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>No</th>
                                    <th>Nama Pembina</th>
                                    <th>Username</th>
                                    <th>Tipe</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($pembinaList)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">Belum ada pembina yang ditugaskan.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($pembinaList as $i => $p) : ?>
                                        <tr>
                                            <td><?= $i + 1 ?></td>
                                            <td><?= esc($p['pembina_name']) ?></td>
                                            <td><?= esc($p['username']) ?></td>
                                            <td>
                                                <?php if ($p['is_external']): ?>
                                                    <span class="badge badge-info">Pelatih Luar</span>
                                                <?php else: ?>
                                                    <span class="badge badge-success">Guru</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <form action="<?= base_url('admin/ekskul/pembina/delete/' . $p['id']) ?>" method="post" onsubmit="return confirm('Hapus penugasan pembina ini?');">
                                                    <?= csrf_field() ?>
                                                    <button type="submit" class="btn btn-sm btn-danger">
                                                        <i class="fas fa-times"></i> Hapus
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
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
                theme: 'bootstrap4'
            });
        }
    });
</script>
<?= $this->endSection() ?>
