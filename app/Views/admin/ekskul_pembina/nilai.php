<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Penilaian Akhir: <?= esc($ekskul['name']) ?></h1>
            <a href="<?= base_url('admin/ekskul-pembina') ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>
    </div>

    <?php if (session()->getFlashdata('success')) : ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Pilih Semester -->
    <div class="mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <a href="<?= base_url('admin/ekskul-pembina/nilai/' . $ekskul['id'] . '/1') ?>"
               class="btn <?= $semester == '1' ? 'btn-primary' : 'btn-outline-primary' ?>">
                📋 Semester 1 (Ganjil)
            </a>
            <a href="<?= base_url('admin/ekskul-pembina/nilai/' . $ekskul['id'] . '/2') ?>"
               class="btn <?= $semester == '2' ? 'btn-primary' : 'btn-outline-primary' ?> ms-2">
                📋 Semester 2 (Genap)
            </a>
        </div>
        <a href="<?= base_url('admin/ekskul-pembina/nilai/print/' . $ekskul['id'] . '/' . $semester) ?>"
           target="_blank"
           class="btn btn-success">
            <i class="fas fa-print"></i> Cetak Nilai
        </a>
    </div>

    <form action="<?= base_url('admin/ekskul-pembina/nilai/store/' . $ekskul['id']) ?>" method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="semester" value="<?= esc($semester) ?>">
        
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Input Nilai Semester <?= $semester == '1' ? '1 (Ganjil)' : '2 (Genap)' ?> — TA <?= esc($activeYear['year']) ?></h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm datatable">
                        <thead class="bg-light">
                            <tr>
                                <th width="4%">No</th>
                                <th width="14%">NIS</th>
                                <th width="20%">Nama Siswa</th>
                                <th width="14%">Kelas</th>
                                <th width="18%">Predikat</th>
                                <th width="30%">Keterangan Tambahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($members)): ?>
                                <?php foreach ($members as $i => $m) : 
                                    $s = $scores[$m['student_id']] ?? null;
                                    $pred = $s['predicate'] ?? '';
                                    $desc = $s['description'] ?? '';
                                ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= $i + 1 ?></td>
                                        <td class="align-middle"><?= esc($m['nis']) ?></td>
                                        <td class="align-middle"><?= esc($m['student_name']) ?></td>
                                        <td class="align-middle"><?= esc($m['class_name'] ?? '-') ?></td>
                                        <td>
                                            <select name="predicate[<?= $m['student_id'] ?>]" class="form-control form-control-sm">
                                                <option value="">-- Pilih --</option>
                                                <option value="Sangat Baik" <?= $pred == 'Sangat Baik' ? 'selected' : '' ?>>Sangat Baik</option>
                                                <option value="Baik" <?= $pred == 'Baik' ? 'selected' : '' ?>>Baik</option>
                                                <option value="Cukup" <?= $pred == 'Cukup' ? 'selected' : '' ?>>Cukup</option>
                                                <option value="Kurang" <?= $pred == 'Kurang' ? 'selected' : '' ?>>Kurang</option>
                                            </select>
                                        </td>
                                        <td>
                                            <textarea name="description[<?= $m['student_id'] ?>]" class="form-control form-control-sm" rows="1" placeholder="Contoh: Sangat aktif dalam kegiatan..."><?= esc($desc) ?></textarea>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-right">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Simpan Nilai
                    </button>
                </div>
            </div>
        </div>
        
    </form>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    $(document).ready(function() {
        if ($('.datatable').length) {
            $('.datatable').DataTable({
                paging: false,
                ordering: false,
                info: false
            });
        }
    });
</script>
<?= $this->endSection() ?>
