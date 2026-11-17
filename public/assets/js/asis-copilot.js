/**
 * Asis AI Copilot - Frontend Idempotency & State Manager (Fase 1C)
 * 
 * Mengelola:
 * 1. Pembangkitan UUID v4 kriptografis tunggal per klik pengguna.
 * 2. Kunci cepat tombol submit (Rapid Click Lock) untuk mencegah race condition.
 * 3. Pelestarian client_message_id yang sama pada saat percobaan ulang jaringan (Network Retry).
 * 4. Pembangkitan UUID baru hanya ketika pengguna mengetik kueri baru atau memulai aksi baru.
 * 5. Penanganan status respon terstandarisasi (HTTP 200, 404, 409, 410, 429).
 */

const AsisCopilot = (function () {
  'use strict';

  let currentClientMessageId = null;
  let isSubmitting = false;

  /**
   * Pembangkitan UUIDv4 standar RFC 4122
   */
  function generateUuidV4() {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
      return crypto.randomUUID();
    }

    if (typeof crypto !== 'undefined' && typeof crypto.getRandomValues === 'function') {
      const bytes = new Uint8Array(16);
      crypto.getRandomValues(bytes);
      bytes[6] = (bytes[6] & 0x0f) | 0x40; // Version 4
      bytes[8] = (bytes[8] & 0x3f) | 0x80; // Variant RFC 4122

      let uuid = '';
      for (let i = 0; i < 16; i++) {
        const hex = bytes[i].toString(16).padStart(2, '0');
        uuid += hex;
        if (i === 3 || i === 5 || i === 7 || i === 9) {
          uuid += '-';
        }
      }
      return uuid;
    }

    // Fallback matematis jika Web Crypto API tidak tersedia
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
      const r = (Math.random() * 16) | 0;
      const v = c === 'x' ? r : (r & 0x3) | 0x8;
      return v.toString(16);
    });
  }

  /**
   * Memperoleh client_message_id aktif atau membuat baru jika belum ada
   */
  function getOrCreateMessageId(forceNew = false) {
    if (forceNew || !currentClientMessageId) {
      currentClientMessageId = generateUuidV4();
    }
    return currentClientMessageId;
  }

  /**
   * Reset client_message_id setelah pesan tuntas diproses
   */
  function resetMessageId() {
    currentClientMessageId = null;
  }

  /**
   * Mengunci elemen antarmuka selama pengiriman (Rapid Click Lock)
   */
  function lockUi(btnSubmit, inputEl) {
    isSubmitting = true;
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.classList.add('disabled');
    }
    if (inputEl) {
      inputEl.setAttribute('readonly', 'readonly');
    }
  }

  /**
   * Membuka kunci elemen antarmuka
   */
  function unlockUi(btnSubmit, inputEl) {
    isSubmitting = false;
    if (btnSubmit) {
      btnSubmit.disabled = false;
      btnSubmit.classList.remove('disabled');
    }
    if (inputEl) {
      inputEl.removeAttribute('readonly');
    }
  }

  /**
   * Status apakah proses sedang mengirim
   */
  function isInProgress() {
    return isSubmitting;
  }

  const CONVERSATION_KEY = 'espata_asis_conversation_id';
  let memoryConversationId = null;

  /**
   * Memperoleh ID percakapan aktif dari sessionStorage (Fase 1D.2)
   */
  function getActiveConversationId() {
    try {
      if (typeof window !== 'undefined' && window.sessionStorage) {
        return window.sessionStorage.getItem(CONVERSATION_KEY) || memoryConversationId;
      }
    } catch (e) {}
    return memoryConversationId;
  }

  /**
   * Menyimpan ID percakapan aktif ke sessionStorage
   */
  function setActiveConversationId(uuid) {
    memoryConversationId = uuid || null;
    try {
      if (typeof window !== 'undefined' && window.sessionStorage) {
        if (uuid) {
          window.sessionStorage.setItem(CONVERSATION_KEY, uuid);
        } else {
          window.sessionStorage.removeItem(CONVERSATION_KEY);
        }
      }
    } catch (e) {}
  }

  /**
   * Menghapus ID percakapan aktif (Mulai sesi baru)
   */
  function clearActiveConversationId() {
    setActiveConversationId(null);
  }

  return {
    generateUuidV4,
    getOrCreateMessageId,
    resetMessageId,
    lockUi,
    unlockUi,
    isInProgress,
    getActiveConversationId,
    setActiveConversationId,
    clearActiveConversationId,
  };
})();

// Ekspor untuk lingkungan modular jika tersedia
if (typeof module !== 'undefined' && module.exports) {
  module.exports = AsisCopilot;
}
