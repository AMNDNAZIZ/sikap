<?php

namespace App\Services\Ai\Intent;

/**
 * IntentClassifier
 * 
 * Pengklasifikasi Intensi Percakapan Asis AI (Fase 1D.1 - Conversational Core)
 * Mengklasifikasikan kueri pengguna ke dalam 8 kategori intensi percakapan
 * menggunakan pendekatan berbasis aturan heuristik deterministik cepat (0 ms latency).
 */
class IntentClassifier
{
    public const INTENT_GENERAL_CONVERSATION = 'GENERAL_CONVERSATION';
    public const INTENT_APPLICATION_QUESTION = 'APPLICATION_QUESTION';
    public const INTENT_KNOWLEDGE_RETRIEVAL  = 'KNOWLEDGE_RETRIEVAL';
    public const INTENT_TASK_ASSISTANCE      = 'TASK_ASSISTANCE';
    public const INTENT_NAVIGATION           = 'NAVIGATION';
    public const INTENT_ACTION_REQUEST       = 'ACTION_REQUEST';
    public const INTENT_CLARIFICATION        = 'CLARIFICATION';
    public const INTENT_UNSUPPORTED_REQUEST  = 'UNSUPPORTED_REQUEST';

    /**
     * Daftar seluruh intensi yang valid
     */
    public static function getValidIntents(): array
    {
        return [
            self::INTENT_GENERAL_CONVERSATION,
            self::INTENT_APPLICATION_QUESTION,
            self::INTENT_KNOWLEDGE_RETRIEVAL,
            self::INTENT_TASK_ASSISTANCE,
            self::INTENT_NAVIGATION,
            self::INTENT_ACTION_REQUEST,
            self::INTENT_CLARIFICATION,
            self::INTENT_UNSUPPORTED_REQUEST,
        ];
    }

    /**
     * Klasifikasikan kueri pengguna
     *
     * @param string $query Kueri pengguna
     * @return array [
     *     'intent'     => string,
     *     'confidence' => float,
     *     'reason'     => string,
     * ]
     */
    public static function classify(string $query): array
    {
        $q = trim(mb_strtolower($query));
        if ($q === '') {
            return [
                'intent'     => self::INTENT_GENERAL_CONVERSATION,
                'confidence' => 1.0,
                'reason'     => 'Empty query default',
            ];
        }

        // 1. UNSUPPORTED_REQUEST (Topik non-edukasi & di luar ruang lingkup aplikasi / sekolah)
        if (self::isUnsupportedRequest($q)) {
            return [
                'intent'     => self::INTENT_UNSUPPORTED_REQUEST,
                'confidence' => 0.95,
                'reason'     => 'Detected out-of-scope non-educational topic',
            ];
        }

        // 2. CLARIFICATION (Permintaan klarifikasi / perbaikan lanjutan yang ambigu tanpa konteks)
        if (self::isClarification($q)) {
            return [
                'intent'     => self::INTENT_CLARIFICATION,
                'confidence' => 0.90,
                'reason'     => 'Ambiguous refinement / follow-up expression without context',
            ];
        }

        // 3. TASK_ASSISTANCE (Bantuan pembuatan draft soal, surat, materi, silabus, dll.)
        // Diperiksa sebelum GENERAL_CONVERSATION agar sapaan di awal (misal: "Halo, tolong buatkan...") tetap masuk TASK_ASSISTANCE
        if (self::isTaskAssistance($q)) {
            return [
                'intent'     => self::INTENT_TASK_ASSISTANCE,
                'confidence' => 0.92,
                'reason'     => 'Request for content creation, drafting, or pedagogical generation',
            ];
        }

        // 4. GENERAL_CONVERSATION (Sapaan, basa-basi, terima kasih, perkenalan diri AI)
        if (self::isGeneralConversation($q)) {
            return [
                'intent'     => self::INTENT_GENERAL_CONVERSATION,
                'confidence' => 0.95,
                'reason'     => 'Greeting, courtesy, or conversational persona query',
            ];
        }

        // 5. NAVIGATION (Permintaan membuka atau mengarahkan ke menu tertentu)
        if (self::isNavigation($q)) {
            return [
                'intent'     => self::INTENT_NAVIGATION,
                'confidence' => 0.90,
                'reason'     => 'Explicit navigation / menu-opening request',
            ];
        }

        // 6. ACTION_REQUEST (Permintaan eksekusi aksi sistem: ekspor, cetak, download, reset)
        if (self::isActionRequest($q)) {
            return [
                'intent'     => self::INTENT_ACTION_REQUEST,
                'confidence' => 0.88,
                'reason'     => 'Direct system action request',
            ];
        }

        // 7. APPLICATION_QUESTION (Pertanyaan seputar sistem, fitur, atau cara penggunaan ESPATA)
        if (self::isApplicationQuestion($q)) {
            return [
                'intent'     => self::INTENT_APPLICATION_QUESTION,
                'confidence' => 0.88,
                'reason'     => 'Question about ESPATA features, workflow, or system capabilities',
            ];
        }

        // 8. KNOWLEDGE_RETRIEVAL (Pertanyaan konseptual pendidikan, pedagogik, kurikulum)
        if (self::isKnowledgeRetrieval($q)) {
            return [
                'intent'     => self::INTENT_KNOWLEDGE_RETRIEVAL,
                'confidence' => 0.88,
                'reason'     => 'Pedagogical, curricular, or conceptual knowledge query',
            ];
        }

        // Default heuristic: Jika kalimat tanya umum (apa, bagaimana, jelaskan)
        if (preg_match('/^(apa|bagaimana|mengapa|kenapa|siapa|kapan|dimana|jelaskan|sebutkan)\b/i', $q)) {
            return [
                'intent'     => self::INTENT_KNOWLEDGE_RETRIEVAL,
                'confidence' => 0.70,
                'reason'     => 'General explanatory question pattern',
            ];
        }

        return [
            'intent'     => self::INTENT_GENERAL_CONVERSATION,
            'confidence' => 0.50,
            'reason'     => 'General open conversational query',
        ];
    }

    /**
     * Deteksi permintaan di luar ruang lingkup aplikasi / pendidikan
     */
    protected static function isUnsupportedRequest(string $q): bool
    {
        $patterns = [
            '/\b(pajak\s*(penghasilan|pribadi|pph|tahunan|motor|mobil|bumi|bangunan)|hitung\s*pajak)\b/i',
            '/\b(resep\s*(masakan|makanan|kue|rendang|nasi\s*goreng|ayam|sayur)|cara\s*memasak|bumbu\s*dapur)\b/i',
            '/\b(ramalan\s*(cuaca|zodiak|bintang|jodoh)|horoskop|shio)\b/i',
            '/\b(servis\s*(motor|mobil)|bengkel|ganti\s*oli|harga\s*emas|kurs\s*dollar|crypto|bitcoin)\b/i',
            '/\b(liga\s*inggris|hasil\s*skor|klasemen\s*bola|pertandingan\s*sepak\s*bola)\b/i',
            '/\b(obat\s*(sakit\s*kepala|flu|demam|batuk)|diagnosa\s*penyakit|gejala\s*penyakit)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi permintaan klarifikasi / lanjutan ambigu tanpa konteks
     */
    protected static function isClarification(string $q): bool
    {
        $phrases = [
            'buat lebih sederhana',
            'sederhanakan',
            'lebih sederhana',
            'lebih singkat',
            'lebih panjang',
            'lebih detil',
            'lebih spesifik',
            'jelaskan lagi',
            'maksudnya apa',
            'maksudnya gimana',
            'maksudmu apa',
            'kurang jelas',
            'bisa lebih jelas',
            'ulangi lagi',
            'coba lagi',
            'lanjutkan',
            'terus gimana',
            'lalu apa',
            'kok bisa',
            'kenapa begitu',
            'buat lebih ringkas',
        ];

        // Jika persis sama atau sangat pendek (<= 3 kata) mengandung frasa tersebut
        foreach ($phrases as $phrase) {
            if ($q === $phrase || str_starts_with($q, $phrase)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi permintaan bantuan tugas (pembuatan soal, draf surat, rubrik, dsb.)
     */
    protected static function isTaskAssistance(string $q): bool
    {
        // Pengecualian: Pertanyaan "bagaimana cara ..." atau panduan penggunaan adalah APPLICATION_QUESTION
        if (preg_match('/\b(bagaimana\s*cara|gimana\s*cara|panduan|tutorial)\b/i', $q)) {
            return false;
        }

        $patterns = [
            '/\b(bantu\s*(buat|buatkan|bikin|susun|rancang|tulis)|buatkan|susunkan|tuliskan|rancang|buat\s*draf|bikin\s*draf)\b/i',
            '/\b(ingin|mau|tolong|mohon|perlu)\s+(membuat|buat|menyusun|susun|merancang|rancang|menulis|tulis)\b/i',
            '/\b(buat|membuat|susun|menyusun|rancang|merancang)\s+(soal|surat|rpp|modul|rubrik|silabus|pengumuman|kisi-kisi|materi|contoh|teks|naskah|asesmen|penilaian|evaluasi|instrumen|lkpd|lembar\s*kerja)\b/i',
            '/\b(butir\s*soal|soal\s*hots|soal\s*pilihan\s*ganda|soal\s*esai|soal\s*ujian)\b/i',
            '/\b(surat\s*undangan|surat\s*pemberitahuan|surat\s*peringatan|surat\s*izin)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi sapaan, basa-basi, etika sopan, atau pertanyaan identitas Asis AI
     */
    protected static function isGeneralConversation(string $q): bool
    {
        $patterns = [
            // Sapaan
            '/\b(halo|hai|helo|hi|assalamualaikum|assalamu\'alaikum|sampurasun|kulonuwun)\b/i',
            '/\b(selamat\s*(pagi|siang|sore|malam|datang|hari\s*guru))\b/i',
            '/\b(pagi\s*asis|siang\s*asis|sore\s*asis|malam\s*asis)\b/i',
            // Ucapan terima kasih / sopan santun
            '/\b(terima\s*kasih|makasih|thanks|thank\s*you|matur\s*nuwun|syukron|sama-sama|kembali)\b/i',
            // Persona / identitas asisten
            '/\b(siapa\s*(kamu|anda|asis)|kamu\s*siapa|nama\s*kamu\s*siapa|siapa\s*namamu)\b/i',
            '/\b(apa\s*kabar|gimana\s*kabarnya|kabarmu)\b/i',
            '/\b(kamu\s*(bisa\s*apa|robot|ai)|apa\s*yang\s*bisa\s*kamu\s*bantu|keahlianmu|apa\s*fungsi\s*kamu)\b/i',
            // Perpisahan
            '/\b(sampai\s*jumpa|dadah|bye|selamat\s*tinggal)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi permintaan navigasi membuka menu atau halaman
     */
    protected static function isNavigation(string $q): bool
    {
        $patterns = [
            '/\b(buka\s*(menu|halaman|layar|fitur|modul|link))\b/i',
            '/\b(ke\s*menu|menuju\s*(ke|menu)|arahkan\s*ke|masuk\s*ke|tampilkan\s*menu|tunjukkan\s*menu|navigasi\s*ke|pergi\s*ke)\b/i',
            '/^(menu|halaman)\s+[a-z0-9_-]+$/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi aksi sistem spesifik (ekspor, cetak, unduh)
     */
    protected static function isActionRequest(string $q): bool
    {
        $patterns = [
            '/\b(cetak|print|download|unduh|ekspor|export|impor|import|reset\s*password|generate\s*rapor|backup)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi pertanyaan mengenai fitur, cara pakai, atau modul ESPATA
     */
    protected static function isApplicationQuestion(string $q): bool
    {
        $patterns = [
            '/\b(fitur\s*(yang\s*ada\s*di\s*)?espata|apa\s*saja\s*fitur|fitur\s*apa\s*saja)\b/i',
            '/\b(tentang\s*espata|apa\s*itu\s*espata|aplikasi\s*espata|siakad\s*espata)\b/i',
            '/\b(bagaimana\s*cara\s*(input|absen|presensi|rekap|login|unduh|cetak|buat|membuat|buka|lihat))\b/i',
            '/\b(panduan\s*(aplikasi|fitur|sistem|espata|penggunaan))\b/i',
            '/\b(modul\s*apa\s*saja|cara\s*menggunakan\s*espata)\b/i',
            '/\b(bagaimana\s*cara|gimana\s*cara|cara\s*menggunakan|cara\s*pakai).*(di\s*espata|pada\s*espata|di\s*aplikasi|di\s*sistem)\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Deteksi pertanyaan konseptual pedagogik, kurikulum, dan pendidikan
     */
    protected static function isKnowledgeRetrieval(string $q): bool
    {
        $patterns = [
            '/\b(asesmen\s*(formatif|sumatif|diagnostik|autentik))\b/i',
            '/\b(perbedaan\s*asesmen|bedanya\s*asesmen|perbedaan\s*formatif|formatif\s*dan\s*sumatif)\b/i',
            '/\b(kurikulum\s*merdeka|capaian\s*pembelajaran|alur\s*tujuan\s*pembelajaran|atp|modul\s*ajar)\b/i',
            '/\b(profil\s*pelajar\s*pancasila|kokurikuler|pembelajaran\s*berdiferensiasi|taksonomi\s*bloom)\b/i',
            '/\b(apa\s*itu\s*(kkm|kktp|rubrik|kriteria\s*ketercapaian))\b/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $q)) {
                return true;
            }
        }

        return false;
    }
}
