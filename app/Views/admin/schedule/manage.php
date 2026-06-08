<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-md mb-4 border-primary">
            <div class="card-header bg-primary py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-white">
                    <i class="bi bi-pencil-square me-2"></i> Kelola Jadwal: <?= esc($class['name']) ?>
                </h5>
                <a href="<?= base_url('admin/schedules?class_id=' . $class['id']) ?>" class="btn btn-sm btn-light rounded-pill px-3 shadow-sm">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>
            <div class="card-body">
                <div class="alert alert-info border-0 shadow-sm mb-4">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle-fill fs-3 me-3"></i>
                        <div>
                            <p class="mb-0"><strong>Tips:</strong> Anda dapat mengisi jadwal untuk semua hari sekaligus, lalu klik <strong>"Simpan Semua Jadwal"</strong> di bawah untuk menyimpan semuanya dalam sekali klik. Jadwal disusun berdasarkan mata pelajaran yang sudah terdaftar pada <strong>Plotting Pengajaran</strong> kelas ini.</p>
                        </div>
                    </div>
                </div>

                <ul class="nav nav-pills nav-fill mb-4 p-2 bg-light rounded-3 shadow-sm" id="scheduleTabs" role="tablist">
                    <?php foreach ($days as $num => $name): if ($num > 6) continue; ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?= $num == 1 ? 'active' : '' ?> fw-bold rounded-pill mx-1" 
                                    id="day-<?= $num ?>-tab" 
                                    data-bs-toggle="tab" 
                                    data-bs-target="#day-<?= $num ?>" 
                                    type="button" 
                                    role="tab">
                                <?= $name ?>
                            </button>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <!-- Form wrapper untuk semua hari -->
                <form action="<?= base_url('admin/schedules/store-bulk') ?>" method="post" id="bulkScheduleForm">
                    <?= csrf_field() ?>
                    <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                    <input type="hidden" name="academic_year_id" value="<?= $activeYear['id'] ?>">

                    <div class="tab-content" id="scheduleTabsContent">
                        <?php foreach ($days as $num => $name): if ($num > 6) continue; ?>
                            <div class="tab-pane fade <?= $num == 1 ? 'show active' : '' ?>" id="day-<?= $num ?>" role="tabpanel">
                                <input type="hidden" name="days[]" value="<?= $num ?>">
                                
                                <div class="table-responsive">
                                    <table class="table table-hover border align-middle" id="table-day-<?= $num ?>">
                                        <thead class="bg-light text-center small text-uppercase fw-bold">
                                            <tr>
                                                <th width="200">Jam Mulai</th>
                                                <th width="200">Jam Selesai</th>
                                                <th>Mata Pelajaran & Guru</th>
                                                <th width="80"></th>
                                            </tr>
                                        </thead>
                                        <tbody class="row-container">
                                            <?php 
                                            $daySchedules = array_filter($schedules, function($s) use ($num) {
                                                return $s['day_of_week'] == $num;
                                            });
                                            if (empty($daySchedules)):
                                            ?>
                                                <tr class="empty-row">
                                                    <td colspan="4" class="text-center py-4 text-muted fst-italic">
                                                        Belum ada jadwal untuk hari <?= $name ?>. Klik tombol "+" untuk menambah.
                                                    </td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($daySchedules as $s): ?>
                                                    <tr>
                                                        <td>
                                                            <input type="time" name="day_<?= $num ?>_start_time[]" class="form-control" value="<?= substr($s['start_time'], 0, 5) ?>" required>
                                                        </td>
                                                        <td>
                                                            <input type="time" name="day_<?= $num ?>_end_time[]" class="form-control" value="<?= substr($s['end_time'], 0, 5) ?>" required>
                                                        </td>
                                                        <td>
                                                            <select name="day_<?= $num ?>_assignment_id[]" class="form-select select2-simple" required>
                                                                <option value="">-- Pilih Mata Pelajaran --</option>
                                                                <?php foreach ($assignments as $a): ?>
                                                                    <option value="<?= $a['subject_id'] ?>|<?= $a['teacher_id'] ?>" <?= ($s['subject_id'] == $a['subject_id'] && $s['teacher_id'] == $a['teacher_id']) ? 'selected' : '' ?>>
                                                                        <?= esc($a['subject_name']) ?> (<?= esc($a['teacher_name']) ?>)
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                        </td>
                                                        <td class="text-center">
                                                            <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="bg-light">
                                                <td colspan="4">
                                                    <button type="button" class="btn btn-sm btn-outline-primary btn-add-row" data-day="<?= $num ?>">
                                                        <i class="bi bi-plus-lg me-1"></i> Tambah Slot Jam
                                                    </button>
                                                </td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Tombol Simpan Semua di bawah -->
                    <div class="mt-4 text-center bg-light p-4 rounded-3 shadow-sm">
                        <button type="submit" class="btn btn-success btn-lg px-5 rounded-pill shadow fw-bold">
                            <i class="bi bi-save me-2"></i> Simpan Semua Jadwal
                        </button>
                        <p class="text-muted small mt-2 mb-0">
                            <i class="bi bi-info-circle me-1"></i> Klik untuk menyimpan jadwal semua hari sekaligus
                        </p>
                    </div>
                </form>

            </div>
        </div>
    </div>
</div>

<template id="row-template">
    <tr>
        <td>
            <input type="time" name="day_{DAY}_start_time[]" class="form-control" required>
        </td>
        <td>
            <input type="time" name="day_{DAY}_end_time[]" class="form-control" required>
        </td>
        <td>
            <select name="day_{DAY}_assignment_id[]" class="form-select" required>
                <option value="">-- Pilih Mata Pelajaran --</option>
                <?php foreach ($assignments as $a): ?>
                    <option value="<?= $a['subject_id'] ?>|<?= $a['teacher_id'] ?>">
                        <?= esc($a['subject_name']) ?> (<?= esc($a['teacher_name']) ?>)
                    </option>
                <?php endforeach; ?>
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-outline-danger remove-row"><i class="bi bi-x-lg"></i></button>
        </td>
    </tr>
</template>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Schedule manage script loaded');
    
    // 🔔 Flash Message Handler with SweetAlert2
    <?php if (session()->getFlashdata('success')): ?>
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: '<?= session()->getFlashdata('success') ?>',
            showConfirmButton: true,
            confirmButtonColor: '#198754',
            timer: 5000,
            timerProgressBar: true
        });
    <?php endif; ?>

    <?php if (session()->getFlashdata('error')): ?>
        Swal.fire({
            icon: 'error',
            title: 'Gagal!',
            text: '<?= session()->getFlashdata('error') ?>',
            showConfirmButton: true,
            confirmButtonColor: '#dc3545'
        });
    <?php endif; ?>

    <?php if (session()->getFlashdata('warning')): ?>
        Swal.fire({
            icon: 'warning',
            title: 'Perhatian!',
            text: '<?= session()->getFlashdata('warning') ?>',
            showConfirmButton: true,
            confirmButtonColor: '#ffc107'
        });
    <?php endif; ?>

    <?php if (session()->getFlashdata('info')): ?>
        Swal.fire({
            icon: 'info',
            title: 'Informasi',
            text: '<?= session()->getFlashdata('info') ?>',
            showConfirmButton: true,
            confirmButtonColor: '#0dcaf0'
        });
    <?php endif; ?>
    
    // Add row logic
    document.querySelectorAll('.btn-add-row').forEach(btn => {
        console.log('Found add button for day:', btn.dataset.day);
        
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            console.log('Add button clicked for day:', this.dataset.day);
            
            const day = this.dataset.day;
            const tableId = `table-day-${day}`;
            const container = document.querySelector(`#${tableId} .row-container`);
            const template = document.querySelector('#row-template');
            
            console.log('Container found:', container);
            console.log('Template found:', template);
            
            if (!container || !template) {
                console.error('Container or template not found!');
                return;
            }
            
            // Remove empty row if exists
            const emptyRow = container.querySelector('.empty-row');
            if (emptyRow) {
                console.log('Removing empty row');
                emptyRow.remove();
            }
            
            // Clone template
            const clone = template.content.cloneNode(true);
            
            // Get the tr element from clone
            const tr = clone.querySelector('tr');
            if (!tr) {
                console.error('TR not found in template!');
                return;
            }
            
            // Replace {DAY} in all attributes and text
            tr.innerHTML = tr.innerHTML.replace(/{DAY}/g, day);
            
            // Append to container
            container.appendChild(tr);
            console.log('Row added successfully');
        });
    });

    // Remove row logic
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('remove-row') || e.target.closest('.remove-row'))) {
            e.preventDefault();
            console.log('Remove button clicked');
            
            const btn = e.target.classList.contains('remove-row') ? e.target : e.target.closest('.remove-row');
            const row = btn.closest('tr');
            const container = row.closest('.row-container');
            
            row.remove();
            console.log('Row removed');
            
            if (container.children.length === 0) {
                container.innerHTML = `<tr class="empty-row"><td colspan="4" class="text-center py-4 text-muted fst-italic">Belum ada jadwal. Klik tombol "+" untuk menambah.</td></tr>`;
                console.log('Empty row added back');
            }
        }
    });

    // Form validation before submit
    const form = document.getElementById('bulkScheduleForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const hasSchedule = document.querySelectorAll('.row-container tr:not(.empty-row)').length > 0;
            
            console.log('Form submit, has schedule:', hasSchedule);
            
            if (!hasSchedule) {
                e.preventDefault();
                Swal.fire({
                    icon: 'warning',
                    title: 'Perhatian!',
                    text: 'Silakan tambahkan minimal satu jadwal sebelum menyimpan.',
                    confirmButtonColor: '#ffc107'
                });
                return false;
            }
            
            // Show loading
            Swal.fire({
                title: 'Menyimpan...',
                text: 'Mohon tunggu, sedang menyimpan jadwal',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            return true;
        });
    }
});
</script>

<?= $this->endSection() ?>
