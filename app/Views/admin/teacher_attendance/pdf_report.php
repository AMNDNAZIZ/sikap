<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Absensi Guru</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #222; }
        h3 { text-align: center; margin: 0 0 4px 0; }
        p.subtitle { text-align: center; margin: 0 0 12px 0; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background: #2c3e50; color: #fff; padding: 6px 8px; text-align: center; }
        td { padding: 5px 8px; border: 1px solid #ccc; }
        tr:nth-child(even) { background: #f5f5f5; }
        .text-center { text-align: center; }
        .badge-ok   { color: #155724; background: #d4edda; padding: 2px 6px; border-radius: 4px; }
        .badge-warn { color: #856404; background: #fff3cd; padding: 2px 6px; border-radius: 4px; }
        .badge-bad  { color: #721c24; background: #f8d7da; padding: 2px 6px; border-radius: 4px; }
    </style>
</head>
<body>
    <h3>Laporan Absensi Guru</h3>
    <p class="subtitle"><?= date('F Y', strtotime($month . '-01')) ?></p>

    <table>
        <thead>
            <tr>
                <th style="width:30px">No</th>
                <th>Nama Guru</th>
                <th>NIP</th>
                <th>Total JP Terjadwal</th>
                <th>JP Hadir</th>
                <th>JP Tidak Hadir</th>
                <th>% Kehadiran</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($rekap as $r):
                $p = $r['persen'];
                $cls = $p >= 90 ? 'badge-ok' : ($p >= 75 ? 'badge-warn' : 'badge-bad');
            ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= esc($r['teacher_name']) ?></td>
                    <td><?= esc($r['nip'] ?? '-') ?></td>
                    <td class="text-center"><?= $r['total_jp'] ?></td>
                    <td class="text-center"><?= $r['jp_hadir'] ?></td>
                    <td class="text-center"><?= $r['jp_th'] ?></td>
                    <td class="text-center">
                        <span class="<?= $cls ?>"><?= $p ?>%</span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rekap)): ?>
                <tr><td colspan="7" class="text-center">Tidak ada data</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
