<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3>📓 Jurnal Mengajar</h3>
    <?php if (!$readonly): ?>
    <a href="<?= base_url('admin/teaching-journal/add' . ($selected_class ? '?class_id='.$selected_class : '') . ($selected_subject ? '&subject_id='.$selected_subject : '')) ?>" class="btn btn-primary">
        ➕ Tambah Jurnal
    </a>
    <?php endif; ?>
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
                            <small class="text-muted"><?= esc($j['subject_name']) ?></small>
                        </td>
                        <td>
                            <strong><?= esc($j['elemen']) ?></strong><br>
                            <small><?= esc($j['lingkup_materi']) ?></small>
                        </td>
                        <td><?= nl2br(esc($j['notes'])) ?></td>
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
