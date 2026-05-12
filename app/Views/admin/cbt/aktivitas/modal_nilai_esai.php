<div class="p-3">
  <form id="formNilaiEsai">
    <?= csrf_field() ?>
    <input type="hidden" name="test_id" value="<?= esc($test_id) ?>">
    <input type="hidden" name="student_id" value="<?= esc($student_id) ?>">

    <table class="table table-bordered align-middle">
      <thead class="table-warning text-center">
        <tr>
          <th width="5%">No</th>
          <th>Soal Esai</th>
          <th width="25%">Jawaban Siswa</th>
          <th width="10%">Nilai</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        foreach ($soal as $s): ?>
          <tr>
            <td class="text-center"><?= $no++ ?></td>
            <td><?= esc($s['question_text']) ?></td>
            <td><?= esc($s['answer'] ?? '-') ?></td>
            <td class="text-center">
              <input type="number" name="scores[<?= $s['id'] ?>]"
                class="form-control form-control-sm score-input text-center" min="0" max="100"
                value="<?= esc($s['score'] ?? '') ?>">
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <div>
        <strong>Rata-rata Esai: </strong><span id="rataEsai">0</span><br>
        <strong>Nilai Akhir (× bobot <?= $bobot_esai ?>%): </strong><span id="nilaiAkhir">0</span>
      </div>
      <button type="submit" class="btn btn-success">
        <i class="bi bi-save"></i> Simpan Semua Nilai
      </button>
    </div>
  </form>
</div>

<script>
  $(function () {
    const bobot = <?= $bobot_esai ?>;

    // Fungsi hitung otomatis rata-rata dan nilai akhir
    function hitungNilai() {
      let total = 0, count = 0;
      $('.score-input').each(function () {
        const val = parseFloat($(this).val());
        if (!isNaN(val)) {
          total += val;
          count++;
        }
      });
      const rata = count > 0 ? (total / count) : 0;
      const akhir = rata * (bobot / 100);

      $('#rataEsai').text(rata.toFixed(2));
      $('#nilaiAkhir').text(akhir.toFixed(2));
    }

    // Hitung ulang setiap kali guru ubah nilai
    $(document).on('input', '.score-input', hitungNilai);

    // Jalankan saat pertama kali
    hitungNilai();
  });
</script>