<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 pb-4">
    <h4 class="mt-3 fw-bold">💳 Informasi Pembayaran</h4>

    <?php if (!empty($error)): ?>
        <div class="alert alert-warning border-0 shadow-sm">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= esc($error) ?>
        </div>
    <?php elseif (!empty($data)): ?>

        <?php
            $siswaData = $data['siswa'] ?? [];
            $summary = $data['summary'] ?? [];
            $bulanan = $data['bulanan'] ?? [];
            $bebas = $data['bebas'] ?? [];

            // Normalisasi summary — handle format lama (string) dan baru (numeric)
            $summaryTotal = $summary['total'] ?? 0;
            $summaryDibayar = $summary['dibayar'] ?? 0;
            $summarySisa = $summary['sisa'] ?? 0;

            // Jika format lama (key total_tagihan), parse angka dari string
            if (isset($summary['total_tagihan']) && !isset($summary['total'])) {
                $summaryTotal = (float) preg_replace('/[^0-9]/', '', $summary['total_tagihan']);
                $summaryDibayar = (float) preg_replace('/[^0-9]/', '', $summary['total_dibayar'] ?? '0');
                $summarySisa = (float) preg_replace('/[^0-9]/', '', $summary['sisa_tunggakan'] ?? '0');
            }

            // Helper bulan
            $namaBulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
        ?>

        <!-- Data Siswa -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-primary text-white">Data Siswa</div>
            <div class="card-body">
                <table class="table table-borderless w-auto mb-0">
                    <tr><th>NIS</th><td>: <?= esc($siswaData['nis'] ?? $student['nis'] ?? '-') ?></td></tr>
                    <tr><th>Nama</th><td>: <?= esc($siswaData['nama'] ?? $student['name'] ?? '-') ?></td></tr>
                    <tr><th>Kelas</th><td>: <?= esc($siswaData['kelas'] ?? '-') ?></td></tr>
                </table>
            </div>
        </div>

        <!-- Rangkuman Keuangan -->
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-info text-white">Rangkuman Keuangan</div>
            <div class="card-body">
                <table class="table table-borderless w-auto mb-0">
                    <tr><th>Total Tagihan</th><td>: Rp. <?= number_format($summaryTotal, 0, ',', '.') ?></td></tr>
                    <tr><th>Dibayarkan</th><td>: Rp. <?= number_format($summaryDibayar, 0, ',', '.') ?></td></tr>
                    <tr><th>Sisa Tunggakan</th><td>: Rp. <?= number_format($summarySisa, 0, ',', '.') ?></td></tr>
                    <tr>
                        <th>Status</th>
                        <td>:
                            <?php if ($summaryDibayar == 0 && $summaryTotal > 0): ?>
                                <span class="badge bg-danger">Belum Bayar</span>
                            <?php elseif ($summarySisa > 0): ?>
                                <span class="badge bg-warning text-dark">Kurang Bayar</span>
                            <?php else: ?>
                                <span class="badge bg-success">Lunas</span>
                            <?php endif ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Pembayaran Bulanan -->
        <?php if (!empty($bulanan)): ?>
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-success text-white">Pembayaran Bulanan</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered m-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Jenis Bayaran</th>
                                <th>Kelas</th>
                                <th>Tagihan</th>
                                <th>Dibayar</th>
                                <th>Tunggakan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bulanan as $i => $b):
                                $statusClass = ($b['dibayar'] == 0) ? 'text-danger' : (($b['sisa'] > 0) ? 'text-warning' : 'text-success');
                            ?>
                            <tr>
                                <td class="<?= $statusClass ?>"><?= $i + 1 ?></td>
                                <td class="<?= $statusClass ?>"><?= esc($b['nama']) ?></td>
                                <td class="<?= $statusClass ?>"><?= esc($b['kelas_tagihan'] ?? '-') ?></td>
                                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['total'], 0, ',', '.') ?></td>
                                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['dibayar'], 0, ',', '.') ?></td>
                                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['sisa'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($b['dibayar'] == 0): ?>
                                        <span class="badge bg-danger">Belum Bayar</span>
                                    <?php elseif ($b['sisa'] > 0): ?>
                                        <span class="badge bg-warning text-dark">Kurang Bayar</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Lunas</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#detail_bulanan_<?= $b['id'] ?>">Rincian</button>
                                </td>
                            </tr>
                            <tr class="collapse" id="detail_bulanan_<?= $b['id'] ?>">
                                <td colspan="8" class="p-0">
                                    <table class="table table-sm table-bordered table-striped table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Bulan</th>
                                                <th>Tagihan</th>
                                                <th>Tgl. Bayar</th>
                                                <th>Cara Bayar</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($b['items'] as $item): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= $namaBulan[(int)($item['bulan'] ?? 0)] ?? '-' ?></td>
                                                <td>Rp. <?= number_format((float)($item['jumlah'] ?? 0), 0, ',', '.') ?></td>
                                                <td><?= !empty($item['tanggal_pembayaran']) ? date('d-m-Y', strtotime($item['tanggal_pembayaran'])) : '-' ?></td>
                                                <td><?= esc(ucfirst($item['cara_bayar_detail'] ?? $item['cara_bayar'] ?? '-')) ?></td>
                                                <td>
                                                    <?= ($item['status'] ?? '') == 'lunas'
                                                        ? '<span class="badge bg-success">Lunas</span>'
                                                        : '<span class="badge bg-danger">Belum</span>' ?>
                                                </td>
                                            </tr>
                                            <?php endforeach ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif ?>

        <!-- Pembayaran Bebas -->
        <?php if (!empty($bebas)): ?>
        <div class="card mb-4 border-0 shadow-sm">
            <div class="card-header bg-warning text-dark">Pembayaran Bebas</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered m-0">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Jenis Bayaran</th>
                                <th>Kelas</th>
                                <th>Tagihan</th>
                                <th>Dibayar</th>
                                <th>Tunggakan</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bebas as $i => $b):
                                $statusClass = ($b['dibayar'] == 0) ? 'text-danger' : (($b['sisa'] > 0) ? 'text-warning' : 'text-success');
                            ?>
                            <tr>
                                <td class="<?= $statusClass ?>"><?= $i + 1 ?></td>
                                <td class="<?= $statusClass ?>"><?= esc($b['nama']) ?></td>
                                <td class="<?= $statusClass ?>"><?= esc($b['kelas_tagihan'] ?? '-') ?></td>
                                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['total'], 0, ',', '.') ?></td>
                                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['dibayar'], 0, ',', '.') ?></td>
                                <td class="<?= $statusClass ?>">Rp. <?= number_format($b['sisa'], 0, ',', '.') ?></td>
                                <td>
                                    <?php if ($b['dibayar'] == 0): ?>
                                        <span class="badge bg-danger">Belum Bayar</span>
                                    <?php elseif ($b['sisa'] > 0): ?>
                                        <span class="badge bg-warning text-dark">Kurang Bayar</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Lunas</span>
                                    <?php endif ?>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#detail_bebas_<?= $b['id'] ?>">Rincian</button>
                                </td>
                            </tr>
                            <tr class="collapse" id="detail_bebas_<?= $b['id'] ?>">
                                <td colspan="8" class="p-0 bg-light">
                                    <table class="table table-sm table-bordered table-striped table-hover mb-0">
                                        <thead class="table-light">
                                            <tr>
                                                <th>No</th>
                                                <th>Tgl. Bayar</th>
                                                <th>Jml. Bayar</th>
                                                <th>Cara Bayar</th>
                                                <th>Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($b['pembayaran'] ?? [])): ?>
                                                <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat pembayaran</td></tr>
                                            <?php else: ?>
                                                <?php $no = 1; foreach ($b['pembayaran'] as $p): ?>
                                                <tr>
                                                    <td><?= $no++ ?></td>
                                                    <td><?= date('d-m-Y', strtotime($p['tanggal'])) ?></td>
                                                    <td>Rp. <?= number_format((float)$p['jumlah_bayar'], 0, ',', '.') ?></td>
                                                    <td><?= esc(ucfirst($p['cara_bayar'] ?? '-')) ?></td>
                                                    <td><?= esc($p['keterangan'] ?? '-') ?></td>
                                                </tr>
                                                <?php endforeach ?>
                                            <?php endif ?>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                            <?php endforeach ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif ?>

        <?php if (empty($bulanan) && empty($bebas)): ?>
            <div class="alert alert-info border-0 shadow-sm">
                <i class="bi bi-info-circle me-2"></i>Belum ada data tagihan yang tersedia.
            </div>
        <?php endif ?>

    <?php else: ?>
        <div class="alert alert-info border-0 shadow-sm">
            <i class="bi bi-info-circle me-2"></i>Belum ada data pembayaran yang tersedia.
        </div>
    <?php endif; ?>
</div>

<?= $this->endSection() ?>
