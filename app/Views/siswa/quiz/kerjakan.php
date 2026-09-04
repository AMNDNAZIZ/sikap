<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
  $sessionId        = $session['id'];
  $quizId           = $quiz['id'];
  $hasDuration      = (int)($quiz['duration']) > 0;
  $remainingSeconds = (int)($remainingSeconds ?? 0);
  $totalQ           = count($questions);
  $csrfName         = csrf_token();
  $csrfHash         = csrf_hash();
?>

<!-- Header sticky -->
<div class="d-flex align-items-center gap-2 mb-3">
  <a href="<?= base_url('siswa/quiz') ?>"
     onclick="return confirm('Jawaban sudah tersimpan. Yakin keluar? Kuis akan dilanjutkan nanti.')"
     class="btn btn-sm btn-outline-secondary flex-shrink-0">
    <i class="bi bi-arrow-left"></i>
  </a>
  <div class="flex-grow-1 overflow-hidden">
    <h6 class="fw-bold mb-0 text-truncate"><?= esc($quiz['title']) ?></h6>
    <div class="text-muted" style="font-size:0.73rem;">
      <?= esc($quiz['subject_name'] ?? '') ?>
      &nbsp;·&nbsp;<?= $totalQ ?> soal
      &nbsp;·&nbsp;Attempt #<?= $session['attempt_number'] ?? 1 ?>
    </div>
  </div>
  <!-- Timer -->
  <?php if ($hasDuration): ?>
    <div id="timerBox"
         class="badge bg-primary text-white flex-shrink-0"
         style="font-size:0.9rem; padding:0.4rem 0.7rem; font-variant-numeric:tabular-nums;">
      <i class="bi bi-clock me-1"></i><span id="timerDisplay">--:--</span>
    </div>
  <?php endif; ?>
</div>

<!-- Progress bar -->
<div class="progress mb-3" style="height:5px;">
  <div class="progress-bar bg-primary" id="progressBar" role="progressbar" style="width:0%"></div>
</div>

<!-- Navigasi soal (pill buttons) -->
<div class="mb-3 d-flex flex-wrap gap-1" id="navBtns">
  <?php for ($i = 1; $i <= $totalQ; $i++): ?>
    <?php $qid = $questions[$i-1]['id']; ?>
    <button type="button"
            class="btn btn-sm nav-q-btn <?= isset($savedAnswers[$qid]) && $savedAnswers[$qid] !== '' ? 'btn-primary' : 'btn-outline-secondary' ?>"
            data-idx="<?= $i ?>"
            style="min-width:36px; font-size:0.78rem;">
      <?= $i ?>
    </button>
  <?php endfor; ?>
</div>

<!-- Soal-soal -->
<div id="questionContainer">
  <?php foreach ($questions as $idx => $q):
    $qno      = $idx + 1;
    $qid      = $q['id'];
    $typeNorm = $q['type_norm'];
    $saved    = $savedAnswers[$qid] ?? '';
    $images   = $q['media_images'] ?? [];
  ?>
  <div class="card border-0 shadow-sm mb-3 question-card"
       id="q-card-<?= $qno ?>"
       style="<?= $qno > 1 ? 'display:none;' : '' ?>">
    <div class="card-body p-3 p-md-4">

      <!-- Nomor + tipe -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="badge bg-primary">Soal <?= $qno ?> / <?= $totalQ ?></span>
        <span class="badge bg-light text-secondary border" style="font-size:0.68rem;">
          <?= match($typeNorm) {
            'pgk'  => 'PG Kompleks',
            'bs'   => 'Benar/Salah',
            'esai' => 'Esai',
            default=> 'Pilihan Ganda'
          } ?>
        </span>
      </div>

      <!-- Teks soal -->
      <div class="question-text mb-3" style="font-size:0.9rem; line-height:1.7;">
        <?= $q['question_text'] ?: ($q['raw_text'] ?? '<em class="text-muted">Soal tidak tersedia.</em>') ?>
      </div>

      <!-- Gambar -->
      <?php if (!empty($images)): ?>
        <div class="mb-3 d-flex flex-wrap gap-2">
          <?php foreach ($images as $img): ?>
            <img src="<?= base_url('uploads/cbt/' . esc($img)) ?>"
                 class="img-fluid rounded border"
                 style="max-height:220px; cursor:pointer;"
                 onclick="window.open(this.src)" alt="Gambar soal">
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <!-- ── Opsi Jawaban ── -->

      <?php if ($typeNorm === 'pg'): ?>
        <?php foreach ($q['options'] as $key => $text): ?>
          <div class="form-check mb-2">
            <input class="form-check-input quiz-answer"
                   type="radio"
                   name="answer_<?= $qid ?>"
                   id="opt_<?= $qid ?>_<?= $key ?>"
                   value="<?= esc($key) ?>"
                   data-qid="<?= $qid ?>"
                   <?= $saved === $key ? 'checked' : '' ?>>
            <label class="form-check-label" for="opt_<?= $qid ?>_<?= $key ?>"
                   style="font-size:0.875rem; cursor:pointer;">
              <strong><?= $key ?>.</strong> <?= $text ?>
            </label>
          </div>
        <?php endforeach; ?>

      <?php elseif ($typeNorm === 'pgk'): ?>
        <div class="small text-muted mb-2">Pilih semua jawaban yang benar:</div>
        <?php
          $savedPgk = $saved !== '' ? explode(',', $saved) : [];
        ?>
        <?php foreach ($q['options'] as $key => $text): ?>
          <div class="form-check mb-2">
            <input class="form-check-input quiz-answer-pgk"
                   type="checkbox"
                   name="answer_pgk_<?= $qid ?>[]"
                   id="opt_<?= $qid ?>_<?= $key ?>"
                   value="<?= esc($key) ?>"
                   data-qid="<?= $qid ?>"
                   <?= in_array($key, $savedPgk) ? 'checked' : '' ?>>
            <label class="form-check-label" for="opt_<?= $qid ?>_<?= $key ?>"
                   style="font-size:0.875rem; cursor:pointer;">
              <strong><?= $key ?>.</strong> <?= $text ?>
            </label>
          </div>
        <?php endforeach; ?>

      <?php elseif ($typeNorm === 'bs'): ?>
        <div class="small text-muted mb-2">Tentukan setiap pernyataan Benar atau Salah:</div>
        <?php
          $savedBs = $saved !== '' ? explode(',', $saved) : [];
          $bsKeys  = array_keys($q['options']);
        ?>
        <?php foreach ($bsKeys as $i => $key): ?>
          <div class="d-flex align-items-start gap-3 mb-2 p-2 bg-light-subtle rounded">
            <div class="flex-grow-1 small"><?= $q['options'][$key] ?></div>
            <div class="d-flex gap-2 flex-shrink-0">
              <div class="form-check mb-0">
                <input class="form-check-input quiz-answer-bs"
                       type="radio"
                       name="bs_<?= $qid ?>_<?= $i ?>"
                       id="bs_<?= $qid ?>_<?= $i ?>_B"
                       value="B"
                       data-qid="<?= $qid ?>"
                       data-bsindex="<?= $i ?>"
                       data-bstotal="<?= count($bsKeys) ?>"
                       <?= (isset($savedBs[$i]) && strtoupper($savedBs[$i]) === 'B') ? 'checked' : '' ?>>
                <label class="form-check-label text-success small fw-semibold"
                       for="bs_<?= $qid ?>_<?= $i ?>_B">Benar</label>
              </div>
              <div class="form-check mb-0">
                <input class="form-check-input quiz-answer-bs"
                       type="radio"
                       name="bs_<?= $qid ?>_<?= $i ?>"
                       id="bs_<?= $qid ?>_<?= $i ?>_S"
                       value="S"
                       data-qid="<?= $qid ?>"
                       data-bsindex="<?= $i ?>"
                       data-bstotal="<?= count($bsKeys) ?>"
                       <?= (isset($savedBs[$i]) && strtoupper($savedBs[$i]) === 'S') ? 'checked' : '' ?>>
                <label class="form-check-label text-danger small fw-semibold"
                       for="bs_<?= $qid ?>_<?= $i ?>_S">Salah</label>
              </div>
            </div>
          </div>
        <?php endforeach; ?>

      <?php elseif ($typeNorm === 'esai'): ?>
        <div class="small text-muted mb-2">Tulis jawabanmu:</div>
        <textarea class="form-control quiz-answer-esai"
                  name="answer_esai_<?= $qid ?>"
                  data-qid="<?= $qid ?>"
                  rows="4"
                  style="font-size:0.875rem;"
                  placeholder="Tulis jawaban di sini..."><?= esc($saved) ?></textarea>

      <?php endif; ?>

    </div><!-- /.card-body -->

    <!-- Navigasi prev/next per kartu -->
    <div class="card-footer bg-transparent py-2 d-flex justify-content-between gap-2">
      <button type="button" class="btn btn-sm btn-outline-secondary" id="prevBtn-<?= $qno ?>"
              onclick="goTo(<?= $qno - 1 ?>)" <?= $qno === 1 ? 'disabled' : '' ?>>
        <i class="bi bi-chevron-left"></i> Sebelumnya
      </button>
      <?php if ($qno < $totalQ): ?>
        <button type="button" class="btn btn-sm btn-primary"
                onclick="goTo(<?= $qno + 1 ?>)">
          Berikutnya <i class="bi bi-chevron-right"></i>
        </button>
      <?php else: ?>
        <button type="button" class="btn btn-sm btn-success" id="btnSubmit">
          <i class="bi bi-check2-circle me-1"></i>Kumpulkan Kuis
        </button>
      <?php endif; ?>
    </div>
  </div>
  <?php endforeach; ?>
</div><!-- /#questionContainer -->

<!-- Tombol submit floating (selalu tersedia) -->
<div class="text-end mt-2 mb-4">
  <button type="button" class="btn btn-success btn-sm" id="btnSubmitFloat">
    <i class="bi bi-check2-circle me-1"></i>Kumpulkan Kuis
  </button>
</div>

<!-- Modal konfirmasi submit -->
<div class="modal fade" id="submitModal" tabindex="-1">
  <div class="modal-dialog modal-sm">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title">Kumpulkan Kuis?</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body small">
        <span id="unansweredMsg"></span>
        Jawaban yang sudah disimpan tidak bisa diubah setelah dikumpulkan.
      </div>
      <div class="modal-footer py-2">
        <button id="confirmSubmit" class="btn btn-success btn-sm">Ya, Kumpulkan</button>
        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Periksa Lagi</button>
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
(function(){
'use strict';

var CSRF_NAME       = "<?= $csrfName ?>";
var CSRF_HASH       = "<?= $csrfHash ?>";
var SESSION_ID      = <?= $sessionId ?>;
var TOTAL_Q         = <?= $totalQ ?>;
var HAS_DURATION    = <?= $hasDuration ? 'true' : 'false' ?>;
var REMAINING_SEC   = <?= $remainingSeconds ?>;
var SAVE_URL        = "<?= base_url('siswa/quiz/save-answer') ?>";
var BULK_URL        = "<?= base_url('siswa/quiz/save-bulk') ?>";
var SUBMIT_URL      = "<?= base_url('siswa/quiz/' . $sessionId . '/submit') ?>";

var currentQ = 1;
var submitModal = new bootstrap.Modal(document.getElementById('submitModal'));

// ── Navigasi antar soal ───────────────────────────────────────────────
window.goTo = function(n) {
  if (n < 1 || n > TOTAL_Q) return;
  document.getElementById('q-card-' + currentQ).style.display = 'none';
  document.getElementById('q-card-' + n).style.display = 'block';
  currentQ = n;
  updateNavBtns();
  window.scrollTo({top: 0, behavior: 'smooth'});
};

function updateNavBtns() {
  document.querySelectorAll('.nav-q-btn').forEach(function(b){
    var active = parseInt(b.dataset.idx) === currentQ;
    b.style.outline = active ? '2px solid #0d6efd' : 'none';
    b.style.outlineOffset = '2px';
  });
  var pct = Math.round((answeredCount() / TOTAL_Q) * 100);
  document.getElementById('progressBar').style.width = pct + '%';
}

document.querySelectorAll('.nav-q-btn').forEach(function(b){
  b.addEventListener('click', function(){ goTo(parseInt(this.dataset.idx)); });
});

// ── Hitung jumlah terisi ──────────────────────────────────────────────
function answeredCount() {
  var count = 0;
  document.querySelectorAll('.nav-q-btn').forEach(function(b){
    if (b.classList.contains('btn-primary')) count++;
  });
  return count;
}

function markNavAnswered(qid, answered) {
  // Cari tombol nav berdasarkan index soal yang sesuai qid
  document.querySelectorAll('.question-card').forEach(function(card, i){
    var radios = card.querySelectorAll('[data-qid="' + qid + '"]');
    if (radios.length > 0) {
      var btn = document.querySelector('.nav-q-btn[data-idx="'+(i+1)+'"]');
      if (btn) {
        if (answered) {
          btn.classList.remove('btn-outline-secondary');
          btn.classList.add('btn-primary');
        } else {
          btn.classList.remove('btn-primary');
          btn.classList.add('btn-outline-secondary');
        }
      }
    }
  });
  updateNavBtns();
}

// ── Simpan jawaban (fire-and-forget) ─────────────────────────────────
function saveAnswer(qid, answer) {
  var body = new URLSearchParams();
  body.append(CSRF_NAME, CSRF_HASH);
  body.append('session_id',  SESSION_ID);
  body.append('question_id', qid);
  body.append('answer',      answer);
  fetch(SAVE_URL, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
    body: body.toString()
  }).catch(function(){});
}

// ── PG: radio change ─────────────────────────────────────────────────
document.querySelectorAll('.quiz-answer').forEach(function(inp){
  inp.addEventListener('change', function(){
    if (this.checked) {
      saveAnswer(this.dataset.qid, this.value);
      markNavAnswered(this.dataset.qid, true);
    }
  });
});

// ── PGK: checkbox change ─────────────────────────────────────────────
document.querySelectorAll('.quiz-answer-pgk').forEach(function(inp){
  inp.addEventListener('change', function(){
    var qid = this.dataset.qid;
    var checked = Array.from(
      document.querySelectorAll('.quiz-answer-pgk[data-qid="'+qid+'"]:checked')
    ).map(function(c){ return c.value; });
    var answer = checked.join(',');
    saveAnswer(qid, answer);
    markNavAnswered(qid, checked.length > 0);
  });
});

// ── BS: radio change ─────────────────────────────────────────────────
document.querySelectorAll('.quiz-answer-bs').forEach(function(inp){
  inp.addEventListener('change', function(){
    var qid     = this.dataset.qid;
    var bsTotal = parseInt(this.dataset.bstotal);
    var parts   = [];
    var allDone = true;
    for (var i = 0; i < bsTotal; i++) {
      var radios = document.querySelectorAll('[name="bs_'+qid+'_'+i+'"]');
      var val = '';
      radios.forEach(function(r){ if (r.checked) val = r.value; });
      if (val === '') allDone = false;
      parts.push(val);
    }
    if (allDone) {
      saveAnswer(qid, parts.join(','));
      markNavAnswered(qid, true);
    }
  });
});

// ── Esai: debounced auto-save ─────────────────────────────────────────
var esaiTimers = {};
document.querySelectorAll('.quiz-answer-esai').forEach(function(ta){
  ta.addEventListener('input', function(){
    var qid = this.dataset.qid;
    var val = this.value;
    clearTimeout(esaiTimers[qid]);
    esaiTimers[qid] = setTimeout(function(){
      saveAnswer(qid, val);
      markNavAnswered(qid, val.trim() !== '');
    }, 1200);
  });
});

// ── Auto-save bulk setiap 30 detik ───────────────────────────────────
setInterval(function(){
  var answers = {};
  // PG
  document.querySelectorAll('.quiz-answer:checked').forEach(function(r){
    answers[r.dataset.qid] = r.value;
  });
  // PGK
  var pgkGroups = {};
  document.querySelectorAll('.quiz-answer-pgk').forEach(function(c){
    var qid = c.dataset.qid;
    if (!pgkGroups[qid]) pgkGroups[qid] = [];
    if (c.checked) pgkGroups[qid].push(c.value);
  });
  for (var qid in pgkGroups) {
    if (pgkGroups[qid].length > 0) answers[qid] = pgkGroups[qid].join(',');
  }
  // Esai
  document.querySelectorAll('.quiz-answer-esai').forEach(function(ta){
    if (ta.value.trim()) answers[ta.dataset.qid] = ta.value;
  });

  if (Object.keys(answers).length === 0) return;
  fetch(BULK_URL, {
    method: 'POST',
    headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
    body: JSON.stringify({ session_id: SESSION_ID, answers: answers, [CSRF_NAME]: CSRF_HASH })
  }).catch(function(){});
}, 30000);

// ── Timer ─────────────────────────────────────────────────────────────
if (HAS_DURATION) {
  var remaining = REMAINING_SEC;
  var timerDisplay = document.getElementById('timerDisplay');
  var timerBox     = document.getElementById('timerBox');

  function updateTimer() {
    if (remaining <= 0) {
      clearInterval(timerInterval);
      timerDisplay.textContent = '00:00';
      timerBox.classList.replace('bg-primary','bg-danger');
      // Auto-submit
      doSubmit(true);
      return;
    }
    var m = Math.floor(remaining / 60);
    var s = remaining % 60;
    timerDisplay.textContent = String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
    if (remaining <= 60) timerBox.classList.replace('bg-primary','bg-danger');
    else if (remaining <= 300) timerBox.classList.replace('bg-primary','bg-warning');
    remaining--;
  }
  var timerInterval = setInterval(updateTimer, 1000);
  updateTimer();
}

// ── Submit ────────────────────────────────────────────────────────────
function showSubmitModal() {
  var answered = answeredCount();
  var unanswered = TOTAL_Q - answered;
  var msg = document.getElementById('unansweredMsg');
  msg.textContent = unanswered > 0
    ? unanswered + ' soal belum dijawab. '
    : 'Semua soal sudah dijawab. ';
  submitModal.show();
}

function doSubmit(forced) {
  var btn = document.getElementById('confirmSubmit');
  if (btn) { btn.disabled = true; btn.textContent = 'Mengumpulkan...'; }
  submitModal.hide();

  var body = new URLSearchParams();
  body.append(CSRF_NAME, CSRF_HASH);
  if (forced) body.append('forced', '1');

  fetch(SUBMIT_URL, {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
    body: body.toString()
  })
  .then(function(r){ return r.json(); })
  .then(function(d){
    if (d.redirect) window.location.href = d.redirect;
    else if (btn) { btn.disabled = false; btn.textContent = 'Ya, Kumpulkan'; }
  })
  .catch(function(){
    if (btn) { btn.disabled = false; btn.textContent = 'Ya, Kumpulkan'; }
  });
}

document.getElementById('confirmSubmit').addEventListener('click', function(){ doSubmit(false); });
document.getElementById('btnSubmit')    && document.getElementById('btnSubmit').addEventListener('click',      showSubmitModal);
document.getElementById('btnSubmitFloat').addEventListener('click', showSubmitModal);

// init
updateNavBtns();

})();
</script>
<?= $this->endSection() ?>
