<?= $this->extend('layouts/print') ?>
<?= $this->section('content') ?>

<?php foreach ($reportData as $index => $data): ?>
    <div class="<?= $index > 0 ? 'page-break' : '' ?>">
        <h5>DATA TUNGGAKAN PEMBAYARAN</h5>

        <table class="table table-borderless table-sm w-auto mb-4">
            <tr>
                <td style="width:100px">NAMA</td>
                <td>:
                    <?= esc($data['siswa']['nama']) ?>
                </td>
            </tr>
            <tr>
                <td>NIS</td>
                <td>:
                    <?= esc($data['siswa']['nis']) ?>
                </td>
            </tr>
            <tr>
                <td>KELAS</td>
                <td>:
                    <?= esc($kelas) ?>
                </td>
            </tr>
        </table>

        <table class="table table-bordered table-sm">
            <thead class="table-light">
                <tr>
                    <th class="text-center">No</th>
                    <th>Jenis Bayaran</th>
                    <th class="text-end">Tagihan</th>
                    <th class="text-end">Dibayar</th>
                    <th class="text-end">Tunggakan</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $totalTagihan = 0;
                $totalBayar = 0;
                $totalSisa = 0;
                ?>
                <?php foreach ($data['tunggakan'] as $row): ?>
                    <?php
                    $tagihan = $row['tagihan'];
                    $dibayar = $row['dibayar'];
                    $sisa = $row['sisa'];
                    $totalTagihan += $tagihan;
                    $totalBayar += $dibayar;
                    $totalSisa += $sisa;
                    ?>
                    <tr>
                        <td class="text-center">
                            <?= $no++ ?>
                        </td>
                        <td>
                            <?= esc($row['jenis']) ?>
                        </td>
                        <td class="text-end">Rp
                            <?= number_format($tagihan, 0, ',', '.') ?>
                        </td>
                        <td class="text-end">Rp
                            <?= number_format($dibayar, 0, ',', '.') ?>
                        </td>
                        <td class="text-end">Rp
                            <?= number_format($sisa, 0, ',', '.') ?>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <tr class="fw-bold table-light">
                    <td colspan="2" class="text-end">Total</td>
                    <td class="text-end">Rp
                        <?= number_format($totalTagihan, 0, ',', '.') ?>
                    </td>
                    <td class="text-end">Rp
                        <?= number_format($totalBayar, 0, ',', '.') ?>
                    </td>
                    <td class="text-end">Rp
                        <?= number_format($totalSisa, 0, ',', '.') ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <?php if (!empty($infoBulan)): ?>
            <div class="text-muted mt-2 small">
                <?= $infoBulan ?>
            </div>
        <?php endif ?>
    </div>
<?php endforeach ?>

<style>
    @media print {
        .page-break {
            page-break-before: always;
        }
    }
</style>

<?= $this->endSection() ?>