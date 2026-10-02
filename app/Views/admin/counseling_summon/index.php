<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3><?= $title ?></h3>
                <p class="text-subtitle text-muted">Manajemen surat panggilan orang tua / wali siswa.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Surat Panggilan</li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <section class="section">
        <?php if (session()->getFlashdata('success')) : ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Daftar Panggilan</h4>
                <a href="<?= base_url('admin/counseling-summon/create') ?>" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Buat Surat Baru</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped dataTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Siswa (Kelas)</th>
                                <th>Jadwal Panggilan</th>
                                <th>Alasan Pemanggilan</th>
                                <th>Status</th>
                                <th>Dibuat Oleh</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no=1; foreach($summons as $row): ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <strong><?= esc($row['student_name']) ?></strong><br>
                                        <small><?= esc($row['nis']) ?> | <?= esc($row['class_name']) ?></small>
                                    </td>
                                    <td>
                                        <?= date('d M Y', strtotime($row['summon_date'])) ?><br>
                                        <small><?= date('H:i', strtotime($row['summon_date'])) ?> WIB</small>
                                    </td>
                                    <td><?= esc($row['reason']) ?></td>
                                    <td>
                                        <?php if($row['status'] == 'Pending'): ?>
                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                        <?php elseif($row['status'] == 'Attended'): ?>
                                            <span class="badge bg-success">Hadir</span>
                                        <?php elseif($row['status'] == 'Ignored'): ?>
                                            <span class="badge bg-danger">Tidak Hadir (Mangkir)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($row['creator_name']) ?></td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <a href="<?= base_url('admin/counseling-summon/print-pdf/'.$row['id']) ?>" class="btn btn-sm btn-secondary" target="_blank"><i class="bi bi-printer"></i> Cetak</a>
                                            <button class="btn btn-sm btn-info" data-bs-toggle="modal" data-bs-target="#statusModal<?= $row['id'] ?>"><i class="bi bi-pencil-square"></i> Proses</button>
                                            <form action="<?= base_url('admin/counseling-summon/delete/'.$row['id']) ?>" method="post" onsubmit="return confirm('Hapus data ini?');">
                                                <?= csrf_field() ?>
                                                <button class="btn btn-sm btn-danger w-100"><i class="bi bi-trash"></i> Hapus</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                                <!-- Modal Status -->
                                <div class="modal fade" id="statusModal<?= $row['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="<?= base_url('admin/counseling-summon/updateStatus/'.$row['id']) ?>" method="post">
                                            <?= csrf_field() ?>
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Update Status Kehadiran</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label">Status Kehadiran Ortu/Wali</label>
                                                        <select name="status" class="form-select">
                                                            <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Menunggu (Belum Waktunya)</option>
                                                            <option value="Attended" <?= $row['status'] == 'Attended' ? 'selected' : '' ?>>Hadir (Memenuhi Panggilan)</option>
                                                            <option value="Ignored" <?= $row['status'] == 'Ignored' ? 'selected' : '' ?>>Tidak Hadir (Mangkir)</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-primary">Simpan</button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('.dataTable').DataTable({ responsive: true });
        }
    });
</script>
<?= $this->endSection() ?>
