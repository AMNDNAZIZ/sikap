<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<style>
    /* ── Hero Banner ─────────────────────────────── */
    .dh-hero {
        background: linear-gradient(135deg, #1e3a8a 0%, #2563eb 60%, #3b82f6 100%);
        border-radius: 16px;
        padding: 32px 36px;
        color: #fff;
        margin-bottom: 24px;
        position: relative;
        overflow: hidden;
    }
    .dh-hero::before {
        content: '';
        position: absolute;
        top: -40px; right: -40px;
        width: 200px; height: 200px;
        border-radius: 50%;
        background: rgba(255,255,255,.06);
    }
    .dh-hero::after {
        content: '';
        position: absolute;
        bottom: -60px; right: 60px;
        width: 280px; height: 280px;
        border-radius: 50%;
        background: rgba(255,255,255,.04);
    }
    .dh-hero h1 { font-size: 1.6rem; font-weight: 700; margin-bottom: 4px; }
    .dh-hero p  { opacity: .85; margin: 0; font-size: .9rem; }
    .dh-hero .hero-icon {
        font-size: 3.5rem;
        opacity: .25;
        position: absolute;
        right: 36px; top: 50%;
        transform: translateY(-50%);
    }

    /* ── Info Chips ──────────────────────────────── */
    .info-chips { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 24px; }
    .info-chip {
        display: flex; align-items: center; gap: 8px;
        background: #fff; border: 1px solid #e2e8f0;
        border-radius: 10px; padding: 10px 16px;
        font-size: .82rem; color: #374151;
        box-shadow: 0 1px 4px rgba(0,0,0,.05);
        flex: 1; min-width: 160px;
    }
    .info-chip .chip-icon {
        width: 34px; height: 34px; border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1rem; flex-shrink: 0;
    }
    .info-chip .chip-label { font-weight: 600; font-size: .75rem; color: #9ca3af; text-transform: uppercase; letter-spacing: .04em; }
    .info-chip .chip-value { font-weight: 600; color: #111827; }

    /* ── Config Card ─────────────────────────────── */
    .config-card {
        background: #fff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        box-shadow: 0 2px 12px rgba(0,0,0,.06);
        overflow: hidden;
    }
    .config-card .card-header-custom {
        background: linear-gradient(90deg, #f8faff, #eff6ff);
        border-bottom: 1px solid #dbeafe;
        padding: 18px 24px;
    }
    .config-card .card-body { padding: 24px; }

    /* ── Tombol Generate ─────────────────────────── */
    #btnBukaModal {
        background: linear-gradient(135deg, #2563eb, #1d4ed8);
        border: none;
        border-radius: 10px;
        padding: 10px 24px;
        font-weight: 600;
        font-size: .9rem;
        letter-spacing: .01em;
        box-shadow: 0 4px 14px rgba(37,99,235,.35);
        transition: transform .15s, box-shadow .15s;
    }
    #btnBukaModal:hover { transform: translateY(-1px); box-shadow: 0 6px 20px rgba(37,99,235,.45); }
    #btnBukaModal:active { transform: translateY(0); }
</style>

<!-- ═══ HERO ═══ -->
<div class="dh-hero">
    <h1><i class="bi bi-printer-fill me-2"></i>Cetak Daftar Hadir</h1>
    <p>Cetak daftar hadir siswa secara dinamis — pilih kelas, susun kolom, dan unduh PDF siap cetak.</p>
    <span class="hero-icon">📋</span>
</div>

<!-- ═══ INFO CHIPS ═══ -->
<div class="info-chips">
    <div class="info-chip">
        <div class="chip-icon" style="background:#eff6ff; color:#2563eb;">🏫</div>
        <div>
            <div class="chip-label">Total Kelas</div>
            <div class="chip-value"><?= count($classes) ?> Kelas</div>
        </div>
    </div>
    <div class="info-chip">
        <div class="chip-icon" style="background:#f0fdf4; color:#16a34a;">📄</div>
        <div>
            <div class="chip-label">Format</div>
            <div class="chip-value">PDF (A4 / F4)</div>
        </div>
    </div>
    <div class="info-chip">
        <div class="chip-icon" style="background:#fdf4ff; color:#9333ea;">🖋️</div>
        <div>
            <div class="chip-label">Kolom</div>
            <div class="chip-value">Dapat dikustom</div>
        </div>
    </div>
    <div class="info-chip">
        <div class="chip-icon" style="background:#fff7ed; color:#ea580c;">📑</div>
        <div>
            <div class="chip-label">Kop Surat</div>
            <div class="chip-value">Opsional (on/off)</div>
        </div>
    </div>
</div>

<!-- ═══ CONFIG CARD ═══ -->
<div class="config-card">
    <div class="card-header-custom d-flex align-items-center gap-2">
        <i class="bi bi-sliders text-primary fs-5"></i>
        <div>
            <div class="fw-bold" style="font-size:.95rem;">Konfigurasi Cetak</div>
            <div class="text-muted" style="font-size:.8rem;">Pilih kelas lalu atur kolom dan tampilan dokumen</div>
        </div>
    </div>
    <div class="card-body">
        <div class="row mb-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold">Pilih Kelas</label>
                <select id="selectKelas" class="form-select">
                    <option value="">Semua Kelas</option>
                    <?php foreach ($classes as $c): ?>
                        <option value="<?= $c['id'] ?>" <?= ($kelasId == $c['id']) ? 'selected' : '' ?>>
                            <?= esc($c['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="form-text">Kosongkan untuk mencetak semua kelas (dipisah per halaman).</div>
            </div>
        </div>

        <button type="button" class="btn btn-primary" id="btnBukaModal">
            <i class="bi bi-gear-fill me-2"></i>Atur &amp; Cetak Daftar Hadir
        </button>
    </div>
</div>


<!-- Modal Konfigurasi -->
<div class="modal fade" id="modalKonfigurasi" tabindex="-1" aria-labelledby="modalKonfigurasiLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formCetakPDF" method="POST" action="<?= base_url('admin/tata-usaha/cetak-daftar-hadir/generate') ?>" target="_blank">
                <?= csrf_field() ?>
                <input type="hidden" name="kelas_id" id="hiddenKelasId">

                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalKonfigurasiLabel">
                        <i class="bi bi-sliders me-2"></i>Konfigurasi Daftar Hadir
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <!-- Tanggal -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="inputTanggal">Tanggal Kegiatan</label>
                        <input type="date" class="form-control" id="inputTanggal" name="tanggal"
                               value="<?= date('Y-m-d') ?>">
                        <div class="form-text">Tanggal yang akan tercantum pada daftar hadir dan tanda tangan.</div>
                    </div>

                    <!-- Nama Kegiatan -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold" for="inputNamaKegiatan">Nama Kegiatan</label>
                        <input type="text" class="form-control" id="inputNamaKegiatan" name="nama_kegiatan"
                               placeholder="Contoh: Imunisasi, Rapat Wali Murid, Ujian Tengah Semester">
                        <div class="form-text">Akan ditampilkan sebagai subjudul di bawah "DAFTAR HADIR KEGIATAN".</div>
                    </div>

                    <!-- Kolom Tabel (Sortable) -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Kolom Tabel <small class="text-muted fw-normal">(geser untuk mengubah urutan)</small></label>
                        <ul id="sortableKolom" class="list-group">
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="no">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_no" value="no" checked>
                                    <label class="form-check-label" for="col_no">No</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="nis">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_nis" value="nis" checked>
                                    <label class="form-check-label" for="col_nis">NIS</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="nisn">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_nisn" value="nisn">
                                    <label class="form-check-label" for="col_nisn">NISN</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="name">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_name" value="name" checked>
                                    <label class="form-check-label" for="col_name">Nama Siswa</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="gender">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_gender" value="gender">
                                    <label class="form-check-label" for="col_gender">Jenis Kelamin</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="birth_place">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_birth_place" value="birth_place">
                                    <label class="form-check-label" for="col_birth_place">Tempat Lahir</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="birth_date">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_birth_date" value="birth_date">
                                    <label class="form-check-label" for="col_birth_date">Tanggal Lahir</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="religion">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_religion" value="religion">
                                    <label class="form-check-label" for="col_religion">Agama</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="address">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_address" value="address">
                                    <label class="form-check-label" for="col_address">Alamat</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="father_name">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_father_name" value="father_name">
                                    <label class="form-check-label" for="col_father_name">Nama Ayah</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="mother_name">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_mother_name" value="mother_name">
                                    <label class="form-check-label" for="col_mother_name">Nama Ibu</label>
                                </div>
                            </li>
                            <li class="list-group-item d-flex align-items-center gap-2" data-value="Tanda Tangan">
                                <i class="bi bi-grip-vertical text-muted" style="cursor:grab;"></i>
                                <div class="form-check form-check-inline m-0">
                                    <input class="form-check-input kolom-check" type="checkbox" id="col_ttd" value="Tanda Tangan" checked>
                                    <label class="form-check-label" for="col_ttd">Tanda Tangan</label>
                                </div>
                            </li>
                        </ul>

                        <!-- Kolom Kustom -->
                        <div class="mt-2">
                            <label class="form-label fw-semibold mb-1">Tambah Kolom Kosong Kustom</label>
                            <div class="input-group input-group-sm">
                                <input type="text" id="inputKolomKustom" class="form-control"
                                       placeholder="Nama kolom, misal: Keterangan / Paraf Wali">
                                <button type="button" class="btn btn-outline-secondary" id="btnTambahKustom">
                                    <i class="bi bi-plus-lg"></i> Tambah
                                </button>
                            </div>
                            <div id="daftarKolomKustom" class="d-flex flex-wrap gap-2 mt-2"></div>
                        </div>

                        <!-- Hidden inputs akan di-generate oleh JS -->
                        <div id="hiddenKolomInputs"></div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Ukuran Kertas</label>
                            <select name="kertas" id="selectKertas" class="form-select">
                                <option value="A4">A4</option>
                                <option value="F4">F4 (Folio)</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Kop Surat</label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="kop_surat" id="toggleKop" value="1" checked>
                                <label class="form-check-label" for="toggleKop">Gunakan Kop Surat</label>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label fw-semibold">Tanda Tangan</label>
                            <div class="form-check form-switch mt-1">
                                <input class="form-check-input" type="checkbox" name="tampil_ttd" id="toggleTTD" value="1" checked>
                                <label class="form-check-label" for="toggleTTD">Tampilkan Tanda Tangan Kepsek</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnGenerate">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Generate PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>

<script>
$(document).ready(function () {

    // ── Inisialisasi Select2 kelas
    $('#selectKelas').select2({ theme: 'bootstrap-5', placeholder: 'Pilih kelas...' });

    // ── Inisialisasi Sortable untuk daftar kolom
    const sortableEl = document.getElementById('sortableKolom');
    Sortable.create(sortableEl, {
        handle: '.bi-grip-vertical',
        animation: 150
    });

    // ── Buka Modal
    $('#btnBukaModal').on('click', function () {
        const kelasId = $('#selectKelas').val();
        $('#hiddenKelasId').val(kelasId);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('modalKonfigurasi')).show();
    });

    // ── Tambah kolom kustom
    function tambahKolomKustom(nama) {
        nama = nama.trim();
        if (!nama) return;

        // Prefiks __custom__ agar controller bisa bedakan dengan field siswa
        const val = '__custom__' + nama;
        const badge = $(`
            <span class="badge bg-secondary d-flex align-items-center gap-1" style="font-size:.85rem;padding:.4em .65em;">
                ${$('<span>').text(nama).html()}
                <button type="button" class="btn-close btn-close-white ms-1" style="font-size:.65rem;"
                        data-val="${val.replace(/"/g,'&quot;')}"></button>
            </span>
        `);
        $('#daftarKolomKustom').append(badge);
        $('#inputKolomKustom').val('');
    }

    $('#btnTambahKustom').on('click', function () {
        tambahKolomKustom($('#inputKolomKustom').val());
    });
    $('#inputKolomKustom').on('keypress', function (e) {
        if (e.key === 'Enter') { e.preventDefault(); tambahKolomKustom($(this).val()); }
    });
    $(document).on('click', '#daftarKolomKustom .btn-close', function () {
        $(this).closest('.badge').remove();
    });

    // ── Pada submit: bangun hidden inputs kolom berurutan sesuai urutan sortable
    $('#formCetakPDF').on('submit', function () {
        // Hapus hidden inputs lama
        $('#hiddenKolomInputs').empty();

        // 1. Kolom standar (sesuai urutan sortable, hanya yang di-checked)
        $('#sortableKolom .kolom-check:checked').each(function () {
            $('<input>').attr({ type: 'hidden', name: 'kolom[]', value: $(this).val() })
                        .appendTo('#hiddenKolomInputs');
        });

        // 2. Kolom kustom (sesuai urutan badge)
        $('#daftarKolomKustom .btn-close').each(function () {
            $('<input>').attr({ type: 'hidden', name: 'kolom[]', value: $(this).data('val') })
                        .appendTo('#hiddenKolomInputs');
        });

        // Sembunyikan modal setelah delay
        setTimeout(function () {
            const modal = bootstrap.Modal.getInstance(document.getElementById('modalKonfigurasi'));
            if (modal) modal.hide();
        }, 300);
    });
});
</script>
<?= $this->endSection() ?>
