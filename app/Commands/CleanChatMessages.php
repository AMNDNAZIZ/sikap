<?php

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * CleanChatMessages — Hapus pesan chat yang lebih dari 7 hari
 *
 * Jalankan manual:
 *   php spark chat:clean
 *
 * Jadwalkan via cron job (setiap hari jam 02:00):
 *   0 2 * * * /usr/bin/php /path/to/siakad/spark chat:clean >> /path/to/siakad/writable/logs/chat_clean.log 2>&1
 *
 * Atau di Windows Task Scheduler:
 *   php C:\xampp\htdocs\siakad\spark chat:clean
 */
class CleanChatMessages extends BaseCommand
{
    protected $group       = 'App';
    protected $name        = 'chat:clean';
    protected $description = 'Hapus pesan chat (kelas & staff) yang lebih dari 7 hari untuk mencegah penumpukan data.';

    // Batas hari — bisa diubah sesuai kebutuhan
    const RETENTION_DAYS = 7;

    public function run(array $params)
    {
        $db        = \Config\Database::connect();
        $limitDate = date('Y-m-d H:i:s', strtotime('-' . self::RETENTION_DAYS . ' days'));
        $uploadPath = defined('UPLOAD_PATH') ? UPLOAD_PATH : FCPATH . 'uploads/';

        CLI::write('[' . date('Y-m-d H:i:s') . '] Mulai cleanup pesan chat (retensi: ' . self::RETENTION_DAYS . ' hari)...', 'cyan');
        CLI::write('Batas waktu: ' . $limitDate, 'light_gray');

        // ── 1. OBROLAN KELAS (tabel chat_messages) ──────────────────────
        CLI::write('');
        CLI::write('→ Obrolan Kelas (chat_messages)...', 'yellow');

        $oldClassMessages = $db->table('chat_messages')
            ->where('created_at <', $limitDate)
            ->get()->getResultArray();

        $classAttachDeleted = 0;
        foreach ($oldClassMessages as $msg) {
            if (!empty($msg['attachment'])) {
                $filePath = $uploadPath . 'chat/' . $msg['attachment'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                    $classAttachDeleted++;
                }
            }
        }

        $classCount = count($oldClassMessages);
        if ($classCount > 0) {
            $db->table('chat_messages')->where('created_at <', $limitDate)->delete();
            CLI::write("  Dihapus: {$classCount} pesan, {$classAttachDeleted} file attachment.", 'green');
        } else {
            CLI::write('  Tidak ada pesan lama.', 'light_gray');
        }

        // ── 2. OBROLAN STAFF (tabel staff_chat_messages) ────────────────
        CLI::write('');
        CLI::write('→ Obrolan Staff (staff_chat_messages)...', 'yellow');

        $oldStaffMessages = $db->table('staff_chat_messages')
            ->where('created_at <', $limitDate)
            ->get()->getResultArray();

        $staffAttachDeleted = 0;
        foreach ($oldStaffMessages as $msg) {
            if (!empty($msg['attachment'])) {
                $filePath = $uploadPath . 'chat/' . $msg['attachment'];
                if (file_exists($filePath)) {
                    @unlink($filePath);
                    $staffAttachDeleted++;
                }
            }
        }

        $staffCount = count($oldStaffMessages);
        if ($staffCount > 0) {
            $db->table('staff_chat_messages')->where('created_at <', $limitDate)->delete();
            CLI::write("  Dihapus: {$staffCount} pesan, {$staffAttachDeleted} file attachment.", 'green');
        } else {
            CLI::write('  Tidak ada pesan lama.', 'light_gray');
        }

        // ── 3. MENTION ORPHAN (mention yang message-nya sudah dihapus) ──
        CLI::write('');
        CLI::write('→ Membersihkan mention orphan...', 'yellow');

        // chat_mentions yang message_id-nya sudah tidak ada di chat_messages
        $orphanClass = $db->query("
            DELETE cm FROM chat_mentions cm
            LEFT JOIN chat_messages msg ON msg.id = cm.message_id
            WHERE msg.id IS NULL
        ");
        $classOrphan = $db->affectedRows();

        // staff_chat_mentions yang message_id-nya sudah tidak ada di staff_chat_messages
        $orphanStaff = $db->query("
            DELETE scm FROM staff_chat_mentions scm
            LEFT JOIN staff_chat_messages scmsg ON scmsg.id = scm.message_id
            WHERE scmsg.id IS NULL
        ");
        $staffOrphan = $db->affectedRows();

        CLI::write("  Mention orphan dihapus: {$classOrphan} (kelas) + {$staffOrphan} (staff).", 'green');

        // ── Ringkasan ────────────────────────────────────────────────────
        $total = $classCount + $staffCount;
        $totalAttach = $classAttachDeleted + $staffAttachDeleted;
        $totalOrphan = $classOrphan + $staffOrphan;

        CLI::write('');
        CLI::write('══════════════════════════════════════', 'cyan');
        CLI::write("✅ Selesai. Total dihapus:", 'green');
        CLI::write("   Pesan    : {$total} ({$classCount} kelas + {$staffCount} staff)", 'white');
        CLI::write("   Attachment: {$totalAttach} file", 'white');
        CLI::write("   Mention  : {$totalOrphan} orphan", 'white');
        CLI::write('══════════════════════════════════════', 'cyan');

        log_message('info', "chat:clean selesai. Pesan dihapus: {$total}, attachment: {$totalAttach}, mention orphan: {$totalOrphan}");
    }
}
