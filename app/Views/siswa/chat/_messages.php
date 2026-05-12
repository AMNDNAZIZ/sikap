<?php foreach ($messages as $msg): ?>
  <div id="msg-<?= $msg['id'] ?>" 
       class="chat-message <?= $msg['user_id'] == session('user.id') ? 'sent' : 'received' ?>">

    <div class="chat-bubble <?= $msg['user_id'] == session('user.id') ? 'sent' : 'received' ?>">

      <!-- 🔹 Jika pesan ini balasan -->
      <?php if (!empty($msg['reply_message'])): ?>
        <div class="chat-reply small mb-1 p-1 border-start"
             onclick="scrollToMessage(<?= $msg['reply_id'] ?>)">
          <b><?= esc($msg['reply_user']) ?>:</b> 
          <?= esc(mb_strimwidth($msg['reply_message'], 0, 40, '...')) ?>
        </div>
      <?php endif; ?>

      <!-- 🔹 Identitas & isi pesan -->
      <div class="chat-username"><?= esc($msg['display_name']) ?></div>
      <div class="chat-text"><?= esc($msg['message']) ?></div>

      <!-- 🔹 Footer: waktu + tombol balas -->
      <div class="chat-footer">
        <span class="chat-time"><?= date('H:i', strtotime($msg['created_at'])) ?></span>
        <span class="chat-action">
          <a href="javascript:void(0)" 
             onclick="setReply('<?= $msg['id'] ?>','<?= esc(addslashes($msg['message'])) ?>')">
            🔁 Balas
          </a>
        </span>
      </div>

    </div>
  </div>
<?php endforeach; ?>
