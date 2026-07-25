<div class="container pb-5">
  <div class="row">
    <?php foreach ($advantages ?? [] as $adv): ?>
      <div class="col-lg-3 col-md-6 mb-3">
        <div class="bg-light text-dark border text-center p-3 rounded">
          <div class="pb-3"><i class="<?= esc($adv['icon']) ?> fa-3x text-success"></i></div>
          <div class="h5 text-uppercase pb-2"><?= esc($adv['title']) ?></div>
          <div><?= esc($adv['desc']) ?></div>
        </div>
      </div>
    <?php endforeach ?>
  </div>
</div>
