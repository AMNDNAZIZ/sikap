<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<div class="container">
    <h3>📍 <?= esc($title) ?></h3>
    <p class="text-muted">Tandai lokasi rumah Anda pada peta untuk memudahkan sekolah dalam pendataan</p>

    <div class="row">
        <div class="col-md-8">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">🗺️ Peta Lokasi</h5>
                </div>
                <div class="card-body p-0">
                    <div id="map" style="height: 500px; width: 100%;"></div>
                </div>
                <div class="card-footer">
                    <small class="text-muted">
                        💡 <strong>Cara menggunakan:</strong><br>
                        1. Klik tombol "📍 Gunakan Lokasi Saat Ini" untuk deteksi otomatis, atau<br>
                        2. Klik pada peta untuk menandai lokasi rumah Anda, atau<br>
                        3. Cari alamat menggunakan kotak pencarian
                    </small>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card mb-3">
                <div class="card-header">
                    <h5 class="mb-0">📝 Informasi Lokasi</h5>
                </div>
                <div class="card-body">
                    <form id="locationForm">
                        <?= csrf_field() ?>
                        
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" class="form-control" value="<?= esc($student['name']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">NIS</label>
                            <input type="text" class="form-control" value="<?= esc($student['nis']) ?>" readonly>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Alamat <span class="text-danger">*</span></label>
                            <textarea name="address" id="address" class="form-control" rows="3" required><?= esc($student['address'] ?? '') ?></textarea>
                            <small class="text-muted">Alamat lengkap rumah Anda</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Latitude <span class="text-danger">*</span></label>
                            <input type="text" name="latitude" id="latitude" class="form-control" 
                                   value="<?= esc($student['latitude'] ?? '') ?>" readonly required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Longitude <span class="text-danger">*</span></label>
                            <input type="text" name="longitude" id="longitude" class="form-control" 
                                   value="<?= esc($student['longitude'] ?? '') ?>" readonly required>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="button" id="getCurrentLocationBtn" class="btn btn-info">
                                📍 Gunakan Lokasi Saat Ini
                            </button>
                            <button type="submit" class="btn btn-primary">
                                💾 Simpan Lokasi
                            </button>
                        </div>

                        <?php if (!empty($student['latitude']) && !empty($student['longitude'])): ?>
                            <div class="alert alert-success mt-3">
                                <small>
                                    ✅ Lokasi sudah tersimpan<br>
                                    Terakhir update: <?= date('d/m/Y H:i', strtotime($student['updated_at'])) ?>
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning mt-3">
                                <small>
                                    ⚠️ Lokasi belum tersimpan<br>
                                    Silakan tandai lokasi rumah Anda
                                </small>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">ℹ️ Informasi</h5>
                </div>
                <div class="card-body">
                    <small>
                        <strong>Mengapa perlu mengisi lokasi?</strong><br>
                        Data lokasi digunakan untuk:<br>
                        • Pendataan sebaran siswa<br>
                        • Perencanaan transportasi sekolah<br>
                        • Zonasi penerimaan siswa baru<br>
                        • Koordinasi kegiatan sekolah<br><br>
                        
                        <strong>Privasi:</strong><br>
                        Data lokasi hanya dapat dilihat oleh admin dan guru sekolah.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.css" />

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet-control-geocoder@2.4.0/dist/Control.Geocoder.js"></script>

<script>
let map;
let marker;
const defaultLat = <?= !empty($student['latitude']) ? $student['latitude'] : '-6.200000' ?>;
const defaultLng = <?= !empty($student['longitude']) ? $student['longitude'] : '106.816666' ?>;

// Initialize map
function initMap() {
    // Create map
    map = L.map('map').setView([defaultLat, defaultLng], 15);

    // Add CartoDB tiles (no referer restrictions)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19,
        subdomains: 'abcd'
    }).addTo(map);

    // Add geocoder (search)
    L.Control.geocoder({
        defaultMarkGeocode: false
    })
    .on('markgeocode', function(e) {
        const latlng = e.geocode.center;
        setMarker(latlng.lat, latlng.lng);
        map.setView(latlng, 16);
    })
    .addTo(map);

    // Add existing marker if coordinates exist
    <?php if (!empty($student['latitude']) && !empty($student['longitude'])): ?>
        setMarker(<?= $student['latitude'] ?>, <?= $student['longitude'] ?>);
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

    // Add new marker
    marker = L.marker([lat, lng], {
        draggable: true
    }).addTo(map);

    marker.bindPopup('📍 Lokasi Rumah Saya').openPopup();

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
    btn.innerHTML = '⏳ Mendeteksi lokasi...';

    if (!navigator.geolocation) {
        alert('Browser Anda tidak mendukung geolocation');
        btn.disabled = false;
        btn.innerHTML = '📍 Gunakan Lokasi Saat Ini';
        return;
    }

    navigator.geolocation.getCurrentPosition(
        function(position) {
            const lat = position.coords.latitude;
            const lng = position.coords.longitude;
            
            setMarker(lat, lng);
            map.setView([lat, lng], 16);

            btn.disabled = false;
            btn.innerHTML = '✅ Lokasi Terdeteksi';
            
            setTimeout(() => {
                btn.innerHTML = '📍 Gunakan Lokasi Saat Ini';
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
            btn.innerHTML = '📍 Gunakan Lokasi Saat Ini';
        },
        {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        }
    );
});

// Form submit
document.getElementById('locationForm').addEventListener('submit', function(e) {
    e.preventDefault();

    const latitude = document.getElementById('latitude').value;
    const longitude = document.getElementById('longitude').value;
    const address = document.getElementById('address').value;

    if (!latitude || !longitude) {
        alert('Silakan tandai lokasi rumah Anda pada peta terlebih dahulu');
        return;
    }

    if (!address.trim()) {
        alert('Alamat harus diisi');
        return;
    }

    const submitBtn = this.querySelector('button[type="submit"]');
    submitBtn.disabled = true;
    submitBtn.innerHTML = '⏳ Menyimpan...';

    const formData = new FormData(this);

    fetch('<?= site_url('siswa/location/update') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('✅ ' + data.message);
            location.reload();
        } else {
            alert('❌ ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Terjadi kesalahan saat menyimpan data');
    })
    .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '💾 Simpan Lokasi';
    });
});

// Initialize map on page load
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
</script>
<?= $this->endSection() ?>
