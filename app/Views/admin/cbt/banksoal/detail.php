<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- MathJax untuk render LaTeX matematika -->
<script>
window.MathJax = {
    tex: {
        inlineMath:  [['$', '$'], ['\\(', '\\)']],
        displayMath: [['$$', '$$'], ['\\[', '\\]']],
        processEscapes: true
    },
    options: { skipHtmlTags: ['script','noscript','style','textarea','pre'] },
    startup: { typeset: false }
};
</script>
<script async src="https://cdn.jsdelivr.net/npm/mathjax@3/es5/tex-chtml.min.js"></script>

<style>
  .cbt-header-responsive {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 12px;
    margin-bottom: 1.25rem;
  }

  .btn-group-cbt {
    display: inline-flex;
    flex-wrap: nowrap;
  }

  @media (max-width: 768px) {
    .container-fluid {
      padding-left: 10px !important;
      padding-right: 10px !important;
    }

    .cbt-header-responsive {
      flex-direction: column;
      align-items: stretch;
      gap: 10px;
    }

    .cbt-header-responsive .btn-group {
      width: 100%;
    }

    .cbt-header-responsive .btn {
      flex: 1;
    }

    .btn-group-cbt {
      display: flex !important;
      flex-wrap: wrap !important;
      gap: 3px !important;
    }

    .btn-group-cbt .btn {
      border-radius: 6px !important;
      min-width: 34px !important;
      height: 34px !important;
      padding: 0 !important;
      display: inline-flex !important;
      align-items: center !important;
      justify-content: center !important;
    }

    .table-responsive {
      border-radius: 12px;
      -webkit-overflow-scrolling: touch;
    }

    #tableQuestions {
      font-size: 0.84rem;
    }

    #tableQuestions th, #tableQuestions td {
      padding: 8px 10px;
    }

    .modal-dialog {
      margin: 0.5rem;
      max-width: calc(100% - 1rem) !important;
    }

    .modal-content {
      border-radius: 18px !important;
    }
  }
</style>

<div class="container-fluid py-2">
  <div class="cbt-header-responsive">
    <div>
      <h4 class="mb-0 fw-bold"><i class="bi bi-file-earmark-text text-primary me-1"></i> Rincian Bank Soal</h4>
      <small class="text-muted">
        <span class="badge bg-primary bg-opacity-10 text-primary font-monospace"><?= esc($bank['code']) ?></span> &bull; 
        <strong><?= esc($bank['subject_name'] ?? '-') ?></strong> &bull; Level <?= esc($bank['level']) ?>
      </small>
    </div>

    <div class="d-flex gap-2 w-100 w-sm-auto justify-content-end">
      <a href="<?= site_url('admin/cbt/banksoal/tambah_soal/' . $bank['id']) ?>" class="btn btn-success rounded-pill px-3 py-2 fw-semibold shadow-sm flex-fill flex-sm-grow-0 d-inline-flex align-items-center justify-content-center">
        <i class="bi bi-plus-circle me-1"></i> Tambah Soal
      </a>
      <a href="<?= site_url('admin/cbt/banksoal') ?>" class="btn btn-outline-secondary rounded-pill px-3 py-2 flex-fill flex-sm-grow-0 d-inline-flex align-items-center justify-content-center">
        <i class="bi bi-arrow-left me-1"></i> Kembali
      </a>
    </div>
  </div>

  <!-- 📱 TAMPILAN MOBILE: KARTU SOAL (Hanya tampil di HP / Layar < 768px) -->
  <div class="d-block d-md-none mb-3">
    <!-- Search Toolbar Mobile -->
    <div class="card border-0 shadow-sm p-3 mb-3" style="border-radius: 12px;">
      <div class="input-group">
        <span class="input-group-text bg-light border-end-0"><i class="bi bi-search text-muted"></i></span>
        <input type="text" id="searchQuestionMobile" class="form-control border-start-0" placeholder="Cari nomor atau isi soal...">
      </div>
      <div class="d-flex justify-content-between align-items-center pt-2">
        <span class="small text-muted">Total: <strong><?= count($questions) ?> Butir Soal</strong></span>
        <a href="<?= site_url('admin/cbt/banksoal/tambah_soal/' . $bank['id']) ?>" class="btn btn-sm btn-outline-success py-1 px-2 rounded-pill">
          <i class="bi bi-plus-circle me-1"></i> Tambah
        </a>
      </div>
    </div>

    <div id="mobileQuestionCardsContainer">
      <?php foreach ($questions as $i => $q): 
        $previewText = strip_tags($q['question_text']);
        $previewText = str_replace(['\\r\\n', '\\n', '\\r', "\r\n", "\n", "\r"], ' ', $previewText);
        $previewSnippet = substr($previewText, 0, 140) . (strlen($previewText) > 140 ? '...' : '');

        $badgeColor = 'bg-primary';
        $typeName = 'PG';
        if ($q['question_type'] === 'pg_kompleks') {
          $badgeColor = 'bg-info text-dark';
          $typeName = 'PGK';
        } elseif ($q['question_type'] === 'benar_salah') {
          $badgeColor = 'bg-warning text-dark';
          $typeName = 'B/S';
        } elseif ($q['question_type'] === 'esai' || $q['question_type'] === 'essay') {
          $badgeColor = 'bg-success';
          $typeName = 'Esai';
        }
      ?>
        <div class="card border-0 shadow-sm mb-3 question-card-item" style="border-radius: 14px;" data-search="<?= strtolower(esc(($i + 1) . ' ' . $previewText . ' ' . $typeName)) ?>">
          <div class="card-body p-3">
            <!-- Top Row: No, Tipe, Skor -->
            <div class="d-flex justify-content-between align-items-center mb-2">
              <span class="badge bg-secondary bg-opacity-10 text-secondary border font-monospace px-2 py-1">
                SOAL NO. <?= $i + 1 ?>
              </span>
              <div class="d-flex gap-1 align-items-center">
                <span class="badge <?= $badgeColor ?> px-2 py-1"><?= $typeName ?></span>
                <span class="badge bg-light text-dark border px-2 py-1">Skor: <?= esc($q['score']) ?></span>
              </div>
            </div>

            <!-- Question Snippet -->
            <p class="text-dark small mb-3" style="line-height: 1.5;">
              <?= esc($previewSnippet) ?>
            </p>

            <!-- Action Buttons Touch Friendly -->
            <div class="btn-group w-100 shadow-sm" role="group">
              <button type="button" class="btn btn-outline-primary btn-sm py-2 btn-view-question d-flex align-items-center justify-content-center gap-1"
                data-index="<?= $i ?>"
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
                <i class="bi bi-eye"></i> Lihat
              </button>

              <a href="<?= site_url('admin/cbt/banksoal/edit_soal/' . $bank['id'] . '/' . $q['id']) ?>"
                class="btn btn-outline-warning btn-sm py-2 d-flex align-items-center justify-content-center gap-1">
                <i class="bi bi-pencil"></i> Edit
              </a>

              <button type="button" class="btn btn-outline-danger btn-sm py-2 btn-delete-question d-flex align-items-center justify-content-center gap-1"
                data-id="<?= $q['id'] ?>"
                data-bank-id="<?= $bank['id'] ?>">
                <i class="bi bi-trash"></i> Hapus
              </button>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($questions)): ?>
        <div class="text-center py-5 text-muted bg-white rounded-3 border">
          <i class="bi bi-file-earmark-x fs-1 text-secondary mb-2 d-block"></i>
          <h6>Belum ada butir soal dalam bank ini</h6>
          <a href="<?= site_url('admin/cbt/banksoal/tambah_soal/' . $bank['id']) ?>" class="btn btn-sm btn-success mt-2">
            <i class="bi bi-plus-circle me-1"></i> Tambah Soal
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- 💻 TAMPILAN DESKTOP: TABEL SOAL (Hanya tampil di layar >= 768px) -->
  <div class="card shadow-sm border-0 rounded-3 d-none d-md-block">
    <div class="card-body table-responsive p-2 p-md-3">
      <table id="tableQuestions" class="table table-striped align-middle w-100 mb-0">
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
                <?= strip_tags(substr(str_replace(['\\r\\n', '\\n', '\\r', "\r\n", "\r", "\n"], ' ', $q['question_text']), 0, 100)) ?>
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
                <div class="btn-group btn-group-cbt">
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
        text: $(this).data('text') || $(this).data('plain') || '',
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
        if (!html) return '';
        let out = html;
        
        // Normalisasi literal \n dan \r\n jika masih tersimpan sebagai teks literal
        out = out.replace(/\\r\\n/g, '\n').replace(/\\n/g, '\n').replace(/\\r/g, '\n');

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

        // Ganti newline asli yang tersisa menjadi <br> agar baris baru dirender
        out = out.replace(/\r?\n/g, '<br>');

        return out.trim();
      }

      function formatOptionText(text) {
        if (!text) return '';
        return String(text)
          .replace(/\\r\\n/g, '<br>')
          .replace(/\\n/g, '<br>')
          .replace(/\\r/g, '<br>')
          .replace(/\r?\n/g, '<br>');
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
              <div>${formatOptionText(parsed[opt])}</div>
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
                <td>${formatOptionText(parsed[opt])}</td>
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

      // Render LaTeX matematika di teks soal & opsi
      if (window.MathJax && window.MathJax.typesetPromise) {
        MathJax.typesetPromise([document.getElementById('viewText'), document.getElementById('optionList')])
          .catch(function(err){ console.warn('MathJax:', err); });
      }
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

    // Rapikan gambar di dalamnya + render ulang MathJax saat modal tampil
    $(document).on('shown.bs.modal', '#modalViewQuestion', function () {
      $('#viewText img').each(function () {
        $(this).addClass('img-fluid d-block mx-auto my-2 rounded');
      });
      if (window.MathJax && window.MathJax.typesetPromise) {
        MathJax.typesetPromise([document.getElementById('modalViewQuestion')])
          .catch(function(err){ console.warn('MathJax:', err); });
      }
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
    // Live search mobile question cards
    $('#searchQuestionMobile').on('input', function () {
      const term = $(this).val().toLowerCase().trim();
      $('.question-card-item').each(function () {
        const text = $(this).data('search') || '';
        if (text.indexOf(term) !== -1 || term === '') {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    });

  });
</script>

<?= $this->endSection() ?>