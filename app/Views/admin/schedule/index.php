<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-12">
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-primary">
                    <i class="bi bi-calendar-range-fill me-2"></i> Jadwal Pelajaran
                </h5>
                <span class="badge bg-info p-2 rounded-pill shadow-sm">
                    Tahun Ajaran: <?= esc($activeYear['year']) ?>
                </span>
            </div>
            <div class="card-body">
                <div class="row align-items-end mb-4">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">Pilih Kelas</label>
                        <form action="<?= base_url('admin/schedules') ?>" method="get" id="classFilterForm">
                            <select name="class_id" class="form-select select2" onchange="this.form.submit()">
                                <option value="">-- Pilih Kelas untuk Melihat Jadwal --</option>
                                <?php foreach ($classes as $class): ?>
                                    <option value="<?= $class['id'] ?>" <?= $selectedClassId == $class['id'] ? 'selected' : '' ?>>
                                        <?= esc($class['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-8 text-md-end">
                        <?php if ($selectedClassId && $canManage): ?>
                            <a href="<?= base_url('admin/schedules/manage/' . $selectedClassId) ?>" class="btn btn-primary px-4 shadow-sm">
                                <i class="bi bi-pencil-square me-2"></i> Kelola Jadwal Kelas Ini
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($selectedClassId && !empty($schedules)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="bg-light text-center">
                                <tr>
                                    <th width="120">Hari</th>
                                    <th width="150">Waktu</th>
                                    <th>Mata Pelajaran</th>
                                    <th>Guru Pengampu</th>
                                    <?php if ($canManage): ?>
                                        <th width="100">Aksi</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $currentDay = '';
                                foreach ($schedules as $row): 
                                ?>
                                    <tr>
                                        <?php if ($currentDay != $row['day_of_week']): ?>
                                            <?php 
                                            $currentDay = $row['day_of_week'];
                                            // Count rows for this day to rowspan
                                            $dayCount = count(array_filter($schedules, function($s) use ($currentDay) {
                                                return $s['day_of_week'] == $currentDay;
                                            }));
                                            ?>
                                            <td rowspan="<?= $dayCount ?>" class="align-middle fw-bold bg-light text-center">
                                                <?= $days[$row['day_of_week']] ?>
                                            </td>
                                        <?php endif; ?>
                                        <td class="text-center font-monospace">
                                            <?= substr($row['start_time'], 0, 5) ?> - <?= substr($row['end_time'], 0, 5) ?>
                                        </td>
                                        <td class="fw-bold text-dark">
                                            <?php
                                            $isMySubject = empty($teacherSubjectIds) || in_array($row['subject_id'], $teacherSubjectIds);
                                            ?>
                                            <?php if ($isMySubject): ?>
                                                <a href="<?= base_url('admin/teaching-journal?class_id=' . $row['class_id'] . '&subject_id=' . $row['subject_id']) ?>" class="text-decoration-none text-primary">
                                                    <i class="bi bi-book-half me-1"></i> <?= esc($row['subject_name']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">
                                                    <i class="bi bi-book-half me-1"></i> <?= esc($row['subject_name']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= esc($row['teacher_name']) ?></td>
                                        <?php if ($canManage): ?>
                                            <td class="text-center">
                                                <a href="<?= base_url('admin/schedules/delete/' . $row['id']) ?>" 
                                                   class="btn btn-sm btn-outline-danger border-0" 
                                                   onclick="return confirm('Hapus slot jadwal ini?')">
                                                    <i class="bi bi-trash"></i>
                                                </a>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php elseif ($selectedClassId): ?>
                    <div class="alert alert-light border border-dashed py-5 text-center">
                        <i class="bi bi-calendar-x text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">Belum ada jadwal yang diatur untuk kelas ini.</p>
                        <?php if ($canManage): ?>
                            <a href="<?= base_url('admin/schedules/manage/' . $selectedClassId) ?>" class="btn btn-sm btn-primary mt-3">
                                Mulai Buat Jadwal
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <img src="<?= base_url('assets/img/illustrations/calendar.svg') ?>" alt="Calendar" style="max-width: 15rem; margin-bottom: 2rem;">
                        <h5 class="text-muted">Silakan pilih kelas terlebih dahulu untuk melihat atau mengelola jadwal pelajaran.</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>
