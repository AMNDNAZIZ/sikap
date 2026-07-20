<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="mb-0">Detail Dokumen Kokurikuler</h4>
            <small class="text-muted"><?= esc($document['tema']) ?></small>
        </div>
        <div>
            <a href="<?= base_url('admin/kokurikuler') ?>" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
            </a>
            <?php if ($document['status'] === 'completed'): ?>
                <a href="<?= base_url('admin/kokurikuler/export-pdf/' . $document['id']) ?>" class="btn btn-success">
                    <i class="bi bi-file-pdf"></i> Export PDF
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Status Badge -->
    <div class="alert <?= $document['status'] === 'completed' ? 'alert-success' : 'alert-warning' ?>">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <strong>Status:</strong> 
                <?= $document['status'] === 'completed' ? 'Dokumen Selesai' : 'Draft - Belum Generate AI' ?>
            </div>
            <?php if ($document['status'] === 'draft'): ?>
                <button type="button" class="btn btn-primary" id="btn_generate_ai">
                    <i class="bi bi-magic"></i> Generate dengan AI
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Informasi Dasar -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Informasi Dasar</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Tahun Ajaran</th>
                            <td>: <?= esc($document['year_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Fase</th>
                            <td>: Fase <?= esc($document['fase']) ?></td>
                        </tr>
                        <tr>
                            <th>Level Kelas</th>
                            <td>: <?= esc($document['level_kelas']) ?></td>
                        </tr>
                        <tr>
                            <th>Jumlah Pertemuan</th>
                            <td>: <?= esc($document['jumlah_pertemuan']) ?> pertemuan</td>
                        </tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <table class="table table-borderless">
                        <tr>
                            <th width="200">Tema</th>
                            <td>: <?= esc($document['tema']) ?></td>
                        </tr>
                        <tr>
                            <th>Jenis Kokurikuler</th>
                            <td>: 
                                <?php
                                $jenis = [
                                    'lintas_disiplin' => 'Kolaboratif Lintas Disiplin Ilmu',
                                    '7kaih' => 'Melalui 7 KAIH',
                                    'lainnya' => 'Kegiatan Lainnya'
                                ];
                                $jenisValue = $document['jenis_kokurikuler'] ?? $document['bentuk_kegiatan'] ?? '';
                                echo $jenis[$jenisValue] ?? $jenisValue;
                                ?>
                            </td>
                        </tr>
                        <?php if (!empty($document['bentuk_kegiatan_konkret'])): ?>
                        <tr>
                            <th>Bentuk Kegiatan</th>
                            <td>: <?= esc($document['bentuk_kegiatan_konkret']) ?></td>
                        </tr>
                        <?php endif; ?>
                        <tr>
                            <th>Dibuat Oleh</th>
                            <td>: <?= esc($document['creator_name']) ?></td>
                        </tr>
                        <tr>
                            <th>Tanggal Dibuat</th>
                            <td>: <?= date('d/m/Y H:i', strtotime($document['created_at'])) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Dimensi Profil Lulusan -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0">Dimensi Profil Lulusan</h5>
        </div>
        <div class="card-body">
            <?php 
            $dimensi = json_decode($document['dimensi_profil'], true);
            if ($dimensi && is_array($dimensi)):
            ?>
                <ul class="list-group">
                    <?php foreach ($dimensi as $d): ?>
                        <li class="list-group-item">
                            <i class="bi bi-check-circle-fill text-success"></i> <?= esc($d) ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    </div>

    <!-- Detail Kegiatan -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning text-dark">
            <h5 class="mb-0">Detail Kegiatan</h5>
        </div>
        <div class="card-body">
            <?php 
            $jenisKokurikuler = $document['jenis_kokurikuler'] ?? $document['bentuk_kegiatan'] ?? '';
            ?>
            <?php if ($jenisKokurikuler === 'lintas_disiplin'): ?>
                <?php 
                $detail = json_decode($document['kegiatan_detail'], true);
                if ($detail && isset($detail['items']) && is_array($detail['items'])):
                    // Group items by subject_id
                    $groupedBySubject = [];
                    foreach ($detail['items'] as $item) {
                        $subjectId = $item['subject_id'];
                        if (!isset($groupedBySubject[$subjectId])) {
                            $groupedBySubject[$subjectId] = [];
                        }
                        $groupedBySubject[$subjectId][] = $item;
                    }
                ?>
                    <h6>Mata Pelajaran yang Terlibat:</h6>
                    <div class="list-group">
                        <?php foreach ($groupedBySubject as $subjectId => $items): ?>
                            <div class="list-group-item">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-book-fill text-primary me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-2"><?= isset($subjectNames[$subjectId]) ? esc($subjectNames[$subjectId]) : 'Mata Pelajaran ID: ' . $subjectId ?></h6>
                                        <?php if (isset($subjectTPs[$subjectId]) && !empty($subjectTPs[$subjectId])): ?>
                                            <small class="text-muted d-block mb-1"><strong>Tujuan Pembelajaran:</strong></small>
                                            <ul class="mb-0 small">
                                                <?php foreach ($subjectTPs[$subjectId] as $tp): ?>
                                                    <li>
                                                        <?php if (!empty($tp['kode_tp'])): ?>
                                                            <strong><?= esc($tp['kode_tp']) ?>.</strong> 
                                                        <?php endif; ?>
                                                        <?= esc($tp['deskripsi']) ?>
                                                        <?php if (!empty($tp['dimensi_profil'])): ?>
                                                            <br><em class="text-muted" style="font-size: 0.85em;">
                                                                <i class="bi bi-star-fill"></i> Dimensi: <?= esc($tp['dimensi_profil']) ?>
                                                                <?php if (!empty($tp['sub_dimensi'])): ?>
                                                                    <br><i class="bi bi-arrow-return-right"></i> Sub Dimensi: <?= esc($tp['sub_dimensi']) ?>
                                                                <?php endif; ?>
                                                            </em>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php elseif ($detail && isset($detail['subjects'])): ?>
                    <!-- Old format fallback -->
                    <h6>Mata Pelajaran yang Terlibat:</h6>
                    <div class="list-group">
                        <?php foreach ($detail['subjects'] as $subjectId): ?>
                            <div class="list-group-item">
                                <div class="d-flex align-items-start">
                                    <i class="bi bi-book-fill text-primary me-2 mt-1"></i>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-2"><?= isset($subjectNames[$subjectId]) ? esc($subjectNames[$subjectId]) : 'Mata Pelajaran ID: ' . $subjectId ?></h6>
                                        <?php if (isset($subjectTPs[$subjectId]) && !empty($subjectTPs[$subjectId])): ?>
                                            <small class="text-muted d-block mb-1"><strong>Tujuan Pembelajaran:</strong></small>
                                            <ul class="mb-0 small">
                                                <?php foreach ($subjectTPs[$subjectId] as $tp): ?>
                                                    <li>
                                                        <?php if (!empty($tp['kode_tp'])): ?>
                                                            <strong><?= esc($tp['kode_tp']) ?>.</strong> 
                                                        <?php endif; ?>
                                                        <?= esc($tp['deskripsi']) ?>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php elseif ($jenisKokurikuler === '7kaih'): ?>
                <?php 
                $detail = json_decode($document['kegiatan_detail'], true);
                if ($detail && isset($detail['items']) && is_array($detail['items'])):
                ?>
                    <h6>Kegiatan 7 KAIH:</h6>
                    <ul>
                        <?php foreach ($detail['items'] as $item): ?>
                            <li>
                                <?php if (is_array($item)): ?>
                                    <strong><?= esc($item['kaih']) ?></strong>
                                    <?php if (!empty($item['dimensi_profil'])): ?>
                                        <br><em class="text-muted" style="font-size: 0.9em;">
                                            <i class="bi bi-star-fill"></i> Dimensi: <?= esc($item['dimensi_profil']) ?>
                                            <?php if (!empty($item['sub_dimensi'])): ?>
                                                <br><i class="bi bi-arrow-return-right"></i> Sub Dimensi: <?= esc($item['sub_dimensi']) ?>
                                            <?php endif; ?>
                                        </em>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= esc($item) ?>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            <?php else: ?>
                <?php 
                $detail = json_decode($document['kegiatan_detail'], true);
                if ($detail && isset($detail['text'])):
                ?>
                    <p><?= nl2br(esc($detail['text'])) ?></p>
                    
                    <?php if (isset($detail['items']) && !empty($detail['items'])): ?>
                        <h6>Sub Dimensi untuk Penilaian:</h6>
                        <ul>
                            <?php foreach ($detail['items'] as $item): ?>
                                <li>
                                    <strong><?= esc($item['dimensi_profil']) ?></strong>: <?= esc($item['sub_dimensi']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php else: ?>
                    <p><?= nl2br(esc($document['kegiatan_detail'])) ?></p>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($document['status'] === 'completed'): ?>
        <!-- Tujuan Pembelajaran -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Tujuan Pembelajaran</h5>
            </div>
            <div class="card-body">
                <?= nl2br(esc($document['tujuan_pembelajaran'])) ?>
            </div>
        </div>

        <!-- Praktik Pedagogis -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Praktik Pedagogis</h5>
            </div>
            <div class="card-body">
                <?= nl2br(esc($document['praktik_pedagogis'])) ?>
            </div>
        </div>

        <!-- Lingkungan Belajar -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Lingkungan Belajar</h5>
            </div>
            <div class="card-body">
                <?= nl2br(esc($document['lingkungan_belajar'])) ?>
            </div>
        </div>

        <!-- Kemitraan dan Teknologi -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Kemitraan dan Teknologi Digital</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Kemitraan Pembelajaran:</h6>
                        <?php 
                        $kemitraan = json_decode($document['kemitraan'], true);
                        if ($kemitraan && is_array($kemitraan)):
                        ?>
                            <ul>
                                <?php foreach ($kemitraan as $k): ?>
                                    <li><?= esc($k) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada data</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6">
                        <h6>Teknologi Digital:</h6>
                        <?php 
                        $teknologi = json_decode($document['teknologi_digital'], true);
                        if ($teknologi && is_array($teknologi)):
                        ?>
                            <ul>
                                <?php foreach ($teknologi as $t): ?>
                                    <li><?= esc($t) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <p class="text-muted">Tidak ada data</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Kegiatan Kokurikuler -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0">Rincian Kegiatan Kokurikuler</h5>
            </div>
            <div class="card-body">
                <?php 
                $kegiatan = json_decode($document['kegiatan_kokurikuler'], true);
                if ($kegiatan && is_array($kegiatan)):
                ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th width="100">Pertemuan</th>
                                    <th>Kegiatan</th>
                                    <th>Deskripsi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($kegiatan as $k): ?>
                                    <tr>
                                        <td class="text-center"><?= $k['pertemuan'] ?? '-' ?></td>
                                        <td><?= esc($k['kegiatan'] ?? '-') ?></td>
                                        <td><?= esc($k['deskripsi'] ?? '-') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">Tidak ada data kegiatan</p>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('#btn_generate_ai').on('click', function() {
        if (!confirm('Generate konten dengan AI? Proses ini mungkin memakan waktu beberapa menit.')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Generating...');
        
        $.ajax({
            url: '<?= base_url('admin/kokurikuler/generate-ai/' . $document['id']) ?>',
            type: 'POST',
            dataType: 'json',
            data: {
                '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('Konten berhasil di-generate! Halaman akan di-refresh.');
                    location.reload();
                } else {
                    alert('Error: ' + response.message);
                    $btn.prop('disabled', false).html('<i class="bi bi-magic"></i> Generate dengan AI');
                }
            },
            error: function(xhr) {
                alert('Terjadi kesalahan. Silakan coba lagi.');
                console.error(xhr.responseText);
                $btn.prop('disabled', false).html('<i class="bi bi-magic"></i> Generate dengan AI');
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
