<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-8 mx-auto">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><?= esc($title) ?></h5>
                <a href="<?= base_url('admin/teaching-journal') ?>" class="btn btn-sm btn-light">⬅️ Kembali</a>
            </div>
            <div class="card-body">
                <form action="<?= base_url('admin/teaching-journal/store') ?>" method="post">
                    <input type="hidden" name="id" value="<?= $journal['id'] ?? '' ?>">
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Tanggal</label>
                            <input type="date" name="date" class="form-control" value="<?= $journal['date'] ?? $today ?>" required>
                        </div>
                        
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kelas</label>
                            <select name="class_id" id="class_id" class="form-select" required>
                                <option value="">-- Pilih Kelas --</option>
                                <?php foreach ($classes as $c): ?>
                                    <option value="<?= $c['id'] ?>" <?= ($journal['class_id'] ?? $selected_class) == $c['id'] ? 'selected' : '' ?>>
                                        <?= esc($c['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Mata Pelajaran</label>
                            <select name="subject_id" id="subject_id" class="form-select" required>
                                <option value="">-- Pilih Mapel --</option>
                                <?php foreach ($subjects as $s): ?>
                                    <option value="<?= $s['id'] ?>" <?= ($journal['subject_id'] ?? $selected_subject) == $s['id'] ? 'selected' : '' ?>>
                                        <?= esc($s['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Lingkup Materi / ATP</label>
                            <select name="atp_id" id="atp_id" class="form-select" required>
                                <option value="">-- Pilih Materi --</option>
                                <!-- Will be loaded via AJAX -->
                            </select>
                            <small class="text-muted">Format: Sem[X] | Lingkup Materi (Elemen CP)</small>
                        </div>

                        <div class="col-12">
                            <label class="form-label fw-bold">Catatan Proses Pembelajaran</label>
                            <textarea name="notes" class="form-control" rows="5" placeholder="Contoh: Menyampaikan materi tentang..., siswa aktif bertanya mengenai..., evaluasi dilakukan dengan..." required><?= $journal['notes'] ?? '' ?></textarea>
                        </div>
                    </div>

                    <div class="mt-4 text-end">
                        <button type="submit" class="btn btn-success px-4">💾 Simpan Jurnal</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const classSelect = document.getElementById('class_id');
        const subjectSelect = document.getElementById('subject_id');
        const atpSelect = document.getElementById('atp_id');
        const currentAtpId = '<?= $journal['atp_id'] ?? '' ?>';

        function loadAtps() {
            const classId = classSelect.value;
            const subjectId = subjectSelect.value;

            if (classId && subjectId) {
                fetch(`<?= base_url('admin/teaching-journal/get-atps') ?>?class_id=${classId}&subject_id=${subjectId}`)
                    .then(response => response.json())
                    .then(data => {
                        atpSelect.innerHTML = '<option value="">-- Pilih Elemen --</option>';
                        data.forEach(atp => {
                            const option = document.createElement('option');
                            option.value = atp.id;
                            option.textContent = `Sem${atp.semester} | ${atp.lingkup_materi} (${atp.elemen})`;
                            if (atp.id == currentAtpId) {
                                option.selected = true;
                            }
                            atpSelect.appendChild(option);
                        });
                    })
                    .catch(error => console.error('Error loading ATPs:', error));
            } else {
                atpSelect.innerHTML = '<option value="">-- Pilih Elemen --</option>';
            }
        }

        classSelect.addEventListener('change', loadAtps);
        subjectSelect.addEventListener('change', loadAtps);

        // Initial load if editing or pre-selected
        if (classSelect.value && subjectSelect.value) {
            loadAtps();
        }
    });
</script>

<?= $this->endSection() ?>
