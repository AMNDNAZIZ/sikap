<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $score      = (float) ($session['total_score'] ?? $session['score'] ?? 0);
  $pct        = $totalCount > 0 ? round(($correctCount / $totalCount) * 100) : 0;
  $scoreColor = $score >= 80 ? 'success' : ($score >= 60 ? 'warning' : 'danger');
  $duration   = $session['finished_at'] && $session['started_at']
                  ? max(0, (int)$session['finished_at'] - (int)$session['started_at'])
                  : 0;
  $durLabel   = $duration > 0
                  ? floor($duration/60).'m '.($duration%60).'d'
                  : '—';
?>

<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= esc($backUrl) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left"></i>
  </a>
  <h5 class="fw-bold mb-0 flex-grow-1 text-truncate"><?= esc($title) ?></h5>
</div>

<!-- ── Kartu Skor ──────────────────────────────────────────────────── -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-body text-center py-4">

    <!-- Skor besar -->
    <div class="mb-1 text-muted small">Nilai Kamu</div>
    <div class="fw-bold text-<?= $scoreColor ?>" style="font-size:3rem; line-height:1.1;">
      <?= number_format($score, 1) ?>
    </div>
    <div class="text-muted small mb-3">dari 100</div>

    <!-- Progress bar nilai -->
    <div class="progress mx-auto mb-3" style="height:10px; max-width:260px; border-radius:99px;">
      <div class="progress-bar bg-<?= $scoreColor ?>" role="progressbar"
           style="width:<?= $score ?>%; border-radius:99px;"></div>
    </div>

    <!-- Badge + emoji -->
    <div class="mb-3">
      <?php if ($score >= 90): ?>
        <span class="badge bg-success fs-6">🏆 Luar Biasa!</span>
      <?php elseif ($score >= 80): ?>
        <span class="badge bg-success fs-6">⭐ Bagus Sekali!</span>
      <?php elseif ($score >= 70): ?>
        <span class="badge bg-primary fs-6">👍 Cukup Bagus</span>
      <?php elseif ($score >= 60): ?>
        <span class="badge bg-warning text-dark fs-6">📚 Perlu Belajar Lagi</span>
      <?php else: ?>
        <span class="badge bg-danger fs-6">💪 Ayo Semangat!</span>
      <?php endif; ?>
    </div>

    <!-- Statistik ringkas -->
    <div class="row g-2 justify-content-center" style="max-width:380px; margin:0 auto;">
      <div class="col-4">
        <div class="bg-success-subtle rounded p-2">
          <div class="fw-bold text-success"><?= $correctCount ?></div>
          <div class="text-muted" style="font-size:0.72rem;">Benar</div>
        </div>
      </div>
      <div class="col-4">
        <div class="bg-danger-subtle rounded p-2">
          <div class="fw-bold text-danger"><?= $totalCount - $correctCount ?></div>
          <div class="text-muted" style="font-size:0.72rem;">Salah/Kosong</div>
        </div>
      </div>
      <div class="col-4">
        <div class="bg-secondary-subtle rounded p-2">
          <div class="fw-bold text-secondary"><?= $durLabel ?></div>
          <div class="text-muted" style="font-size:0.72rem;">Durasi</div>
        </div>
      </div>
    </div>

    <!-- Tombol aksi -->
    <div class="d-flex justify-content-center gap-2 mt-3 flex-wrap">
      <?php if ($quiz['can_retry'] ?? false): ?>
        <form method="post" action="<?= base_url('siswa/quiz/' . $quiz['id'] . '/mulai') ?>">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-arrow-repeat me-1"></i>Kerjakan Ulang
          </button>
        </form>
      <?php endif; ?>
      <a href="<?= esc($backUrl) ?>"
         class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-<?= $quiz['material_id'] ? 'book' : 'list' ?> me-1"></i>
        <?= $quiz['material_id'] ? 'Kembali ke Materi' : 'Kembali ke Daftar' ?>
      </a>
    </div>

  </div>
</div>

<!-- ── Riwayat Attempt ─────────────────────────────────────────────── -->
<?php if (count($history) > 1): ?>
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white border-bottom py-2">
    <span class="fw-semibold small"><i class="bi bi-clock-history me-2 text-secondary"></i>Riwayat Pengerjaan</span>
  </div>
  <div class="list-group list-group-flush">
    <?php foreach ($history as $h): ?>
      <?php
        $hScore = (float)($h['total_score'] ?? $h['score'] ?? 0);
        $hColor = $hScore >= 80 ? 'success' : ($hScore >= 60 ? 'warning' : 'danger');
        $isCurrent = $h['id'] == $session['id'];
      ?>
      <div class="list-group-item px-3 py-2 d-flex justify-content-between align-items-center small
                  <?= $isCurrent ? 'bg-primary-subtle' : '' ?>">
        <div>
          <span class="fw-semibold">Attempt #<?= $h['attempt_number'] ?></span>
          <?= $isCurrent ? '<span class="badge bg-primary ms-1" style="font-size:0.65rem;">Sekarang</span>' : '' ?>
          <div class="text-muted" style="font-size:0.72rem;">
            <?= $h['finished_at'] ? date('d M Y H:i', (int)$h['finished_at']) : '—' ?>
          </div>
        </div>
        <span class="badge bg-<?= $hColor ?>-subtle text-<?= $hColor ?> fw-bold" style="font-size:0.85rem;">
          <?= number_format($hScore, 1) ?>
        </span>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ── Pembahasan Per Soal ─────────────────────────────────────────── -->
<?php if ($showAnswer && !empty($details)): ?>
<div class="mb-3 d-flex align-items-center justify-content-between">
  <h6 class="fw-bold mb-0"><i class="bi bi-journal-check me-2 text-primary"></i>Pembahasan Jawaban</h6>
  <span class="small text-muted"><?= count($details) ?> soal</span>
</div>

<?php foreach ($details as $di => $d): ?>
  <?php
    $q         = $d['question'];
    $isCorrect = $d['is_correct'];
    $typeNorm  = $d['type_norm'];
    $given     = $d['given'];
    $correct   = $d['correct'];
    $images    = $q['media_images'] ?? [];
  ?>
  <div class="card border-0 shadow-sm mb-3 border-start border-4
              border-<?= $typeNorm === 'esai' ? 'secondary' : ($isCorrect ? 'success' : 'danger') ?>">
    <div class="card-body p-3">

      <!-- Header soal -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-secondary">Soal <?= $di + 1 ?></span>
        <div class="d-flex gap-1 align-items-center">
          <span class="badge bg-light text-secondary border" style="font-size:0.65rem;">
            <?= match($typeNorm) { 'pgk'=>'PG Kompleks','bs'=>'Benar/Salah','esai'=>'Esai', default=>'PG' } ?>
          </span>
          <?php if ($typeNorm !== 'esai'): ?>
            <span class="badge <?= $isCorrect ? 'bg-success' : 'bg-danger' ?>">
              <?= $isCorrect ? '✓ Benar' : '✗ Salah' ?>
            </span>
          <?php else: ?>
            <span class="badge bg-secondary">Belum dinilai</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Teks soal -->
      <div style="font-size:0.85rem; line-height:1.65;" class="mb-2">
        <?= $q['question_text'] ?: ($q['raw_text'] ?? '') ?>
      </div>

      <?php if (!empty($images)): ?>
        <div class="mb-2 d-flex flex-wrap gap-2">
          <?php foreach ($images as $img): ?>
            <img src="<?= base_url('uploads/cbt/' . esc($img)) ?>"
                 class="img-fluid rounded border" style="max-height:160px;" alt="">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- Opsi + highlight benar/salah -->
      <?php if ($typeNorm === 'pg' && !empty($q['options'])): ?>
        <?php foreach ($q['options'] as $key => $text): ?>
          <?php
            $isGiven   = strtoupper($given) === $key;
            $isCorrectOpt = strtoupper($correct) === $key;
            $optClass  = '';
            if ($isCorrectOpt) $optClass = 'bg-success-subtle border-success';
            elseif ($isGiven && !$isCorrectOpt) $optClass = 'bg-danger-subtle border-danger';
          ?>
          <div class="d-flex align-items-start gap-2 py-1 px-2 mb-1 rounded border <?= $optClass ?>"
               style="font-size:0.82rem;">
            <strong class="flex-shrink-0"><?= $key ?>.</strong>
            <span><?= $text ?></span>
            <span class="ms-auto flex-shrink-0">
              <?= $isCorrectOpt ? '<i class="bi bi-check-circle-fill text-success"></i>' : '' ?>
              <?= ($isGiven && !$isCorrectOpt) ? '<i class="bi bi-x-circle-fill text-danger"></i>' : '' ?>
            </span>
          </div>
        <?php endforeach; ?>

      <?php elseif ($typeNorm === 'pgk'): ?>
        <div class="small mb-1 text-muted">
          Jawabanmu: <strong><?= esc($given ?: '—') ?></strong><br>
          Kunci: <strong class="text-success"><?= esc($correct) ?></strong>
        </div>

      <?php elseif ($typeNorm === 'bs'): ?>
        <?php
          $cArr = explode(',', $correct);
          $sArr = $given !== '' ? explode(',', $given) : [];
          $bsKeys = array_keys($q['options'] ?? []);
        ?>
        <?php foreach ($bsKeys as $bi => $bk): ?>
          <?php
            $cVal = strtoupper(trim($cArr[$bi] ?? ''));
            $sVal = strtoupper(trim($sArr[$bi] ?? ''));
            $bsMatch = $sVal !== '' && $sVal === $cVal;
          ?>
          <div class="d-flex align-items-start gap-2 py-1 px-2 mb-1 rounded border
                      <?= $bsMatch ? 'bg-success-subtle border-success' : 'bg-danger-subtle border-danger' ?>"
               style="font-size:0.82rem;">
            <span class="flex-grow-1"><?= $q['options'][$bk] ?? '' ?></span>
            <span class="flex-shrink-0 text-muted">
              Kunci: <strong class="text-success"><?= $cVal ?: '—' ?></strong>
              <?php if ($sVal): ?>
                | Jawabanmu: <strong class="text-<?= $bsMatch ? 'success' : 'danger' ?>"><?= $sVal ?></strong>
              <?php else: ?>
                | <em class="text-muted">kosong</em>
              <?php endif; ?>
            </span>
          </div>
        <?php endforeach; ?>

      <?php elseif ($typeNorm === 'esai'): ?>
        <div class="bg-light rounded p-2" style="font-size:0.82rem;">
          <div class="text-muted small mb-1">Jawabanmu:</div>
          <div><?= $given ? nl2br(esc($given)) : '<em class="text-muted">Tidak dijawab</em>' ?></div>
          <?php if (!empty($q['essay_answer'])): ?>
            <div class="text-muted small mt-2 mb-1">Kunci / Contoh Jawaban:</div>
            <div class="text-success"><?= nl2br(esc($q['essay_answer'])) ?></div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div><!-- /.card-body -->
  </div>
<?php endforeach; ?>

<?php elseif (!$showAnswer): ?>
<div class="alert alert-secondary py-2 small">
  <i class="bi bi-eye-slash me-1"></i>Pembahasan jawaban tidak ditampilkan untuk kuis ini.
</div>
<?php endif; ?>

<!-- Tombol kembali bawah -->
<div class="text-center mt-3 mb-4">
  <a href="<?= esc($backUrl) ?>" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-<?= $quiz['material_id'] ? 'book' : 'list' ?> me-1"></i>
    <?= $quiz['material_id'] ? 'Kembali ke Materi' : 'Kembali ke Daftar Kuis' ?>
  </a>
</div>

<?= $this->endSection() ?>
