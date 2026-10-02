<?= $this->extend('layouts/app') ?>

<?= $this->section('content') ?>
<div class="page-heading">
    <div class="page-title">
        <div class="row">
            <div class="col-12 col-md-8 order-md-1 order-last">
                <h3><?= esc($title) ?></h3>
                <p class="text-subtitle text-muted">Jawablah pertanyaan berikut dengan jujur sesuai kondisi Anda.</p>
            </div>
            <div class="col-12 col-md-4 order-md-2 order-first text-end">
                <a href="<?= base_url('siswa/asesmen') ?>" class="btn btn-outline-secondary"><i class="bi bi-arrow-left"></i> Kembali</a>
            </div>
        </div>
    </div>

    <section class="section">
        <form action="<?= base_url('siswa/asesmen/submit/' . $assignment['id']) ?>" method="POST" id="assessmentForm">
            <?= csrf_field() ?>
            
            <div class="row">
                <div class="col-md-3 d-none d-md-block">
                    <!-- Navigasi Domain/Aspek -->
                    <div class="card shadow-sm sticky-top" style="top: 20px;">
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush" id="list-tab" role="tablist">
                                <?php $i = 0; foreach ($domains as $d): ?>
                                    <a class="list-group-item list-group-item-action <?= $i === 0 ? 'active' : '' ?>" id="list-domain-<?= $d['id'] ?>-list" data-bs-toggle="list" href="#list-domain-<?= $d['id'] ?>" role="tab">
                                        <?= esc($d['domain_name']) ?>
                                    </a>
                                <?php $i++; endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-9">
                    <!-- Konten Pertanyaan -->
                    <div class="tab-content" id="nav-tabContent">
                        <?php $i = 0; foreach ($domains as $d): ?>
                            <div class="tab-pane fade <?= $i === 0 ? 'show active' : '' ?>" id="list-domain-<?= $d['id'] ?>" role="tabpanel">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="m-0 text-white"><?= esc($d['domain_name']) ?></h5>
                                    </div>
                                    <div class="card-body">
                                        <?php 
                                        $no = 1;
                                        foreach ($questions as $q): 
                                            if ($q['domain_id'] != $d['id']) continue;
                                            
                                            $ans = $savedAnswers[$q['id']] ?? null;
                                        ?>
                                            <div class="mb-4 pb-4 border-bottom question-item">
                                                <p class="fw-bold mb-3"><?= $no++ ?>. <?= esc($q['question_text']) ?></p>
                                                
                                                <div class="row">
                                                    <?php foreach ($scale as $s): ?>
                                                        <div class="col-6 col-md-3 mb-2">
                                                            <div class="form-check form-check-inline w-100 p-0 m-0">
                                                                <input type="radio" class="btn-check answer-radio" 
                                                                       name="q_<?= $q['id'] ?>" 
                                                                       id="q_<?= $q['id'] ?>_<?= $s['value'] ?>" 
                                                                       value="<?= $s['value'] ?>" 
                                                                       data-qid="<?= $q['id'] ?>"
                                                                       <?= ($ans && $ans['text_answer'] == $s['value']) ? 'checked' : '' ?>
                                                                       required>
                                                                <label class="btn btn-outline-primary w-100 text-start h-100" for="q_<?= $q['id'] ?>_<?= $s['value'] ?>">
                                                                    <small><?= esc($s['label']) ?></small>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="card-footer bg-light text-end">
                                        <?php if ($i > 0): ?>
                                            <button type="button" class="btn btn-secondary me-2" onclick="prevTab(<?= $i-1 ?>)">Sebelumnya</button>
                                        <?php endif; ?>
                                        
                                        <?php if ($i < count($domains) - 1): ?>
                                            <button type="button" class="btn btn-primary" onclick="nextTab(<?= $i+1 ?>)">Selanjutnya <i class="bi bi-arrow-right"></i></button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-success" onclick="submitAssessment()"><i class="bi bi-check2-circle"></i> Selesai & Submit</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php $i++; endforeach; ?>
                    </div>
                </div>
            </div>
        </form>
    </section>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
    const saveUrl = '<?= base_url('siswa/asesmen/save-answer') ?>';
    const assignmentStudentId = <?= $assignment['id'] ?>;
    const csrfName = '<?= csrf_token() ?>';
    const csrfHash = '<?= csrf_hash() ?>';

    // Auto-save via AJAX
    document.querySelectorAll('.answer-radio').forEach(radio => {
        radio.addEventListener('change', function() {
            const qId = this.getAttribute('data-qid');
            const value = this.value;

            let formData = new FormData();
            formData.append('assignment_student_id', assignmentStudentId);
            formData.append('question_id', qId);
            formData.append('value', value);
            formData.append(csrfName, csrfHash);

            fetch(saveUrl, {
                method: 'POST',
                body: formData,
                headers: {'X-Requested-With': 'XMLHttpRequest'}
            })
            .then(res => res.json())
            .then(data => {
                // Success indicator optional (e.g., small green check)
                if(data.status === 'success') {
                    // console.log('Saved Q'+qId);
                }
            });
        });
    });

    function nextTab(index) {
        const tabs = document.querySelectorAll('#list-tab .list-group-item');
        if (tabs[index]) {
            let tab = new bootstrap.Tab(tabs[index]);
            tab.show();
            window.scrollTo(0,0);
        }
    }

    function prevTab(index) {
        const tabs = document.querySelectorAll('#list-tab .list-group-item');
        if (tabs[index]) {
            let tab = new bootstrap.Tab(tabs[index]);
            tab.show();
            window.scrollTo(0,0);
        }
    }

    function submitAssessment() {
        // Validate required
        const form = document.getElementById('assessmentForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        if (confirm('Apakah Anda yakin telah mengisi semua dengan benar? Jawaban tidak dapat diubah setelah disubmit.')) {
            form.submit();
        }
    }
</script>
<?= $this->endSection() ?>
