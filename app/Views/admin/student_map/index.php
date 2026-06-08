<?= $this->extend('layouts/app') ?>
<?= $this->section('content') ?>

<!-- Referrer Policy for OpenStreetMap -->
<meta name="referrer" content="no-referrer-when-downgrade">

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3>🗺️ <?= esc($title) ?></h3>
        <div>
            <a href="<?= site_url('admin/student-map/statistics') ?>" class="btn btn-info">
                📊 Statistik
            </a>
            <a href="<?= site_url('admin/student-map/export') ?>" class="btn btn-success">
                📥 Export Excel
            </a>
        </div>
    </div>

    <!-- Filter Section -->
    <div class="card mb-3">
        <div class="card-body">
            <form id="filterForm" class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Tingkat</label>
                    <select name="level" id="levelFilter" class="form-select">
                        <option value="">Semua Tingkat</option>
                        <?php foreach ($levels as $lvl): ?>
                            <option value="<?= $lvl['level'] ?>" <?= $filters['level'] == $lvl['level'] ? 'selected' : '' ?>>
                                Tingkat <?= esc($lvl['level']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Kelas</label>
                    <select name="class_id" id="classFilter" class="form-select">
                        <option value="">Semua Kelas</option>
                        <?php foreach ($classes as $class): ?>
                            <option value="<?= $class['id'] ?>" <?= $filters['class_id'] == $class['id'] ? 'selected' : '' ?>>
                                <?= esc($class['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">Jarak Maks (km)</label>
                    <input type="number" name="max_distance" id="distanceFilter" class="form-control" 
                           placeholder="Contoh: 5" value="<?= esc($filters['max_distance'] ?? '') ?>" step="0.1">
                </div>

                <div class="col-md-2">
                    <label class="form-label">Status Koordinat</label>
                    <select name="has_coordinates" id="coordinatesFilter" class="form-select">
                        <option value="">Semua</option>
                        <option value="yes" <?= $filters['has_coordinates'] == 'yes' ? 'selected' : '' ?>>Sudah diisi</option>
                        <option value="no" <?= $filters['has_coordinates'] == 'no' ? 'selected' : '' ?>>Belum diisi</option>
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        🔍 Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-3" id="statisticsCards">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title">Total Siswa</h6>
                    <h3 id="totalStudents">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title">Sudah Diisi</h6>
                    <h3 id="withCoordinates">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h6 class="card-title">Belum Diisi</h6>
                    <h3 id="withoutCoordinates">-</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title">Jarak Rata-rata</h6>
                    <h3 id="avgDistance">-</h3>
                </div>
            </div>
        </div>
    </div>

    <!-- Map Container -->
    <div class="card mb-3">
        <div class="card-body p-0">
            <div id="map" style="height: 600px; width: 100%;"></div>
        </div>
    </div>

    <!-- Students Without Coordinates -->
    <div class="card" id="noCoordinatesCard" style="display: none;">
        <div class="card-header bg-warning">
            <h5 class="mb-0">⚠️ Siswa Belum Mengisi Koordinat</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Alamat</th>
                        </tr>
                    </thead>
                    <tbody id="noCoordinatesList"></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />

<style>
.marker-cluster-small {
    background-color: rgba(181, 226, 140, 0.6);
}
.marker-cluster-small div {
    background-color: rgba(110, 204, 57, 0.6);
}
.marker-cluster-medium {
    background-color: rgba(241, 211, 87, 0.6);
}
.marker-cluster-medium div {
    background-color: rgba(240, 194, 12, 0.6);
}
.marker-cluster-large {
    background-color: rgba(253, 156, 115, 0.6);
}
.marker-cluster-large div {
    background-color: rgba(241, 128, 23, 0.6);
}
</style>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

<script>
let map;
let markers;
let schoolMarker;

// Initialize map
function initMap() {
    // Create map centered on school
    map = L.map('map').setView([<?= $schoolLat ?>, <?= $schoolLng ?>], 13);

    // Add tile layer - using CartoDB Positron (no referer restrictions)
    // Alternative tile providers that work without referer issues:
    // 1. CartoDB Positron (light theme)
    L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
        attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors © <a href="https://carto.com/attributions">CARTO</a>',
        maxZoom: 19,
        subdomains: 'abcd'
    }).addTo(map);
    
    // 2. Alternative: CartoDB Voyager (colored theme) - uncomment to use
    // L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
    //     attribution: '© OpenStreetMap contributors © CARTO',
    //     maxZoom: 19,
    //     subdomains: 'abcd'
    // }).addTo(map);
    
    // 3. Alternative: Esri World Street Map - uncomment to use
    // L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Street_Map/MapServer/tile/{z}/{y}/{x}', {
    //     attribution: 'Tiles © Esri',
    //     maxZoom: 19
    // }).addTo(map);

    // Initialize marker cluster group
    markers = L.markerClusterGroup({
        maxClusterRadius: 50,
        spiderfyOnMaxZoom: true,
        showCoverageOnHover: false
    });

    // Load data
    loadMapData();
}

// Load map data from API
function loadMapData() {
    const formData = new FormData(document.getElementById('filterForm'));
    const params = new URLSearchParams(formData).toString();

    fetch('<?= site_url('admin/student-map/get-data') ?>?' + params)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateMap(data);
                updateStatistics(data.statistics);
                updateNoCoordinatesList(data.studentsWithoutCoordinates);
            }
        })
        .catch(error => {
            console.error('Error loading map data:', error);
            alert('Gagal memuat data peta');
        });
}

// Update map with markers
function updateMap(data) {
    // Clear existing markers
    markers.clearLayers();
    if (schoolMarker) {
        map.removeLayer(schoolMarker);
    }

    // Add school marker with custom HTML icon
    const schoolIcon = L.divIcon({
        className: 'custom-school-marker',
        html: '<div style="background-color: #dc3545; width: 30px; height: 30px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 3px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><div style="transform: rotate(45deg); margin-top: 3px; text-align: center; color: white; font-size: 16px;">🏫</div></div>',
        iconSize: [30, 42],
        iconAnchor: [15, 42],
        popupAnchor: [0, -42]
    });

    schoolMarker = L.marker([data.school.latitude, data.school.longitude], {
        icon: schoolIcon
    }).addTo(map);

    schoolMarker.bindPopup(`
        <div style="min-width: 200px;">
            <h6><strong>🏫 ${data.school.name}</strong></h6>
            <p class="mb-1"><small>Lokasi Sekolah</small></p>
        </div>
    `);

    // Add student markers with custom HTML icons
    data.markers.forEach(student => {
        const iconColor = student.gender === 'L' ? '#0d6efd' : '#d63384'; // blue for male, pink for female
        const iconEmoji = student.gender === 'L' ? '👦' : '👧';
        
        const studentIcon = L.divIcon({
            className: 'custom-student-marker',
            html: `<div style="background-color: ${iconColor}; width: 28px; height: 28px; border-radius: 50% 50% 50% 0; transform: rotate(-45deg); border: 2px solid white; box-shadow: 0 2px 5px rgba(0,0,0,0.3);"><div style="transform: rotate(45deg); margin-top: 2px; text-align: center; font-size: 14px;">${iconEmoji}</div></div>`,
            iconSize: [28, 40],
            iconAnchor: [14, 40],
            popupAnchor: [0, -40]
        });

        const marker = L.marker([student.latitude, student.longitude], {
            icon: studentIcon
        });

        marker.bindPopup(`
            <div style="min-width: 250px;">
                <h6><strong>${student.name}</strong></h6>
                <table class="table table-sm table-borderless mb-0">
                    <tr><td><small>NIS:</small></td><td><small>${student.nis}</small></td></tr>
                    <tr><td><small>Kelas:</small></td><td><small>${student.class}</small></td></tr>
                    <tr><td><small>Alamat:</small></td><td><small>${student.address}</small></td></tr>
                    <tr><td><small>Jarak:</small></td><td><small><strong>${student.distance} km</strong></small></td></tr>
                </table>
                <div class="mt-2">
                    <a href="https://www.google.com/maps/dir/?api=1&origin=${data.school.latitude},${data.school.longitude}&destination=${student.latitude},${student.longitude}" 
                       target="_blank" class="btn btn-sm btn-primary">
                        📍 Rute
                    </a>
                </div>
            </div>
        `);

        markers.addLayer(marker);
    });

    map.addLayer(markers);

    // Fit bounds if there are markers
    if (data.markers.length > 0) {
        const bounds = markers.getBounds();
        bounds.extend([data.school.latitude, data.school.longitude]);
        map.fitBounds(bounds, { padding: [50, 50] });
    }
}

// Update statistics cards
function updateStatistics(stats) {
    document.getElementById('totalStudents').textContent = stats.total;
    document.getElementById('withCoordinates').textContent = stats.withCoordinates;
    document.getElementById('withoutCoordinates').textContent = stats.withoutCoordinates;
    document.getElementById('avgDistance').textContent = stats.averageDistance + ' km';
}

// Update list of students without coordinates
function updateNoCoordinatesList(students) {
    const card = document.getElementById('noCoordinatesCard');
    const tbody = document.getElementById('noCoordinatesList');

    if (students.length > 0) {
        card.style.display = 'block';
        tbody.innerHTML = students.map(student => `
            <tr>
                <td>${student.nis}</td>
                <td>${student.name}</td>
                <td>${student.class}</td>
                <td>${student.address}</td>
            </tr>
        `).join('');
    } else {
        card.style.display = 'none';
    }
}

// Filter form submit
document.getElementById('filterForm').addEventListener('submit', function(e) {
    e.preventDefault();
    loadMapData();
});

// Initialize map on page load
document.addEventListener('DOMContentLoaded', function() {
    initMap();
});
</script>
<?= $this->endSection() ?>
