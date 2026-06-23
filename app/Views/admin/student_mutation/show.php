<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="mb-3">
  <a href="<?= base_url('admin/student-mutation') ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-arrow-left"></i> Kembali
  </a>
</div>

<?php if (session()->getFlashdata('success')): ?>
  <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
<?php endif ?>
<?php if (session()->getFlashdata('error')): ?>
  <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
<?php endif ?>

<?php
  $typeLabel = ['masuk' => 'Mutasi Masuk', 'keluar' => 'Mutasi Keluar', 'pindah_kelas' => 'Pindah Kelas'];
  $typeBadge = ['masuk' => 'success', 'keluar' => 'warning', 'pindah_kelas' => 'info'];
  $statusBadge = ['pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger'];
  $statusLabel = ['pending' => 'Pending', 'approved' => 'Disetujui', 'rejected' => 'Ditolak'];
?>

<h4>📋 Detail Mutasi</h4>

<div class="row">
  <div class="col-md-8">
    <div class="card mb-3">
      <div class="card-header d-flex justify-content-between">
        <span class="fw-bold">Informasi Mutasi</span>
        <span class="badge bg-<?= $statusBadge[$mutation['status']] ?>"><?= $statusLabel[$mutation['status']] ?></span>
      </div>
      <div class="card-body">
        <table class="table table-borderless mb-0">
          <tr>
            <th width="30%">Jenis Mutasi</th>
            <td><span class="badge bg-<?= $typeBadge[$mutation['type']] ?>"><?= $typeLabel[$mutation['type']] ?></span></td>
          </tr>
          <tr>
            <th>Tanggal Mutasi</th>
            <td><?= date('d/m/Y', strtotime($mutation['mutation_date'])) ?></td>
          </tr>
          <?php if ($mutation['from_school']): ?>
          <tr>
            <th>Sekolah Asal</th>
            <td><?= esc($mutation['from_school']) ?></td>
          </tr>
          <?php endif ?>
          <?php if ($mutation['to_school']): ?>
          <tr>
            <th>Sekolah Tujuan</th>
            <td><?= esc($mutation['to_school']) ?></td>
          </tr>
          <?php endif ?>
          <?php if ($mutation['from_class_name']): ?>
          <tr>
            <th>Kelas Asal</th>
            <td><?= esc($mutation['from_class_name']) ?></td>
          </tr>
          <?php endif ?>
          <?php if ($mutation['to_class_name']): ?>
          <tr>
            <th>Kelas Tujuan</th>
            <td><?= esc($mutation['to_class_name']) ?></td>
          </tr>
          <?php endif ?>
          <?php if ($mutation['letter_number']): ?>
          <tr>
            <th>Nomor Surat</th>
            <td><?= esc($mutation['letter_number']) ?></td>
          </tr>
          <?php endif ?>
          <?php if ($mutation['reason']): ?>
          <tr>
            <th>Alasan</th>
            <td><?= nl2br(esc($mutation['reason'])) ?></td>
          </tr>
          <?php endif ?>
          <?php if ($mutation['note'] && $mutation['status'] === 'rejected'): ?>
          <tr>
            <th>Catatan Penolakan</th>
            <td class="text-danger"><?= nl2br(esc($mutation['note'])) ?></td>
          </tr>
          <?php endif ?>
          <?php if ($mutation['approved_by_name']): ?>
          <tr>
            <th>Diproses Oleh</th>
            <td><?= esc($mutation['approved_by_name']) ?>
                <small class="text-muted">(<?= $mutation['approved_at'] ? date('d/m/Y H:i', strtotime($mutation['approved_at'])) : '-' ?>)</small>
            </td>
          </tr>
          <?php endif ?>
        </table>
      </div>
    </div>

    <?php if ($mutation['student_id'] && $mutation['student_name']): ?>
    <div class="card mb-3">
      <div class="card-header fw-bold">Data Siswa</div>
      <div class="card-body">
        <table class="table table-borderless mb-0">
          <tr>
            <th width="30%">Nama</th>
            <td><?= esc($mutation['student_name']) ?></td>
          </tr>
          <tr>
            <th>NISN</th>
            <td><?= esc($mutation['nisn'] ?? '-') ?></td>
          </tr>
          <tr>
            <th>NIS</th>
            <td><?= esc($mutation['nis'] ?? '-') ?></td>
          </tr>
          <tr>
            <th>Jenis Kelamin</th>
            <td><?= ($mutation['gender'] ?? '') == 'L' ? 'Laki-laki' : 'Perempuan' ?></td>
          </tr>
          <?php if (!empty($mutation['birth_place']) || !empty($mutation['birth_date'])): ?>
          <tr>
            <th>TTL</th>
            <td><?= esc($mutation['birth_place'] ?? '') ?><?= $mutation['birth_date'] ? ', ' . date('d/m/Y', strtotime($mutation['birth_date'])) : '' ?></td>
          </tr>
          <?php endif ?>
        </table>
      </div>
    </div>
    <?php endif ?>
  </div>

  <div class="col-md-4">
    <!-- Aksi -->
    <div class="card mb-3">
      <div class="card-header fw-bold">Aksi</div>
      <div class="card-body d-grid gap-2">
        <?php if ($mutation['status'] === 'pending'): ?>
          <form method="post" action="<?= base_url('admin/student-mutation/approve/' . $mutation['id']) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-success w-100 mb-2"
                    onclick="return confirm('Setujui mutasi ini? Data siswa akan diperbarui.')">
              <i class="bi bi-check-circle"></i> Setujui
            </button>
          </form>

          <button type="button" class="btn btn-danger w-100" data-bs-toggle="modal" data-bs-target="#rejectModal">
            <i class="bi bi-x-circle"></i> Tolak
          </button>
        <?php endif ?>

        <?php if ($mutation['status'] === 'approved'): ?>
          <a href="<?= base_url('admin/student-mutation/print/' . $mutation['id']) ?>"
             class="btn btn-danger w-100" target="_blank">
            <i class="bi bi-printer"></i> Cetak Surat
          </a>
        <?php endif ?>

        <?php if ($mutation['status'] !== 'approved'): ?>
          <form method="post" action="<?= base_url('admin/student-mutation/delete/' . $mutation['id']) ?>"
                onsubmit="return confirm('Yakin hapus mutasi ini?')">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger w-100 mt-2">
              <i class="bi bi-trash"></i> Hapus
            </button>
          </form>
        <?php endif ?>
      </div>
    </div>

    <?php if (!empty($mutation['attachment'])): ?>
    <div class="card mb-3">
      <div class="card-header fw-bold">Dokumen Pendukung</div>
      <div class="card-body">
        <?php
          $ext = pathinfo($mutation['attachment'], PATHINFO_EXTENSION);
          $isImage = in_array(strtolower($ext), ['jpg', 'jpeg', 'png']);
        ?>
        <?php if ($isImage): ?>
          <img src="<?= base_url($mutation['attachment']) ?>" class="img-fluid rounded" alt="Dokumen">
        <?php else: ?>
          <a href="<?= base_url($mutation['attachment']) ?>" target="_blank" class="btn btn-outline-primary w-100">
            <i class="bi bi-file-pdf"></i> Lihat Dokumen
          </a>
        <?php endif ?>
      </div>
    </div>
    <?php endif ?>
  </div>
</div>

<!-- Modal Reject -->
<?php if ($mutation['status'] === 'pending'): ?>
<div class="modal fade" id="rejectModal" tabindex="-1">
  <div class="modal-dialog">
    <form method="post" action="<?= base_url('admin/student-mutation/reject/' . $mutation['id']) ?>">
      <?= csrf_field() ?>
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Tolak Mutasi</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <label class="form-label">Catatan Penolakan</label>
          <textarea name="note" class="form-control" rows="3" placeholder="Alasan penolakan..."></textarea>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-danger">Tolak Mutasi</button>
        </div>
      </div>
    </form>
  </div>
</div>
<?php endif ?>

<?= $this->endSection() ?>
