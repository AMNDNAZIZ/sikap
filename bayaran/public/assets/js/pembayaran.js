
$(function () {
  initSelect2Siswa();
  initAngkaInputFormat();
});

function initSelect2Siswa() {
  $('#siswa_id').select2({
    placeholder: 'Ketik nama atau NIS siswa',
    ajax: {
      url: BASE_URL + '/admin/pembayaran/search/siswa',
      dataType: 'json',
      delay: 250,
      data: params => ({ q: params.term }),
      processResults: data => ({ results: data.results })
    },
    minimumInputLength: 2,
    width: '100%'
  });
}

$(document).on('submit', '.form-bayar', function (e) {
  e.preventDefault();
  const form = $(this);
  const row = form.closest('tr');

  const tagihanId = row.data('id');
  const tanggal = row.find('input[name="tanggal"]').val();
  const caraBayar = row.find('select[name="cara_bayar"]').val();

  if (!tanggal) return alert('Tanggal wajib diisi');
  if (!caraBayar) return alert('Pilih cara bayar');

  const payload = {
    siswa_id: form.find('input[name="siswa_id"]').val(),
    tagihan_id: tagihanId,
    jenis_bayar_id: form.find('input[name="jenis_bayar_id"]').val(),
    tanggal,
    cara_bayar: caraBayar,
    [CSRF_NAME]: CSRF_HASH
  };

  $.post(BASE_URL + '/admin/pembayaran/bayar', payload)
    .done(res => {
      updateCSRF(res);
      if (res.status !== 'ok') return alert(res.message || 'Terjadi kesalahan.');
      row.find('.col-check').html(`<input type="checkbox" class="check-cetak-bulanan" value="${res.id}">`);
      row.find('.status').html('<span class="badge bg-success">Lunas</span>');
      row.find('.tanggal').text(formatDate(tanggal));
      row.find('.cara').text(capitalize(caraBayar));
      row.find('.aksi').html(`
        <a href="${BASE_URL}/admin/pembayaran/cetak/${res.id}" target="_blank" class="btn btn-sm btn-info">Cetak</a>
        <button class="btn btn-sm btn-danger btn-hapus" data-id="${res.id}">Hapus</button>
      `);
      updateRingkasanKeuangan();
    })
    .fail(() => alert('Gagal menyimpan pembayaran.'));
});

$(document).on('click', '.btn-hapus', function (e) {
  e.preventDefault();
  e.stopImmediatePropagation(); // ⛔️ kunci: stop trigger ganda

  const btn = $(this);
  if (btn.prop('disabled') || btn.data('running')) return;

  if (!confirm('Yakin hapus pembayaran ini?')) return;

  btn.prop('disabled', true).data('running', true);

  const row = btn.closest('tr');
  const pembayaranId = btn.data('id');
  const siswaId = row.data('siswa');
  const jenisId = row.data('jenis');

  if (!pembayaranId || !siswaId || !jenisId) {
    alert('Data tidak lengkap.');
    btn.prop('disabled', false).data('running', false);
    return;
  }

  $.post(BASE_URL + `/admin/pembayaran/delete-bayar/${pembayaranId}`, {
    [CSRF_NAME]: CSRF_HASH
  }).done((res) => {
    updateCSRF(res);
    row.find('.col-check').empty();
    row.find('.status').html('<span class="badge bg-danger">Belum</span>');
    row.find('.tanggal').html(`<input type="date" name="tanggal" class="form-control form-control-sm" value="${today()}">`);
    row.find('.cara').html(`
      <select name="cara_bayar" class="form-select form-select-sm">
        <option>Tunai</option>
        <option>Transfer</option>
        <option>KJP</option>
        <option>BPMS</option>
      </select>
    `);
    row.find('.aksi').html(`
      <form class="form-bayar d-flex gap-1 align-items-center">
        ${csrfInput()}
        <input type="hidden" name="siswa_id" value="${siswaId}">
        <input type="hidden" name="jenis_bayar_id" value="${jenisId}">
        <button class="btn btn-sm btn-success">Bayar</button>
      </form>
    `);
    updateRingkasanKeuangan();
  }).fail((res) => {
    updateCSRF(res.responseJSON);
    alert('Gagal menghapus pembayaran.');
  }).always(() => {
    btn.prop('disabled', false).data('running', false);
  });
});

$(document).on('submit', '.form-bebas', function (e) {
  e.preventDefault();
  const form = $(this);
  const jenisId = form.attr('id').replace('form_bebas_', '');
  const tanggal = form.find('input[name="tanggal"]').val();
  const jumlah = parseInt(form.find('input[name="jumlah_bayar"]').data('raw') || '0');
  const cara = form.find('select[name="cara_bayar"]').val();
  const angsuranKe = form.find('input[name="angsuran_ke"]').val();

  if (!tanggal) return alert('Tanggal wajib diisi');
  if (!jumlah || jumlah <= 0) return alert('Jumlah bayar harus lebih dari 0');
  if (!angsuranKe) return alert('Angsuran ke- wajib diisi');

  form.find('input[name="keterangan"]').remove();
  form.append(`<input type="hidden" name="keterangan" value="Angsuran ke-${angsuranKe}">`);

  $.post(form.attr('action'), form.serialize())
    .done(res => {
      updateCSRF(res);
      if (res.status !== 'ok') return alert(res.message || 'Gagal menyimpan pembayaran.');
      const tbody = $('#riwayat_' + jenisId);
      tbody.append(`
        <tr>
          <td>${tbody.find('tr').length + 1}</td>
          <td>${formatDate(tanggal)}</td>
          <td>${formatRupiah(res.jumlah_bayar)}</td>
          <td>${capitalize(res.cara_bayar)}</td>
          <td>${res.keterangan || '-'}</td>
          <td>
            <form method="post" action="${BASE_URL}/admin/pembayaran/delete-bayar/${res.id}" class="form-hapus-bebas" data-id="${res.id}" data-jenis="${jenisId}">
              <input type="hidden" name="${CSRF_NAME}" value="${CSRF_HASH}">
              <a href="${BASE_URL}/admin/pembayaran/cetak/${res.id}" target="_blank" class="btn btn-sm btn-info">Cetak</a>
              <button type="submit" class="btn btn-sm btn-danger">Hapus</button>
            </form>
          </td>
        </tr>
      `);
      form.trigger('reset').find('input[name="tanggal"]').val(today());
      updateRingkasanKeuangan();
    })
    .fail(() => alert('Gagal menyimpan pembayaran.'));
});

$(document).on('submit', '.form-hapus-bebas', function (e) {
  e.preventDefault();
  if (!confirm('Yakin ingin menghapus pembayaran ini?')) return;
  const form = $(this);
  $.post(form.attr('action'), form.serialize())
    .done((res) => {
      updateCSRF(res);
      form.closest('tr').remove();
    })
    .fail((res) => {
      updateCSRF(res.responseJSON);
      alert('Gagal menghapus pembayaran.');
    });
});

$(document).on('click', '.btn-cetak-banyak', function (e) {
  const container = $(this).closest('.form-cetak-bulanan');

  // Cari semua checkbox di dalam table detail yang sama
  const ids = container.closest('td')
    .find('.check-cetak-bulanan:checked')
    .map(function () {
      return this.value;
    }).get();

  if (ids.length === 0) {
    alert('Pilih minimal satu pembayaran.');
    e.preventDefault();
    return;
  }

  container.find('.input-pembayaran-ids').val(ids.join(','));
});


function updateCSRF(res) {
  if (res && res.csrf_hash) {
    CSRF_HASH = res.csrf_hash;
    // Update all hidden CSRF inputs in the page
    $(`input[name="${CSRF_NAME}"]`).val(res.csrf_hash);
  }
}

function initAngkaInputFormat() {
  $(document).on('input', '.input-angka', function () {
    const raw = this.value.replace(/\D/g, '');
    this.dataset.raw = raw;
    this.value = Number(raw).toLocaleString('id-ID');
  });
  $(document).on('submit', 'form', function () {
    $(this).find('.input-angka').each(function () {
      if (this.dataset.raw) this.value = this.dataset.raw;
    });
  });
}

function updateRingkasanKeuangan() {
  const siswaId = $('#siswa_id').val();
  if (!siswaId) return;
  const openedCollapse = $('.collapse.show').map(function () {
    return this.id;
  }).get();
  $.get(BASE_URL + '/admin/pembayaran?siswa_id=' + siswaId, function (html) {
    const doc = $('<div>').html(html);

    // Sinkronisasi CSRF terbaru dari HTML
    const newHash = doc.find(`input[name="${CSRF_NAME}"]`).val() || doc.html().match(new RegExp(`CSRF_HASH\\s*=\\s*'([^']+)'`))?.[1];
    if (newHash) updateCSRF({ csrf_hash: newHash });

    $('.card:has(.card-header.bg-info)').replaceWith(doc.find('.card:has(.card-header.bg-info)'));
    $('.card:has(.card-header.bg-success)').replaceWith(doc.find('.card:has(.card-header.bg-success)'));
    $('.card:has(.card-header.bg-warning)').replaceWith(doc.find('.card:has(.card-header.bg-warning)'));
    openedCollapse.forEach(id => $('#' + id).addClass('show'));
  });
}

function csrfInput() {
  return `<input type="hidden" name="${CSRF_NAME}" value="${CSRF_HASH}">`;
}
function today() {
  return new Date().toISOString().split('T')[0];
}
function formatDate(date) {
  return date.split('-').reverse().join('-');
}
function formatRupiah(n) {
  const num = parseInt(n);
  return isNaN(num) ? 'Rp 0' : 'Rp ' + num.toLocaleString('id-ID');
}
function capitalize(str) {
  return str.charAt(0).toUpperCase() + str.slice(1);
}
