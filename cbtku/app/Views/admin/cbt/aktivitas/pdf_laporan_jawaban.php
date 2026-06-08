<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #222;
      margin: 20px;
    }

    h3 {
      margin-bottom: 0;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .table th,
    .table td {
      border: 1px solid #ccc;
      padding: 6px;
      vertical-align: top;
    }

    .table th {
      background: #f0f0f0;
      text-align: center;
    }

    .option-list {
      list-style: none;
      padding-left: 10px;
      margin: 0;
    }

    .option-item {
      padding: 3px 0;
      margin-bottom: 4px;
    }

    .correct {
      color: green;
      font-weight: bold;
    }

    .wrong {
      color: red;
      font-weight: bold;
    }

    .question-text img,
    .option-item img {
      display: block !important;
      /* 🔥 Paksa tampil di baris sendiri */
      margin: 10px auto !important;
      /* 🔥 Beri jarak & tengah */
      max-width: 95% !important;
      height: auto !important;
      page-break-inside: avoid;
    }

    .question-text p img,
    .option-item p img {
      display: block !important;
      margin: 10px auto !important;
    }

    .question-text br+img,
    .option-item br+img {
      display: block !important;
      margin-top: 8px !important;
    }

    .question-text {
      margin-bottom: 8px;
      line-height: 1.4;
    }

    .essay-answer {
      border: 1px solid #ccc;
      padding: 6px;
      border-radius: 5px;
      background: #fafafa;
      margin-top: 5px;
    }

    .page-break {
      page-break-before: always;
    }
  </style>
</head>

<body>

  <h3><?= esc($test['exam_name']) ?></h3>
  <p>
    <strong>Mata Pelajaran:</strong> <?= esc($test['subject_name']) ?><br>
    <strong>Bank Soal:</strong> <?= esc($test['bank_code']) ?><br>
    <strong>Nama:</strong> <?= esc($student['name']) ?><br>
    <strong>Kelas:</strong> <?= esc($student['class_name']) ?>
  </p>

  <hr>

  <h4>📊 Rekap Nilai</h4>
  <table class="table">
    <tr>
      <th>Komponen</th>
      <th>Nilai (0-100)</th>
      <th>Bobot (%)</th>
      <th>Nilai Akhir</th>
    </tr>
    <tr>
      <td>Pilihan Ganda</td>
      <td><?= $nilai_pg_raw ?></td>
      <td><?= $bobot_pg ?></td>
      <td><?= $nilai_pg_akhir ?></td>
    </tr>
    <tr>
      <td>PG Kompleks</td>
      <td><?= $nilai_pgk_raw ?></td>
      <td><?= $bobot_pgk ?></td>
      <td><?= $nilai_pgk_akhir ?></td>
    </tr>
    <tr>
      <td>Benar / Salah</td>
      <td><?= $nilai_bs_raw ?></td>
      <td><?= $bobot_bs ?></td>
      <td><?= $nilai_bs_akhir ?></td>
    </tr>
    <tr>
      <td>Esai</td>
      <td><?= $nilai_esai_raw ?></td>
      <td><?= $bobot_esai ?></td>
      <td><?= $nilai_esai_akhir ?></td>
    </tr>
    <tr>
      <td colspan="3" style="text-align: right;"><strong>Total Akhir</strong></td>
      <td><strong><?= $nilai_total ?></strong></td>
    </tr>
  </table>

  <hr>
  <h4>📘 Rincian Jawaban</h4>

  <?php foreach ($questions as $i => $q): ?>
    <div style="margin-bottom: 20px; page-break-inside: avoid;">
      <strong><?= $i + 1 ?>. <?= strtoupper(str_replace('_', ' ', $q['type'])) ?></strong><br>

      <!-- Teks Soal -->
      <div class="question-text">
        <?= $q['text'] ?>
      </div>

      <?php if (in_array($q['type'], ['pg', 'pilihan_ganda', 'multiple_choice'])): ?>
        <ul class="option-list">
          <?php foreach ($q['options'] as $key => $val):
            if (!$val)
              continue; ?>
            <li
              class="option-item <?= ($key == $q['correct_option']) ? 'correct' : (($key == $q['answer'] && $key != $q['correct_option']) ? 'wrong' : '') ?>">
              <strong><?= $key ?>.</strong>
              <?= strip_tags($val, '<b><i><u><p><br><img>') ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <p>
          Jawaban: <strong><?= esc($q['answer'] ?: '-') ?></strong> |
          Kunci: <strong><?= esc($q['correct_option'] ?: '-') ?></strong> |
          <?= (strtoupper($q['answer']) == strtoupper($q['correct_option'])) ? '<span class="correct">Benar</span>' : '<span class="wrong">Salah</span>' ?>
        </p>

      <?php elseif ($q['type'] === 'pg_kompleks' || $q['type'] === 'pgk'): ?>
        <ul class="option-list">
          <?php foreach ($q['options'] as $key => $val):
            if (!$val)
              continue;
            $correctOptions = explode(',', strtoupper($q['correct_option']));
            $studentAnswers = !empty($q['answer']) ? explode(',', strtoupper($q['answer'])) : [];
            $isCorrectOption = in_array($key, $correctOptions);
            $isSelected = in_array($key, $studentAnswers);

            $class = '';
            if ($isCorrectOption) {
              $class = 'correct';
            } elseif ($isSelected && !$isCorrectOption) {
              $class = 'wrong';
            }
            ?>
            <li class="option-item <?= $class ?>">
              <strong><?= $key ?>.</strong>
              <?= strip_tags($val, '<b><i><u><p><br><img>') ?>
              <?php if ($isSelected): ?><strong> [Dipilih]</strong><?php endif; ?>
            </li>
          <?php endforeach; ?>
        </ul>

        <p>
          Jawaban: <strong><?= esc($q['answer'] ?: '-') ?></strong> |
          Kunci: <strong><?= esc($q['correct_option'] ?: '-') ?></strong> |
          <?= ($q['is_correct'] ?? false) ? '<span class="correct">Sesuai (≥70%)</span>' : '<span class="wrong">Tidak Sesuai</span>' ?>
        </p>

      <?php elseif ($q['type'] === 'benar_salah' || $q['type'] === 'bs'): ?>
        <ul class="option-list">
          <?php
          $cArr = explode(',', strtoupper($q['correct_option']));
          $sArr = explode(',', strtoupper($q['answer']));
          $idx = 0;
          foreach ($q['options'] as $key => $val):
            if (!$val)
              continue;
            $isCorrectItem = isset($sArr[$idx]) && $sArr[$idx] === ($cArr[$idx] ?? '');
            ?>
            <li class="option-item <?= $isCorrectItem ? 'correct' : 'wrong' ?>">
              <strong><?= $key ?>.</strong>
              <?= strip_tags($val, '<b><i><u><p><br><img>') ?>
              <strong>[<?= $sArr[$idx] ?? '-' ?>]</strong>
              (Kunci: <?= $cArr[$idx] ?? '-' ?>)
            </li>
            <?php $idx++; endforeach; ?>
        </ul>
        <p>
          Status:
          <?= ($q['is_correct'] ?? false) ? '<span class="correct">Benar (≥70%)</span>' : '<span class="wrong">Salah</span>' ?>
        </p>

      <?php elseif (in_array($q['type'], ['esai', 'essay'])): ?>
        <p><strong>Jawaban Esai:</strong></p>
        <div class="essay-answer">
          <?= nl2br(esc($q['answer'] ?? '(Belum dijawab)')) ?>
        </div>
        <p>Nilai: <strong><?= esc($q['score'] ?? '-') ?></strong></p>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>

</body>

</html>