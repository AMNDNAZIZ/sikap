<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h4 class="mb-0"><?= $title ?></h4>
            <small class="text-muted">Rekap hasil kegiatan kokurikuler yang telah selesai dinilai</small>
        </div>
        <?php if (!empty($documents)): ?>
        <a href="<?= base_url('admin/kokurikuler/pelaporan/cetak') ?>" target="_blank" class="btn btn-success btn-sm">
            <i class="bi bi-printer"></i> Cetak Semua Laporan
        </a>
        <?php endif; ?>
    </div>

    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($documents)): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-clipboard-x text-muted" style="font-size: 3rem;"></i>
                <h5 class="mt-3 text-muted">Belum Ada Laporan</h5>
                <p class="text-muted">Laporan akan tersedia setelah penilaian seluruh siswa selesai (100%).</p>
                <a href="<?= base_url('admin/kokurikuler/penilaian') ?>" class="btn btn-primary btn-sm">
                    <i class="bi bi-clipboard-check"></i> Ke Halaman Penilaian
                </a>
            </div>
        </div>
    <?php else: ?>

        <?php foreach ($documents as $idx => $doc): ?>
        <?php $laporan = $doc['laporan'] ?? []; ?>
        <div class="card shadow-sm mb-4">
            <!-- Card Header -->
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <div>
                    <strong><?= esc($doc['tema']) ?></strong>
                    <span class="badge bg-light text-dark ms-2"><?= esc($doc['year_name']) ?> &bull; Sem <?= esc($doc['semester']) ?></span>
                    <span class="badge bg-light text-dark ms-1">Fase <?= esc($doc['fase']) ?> / Kelas <?= esc($doc['level_kelas']) ?></span>
                </div>
                <span class="badge bg-success">✓ Selesai</span>
            </div>

            <div class="card-body">
                <!-- Info Kegiatan -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted">Bentuk Kegiatan</small><br>
                        <strong><?= esc($doc['bentuk_kegiatan_konkret'] ?: '-') ?></strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted">Dimensi yang Dinilai</small><br>
                        <?php foreach ($doc['rubrik'] as $r): ?>
                            <span class="badge bg-primary me-1"><?= esc($r['dimensi_profil']) ?> – <?= esc($r['sub_dimensi']) ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tabel Rekap Siswa -->
                <div class="table-responsive mb-3">
                    <table class="table table-bordered table-sm" style="font-size: 0.85rem;">
                        <thead class="table-light">
                            <tr>
                                <th width="4%">No</th>
                                <th width="10%">NIS</th>
                                <th width="20%">Nama Siswa</th>
                                <?php foreach ($doc['rubrik'] as $r): ?>
                                    <th class="text-center" width="8%">
                                        <?= esc($r['dimensi_profil']) ?><br>
                                        <small class="text-muted"><?= esc($r['sub_dimensi']) ?></small>
                                    </th>
                                <?php endforeach; ?>
                                <th>Deskripsi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $capaianBadge = ['Berkembang' => 'warning', 'Cakap' => 'primary', 'Mahir' => 'success'];
                            $capaianIcon  = ['Berkembang' => '🟡', 'Cakap' => '🔵', 'Mahir' => '🟢'];
                            foreach ($doc['students'] as $i => $student):
                            ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= esc($student['nis']) ?></td>
                                <td><?= esc($student['name']) ?></td>
                                <?php foreach ($doc['rubrik'] as $r): ?>
                                    <?php
                                    $capaian = $student['penilaian_detail'][(string)$r['id']] ?? '-';
                                    $badge   = $capaianBadge[$capaian] ?? 'secondary';
                                    $icon    = $capaianIcon[$capaian] ?? '';
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

                <!-- Refleksi & Rekomendasi -->
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="bi bi-journal-text text-info"></i> Refleksi Pelaksanaan</label>
                        <textarea class="form-control refleksi-input" rows="4"
                                  data-doc-id="<?= $doc['id'] ?>"
                                  placeholder="Tuliskan refleksi pelaksanaan kegiatan kokurikuler ini..."><?= esc($laporan['refleksi'] ?? '') ?></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold"><i class="bi bi-lightbulb text-warning"></i> Rekomendasi Perbaikan</label>
                        <textarea class="form-control rekomendasi-input" rows="4"
                                  data-doc-id="<?= $doc['id'] ?>"
                                  placeholder="Tuliskan rekomendasi untuk kegiatan serupa di masa mendatang..."><?= esc($laporan['rekomendasi'] ?? '') ?></textarea>
                    </div>
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-primary btn-sm btn-save-laporan"
                                data-doc-id="<?= $doc['id'] ?>">
                            <i class="bi bi-save"></i> Simpan Refleksi & Rekomendasi
                        </button>
                        <?php if (!empty($laporan['updated_at'])): ?>
                            <small class="text-muted ms-2">Terakhir disimpan: <?= date('d/m/Y H:i', strtotime($laporan['updated_at'])) ?></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

    <?php endif; ?>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(document).ready(function() {
    $('.btn-save-laporan').on('click', function() {
        const $btn     = $(this);
        const docId    = $btn.data('doc-id');
        const refleksi    = $(`.refleksi-input[data-doc-id="${docId}"]`).val();
        const rekomendasi = $(`.rekomendasi-input[data-doc-id="${docId}"]`).val();

        $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Menyimpan...');

        $.ajax({
            url: '<?= base_url('admin/kokurikuler/pelaporan/save-laporan') ?>',
            type: 'POST',
            data: {
                <?= csrf_token() ?>: '<?= csrf_hash() ?>',
                document_id: docId,
                refleksi: refleksi,
                rekomendasi: rekomendasi
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Update last saved timestamp
                    const now = new Date();
                    const ts  = now.toLocaleDateString('id-ID') + ' ' + now.toLocaleTimeString('id-ID', {hour:'2-digit', minute:'2-digit'});
                    $btn.closest('.col-12').find('small.text-muted').remove();
                    $btn.after(`<small class="text-muted ms-2">Terakhir disimpan: ${ts}</small>`);
                    $btn.html('<i class="bi bi-check-circle"></i> Tersimpan');
                    setTimeout(() => $btn.html('<i class="bi bi-save"></i> Simpan Refleksi & Rekomendasi'), 2000);
                } else {
                    alert('Gagal menyimpan: ' + response.message);
                    $btn.html('<i class="bi bi-save"></i> Simpan Refleksi & Rekomendasi');
                }
            },
            error: function() {
                alert('Terjadi kesalahan saat menyimpan.');
                $btn.html('<i class="bi bi-save"></i> Simpan Refleksi & Rekomendasi');
            },
            complete: function() {
                $btn.prop('disabled', false);
            }
        });
    });
});
</script>
<?= $this->endSection() ?>
