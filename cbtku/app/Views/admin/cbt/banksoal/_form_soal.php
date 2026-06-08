<div class="mb-3">
  <label>Pertanyaan</label>
  <textarea name="question_text" class="form-control" rows="2" required><?= esc($q['question_text'] ?? '') ?></textarea>
</div>

<div class="row">
  <?php foreach (['a', 'b', 'c', 'd', 'e'] as $opt): ?>
    <div class="col-md-6 mb-2">
      <label>Opsi <?= strtoupper($opt) ?></label>
      <input type="text" name="option_<?= $opt ?>" class="form-control" value="<?= esc($q['option_' . $opt] ?? '') ?>">
    </div>
  <?php endforeach; ?>
</div>

<div class="row">
  <div class="col-md-6 mb-2">
    <label>Kunci Jawaban</label>
    <select name="correct_option" class="form-select" required>
      <option value="">-- Pilih --</option>
      <?php foreach (['A', 'B', 'C', 'D', 'E'] as $opt):
        $currentKeys = explode(',', $q['correct_option'] ?? '');
        $isSelected = in_array(strtolower($opt), array_map('strtolower', $currentKeys));
        ?>
        <option value="<?= strtolower($opt) ?>" <?= $isSelected ? 'selected' : '' ?>>
          <?= $opt ?>
        </option>
      <?php endforeach; ?>
    </select>
    <small class="text-muted">Untuk PG Kompleks, tahan Ctrl (Windows) / Cmd (Mac) untuk memilih lebih dari satu.</small>
  </div>
  <div class="col-md-6 mb-2">
    <label>Skor</label>
    <input type="number" name="score" class="form-control" step="0.1" min="0" value="<?= esc($q['score'] ?? 1) ?>">
  </div>
</div>