<table border="1" cellpadding="4" cellspacing="0">
  <thead>
    <tr>
      <th>No</th>
      <th>NIS</th>
      <th>Nama</th>
      <th>Total</th>
      <th>Dibayar</th>
      <th>Sisa</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php $i = 1 ?>
    <?php foreach ($data as $row): ?>
      <tr>
        <td><?= $i++ ?></td>
        <td><?= $row['siswa']['nis'] ?></td>
        <td><?= $row['siswa']['nama'] ?></td>
        <td><?= $row['total'] ?></td>
        <td><?= $row['dibayar'] ?></td>
        <td><?= $row['sisa'] ?></td>
        <td><?= $row['status'] ?></td>
      </tr>
    <?php endforeach ?>
  </tbody>
</table>
