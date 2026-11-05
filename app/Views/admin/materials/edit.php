<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex align-items-center justify-content-between mb-3">
  <h5 class="fw-bold mb-0">
    <i class="bi bi-pencil me-2 text-warning"></i><?= esc($title) ?>
  </h5>
  <a href="<?= site_url('admin/materials/' . $subject['id']) ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if ($isSubMat): ?>
  <div class="alert alert-info py-2 small mb-3">
    <i class="bi bi-journals me-2"></i>Sub Materi — level dan materi induk tidak bisa diubah.
  </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="card-body">
    <form method="post" action="<?= site_url('admin/materials/update/' . $material['id']) ?>"
          enctype="multipart/form-data">
      <?= csrf_field() ?>
      <input type="hidden" name="subject_id" value="<?= $subject['id'] ?>">
      <input type="hidden" name="year_id"    value="<?= $activeYear['id'] ?? $material['year_id'] ?>">

      <div class="row g-3">

        <!-- Level (hanya Materi induk) -->
        <?php if (!$isSubMat): ?>
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Level Kelas</label>
          <select name="level" class="form-select form-select-sm">
            <option value="0" <?= $material['level'] == 0 ? 'selected' : '' ?>>Semua Level</option>
            <?php foreach ($levels as $lv): ?>
              <option value="<?= $lv ?>" <?= $material['level'] == $lv ? 'selected' : '' ?>>
                Kelas <?= $lv ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php else: ?>
          <input type="hidden" name="level" value="<?= $material['level'] ?>">
        <?php endif; ?>

        <!-- Semester -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Semester</label>
          <select name="semester" class="form-select form-select-sm">
            <option value="1" <?= $material['semester'] == 1 ? 'selected' : '' ?>>Ganjil</option>
            <option value="2" <?= $material['semester'] == 2 ? 'selected' : '' ?>>Genap</option>
          </select>
        </div>

        <!-- Urutan -->
        <div class="col-md-4">
          <label class="form-label small fw-semibold">Urutan Tampil</label>
          <input type="number" name="sort_order" class="form-control form-control-sm"
                 min="0" value="<?= esc($material['sort_order'] ?? 0) ?>">
        </div>

        <!-- Judul -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Judul <span class="text-danger">*</span></label>
          <input type="text" name="title" class="form-control form-control-sm"
                 value="<?= esc($material['title']) ?>" required>
        </div>

        <!-- Deskripsi -->
        <div class="col-12">
          <label class="form-label small fw-semibold">Deskripsi Singkat</label>
          <textarea name="description" class="form-control form-control-sm" rows="2"><?= esc($material['description'] ?? '') ?></textarea>
        </div>

        <?php if ($isSubMat): ?>
          <?php if (!empty($isPublished)): ?>
            <!-- ══════════════════════════════════════════════════════════
                 SUB MATERI SUDAH TERBIT (TERKUNCI / READ-ONLY SEGMEN)
            ═══════════════════════════════════════════════════════════ -->
            <div class="col-12">
              <div class="alert alert-warning py-2 px-3 small mb-3 border border-warning">
                <i class="bi bi-lock-fill me-1"></i><strong>Sub Materi Sudah Diterbitkan (Siap):</strong>
                Langkah pembelajaran (segmen) dan penugasan telah terkunci demi menjaga integritas data pengerjaan dan penilaian siswa. Anda dapat mengubah judul, urutan, atau deskripsi umum di atas.
              </div>

              <?php if (!empty($segments)): ?>
                <div class="card border mb-3">
                  <div class="card-header bg-light py-2 d-flex align-items-center justify-content-between">
                    <span class="fw-bold small"><i class="bi bi-layers me-2 text-primary"></i>Langkah Pembelajaran Aktif (<?= count($segments) ?> Langkah)</span>
                    <span class="badge bg-secondary">Total Durasi: <?= array_sum(array_column($segments, 'estimated_minutes')) ?> Menit</span>
                  </div>
                  <div class="list-group list-group-flush">
                    <?php foreach ($segments as $sIdx => $seg): ?>
                      <div class="list-group-item py-2">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                          <div class="d-flex align-items-center gap-2">
                            <span class="badge bg-primary">Langkah #<?= $sIdx + 1 ?></span>
                            <span class="fw-semibold small"><?= esc($seg['title']) ?></span>
                            <span class="badge bg-light text-secondary border"><?= strtoupper(esc($seg['content_type'])) ?></span>
                            <span class="text-muted smaller"><i class="bi bi-clock me-1"></i><?= (int)($seg['estimated_minutes'] ?? 10) ?> Menit</span>
                          </div>
                          <?php if (!empty($seg['has_task'])): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle">
                              <i class="bi bi-clipboard-check me-1"></i><?= esc($seg['task']['judul'] ?? 'Ada Penugasan Formatif') ?>
                            </span>
                          <?php endif; ?>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>

          <?php else: ?>
            <!-- ══════════════════════════════════════════════════════════
                 SUB MATERI BELUM DITERBITKAN (EDITABLE SEGMEN & TUGAS)
            ═══════════════════════════════════════════════════════════ -->
            <!-- Mode Penyusunan Sub Materi -->
            <div class="col-12">
              <label class="form-label small fw-semibold d-block">Metode Penyusunan Sub Materi</label>
              <div class="btn-group btn-group-sm w-100" role="group">
                <input type="radio" class="btn-check" name="builder_mode" id="modeMulti" value="multi" <?= (!empty($segments) || old('builder_mode') === 'multi' || empty($material['content_type'])) ? 'checked' : '' ?>>
                <label class="btn btn-outline-primary" for="modeMulti">
                  <i class="bi bi-diagram-3 me-1"></i><strong>Multi-Langkah Beragam Media</strong> (Teks, Video, Simulasi HTML, Slide, PDF, Penugasan)
                </label>
                <input type="radio" class="btn-check" name="builder_mode" id="modeSingle" value="single" <?= (empty($segments) && old('builder_mode') !== 'multi' && !empty($material['content_type'])) ? 'checked' : '' ?>>
                <label class="btn btn-outline-secondary" for="modeSingle">
                  <i class="bi bi-file-earmark-text me-1"></i>Konten Tunggal Sederhana
                </label>
              </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 PANEL MULTI-SEGMEN (MICRO-LEARNING STEPPER DENGAN TUGAS)
            ═══════════════════════════════════════════════════════════ -->
            <div class="col-12" id="sectionMultiSegment">
              <div class="alert alert-primary-subtle border border-primary-subtle py-2 small mb-3">
                <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                <strong>Kelola Segmen Belajar &amp; Tugas:</strong> Anda dapat mengedit materi per langkah, mengubah tipe media, menghapus/menambah langkah baru, atau menyisipkan <strong>Tugas / Aktivitas Formatif</strong> pada setiap langkah pembelajaran sebelum diterbitkan.
              </div>

              <div id="segmentCardsContainer">
                <?php if (!empty($segments)): ?>
                  <?php foreach ($segments as $idx => $seg): ?>
                    <div class="card border border-2 border-primary-subtle shadow-sm mb-3 segment-item" data-seg-idx="<?= $idx ?>">
                      <input type="hidden" name="segments[<?= $idx ?>][id]" value="<?= $seg['id'] ?>" class="seg-input-id">
                      <div class="card-header bg-light d-flex align-items-center justify-content-between py-2">
                        <div class="d-flex align-items-center gap-2">
                          <span class="badge bg-primary seg-badge">Langkah #<?= $idx + 1 ?></span>
                          <span class="fw-semibold small text-truncate seg-label-preview" style="max-width:250px;"><?= esc($seg['title']) ?></span>
                        </div>
                        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-del-seg" title="Hapus Segmen" style="font-size:0.75rem;">
                          <i class="bi bi-trash me-1"></i>Hapus
                        </button>
                      </div>
                      <div class="card-body p-3">
                        <div class="row g-2 mb-3">
                          <div class="col-md-5">
                            <label class="form-label small fw-semibold mb-1">Judul Segmen <span class="text-danger">*</span></label>
                            <input type="text" name="segments[<?= $idx ?>][title]" class="form-control form-control-sm seg-input-title"
                                   value="<?= esc($seg['title']) ?>" required placeholder="cth: Pengenalan Konsep">
                          </div>
                          <div class="col-md-4">
                            <label class="form-label small fw-semibold mb-1">Tipe Konten <span class="text-danger">*</span></label>
                            <select name="segments[<?= $idx ?>][content_type]" class="form-select form-select-sm seg-select-type">
                              <option value="text"  <?= $seg['content_type'] === 'text'  ? 'selected' : '' ?>>Teks &amp; Penjelasan</option>
                              <option value="video" <?= $seg['content_type'] === 'video' ? 'selected' : '' ?>>Video YouTube / Vimeo</option>
                              <option value="html"  <?= $seg['content_type'] === 'html'  ? 'selected' : '' ?>>HTML Interaktif (Simulasi/Praktik)</option>
                              <option value="slide" <?= $seg['content_type'] === 'slide' ? 'selected' : '' ?>>Slide Gambar (Presentasi)</option>
                              <option value="pdf"   <?= $seg['content_type'] === 'pdf'   ? 'selected' : '' ?>>Dokumen PDF</option>
                              <option value="link"  <?= $seg['content_type'] === 'link'  ? 'selected' : '' ?>>Tautan Eksternal</option>
                            </select>
                          </div>
                          <div class="col-md-3">
                            <label class="form-label small fw-semibold mb-1">Estimasi (menit)</label>
                            <input type="number" name="segments[<?= $idx ?>][estimated_minutes]" class="form-control form-control-sm"
                                   min="1" max="180" value="<?= esc($seg['estimated_minutes'] ?? 15) ?>">
                          </div>
                        </div>

                        <!-- Dynamic Content Body per Tipe -->
                        <div class="seg-content-body">
                          <div class="seg-panel seg-panel-text" style="<?= $seg['content_type'] === 'text' ? '' : 'display:none;' ?>">
                            <label class="form-label small fw-semibold mb-1">Penjelasan Teks</label>
                            <textarea name="segments[<?= $idx ?>][content]" class="form-control form-control-sm" rows="6"
                                      placeholder="Tuliskan uraian materi, pengantar, atau teks bacaan di sini..."><?= esc($seg['content'] ?? '') ?></textarea>
                          </div>

                          <div class="seg-panel seg-panel-video" style="<?= $seg['content_type'] === 'video' ? '' : 'display:none;' ?>">
                            <label class="form-label small fw-semibold mb-1">URL Video YouTube / Vimeo</label>
                            <input type="url" name="segments[<?= $idx ?>][video_url]" class="form-control form-control-sm"
                                   value="<?= esc($seg['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=...">
                            <div class="form-text">Masukkan tautan lengkap YouTube/Vimeo. Sistem akan menampilkan player secara otomatis.</div>
                          </div>

                          <div class="seg-panel seg-panel-html" style="<?= $seg['content_type'] === 'html' ? '' : 'display:none;' ?>">
                            <label class="form-label small fw-semibold mb-1">Kode HTML / JS Interaktif</label>
                            <textarea name="segments[<?= $idx ?>][content_html]" class="form-control form-control-sm font-monospace" rows="8"
                                      placeholder="<!DOCTYPE html>... Tempelkan kode HTML, simulasi PhET, atau widget interaktif"><?= esc($seg['content_html'] ?? ($seg['content'] ?? '')) ?></textarea>
                            <div class="form-text text-muted">Akan dijalankan di iframe sandbox yang aman bagi siswa.</div>
                          </div>

                          <div class="seg-panel seg-panel-slide" style="<?= $seg['content_type'] === 'slide' ? '' : 'display:none;' ?>">
                            <label class="form-label small fw-semibold mb-1">Slide Gambar</label>
                            <?php if (!empty($seg['slides'])): ?>
                              <div class="row g-2 mb-2 existing-segment-slides">
                                <?php foreach ($seg['slides'] as $sl): ?>
                                  <div class="col-4 col-md-3" id="seg-slide-<?= $sl['id'] ?>">
                                    <div class="border rounded p-1 h-100 text-center position-relative">
                                      <img src="<?= base_url('uploads/materials/slides/' . esc($sl['image_path'])) ?>"
                                           style="width:100%;height:80px;object-fit:cover;border-radius:4px;">
                                      <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1 btn-del-seg-slide"
                                              data-id="<?= $sl['id'] ?>" title="Hapus slide" style="font-size:0.62rem;">
                                        <i class="bi bi-trash"></i>
                                      </button>
                                    </div>
                                  </div>
                                <?php endforeach; ?>
                              </div>
                            <?php endif; ?>
                            <input type="file" name="segment_slides_<?= $idx ?>[]" class="form-control form-control-sm"
                                   accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                            <div class="form-text">Tambah slide baru (JPG/PNG/WEBP/GIF, maks 5MB per gambar).</div>
                          </div>

                          <div class="seg-panel seg-panel-pdf" style="<?= $seg['content_type'] === 'pdf' ? '' : 'display:none;' ?>">
                            <label class="form-label small fw-semibold mb-1">File Dokumen PDF</label>
                            <?php if (!empty($seg['file_path'])): ?>
                              <div class="mb-2">
                                <a href="<?= base_url('uploads/materials/' . esc($seg['file_path'])) ?>" target="_blank" class="btn btn-sm btn-outline-danger py-0 px-2 small">
                                  <i class="bi bi-file-earmark-pdf me-1"></i>Lihat File PDF Saat Ini
                                </a>
                              </div>
                            <?php endif; ?>
                            <input type="file" name="segment_pdf_<?= $idx ?>" class="form-control form-control-sm" accept=".pdf">
                            <div class="form-text">Kosongkan jika tidak ingin mengubah file PDF (maks 10MB).</div>
                          </div>

                          <div class="seg-panel seg-panel-link" style="<?= $seg['content_type'] === 'link' ? '' : 'display:none;' ?>">
                            <label class="form-label small fw-semibold mb-1">URL Tautan Eksternal</label>
                            <input type="url" name="segments[<?= $idx ?>][external_link]" class="form-control form-control-sm"
                                   value="<?= esc($seg['external_link'] ?? '') ?>" placeholder="https://...">
                            <div class="form-text">Link ke artikel, materi bacaan luar, website referensi, dll.</div>
                          </div>
                        </div>

                        <!-- Opsi Tugas / Tagihan Aktivitas Segmen (Penugasan Formatif) -->
                        <div class="mt-3 pt-3 border-top">
                          <div class="form-check form-switch">
                            <input class="form-check-input seg-task-toggle" type="checkbox" name="segments[<?= $idx ?>][has_task]" value="1" id="segTask_<?= $idx ?>" <?= !empty($seg['has_task']) ? 'checked' : '' ?>>
                            <label class="form-check-label fw-semibold small text-primary" for="segTask_<?= $idx ?>">
                              <i class="bi bi-clipboard-check me-1"></i>Sertakan Tugas / Tagihan Aktivitas pada Langkah Ini (Formatif)
                            </label>
                          </div>
                          <div class="seg-task-details mt-2 p-2 rounded bg-light border" style="<?= !empty($seg['has_task']) ? '' : 'display:none;' ?>">
                            <div class="small text-muted mb-2">
                              <i class="bi bi-info-circle me-1"></i>Siswa wajib mengerjakan &amp; mengumpulkan tugas ini sebelum dapat menyelesaikan langkah dan lanjut ke langkah berikutnya.
                            </div>
                            <div class="row g-2">
                              <div class="col-md-8">
                                <label class="form-label small fw-semibold mb-1">Judul Tugas</label>
                                <input type="text" name="segments[<?= $idx ?>][task_title]" class="form-control form-control-sm seg-task-title"
                                       value="<?= esc($seg['task']['judul'] ?? ('Tugas: ' . $seg['title'])) ?>" placeholder="cth: Refleksi Video &amp; Lembar Analisis">
                              </div>
                              <div class="col-md-4">
                                <label class="form-label small fw-semibold mb-1">Batas Waktu (Hari)</label>
                                <?php
                                  $dDays = 7;
                                  if (!empty($seg['task']['selesai_at']) && !empty($seg['task']['mulai_at'])) {
                                      $diff = strtotime($seg['task']['selesai_at']) - strtotime($seg['task']['mulai_at']);
                                      $dDays = max(1, (int)round($diff / 86400));
                                  }
                                ?>
                                <input type="number" name="segments[<?= $idx ?>][task_deadline_days]" class="form-control form-control-sm seg-task-deadline"
                                       min="1" max="30" value="<?= $dDays ?>">
                              </div>
                              <div class="col-12">
                                <label class="form-label small fw-semibold mb-1">Petunjuk / Instruksi Tugas</label>
                                <textarea name="segments[<?= $idx ?>][task_instructions]" class="form-control form-control-sm seg-task-instructions" rows="2"
                                          placeholder="Tuliskan instruksi pengerjaan yang harus dikerjakan dan dikirimkan oleh siswa..."><?= esc($seg['task']['deskripsi'] ?? '') ?></textarea>
                              </div>
                            </div>
                          </div>
                        </div>

                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>

              <div class="text-center mt-2 mb-3">
                <button type="button" class="btn btn-outline-primary btn-sm px-3" id="btnAddSegment">
                  <i class="bi bi-plus-circle me-1"></i>Tambah Segmen Belajar Baru
                </button>
              </div>
            </div>

            <!-- ══════════════════════════════════════════════════════════
                 PANEL KONTEN TUNGGAL (KLASIK)
            ═══════════════════════════════════════════════════════════ -->
            <div class="col-12" id="sectionSingleContent" style="<?= (!empty($segments) || old('builder_mode') === 'multi' || empty($material['content_type'])) ? 'display: none;' : '' ?>">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Tipe Konten <span class="text-danger">*</span></label>
                  <select name="content_type" id="contentType" class="form-select form-select-sm">
                    <option value="text"  <?= ($material['content_type'] ?? 'text') === 'text'  ? 'selected' : '' ?>>Teks (Editor)</option>
                    <option value="slide" <?= ($material['content_type'] ?? '') === 'slide' ? 'selected' : '' ?>>Slide Gambar</option>
                    <option value="html"  <?= ($material['content_type'] ?? '') === 'html'  ? 'selected' : '' ?>>Halaman HTML Interaktif</option>
                    <option value="pdf"   <?= ($material['content_type'] ?? '') === 'pdf'   ? 'selected' : '' ?>>File PDF</option>
                    <option value="video" <?= ($material['content_type'] ?? '') === 'video' ? 'selected' : '' ?>>Video</option>
                    <option value="link"  <?= ($material['content_type'] ?? '') === 'link'  ? 'selected' : '' ?>>Link</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label small fw-semibold">Estimasi Waktu (menit)</label>
                  <input type="number" name="estimated_minutes" class="form-control form-control-sm"
                         min="1" max="300" value="<?= esc($material['estimated_minutes'] ?? '') ?>">
                </div>

                <?php
                  $curType    = $material['content_type'] ?? 'text';
                  $textValue  = ($curType === 'html') ? '' : ($material['content'] ?? '');
                  $htmlValue  = ($curType === 'html') ? ($material['content'] ?? '') : '';
                ?>
                <div class="col-12" id="panelText">
                  <label class="form-label small fw-semibold">Konten</label>
                  <textarea name="content" id="editorContent" class="form-control" rows="10"><?= esc($textValue) ?></textarea>
                </div>
                <div class="col-12" id="panelHtml" style="display:none;">
                  <label class="form-label small fw-semibold">Kode HTML Interaktif</label>
                  <textarea name="content_html" id="htmlContent" class="form-control" rows="16" spellcheck="false"
                            style="font-family:ui-monospace,SFMono-Regular,Menlo,Consolas,monospace;font-size:0.82rem;line-height:1.5;"
                            placeholder="<!DOCTYPE html> ..."><?= esc($htmlValue) ?></textarea>
                  <div class="alert alert-warning border-0 py-2 small mt-2 mb-0">
                    <i class="bi bi-shield-lock me-1"></i>
                    HTML lengkap (boleh CSS &amp; JavaScript). Dijalankan dalam
                    <strong>iframe terisolasi (sandbox)</strong> demi keamanan.
                  </div>
                </div>
                <div class="col-12" id="panelSlide" style="display:none;">
                  <label class="form-label small fw-semibold">Slide Gambar</label>

                  <!-- Slide yang sudah ada -->
                  <?php if (!empty($slides)): ?>
                    <div class="row g-2 mb-2" id="existingSlides">
                      <?php foreach ($slides as $sl): ?>
                        <div class="col-4 col-md-3" id="slide-<?= $sl['id'] ?>">
                          <div class="border rounded p-1 h-100 text-center position-relative">
                            <img src="<?= base_url('uploads/materials/slides/' . esc($sl['image_path'])) ?>"
                                 style="width:100%;height:90px;object-fit:cover;border-radius:4px;">
                            <button type="button" class="btn btn-danger btn-sm position-absolute top-0 end-0 py-0 px-1 btn-del-slide"
                                    data-id="<?= $sl['id'] ?>" title="Hapus slide" style="font-size:0.62rem;">
                              <i class="bi bi-trash"></i>
                            </button>
                          </div>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-muted small mb-2" id="noSlideMsg">Belum ada slide.</div>
                  <?php endif; ?>

                  <label class="form-label small fw-semibold">Tambah Gambar Slide</label>
                  <input type="file" name="slide_images[]" id="slideInput" class="form-control form-control-sm"
                         accept="image/jpeg,image/png,image/webp,image/gif" multiple>
                  <div class="form-text">
                    Gambar baru ditambahkan setelah slide yang ada (JPG/PNG/WEBP/GIF, maks 5 MB per gambar).
                  </div>
                  <div id="slidePreview" class="row g-2 mt-1"></div>
                </div>
                <div class="col-12" id="panelPdf" style="display:none;">
                  <label class="form-label small fw-semibold">File PDF</label>
                  <?php if (!empty($material['file_path'])): ?>
                    <div class="mb-2">
                      <a href="<?= base_url('uploads/materials/' . $material['file_path']) ?>"
                         target="_blank" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-file-earmark-pdf me-1"></i>Lihat File Saat Ini
                      </a>
                    </div>
                  <?php endif; ?>
                  <input type="file" name="file_upload" class="form-control form-control-sm" accept=".pdf">
                  <div class="form-text">Kosongkan untuk mempertahankan file lama. Maks 10 MB.</div>
                </div>
                <div class="col-12" id="panelVideo" style="display:none;">
                  <label class="form-label small fw-semibold">URL Video</label>
                  <input type="url" name="video_url" class="form-control form-control-sm"
                         value="<?= esc($material['video_url'] ?? '') ?>"
                         placeholder="https://www.youtube.com/watch?v=...">
                </div>
                <div class="col-12" id="panelLink" style="display:none;">
                  <label class="form-label small fw-semibold">URL Eksternal</label>
                  <input type="url" name="external_link" class="form-control form-control-sm"
                         value="<?= esc($material['external_link'] ?? '') ?>">
                </div>
              </div>
            </div>
          <?php endif; ?>
        <?php endif; ?>

        <!-- Siap -->
        <div class="col-12">
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="is_published" id="isPublished"
                   <?= ($material['is_published'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label small" for="isPublished">
              <strong>Tandai sebagai siap</strong>
              <?= $isSubMat
                  ? '— sub materi bisa dipublish ke kelas'
                  : '— materi bisa dibuat sub materinya' ?>
            </label>
          </div>
        </div>

      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success btn-sm">
          <i class="bi bi-save me-1"></i>Simpan Perubahan
        </button>
        <a href="<?= site_url('admin/materials/' . $subject['id']) ?>"
           class="btn btn-secondary btn-sm">Batal</a>
      </div>
    </form>
  </div>
</div>

<?= $this->endSection() ?>
<?= $this->section('scripts') ?>
<?php if ($isSubMat): ?>
<script src="https://cdn.ckeditor.com/ckeditor5/41.4.2/classic/ckeditor.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
(function(){
  var inst      = null;
  var uploadUrl = "<?= site_url('admin/materials/upload-image') ?>";
  var csrfName  = "<?= csrf_token() ?>";
  var csrfHash  = "<?= csrf_hash() ?>";
  var isPublished = <?= !empty($isPublished) ? 'true' : 'false' ?>;

  // ── Custom upload adapter ─────────────────────────────────────────────
  function ImageUploadAdapter(loader) {
    this.loader = loader;
  }
  ImageUploadAdapter.prototype.upload = function() {
    var loader = this.loader;
    return loader.file.then(function(file) {
      return new Promise(function(resolve, reject) {
        var data = new FormData();
        data.append('upload', file);
        data.append(csrfName, csrfHash);

        var xhr = new XMLHttpRequest();
        xhr.open('POST', uploadUrl, true);
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

        xhr.upload.onprogress = function(e) {
          if (e.lengthComputable) {
            loader.uploadTotal = e.total;
            loader.uploaded    = e.loaded;
          }
        };
        xhr.onload = function() {
          if (xhr.status < 200 || xhr.status > 299) {
            return reject('Upload gagal (HTTP ' + xhr.status + ')');
          }
          var res = JSON.parse(xhr.responseText);
          if (res.error) return reject(res.error.message || 'Upload gagal');
          resolve({ default: res.url });
        };
        xhr.onerror = function() { reject('Upload gagal — periksa koneksi.'); };
        xhr.send(data);
      });
    });
  };
  ImageUploadAdapter.prototype.abort = function() {};

  function uploadAdapterPlugin(editor) {
    editor.plugins.get('FileRepository').createUploadAdapter = function(loader) {
      return new ImageUploadAdapter(loader);
    };
  }

  var panels = {
    text:  document.getElementById('panelText'),
    slide: document.getElementById('panelSlide'),
    html:  document.getElementById('panelHtml'),
    pdf:   document.getElementById('panelPdf'),
    video: document.getElementById('panelVideo'),
    link:  document.getElementById('panelLink')
  };

  // Preview gambar slide baru (single mode)
  var slideInput = document.getElementById('slideInput');
  if (slideInput) {
    slideInput.addEventListener('change', function() {
      var box = document.getElementById('slidePreview');
      if (!box) return;
      box.innerHTML = '';
      Array.from(this.files || []).forEach(function(f, idx) {
        if (!f.type.startsWith('image/')) return;
        var url = URL.createObjectURL(f);
        var col = document.createElement('div');
        col.className = 'col-4 col-md-3';
        col.innerHTML =
          '<div class="border rounded p-1 h-100 text-center border-success">' +
          '<img src="' + url + '" style="width:100%;height:90px;object-fit:cover;border-radius:4px;">' +
          '<div class="text-success" style="font-size:0.62rem;">Baru ' + (idx + 1) + '</div>' +
          '</div>';
        box.appendChild(col);
      });
    });
  }

  // Hapus slide existing single mode via AJAX
  document.querySelectorAll('.btn-del-slide').forEach(function(btn) {
    btn.addEventListener('click', function() {
      if (!confirm('Hapus slide ini?')) return;
      var id = this.dataset.id;
      var body = new URLSearchParams();
      body.append(csrfName, csrfHash);
      fetch('<?= site_url('admin/materials/slide-delete/') ?>' + id, {
        method: 'POST',
        headers: { 'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' },
        body: body.toString()
      }).then(function(r){ return r.json(); }).then(function(d){
        if (d.success) {
          var el = document.getElementById('slide-' + id);
          if (el) el.remove();
        } else {
          alert(d.message || 'Gagal menghapus slide.');
        }
      }).catch(function(){ alert('Terjadi kesalahan.'); });
    });
  });

  // Hapus slide segmen existing via AJAX
  document.addEventListener('click', function(e) {
    var btn = e.target.closest('.btn-del-seg-slide');
    if (!btn) return;
    if (!confirm('Hapus slide segmen ini?')) return;
    var id = btn.dataset.id;
    var body = new URLSearchParams();
    body.append(csrfName, csrfHash);
    fetch('<?= site_url('admin/materials/segment-slide-delete/') ?>' + id, {
      method: 'POST',
      headers: { 'Content-Type':'application/x-www-form-urlencoded', 'X-Requested-With':'XMLHttpRequest' },
      body: body.toString()
    }).then(function(r){ return r.json(); }).then(function(d){
      if (d.success) {
        var el = document.getElementById('seg-slide-' + id);
        if (el) el.remove();
      } else {
        alert(d.message || 'Gagal menghapus slide segmen.');
      }
    }).catch(function(){ alert('Terjadi kesalahan.'); });
  });

  function initEditor() {
    if (inst) return;
    var el = document.querySelector('#editorContent');
    if (!el) return;
    ClassicEditor.create(el, {
      extraPlugins: [uploadAdapterPlugin],
      toolbar: [
        'heading', '|',
        'bold', 'italic', '|',
        'bulletedList', 'numberedList', '|',
        'outdent', 'indent', '|',
        'blockQuote', 'insertTable', '|',
        'uploadImage', '|',
        'link', '|',
        'undo', 'redo'
      ],
      image: {
        toolbar: [
          'imageStyle:inline', 'imageStyle:block', 'imageStyle:side', '|',
          'toggleImageCaption', 'imageTextAlternative'
        ]
      },
      language: 'id'
    }).then(function(editor) {
      inst = editor;
      editor.model.document.on('change:data', function() {
        if (el) el.value = editor.getData();
      });
    }).catch(console.error);
  }

  function destroyEditor() {
    if (inst) {
      try { inst.destroy(); } catch (e) {}
      inst = null;
    }
  }

  function switchPanel(type) {
    Object.keys(panels).forEach(function(k) {
      if (panels[k]) panels[k].style.display = (k === type) ? 'block' : 'none';
    });
    if (type === 'text') {
      initEditor();
    } else {
      destroyEditor();
    }
  }

  var contentTypeSel = document.getElementById('contentType');
  if (contentTypeSel) {
    contentTypeSel.addEventListener('change', function() {
      switchPanel(this.value);
    });
  }

  // ══════════════════════════════════════════════════════════════════════
  // BUILDER MODE TOGGLER (MULTI-SEGMEN VS KONTEN TUNGGAL)
  // ══════════════════════════════════════════════════════════════════════
  var modeMultiRadio  = document.getElementById('modeMulti');
  var modeSingleRadio = document.getElementById('modeSingle');
  var secMulti        = document.getElementById('sectionMultiSegment');
  var secSingle       = document.getElementById('sectionSingleContent');
  var segContainer    = document.getElementById('segmentCardsContainer');
  var btnAddSeg       = document.getElementById('btnAddSegment');

  function updateBuilderMode() {
    var isMulti = modeMultiRadio && modeMultiRadio.checked;
    if (secMulti)  secMulti.style.display  = isMulti ? 'block' : 'none';
    if (secSingle) secSingle.style.display = isMulti ? 'none' : 'block';

    if (!isMulti && contentTypeSel) {
      switchPanel(contentTypeSel.value || 'text');
    }
  }

  if (modeMultiRadio && modeSingleRadio) {
    modeMultiRadio.addEventListener('change', updateBuilderMode);
    modeSingleRadio.addEventListener('change', updateBuilderMode);
  }

  // ══════════════════════════════════════════════════════════════════════
  // DYNAMIC SEGMENT BUILDER ENGINE
  // ══════════════════════════════════════════════════════════════════════
  function createSegmentHtml(idx, title, type, minutes, hasTask, taskTitle, taskInstructions, taskDeadlineDays, segId) {
    title            = title || ('Langkah ' + (idx + 1) + ' — Materi');
    type             = type || 'text';
    minutes          = minutes || 15;
    hasTask          = !!hasTask;
    taskTitle        = taskTitle || ('Tugas: ' + title);
    taskInstructions = taskInstructions || '';
    taskDeadlineDays = taskDeadlineDays || 7;
    segId            = segId || '';

    return `
    <div class="card border border-2 border-primary-subtle shadow-sm mb-3 segment-item" data-seg-idx="${idx}">
      <input type="hidden" name="segments[${idx}][id]" value="${segId}" class="seg-input-id">
      <div class="card-header bg-light d-flex align-items-center justify-content-between py-2">
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary seg-badge">Langkah #${idx + 1}</span>
          <span class="fw-semibold small text-truncate seg-label-preview" style="max-width:250px;">${title}</span>
        </div>
        <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2 btn-del-seg" title="Hapus Segmen" style="font-size:0.75rem;">
          <i class="bi bi-trash me-1"></i>Hapus
        </button>
      </div>
      <div class="card-body p-3">
        <div class="row g-2 mb-3">
          <div class="col-md-5">
            <label class="form-label small fw-semibold mb-1">Judul Segmen <span class="text-danger">*</span></label>
            <input type="text" name="segments[${idx}][title]" class="form-control form-control-sm seg-input-title"
                   value="${title}" required placeholder="cth: Pengenalan Konsep">
          </div>
          <div class="col-md-4">
            <label class="form-label small fw-semibold mb-1">Tipe Konten <span class="text-danger">*</span></label>
            <select name="segments[${idx}][content_type]" class="form-select form-select-sm seg-select-type">
              <option value="text"  ${type==='text'?'selected':''}>Teks &amp; Penjelasan</option>
              <option value="video" ${type==='video'?'selected':''}>Video YouTube / Vimeo</option>
              <option value="html"  ${type==='html'?'selected':''}>HTML Interaktif (Simulasi/Praktik)</option>
              <option value="slide" ${type==='slide'?'selected':''}>Slide Gambar (Presentasi)</option>
              <option value="pdf"   ${type==='pdf'?'selected':''}>Dokumen PDF</option>
              <option value="link"  ${type==='link'?'selected':''}>Tautan Eksternal</option>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label small fw-semibold mb-1">Estimasi (menit)</label>
            <input type="number" name="segments[${idx}][estimated_minutes]" class="form-control form-control-sm"
                   min="1" max="180" value="${minutes}">
          </div>
        </div>

        <!-- Dynamic Content Body per Tipe -->
        <div class="seg-content-body">
          <div class="seg-panel seg-panel-text" style="${type==='text'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Penjelasan Teks</label>
            <textarea name="segments[${idx}][content]" class="form-control form-control-sm" rows="6"
                      placeholder="Tuliskan uraian materi, pengantar, atau teks bacaan di sini..."></textarea>
          </div>

          <div class="seg-panel seg-panel-video" style="${type==='video'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">URL Video YouTube / Vimeo</label>
            <input type="url" name="segments[${idx}][video_url]" class="form-control form-control-sm"
                   placeholder="https://www.youtube.com/watch?v=...">
            <div class="form-text">Masukkan link lengkap video. Sistem akan menampilkan player secara otomatis.</div>
          </div>

          <div class="seg-panel seg-panel-html" style="${type==='html'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Kode HTML / JS Interaktif</label>
            <textarea name="segments[${idx}][content_html]" class="form-control form-control-sm font-monospace" rows="8"
                      placeholder="<!DOCTYPE html>... Tempelkan kode HTML, simulasi PhET, atau widget interaktif"></textarea>
            <div class="form-text text-muted">Akan dijalankan di iframe sandbox yang aman bagi siswa.</div>
          </div>

          <div class="seg-panel seg-panel-slide" style="${type==='slide'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Unggah Gambar Slide</label>
            <input type="file" name="segment_slides_${idx}[]" class="form-control form-control-sm"
                   accept="image/jpeg,image/png,image/webp,image/gif" multiple>
            <div class="form-text">Pilih beberapa gambar (maks 5MB per gambar).</div>
          </div>

          <div class="seg-panel seg-panel-pdf" style="${type==='pdf'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">Unggah File PDF</label>
            <input type="file" name="segment_pdf_${idx}" class="form-control form-control-sm" accept=".pdf">
            <div class="form-text">File dokumen materi berformat PDF (maks 10MB).</div>
          </div>

          <div class="seg-panel seg-panel-link" style="${type==='link'?'':'display:none;'}">
            <label class="form-label small fw-semibold mb-1">URL Tautan Eksternal</label>
            <input type="url" name="segments[${idx}][external_link]" class="form-control form-control-sm"
                   placeholder="https://...">
            <div class="form-text">Link ke artikel, materi referensi luar, dll.</div>
          </div>
        </div>

        <!-- Opsi Tugas / Tagihan Aktivitas Segmen -->
        <div class="mt-3 pt-3 border-top">
          <div class="form-check form-switch">
            <input class="form-check-input seg-task-toggle" type="checkbox" name="segments[${idx}][has_task]" value="1" id="segTask_${idx}" ${hasTask ? 'checked' : ''}>
            <label class="form-check-label fw-semibold small text-primary" for="segTask_${idx}">
              <i class="bi bi-clipboard-check me-1"></i>Sertakan Tugas / Tagihan Aktivitas pada Langkah Ini (Formatif)
            </label>
          </div>
          <div class="seg-task-details mt-2 p-2 rounded bg-light border" style="${hasTask ? '' : 'display:none;'}">
            <div class="small text-muted mb-2">
              <i class="bi bi-info-circle me-1"></i>Siswa wajib mengerjakan &amp; mengumpulkan tugas ini sebelum dapat menyelesaikan langkah dan lanjut ke langkah berikutnya.
            </div>
            <div class="row g-2">
              <div class="col-md-8">
                <label class="form-label small fw-semibold mb-1">Judul Tugas</label>
                <input type="text" name="segments[${idx}][task_title]" class="form-control form-control-sm seg-task-title"
                       value="${taskTitle}" placeholder="cth: Refleksi Video &amp; Lembar Analisis">
              </div>
              <div class="col-md-4">
                <label class="form-label small fw-semibold mb-1">Batas Waktu (Hari)</label>
                <input type="number" name="segments[${idx}][task_deadline_days]" class="form-control form-control-sm seg-task-deadline"
                       min="1" max="30" value="${taskDeadlineDays}">
              </div>
              <div class="col-12">
                <label class="form-label small fw-semibold mb-1">Petunjuk / Instruksi Tugas</label>
                <textarea name="segments[${idx}][task_instructions]" class="form-control form-control-sm seg-task-instructions" rows="2"
                          placeholder="Tuliskan instruksi pengerjaan yang harus dikerjakan dan dikirimkan oleh siswa...">${taskInstructions}</textarea>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>`;
  }

  function attachSegmentEvents(card) {
    var titleInput = card.querySelector('.seg-input-title');
    var labelPrev  = card.querySelector('.seg-label-preview');
    if (titleInput && labelPrev) {
      titleInput.addEventListener('input', function() {
        labelPrev.textContent = this.value || 'Segmen';
      });
    }

    var typeSel = card.querySelector('.seg-select-type');
    if (typeSel) {
      typeSel.addEventListener('change', function() {
        var t = this.value;
        card.querySelectorAll('.seg-panel').forEach(function(p) {
          p.style.display = 'none';
        });
        var activePanel = card.querySelector('.seg-panel-' + t);
        if (activePanel) activePanel.style.display = 'block';
      });
    }

    var delBtn = card.querySelector('.btn-del-seg');
    if (delBtn) {
      delBtn.addEventListener('click', function() {
        if (!confirm('Hapus langkah pembelajaran ini?')) return;
        card.remove();
        reindexSegments();
      });
    }

    var taskToggle  = card.querySelector('.seg-task-toggle');
    var taskDetails = card.querySelector('.seg-task-details');
    if (taskToggle && taskDetails) {
      taskToggle.addEventListener('change', function() {
        taskDetails.style.display = this.checked ? 'block' : 'none';
      });
    }
  }

  function reindexSegments() {
    if (!segContainer) return;
    var items = segContainer.querySelectorAll('.segment-item');
    items.forEach(function(item, idx) {
      item.dataset.segIdx = idx;
      var badge = item.querySelector('.seg-badge');
      if (badge) badge.textContent = 'Langkah #' + (idx + 1);

      // Preserve existing ID field while updating name index
      var idInput = item.querySelector('.seg-input-id');
      if (idInput) idInput.name = 'segments[' + idx + '][id]';

      var title = item.querySelector('.seg-input-title');
      if (title) title.name = 'segments[' + idx + '][title]';

      var type = item.querySelector('.seg-select-type');
      if (type) type.name = 'segments[' + idx + '][content_type]';

      var est = item.querySelector('input[name*="estimated_minutes"]');
      if (est) est.name = 'segments[' + idx + '][estimated_minutes]';

      var txt = item.querySelector('textarea[name*="[content]"]');
      if (txt) txt.name = 'segments[' + idx + '][content]';

      var html = item.querySelector('textarea[name*="[content_html]"]');
      if (html) html.name = 'segments[' + idx + '][content_html]';

      var vid = item.querySelector('input[name*="[video_url]"]');
      if (vid) vid.name = 'segments[' + idx + '][video_url]';

      var link = item.querySelector('input[name*="[external_link]"]');
      if (link) link.name = 'segments[' + idx + '][external_link]';

      var pdf = item.querySelector('input[type="file"][name*="segment_pdf_"]');
      if (pdf) pdf.name = 'segment_pdf_' + idx;

      var slides = item.querySelector('input[type="file"][name*="segment_slides_"]');
      if (slides) slides.name = 'segment_slides_' + idx + '[]';

      var taskCheck = item.querySelector('.seg-task-toggle');
      if (taskCheck) {
        taskCheck.name = 'segments[' + idx + '][has_task]';
        taskCheck.id = 'segTask_' + idx;
        var taskLbl = item.querySelector('label[for^="segTask_"]');
        if (taskLbl) taskLbl.htmlFor = 'segTask_' + idx;
      }

      var taskTitle = item.querySelector('.seg-task-title');
      if (taskTitle) taskTitle.name = 'segments[' + idx + '][task_title]';

      var taskDays = item.querySelector('.seg-task-deadline');
      if (taskDays) taskDays.name = 'segments[' + idx + '][task_deadline_days]';

      var taskInst = item.querySelector('.seg-task-instructions');
      if (taskInst) taskInst.name = 'segments[' + idx + '][task_instructions]';
    });
  }

  function addSegment(title, type, minutes, hasTask, taskTitle, taskInstructions, taskDeadlineDays, segId) {
    if (!segContainer) return;
    var nextIdx = segContainer.querySelectorAll('.segment-item').length;
    var temp = document.createElement('div');
    temp.innerHTML = createSegmentHtml(nextIdx, title, type, minutes, hasTask, taskTitle, taskInstructions, taskDeadlineDays, segId).trim();
    var card = temp.firstChild;
    segContainer.appendChild(card);
    attachSegmentEvents(card);
    reindexSegments();
  }

  if (btnAddSeg) {
    btnAddSeg.addEventListener('click', function() {
      var nextNum = segContainer.querySelectorAll('.segment-item').length + 1;
      addSegment('Langkah ' + nextNum + ': Materi Baru', 'text', 15);
    });
  }

  // Bind existing cards on DOM load
  if (segContainer) {
    segContainer.querySelectorAll('.segment-item').forEach(function(card) {
      attachSegmentEvents(card);
    });

    if (typeof Sortable !== 'undefined') {
      new Sortable(segContainer, {
        animation: 150,
        handle: '.card-header',
        ghostClass: 'opacity-50',
        onEnd: function() {
          reindexSegments();
        }
      });
    }
  }

  // ══════════════════════════════════════════════════════════════════════
  // FORM SUBMIT HANDLER
  // ══════════════════════════════════════════════════════════════════════
  var form = document.querySelector('form');
  if (form) {
    form.addEventListener('submit', function() {
      var isMulti = modeMultiRadio && modeMultiRadio.checked;
      if (isMulti) {
        if (segContainer) {
          var firstType = segContainer.querySelector('.seg-select-type');
          if (firstType && contentTypeSel) {
            contentTypeSel.value = firstType.value;
          }
        }
        var editorContent = document.getElementById('editorContent');
        if (editorContent) {
          editorContent.value = '';
        }
      } else {
        if (segContainer) segContainer.innerHTML = '';
      }
    });
  }

  // Inisialisasi tampilan panel
  if (contentTypeSel) {
    switchPanel(contentTypeSel.value || 'text');
  }
})();
</script>
<?php endif; ?>
<?= $this->endSection() ?>
