<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  /* ===== Chat Layout Core ===== */
  .chat-card-body {
    height: 72vh;
    min-height: 420px;
    max-height: 85vh;
  }

  .chat-box {
    background: #f0f2f5;
    padding: 10px;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    max-width: 100%;
    -webkit-overflow-scrolling: touch; /* Smooth momentum scrolling di iOS Safari */
  }

  /* Setiap pesan tidak boleh melewati container */
  .chat-message {
    margin-bottom: 10px;
    display: block;
    width: 100%;
    max-width: 100%;
    overflow-wrap: break-word;
    word-break: break-word;
  }

  /* Bubble tampilan pesan */
  .chat-bubble {
    display: inline-block;
    padding: 10px 14px;
    border-radius: 12px;
    max-width: 78%;
    font-size: 0.9rem;
    word-wrap: break-word;
    overflow-wrap: anywhere;
    white-space: normal;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.08);
  }

  .chat-bubble.sent {
    background: #d1f4cc;
    margin-left: auto;
    margin-right: 0;
    text-align: left;
    border-bottom-right-radius: 3px;
  }

  .chat-bubble.received {
    background: #ffffff;
    margin-right: auto;
    text-align: left;
    border-bottom-left-radius: 3px;
  }

  /* Username di atas pesan */
  .chat-username {
    font-size: 0.75rem;
    font-weight: bold;
    margin-bottom: 4px;
  }

  /* Balasan (reply preview di dalam pesan) */
  .chat-reply {
    border-left: 3px solid #6c757d;
    padding-left: 8px;
    margin-bottom: 6px;
    font-size: 0.8rem;
    color: #495057;
    background: #f8f9fa;
    border-radius: 6px;
    cursor: pointer;
    overflow-wrap: anywhere;
  }

  /* Footer: waktu & aksi */
  .chat-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-size: 0.7rem;
    opacity: 0.65;
    margin-top: 4px;
    gap: 8px;
  }

  .chat-action {
    display: none;
    color: #0d6efd;
    cursor: pointer;
    text-decoration: none;
    padding: 2px 4px;
    border-radius: 4px;
  }

  .chat-bubble:hover .chat-action,
  .chat-bubble.show-action .chat-action {
    display: inline-block;
  }

  /* Mention highlight */
  .mention {
    color: #0d6efd;
    font-weight: 600;
    overflow-wrap: anywhere;
  }

  /* Tombol melayang scroll ke bawah & notifikasi pesan baru */
  #scroll-btn {
    position: absolute;
    bottom: 20px;
    right: 20px;
    display: none;
    z-index: 100;
    border-radius: 50px;
    padding: 6px 14px;
    font-size: 0.8rem;
    box-shadow: 0 4px 10px rgba(0,0,0,0.15);
    transition: transform 0.2s ease;
  }

  #scroll-btn:active {
    transform: scale(0.95);
  }

  /* Indikator status koneksi real-time */
  .chat-status-pill {
    font-size: 0.7rem;
    padding: 2px 8px;
    border-radius: 20px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
  }

  /* Penyesuaian Khusus HP / Layar Kecil (< 768px) */
  @media (max-width: 768px) {
    .chat-card-body {
      height: calc(100dvh - 160px);
      min-height: 320px;
      padding: 8px !important;
    }

    .chat-bubble {
      max-width: 88%;
      font-size: 0.85rem;
      padding: 8px 12px;
    }

    /* Di layar sentuh HP: tombol balas selalu tampak tanpa perlu hover */
    .chat-action {
      display: inline-block;
      opacity: 0.85;
      font-size: 0.75rem;
    }

    #scroll-btn {
      bottom: 15px;
      right: 15px;
      padding: 5px 12px;
      font-size: 0.75rem;
    }
  }
</style>

<div class="card shadow-sm border-0">
  <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center gap-2">
      <span class="fs-5">💬</span>
      <div>
        <h6 class="mb-0 fw-bold">Obrolan Kelas <?= esc($class['name']) ?></h6>
        <span class="chat-status-pill bg-success bg-opacity-10 text-success" id="chat-status-badge">
          <span class="spinner-grow spinner-grow-sm" style="width: 6px; height: 6px;" role="status"></span> Real-time
        </span>
      </div>
    </div>
    <a href="<?= site_url($role . '/chat') ?>" class="btn btn-sm btn-outline-secondary rounded-pill px-3">
      ← Kembali
    </a>
  </div>
  <div class="card-body chat-card-body d-flex flex-column">

    <!-- Chat Box -->
    <div id="chat-box-container" class="position-relative flex-grow-1 overflow-hidden d-flex flex-column border rounded-3 mb-2 bg-light">
      <div id="chat-box" class="p-2 flex-grow-1 overflow-auto">
        <div class="text-center py-4 text-muted" id="chat-loading-placeholder">
          <span class="spinner-border spinner-border-sm me-2 text-primary"></span> Memuat obrolan...
        </div>
      </div>
      <button type="button" id="scroll-btn" class="btn btn-primary btn-sm rounded-pill shadow" onclick="forceScrollBottom()">
        <span id="scroll-btn-text">↓ Ke Pesan Terbaru</span>
      </button>
    </div>

    <!-- Form Kirim Pesan -->
    <form id="chat-form" method="post" action="<?= site_url($role . '/chat/send') ?>" class="d-flex flex-column w-100">
      <?= csrf_field() ?>
      <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
      <input type="hidden" name="reply_to" id="reply_to" value="">

      <!-- Reply Preview -->
      <div id="reply-preview" class="alert alert-secondary py-2 px-3 small d-none mb-2 rounded-3 border-0 bg-secondary bg-opacity-10 d-flex justify-content-between align-items-center">
        <div>
          <i class="bi bi-reply-fill text-primary me-1"></i> Membalas: <b id="reply-text"></b>
        </div>
        <button type="button" class="btn-close" style="font-size: 0.7rem;" onclick="clearReply()"></button>
      </div>

      <div class="d-flex align-items-end gap-1 gap-sm-2">
        <button type="button" class="btn btn-outline-secondary rounded-circle p-2 flex-shrink-0" style="width: 42px; height: 42px;"
          title="Kirim Foto"
          onclick="document.getElementById('photo-input').click()">
          📷
        </button>
        <input type="file" id="photo-input" name="photo" class="d-none" accept="image/*" onchange="previewImage(this)">

        <textarea id="chat-input" name="message" class="form-control rounded-4 px-3 py-2" placeholder="Tulis pesan... (@nama untuk mention)" rows="1"
          style="resize: none; overflow-y: hidden; max-height: 120px;"></textarea>
        <button type="submit" id="btn-send-chat" class="btn btn-primary rounded-circle p-2 flex-shrink-0" style="width: 42px; height: 42px;" title="Kirim">
          <span id="btn-send-icon">➤</span>
        </button>
      </div>

      <!-- Photo Preview -->
      <div id="photo-preview-container" class="mt-2 d-none position-relative" style="max-width: 90px;">
        <img id="photo-preview" src="#" class="img-thumbnail rounded-3 shadow-sm" style="max-height: 80px; object-fit: cover;">
        <button type="button" class="btn-close position-absolute top-0 end-0 bg-white rounded-circle p-1 shadow-sm" style="font-size: 0.6rem;" onclick="clearPhoto()"></button>
      </div>
    </form>

  </div>
</div>

<!-- Modal Image Preview -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body p-0 text-center position-relative">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3 shadow" data-bs-dismiss="modal" aria-label="Close"></button>
        <img id="modalImage" src="" class="img-fluid rounded-3 shadow-lg" style="max-height: 90vh;">
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  let lastMessageId = 0;
  let isPolling = false;
  let pollIntervalMs = 2500; // 2.5 detik saat tab aktif
  let pollTimer = null;
  let unreadNewCount = 0;
  let isTabActive = true;

  // Inisialisasi obrolan awal (memuat pesan yang sudah ada)
  function initChat() {
    let chatBox = $('#chat-box');
    $.get("<?= site_url($role . '/chat/fetch/' . $room['id']) ?>", function (html) {
      chatBox.html(html);
      findAndUpdateLastId();
      forceScrollBottom();
      startPolling();
    }).fail(function (xhr) {
      console.error("Init chat failed: ", xhr.responseText);
      chatBox.html('<div class="alert alert-danger my-3">Gagal memuat pesan. Silakan muat ulang halaman.</div>');
    });
  }

  // Cari ID pesan terakhir dari DOM
  function findAndUpdateLastId() {
    let msgElements = $('#chat-box').find('.chat-message[data-id]');
    if (msgElements.length > 0) {
      let maxId = 0;
      msgElements.each(function () {
        let idVal = parseInt($(this).data('id'), 10);
        if (idVal > maxId) maxId = idVal;
      });
      if (maxId > lastMessageId) {
        lastMessageId = maxId;
      }
    }
  }

  // Polling incremental real-time (hanya download pesan baru setelah lastMessageId)
  function pollNewMessages() {
    if (isPolling) return;
    isPolling = true;

    let chatBox = $('#chat-box');
    let container = chatBox[0];
    let isNearBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) <= 120;

    $.getJSON("<?= site_url($role . '/chat/fetch/' . $room['id']) ?>", { after: lastMessageId }, function (res) {
      if (res && res.status === 'ok' && res.count > 0 && res.html) {
        // Parse HTML untuk membersihkan date-divider duplikat
        let $temp = $('<div>').html(res.html);
        $temp.find('.date-divider').each(function () {
          let dateStr = $(this).data('date');
          if (chatBox.find('.date-divider[data-date="' + dateStr + '"]').length > 0) {
            $(this).remove();
          }
        });

        // Hapus placeholder jika ada
        $('#chat-loading-placeholder').remove();

        // Tambahkan hanya pesan baru secara halus ke DOM
        chatBox.append($temp.html());
        lastMessageId = res.last_id || lastMessageId;

        if (isNearBottom) {
          waitForImagesAndScroll();
          unreadNewCount = 0;
          $('#scroll-btn').fadeOut();
        } else {
          // Jika pengguna sedang scroll ke atas membaca riwayat lama, jangan paksa scroll ke bawah
          unreadNewCount += res.count;
          $('#scroll-btn-text').text(`↓ ${unreadNewCount} Pesan Baru`);
          $('#scroll-btn').fadeIn();
        }
      }
      $('#chat-status-badge').removeClass('bg-warning text-dark').addClass('bg-success text-success').html(
        '<span class="spinner-grow spinner-grow-sm" style="width: 6px; height: 6px;" role="status"></span> Real-time'
      );
    }).fail(function (xhr) {
      $('#chat-status-badge').removeClass('bg-success text-success').addClass('bg-warning text-dark').text('⚠️ Menghubungkan...');
    }).always(function () {
      isPolling = false;
    });
  }

  // Pengatur timer adaptif
  function startPolling() {
    clearInterval(pollTimer);
    pollTimer = setInterval(pollNewMessages, pollIntervalMs);
  }

  // Deteksi ketika tab disembunyikan/layar HP mati untuk hemat baterai dan kuota siswa
  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      isTabActive = false;
      pollIntervalMs = 15000; // Hemat daya: 15 detik saat background
      startPolling();
    } else {
      isTabActive = true;
      pollIntervalMs = 2500; // Real-time: 2.5 detik saat layar aktif
      startPolling();
      pollNewMessages(); // Segera cek pesan begitu layar HP dibuka kembali
    }
  });

  function waitForImagesAndScroll() {
    let chatBox = $('#chat-box');
    let imgs = chatBox.find('img');
    let loaded = 0;

    if (imgs.length === 0) {
      forceScrollBottom();
      return;
    }

    imgs.each(function () {
      if (this.complete) {
        loaded++;
      } else {
        $(this).one('load error', function () {
          loaded++;
          if (loaded === imgs.length) forceScrollBottom();
        });
      }
    });

    if (loaded === imgs.length) forceScrollBottom();
  }

  function forceScrollBottom() {
    let container = document.getElementById('chat-box');
    if (container) {
      container.scrollTop = container.scrollHeight;
    }
    unreadNewCount = 0;
    $('#scroll-btn').fadeOut();
  }

  function clearReply() {
    $('#reply_to').val('');
    $('#reply-preview').addClass('d-none');
  }

  function setReply(msgId, text) {
    $('#reply_to').val(msgId);
    $('#reply-text').text(text.substring(0, 45) + (text.length > 45 ? '...' : ''));
    $('#reply-preview').removeClass('d-none');
    $('#chat-input').focus();
  }

  function scrollToMessage(msgId) {
    let target = document.getElementById('msg-' + msgId);
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.add('bg-warning', 'bg-opacity-25', 'rounded-3');
      setTimeout(() => target.classList.remove('bg-warning', 'bg-opacity-25', 'rounded-3'), 2000);
    }
  }

  function previewImage(input) {
    if (input.files && input.files[0]) {
      let reader = new FileReader();
      reader.onload = function (e) {
        $('#photo-preview').attr('src', e.target.result);
        $('#photo-preview-container').removeClass('d-none');
      }
      reader.readAsDataURL(input.files[0]);
    }
  }

  function clearPhoto() {
    $('#photo-input').val('');
    $('#photo-preview').attr('src', '#');
    $('#photo-preview-container').addClass('d-none');
  }

  $(function () {
    // Jalankan inisialisasi chat
    initChat();

    // Bersihkan mention saat buka room
    $.get("<?= site_url($role . '/chat/clear-mentions/' . $class['id']) ?>", function (res) {
      if (res && res.cleared > 0) {
        if (window.parent && window.parent.updateMentionBadge) {
          window.parent.updateMentionBadge();
        }
      }
    });

    // Kirim pesan via AJAX dengan proteksi double-submit & auto-CSRF
    $('#chat-form').on('submit', function (e) {
      e.preventDefault();

      let msgText = $('#chat-input').val().trim();
      let hasFile = $('#photo-input').val() !== '';
      if (!msgText && !hasFile) return;

      let sendBtn = $('#btn-send-chat');
      let sendIcon = $('#btn-send-icon');
      sendBtn.prop('disabled', true);
      sendIcon.html('<span class="spinner-border spinner-border-sm" role="status"></span>');

      let formData = new FormData(this);

      $.ajax({
        url: "<?= site_url($role . '/chat/send') ?>",
        type: "POST",
        data: formData,
        processData: false,
        contentType: false,
        dataType: 'json',
        success: function (res) {
          if (res.status === 'ok') {
            $('#chat-input').val('').css('height', 'auto');
            clearReply();
            clearPhoto();

            // Perbarui token CSRF jika server mengembalikannya
            if (res.csrf) {
              $('input[name="<?= csrf_token() ?>"]').val(res.csrf);
            }

            // Segera fetch pesan baru agar pesan sendiri muncul instan (real-time)
            pollNewMessages();
            setTimeout(forceScrollBottom, 150);
          }
        }
      }).fail(function (xhr) {
        let msg = "Gagal mengirim pesan.";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          msg += "\nDetail: " + xhr.responseJSON.message;
        }
        if (xhr.responseJSON && xhr.responseJSON.csrf) {
          $('input[name="<?= csrf_token() ?>"]').val(xhr.responseJSON.csrf);
        }
        console.error("Send failed: ", xhr.responseText);
        alert(msg);
      }).always(function () {
        sendBtn.prop('disabled', false);
        sendIcon.text('➤');
      });
    });

    // Auto-resize textarea
    $('#chat-input').on('input', function () {
      this.style.height = 'auto';
      this.style.height = Math.min(this.scrollHeight, 120) + 'px';
    });

    // Kirim dengan Enter (Shift+Enter untuk baris baru) di desktop
    $('#chat-input').on('keydown', function (e) {
      if (e.key === 'Enter' && !e.shiftKey && window.innerWidth > 768) {
        e.preventDefault();
        $('#chat-form').submit();
      }
    });

    // Scroll helper saat keyboard virtual HP muncul
    $('#chat-input').on('focus', function () {
      setTimeout(forceScrollBottom, 300);
    });

    if (window.visualViewport) {
      window.visualViewport.addEventListener('resize', () => {
        if (document.activeElement && document.activeElement.id === 'chat-input') {
          setTimeout(forceScrollBottom, 100);
        }
      });
    }

    // Deteksi scroll untuk tombol scroll-to-bottom
    $('#chat-box').on('scroll', function () {
      let container = this;
      let isNearBottom = (container.scrollHeight - container.scrollTop - container.clientHeight) <= 120;
      if (isNearBottom) {
        unreadNewCount = 0;
        $('#scroll-btn').fadeOut();
      } else {
        if (unreadNewCount === 0) {
          $('#scroll-btn-text').text('↓ Ke Pesan Terbaru');
        }
        $('#scroll-btn').fadeIn();
      }
    });

    $(document).on('click', '.btn-reply', function () {
      let msgId = $(this).data('id');
      let text = $(this).data('text');
      setReply(msgId, text);
    });

    // Image Preview Modal
    $(document).on('click', '.chat-img-link', function (e) {
      e.preventDefault();
      let src = $(this).attr('href');
      $('#modalImage').attr('src', src);
      new bootstrap.Modal(document.getElementById('imageModal')).show();
    });
  });
</script>
<?= $this->endSection() ?>