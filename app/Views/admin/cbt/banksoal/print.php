<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Bank Soal - <?= esc($bank['code']) ?></title>
    <style>
        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .header h2 {
            margin: 0;
            text-transform: uppercase;
        }

        .header p {
            margin: 5px 0 0;
        }

        .info-table {
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 2px 0;
        }

        .section-header {
            margin-top: 20px;
            margin-bottom: 12px;
            padding: 8px 12px;
            background-color: #f1f5f9;
            border-left: 4px solid #1e40af;
            border-top: 1px solid #e2e8f0;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            page-break-after: avoid;
        }

        .section-title {
            font-size: 11pt;
            font-weight: bold;
            color: #0f172a;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .section-instruction {
            font-size: 9.5pt;
            color: #334155;
            line-height: 1.4;
            font-style: italic;
        }

        .question-item {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .question-text {
            margin-bottom: 10px;
            font-weight: normal;
        }

        .options {
            list-style-type: none;
            padding-left: 20px;
        }

        .options li {
            margin-bottom: 5px;
        }

        .options li span.opt-label {
            font-weight: bold;
            display: inline-block;
            width: 20px;
        }

        .bs-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .bs-table th,
        .bs-table td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        .bs-table th {
            background-color: #f9f9f9;
        }

        .esai-box {
            width: 100%;
            height: 100px;
            border: 1px solid #ccc;
            margin-top: 10px;
        }

        .footer {
            position: fixed;
            bottom: -30px;
            left: 0px;
            right: 0px;
            height: 50px;
            text-align: center;
            font-size: 9pt;
            color: #777;
        }

        img {
            max-width: 100%;
            height: auto;
            display: block;
            margin: 10px 0;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>
    <div class="header">
        <h2>Bank Soal Ujian</h2>
        <p>Aplikasi CBTku V.2.0</p>
    </div>

    <table class="info-table">
        <tr>
            <td width="20%">Kode Bank</td>
            <td width="5%">:</td>
            <td><strong><?= esc($bank['code']) ?></strong></td>
            <td width="20%">Mata Pelajaran</td>
            <td width="5%">:</td>
            <td><?= esc($bank['subject_name'] ?? '-') ?></td>
        </tr>
        <tr>
            <td>Tingkat/Kelas</td>
            <td>:</td>
            <td><?= esc($bank['level'] ?? '-') ?></td>
            <td>Jumlah Soal</td>
            <td>:</td>
            <td><?= count($questions) ?> Butir</td>
        </tr>
    </table>

    <?php
    if (!function_exists('cbt_to_roman')) {
        function cbt_to_roman($number) {
            $map = [
                'M' => 1000, 'CM' => 900, 'D' => 500, 'CD' => 400,
                'C' => 100, 'XC' => 90, 'L' => 50, 'XL' => 40,
                'X' => 10, 'IX' => 9, 'V' => 5, 'IV' => 4, 'I' => 1
            ];
            $returnValue = '';
            while ($number > 0) {
                foreach ($map as $roman => $int) {
                    if ($number >= $int) {
                        $number -= $int;
                        $returnValue .= $roman;
                        break;
                    }
                }
            }
            return $returnValue ?: 'I';
        }
    }

    $typeTitles = [
        'pg'          => 'PILIHAN GANDA',
        'pg_kompleks' => 'PILIHAN GANDA KOMPLEKS',
        'benar_salah' => 'BENAR / SALAH',
        'jodohkan'    => 'MENJODOHKAN',
        'esai'        => 'URAIAN / ESAI',
    ];

    $extractPetunjuk = function (&$text) {
        $petunjuk = null;
        if (preg_match('/<(p|div|blockquote)[^>]*>(?:\s*<(?:strong|b|span)[^>]*>)?\s*(Petunjuk\s+(?:pengerjaan|soal|khusus).*?)<\/\1>/is', $text, $m)) {
            $petunjuk = trim(strip_tags($m[2]));
            $text = str_replace($m[0], '', $text);
        } elseif (preg_match('/<(strong|b)[^>]*>\s*(Petunjuk\s+(?:pengerjaan|soal|khusus).*?)<\/\1>/is', $text, $m)) {
            $petunjuk = trim(strip_tags($m[2]));
            $text = str_replace($m[0], '', $text);
        } elseif (preg_match('/(Petunjuk\s+(?:pengerjaan|soal|khusus)[^<\r\n]+(?:\.[^<\r\n]+)*)/is', $text, $m)) {
            $petunjuk = trim(strip_tags($m[1]));
            $text = str_replace($m[0], '', $text);
        }
        $text = preg_replace('/(<p[^>]*>\s*(?:&nbsp;|\s)*<\/p>)+$/i', '', trim($text));
        return $petunjuk;
    };

    $detectTypeFromText = function ($text, $fallbackType = 'pg') {
        if (preg_match('/benar\s*(?:atau|\/)?\s*salah/i', $text)) {
            return 'benar_salah';
        }
        if (preg_match('/semua\s+jawaban|lebih\s+dari\s+satu|kompleks/i', $text)) {
            return 'pg_kompleks';
        }
        if (preg_match('/jodoh|pasang/i', $text)) {
            return 'jodohkan';
        }
        if (preg_match('/esai|uraian/i', $text)) {
            return 'esai';
        }
        return $fallbackType;
    };

    $formatInstruction = function ($rawPetunjuk, $type) {
        if (!empty($rawPetunjuk)) {
            $clean = preg_replace('/^Petunjuk\s+(?:pengerjaan\s+)?(?:untuk\s+penyusun\s+soal\s+adalah\s*|soal\s*[:\-–]?\s*|:\s*)/i', '', $rawPetunjuk);
            $clean = ucfirst(trim($clean));
            if (!empty($clean)) {
                return '<strong>Petunjuk:</strong> ' . $clean;
            }
        }
        switch ($type) {
            case 'pg':
                return '<strong>Petunjuk:</strong> Pilihlah salah satu jawaban yang paling tepat.';
            case 'pg_kompleks':
                return '<strong>Petunjuk:</strong> Pilihlah semua jawaban yang benar pada butir soal berikut (jawaban benar lebih dari satu).';
            case 'benar_salah':
                return '<strong>Petunjuk:</strong> Tentukan apakah setiap pernyataan berikut bernilai <strong>Benar (B)</strong> atau <strong>Salah (S)</strong> berdasarkan stimulus.';
            case 'jodohkan':
                return '<strong>Petunjuk:</strong> Pasangkanlah pernyataan pada kolom kiri dengan jawaban yang sesuai pada kolom kanan.';
            case 'esai':
                return '<strong>Petunjuk:</strong> Jawablah pertanyaan-pertanyaan berikut dengan jelas dan benar.';
            default:
                return '';
        }
    };

    $processedQuestions = [];
    $currentSection = null;
    $sectionIndex = 0;

    foreach ($questions as $i => $q) {
        $text = $q['question_text_clean'] ?? '';
        $extracted = $extractPetunjuk($text);
        $q['question_text_clean'] = $text;

        $effectiveType = $q['question_type'] ?? 'pg';
        if ($extracted) {
            $detected = $detectTypeFromText($extracted, $effectiveType);
            if ($detected !== $effectiveType) {
                $effectiveType = $detected;
            }
        }

        $isSectionChange = false;
        if ($currentSection === null || $currentSection !== $effectiveType || $extracted !== null) {
            $isSectionChange = true;
            $currentSection = $effectiveType;
            $sectionIndex++;
        }

        $q['_section_change'] = $isSectionChange;
        $q['_section_index'] = $sectionIndex;
        $q['_section_type'] = $effectiveType;
        $q['_instruction'] = $formatInstruction($extracted, $effectiveType);

        $processedQuestions[] = $q;
    }
    ?>

    <div class="content">
        <?php foreach ($processedQuestions as $i => $q): ?>
            <?php if ($q['_section_change']): ?>
                <div class="section-header">
                    <div class="section-title">
                        BAGIAN <?= cbt_to_roman($q['_section_index']) ?>: <?= $typeTitles[$q['_section_type']] ?? strtoupper($q['_section_type']) ?>
                    </div>
                    <?php if (!empty($q['_instruction'])): ?>
                        <div class="section-instruction"><?= $q['_instruction'] ?></div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="question-item">
                <div class="question-text">
                    <strong><?= $i + 1 ?>.</strong> <?= $q['question_text_clean'] ?>
                </div>

                <?php if ($q['question_type'] === 'pg' || $q['question_type'] === 'pg_kompleks'): ?>
                    <ul class="options">
                        <?php foreach (['A', 'B', 'C', 'D', 'E'] as $opt): ?>
                            <?php if (!empty($q['option_' . strtolower($opt)])): ?>
                                <li>
                                    <span class="opt-label"><?= $opt ?>.</span>
                                    <?= $q['option_' . strtolower($opt)] ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>

                <?php elseif ($q['question_type'] === 'benar_salah'): ?>
                    <table class="bs-table">
                        <thead>
                            <tr>
                                <th width="5%">No</th>
                                <th>Pernyataan</th>
                                <th width="15%" style="text-align: center;">Benar</th>
                                <th width="15%" style="text-align: center;">Salah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Decode options if stored as JSON or just iterate A-E
                            $options = [
                                'A' => $q['option_a'],
                                'B' => $q['option_b'],
                                'C' => $q['option_c'],
                                'D' => $q['option_d'],
                                'E' => $q['option_e']
                            ];
                            $idx = 1;
                            foreach ($options as $optKey => $optVal):
                                if (empty($optVal))
                                    continue;
                                ?>
                                <tr>
                                    <td style="text-align: center;"><?= $idx++ ?></td>
                                    <td><?= $optVal ?></td>
                                    <td></td>
                                    <td></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                <?php elseif ($q['question_type'] === 'esai'): ?>
                    <div class="esai-box"></div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="footer">
        Dicetak pada <?= date('d/m/Y H:i') ?> | Halaman <span class="page-number"></span>
    </div>
</body>

</html>