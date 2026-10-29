<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📓 Jurnal Mengajar</h3>
    <div class="d-flex gap-2">
        <a href="<?= base_url('admin/teaching-journal/monitoring') ?>" class="btn btn-outline-primary">
            📊 Monitoring Keterlaksanaan
        </a>
        <?php if (!$readonly): ?>
        <a href="<?= base_url('admin/teaching-journal/add' . ($selected_class ? '?class_id='.$selected_class : '') . ($selected_subject ? '&subject_id='.$selected_subject : '')) ?>" class="btn btn-primary">
            ➕ Tambah Jurnal
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="card mb-4 shadow-sm">
    <div class="card-body">
        <form action="<?= base_url('admin/teaching-journal') ?>" method="get" class="row g-3">
            <div class="col-md-3">
                <label class="form-label fw-bold">Kelas</label>
                <select name="class_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= $selected_class == $c['id'] ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fw-bold">Mata Pelajaran</label>
                <select name="subject_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Pilih Mapel --</option>
                    <?php foreach ($subjects as $s): ?>
                        <option value="<?= $s['id'] ?>" <?= $selected_subject == $s['id'] ? 'selected' : '' ?>>
                            <?= esc($s['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php if (!empty($teachers)): ?>
            <div class="col-md-3">
                <label class="form-label fw-bold">Guru</label>
                <select name="teacher_id" class="form-select" onchange="this.form.submit()">
                    <option value="">-- Semua Guru --</option>
                    <?php foreach ($teachers as $t): ?>
                        <option value="<?= $t['id'] ?>" <?= $selected_teacher == $t['id'] ? 'selected' : '' ?>>
                            <?= esc($t['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-<?= !empty($teachers) ? '3' : '6' ?>">
                <label class="form-label fw-bold">Dari Tanggal</label>
                <input type="date" name="date_from" class="form-control" value="<?= esc($date_from ?? '') ?>">
            </div>
            <div class="col-md-<?= !empty($teachers) ? '3' : '6' ?>">
                <label class="form-label fw-bold">Sampai Tanggal</label>
                <input type="date" name="date_to" class="form-control" value="<?= esc($date_to ?? '') ?>">
            </div>
            <div class="col-md-<?= !empty($teachers) ? '3' : '6' ?> d-flex align-items-end">
                <button type="submit" class="btn btn-secondary me-2">🔍 Filter</button>
                <?php if ($selected_class || $selected_subject || $selected_teacher || $date_from || $date_to): ?>
                <a href="<?= base_url('admin/teaching-journal') ?>" class="btn btn-outline-secondary">🔄 Reset</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif; ?>
<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-hover table-bordered bg-white shadow-sm">
        <thead class="table-dark">
            <tr>
                <th style="width: 50px;">No</th>
                <th style="width: 120px;">Tanggal</th>
                <th style="width: 150px;">Kelas / Mapel</th>
                <th>Elemen / Materi</th>
                <th>Catatan Pembelajaran</th>
                <?php if (!$readonly): ?>
                <th style="width: 150px;">Aksi</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php if (!empty($journals)): ?>
                <?php $no = 1; foreach ($journals as $j): ?>
                    <tr>
                        <td class="text-center"><?= $no++ ?></td>
                        <td><?= date('d/m/Y', strtotime($j['date'])) ?></td>
                        <td>
                            <span class="badge bg-info text-dark"><?= esc($j['class_name']) ?></span><br>
                            <?php
                                // Ambil multi-subject dari pivot jika ada
                                $extraSubjects = db_connect()->table('teaching_journal_subjects tjs')
                                    ->select('s.name as subject_name')
                                    ->join('subjects s', 's.id = tjs.subject_id')
                                    ->where('tjs.journal_id', $j['id'])
                                    ->get()->getResultArray();
                                
                                if (!empty($extraSubjects)):
                                    foreach ($extraSubjects as $es):
                            ?>
                                <small class="text-muted d-block"><?= esc($es['subject_name']) ?></small>
                            <?php
                                    endforeach;
                                else:
                            ?>
                                <small class="text-muted"><?= esc($j['subject_name']) ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                                // Ambil multi-subject elemen dari pivot
                                $extraElements = db_connect()->table('teaching_journal_subjects tjs')
                                    ->select('s.name as subject_name, atp.lingkup_materi, atp.semester')
                                    ->join('subjects s', 's.id = tjs.subject_id')
                                    ->join('alur_tujuan_pembelajaran atp', 'atp.id = tjs.atp_id', 'left')
                                    ->where('tjs.journal_id', $j['id'])
                                    ->get()->getResultArray();
                                
                                if (!empty($extraElements)):
                                    foreach ($extraElements as $el):
                            ?>
                                <div class="mb-1">
                                    <strong><?= esc($el['subject_name']) ?></strong>
                                    <?php if (!empty($el['lingkup_materi'])): ?>
                                        <br><small class="text-muted">Sem<?= $el['semester'] ?> | <?= esc($el['lingkup_materi']) ?></small>
                                    <?php endif; ?>
                                </div>
                            <?php
                                    endforeach;
                                else:
                            ?>
                                <strong><?= esc($j['elemen'] ?? '-') ?></strong><br>
                                <small><?= esc($j['lingkup_materi'] ?? '-') ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex flex-wrap gap-1 mb-1">
                                <?php
                                    $status = $j['status_realisasi'] ?? 'terlaksana';
                                    if ($status === 'sebagian'):
                                ?>
                                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle"><i class="bi bi-exclamation-triangle me-1"></i>Sebagian</span>
                                <?php elseif ($status === 'tertunda'): ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-clock-history me-1"></i>Tertunda</span>
                                <?php else: ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-check-circle me-1"></i>Terlaksana</span>
                                <?php endif; ?>

                                <?php if (!empty($j['pertemuan_ke'])): ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><i class="bi bi-bookmark me-1"></i>Pertemuan <?= esc($j['pertemuan_ke']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-1"><?= nl2br(esc($j['notes'])) ?></div>
                            <?php if (!empty($j['rencana_snapshot'])): ?>
                                <?php $snap = json_decode($j['rencana_snapshot'], true); ?>
                                <?php if (!empty($snap['rencana_teks'])): ?>
                                    <details class="small mt-2 text-muted">
                                        <summary class="text-primary text-decoration-none fw-semibold" style="cursor: pointer; font-size: 0.8rem;">
                                            <i class="bi bi-journal-text me-1"></i> Lihat Rencana (<?= esc($snap['model'] ?? 'Rencana') ?><?= !empty($snap['durasi']) ? ' - ' . esc($snap['durasi']) : '' ?>)
                                        </summary>
                                        <div class="p-2 bg-light border rounded mt-1 text-secondary" style="font-size: 0.8rem; white-space: pre-wrap;"><?= esc($snap['rencana_teks']) ?></div>
                                    </details>
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <?php if (!$readonly): ?>
                        <td>
                            <a href="<?= base_url('admin/teaching-journal/edit/' . $j['id']) ?>" class="btn btn-sm btn-warning">✏️ Edit</a>
                            <a href="<?= base_url('admin/teaching-journal/delete/' . $j['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus jurnal ini?')">🗑️ Hapus</a>
                        </td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="<?= !$readonly ? '6' : '5' ?>" class="text-center py-4 text-muted italic">
                        Belum ada riwayat jurnal untuk filter ini.
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?= $this->endSection() ?>
