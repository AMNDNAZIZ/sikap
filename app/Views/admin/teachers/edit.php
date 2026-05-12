<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
  <div class="d-flex justify-content-between align-items-center">
    <h1 class="mt-4">Edit Data Guru</h1>
    <a href="<?= base_url('admin/teachers') ?>" class="btn btn-secondary mt-4">⬅ Kembali</a>
  </div>

  <div class="card mb-4 mt-2">
    <div class="card-header">
      <ul class="nav nav-tabs card-header-tabs" id="teacherTab" role="tablist">
        <li class="nav-item">
          <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal"
            type="button">Identitas Pribadi</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="domicile-tab" data-bs-toggle="tab" data-bs-target="#domicile"
            type="button">Domisili & Kontak</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment"
            type="button">Kepegawaian & Sertifikasi</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="education-tab" data-bs-toggle="tab" data-bs-target="#education"
            type="button">Pendidikan</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="career-tab" data-bs-toggle="tab" data-bs-target="#career" type="button">Pelatihan
            & Karier</button>
        </li>
        <li class="nav-item">
          <button class="nav-link" id="documents-tab" data-bs-toggle="tab" data-bs-target="#documents" type="button">Arsip Dokumen</button>
        </li>
      </ul>
    </div>
    <div class="card-body">
      <form action="<?= base_url('admin/teachers/update/' . $teacher['id']) ?>" method="post"
        enctype="multipart/form-data">
        <?= csrf_field() ?>
        <div class="tab-content" id="teacherTabContent">

          <!-- Tab 1: Identitas Pribadi -->
          <div class="tab-pane fade show active" id="personal" role="tabpanel">
            <div class="row">
              <div class="col-md-3 text-center mb-3">
                <?php if (!empty($teacher['photo'])): ?>
                  <img src="<?= base_url('uploads/teachers/' . $teacher['photo']) ?>" class="img-thumbnail mb-2"
                    style="width: 100%; max-width: 200px;">
                <?php else: ?>
                  <div class="bg-light border rounded mb-2 d-flex align-items-center justify-content-center"
                    style="height: 200px;">
                    <i class="fas fa-user fa-5x text-secondary"></i>
                  </div>
                <?php endif; ?>
                <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
                <small class="text-muted">Ganti Foto (JPG/PNG)</small>
              </div>
              <div class="col-md-9">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nama Lengkap (Sesuai KTP)</label>
                    <input type="text" name="name" value="<?= $teacher['name'] ?>" class="form-control" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">NIK</label>
                    <input type="text" name="nik" value="<?= $teacher['nik'] ?>" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Tempat Lahir</label>
                    <input type="text" name="birth_place" value="<?= $teacher['birth_place'] ?>" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Tanggal Lahir</label>
                    <input type="date" name="birth_date" value="<?= $teacher['birth_date'] ?>" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Jenis Kelamin</label>
                    <select name="gender" class="form-select" required>
                      <option value="L" <?= $teacher['gender'] == 'L' ? 'selected' : '' ?>>Laki-laki</option>
                      <option value="P" <?= $teacher['gender'] == 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Agama</label>
                    <select name="religion" class="form-select">
                      <option value="">- Pilih Agama -</option>
                      <?php foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu'] as $r): ?>
                        <option value="<?= $r ?>" <?= ($teacher['religion'] ?? '') == $r ? 'selected' : '' ?>><?= $r ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Nama Ibu Kandung</label>
                    <input type="text" name="mother_name" value="<?= $teacher['mother_name'] ?>" class="form-control">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Status Pernikahan</label>
                    <select name="marital_status" class="form-select">
                      <option value="">- Pilih Status -</option>
                      <?php foreach (['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'] as $s): ?>
                        <option value="<?= $s ?>" <?= ($teacher['marital_status'] ?? '') == $s ? 'selected' : '' ?>><?= $s ?>
                        </option>
                      <?php endforeach; ?>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Tab 2: Domisili & Kontak -->
          <div class="tab-pane fade" id="domicile" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-12">
                <label class="form-label">Alamat Rumah</label>
                <textarea name="address" class="form-control" rows="2"><?= $teacher['address'] ?></textarea>
              </div>
              <div class="col-md-3">
                <label class="form-label">RT/RW</label>
                <input type="text" name="rt_rw" value="<?= $teacher['rt_rw'] ?>" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Desa/Kelurahan</label>
                <input type="text" name="village" value="<?= $teacher['village'] ?>" class="form-control">
              </div>
              <div class="col-md-5">
                <label class="form-label">Kecamatan</label>
                <input type="text" name="district" value="<?= $teacher['district'] ?>" class="form-control">
              </div>
              <div class="col-md-5">
                <label class="form-label">Kabupaten/Kota</label>
                <input type="text" name="city" value="<?= $teacher['city'] ?>" class="form-control">
              </div>
              <div class="col-md-4">
                <label class="form-label">Provinsi</label>
                <input type="text" name="province" value="<?= $teacher['province'] ?>" class="form-control">
              </div>
              <div class="col-md-3">
                <label class="form-label">Kode Pos</label>
                <input type="text" name="postal_code" value="<?= $teacher['postal_code'] ?>" class="form-control">
              </div>
              <hr>
              <div class="col-md-6">
                <label class="form-label">Nomor Telepon/HP</label>
                <input type="text" name="phone" value="<?= $teacher['phone'] ?>" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Email Aktif</label>
                <input type="email" name="email" value="<?= $teacher['email'] ?>" class="form-control">
              </div>
            </div>
          </div>

          <!-- Tab 3: Kepegawaian & Sertifikasi -->
          <div class="tab-pane fade" id="employment" role="tabpanel">
            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Status Kepegawaian</label>
                <select name="employment_status" class="form-select">
                  <option value="">- Pilih Status -</option>
                  <?php foreach (['PNS', 'PPPK', 'GTY/PTY', 'Honorer Sekolah'] as $s): ?>
                    <option value="<?= $s ?>" <?= $teacher['employment_status'] == $s ? 'selected' : '' ?>><?= $s ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-6">
                <label class="form-label">NIP (Jika Ada)</label>
                <input type="text" name="nip" value="<?= $teacher['nip'] ?>" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">NUPTK</label>
                <input type="text" name="nuptk" value="<?= $teacher['nuptk'] ?>" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Lembaga Pengangkat</label>
                <input type="text" name="appointing_agency" value="<?= $teacher['appointing_agency'] ?>"
                  class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">SK Pengangkatan</label>
                <input type="text" name="appointment_sk" value="<?= $teacher['appointment_sk'] ?>" class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">TMT Pengangkatan</label>
                <input type="date" name="appointment_tmt" value="<?= $teacher['appointment_tmt'] ?>"
                  class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Jabatan Fungsional</label>
                <input type="text" name="functional_position" value="<?= $teacher['functional_position'] ?>"
                  class="form-control">
              </div>
              <div class="col-md-6">
                <label class="form-label">Pangkat/Golongan</label>
                <input type="text" name="rank_grade" value="<?= $teacher['rank_grade'] ?>" class="form-control">
              </div>
              <hr>
              <h6 class="mb-0">Sertifikasi Pendidik</h6>
              <div class="col-md-5">
                <label class="form-label">Nomor Sertifikat</label>
                <input type="text" name="certification_number" value="<?= $teacher['certification_number'] ?>"
                  class="form-control">
              </div>
              <div class="col-md-5">
                <label class="form-label">Bidang Studi Sertifikasi</label>
                <input type="text" name="certification_field" value="<?= $teacher['certification_field'] ?>"
                  class="form-control">
              </div>
              <div class="col-md-2">
                <label class="form-label">Tahun</label>
                <input type="number" name="certification_year" value="<?= $teacher['certification_year'] ?>"
                  class="form-control">
              </div>
            </div>
          </div>

          <!-- Tab 4: Pendidikan -->
          <div class="tab-pane fade" id="education" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
              <h6>Riwayat Pendidikan Formal</h6>
              <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                data-bs-target="#modalEducation">+ Tambah</button>
            </div>
            <table class="table table-bordered table-sm">
              <thead class="bg-light">
                <tr>
                  <th>Jenjang</th>
                  <th>Jurusan</th>
                  <th>Institusi</th>
                  <th>Lulus</th>
                  <th>Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($educations)): ?>
                  <tr>
                    <td colspan="5" class="text-center">Belum ada data.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($educations as $edu): ?>
                    <tr>
                      <td><?= $edu['level'] ?></td>
                      <td><?= $edu['major'] ?></td>
                      <td><?= $edu['institution'] ?></td>
                      <td><?= $edu['graduation_year'] ?></td>
                      <td>
                        <button type="button" class="btn btn-xs btn-danger btn-delete-sub" data-type="education"
                          data-id="<?= $edu['id'] ?>"><i class="fas fa-trash"></i></button>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <!-- Tab 5: Pelatihan & Karier -->
          <div class="tab-pane fade" id="career" role="tabpanel">
            <div class="mb-4">
              <div class="d-flex justify-content-between mb-2">
                <h6>Riwayat Pelatihan/Diklat</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                  data-bs-target="#modalTraining">+ Tambah</button>
              </div>
              <table class="table table-bordered table-sm">
                <thead class="bg-light">
                  <tr>
                    <th>Nama Pelatihan</th>
                    <th>Tahun</th>
                    <th>Penyelenggara</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($trainings)): ?>
                    <tr>
                      <td colspan="4" class="text-center">Belum ada data.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($trainings as $tr): ?>
                      <tr>
                        <td><?= $tr['name'] ?></td>
                        <td><?= $tr['year'] ?></td>
                        <td><?= $tr['organizer'] ?></td>
                        <td>
                          <button type="button" class="btn btn-xs btn-danger btn-delete-sub" data-type="training"
                            data-id="<?= $tr['id'] ?>"><i class="fas fa-trash"></i></button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

            <hr>

            <div class="mb-2">
              <div class="d-flex justify-content-between mb-2">
                <h6>Riwayat Karier / SK Mengajar</h6>
                <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                  data-bs-target="#modalCareer">+ Tambah</button>
              </div>
              <table class="table table-bordered table-sm">
                <thead class="bg-light">
                  <tr>
                    <th>Tahun Ajaran</th>
                    <th>No. SK</th>
                    <th>Keterangan Tugas</th>
                    <th>Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($careers)): ?>
                    <tr>
                      <td colspan="4" class="text-center">Belum ada data.</td>
                    </tr>
                  <?php else: ?>
                    <?php foreach ($careers as $cr): ?>
                      <tr>
                        <td><?= $cr['academic_year'] ?></td>
                        <td><?= $cr['sk_number'] ?></td>
                        <td><?= $cr['assignment_description'] ?></td>
                        <td>
                          <button type="button" class="btn btn-xs btn-danger btn-delete-sub" data-type="career"
                            data-id="<?= $cr['id'] ?>"><i class="fas fa-trash"></i></button>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>

          <!-- Tab 6: Arsip Dokumen -->
          <div class="tab-pane fade" id="documents" role="tabpanel">
            <div class="d-flex justify-content-between mb-3">
              <h6>Arsip Dokumen Guru</h6>
              <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal"
                data-bs-target="#modalDocument">+ Upload Dokumen</button>
            </div>
            
            <?php if (empty($documents)): ?>
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>Belum ada dokumen yang diupload.
              </div>
            <?php else: ?>
              <div class="row g-3">
                <?php foreach ($documents as $doc): ?>
                  <div class="col-md-6 col-lg-4">
                    <div class="card h-100">
                      <div class="card-body">
                        <div class="d-flex align-items-start">
                          <div class="flex-shrink-0 me-3">
                            <?php
                            $icon = 'file-earmark';
                            if (strpos($doc['file_type'], 'pdf') !== false) {
                              $icon = 'file-earmark-pdf text-danger';
                            } elseif (strpos($doc['file_type'], 'word') !== false) {
                              $icon = 'file-earmark-word text-primary';
                            } elseif (strpos($doc['file_type'], 'excel') !== false || strpos($doc['file_type'], 'spreadsheet') !== false) {
                              $icon = 'file-earmark-excel text-success';
                            } elseif (strpos($doc['file_type'], 'image') !== false) {
                              $icon = 'file-earmark-image text-info';
                            }
                            ?>
                            <i class="bi bi-<?= $icon ?>" style="font-size: 2rem;"></i>
                          </div>
                          <div class="flex-grow-1">
                            <h6 class="card-title mb-1"><?= esc($doc['title']) ?></h6>
                            <p class="card-text small text-muted mb-2">
                              <?= esc($doc['original_name']) ?><br>
                              <span class="badge bg-secondary"><?= number_format($doc['file_size'] / 1024, 1) ?> KB</span>
                              <span class="text-muted ms-2"><?= date('d/m/Y', strtotime($doc['created_at'])) ?></span>
                            </p>
                            <div class="btn-group btn-group-sm">
                              <button type="button" class="btn btn-outline-info btn-sm btn-preview-doc"
                                      data-id="<?= $doc['id'] ?>" 
                                      data-title="<?= esc($doc['title']) ?>"
                                      data-type="<?= $doc['file_type'] ?>"
                                      data-filename="<?= $doc['filename'] ?>">
                                <i class="bi bi-eye"></i> Preview
                              </button>
                              <a href="<?= base_url('admin/teachers/downloadDocument/' . $doc['id']) ?>" 
                                 class="btn btn-outline-primary btn-sm">
                                <i class="bi bi-download"></i> Download
                              </a>
                              <button type="button" class="btn btn-outline-danger btn-sm btn-delete-doc" 
                                      data-id="<?= $doc['id'] ?>" data-title="<?= esc($doc['title']) ?>">
                                <i class="bi bi-trash"></i>
                              </button>
                            </div>
                          </div>
                        </div>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>

        </div>

        <div class="mt-4 border-top pt-3 text-end">
          <button type="submit" class="btn btn-primary btn-lg px-5">💾 Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Modals for History Data -->
<div class="modal fade" id="modalEducation" tabindex="-1">
  <div class="modal-dialog">
    <form action="<?= base_url('admin/teachers/addEducation/' . $teacher['id']) ?>" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Riwayat Pendidikan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Jenjang</label>
          <select name="level" class="form-select" required>
            <option value="SD">SD</option>
            <option value="SLTP">SLTP</option>
            <option value="SLTA">SLTA</option>
            <option value="D3">D3</option>
            <option value="S1">S1</option>
            <option value="S2">S2</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Jurusan</label>
          <input type="text" name="major" class="form-control">
        </div>
        <div class="mb-3">
          <label class="form-label">Institusi</label>
          <input type="text" name="institution" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Tahun Lulus</label>
          <input type="number" name="graduation_year" class="form-control" value="<?= date('Y') ?>" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalTraining" tabindex="-1">
  <div class="modal-dialog">
    <form action="<?= base_url('admin/teachers/addTraining/' . $teacher['id']) ?>" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Riwayat Pelatihan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Nama Pelatihan/Diklat</label>
          <input type="text" name="name" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Tahun</label>
          <input type="number" name="year" class="form-control" value="<?= date('Y') ?>" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Penyelenggara</label>
          <input type="text" name="organizer" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Nomor Sertifikat</label>
          <input type="text" name="certificate_number" class="form-control">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalCareer" tabindex="-1">
  <div class="modal-dialog">
    <form action="<?= base_url('admin/teachers/addCareer/' . $teacher['id']) ?>" method="post" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Tambah Riwayat Karier / SK Mengajar</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Tahun Ajaran</label>
          <select name="academic_year_id" class="form-select" required>
            <?php foreach ($years as $y): ?>
              <option value="<?= $y['id'] ?>" <?= $y['is_active'] ? 'selected' : '' ?>><?= $y['year'] ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Nomor SK</label>
          <input type="text" name="sk_number" class="form-control" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Keterangan Tugas Mengajar</label>
          <textarea name="assignment_description" class="form-control" rows="3" required
            placeholder="Contoh: Guru Kelas IV, Mengampu Mapel PAI..."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan</button>
      </div>
    </form>
  </div>
</div>

<div class="modal fade" id="modalDocument" tabindex="-1">
  <div class="modal-dialog">
    <form action="<?= base_url('admin/teachers/uploadDocument/' . $teacher['id']) ?>" method="post" 
          enctype="multipart/form-data" class="modal-content">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title">Upload Dokumen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Judul Dokumen</label>
          <input type="text" name="title" class="form-control" required 
                 placeholder="Contoh: Ijazah S1, Sertifikat Pelatihan, SK Pengangkatan">
        </div>
        <div class="mb-3">
          <label class="form-label">File Dokumen</label>
          <input type="file" name="document" class="form-control" required 
                 accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
          <small class="text-muted">
            Format: PDF, DOC, DOCX, XLS, XLSX, JPG, PNG. Maksimal 5MB.
          </small>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Upload</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Preview Dokumen -->
<div class="modal fade" id="modalPreview" tabindex="-1">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="previewTitle">Preview Dokumen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewBody" style="min-height: 500px;">
        <div class="text-center py-5">
          <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
          </div>
          <p class="mt-2">Memuat dokumen...</p>
        </div>
      </div>
      <div class="modal-footer">
        <a href="#" id="previewDownloadBtn" class="btn btn-primary" target="_blank">
          <i class="bi bi-download"></i> Download
        </a>
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    const deleteBtns = document.querySelectorAll('.btn-delete-sub');
    deleteBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
          const type = this.dataset.type;
          const id = this.dataset.id;
          window.location.href = `<?= base_url('admin/teachers') ?>/deleteSub/${type}/${id}`;
        }
      });
    });

    // Delete document handler
    const deleteDocBtns = document.querySelectorAll('.btn-delete-doc');
    deleteDocBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        const title = this.dataset.title;
        if (confirm(`Apakah Anda yakin ingin menghapus dokumen "${title}"?`)) {
          const id = this.dataset.id;
          window.location.href = `<?= base_url('admin/teachers/deleteDocument') ?>/${id}`;
        }
      });
    });

    // Preview document handler
    const previewBtns = document.querySelectorAll('.btn-preview-doc');
    const previewModal = new bootstrap.Modal(document.getElementById('modalPreview'));
    const previewTitle = document.getElementById('previewTitle');
    const previewBody = document.getElementById('previewBody');
    const previewDownloadBtn = document.getElementById('previewDownloadBtn');

    previewBtns.forEach(btn => {
      btn.addEventListener('click', function () {
        const id = this.dataset.id;
        const title = this.dataset.title;
        const type = this.dataset.type;
        const filename = this.dataset.filename;
        
        previewTitle.textContent = `Preview: ${title}`;
        previewDownloadBtn.href = `<?= base_url('admin/teachers/downloadDocument') ?>/${id}`;
        
        // Reset body with loading
        previewBody.innerHTML = `
          <div class="text-center py-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Memuat dokumen...</p>
          </div>
        `;
        
        previewModal.show();
        
        // Generate preview based on file type
        setTimeout(() => {
          const fileUrl = `<?= base_url('admin/teachers/viewDocument') ?>/${id}`;
          
          if (type.includes('pdf')) {
            // PDF Preview
            previewBody.innerHTML = `
              <iframe src="${fileUrl}" 
                      style="width: 100%; height: 600px; border: none;"
                      title="${title}">
              </iframe>
              <p class="text-muted text-center mt-2">
                <small>Jika PDF tidak tampil, <a href="<?= base_url('admin/teachers/downloadDocument') ?>/${id}" target="_blank">download file</a></small>
              </p>
            `;
          } else if (type.includes('image')) {
            // Image Preview
            previewBody.innerHTML = `
              <div class="text-center">
                <img src="${fileUrl}" 
                     class="img-fluid" 
                     alt="${title}"
                     style="max-height: 600px; object-fit: contain;">
              </div>
            `;
          } else if (type.includes('word') || type.includes('document')) {
            // Word Document - Try direct view first, fallback to Office Viewer
            previewBody.innerHTML = `
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Dokumen Word</strong> - Preview mungkin memerlukan waktu loading
              </div>
              <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(window.location.origin + fileUrl)}" 
                      style="width: 100%; height: 600px; border: none;"
                      title="${title}">
              </iframe>
              <p class="text-muted text-center mt-2">
                <small>Jika dokumen tidak tampil, <a href="<?= base_url('admin/teachers/downloadDocument') ?>/${id}">download file</a> untuk membukanya di komputer Anda.</small>
              </p>
            `;
          } else if (type.includes('excel') || type.includes('spreadsheet')) {
            // Excel - Office Online Viewer
            previewBody.innerHTML = `
              <div class="alert alert-info">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Dokumen Excel</strong> - Preview mungkin memerlukan waktu loading
              </div>
              <iframe src="https://view.officeapps.live.com/op/embed.aspx?src=${encodeURIComponent(window.location.origin + fileUrl)}" 
                      style="width: 100%; height: 600px; border: none;"
                      title="${title}">
              </iframe>
              <p class="text-muted text-center mt-2">
                <small>Jika dokumen tidak tampil, <a href="<?= base_url('admin/teachers/downloadDocument') ?>/${id}">download file</a> untuk membukanya di komputer Anda.</small>
              </p>
            `;
          } else {
            // Unsupported file type
            previewBody.innerHTML = `
              <div class="alert alert-warning text-center">
                <i class="bi bi-file-earmark" style="font-size: 4rem;"></i>
                <h5 class="mt-3">Preview tidak tersedia</h5>
                <p>Tipe file ini tidak mendukung preview langsung.</p>
                <p class="mb-0">Silakan download file untuk membukanya.</p>
              </div>
            `;
          }
        }, 300);
      });
    });

    // Keep active tab on refresh
    const activeTab = localStorage.getItem('teacherActiveTab');
    if (activeTab) {
      const tabEl = document.querySelector(`#teacherTab button[data-bs-target="${activeTab}"]`);
      if (tabEl) {
        bootstrap.Tab.getOrCreateInstance(tabEl).show();
      }
    }

    const tabButtons = document.querySelectorAll('#teacherTab button');
    tabButtons.forEach(btn => {
      btn.addEventListener('shown.bs.tab', function () {
        localStorage.setItem('teacherActiveTab', this.dataset.bsTarget);
      });
    });
  });
</script>

<style>
  /* Tab Navigation Styling - Improved Visibility */
  .nav-tabs {
    border-bottom: 2px solid #e9ecef;
  }

  .nav-tabs .nav-link {
    color: #6c757d;
    border: none;
    border-bottom: 3px solid transparent;
    padding: 1rem 1.5rem;
    transition: all 0.3s ease;
    background: transparent;
    font-size: 0.95rem;
  }

  .nav-tabs .nav-link:hover {
    color: #0d6efd;
    background: #f8f9fa;
    border-color: transparent;
    border-bottom-color: #dee2e6;
  }

  .nav-tabs .nav-link.active {
    color: #0d6efd !important;
    background: #f8f9fa;
    border: none;
    border-bottom: 3px solid #0d6efd;
    font-weight: 600;
  }

  /* Form Elements */
  .form-control:focus,
  .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
  }

  /* Responsive */
  @media (max-width: 768px) {
    .nav-tabs .nav-link {
      padding: 0.75rem 1rem;
      font-size: 0.85rem;
    }
  }
</style>

<?= $this->endSection() ?>