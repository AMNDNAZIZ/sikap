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

    <div class="content">
        <?php foreach ($questions as $i => $q): ?>
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