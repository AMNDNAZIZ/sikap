<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="container-fluid">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Log Aktivitas (Audit Trail)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>User</th>
                            <th>Aksi</th>
                            <th>Tabel/Modul</th>
                            <th>IP Address</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="6" class="text-center">Belum ada log aktivitas.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></td>
                                    <td>
                                        <?php if ($log['user_id']): ?>
                                            <?= esc($log['fullname'] ?? $log['username']) ?> 
                                            <small class="text-muted">(ID: <?= $log['user_id'] ?>)</small>
                                        <?php else: ?>
                                            <span class="text-muted">Sistem / Guest</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php 
                                            $badgeClass = 'bg-secondary';
                                            if ($log['action'] == 'insert') $badgeClass = 'bg-success';
                                            elseif ($log['action'] == 'update') $badgeClass = 'bg-warning text-dark';
                                            elseif ($log['action'] == 'delete') $badgeClass = 'bg-danger';
                                            elseif ($log['action'] == 'login') $badgeClass = 'bg-info text-dark';
                                        ?>
                                        <span class="badge <?= $badgeClass ?>"><?= strtoupper(esc($log['action'])) ?></span>
                                    </td>
                                    <td>
                                        <?= esc($log['table_name'] ?? '-') ?>
                                        <?php if ($log['record_id']): ?>
                                            <small class="text-muted">#<?= esc($log['record_id']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= esc($log['ip_address']) ?></td>
                                    <td>
                                        <a href="<?= base_url('admin/audit-logs/' . $log['id']) ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div class="mt-3">
                <?= $pager->links() ?>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
