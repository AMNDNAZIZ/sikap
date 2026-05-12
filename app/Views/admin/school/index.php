<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container-fluid px-4 pb-5">
    <!-- Header -->
    <div class="d-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="mt-4 fw-bold">Identitas Sekolah</h1>
            <p class="text-muted">Kelola informasi dasar, visi, misi, dan branding sekolah Anda.</p>
        </div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="<?= base_url('dashboard') ?>">Dashboard</a></li>
                <li class="breadcrumb-item active">Identitas Sekolah</li>
            </ol>
        </nav>
    </div>

    <!-- Alerts -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
            <i class="bi bi-check-circle-fill me-2"></i>
            <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form action="<?= base_url('admin/school/update') ?>" method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $school['id'] ?? '' ?>">

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-bottom-0 pt-4 px-4">
                <ul class="nav nav-tabs card-header-tabs" id="schoolTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="profil-tab" data-bs-toggle="tab"
                            data-bs-target="#profil" type="button" role="tab" aria-controls="profil"
                            aria-selected="true">
                            <i class="bi bi-building me-2"></i>Profil Sekolah
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="lokasi-tab" data-bs-toggle="tab"
                            data-bs-target="#lokasi" type="button" role="tab" aria-controls="lokasi"
                            aria-selected="false">
                            <i class="bi bi-geo-alt me-2"></i>Lokasi Sekolah
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="visi-tab" data-bs-toggle="tab" data-bs-target="#visi"
                            type="button" role="tab" aria-controls="visi" aria-selected="false">
                            <i class="bi bi-bullseye me-2"></i>Visi & Misi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="branding-tab" data-bs-toggle="tab"
                            data-bs-target="#branding" type="button" role="tab" aria-controls="branding"
                            aria-selected="false">
                            <i class="bi bi-palette me-2"></i>Branding & Medsos
                        </button>
                    </li>
                </ul>
            </div>

            <div class="card-body p-4">
                <div class="tab-content" id="schoolTabsContent">

                    <!-- Tab 1: Profil Sekolah -->
                    <div class="tab-pane fade show active" id="profil" role="tabpanel" aria-labelledby="profil-tab">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Nama Sekolah</label>
                                <input type="text" name="name" class="form-control" value="<?= $school['name'] ?? '' ?>"
                                    required placeholder="Contoh: SMA Negeri 1 Jakarta">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Level Sekolah</label>
                                <select name="level" class="form-select" required>
                                    <option value="">Pilih Level</option>
                                    <option value="1" <?= ($school['level'] ?? '') == 1 ? 'selected' : '' ?>>SD / Sederajat
                                    </option>
                                    <option value="2" <?= ($school['level'] ?? '') == 2 ? 'selected' : '' ?>>SMP /
                                        Sederajat</option>
                                    <option value="3" <?= ($school['level'] ?? '') == 3 ? 'selected' : '' ?>>SMA /
                                        Sederajat</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kepala Sekolah</label>
                                <input type="text" name="headmaster" class="form-control"
                                    value="<?= $school['headmaster'] ?? '' ?>" placeholder="Nama dan Gelar">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">NIP Kepala Sekolah</label>
                                <input type="text" name="principal_nip" class="form-control"
                                    value="<?= $school['principal_nip'] ?? '' ?>" placeholder="NIP">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Telepon</label>
                                <input type="text" name="phone" class="form-control"
                                    value="<?= $school['phone'] ?? '' ?>">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Email</label>
                                <input type="email" name="email" class="form-control"
                                    value="<?= $school['email'] ?? '' ?>">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-semibold">Alamat Lengkap</label>
                                <textarea name="address" class="form-control"
                                    rows="3"><?= $school['address'] ?? '' ?></textarea>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kabupaten/Kota</label>
                                <input type="text" name="city_regency" class="form-control"
                                    value="<?= $school['city_regency'] ?? '' ?>" placeholder="Contoh: Indramayu">
                                <small class="text-muted">Digunakan untuk titimangsa pada dokumen (ATP, Raport, dll).</small>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Lokasi Sekolah -->
                    <div class="tab-pane fade" id="lokasi" role="tabpanel" aria-labelledby="lokasi-tab">
                        <div class="row g-4">
                            <div class="col-12">
                                <div class="alert alert-info border-0">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>Koordinat lokasi sekolah</strong> digunakan untuk fitur peta sebaran siswa dan perhitungan jarak.
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Latitude</label>
                                <input type="text" name="latitude" id="latitude" class="form-control" 
                                    value="<?= $school['latitude'] ?? '' ?>" 
                                    placeholder="Contoh: -6.200000" step="any">
                                <small class="text-muted">Koordinat lintang (-90 sampai 90)</small>
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Longitude</label>
                                <input type="text" name="longitude" id="longitude" class="form-control" 
                                    value="<?= $school['longitude'] ?? '' ?>" 
                                    placeholder="Contoh: 106.816666" step="any">
                                <small class="text-muted">Koordinat bujur (-180 sampai 180)</small>
                            </div>

                            <div class="col-12">
                                <button type="button" id="getCurrentLocationBtn" class="btn btn-info mb-3">
                                    <i class="bi bi-geo-alt-fill me-2"></i>Gunakan Lokasi Saat Ini
                                </button>
                                <button type="button" id="searchAddressBtn" class="btn btn-secondary mb-3 ms-2">
                                    <i class="bi bi-search me-2"></i>Cari Alamat
                                </button>
                            </div>

                            <div class="col-12">
                                <label class="form-label fw-semibold">Peta Lokasi</label>
                                <div id="schoolMap" style="height: 400px; width: 100%; border-radius: 8px; overflow: hidden;"></div>
                                <small class="text-muted d-block mt-2">
                                    💡 <strong>Cara menggunakan:</strong><br>
                                    1. Klik tombol "Gunakan Lokasi Saat Ini" untuk deteksi otomatis, atau<br>
                                    2. Klik pada peta untuk menandai lokasi sekolah, atau<br>
                                    3. Cari alamat menggunakan tombol "Cari Alamat"
                                </small>
                            </div>

                            <div class="col-12">
                                <div class="card bg-light border-0">
                                    <div class="card-body">
                                        <h6 class="fw-bold mb-2">📍 Cara Mendapatkan Koordinat dari Google Maps:</h6>
                                        <ol class="mb-0 small">
                                            <li>Buka Google Maps di browser</li>
                                            <li>Cari lokasi sekolah Anda</li>
                                            <li>Klik kanan pada lokasi sekolah</li>
                                            <li>Klik koordinat yang muncul (akan ter-copy otomatis)</li>
                                            <li>Paste di form Latitude dan Longitude</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Visi Misi -->
                    <div class="tab-pane fade" id="visi" role="tabpanel" aria-labelledby="visi-tab">
                        <div class="row g-4">
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-primary">Visi Sekolah</label>
                                <textarea name="vision" class="form-control"
                                    rows="6"><?= $school['vision'] ?? '' ?></textarea>
                                <small class="text-muted">Jelaskan visi jangka panjang sekolah.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold text-success">Misi Sekolah</label>
                                <textarea name="mission" class="form-control" rows="6"
                                    placeholder="Poin 1...&#10;Poin 2..."><?= $school['mission'] ?? '' ?></textarea>
                                <small class="text-muted">Gunakan baris baru (Enter) untuk setiap poin misi.</small>
                            </div>
                            <div class="col-12">
                                <div class="card bg-light border-0 rounded-3">
                                    <div class="card-body">
                                        <label class="form-label fw-semibold">Gambar Ilustrasi Visi</label>
                                        <div class="d-flex align-items-center gap-4">
                                            <img id="visionPreview"
                                                src="<?= !empty($school['vision_image']) ? base_url('uploads/vision/' . $school['vision_image']) : 'https://placehold.co/400x200?text=No+Image' ?>"
                                                class="rounded shadow-sm"
                                                style="max-height: 120px; max-width: 250px; object-fit: cover;">
                                            <div class="flex-grow-1">
                                                <input type="file" name="vision_image" class="form-control"
                                                    id="visionInput" accept="image/*">
                                                <small class="text-muted d-block mt-1">Gambar ini ditampilkan di bagian
                                                    Visi Misi halaman depan.</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab 3: Branding & Medsos -->
                    <div class="tab-pane fade" id="branding" role="tabpanel" aria-labelledby="branding-tab">
                        <div class="row g-4">
                            <div class="col-md-12">
                                <h6 class="fw-bold mb-3 section-title"><i class="bi bi-image me-2"></i>Logo Sekolah</h6>
                                <div class="d-flex align-items-center gap-4 p-3 border rounded bg-light">
                                    <img id="logoPreview"
                                        src="<?= !empty($school['logo']) ? base_url('uploads/logo/' . $school['logo']) : 'https://placehold.co/150x150?text=No+Logo' ?>"
                                        class="rounded shadow-sm bg-white p-1"
                                        style="max-height: 100px; width: 100px; object-fit: contain;">
                                    <div class="flex-grow-1">
                                        <input type="file" name="logo" class="form-control" id="logoInput"
                                            accept="image/*">
                                        <small class="text-muted d-block mt-1">Format: PNG/JPG. Disarankan rasio
                                            1:1.</small>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <h6 class="fw-bold mb-3 section-title mt-4"><i class="bi bi-share me-2"></i>Media Sosial
                                </h6>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label">Facebook</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-primary border-end-0"><i
                                                    class="bi bi-facebook"></i></span>
                                            <input type="url" name="facebook" class="form-control border-start-0"
                                                value="<?= $school['facebook'] ?? '' ?>"
                                                placeholder="https://facebook.com/...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Instagram</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-danger border-end-0"><i
                                                    class="bi bi-instagram"></i></span>
                                            <input type="url" name="instagram" class="form-control border-start-0"
                                                value="<?= $school['instagram'] ?? '' ?>"
                                                placeholder="https://instagram.com/...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">YouTube</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-danger border-end-0"><i
                                                    class="bi bi-youtube"></i></span>
                                            <input type="url" name="youtube" class="form-control border-start-0"
                                                value="<?= $school['youtube'] ?? '' ?>"
                                                placeholder="https://youtube.com/...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">TikTok</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-dark border-end-0"><i
                                                    class="bi bi-tiktok"></i></span>
                                            <input type="url" name="tiktok" class="form-control border-start-0"
                                                value="<?= $school['tiktok'] ?? '' ?>"
                                                placeholder="https://tiktok.com/@...">
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label">Twitter / X</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-dark border-end-0"><i
                                                    class="bi bi-twitter-x"></i></span>
                                            <input type="url" name="twitter" class="form-control border-start-0"
                                                value="<?= $school['twitter'] ?? '' ?>" placeholder="https://x.com/...">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <?php if (session()->get('user')['role_id'] != 2): ?>
                <div class="card-footer bg-white border-top-0 p-4 text-end">
                    <button type="submit" class="btn btn-primary px-5 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-save me-2"></i>Simpan Perubahan
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css" />

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

    .nav-tabs .nav-link i {
        font-size: 1.1rem;
        vertical-align: middle;
    }

    /* Card Styling */
    .card {
        transition: box-shadow 0.3s ease;
    }

    .card:hover {
        box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
    }

    /* Form Elements */
    .form-label {
        font-size: 0.9rem;
        margin-bottom: 0.5rem;
    }

    .form-control:focus,
    .form-select:focus {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
    }

    /* Image Preview */
    .img-preview-container {
        width: 100px;
        height: 100px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #dee2e6;
    }

    /* Section Titles */
    .section-title {
        color: #495057;
        border-bottom: 2px solid #e9ecef;
        padding-bottom: 0.5rem;
    }

    /* Input Group Icons */
    .input-group-text {
        min-width: 45px;
        justify-content: center;
    }

    /* Responsive */
    @media (max-width: 768px) {
        .nav-tabs .nav-link {
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
        }

        .nav-tabs .nav-link i {
            font-size: 1rem;
        }
    }
</style>

<script>
    // Helper to preview images
    const setupPreview = (inputId, imgId) => {
        const input = document.getElementById(inputId);
        if (input) {
            input.onchange = evt => {
                const [file] = input.files;
                if (file) document.getElementById(imgId).src = URL.createObjectURL(file);
            }
        }
    };
    setupPreview('logoInput', 'logoPreview');
    setupPreview('visionInput', 'visionPreview');
</script>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>

<script>
let map;
let marker;
const defaultLat = <?= !empty($school['latitude']) ? $school['latitude'] : '-6.200000' ?>;
const defaultLng = <?= !empty($school['longitude']) ? $school['longitude'] : '106.816666' ?>;

// Initialize map when Lokasi tab is shown
document.getElementById('lokasi-tab').addEventListener('shown.bs.tab', function () {
    if (!map) {
        initMap();
    }
});

// Initialize map
function initMap() {
    // Create map
    map = L.map('schoolMap').setView([defaultLat, defaultLng], 15);

    // Add CartoDB tiles (no referer restrictions)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19,
        subdomains: 'abcd'
    }).addTo(map);

    // Add geocoder (search)
    const geocoder = L.Control.geocoder({
        defaultMarkGeocode: false
    })
    .on('markgeocode', function(e) {
        const latlng = e.geocode.center;
        setMarker(latlng.lat, latlng.lng);
        map.setView(latlng, 16);
    })
    .addTo(map);

    // Add existing marker if coordinates exist
    <?php if (!empty($school['latitude']) && !empty($school['longitude'])): ?>
        setMarker(<?= $school['latitude'] ?>, <?= $school['longitude'] ?>);
    <?php endif; ?>

    // Click on map to set marker
    map.on('click', function(e) {
        setMarker(e.latlng.lat, e.latlng.lng);
    });
}

// Set marker on map
function setMarker(lat, lng) {
    // Remove existing marker
    if (marker) {
        map.removeLayer(marker);
    }

    // Add new marker with custom HTML icon
    const schoolIcon = L.divIcon({
        className: 'custom-school-marker',
        html: '<div style="background-color: #dc3545; width: 32px; height: 32px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 3px 6px rgba(0,0,0,0.4);"><div style="transform: rotate(45deg); margin-top: 4px; text-align: center; color: white; font-size: 18px;">🏫</div></div>',
        iconSize: [32, 44],
        iconAnchor: [16, 44],
        popupAnchor: [0, -44]
    });

    marker = L.marker([lat, lng], {
        icon: schoolIcon,
        draggable: true
    }).addTo(map);

    marker.bindPopup('🏫 Lokasi Sekolah').openPopup();

    // Update form fields
    document.getElementById('latitude').value = lat.toFixed(8);
    document.getElementById('longitude').value = lng.toFixed(8);

    // Marker drag event
    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        document.getElementById('latitude').value = position.lat.toFixed(8);
        document.getElementById('longitude').value = position.lng.toFixed(8);
    });
}

// Get current location from browser
document.getElementById('getCurrentLocationBtn').addEventListener('click', function() {
    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Mendeteksi lokasi...';

    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung geolocation');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-geo-alt-fill me-2"></i>Gunakan Lokasi Saat Ini';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            // Initialize map if not yet
            if (!map) {
                initMap();
            }
            
            setMarker(lat, lng);
            map.setView([lat, lng], 16);

            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i>Lokasi Terdeteksi';
            
            setTimeout(() => {
                btn.innerHTML = '<i class="bi bi-geo-alt-fill me-2"></i>Gunakan Lokasi Saat Ini';
            }, 2000);
        },
        function(error) {
            let message = 'Gagal mendeteksi lokasi';
            
            switch(error.code) {
                case error.PERMISSION_DENIED:
                    message = 'Izin akses lokasi ditolak. Silakan aktifkan di pengaturan browser.';
                    break;
                case error.POSITION_UNAVAILABLE:
                    message = 'Informasi lokasi tidak tersedia';
                    break;
                case error.TIMEOUT:
                    message = 'Timeout mendeteksi lokasi';
                    break;
            }
            
            alert(message);
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-geo-alt-fill me-2"></i>Gunakan Lokasi Saat Ini';
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
});

// Search address button
document.getElementById('searchAddressBtn').addEventListener('click', function() {
    // Initialize map if not yet
    if (!map) {
        initMap();
    }
    
    // Trigger geocoder search
    const geocoderElement = document.querySelector('.leaflet-control-geocoder');
    if (geocoderElement) {
        geocoderElement.querySelector('a').click();
    }
});

// Update marker when latitude/longitude input changes
document.getElementById('latitude').addEventListener('change', function() {
    const lat = parseFloat(this.value);
    const lng = parseFloat(document.getElementById('longitude').value);
    
    if (!isNaN(lat) && !isNaN(lng)) {
        if (!map) {
            initMap();
        }
        setMarker(lat, lng);
        map.setView([lat, lng], 15);
    }
});

document.getElementById('longitude').addEventListener('change', function() {
    const lat = parseFloat(document.getElementById('latitude').value);
    const lng = parseFloat(this.value);
    
    if (!isNaN(lat) && !isNaN(lng)) {
        if (!map) {
            initMap();
        }
        setMarker(lat, lng);
        map.setView([lat, lng], 15);
    }
});
</script>

<?= $this->endSection() ?>