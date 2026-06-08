<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h3>📚 Materi Pembelajaran - <?= esc($subject['name']) ?></h3>

<a href="<?= site_url('admin/materials/create/' . $subject['id']) ?>" 
   class="btn btn-primary btn-sm mb-3">➕ Tambah Materi</a>

<table class="table table-bordered align-middle">
  <thead class="table-light">
    <tr>
      <th>#</th>
      <th>Tahun Ajaran</th>
      <th>Semester</th>
      <th>Judul</th>
      <th>Deskripsi</th>
      <th style="width: 180px;">Aksi</th>
    </tr>
  </thead>
  <tbody>
    <?php $no = 1; foreach ($materials as $m): ?>
      <tr id="row-<?= $m['id'] ?>">
        <td><?= $no++ ?></td>
        <td><?= esc($m['year_name']) ?></td>
        <td><?= ucfirst($m['semester']) ?></td>
        <td><?= esc($m['title']) ?></td>
        <td><?= esc($m['description']) ?: '-' ?></td>
        <td>
          <a href="<?= site_url('admin/materials/edit/' . $m['id']) ?>" 
             class="btn btn-warning btn-sm">✏️ Edit</a>

          <button 
            class="btn btn-danger btn-sm btn-delete"
            data-id="<?= $m['id'] ?>"
            data-title="<?= esc($m['title']) ?>"
            data-subject="<?= esc($subject['name']) ?>"
            data-semester="<?= ucfirst($m['semester']) ?>"
            data-description="<?= esc($m['description']) ?: '-' ?>"
          >🗑 Hapus</button>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<a href="<?= !empty($returnUrl) ? esc($returnUrl) : base_url('admin/assessments') ?>" 
   class="btn btn-secondary mt-3">
   ⬅ Kembali
</a>

<!-- 🗑 Modal Konfirmasi Hapus -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title" id="deleteModalLabel">🗑 Konfirmasi Hapus Materi</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>

      <div class="modal-body">
        <div class="alert alert-warning mb-0">
          <p>Apakah Anda yakin ingin menghapus materi berikut?</p>
          <ul class="mb-0">
            <li><strong>Mata Pelajaran:</strong> <span id="infoSubject"></span></li>
            <li><strong>Semester:</strong> <span id="infoSemester"></span></li>
            <li><strong>Judul:</strong> <span id="infoTitle"></span></li>
            <li><strong>Deskripsi:</strong> <span id="infoDescription"></span></li>
          </ul>
        </div>
      </div>

      <div class="modal-footer">
        <button id="confirmDeleteBtn" class="btn btn-danger">Ya, Hapus</button>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
      </div>
    </div>
  </div>
</div>

<!-- 🧠 Script untuk handle modal & AJAX delete -->
<script>
document.addEventListener('DOMContentLoaded', function () {
  let deleteId = null;
  const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
  const confirmBtn = document.getElementById('confirmDeleteBtn');

  const infoSubject = document.getElementById('infoSubject');
  const infoSemester = document.getElementById('infoSemester');
  const infoTitle = document.getElementById('infoTitle');
  const infoDescription = document.getElementById('infoDescription');

  // Saat tombol hapus diklik → tampilkan modal dengan info materi
  document.querySelectorAll('.btn-delete').forEach(btn => {
    btn.addEventListener('click', function () {
      deleteId = this.dataset.id;
      infoSubject.textContent = this.dataset.subject;
      infoSemester.textContent = this.dataset.semester;
      infoTitle.textContent = this.dataset.title;
      infoDescription.textContent = this.dataset.description;
      deleteModal.show();
    });
  });

  // Saat konfirmasi hapus diklik → kirim POST AJAX
  confirmBtn.addEventListener('click', async function () {
    if (!deleteId) return;

    confirmBtn.disabled = true;
    confirmBtn.textContent = 'Menghapus...';

    const response = await fetch(`<?= site_url('admin/materials/delete/') ?>${deleteId}`, {
      method: 'POST', // harus huruf besar
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: '<?= csrf_token() ?>=<?= csrf_hash() ?>'
    });

    const data = await response.json();
    confirmBtn.disabled = false;
    confirmBtn.textContent = 'Ya, Hapus';
    deleteModal.hide();

    if (data.status === 'success') {
      // Efek fade-out sebelum hilang
      const row = document.getElementById(`row-${deleteId}`);
      if (row) {
        row.style.transition = 'opacity 0.5s';
        row.style.opacity = '0';
        setTimeout(() => row.remove(), 500);
      }

      showToast('✅ ' + data.message, 'success');
    } else {
      showToast('❌ ' + data.message, 'danger');
    }
  });

  // Toast helper
  function showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
    toast.style.zIndex = 2000;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 2000);
  }
});
</script>

<?= $this->endSection() ?>
