      <div class="col-lg-6 mb-4">
        <h2 class="section-title">Hubungi Kami</h2>
        <p>Bila ada pertanyaan jangan ragu untuk menghubungi kami</p>
        <div class="d-flex mb-2"><i class="fas fa-map-marker-alt me-2"></i><span><?= esc($school['address'] ?? 'Jl. Nama Jalan No. 123 Yogyakarta') ?></span></div>
        <div class="d-flex mb-2"><i class="fas fa-phone me-2"></i><span><?= esc($school['phone'] ?? '0274 - 1231231') ?></span></div>
        <div class="d-flex mb-2"><i class="fab fa-whatsapp me-2"></i><span><?= esc($school['phone'] ?? '081234567890') ?></span></div>
        <div class="pt-3">
          <?php foreach ($socials ?? [] as $soc): ?>
            <a href="<?= esc($soc['url']) ?>" class="btn btn-dark me-2"><i class="<?= esc($soc['icon']) ?>"></i></a>
          <?php endforeach ?>
        </div>
      </div>
    </div>
  </div>
</div>
