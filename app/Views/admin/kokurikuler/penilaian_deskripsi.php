<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h5 class="mb-0"><?= $title ?></h5>
            <small class="text-muted">
                <?= esc($document['year_name']) ?> - Semester <?= esc($document['semester']) ?> |
                Fase <?= esc($document['fase']) ?> - Kelas <?= esc($document['level_kelas']) ?>
            </small>
        </div>
        <div class="d-flex gap-2">
            <a href="<?= base_url('admin/kokurikuler/penilaian/cetak/' . $document['id']) ?>"
               class="btn btn-success btn-sm" target="_blank">
                <i class="bi bi-printer"></i> Cetak
            </a>
            <a href="<?= base_url('admin/kokurikuler/penilaian') ?>" class="btn btn-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
        </div>
    </div>

    <!-- Info Dokumen -->
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-6">
                    <strong>Tema:</strong> <?= esc($document['tema']) ?><br>
                    <strong>Kegiatan:</strong> <?= esc($document['bentuk_kegiatan_konkret'] ?: '-') ?>
                </div>
                <div class="col-md-6">
                    <strong>Dimensi yang Dinilai:</strong><br>
                    <?php foreach ($rubrik as $r): ?>
                        <span class="badge bg-primary me-1"><?= esc($r['dimensi_profil']) ?> - <?= esc($r['sub_dimensi']) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Deskripsi per Siswa -->
    <div class="card shadow-sm">
        <div class="card-header">
            <strong><i class="bi bi-file-text"></i> Deskripsi Penilaian Siswa</strong>
            <span class="badge bg-secondary ms-2"><?= count($students) ?> siswa</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th width="4%">No</th>
                            <th width="10%">NIS</th>
                            <th width="20%">Nama</th>
                            <?php foreach ($rubrik as $r): ?>
                                <th width="8%" class="text-center"><?= esc($r['dimensi_profil']) ?><br><small class="text-muted"><?= esc($r['sub_dimensi']) ?></small></th>
                            <?php endforeach; ?>
                            <th>Deskripsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($students as $i => $student): ?>
                            <?php
                            $capaianBadge = ['Berkembang' => 'warning', 'Cakap' => 'primary', 'Mahir' => 'success'];
                            $capaianIcon  = ['Berkembang' => '🟡', 'Cakap' => '🔵', 'Mahir' => '🟢'];
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($student['nis']) ?></td>
                                <td><?= esc($student['name']) ?></td>
                                <?php foreach ($rubrik as $r): ?>
                                    <?php
                                    $capaian = $student['penilaian_detail'][(string)$r['id']] ?? '-';
                                    $badge = $capaianBadge[$capaian] ?? 'secondary';
                                    $icon  = $capaianIcon[$capaian] ?? '';
                                    ?>
                                    <td class="text-center">
                                        <span class="badge bg-<?= $badge ?>"><?= $icon ?> <?= esc($capaian) ?></span>
                                    </td>
                                <?php endforeach; ?>
                                <td><small><?= esc($student['deskripsi']) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
