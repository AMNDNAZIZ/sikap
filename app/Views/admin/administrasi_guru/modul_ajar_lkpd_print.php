<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LKPD Pertemuan <?= esc($pertemuan) ?> - <?= esc($subject['name'] ?? 'Mata Pelajaran') ?></title>
    <style>
        @page {
            size: A4 portrait;
            margin: 1.5cm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Times New Roman', Times, serif, Arial, sans-serif;
            font-size: 11pt;
            line-height: 1.45;
            color: #111;
            margin: 0;
            padding: 0;
            background: #f8f9fa;
        }
        
        /* Toolbar Kontrol Cetak (Hanya tampil di layar) */
        .print-toolbar {
            background: #ffffff;
            border-bottom: 2px solid #0d6efd;
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            padding: 10px 20px;
            margin-bottom: 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            font-size: 13px;
        }
        .toolbar-left, .toolbar-center {
            display: flex;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .toolbar-separator {
            width: 1px;
            height: 24px;
            background: #dee2e6;
            margin: 0 4px;
        }
        .btn-toolbar {
            padding: 6px 14px;
            border-radius: 6px;
            border: 1px solid transparent;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease-in-out;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            text-decoration: none;
        }
        .btn-print { background: #0d6efd; color: #fff; }
        .btn-print:hover { background: #0b5ed7; }
        .btn-close-print { background: #6c757d; color: #fff; }
        .btn-close-print:hover { background: #5c636a; }

        .sig-option-group {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8f9fa;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid #e9ecef;
        }
        .sig-label {
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 5px;
            cursor: pointer;
            color: #212529;
            margin: 0;
            user-select: none;
        }
        .sig-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
        }
        .badge-success { background: #d1e7dd; color: #0f5132; }
        .badge-warning { background: #fff3cd; color: #664d03; }

        .btn-sig-upload {
            background: #e7f1ff;
            color: #0d6efd;
            border: 1px solid #b6d4fe;
            border-radius: 4px;
            padding: 3px 8px;
            font-size: 11px;
            cursor: pointer;
            font-weight: 500;
            transition: background 0.15s;
        }
        .btn-sig-upload:hover {
            background: #cfe2ff;
        }
        .input-date {
            padding: 3px 8px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            font-size: 12px;
            font-family: inherit;
        }

        .signature-space {
            min-height: 70px;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 4px 0;
            position: relative;
        }
        .signature-img {
            max-height: 65px;
            max-width: 180px;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .d-none { display: none !important; }

        /* Container Lembar Kerja */
        .sheet-container {
            width: 210mm;
            min-height: 297mm;
            padding: 18mm 20mm;
            margin: 0 auto 30px auto;
            background: #ffffff;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        /* Header LKPD */
        .lkpd-header {
            text-align: center;
            border-bottom: 3px double #222;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .lkpd-school-title {
            font-size: 13pt;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }
        .lkpd-main-title {
            font-size: 16pt;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #0b3d91;
            margin: 6px 0 2px 0;
        }
        .lkpd-sub-title {
            font-size: 11pt;
            font-style: italic;
            color: #444;
        }

        /* Box Identitas Siswa */
        .meta-box {
            border: 1px solid #333;
            border-radius: 4px;
            padding: 8px 12px;
            margin-bottom: 18px;
            background: #fafafa;
            font-size: 10.5pt;
        }
        .meta-table {
            width: 100%;
            border-collapse: collapse;
        }
        .meta-table td {
            padding: 3px 6px;
            vertical-align: middle;
        }
        .dotted-line {
            border-bottom: 1px dotted #555;
            display: inline-block;
            width: 90%;
            height: 14px;
        }

        /* Konten Utama LKPD */
        .lkpd-content {
            font-size: 11pt;
            line-height: 1.5;
        }
        .lkpd-content h3, .lkpd-content h4, .lkpd-content h5 {
            color: #0b3d91;
            margin-top: 18px;
            margin-bottom: 8px;
            border-bottom: 1px solid #dcdcdc;
            padding-bottom: 4px;
        }
        .lkpd-content h3 { font-size: 13pt; }
        .lkpd-content h4 { font-size: 12pt; }
        
        /* Styling SVG & Ilustrasi Visual */
        .lkpd-content svg {
            display: block;
            margin: 12px auto;
            max-width: 100% !important;
            height: auto !important;
            border-radius: 6px;
            filter: drop-shadow(0 1px 3px rgba(0,0,0,0.06));
        }
        .lkpd-content figure {
            text-align: center;
            margin: 14px 0;
        }
        .lkpd-content figcaption {
            font-size: 9.5pt;
            color: #555;
            font-style: italic;
            margin-top: 4px;
        }

        /* Tabel Observasi / Aktivitas */
        .lkpd-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 10.5pt;
        }
        .lkpd-content th, .lkpd-content td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
            vertical-align: top;
        }
        .lkpd-content th {
            background-color: #f1f3f5;
            font-weight: bold;
            text-align: center;
        }

        /* Kotak Jawaban / Catatan Siswa */
        .answer-box {
            border: 1px dashed #666;
            background: #fff;
            min-height: 80px;
            border-radius: 4px;
            margin: 8px 0 14px 0;
            padding: 8px;
            position: relative;
        }
        .answer-box::before {
            content: "Area Jawaban Siswa:";
            display: block;
            font-size: 8.5pt;
            color: #888;
            font-style: italic;
            margin-bottom: 4px;
        }
        .answer-lines {
            background-image: repeating-linear-gradient(transparent, transparent 23px, #ccc 24px);
            line-height: 24px;
            min-height: 96px;
            margin: 8px 0 14px 0;
        }

        /* Kotak Refleksi Diri */
        .reflection-card {
            background: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 10px 14px;
            margin-top: 20px;
            border-radius: 0 4px 4px 0;
        }

        /* Footer Tanda Tangan */
        .lkpd-footer-signature {
            margin-top: 30px;
            width: 100%;
            display: flex;
            justify-content: space-between;
            page-break-inside: avoid;
            font-size: 10.5pt;
        }
        .sig-block {
            width: 45%;
            text-align: center;
        }

        /* Print Specific Media Rules */
        @media print {
            body {
                background: #ffffff;
            }
            .no-print, .print-toolbar {
                display: none !important;
            }
            .sheet-container {
                width: 100% !important;
                min-height: auto !important;
                margin: 0 !important;
                padding: 0 !important;
                box-shadow: none !important;
            }
            .lkpd-main-title, .lkpd-content h3, .lkpd-content h4 {
                color: #000000 !important;
            }
            .meta-box, .reflection-card {
                background: #ffffff !important;
            }
            .lkpd-content table th {
                background-color: #eee !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>

    <!-- Toolbar Kontrol Cetak (Tidak tercetak) -->
    <div class="print-toolbar no-print">
        <div class="toolbar-left">
            <button onclick="window.print()" class="btn-toolbar btn-print">
                🖨️ Cetak LKPD (A4)
            </button>
            <button onclick="window.close()" class="btn-toolbar btn-close-print">
                ✖ Tutup
            </button>
            <span style="color:#555; font-size:12px; margin-left:5px;">
                Pertemuan ke-<?= esc($pertemuan) ?> &bull; <strong><?= esc($subject['name'] ?? 'Mata Pelajaran') ?></strong> (Kelas <?= esc($class['name'] ?? '-') ?>)
            </span>
        </div>

        <div class="toolbar-center">
            <!-- Opsi TTD Guru Mapel -->
            <div class="sig-option-group">
                <label class="sig-label">
                    <input type="checkbox" id="chk_ttd_guru" <?= !empty($teacher_sig_base64) ? 'checked' : '' ?> onchange="toggleSignature('guru', this.checked)">
                    <span>TTD Guru</span>
                </label>
                <span id="badge_guru" class="sig-badge <?= !empty($teacher_sig_base64) ? 'badge-success' : 'badge-warning' ?>">
                    <?= !empty($teacher_sig_base64) ? 'Scan Tersedia' : 'Belum Ada' ?>
                </span>
                <button type="button" class="btn-sig-upload" onclick="triggerUpload('teacher')">
                    📁 <?= !empty($teacher_sig_base64) ? 'Ganti TTD' : 'Upload TTD' ?>
                </button>
            </div>

            <div class="toolbar-separator"></div>

            <!-- Opsi Titimangsa Tanggal -->
            <div class="sig-option-group">
                <label class="sig-label" style="font-size: 11px; color: #555;">Titimangsa:</label>
                <input type="date" id="input_titimangsa_date" value="<?= esc($current_date_iso) ?>" class="input-date" onchange="updateTitimangsaDate(this.value)">
            </div>
        </div>

        <div class="toolbar-right">
            <small style="color:#6c757d;">
                💡 Tips: Simpan sebagai PDF atau cetak ke kertas A4.
            </small>
        </div>
    </div>

    <!-- Hidden input untuk trigger upload file tanda tangan -->
    <input type="file" id="input_sig_file" accept="image/png,image/jpeg,image/webp" style="display:none;" onchange="handleFileSelected(this)">

    <!-- Lembar Kerja Peserta Didik (Container A4) -->
    <div class="sheet-container">
        <!-- Header -->
        <div class="lkpd-header">
            <div class="lkpd-school-title"><?= esc($school['name'] ?? 'SEKOLAH') ?></div>
            <div class="lkpd-main-title">LEMBAR KERJA PESERTA DIDIK (LKPD)</div>
            <div class="lkpd-sub-title">Pertemuan ke-<?= esc($pertemuan) ?>: <?= esc($lkpd['title'] ?? ('Topik Pertemuan ' . $pertemuan)) ?></div>
        </div>

        <!-- Box Identitas Siswa -->
        <div class="meta-box">
            <table class="meta-table">
                <tr>
                    <td width="15%"><strong>Nama Siswa/Kel:</strong></td>
                    <td width="45%"><span class="dotted-line"></span></td>
                    <td width="15%"><strong>Mata Pelajaran:</strong></td>
                    <td width="25%"><?= esc($subject['name'] ?? '-') ?></td>
                </tr>
                <tr>
                    <td><strong>Kelas / Fase:</strong></td>
                    <td><?= esc($class['name'] ?? '-') ?> <?= !empty($atp['fase']) ? ' / Fase ' . esc($atp['fase']) : '' ?></td>
                    <td><strong>Hari / Tgl:</strong></td>
                    <td><span class="dotted-line" style="width: 80%;"></span></td>
                </tr>
                <tr>
                    <td><strong>Lingkup Materi:</strong></td>
                    <td colspan="3"><?= esc($atp['lingkup_materi'] ?? '-') ?></td>
                </tr>
            </table>
        </div>

        <!-- Konten Hasil AI / Input Guru -->
        <div class="lkpd-content">
            <?= $lkpd['content'] ?? '' ?>
        </div>

        <!-- Tanda Tangan Feedback Guru & Nilai -->
        <div class="lkpd-footer-signature">
            <div class="sig-block">
                <div>Catatan / Feedback Guru:</div>
                <div style="border: 1px dotted #888; height: 75px; margin-top: 5px; border-radius: 4px; padding: 6px; font-size: 9pt; color: #777; text-align: left;">
                    <em>Catatan / Penilaian Guru:</em>
                </div>
            </div>
            <div class="sig-block" style="text-align: right;">
                <div style="text-align: center; display: inline-block; min-width: 220px;">
                    <div><?= esc($school['city_regency'] ?? ($school['city'] ?? 'Indramayu')) ?>, <span id="doc_titimangsa_date"><?= esc($formatted_date) ?></span></div>
                    <div style="margin-top: 4px;">Guru Mata Pelajaran,</div>
                    <div class="signature-space">
                        <img id="img_ttd_guru" 
                             class="signature-img <?= empty($teacher_sig_base64) ? 'd-none' : '' ?>" 
                             src="<?= $teacher_sig_base64 ?: '' ?>" 
                             alt="Tanda Tangan Guru">
                    </div>
                    <strong><?= esc($teacher['name'] ?? 'Guru Pengampu') ?></strong><br>
                    <?php if (!empty($teacher['nip'])): ?>
                        <span>NIP. <?= esc($teacher['nip']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentUploadType = null;
    const teacherId = <?= (int)($teacher_id ?? 0) ?>;
    const uploadUrl = '<?= base_url("admin/administrasi-guru/modul-ajar/upload-signature") ?>';
    const csrfName  = '<?= csrf_token() ?>';
    let csrfHash    = '<?= csrf_hash() ?>';

    // Inisialisasi status tanda tangan dari localStorage jika pernah disimpan
    document.addEventListener('DOMContentLoaded', function () {
        const savedGuru = localStorage.getItem('lkpd_ttd_guru');

        if (savedGuru !== null) {
            const isChecked = savedGuru === '1';
            const chk = document.getElementById('chk_ttd_guru');
            const img = document.getElementById('img_ttd_guru');
            if (img && img.getAttribute('src')) {
                chk.checked = isChecked;
                if (!isChecked) img.classList.add('d-none');
                else img.classList.remove('d-none');
            }
        }
    });

    function toggleSignature(type, show) {
        const img = document.getElementById('img_ttd_guru');
        if (!img) return;

        if (show) {
            if (!img.getAttribute('src')) {
                alert('Tanda tangan scan Guru belum diunggah. Silakan klik tombol "Upload TTD".');
                document.getElementById('chk_ttd_guru').checked = false;
                return;
            }
            img.classList.remove('d-none');
        } else {
            img.classList.add('d-none');
        }

        localStorage.setItem('lkpd_ttd_guru', show ? '1' : '0');
    }

    function triggerUpload(type) {
        currentUploadType = type;
        const fileInput = document.getElementById('input_sig_file');
        fileInput.value = '';
        fileInput.click();
    }

    function handleFileSelected(input) {
        if (!input.files || !input.files[0]) return;
        const file = input.files[0];

        if (file.size > 2 * 1024 * 1024) {
            alert('Ukuran file maksimal 2 MB.');
            return;
        }

        const formData = new FormData();
        formData.append('type', currentUploadType);
        formData.append('teacher_id', teacherId);
        formData.append('signature_file', file);
        formData.append(csrfName, csrfHash);

        const targetBadge = document.getElementById('badge_guru');
        const prevBadgeText = targetBadge ? targetBadge.textContent : '';
        if (targetBadge) targetBadge.textContent = 'Mengunggah...';

        fetch(uploadUrl, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(async res => {
            const data = await res.json();
            return data;
        })
        .then(data => {
            if (data.success && data.base64) {
                const img = document.getElementById('img_ttd_guru');
                if (img) {
                    img.src = data.base64;
                    img.classList.remove('d-none');
                }
                const chk = document.getElementById('chk_ttd_guru');
                if (chk) chk.checked = true;
                if (targetBadge) {
                    targetBadge.className = 'sig-badge badge-success';
                    targetBadge.textContent = 'Scan Tersedia';
                }
                localStorage.setItem('lkpd_ttd_guru', '1');
                alert(data.message || 'Tanda tangan berhasil diperbarui!');
            } else {
                if (targetBadge) targetBadge.textContent = prevBadgeText;
                alert(data.message || 'Gagal mengunggah tanda tangan.');
            }
        })
        .catch(err => {
            if (targetBadge) targetBadge.textContent = prevBadgeText;
            alert('Terjadi kesalahan saat mengunggah: ' + err.message);
        });
    }

    function updateTitimangsaDate(val) {
        if (!val) return;
        const months = [
            'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
            'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
        ];
        const parts = val.split('-');
        if (parts.length === 3) {
            const d = parseInt(parts[2], 10);
            const m = months[parseInt(parts[1], 10) - 1] || '';
            const y = parts[0];
            const formatted = d + ' ' + m + ' ' + y;
            const el = document.getElementById('doc_titimangsa_date');
            if (el) el.textContent = formatted;
        }
    }
    </script>
</body>
</html>
