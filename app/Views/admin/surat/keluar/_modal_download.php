<?php
// 🎯 Standar Skill Diterapkan: high-end-visual-design (UI)
// Refs: rekap-file-md/TandaTanganCap/Implementation-Plan-TandaTanganCap.md

$hasSig  = false;
$hasSeal = false;
$defSig  = false;
$defSeal = false;

try {
    $settings = new \App\Models\SettingsModel();
    $sigPath  = $settings->getValue('ks_signature_path');
    $sealPath = $settings->getValue('school_seal_path');
    $hasSig   = !empty($sigPath) && is_file(FCPATH . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sigPath), DIRECTORY_SEPARATOR));
    $hasSeal  = !empty($sealPath) && is_file(FCPATH . ltrim(str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $sealPath), DIRECTORY_SEPARATOR));
    $defSig   = (bool) $settings->getValue('use_signature');
    $defSeal  = (bool) $settings->getValue('use_seal');
} catch (\Throwable $e) {
    // Graceful fallback jika terjadi kegagalan koneksi/query settings
}
?>

<!-- ===== MODAL KONFIRMASI DOWNLOAD PDF ===== -->
<div class="modal fade" id="modalDownloadPdf" tabindex="-1" aria-labelledby="modalDownloadPdfLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg rounded-3">
      
      <!-- Header -->
      <div class="modal-header bg-light border-bottom py-3 px-4">
        <div class="d-flex align-items-center gap-2">
          <div class="p-2 bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center" style="width:38px;height:38px;">
            <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
          </div>
          <div>
            <h6 class="modal-title fw-bold text-dark mb-0" id="modalDownloadPdfLabel">Opsi Unduh Dokumen PDF</h6>
            <small class="text-muted" style="font-size:0.75rem;">Sesuaikan pembubuhan tanda tangan &amp; stempel resmi</small>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <!-- Body -->
      <div class="modal-body p-4">
        
        <!-- Info Dokumen Singkat -->
        <div class="p-3 bg-light rounded-3 border mb-3">
          <div class="d-flex justify-content-between align-items-center mb-1">
            <span class="text-uppercase fw-semibold text-muted" style="font-size:0.68rem; letter-spacing:0.04em;">Nomor Surat:</span>
            <span id="modal-download-badge-type" class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size:0.7rem;">Surat</span>
          </div>
          <div id="modal-download-letter-number" class="fw-bold font-monospace text-primary fs-6 mb-1">...</div>
          <div class="text-muted small text-truncate" id="modal-download-recipient">Penerima: -</div>
        </div>

        <p class="small text-muted mb-3" style="font-size:0.8rem;">
          Pilih kelengkapan pengesahan digital yang ingin dicetak pada dokumen PDF:
        </p>

        <!-- Pilihan 1: Tanda Tangan Kepala Sekolah -->
        <div class="card border rounded-3 p-3 mb-2.5 transition-all <?= $hasSig ? 'border-success-subtle bg-success-subtle bg-opacity-10' : 'bg-light opacity-75' ?>">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" role="switch"
                     id="modal_switch_signature"
                     <?= $hasSig && $defSig ? 'checked' : '' ?>
                     <?= !$hasSig ? 'disabled' : '' ?>>
              <label class="form-check-label fw-semibold text-dark small" for="modal_switch_signature">
                Tanda Tangan Kepala Sekolah
              </label>
              <small class="text-muted d-block" style="font-size:0.73rem;">
                Disisipkan tepat di atas nama Kepala Sekolah
              </small>
            </div>
            <div>
              <?php if ($hasSig): ?>
                <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-2" style="font-size:0.68rem;">
                  <i class="bi bi-check-circle-fill me-0.5"></i> Siap
                </span>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2" style="font-size:0.68rem;" title="Unggah berkas di menu Pengaturan KOP">
                  <i class="bi bi-x-circle me-0.5"></i> Belum ada
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Pilihan 2: Cap / Stempel Sekolah -->
        <div class="card border rounded-3 p-3 mb-3 transition-all <?= $hasSeal ? 'border-warning-subtle bg-warning-subtle bg-opacity-10' : 'bg-light opacity-75' ?>">
          <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="form-check form-switch mb-0">
              <input class="form-check-input" type="checkbox" role="switch"
                     id="modal_switch_seal"
                     <?= $hasSeal && $defSeal ? 'checked' : '' ?>
                     <?= !$hasSeal ? 'disabled' : '' ?>>
              <label class="form-check-label fw-semibold text-dark small" for="modal_switch_seal">
                Cap / Stempel Sekolah
              </label>
              <small class="text-muted d-block" style="font-size:0.73rem;">
                Stempel dinas resmi (posisi proporsional di area tanda tangan)
              </small>
            </div>
            <div>
              <?php if ($hasSeal): ?>
                <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle rounded-pill px-2" style="font-size:0.68rem;">
                  <i class="bi bi-check-circle-fill me-0.5"></i> Siap
                </span>
              <?php else: ?>
                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle rounded-pill px-2" style="font-size:0.68rem;" title="Unggah berkas di menu Pengaturan KOP">
                  <i class="bi bi-x-circle me-0.5"></i> Belum ada
                </span>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="alert alert-info py-2 px-3 rounded-2 border-0 bg-info bg-opacity-10 text-info-emphasis d-flex align-items-center gap-2 mb-0" style="font-size:0.73rem;">
          <i class="bi bi-info-circle-fill fs-6 flex-shrink-0"></i>
          <span>Jika opsi tidak dicentang, ruang tanda tangan tetap tersedia untuk tanda tangan basah &amp; cap fisik.</span>
        </div>

      </div>

      <!-- Footer -->
      <div class="modal-footer bg-light border-top py-2.5 px-4 d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">
          Batal
        </button>
        <div class="d-flex gap-2">
          <a href="#" id="modal-btn-preview" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3 shadow-xs">
            <i class="bi bi-eye me-1"></i>Pratinjau
          </a>
          <a href="#" id="modal-btn-download" class="btn btn-success btn-sm rounded-pill px-4 shadow-xs">
            <i class="bi bi-download me-1"></i>Unduh PDF
          </a>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  let activeLetterId = null;

  const modalEl = document.getElementById('modalDownloadPdf');
  if (!modalEl) return;

  const lblNumber    = document.getElementById('modal-download-letter-number');
  const lblRecipient = document.getElementById('modal-download-recipient');
  const badgeType    = document.getElementById('modal-download-badge-type');
  const switchSig    = document.getElementById('modal_switch_signature');
  const switchSeal   = document.getElementById('modal_switch_seal');
  const btnDownload  = document.getElementById('modal-btn-download');
  const btnPreview   = document.getElementById('modal-btn-preview');

  function updateActionUrls() {
    if (!activeLetterId) return;

    const withSig  = switchSig && switchSig.checked ? '1' : '0';
    const withSeal = switchSeal && switchSeal.checked ? '1' : '0';

    const baseUrl = '<?= rtrim(base_url(), '/') ?>';
    const downloadUrl = `${baseUrl}/admin/surat-keluar/download/${activeLetterId}?with_signature=${withSig}&with_seal=${withSeal}`;
    const previewUrl  = `${baseUrl}/admin/surat-keluar/view-pdf/${activeLetterId}?with_signature=${withSig}&with_seal=${withSeal}`;

    btnDownload.setAttribute('href', downloadUrl);
    btnPreview.setAttribute('href', previewUrl);
  }

  // Delegasi klik tombol pemicu modal download
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-trigger-download');
    if (!btn) return;

    activeLetterId = btn.getAttribute('data-letter-id');
    const letterNumber = btn.getAttribute('data-letter-number') || '-';
    const recipient    = btn.getAttribute('data-recipient-name') || '-';
    const letterType   = btn.getAttribute('data-letter-type') || 'Surat';

    if (lblNumber) lblNumber.textContent = letterNumber;
    if (lblRecipient) lblRecipient.textContent = 'Penerima: ' + recipient;
    if (badgeType) badgeType.textContent = letterType;

    updateActionUrls();
  });

  if (switchSig)  switchSig.addEventListener('change', updateActionUrls);
  if (switchSeal) switchSeal.addEventListener('change', updateActionUrls);

  // Tutup modal otomatis sesaat setelah download dimulai
  if (btnDownload) {
    btnDownload.addEventListener('click', function () {
      setTimeout(function () {
        const modalInstance = bootstrap.Modal.getInstance(modalEl);
        if (modalInstance) {
          modalInstance.hide();
        }
      }, 700);
    });
  }
});
</script>
