<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>
<?php if (isset($tahun) && $tahun === 'Belum diset'): ?>
  <div class="alert alert-warning">
    Belum ada tahun ajaran aktif. Silakan atur di menu Tahun Ajaran.
  </div>
<?php endif ?>

<h4>Arsip Siswa</h4>

<?php if (session()->getFlashdata('msg')): ?>
  <div class="alert alert-info"><?= session()->getFlashdata('msg') ?></div>
<?php endif; ?>

<form method="get" class="mb-3">
  <select name="status" class="form-select w-auto d-inline" onchange="this.form.submit()">
    <option value="lulus" <?= $status == 'lulus' ? 'selected' : '' ?>>Lulus</option>
    <option value="pindah" <?= $status == 'pindah' ? 'selected' : '' ?>>Pindah</option>
    <option value="do" <?= $status == 'do' ? 'selected' : '' ?>>Drop Out</option>
  </select>
</form>

<table class="table table-bordered">
  <thead>
    <tr>
      <th>#</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>Kelas Terakhir</th>
      <th>Status</th>
      <th>Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no=1; ?>
    <?php foreach ($siswa as $s): ?>
    <tr>
      <td><?= $no++; ?></td>
      <td><?= esc($s['nis']) ?></td>
      <td><?= esc($s['nama']) ?></td>
      <td><?= esc($s['nama_kelas'] ?? '-') ?></td>
      <td><?= ucfirst($s['status']) ?></td>
      <td>
        <a href="<?= site_url("admin/arsip-siswa/aktifkan/{$s['id']}") ?>"
           class="btn btn-sm btn-primary"
           onclick="return confirm('Yakin mengaktifkan kembali siswa ini?')">
          Kembalikan ke Aktif
        </a>
      </td>
    </tr>
    <?php endforeach ?>
  </tbody>
</table>

<!-- ✅ Paginasi -->
<?php if (isset($totalPages) && $totalPages > 1): ?>
  <nav>
    <ul class="pagination">
      <?php for ($i = 1; $i <= $totalPages; $i++): ?>
        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
          <a class="page-link" href="?status=<?= esc($status) ?>&page=<?= $i ?>"><?= $i ?></a>
        </li>
      <?php endfor ?>
    </ul>
  </nav>
<?php elseif (isset($pager) && $pager): ?>
  <div class="mt-3">
    <?= $pager->links('default', 'bootstrap') ?>
  </div>
<?php endif ?>

<?= $this->endSection() ?>
