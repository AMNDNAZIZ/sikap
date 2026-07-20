<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
  th.active-date {
    background-color: #0d6efd !important;
    color: white !important;
  }

  .text-success {
    color: #198754;
  }

  .text-warning {
    color: #ffc107;
  }

  .text-danger {
    color: #dc3545;
  }

  b.status-text.editable {
    cursor: pointer;
    text-decoration: underline;
  }

  .instruction-box {
    border: 1px solid #ced4da;
    background-color: #e9ecef;
    padding: 12px 16px;
    margin-bottom: 15px;
    border-radius: 4px;
    font-size: 14px;
    color: #495057;
  }

  .small-note {
    font-size: 13px;
    color: #6c757d;
    margin-bottom: 8px;
  }
</style>

<div class="instruction-box">
  <strong>Petunjuk:</strong> Pilih bulan untuk menampilkan data absensi. Klik tanggal berwarna biru untuk mengisi atau
  mengubah status absensi siswa pada tanggal tersebut.
</div>

<?php if (isset($studentStats)): ?>
  <div class="alert alert-info d-flex align-items-center mb-3 py-2">
    <i class="bi bi-people-fill me-2 fs-5"></i>
    <div class="d-flex gap-4">
      <div><strong>Total Siswa:</strong> <?= $studentStats['total'] ?></div>
      <div class="vr"></div>
      <div><strong>Laki-laki:</strong> <?= $studentStats['L'] ?></div>
      <div class="vr"></div>
      <div><strong>Perempuan:</strong> <?= $studentStats['P'] ?></div>
    </div>
  </div>
<?php endif; ?>

<div class="d-flex justify-content-between mb-3">
  <!-- Filter Bulanan -->
  <form class="d-flex gap-2" method="get" action="<?= base_url('admin/attendance/view') ?>">
    <input type="month" class="form-control" name="month" value="<?= esc($month) ?>">
    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
    <button class="btn btn-secondary text-nowrap">Tampilkan Bulanan</button>
  </form>

  <!-- Filter Mingguan -->
  <form class="d-flex gap-2" method="get" action="<?= base_url('admin/attendance/week') ?>">
    <input type="week" class="form-control" name="week" value="<?= esc($week ?? date('Y-\WW')) ?>">
    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
    <button class="btn btn-info text-nowrap">Tampilkan Mingguan</button>
  </form>
</div>

<div class="mb-3 d-flex gap-2">
  <a href="<?= base_url('admin/attendance/pdf?class_id=' . (int) $class['id'] . '&month=' . $month) ?>" target="_blank"
    class="btn btn-danger">
    <i class="bi bi-file-earmark-pdf"></i> Cetak PDF
  </a>
  <a href="<?= base_url('admin/attendance/excel?class_id=' . (int) $class['id'] . '&month=' . $month) ?>"
    class="btn btn-success">
    <i class="bi bi-file-earmark-excel"></i> Ekspor Excel
  </a>

  <!-- ================== TOMBOL REKAP (DI BAWAH TABEL) ================== -->

  <div class="btn-group">
    <button type="button" class="btn btn-primary dropdown-toggle" data-bs-toggle="dropdown">
      Rekap Absensi
    </button>

    <ul class="dropdown-menu">
      <?php $cid = (int) $class['id']; ?>
      <li><a class="dropdown-item" href="<?= base_url("admin/attendance/rekap?class_id={$cid}&periode=semester1") ?>">Semester 1 (Juli–Desember)</a></li>
      <li><a class="dropdown-item" href="<?= base_url("admin/attendance/rekap?class_id={$cid}&periode=semester2") ?>">Semester 2 (Januari–Juni)</a></li>
      <li><a class="dropdown-item" href="<?= base_url("admin/attendance/rekap?class_id={$cid}&periode=tahun") ?>">Satu Tahun (Juli–Juni)</a></li>
    </ul>
  </div>
</div>

<h4>Absensi Bulan <?= date('F Y', strtotime($month . '-01')) ?> - <?= esc($class['name']) ?></h4>

<form method="post" action="<?= base_url('admin/attendance/save') ?>" id="attendance-form">
  <?= csrf_field() ?>
  <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
  <input type="hidden" name="date" id="att-date" value="">

  <?php $today = date('Y-m-d'); ?>
  <div class="table-responsive">
    <table class="table table-bordered align-middle text-center" id="attendance-table">
      <thead class="table-success">
        <tr>
          <th>No</th>
          <th>Induk</th>
          <th>Nama Siswa</th>
          <th>JK</th>
          <?php foreach ($dates as $d):
            // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
            $isWeekend = is_weekend($d, $schoolDays);
            $isHoliday = $isWeekend || isset($holidays[$d]);
            $cls = $isHoliday ? 'bg-danger text-white' : 'bg-teal';
            // Tooltip: keterangan libur
            $holidayDesc = '';
            if ($isWeekend) {
                $holidayDesc = (date('N', strtotime($d)) == 6) ? 'Sabtu' : 'Minggu';
            } elseif (isset($holidays[$d])) {
                $holidayDesc = $holidays[$d];
            }
            ?>
            <th class="<?= $cls ?>" data-date="<?= $d ?>"<?= $holidayDesc ? ' title="' . esc($holidayDesc) . '"' : '' ?>>
              <?php if ($d <= $today && !$isHoliday && session()->get('user')['role_id'] != 2): ?>
                <a href="#" class="date-link" data-date="<?= $d ?>"><?= date('d', strtotime($d)) ?></a>
              <?php else: ?>
                <span class="disabled text-white"><?= date('d', strtotime($d)) ?></span>
              <?php endif; ?>
            </th>
          <?php endforeach; ?>
          <th>H</th>
          <th>I</th>
          <th>S</th>
          <th>A</th>
          <th>%</th>
        </tr>
      </thead>

      <tbody id="att-body">
        <?php $no = 1;
        foreach ($students as $s):
          $H = $I = $S = $A = 0; ?>
          <tr data-student="<?= $s['id'] ?>">
            <td><?= $no++ ?></td>
            <td><?= esc($s['nis'] ?? '-') ?></td>
            <td style="text-align:left; white-space:nowrap"><?= esc($s['name']) ?></td>
            <td><?= esc($s['gender']) ?></td>

            <?php foreach ($dates as $d):
              // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
              $isWeekend = is_weekend($d, $schoolDays);
              $isHoliday = $isWeekend || isset($holidays[$d]);

              $val = $attMap[$s['id']][$d] ?? null;

              if ($isHoliday) {
                $val = '-';
              } elseif ($d <= $today) {
                $val = $val ?? 'H';
              } else {
                $val = '-';
              }

              if ($val === 'H')
                $H++;
              elseif ($val === 'I')
                $I++;
              elseif ($val === 'S')
                $S++;
              elseif ($val === 'A')
                $A++;
              ?>
              <td class="<?= $isHoliday ? 'bg-danger text-white' : '' ?>" data-student="<?= $s['id'] ?>"
                data-date="<?= $d ?>" data-value="<?= $val ?>">
                <?php if ($val === '-'): ?>
                  -
                <?php else: ?>
                  <?php
                  $colorClass = '';
                  if ($val === 'H')
                    $colorClass = 'text-success';
                  elseif ($val === 'I' || $val === 'S')
                    $colorClass = 'text-warning';
                  elseif ($val === 'A')
                    $colorClass = 'text-danger';
                  ?>
                  <b class="status-text <?= $colorClass ?>" data-student="<?= $s['id'] ?>"
                    data-date="<?= $d ?>"><?= $val ?></b>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>

            <?php
            $workDays = 0;
            foreach ($dates as $d) {
              // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
              $isWeekend = is_weekend($d, $schoolDays);
              $isHoliday = $isWeekend || isset($holidays[$d]);
              if ($d <= $today && !$isHoliday)
                $workDays++;
            }
            $percent = $workDays ? round(($H / $workDays) * 100, 1) : 0;
            ?>
            <td><b class="text-success"><?= $H ?></b></td>
            <td><b class="text-warning"><?= $I ?></b></td>
            <td><b class="text-warning"><?= $S ?></b></td>
            <td><b class="text-danger"><?= $A ?></b></td>
            <td><b><?= $percent ?>%</b></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <?php
    $totalH = $totalI = $totalS = $totalA = 0;
    foreach ($students as $s) {
      foreach ($dates as $d) {
        // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
        $isWeekend = is_weekend($d, $schoolDays);
        $isHoliday = $isWeekend || isset($holidays[$d]);
        $val = $attMap[$s['id']][$d] ?? null;

        if ($isHoliday) {
          $val = '-';
        } elseif ($d <= $today) {
          $val = $val ?? 'H';
        } else {
          $val = '-';
        }

        if ($val === 'H')
          $totalH++;
        elseif ($val === 'I')
          $totalI++;
        elseif ($val === 'S')
          $totalS++;
        elseif ($val === 'A')
          $totalA++;
      }
    }
    $totalAll = $totalH + $totalI + $totalS + $totalA;
    $percentClass = $totalAll ? round(($totalH / $totalAll) * 100, 1) : 0;
    ?>

    <div class="alert alert-info mt-3">
      <b>Rekap Kelas:</b>
      H: <span class="text-success"><?= $totalH ?></span>,
      I: <span class="text-warning"><?= $totalI ?></span>,
      S: <span class="text-warning"><?= $totalS ?></span>,
      A: <span class="text-danger"><?= $totalA ?></span>
      | <b>Persentase Hadir:</b> <?= $percentClass ?>%
      <div class="small-note">Perhitungan berdasarkan hari efektif (Senin–Jumat), mengecualikan hari libur sekolah.
        Rekap/periode dihitung sampai tanggal hari ini (<?= date('d M Y', strtotime($today)) ?>).</div>
    </div>
  </div>

  <?php if (session()->get('user')['role_id'] != 2): ?>
    <div class="d-flex align-items-center gap-2 mt-3">
      <button class="btn btn-primary" id="btn-save" disabled>Simpan Absensi</button>
    </div>
  <?php endif; ?>
</form>

<script>
  const today = '<?= $today ?>';
  let activeDate = null;

  function resetTable() {
    document.querySelectorAll('thead th[data-date]').forEach(th => {
      th.classList.remove('active-date');
    });

    document.querySelectorAll('tbody td[data-date]').forEach(td => {
      const date = td.dataset.date;
      let val = td.getAttribute('data-value');
      const isHoliday = td.classList.contains('bg-danger');

      if (isHoliday) {
        td.innerHTML = '-';
        return;
      }

      if (!val || val === '-') {
        if (date <= today) {
          val = 'H';
        } else {
          val = '-';
        }
      }

      td.setAttribute('data-value', val);

      if (val === '-') {
        td.innerHTML = `<b class="status-text" data-student="${td.dataset.student}" data-date="${date}">-</b>`;
      } else {
        let colorClass = '';
        if (val === 'H') colorClass = 'text-success';
        else if (val === 'I' || val === 'S') colorClass = 'text-warning';
        else if (val === 'A') colorClass = 'text-danger';

        td.innerHTML = `<b class="status-text ${colorClass}" data-student="${td.dataset.student}" data-date="${date}">${val}</b>`;
      }
      const b = td.querySelector('b');
      if (b) b.classList.remove('editable');
    });

    const btn = document.getElementById('btn-save');
    if (btn) btn.disabled = true;
    document.getElementById('att-date').value = '';
    activeDate = null;
  }

  function activateDate(date) {
    activeDate = date;
    document.getElementById('att-date').value = date;

    document.querySelectorAll('thead th[data-date]').forEach(th => {
      th.classList.toggle('active-date', th.dataset.date === date);
    });

    document.querySelectorAll(`tbody td[data-date]`).forEach(td => {
      const tdDate = td.dataset.date;
      let val = td.getAttribute('data-value');
      const isHoliday = td.classList.contains('bg-danger');

      if (isHoliday) return;

      if (tdDate === date) {
        if (!val || val === '-') {
          val = (tdDate <= today) ? 'H' : '-';
          td.setAttribute('data-value', val);
        }

        if (val !== '-') {
          let colorClass = '';
          if (val === 'H') colorClass = 'text-success';
          else if (val === 'I' || val === 'S') colorClass = 'text-warning';
          else if (val === 'A') colorClass = 'text-danger';

          td.innerHTML = `<b class="status-text editable ${colorClass}" data-student="${td.dataset.student}" data-date="${date}">${val}</b>`;
        } else {
          td.innerHTML = `<b class="status-text" data-student="${td.dataset.student}" data-date="${date}">-</b>`;
        }
      } else {
        if (!val || val === '-') {
          td.innerHTML = `<b class="status-text" data-student="${td.dataset.student}" data-date="${tdDate}">-</b>`;
        } else {
          let colorClass = '';
          if (val === 'H') colorClass = 'text-success';
          else if (val === 'I' || val === 'S') colorClass = 'text-warning';
          else if (val === 'A') colorClass = 'text-danger';

          td.innerHTML = `<b class="status-text ${colorClass}" data-student="${td.dataset.student}" data-date="${tdDate}">${val}</b>`;
        }
      }
    });

    const btn = document.getElementById('btn-save');
    if (btn) btn.disabled = false;
  }

  // tanggal link klik -> aktifkan mode edit per-tanggal
  document.querySelectorAll('.date-link').forEach(link => {
    link.addEventListener('click', function (e) {
      e.preventDefault();
      const date = this.dataset.date;
      if (date > today) return;
      resetTable();
      activateDate(date);
    });
  });

  // klik pada sel editable membuka select untuk ubah status
  document.getElementById('attendance-table').addEventListener('click', function (e) {
    if (e.target.classList.contains('status-text') && e.target.classList.contains('editable')) {
      const b = e.target;
      const studentId = b.dataset.student;
      const date = b.dataset.date;
      const currentVal = b.textContent;
      if (date !== activeDate) return;

      const td = b.parentElement;
      td.innerHTML = `
        <select name="status[${studentId}]" class="form-select form-select-sm" autofocus>
          <option value="H" ${currentVal === 'H' ? 'selected' : ''}>H</option>
          <option value="I" ${currentVal === 'I' ? 'selected' : ''}>I</option>
          <option value="S" ${currentVal === 'S' ? 'selected' : ''}>S</option>
          <option value="A" ${currentVal === 'A' ? 'selected' : ''}>A</option>
        </select>
      `;

      const select = td.querySelector('select');
      select.focus();

      select.addEventListener('change', function () {
        const val = this.value;
        td.setAttribute('data-value', val);
        let colorClass = '';
        if (val === 'H') colorClass = 'text-success';
        else if (val === 'I' || val === 'S') colorClass = 'text-warning';
        else if (val === 'A') colorClass = 'text-danger';
        td.innerHTML = `<b class="status-text editable ${colorClass}" data-student="${studentId}" data-date="${date}">${val}</b>`;
      });

      select.addEventListener('blur', function () {
        const val = this.value;
        td.setAttribute('data-value', val);
        let colorClass = '';
        if (val === 'H') colorClass = 'text-success';
        else if (val === 'I' || val === 'S') colorClass = 'text-warning';
        else if (val === 'A') colorClass = 'text-danger';
        td.innerHTML = `<b class="status-text editable ${colorClass}" data-student="${studentId}" data-date="${date}">${val}</b>`;
      });
    }
  });

  // saat submit form, kumpulkan status[student_id] hanya untuk tanggal aktif
  document.getElementById('attendance-form').addEventListener('submit', function (e) {
    if (!activeDate) {
      e.preventDefault();
      alert('Silakan pilih tanggal absensi terlebih dahulu.');
      return false;
    }
    
    // Refresh CSRF token sebelum submit (untuk mencegah token expired)
    const csrfInput = this.querySelector('input[name="csrf_test_name"]');
    if (csrfInput) {
      // Get fresh CSRF token from meta tag
      const csrfMeta = document.querySelector('meta[name="csrf_test_name"]');
      if (csrfMeta) {
        csrfInput.value = csrfMeta.getAttribute('content');
      }
    }
    
    // hapus input status tersembunyi yang mungkin tersisa
    document.querySelectorAll('input[name^="status["]').forEach(el => el.remove());
    document.querySelectorAll(`tbody td[data-date="${activeDate}"]`).forEach(td => {
      const studentId = td.dataset.student;
      const val = td.getAttribute('data-value');
      if (val && val !== '-') {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = `status[${studentId}]`;
        input.value = val;
        this.appendChild(input);
      }
    });
  });

  // inisialisasi
  resetTable();
</script>

<?= $this->endSection() ?>