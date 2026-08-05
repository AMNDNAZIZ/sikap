<table border="1" cellpadding="4" cellspacing="0">
  <thead>
    <tr>
      <th>No</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>Jenis Bayaran</th>
      <th>Jumlah</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1 ?>
    <?php foreach ($data as $kelas => $siswaData): ?>
      <tr><td colspan="5"><strong>Kelas: <?= $kelas ?></strong></td></tr>
      <?php $sub = 0 ?>
      <?php foreach ($siswaData as $siswa => $jenisList): ?>
        <?php foreach ($jenisList as $jenis => $jumlah): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><?= '' ?></td>
            <td><?= $siswa ?></td>
            <td><?= $jenis ?></td>
            <td><?= $jumlah ?></td>
          </tr>
          <?php $sub += $jumlah ?>
        <?php endforeach ?>
      <?php endforeach ?>
      <tr><td colspan="4"><strong>Total Kelas</strong></td><td><strong><?= $sub ?></strong></td></tr>
    <?php endforeach ?>
  </tbody>
</table>
