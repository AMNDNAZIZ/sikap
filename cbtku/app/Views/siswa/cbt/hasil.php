<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<style>
  .hasil-container {
    max-width: 900px;
    margin: 0 auto;
    padding: 0.5rem 12px 2rem;
  }

  .hasil-header-box {
    background: #ffffff;
    border-radius: 18px;
    padding: 1.25rem 1.5rem;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.04);
    border: 1px solid #e2e8f0;
    margin-bottom: 1.25rem;
  }

  .result-summary-card {
    background: linear-gradient(135deg, #eff6ff 0%, #f0fdf4 100%);
    border: 1.5px solid #bfdbfe;
    border-radius: 18px;
    padding: 1.5rem;
    margin-bottom: 1.25rem;
    position: relative;
    overflow: hidden;
  }

  .score-big-display {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #ffffff;
    border-radius: 16px;
    padding: 10px 24px;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.12);
    border: 1px solid #dbeafe;
  }

  .score-number {
    font-size: 2.4rem;
    font-weight: 800;
    color: #0d6efd;
    line-height: 1;
  }

  .stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
    gap: 10px;
    margin-bottom: 1.5rem;
  }

  .stat-pill {
    background: #ffffff;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 14px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
  }

  .question-card {
    margin-bottom: 1rem;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    overflow: hidden;
    background: #ffffff;
  }

  .question-card-header {
    background: #f8fafc;
    border-bottom: 1px solid #f1f5f9;
    padding: 10px 16px;
    font-weight: 700;
    font-size: 0.95rem;
  }

  .question-text {
    white-space: pre-line;
    line-height: 1.6;
    color: #1e293b;
    font-size: 0.98rem;
  }

  .question-text p {
    margin-bottom: 0.5rem !important;
  }

  .question-text img {
    display: block;
    max-width: 100% !important;
    height: auto !important;
    margin: 0.75rem auto;
    border-radius: 8px;
  }

  .option-list {
    list-style: none;
    padding: 0;
    margin: 0 0 1rem 0;
  }

  .option-item {
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 10px 14px;
    margin-bottom: 8px;
    background: #ffffff;
    white-space: pre-line;
    line-height: 1.4;
    display: flex;
    align-items: flex-start;
    gap: 10px;
    font-size: 0.92rem;
    transition: all 0.15s ease;
  }

  .option-item p {
    margin-bottom: 0 !important;
  }

  .option-item img {
    display: block;
    margin: 8px 0;
    max-width: 100%;
    height: auto;
    max-height: 180px;
    object-fit: contain;
    border-radius: 6px;
    background: #f8fafc;
    padding: 4px;
    border: 1px solid #dee2e6;
  }

  .option-item.correct {
    background: #dcfce7 !important;
    border-color: #22c55e !important;
    color: #14532d;
    font-weight: 500;
  }

  .option-item.incorrect {
    background: #fee2e2 !important;
    border-color: #ef4444 !important;
    color: #7f1d1d;
  }

  .option-item.student-answer {
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.4);
    border-color: #0d6efd;
  }

  .essay-answer {
    background: #f8fafc;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    padding: 14px;
    min-height: 80px;
    white-space: pre-wrap;
    font-size: 0.95rem;
    color: #1e293b;
  }

  .answer-info {
    font-size: 0.88rem;
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px dashed #e2e8f0;
    display: flex;
    flex-wrap: wrap;
    gap: 8px 16px;
    align-items: center;
  }

  .answer-info .correct {
    color: #15803d;
    font-weight: 700;
  }

  .answer-info .wrong {
    color: #b91c1c;
    font-weight: 700;
  }

  .btn-back-cbt {
    border-radius: 50px;
    padding: 12px 24px;
    font-weight: 700;
    font-size: 0.95rem;
  }

  @media (max-width: 576px) {
    .hasil-container {
      padding: 0.25rem 8px 2rem;
    }

    .hasil-header-box {
      padding: 1rem;
      border-radius: 14px;
      margin-bottom: 0.75rem;
    }

    .hasil-header-box h4 {
      font-size: 1.15rem;
    }

    .result-summary-card {
      padding: 1rem;
      border-radius: 14px;
      margin-bottom: 0.75rem;
    }

    .score-number {
      font-size: 2rem;
    }

    .stats-grid {
      grid-template-columns: 1fr 1fr;
      gap: 6px;
      margin-bottom: 1rem;
    }

    .stat-pill {
      padding: 8px 10px;
      font-size: 0.8rem;
      border-radius: 10px;
    }

    .question-card {
      border-radius: 14px;
      margin-bottom: 0.75rem;
    }

    .question-card-header {
      padding: 8px 12px;
      font-size: 0.88rem;
    }

    .question-card .card-body {
      padding: 12px;
    }

    .question-text {
      font-size: 0.9rem;
    }

    .option-item {
      padding: 8px 10px;
      font-size: 0.85rem;
      border-radius: 10px;
      gap: 6px;
    }

    .btn-back-cbt {
      width: 100%;
    }
  }
</style>

<div class="hasil-container">
  <div class="hasil-header-box">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
      <div>
        <h4 class="fw-bold mb-1"><?= esc($test['exam_name'] ?? 'Hasil Ujian') ?></h4>
        <div class="text-muted small">
          <i class="bi bi-book me-1"></i><?= esc($test['subject_name'] ?? '-') ?> &middot;
          Kode: <?= esc($test['bank_code'] ?? '-') ?>
        </div>
      </div>
      <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 rounded-pill px-3 py-2 small">
        <i class="bi bi-person-check me-1"></i> Selesai Dikerjakan
      </span>
    </div>
  </div>

  <!-- Ringkasan Nilai -->
  <div class="result-summary-card">
    <div class="row align-items-center">
      <div class="col-md-7 mb-3 mb-md-0">
        <h6 class="fw-bold text-dark mb-2">🎯 Rincian Akumulasi Nilai:</h6>
        <div class="table-responsive">
          <table class="table table-sm table-borderless w-auto text-secondary small mb-0">
            <tr>
              <td class="fw-medium">Pilihan Ganda</td>
              <td>: <?= number_format($nilai_pg ?? 0, 2) ?> &times; <?= $bobot_pg ?? 0 ?>%</td>
              <td class="fw-bold text-dark">= <?= number_format(($nilai_pg * $bobot_pg / 100), 2) ?></td>
            </tr>
            <tr>
              <td class="fw-medium">PG Kompleks</td>
              <td>: <?= number_format($nilai_pgk ?? 0, 2) ?> &times; <?= $bobot_pg_kompleks ?? 0 ?>%</td>
              <td class="fw-bold text-dark">= <?= number_format(($nilai_pgk * $bobot_pg_kompleks / 100), 2) ?></td>
            </tr>
            <tr>
              <td class="fw-medium">Benar / Salah</td>
              <td>: <?= number_format($nilai_bs ?? 0, 2) ?> &times; <?= $bobot_bs ?? 0 ?>%</td>
              <td class="fw-bold text-dark">= <?= number_format(($nilai_bs * $bobot_bs / 100), 2) ?></td>
            </tr>
            <tr>
              <td class="fw-medium">Esai / Uraian</td>
              <td>: <?= number_format($nilai_esai ?? 0, 2) ?> &times; <?= $bobot_esai ?? 0 ?>%</td>
              <td class="fw-bold text-dark">= <?= number_format(($nilai_esai * $bobot_esai / 100), 2) ?></td>
            </tr>
          </table>
        </div>
        <p class="text-muted small mt-2 mb-0" style="font-size: 0.78rem;">
          * Nilai esai mungkin belum final bila ada penilaian manual oleh guru.
        </p>
      </div>

      <div class="col-md-5 text-center">
        <div class="text-muted small text-uppercase fw-bold mb-1" style="letter-spacing: 1px;">Skor Akhir</div>
        <div class="score-big-display">
          <span class="score-number"><?= esc($score) ?></span>
        </div>
      </div>
    </div>
  </div>

  <!-- Statistik Jawaban -->
  <div class="stats-grid">
    <div class="stat-pill">
      <div class="text-muted small">Pilihan Ganda</div>
      <div class="fw-bold text-dark">Benar <?= $correct_pg ?> / <?= $total_pg ?></div>
    </div>
    <div class="stat-pill">
      <div class="text-muted small">PG Kompleks</div>
      <div class="fw-bold text-dark"><?= number_format($correct_pgk, 2) ?> poin <small class="text-muted">(<?= $total_pg_kompleks ?> soal)</small></div>
    </div>
    <div class="stat-pill">
      <div class="text-muted small">Benar / Salah</div>
      <div class="fw-bold text-dark"><?= number_format($correct_bs, 2) ?> poin <small class="text-muted">(<?= $total_bs ?> soal)</small></div>
    </div>
    <div class="stat-pill">
      <div class="text-muted small">Esai / Uraian</div>
      <div class="fw-bold text-dark"><?= $total_esai ?> Soal</div>
    </div>
  </div>

  <h5 class="fw-bold mb-3 fs-6 text-secondary">
    <i class="bi bi-card-checklist me-1 text-primary"></i> Rincian Pembahasan & Jawaban
  </h5>

  <?php if (!empty($details)): ?>
    <?php foreach ($details as $no => $q): ?>
      <div class="card question-card">
        <div class="card-body">
          <h6 class="mb-2">
            Soal <?= $no + 1 ?> (<?= strtoupper($q['type']) ?>)
          </h6>

          <!-- Teks Soal -->
          <div class="question-text mb-3">
            <?= str_replace(['\\r\\n', '\\n', '\\r'], "\n", $q['text']) ?>
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
                if ($show_answer_key) {
                  if ($isCorrect)
                    $classes[] = 'correct';
                  elseif ($isStudent && !$isCorrect)
                    $classes[] = 'incorrect';
                }
                ?>
                <li class="option-item <?= implode(' ', $classes) ?>">
                  <strong><?= esc($key) ?>.</strong>
                  <div><?= strip_tags(str_replace(['\\r\\n', '\\n', '\\r'], "\n", $optText), '<p><img><br><b><i><u><strong><span>') ?></div>
                </li>
              <?php endforeach; ?>
            </ul>

            <!-- Keterangan hasil -->
            <div class="answer-info">
              <span><strong>Jawaban Anda:</strong> <?= esc($q['answer'] ?: '-') ?></span>
              <?php if ($show_answer_key): ?>
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
              <?php else: ?>
              <span class="text-muted fst-italic small"><i class="bi bi-lock-fill"></i> Kunci jawaban akan tersedia setelah semua peserta menyelesaikan ujian.</span>
              <?php endif; ?>
            </div>

          <?php elseif ($q['type'] === 'benar_salah' || $q['type'] === 'bs'): ?>
            <div class="table-responsive">
              <table class="table table-bordered table-sm mt-2">
                <thead class="table-light">
                  <tr>
                    <th>Pernyataan</th>
                    <th class="text-center">Anda</th>
                    <?php if ($show_answer_key): ?>
                    <th class="text-center">Kunci</th>
                    <th class="text-center">Hasil</th>
                    <?php endif; ?>
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
                      <?php if ($show_answer_key): ?>
                      <td class="text-center fw-bold"><?= $keyChar ?></td>
                      <td class="text-center">
                        <?= $isMatch ? '✅' : '❌' ?>
                      </td>
                      <?php endif; ?>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <div class="answer-info">
              <?php if ($show_answer_key):
              $scoreBS = ($totalItems > 0) ? ($matches / $totalItems) : 0;
              if ($scoreBS >= 1) {
                echo '<span class="correct">✅ Benar Semua</span>';
              } elseif ($scoreBS > 0) {
                echo '<span class="text-warning fw-bold">⚠️ Sebagian Benar (' . round($scoreBS * 100) . '%)</span>';
              } else {
                echo '<span class="wrong">❌ Salah Semua</span>';
              }
              else: ?>
              <span class="text-muted fst-italic small"><i class="bi bi-lock-fill"></i> Kunci jawaban akan tersedia setelah semua peserta menyelesaikan ujian.</span>
              <?php endif; ?>
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

  <div class="mt-4 text-center text-md-end">
    <a href="<?= site_url('siswa/cbt') ?>" class="btn btn-secondary btn-back-cbt shadow-sm">
      <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Ujian
    </a>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- 🔹 Load MathJax untuk Formula Matematika -->
<script>
  window.MathJax = {
    tex: {
      inlineMath: [['$', '$'], ['\\(', '\\)']],
      displayMath: [['$$', '$$'], ['\\[', '\\]']],
      processEscapes: true
    },
    options: { skipHtmlTags: ['script', 'noscript', 'style', 'textarea', 'pre'] },
    startup: {
      pageReady: () => {
        return MathJax.startup.defaultPageReady().catch(() => {
          return Promise.resolve();
        });
      }
    }
  };
</script>
<script src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-mml-chtml.js" async></script>
<?= $this->endSection() ?>