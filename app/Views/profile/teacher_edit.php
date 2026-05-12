<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4">
    <h1 class="mt-4">Profil Saya</h1>
    <p class="text-muted">Kelola informasi pribadi dan riwayat profesional Anda di sini.</p>

    <div class="card mb-4">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="profileTab" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" id="personal-tab" data-bs-toggle="tab" data-bs-target="#personal"
                        type="button">Identitas & Domisili</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="employment-tab" data-bs-toggle="tab" data-bs-target="#employment"
                        type="button">Kepegawaian</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="education-tab" data-bs-toggle="tab" data-bs-target="#education"
                        type="button">Pendidikan</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="career-tab" data-bs-toggle="tab" data-bs-target="#career"
                        type="button">Pelatihan & Karier</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link" id="account-tab" data-bs-toggle="tab" data-bs-target="#account"
                        type="button">Akun Login</button>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= base_url('profile/documents') ?>">
                        <i class="bi bi-folder2-open me-1"></i>Arsip Dokumen
                    </a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <form action="<?= base_url('profile/update-teacher') ?>" method="post" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <div class="tab-content" id="profileTabContent">

                    <!-- Tab 1: Identitas & Domisili -->
                    <div class="tab-pane fade show active" id="personal" role="tabpanel">
                        <div class="row">
                            <div class="col-md-3 text-center mb-3">
                                <?php if (!empty($teacher['photo'])): ?>
                                    <img src="<?= base_url('uploads/teachers/' . $teacher['photo']) ?>"
                                        class="img-thumbnail mb-2" style="width: 100%; max-width: 200px;">
                                <?php else: ?>
                                    <div class="bg-light border rounded mb-2 d-flex align-items-center justify-content-center"
                                        style="height: 200px;">
                                        <i class="fas fa-user fa-5x text-secondary"></i>
                                    </div>
                                <?php endif; ?>
                                <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
                                <small class="text-muted">Foto Profil (JPG/PNG)</small>
                            </div>
                            <div class="col-md-9">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Lengkap</label>
                                        <input type="text" name="name" value="<?= $teacher['name'] ?>"
                                            class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">NIK</label>
                                        <input type="text" name="nik" value="<?= $teacher['nik'] ?>"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tempat Lahir</label>
                                        <input type="text" name="birth_place" value="<?= $teacher['birth_place'] ?>"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Tanggal Lahir</label>
                                        <input type="date" name="birth_date" value="<?= $teacher['birth_date'] ?>"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Jenis Kelamin</label>
                                        <select name="gender" class="form-select" required>
                                            <option value="L" <?= $teacher['gender'] == 'L' ? 'selected' : '' ?>>Laki-laki
                                            </option>
                                            <option value="P" <?= $teacher['gender'] == 'P' ? 'selected' : '' ?>>Perempuan
                                            </option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Agama</label>
                                        <select name="religion" class="form-select">
                                            <option value="">- Pilih Agama -</option>
                                            <?php foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu'] as $r): ?>
                                                <option value="<?= $r ?>" <?= ($teacher['religion'] ?? '') == $r ? 'selected' : '' ?>>
                                                    <?= $r ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Status Pernikahan</label>
                                        <select name="marital_status" class="form-select">
                                            <option value="">- Pilih Status -</option>
                                            <?php foreach (['Belum Kawin', 'Kawin', 'Cerai Hidup', 'Cerai Mati'] as $s): ?>
                                                <option value="<?= $s ?>" <?= ($teacher['marital_status'] ?? '') == $s ? 'selected' : '' ?>>
                                                    <?= $s ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nama Ibu Kandung</label>
                                        <input type="text" name="mother_name" value="<?= $teacher['mother_name'] ?>"
                                            class="form-control">
                                    </div>
                                </div>
                                <hr>
                                <div class="row g-3 mt-1">
                                    <div class="col-md-12">
                                        <label class="form-label">Alamat Lengkap</label>
                                        <textarea name="address" class="form-control"
                                            rows="2"><?= $teacher['address'] ?></textarea>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">RT/RW</label>
                                        <input type="text" name="rt_rw" value="<?= $teacher['rt_rw'] ?>"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Desa/Kelurahan</label>
                                        <input type="text" name="village" value="<?= $teacher['village'] ?>"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Kecamatan</label>
                                        <input type="text" name="district" value="<?= $teacher['district'] ?>"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Kabupaten/Kota</label>
                                        <input type="text" name="city" value="<?= $teacher['city'] ?>"
                                            class="form-control">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Nomor HP/WA Aktif</label>
                                        <input type="text" name="phone" value="<?= $teacher['phone'] ?>"
                                            class="form-control">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Kepegawaian (Read Only for Teacher) -->
                    <div class="tab-pane fade" id="employment" role="tabpanel">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> Data kepegawaian hanya dapat diubah oleh Administrator.
                            Jika ada kesalahan data, silakan hubungi bagian TU.
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">NIP</label>
                                <input type="text" value="<?= $teacher['nip'] ?: '-' ?>" class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">NUPTK</label>
                                <input type="text" value="<?= $teacher['nuptk'] ?: '-' ?>" class="form-control"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Status Kepegawaian</label>
                                <input type="text" value="<?= $teacher['employment_status'] ?: '-' ?>"
                                    class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Lembaga Pengangkat</label>
                                <input type="text" value="<?= $teacher['appointing_agency'] ?: '-' ?>"
                                    class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">SK Pengangkatan</label>
                                <input type="text" value="<?= $teacher['appointment_sk'] ?: '-' ?>" class="form-control"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">TMT Pengangkatan</label>
                                <input type="text" value="<?= $teacher['appointment_tmt'] ?: '-' ?>"
                                    class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Pangkat/Golongan</label>
                                <input type="text" value="<?= $teacher['rank_grade'] ?: '-' ?>" class="form-control"
                                    readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Jabatan Fungsional</label>
                                <input type="text" value="<?= $teacher['functional_position'] ?: '-' ?>"
                                    class="form-control" readonly>
                            </div>
                            <hr>
                            <h6>Sertifikasi</h6>
                            <div class="col-md-6">
                                <label class="form-label">Nomor Sertifikat</label>
                                <input type="text" value="<?= $teacher['certification_number'] ?: '-' ?>"
                                    class="form-control" readonly>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Bidang Studi Sertifikasi</label>
                                <input type="text" value="<?= $teacher['certification_field'] ?: '-' ?>"
                                    class="form-control" readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Pendidikan -->
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
                                            <td>
                                                <?= $edu['level'] ?>
                                            </td>
                                            <td>
                                                <?= $edu['major'] ?>
                                            </td>
                                            <td>
                                                <?= $edu['institution'] ?>
                                            </td>
                                            <td>
                                                <?= $edu['graduation_year'] ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-xs btn-danger btn-delete-sub"
                                                    data-type="education" data-id="<?= $edu['id'] ?>"><i
                                                        class="fas fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Tab 4: Pelatihan & Karier -->
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
                                                <td>
                                                    <?= $tr['name'] ?>
                                                </td>
                                                <td>
                                                    <?= $tr['year'] ?>
                                                </td>
                                                <td>
                                                    <?= $tr['organizer'] ?>
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-xs btn-danger btn-delete-sub"
                                                        data-type="training" data-id="<?= $tr['id'] ?>"><i
                                                            class="fas fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <hr>

                        <div class="mb-2">
                            <h6>Riwayat Karier / Penugasan</h6>
                            <p class="text-muted small">Riwayat karier/penugasan otomatis tercatat oleh Admin
                                berdasarkan SK Mengajar setiap tahun ajaran.</p>
                            <table class="table table-bordered table-sm">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Tahun Ajaran</th>
                                        <th>No. SK</th>
                                        <th>Keterangan Tugas</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($careers)): ?>
                                        <tr>
                                            <td colspan="3" class="text-center">Belum ada data.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($careers as $cr): ?>
                                            <tr>
                                                <td>
                                                    <?= $cr['academic_year'] ?>
                                                </td>
                                                <td>
                                                    <?= $cr['sk_number'] ?>
                                                </td>
                                                <td>
                                                    <?= $cr['assignment_description'] ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Tab 5: Akun Login -->
                    <div class="tab-pane fade" id="account" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Ubah Password</h6>
                                <div class="mb-3">
                                    <label class="form-label">Password Baru</label>
                                    <input type="password" name="new_password" class="form-control">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Konfirmasi Password Baru</label>
                                    <input type="password" name="confirm_password" class="form-control">
                                </div>
                                <div class="alert alert-warning">
                                    <small><i class="fas fa-exclamation-triangle"></i> Isi field di atas hanya jika Anda
                                        ingin mengubah password login Anda.</small>
                                </div>
                            </div>
                            <div class="col-md-6 border-start">
                                <h6>Informasi Akun</h6>
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" value="<?= $user['username'] ?>" class="form-control" readonly>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Email Terdaftar</label>
                                    <input type="text" value="<?= $user['email'] ?>" class="form-control" readonly>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>

                <div class="mt-4 border-top pt-3 text-end">
                    <button type="submit" class="btn btn-primary btn-lg px-5">💾 Simpan Perubahan</button>
                </div>
            </form>
        </div><!-- end card-body -->
    </div><!-- end card -->
</div><!-- end container -->

<!-- Modals -->
<div class="modal fade" id="modalEducation" tabindex="-1">
    <div class="modal-dialog">
        <form action="<?= base_url('profile/add-education') ?>" method="post" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Riwayat Pendidikan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jenjang</label>
                    <select name="level" class="form-select">
                        <option value="SD">SD</option>
                        <option value="SMP">SMP</option>
                        <option value="SMA/SMK">SMA/SMK</option>
                        <option value="D1">D1</option>
                        <option value="D2">D2</option>
                        <option value="D3">D3</option>
                        <option value="S1">S1</option>
                        <option value="S2">S2</option>
                        <option value="S3">S3</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Jurusan/Program Studi</label>
                    <input type="text" name="major" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Institusi</label>
                    <input type="text" name="institution" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tahun Lulus</label>
                    <input type="number" name="graduation_year" class="form-control" min="1970" max="<?= date('Y') ?>">
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
        <form action="<?= base_url('profile/add-training') ?>" method="post" class="modal-content">
            <?= csrf_field() ?>
            <div class="modal-header">
                <h5 class="modal-title">Tambah Riwayat Pelatihan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nama Pelatihan</label>
                    <input type="text" name="name" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tahun</label>
                    <input type="number" name="year" class="form-control" min="1990" max="<?= date('Y') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label">Penyelenggara</label>
                    <input type="text" name="organizer" class="form-control">
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

<script>
document.addEventListener('DOMContentLoaded', function () {
    // Delete sub-data buttons
    const deleteBtns = document.querySelectorAll('.btn-delete-sub');
    deleteBtns.forEach(btn => {
        btn.addEventListener('click', function () {
            if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
                const type = this.dataset.type;
                const id = this.dataset.id;
                window.location.href = `<?= base_url('profile') ?>/delete-sub/${type}/${id}`;
            }
        });
    });
});
</script>

<?= $this->endSection() ?>
