<?php

namespace App\Services;

use App\Models\SubjectMaterialModel;
use App\Models\MaterialSegmentModel;
use App\Models\TugasModel;
use App\Models\AcademicYearModel;

/**
 * Service untuk mentransformasi Rencana Modul Ajar (Pertemuan Mikro)
 * menjadi Paket Pembelajaran Digital E-Learning (Bahan Micro-learning, LKPD, & Kuis Formatif)
 * menggunakan AI (Gemini/Groq) dengan Graceful Fallback otomatis.
 */
class ElearningPackageGeneratorService extends BaseService
{
    protected SubjectMaterialModel $materialModel;
    protected MaterialSegmentModel $segmentModel;
    protected TugasModel $tugasModel;
    protected AcademicYearModel $yearModel;

    public function __construct($db = null)
    {
        parent::__construct($db);
        if ($db !== null) {
            $this->db = $db;
        }
        $this->materialModel = new SubjectMaterialModel();
        $this->segmentModel  = new MaterialSegmentModel();
        $this->tugasModel    = new TugasModel();
        $this->yearModel     = new AcademicYearModel();
    }

    /**
     * Parse rincian pertemuan dari HTML Modul Ajar (mengambil tahapan Mindful, Meaningful, Joyful)
     */
    public function extractMeetingData(?string $modulContent, int $pertemuanKe): ?array
    {
        if (empty($modulContent)) {
            return null;
        }

        // Cari blok pertemuan dengan pola HTML h3 / table / bold
        $meetings = $this->parseMeetingsFromHtml($modulContent);
        foreach ($meetings as $m) {
            if ((int)$m['pertemuan_ke'] === $pertemuanKe) {
                return $m;
            }
        }

        // Fallback jika nomor pertemuan tidak cocok persis: kembalikan pertemuan pertama jika ada
        return $meetings[0] ?? null;
    }

    /**
     * Parser langkah-langkah pertemuan Modul Ajar
     */
    public function parseMeetingsFromHtml(?string $html): array
    {
        if (empty($html) || !is_string($html) || strlen(trim($html)) < 10) {
            return [];
        }

        $meetings = [];
        $cleanHtml = strip_tags($html, '<h3><h4><h2><p><table><tr><td><th><b><strong>');

        // Regex pendeteksi blok pertemuan
        if (preg_match_all('/(?:<h[2-4][^>]*>|<b>|<strong>)\s*(?:Pertemuan\s*(?:ke-?|\s*)\s*(\d+)[^<]*)(?:<\/h[2-4]>|<\/b>|<\/strong>)/iu', $cleanHtml, $matches, PREG_OFFSET_CAPTURE)) {
            $count = count($matches[0]);
            for ($i = 0; $i < $count; $i++) {
                $pNum = (int)$matches[1][$i][0];
                $startPos = $matches[0][$i][1];
                $endPos = ($i + 1 < $count) ? $matches[0][$i + 1][1] : strlen($cleanHtml);
                $meetingChunk = substr($cleanHtml, $startPos, $endPos - $startPos);

                $plainChunk = trim(strip_tags($meetingChunk));
                $headerTitle = trim(strip_tags($matches[0][$i][0]));
                $judul = !empty($headerTitle) ? $headerTitle : "Pertemuan ke-{$pNum}";

                $durasi = '90 menit';
                if (preg_match('/(\d+)\s*(?:JP|menit)/i', $plainChunk, $dm)) {
                    $durasi = $dm[0];
                }

                $tahapList = [];
                $tahapPatterns = [
                    'Mindful'    => '/(?:Mindful|Apersepsi|Pembukaan|Awal)[\s\S]*?(?=(?:Meaningful|Joyful|Kegiatan Inti|Penutup|$))/iu',
                    'Meaningful' => '/(?:Meaningful|Kegiatan Inti|Eksplorasi|Inti)[\s\S]*?(?=(?:Joyful|Penutup|Refleksi|$))/iu',
                    'Joyful'     => '/(?:Joyful|Penutup|Refleksi|Kesimpulan)[\s\S]*?$/iu',
                ];

                foreach ($tahapPatterns as $tahapName => $pattern) {
                    if (preg_match($pattern, $plainChunk, $tm)) {
                        $desc = trim(preg_replace('/\s+/', ' ', $tm[0]));
                        if (strlen($desc) > 20) {
                            $tahapList[] = [
                                'tahap'     => $tahapName,
                                'deskripsi' => substr($desc, 0, 400),
                            ];
                        }
                    }
                }

                $meetings[] = [
                    'pertemuan_ke' => $pNum,
                    'judul'        => $judul,
                    'durasi'       => $durasi,
                    'tahap_list'   => $tahapList,
                    'raw_text'     => substr($plainChunk, 0, 800),
                ];
            }
        }

        return $meetings;
    }

    /**
     * Generate draf paket e-learning (Materi Bacaan, Diskusi Pemantik, LKPD Digital, Kuis Formatif)
     */
    public function generatePackageDraft(int $modulId, int $pertemuanKe, ?int $classId = null, ?array $user = null): array
    {
        $modul = $this->db->table('modul_ajar ma')
            ->select('ma.*, s.name as subject_name, c.name as class_name, c.level as class_level')
            ->join('subjects s', 's.id = ma.subject_id', 'left')
            ->join('classes c', 'c.id = ma.class_id', 'left')
            ->where('ma.id', $modulId)
            ->get()->getRowArray();

        if (!$modul) {
            return [
                'success' => false,
                'message' => 'Modul Ajar tidak ditemukan.',
            ];
        }

        $atpInfo = null;
        if (!empty($modul['atp_id'])) {
            $atpInfo = $this->db->table('alur_tujuan_pembelajaran')
                ->where('id', $modul['atp_id'])
                ->get()->getRowArray();
        }

        $materiJudul = $atpInfo['lingkup_materi'] ?? 'Materi Pembelajaran';
        $semester    = (int)($atpInfo['semester'] ?? 1);
        $subjectName = $modul['subject_name'] ?? 'Mata Pelajaran';
        $classLevel  = (int)($modul['class_level'] ?? 6);

        // Ekstrak rincian pertemuan & estimasi total menit kegiatan
        $meeting = $this->extractMeetingData($modul['content'] ?? '', $pertemuanKe);
        $meetingJudul = $meeting ? $meeting['judul'] : "Pertemuan ke-{$pertemuanKe}";
        $meetingText  = $meeting ? ($meeting['raw_text'] ?? '') : '';

        // Hitung total menit teralokasi pada modul ajar (standar 2 JP SD = 70 menit)
        $totalMinutes = 70;
        if ($meeting && !empty($meeting['durasi'])) {
            if (preg_match('/(\d+)\s*(?:menit|m)/i', $meeting['durasi'], $dm)) {
                $totalMinutes = (int)$dm[1];
            } elseif (preg_match('/(\d+)\s*JP/i', $meeting['durasi'], $dj)) {
                $totalMinutes = (int)$dj[1] * 35;
            }
        }
        if ($totalMinutes < 30) $totalMinutes = 70;

        // Ambil konfigurasi API AI
        $aiConfig = $this->resolveAiKey($user);

        $aiGenerated = null;
        if (!empty($aiConfig['api_key'])) {
            $aiGenerated = $this->callAiGenerator($aiConfig, $subjectName, $classLevel, $materiJudul, $meetingJudul, $meetingText, $totalMinutes);
        }

        // Jika AI berhasil dan memiliki susunan segmen, gunakan hasil AI. Jika tidak, gunakan Desainer Pedagogis Cerdas.
        if (!empty($aiGenerated) && is_array($aiGenerated) && !empty($aiGenerated['segments'])) {
            $package = $aiGenerated;
            $package['source'] = 'ai';
            $package['ai_provider'] = $aiConfig['provider'] ?? 'gemini';
        } else {
            $package = $this->buildFallbackPackage($subjectName, $classLevel, $materiJudul, $pertemuanKe, $meeting, $totalMinutes);
            $package['source'] = 'smart_fallback';
        }

        // Normalisasi struktur package agar seragam dan aman dikonsumsi client/UI
        $microTitle   = $package['micro_learning']['title'] ?? "Konsep Utama: {$materiJudul}";
        $summaryHtml  = $package['micro_learning']['summary_html'] ?? ($package['summary_html'] ?? ($package['summary'] ?? ''));
        $discPrompt   = $package['discussion']['prompt'] ?? ($package['discussion_prompt'] ?? "Menurut pendapatmu, apa manfaat utama mempelajari topik {$materiJudul} dalam kehidupan sehari-hari?");
        $lkpdTitle    = $package['lkpd']['title'] ?? ($package['lkpd_task']['title'] ?? "LKPD: {$meetingJudul}");
        $lkpdDesc     = $package['lkpd']['instructions'] ?? ($package['lkpd_task']['instructions_html'] ?? '');
        $quizList     = $package['quiz'] ?? ($package['mini_quiz'] ?? []);
        $segments     = $package['segments'] ?? [];

        $normalizedPackage = array_merge($package, [
            'sub_judul'         => $package['sub_judul'] ?? "{$meetingJudul}: {$materiJudul}",
            'summary_html'      => $summaryHtml,
            'summary'           => $package['summary'] ?? strip_tags($summaryHtml),
            'discussion_prompt' => $discPrompt,
            'discussion'        => ['prompt' => $discPrompt],
            'lkpd_title'        => $lkpdTitle,
            'lkpd_desc'         => $lkpdDesc,
            'lkpd'              => ['title' => $lkpdTitle, 'instructions' => $lkpdDesc],
            'quiz'              => $quizList,
            'segments'          => $segments,
            'total_minutes'     => $totalMinutes,
        ]);

        return [
            'success'      => true,
            'modul_id'     => $modulId,
            'pertemuan_ke' => $pertemuanKe,
            'subject_id'   => (int)$modul['subject_id'],
            'class_id'     => $classId ?: (int)$modul['class_id'],
            'level'        => $classLevel,
            'semester'     => $semester,
            'materi_judul' => $materiJudul,
            'sub_judul'    => $package['sub_judul'] ?? "{$meetingJudul}: {$materiJudul}",
            'meeting'      => $meeting ?: [
                'pertemuan_ke' => $pertemuanKe,
                'judul'        => $meetingJudul,
                'durasi'       => "{$totalMinutes} menit",
            ],
            'package'      => $normalizedPackage,
        ];
    }

    /**
     * Resolusi API Key AI dari identitas pengguna aktif (guru atau admin) atau environment
     */
    protected function resolveAiKey(?array $user): array
    {
        if (empty($user)) {
            $user = session()->get('user') ?? [];
        }

        $apiKey = '';
        $provider = 'gemini';

        $roleId = (int)($user['role_id'] ?? 0);
        if ($roleId === 3 && !empty($user['related_id'])) {
            $teacher = $this->db->table('teachers')->where('id', $user['related_id'])->get()->getRowArray();
            $apiKey = $teacher['gemini_api_key'] ?? '';
            $provider = $teacher['ai_provider'] ?? 'gemini';
        } elseif ($roleId === 1 && !empty($user['id'])) {
            $userData = $this->db->table('users')->where('id', $user['id'])->get()->getRowArray();
            $apiKey = $userData['gemini_api_key'] ?? '';
            $provider = $userData['ai_provider'] ?? 'gemini';
        }

        if (empty($apiKey)) {
            $apiKey = getenv('GEMINI_API_KEY') ?: ($_ENV['GEMINI_API_KEY'] ?? '');
        }
        if (empty($apiKey)) {
            $groqKey = getenv('GROQ_API_KEY') ?: ($_ENV['GROQ_API_KEY'] ?? '');
            if (!empty($groqKey)) {
                $apiKey = $groqKey;
                $provider = 'groq';
            }
        }

        return [
            'api_key'  => trim((string)$apiKey),
            'provider' => $provider ?: 'gemini',
        ];
    }

    /**
     * Panggil API Gemini / Groq untuk transformasi cerdas menjadi alur multi-segmen aksi nyata
     */
    protected function callAiGenerator(array $aiConfig, string $subject, int $level, string $materi, string $pertemuan, string $scenario, int $totalMinutes = 70): ?array
    {
        $prompt = "Anda adalah Ahli Desain Instruksional Pembelajaran Digital (LMS) dan Spesialis Kurikulum Merdeka.
Tugas Anda: Mengubah skenario tatap muka dari Modul Ajar menjadi 'Alur Pembelajaran Digital Aktif (E-Learning Multi-Segmen)' yang menguraikan aksi nyata siswa dengan beragam media interaktif untuk siswa kelas {$level} SD pada mata pelajaran {$subject}.

Informasi Pembelajaran:
- Mata Pelajaran: {$subject} (Kelas {$level})
- Topik Utama: {$materi}
- Sesi Pertemuan: {$pertemuan}
- Total Alokasi Waktu: {$totalMinutes} Menit
- Isi Skenario dari Modul Ajar:
{$scenario}

Instruksi Desain Instruksional:
1. URAIKAN kegiatan belajar menjadi 3 sampai 4 SEGMEN AKSI NYATA BERAGAM MEDIA agar siswa aktif berpikir, mengamati, dan berinteraksi (bukan hanya membaca teks pasif!).
2. PILIH tipe konten yang bervariasi dari daftar tipe yang didukung sistem:
   - 'video': Video stimulus pengantar / apersepsi. Berikan video_url YouTube edukatif (misal: https://www.youtube.com/results?search_query=...).
   - 'text': Penjelasan konsep mendalam yang ramah anak dengan analogi nyata, atau tantangan refleksi.
   - 'html': Simulasi interaktif / eksperimen digital / widget kuis interaktif mandiri berbasis HTML5 + CSS + JavaScript mandiri (self-contained, siap dijalankan di iframe siswa).
   - 'link': Tautan sumber belajar luar atau simulasi interaktif (seperti PhET atau portal edukasi).
3. ALOKASIKAN waktu menit per segmen secara proporsional sehingga JUMLAH estimated_minutes seluruh segmen sama dengan {$totalMinutes} menit.
4. Buat kode HTML interaktif yang fungsional, rapi, dan menarik secara visual untuk segmen tipe 'html'.

Output HARUS berupa JSON valid murni (tanpa tanda kutip backtick ```json) dengan struktur persis berikut:
{
  \"sub_judul\": \"{$pertemuan}: {$materi}\",
  \"summary\": \"Deskripsi singkat 2-3 kalimat mengenai alur aksi belajar siswa pada pertemuan ini.\",
  \"total_minutes\": {$totalMinutes},
  \"segments\": [
    {
      \"title\": \"Langkah 1: [Judul Stimulus Apersepsi Video]\",
      \"content_type\": \"video\",
      \"estimated_minutes\": 15,
      \"description\": \"Aktivitas awal siswa mengamati stimulus visual.\",
      \"video_url\": \"https://www.youtube.com/results?search_query=...\",
      \"content\": \"\",
      \"content_html\": \"\",
      \"external_link\": \"\"
    },
    {
      \"title\": \"Langkah 2: [Judul Eksplorasi Konsep Inti]\",
      \"content_type\": \"text\",
      \"estimated_minutes\": 25,
      \"description\": \"Siswa mendalami konsep utama secara bermakna.\",
      \"video_url\": \"\",
      \"content\": \"Uraian materi penjelasan konsep...\",
      \"content_html\": \"\",
      \"external_link\": \"\"
    },
    {
      \"title\": \"Langkah 3: [Judul Simulasi / Praktik Interaktif]\",
      \"content_type\": \"html\",
      \"estimated_minutes\": 20,
      \"description\": \"Siswa melakukan simulasi interaktif untuk menguji pemahaman.\",
      \"video_url\": \"\",
      \"content\": \"\",
      \"content_html\": \"<!DOCTYPE html><html><head><meta charset='UTF-8'><style>body{font-family:sans-serif;padding:15px;background:#f8fafc;}...</style></head><body>...</body></html>\",
      \"external_link\": \"\"
    },
    {
      \"title\": \"Langkah 4: [Judul Refleksi & Diskusi Pemantik]\",
      \"content_type\": \"text\",
      \"estimated_minutes\": 10,
      \"description\": \"Siswa berdiskusi dan menuliskan refleksi pemahaman.\",
      \"video_url\": \"\",
      \"content\": \"Pertanyaan diskusi dan panduan refleksi...\",
      \"content_html\": \"\",
      \"external_link\": \"\"
    }
  ],
  \"discussion_prompt\": \"Pertanyaan pemantik terbuka untuk memicu pemikiran kritis siswa.\",
  \"micro_learning\": {
    \"title\": \"Konsep Utama: {$materi}\",
    \"summary_html\": \"<p>Rangkuman konsep utama dalam 2-3 paragraf ramah anak.</p>\"
  }
}";

        $apiKey = $aiConfig['api_key'];
        $provider = $aiConfig['provider'];

        try {
            if ($provider === 'groq') {
                $url = 'https://api.groq.com/openai/v1/chat/completions';
                $headers = [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json'
                ];
                $payload = json_encode([
                    "model" => "llama-3.1-8b-instant",
                    "messages" => [
                        ["role" => "system", "content" => "You are a professional educational instructional designer. Respond only in valid raw JSON."],
                        ["role" => "user", "content" => $prompt]
                    ],
                    "temperature" => 0.5,
                    "max_tokens" => 3000
                ]);
            } else {
                // Gemini API
                $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash:generateContent?key=" . $apiKey;
                $headers = ['Content-Type: application/json'];
                $payload = json_encode([
                    "contents" => [
                        ["parts" => [["text" => $prompt]]]
                    ],
                    "generationConfig" => [
                        "temperature"     => 0.4,
                        "maxOutputTokens" => 3000
                    ]
                ]);
            }

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200 && !empty($response)) {
                $resData = json_decode($response, true);
                $rawText = '';
                if ($provider === 'groq') {
                    $rawText = $resData['choices'][0]['message']['content'] ?? '';
                } else {
                    $rawText = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
                }

                // Bersihkan markdown codeblock jika AI membungkusnya
                $rawText = trim(preg_replace('/^```(?:json)?/i', '', trim($rawText)));
                $rawText = trim(preg_replace('/```$/', '', $rawText));

                $parsed = json_decode($rawText, true);
                if (is_array($parsed) && (isset($parsed['segments']) || isset($parsed['micro_learning']))) {
                    return $parsed;
                }
            }
        } catch (\Throwable $e) {
            log_message('error', 'AI Generator Exception: ' . $e->getMessage());
        }

        return null;
    }

    /**
     * Generator Desain Instruksional Pedagogis Cerdas (Smart Fallback jika tanpa AI / offline)
     * Menguraikan modul ajar menjadi 4 segmen aksi nyata beragam media (Video, Teks, HTML Simulasi, Refleksi)
     * dengan alokasi waktu yang proporsional sesuai jam tatap muka modul ajar.
     */
    public function buildFallbackPackage(string $subject, int $level, string $materi, int $pertemuanKe, ?array $meeting, int $totalMinutes = 70): array
    {
        $mindfulDesc = '';
        $meaningfulDesc = '';
        $joyfulDesc = '';

        if (!empty($meeting['tahap_list'])) {
            foreach ($meeting['tahap_list'] as $t) {
                if ($t['tahap'] === 'Mindful') $mindfulDesc = $t['deskripsi'];
                if ($t['tahap'] === 'Meaningful') $meaningfulDesc = $t['deskripsi'];
                if ($t['tahap'] === 'Joyful') $joyfulDesc = $t['deskripsi'];
            }
        }

        if (empty($meaningfulDesc) && !empty($meeting['raw_text'])) {
            $meaningfulDesc = $meeting['raw_text'];
        }

        // Distribusi alokasi waktu menit proporsional terhadap total alokasi waktu modul
        if ($totalMinutes <= 50) {
            $mVideo = 10;
            $mText  = 15;
            $mHtml  = 15;
            $mRefl  = 10;
        } elseif ($totalMinutes <= 70) {
            $mVideo = 15;
            $mText  = 25;
            $mHtml  = 20;
            $mRefl  = 10;
        } else {
            $mVideo = 15;
            $mText  = 35;
            $mHtml  = 25;
            $mRefl  = 15;
        }

        $searchQuery = urlencode("{$subject} {$materi} kelas {$level} sd");
        $videoUrl = "https://www.youtube.com/results?search_query={$searchQuery}";

        // Teks penjelasan materi yang kaya dan terstruktur
        $penjelasanText = "Topik: {$materi}\n\n" .
            "1. Pengantar Konsep:\n" .
            (!empty($mindfulDesc) ? "{$mindfulDesc}\n\n" : "Pada pertemuan ini kita mempelajari konsep {$materi} secara mendalam dan bermakna.\n\n") .
            "2. Pembahasan Inti:\n" .
            (!empty($meaningfulDesc) ? "{$meaningfulDesc}\n\n" : "Pelajari komponen-komponen utama dan fakta penting terkait materi ini bersama kelompok belajarmu.\n\n") .
            "3. Poin-Poin Penting:\n" .
            "- Pahami definisi dan prinsip dasar {$materi}.\n" .
            "- Identifikasi contoh nyata penerapannya dalam kehidupan sehari-hari.\n" .
            "- Diskusikan temuan menarik bersama teman sekelas.";

        // Widget HTML interaktif simulasi/kartu konsep mandiri
        $safeMateri = htmlspecialchars($materi, ENT_QUOTES, 'UTF-8');
        $safeSubject = htmlspecialchars($subject, ENT_QUOTES, 'UTF-8');
        $htmlWidget = "<!DOCTYPE html>
<html lang=\"id\">
<head>
<meta charset=\"UTF-8\">
<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">
<title>Simulasi Interaktif: {$safeMateri}</title>
<style>
  body { font-family: system-ui, -apple-system, sans-serif; background: #f8fafc; color: #1e293b; padding: 18px; margin: 0; line-height: 1.5; }
  .sim-card { background: #ffffff; border-radius: 12px; border: 1px solid #e2e8f0; padding: 20px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.06); max-width: 680px; margin: 0 auto; }
  .badge-tag { background: #e0f2fe; color: #0284c7; padding: 4px 10px; border-radius: 9999px; font-size: 0.8rem; font-weight: 700; display: inline-block; margin-bottom: 10px; }
  h3 { margin: 0 0 8px 0; color: #0f172a; font-size: 1.25rem; }
  p.lead-text { color: #475569; font-size: 0.92rem; margin: 0 0 16px 0; }
  .action-box { background: #f1f5f9; border-radius: 8px; padding: 14px; margin-bottom: 14px; }
  .btn-choice { display: block; width: 100%; text-align: left; background: white; border: 2px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; margin-bottom: 8px; font-size: 0.92rem; cursor: pointer; transition: all 0.2s ease; }
  .btn-choice:hover { background: #f8fafc; border-color: #3b82f6; }
  .btn-choice.correct { background: #dcfce7 !important; border-color: #22c55e !important; color: #15803d; font-weight: bold; }
  .btn-choice.wrong { background: #fee2e2 !important; border-color: #ef4444 !important; color: #b91c1c; }
  .feedback-box { margin-top: 14px; padding: 12px 14px; border-radius: 8px; font-size: 0.92rem; display: none; }
  .feedback-box.success { background: #f0fdf4; border: 1px solid #86efac; color: #166534; }
  .feedback-box.error { background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; }
</style>
</head>
<body>
<div class=\"sim-card\">
  <span class=\"badge-tag\">🎯 Simulasi Pemahaman Interaktif</span>
  <h3>Uji Konsep & Aksi Nyata: {$safeMateri}</h3>
  <p class=\"lead-text\">Pilihlah tindakan atau analisis yang paling tepat untuk menguji sejauh mana kamu memahami materi ini!</p>
  
  <div class=\"action-box\">
    <div style=\"font-weight: 600; margin-bottom: 10px;\">Pertanyaan Simulasi:</div>
    <div style=\"font-size: 0.95rem; margin-bottom: 12px;\">Manakah tindakan berikut yang paling mencerminkan penerapan konsep <strong>{$safeMateri}</strong> dalam kehidupan nyata?</div>
    <button class=\"btn-choice\" onclick=\"handleCheck(this, true)\">A. Menganalisis fenomena di lingkungan sekitar dan menghubungkannya dengan konsep {$safeMateri}</button>
    <button class=\"btn-choice\" onclick=\"handleCheck(this, false)\">B. Hanya menghafal teks definisi tanpa melakukan pengamatan atau praktik</button>
    <button class=\"btn-choice\" onclick=\"handleCheck(this, false)\">C. Mengabaikan kerja sama kelompok saat proses penyelidikan masalah</button>
  </div>
  <div id=\"feedback\" class=\"feedback-box\"></div>
</div>
<script>
function handleCheck(btn, isCorrect) {
  var all = document.querySelectorAll('.btn-choice');
  all.forEach(function(b){ b.disabled = true; });
  var fb = document.getElementById('feedback');
  if (isCorrect) {
    btn.classList.add('correct');
    fb.className = 'feedback-box success';
    fb.innerHTML = '<strong>🎉 Tepat Sekali!</strong> Pemahaman bermakna dicapai ketika kita menghubungkan konsep teori dengan penyelidikan nyata di lingkungan sekitar.';
  } else {
    btn.classList.add('wrong');
    fb.className = 'feedback-box error';
    fb.innerHTML = '<strong>💡 Perlu Diperhatikan:</strong> Pilihan tersebut kurang tepat. Pembelajaran aktif menekankan pada penyelidikan dan kerja sama.';
  }
  fb.style.display = 'block';
}
</script>
</body>
</html>";

        // Teks refleksi & diskusi
        $refleksiText = "Panduan Diskusi Pemantik & Refleksi Belajar:\n\n" .
            "1. Pertanyaan Pemantik:\n" .
            "Menurut pendapat kelompokmu, apa manfaat terbesar mempelajari topik {$materi} dalam kehidupan sehari-hari?\n\n" .
            "2. Lembar Refleksi Mandiri Siswa:\n" .
            "- Apa hal baru paling menarik yang kamu pelajari hari ini?\n" .
            "- Bagian mana dari materi yang masih membutuhkan penjelasan tambahan?\n" .
            (!empty($joyfulDesc) ? "- Catatan Refleksi: {$joyfulDesc}" : "");

        $segments = [
            [
                'title'             => 'Langkah 1: Stimulus Apersepsi & Pengamatan Video',
                'content_type'      => 'video',
                'estimated_minutes' => $mVideo,
                'description'       => "Siswa mengamati video stimulus pengantar topik {$materi} untuk menumbuhkan rasa ingin tahu.",
                'video_url'         => $videoUrl,
                'content'           => '',
                'content_html'      => '',
                'external_link'     => '',
            ],
            [
                'title'             => 'Langkah 2: Eksplorasi Konsep & Fakta Utama',
                'content_type'      => 'text',
                'estimated_minutes' => $mText,
                'description'       => "Siswa membaca penjelasan terstruktur, memahami konsep inti dan fakta penting.",
                'video_url'         => '',
                'content'           => $penjelasanText,
                'content_html'      => '',
                'external_link'     => '',
            ],
            [
                'title'             => 'Langkah 3: Simulasi Interaktif & Praktik Digital',
                'content_type'      => 'html',
                'estimated_minutes' => $mHtml,
                'description'       => "Siswa menguji pemahaman konsep secara mandiri melalui simulasi interaktif.",
                'video_url'         => '',
                'content'           => '',
                'content_html'      => $htmlWidget,
                'external_link'     => '',
            ],
            [
                'title'              => 'Langkah 4: Penguatan, Refleksi & Diskusi Kelas',
                'content_type'       => 'text',
                'estimated_minutes'  => $mRefl,
                'description'        => "Siswa merumuskan kesimpulan, berdiskusi, dan mencatat refleksi belajar.",
                'video_url'          => '',
                'content'            => $refleksiText,
                'content_html'       => '',
                'external_link'      => '',
                'has_task'           => true,
                'task_title'         => "Refleksi & Lembar Analisis: {$materi}",
                'task_instructions'  => "Tuliskan refleksi singkat mengenai topik {$materi} yang telah dipelajari pada pertemuan ini, serta berikan 1 contoh penerapannya dalam kehidupan sehari-hari.",
                'task_deadline_days' => 7,
            ],
        ];

        return [
            'sub_judul'         => "Pertemuan {$pertemuanKe}: {$materi}",
            'summary'           => "Alur belajar aktif 4 langkah mencakup stimulus video, eksplorasi konsep, simulasi interaktif, dan refleksi.",
            'total_minutes'     => $totalMinutes,
            'segments'          => $segments,
            'micro_learning'    => [
                'title'        => "Konsep Utama: {$materi}",
                'summary_html' => "<p>Pada pertemuan ke-{$pertemuanKe} ini, kita mempelajari topik <strong>{$materi}</strong>.</p>" .
                                  (!empty($mindfulDesc) ? "<p>{$mindfulDesc}</p>" : "") .
                                  (!empty($meaningfulDesc) ? "<p>{$meaningfulDesc}</p>" : "<p>Pelajari konsep-konsep inti yang telah disampaikan dalam kegiatan kelas dan diskusikan bersama kelompok.</p>"),
                'key_points'   => [
                    "Memahami konsep dasar {$materi} secara bermakna.",
                    "Mampu menghubungkan teori dengan contoh nyata di lingkungan sekitar.",
                    "Melakukan simulasi dan refleksi evaluasi pemahaman di akhir sesi pembelajaran."
                ]
            ],
            'discussion_prompt' => "Menurut pendapatmu, apa manfaat utama mempelajari topik {$materi} dalam kehidupan sehari-hari?",
            'lkpd_task' => [
                'title'             => "LKPD Digital: {$materi} (Pertemuan {$pertemuanKe})",
                'instructions_html' => "<p><strong>Petunjuk Pengerjaan:</strong></p>" .
                                       "<ol>" .
                                       "<li>Diskusikan bersama kelompokmu materi <em>{$materi}</em> yang telah dibahas.</li>" .
                                       "<li>Tuliskan 3 temuan atau fakta penting yang kamu temukan selama proses pembelajaran.</li>" .
                                       "<li>Ketikkan jawaban atau unggah dokumen hasil diskusimu pada kolom yang disediakan di bawah ini.</li>" .
                                       "</ol>",
                'submission_guide'  => "Ketik langsung hasil diskusi kelompokmu pada lembar jawaban tugas."
            ],
            'mini_quiz' => [
                [
                    'question'    => "Apa tujuan utama mempelajari topik {$materi} pada pertemuan ini?",
                    'options'     => [
                        'A' => "Memahami konsep dan menerapkannya dalam kehidupan sehari-hari",
                        'B' => "Menghafal teks tanpa memahami maknanya",
                        'C' => "Melewatkan kegiatan praktik dan diskusi",
                        'D' => "Hanya mengerjakan soal ujian akhir semester"
                    ],
                    'answer'      => 'A',
                    'explanation' => "Pembelajaran bermakna bertujuan agar siswa memahami konsep dan dapat mengaplikasikannya secara nyata."
                ],
                [
                    'question'    => "Sikap yang paling tepat saat melakukan kegiatan eksplorasi materi di kelas adalah...",
                    'options'     => [
                        'A' => "Pasif dan menunggu teman lain mengerjakan",
                        'B' => "Aktif, teliti, dan bekerja sama dengan anggota kelompok",
                        'C' => "Bekerja sendiri tanpa berdiskusi",
                        'D' => "Meninggalkan lembar kerja sebelum selesai"
                    ],
                    'answer'      => 'B',
                    'explanation' => "Kerja sama dan keaktifan kelompok adalah kunci keberhasilan pembelajaran bermakna."
                ],
                [
                    'question'    => "Langkah refleksi di akhir pembelajaran berguna untuk...",
                    'options'     => [
                        'A' => "Mengetahui sejauh mana pemahaman kita terhadap materi",
                        'B' => "Menambah beban belajar siswa",
                        'C' => "Menghabiskan sisa waktu jam pelajaran",
                        'D' => "Mengulang seluruh materi dari awal"
                    ],
                    'answer'      => 'A',
                    'explanation' => "Refleksi membantu siswa dan guru mengukur ketercapaian tujuan pembelajaran."
                ]
            ]
        ];
    }

    /**
     * Menerbitkan (Deploy) Paket Pembelajaran ke Database E-Learning Siakad
     * Mengisi: subject_materials, material_segments, subject_material_publishes, dan tugas
     */
    public function deployPackageToElearning(array $payload, array $user): array
    {
        $modulId    = (int)($payload['modul_id'] ?? 0);
        $classId    = (int)($payload['class_id'] ?? 0);
        $subjectId  = (int)($payload['subject_id'] ?? 0);
        $teacherId  = (int)($payload['teacher_id'] ?? ($user['related_id'] ?? 0));
        $userId     = (int)($user['id'] ?? 1);
        $level      = (int)($payload['level'] ?? 6);
        $semester   = (int)($payload['semester'] ?? 1);

        $parentTitle = $payload['materi_judul'] ?? '';
        $subTitle    = $payload['sub_judul'] ?? 'Sesi Pertemuan Digital';
        $summaryHtml = $payload['summary_html'] ?? '';
        $discussion  = $payload['discussion_prompt'] ?? '';
        $lkpdTitle   = $payload['lkpd_title'] ?? '';
        $lkpdDesc    = $payload['lkpd_desc'] ?? '';
        $publishClass = !empty($payload['publish_to_class']);

        // Jika subject_id belum ada, cari dari modul ajar
        if ($modulId > 0) {
            $modul = $this->modulModel->find($modulId);
            if ($modul) {
                if ($subjectId <= 0) {
                    $subjectId = (int)($modul['subject_id'] ?? 0);
                }
                if ($teacherId <= 0) {
                    $teacherId = (int)($modul['teacher_id'] ?? $teacherId);
                }
                if (empty($parentTitle)) {
                    $parentTitle = $modul['topik_materi'] ?? 'Materi Pembelajaran';
                }
                if ($classId <= 0 && !empty($modul['class_id'])) {
                    $classId = (int)$modul['class_id'];
                }
            }
        }

        if (empty($parentTitle)) {
            $parentTitle = 'Materi Pembelajaran';
        }
        if (empty($lkpdTitle)) {
            $lkpdTitle = "LKPD: {$parentTitle}";
        }

        // Resolusi level kelas
        if ($classId > 0) {
            $class = $this->classModel->find($classId);
            if ($class && !empty($class['level'])) {
                $level = (int)$class['level'];
            }
        }

        if ($subjectId <= 0) {
            return [
                'success' => false,
                'message' => 'Mata pelajaran tidak teridentifikasi untuk menerbitkan paket E-Learning.',
            ];
        }

        // Tahun ajaran aktif
        $activeYear = $this->yearModel->getActiveYear();
        $academicYearId = $activeYear['id'] ?? 1;

        $this->db->transStart();

        // 1. Dapatkan atau Buat Materi Induk (Parent Material)
        $parentMaterial = $this->materialModel
            ->where('subject_id', $subjectId)
            ->where('level', $level)
            ->where('year_id', $academicYearId)
            ->where('semester', $semester)
            ->where('parent_id IS NULL')
            ->where('title', $parentTitle)
            ->first();

        if (!$parentMaterial) {
            $parentId = $this->materialModel->insert([
                'parent_id'         => null,
                'level'             => $level,
                'subject_id'        => $subjectId,
                'year_id'           => $academicYearId,
                'semester'          => $semester,
                'title'             => $parentTitle,
                'description'       => "Materi pokok semester {$semester}",
                'content_type'      => 'text',
                'is_published'      => 1,
                'created_by'        => $userId,
                'sort_order'        => 1,
            ]);
        } else {
            $parentId = (int)$parentMaterial['id'];
        }

        // 2. Buat Sub-Materi (Pertemuan Spesifik)
        $subMatId = $this->materialModel->insert([
            'parent_id'         => $parentId,
            'level'             => $level,
            'subject_id'        => $subjectId,
            'year_id'           => $academicYearId,
            'semester'          => $semester,
            'title'             => $subTitle,
            'description'       => "Sesi pembelajaran digital dan LKPD untuk {$subTitle}",
            'content_type'      => 'text',
            'content'           => $summaryHtml,
            'is_published'      => 1,
            'created_by'        => $userId,
            'published_by'      => $userId,
            'published_at'      => date('Y-m-d H:i:s'),
            'sort_order'        => 1,
        ]);

        // 3. Tambahkan Segmen Pembelajaran Digital (Material Segments)
        // Segmen 1: Rangkuman Konsep (Mindful & Meaningful)
        $this->segmentModel->insert([
            'material_id'       => $subMatId,
            'title'             => '1. Rangkuman Konsep & Eksplorasi',
            'content_type'      => 'text',
            'content'           => strip_tags($summaryHtml),
            'content_html'      => $summaryHtml,
            'estimated_minutes' => 30,
            'sort_order'        => 1,
        ]);

        // Segmen 2: Bahan Diskusi Kelas (Pemantik)
        if (!empty($discussion)) {
            $discHtml = "<div class='alert alert-info'><i class='bi bi-chat-quote me-2'></i><strong>Pertanyaan Diskusi:</strong><br>{$discussion}</div>";
            $this->segmentModel->insert([
                'material_id'       => $subMatId,
                'title'             => '2. Diskusi & Pemahaman Bersama',
                'content_type'      => 'text',
                'content'           => $discussion,
                'content_html'      => $discHtml,
                'estimated_minutes' => 15,
                'sort_order'        => 2,
            ]);
        }

        // 4. Publikasikan ke Kelas jika diminta
        if ($publishClass && $classId > 0) {
            $this->materialModel->publishToClass($subMatId, $classId, $userId, $academicYearId);
        }

        // 5. Buat Penugasan LKPD Digital di tabel tugas (jika disediakan)
        $tugasId = null;
        if (!empty($lkpdDesc)) {
            $now = date('Y-m-d H:i:s');
            $deadline = date('Y-m-d H:i:s', strtotime('+7 days'));
            $tugasId = $this->tugasModel->insert([
                'teacher_id' => $teacherId,
                'class_id'   => $classId,
                'subject_id' => $subjectId,
                'judul'      => $lkpdTitle,
                'deskripsi'  => $lkpdDesc,
                'mulai_at'   => $now,
                'selesai_at' => $deadline,
                'created_by' => $userId,
            ]);
        }

        $this->db->transComplete();

        if ($this->db->transStatus() === false) {
            return [
                'success' => false,
                'message' => 'Gagal menyimpan paket pembelajaran ke database E-Learning.',
            ];
        }

        return [
            'success'     => true,
            'material_id' => $subMatId,
            'parent_id'   => $parentId,
            'tugas_id'    => $tugasId,
            'message'     => 'Paket E-Learning berhasil diterbitkan ke portal pembelajaran siswa!',
        ];
    }
}

