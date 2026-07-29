<?= $this->extend('layouts/print') ?>
<?= $this->section('content') ?>

<div class="text-center mb-4">
    <h5 class="mb-1 text-uppercase fw-bold">Data Rinci Tunggakan (Seluruh Masa)</h5>
    <div class="text-muted">
        <div>Kelas : <?= esc($kelas) ?></div>
        <div>Sampai Bulan : <?= esc($labelBulan) ?></div>
    </div>
</div>

<table class="table table-bordered table-sm">
    <thead class="text-center align-middle small fw-bold" style="background-color: #f8f9fa;">
        <tr>
            <th style="width: 40px;">NO</th>
            <th style="width: 100px;">NIS</th>
            <th>NAMA SISWA</th>
            <th>SPP</th>
            <th style="width: 40px;">BLN</th>
            <th>PPDB</th>
            <th>KEGIATAN</th>
            <th>AKHIR TAHUN</th>
            <th>TOTAL</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php
        $no = 1;
        $t_spp = $t_ppdb = $t_kegiatan = $t_akhir = $t_total = 0;
        ?>
        <?php foreach ($data as $row): ?>
            <?php
            $t_spp += $row['spp'];
            $t_ppdb += $row['ppdb'];
            $t_kegiatan += $row['kegiatan'];
            $t_akhir += $row['akhir'];
            $t_total += $row['total'];
            ?>
            <tr>
                <td class="text-center">
                    <?= $no++ ?>
                </td>
                <td class="text-center">
                    <?= esc($row['nis']) ?>
                </td>
                <td>
                    <?= esc($row['nama']) ?>
                </td>
                <td class="text-end">Rp
                    <?= number_format($row['spp'], 0, ',', '.') ?>
                </td>
                <td class="text-center">
                    <?= $row['bln'] ?>
                </td>
                <td class="text-end">Rp
                    <?= number_format($row['ppdb'], 0, ',', '.') ?>
                </td>
                <td class="text-end">Rp
                    <?= number_format($row['kegiatan'], 0, ',', '.') ?>
                </td>
                <td class="text-end">Rp
                    <?= number_format($row['akhir'], 0, ',', '.') ?>
                </td>
                <td class="text-end fw-bold">Rp
                    <?= number_format($row['total'], 0, ',', '.') ?>
                </td>
            </tr>
        <?php endforeach ?>
        <tr class="fw-bold text-end" style="background-color: #f8f9fa;">
            <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
            <td>Rp
                <?= number_format($t_spp, 0, ',', '.') ?>
            </td>
            <td></td>
            <td>Rp
                <?= number_format($t_ppdb, 0, ',', '.') ?>
            </td>
            <td>Rp
                <?= number_format($t_kegiatan, 0, ',', '.') ?>
            </td>
            <td>Rp
                <?= number_format($t_akhir, 0, ',', '.') ?>
            </td>
            <td>Rp
                <?= number_format($t_total, 0, ',', '.') ?>
            </td>
        </tr>
    </tbody>
</table>

<div class="mt-4 text-end small">
    Dicetak pada:
    <?= date('d/m/Y H:i') ?>
</div>

<?= $this->endSection() ?>