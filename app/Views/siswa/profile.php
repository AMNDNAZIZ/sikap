<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="row">
    <div class="col-md-4">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body text-center">
                <div class="mb-4">
                    <?php if (!empty($student['photo']) && file_exists(FCPATH . 'uploads/students/' . $student['photo'])): ?>
                        <img src="<?= base_url('uploads/students/' . $student['photo']) ?>"
                            class="rounded-circle img-thumbnail shadow-sm"
                            style="width: 180px; height: 180px; object-fit: cover;" alt="Foto Siswa">
                    <?php else: ?>
                        <div class="bg-light rounded-circle d-flex align-items-center justify-content-center mx-auto border shadow-sm"
                            style="width: 180px; height: 180px;">
                            <i class="fas fa-user-graduate fa-6x text-secondary"></i>
                        </div>
                    <?php endif; ?>
                </div>
                <h4 class="mb-1 text-primary">
                    <?= esc($student['name']) ?>
                </h4>
                <p class="text-muted mb-0">
                    <?= esc($student['nis']) ?> /
                    <?= esc($student['nisn']) ?>
                </p>
                <div class="badge bg-success mt-2 px-3 py-2">
                    <?= esc($record['class_name'] ?? 'Belum ada kelas') ?>
                </div>

                <div class="alert alert-info mt-4 mb-0 text-start overflow-hidden" style="font-size: 0.85rem;">
                    <i class="fas fa-info-circle me-1"></i> <strong>Catatan:</strong> <br>
                    Data utama (Nama, NIS, NISN, TTL, Foto) hanya dapat diubah oleh Admin.
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 text-primary"><i class="fas fa-id-card me-2"></i>Data Utama</h5>
            </div>
            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">NISN</small>
                        <span class="fw-bold">
                            <?= esc($student['nisn']) ?>
                        </span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">NIS</small>
                        <span class="fw-bold">
                            <?= esc($student['nis']) ?>
                        </span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Tempat, Tanggal Lahir</small>
                        <span class="fw-bold">
                            <?= esc($student['birth_place']) ?>,
                            <?= !empty($student['birth_date']) ? date('d/m/Y', strtotime($student['birth_date'])) : '-' ?>
                        </span>
                    </li>
                    <li class="list-group-item px-4 py-3">
                        <small class="text-muted d-block">Jenis Kelamin</small>
                        <span class="fw-bold">
                            <?= $student['gender'] == 'L' ? 'Laki-laki' : 'Perempuan' ?>
                        </span>
                    </li>
                    <li class="list-group-item px-4 py-3 bg-light border-start border-primary border-4">
                        <small class="text-muted d-block fw-bold text-uppercase" style="font-size: 0.7rem;">Diterima di Kelas (Permanen)</small>
                        <span class="fw-bold text-primary fs-5">
                            <?= esc($student['admission_class'] ?? '-') ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success border-0 shadow-sm mb-4">
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>

        <form action="<?= base_url('siswa/profile/update') ?>" method="post">
            <?= csrf_field() ?>
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-0">
                    <ul class="nav nav-pills card-header-pills" id="studentTab" role="tablist">
                        <li class="nav-item">
                            <button class="nav-link active fw-bold px-4" id="personal-tab" data-bs-toggle="tab"
                                data-bs-target="#personal" type="button" role="tab">Data Pendukung</button>
                        </li>
                        <li class="nav-item">
                            <button class="nav-link fw-bold px-4" id="family-tab" data-bs-toggle="tab"
                                data-bs-target="#family" type="button" role="tab">Keluarga</button>
                        </li>
                    </ul>
                </div>
                <div class="card-body p-4">
                    <div class="tab-content" id="studentTabContent">
                        <!-- Data Pendukung & Alamat -->
                        <div class="tab-pane fade show active" id="personal" role="tabpanel">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">NIK</label>
                                    <input type="text" name="nik" class="form-control"
                                        value="<?= esc($student['nik']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Anak ke</label>
                                    <input type="text" name="child_order" class="form-control"
                                        value="<?= esc($student['child_order']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Agama</label>
                                    <select name="religion" class="form-select">
                                        <?php $religions = ['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu']; ?>
                                        <?php foreach ($religions as $r): ?>
                                            <option value="<?= $r ?>" <?= ($student['religion'] ?? '') == $r ? 'selected' : '' ?>>
                                                <?= $r ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Kewarganegaraan</label>
                                    <input type="text" name="nationality" class="form-control"
                                        value="<?= esc($student['nationality'] ?? 'WNI') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Kebutuhan Khusus</label>
                                    <input type="text" name="special_needs" class="form-control"
                                        value="<?= esc($student['special_needs']) ?>"
                                        placeholder="tunarungu, tunanetra, dll">
                                </div>
                                <div class="col-12 mt-3">
                                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">Domisili & Transportasi
                                    </h6>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Alamat Lengkap</label>
                                    <textarea name="address" class="form-control"
                                        rows="3"><?= esc($student['address']) ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Tempat Tinggal</label>
                                    <select name="residence_type" class="form-select">
                                        <option value="">- Pilih -</option>
                                        <option value="Bersama Orang Tua" <?= ($student['residence_type'] ?? '') == 'Bersama Orang Tua' ? 'selected' : '' ?>>Bersama Orang Tua</option>
                                        <option value="Wali" <?= ($student['residence_type'] ?? '') == 'Wali' ? 'selected' : '' ?>>Wali</option>
                                        <option value="Asrama" <?= ($student['residence_type'] ?? '') == 'Asrama' ? 'selected' : '' ?>>Asrama</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Moda Transportasi</label>
                                    <select name="transportation" class="form-select">
                                        <option value="">- Pilih -</option>
                                        <option value="Antar-jemput mobil" <?= ($student['transportation'] ?? '') == 'Antar-jemput mobil' ? 'selected' : '' ?>>Antar-jemput mobil</option>
                                        <option value="Antar-jemput motor" <?= ($student['transportation'] ?? '') == 'Antar-jemput motor' ? 'selected' : '' ?>>Antar-jemput motor</option>
                                        <option value="Sepeda" <?= ($student['transportation'] ?? '') == 'Sepeda' ? 'selected' : '' ?>>Sepeda</option>
                                        <option value="Jalan kaki" <?= ($student['transportation'] ?? '') == 'Jalan kaki' ? 'selected' : '' ?>>Jalan kaki</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Jarak ke Sekolah</label>
                                    <input type="text" name="distance" class="form-control"
                                        value="<?= esc($student['distance']) ?>" placeholder="Misal: 1 km">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Latitude</label>
                                    <input type="text" name="latitude" class="form-control"
                                        value="<?= esc($student['latitude']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Longitude</label>
                                    <input type="text" name="longitude" class="form-control"
                                        value="<?= esc($student['longitude']) ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Data Orang Tua/Wali -->
                        <div class="tab-pane fade" id="family" role="tabpanel">
                            <!-- Ayah -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">DATA AYAH</h6>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Nama Ayah</label>
                                    <input type="text" name="father_name" class="form-control"
                                        value="<?= esc($student['father_name']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">NIK Ayah</label>
                                    <input type="text" name="father_nik" class="form-control"
                                        value="<?= esc($student['father_nik']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Tahun Lahir</label>
                                    <input type="text" name="father_birth_year" class="form-control"
                                        value="<?= esc($student['father_birth_year']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Pendidikan</label>
                                    <select name="father_education" class="form-select">
                                        <option value="">- Pilih -</option>
                                        <?php $edu = ['SD', 'SLTP', 'SLTA', 'S1', 'S2', 'S3']; ?>
                                        <?php foreach ($edu as $e): ?>
                                            <option value="<?= $e ?>" <?= ($student['father_education'] ?? '') == $e ? 'selected' : '' ?>>
                                                <?= $e ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Pekerjaan</label>
                                    <input type="text" name="father_job" class="form-control"
                                        value="<?= esc($student['father_job']) ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Penghasilan</label>
                                    <input type="text" name="father_income" class="form-control"
                                        value="<?= esc($student['father_income']) ?>">
                                </div>
                            </div>

                            <!-- Ibu -->
                            <div class="row g-3 mb-4">
                                <div class="col-12">
                                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">DATA IBU</h6>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Nama Ibu</label>
                                    <input type="text" name="mother_name" class="form-control"
                                        value="<?= esc($student['mother_name']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">NIK Ibu</label>
                                    <input type="text" name="mother_nik" class="form-control"
                                        value="<?= esc($student['mother_nik']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Tahun Lahir</label>
                                    <input type="text" name="mother_birth_year" class="form-control"
                                        value="<?= esc($student['mother_birth_year']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Pendidikan</label>
                                    <select name="mother_education" class="form-select">
                                        <option value="">- Pilih -</option>
                                        <?php foreach ($edu as $e): ?>
                                            <option value="<?= $e ?>" <?= ($student['mother_education'] ?? '') == $e ? 'selected' : '' ?>>
                                                <?= $e ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Pekerjaan</label>
                                    <input type="text" name="mother_job" class="form-control"
                                        value="<?= esc($student['mother_job']) ?>">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold">Penghasilan</label>
                                    <input type="text" name="mother_income" class="form-control"
                                        value="<?= esc($student['mother_income']) ?>">
                                </div>
                            </div>

                            <!-- Wali -->
                            <div class="row g-3">
                                <div class="col-12">
                                    <h6 class="text-primary fw-bold mb-3 border-bottom pb-2">DATA WALI (OPSIONAL)</h6>
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold">Nama Wali</label>
                                    <input type="text" name="guardian_name" class="form-control"
                                        value="<?= esc($student['guardian_name']) ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Pendidikan Wali</label>
                                    <select name="guardian_education" class="form-select">
                                        <option value="">- Pilih -</option>
                                        <?php foreach ($edu as $e): ?>
                                            <option value="<?= $e ?>" <?= ($student['guardian_education'] ?? '') == $e ? 'selected' : '' ?>>
                                                <?= $e ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Pekerjaan Wali</label>
                                    <input type="text" name="guardian_job" class="form-control"
                                        value="<?= esc($student['guardian_job']) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Penghasilan Wali</label>
                                    <input type="text" name="guardian_income" class="form-control"
                                        value="<?= esc($student['guardian_income']) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-white p-4 text-end border-top shadow-sm">
                    <button type="submit" class="btn btn-primary px-5 py-2 fw-bold shadow-sm">
                        <i class="fas fa-save me-1"></i> Perbarui Profil
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<?= $this->endSection() ?>