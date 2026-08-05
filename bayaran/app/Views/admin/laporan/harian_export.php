<table border="1" cellpadding="5" cellspacing="0">
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
    <?php foreach ($list as $row): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= esc($row['nis']) ?></td>
        <td><?= esc($row['nama']) ?></td>
        <td><?= esc($row['jenis']) ?></td>
        <td><?= $row['jumlah'] ?></td>
      </tr>
    <?php endforeach ?>
    <tr>
      <td colspan="4"><strong>Total</strong></td>
      <td><strong><?= $total ?></strong></td>
    </tr>
  </tbody>
</table>
