<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h4 class="mb-0">Rincian Bank Soal</h4>
      <small class="text-muted">
        <?= esc($bank['code']) ?> â€” <?= esc($bank['subject_name'] ?? '-') ?> â€” Level <?= esc($bank['level']) ?>
      </small>
    </div>

    <div class="btn-group">
      <a href="<?= site_url('admin/cbt/banksoal/tambah_soal/' . $bank['id']) ?>" class="btn btn-success">
        <i class="bi bi-plus-circle"></i> Tambah Soal
      </a>
      <a href="<?= site_url('admin/cbt/banksoal') ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>
    </div>
  </div>

  <div class="card shadow-sm">
    <div class="card-body table-responsive">
      <table id="tableQuestions" class="table table-striped align-middle w-100">
        <thead class="table-light">
          <tr>
            <th width="5%">No</th>
            <th>Soal</th>
            <th width="10%">Jenis</th>
            <th width="10%">Skor</th>
            <th width="20%">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($questions as $i => $q): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td>
                <?= strip_tags(substr($q['question_text'], 0, 100)) ?>
                <?= strlen($q['question_text']) > 100 ? '...' : '' ?>
              </td>
              <td>
                <?php
                if ($q['question_type'] === 'pg_kompleks')
                  echo 'PG Kompleks';
                elseif ($q['question_type'] === 'pg')
                  echo 'PG';
                elseif ($q['question_type'] === 'benar_salah')
                  echo 'Benar/Salah';
                else
                  echo 'Esai';
                ?>
              </td>
              <td><?= esc($q['score']) ?></td>
              <td>
                <div class="btn-group">
                  <button class="btn btn-outline-info btn-sm btn-view-question" data-index="<?= $i ?>"
                    data-id="<?= $q['id'] ?>" data-text="<?= htmlspecialchars($q['raw_text']) ?>"
                    data-plain="<?= htmlspecialchars($q['question_text']) ?>" data-type="<?= $q['question_type'] ?>"
                    data-score="<?= $q['score'] ?>" data-options="<?= htmlspecialchars(json_encode([
                        'A' => $q['option_a'],
                        'B' => $q['option_b'],
                        'C' => $q['option_c'],
                        'D' => $q['option_d'],
                        'E' => $q['option_e'],
                        'key' => $q['correct_option']
                      ]), ENT_QUOTES, 'UTF-8') ?>">
                    <i class="bi bi-eye"></i>
                  </button>

                  <a href="<?= site_url('admin/cbt/banksoal/edit_soal/' . $bank['id'] . '/' . $q['id']) ?>"
                    class="btn btn-outline-warning btn-sm">
                    <i class="bi bi-pencil"></i>
                  </a>

                  <button class="btn btn-outline-danger btn-sm btn-delete-question" data-id="<?= $q['id'] ?>"
                    data-bank-id="<?= $bank['id'] ?>">
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- ðŸ”¹ Modal Lihat Soal (Versi CBT Viewer) -->
<div class="modal fade" id="modalViewQuestion" tabindex="-1">
  <div class="modal-dialog modal-xl modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white d-flex justify-content-between">
        <h5 class="modal-title"><i class="bi bi-eye"></i> Lihat Soal</h5>
        <button class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body bg-light">
        <div class="text-end mb-2">
          <small>Ukuran huruf soal</small>
          <span class="ms-2 font-control-btn" data-size="sm">A</span>
          <span class="ms-1 font-control-btn active" data-size="md">A</span>
          <span class="ms-1 font-control-btn" data-size="lg">A</span>
        </div>

        <div class="card border-0 shadow-sm">
          <div class="card-body">
            <div class="mb-3 d-flex justify-content-between">
              <div><strong>SOAL NO <span id="viewIndex">1</span></strong></div>
              <div><strong>Skor:</strong> <span id="viewScore"></span></div>
            </div>

            <div id="viewText" class="p-3 mb-3 border rounded bg-white soal-text"></div>

            <div id="viewOptions" class="p-3 border rounded bg-white">
              <h6 class="fw-bold mb-3">Kunci Jawaban</h6>
              <div id="optionList"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between">
        <button id="btnPrevQuestion" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Sebelumnya
        </button>
        <div>
          <a id="btnEditQuestion" href="#" class="btn btn-warning me-2">
            <i class="bi bi-pencil"></i> Edit
          </a>
          <button id="btnNextQuestion" class="btn btn-primary">
            Selanjutnya <i class="bi bi-arrow-right"></i>
          </button>
        </div>
      </div>
    </div>
  </div>
</div>
<style>
  /* Pastikan area soal rapi */
  .soal-text {
    line-height: 1.6;
    font-size: 1rem;
  }

  /* Gambar tampil di tengah dan di baris sendiri */
  .soal-text img {
    display: block;
    /* agar tidak sejajar dengan teks */
    margin: 10px left;
    /* jarak atas bawah dan center horizontal */
    max-width: 100%;
    /* responsif */
    height: auto;
    /* menjaga rasio */
    border-radius: 6px;
    /* opsional agar lembut */
  }

  /* Agar gambar tidak terlalu menempel ke teks */
  .soal-text p {
    margin-bottom: 0.8rem;
  }

  .font-control-btn {
    cursor: pointer;
    font-weight: bold;
  }

  .font-control-btn.active {
    text-decoration: underline;
  }

  .soal-text.sm {
    font-size: 0.9rem;
  }

  .soal-text.md {
    font-size: 1rem;
  }

  .soal-text.lg {
    font-size: 1.2rem;
  }
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>

<script>
  $(function () {
    const soalData = [];
    $('.btn-view-question').each(function () {
      soalData.push({
        id: $(this).data('id'),
        text: $(this).data('text'),
        type: $(this).data('type'),
        score: $(this).data('score'),
        options: $(this).data('options')
      });
    });

    let currentIndex = 0;

    /** ========================
     * Tampilkan Soal di Modal
     * ======================== */
    function renderQuestion(index) {
      const q = soalData[index];
      if (!q) return;
      const parsed = typeof q.options === 'string' ? JSON.parse(q.options) : q.options;

      $('#viewIndex').text(index + 1);
      $('#viewScore').text(q.score);
      function cleanRawText(html) {
        let out = html;
        
        // Preserve img tags by temporarily replacing them
        const imgTags = [];
        out = out.replace(/<img[^>]*>/gi, function(match) {
          imgTags.push(match);
          return `___IMG_PLACEHOLDER_${imgTags.length - 1}___`;
        });
        
        // Clean formatting tags
        out = out.replace(/<\/?(span|strong|em|font|u|b|i)[^>]*>/gi, '');
        
        // Remove option lines (A-E)
        out = out.replace(/<p[^>]*>\s*[\(]?[A-Ea-e]\s*(?:[:.)]|\s+-)\s*[\s\S]*?<\/p>/gi, '');
        out = out.replace(/^\s*[\(]?[A-Ea-e]\s*(?:[:.)]|\s+-)\s*.*$/gim, '');
        
        // Remove Kunci and Tipe lines
        out = out.replace(/<p[^>]*>\s*Kunci\s*[\:\=\-]\s*[\s\S]*?<\/p>/gi, '');
        out = out.replace(/^\s*Kunci\s*[\:\=\-]\s*.*$/gim, '');
        out = out.replace(/<p[^>]*>\s*Tipe\s*[\:\=\-]\s*[\s\S]*?<\/p>/gi, '');
        out = out.replace(/^\s*Tipe\s*[\:\=\-]\s*.*$/gim, '');
        
        // Remove empty paragraphs
        out = out.replace(/<p[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/p>/gi, '');

        // Remove inline Tipe: and Kunci: patterns
        out = out.replace(/Tipe\s*:\s*[A-Za-z0-9_]+/gi, '');
        out = out.replace(/Kunci\s*:\s*[A-Za-z0-9,\s]+/gi, '');

        // Inline Truncation (Aggressive) - but preserve img placeholders
        out = out.replace(/([\s\.,;>\?!]|&nbsp;)([\(]?[A-Ea-e]\s*[:.)-][\s\S]*)$/i, '$1');
        out = out.replace(/([\s\.,;>\?!]|&nbsp;)(Kunci\s*[:=][\s\S]*)$/i, '$1');

        // Restore img tags
        imgTags.forEach((imgTag, index) => {
          out = out.replace(`___IMG_PLACEHOLDER_${index}___`, imgTag);
        });

        return out.trim();
      }
      let cleanText = cleanRawText(q.text);
      $('#viewText').html(cleanText);


      let html = '';
      if (q.type === 'pg' || q.type === 'pg_kompleks') {
        const keys = (parsed.key || '').split(',');
        ['A', 'B', 'C', 'D', 'E'].forEach(opt => {
          if (parsed[opt]) {
            const isSelected = keys.includes(opt);
            const active = isSelected ? 'bg-primary text-white fw-bold' : '';
            html += `
            <div class="p-2 mb-2 border rounded d-flex align-items-center ${active}">
              <div class="me-2 fw-bold">${opt}.</div>
              <div>${parsed[opt]}</div>
            </div>
          `;
          }
        });
      } else if (q.type === 'benar_salah') {
        const keys = (parsed.key || '').split(',');
        html = `
          <div class="table-responsive">
            <table class="table table-bordered table-sm">
              <thead class="table-light">
                <tr>
                  <th>Pernyataan</th>
                  <th width="80" class="text-center">Kunci</th>
                </tr>
              </thead>
              <tbody>
        `;
        ['A', 'B', 'C', 'D', 'E'].forEach((opt, idx) => {
          if (parsed[opt]) {
            const keyVal = keys[idx] || '-';
            html += `
              <tr>
                <td>${parsed[opt]}</td>
                <td class="text-center fw-bold text-primary">${keyVal}</td>
              </tr>
            `;
          }
        });
        html += `</tbody></table></div>`;
      } else {
        html = `<div class="alert alert-info">Soal Esai - Tidak memiliki opsi jawaban.</div>`;
      }

      $('#optionList').html(html);
      $('#btnEditQuestion').attr('href', "<?= site_url('admin/cbt/banksoal/edit_soal/' . $bank['id']) ?>/" + q.id);

      // Tombol navigasi
      $('#btnPrevQuestion').prop('disabled', index === 0);
      $('#btnNextQuestion').prop('disabled', index === soalData.length - 1);
    }

    /** ========================
     * Tombol Lihat (buka modal)
     * ======================== */
    $(document).on('click', '.btn-view-question', function () {
      currentIndex = $(this).data('index');
      renderQuestion(currentIndex);
      $('#modalViewQuestion').modal('show');
    });

    $('#btnPrevQuestion').on('click', function () {
      if (currentIndex > 0) {
        currentIndex--;
        renderQuestion(currentIndex);
      }
    });

    $('#btnNextQuestion').on('click', function () {
      if (currentIndex < soalData.length - 1) {
        currentIndex++;
        renderQuestion(currentIndex);
      }
    });

    // Setelah set isi soal
    //$('#viewText').html(question.question_text);

    // Rapikan gambar di dalamnya
    $(document).on('shown.bs.modal', '#modalViewQuestion', function () {
      $('#viewText img').each(function () {
        $(this).addClass('img-fluid d-block mx-auto my-2 rounded');
      });
    });

    /** ========================
     * Pengaturan Ukuran Font
     * ======================== */
    $('.font-control-btn').on('click', function () {
      $('.font-control-btn').removeClass('active');
      $(this).addClass('active');
      const size = $(this).data('size');
      $('#viewText').removeClass('sm md lg').addClass(size);
    });

    /** ========================
     * Hapus Soal
     * ======================== */
    const csrfName = $('meta[name]').attr('name');
    let csrfHash = $('meta[name]').attr('content');
    $(document).on('click', '.btn-delete-question', function () {
      const id = $(this).data('id');
      const bankId = $(this).data('bank-id');

      Swal.fire({
        title: 'Hapus Soal?',
        text: 'Soal ini akan dihapus secara permanen dari bank soal.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Ya, hapus',
        cancelButtonText: 'Batal'
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            url: '<?= site_url('admin/cbt/banksoal/deleteQuestionAjax') ?>',
            method: 'POST',
            dataType: 'json',
            data: {
              id: id,
              bank_id: bankId,
              [csrfName]: csrfHash // ðŸ’‰ penting agar lolos CSRF protection
            },
            success: function (res) {
              if (res.success) {
                Swal.fire('Berhasil', res.message, 'success');
                setTimeout(() => location.reload(), 1000);
              } else {
                Swal.fire('Gagal', res.error || 'Terjadi kesalahan.', 'error');
              }
            },
            error: function (xhr) {
              console.error(xhr.responseText);
              Swal.fire('Error', 'Gagal menghapus soal. Periksa koneksi atau log server.', 'error');
            }
          });
        }
      });
    });

  });
</script>

<?= $this->endSection() ?>