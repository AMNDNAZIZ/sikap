<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1 text-gray-800">📝 Buat Modul Ajar Mandiri / Copas Word</h1>
        <p class="text-muted small mb-0">Susun modul ajar dengan format bebas, atau salin-tempel langsung dari Microsoft Word / Google Docs.</p>
    </div>
    <a href="<?= base_url('admin/administrasi-guru/modul-ajar?class_id=' . $class_id . '&subject_id=' . $subject['id']) ?>" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Modul
    </a>
</div>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= session()->getFlashdata('error') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="row">
    <!-- Kolom Kiri: Informasi Materi / ATP -->
    <div class="col-lg-4 mb-4">
        <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 font-weight-bold text-primary"><i class="bi bi-info-circle me-1"></i> Informasi Pembelajaran</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" width="110">Mata Pelajaran</td>
                        <td class="fw-bold">: <?= esc($subject['name']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kelas</td>
                        <td class="fw-bold">: <?= esc($class['name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Materi / ATP</td>
                        <td class="fw-bold text-primary">: <?= esc($atp['lingkup_materi'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Alokasi Waktu</td>
                        <td>: <?= esc($atp['alokasi_waktu'] ?? 4) ?> JP</td>
                    </tr>
                </table>

                <?php if (!empty($atp['tps'])): ?>
                    <hr class="my-3">
                    <div class="small fw-bold text-muted mb-2">Tujuan Pembelajaran (TP):</div>
                    <ul class="small ps-3 mb-0 text-secondary">
                        <?php foreach ($atp['tps'] as $tp): ?>
                            <li class="mb-1"><strong><?= esc($tp['kode_tp'] ?? '') ?>:</strong> <?= esc($tp['deskripsi'] ?? '') ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm bg-primary-subtle text-primary-emphasis">
            <div class="card-body p-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-lightbulb-fill text-warning me-1"></i> Tips Paste dari Word:</h6>
                <ol class="small ps-3 mb-0">
                    <li class="mb-1">Buka dokumen modul ajar Anda di <strong>Microsoft Word</strong> atau <strong>Google Docs</strong>.</li>
                    <li class="mb-1">Pilih / blok seluruh teks modul (atau tekan <code>Ctrl + A</code>), lalu tekan <strong>Copy</strong> (<code>Ctrl + C</code>).</li>
                    <li class="mb-1">Klik di dalam area editor di samping, lalu tekan <strong>Paste</strong> (<code>Ctrl + V</code>).</li>
                    <li>Format tabel, cetak tebal, daftar nomor, dan judul akan dipertahankan secara rapi!</li>
                </ol>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Editor Mandiri -->
    <div class="col-lg-8 mb-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 font-weight-bold text-dark"><i class="bi bi-file-earmark-richtext text-success me-1"></i> Lembar Kerja Editor Modul</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btnInsertTemplate">
                    <i class="bi bi-file-earmark-plus me-1"></i> Sisipkan Kerangka Dasar
                </button>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/administrasi-guru/modul-ajar/save') ?>" method="post" id="formModulMandiri">
                    <?= csrf_field() ?>
                    <input type="hidden" name="atp_id" value="<?= esc($atp['id']) ?>">
                    <input type="hidden" name="class_id" value="<?= esc($class_id) ?>">
                    <input type="hidden" name="subject_id" value="<?= esc($subject['id']) ?>">

                    <div class="mb-3">
                        <textarea name="content" id="editor" class="form-control"><?= old('content', $existing['content'] ?? '') ?></textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <a href="<?= base_url('admin/administrasi-guru/modul-ajar?class_id=' . $class_id . '&subject_id=' . $subject['id']) ?>" class="btn btn-light border">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-success px-4" id="btnSubmitModul">
                            <i class="bi bi-check-circle me-1"></i> Simpan Modul Ajar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="https://cdn.ckeditor.com/ckeditor5/39.0.1/super-build/ckeditor.js"></script>
<script>
    let editorInstance;

    CKEDITOR.ClassicEditor.create(document.getElementById("editor"), {
        toolbar: {
            items: [
                'heading', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'bulletedList', 'numberedList', 'todoList', '|',
                'outdent', 'indent', '|',
                'undo', 'redo', '|',
                'fontSize', 'fontFamily', 'fontColor', 'fontBackgroundColor', 'highlight', '|',
                'alignment', '|',
                'link', 'insertTable', 'blockQuote', '|',
                'specialCharacters', 'horizontalLine', 'pageBreak', '|',
                'sourceEditing'
            ],
            shouldNotGroupWhenFull: true
        },
        list: {
            properties: {
                styles: true,
                startIndex: true,
                reversed: true
            }
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraph', class: 'ck-heading_paragraph' },
                { model: 'heading1', view: 'h1', title: 'Heading 1', class: 'ck-heading_heading1' },
                { model: 'heading2', view: 'h2', title: 'Heading 2', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Heading 3', class: 'ck-heading_heading3' },
                { model: 'heading4', view: 'h4', title: 'Heading 4', class: 'ck-heading_heading4' }
            ]
        },
        placeholder: 'Silakan ketik atau Paste (Ctrl + V) naskah modul ajar Anda langsung dari Microsoft Word di sini...',
        fontFamily: {
            options: [
                'default',
                'Arial, Helvetica, sans-serif',
                'Courier New, Courier, monospace',
                'Georgia, serif',
                'Times New Roman, Times, serif',
                'Verdana, Geneva, sans-serif'
            ],
            supportAllValues: true
        },
        fontSize: {
            options: [ 10, 11, 12, 14, 'default', 16, 18, 20, 22 ],
            supportAllValues: true
        },
        htmlSupport: {
            allow: [
                {
                    name: /.*/,
                    attributes: true,
                    classes: true,
                    styles: true
                }
            ]
        },
        htmlEmbed: {
            showPreviews: true
        },
        removePlugins: [
            'CKBox',
            'CKFinder',
            'EasyImage',
            'RealTimeCollaborativeComments',
            'RealTimeCollaborativeTrackChanges',
            'RealTimeCollaborativeRevisionHistory',
            'PresenceList',
            'Comments',
            'TrackChanges',
            'TrackChangesData',
            'RevisionHistory',
            'Pagination',
            'WProofreader',
            'MathType',
            'SlashCommand',
            'Template',
            'DocumentOutline',
            'FormatPainter',
            'TableOfContents',
            'PasteFromOfficeEnhanced',
            'CaseChange'
        ]
    }).then(editor => {
        editorInstance = editor;
    }).catch(error => {
        console.error(error);
    });

    // Tombol sisipkan kerangka dasar
    $('#btnInsertTemplate').on('click', function() {
        if (!editorInstance) return;
        const currentData = editorInstance.getData();
        if (currentData.trim().length > 0) {
            if (!confirm('Editor sudah memiliki isi. Sisipkan kerangka di posisi kursor atau ganti isi? Tekan OK untuk menambahkan.')) {
                return;
            }
        }

        const templateHtml = `
<h3>A. Identitas Modul</h3>
<table style="width:100%; border-collapse:collapse;" border="1">
    <tr><td style="width:200px; padding:6px; font-weight:bold;">Nama Penyusun</td><td style="padding:6px;">-</td></tr>
    <tr><td style="padding:6px; font-weight:bold;">Mata Pelajaran</td><td style="padding:6px;"><?= esc($subject['name']) ?></td></tr>
    <tr><td style="padding:6px; font-weight:bold;">Kelas / Fase</td><td style="padding:6px;"><?= esc($class['name'] ?? '-') ?></td></tr>
    <tr><td style="padding:6px; font-weight:bold;">Lingkup Materi</td><td style="padding:6px;"><?= esc($atp['lingkup_materi'] ?? '-') ?></td></tr>
    <tr><td style="padding:6px; font-weight:bold;">Alokasi Waktu</td><td style="padding:6px;"><?= esc($atp['alokasi_waktu'] ?? 4) ?> JP</td></tr>
</table>

<h3>B. Capaian Pembelajaran &amp; Kompetensi Awal</h3>
<p><?= esc($atp['cp_deskripsi'] ?? 'Tuliskan capaian pembelajaran atau kompetensi awal di sini...') ?></p>

<h3>C. Tujuan Pembelajaran</h3>
<p>Peserta didik diharapkan mampu:</p>
<ol>
    <?php if (!empty($atp['tps'])): ?>
        <?php foreach ($atp['tps'] as $tp): ?>
            <li><?= esc($tp['deskripsi']) ?></li>
        <?php endforeach; ?>
    <?php else: ?>
        <li>Menjelaskan konsep utama materi...</li>
    <?php endif; ?>
</ol>

<h3>D. Profil Pelajar Pancasila</h3>
<ul>
    <li>Beriman, bertakwa kepada Tuhan YME, dan berakhlak mulia</li>
    <li>Bernalar Kritis</li>
    <li>Kreatif &amp; Gotong Royong</li>
</ul>

<h3>E. Sarana dan Prasarana</h3>
<p>Buku pegangan, proyektor, lembar kerja siswa (LKPD), jaringan internet.</p>

<h3>F. Kegiatan Pembelajaran</h3>
<table style="width:100%; border-collapse:collapse;" border="1">
    <thead>
        <tr style="background-color:#f1f5f9;">
            <th style="padding:6px; width:120px;">Tahap</th>
            <th style="padding:6px;">Deskripsi Kegiatan</th>
            <th style="padding:6px; width:80px;">Waktu</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding:6px; font-weight:bold;">Pendahuluan</td>
            <td style="padding:6px;">Salam, doa, presensi, apersepsi dan penyampaian tujuan pembelajaran.</td>
            <td style="padding:6px; text-align:center;">10 Menit</td>
        </tr>
        <tr>
            <td style="padding:6px; font-weight:bold;">Kegiatan Inti</td>
            <td style="padding:6px;">Eksplorasi materi, diskusi kelompok, pengerjaan LKPD, dan presentasi hasil.</td>
            <td style="padding:6px; text-align:center;">60 Menit</td>
        </tr>
        <tr>
            <td style="padding:6px; font-weight:bold;">Penutup</td>
            <td style="padding:6px;">Refleksi bersama, kesimpulan, evaluasi singkat, dan tindak lanjut.</td>
            <td style="padding:6px; text-align:center;">10 Menit</td>
        </tr>
    </tbody>
</table>

<h3>G. Asesmen / Penilaian</h3>
<p><strong>1. Asesmen Formatif:</strong> Pengamatan sikap &amp; keaktifan diskusi kelompok.<br>
<strong>2. Asesmen Sumatif:</strong> Tes tertulis / penugasan LKPD.</p>
`;

        if (currentData.trim().length === 0) {
            editorInstance.setData(templateHtml);
        } else {
            editorInstance.model.change(writer => {
                const insertPosition = editorInstance.model.document.selection.getFirstPosition();
                editorInstance.execute('insertHtml', templateHtml);
            });
        }
    });

    $('#formModulMandiri').on('submit', function() {
        if (editorInstance) {
            const data = editorInstance.getData();
            if (!data || data.replace(/<[^>]*>/g, '').trim().length === 0) {
                alert('Konten modul ajar masih kosong. Silakan tulis atau paste modul terlebih dahulu.');
                return false;
            }
        }
        $('#btnSubmitModul').prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-1"></span> Menyimpan...');
    });
</script>
<?= $this->endSection() ?>
