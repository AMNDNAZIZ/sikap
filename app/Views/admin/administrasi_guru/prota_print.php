<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Prota - <?= esc($subject['name']) ?></title>
    <style>
        body { font-family: 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.1; color: #000; margin: 0; padding: 20px; }
        p { margin: 0; padding: 0; }
        .text-center { text-align: center; }
        .text-end { text-align: right; }
        .fw-bold { font-weight: bold; }
        .mb-0 { margin-bottom: 0; }
        .mb-2 { margin-bottom: 8px; }
        .mb-3 { margin-bottom: 12px; }
        .mb-4 { margin-bottom: 16px; }
        .mb-5 { margin-bottom: 20px; }
        .mt-5 { margin-top: 40px; }
        .pt-3 { padding-top: 12px; }
        .pb-1 { padding-bottom: 4px; }
        .pb-4 { padding-bottom: 16px; }
        
        table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
        .table-bordered { border: 1px solid #000; }
        .table-bordered th, .table-bordered td { border: 1px solid #000; padding: 4px 6px; }
        .table-borderless th, .table-borderless td { border: none !important; padding: 1px 4px; }
        
        .bg-light { background-color: #f2f2f2 !important; -webkit-print-color-adjust: exact; }
        .small { font-size: 10pt; }
        .text-uppercase { text-transform: uppercase; }
        .text-decoration-underline { text-decoration: underline; }
        
        @media print {
            @page { size: portrait; margin: 1cm; }
            .no-print { display: none; }
            body { padding: 0; }
        }

        .header-section { margin-bottom: 30px; }
        .header-section h3 { margin: 0; text-transform: uppercase; }
        
        .signature-row { display: flex; justify-content: space-between; margin-top: 50px; }
        .signature-box { width: 40%; text-align: center; }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="background: #ffc; padding: 10px; border: 1px solid #cc0; margin-bottom: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 8px 20px; cursor: pointer;">Print Sekarang</button>
        <p style="margin: 5px 0 0 0; font-size: 0.9em;">Gunakan pengaturan <b>Portrait</b> pada jendela print.</p>
    </div>

    <!-- Document Header -->
    <div class="header-section text-center">
        <h3>PROGRAM TAHUNAN (PROTA)</h3>
        <h3 class="fw-bold text-uppercase text-center">TAHUN PELAJARAN <?= date('Y') ?>/<?= date('Y') + 1 ?></h3>
    </div>

    <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
        <div style="width: 50%;">
            <table class="table-borderless small">
                <tr>
                    <td width="140">Satuan Pendidikan</td>
                    <td width="10">:</td>
                    <td class="fw-bold"><?= esc($school['name'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td>Mata Pelajaran</td>
                    <td>:</td>
                    <td class="fw-bold"><?= esc($subject['name']) ?></td>
                </tr>
            </table>
        </div>
        <div style="width: 30%;">
            <table class="table-borderless small">
                <tr>
                    <td width="100">Kelas / Fase</td>
                    <td width="10">:</td>
                    <td class="fw-bold"><?= esc($kelas) ?> / <?= esc($fase) ?></td>
                </tr>
            </table>
        </div>
    </div>

    <table class="table-bordered">
        <thead class="bg-light text-center small fw-bold">
            <tr>
                <th width="40">NO</th>
                <th>LINGKUP MATERI / TUJUAN PEMBELAJARAN</th>
                <th width="100">ALOKASI WAKTU</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $semesters = [1 => 'GANJIL', 2 => 'GENAP'];
            $totalJp = 0;
            foreach ($semesters as $semKey => $semName): 
                $atpsInSem = array_filter($prota, fn($a) => $a['semester'] == $semKey);
            ?>
                <tr class="bg-light">
                    <td colspan="3" class="text-center fw-bold py-2">
                        SEMESTER <?= $semKey ?> (<?= $semName ?>)
                    </td>
                </tr>

                <?php if (!empty($atpsInSem)): ?>
                    <?php $no = 1; foreach ($atpsInSem as $atp): ?>
                        <?php 
                        $tps = $atp['tps'] ?? [];
                        $totalJp += $atp['alokasi_waktu'];
                        ?>
                        <tr>
                            <td class="text-center align-middle"><?= $no++ ?></td>
                            <td style="padding: 0;">
                                <div style="padding: 8px; font-weight: bold; border-bottom: 1px solid #000; background: #fafafa;">
                                    <?= esc($atp['lingkup_materi']) ?>
                                </div>
                                <div style="padding: 10px;">
                                    <table class="table-borderless small" style="margin-bottom: 0;">
                                        <?php foreach ($tps as $tp): ?>
                                            <tr>
                                                <td width="40" valign="top"><strong><?= esc($tp['kode_tp']) ?></strong></td>
                                                <td valign="top"><?= esc($tp['deskripsi']) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </table>
                                </div>
                            </td>
                            <td class="text-center fw-bold"><?= $atp['alokasi_waktu'] ?> JP</td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 small" style="font-style: italic;">
                            Belum ada data alur pembelajaran untuk semester ini.
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="fw-bold bg-light">
                <td colspan="2" class="text-end" style="padding-right: 20px;">TOTAL ALOKASI WAKTU TAHUNAN</td>
                <td class="text-center"><?= $totalJp ?> JP</td>
            </tr>
        </tfoot>
    </table>

    <div class="signature-row pt-3">
        <div class="signature-box">
            <p>Mengetahui,</p>
            <p>Kepala Sekolah</p>
            <br><br><br>
            <p class="fw-bold text-decoration-underline"><?= esc($school['headmaster'] ?? '-') ?></p>
            <p class="small">NIP. <?= esc($school['principal_nip'] ?? '-') ?></p>
        </div>
        <div class="signature-box">
            <p><?= esc($school['city_regency'] ?? 'Indramayu') ?>, <?= date('d F Y') ?></p>
            <p>Guru Mata Pelajaran</p>
            <br><br><br>
            <p class="fw-bold text-decoration-underline"><?= esc($teacher['name'] ?? '-') ?></p>
            <p class="small">NIP. <?= esc($teacher['nip'] ?? '-') ?></p>
        </div>
    </div>

</body>
</html>
