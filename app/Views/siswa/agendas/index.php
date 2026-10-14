<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<?php
$monthInt     = intval($month);
$firstDay     = strtotime("$year-$month-01");
$startWeekday = date('w', $firstDay); // 0=minggu, 6=sabtu
$daysInMonth  = date('t', $firstDay);

$headers = ['Minggu','Senin','Selasa','Rabu','Kamis',"Jum'at",'Sabtu'];

// buat matriks minggu
$weeks    = [];
$week     = array_fill(0,7,'');
$day      = 1;
$startIdx = $startWeekday;

// minggu pertama
for ($i = 0; $i < 7; $i++) {
    if ($i < $startIdx) $week[$i] = '';
    else { $week[$i] = $day++; }
}
$weeks[] = $week;

// minggu berikutnya
while ($day <= $daysInMonth) {
    $week = [];
    for ($i=0; $i<7; $i++) {
        if ($day <= $daysInMonth) $week[$i] = $day++;
        else $week[$i] = '';
    }
    $weeks[] = $week;
}

// bulan sebelumnya & sesudahnya
$prevMonth = $monthInt - 1;
$prevYear  = $year;
if ($prevMonth < 1) { $prevMonth = 12; $prevYear--; }

$nextMonth = $monthInt + 1;
$nextYear  = $year;
if ($nextMonth > 12) { $nextMonth = 1; $nextYear++; }

// safety defaults
$agendaMap = $agendaMap ?? [];      // [ 'YYYY-MM-DD' => count ]
$holidays  = $holidays ?? [];       // array of holiday rows with ['date'=>...]
?>

<div class="container-fluid p-0 pb-5">
  <div class="d-flex align-items-center gap-2 mb-3">
    <i class="bi bi-calendar-event text-primary fs-5"></i>
    <h5 class="fw-bold mb-0">Agenda Kelas</h5>
  </div>

  <!-- 🔹 Navigasi bulan & tahun -->
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body py-2 px-3">
      <div class="d-flex align-items-center gap-2 flex-wrap">
        <a href="<?= site_url("siswa/agendas/$prevYear/".str_pad($prevMonth,2,'0',STR_PAD_LEFT)) ?>" class="btn btn-outline-secondary btn-sm">◀</a>

        <select id="monthSelect" class="form-select form-select-sm" style="max-width:130px;">
          <?php for ($m=1; $m<=12; $m++): ?>
            <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= (str_pad($m, 2, '0', STR_PAD_LEFT) == $month) ? 'selected' : '' ?>>
              <?= date('F', mktime(0,0,0,$m,1)) ?>
            </option>
          <?php endfor; ?>
        </select>

        <select id="yearSelect" class="form-select form-select-sm" style="max-width:90px;">
          <?php for ($y=date('Y')-2; $y<=date('Y')+2; $y++): ?>
            <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>

        <a href="<?= site_url("siswa/agendas/$nextYear/".str_pad($nextMonth,2,'0',STR_PAD_LEFT)) ?>" class="btn btn-outline-secondary btn-sm">▶</a>
      </div>
    </div>
  </div>

  <!-- 🔹 Legend -->
  <div class="mb-2 d-flex align-items-center gap-3 flex-wrap">
    <div class="d-flex align-items-center">
      <div style="width:14px;height:14px;background:#f8d7da;border-radius:3px;border:1px solid rgba(0,0,0,0.08);margin-right:5px"></div>
      <small class="text-muted">Libur / Weekend</small>
    </div>
    <div class="d-flex align-items-center">
      <span class="badge bg-primary me-2" style="width:10px;height:10px;padding:0;border-radius:50%">&nbsp;</span>
      <small class="text-muted">Ada Agenda</small>
    </div>
  </div>

  <!-- 🔹 Kalender -->
  <div class="table-responsive">
  <table class="table table-bordered calendar mb-0">
    <thead class="table-light">
      <tr>
        <?php
          $shortDays = ['Min','Sen','Sel','Rab','Kam',"Jum",'Sab'];
          foreach($headers as $i => $h):
        ?>
          <th class="text-center p-1 p-md-2" style="font-size:clamp(0.65rem,2vw,0.85rem);"><?= $shortDays[$i] ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php foreach($weeks as $wIndex => $w): ?>
        <tr class="calendar-week" data-week="<?= $wIndex ?>">
          <?php foreach($w as $i => $d): ?>
            <?php if ($d === ''): ?>
              <td style="height:80px;"></td>
            <?php else:
               $date = sprintf('%04d-%02d-%02d', $year, $monthInt, $d);

               // cek hari libur
               $isHoliday = false;
               foreach($holidays as $h) {
                   if (isset($h['date']) && $h['date'] === $date) { $isHoliday = true; break; }
               }

               // cek weekend (Minggu index 0, Sabtu index 6)
               $isWeekend = ($i == 0 || $i == 6);

               // class dasar
               $classes = ['calendar-day', 'align-top', 'p-2'];

               // kalau libur atau weekend -> beri fill merah muda
               if ($isHoliday || $isWeekend) {
                   $classes[] = 'bg-danger';
                   $classes[] = 'bg-opacity-10';
               }
            ?>
              <td class="<?= implode(' ', $classes) ?>"
                  style="height:clamp(50px,10vw,80px); cursor:pointer; min-width:0;"
                  data-date="<?= $date ?>">
                <div class="date-number fw-bold" style="font-size:clamp(0.7rem,2.5vw,1rem);"><?= $d ?></div>

                <?php if (!empty($agendaMap[$date])): ?>
                  <div class="text-primary d-none d-sm-block" style="font-size:0.7rem;">● <?= $agendaMap[$date] ?></div>
                  <div class="text-primary d-sm-none" style="font-size:0.55rem;">●</div>
                <?php endif; ?>

                <div class="small text-muted d-none d-md-block" style="font-size:0.65rem;">klik</div>
              </td>
            <?php endif; ?>
          <?php endforeach; ?>
        </tr>

        <tr class="week-agenda-row" data-week-row="<?= $wIndex ?>" style="display:none;">
          <td colspan="7" class="p-0">
            <div class="day-agendas-container p-2"></div>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div><!-- end table-responsive -->
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
$(function(){
  // Redirect dropdown bulan/tahun
  $('#monthSelect, #yearSelect').on('change', function(){
    const y = $('#yearSelect').val();
    const m = $('#monthSelect').val();
    window.location.href = '<?= site_url('siswa/agendas') ?>/' + y + '/' + m;
  });

  // Klik tanggal (delegated)
  $(document).on('click', '.calendar-day', function(){
    const date = $(this).data('date');
    const weekRow = $(this).closest('tr').data('week');
    const weekAgendaRow = $('tr.week-agenda-row[data-week-row="'+weekRow+'"]');
    const container = weekAgendaRow.find('.day-agendas-container');

    if (weekAgendaRow.is(':visible') && container.data('date') === date) {
      weekAgendaRow.hide();
      $('.calendar-day').removeClass('bg-primary text-white');
      return;
    }

    $('tr.week-agenda-row').hide();
    $('.calendar-day').removeClass('bg-primary text-white');

    $.get('<?= site_url('siswa/agendas/date') ?>/' + date, function(html){
      container.html(html);
      container.data('date', date);
      weekAgendaRow.show();
      $('[data-date="'+date+'"]').addClass('bg-primary text-white');
    })
  });
});
</script>
<?= $this->endSection() ?>
