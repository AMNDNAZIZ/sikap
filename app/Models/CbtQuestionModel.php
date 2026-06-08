<?php

namespace App\Models;

use CodeIgniter\Model;

class CbtQuestionModel extends Model
{
    protected $table = 'cbt_questions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'bank_id',
        'question_text',
        'raw_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'option_e',
        'correct_option',
        'question_type',
        'essay_answer',
        'score',
        'media_image',
        'media_audio',
        'media_video',
        'has_image',
        'has_audio'
    ];

    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';

    /**
     * Ambil daftar soal beserta nama bank soal
     */
    public function getWithBank($bankId = null)
    {
        $builder = $this->select('cbt_questions.*, cbt_question_banks.code as bank_code')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_questions.bank_id', 'left');

        if ($bankId) {
            $builder->where('cbt_questions.bank_id', $bankId);
        }

        return $builder->orderBy('cbt_questions.id', 'ASC')->findAll();
    }

    public function getWithBankAndMedia($bankId = null)
    {
        $builder = $this->select('cbt_questions.*, cbt_question_banks.code as bank_code')
            ->join('cbt_question_banks', 'cbt_question_banks.id = cbt_questions.bank_id', 'left');
        if ($bankId) {
            $builder->where('cbt_questions.bank_id', $bankId);
        }
        $results = $builder->orderBy('cbt_questions.id', 'ASC')->findAll();
        // Decode media_image jika JSON
        foreach ($results as &$row) {
            $row['media_images'] = json_decode($row['media_image'], true) ?? [];
        }
        return $results;
    }

    /**
     * Parse Raw HTML menjadi struktur soal (Question, Options, Key)
     * Mempertahankan formatting block-level (paragraf, div, list).
     */
    public function parseRawQuestion($html)
    {
        // 1️⃣ Simpan tag <img> dengan placeholder
        preg_match_all('/<img[^>]+>/i', $html, $imgMatches);
        $imgTags = $imgMatches[0] ?? [];
        $htmlPlaceholder = preg_replace_callback('/<img[^>]+>/i', function ($m) {
            return ' [[IMG]] ';
        }, $html);

        // 2️⃣ Sisipkan splitter token '[[SPLIT]]' di akhir block-level elements
        $blockTags = ['p', 'div', 'li', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6', 'blockquote', 'pre', 'hr', 'tr', 'table'];
        foreach ($blockTags as $tag) {
            $htmlPlaceholder = preg_replace("/<\/{$tag}>/i", "</{$tag}>[[SPLIT]]", $htmlPlaceholder);
        }

        // <br> juga splitter
        $htmlPlaceholder = preg_replace('/<br\s*\/?>/i', "<br>[[SPLIT]]", $htmlPlaceholder);

        // 2b. INLINE SPLITTER: Inject SPLIT before options that are inline
        // Pattern: (punctuation/space/tag/^)(OptionLetter/Digit)(Separator)(Space*)
        $htmlPlaceholder = preg_replace('/([\s\.,;:\?!\(\)>]|&nbsp;|^)([\(]?[A-E1-5]\s*[\.:\)\-]\s*)/u', '$1[[SPLIT]]$2', $htmlPlaceholder);


        // Also split Kunci:
        $htmlPlaceholder = preg_replace('/([\s\.,;:\?!\(\)>]|&nbsp;|^)(Kunci\s*[:=]\s*)/iu', '$1[[SPLIT]]$2', $htmlPlaceholder);
        $htmlPlaceholder = preg_replace('/([\s\.,;:\?!\(\)>]|&nbsp;|^)(Tipe\s*[:=]\s*)/iu', '$1[[SPLIT]]$2', $htmlPlaceholder);

        // 3️⃣ Strip tags tapi ALLOW formatting
        $allowedTags = '<b><i><u><strong><em><span><ul><ol><li><table><tbody><thead><tr><td><th><p><div><h1><h2><h3><h4><h5><h6><pre><blockquote><hr><a>';

        $cleanHtml = strip_tags($htmlPlaceholder, $allowedTags);
        $cleanHtml = html_entity_decode($cleanHtml, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // 4️⃣ Kembalikan tag <img>
        foreach ($imgTags as $tag) {
            $cleanHtml = preg_replace('/\[\[IMG\]\]/', $tag, $cleanHtml, 1);
        }

        // 5️⃣ Pecah berdasarkan SPLIT token
        $rawLines = explode('[[SPLIT]]', $cleanHtml);

        $questionParts = [];
        $options = [];
        $key = null;
        $type = 'pg';

        foreach ($rawLines as $line) {
            $trimmedLine = trim($line);
            if ($trimmedLine === '')
                continue;

            $plainText = trim(strip_tags($trimmedLine));

            // Deteksi Opsi: A. ... / 1. ...
            if (preg_match('/^[\\(]?\\s*([A-E1-5])\\s*(?:[:.)]|\\s+-)\\s*(.+)$/iu', $plainText, $m)) {
                $rawKey = strtoupper($m[1]);
                $map = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E'];
                $optKey = $map[$rawKey] ?? $rawKey;
                $options[$optKey] = trim($m[2]);

                // Hapus baris terakhir di questionParts jika hanya berisi tag pembuka (p, div, dll)
                // Ini mencegah spasi kosong yang ditinggalkan oleh splitter inline
                if (!empty($questionParts)) {
                    $lastIdx = count($questionParts) - 1;
                    if (preg_match('/^<[a-z0-9]+[^>]*>$/i', trim($questionParts[$lastIdx]))) {
                        array_pop($questionParts);
                    }
                }
            }
            // Deteksi Kunci
            elseif (preg_match('/^Kunci\\s*[:.)]?\\s*(esai|.*)/i', $plainText, $m)) {
                $rawKey = trim($m[1]);

                // Cleanup orphan tags before the key
                if (!empty($questionParts)) {
                    $lastIdx = count($questionParts) - 1;
                    if (preg_match('/^<[a-z0-9]+[^>]*>$/i', trim($questionParts[$lastIdx]))) {
                        array_pop($questionParts);
                    }
                }

                if (strtolower($rawKey) === 'esai') {
                    $type = 'esai';
                } else {
                    // BS support: match A-E and S
                    preg_match_all('/[A-ES]/i', $rawKey, $keyMatches);
                    if (!empty($keyMatches[0])) {
                        $uniqueKeys = array_unique(array_map('strtoupper', $keyMatches[0]));
                        sort($uniqueKeys);
                        $key = implode(',', $uniqueKeys);

                        // Heuristik: Hanya anggap BS jika kuncinya HANYA berisi B dan S
                        // PGK biasanya punya kunci A,B,C,D... jadi jangan asal detect BS jika ada A/C/D
                        $hasNonBS = preg_match('/[ACDE]/i', $key);

                        if (preg_match('/[BS]/i', $rawKey) && !$hasNonBS && count($uniqueKeys) > 1) {
                            $type = 'benar_salah';
                        } elseif (count($uniqueKeys) > 1) {
                            $type = 'pg_kompleks';
                        }
                    }
                }
            } elseif (preg_match('/^Tipe\s*[:=]\s*.*/i', $plainText)) {
                // Skip "Tipe: BS" or similar from being added to question text
                continue;
            }
            // Bagian dari Soal (Keep HTML)
            else {
                $questionParts[] = $trimmedLine;
            }
        }

        // 6️⃣ GENERATE QUESTION TEXT (Assembly Method)
        // Re-assemble from $questionParts to ensure we don't include parts detected as Options/Keys
        $questionText = implode(' ', $questionParts);

        // Remove empty paragraphs/divs left behind
        $questionText = preg_replace('/<(p|div)[^>]*>(\s|&nbsp;|<br\s*\/?>)*<\/\1>/is', '', $questionText);

        // 🌟 FIX: Aggressive Cleanup of leading/trailing orphaned tags
        // This prevents broken HTML from closing parent containers in the view.
        // Strip leading closing tags
        $questionText = preg_replace('/^(\s*<\/(?:span|div|p|strong|em|i|b|u|li|ul|ol|table|tr|td|h[1-6])>[^><]*)+/i', '', $questionText);
        // Strip trailing opening tags
        $questionText = preg_replace('/(\s*<(?:span|div|p|strong|em|i|b|u|li|ul|ol|table|tr|td|h[1-6])[^>]*>\s*)+$/i', '', $questionText);
        // Strip trailing closing tags (to be extra safe with dangling orphans)
        $questionText = preg_replace('/(\s*<\/(?:span|div|p|strong|em|i|b|u|li|ul|ol|table|tr|td|h[1-6])>\s*)+$/i', '', $questionText);

        $questionText = trim($questionText);

        if (empty($options)) {
            // Jika tidak ada opsi tapi ada kunci berupa A,B,C... (biasanya BS atau PGK)
            if ($key && $type !== 'esai') {
                // FALLBACK: Ambil sisa questionParts sebagai opsi jika tidak ada awalan A-E
                // Khusus jika questionParts > 1
                if (count($questionParts) > 1) {
                    $keysMatched = explode(',', $key);
                    $totalNeeded = count($keysMatched);
                    // Ambil N baris terakhir sebagai opsi
                    $lastPart = array_pop($questionParts);
                    $options['A'] = $lastPart;
                    $type = (strpos($key, ',') !== false) ? 'benar_salah' : 'pg';
                }

                if (strpos($key, ',') !== false && $type !== 'pg_kompleks') {
                    $type = 'benar_salah';
                }
            } else {
                $type = 'esai';
            }
        }

        return [
            'question' => $questionText,
            'options' => $options,
            'key' => $key,
            'type' => $type,
        ];
    }

}
