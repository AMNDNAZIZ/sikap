<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h1 class="h3 mb-0 text-gray-800">Edit Jurnal & Absensi</h1>
            <a href="<?= base_url('admin/ekskul-pembina/jurnal/' . $ekskul['id']) ?>" class="btn btn-secondary">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
            </a>
        </div>
    </div>

    <form action="<?= base_url('admin/ekskul-pembina/jurnal/update/' . $jurnal['id']) ?>" method="post">
        <?= csrf_field() ?>
        
        <!-- Informasi Jurnal -->
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">Detail Kegiatan: <?= esc($ekskul['name']) ?></h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Tanggal Pelaksanaan</label>
                            <input type="date" name="date" class="form-control" value="<?= esc($jurnal['date']) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="form-group">
                            <label>Materi / Kegiatan Hari Ini</label>
                            <input type="text" name="materi" class="form-control" value="<?= esc($jurnal['materi']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Absensi (Edit — tampil status saat ini) -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-primary">Presensi Kehadiran Siswa</h6>
                <small class="text-muted"><em>Siswa yang tidak tercatat ketidakhadirannya dianggap Hadir.</em></small>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="bg-light">
                            <tr>
                                <th width="5%">No</th>
                                <th width="15%">NIS</th>
                                <th width="35%">Nama Siswa</th>
                                <th width="20%">Status Kehadiran</th>
                                <th width="25%">Keterangan Tambahan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($members)): ?>
                                <tr>
                                    <td colspan="5" class="text-center">Belum ada anggota di ekstrakurikuler ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($members as $i => $m) : 
                                    $currentStatus = $attMap[$m['student_id']] ?? 'hadir';
                                    $currentNotes  = $notesMap[$m['student_id']] ?? '';
                                    $notesDisabled = ($currentStatus === 'hadir') ? 'disabled' : '';
                                ?>
                                    <tr>
                                        <td class="text-center align-middle"><?= $i + 1 ?></td>
                                        <td class="align-middle"><?= esc($m['nis']) ?></td>
                                        <td class="align-middle"><?= esc($m['student_name']) ?></td>
                                        <td>
                                            <select name="attendances[<?= $m['student_id'] ?>]" class="form-control form-control-sm status-select">
                                                <option value="hadir"   <?= $currentStatus == 'hadir'  ? 'selected' : '' ?>>Hadir (Default)</option>
                                                <option value="sakit"   <?= $currentStatus == 'sakit'  ? 'selected' : '' ?>>Sakit</option>
                                                <option value="izin"    <?= $currentStatus == 'izin'   ? 'selected' : '' ?>>Izin</option>
                                                <option value="alfa"    <?= $currentStatus == 'alfa'   ? 'selected' : '' ?>>Alfa (Tanpa Keterangan)</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="notes[<?= $m['student_id'] ?>]"
                                                class="form-control form-control-sm notes-input"
                                                value="<?= esc($currentNotes) ?>"
                                                placeholder="Isi jika perlu"
                                                <?= $notesDisabled ?>>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <div class="mt-4 text-right">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Simpan Perubahan
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
        // Enable notes input only if status is not "hadir"
        $('.status-select').on('change', function() {
            var val = $(this).val();
            var inputNotes = $(this).closest('tr').find('.notes-input');
            if(val !== 'hadir') {
                inputNotes.prop('disabled', false);
            } else {
                inputNotes.prop('disabled', true);
                inputNotes.val('');
            }
        });
    });
</script>
<?= $this->endSection() ?>
