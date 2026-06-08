<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>
<style>
.chat-message { margin-bottom: 4px; }

.chat-bubble {
  padding: 6px 10px;
  border-radius: 12px;
  max-width: 70%;
  font-size: 0.9rem;
  line-height: 1.3;
}

.chat-bubble.sent {
  background: #0d6efd;
  color: white;
  text-align: left;
}

.chat-bubble.received {
  background: #e9ecef;
  color: #212529;
}

.chat-username {
  font-weight: 600;
  font-size: 0.75rem;
  margin-bottom: 2px;
}

.chat-text {
  margin: 0;
}

.chat-time {
  font-size: 0.7rem;
  text-align: right;
  opacity: 0.7;
  margin-top: 2px;
}
</style>

<div class="card">
  <div class="card-header">
    💬 Obrolan Kelas <?= esc($class['name'] ?? $room['class_id']) ?>
  </div>
  <div class="card-body d-flex flex-column" style="height: 70vh;">

    <!-- Chat Box -->
    <div id="chat-box" class="border rounded p-2 mb-2 flex-grow-1 overflow-auto" style="background:#f9f9f9;">
      <div class="text-muted">Memuat pesan...</div>
    </div>

    <!-- Form Kirim Pesan -->
    <form id="chat-form" method="post" action="<?= site_url('admin/chat/send') ?>" class="d-flex align-items-center">
      <?= csrf_field() ?>
      <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
      <input type="text" id="chat-input" name="message" class="form-control me-2" placeholder="Tulis pesan..." autocomplete="off" required>
      <button type="submit" class="btn btn-primary">Kirim</button>
    </form>

  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function(){
  const classId = <?= (int) $room['class_id'] ?>;

  // 🔹 Clear mention badge saat room dibuka
  $.get("<?= site_url('admin/chat/clear-mentions') ?>/" + classId, function(){
    $("#mentionBadge").hide();
  });

  // 🔹 Refresh pesan
  function refreshChat() {
    $.get("<?= site_url('admin/chat/fetch/'.$room['id']) ?>", function(html){
      $('#chat-box').html(html);
      $('#chat-box').scrollTop($('#chat-box')[0].scrollHeight);
    });
  }

  refreshChat();                 // pertama kali load
  setInterval(refreshChat, 5000); // refresh tiap 5 detik

  // 🔹 Kirim pesan
  $('#chat-form').on('submit', function(e){
    e.preventDefault();
    $.post("<?= site_url('admin/chat/send') ?>", $(this).serialize(), function(){
      $('#chat-input').val('');
      refreshChat();
    }).fail(function(){
      alert('Gagal mengirim pesan');
    });
  });
});
</script>
<?= $this->endSection() ?>


