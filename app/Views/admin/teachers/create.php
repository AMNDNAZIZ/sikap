<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="d-flex justify-content-between align-items-center">
  <h1 class="mt-4">Tambah Guru Baru</h1>
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
    </ul>
  </div>
  <div class="card-body">
    <form action="<?= base_url('admin/teachers/store') ?>" method="post" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="tab-content" id="teacherTabContent">

        <!-- Tab 1: Identitas Pribadi -->
        <div class="tab-pane fade show active" id="personal" role="tabpanel">
          <div class="row">
            <div class="col-md-3 text-center mb-3">
              <div class="bg-light border rounded mb-2 d-flex align-items-center justify-content-center"
                style="height: 200px;">
                <i class="fas fa-user fa-5x text-secondary"></i>
              </div>
              <input type="file" name="photo" class="form-control form-control-sm" accept="image/*">
              <small class="text-muted">Foto Profil (JPG/PNG)</small>
            </div>
            <div class="col-md-9">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Nama Lengkap (Sesuai KTP)</label>
                  <input type="text" name="name" class="form-control" required
                    placeholder="Contoh: Budi Santoso, S.Pd.">
                </div>
                <div class="col-md-6">
                  <label class="form-label">NIK</label>
                  <input type="text" name="nik" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tempat Lahir</label>
                  <input type="text" name="birth_place" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Tanggal Lahir</label>
                  <input type="date" name="birth_date" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Jenis Kelamin</label>
                  <select name="gender" class="form-select" required>
                    <option value="L">Laki-laki</option>
                    <option value="P">Perempuan</option>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Agama</label>
                  <select name="religion" class="form-select">
                    <option value="">- Pilih Agama -</option>
                    <?php foreach (['Islam', 'Kristen', 'Katolik', 'Hindu', 'Budha', 'Konghucu'] as $r): ?>
                      <option value="<?= $r ?>"><?= $r ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nama Ibu Kandung</label>
                  <input type="text" name="mother_name" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Status Pernikahan</label>
                  <select name="marital_status" class="form-select">
                    <option value="">- Pilih Status -</option>
                    <option value="Belum Kawin">Belum Kawin</option>
                    <option value="Kawin">Kawin</option>
                    <option value="Cerai Hidup">Cerai Hidup</option>
                    <option value="Cerai Mati">Cerai Mati</option>
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
              <textarea name="address" class="form-control" rows="2"></textarea>
            </div>
            <div class="col-md-3">
              <label class="form-label">RT/RW</label>
              <input type="text" name="rt_rw" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Desa/Kelurahan</label>
              <input type="text" name="village" class="form-control">
            </div>
            <div class="col-md-5">
              <label class="form-label">Kecamatan</label>
              <input type="text" name="district" class="form-control">
            </div>
            <div class="col-md-5">
              <label class="form-label">Kabupaten/Kota</label>
              <input type="text" name="city" class="form-control">
            </div>
            <div class="col-md-4">
              <label class="form-label">Provinsi</label>
              <input type="text" name="province" class="form-control">
            </div>
            <div class="col-md-3">
              <label class="form-label">Kode Pos</label>
              <input type="text" name="postal_code" class="form-control">
            </div>
            <hr>
            <div class="col-md-6">
              <label class="form-label">Nomor Telepon/HP</label>
              <input type="text" name="phone" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Email Aktif</label>
              <input type="email" name="email" class="form-control">
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
                <option value="PNS">PNS</option>
                <option value="PPPK">PPPK</option>
                <option value="GTY/PTY">GTY/PTY</option>
                <option value="Honorer Sekolah">Honorer Sekolah</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">NIP (Jika Ada)</label>
              <input type="text" name="nip" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">NUPTK</label>
              <input type="text" name="nuptk" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Lembaga Pengangkat</label>
              <input type="text" name="appointing_agency" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">SK Pengangkatan</label>
              <input type="text" name="appointment_sk" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">TMT Pengangkatan</label>
              <input type="date" name="appointment_tmt" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Jabatan Fungsional</label>
              <input type="text" name="functional_position" class="form-control">
            </div>
            <div class="col-md-6">
              <label class="form-label">Pangkat/Golongan</label>
              <input type="text" name="rank_grade" class="form-control">
            </div>
            <hr>
            <h6 class="mb-0">Sertifikasi Pendidik</h6>
            <div class="col-md-5">
              <label class="form-label">Nomor Sertifikat</label>
              <input type="text" name="certification_number" class="form-control">
            </div>
            <div class="col-md-5">
              <label class="form-label">Bidang Studi Sertifikasi</label>
              <input type="text" name="certification_field" class="form-control">
            </div>
            <div class="col-md-2">
              <label class="form-label">Tahun</label>
              <input type="number" name="certification_year" class="form-control">
            </div>
          </div>
          <div class="mt-4 text-info">
            <small><i class="fas fa-info-circle"></i> Riwayat pendidikan dan karier dapat ditambahkan setelah data guru
              disimpan.</small>
          </div>
        </div>

      </div>

      <div class="mt-4 border-top pt-3 text-end">
        <button type="submit" class="btn btn-success btn-lg px-5">💾 Simpan Data</button>
      </div>
    </form>
  </div>
</div>

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