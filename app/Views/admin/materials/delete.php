<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>🗑 Hapus Materi</h3>

<div class="alert alert-warning">
  <p>Apakah Anda yakin ingin menghapus materi berikut?</p>
  <ul>
    <li><strong>Mata Pelajaran:</strong> <?= esc($subject['name']) ?></li>
    <li><strong>Semester:</strong> <?= ucfirst($material['semester']) ?></li>
    <li><strong>Judul:</strong> <?= esc($material['title']) ?></li>
    <li><strong>Deskripsi:</strong> <?= esc($material['description']) ?: '-' ?></li>
  </ul>
</div>

<form method="post" action="<?= site_url('admin/materials/delete/'.$material['id']) ?>">
  <?= csrf_field() ?>
  <button type="submit" class="btn btn-danger">Ya, Hapus</button>
  <a href="<?= site_url('admin/materials/'.$subject['id']) ?>" class="btn btn-secondary">Batal</a>
</form>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<script>
document.querySelector('form').addEventListener('submit', function(e) {
  console.log('Form dikirim!');
});
</script>
<?= $this->endSection() ?>