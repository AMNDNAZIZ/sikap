<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
    .daily-container { padding-bottom: 80px; }

    .student-row {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        border-bottom: 1px solid #f0f0f0;
    }
    .student-row:active {
        background: #f8f9fa;
    }
    .student-no {
        width: 24px;
        font-size: 0.75rem;
        color: #999;
        text-align: center;
        flex-shrink: 0;
    }
    .student-name {
        flex: 1;
        min-width: 0;
        font-size: 0.9rem;
        font-weight: 500;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 0 8px;
    }

    /* Single tap-to-cycle badge */
    .status-badge {
        width: 42px;
        height: 42px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1.1rem;
        cursor: pointer;
        flex-shrink: 0;
        user-select: none;
        -webkit-tap-highlight-color: transparent;
        transition: transform 0.1s;
    }
    .status-badge:active {
        transform: scale(0.88);
    }
    .status-badge.st-H { background: #198754; color: #fff; }
    .status-badge.st-I { background: #ffc107; color: #000; }
    .status-badge.st-S { background: #fd7e14; color: #fff; }
    .status-badge.st-A { background: #dc3545; color: #fff; }

    /* Sticky header */
    .daily-header {
        position: sticky;
        top: 0;
        z-index: 10;
        background: #fff;
        border-bottom: 1px solid #dee2e6;
        padding: 8px 12px;
    }

    .summary-bar {
        display: flex;
        gap: 10px;
        font-size: 0.8rem;
        font-weight: 600;
    }
    .summary-bar .s-h { color: #198754; }
    .summary-bar .s-i { color: #ffc107; }
    .summary-bar .s-s { color: #fd7e14; }
    .summary-bar .s-a { color: #dc3545; }

    /* Desktop override */
    @media (min-width: 768px) {
        .daily-container { max-width: 500px; margin: 0 auto; }
    }

    /* Save button fixed to bottom of viewport */
    .save-btn-wrapper {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 1050;
        background: #fff;
        border-top: 1px solid #dee2e6;
        padding: 0;
    }
    .save-btn-wrapper .btn {
        border-radius: 0 !important;
        padding-top: 16px;
        padding-bottom: calc(16px + env(safe-area-inset-bottom, 0px));
    }

    /* Hide layout footer on this page to avoid overlap */
    footer { display: none !important; }
</style>

<div class="daily-container">
    <!-- Top bar -->
    <div class="d-flex align-items-center justify-content-between px-3 py-2 border-bottom">
        <a href="<?= base_url('admin/attendance') ?>" class="text-decoration-none text-muted">
            <i class="bi bi-arrow-left"></i>
        </a>
        <strong class="fs-6"><?= esc($class['name']) ?></strong>
        <span></span>
    </div>

    <!-- Alert -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show py-2 mx-3 mt-2 mb-0 small" role="alert">
            <i class="bi bi-check-circle-fill me-1"></i><?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show py-2 mx-3 mt-2 mb-0 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i><?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close btn-sm" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Date selector compact -->
    <div class="px-3 py-2 bg-light border-bottom">
        <form method="get" action="<?= base_url('admin/attendance/daily') ?>" class="d-flex align-items-center gap-2">
            <input type="hidden" name="class_id" value="<?= (int)$class['id'] ?>">
            <input type="date" name="date" class="form-control form-control-sm" value="<?= esc($date) ?>" max="<?= $today ?>" onchange="this.form.submit()">
            <span class="text-nowrap small fw-bold <?= $date === $today ? 'text-success' : 'text-primary' ?>">
                <?= $date === $today ? '📅 Hari Ini' : tanggal_indo($date) ?>
            </span>
        </form>
    </div>

    <?php if ($isHoliday): ?>
        <div class="text-center py-5 px-3">
            <i class="bi bi-calendar-x fs-1 text-warning d-block mb-2"></i>
            <strong>Hari Libur</strong>
            <div class="small text-muted"><?= esc($holidayDesc) ?></div>
        </div>
    <?php elseif ($isFuture): ?>
        <div class="text-center py-5 px-3">
            <i class="bi bi-clock-history fs-1 text-info d-block mb-2"></i>
            <strong>Tanggal Belum Tiba</strong>
            <div class="small text-muted">Hanya bisa isi hari ini atau sebelumnya.</div>
        </div>
    <?php else: ?>
        <form method="post" action="<?= base_url('admin/attendance/save') ?>" id="daily-form">
            <?= csrf_field() ?>
            <input type="hidden" name="class_id" value="<?= (int)$class['id'] ?>">
            <input type="hidden" name="date" value="<?= esc($date) ?>">

            <!-- Sticky summary + action -->
            <div class="daily-header">
                <div class="d-flex align-items-center justify-content-between">
                    <div class="summary-bar">
                        <span class="s-h">H:<span id="countH">0</span></span>
                        <span class="s-i">I:<span id="countI">0</span></span>
                        <span class="s-s">S:<span id="countS">0</span></span>
                        <span class="s-a">A:<span id="countA">0</span></span>
                    </div>
                    <?php if (!$isReadonly): ?>
                        <button type="button" class="btn btn-sm btn-outline-success py-0 px-2" id="btnAllHadir" style="font-size:0.75rem;">
                            Semua H
                        </button>
                    <?php endif; ?>
                </div>
                <div class="text-muted mt-1" style="font-size:0.7rem;">Tap status untuk mengubah: H → I → S → A → H</div>
            </div>

            <!-- Student list -->
            <div id="studentList">
                <?php $no = 1; foreach ($students as $s):
                    $currentStatus = $attMap[$s['id']] ?? 'H';
                ?>
                <div class="student-row">
                    <span class="student-no"><?= $no++ ?></span>
                    <span class="student-name"><?= esc($s['name']) ?></span>
                    <?php if (!$isReadonly): ?>
                        <div class="status-badge st-<?= $currentStatus ?>" data-student="<?= $s['id'] ?>" data-status="<?= $currentStatus ?>">
                            <?= $currentStatus ?>
                        </div>
                    <?php else: ?>
                        <div class="status-badge st-<?= $currentStatus ?>">
                            <?= $currentStatus ?>
                        </div>
                    <?php endif; ?>
                    <input type="hidden" name="status[<?= $s['id'] ?>]" value="<?= $currentStatus ?>">
                </div>
                <?php endforeach; ?>
            </div>

            <?php if (!$isReadonly): ?>
                <!-- Sticky bottom save button -->
                <div class="save-btn-wrapper">
                    <button type="submit" class="btn btn-primary w-100 fw-bold py-3 rounded-0">
                        <i class="bi bi-check-lg me-1"></i>Simpan Absensi
                    </button>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>

    <!-- Link ke mode lain -->
    <div class="text-center mt-4 mb-3">
        <a href="<?= base_url('admin/attendance/view?class_id=' . (int)$class['id'] . '&month=' . date('Y-m', strtotime($date))) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-calendar-month me-1"></i>Bulanan
        </a>
        <a href="<?= base_url('admin/attendance/week?class_id=' . (int)$class['id'] . '&week=' . date('Y-\WW', strtotime($date))) ?>" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-calendar-week me-1"></i>Mingguan
        </a>
    </div>
</div>

<?php if (!$isHoliday && !$isFuture && !$isReadonly): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const cycle = ['H', 'I', 'S', 'A'];
    
    function updateCounts() {
        let h = 0, i = 0, s = 0, a = 0;
        document.querySelectorAll('#studentList input[type="hidden"]').forEach(input => {
            const v = input.value;
            if (v === 'H') h++;
            else if (v === 'I') i++;
            else if (v === 'S') s++;
            else if (v === 'A') a++;
        });
        document.getElementById('countH').textContent = h;
        document.getElementById('countI').textContent = i;
        document.getElementById('countS').textContent = s;
        document.getElementById('countA').textContent = a;
    }

    // Tap badge to cycle status
    document.querySelectorAll('.status-badge[data-student]').forEach(badge => {
        badge.addEventListener('click', function() {
            let current = this.dataset.status;
            let idx = cycle.indexOf(current);
            let next = cycle[(idx + 1) % cycle.length];
            
            // Update badge
            this.dataset.status = next;
            this.textContent = next;
            this.className = 'status-badge st-' + next;
            
            // Update hidden input
            this.parentElement.querySelector('input[type="hidden"]').value = next;
            
            updateCounts();
        });
    });

    // All hadir
    const btnAll = document.getElementById('btnAllHadir');
    if (btnAll) {
        btnAll.addEventListener('click', function() {
            document.querySelectorAll('.status-badge[data-student]').forEach(badge => {
                badge.dataset.status = 'H';
                badge.textContent = 'H';
                badge.className = 'status-badge st-H';
                badge.parentElement.querySelector('input[type="hidden"]').value = 'H';
            });
            updateCounts();
        });
    }

    updateCounts();
});
</script>
<?php endif; ?>

<?= $this->endSection() ?>
