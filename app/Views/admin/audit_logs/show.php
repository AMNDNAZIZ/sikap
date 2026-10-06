<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Detail Log Aktivitas</h1>
        <a href="<?= base_url('admin/audit-logs') ?>" class="d-none d-sm-inline-block btn btn-sm btn-secondary shadow-sm">
            <i class="fas fa-arrow-left fa-sm text-white-50"></i> Kembali
        </a>
    </div>

    <div class="row">
        <!-- Informasi Umum -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Informasi Log</h6>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <th width="30%">Waktu (Kapan)</th>
                            <td>: <?= date('d F Y H:i:s', strtotime($log['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <th>Aksi (Apa)</th>
                            <td>: <span class="badge bg-primary"><?= strtoupper(esc($log['action'])) ?></span></td>
                        </tr>
                        <tr>
                            <th>User (Siapa)</th>
                            <td>: <?= esc($log['fullname'] ?? $log['username'] ?? 'Sistem / Guest') ?> (ID: <?= $log['user_id'] ?: '-' ?>)</td>
                        </tr>
                        <tr>
                            <th>Tabel/Modul</th>
                            <td>: <?= esc($log['table_name'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>Record ID</th>
                            <td>: <?= esc($log['record_id'] ?? '-') ?></td>
                        </tr>
                        <tr>
                            <th>IP Address (Dari Mana)</th>
                            <td>: <?= esc($log['ip_address']) ?></td>
                        </tr>
                        <tr>
                            <th>User Agent (Perangkat)</th>
                            <td>: <?= esc($log['user_agent']) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detail Data -->
        <div class="col-lg-6 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Perubahan Data</h6>
                </div>
                <div class="card-body">
                    <?php if ($log['old_data'] || $log['new_data']): ?>
                        <ul class="nav nav-tabs" id="dataTab" role="tablist">
                            <?php if ($log['new_data']): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="new-tab" data-bs-toggle="tab" data-bs-target="#new" type="button" role="tab" aria-controls="new" aria-selected="true">Data Baru (Setelah)</button>
                            </li>
                            <?php endif; ?>
                            <?php if ($log['old_data']): ?>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?= !$log['new_data'] ? 'active' : '' ?>" id="old-tab" data-bs-toggle="tab" data-bs-target="#old" type="button" role="tab" aria-controls="old" aria-selected="<?= !$log['new_data'] ? 'true' : 'false' ?>">Data Lama (Sebelum)</button>
                            </li>
                            <?php endif; ?>
                        </ul>
                        <div class="tab-content mt-3" id="dataTabContent">
                            <?php if ($log['new_data']): ?>
                            <div class="tab-pane fade show active" id="new" role="tabpanel" aria-labelledby="new-tab">
                                <pre class="bg-light p-3 border rounded"><code class="language-json"><?= esc(json_encode(json_decode($log['new_data']), JSON_PRETTY_PRINT)) ?></code></pre>
                            </div>
                            <?php endif; ?>
                            <?php if ($log['old_data']): ?>
                            <div class="tab-pane fade <?= !$log['new_data'] ? 'show active' : '' ?>" id="old" role="tabpanel" aria-labelledby="old-tab">
                                <pre class="bg-light p-3 border rounded"><code class="language-json"><?= esc(json_encode(json_decode($log['old_data']), JSON_PRETTY_PRINT)) ?></code></pre>
                            </div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center text-muted my-5">
                            <i class="fas fa-database fa-3x mb-3"></i>
                            <p>Tidak ada detail perubahan data yang direkam.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
