<table class="table table-bordered table-striped align-middle text-center">
    <thead class="table-light">
        <tr>
            <th rowspan="2">No</th>
            <th rowspan="2">Nama Siswa</th>

            <?php if (!empty($visibleMaterials)) : ?>
                <th colspan="<?= array_sum(array_map(fn($m) => count($formatifMethods[$m['id']] ?? []), $visibleMaterials)) + 1 ?>">
                    Formatif
                </th>
            <?php endif; ?>

            <?php if (!empty($sumatifMethods)) : ?>
                <th colspan="<?= count($sumatifMethods) + 1 ?>">Sumatif</th>
            <?php endif; ?>

            <th rowspan="2">Nilai Rapor</th>
            <?php if ($semester == 2 && $hasFinal) : ?>
                <th rowspan="2">Nilai Final</th>
            <?php endif; ?>
        </tr>
        <tr>
            <?php foreach ($visibleMaterials as $idx => $m) : ?>
                <?php foreach ($formatifMethods[$m['id']] as $method) : ?>
                    <th><?= 'M' . ($idx + 1) . " ($method)" ?></th>
                <?php endforeach; ?>
            <?php endforeach; ?>
            <?php if (!empty($visibleMaterials)) : ?>
                <th>Rerata</th>
            <?php endif; ?>

            <?php foreach ($sumatifMethods as $method) : ?>
                <th><?= ucfirst($method) ?></th>
            <?php endforeach; ?>
            <?php if (!empty($sumatifMethods)) : ?>
                <th>Rerata</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($students as $i => $stu) : ?>
            <?php $sid = $stu['id']; ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td class="text-start"><?= esc($stu['name']) ?></td>

                <?php foreach ($visibleMaterials as $m) : ?>
                    <?php foreach ($formatifMethods[$m['id']] as $method) : ?>
                        <td><?= $scores[$sid][$semester]['formatif'][$m['id']][$method] ?? '-' ?></td>
                    <?php endforeach; ?>
                <?php endforeach; ?>
                <?php if (!empty($visibleMaterials)) : ?>
                    <td><strong><?= $scores[$sid][$semester]['formatif_avg'] ?? '-' ?></strong></td>
                <?php endif; ?>

                <?php foreach ($sumatifMethods as $method) : ?>
                    <td><?= $scores[$sid][$semester]['sumatif'][ucfirst($method)] ?? '-' ?></td>
                <?php endforeach; ?>
                <?php if (!empty($sumatifMethods)) : ?>
                    <td><strong><?= $scores[$sid][$semester]['sumatif_avg'] ?? '-' ?></strong></td>
                <?php endif; ?>

                <td><strong><?= $scores[$sid][$semester]['rapor'] ?? '-' ?></strong></td>

                <?php if ($semester == 2 && $hasFinal) : ?>
                    <td><strong><?= $scores[$sid][$semester]['final'] ?? '-' ?></strong></td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
