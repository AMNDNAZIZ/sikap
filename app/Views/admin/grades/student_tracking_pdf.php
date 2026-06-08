<!DOCTYPE html>
<html>

<head>
    <title>Riwayat Nilai</title>
    <style>
        body {
            font-family: sans-serif;
            font-size: 12px;
        }

        .header {
            margin-bottom: 20px;
        }

        .header h3 {
            margin: 0 0 10px;
            font-size: 14px;
            font-weight: bold;
        }

        .info-table {
            border: none;
            width: 100%;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .info-label {
            width: 150px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th,
        .data-table td {
            border: 1px solid #000;
            padding: 4px;
        }

        .data-table th {
            text-align: center;
            vertical-align: middle;
            background-color: #f0f0f0;
        }

        .data-table td.center {
            text-align: center;
        }

        .footer {
            margin-top: 30px;
            width: 100%;
        }

        .signature-box {
            float: right;
            width: 250px;
            text-align: left;
        }

        .signature-box p {
            margin: 5px 0;
        }

        .signature-space {
            height: 60px;
        }
    </style>
</head>

<body>

    <div class="header">
        <h3>DATA RIWAYAT NILAI</h3>
    </div>

    <table class="info-table">
        <tr>
            <td class="info-label">Nama</td>
            <td>:
                <?= esc($student['name']) ?>
            </td>
        </tr>
        <tr>
            <td class="info-label">NIS/NISN</td>
            <td>:
                <?= esc($student['nis']) ?>
            </td>
        </tr>
        <tr>
            <td class="info-label">Tempat, Tanggal Lahir</td>
            <td>:
                <?= esc($student['birth_place']) ?>,
                <?= esc($student['birth_date']) ?>
            </td>
        </tr>
    </table>

    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="3" style="width: 30px;">No</th>
                <th rowspan="3">Mata Pelajaran</th>
                <?php foreach ($records as $rec): ?>
                    <th colspan="2">
                        <?= esc($rec['year_name']) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($records as $rec): ?>
                    <th colspan="2">
                        <?= esc($rec['class_name']) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
            <tr>
                <?php foreach ($records as $rec): ?>
                    <th style="width: 30px;">Smt 1</th>
                    <th style="width: 30px;">Smt 2</th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1;
            foreach ($subjects as $subjectName): ?>
                <tr>
                    <td class="center">
                        <?= $no++ ?>
                    </td>
                    <td>
                        <?= esc($subjectName) ?>
                    </td>
                    <?php foreach ($records as $rec): ?>
                        <?php
                        $yid = $rec['academic_year_id'];
                        $val1 = $matrix[$subjectName][$yid][1] ?? '';
                        $val2 = $matrix[$subjectName][$yid][2] ?? '';
                        ?>
                        <td class="center">
                            <?= $val1 !== '' && $val1 !== null ? number_format((float)$val1, 2) : '-' ?>
                        </td>
                        <td class="center">
                            <?= $val2 !== '' && $val2 !== null ? number_format((float)$val2, 2) : '-' ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>

            <!-- Blank rows filler if needed -->
            <?php if (count($subjects) < 5): ?>
                <?php for ($i = 0; $i < 3; $i++): ?>
                    <tr>
                        <td>&nbsp;</td>
                        <td>&nbsp;</td>
                        <?php foreach ($records as $rec): ?>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        <?php endforeach; ?>
                    </tr>
                <?php endfor; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <p style="font-size:10px; color:#555; margin-top:8px;">
        * Nilai yang ditampilkan adalah <strong>Nilai Erapor</strong> (input guru) jika sudah diisi,
        atau <strong>Nilai Rapor Acuan Sistem</strong> (% formatif + sumatif) jika belum.
    </p>

    <div class="footer">
        <div class="signature-box">
            <p>Jakarta,
                <?= date('d F Y') ?>
            </p>
            <p>Kepala <?= esc($school['name']) ?? 'Sekolah' ?></p>
            <div class="signature-space"></div>
            <p><?= esc($school['headmaster']) ?? '..........................' ?></p>
            <p>NIP: <?= esc($school['principal_nip']) ?? '-' ?></p>
        </div>
        <div style="clear: both;"></div>
    </div>

</body>

</html>