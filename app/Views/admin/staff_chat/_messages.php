<?php
use CodeIgniter\I18n\Time;

$lastDate = null;
$roleLabels = [
    1 => 'Admin',
    2 => 'Kepala Sekolah',
    3 => 'Guru',
    7 => 'Staf',
];
?>
<?php foreach ($messages as $msg):
    try {
        $rawDate   = $msg['created_at'] ?? date('Y-m-d H:i:s');
        $createdAt = Time::parse($rawDate);
        $msgTime   = $createdAt->setTimezone('Asia/Jakarta');
        $msgDate   = $msgTime->format('Y-m-d');
    } catch (\Exception $e) {
        $msgDate = date('Y-m-d');
        $msgTime = Time::now('Asia/Jakarta');
    }

    // Separator tanggal
    if ($lastDate !== $msgDate):
        $today     = date('Y-m-d');
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if ($msgDate === $today)           $displayDate = 'Hari Ini';
        elseif ($msgDate === $yesterday)   $displayDate = 'Kemarin';
        else                               $displayDate = date('d F Y', strtotime($msgDate));
        $lastDate = $msgDate;
?>
    <div class="text-center my-3">
        <span class="badge bg-secondary opacity-75"><?= $displayDate ?></span>
    </div>
<?php endif; ?>

    <div id="msg-<?= $msg['id'] ?>"
         class="chat-message <?= $msg['user_id'] == $currentUserId ? 'text-end' : 'text-start' ?>">

        <div class="chat-bubble <?= $msg['user_id'] == $currentUserId ? 'sent' : 'received' ?>">

            <?php if (!empty($msg['reply_message']) || !empty($msg['reply_attachment'])): ?>
                <div class="chat-reply small mb-1" onclick="scrollToMessage(<?= $msg['reply_id'] ?>)">
                    <b><?= esc($msg['reply_user']) ?>:</b>
                    <?php if (!empty($msg['reply_attachment'])): ?>
                        <div class="text-muted small"><i class="fas fa-image"></i> Foto</div>
                    <?php endif; ?>
                    <?= esc(mb_strimwidth($msg['reply_message'] ?? '', 0, 50, '...')) ?>
                </div>
            <?php endif; ?>

            <!-- Nama + role pengirim -->
            <div class="chat-username"><?= esc($msg['display_name']) ?></div>
            <?php if (!empty($msg['role_name'])): ?>
                <div class="chat-role-badge text-muted"><?= esc($msg['role_name']) ?></div>
            <?php endif; ?>

            <!-- Attachment -->
            <?php if (!empty($msg['attachment'])): ?>
                <div class="chat-attachment mb-2">
                    <a href="<?= base_url('uploads/chat/' . $msg['attachment']) ?>" class="chat-img-link">
                        <img src="<?= base_url('uploads/chat/' . $msg['attachment']) ?>"
                             class="img-fluid rounded"
                             style="max-width:200px; max-height:200px; object-fit:cover;">
                    </a>
                </div>
            <?php endif; ?>

            <!-- Isi pesan dengan mention highlight -->
            <?php if (!empty($msg['message'])): ?>
                <div class="chat-text">
                    <?= nl2br(preg_replace(
                        '/@(\w+)/',
                        '<span class="mention">@$1</span>',
                        esc($msg['message'])
                    )) ?>
                </div>
            <?php endif; ?>

            <!-- Footer: waktu + balas -->
            <div class="chat-footer">
                <span class="chat-time"><?= $msgTime->format('H:i') ?></span>
                <a href="javascript:void(0)" class="chat-action btn-reply"
                   data-id="<?= $msg['id'] ?>"
                   data-text="<?= esc(addslashes($msg['message'] ?? '')) ?>">
                    🔁 Balas
                </a>
            </div>

        </div>
    </div>
<?php endforeach; ?>
