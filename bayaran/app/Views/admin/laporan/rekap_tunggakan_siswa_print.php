<?= $this->extend('layouts/print') ?>
<?= $this->section('content') ?>

<div class="text-center mb-4">
    <h5 class="mb-1 text-uppercase fw-bold">Pemberitahuan Tunggakan Pembayaran</h5>

</div>

<div class="mb-4 small">
    <table class="table table-borderless table-sm w-auto">
        <tr>
            <td style="width: 120px;">NIS</td>
            <td style="width: 10px;">:</td>
            <td class="fw-bold"><?= esc($data['nis']) ?></td>
        </tr>
        <tr>
            <td>Nama Siswa</td>
            <td>:</td>
            <td class="fw-bold"><?= esc($data['nama']) ?></td>
        </tr>
        <tr>
            <td>Kelas</td>
            <td>:</td>
            <td><?= esc($kelas) ?></td>
        </tr>
    </table>
</div>

<p class="small">Berikut adalah rincian tunggakan pembayaran sekolah untuk siswa tersebut di atas:</p>

<table class="table table-bordered table-sm">
    <thead class="text-center align-middle small fw-bold" style="background-color: #f8f9fa;">
        <tr>
            <th style="width: 200px;">JENIS PEMBAYARAN</th>
            <th>KETERANGAN</th>
            <th style="width: 150px;">JUMLAH TUNGGAKAN</th>
        </tr>
    </thead>
    <tbody class="small">
        <?php if ($data['spp'] > 0): ?>
            <tr>
                <td class="ps-2">SPP</td>
                <td class="ps-2">Tunggakan selama <?= $data['bln'] ?> bulan</td>
                <td class="text-end pe-2">Rp <?= number_format($data['spp'], 0, ',', '.') ?></td>
            </tr>
        <?php endif ?>

        <?php if ($data['ppdb'] > 0): ?>
            <tr>
                <td class="ps-2">PPDB / Pangkal</td>
                <td class="ps-2">-</td>
                <td class="text-end pe-2">Rp <?= number_format($data['ppdb'], 0, ',', '.') ?></td>
            </tr>
        <?php endif ?>

        <?php if ($data['kegiatan'] > 0): ?>
            <tr>
                <td class="ps-2">KEGIATAN / Buku</td>
                <td class="ps-2">-</td>
                <td class="text-end pe-2">Rp <?= number_format($data['kegiatan'], 0, ',', '.') ?></td>
            </tr>
        <?php endif ?>

        <?php if ($data['akhir'] > 0): ?>
            <tr>
                <td class="ps-2">AKHIR TAHUN</td>
                <td class="ps-2">-</td>
                <td class="text-end pe-2">Rp <?= number_format($data['akhir'], 0, ',', '.') ?></td>
            </tr>
        <?php endif ?>

        <tr class="fw-bold bg-light">
            <td colspan="2" class="text-center">TOTAL TUNGGAKAN</td>
            <td class="text-end pe-2">Rp <?= number_format($data['total'], 0, ',', '.') ?></td>
        </tr>
    </tbody>
</table>

<div class="small mt-2">
    <strong>Catatan:</strong> Tunggakan dihitung sampai dengan bulan <strong><?= esc($labelBulan) ?></strong>.
</div>

<div class="mt-5 small">
    <div class="row">
        <div class="col-7"></div>
        <div class="col-5 text-center">
            <p>Dicetak pada, <?= date('d/m/Y H:i') ?></p>
            <br><br><br><br>
            <p class="fw-bold mb-0">( ____________________ )</p>
            <p>Bendahara Sekolah</p>
        </div>
    </div>
</div>

<style>
    @media print {

        .table-sm td,
        .table-sm th {
            padding: 0.3rem !important;
        }
    }
</style>

<?= $this->endSection() ?>