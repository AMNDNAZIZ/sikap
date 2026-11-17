<?php

namespace App\Services\Ai\Memory;

use CodeIgniter\Database\ConnectionInterface;

/**
 * Pengelola Memori Sesi Percakapan Multi-turn Asis AI (Fase 1D.2)
 * 
 * Bertanggung jawab atas:
 * 1. Manajemen siklus hidup percakapan (ai_conversations) dan pesan (ai_conversation_messages).
 * 2. Isolasi kepemilikan sesi ketat (Server-Side Ownership Validation & Anti-Tampering).
 * 3. Perakitan konteks riwayat multi-turn (Context Assembly) dengan batas 4-6 putaran & ~800 token.
 * 4. Deduplikasi pesan berbasis client_message_id agar aman dari cURL network retry.
 */
class ConversationMemoryManager
{
    protected ?ConnectionInterface $db;

    public function __construct(?ConnectionInterface $db = null)
    {
        $this->db = $db ?: db_connect();
    }

    /**
     * Memperoleh sesi percakapan aktif milik pengguna atau membuat sesi baru jika tidak ditemukan
     * 
     * KETENTUAN KEAMANAN:
     * - Parameter $uuid divalidasi format regex UUIDv4.
     * - Server wajib memverifikasi user_id pemilik percakapan.
     * - Jika $uuid milik pengguna lain atau tidak ditemukan, sistem otomatis membuat sesi baru
     *   tanpa membocorkan eksistensi UUID pengguna lain (Anti-Leakage / Zero-Trust).
     */
    public function getOrCreateConversation(
        int $userId,
        int $roleId,
        ?string $uuid = null,
        ?string $initialTitle = null
    ): array {
        if ($this->isValidUuid($uuid)) {
            $existing = $this->db->table('ai_conversations')
                ->where('conversation_uuid', $uuid)
                ->where('user_id', $userId)
                ->get()
                ->getRowArray();

            if ($existing) {
                return $existing;
            }
        }

        // Buat percakapan baru dengan UUID v4 kriptografis
        $newUuid = $this->generateUuidV4();
        $title = !empty($initialTitle) ? mb_substr(trim($initialTitle), 0, 80) : 'Percakapan Baru';
        $now = date('Y-m-d H:i:s');

        $this->db->table('ai_conversations')->insert([
            'conversation_uuid' => $newUuid,
            'user_id'           => $userId,
            'role_id'           => $roleId,
            'title'             => $title,
            'last_intent'       => null,
            'context_summary'   => null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ]);

        $insertId = $this->db->insertID();

        return [
            'id'                => $insertId,
            'conversation_uuid' => $newUuid,
            'user_id'           => $userId,
            'role_id'           => $roleId,
            'title'             => $title,
            'last_intent'       => null,
            'context_summary'   => null,
            'created_at'        => $now,
            'updated_at'        => $now,
        ];
    }

    /**
     * Mencatat pesan pengguna ke dalam riwayat percakapan
     */
    public function recordUserMessage(
        int $conversationId,
        string $query,
        ?string $clientMessageId = null,
        ?string $intentType = null
    ): int {
        // Anti-Duplikasi: Jika client_message_id sudah tercatat pada sesi ini, jangan duplikasi
        if (!empty($clientMessageId)) {
            $existing = $this->db->table('ai_conversation_messages')
                ->select('id')
                ->where('conversation_id', $conversationId)
                ->where('client_message_id', $clientMessageId)
                ->where('sender_role', 'user')
                ->get()
                ->getRowArray();

            if ($existing) {
                return (int) $existing['id'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $tokenCount = $this->estimateTokenCount($query);

        $this->db->table('ai_conversation_messages')->insert([
            'conversation_id'   => $conversationId,
            'sender_role'       => 'user',
            'client_message_id' => $clientMessageId,
            'message_text'      => $query,
            'ai_content'        => null,
            'intent_type'       => $intentType,
            'matched_action_id' => null,
            'token_count'       => $tokenCount,
            'created_at'        => $now,
        ]);

        $messageId = $this->db->insertID();

        // Perbarui timestamp & last_intent pada percakapan induk
        $updateData = [
            'updated_at'  => $now,
            'last_intent' => $intentType,
        ];

        // Jika judul masih default 'Percakapan Baru', perbarui dengan intisari kueri pertama
        $conv = $this->db->table('ai_conversations')
            ->select('title')
            ->where('id', $conversationId)
            ->get()
            ->getRowArray();

        if ($conv && ($conv['title'] === 'Percakapan Baru' || empty($conv['title']))) {
            $updateData['title'] = mb_substr(trim($query), 0, 80);
        }

        $this->db->table('ai_conversations')
            ->where('id', $conversationId)
            ->update($updateData);

        return $messageId;
    }

    /**
     * Mencatat balasan asisten AI ke dalam riwayat percakapan
     */
    public function recordAssistantReply(
        int $conversationId,
        string $explanation,
        ?string $aiContent = null,
        ?string $intentType = null,
        ?string $matchedActionId = null,
        ?string $clientMessageId = null
    ): int {
        // Anti-Duplikasi: Periksa apakah balasan asisten untuk client_message_id ini sudah ada
        if (!empty($clientMessageId)) {
            $existing = $this->db->table('ai_conversation_messages')
                ->select('id')
                ->where('conversation_id', $conversationId)
                ->where('client_message_id', $clientMessageId)
                ->where('sender_role', 'assistant')
                ->get()
                ->getRowArray();

            if ($existing) {
                return (int) $existing['id'];
            }
        }

        $now = date('Y-m-d H:i:s');
        $fullText = $explanation . (!empty($aiContent) ? "\n" . $aiContent : '');
        $tokenCount = $this->estimateTokenCount($fullText);

        $this->db->table('ai_conversation_messages')->insert([
            'conversation_id'   => $conversationId,
            'sender_role'       => 'assistant',
            'client_message_id' => $clientMessageId,
            'message_text'      => $explanation,
            'ai_content'        => $aiContent,
            'intent_type'       => $intentType,
            'matched_action_id' => $matchedActionId,
            'token_count'       => $tokenCount,
            'created_at'        => $now,
        ]);

        $messageId = $this->db->insertID();

        $this->db->table('ai_conversations')
            ->where('id', $conversationId)
            ->update([
                'updated_at'  => $now,
                'last_intent' => $intentType,
            ]);

        return $messageId;
    }

    /**
     * Merakit konteks riwayat percakapan untuk prompt LLM (Context Assembly)
     * 
     * KETENTUAN MANDAT EAO-HO-2026-016:
     * - Batasan putaran percakapan: 4 sampai 6 pesan terakhir.
     * - Batasan anggaran token: maksimal ~800 token.
     * - Prioritas: Pesan terbaru diprioritaskan; pesan tertua dipangkas jika melebihi budget.
     * - Format keluaran: Standar OpenAI messages array [['role' => 'user'|'assistant', 'content' => '...']].
     */
    public function getAssembledHistory(
        int $conversationId,
        int $maxTurns = 6,
        int $maxTokens = 800
    ): array {
        $rows = $this->db->table('ai_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('id', 'DESC')
            ->limit($maxTurns)
            ->get()
            ->getResultArray();

        if (empty($rows)) {
            return [];
        }

        // Urutkan kembali menjadi kronologis (terlama ke terbaru)
        $rows = array_reverse($rows);

        // Pengelolaan batas anggaran token (Trimming budget)
        $totalTokens = 0;
        foreach ($rows as $r) {
            $totalTokens += (int) $r['token_count'];
        }

        // Jika total melebihi maxTokens, pangkas putaran dari yang paling awal
        while ($totalTokens > $maxTokens && count($rows) > 1) {
            $shifted = array_shift($rows);
            $totalTokens -= (int) $shifted['token_count'];
        }

        $history = [];
        foreach ($rows as $msg) {
            $role = $msg['sender_role'] === 'assistant' ? 'assistant' : 'user';
            
            // Format isi konteks asisten: sertakan penjelasan dan draf konten jika ada
            $content = (string) $msg['message_text'];
            if ($role === 'assistant' && !empty($msg['ai_content'])) {
                $content .= "\n\n[Draf Konten Dihasilkan]:\n" . $msg['ai_content'];
            }

            $history[] = [
                'role'    => $role,
                'content' => $content,
            ];
        }

        return $history;
    }

    /**
     * Mengambil percakapan berdasarkan UUID dan validasi kepemilikan pengguna
     */
    public function getConversationByUuid(string $uuid, int $userId): ?array
    {
        if (!$this->isValidUuid($uuid)) {
            return null;
        }

        return $this->db->table('ai_conversations')
            ->where('conversation_uuid', $uuid)
            ->where('user_id', $userId)
            ->get()
            ->getRowArray();
    }

    /**
     * Mengambil daftar pesan percakapan untuk tampilan antarmuka (Client Thread Restoration)
     */
    public function getConversationMessages(int $conversationId, int $limit = 50): array
    {
        $messages = $this->db->table('ai_conversation_messages')
            ->where('conversation_id', $conversationId)
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get()
            ->getResultArray();

        $formatted = [];
        foreach ($messages as $m) {
            $formatted[] = [
                'id'                => (int) $m['id'],
                'sender_role'       => $m['sender_role'],
                'message_text'      => $m['message_text'],
                'ai_content'        => $m['ai_content'],
                'intent_type'       => $m['intent_type'],
                'matched_action_id' => $m['matched_action_id'],
                'created_at'        => $m['created_at'],
            ];
        }

        return $formatted;
    }

    /**
     * Estimasi jumlah token teks (Rasio empiris 3.8 karakter / token)
     */
    public function estimateTokenCount(string $text): int
    {
        $len = mb_strlen($text);
        if ($len <= 0) {
            return 0;
        }
        return max(1, (int) ceil($len / 3.8));
    }

    /**
     * Validasi format UUIDv4
     */
    public function isValidUuid(?string $uuid): bool
    {
        if (empty($uuid) || !is_string($uuid)) {
            return false;
        }
        return (bool) preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', trim($uuid));
    }

    /**
     * Pembangkitan UUIDv4 kriptografis standar RFC 4122
     */
    public function generateUuidV4(): string
    {
        $data = random_bytes(16);
        $data[6] = chr((ord($data[6]) & 0x0f) | 0x40);
        $data[8] = chr((ord($data[8]) & 0x3f) | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
