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

    <?php if ($ekskul['category'] === 'wajib'): ?>
        <div class="alert alert-info border-left-info shadow-sm mb-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div class="d-flex align-items-center mb-2 mb-md-0">
                <i class="fas fa-info-circle fa-2x text-info mr-3 me-3"></i>
                <div>
                    <h6 class="font-weight-bold mb-1 text-dark">Ekstrakurikuler Wajib: Seluruh Siswa Aktif Otomatis Terdaftar</h6>
                    <p class="mb-0 text-muted small">
                        Karena <strong><?= esc($ekskul['name']) ?></strong> bersifat wajib, seluruh siswa aktif pada Tahun Ajaran <?= esc($activeYear['year']) ?> secara otomatis terdaftar sebagai anggota. Pembina tidak perlu memasukkan siswa satu per satu.
                    </p>
                </div>
            </div>
            <div>
                <form action="<?= base_url('admin/ekskul-pembina/syncMembers/' . $ekskul['id']) ?>" method="post">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-sm btn-primary text-nowrap">
                        <i class="fas fa-sync-alt fa-sm mr-1 me-1"></i> Sinkronkan Siswa
                    </button>
                </form>
            </div>
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
                    <?php if ($ekskul['category'] === 'wajib'): ?>
                        <div class="alert alert-light border small text-muted mb-3">
                            <i class="fas fa-check-circle text-success mr-1 me-1"></i> Semua siswa aktif otomatis terdaftar. Form ini hanya digunakan jika ada siswa khusus yang belum tersinkron.
                        </div>
                    <?php endif; ?>
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
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Anggota & Pendaftar</h6>
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
                                            <div class="d-flex align-items-center">
                                                <?php if ($m['status'] == 'pending'): ?>
                                                    <form action="<?= base_url('admin/ekskul-pembina/approveMember/' . $m['id']) ?>" method="post" class="d-inline mr-1 me-1">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-success" title="Setujui Pendaftaran">
                                                            <i class="fas fa-check"></i> Setujui
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                <?php if ($ekskul['category'] === 'wajib'): ?>
                                                    <span class="badge bg-light text-secondary border px-2 py-1" title="Anggota wajib, tidak dapat dikeluarkan">
                                                        <i class="fas fa-lock fa-xs me-1 mr-1"></i> Wajib
                                                    </span>
                                                <?php else: ?>
                                                    <form action="<?= base_url('admin/ekskul-pembina/removeMember/' . $m['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('<?= $m['status'] == 'pending' ? 'Tolak pendaftaran siswa ini?' : 'Keluarkan siswa ini dari ekstrakurikuler?' ?>');">
                                                        <?= csrf_field() ?>
                                                        <button type="submit" class="btn btn-sm btn-danger" title="<?= $m['status'] == 'pending' ? 'Tolak Pendaftaran' : 'Keluarkan Anggota' ?>">
                                                            <i class="fas fa-<?= $m['status'] == 'pending' ? 'times' : 'user-minus' ?>"></i> <?= $m['status'] == 'pending' ? 'Tolak' : 'Keluarkan' ?>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
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
