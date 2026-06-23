<?php
$bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
$tgl = date('d', strtotime($mutation['mutation_date'])) . ' ' . $bulan[(int)date('m', strtotime($mutation['mutation_date']))] . ' ' . date('Y', strtotime($mutation['mutation_date']));
?>
<!DOCTYPE html>
<html>
<head>
  <style>
    body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; line-height: 1.5; }
    .surat-judul { text-align: center; margin-bottom: 15px; }
    .surat-judul .judul { font-size: 13px; font-weight: bold; text-decoration: underline; }
    .surat-judul .nomor { font-size: 11px; margin-top: 3px; }
    .pembuka { margin-bottom: 8px; }
    .isi { margin-bottom: 8px; }
    .penutup { margin-bottom: 15px; }
    table.data-tabel { border-collapse: collapse; margin-bottom: 8px; }
    table.data-tabel td { padding: 2px 4px; font-size: 11px; vertical-align: top; }
    table.data-tabel td.label { width: 140px; }
    table.data-tabel td.colon { width: 12px; text-align: center; }
    .ttd-wrapper { float: right; text-align: center; width: 220px; font-size: 11px; }
    .ttd-line { display: block; height: 60px; }
    .ttd-clearfix { clear: both; }
  </style>
</head>
<body>

  <div class="surat-judul">
    <div class="judul">SURAT KETERANGAN MUTASI MASUK</div>
    <div class="nomor">Nomor : <?= esc($mutation['letter_number'] ?? '.................') ?></div>
  </div>

  <div class="pembuka">Yang bertanda tangan di bawah ini :</div>

  <table class="data-tabel">
    <tr><td class="label">Nama</td><td class="colon">:</td><td><?= esc($school['principal_name'] ?? '................................') ?></td></tr>
    <tr><td class="label">NIP</td><td class="colon">:</td><td><?= esc($school['principal_nip'] ?? '................................') ?></td></tr>
    <tr><td class="label">Jabatan</td><td class="colon">:</td><td>Kepala Sekolah</td></tr>
    <tr><td class="label">Tempat Tugas</td><td class="colon">:</td><td><?= esc($school['name'] ?? '................................') ?></td></tr>
  </table>

  <div class="pembuka">Dengan ini menerangkan bahwa :</div>

  <table class="data-tabel">
    <tr><td class="label">Nama</td><td class="colon">:</td><td><strong><?= esc($mutation['student_name'] ?? '-') ?></strong></td></tr>
    <tr><td class="label">NISN</td><td class="colon">:</td><td><?= esc($mutation['nisn'] ?? '-') ?></td></tr>
    <tr>
      <td class="label">Tempat/Tanggal Lahir</td>
      <td class="colon">:</td>
      <td><?= esc($mutation['birth_place'] ?? '') ?><?= !empty($mutation['birth_date']) ? ', ' . date('d/m/Y', strtotime($mutation['birth_date'])) : '' ?></td>
    </tr>
    <tr><td class="label">Sekolah Asal</td><td class="colon">:</td><td><?= esc($mutation['from_school'] ?? '-') ?></td></tr>
    <tr><td class="label">Kelas Diterima</td><td class="colon">:</td><td><?= esc($mutation['to_class_name'] ?? '-') ?></td></tr>
  </table>

  <div class="isi">
    Nama tersebut di atas telah kami nyatakan <strong>DITERIMA</strong> sebagai siswa pindahan di
    <strong><?= esc($school['name'] ?? '................................') ?></strong>
    pada tanggal <?= $tgl ?>.
  </div>

  <div class="penutup">Demikian Surat Keterangan ini dibuat untuk dapat dipergunakan sebagaimana mestinya.</div>

  <div class="ttd-wrapper">
    <?= $school['city'] ?? 'Jakarta' ?>, <?= $tgl ?><br>
    Kepala <?= esc($school['name'] ?? '................................') ?>
    <span class="ttd-line"></span>
    <strong><?= esc($school['principal_name'] ?? '................................') ?></strong><br>
    NIP. <?= esc($school['principal_nip'] ?? '................................') ?>
  </div>
  <div class="ttd-clearfix"></div>

</body>
</html>
