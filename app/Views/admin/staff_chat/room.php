<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  .chat-message { margin-bottom:10px; display:block; width:100%; overflow-wrap:break-word; word-break:break-word; }
  .chat-bubble { display:inline-block; padding:10px 14px; border-radius:10px; max-width:75%; font-size:.9rem; word-wrap:break-word; overflow-wrap:anywhere; box-shadow:0 1px 2px rgba(0,0,0,.1); }
  .chat-bubble.sent { background:#d1f4cc; margin-left:auto; margin-right:0; }
  .chat-bubble.received { background:#fff; margin-right:auto; }
  .chat-username { font-size:.75rem; font-weight:bold; margin-bottom:2px; }
  .chat-role-badge { font-size:.65rem; opacity:.7; margin-bottom:4px; }
  .chat-reply { border-left:3px solid #6c757d; padding-left:6px; margin-bottom:6px; font-size:.8rem; color:#495057; background:#f8f9fa; border-radius:6px; cursor:pointer; overflow-wrap:anywhere; }
  .chat-footer { display:flex; justify-content:space-between; align-items:center; font-size:.7rem; opacity:.6; margin-top:4px; }
  .chat-action { display:none; color:#0d6efd; cursor:pointer; }
  .chat-bubble:hover .chat-action { display:inline-block; }
  .mention { color:#0d6efd; font-weight:500; }
  #scroll-btn { position:absolute; bottom:80px; right:20px; display:none; z-index:100; border-radius:50%; width:40px; height:40px; padding:0; line-height:40px; text-align:center; box-shadow:0 4px 6px rgba(0,0,0,.1); }
  #mention-dropdown { position:absolute; bottom:100%; left:0; right:0; background:#fff; border:1px solid #dee2e6; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.15); z-index:200; max-height:200px; overflow-y:auto; display:none; }
  #mention-dropdown .mention-item { padding:8px 12px; cursor:pointer; font-size:.875rem; border-bottom:1px solid #f0f0f0; }
  #mention-dropdown .mention-item:hover, #mention-dropdown .mention-item.active { background:#e9f5ff; }
</style>

<div class="card">
  <div class="card-header d-flex align-items-center justify-content-between">
    <span>🏫 Obrolan Staff — <?= esc($room['name']) ?></span>
    <small class="text-muted">Admin · Kepala Sekolah · Guru · Staf</small>
  </div>
  <div class="card-body d-flex flex-column" style="height:72vh; min-height:400px;">

    <div id="chat-box-container" class="position-relative border rounded mb-2 bg-light" style="flex:1; overflow:hidden; display:flex; flex-direction:column; min-height:200px;">
      <div id="chat-box" class="p-2 overflow-auto" style="background:#f0f2f5; position:absolute; top:0; left:0; right:0; bottom:0;">
      <?php
        // Render pesan langsung dari server — tidak perlu AJAX untuk load pertama
        if (!empty($initialMessages)):
            foreach ($initialMessages as $msg):
                // Hitung tanggal untuk separator
                try {
                    $rawDate   = $msg['created_at'] ?? date('Y-m-d H:i:s');
                    $createdAt = \CodeIgniter\I18n\Time::parse($rawDate);
                    $msgTime   = $createdAt->setTimezone('Asia/Jakarta');
                    $msgDate   = $msgTime->format('Y-m-d');
                } catch (\Exception $e) {
                    $msgDate = date('Y-m-d');
                    $msgTime = \CodeIgniter\I18n\Time::now('Asia/Jakarta');
                }
                if (!isset($lastMsgDate) || $lastMsgDate !== $msgDate):
                    $today     = date('Y-m-d');
                    $yesterday = date('Y-m-d', strtotime('-1 day'));
                    $dispDate  = $msgDate === $today ? 'Hari Ini' : ($msgDate === $yesterday ? 'Kemarin' : date('d F Y', strtotime($msgDate)));
                    $lastMsgDate = $msgDate;
        ?>
                <div class="text-center my-3"><span class="badge bg-secondary opacity-75"><?= $dispDate ?></span></div>
        <?php endif; ?>
                <div id="msg-<?= $msg['id'] ?>" class="chat-message <?= $msg['user_id'] == $currentUserId ? 'text-end' : 'text-start' ?>">
                    <div class="chat-bubble <?= $msg['user_id'] == $currentUserId ? 'sent' : 'received' ?>">
                        <?php if (!empty($msg['reply_message']) || !empty($msg['reply_attachment'])): ?>
                        <div class="chat-reply small mb-1" onclick="scrollToMessage(<?= $msg['reply_id'] ?>)">
                            <b><?= esc($msg['reply_user']) ?>:</b>
                            <?php if (!empty($msg['reply_attachment'])): ?><div class="text-muted small"><i class="fas fa-image"></i> Foto</div><?php endif; ?>
                            <?= esc(mb_strimwidth($msg['reply_message'] ?? '', 0, 50, '...')) ?>
                        </div>
                        <?php endif; ?>
                        <div class="chat-username"><?= esc($msg['display_name']) ?></div>
                        <?php if (!empty($msg['role_name'])): ?><div class="chat-role-badge text-muted"><?= esc($msg['role_name']) ?></div><?php endif; ?>
                        <?php if (!empty($msg['attachment'])): ?>
                        <div class="chat-attachment mb-2">
                            <a href="<?= base_url('uploads/chat/' . $msg['attachment']) ?>" class="chat-img-link">
                                <img src="<?= base_url('uploads/chat/' . $msg['attachment']) ?>" class="img-fluid rounded" style="max-width:200px;max-height:200px;object-fit:cover;">
                            </a>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($msg['message'])): ?>
                        <div class="chat-text"><?= nl2br(preg_replace('/@(\w+)/', '<span class="mention">@$1</span>', esc($msg['message']))) ?></div>
                        <?php endif; ?>
                        <div class="chat-footer">
                            <span class="chat-time"><?= $msgTime->format('H:i') ?></span>
                            <a href="javascript:void(0)" class="chat-action btn-reply" data-id="<?= $msg['id'] ?>" data-text="<?= esc(addslashes($msg['message'] ?? '')) ?>">🔁 Balas</a>
                        </div>
                    </div>
                </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="text-muted text-center py-4">Belum ada pesan. Mulai percakapan!</div>
        <?php endif; ?>
      </div>
      <button type="button" id="scroll-btn" class="btn btn-primary btn-sm" onclick="forceScrollBottom()">↓</button>
    </div>

    <form id="chat-form" method="post" action="<?= site_url('admin/staff-chat/send') ?>" class="d-flex flex-column w-100">
      <?= csrf_field() ?>
      <input type="hidden" name="room_id" value="<?= $room['id'] ?>">
      <input type="hidden" name="reply_to" id="reply_to" value="">

      <div id="reply-preview" class="alert alert-secondary py-2 px-3 small d-none mb-2">
        <span id="reply-text"></span>
        <button type="button" class="btn-close float-end" onclick="clearReply()"></button>
      </div>

      <div class="d-flex align-items-end position-relative">
        <button type="button" class="btn btn-outline-secondary me-2" onclick="document.getElementById('photo-input').click()">📷</button>
        <input type="file" id="photo-input" name="photo" class="d-none" accept="image/*" onchange="previewImage(this)">
        <div class="flex-grow-1 position-relative me-2">
          <div id="mention-dropdown"></div>
          <textarea id="chat-input" name="message" class="form-control" placeholder="Tulis pesan... Ketik @ untuk mention" rows="1" style="resize:none;overflow-y:hidden;"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Kirim</button>
      </div>

      <div id="photo-preview-container" class="mt-2 d-none position-relative" style="max-width:100px;">
        <img id="photo-preview" src="#" class="img-thumbnail">
        <button type="button" class="btn-close position-absolute top-0 end-0 bg-white" onclick="clearPhoto()"></button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg">
    <div class="modal-content bg-transparent border-0">
      <div class="modal-body p-0 text-center position-relative">
        <button type="button" class="btn-close btn-close-white position-absolute top-0 end-0 m-3" data-bs-dismiss="modal"></button>
        <img id="modalImage" src="" class="img-fluid rounded shadow-lg" style="max-height:90vh;">
      </div>
    </div>
  </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
const MEMBERS = <?= json_encode(array_map(function($m) {
    $key = preg_replace('/[^a-z0-9_]/', '', strtolower(str_replace(' ', '_', $m['fullname'] ?? $m['username'])));
    return ['username' => $m['username'], 'fullname' => $m['fullname'] ?? $m['username'], 'mention_key' => $key];
}, $members), JSON_UNESCAPED_UNICODE) ?>;

const ROOM_ID = <?= (int)$room['id'] ?>;
const FETCH_URL = "<?= site_url('admin/staff-chat/fetch/' . $room['id']) ?>";
const SEND_URL  = "<?= site_url('admin/staff-chat/send') ?>";
const CLEAR_URL = "<?= site_url('admin/staff-chat/clear-mentions') ?>";

let mentionQuery = '', mentionActive = false, mentionIndex = -1;
let lastMessageId = 0;

// Ambil ID pesan tertinggi dari pesan yang sudah di-render server
(function() {
  document.querySelectorAll('[id^="msg-"]').forEach(function(el) {
    var id = parseInt(el.id.replace('msg-', ''));
    if (id > lastMessageId) lastMessageId = id;
  });
})();

function playNotifSound() {
  try {
    var ctx = new (window.AudioContext || window.webkitAudioContext)();
    var osc = ctx.createOscillator(), gain = ctx.createGain();
    osc.connect(gain); gain.connect(ctx.destination);
    osc.frequency.setValueAtTime(880, ctx.currentTime);
    osc.frequency.setValueAtTime(660, ctx.currentTime + 0.1);
    gain.gain.setValueAtTime(0.3, ctx.currentTime);
    gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
    osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.4);
  } catch(e) {}
}

function refreshChat() {
  var chatBox = document.getElementById('chat-box');
  var isAtBottom = chatBox.scrollTop + chatBox.clientHeight >= chatBox.scrollHeight - 100;

  // Tambahkan timestamp untuk mencegah browser cache response AJAX
  $.get(FETCH_URL, { _: Date.now() }).done(function(html) {
    // Guard: jangan update jika response bukan HTML pesan yang valid
    if (!html || html.trim() === '') {
      if (lastMessageId > 0) {
        console.warn('StaffChat: fetch returned empty response, skipping update');
      }
      return;
    }
    if (html.includes('<html') || html.includes('<!DOCTYPE') || html.includes('<title>')) {
      console.warn('StaffChat: fetch returned full HTML page (possible redirect), skipping update');
      return;
    }

    // Deteksi pesan baru
    var tempDiv = $('<div>').html(html);
    var msgIds = tempDiv.find('[id^="msg-"]').map(function() {
      return parseInt(this.id.replace('msg-', ''));
    }).get();
    var newMaxId = msgIds.length > 0 ? Math.max.apply(null, msgIds) : 0;
    var msgCount = tempDiv.find('.chat-message').length;

    // Guard: jangan kosongkan chat jika response tidak punya pesan
    // tapi lastMessageId sudah ada (artinya sebelumnya ada pesan)
    if (msgCount === 0 && lastMessageId > 0) {
      console.warn('StaffChat: fetch returned 0 messages but lastMessageId=' + lastMessageId + ', skipping update to prevent blank chat');
      return;
    }

    if (newMaxId > lastMessageId && lastMessageId > 0) {
      playNotifSound();
    }
    if (newMaxId > 0) lastMessageId = newMaxId;

    $('#chat-box').html(html);
    if (isAtBottom) forceScrollBottom();
  }).fail(function(xhr) {
    console.error('StaffChat fetch error:', xhr.status);
  });
}

function forceScrollBottom() {
  var c = document.getElementById('chat-box');
  c.scrollTop = c.scrollHeight;
  $('#scroll-btn').fadeOut();
}

function clearReply() { $('#reply_to').val(''); $('#reply-preview').addClass('d-none'); }

function setReply(msgId, text) {
  $('#reply_to').val(msgId);
  $('#reply-text').text(text.substring(0, 60));
  $('#reply-preview').removeClass('d-none');
  $('#chat-input').focus();
}

function scrollToMessage(msgId) {
  var t = document.getElementById('msg-' + msgId);
  if (t) { t.scrollIntoView({behavior:'smooth',block:'center'}); t.classList.add('bg-warning'); setTimeout(function(){t.classList.remove('bg-warning');},2000); }
}

function previewImage(input) {
  if (input.files && input.files[0]) {
    var reader = new FileReader();
    reader.onload = function(e) { $('#photo-preview').attr('src', e.target.result); $('#photo-preview-container').removeClass('d-none'); };
    reader.readAsDataURL(input.files[0]);
  }
}

function clearPhoto() { $('#photo-input').val(''); $('#photo-preview').attr('src','#'); $('#photo-preview-container').addClass('d-none'); }

function showMentionDropdown(query) {
  var filtered = MEMBERS.filter(function(m) {
    return m.fullname.toLowerCase().includes(query.toLowerCase()) || m.mention_key.toLowerCase().startsWith(query.toLowerCase());
  }).slice(0, 8);
  var dd = $('#mention-dropdown');
  if (!filtered.length) { dd.hide(); return; }
  dd.html(filtered.map(function(m, i) {
    return '<div class="mention-item' + (i===mentionIndex?' active':'') + '" data-mention="' + m.mention_key + '"><strong>' + m.fullname + '</strong></div>';
  }).join('')).show();
}

function hideMentionDropdown() { $('#mention-dropdown').hide(); mentionActive=false; mentionIndex=-1; }

function insertMention(key) {
  var input = document.getElementById('chat-input');
  var atPos = input.value.lastIndexOf('@');
  if (atPos !== -1) input.value = input.value.substring(0, atPos) + '@' + key + ' ';
  hideMentionDropdown(); input.focus();
}

var chatInterval = null;

function startPolling() {
  if (chatInterval) clearInterval(chatInterval);
  chatInterval = setInterval(refreshChat, 5000);
}

function stopPolling() {
  if (chatInterval) {
    clearInterval(chatInterval);
    chatInterval = null;
  }
}

// Tangani bfcache: saat halaman di-restore, restart polling dan refresh pesan
window.addEventListener('pageshow', function(e) {
  if (e.persisted) {
    // Halaman di-restore dari bfcache — jalankan ulang semua
    lastMessageId = 0;
    refreshChat();
    startPolling();
  }
});

// Bersihkan interval saat halaman di-navigate pergi
window.addEventListener('pagehide', function() {
  stopPolling();
});

$(function() {
  // Scroll ke bawah saat halaman pertama dibuka
  forceScrollBottom();

  // Mulai polling
  startPolling();

  // Clear mention
  $.get(CLEAR_URL);

  // Submit
  $('#chat-form').on('submit', function(e) {
    e.preventDefault();
    var btn = $(this).find('button[type=submit]').prop('disabled', true).text('Mengirim...');
    $.ajax({
      url: SEND_URL, type: 'POST', data: new FormData(this),
      processData: false, contentType: false, dataType: 'json',
      success: function(res) {
        if (res.status === 'ok') {
          $('#chat-input').val('').css('height','auto');
          clearReply(); clearPhoto(); hideMentionDropdown();
          setTimeout(function() { refreshChat(); setTimeout(forceScrollBottom, 200); }, 150);
        }
      },
      error: function(xhr) { alert(xhr.responseJSON?.message || 'Gagal mengirim.'); },
      complete: function() { btn.prop('disabled', false).text('Kirim'); }
    });
  });

  // Auto-resize textarea + mention autocomplete
  $('#chat-input').on('input', function() {
    this.style.height = 'auto'; this.style.height = this.scrollHeight + 'px';
    var val = this.value, atPos = val.lastIndexOf('@');
    if (atPos !== -1) {
      var query = val.substring(atPos + 1);
      if (/^\w*$/.test(query)) { mentionActive=true; mentionQuery=query; showMentionDropdown(query); return; }
    }
    hideMentionDropdown();
  });

  // Keyboard navigation mention
  $('#chat-input').on('keydown', function(e) {
    if (!mentionActive) return;
    var items = $('#mention-dropdown .mention-item');
    if (e.key==='ArrowDown') { e.preventDefault(); mentionIndex=Math.min(mentionIndex+1,items.length-1); items.removeClass('active').eq(mentionIndex).addClass('active'); }
    else if (e.key==='ArrowUp') { e.preventDefault(); mentionIndex=Math.max(mentionIndex-1,0); items.removeClass('active').eq(mentionIndex).addClass('active'); }
    else if (e.key==='Enter'||e.key==='Tab') { if(mentionIndex>=0){e.preventDefault();insertMention(items.eq(mentionIndex).data('mention'));} }
    else if (e.key==='Escape') { hideMentionDropdown(); }
  });

  $(document).on('click', '.mention-item', function() { insertMention($(this).data('mention')); });

  $('#chat-box').on('scroll', function() {
    var near = this.scrollTop + this.clientHeight >= this.scrollHeight - 150;
    near ? $('#scroll-btn').fadeOut() : $('#scroll-btn').fadeIn();
  });

  $(document).on('click', '.btn-reply', function() { setReply($(this).data('id'), $(this).data('text')); });

  $(document).on('click', '.chat-img-link', function(e) {
    e.preventDefault(); $('#modalImage').attr('src', $(this).attr('href'));
    new bootstrap.Modal(document.getElementById('imageModal')).show();
  });
});
</script>
<?= $this->endSection() ?>
