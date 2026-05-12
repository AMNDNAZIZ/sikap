<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
  <h1 class="mt-4">Edit Kelas</h1>
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="<?= base_url('admin/classes') ?>">Manajemen Kelas</a></li>
      <li class="breadcrumb-item active">Edit Kelas</li>
    </ol>
  </nav>

  <div class="card shadow">
    <div class="card-body">
      <form action="<?= base_url('admin/classes/update/' . $class['id']) ?>" method="post">
        <?= csrf_field() ?>
        
        <?php if (session()->getFlashdata('error')): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-1"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
          </div>
        <?php endif; ?>

        <div class="alert alert-info">
          <i class="bi bi-info-circle me-2"></i>
          Level Sekolah: <strong><?= esc($school_level_name) ?></strong> | 
          Tingkat Kelas yang Diizinkan: <strong><?= esc($level_range) ?></strong>
        </div>

        <div class="mb-3">
          <label class="form-label">Nama Kelas <span class="text-danger">*</span></label>
          <input type="text" name="name" value="<?= old('name', $class['name']) ?>" class="form-control" required>
          <small class="text-muted">Masukkan nama kelas (misal: 7A untuk kelas 7 rombel A)</small>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Tingkat <span class="text-danger">*</span></label>
          <input type="number" name="level" value="<?= old('level', $class['level']) ?>" class="form-control" 
                 min="<?= $minLevel ?>" max="<?= $maxLevel ?>" required>
          <small class="text-muted">
            Tingkat kelas harus antara <strong><?= $minLevel ?></strong> dan <strong><?= $maxLevel ?></strong> 
            sesuai level sekolah <?= esc($school_level_name) ?>
          </small>
        </div>
        
        <div class="mb-3">
          <label class="form-label">Wali Kelas (Opsional)</label>
          <select name="teacher_id" class="form-select">
            <option value="">-- Pilih Guru --</option>
            <?php foreach ($teachers as $t): ?>
              <option value="<?= $t['id'] ?>" <?= old('teacher_id', $class['teacher_id']) == $t['id'] ? 'selected' : '' ?>>
                <?= esc($t['name']) ?>
              </option>
            <?php endforeach ?>
          </select>
        </div>
        
        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-success">
            <i class="bi bi-save"></i> Update
          </button>
          <a href="<?= base_url('admin/classes') ?>" class="btn btn-secondary">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->endSection() ?>