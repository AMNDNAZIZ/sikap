<?= $this->extend('layouts/main') ?>
<?= $this->section('content') ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 font-outfit">Rekap Rinci Tunggakan</h5>
    </div>
    <div class="card-body">
        <form method="get" class="row g-3 mb-4">
            <div class="col-md-5">
                <label class="form-label">Pilih Kelas</label>
                <select name="kelas_id" class="form-select" required>
                    <option value="">Pilih Kelas</option>
                    <?php foreach ($kelas as $k): ?>
                        <option value="<?= $k['id'] ?>" <?= $filter['kelas_id'] == $k['id'] ? 'selected' : '' ?>>
                            <?= esc($k['nama_kelas']) ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label">Sampai Bulan</label>
                <select name="sampai_bulan" class="form-select" required>
                    <?php
                    $months = ['Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni'];
                    foreach ($months as $idx => $m): ?>
                        <option value="<?= $idx + 1 ?>" <?= $filter['sampai_bulan'] == ($idx + 1) ? 'selected' : '' ?>>
                            <?= $m ?>
                        </option>
                    <?php endforeach ?>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="bi bi-search me-1"></i> Tampilkan
                </button>
            </div>
        </form>

        <?php if ($filter['kelas_id']): ?>
            <div class="d-flex justify-content-end mb-3">
                <a href="<?= site_url("admin/laporan/rekap-tunggakan/print?kelas_id={$filter['kelas_id']}&sampai_bulan={$filter['sampai_bulan']}") ?>"
                    class="btn btn-warning" target="_blank">
                    <i class="bi bi-printer me-1"></i> Cetak Rekap
                </a>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-hover align-middle">
                    <thead class="table-light text-center small fw-bold">
                        <tr>
                            <th style="width: 50px;">NO</th>
                            <th style="width: 120px;">NIS</th>
                            <th>NAMA SISWA</th>
                            <th>SPP</th>
                            <th style="width: 60px;">BLN</th>
                            <th>PPDB</th>
                            <th>KEGIATAN</th>
                            <th>AKHIR TAHUN</th>
                            <th>TOTAL</th>
                            <th style="width: 80px;">AKSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-4 text-muted">Tidak ada data tunggakan untuk filter ini.
                                </td>
                            </tr>
                        <?php else: ?>
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
                                    <td class="text-end text-nowrap">
                                        <?= number_format($row['spp'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $row['bln'] ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?= number_format($row['ppdb'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?= number_format($row['kegiatan'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end text-nowrap">
                                        <?= number_format($row['akhir'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-end text-nowrap fw-bold">
                                        <?= number_format($row['total'], 0, ',', '.') ?>
                                    </td>
                                    <td class="text-center">
                                        <a href="<?= site_url("admin/laporan/rekap-tunggakan/print-siswa?id={$row['id']}&sampai_bulan={$filter['sampai_bulan']}") ?>"
                                            class="btn btn-sm btn-outline-primary" target="_blank" title="Cetak Surat Tunggakan">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            <tr class="table-light fw-bold text-end">
                                <td colspan="3" class="text-center">TOTAL KESELURUHAN</td>
                                <td>
                                    <?= number_format($t_spp, 0, ',', '.') ?>
                                </td>
                                <td></td>
                                <td>
                                    <?= number_format($t_ppdb, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <?= number_format($t_kegiatan, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <?= number_format($t_akhir, 0, ',', '.') ?>
                                </td>
                                <td>
                                    <?= number_format($t_total, 0, ',', '.') ?>
                                </td>
                                <td></td>
                            </tr>
                        <?php endif ?>
                    </tbody>
                </table>
            </div>
        <?php endif ?>
    </div>
</div>

<?= $this->endSection() ?>