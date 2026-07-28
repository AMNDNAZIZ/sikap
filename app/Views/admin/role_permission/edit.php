<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 pb-5">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mt-4 fw-bold">Atur Permission: <?= esc($role['name']) ?></h1>
            <p class="text-muted">Centang modul/aksi yang diizinkan untuk role ini.</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item"><a href="<?= base_url('admin/role-permission') ?>">Role & Permission</a></li>
                <li class="breadcrumb-item active"><?= esc($role['name']) ?></li>
            </ol>
        </nav>
    </div>

    <!-- Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('admin/role-permission/update/' . $role['id']) ?>" method="post">
        <?= csrf_field() ?>

        <!-- Toolbar -->
        <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill" id="selectAll">
                        <i class="bi bi-check-all me-1"></i>Pilih Semua
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill" id="deselectAll">
                        <i class="bi bi-x-lg me-1"></i>Hapus Semua
                    </button>
                    <span class="text-muted small ms-2" id="checkedCount">0 permission dipilih</span>
                </div>
                <div>
                    <a href="<?= base_url('admin/role-permission') ?>" class="btn btn-sm btn-outline-secondary rounded-pill me-2">
                        <i class="bi bi-arrow-left me-1"></i>Kembali
                    </a>
                    <button type="submit" class="btn btn-sm btn-primary rounded-pill px-4">
                        <i class="bi bi-save me-1"></i>Simpan Permission
                    </button>
                </div>
            </div>
        </div>

        <!-- Permission Groups -->
        <div class="row g-4">
            <?php foreach ($grouped as $groupName => $permissions): ?>
                <div class="col-lg-6 col-xl-4">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-header bg-white border-bottom pt-3 px-4 d-flex align-items-center justify-content-between">
                            <h6 class="fw-bold mb-0">
                                <?php
                                    $icons = [
                                        'Pengaturan' => '⚙️',
                                        'Kesiswaan' => '🎓',
                                        'Personalia' => '👨‍🏫',
                                        'Akademik' => '📚',
                                        'Kurikulum' => '📜',
                                        'CBT' => '💻',
                                        'Tata Usaha' => '📂',
                                        'CMS' => '🌐',
                                        'Lainnya' => '📋',
                                    ];
                                    echo $icons[$groupName] ?? '📌';
                                ?>
                                <?= esc($groupName) ?>
                            </h6>
                            <div>
                                <button type="button" class="btn btn-sm btn-link text-success p-0 me-2 group-select-all" data-group="<?= esc($groupName) ?>" title="Pilih semua dalam grup">
                                    <i class="bi bi-check-all"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-link text-secondary p-0 group-deselect-all" data-group="<?= esc($groupName) ?>" title="Hapus semua dalam grup">
                                    <i class="bi bi-x-lg"></i>
                                </button>
                            </div>
                        </div>
                        <div class="card-body p-4">
                            <?php foreach ($permissions as $perm): ?>
                                <div class="form-check mb-2">
                                    <input class="form-check-input perm-checkbox"
                                           type="checkbox"
                                           name="permissions[]"
                                           value="<?= $perm['id'] ?>"
                                           id="perm_<?= $perm['id'] ?>"
                                           data-group="<?= esc($groupName) ?>"
                                           <?= in_array($perm['id'], $assignedIds) ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="perm_<?= $perm['id'] ?>">
                                        <?= esc($perm['label']) ?>
                                        <small class="text-muted d-block"><?= esc($perm['module'] . '.' . $perm['action']) ?></small>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Bottom Submit -->
        <div class="text-end mt-4">
            <a href="<?= base_url('admin/role-permission') ?>" class="btn btn-outline-secondary rounded-pill me-2">
                <i class="bi bi-arrow-left me-1"></i>Kembali
            </a>
            <button type="submit" class="btn btn-primary rounded-pill px-5">
                <i class="bi bi-save me-1"></i>Simpan Permission
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkboxes = document.querySelectorAll('.perm-checkbox');
    const countEl = document.getElementById('checkedCount');

    function updateCount() {
        const checked = document.querySelectorAll('.perm-checkbox:checked').length;
        countEl.textContent = checked + ' permission dipilih';
    }

    checkboxes.forEach(cb => cb.addEventListener('change', updateCount));
    updateCount();

    // Select All
    document.getElementById('selectAll').addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = true);
        updateCount();
    });

    // Deselect All
    document.getElementById('deselectAll').addEventListener('click', function() {
        checkboxes.forEach(cb => cb.checked = false);
        updateCount();
    });

    // Group Select All
    document.querySelectorAll('.group-select-all').forEach(btn => {
        btn.addEventListener('click', function() {
            const group = this.dataset.group;
            document.querySelectorAll('.perm-checkbox[data-group="' + group + '"]').forEach(cb => cb.checked = true);
            updateCount();
        });
    });

    // Group Deselect All
    document.querySelectorAll('.group-deselect-all').forEach(btn => {
        btn.addEventListener('click', function() {
            const group = this.dataset.group;
            document.querySelectorAll('.perm-checkbox[data-group="' + group + '"]').forEach(cb => cb.checked = false);
            updateCount();
        });
    });
});
</script>

<?= $this->endSection() ?>
