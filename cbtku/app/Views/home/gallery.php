<div class="container pb-5">
  <div class="row pb-3 align-items-top">
    <div class="col-xxl-5 col-lg-6 mb-3">
      <div class="bg-dark text-white p-3 rounded">
        <div class="h5 text-uppercase pb-2">Gallery Sekolah</div>
        <div id="carouselGallery" class="carousel slide" data-bs-ride="carousel">
          <div class="carousel-inner">
            <?php foreach ($gallery ?? [] as $i => $img): ?>
              <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                <img src="<?= base_url('uploads/gallery/'.$img['image']) ?>" class="d-block w-100 rounded" alt="gallery">
              </div>
            <?php endforeach ?>
          </div>
          <button class="carousel-control-prev" type="button" data-bs-target="#carouselGallery" data-bs-slide="prev">
            <span class="carousel-control-prev-icon"></span>
          </button>
          <button class="carousel-control-next" type="button" data-bs-target="#carouselGallery" data-bs-slide="next">
            <span class="carousel-control-next-icon"></span>
          </button>
        </div>
      </div>
    </div>
