<div class="col-xxl-7 col-lg-6 mb-3">
  <div class="h5 text-uppercase">Artikel & Kegiatan Sekolah</div>
  <?php foreach ($articles ?? [] as $article): ?>
    <div class="d-flex py-3 border-bottom">
      <div class="me-3"><img src="<?= base_url('uploads/articles/'.$article['thumb']) ?>" class="rounded" width="64" alt="<?= esc($article['title']) ?>"></div>
      <div>
        <div><a href="<?= base_url('artikel/'.$article['slug']) ?>" class="text-decoration-none text-dark"><?= esc($article['title']) ?></a></div>
        <div class="small text-secondary"><?= esc($article['excerpt']) ?></div>
      </div>
    </div>
  <?php endforeach ?>
  <div class="pt-3">
    <a href="<?= base_url('artikel') ?>" class="btn btn-dark btn-sm">Selengkapnya</a>
  </div>
</div>
</div>
</div>
