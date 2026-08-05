<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<h3>Halo, <?= esc($username) ?> 👋</h3>
<p>Selamat datang di Dashboard Pembayaran Sekolah.<br>
Karena Anda berada dihalaman ini berarti Anda mempunyai hak akses, bertanggungjawablah terhadap akses tersebut!</p>

<!-- 📊 Row: Summary Card -->
<div class="row mt-4 mb-4">
  <?php
    $cards = [
      ['title' => 'Siswa Aktif Tahun Ini', 'value' => $siswaAktif." Siswa", 'bg' => 'primary'],
      ['title' => 'Penerimaan Hari Ini', 'value' => 'Rp ' . number_format($penerimaanHariIni, 0, ',', '.'), 'bg' => 'success'],
      ['title' => 'Kasbon Belum Lunas', 'value' => 'Rp ' . number_format($totalKasbon, 0, ',', '.'), 'bg' => 'danger'],
      ['title' => 'Kas Real', 'value' => 'Rp ' . number_format($kasReal, 0, ',', '.'), 'bg' => 'dark'],
    ];
  ?>
  <?php foreach ($cards as $c): ?>
    <div class="col-md-3 mb-3">
      <div class="card text-bg-<?= $c['bg'] ?> h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h6 class="card-title"><?= $c['title'] ?></h6>
          <p class="card-text fs-5 fw-bold"><?= $c['value'] ?></p>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<!-- 💰 Row: Saldo per Pos Bayar -->
<div class="row mb-4">
  <?php foreach ($saldoPos as $pos): ?>
    <div class="col-md-3 mb-3">
      <div class="card border-primary h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h6 class="card-title text-primary">Saldo <?= esc($pos['nama']) ?></h6>
          <p class="card-text fw-bold">Rp <?= number_format($pos['saldo'], 0, ',', '.') ?></p>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<!-- 💸 Row: Tagihan per Pos Bayar -->
<div class="row">
  <?php foreach ($tagihanPerPos as $tagihan): ?>
    <div class="col-md-3 mb-3">
      <div class="card border-danger h-100">
        <div class="card-body d-flex flex-column justify-content-between">
          <h6 class="card-title text-danger">Tagihan <?= esc($tagihan['nama']) ?></h6>
          <p class="card-text">Rp <?= number_format($tagihan['jumlah'], 0, ',', '.') ?></p>
        </div>
      </div>
    </div>
  <?php endforeach ?>
</div>

<?= $this->endSection() ?>
