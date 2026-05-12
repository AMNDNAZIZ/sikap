<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  /* ===== Chat Layout Core ===== */
  .chat-box {
    background: #f0f2f5;
    padding: 10px;
    display: flex;
    flex-direction: column;
    overflow-x: hidden;
    /* cegah dorong layout */
    max-width: 100%;
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
    border-radius: 10px;
    max-width: 75%;
    font-size: 0.9rem;
    word-wrap: break-word;
    overflow-wrap: anywhere;
    white-space: normal;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
  }

  .chat-bubble.sent {
    background: #d1f4cc;
    margin-left: auto;
    margin-right: 0;
    text-align: left;
  }

  .chat-bubble.received {
    background: #ffffff;
    margin-right: auto;
    text-align: left;
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
    padding-left: 6px;
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
    opacity: 0.6;
    margin-top: 4px;
  }

  .chat-action {
    display: none;
    color: #0d6efd;
    cursor: pointer;
  }

  .chat-bubble:hover .chat-action,
  .chat-bubble.show-action .chat-action {
    display: inline-block;
  }

  /* Mention highlight */
  .mention {
    color: #0d6efd;
    font-weight: 500;
    overflow-wrap: anywhere;
  }

  /* Pastikan body tidak ikut meluber */
  body {
    overflow-x: hidden;
  }

  #scroll-btn {
    position: absolute;
    bottom: 80px;
    right: 20px;
    display: none;
    z-index: 100;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    padding: 0;
    line-height: 40px;
    text-align: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.1);
  }
</style>

<div class="card">
  <div class="card-header">
    💬 Obrolan Group <?= esc($class['name']) ?>
  </div>
  <div class="card-body d-flex flex-column" style="height:70vh">

    <!-- Chat Box -->
    <div id="chat-box-container" class="position-relative flex-grow-1 overflow-hidden d-flex flex-column border rounded mb-2 bg-light">
      <div id="chat-box" class="p-2 flex-grow-1 overflow-auto">
        <div class="text-muted">Memuat pesan...</div>
      </div>
      <button type="button" id="scroll-btn" class="btn btn-primary btn-sm" onclick="forceScrollBottom()">
        ↓
      </button>
    </div>

    <!-- Form Kirim Pesan -->
    <form id="chat-form" method="post" action="<?= site_url($role . '/chat/send') ?>" class="d-flex flex-column w-100">
      <?= csrf_field() ?>
      <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
      <input type="hidden" name="reply_to" id="reply_to" value="">

      <!-- Reply Preview -->
      <div id="reply-preview" class="alert alert-secondary py-2 px-3 small d-none mb-2">
        <span id="reply-text"></span>
        <button type="button" class="btn-close float-end" onclick="clearReply()"></button>
      </div>

      <div class="d-flex align-items-end">
        <button type="button" class="btn btn-outline-secondary me-2"
          onclick="document.getElementById('photo-input').click()">
          📷
        </button>
        <input type="file" id="photo-input" name="photo" class="d-none" accept="image/*" onchange="previewImage(this)">

        <textarea id="chat-input" name="message" class="form-control me-2" placeholder="Tulis pesan..." rows="1"
          style="resize: none; overflow-y: hidden;"></textarea>
        <button type="submit" class="btn btn-primary">Kirim</button>
      </div>

      <!-- Photo Preview -->
      <div id="photo-preview-container" class="mt-2 d-none position-relative" style="max-width: 100px;">
        <img id="photo-preview" src="#" class="img-thumbnail">
        <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="clearPhoto()"></button>
      </div>
    </form>

  </div>
</div>

<!-- Modal Image Preview -->
<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body p-0 text-center position-relative">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal" aria-label="Close"></button>
        <img id="modalImage" src="" class="img-fluid rounded shadow-lg" style="max-height: 90vh;">
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
  function refreshChat() {
    let chatBox = $('#chat-box');
    let container = chatBox[0];
    let isAtBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 100;

    $.get("<?= site_url($role . '/chat/fetch/' . $room['id']) ?>", function (html) {
      // Hanya update jika konten berubah (cegah flicker)
      if (chatBox.data('current-html') !== html) {
        chatBox.data('current-html', html);
        chatBox.html(html);
        
        // Tunggu gambar load sebelum scroll
        if (isAtBottom) {
          waitForImages();
        }
      }
    }).fail(function (xhr) {
      console.error("Fetch failed: ", xhr.responseText);
      if (chatBox.html().trim() === 'Memuat pesan...') {
        chatBox.html('<div class="alert alert-danger">Gagal memuat pesan. Hubungi admin atau coba lagi.</div>');
      }
    });
  }

  function waitForImages() {
    let chatBox = $('#chat-box');
    let imgs = chatBox.find('img');
    let loaded = 0;
    
    if (imgs.length === 0) {
      forceScrollBottom();
      return;
    }

    imgs.each(function() {
      if (this.complete) {
        loaded++;
      } else {
        $(this).on('load error', function() {
          loaded++;
          if (loaded === imgs.length) forceScrollBottom();
        });
      }
    });

    if (loaded === imgs.length) forceScrollBottom();
  }

  function forceScrollBottom() {
    let container = document.getElementById('chat-box');
    container.scrollTop = container.scrollHeight;
    $('#scroll-btn').fadeOut();
  }

  function clearReply() {
    $('#reply_to').val('');
    $('#reply-preview').addClass('d-none');
  }

  function setReply(msgId, text) {
    $('#reply_to').val(msgId);
    $('#reply-text').text(text.substring(0, 50));
    $('#reply-preview').removeClass('d-none');
  }

  function scrollToMessage(msgId) {
    let target = document.getElementById('msg-' + msgId);
    if (target) {
      target.scrollIntoView({ behavior: 'smooth', block: 'center' });
      target.classList.add('bg-warning');
      setTimeout(() => target.classList.remove('bg-warning'), 2000);
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
    refreshChat();
    setInterval(refreshChat, 5000);

    // Bersihkan mention saat buka room
    $.get("<?= site_url($role . '/chat/clear-mentions/' . $class['id']) ?>", function (res) {
      if (res.cleared > 0) {
        // Jika ada mention yang dibersihkan, coba update badge di sidebar jika ada
        if (window.parent && window.parent.updateMentionBadge) {
          window.parent.updateMentionBadge();
        }
      }
    });

    $('#chat-form').on('submit', function (e) {
      e.preventDefault();

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
            refreshChat();
          }
        }
      }).fail(function (xhr) {
        let msg = "Gagal mengirim pesan.";
        if (xhr.responseJSON && xhr.responseJSON.message) {
          msg += "\nDetail: " + xhr.responseJSON.message;
        }
        console.error("Send failed: ", xhr.responseText);
        alert(msg);
      });
    });

    // Auto-resize textarea
    $('#chat-input').on('input', function () {
      this.style.height = 'auto';
      this.style.height = (this.scrollHeight) + 'px';
    });

    // Detect scroll to show/hide button
    $('#chat-box').on('scroll', function() {
      let container = this;
      let isNearBottom = container.scrollTop + container.clientHeight >= container.scrollHeight - 150;
      if (isNearBottom) {
        $('#scroll-btn').fadeOut();
      } else {
        $('#scroll-btn').fadeIn();
      }
    });

    $(document).on('click', '.btn-reply', function () {
      let msgId = $(this).data('id');
      let text = $(this).data('text');
      setReply(msgId, text);
    });

    // Image Preview Modal
    $(document).on('click', '.chat-img-link', function(e) {
      e.preventDefault();
      let src = $(this).attr('href');
      $('#modalImage').attr('src', src);
      new bootstrap.Modal(document.getElementById('imageModal')).show();
    });
  });
</script>
<?= $this->endSection() ?>