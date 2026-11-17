<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Services\Ai\Memory\ConversationMemoryManager;

/**
 * Unit Test untuk ConversationMemoryManager (Fase 1D.2)
 */
class ConversationMemoryManagerTest extends CIUnitTestCase
{
    protected $db;
    protected ConversationMemoryManager $memoryManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('default');
        $this->memoryManager = new ConversationMemoryManager($this->db);
    }

    public function testGenerateAndValidateUuidV4()
    {
        $uuid = $this->memoryManager->generateUuidV4();
        $this->assertNotEmpty($uuid);
        $this->assertTrue($this->memoryManager->isValidUuid($uuid));
        $this->assertFalse($this->memoryManager->isValidUuid('invalid-uuid-12345'));
        $this->assertFalse($this->memoryManager->isValidUuid(null));
    }

    public function testGetOrCreateConversationCreatesNew()
    {
        $userId = 9991;
        $roleId = 3;
        $conv = $this->memoryManager->getOrCreateConversation($userId, $roleId, null, 'Test Percakapan');

        $this->assertNotEmpty($conv['id']);
        $this->assertNotEmpty($conv['conversation_uuid']);
        $this->assertEquals($userId, (int) $conv['user_id']);
        $this->assertEquals($roleId, (int) $conv['role_id']);
        $this->assertEquals('Test Percakapan', $conv['title']);

        // Ambil kembali dengan UUID yang sama
        $fetched = $this->memoryManager->getOrCreateConversation($userId, $roleId, $conv['conversation_uuid']);
        $this->assertEquals($conv['id'], $fetched['id']);
        $this->assertEquals($conv['conversation_uuid'], $fetched['conversation_uuid']);
    }

    public function testCrossUserIsolationPreventsSessionHijacking()
    {
        $userA = 9992;
        $userB = 9993;
        $roleId = 3;

        // User A membuat sesi
        $convA = $this->memoryManager->getOrCreateConversation($userA, $roleId, null, 'Sesi User A');

        // User B mencoba mengakses menggunakan UUID milik User A
        $convB = $this->memoryManager->getOrCreateConversation($userB, $roleId, $convA['conversation_uuid']);

        // Sistem WAJIB mengisolasi dan membuatkan sesi baru untuk User B
        $this->assertNotEquals($convA['id'], $convB['id']);
        $this->assertNotEquals($convA['conversation_uuid'], $convB['conversation_uuid']);
        $this->assertEquals($userB, (int) $convB['user_id']);

        // Verifikasi getConversationByUuid juga menolak cross-user
        $checkA = $this->memoryManager->getConversationByUuid($convA['conversation_uuid'], $userA);
        $this->assertNotNull($checkA);

        $checkCross = $this->memoryManager->getConversationByUuid($convA['conversation_uuid'], $userB);
        $this->assertNull($checkCross);
    }

    public function testRecordUserAndAssistantMessagesWithDeduplication()
    {
        $userId = 9994;
        $roleId = 3;
        $conv = $this->memoryManager->getOrCreateConversation($userId, $roleId, null, 'Deduplikasi Test');
        $convId = (int) $conv['id'];

        $clientMsgId = $this->memoryManager->generateUuidV4();

        // 1. Simpan pesan user
        $msgId1 = $this->memoryManager->recordUserMessage($convId, 'Buatkan 5 soal pecahan', $clientMsgId, 'TASK_ASSISTANCE');
        $this->assertGreaterThan(0, $msgId1);

        // 2. Coba simpan ulang dengan client_message_id yang sama (Replay cURL)
        $msgId1Dup = $this->memoryManager->recordUserMessage($convId, 'Buatkan 5 soal pecahan', $clientMsgId, 'TASK_ASSISTANCE');
        $this->assertEquals($msgId1, $msgId1Dup, 'Pesan user dengan client_message_id yang sama tidak boleh diduplikasi');

        // 3. Simpan balasan asisten
        $replyId = $this->memoryManager->recordAssistantReply($convId, 'Berikut 5 soal pecahan:', '1. 1/2 + 1/4 = ...', 'TASK_ASSISTANCE', null, $clientMsgId);
        $this->assertGreaterThan(0, $replyId);

        // 4. Coba simpan ulang balasan asisten
        $replyIdDup = $this->memoryManager->recordAssistantReply($convId, 'Berikut 5 soal pecahan:', '1. 1/2 + 1/4 = ...', 'TASK_ASSISTANCE', null, $clientMsgId);
        $this->assertEquals($replyId, $replyIdDup, 'Balasan asisten dengan client_message_id yang sama tidak boleh diduplikasi');
    }

    public function testContextAssemblyAndTokenBudgetTrimming()
    {
        $userId = 9995;
        $roleId = 3;
        $conv = $this->memoryManager->getOrCreateConversation($userId, $roleId, null, 'Assembly Test');
        $convId = (int) $conv['id'];

        // Tambah 10 pesan bergantian (user & assistant)
        for ($i = 1; $i <= 5; $i++) {
            $cId = $this->memoryManager->generateUuidV4();
            $this->memoryManager->recordUserMessage($convId, "Pertanyaan nomor {$i}: Penjelasan panjang tentang topik {$i}...", $cId, 'TASK_ASSISTANCE');
            $this->memoryManager->recordAssistantReply($convId, "Jawaban nomor {$i}: Penjelasan detail kurikulum dan pedagogik {$i}...", null, 'TASK_ASSISTANCE', null, $cId);
        }

        // Ambil riwayat dengan maxTurns = 6
        $history = $this->memoryManager->getAssembledHistory($convId, 6, 800);
        $this->assertLessThanOrEqual(6, count($history));

        // Verifikasi urutan kronologis (pesan terakhir harus nomor 5)
        $lastTurn = end($history);
        $this->assertEquals('assistant', $lastTurn['role']);
        $this->assertStringContainsString('nomor 5', $lastTurn['content']);

        // Verifikasi pemangkasan anggaran token (simulasikan maxTokens sangat kecil)
        $trimmedHistory = $this->memoryManager->getAssembledHistory($convId, 6, 30);
        $this->assertLessThanOrEqual(count($history), count($trimmedHistory));
    }
}
