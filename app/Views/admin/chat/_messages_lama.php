<?php foreach ($messages as $msg): ?>
  <div class="chat-message d-flex <?= $msg['user_id'] == session('user.id') ? 'justify-content-end' : 'justify-content-start' ?>">
    <div class="chat-bubble <?= $msg['user_id'] == session('user.id') ? 'sent' : 'received' ?>">
      <div class="chat-username"><?= esc($msg['display_name']) ?></div>
      <div class="chat-text"><?= esc($msg['message']) ?></div>
      <div class="chat-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
    </div>
  </div>
<?php endforeach; ?>
