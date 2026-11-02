<div class="p-2 p-md-3">
  <?php if (!empty($pending_count) && $pending_count > 0): ?>
    <div class="alert alert-warning py-2 px-3 mb-3 d-flex align-items-center rounded-3 shadow-sm border-warning">
      <i class="bi bi-exclamation-triangle-fill me-2 fs-5 text-warning"></i>
      <div>
        <strong>Penilaian Belum Lengkap:</strong> Masih ada <strong><?= $pending_count ?></strong> soal esai yang belum dinilai. Nilai akhir ditahan dan belum dapat dikirim ke buku nilai hingga seluruh soal esai dinilai oleh guru.
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-success py-2 px-3 mb-3 d-flex align-items-center rounded-3 shadow-sm border-success">
      <i class="bi bi-check-circle-fill me-2 fs-5 text-success"></i>
      <div>
        <strong>Penilaian Lengkap:</strong> Seluruh soal esai telah selesai dinilai. Nilai akhir siswa siap dan memenuhi syarat untuk diselaraskan ke buku nilai dan rapor.
      </div>
    </div>
  <?php endif; ?>

  <form id="formNilaiEsai">
    <?= csrf_field() ?>
    <input type="hidden" name="test_id" value="<?= esc($test_id) ?>">
    <input type="hidden" name="student_id" value="<?= esc($student_id) ?>">

    <div class="table-responsive">
    <table class="table table-bordered align-middle">
      <thead class="table-warning text-center">
        <tr>
          <th width="5%">No</th>
          <th>Soal Esai & Riwayat Koreksi</th>
          <th width="35%">Jawaban Siswa</th>
          <th width="120">Nilai (0-100)</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 1;
        foreach ($soal as $s): 
          $revisions = $s['revisions'] ?? [];
          $hasRevisions = !empty($revisions);
        ?>
          <tr>
            <td class="text-center fw-bold align-top pt-3"><?= $no++ ?></td>
            <td class="align-top">
              <div class="fw-semibold text-dark mb-2"><?= esc($s['question_text']) ?></div>
              
              <!-- Input Catatan/Umpan Balik Guru -->
              <div class="mt-2">
                <input type="text" name="feedbacks[<?= $s['id'] ?>]" 
                  class="form-control form-control-sm" 
                  placeholder="Catatan / Umpan Balik untuk revisi ini (opsional)...">
              </div>

              <!-- Riwayat Versi Koreksi (Append-Only) -->
              <?php if ($hasRevisions): ?>
                <div class="mt-2">
                  <a class="text-decoration-none small fw-bold text-primary" data-bs-toggle="collapse" href="#revList-<?= $s['id'] ?>" role="button" aria-expanded="false">
                    <i class="bi bi-clock-history me-1"></i> Riwayat Koreksi (<?= count($revisions) ?> versi tercatat)
                  </a>
                  <div class="collapse mt-2" id="revList-<?= $s['id'] ?>">
                    <div class="card card-body p-2 bg-light border-0 rounded-3 shadow-none small">
                      <div class="list-group list-group-flush">
                        <?php foreach ($revisions as $rev): ?>
                          <div class="list-group-item bg-transparent px-1 py-1 d-flex flex-column gap-1 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                              <div>
                                <?php if ($rev['is_current_confirmed']): ?>
                                  <span class="badge bg-success me-1"><i class="bi bi-check-lg"></i> v<?= $rev['version_number'] ?> Aktif</span>
                                <?php else: ?>
                                  <span class="badge bg-secondary me-1">v<?= $rev['version_number'] ?> Lama</span>
                                <?php endif; ?>
                                <strong>Skor: <?= number_format((float)$rev['score_awarded'], 1) ?></strong>
                              </div>
                              <span class="text-muted" style="font-size: 0.75rem;">
                                <?= esc($rev['graded_at']) ?>
                              </span>
                            </div>
                            <div class="text-muted" style="font-size: 0.8rem;">
                              Penilai: <span class="text-dark fw-medium"><?= esc($rev['grader_name'] ?? $rev['grader_username'] ?? 'Guru/Admin') ?></span>
                              <?php if (!empty($rev['feedback'])): ?>
                                — <em>"<?= esc($rev['feedback']) ?>"</em>
                              <?php endif; ?>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endif; ?>
            </td>
            <td class="bg-light align-top pt-3">
              <div class="p-2 border rounded bg-white text-break" style="max-height: 150px; overflow-y: auto;">
                <?= nl2br(esc($s['answer'] ?? '-')) ?>
              </div>
            </td>
            <td class="text-center align-top pt-3">
              <input type="number" name="scores[<?= $s['id'] ?>]"
                class="form-control form-control-sm score-input text-center fw-bold text-primary" min="0" max="100" step="0.5"
                value="<?= esc($s['score'] ?? '') ?>" placeholder="0-100" style="min-width: 80px;">
              <?php if ($s['score'] === null || $s['score'] === ''): ?>
                <span class="badge bg-warning text-dark mt-1" style="font-size: 0.7rem;">Belum Dinilai</span>
              <?php else: ?>
                <span class="badge bg-info text-dark mt-1" style="font-size: 0.7rem;">Tersimpan</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    </div>

    <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mt-3 pt-2 border-top">
      <div class="p-2 bg-light rounded w-100 w-sm-auto border">
        <div><strong>Rata-rata Esai: </strong><span id="rataEsai" class="fw-bold text-primary">0</span></div>
        <div><strong>Kontribusi Nilai (× bobot <?= $bobot_esai ?>%): </strong><span id="nilaiAkhir" class="fw-bold text-success">0</span></div>
      </div>
      <button type="submit" class="btn btn-success w-100 w-sm-auto py-2 px-3 fw-bold shadow-sm">
        <i class="bi bi-save2 me-1"></i> Simpan Penilaian & Versi Baru
      </button>
    </div>
  </form>
</div>

<script>
  $(function () {
    const bobot = <?= (float)$bobot_esai ?>;

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
