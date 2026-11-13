<!-- ==========================================================================
     AI COPILOT & SMART CHAT ASSISTANT (Ctrl+K / Spotlight / Chatbot Drawer)
     ========================================================================== -->
<div class="modal fade command-palette-modal" id="commandPaletteModal" tabindex="-1" aria-labelledby="commandPaletteModalLabel" aria-hidden="true" data-bs-backdrop="true">
  <div class="modal-dialog modal-dialog-centered modal-lg command-palette-dialog">
    <div class="modal-content command-palette-content border-0 shadow-lg">
      
      <!-- Top Navigation Tabs (Mode Switcher) -->
      <div class="d-flex align-items-center justify-content-between px-3 pt-2 bg-light border-bottom">
        <ul class="nav nav-pills copilot-mode-pills" id="copilotModeTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active py-1 px-3 fs-7" id="tab-navigasi-btn" data-bs-toggle="pill" data-bs-target="#tab-navigasi" type="button" role="tab" style="font-size: 0.78rem;">
              <i class="bi bi-compass me-1"></i>Navigasi Cepat
            </button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link py-1 px-3 fs-7 position-relative" id="tab-chat-btn" data-bs-toggle="pill" data-bs-target="#tab-chat" type="button" role="tab" style="font-size: 0.78rem;">
              <i class="bi bi-chat-dots-fill me-1 text-primary"></i>Chat Asisten AI
              <span class="badge rounded-pill bg-primary ms-1" style="font-size: 0.6rem;">Smart</span>
            </button>
          </li>
        </ul>
        <div class="d-flex align-items-center gap-2 pb-1">
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
      </div>

      <!-- Tab Content Area -->
      <div class="tab-content" id="copilotModeTabContent">
        
        <!-- ==========================================
             TAB 1: NAVIGASI CEPAT (SPOTLIGHT COMMANDS)
             ========================================== -->
        <div class="tab-pane fade show active" id="tab-navigasi" role="tabpanel">
          <!-- Header Input -->
          <div class="command-palette-header p-3 border-bottom d-flex align-items-center gap-2">
            <i class="bi bi-search text-primary fs-5"></i>
            <div class="position-relative flex-grow-1">
              <input type="text" 
                     class="form-control border-0 shadow-none ps-2 pe-4 fs-6 command-input" 
                     id="commandPaletteInput" 
                     placeholder="Ketik perintah atau cari (contoh: 'modul pendidikan pancasila', 'rekap absen')..." 
                     autocomplete="off">
              <button type="button" class="btn btn-sm text-muted position-absolute end-0 top-50 translate-middle-y d-none" id="btnClearCommand">
                <i class="bi bi-x-circle-fill"></i>
              </button>
            </div>

            <!-- Voice Command Button -->
            <button type="button" class="btn btn-sm btn-outline-secondary rounded-circle command-voice-btn" id="btnVoiceCommand" title="Perintah Suara (Bicara)">
              <i class="bi bi-mic"></i>
            </button>
          </div>

          <!-- Quick Suggestion Chips -->
          <div class="command-palette-chips px-3 py-2 bg-light border-bottom d-flex align-items-center gap-2 overflow-x-auto text-nowrap">
            <span class="text-muted small fw-medium me-1" style="font-size: 0.75rem;"><i class="bi bi-lightning-charge me-1"></i>Pintas:</span>
            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 chip-btn" data-query="modul ajar">Modul Ajar</button>
            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 chip-btn" data-query="input nilai">Input Nilai</button>
            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 chip-btn" data-query="presensi">Presensi</button>
            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 chip-btn" data-query="bank soal cbt">Bank Soal CBT</button>
            <button type="button" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2 chip-btn" data-query="rekap kehadiran">Rekap Kehadiran</button>
          </div>

          <!-- List Items Container -->
          <div class="modal-body p-0 command-palette-body" style="max-height: 420px; overflow-y: auto;">
            <!-- Loading State -->
            <div id="commandPaletteLoading" class="text-center py-4 d-none">
              <div class="spinner-border spinner-border-sm text-primary me-2" role="status"></div>
              <span class="text-muted small">Mencari aksi yang diizinkan...</span>
            </div>

            <!-- List Group -->
            <div class="list-group list-group-flush" id="commandPaletteList">
              <!-- Populated by JavaScript -->
            </div>

            <!-- Empty State -->
            <div id="commandPaletteEmpty" class="text-center py-5 d-none">
              <i class="bi bi-search text-muted fs-1 mb-2 d-block"></i>
              <h6 class="text-muted fw-normal mb-1">Tidak ditemukan menu yang cocok</h6>
              <p class="text-muted small mb-3">Tanyakan pada Chat Asisten AI untuk analisis otomatis</p>
              <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" id="btnSwitchToChat">
                <i class="bi bi-chat-dots-fill me-1"></i>Tanya di Chat Asisten AI
              </button>
            </div>
          </div>
        </div>

        <!-- ==========================================
             TAB 2: CHAT ASISTEN AI (INTERACTIVE CHATBOT)
             ========================================== -->
        <div class="tab-pane fade" id="tab-chat" role="tabpanel">
          
          <!-- Chat Message Thread -->
          <div class="p-3 copilot-chat-thread" id="copilotChatThread" style="height: 380px; overflow-y: auto; background: #f8fafc;">
            <!-- Bot Welcome Bubble -->
            <div class="d-flex align-items-start gap-2 mb-3">
              <div class="copilot-avatar-bot bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
                <i class="bi bi-stars" style="font-size: 0.9rem;"></i>
              </div>
              <div class="copilot-bubble-bot p-3 rounded-4 bg-white shadow-sm border" style="max-width: 85%;">
                <div class="fw-semibold text-primary mb-1" style="font-size: 0.8rem;">Asisten AI ESPATA</div>
                <p class="mb-2 text-dark small" style="line-height: 1.45;">
                  Halo! Saya asisten cerdas ESPATA. Anda dapat mengetikkan perintah apa saja seperti <em>"buat modul pancasila"</em>, menanyakan <em>"jadwal saya hari ini"</em>, atau meminta <em>"rekap kehadiran guru"</em>.
                </p>
                <div class="d-flex flex-wrap gap-1 mt-2">
                  <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2 chat-prompt-chip" data-prompt="Jadwal mengajar saya hari ini">📅 Jadwal Hari Ini</button>
                  <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2 chat-prompt-chip" data-prompt="Rekap kehadiran guru hari ini">📊 Rekap Presensi Guru</button>
                  <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2 chat-prompt-chip" data-prompt="Monitoring siswa ujian CBT">💻 Monitoring CBT</button>
                  <button type="button" class="btn btn-xs btn-outline-secondary rounded-pill py-0 px-2 chat-prompt-chip" data-prompt="Modul ajar pendidikan pancasila">📚 Modul Pancasila</button>
                </div>
              </div>
            </div>
            <!-- More messages will be appended here -->
          </div>

          <!-- Chat Input Footer -->
          <div class="p-2 border-top bg-white">
            <form id="copilotChatForm" class="d-flex align-items-center gap-2">
              <input type="text" 
                     class="form-control rounded-pill border shadow-none px-3 py-2 fs-6" 
                     id="copilotChatInput" 
                     placeholder="Tanyakan atau ketik perintah ke asisten..." 
                     autocomplete="off">
              <button type="submit" class="btn btn-primary rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" id="btnSendChat" style="width: 40px; height: 40px;" title="Kirim Perintah">
                <i class="bi bi-send-fill" style="font-size: 0.9rem;"></i>
              </button>
            </form>
          </div>

        </div>

      </div>

      <!-- Footer Help / Hotkeys -->
      <div class="modal-footer px-3 py-2 bg-light border-top d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-3 text-muted small" style="font-size: 0.72rem;">
          <span><kbd class="bg-white text-secondary border px-1 rounded shadow-none">Ctrl</kbd> + <kbd class="bg-white text-secondary border px-1 rounded shadow-none">K</kbd> Pintas</span>
          <span><kbd class="bg-white text-secondary border px-1 rounded shadow-none">Esc</kbd> Tutup</span>
        </div>
        <div class="text-muted small" style="font-size: 0.72rem;">
          <span class="text-primary fw-medium"><i class="bi bi-shield-check me-1"></i>RBAC Protected & Audit Logged</span>
        </div>
      </div>

    </div>
  </div>
</div>

<!-- Floating Action Button for AI Copilot (Mobile & Desktop Shortcut) -->
<button type="button" 
        class="btn btn-primary shadow-lg position-fixed copilot-floating-btn d-flex align-items-center justify-content-center" 
        id="btnFloatingCopilot" 
        title="Buka AI Copilot & Navigasi Cepat (Ctrl+K)">
  <i class="bi bi-stars fs-5"></i>
  <span class="copilot-floating-text ms-2 d-none d-md-inline fw-medium">AI Copilot</span>
</button>

<style>
/* ── Command Palette Modal Styles ── */
.command-palette-dialog {
  max-width: 680px;
  margin-top: 5vh;
}
.command-palette-content {
  border-radius: 16px;
  overflow: hidden;
  background: #ffffff;
}
.copilot-mode-pills .nav-link {
  color: #64748b;
  border-radius: 8px;
  font-weight: 500;
  transition: all 0.15s ease;
}
.copilot-mode-pills .nav-link.active {
  background-color: #ffffff;
  color: #0d6efd;
  box-shadow: 0 1px 3px rgba(0,0,0,0.1);
}
.command-palette-header input {
  font-size: 1.05rem;
}
.command-palette-header input:focus {
  outline: none;
  box-shadow: none;
}
.command-item {
  padding: 10px 16px;
  border: none;
  border-bottom: 1px solid rgba(241, 245, 249, 0.9);
  transition: all 0.15s ease-in-out;
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: space-between;
}
.command-item:last-child {
  border-bottom: none;
}
.command-item:hover, .command-item.active {
  background-color: #f0f7ff !important;
  color: #0d6efd;
}
.command-item.active .command-title {
  color: #0d6efd;
  font-weight: 600;
}
.command-icon-box {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: #f8fafc;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
  border: 1px solid #e2e8f0;
}
.command-item:hover .command-icon-box,
.command-item.active .command-icon-box {
  background: #e0f2fe;
  color: #0284c7;
  border-color: #bae6fd;
}
.category-header {
  font-size: 0.72rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  color: #64748b;
  padding: 8px 16px 4px 16px;
  background-color: #f8fafc;
}
.chip-btn, .chat-prompt-chip {
  font-size: 0.75rem;
  border-color: #cbd5e1;
  color: #475569;
}
.chip-btn:hover, .chat-prompt-chip:hover {
  background-color: #0d6efd;
  border-color: #0d6efd;
  color: #fff;
}

/* ── Chat Thread Bubbles ── */
.copilot-chat-thread::-webkit-scrollbar {
  width: 6px;
}
.copilot-chat-thread::-webkit-scrollbar-thumb {
  background: #cbd5e1;
  border-radius: 3px;
}
.copilot-bubble-user {
  background: #0d6efd;
  color: #ffffff;
  border-radius: 16px 16px 4px 16px;
  padding: 8px 14px;
  max-width: 80%;
  font-size: 0.85rem;
}
.copilot-bubble-bot {
  border-radius: 4px 16px 16px 16px;
  font-size: 0.85rem;
}

/* ── Floating Copilot Button ── */
.copilot-floating-btn {
  bottom: 24px;
  right: 24px;
  z-index: 1040;
  border-radius: 50px;
  height: 48px;
  padding: 0 16px;
  background: linear-gradient(135deg, #0d6efd 0%, #0284c7 100%);
  border: 2px solid rgba(255, 255, 255, 0.4);
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.copilot-floating-btn:hover {
  transform: translateY(-3px) scale(1.02);
  box-shadow: 0 10px 25px -5px rgba(13, 110, 253, 0.45) !important;
}
@media (max-width: 767.98px) {
  .copilot-chat-thread {
    height: 280px !important;
    max-height: 48vh !important;
  }
  .command-palette-dialog {
    margin: 8px;
    margin-top: 12px;
  }
  .copilot-floating-btn {
    bottom: 74px; /* Naik di atas mobile bottom bar */
    right: 16px;
    width: 44px;
    height: 44px;
    padding: 0;
  }
}
</style>

<script>
(function() {
  'use strict';

  let cachedActions = [];
  let isFetchingActions = false;
  let activeIndex = -1;
  let modalInstance = null;

  const modalEl = document.getElementById('commandPaletteModal');
  const inputEl = document.getElementById('commandPaletteInput');
  const listEl = document.getElementById('commandPaletteList');
  const emptyEl = document.getElementById('commandPaletteEmpty');
  const loadingEl = document.getElementById('commandPaletteLoading');
  const btnClear = document.getElementById('btnClearCommand');
  const btnVoice = document.getElementById('btnVoiceCommand');
  const btnFloating = document.getElementById('btnFloatingCopilot');
  const btnNavbar = document.getElementById('btnOpenCommandPalette');
  const btnSwitchToChat = document.getElementById('btnSwitchToChat');

  // Chat Elements
  const chatThread = document.getElementById('copilotChatThread');
  const chatForm = document.getElementById('copilotChatForm');
  const chatInput = document.getElementById('copilotChatInput');

  const CSRF_TOKEN_NAME = '<?= csrf_token() ?>';
  let CSRF_HASH = '<?= csrf_hash() ?>';

  // Inisialisasi Modal
  function getModal() {
    if (!modalInstance && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
      modalInstance = new bootstrap.Modal(modalEl);
    }
    return modalInstance;
  }

  function openPalette(initialQuery = '', openTab = 'navigasi') {
    const modal = getModal();
    if (!modal) return;
    modal.show();

    if (openTab === 'chat') {
      const tabChatBtn = document.getElementById('tab-chat-btn');
      if (tabChatBtn) {
        const tab = new bootstrap.Tab(tabChatBtn);
        tab.show();
        setTimeout(() => {
          chatInput.focus();
          if (initialQuery) {
            chatInput.value = initialQuery;
            submitChatMessage(initialQuery);
          }
        }, 150);
      }
    } else {
      const tabNavBtn = document.getElementById('tab-navigasi-btn');
      if (tabNavBtn) {
        const tab = new bootstrap.Tab(tabNavBtn);
        tab.show();
      }
      setTimeout(() => {
        if (initialQuery) {
          inputEl.value = initialQuery;
          triggerSearch(initialQuery);
        } else {
          inputEl.focus();
          inputEl.select();
          renderActions(cachedActions.slice(0, 15));
        }
      }, 150);
    }
  }

  // Bind Triggers
  if (btnFloating) btnFloating.addEventListener('click', () => openPalette('', 'chat'));
  if (btnNavbar) btnNavbar.addEventListener('click', () => openPalette('', 'navigasi'));

  if (btnSwitchToChat) {
    btnSwitchToChat.addEventListener('click', () => {
      const q = inputEl.value;
      const tabChatBtn = document.getElementById('tab-chat-btn');
      if (tabChatBtn) {
        const tab = new bootstrap.Tab(tabChatBtn);
        tab.show();
        chatInput.value = q;
        submitChatMessage(q);
      }
    });
  }

  // Global Keyboard Shortcut: Ctrl + K atau Cmd + K
  document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && (e.key === 'k' || e.key === 'K')) {
      e.preventDefault();
      openPalette();
    }
  });

  // Ambil Data Aksi dari Server (Sekali di awal, cached di sessionStorage)
  function loadActions() {
    if (cachedActions.length > 0 || isFetchingActions) return;

    try {
      const stored = sessionStorage.getItem('espata_user_actions');
      if (stored) {
        cachedActions = JSON.parse(stored);
        return;
      }
    } catch(e) {}

    isFetchingActions = true;
    fetch('<?= site_url('api/ai-copilot/actions') ?>', {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success' && Array.isArray(data.actions)) {
        cachedActions = data.actions;
        if (data.csrf_hash) {
          CSRF_HASH = data.csrf_hash;
        }
        try {
          sessionStorage.setItem('espata_user_actions', JSON.stringify(cachedActions));
        } catch(e) {}
      }
    })
    .catch(err => console.warn('[AI Copilot] Error loading actions:', err))
    .finally(() => { isFetchingActions = false; });
  }

  document.addEventListener('DOMContentLoaded', loadActions);
  modalEl.addEventListener('show.bs.modal', loadActions);

  // Pencarian Cepat (Fuzzy Filtering Client-Side)
  function triggerSearch(query) {
    const q = query.trim().toLowerCase();

    if (btnClear) {
      if (q.length > 0) btnClear.classList.remove('d-none');
      else btnClear.classList.add('d-none');
    }

    if (!q) {
      renderActions(cachedActions.slice(0, 15));
      return;
    }

    const tokens = q.split(/\s+/).filter(t => t.length > 1);

    const scored = [];
    cachedActions.forEach(act => {
      let score = 0;
      const titleLower = (act.title || '').toLowerCase();
      const subLower = (act.subtitle || '').toLowerCase();
      const keywords = act.keywords || [];

      if (titleLower.includes(q)) score += 50;
      if (subLower.includes(q)) score += 15;

      tokens.forEach(t => {
        if (titleLower.includes(t)) score += 20;
        if (keywords.includes(t)) score += 10;
        if (subLower.includes(t)) score += 5;
      });

      if (score > 0) {
        scored.push({ act, score });
      }
    });

    scored.sort((a, b) => b.score - a.score);
    const results = scored.map(item => item.act);

    if (results.length > 0) {
      renderActions(results.slice(0, 20));
    } else {
      listEl.innerHTML = '';
      emptyEl.classList.remove('d-none');
    }
  }

  // Render Hasil ke List Group dengan Grouping Kategori
  function renderActions(actions) {
    emptyEl.classList.add('d-none');
    listEl.innerHTML = '';
    activeIndex = 0;

    if (!actions || actions.length === 0) {
      emptyEl.classList.remove('d-none');
      return;
    }

    const groups = {};
    actions.forEach(act => {
      const cat = act.category || 'Menu';
      if (!groups[cat]) groups[cat] = [];
      groups[cat].push(act);
    });

    let overallIdx = 0;
    for (const catName in groups) {
      const header = document.createElement('div');
      header.className = 'category-header';
      header.textContent = catName;
      listEl.appendChild(header);

      groups[catName].forEach(act => {
        const item = document.createElement('div');
        item.className = 'command-item list-group-item-action' + (overallIdx === 0 ? ' active' : '');
        item.dataset.index = overallIdx;
        item.dataset.url = act.url;

        item.innerHTML = `
          <div class="d-flex align-items-center gap-3">
            <div class="command-icon-box">
              <i class="${act.icon || 'bi bi-arrow-right-circle'} fs-6"></i>
            </div>
            <div>
              <div class="command-title fw-medium text-dark">${escapeHtml(act.title)}</div>
              <div class="text-muted small" style="font-size: 0.75rem;">${escapeHtml(act.subtitle || '')}</div>
            </div>
          </div>
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 0.68rem;">${escapeHtml(act.badge || 'Aksi')}</span>
            <i class="bi bi-chevron-right text-muted small"></i>
          </div>
        `;

        item.addEventListener('click', () => {
          executeAction(act.url);
        });

        listEl.appendChild(item);
        overallIdx++;
      });
    }
  }

  function executeAction(url) {
    if (!url) return;
    const modal = getModal();
    if (modal) modal.hide();
    window.location.href = url;
  }

  function escapeHtml(str) {
    return (str || '').replace(/[&<>"']/g, function(m) {
      return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'}[m];
    });
  }

  // Input Listeners
  inputEl.addEventListener('input', function() {
    triggerSearch(this.value);
  });

  if (btnClear) {
    btnClear.addEventListener('click', function() {
      inputEl.value = '';
      inputEl.focus();
      triggerSearch('');
    });
  }

  // Keyboard Arrow Navigation
  inputEl.addEventListener('keydown', function(e) {
    const items = listEl.querySelectorAll('.command-item');
    if (items.length === 0) {
      if (e.key === 'Enter') {
        const tabChatBtn = document.getElementById('tab-chat-btn');
        if (tabChatBtn) {
          const tab = new bootstrap.Tab(tabChatBtn);
          tab.show();
          submitChatMessage(inputEl.value);
        }
      }
      return;
    }

    if (e.key === 'ArrowDown') {
      e.preventDefault();
      activeIndex = (activeIndex + 1) % items.length;
      updateActiveItem(items);
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      activeIndex = (activeIndex - 1 + items.length) % items.length;
      updateActiveItem(items);
    } else if (e.key === 'Enter') {
      e.preventDefault();
      if (activeIndex >= 0 && activeIndex < items.length) {
        const targetUrl = items[activeIndex].dataset.url;
        executeAction(targetUrl);
      }
    }
  });

  function updateActiveItem(items) {
    items.forEach((it, idx) => {
      if (idx === activeIndex) {
        it.classList.add('active');
        it.scrollIntoView({ block: 'nearest' });
      } else {
        it.classList.remove('active');
      }
    });
  }

  // Chip Listeners
  document.querySelectorAll('.chip-btn').forEach(btn => {
    btn.addEventListener('click', function() {
      const q = this.dataset.query || '';
      inputEl.value = q;
      inputEl.focus();
      triggerSearch(q);
    });
  });

  // Prompt Chips in Chat
  document.addEventListener('click', function(e) {
    if (e.target && e.target.classList.contains('chat-prompt-chip')) {
      const p = e.target.dataset.prompt;
      if (p) submitChatMessage(p);
    }
  });

  // ==========================================
  // CHATBOT INTERACTION LOGIC
  // ==========================================
  chatForm.addEventListener('submit', function(e) {
    e.preventDefault();
    const q = chatInput.value.trim();
    if (q) {
      submitChatMessage(q);
      chatInput.value = '';
    }
  });

  function appendUserBubble(text) {
    const bubble = document.createElement('div');
    bubble.className = 'd-flex justify-content-end mb-3';
    bubble.innerHTML = `
      <div class="copilot-bubble-user shadow-sm">
        ${escapeHtml(text)}
      </div>
    `;
    chatThread.appendChild(bubble);
    chatThread.scrollTop = chatThread.scrollHeight;
  }

  function appendBotLoading() {
    const loadingId = 'bot-loading-' + Date.now();
    const bubble = document.createElement('div');
    bubble.className = 'd-flex align-items-start gap-2 mb-3';
    bubble.id = loadingId;
    bubble.innerHTML = `
      <div class="copilot-avatar-bot bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
        <i class="bi bi-stars" style="font-size: 0.9rem;"></i>
      </div>
      <div class="copilot-bubble-bot p-3 rounded-4 bg-white shadow-sm border text-muted small d-flex align-items-center gap-2">
        <div class="spinner-border spinner-border-sm text-primary" role="status"></div>
        <span>Memproses data & permintaan...</span>
      </div>
    `;
    chatThread.appendChild(bubble);
    chatThread.scrollTop = chatThread.scrollHeight;
    return loadingId;
  }

  function submitChatMessage(query) {
    appendUserBubble(query);
    const loadingId = appendBotLoading();

    const payload = { query: query };
    payload[CSRF_TOKEN_NAME] = CSRF_HASH;

    fetch('<?= site_url('api/ai-copilot/ask') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
        'X-CSRF-TOKEN': CSRF_HASH
      },
      body: JSON.stringify(payload)
    })
    .then(r => {
      if (r.status === 401) {
        throw new Error('UNAUTHORIZED');
      }
      if (r.status === 429) {
        throw new Error('RATE_LIMITED');
      }
      return r.json();
    })
    .then(res => {
      const loadingEl = document.getElementById(loadingId);
      if (loadingEl) loadingEl.remove();

      if (res.csrf_hash) {
        CSRF_HASH = res.csrf_hash;
      }

      renderBotResponse(res);
    })
    .catch(err => {
      const loadingEl = document.getElementById(loadingId);
      if (loadingEl) loadingEl.remove();

      if (err.message === 'UNAUTHORIZED') {
        renderBotMessage('Sesi login Anda telah berakhir. Silakan muat ulang halaman atau login kembali.');
      } else if (err.message === 'RATE_LIMITED') {
        renderBotMessage('Anda mengirim pesan terlalu cepat. Harap tunggu beberapa detik sebelum mencoba lagi.');
      } else {
        renderBotMessage('Maaf, terjadi kendala saat memproses permintaan Anda. Silakan coba kembali.');
      }
    });
  }

  function renderBotResponse(res) {
    const bubble = document.createElement('div');
    bubble.className = 'd-flex align-items-start gap-2 mb-3';

    let contentHtml = '';

    // A. Widget Jadwal
    if (res.widget_type === 'schedule_card' && res.widget_data) {
      const w = res.widget_data;
      contentHtml += `<p class="mb-2 text-dark small">${escapeHtml(res.explanation)}</p>`;
      if (w.schedules && w.schedules.length > 0) {
        contentHtml += `
          <div class="table-responsive rounded-3 border bg-light mb-2">
            <table class="table table-sm table-borderless mb-0 small">
              <thead class="border-bottom text-muted" style="font-size: 0.7rem;">
                <tr><th>Waktu</th><th>Kelas</th><th>Mapel</th></tr>
              </thead>
              <tbody>
        `;
        w.schedules.forEach(s => {
          contentHtml += `<tr>
            <td class="text-nowrap font-monospace">${escapeHtml(s.start_time?.substring(0,5))} - ${escapeHtml(s.end_time?.substring(0,5))}</td>
            <td><strong>${escapeHtml(s.class_name)}</strong></td>
            <td>${escapeHtml(s.subject_name)}</td>
          </tr>`;
        });
        contentHtml += `</tbody></table></div>`;
      }
    }
    // B. Widget Rekap Kehadiran
    else if (res.widget_type === 'stat_card' && res.widget_data) {
      const w = res.widget_data;
      contentHtml += `
        <p class="mb-2 text-dark small">${escapeHtml(res.explanation)}</p>
        <div class="row g-2 mb-2">
          <div class="col-6">
            <div class="p-2 rounded bg-success-subtle text-success border text-center">
              <div class="small fw-semibold" style="font-size: 0.7rem;">Hadir</div>
              <div class="fs-6 fw-bold">${w.hadir} Guru</div>
            </div>
          </div>
          <div class="col-6">
            <div class="p-2 rounded bg-danger-subtle text-danger border text-center">
              <div class="small fw-semibold" style="font-size: 0.7rem;">Tidak Hadir</div>
              <div class="fs-6 fw-bold">${w.tidak_hadir} Guru</div>
            </div>
          </div>
        </div>
      `;
    }
    // C. Widget CBT Monitoring
    else if (res.widget_type === 'cbt_card' && res.widget_data) {
      const w = res.widget_data;
      contentHtml += `
        <p class="mb-2 text-dark small">${escapeHtml(res.explanation)}</p>
        <div class="p-2 bg-primary-subtle rounded-3 border mb-2 d-flex align-items-center justify-content-between">
          <span class="small fw-medium">Siswa Aktif Ujian</span>
          <span class="badge bg-primary fs-6">${w.active_students}</span>
        </div>
      `;
    }
    // D. Widget Pencarian Siswa
    else if (res.widget_type === 'student_search_card' && res.widget_data) {
      const w = res.widget_data;
      contentHtml += `<p class="mb-2 text-dark small">${escapeHtml(res.explanation)}</p>`;
      if (w.students && w.students.length > 0) {
        contentHtml += `<div class="list-group list-group-flush border rounded-3 bg-white mb-2">`;
        w.students.forEach(st => {
          contentHtml += `
            <div class="list-group-item p-2 d-flex align-items-center justify-content-between small">
              <div>
                <strong class="text-dark">${escapeHtml(st.nama_lengkap)}</strong>
                <div class="text-muted" style="font-size: 0.72rem;">Kelas: ${escapeHtml(st.class_name || '-')} | NISN: ${escapeHtml(st.nisn || '-')}</div>
              </div>
              <a href="<?= site_url('admin/students/edit/') ?>${st.id}" class="btn btn-xs btn-outline-primary rounded-pill py-0 px-2" style="font-size: 0.7rem;">Profil</a>
            </div>
          `;
        });
        contentHtml += `</div>`;
      }
    }
    // D. Standar Rekomendasi Aksi & Konten AI
    else {
      contentHtml += `<p class="mb-2 text-dark small">${escapeHtml(res.explanation || 'Berikut hasil yang ditemukan:')}</p>`;
      if (res.ai_content) {
        contentHtml += `
          <div class="p-2 bg-light rounded-3 border mb-2 small text-dark" style="font-size: 0.8rem; line-height: 1.4;">
            ${escapeHtml(res.ai_content).replace(/\n/g, '<br>')}
          </div>
        `;
      }
    }

    // Tombol Tindakan Langsung (Direct Action Button)
    if (res.matched_action) {
      const act = res.matched_action;
      contentHtml += `
        <div class="mt-2">
          <a href="${act.url}" class="btn btn-sm btn-primary rounded-pill px-3 shadow-none d-inline-flex align-items-center gap-2" style="font-size: 0.78rem;">
            <i class="${act.icon || 'bi bi-arrow-right-circle'}"></i>
            <span>Buka ${escapeHtml(act.title)}</span>
            <i class="bi bi-arrow-right"></i>
          </a>
        </div>
      `;
    }

    bubble.innerHTML = `
      <div class="copilot-avatar-bot bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
        <i class="bi bi-stars" style="font-size: 0.9rem;"></i>
      </div>
      <div class="copilot-bubble-bot p-3 rounded-4 bg-white shadow-sm border" style="max-width: 85%;">
        ${contentHtml}
      </div>
    `;

    chatThread.appendChild(bubble);
    chatThread.scrollTop = chatThread.scrollHeight;
  }

  function renderBotMessage(text) {
    const bubble = document.createElement('div');
    bubble.className = 'd-flex align-items-start gap-2 mb-3';
    bubble.innerHTML = `
      <div class="copilot-avatar-bot bg-primary text-white rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" style="width: 32px; height: 32px;">
        <i class="bi bi-stars" style="font-size: 0.9rem;"></i>
      </div>
      <div class="copilot-bubble-bot p-3 rounded-4 bg-white shadow-sm border text-dark small" style="max-width: 85%;">
        ${escapeHtml(text)}
      </div>
    `;
    chatThread.appendChild(bubble);
    chatThread.scrollTop = chatThread.scrollHeight;
  }

  // Voice Command (Web Speech API)
  if (btnVoice) {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (SpeechRecognition) {
      const recognition = new SpeechRecognition();
      recognition.lang = 'id-ID';
      recognition.continuous = false;
      recognition.interimResults = false;

      let isRecording = false;

      btnVoice.addEventListener('click', function() {
        if (isRecording) {
          recognition.stop();
          return;
        }
        try {
          recognition.start();
          isRecording = true;
          btnVoice.classList.add('btn-danger', 'text-white');
          btnVoice.classList.remove('btn-outline-secondary');
          inputEl.placeholder = 'Mendengarkan suara Anda... Silakan bicara...';
        } catch(e) {
          console.warn('Speech recognition error:', e);
        }
      });

      recognition.onresult = function(event) {
        const transcript = event.results[0][0].transcript;
        inputEl.value = transcript;
        triggerSearch(transcript);
        
        // Switch to chat if it sounds like a conversation
        const tabChatBtn = document.getElementById('tab-chat-btn');
        if (tabChatBtn) {
          const tab = new bootstrap.Tab(tabChatBtn);
          tab.show();
          submitChatMessage(transcript);
        }
      };

      recognition.onend = function() {
        isRecording = false;
        btnVoice.classList.remove('btn-danger', 'text-white');
        btnVoice.classList.add('btn-outline-secondary');
        inputEl.placeholder = "Ketik perintah atau cari (contoh: 'modul pendidikan pancasila', 'rekap absen')...";
      };

      recognition.onerror = function() {
        isRecording = false;
        btnVoice.classList.remove('btn-danger', 'text-white');
        btnVoice.classList.add('btn-outline-secondary');
      };
    } else {
      btnVoice.style.display = 'none';
    }
  }

})();
</script>
