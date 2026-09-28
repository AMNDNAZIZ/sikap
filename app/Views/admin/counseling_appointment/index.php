<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-6 order-md-1 order-last">
                <h3><?= $title ?></h3>
                <p class="text-subtitle text-muted">Kelola pengajuan janji temu konseling dari siswa.</p>
            </div>
            <div class="col-12 col-md-6 order-md-2 order-first">
                <nav aria-label="breadcrumb" class="breadcrumb-header float-start float-lg-end">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?= base_url('admin/dashboard') ?>">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Janji Temu Konseling</li>
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
        <?php if (session()->getFlashdata('error')) : ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= session()->getFlashdata('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover dataTable">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Tanggal Pengajuan</th>
                                <th>Siswa (Kelas)</th>
                                <th>Topik/Keluhan Singkat</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; foreach ($appointments as $row) : ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td>
                                        <?= date('d M Y', strtotime($row['requested_date'])) ?>
                                        <br><small class="text-muted"><?= $row['requested_time'] ? date('H:i', strtotime($row['requested_time'])) . ' WIB' : '-' ?></small>
                                    </td>
                                    <td>
                                        <strong><?= esc($row['student_name']) ?></strong><br>
                                        <small><?= esc($row['nis'] ?? '-') ?> | <?= esc($row['class_name'] ?? '-') ?></small>
                                    </td>
                                    <td>
                                        <?= esc($row['topic']) ?>
                                    </td>
                                    <td>
                                        <?php if ($row['status'] == 'Pending'): ?>
                                            <span class="badge bg-warning text-dark">Menunggu</span>
                                        <?php elseif ($row['status'] == 'Approved'): ?>
                                            <span class="badge bg-info">Disetujui</span>
                                        <?php elseif ($row['status'] == 'Rejected'): ?>
                                            <span class="badge bg-danger">Ditolak</span>
                                        <?php elseif ($row['status'] == 'Completed'): ?>
                                            <span class="badge bg-success">Selesai</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-primary mb-1" data-bs-toggle="modal" data-bs-target="#statusModal<?= $row['id'] ?>">Proses</button>
                                        <form action="<?= base_url('admin/counseling-appointment/delete/' . $row['id']) ?>" method="post" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus janji temu ini?');">
                                            <?= csrf_field() ?>
                                            <button type="submit" class="btn btn-sm btn-danger mb-1"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </td>
                                </tr>

                                <!-- Modal Proses Status -->
                                <div class="modal fade" id="statusModal<?= $row['id'] ?>" tabindex="-1" aria-labelledby="statusModalLabel<?= $row['id'] ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <form action="<?= base_url('admin/counseling-appointment/updateStatus/' . $row['id']) ?>" method="post">
                                            <?= csrf_field() ?>
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="statusModalLabel<?= $row['id'] ?>">Proses Janji Temu</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Topik / Keluhan</label>
                                                        <p class="mb-0 bg-light p-2 rounded border"><?= esc($row['topic']) ?></p>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Tanggal Janji Temu</label>
                                                        <input type="datetime-local" name="proposed_date" class="form-control" value="<?= $row['requested_date'] && $row['requested_time'] ? date('Y-m-d\TH:i', strtotime($row['requested_date'] . ' ' . $row['requested_time'])) : '' ?>">
                                                        <small class="text-muted">Ubah jika Anda ingin menjadwalkan ulang.</small>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Status</label>
                                                        <select name="status" class="form-select">
                                                            <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Menunggu</option>
                                                            <option value="Approved" <?= $row['status'] == 'Approved' ? 'selected' : '' ?>>Disetujui (Jadwalkan)</option>
                                                            <option value="Rejected" <?= $row['status'] == 'Rejected' ? 'selected' : '' ?>>Ditolak</option>
                                                            <option value="Completed" <?= $row['status'] == 'Completed' ? 'selected' : '' ?>>Selesai</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
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

<!-- DataTables Initialization -->
<script>
    $(document).ready(function() {
        if ($.fn.DataTable) {
            $('.dataTable').DataTable({
                responsive: true,
                language: {
                    search: "Pencarian:",
                    lengthMenu: "Tampilkan _MENU_ data per halaman",
                    zeroRecords: "Data tidak ditemukan",
                    info: "Menampilkan halaman _PAGE_ dari _PAGES_",
                    infoEmpty: "Tidak ada data yang tersedia",
                    infoFiltered: "(difilter dari _MAX_ total data)",
                    paginate: {
                        first: "Pertama",
                        last: "Terakhir",
                        next: "Lanjut",
                        previous: "Kembali"
                    }
                }
            });
        }
    });
</script>
<?= $this->endSection() ?>
