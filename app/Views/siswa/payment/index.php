<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 pb-4">
    <h4 class="mt-3 fw-bold">💳 Informasi Pembayaran</h4>
    <p class="text-muted mb-4">Data pembayaran diambil dari sistem pembayaran sekolah.</p>

    <!-- Info Siswa -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body d-flex align-items-center gap-3">
            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width:48px;height:48px;font-size:1.2rem;">
                👤
            </div>
            <div>
                <strong><?= esc($student['name'] ?? '-') ?></strong>
                <div class="text-muted small">NIS: <?= esc($student['nis'] ?? '-') ?></div>
            </div>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc($error) ?>
        </div>
    <?php elseif (!empty($data)): ?>

        <!-- Ringkasan Pembayaran -->
        <?php if (isset($data['summary'])): ?>
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Total Tagihan</div>
                    <div class="fw-bold text-primary"><?= esc($data['summary']['total_tagihan'] ?? '-') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Total Dibayar</div>
                    <div class="fw-bold text-success"><?= esc($data['summary']['total_dibayar'] ?? '-') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Sisa Tunggakan</div>
                    <div class="fw-bold text-danger"><?= esc($data['summary']['sisa_tunggakan'] ?? '-') ?></div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card border-0 shadow-sm text-center p-3">
                    <div class="text-muted small">Status</div>
                    <?php
                        $status = $data['summary']['status'] ?? 'unknown';
                        $statusClass = ($status === 'lunas') ? 'bg-success' : (($status === 'sebagian') ? 'bg-warning text-dark' : 'bg-danger');
                    ?>
                    <span class="badge <?= $statusClass ?>"><?= esc(ucfirst($status)) ?></span>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Riwayat Pembayaran -->
        <?php if (isset($data['payments']) && is_array($data['payments'])): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white">
                <h6 class="fw-bold mb-0">📋 Riwayat Pembayaran</h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Keterangan</th>
                                <th class="text-end">Jumlah</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($data['payments'] as $p): ?>
                            <tr>
                                <td><?= esc($p['tanggal'] ?? '-') ?></td>
                                <td><?= esc($p['keterangan'] ?? '-') ?></td>
                                <td class="text-end"><?= esc($p['jumlah'] ?? '-') ?></td>
                                <td class="text-center">
                                    <?php
                                        $pStatus = $p['status'] ?? '';
                                        $pClass = ($pStatus === 'lunas') ? 'bg-success' : (($pStatus === 'pending') ? 'bg-warning text-dark' : 'bg-secondary');
                                    ?>
                                    <span class="badge <?= $pClass ?>"><?= esc(ucfirst($pStatus)) ?></span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Jika API mengembalikan format custom/plain -->
        <?php if (!isset($data['summary']) && !isset($data['payments'])): ?>
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body">
                <pre class="mb-0"><?= esc(json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
            </div>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <div class="alert alert-info border-0 shadow-sm">
            <i class="bi bi-info-circle me-2"></i>Belum ada data pembayaran yang tersedia.
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
