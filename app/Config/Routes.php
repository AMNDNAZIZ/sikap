<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Home::index');
$routes->get('/profil', 'Home::profil');
$routes->get('/tentang', 'Home::about');
$routes->get('/login', 'Auth::login');

// Public Guest Book (Buku Tamu)
$routes->get('buku-tamu', 'BukuTamu::index');
$routes->get('buku-tamu/umum', 'BukuTamu::formUmum');
$routes->post('buku-tamu/umum/store', 'BukuTamu::storeUmum');
$routes->get('buku-tamu/dinas', 'BukuTamu::formDinas');
$routes->post('buku-tamu/dinas/store', 'BukuTamu::storeDinas');
$routes->get('buku-tamu/sukses', 'BukuTamu::sukses');

// Public QR Code Verification
$routes->get('verify/(:any)', 'Verify::index/$1');

$routes->get('activate', 'Activate::index');
$routes->post('activate/process', 'Activate::process');
$routes->post('activate/checkRenewal', 'Activate::checkRenewal');
$routes->post('activate/checkOnline', 'Activate::checkOnline');

// PWA Routes
$routes->get('manifest.json', 'Pwa::manifest');
$routes->get('service-worker.js', 'Pwa::serviceWorker');
$routes->get('offline.html', 'Pwa::offline');

// API Routes — untuk SIAKAD Sync Agent (PowerShell bridge dari komputer Dapodik)
// Tidak memerlukan session/CSRF — autentikasi via header X-Siakad-Sync-Token
$routes->post('api/dapodik/receive', 'Api\DapodikReceive::index');

$routes->post('/auth/attemptLogin', 'Auth::attemptLogin');
$routes->get('/logout', 'Auth::logout');
$routes->post('/auth/ping-session', 'Auth::pingSession'); // Keep session alive
$routes->get('/auth/change-password-required', 'Auth::changePasswordRequired');
$routes->post('/auth/update-password-required', 'Auth::updatePasswordRequired');
$routes->get('berita', 'News::index');
$routes->get('berita/(:any)', 'News::view/$1');
$routes->get('/dashboard', 'Dashboard::index');

// Maintenance Routes (Accessible even during license lock)
$routes->get('maintenance/reset-hashes/(:any)', 'Maintenance::resetHashes/$1');
$routes->get('maintenance/debug-token', 'Maintenance::debugToken');

// ==================== ADMIN & GURU ROUTES ====================
$routes->group('admin', ['namespace' => 'App\Controllers\Admin'], function ($routes) {

    // Helper to apply auth filter to subgroups
    $auth1 = ['filter' => 'auth:1'];
    $auth12 = ['filter' => 'auth:1,2'];
    $auth13 = ['filter' => 'auth:1,3'];
    $auth123 = ['filter' => 'auth:1,2,3'];
    $auth126 = ['filter' => 'auth:1,2,6'];
    $auth17   = ['filter' => 'auth:1,7'];
    $auth127  = ['filter' => 'auth:1,2,7'];
    $auth1237 = ['filter' => 'auth:1,2,3,7'];

    // 1. User Management (Admin & Principal)
    $routes->get('users', 'User::index', $auth12);
    $routes->get('users/create', 'User::create', $auth12);
    $routes->post('users/store', 'User::store', $auth12);
    $routes->get('users/edit/(:num)', 'User::edit/$1', $auth12);
    $routes->post('users/update/(:num)', 'User::update/$1', $auth12);

    // Session Management
    $routes->get('session-info', 'User::sessionInfo', $auth1);
    $routes->post('session-clean', 'User::sessionClean', $auth1);
    $routes->get('users/delete/(:num)', 'User::delete/$1', $auth12);
    $routes->get('users/reset-password/(:num)', 'User::resetPassword/$1', $auth12);

    // 2. Identitas & Pengaturan
    $routes->get('school', 'School::index', $auth127);
    $routes->post('school/update', 'School::update', $auth127);
    $routes->get('academic-year', 'AcademicYear::index', $auth127);
    $routes->get('academic-year/create', 'AcademicYear::create', $auth127);
    $routes->post('academic-year/store', 'AcademicYear::store', $auth127);
    $routes->get('academic-year/set-active/(:num)', 'AcademicYear::setActive/$1', $auth127);
    $routes->post('academic-year/set-active/(:num)', 'AcademicYear::setActive/$1', $auth127);
    $routes->get('academic-year/edit/(:num)', 'AcademicYear::edit/$1', $auth127);
    $routes->post('academic-year/update/(:num)', 'AcademicYear::update/$1', $auth127);

    // Jenjang Master
    $routes->get('jenjang-master', 'JenjangMaster::index', $auth1);
    $routes->post('jenjang-master/store', 'JenjangMaster::store', $auth1);
    $routes->get('jenjang-master/delete/(:num)', 'JenjangMaster::delete/$1', $auth1);

    // Mapel Master
    $routes->get('mapel-master', 'MapelMaster::index', $auth12);
    $routes->post('mapel-master/store', 'MapelMaster::store', $auth12);
    $routes->get('mapel-master/delete/(:num)', 'MapelMaster::delete/$1', $auth12);


    // 3. Updater (Admin Only)
    $routes->get('updater', 'Updater::index', ['filter' => 'auth:1']);
    $routes->post('updater/patch-files', 'Updater::patchFiles', ['filter' => 'auth:1']);
    $routes->get('updater/run-migrations', 'Updater::runMigrations', ['filter' => 'auth:1']);
    $routes->get('updater/generate-patch', 'Updater::generatePatch', ['filter' => 'auth:1']);
    $routes->get('updater/generate-manifest', 'Updater::generateManifest', ['filter' => 'auth:1']);
    $routes->get('updater/check-online', 'Updater::checkOnlineUpdate', ['filter' => 'auth:1']);
    $routes->post('updater/apply-online', 'Updater::applyOnlineUpdate', ['filter' => 'auth:1']);
    $routes->get('updater/backup-database', 'Updater::backupDatabase', ['filter' => 'auth:1']);
    $routes->post('updater/restore-database', 'Updater::restoreDatabase', ['filter' => 'auth:1']);

    // 4. Personalia & Kesiswaan (Admin & Principal)
    $routes->get('teachers', 'Teachers::index', $auth127);
    $routes->get('teachers/create', 'Teachers::create', $auth127);
    $routes->post('teachers/store', 'Teachers::store', $auth127);
    $routes->get('teachers/downloadTemplate', 'Teachers::downloadTemplate', $auth127);
    $routes->post('teachers/import', 'Teachers::import', $auth127);
    $routes->post('teachers/import-dapodik/preview', 'Teachers::importDapodikPreview', $auth127);
    $routes->post('teachers/import-dapodik', 'Teachers::importDapodik', $auth127);
    $routes->post('teachers/uploadDocument/(:num)', 'Teachers::uploadDocument/$1', $auth127);
    $routes->get('teachers/deleteDocument/(:num)', 'Teachers::deleteDocument/$1', $auth127);
    $routes->get('teachers/downloadDocument/(:num)', 'Teachers::downloadDocument/$1', $auth127);
    $routes->get('teachers/viewDocument/(:num)', 'Teachers::viewDocument/$1', $auth127);
    $routes->get('teachers/edit/(:num)', 'Teachers::edit/$1', $auth127);
    $routes->post('teachers/update/(:num)', 'Teachers::update/$1', $auth127);
    $routes->get('teachers/delete/(:num)', 'Teachers::delete/$1', $auth127);
    $routes->post('teachers/addEducation/(:num)', 'Teachers::addEducation/$1', $auth127);
    $routes->post('teachers/addTraining/(:num)', 'Teachers::addTraining/$1', $auth127);
    $routes->post('teachers/addCareer/(:num)', 'Teachers::addCareer/$1', $auth127);
    $routes->get('teachers/deleteSub/(:segment)/(:num)', 'Teachers::deleteSub/$1/$2', $auth127);

    $routes->get('students', 'Students::index', $auth127);
    $routes->get('students/create', 'Students::create', $auth127);
    $routes->post('students/store', 'Students::store', $auth127);
    $routes->get('students/edit/(:num)', 'Students::edit/$1', $auth127);
    $routes->post('students/update/(:num)', 'Students::update/$1', $auth127);
    $routes->post('students/delete/(:num)', 'Students::delete/$1', $auth127);
    $routes->get('students/show/(:num)', 'Students::show/$1', $auth127);
    $routes->post('students/import', 'Students::import', $auth127);
    $routes->get('students/download-template', 'Students::downloadTemplate', $auth127);

    // Student Map (Admin, Principal, Teacher)
    $routes->get('student-map', 'StudentMap::index', $auth123);
    $routes->get('student-map/get-data', 'StudentMap::getData', $auth123);
    $routes->get('student-map/statistics', 'StudentMap::statistics', $auth123);
    $routes->get('student-map/export', 'StudentMap::export', $auth123);

    $routes->get('classes', 'Classes::index', $auth127);
    $routes->get('classes/create', 'Classes::create', $auth127);
    $routes->post('classes/store', 'Classes::store', $auth127);
    $routes->get('classes/edit/(:num)', 'Classes::edit/$1', $auth127);
    $routes->post('classes/update/(:num)', 'Classes::update/$1', $auth127);
    $routes->get('classes/delete/(:num)', 'Classes::delete/$1', $auth127);
    $routes->get('classes/toggle-active/(:num)', 'Classes::toggleActive/$1', $auth127);

    $routes->get('active-classes', 'ActiveClasses::index', $auth127);
    $routes->post('active-classes/update/(:num)', 'ActiveClasses::update/$1', $auth127);

    $routes->get('placement', 'Placement::index', $auth127);
    $routes->post('placement/store', 'Placement::store', $auth127);

    $routes->get('promotions', 'Promotions::index', $auth127);
    $routes->get('promotions/fix-graduated', 'Promotions::fixGraduatedAccounts', $auth127);
    $routes->post('promotions/process', 'Promotions::process', $auth127);
    $routes->post('promotions/promote', 'Promotions::promote', $auth127);
    $routes->post('promotions/graduate', 'Promotions::graduate', $auth127);
    $routes->post('promotions/cancel', 'Promotions::cancel', $auth127);

    $routes->get('student-records', 'StudentRecords::index', $auth127);
    $routes->get('student-records/(:num)', 'StudentRecords::index/$1', $auth127);

    $routes->get('alumni', 'Alumni::index', $auth127);
    $routes->get('alumni/export-pdf', 'Alumni::exportPdf', $auth127);

    $routes->get('student-mutation', 'StudentMutation::index', $auth127);
    $routes->get('student-mutation/create', 'StudentMutation::create', $auth127);
    $routes->post('student-mutation/store', 'StudentMutation::store', $auth127);
    $routes->get('student-mutation/show/(:num)', 'StudentMutation::show/$1', $auth127);
    $routes->post('student-mutation/approve/(:num)', 'StudentMutation::approve/$1', $auth127);
    $routes->post('student-mutation/reject/(:num)', 'StudentMutation::reject/$1', $auth127);
    $routes->get('student-mutation/print/(:num)', 'StudentMutation::print/$1', $auth127);
    $routes->post('student-mutation/delete/(:num)', 'StudentMutation::delete/$1', $auth127);

    // Wali Kelas Students (Guru only)
    $routes->get('wali-kelas-students', 'WaliKelasStudents::index', ['filter' => 'auth:3']);
    $routes->get('wali-kelas-students/detail/(:num)', 'WaliKelasStudents::detail/$1', ['filter' => 'auth:3']);
    $routes->post('wali-kelas-students/update/(:num)', 'WaliKelasStudents::update/$1', ['filter' => 'auth:3']);

    $routes->get('sync-parents', 'AccountSync::syncParents', $auth12);

    // 5. Master Data (Admin & Principal)
    $routes->get('subjects', 'Subjects::index', $auth127);
    $routes->get('subjects/create', 'Subjects::create', $auth127);
    $routes->post('subjects/store', 'Subjects::store', $auth127);
    $routes->get('subjects/edit/(:num)', 'Subjects::edit/$1', $auth127);
    $routes->post('subjects/update/(:num)', 'Subjects::update/$1', $auth127);
    $routes->post('subjects/delete/(:num)', 'Subjects::delete/$1', $auth127);

    $routes->get('holidays', 'Holidays::index', $auth127);
    $routes->get('holidays/create', 'Holidays::create', $auth127);
    $routes->post('holidays/store', 'Holidays::store', $auth127);
    $routes->get('holidays/edit/(:num)', 'Holidays::edit/$1', $auth127);
    $routes->post('holidays/update/(:num)', 'Holidays::update/$1', $auth127);
    $routes->get('holidays/delete/(:num)', 'Holidays::delete/$1', $auth127);

    $routes->get('teachingassignments', 'TeachingAssignments::index', $auth127);
    $routes->get('teachingassignments/create', 'TeachingAssignments::create', $auth127);
    $routes->get('teachingassignments/get-existing', 'TeachingAssignments::getExistingAssignments', $auth127);
    $routes->post('teachingassignments/store', 'TeachingAssignments::store', $auth127);
    $routes->get('teachingassignments/edit/(:num)', 'TeachingAssignments::edit/$1', $auth127);
    $routes->post('teachingassignments/update/(:num)', 'TeachingAssignments::update/$1', $auth127);
    $routes->get('teachingassignments/delete/(:num)', 'TeachingAssignments::delete/$1', $auth127);
    $routes->post('teachingassignments/bulk-delete', 'TeachingAssignments::bulkDelete', $auth127);

    // 5.8 Administrasi Guru (Kurikulum Merdeka)
    $routes->group('administrasi-guru', $auth123, function ($routes) use ($auth123) {
        $routes->get('/', 'AdministrasiGuru::index');
        $routes->get('monitoring', 'AdministrasiGuru::monitoring');
        $routes->get('mapping', 'AdministrasiGuru::mapping', $auth123);
        $routes->post('mapping/update', 'AdministrasiGuru::updateMapping', $auth123);
        
        // CP Master CRUD
        $routes->get('cp-master', 'AdministrasiGuru::cpMasterIndex', $auth123);
        $routes->post('cp-master/store', 'AdministrasiGuru::cpMasterStore', $auth123);
        $routes->get('cp-master/delete/(:num)', 'AdministrasiGuru::cpMasterDelete/$1', $auth123);

        $routes->get('cp', 'AdministrasiGuru::cp');
        $routes->get('tp', 'AdministrasiGuru::tp');
        $routes->post('tp/store', 'AdministrasiGuru::tpStore');
        $routes->get('tp/delete/(:num)', 'AdministrasiGuru::tpDelete/$1');
        $routes->get('atp', 'AdministrasiGuru::atp');
        $routes->post('atp/store', 'AdministrasiGuru::atpStore');
        $routes->get('atp/delete/(:num)', 'AdministrasiGuru::atpDelete/$1');
        $routes->post('atp/copy-from-source', 'AdministrasiGuru::atpCopyFromSource');
        $routes->get('atp/print/(:num)/(:num)', 'AdministrasiGuru::atpPrint/$1/$2');
        $routes->get('prota-prosem', 'AdministrasiGuru::protaProsem');
        $routes->get('prota/print/(:num)/(:num)', 'AdministrasiGuru::protaPrint/$1/$2');
        $routes->get('prosem/input/(:num)/(:num)/(:num)', 'AdministrasiGuru::prosemInput/$1/$2/$3');
        $routes->get('prosem/print/(:num)/(:num)/(:num)', 'AdministrasiGuru::prosemPrint/$1/$2/$3');
        $routes->post('prosem/save', 'AdministrasiGuru::prosemSave');
        $routes->get('modul-ajar', 'AdministrasiGuru::modulAjar');
        $routes->post('modul-ajar/saveApiKey', 'AdministrasiGuru::saveApiKey');
        $routes->post('modul-ajar/generate', 'AdministrasiGuru::generateModulAjar');
        $routes->get('modul-ajar/edit/(:num)/(:num)', 'AdministrasiGuru::editModulAjar/$1/$2');
        $routes->post('modul-ajar/update', 'AdministrasiGuru::updateModulAjar');
        $routes->get('modul-ajar/print/(:num)/(:num)', 'AdministrasiGuru::printModulAjar/$1/$2');
        $routes->get('modul-ajar/delete/(:num)/(:num)/(:num)', 'AdministrasiGuru::deleteModulAjar/$1/$2/$3');
        $routes->post('modul-ajar/copy', 'AdministrasiGuru::copyModulAjar');
    });

    // 5.9 Kokurikuler (Kurikulum Merdeka) - Admin, Kepsek & Guru Kelas
    $routes->group('kokurikuler', $auth123, function ($routes) {
        // Perencanaan (Tahap 1)
        $routes->get('/', 'Kokurikuler::index');
        $routes->get('create', 'Kokurikuler::create');
        $routes->post('store', 'Kokurikuler::store');
        $routes->post('generate-ai/(:num)', 'Kokurikuler::generateAI/$1');
        $routes->get('view/(:num)', 'Kokurikuler::view/$1');
        $routes->get('export-pdf/(:num)', 'Kokurikuler::exportPDF/$1');
        $routes->get('delete/(:num)', 'Kokurikuler::delete/$1');
        $routes->get('get-atp/(:num)', 'Kokurikuler::getATPBySubject/$1');
        
        // Template & Activation
        $routes->get('use-template/(:num)', 'Kokurikuler::useTemplate/$1');
        $routes->get('activate-old-plan/(:num)', 'Kokurikuler::activateOldPlan/$1');
        $routes->get('get-available-templates', 'Kokurikuler::getAvailableTemplates');
        
        // Pelaksanaan (Tahap 2) - Coming Soon
        $routes->get('pelaksanaan', 'Kokurikuler::pelaksanaan');
        $routes->get('pelaksanaan/detail/(:num)', 'Kokurikuler::pelaksanaanDetail/$1');
        $routes->post('pelaksanaan/save', 'Kokurikuler::savePelaksanaan');
        $routes->get('pelaksanaan/file/(.+)', 'Kokurikuler::serveFile/$1');
        
        // Penilaian/Asesmen (Tahap 3) - Coming Soon
        $routes->get('penilaian', 'Kokurikuler::penilaian');
        $routes->get('penilaian/detail/(:num)', 'Kokurikuler::penilaianDetail/$1');
        $routes->get('penilaian/form/(:num)/(:num)', 'Kokurikuler::penilaianForm/$1/$2');
        $routes->post('penilaian/save', 'Kokurikuler::savePenilaian');
        
        // Batch Penilaian (NEW - for single-page batch assessment)
        $routes->get('penilaian/test-ajax', 'Kokurikuler::testAjax');
        $routes->get('penilaian/get-rubrik/(:num)', 'Kokurikuler::getRubrik/$1');
        $routes->get('penilaian/get-students-penilaian/(:num)/(:num)', 'Kokurikuler::getStudentsPenilaian/$1/$2');
        $routes->post('penilaian/save-batch', 'Kokurikuler::saveBatchPenilaian');
        $routes->get('penilaian/deskripsi/(:num)', 'Kokurikuler::penilaianDeskripsi/$1');
        $routes->get('penilaian/cetak/(:num)', 'Kokurikuler::penilaianCetak/$1');
        
        // Manual Regenerate Rubrik (Fix for missing sub_dimensi)
        $routes->get('regenerate-rubrik/(:num)', 'Kokurikuler::regenerateRubrik/$1');
        
        // Pelaporan (Tahap 4)
        $routes->get('pelaporan', 'Kokurikuler::pelaporan');
        $routes->post('pelaporan/save-laporan', 'Kokurikuler::saveLaporan');
        $routes->get('pelaporan/cetak', 'Kokurikuler::pelaporanCetak');
    });

    // 5.5 Integrasi Dapodik (Admin & Principal)
    $routes->get('dapodik', 'Dapodik::index', $auth127);
    $routes->post('dapodik/testConnection', 'Dapodik::testConnection', $auth127);
    $routes->get('dapodik/fetchStudents', 'Dapodik::fetchStudents', $auth127);
    $routes->get('dapodik/fetchTeachers', 'Dapodik::fetchTeachers', $auth127);
    $routes->post('dapodik/syncStudents', 'Dapodik::syncStudents', $auth127);
    $routes->post('dapodik/syncTeachers', 'Dapodik::syncTeachers', $auth127);

    // 5.6 Tata Usaha & Persuratan (Admin, Kepsek, Staf)
    $routes->get('tata-usaha/cetak-daftar-hadir', 'TataUsaha::cetakDaftarHadir', $auth127);
    $routes->post('tata-usaha/cetak-daftar-hadir/generate', 'TataUsaha::generatePDF', $auth127);

    // KOP Surat Settings
    $routes->get('settings/kop-surat', 'SettingsController::kopSurat', $auth127);
    $routes->post('settings/kop-surat/upload', 'SettingsController::uploadKopSurat', $auth127);

    // Surat Masuk
    $routes->get('surat-masuk', 'SuratMasuk::index', $auth127);
    $routes->get('surat-masuk/create', 'SuratMasuk::create', $auth127);
    $routes->post('surat-masuk/store', 'SuratMasuk::store', $auth127);
    $routes->get('surat-masuk/detail/(:num)', 'SuratMasuk::detail/$1', $auth127);
    $routes->get('surat-masuk/edit/(:num)', 'SuratMasuk::edit/$1', $auth127);
    $routes->post('surat-masuk/update/(:num)', 'SuratMasuk::update/$1', $auth127);
    $routes->get('surat-masuk/delete/(:num)', 'SuratMasuk::delete/$1', $auth127);
    $routes->get('surat-masuk/scan/(:num)', 'SuratMasuk::viewScan/$1', $auth127);

    // Surat Keluar
    $routes->get('surat-keluar', 'SuratKeluar::index', $auth127);
    $routes->get('surat-keluar/create', 'SuratKeluar::create', $auth127);
    $routes->post('surat-keluar/store', 'SuratKeluar::store', $auth127);
    $routes->get('surat-keluar/detail/(:num)', 'SuratKeluar::detail/$1', $auth127);
    $routes->get('surat-keluar/download-pdf/(:num)', 'SuratKeluar::downloadPdf/$1', $auth127);
    $routes->get('surat-keluar/download/(:num)', 'SuratKeluar::downloadPdf/$1', $auth127);
    $routes->post('surat-keluar/revoke/(:num)', 'SuratKeluar::revoke/$1', $auth127);
    $routes->get('surat-keluar/search-siswa', 'SuratKeluar::searchSiswa', $auth127);
    $routes->get('surat-keluar/search-guru', 'SuratKeluar::searchGuru', $auth127);
    $routes->get('surat-keluar/create-eksternal', 'SuratKeluar::createEksternal', $auth127);
    $routes->post('surat-keluar/store-eksternal', 'SuratKeluar::storeEksternal', $auth127);
    $routes->get('surat-keluar/view-pdf/(:num)', 'SuratKeluar::viewPdf/$1', $auth127);

    // Agenda Surat
    $routes->get('agenda-surat', 'AgendaSurat::index', $auth127);
    $routes->get('agenda-surat/export', 'AgendaSurat::exportExcel', $auth127);

    // Buku Tamu Admin
    $routes->get('buku-tamu', 'BukuTamuAdmin::index', $auth127);
    $routes->get('buku-tamu/export-pdf', 'BukuTamuAdmin::exportPdf', $auth127);
    $routes->get('buku-tamu/export-excel', 'BukuTamuAdmin::exportExcel', $auth127);
    $routes->get('buku-tamu/delete/(:num)', 'BukuTamuAdmin::delete/$1', $auth127);
    $routes->get('buku-tamu/print-qr', 'BukuTamuAdmin::printQr', $auth127);

    // 6. Akademik & CBT (Admin, Principal, Guru)
    $routes->get('attendance', 'Attendance::index', $auth123);
    $routes->get('attendance/view', 'Attendance::view', $auth123);
    $routes->post('attendance/save', 'Attendance::save', $auth123);
    $routes->get('attendance/pdf', 'Attendance::pdf', $auth123);
    $routes->get('attendance/excel', 'Attendance::excel', $auth123);
    $routes->get('attendance/rekap', 'Attendance::rekap', $auth123);
    $routes->get('attendance/rekapPdf', 'Attendance::rekapPdf', $auth123);
    $routes->get('attendance/rekapExcel', 'Attendance::rekapExcel', $auth123);
    $routes->get('attendance/week', 'Attendance::week', $auth123);
    $routes->get('attendance/report/semester', 'Attendance::reportSemester', $auth123);
    $routes->get('attendance/report/year', 'Attendance::reportYear', $auth123);

    $routes->get('agendas', 'Agendas::index', $auth123);
    $routes->get('agendas/(:num)/(:num)', 'Agendas::index/$1/$2', $auth123);
    $routes->get('agendas/date/(:segment)', 'Agendas::byDate/$1', $auth123);
    $routes->get('agendas/create', 'Agendas::create', $auth123);
    $routes->post('agendas/store', 'Agendas::store', $auth123);
    $routes->get('agendas/(:num)', 'Agendas::show/$1', $auth123);
    $routes->get('agendas/(:num)/edit', 'Agendas::edit/$1', $auth123);
    $routes->post('agendas/(:num)/update', 'Agendas::update/$1', $auth123);
    $routes->get('agendas/(:num)/delete', 'Agendas::delete/$1', $auth123);

    // Teaching Journal (Jurnal Mengajar) - Admin, Principal, Teacher
    $routes->get('teaching-journal', 'TeachingJournalController::index', $auth123);
    $routes->get('teaching-journal/add', 'TeachingJournalController::add', $auth123);
    $routes->get('teaching-journal/edit/(:num)', 'TeachingJournalController::edit/$1', $auth123);
    $routes->post('teaching-journal/store', 'TeachingJournalController::store', $auth123);
    $routes->get('teaching-journal/delete/(:num)', 'TeachingJournalController::delete/$1', $auth123);
    $routes->get('teaching-journal/get-atps', 'TeachingJournalController::getAtps', $auth123);

    // School Schedule (Jadwal Pelajaran)
    $routes->get('schedules', 'ScheduleController::index', $auth1237);
    $routes->get('schedules/manage/(:num)', 'ScheduleController::manage/$1', $auth1237);
    $routes->post('schedules/store', 'ScheduleController::store', ['filter' => 'auth:1,3,7']);
    $routes->post('schedules/store-bulk', 'ScheduleController::storeBulk', ['filter' => 'auth:1,3,7']);
    $routes->get('schedules/delete/(:num)', 'ScheduleController::delete/$1', ['filter' => 'auth:1,3,7']);

    $routes->get('announcements', 'Announcement::index', $auth123);
    $routes->get('announcements/create', 'Announcement::create', $auth123);
    $routes->post('announcements/store', 'Announcement::store', $auth123);
    $routes->get('announcements/edit/(:num)', 'Announcement::edit/$1', $auth123);
    $routes->post('announcements/update/(:num)', 'Announcement::update/$1', $auth123);
    $routes->get('announcements/delete/(:num)', 'Announcement::delete/$1', $auth123);

    $routes->get('grades', 'GradesManageController::index', $auth123);
    $routes->get('grades/rekap', 'GradesManageController::rekap', $auth123);
    $routes->get('grades/rekap/cetak', 'GradesManageController::rekapCetak', $auth123);
    $routes->get('grades/rekap/excel', 'GradesManageController::rekapExcel', $auth123);
    $routes->get('grades/select-teacher', 'GradesManageController::selectTeacher', $auth123);
    $routes->get('grades/show', 'GradesManageController::show', $auth123);
    $routes->get('grades/show/(:num)/(:num)', 'GradesManageController::show/$1/$2', $auth123);
    $routes->get('grades/show/(:num)/(:num)/(:num)', 'GradesManageController::show/$1/$2/$3', $auth123);
    $routes->get('grades/pdf/(:num)/(:num)', 'GradesManageController::pdf/$1/$2', $auth123);
    $routes->get('grades/select-class-subject', 'GradesManageController::selectClassSubject', $auth123);
    $routes->get('grades/select-subject/(:num)', 'GradesManageController::selectSubject/$1', $auth123);
    $routes->get('grades/teacher-info/(:num)', 'GradesManageController::getTeacherInfo/$1', $auth123);
    $routes->get('grades/(:num)/(:num)/(:num)/pdf', 'GradesManageController::pdf/$1/$2/$3', $auth123);
    $routes->get('grades/(:num)/(:num)/(:num)/excel', 'GradesManageController::excel/$1/$2/$3', $auth123);
    $routes->get('grades/tracking', 'GradesManageController::studentTracking', $auth123);
    $routes->get('grades/tracking/pdf', 'GradesManageController::studentTrackingPdf', $auth123);
    $routes->get('grades/tracking/excel', 'GradesManageController::studentTrackingExcel', $auth123);
    $routes->get('grades/search-student', 'GradesManageController::searchStudent', $auth123);

    $routes->get('assessments', 'Assessment::index', $auth123);
    $routes->get('assessments/input/(:num)/(:num)/(:alpha)', 'Assessment::input/$1/$2/$3', $auth123);
    $routes->post('assessments/store', 'Assessment::store', $auth123);
    $routes->get('assessments/formatifList/(:num)/(:num)', 'Assessment::formatifList/$1/$2', $auth123);
    $routes->get('assessments/sumatifList/(:num)/(:num)', 'Assessment::sumatifList/$1/$2', $auth123);
    $routes->get('assessments/finalList/(:num)/(:num)', 'Assessment::finalList/$1/$2', $auth123);
    $routes->get('assessments/viewScores/formatif/(:num)/(:alpha)', 'Assessment::viewScores/formatif/$1/$2', $auth123);
    $routes->get('assessments/viewScores/sumatif/(:num)/(:num)/(:alpha)', 'Assessment::viewScores/sumatif/$1/$2/$3', $auth123);
    $routes->get('assessments/viewScores/final/(:num)', 'Assessment::viewScores/final/$1', $auth123);
    $routes->get('assessments/edit/(:num)/(:segment)', 'Assessment::edit/$1/$2', $auth123);
    $routes->post('assessments/update/(:num)/(:segment)', 'Assessment::update/$1/$2', $auth123);
    $routes->get('assessments/deleteOne/(:num)/(:segment)', 'Assessment::deleteOne/$1/$2', $auth123);
    $routes->get('assessments/deleteBatch/formatif/(:num)/(:alpha)', 'Assessment::deleteBatch/formatif/$1/$2', $auth123);
    $routes->get('assessments/deleteBatch/sumatif/(:num)/(:num)/(:segment)', 'Assessment::deleteBatch/sumatif/$1/$2/$3', $auth123);
    $routes->get('assessments/deleteBatch/final/(:num)/(:num)', 'Assessment::deleteBatch/final/$1/$2', $auth123);

    // Nilai Erapor (prerogratif guru) — Admin, Kepsek, Guru
    $routes->get('erapor', 'Erapor::index', $auth123);
    $routes->get('erapor/subjects', 'Erapor::subjectList', $auth123);
    $routes->get('erapor/subjects/(:num)', 'Erapor::subjectList/$1', $auth123);
    $routes->get('erapor/input/(:num)/(:num)/(:num)', 'Erapor::input/$1/$2/$3', $auth123);
    $routes->post('erapor/save', 'Erapor::save', $auth123);

    $routes->get('materials/(:num)', 'Materials::index/$1', $auth123);
    $routes->get('materials/index/(:num)', 'Materials::index/$1', $auth123);
    $routes->get('materials/create/(:num)', 'Materials::create/$1', $auth123);
    $routes->post('materials/store', 'Materials::store', $auth123);
    $routes->get('materials/edit/(:num)', 'Materials::edit/$1', $auth123);
    $routes->post('materials/update/(:num)', 'Materials::update/$1', $auth123);
    $routes->get('materials/delete/(:num)', 'Materials::delete/$1', $auth123);

    $routes->get('student-notes', 'StudentNotes::index', $auth123);
    $routes->get('student-notes/show/(:num)', 'StudentNotes::show/$1', $auth123);
    $routes->get('student-notes/create/(:num)', 'StudentNotes::create/$1', $auth123);
    $routes->post('student-notes/store', 'StudentNotes::store', $auth123);
    $routes->get('student-notes/edit/(:num)', 'StudentNotes::edit/$1', $auth123);
    $routes->post('student-notes/update/(:num)', 'StudentNotes::update/$1', $auth123);
    $routes->get('student-notes/delete/(:num)', 'StudentNotes::delete/$1', $auth123);

    $routes->get('behaviors', 'Behaviors::index', $auth123);
    $routes->get('behaviors/create', 'Behaviors::create', $auth123);
    $routes->post('behaviors/store', 'Behaviors::store', $auth123);
    $routes->get('behaviors/edit/(:num)', 'Behaviors::edit/$1', $auth123);
    $routes->post('behaviors/update/(:num)', 'Behaviors::update/$1', $auth123);
    $routes->get('behaviors/delete/(:num)', 'Behaviors::delete/$1', $auth123);

    $routes->get('chat', 'Chat::index', $auth123);
    $routes->get('chat/room/(:num)', 'Chat::room/$1', $auth123);
    $routes->post('chat/send', 'Chat::send', $auth123);
    $routes->get('chat/fetch/(:num)', 'Chat::fetch/$1', $auth123);
    $routes->get('chat/mentions', 'Chat::mentions', $auth123);
    $routes->get('chat/clear-mentions/(:num)', 'Chat::clearMentions/$1', $auth123);

    // Staff Chat — grup chat internal (Admin, Kepsek, Guru, Staf)
    $routes->get('staff-chat', 'StaffChat::index', $auth1237);
    $routes->post('staff-chat/send', 'StaffChat::send', $auth1237);
    $routes->get('staff-chat/fetch/(:num)', 'StaffChat::fetch/$1', $auth1237);
    $routes->get('staff-chat/mentions', 'StaffChat::mentions', $auth1237);
    $routes->get('staff-chat/clear-mentions', 'StaffChat::clearMentions', $auth1237);

    $routes->get('cbt/banksoal', 'CbtBankSoal::index', $auth123);
    $routes->post('cbt/banksoal/storeAjax', 'CbtBankSoal::storeAjax', $auth123);
    $routes->post('cbt/banksoal/updateAjax', 'CbtBankSoal::updateAjax', $auth123);
    $routes->post('cbt/banksoal/addQuestionAjax', 'CbtBankSoal::addQuestionAjax', $auth123);
    $routes->post('cbt/banksoal/updateQuestionAjax', 'CbtBankSoal::updateQuestionAjax', $auth123);
    $routes->post('cbt/banksoal/deleteQuestionAjax', 'CbtBankSoal::deleteQuestionAjax', $auth123);
    $routes->get('cbt/banksoal/deleteAudio/(:num)/(:any)', 'CbtBankSoal::deleteAudio/$1/$2', $auth123);
    $routes->get('cbt/banksoal/backup/(:num)', 'CbtBankSoal::backup/$1', $auth123);
    $routes->post('cbt/banksoal/restore', 'CbtBankSoal::restore', $auth123);

    // CBT - Schedules & Attendance
    $routes->get('exam-schedule', 'ExamSchedule::index', $auth123);
    $routes->get('exam-schedule/create', 'ExamSchedule::create', $auth123);
    $routes->post('exam-schedule/store', 'ExamSchedule::store', $auth123);
    $routes->get('exam-schedule/edit/(:num)', 'ExamSchedule::edit/$1', $auth123);
    $routes->post('exam-schedule/update/(:num)', 'ExamSchedule::update/$1', $auth123);
    $routes->get('exam-schedule/delete/(:num)', 'ExamSchedule::delete/$1', $auth123);

    $routes->get('kartu-peserta', 'KartuPeserta::index', $auth123);
    $routes->get('cbt/kartu-peserta-lihat/(:num)', 'KartuPeserta::lihat/$1', $auth123);
    $routes->get('cbt/kartu-peserta/cetakMassal/(:num)/(:num)', 'KartuPeserta::cetakMassal/$1/$2', $auth123);
    $routes->get('cbt/kartu-peserta/pdf/(:any)/(:any)', 'KartuPeserta::cetakPdfMassal/$1/$2', $auth123);

    $routes->get('cbt/attendance', 'ExamAttendance::index', $auth123);
    $routes->get('cbt/attendance/printByClass/(:num)/(:num)', 'ExamAttendance::printByClass/$1/$2', $auth123);
    $routes->get('cbt/attendance/printPdf/(:num)/(:num)', 'ExamAttendance::printPdf/$1/$2', $auth123);
    $routes->get('cbt/banksoal/create', 'CbtBankSoal::create', $auth123);
    $routes->post('cbt/banksoal/create', 'CbtBankSoal::create', $auth123);
    $routes->get('cbt/banksoal/copy/(:num)', 'CbtBankSoal::copy/$1', $auth123);
    $routes->get('cbt/banksoal/detail/(:num)', 'CbtBankSoal::detail/$1', $auth123);
    $routes->get('cbt/banksoal/print/(:num)', 'CbtBankSoal::print/$1', $auth123);
    $routes->get('cbt/banksoal/delete/(:num)', 'CbtBankSoal::delete/$1', $auth123);
    $routes->get('cbt/banksoal/toggle/(:num)', 'CbtBankSoal::toggle/$1', $auth123);
    $routes->get('cbt/banksoal/tambah_soal/(:num)', 'CbtBankSoal::tambahSoal/$1', $auth123);
    $routes->post('cbt/banksoal/parseSoal', 'CbtBankSoal::parseSoal', $auth123);
    $routes->post('cbt/banksoal/saveParsedSoal/(:num)', 'CbtBankSoal::saveParsedSoal/$1', $auth123);
    $routes->post('cbt/banksoal/previewParsedSoal', 'CbtBankSoal::previewParsedSoal', $auth123);
    $routes->post('cbt/banksoal/uploadImage', 'CbtBankSoal::uploadImage', $auth123);
    $routes->post('cbt/banksoal/update_soal/(:num)/(:num)', 'CbtBankSoal::updateSoal/$1/$2', $auth123);
    $routes->get('cbt/banksoal/edit_soal/(:num)/(:num)', 'CbtBankSoal::edit_soal/$1/$2', $auth123);
    $routes->post('cbt/banksoal/bulkDelete', 'CbtBankSoal::bulkDelete', $auth123);

    $routes->get('cbt/examname', 'CbtExamName::index', $auth123);
    $routes->post('cbt/examname/store', 'CbtExamName::store', $auth123);
    $routes->post('cbt/examname/update/(:num)', 'CbtExamName::update/$1', $auth123);
    $routes->get('cbt/examname/delete/(:num)', 'CbtExamName::delete/$1', $auth123);

    $routes->get('cbt/teststatus', 'CbtTestStatus::index', $auth123);
    $routes->get('cbt/teststatus/create', 'CbtTestStatus::create', $auth123);
    $routes->post('cbt/teststatus/store', 'CbtTestStatus::store', $auth123);
    $routes->get('cbt/teststatus/edit/(:num)', 'CbtTestStatus::edit/$1', $auth123);
    $routes->post('cbt/teststatus/update/(:num)', 'CbtTestStatus::update/$1', $auth123);
    $routes->get('cbt/teststatus/delete/(:num)', 'CbtTestStatus::delete/$1', $auth123);
    $routes->get('cbt/teststatus/togglePause/(:num)', 'CbtTestStatus::togglePause/$1', $auth123);
    $routes->get('cbt/teststatus/toggleVisible/(:num)', 'CbtTestStatus::toggleVisible/$1', $auth123);
    $routes->get('cbt/teststatus/detail/(:num)', 'CbtTestStatus::detail/$1', $auth123);

    $routes->get('cbt/aktivitas', 'CbtAktivitas::aktivitasIndex', $auth123);
    $routes->get('cbt/aktivitas/detail/(:num)', 'CbtAktivitas::detail/$1', $auth123);
    $routes->get('cbt/aktivitas/detail_jawaban/(:num)', 'CbtAktivitas::detail_jawaban/$1', $auth123);
    $routes->post('cbt/aktivitas/forceFinish/(:num)', 'CbtAktivitas::forceFinish/$1', $auth123);
    $routes->post('cbt/aktivitas/addTime/(:num)', 'CbtAktivitas::addTime/$1', $auth123);
    $routes->post('cbt/aktivitas/resetSession/(:num)', 'CbtAktivitas::resetSession/$1', $auth123);
    $routes->get('cbt/aktivitas/belumTes/(:num)', 'CbtAktivitas::belumTes/$1', $auth123);
    $routes->get('cbt/aktivitas/unduhNilai/(:num)', 'CbtAktivitas::unduhNilai/$1', $auth123);
    $routes->get('cbt/aktivitas/analisis/(:num)', 'CbtAktivitas::analisisSoal/$1', $auth123);
    $routes->get('cbt/aktivitas/analisis/download/(:num)', 'CbtAktivitas::analisisDownload/$1', $auth123);
    $routes->get('cbt/aktivitas/analisisjawaban/download/(:num)', 'CbtAktivitas::downloadAnalisis/$1', $auth123);
    $routes->get('cbt/aktivitas/laporan/(:num)', 'CbtAktivitas::laporanJawaban/$1', $auth123);
    $routes->get('cbt/aktivitas/laporan/pdf/(:num)/(:num)', 'CbtAktivitas::laporanJawabanPdf/$1', $auth123);
    $routes->post('cbt/aktivitas/laporan/nilaiEsai', 'CbtAktivitas::simpanNilaiEsai', $auth123);
    $routes->get('cbt/aktivitas/getSoalEsai/(:num)/(:num)', 'CbtAktivitas::getSoalEsai/$1/$2', $auth123);
    $routes->post('cbt/aktivitas/simpanNilaiEsaiDetail', 'CbtAktivitas::simpanNilaiEsaiDetail', $auth123);
    $routes->get('cbt/aktivitas/laporan_pdf/(:num)/(:num)', 'CbtAktivitas::laporanJawabPdf/$1/$2', $auth123);

    $routes->get('cbt/convertnilai', 'CbtConvertNilai::index', $auth123);
    $routes->post('cbt/convertnilai/preview', 'CbtConvertNilai::preview', $auth123);
    $routes->post('cbt/convertnilai/save', 'CbtConvertNilai::save', $auth123);

    // 7. CMS (Admin & Kontributor & Principal)
    $routes->group('cms', $auth126, function ($routes) {
        $routes->get('sliders', 'Cms\Sliders::index');
        $routes->get('sliders/create', 'Cms\Sliders::create');
        $routes->post('sliders/store', 'Cms\Sliders::store');
        $routes->get('sliders/edit/(:num)', 'Cms\Sliders::edit/$1');
        $routes->post('sliders/update/(:num)', 'Cms\Sliders::update/$1');
        $routes->get('sliders/delete/(:num)', 'Cms\Sliders::delete/$1');

        $routes->get('links', 'Cms\Links::index');
        $routes->get('links/create', 'Cms\Links::create');
        $routes->post('links/store', 'Cms\Links::store');
        $routes->get('links/edit/(:num)', 'Cms\Links::edit/$1');
        $routes->post('links/update/(:num)', 'Cms\Links::update/$1');
        $routes->delete('links/delete/(:num)', 'Cms\Links::delete/$1');

        $routes->get('facilities', 'Cms\Facilities::index');
        $routes->get('facilities/create', 'Cms\Facilities::create');
        $routes->post('facilities/store', 'Cms\Facilities::store');
        $routes->get('facilities/edit/(:num)', 'Cms\Facilities::edit/$1');
        $routes->post('facilities/update/(:num)', 'Cms\Facilities::update/$1');
        $routes->get('facilities/delete/(:num)', 'Cms\Facilities::delete/$1');

        $routes->get('articles', 'Cms\Articles::index');
        $routes->get('articles/create', 'Cms\Articles::create');
        $routes->post('articles/store', 'Cms\Articles::store');
        $routes->get('articles/edit/(:num)', 'Cms\Articles::edit/$1');
        $routes->post('articles/update/(:num)', 'Cms\Articles::update/$1');
        $routes->get('articles/delete/(:num)', 'Cms\Articles::delete/$1');

        $routes->get('activities', 'Cms\Activities::index');
        $routes->get('activities/create', 'Cms\Activities::create');
        $routes->post('activities/store', 'Cms\Activities::store');
        $routes->get('activities/edit/(:num)', 'Cms\Activities::edit/$1');
        $routes->post('activities/update/(:num)', 'Cms\Activities::update/$1');
        $routes->get('activities/delete/(:num)', 'Cms\Activities::delete/$1');

    });

    $routes->get('changelogs', 'Changelogs::index');
    $routes->get('changelogs/create', 'Changelogs::create');
    $routes->get('changelogs/edit/(:num)', 'Changelogs::edit/$1');
    $routes->post('changelogs/save', 'Changelogs::save');
    $routes->get('changelogs/delete/(:num)', 'Changelogs::delete/$1');

    // ── Absensi Guru ──────────────────────────────────────────────────────────
    $routes->get('teacher-attendance', 'TeacherAttendance::index', ['filter' => 'auth:1,7']);
    $routes->post('teacher-attendance/save', 'TeacherAttendance::save', ['filter' => 'auth:1,7']);
    $routes->post('teacher-attendance/submit-session', 'TeacherAttendance::submitSession', ['filter' => 'auth:1,7']);
    $routes->get('teacher-attendance/report', 'TeacherAttendance::report', ['filter' => 'auth:1,2,7']);
    $routes->get('teacher-attendance/report/detail/(:num)', 'TeacherAttendance::reportDetail/$1', ['filter' => 'auth:1,2,7']);
    $routes->get('teacher-attendance/export-excel', 'TeacherAttendance::exportExcel', ['filter' => 'auth:1,2,7']);
    $routes->get('teacher-attendance/export-pdf', 'TeacherAttendance::exportPdf', ['filter' => 'auth:1,2,7']);
    $routes->get('teacher-attendance/my', 'TeacherAttendance::myAttendance', ['filter' => 'auth:3']);

});

// ==================== SISWA & ORANG TUA ROUTES ====================
$routes->group('siswa', ['namespace' => 'App\Controllers\Siswa', 'filter' => 'auth:4,5'], function ($routes) {
    $routes->get('grades/(:num)', 'Grades::index/$1');
    $routes->get('grades', 'Grades::index');
    $routes->get('grades/pdf/(:num)', 'Grades::pdf/$1');
    $routes->get('attendance', 'AttendanceController::index');
    $routes->get('agendas', 'Agendas::index');
    $routes->get('agendas/(:num)/(:num)', 'Agendas::index/$1/$2');
    $routes->get('agendas/date/(:segment)', 'Agendas::byDate/$1');
    $routes->get('agendas/(:num)', 'Agendas::show/$1');
    $routes->get('student-notes', 'StudentNotes::index');
    $routes->get('announcement', 'Announcement::index');

    // Student Location (Siswa only - role_id 5)
    $routes->get('location', 'Location::index', ['filter' => 'auth:5']);
    $routes->post('location/update', 'Location::update', ['filter' => 'auth:5']);
    $routes->get('announcement/show/(:num)', 'Announcement::show/$1');
    $routes->get('chat', 'Chat::index');
    $routes->get('chat/room/(:num)', 'Chat::room/$1');
    $routes->post('chat/send', 'Chat::send');
    $routes->get('chat/fetch/(:num)', 'Chat::fetch/$1');
    $routes->get('chat/clear-mentions/(:num)', 'Chat::clearMentions/$1');

    $routes->get('profile', 'Profile::index');
    $routes->post('profile/update', 'Profile::update');

    $routes->get('cbt', 'Cbt::index');
    $routes->post('cbt/verifyToken/(:num)', 'Cbt::verifyToken/$1');
    $routes->post('cbt/ping', 'Cbt::ping');
    $routes->get('cbt/getScore/(:num)', 'Cbt::getScore/$1');
    $routes->get('cbt/peraturan/(:num)', 'Cbt::peraturan/$1');
    $routes->get('cbt/mulai/(:num)', 'Cbt::mulai/$1');
    $routes->post('cbt/saveAnswer', 'Cbt::saveAnswer');
    $routes->post('cbt/submit/(:num)', 'Cbt::submit/$1');
    $routes->get('cbt/selesai/(:num)', 'Cbt::selesai/$1');
    $routes->get('cbt/hasil/(:num)', 'Cbt::hasil/$1');
    $routes->post('cbt/saveAnswersBulk', 'Cbt::saveAnswersBulk');
    $routes->get('cbt/saveAnswersBulk', 'Cbt::saveAnswersBulk');
});

// ==================== OTHER ROUTES ====================
$routes->group('profile', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Profile::index');
    $routes->get('change-password', 'Profile::changePassword');
    $routes->post('update-password', 'Profile::updatePassword');
    $routes->post('update-teacher', 'Profile::updateTeacher');
    $routes->post('add-education', 'Profile::addEducation');
    $routes->post('add-training', 'Profile::addTraining');
    $routes->get('delete-sub/(:segment)/(:num)', 'Profile::deleteSub/$1/$2');
    $routes->post('upload-document', 'Profile::uploadDocument');
    $routes->get('download-document/(:num)', 'Profile::downloadDocument/$1');
    $routes->get('delete-document/(:num)', 'Profile::deleteDocument/$1');
    $routes->get('documents', 'Profile::documents');
    $routes->get('preview-document/(:num)', 'Profile::previewDocument/$1');
});


// ==================== API ROUTES ====================
// API untuk Bridge Sync dari Dapodik Lokal ke Online
$routes->group('api', ['namespace' => 'App\Controllers\Api'], function ($routes) {
    // Dapodik Sync API - Receive data from bridge agent
    $routes->post('dapodik/receive', 'DapodikApi::receive');
});
