<!-- Modal Export Kop -->
<div class="modal fade" id="modalExportKop" tabindex="-1" aria-labelledby="modalExportKopLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalExportKopLabel">Pengaturan Ekspor Dokumen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" value="1" id="useKopCheckbox" checked>
          <label class="form-check-label" for="useKopCheckbox">
            Gunakan Kop Surat pada dokumen ini
          </label>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="button" class="btn btn-primary" id="btn-lanjut-export">Lanjutkan Cetak</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let exportUrl = '';
    
    document.querySelectorAll('.btn-export').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            exportUrl = this.getAttribute('data-href');
            var exportModal = new bootstrap.Modal(document.getElementById('modalExportKop'));
            exportModal.show();
        });
    });

    document.getElementById('btn-lanjut-export').addEventListener('click', function() {
        const useKop = document.getElementById('useKopCheckbox').checked ? 1 : 0;
        const separator = exportUrl.indexOf('?') !== -1 ? '&' : '?';
        const finalUrl = exportUrl + separator + 'use_kop=' + useKop;
        
        window.open(finalUrl, '_blank');
        
        var modalEl = document.getElementById('modalExportKop');
        var modal = bootstrap.Modal.getInstance(modalEl);
        modal.hide();
    });
});
</script>
