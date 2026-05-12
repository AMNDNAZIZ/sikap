<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<style>
  :root {
    --primary-gradient: linear-gradient(135deg, #0531f7ff 0%, #08fa30ff 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --card-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    --card-hover-shadow: 0 15px 40px rgba(0, 0, 0, 0.15);
  }

  body {
    background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
    min-height: 100vh;
  }

  /* Header Section */
  .page-header {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
  }

  .page-header::before {
    content: '';
    position: absolute;
    top: 0;
    right: 0;
    width: 300px;
    height: 300px;
    background: var(--primary-gradient);
    opacity: 0.1;
    border-radius: 50%;
    transform: translate(30%, -30%);
  }

  .school-header {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    margin-bottom: 1.5rem;
    position: relative;
    z-index: 1;
  }

  .school-logo {
    width: 80px;
    height: 80px;
    object-fit: contain;
    border-radius: 15px;
    background: white;
    padding: 10px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
  }

  .school-info h4 {
    margin: 0;
    font-weight: 700;
    color: #2d3748;
    font-size: 1.5rem;
  }

  .school-info p {
    margin: 0;
    color: #718096;
    font-size: 0.95rem;
  }

  /* Student Info Card */
  .student-info-card {
    background: white;
    border-radius: 20px;
    padding: 2rem;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
  }

  .student-info-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 5px;
    background: var(--primary-gradient);
  }

  .info-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1.5rem;
  }

  .info-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: #f7fafc;
    border-radius: 12px;
    transition: all 0.3s ease;
  }

  .info-item:hover {
    background: #edf2f7;
    transform: translateY(-2px);
  }

  .info-icon {
    width: 45px;
    height: 45px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    background: var(--primary-gradient);
    color: white;
    font-size: 1.2rem;
    flex-shrink: 0;
  }

  .info-content {
    flex: 1;
  }

  .info-label {
    font-size: 0.85rem;
    color: #718096;
    margin-bottom: 0.25rem;
  }

  .info-value {
    font-weight: 600;
    color: #2d3748;
    font-size: 1rem;
  }

  /* Section Title */
  .section-title {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 2rem;
  }

  .section-title h4 {
    margin: 0;
    font-weight: 700;
    color: #2d3748;
    font-size: 1.5rem;
  }

  .section-title .badge {
    background: var(--primary-gradient);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 20px;
    font-size: 0.9rem;
  }

  /* Exam Cards */
  .exam-card {
    background: white;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: var(--card-shadow);
    transition: all 0.3s ease;
    border: none;
    height: 100%;
    display: flex;
    flex-direction: column;
  }

  .exam-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-hover-shadow);
  }

  .exam-card.highlight {
    animation: highlightPulse 2s ease-in-out;
  }

  @keyframes highlightPulse {

    0%,
    100% {
      box-shadow: var(--card-shadow);
    }

    50% {
      box-shadow: 0 0 30px rgba(102, 126, 234, 0.6);
    }
  }

  .exam-card-header {
    padding: 1.5rem;
    background: var(--primary-gradient);
    color: white;
    position: relative;
  }

  .exam-card-header::after {
    content: '';
    position: absolute;
    bottom: -10px;
    left: 0;
    right: 0;
    height: 20px;
    background: white;
    border-radius: 20px 20px 0 0;
  }

  .exam-badge {
    display: inline-block;
    background: rgba(255, 255, 255, 0.2);
    padding: 0.4rem 0.8rem;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
    margin-bottom: 0.5rem;
  }

  .exam-title {
    font-size: 1.25rem;
    font-weight: 700;
    margin: 0.5rem 0;
    color: white;
  }

  .exam-subtitle {
    font-size: 0.9rem;
    opacity: 0.9;
    margin: 0;
  }

  .exam-card-body {
    padding: 1.5rem;
    flex: 1;
    display: flex;
    flex-direction: column;
  }

  .exam-meta {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin-bottom: 1.5rem;
  }

  .meta-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #4a5568;
    font-size: 0.95rem;
  }

  .meta-icon {
    width: 35px;
    height: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #f7fafc;
    border-radius: 10px;
    color: #667eea;
  }

  /* Status Alerts */
  .status-alert {
    border-radius: 15px;
    padding: 1rem;
    text-align: center;
    font-weight: 600;
    margin-top: auto;
    border: none;
  }

  .status-alert.finished {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
  }

  .status-alert.waiting {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
  }

  .status-alert.running {
    background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    color: #2d3748;
  }

  .status-alert.ended {
    background: #e2e8f0;
    color: #4a5568;
  }

  /* Token Form */
  .token-section {
    margin-top: 1rem;
    padding: 1rem;
    background: #f7fafc;
    border-radius: 15px;
  }

  .token-display {
    background: white;
    padding: 1rem;
    border-radius: 12px;
    text-align: center;
    margin-bottom: 1rem;
    border: 2px dashed #667eea;
  }

  .token-display h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #667eea;
    margin: 0;
    letter-spacing: 3px;
  }

  .token-form .form-label {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 0.5rem;
  }

  .token-form input {
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    padding: 0.75rem 1rem;
    font-size: 1.1rem;
    text-align: center;
    letter-spacing: 2px;
    font-weight: 600;
    text-transform: uppercase;
    transition: all 0.3s ease;
  }

  .token-form input:focus {
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
  }

  .btn-start-exam {
    background: var(--primary-gradient);
    border: none;
    border-radius: 12px;
    padding: 0.75rem 1.5rem;
    font-weight: 600;
    color: white;
    width: 100%;
    transition: all 0.3s ease;
  }

  .btn-start-exam:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
    color: white;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 4rem 2rem;
    background: white;
    border-radius: 20px;
    box-shadow: var(--card-shadow);
  }

  .empty-state i {
    font-size: 5rem;
    color: #cbd5e0;
    margin-bottom: 1rem;
  }

  .empty-state h5 {
    color: #4a5568;
    margin-bottom: 0.5rem;
  }

  .empty-state p {
    color: #718096;
    margin: 0;
  }

  /* Responsive */
  @media (max-width: 768px) {
    .page-header {
      padding: 1.5rem;
      margin-bottom: 1.5rem;
    }

    .school-header {
      flex-direction: column;
      text-align: center;
      gap: 1rem;
    }

    .school-logo {
      width: 60px;
      height: 60px;
    }

    .school-info h4 {
      font-size: 1.2rem;
    }

    .student-info-card {
      padding: 1.5rem;
    }

    .info-grid {
      grid-template-columns: 1fr;
      gap: 1rem;
    }

    .info-item {
      padding: 0.75rem;
    }

    .info-icon {
      width: 40px;
      height: 40px;
      font-size: 1rem;
    }

    .section-title {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
      margin-bottom: 1.5rem;
    }

    .section-title h4 {
      font-size: 1.25rem;
    }

    .exam-card-header {
      padding: 1.25rem;
    }

    .exam-title {
      font-size: 1.1rem;
    }

    .exam-card-body {
      padding: 1.25rem;
    }

    .token-display h3 {
      font-size: 1.5rem;
      letter-spacing: 2px;
    }
  }

  /* Extra small devices (phones in portrait) */
  @media (max-width: 576px) {
    .page-header {
      padding: 1rem;
      border-radius: 15px;
    }

    .school-logo {
      width: 50px;
      height: 50px;
    }

    .school-info h4 {
      font-size: 1rem;
    }

    .school-info p {
      font-size: 0.85rem;
    }

    .student-info-card {
      padding: 1rem;
    }

    .info-item {
      padding: 0.6rem;
      gap: 0.75rem;
    }

    .info-icon {
      width: 35px;
      height: 35px;
      font-size: 0.9rem;
    }

    .info-label {
      font-size: 0.75rem;
    }

    .info-value {
      font-size: 0.9rem;
    }

    .section-title h4 {
      font-size: 1.1rem;
    }

    .section-title .badge {
      padding: 0.4rem 0.8rem;
      font-size: 0.8rem;
    }

    .exam-card {
      border-radius: 15px;
    }

    .exam-card-header {
      padding: 1rem;
    }

    .exam-badge {
      padding: 0.3rem 0.6rem;
      font-size: 0.75rem;
    }

    .exam-title {
      font-size: 1rem;
    }

    .exam-subtitle {
      font-size: 0.85rem;
    }

    .exam-card-body {
      padding: 1rem;
    }

    .meta-item {
      font-size: 0.85rem;
    }

    .meta-icon {
      width: 30px;
      height: 30px;
      font-size: 0.85rem;
    }

    .status-alert {
      padding: 0.75rem;
      font-size: 0.9rem;
    }

    .token-section {
      padding: 0.75rem;
    }

    .token-display {
      padding: 0.75rem;
    }

    .token-display h3 {
      font-size: 1.25rem;
      letter-spacing: 1px;
    }

    .token-form input {
      padding: 0.6rem 0.8rem;
      font-size: 1rem;
    }

    .btn-start-exam {
      padding: 0.6rem 1rem;
      font-size: 0.95rem;
    }

    .empty-state {
      padding: 3rem 1.5rem;
    }

    .empty-state i {
      font-size: 3.5rem;
    }
  }
</style>

<?php
use App\Models\StudentModel;
use App\Models\SchoolModel;

$schoolModel = new SchoolModel();
$school = $schoolModel->first();
$user = session()->get('user');
$studentModel = new StudentModel();

$student = null;
if (!empty($user['related_id'])) {
  $student = $studentModel
    ->select('students.*, classes.name as class_name')
    ->join('classes', 'classes.id = students.class_id', 'left')
    ->find($user['related_id']);
}
?>

<div class="container-fluid px-lg-4 px-md-3 px-2 py-lg-4 py-md-3 py-2">
  <!-- Page Header -->
  <div class="page-header">
    <div class="school-header">
      <?php if (!empty($school['logo'])): ?>
        <img src="<?= base_url('uploads/logo/' . $school['logo']) ?>" alt="Logo Sekolah" class="school-logo">
      <?php endif; ?>
      <div class="school-info">
        <h4><?= esc($school['name'] ?? 'Sistem CBT') ?></h4>
        <p><i class="bi bi-geo-alt"></i> Computer Based Test - Portal Ujian Online</p>
      </div>
    </div>

    <?php if ($student): ?>
      <div class="student-info-card">
        <h5 class="mb-0" style="color: #2d3748; font-weight: 700;">
          <i class="bi bi-person-badge"></i> Informasi Peserta Ujian
        </h5>

        <div class="info-grid">
          <div class="info-item">
            <div class="info-icon">
              <i class="bi bi-hash"></i>
            </div>
            <div class="info-content">
              <div class="info-label">Nomor Ujian</div>
              <div class="info-value"><?= esc($student['username'] ?? $student['nis'] ?? '-') ?></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <i class="bi bi-card-text"></i>
            </div>
            <div class="info-content">
              <div class="info-label">NIS</div>
              <div class="info-value"><?= esc($student['nis'] ?? '-') ?></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <i class="bi bi-person"></i>
            </div>
            <div class="info-content">
              <div class="info-label">Nama Lengkap</div>
              <div class="info-value"><?= strtoupper(esc($student['name'] ?? '-')) ?></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <i class="bi bi-gender-ambiguous"></i>
            </div>
            <div class="info-content">
              <div class="info-label">Jenis Kelamin</div>
              <div class="info-value"><?= ($student['gender'] ?? 'L') == 'L' ? 'Laki-laki' : 'Perempuan' ?></div>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon">
              <i class="bi bi-building"></i>
            </div>
            <div class="info-content">
              <div class="info-label">Kelas</div>
              <div class="info-value"><?= esc($student['class_name'] ?? '-') ?></div>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </div>

  <!-- Exam List Section -->
  <div class="section-title">
    <h4><i class="bi bi-clipboard-check"></i> Daftar Ujian</h4>
    <span class="badge"><?= is_array($tests) ? count($tests) : 0 ?> Ujian Tersedia</span>
  </div>

  <?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert" style="border-radius: 15px;">
      <i class="bi bi-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
  <?php endif; ?>

  <div class="row" id="exam-list">
    <?php if (empty($tests)): ?>
      <div class="col-12">
        <div class="empty-state">
          <i class="bi bi-inbox"></i>
          <h5>Belum Ada Ujian</h5>
          <p>Saat ini belum ada ujian yang tersedia untuk kelas Anda.</p>
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($tests as $test): ?>
        <?php
        $startTime = strtotime($test['start_time']);
        $endTime = strtotime($test['end_time']);
        ?>
        <div class="col-lg-4 col-md-6 mb-4">
          <div class="exam-card" id="exam-card-<?= $test['test_id'] ?>" data-start="<?= $test['start_time'] ?>"
            data-end="<?= $test['end_time'] ?>" data-id="<?= $test['test_id'] ?>"
            data-show-token="<?= $test['show_token'] ?>" data-token="<?= esc($test['token']) ?>"
            data-status="<?= esc($test['session_status'] ?? '') ?>"
            data-score="<?= esc($test['session_score'] ?? '') !== '' ? $test['session_score'] : '-' ?>"
            data-show-score="<?= esc($test['show_score'] ?? '') ?>">

            <div class="exam-card-header">
              <div class="exam-badge"><?= esc($test['bank_code']) ?></div>
              <h5 class="exam-title"><?= esc($test['subject_name']) ?></h5>
              <p class="exam-subtitle"><?= esc($test['exam_name']) ?></p>
            </div>

            <div class="exam-card-body">
              <div class="exam-meta">
                <div class="meta-item">
                  <div class="meta-icon">
                    <i class="bi bi-calendar-event"></i>
                  </div>
                  <div>
                    <strong><?= date('d M Y', $startTime) ?></strong>
                  </div>
                </div>
                <div class="meta-item">
                  <div class="meta-icon">
                    <i class="bi bi-clock"></i>
                  </div>
                  <div>
                    <?= date('H:i', $startTime) ?> - <?= date('H:i', $endTime) ?> WIB
                  </div>
                </div>
              </div>

              <div class="exam-status">
                <!-- Status will be filled by JavaScript -->
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  // 🔹 FIX: Clear exam finished flags from sessionStorage
  // This prevents issues when starting a new exam after finishing previous one
  (function () {
    try {
      // Get all keys from sessionStorage
      var keys = Object.keys(sessionStorage);
      // Remove all exam_finished_* flags
      for (var i = 0; i < keys.length; i++) {
        if (keys[i].indexOf('exam_finished_') === 0) {
          sessionStorage.removeItem(keys[i]);
        }
      }
    } catch (e) {
      console.warn('Failed to clear exam flags:', e);
    }
  })();

  document.addEventListener("DOMContentLoaded", function () {

    // === 🔹 RENDER STATUS KARTU UJIAN ===
    // 🔧 FIX: Use ES5 string concatenation for old browser compatibility
    var renderStatus = function (card, state) {
      try {
        var showToken = (card.dataset.showToken || '').toLowerCase() === 'ya';
        var showScore = (card.dataset.showScore || '').toLowerCase() === 'ya';
        var token = card.dataset.token || '-';
        var id = card.dataset.id;
        var alreadyDone = (card.dataset.status || '').toLowerCase() === 'finished';
        var score = card.dataset.score && card.dataset.score !== '' ? card.dataset.score : '-';
        var html = '';

        if (alreadyDone) {
          html = '<div class="status-alert finished">' +
            '<i class="bi bi-check-circle-fill"></i> UJIAN SUDAH SELESAI';
          if (showScore) {
            html += '<div class="mt-2"><small>Nilai Anda:</small><br><h4 class="mb-0">' + score + '</h4></div>';
          }
          html += '</div>';
        }
        else if (state === 'waiting' || state === 'upcoming') {
          var startDate = new Date(card.dataset.start);
          var startText = startDate.toLocaleDateString('id-ID', {
            year: 'numeric', month: 'short', day: 'numeric'
          }) + ' ' + startDate.toLocaleTimeString('id-ID', {
            hour: '2-digit', minute: '2-digit'
          });

          html = '<div class="status-alert waiting">' +
            '<i class="bi bi-hourglass-split"></i> MENUNGGU WAKTU UJIAN' +
            '<div class="mt-2"><small>' + startText + '</small></div>' +
            '</div>';
        }
        else if (state === 'running' || state === 'ready') {
          html = '<div class="token-section">';

          if (showToken) {
            html += '<div class="token-display">' +
              '<small style="color: #718096;">Token Ujian</small>' +
              '<h3>' + token + '</h3>' +
              '</div>';
          } else {
            html += '<div class="alert alert-warning mb-3" style="border-radius: 12px;">' +
              '<i class="bi bi-info-circle"></i> Token akan diberikan oleh pengawas' +
              '</div>';
          }

          html += '<form action="<?= site_url("siswa/cbt/verifyToken/") ?>' + id + '" method="post" class="token-form">' +
            '<?= csrf_field() ?>' +
            '<label class="form-label">Masukkan Token Ujian</label>' +
            '<input type="text" name="token" class="form-control mb-3" maxlength="6" placeholder="XXXXXX" required>' +
            '<button type="submit" class="btn-start-exam">' +
            '<i class="bi bi-play-circle"></i> Mulai Ujian' +
            '</button>' +
            '</form>' +
            '</div>';
        }
        else if (state === 'ended' || state === 'passed') {
          html = '<div class="status-alert ended">' +
            '<i class="bi bi-clock-history"></i> WAKTU UJIAN TELAH BERAKHIR' +
            '</div>';
        }

        var container = card.querySelector('.exam-status');
        if (!container) {
          console.error('[CBT] exam-status container not found for card:', card);
          return;
        }

        // Simple assignment without trim comparison for better compatibility
        container.innerHTML = html;
        card.dataset.statusState = state;

        console.log('[CBT] Status rendered:', state, 'for card:', id);
      } catch (error) {
        console.error('[CBT] Error rendering status:', error);
        // Fallback: show basic error message
        var container = card.querySelector('.exam-status');
        if (container) {
          container.innerHTML = '<div class="alert alert-danger" style="border-radius: 12px;">' +
            '<i class="bi bi-exclamation-triangle"></i> Error loading form. Please refresh page.' +
            '</div>';
        }
      }
    };

    // === 🔹 UPDATE STATUS BERDASARKAN WAKTU ===
    var updateStatus = function () {
      try {
        var now = new Date();
        var cards = document.querySelectorAll(".exam-card");

        for (var i = 0; i < cards.length; i++) {
          var card = cards[i];
          var alreadyDone = (card.dataset.status || '').toLowerCase() === 'finished';

          if (alreadyDone) {
            renderStatus(card, 'finished');
            continue;
          }

          var start = new Date(card.dataset.start);
          var end = new Date(card.dataset.end);
          var state = '';

          if (now < start) {
            state = 'waiting';
          } else if (now >= start && now <= end) {
            state = 'running';
          } else {
            state = 'ended';
          }

          if (card.dataset.statusState !== state) {
            renderStatus(card, state);
          }
        }
      } catch (error) {
        console.error('[CBT] Error updating status:', error);
      }
    };

    updateStatus();
    setInterval(updateStatus, 10000);

    // === 🔹 HIGHLIGHT KARTU BARU SELESAI ===
    var justFinishedId = <?= json_encode($just_finished ?? null) ?>;
    var isForced = <?= json_encode(session()->getFlashdata('forced_submit') ?? false) ?>;

    if (isForced) {
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Ujian Dihentikan!',
          text: 'Anda terdeteksi melakukan pelanggaran kecurangan yang berulang. Ujian telah dihentikan secara otomatis.',
          icon: 'error',
          confirmButtonColor: '#d33',
          confirmButtonText: 'Saya Mengerti'
        });
      } else {
        alert('Ujian Dihentikan! Anda terdeteksi melakukan pelanggaran kecurangan yang berulang.');
      }
    }

    if (justFinishedId) {
      var card = document.getElementById('exam-card-' + justFinishedId);
      if (card) {
        card.classList.add('highlight');
        setTimeout(function () {
          card.classList.remove('highlight');
          card.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }, 1000);
        refreshScore(card);
      }
    }

    // === 🔹 AUTO REFRESH NILAI SETELAH SELESAI ===
    function refreshScore(card) {
      var testId = card.dataset.id;

      // Check if fetch is supported
      if (typeof fetch === 'undefined') {
        console.warn('[CBT] Fetch API not supported, skipping score refresh');
        return;
      }

      fetch('<?= site_url("siswa/cbt/getScore/") ?>' + testId, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      })
        .then(function (res) {
          return res.json();
        })
        .then(function (data) {
          if (data.success) {
            card.dataset.status = 'finished';
            card.dataset.score = data.score;
            renderStatus(card, 'finished');
          }
        })
        .catch(function (err) {
          console.error('[CBT] Failed to refresh score:', err);
        });
    }

  });
</script>

<?= $this->endSection() ?>