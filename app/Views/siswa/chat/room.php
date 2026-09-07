<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
.chat-box {
  background: #f0f2f5;
  padding: 10px;
  display: flex;
  flex-direction: column;
}

.chat-message {
  display: flex;
  margin-bottom: 10px;
  width: 100%;
}

/* bubble utama */
.chat-bubble {
  padding: 5px 8px;
  border-radius: 10px;
  max-width: 75%;
  font-size: 0.9rem;
  word-wrap: break-word;
  box-shadow: 0 1px 2px rgba(0,0,0,0.1);
  display: flex;
  flex-direction: column;
}

.chat-message.sent {
  justify-content: flex-end;  /* ➡️ pesan user sendiri ke kanan */
}

.chat-message.received {
  justify-content: flex-start; /* ⬅️ pesan lawan ke kiri */
}

.chat-bubble.sent {
  background: #d1f4cc;   /* hijau muda */
  text-align: left;
}

.chat-bubble.received {
  background: #ffffff;   /* putih */
  text-align: left;
}

.chat-username {
  font-size: 0.65rem;
  font-weight: bold;
  margin-bottom: 4px;
  color: #0d6efd;
}

.chat-reply {
  border-left: 3px solid #6c757d;
  padding-left: 6px;
  margin-bottom: 6px;
  font-size: 0.65rem;
  color: #495057;
  background: #f8f9fa;
  border-radius: 6px;
  cursor: pointer;
}

.chat-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  font-size: 0.7rem;
  opacity: 0.6;
  margin-top: 4px;
}

.chat-time {
  opacity: 0.6;
}

.mention {
  color: #0d6efd;
  font-weight: 500;
}

.chat-action {
  display: none;
  color: #0d6efd;
  cursor: pointer;
}

.chat-bubble:hover .chat-action {
  display: inline-block; /* muncul saat hover */
}

.chat-bubble.show-action .chat-action {
  display: inline-block; /* untuk mobile (long press) */
}
</style>

<div class="card">
  <div class="card-header">
    💬 Obrolan Kelas <?= esc($class['name']) ?>
  </div>
  <div class="card-body d-flex flex-column" style="height:70vh">

    <!-- Chat Box -->
    <div id="chat-box" class="border rounded p-2 mb-2 flex-grow-1 overflow-auto bg-light">
      <div class="text-muted">Memuat pesan...</div>
    </div>

    <!-- Form Kirim Pesan -->
    <form id="chat-form" method="post" action="<?= site_url('siswa/chat/send') ?>" class="d-flex flex-column w-100">
      <?= csrf_field() ?>
      <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
      <input type="hidden" name="reply_to" id="reply_to" value="">

      <!-- Reply Preview -->
      <div id="reply-preview" class="alert alert-secondary py-2 px-3 small d-none mb-2">
        <span id="reply-text"></span>
        <button type="button" class="btn-close float-end" onclick="clearReply()"></button>
      </div>

      <div class="d-flex align-items-center">
        <input type="text" id="chat-input" name="message" class="form-control me-2" placeholder="Tulis pesan..." autocomplete="off" required>
        <button type="submit" class="btn btn-primary">Kirim</button>
      </div>
    </form>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
function refreshChat() {
  let chatBox = $('#chat-box');
  let isAtBottom = chatBox[0].scrollTop + chatBox[0].clientHeight >= chatBox[0].scrollHeight - 50;

  $.get("<?= site_url('siswa/chat/fetch/'.$room['id']) ?>", function(html){
    chatBox.html(html);
    if (isAtBottom) {
      chatBox.scrollTop(chatBox[0].scrollHeight);
    }
  });
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

$(function(){
  refreshChat();
  setInterval(refreshChat, 5000);

  var CSRF_NAME = "<?= csrf_token() ?>";

  // Update field CSRF di semua form dengan token terbaru dari response
  function updateCsrf(res) {
    if (res && res.csrf) {
      $('input[name="' + CSRF_NAME + '"]').val(res.csrf);
    }
  }

  $('#chat-form').on('submit', function(e){
    e.preventDefault();
    var $form = $(this);
    $.post("<?= site_url('siswa/chat/send') ?>", $form.serialize(), function(res){
      updateCsrf(res);
      if (res.status === 'ok') {
        $('#chat-input').val('');
        clearReply();
        refreshChat();
      } else if (res.message) {
        alert('Gagal mengirim: ' + res.message);
      }
    }, 'json').fail(function(xhr){
      // CSRF gagal (403) — ambil token baru lalu minta user coba lagi
      if (xhr.status === 403) {
        // Refresh halaman token via reload ringan CSRF
        $.get("<?= site_url('siswa/chat/csrf') ?>", function(r){
          if (r && r.csrf) $('input[name="' + CSRF_NAME + '"]').val(r.csrf);
        }, 'json').always(function(){
          alert('Sesi keamanan diperbarui. Silakan kirim pesan lagi.');
        });
      } else {
        alert('Gagal mengirim pesan. Coba lagi.');
      }
    });
  });

  // Mobile long-press untuk munculkan tombol balas
  let pressTimer;
  $(document).on('touchstart mousedown', '.chat-bubble', function() {
    let $bubble = $(this);
    pressTimer = setTimeout(function() {
      $('.chat-bubble').removeClass('show-action');
      $bubble.addClass('show-action');
    }, 600);
  }).on('touchend mouseup mouseleave', '.chat-bubble', function() {
    clearTimeout(pressTimer);
  });

  // Tutup tombol balas saat klik di luar bubble
  $(document).on('click touchstart', function(e) {
    if (!$(e.target).closest('.chat-bubble').length) {
      $('.chat-bubble').removeClass('show-action');
    }
  });
});
</script>
<?= $this->endSection() ?>
