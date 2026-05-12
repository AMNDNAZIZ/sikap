<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<h4>DAFTAR NILAI</h4>
<p>
    Tahun Ajaran: <?= esc($yearName) ?><br>
    Kelas: <?= esc($class['name']) ?><br>
    Mata Pelajaran: <?= esc($subject['name']) ?><br>
</p>

<!-- Tabs -->
<ul class="nav nav-tabs mb-3">
  <li class="nav-item">
    <a class="nav-link <?= $semester==1?'active':'' ?>" href="<?= site_url("admin/grades/show/$class[id]/$subject[id]/1") ?>">Semester 1</a>
  </li>
  <li class="nav-item">
    <a class="nav-link <?= $semester==2?'active':'' ?>" href="<?= site_url("admin/grades/show/$class[id]/$subject[id]/2") ?>">Semester 2</a>
  </li>
  <li class="nav-item ms-auto">
    <a class="btn btn-sm btn-outline-primary" href="<?= site_url("admin/grades/$class[id]/$subject[id]/$semester/pdf") ?>">📄 Cetak PDF</a>
    <a class="btn btn-sm btn-outline-success" href="<?= site_url("admin/grades/$class[id]/$subject[id]/$semester/excel") ?>">📊 Export Excel</a>
  </li>
</ul>


<br><br>

<table class="table table-bordered table-striped">
  <thead class="table-light">
        <tr>
            <th rowspan="2">No</th>
            <th rowspan="2">Nama Siswa</th>

            <?php
            $totalFormCols = 0;
            foreach ($visibleMaterials as $m) {
                $totalFormCols += count($formatifMethods[$m['id']] ?? []);
            }
            if ($totalFormCols > 0) echo "<th colspan=\"" . ($totalFormCols + 1) . "\">Formatif</th>";
            $totalSumCols = count($sumatifMethods);
            if ($totalSumCols > 0) echo "<th colspan=\"" . ($totalSumCols + 1) . "\">Sumatif</th>";
            ?>
            <th rowspan="2">Nilai Rapor</th>
            <?php if ($semester == 2 && $hasFinal): ?>
                <th rowspan="2">Nilai Final</th>
            <?php endif; ?>
        </tr>
        <tr>
            <!-- Formatif sub-columns -->
            <?php foreach ($visibleMaterials as $idx => $m): ?>
                <?php $mIndex = $idx + 1; ?>
                <?php foreach ($formatifMethods[$m['id']] as $method): ?>
                    <th>M<?= $mIndex ?> (<?= esc($method) ?>)</th>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if ($totalFormCols > 0): ?>
                <th>Rerata</th>
            <?php endif; ?>

            <!-- Sumatif sub-columns -->
            <?php foreach ($sumatifMethods as $method): ?>
                <th><?= ucfirst(esc($method)) ?></th>
            <?php endforeach; ?>
            <?php if ($totalSumCols > 0): ?>
                <th>Rerata</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $i => $stu): ?>
            <?php $sid = $stu['id']; ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= esc($stu['name']) ?></td>

                <!-- Formatif values: tampil per visible material per method -->
                <?php
                // collect values for average calculation (but controller juga sudah menghitung avg)
                foreach ($visibleMaterials as $m) {
                    $mid = $m['id'];
                    foreach ($formatifMethods[$mid] as $method) {
                        $val = $scores[$sid][$semester]['formatif'][$mid][$method] ?? '';
                        echo '<td>' . ($val !== '' ? esc(number_format((float)$val, 2)) : '') . '</td>';
                    }
                }
                if ($totalFormCols > 0) {
                    $favg = $scores[$sid][$semester]['formatif_avg'] ?? '';
                    echo '<td style="background:#c7f5c4">' . ($favg !== '' ? esc(number_format((float)$favg, 2)) : '') . '</td>';
                }
                ?>

                <!-- Sumatif -->
                <?php
                foreach ($sumatifMethods as $method) {
                    // keys di controller disimpan dengan ucfirst
                    $val = $scores[$sid][$semester]['sumatif'][ucfirst($method)] ?? '';
                    echo '<td>' . ($val !== '' ? esc(number_format((float)$val, 2)) : '') . '</td>';
                }
                if ($totalSumCols > 0) {
                    $savg = $scores[$sid][$semester]['sumatif_avg'] ?? '';
                    echo '<td style="background:#ffe599">' . ($savg !== '' ? esc(number_format((float)$savg, 2)) : '') . '</td>';
                }
                ?>

                <!-- Rapor -->
                <td style="background:#cfe2f3">
                    <?= $scores[$sid][$semester]['rapor'] ?? '' ?>
                </td>

                <!-- Final -->
                <?php if ($semester == 2 && $hasFinal): ?>
                    <td style="background:#f9cb9c">
                        <?= $scores[$sid][$semester]['final'] ?? '' ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?= $this->endSection() ?>
