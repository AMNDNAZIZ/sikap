<div class="py-2">
  <div class="container text-center bg-success text-white py-5 rounded">
    <div class="fs-2 mb-3"><?= esc($ppdb_title ?? 'Segera Daftar!') ?></div>
    <p><?= esc($ppdb_desc ?? 'Penerimaan Peserta Didik Baru (PPDB) Tahun Pelajaran 2024-2025') ?></p>
    <div class="pt-2">
      <a href="<?= base_url('ppdb/info') ?>" class="btn btn-outline-light me-2">Info Lebih Lanjut</a>
      <a href="<?= base_url('ppdb/daftar') ?>" class="btn btn-outline-light">Daftar Sekarang</a>
    </div>
  </div>
</div>
