<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<style>
  .result-summary {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 1rem;
    margin-bottom: 1.5rem;
  }

  .question-card {
    margin-bottom: 1rem;
  }

  .question-text {
    white-space: pre-line;
    line-height: 1.4;
  }

  .question-text p {
    margin-bottom: 0 !important;
  }

  .question-text img {
    display: block;
    max-width: 100%;
    height: auto;
    margin: 0.5rem 0;
  }

  .option-list {
    list-style: none;
    padding: 0;
  }

  .option-item {
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 8px 12px;
    margin-bottom: 6px;
    background: #fff;
    white-space: pre-line;
    line-height: 1.3;
    display: flex;
    align-items: flex-start;
    gap: 8px;
  }

  .option-item p {
    margin-bottom: 0 !important;
  }

  .option-item img {
    display: block;
    margin: 6px;
    max-width: 100%;
    height: 160px;
    /* tinggi seragam */
    object-fit: contain;
    /* pertahankan rasio tanpa cropping */
    border-radius: 6px;
    background: #f8f9fa;
    /* beri latar abu lembut biar rapi */
    padding: 4px;
    border: 1px solid #dee2e6;
  }

  .option-item.correct {
    background: #d1e7dd;
    border-color: #0f5132;
  }

  .option-item.incorrect {
    background: #f8d7da;
    border-color: #842029;
  }

  .option-item.student-answer {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
  }

  .essay-answer {
    background: #fff;
    border: 1px solid #ccc;
    border-radius: 6px;
    padding: 10px;
    min-height: 100px;
    white-space: pre-wrap;
  }

  .answer-info {
    font-size: 0.9rem;
    margin-top: 6px;
  }

  .answer-info span {
    display: inline-block;
    margin-right: 12px;
  }

  .answer-info .correct {
    color: #0f5132;
    font-weight: 600;
  }

  .answer-info .wrong {
    color: #842029;
    font-weight: 600;
  }

  @media (max-width: 768px) {
    .option-item img {
      height: 120px;
    }
  }
</style>

<div class="container py-4">
  <div class="mb-4">
    <h4 class="fw-bold"><?= esc($test['exam_name'] ?? 'Hasil Ujian') ?></h4>
    <div class="text-muted">
      <small><?= esc($test['subject_name'] ?? '-') ?> &middot;
        Bank: <?= esc($test['bank_code'] ?? '-') ?></small>
    </div>
  </div>

  <!-- Ringkasan Nilai -->
  <div class="result-summary">
    <h5 class="mb-2">🎯 Nilai Anda</h5>
    <div class="fs-4 fw-bold text-primary"><?= esc($score) ?></div>
    <p class="text-muted mb-1">
      <strong>Rincian Nilai:</strong><br>
    <table class="table table-sm table-borderless w-auto text-muted mb-0">
      <tr>
        <td>PG</td>
        <td>: <?= number_format($nilai_pg ?? 0, 2) ?> x <?= $bobot_pg ?? 0 ?>%</td>
        <td>= <?= number_format(($nilai_pg * $bobot_pg / 100), 2) ?></td>
      </tr>
      <tr>
        <td>PG Kompleks</td>
        <td>: <?= number_format($nilai_pgk ?? 0, 2) ?> x <?= $bobot_pg_kompleks ?? 0 ?>%</td>
        <td>= <?= number_format(($nilai_pgk * $bobot_pg_kompleks / 100), 2) ?></td>
      </tr>
      <tr>
        <td>Benar/Salah</td>
        <td>: <?= number_format($nilai_bs ?? 0, 2) ?> x <?= $bobot_bs ?? 0 ?>%</td>
        <td>= <?= number_format(($nilai_bs * $bobot_bs / 100), 2) ?></td>
      </tr>
      <tr>
        <td>Esai</td>
        <td>: <?= number_format($nilai_esai ?? 0, 2) ?> x <?= $bobot_esai ?? 0 ?>%</td>
        <td>= <?= number_format(($nilai_esai * $bobot_esai / 100), 2) ?></td>
      </tr>
    </table>
    <hr class="my-1" style="width: 250px;">
    <strong>Total Akhir: <?= esc($score) ?></strong>
    </p>
    <p class="text-muted small mb-0">
      * Nilai esai mungkin belum final jika belum dinilai manual oleh guru.
    </p>
  </div>

  <div class="mb-4">
    <div><strong>Pilihan Ganda:</strong> Benar <?= $correct_pg ?>/<?= $total_pg ?></div>
    <div><strong>PG Kompleks:</strong> Terkumpul <?= number_format($correct_pgk, 2) ?> poin dari
      <?= $total_pg_kompleks ?> soal
    </div>
    <div><strong>Benar/Salah:</strong> Terkumpul <?= number_format($correct_bs, 2) ?> poin dari <?= $total_bs ?> soal
    </div>
    <div><strong>Esai:</strong> <?= $total_esai ?> soal</div>
  </div>

  <h5 class="mb-3">📘 Rincian Soal</h5>

  <?php if (!empty($details)): ?>
    <?php foreach ($details as $no => $q): ?>
      <div class="card question-card">
        <div class="card-body">
          <h6 class="mb-2">
            Soal <?= $no + 1 ?> (<?= strtoupper($q['type']) ?>)
          </h6>

          <!-- Teks Soal -->
          <div class="question-text mb-3">
            <?= $q['text'] ?>
          </div>

          <?php if (in_array($q['type'], ['pg', 'pilihan_ganda', 'pg_kompleks', 'pgk'])): ?>
            <ul class="option-list">
              <?php foreach ($q['options'] as $key => $optText):
                if (empty($optText))
                  continue;
                $studentKeys = explode(',', $q['answer'] ?? '');
                $correctKeys = explode(',', $q['correct_option'] ?? '');

                $isStudent = in_array($key, $studentKeys);
                $isCorrect = in_array($key, $correctKeys);

                $classes = [];
                if ($isStudent)
                  $classes[] = 'student-answer';
                if ($isCorrect)
                  $classes[] = 'correct';
                elseif ($isStudent && !$isCorrect)
                  $classes[] = 'incorrect';
                ?>
                <li class="option-item <?= implode(' ', $classes) ?>">
                  <strong><?= esc($key) ?>.</strong>
                  <div><?= strip_tags($optText, '<p><img><br><b><i><u><strong><span>') ?></div>
                </li>
              <?php endforeach; ?>
            </ul>

            <!-- Keterangan hasil -->
            <div class="answer-info">
              <span><strong>Jawaban Anda:</strong> <?= esc($q['answer'] ?: '-') ?></span>
              <span><strong>Kunci Jawaban:</strong> <?= esc($q['correct_option'] ?: '-') ?></span>
              <?php
              $finalScore = 0;
              $isPGK = ($q['type'] === 'pg_kompleks' || $q['type'] === 'pgk');

              if ($q['answer'] === $q['correct_option'] && $q['answer'] !== '') {
                $finalScore = 1;
              } elseif ($isPGK && !empty($q['answer']) && !empty($q['correct_option'])) {
                $studentKeys = explode(',', $q['answer']);
                $correctKeys = explode(',', $q['correct_option']);
                $matches = count(array_intersect($studentKeys, $correctKeys));
                $incorrects = count(array_diff($studentKeys, $correctKeys));
                $tCorr = count($correctKeys);

                $rawScore = $matches - (0.5 * $incorrects);
                $finalScore = $tCorr > 0 ? (max(0, $rawScore) / $tCorr) : 0;
              }

              if ($finalScore >= 1) {
                echo '<span class="correct">✅ Benar</span>';
              } elseif ($finalScore > 0) {
                echo '<span class="text-warning fw-bold">⚠️ Sebagian Benar (' . round($finalScore * 100) . '%)</span>';
              } else {
                echo '<span class="wrong">❌ Salah</span>';
              }
              ?>
            </div>

          <?php elseif ($q['type'] === 'benar_salah' || $q['type'] === 'bs'): ?>
            <div class="table-responsive">
              <table class="table table-bordered table-sm mt-2">
                <thead class="table-light">
                  <tr>
                    <th>Pernyataan</th>
                    <th class="text-center">Anda</th>
                    <th class="text-center">Kunci</th>
                    <th class="text-center">Hasil</th>
                  </tr>
                </thead>
                <tbody>
                  <?php
                  $stdAns = explode(',', $q['answer'] ?? '');
                  $keyAns = explode(',', $q['correct_option'] ?? '');
                  $rows = ['A', 'B', 'C', 'D', 'E'];
                  $totalItems = 0;
                  $matches = 0;
                  foreach ($rows as $idx => $rowKey):
                    if (empty($q['options'][$rowKey]))
                      continue;
                    $totalItems++;
                    $stdChar = $stdAns[$idx] ?? '';
                    $keyChar = $keyAns[$idx] ?? '';
                    $isMatch = ($stdChar === $keyChar && $stdChar !== '');
                    if ($isMatch)
                      $matches++;
                    ?>
                    <tr>
                      <td><?= strip_tags($q['options'][$rowKey]) ?></td>
                      <td class="text-center fw-bold"><?= $stdChar ?: '-' ?></td>
                      <td class="text-center fw-bold"><?= $keyChar ?></td>
                      <td class="text-center">
                        <?= $isMatch ? '✅' : '❌' ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="answer-info">
              <?php
              $scoreBS = ($totalItems > 0) ? ($matches / $totalItems) : 0;
              if ($scoreBS >= 1) {
                echo '<span class="correct">✅ Benar Semua</span>';
              } elseif ($scoreBS > 0) {
                echo '<span class="text-warning fw-bold">⚠️ Sebagian Benar (' . round($scoreBS * 100) . '%)</span>';
              } else {
                echo '<span class="wrong">❌ Salah Semua</span>';
              }
              ?>
            </div>

          <?php elseif (in_array($q['type'], ['esai', 'essay'])): ?>
            <div>
              <strong>Jawaban Anda:</strong>
              <div class="essay-answer mt-2">
                <?= $q['answer'] ? esc($q['answer']) : '<em>(Belum dijawab)</em>' ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <div class="alert alert-warning">Tidak ada data soal untuk ditampilkan.</div>
  <?php endif; ?>

  <div class="mt-4 text-end">
    <a href="<?= site_url('siswa/cbt') ?>" class="btn btn-secondary">
      <i class="bi bi-arrow-left"></i> Kembali ke Daftar Ujian
    </a>
  </div>
</div>

<?= $this->endSection() ?>