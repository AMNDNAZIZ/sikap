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
</style>

<div class="instruction-box">
  <strong>Petunjuk:</strong> Klik tanggal berwarna biru untuk mengisi atau mengubah status absensi siswa pada minggu
  ini.
</div>

<div class="d-flex justify-content-between mb-3">
  <form class="d-flex gap-2" method="get" action="<?= base_url('admin/attendance/week') ?>">
    <input type="week" class="form-control" name="week" value="<?= esc($week) ?>">
    <input type="hidden" name="class_id" value="<?= (int) $class['id'] ?>">
    <button class="btn btn-secondary">Tampilkan</button>
  </form>
  <a href="<?= base_url('admin/attendance/view?class_id=' . (int) $class['id'] . '&month=' . date('Y-m', strtotime($weekDates[0]))) ?>"
    class="btn btn-secondary">
    <i class="bi bi-calendar-month"></i> Lihat Bulanan
  </a>
</div>



<h4>Absensi Minggu <?= esc($week) ?> - Kelas <?= esc($class['name']) ?></h4>

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
          <?php foreach ($weekDates as $d):
            // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
            $isWeekend = is_weekend($d, $schoolDays);
            $isHoliday = $isWeekend || isset($holidays[$d]);
            $cls = $isHoliday ? 'bg-danger text-white' : 'bg-teal';
            ?>
            <th class="<?= $cls ?>" data-date="<?= $d ?>">
              <?php if ($d <= $today && !$isHoliday && session()->get('user')['role_id'] != 2): ?>
                <a href="#" class="date-link" data-date="<?= $d ?>"><?= date('D d', strtotime($d)) ?></a>
              <?php else: ?>
                <span class="disabled text-white"><?= date('D d', strtotime($d)) ?></span>
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
      <tbody>
        <?php $no = 1;
        foreach ($students as $s):
          $H = $I = $S = $A = 0; ?>
          <tr data-student="<?= $s['id'] ?>">
            <td><?= $no++ ?></td>
            <td><?= esc($s['nis'] ?? '-') ?></td>
            <td style="text-align:left; white-space:nowrap"><?= esc($s['name']) ?></td>
            <td><?= esc($s['gender']) ?></td>

            <?php foreach ($weekDates as $d):
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
            foreach ($weekDates as $d) {
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
      foreach ($weekDates as $d) {
        // Gunakan helper is_weekend untuk cek weekend berdasarkan school_days
        $isWeekend = is_weekend($d, $schoolDays);
        $isHoliday = $isWeekend || isset($holidays[$d]);
        $val = $attMap[$s['id']][$d] ?? null;

        if ($isHoliday)
          $val = '-';
        elseif ($d <= $today)
          $val = $val ?? 'H';
        else
          $val = '-';

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
      <b>Rekap Mingguan:</b>
      H: <span class="text-success"><?= $totalH ?></span>,
      I: <span class="text-warning"><?= $totalI ?></span>,
      S: <span class="text-warning"><?= $totalS ?></span>,
      A: <span class="text-danger"><?= $totalA ?></span>
      | <b>Persentase Hadir:</b> <?= $percentClass ?>%
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
    document.querySelectorAll('thead th[data-date]').forEach(th => th.classList.remove('active-date'));
    document.querySelectorAll('tbody td[data-date]').forEach(td => {
      const date = td.dataset.date;
      let val = td.getAttribute('data-value');
      const isHoliday = td.classList.contains('bg-danger');
      if (isHoliday) { td.innerHTML = '-'; return; }
      if (!val || val === '-') {
        val = (date <= today) ? 'H' : '-';
      }
      td.setAttribute('data-value', val);
      if (val === '-') {
        td.innerHTML = `<b class="status-text" data-student="${td.dataset.student}" data-date="${date}">-</b>`;
      } else {
        let colorClass = (val === 'H') ? 'text-success' : (val === 'A') ? 'text-danger' : 'text-warning';
        td.innerHTML = `<b class="status-text ${colorClass}" data-student="${td.dataset.student}" data-date="${date}">${val}</b>`;
      }
      td.querySelector('b').classList.remove('editable');
    });
    document.getElementById('btn-save').disabled = true;
    document.getElementById('att-date').value = '';
    activeDate = null;
  }

  function activateDate(date) {
    activeDate = date;
    document.getElementById('att-date').value = date;
    document.querySelectorAll('thead th[data-date]').forEach(th => th.classList.toggle('active-date', th.dataset.date === date));

    document.querySelectorAll(`tbody td[data-date]`).forEach(td => {
      const tdDate = td.dataset.date;
      let val = td.getAttribute('data-value');
      const isHoliday = td.classList.contains('bg-danger');
      if (isHoliday) return;
      if (tdDate === date) {
        if (!val || val === '-') { val = (tdDate <= today) ? 'H' : '-'; td.setAttribute('data-value', val); }
        if (val !== '-') {
          let colorClass = (val === 'H') ? 'text-success' : (val === 'A') ? 'text-danger' : 'text-warning';
          td.innerHTML = `<b class="status-text editable ${colorClass}" data-student="${td.dataset.student}" data-date="${date}">${val}</b>`;
        } else {
          td.innerHTML = `<b class="status-text" data-student="${td.dataset.student}" data-date="${date}">-</b>`;
        }
      } else {
        if (!val || val === '-') {
          td.innerHTML = `<b class="status-text" data-student="${td.dataset.student}" data-date="${tdDate}">-</b>`;
        } else {
          let colorClass = (val === 'H') ? 'text-success' : (val === 'A') ? 'text-danger' : 'text-warning';
          td.innerHTML = `<b class="status-text ${colorClass}" data-student="${td.dataset.student}" data-date="${tdDate}">${val}</b>`;
        }
      }
    });
    document.getElementById('btn-save').disabled = false;
  }

  document.querySelectorAll('.date-link').forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();
      const date = link.dataset.date;
      if (date > today) return;
      resetTable(); activateDate(date);
    });
  });

  document.getElementById('attendance-table').addEventListener('click', e => {
    if (e.target.classList.contains('status-text') && e.target.classList.contains('editable')) {
      const b = e.target, studentId = b.dataset.student, date = b.dataset.date, currentVal = b.textContent;
      if (date !== activeDate) return;
      const td = b.parentElement;
      td.innerHTML = `
        <select name="status[${studentId}]" class="form-select form-select-sm" autofocus>
          <option value="H" ${currentVal === 'H' ? 'selected' : ''}>H</option>
          <option value="I" ${currentVal === 'I' ? 'selected' : ''}>I</option>
          <option value="S" ${currentVal === 'S' ? 'selected' : ''}>S</option>
          <option value="A" ${currentVal === 'A' ? 'selected' : ''}>A</option>
        </select>`;
      const select = td.querySelector('select');
      select.focus();
      select.addEventListener('change', function () {
        const val = this.value;
        td.setAttribute('data-value', val);
        let colorClass = (val === 'H') ? 'text-success' : (val === 'A') ? 'text-danger' : 'text-warning';
        td.innerHTML = `<b class="status-text editable ${colorClass}" data-student="${studentId}" data-date="${date}">${val}</b>`;
      });
      select.addEventListener('blur', function () {
        const val = this.value;
        td.setAttribute('data-value', val);
        let colorClass = (val === 'H') ? 'text-success' : (val === 'A') ? 'text-danger' : 'text-warning';
        td.innerHTML = `<b class="status-text editable ${colorClass}" data-student="${studentId}" data-date="${date}">${val}</b>`;
      });
    }
  });

  document.getElementById('attendance-form').addEventListener('submit', function (e) {
    if (!activeDate) { e.preventDefault(); alert('Silakan pilih tanggal absensi terlebih dahulu.'); return false; }
    
    // Refresh CSRF token sebelum submit (untuk mencegah token expired)
    const csrfInput = this.querySelector('input[name="csrf_test_name"]');
    if (csrfInput) {
      // Get fresh CSRF token from meta tag
      const csrfMeta = document.querySelector('meta[name="csrf_test_name"]');
      if (csrfMeta) {
        csrfInput.value = csrfMeta.getAttribute('content');
      }
    }
    
    document.querySelectorAll('input[name^="status["]').forEach(el => el.remove());
    document.querySelectorAll(`tbody td[data-date="${activeDate}"]`).forEach(td => {
      const studentId = td.dataset.student, val = td.getAttribute('data-value');
      if (val && val !== '-') {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = `status[${studentId}]`; input.value = val;
        this.appendChild(input);
      }
    });
  });

  resetTable();
</script>

<?= $this->endSection() ?>