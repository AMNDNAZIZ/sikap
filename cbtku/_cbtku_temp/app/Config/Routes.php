<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Auth::login');
$routes->get('/login', 'Auth::login');
$routes->post('/auth/attemptLogin', 'Auth::attemptLogin');
$routes->get('/logout', 'Auth::logout');
$routes->get('/dashboard', 'Dashboard::index');

// ==================== FIX LICENSE TOOL (TEMPORARY) ====================
// HAPUS SETELAH SELESAI DIGUNAKAN!
$routes->get('fixlicense', 'FixLicense::index');
$routes->get('fixlicense/delete', 'FixLicense::delete');

// ==================== LICENSE ACTIVATION ====================
$routes->get('activate', 'Activate::index');
$routes->post('activate/process', 'Activate::process');
$routes->post('activate/checkRenewal', 'Activate::checkRenewal');

// ==================== ADMIN ROUTES ====================
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'auth:1'], function ($routes) {

    // User
    $routes->get('users', 'User::index');
    $routes->get('users/create', 'User::create');
    $routes->post('users/store', 'User::store');
    $routes->get('users/edit/(:num)', 'User::edit/$1');
    $routes->post('users/update/(:num)', 'User::update/$1');
    $routes->get('users/delete/(:num)', 'User::delete/$1');
    $routes->get('users/reset-password/(:num)', 'User::resetPassword/$1');

    // Identitas sekolah
    $routes->get('school', 'School::index');
    $routes->post('school/update', 'School::update');

    // Tahun ajaran
    $routes->get('academic-year', 'AcademicYear::index');
    $routes->get('academic-year/create', 'AcademicYear::create');
    $routes->post('academic-year/store', 'AcademicYear::store');
    $routes->get('academic-year/set-active/(:num)', 'AcademicYear::setActive/$1');
    $routes->get('academic-year/edit/(:num)', 'AcademicYear::edit/$1');
    $routes->post('academic-year/update/(:num)', 'AcademicYear::update/$1');

    // Kelas (selain index khusus admin)
    $routes->get('classes', 'Classes::index');
    $routes->get('classes/create', 'Classes::create');
    $routes->post('classes/store', 'Classes::store');
    $routes->get('classes/edit/(:num)', 'Classes::edit/$1');
    $routes->post('classes/update/(:num)', 'Classes::update/$1');
    $routes->get('classes/delete/(:num)', 'Classes::delete/$1');

    // Guru
    $routes->get('teachers', 'Teachers::index');
    $routes->get('teachers/create', 'Teachers::create');
    $routes->post('teachers/store', 'Teachers::store');
    $routes->get('teachers/edit/(:num)', 'Teachers::edit/$1');
    $routes->post('teachers/update/(:num)', 'Teachers::update/$1');
    $routes->post('teachers/delete/(:num)', 'Teachers::delete/$1');
    $routes->post('teachers/import', 'Teachers::import');
    $routes->get('teachers/template', 'Teachers::downloadTemplate');

    // Siswa
    $routes->get('students', 'Students::index');
    $routes->get('students/create', 'Students::create');
    $routes->post('students/store', 'Students::store');
    $routes->get('students/edit/(:num)', 'Students::edit/$1');
    $routes->post('students/update/(:num)', 'Students::update/$1');
    $routes->get('students/delete/(:num)', 'Students::delete/$1');
    $routes->post('students/delete/(:num)', 'Students::delete/$1');
    $routes->post('students/updateStatus/(:num)', 'Students::updateStatus/$1');
    $routes->get('students/import', 'Students::import');
    $routes->post('students/import', 'Students::import');
    $routes->get('students/template', 'Students::template');
    $routes->post('students/doImport', 'Students::doImport');

    // Mapel (selain index khusus admin)
    $routes->get('subjects', 'Subjects::index');
    $routes->get('subjects/create', 'Subjects::create');
    $routes->post('subjects/store', 'Subjects::store');
    $routes->get('subjects/edit/(:num)', 'Subjects::edit/$1');
    $routes->post('subjects/update/(:num)', 'Subjects::update/$1');
    $routes->post('subjects/delete/(:num)', 'Subjects::delete/$1');

    $routes->get('exam-schedule', 'ExamSchedule::index');
    $routes->get('exam-schedule/create', 'ExamSchedule::create');
    $routes->post('exam-schedule/store', 'ExamSchedule::store');
    $routes->get('exam-schedule/edit/(:num)', 'ExamSchedule::edit/$1');
    $routes->post('exam-schedule/update/(:num)', 'ExamSchedule::update/$1');
    $routes->get('exam-schedule/delete/(:num)', 'ExamSchedule::delete/$1');

    // License Management
    $routes->get('license', 'License::index');
    $routes->post('license/checkRenewal', 'License::checkRenewal');
    $routes->post('license/deactivate', 'License::deactivate');

    $routes->get('kartu-peserta', 'KartuPeserta::index');
    $routes->get('cbt/kartu-peserta-lihat/(:num)', 'KartuPeserta::lihat/$1');
    $routes->get('cbt/kartu-peserta/cetakMassal/(:num)/(:num)', 'KartuPeserta::cetakMassal/$1/$2');
    $routes->get('cbt/kartu-peserta/pdf/(:any)/(:any)', 'KartuPeserta::cetakPdfMassal/$1/$2');
    $routes->get('cbt/attendance', 'ExamAttendance::index');
    $routes->get('cbt/attendance/printByRoom/(:num)/(:any)', 'ExamAttendance::printByRoom/$1/$2');
    $routes->get('cbt/attendance/printPdf/(:any)/(:any)', 'ExamAttendance::printPdf/$1/$2');

    // Updater
    $routes->get('updater', 'Updater::index');
    $routes->post('updater/patch-files', 'Updater::patchFiles');
    $routes->get('updater/run-migrations', 'Updater::runMigrations');
    $routes->get('updater/generate-patch', 'Updater::generatePatch');
    $routes->get('updater/generate-manifest', 'Updater::generateManifest');
    $routes->get('updater/check-online', 'Updater::checkOnlineUpdate');
    $routes->post('updater/apply-online', 'Updater::applyOnlineUpdate');
    $routes->get('updater/backup-database', 'Updater::backupDatabase');
    $routes->post('updater/restore-database', 'Updater::restoreDatabase');

    // Data Cleanup Routes
    $routes->get('data-cleanup', 'DataCleanup::index');
    $routes->post('data-cleanup/clean-students', 'DataCleanup::cleanStudents');
    $routes->post('data-cleanup/clean-test-status', 'DataCleanup::cleanTestStatus');
    $routes->post('data-cleanup/clean-all', 'DataCleanup::cleanAll');
    $routes->get('data-cleanup/get-stats', 'DataCleanup::getStats');

    // Session & Cache Settings Routes
    $routes->get('settings/session', 'SessionSettings::index');
    $routes->post('settings/session/update', 'SessionSettings::update');
    $routes->post('settings/cache/flush', 'SessionSettings::flushCache');
});

// ==================== ROUTES FOR ALL LOGGED USERS ====================
$routes->group('admin', ['namespace' => 'App\Controllers\Admin', 'filter' => 'auth'], function ($routes) {


    $routes->get('cbt/banksoal', 'CbtBankSoal::index');
    $routes->get('cbt/banksoal/create', 'CbtBankSoal::create');
    $routes->post('cbt/banksoal/create', 'CbtBankSoal::create');
    $routes->get('cbt/banksoal/copy/(:num)', 'CbtBankSoal::copy/$1');
    $routes->get('cbt/banksoal/detail/(:num)', 'CbtBankSoal::detail/$1');
    $routes->get('cbt/banksoal/print/(:num)', 'CbtBankSoal::print/$1');
    $routes->get('cbt/banksoal/delete/(:num)', 'CbtBankSoal::delete/$1');
    $routes->get('cbt/banksoal/edit_soal/(:num)/(:num)', 'CbtBankSoal::edit_soal/$1/$2');
    $routes->post('cbt/banksoal/bulkDelete', 'CbtBankSoal::bulkDelete');
    $routes->get('cbt/banksoal/backup/(:num)', 'CbtBankSoal::backup/$1');
    $routes->post('cbt/banksoal/restore', 'CbtBankSoal::restore');
    $routes->get('cbt/banksoal/toggle/(:num)', 'CbtBankSoal::toggle/$1');
    $routes->post('cbt/banksoal/(:num)/question/add', 'CbtBankSoal::addQuestion/$1');
    $routes->post('cbt/banksoal/(:num)/question/edit/(:num)', 'CbtBankSoal::editQuestion/$1/$2');
    $routes->get('cbt/banksoal/(:num)/question/delete/(:num)', 'CbtBankSoal::deleteQuestion/$1/$2');
    $routes->post('cbt/banksoal/storeAjax', 'CbtBankSoal::storeAjax');
    $routes->post('cbt/banksoal/deleteQuestionAjax', 'CbtBankSoal::deleteQuestionAjax');
    $routes->post('cbt/banksoal/updateAjax', 'CbtBankSoal::updateAjax');
    $routes->get('cbt/banksoal/tambah_soal/(:num)', 'CbtBankSoal::tambahSoal/$1');
    $routes->post('cbt/banksoal/parseSoal', 'CbtBankSoal::parseSoal');
    $routes->post('cbt/banksoal/saveParsedSoal/(:num)', 'CbtBankSoal::saveParsedSoal/$1');
    $routes->get('cbt/csrf/refresh', 'CbtBankSoal::csrfRefresh');
    $routes->get('cbt/heartbeat', 'CbtHeartbeat::index');
    $routes->post('cbt/banksoal/uploadImage', 'CbtBankSoal::uploadImage');
    $routes->post('cbt/banksoal/update_soal/(:num)/(:num)', 'CbtBankSoal::updateSoal/$1/$2');

    $routes->get('cbt/examname', 'CbtExamName::index');
    $routes->post('cbt/examname/store', 'CbtExamName::store');
    $routes->post('cbt/examname/update/(:num)', 'CbtExamName::update/$1');
    $routes->get('cbt/examname/delete/(:num)', 'CbtExamName::delete/$1');

    $routes->get('cbt/teststatus', 'CbtTestStatus::index');
    $routes->get('cbt/teststatus/create', 'CbtTestStatus::create');
    $routes->post('cbt/teststatus/store', 'CbtTestStatus::store');
    $routes->get('cbt/teststatus/edit/(:num)', 'CbtTestStatus::edit/$1');
    $routes->post('cbt/teststatus/update/(:num)', 'CbtTestStatus::update/$1');
    $routes->get('cbt/teststatus/delete/(:num)', 'CbtTestStatus::delete/$1');
    $routes->get('cbt/teststatus/togglePause/(:num)', 'CbtTestStatus::togglePause/$1');
    $routes->get('cbt/teststatus/toggleVisible/(:num)', 'CbtTestStatus::toggleVisible/$1');
    $routes->get('cbt/teststatus/detail/(:num)', 'CbtTestStatus::detail/$1');
    $routes->get('cbt/teststatus/getFilteredBanks', 'CbtTestStatus::getFilteredBanks');

    $routes->get('cbt/analisis/(:num)', 'CbtAnalisis::index/$1'); // halaman analisis per soal (nanti)
    $routes->get('cbt/analisis/download/(:num)', 'CbtAnalisis::download/$1'); // unduh analisis
    $routes->get('cbt/laporan/(:num)', 'CbtLaporan::index/$1');

    $routes->get('cbt/aktivitas', 'CbtAktivitas::aktivitasIndex'); // halaman utama daftar aktivitas
    $routes->get('cbt/aktivitas/detail/(:num)', 'CbtAktivitas::detail/$1');
    $routes->get('cbt/aktivitas/detail_jawaban/(:num)', 'CbtAktivitas::detail_jawaban/$1');
    $routes->post('cbt/aktivitas/forceFinish/(:num)', 'CbtAktivitas::forceFinish/$1');
    $routes->post('cbt/aktivitas/addTime/(:num)', 'CbtAktivitas::addTime/$1');
    $routes->post('cbt/aktivitas/resetSession/(:num)', 'CbtAktivitas::resetSession/$1');
    $routes->post('cbt/aktivitas/resetLogin/(:num)', 'CbtAktivitas::resetLogin/$1');
    $routes->get('cbt/aktivitas/belumTes/(:num)', 'CbtAktivitas::belumTes/$1');
    $routes->get('cbt/aktivitas/unduhNilai/(:num)', 'CbtAktivitas::unduhNilai/$1');
    $routes->post('cbt/aktivitas/resetMassal/(:num)', 'CbtAktivitas::resetMassal/$1');
    $routes->post('cbt/aktivitas/forceFinishMassal/(:num)', 'CbtAktivitas::forceFinishMassal/$1');
    $routes->get('cbt/aktivitas/analisis/(:num)', 'CbtAktivitas::analisisSoal/$1');
    $routes->get('cbt/aktivitas/analisis/download/(:num)', 'CbtAktivitas::analisisDownload/$1');
    $routes->get('cbt/aktivitas/analisisjawaban/download/(:num)', 'CbtAktivitas::downloadAnalisis/$1');
    $routes->get('cbt/aktivitas/laporan/(:num)', 'CbtAktivitas::laporanJawaban/$1');
    $routes->get('cbt/aktivitas/laporan/pdf/(:num)/(:num)', 'CbtAktivitas::laporanJawabanPdf/$1/$2');
    $routes->post('cbt/aktivitas/laporan/nilaiEsai', 'CbtAktivitas::simpanNilaiEsai');
    $routes->get('cbt/aktivitas/getSoalEsai/(:num)/(:num)', 'CbtAktivitas::getSoalEsai/$1/$2');
    $routes->post('cbt/aktivitas/simpanNilaiEsaiDetail', 'CbtAktivitas::simpanNilaiEsaiDetail');
    $routes->get('cbt/aktivitas/laporan_pdf/(:num)/(:num)', 'CbtAktivitas::laporanJawabPdf/$1/$2');

});



$routes->group('siswa', ['namespace' => 'App\Controllers\Siswa', 'filter' => 'auth:3'], function ($routes) {
    $routes->get('grades/(:num)', 'Grades::index/$1');
    $routes->get('grades', 'Grades::index'); // default semester 1
    $routes->get('grades/pdf/(:num)', 'Grades::pdf/$1');
    $routes->get('attendance', 'AttendanceController::index');
    $routes->get('agendas', 'Agendas::index');
    $routes->get('agendas/(:num)/(:num)', 'Agendas::index/$1/$2');
    $routes->get('agendas/date/(:segment)', 'Agendas::byDate/$1');
    $routes->get('agendas/(:num)', 'Agendas::show/$1');
    $routes->get('student-notes', 'StudentNotes::index');
    $routes->get('announcement', 'Announcement::index');
    $routes->get('announcement/show/(:num)', 'Announcement::show/$1');
    $routes->get('chat', 'Chat::index');               // bisa diarahkan langsung ke kelas siswa
    $routes->get('chat/room/(:num)', 'Chat::room/$1'); // buka room
    $routes->post('chat/send', 'Chat::send');          // kirim pesan
    $routes->get('chat/fetch/(:num)', 'Chat::fetch/$1'); // ambil isi pesan

    $routes->get('cbt', 'Cbt::index');
    $routes->post('cbt/verifyToken/(:num)', 'Cbt::verifyToken/$1');
    $routes->post('cbt/ping', 'Cbt::ping');
    $routes->post('cbt/getTimerSync', 'Cbt::getTimerSync');
    $routes->get('cbt/getScore/(:num)', 'Cbt::getScore/$1');
    $routes->get('cbt/peraturan/(:num)', 'Cbt::peraturan/$1');
    $routes->get('cbt/mulai/(:num)', 'Cbt::mulai/$1');
    $routes->post('cbt/saveAnswer', 'Cbt::saveAnswer');
    $routes->post('cbt/submit/(:num)', 'Cbt::submit/$1');
    $routes->get('cbt/selesai/(:num)', 'Cbt::selesai/$1');
    $routes->get('cbt/hasil/(:num)', 'Cbt::hasil/$1');
    $routes->get('cbt/heartbeat', 'CbtHeartbeat::index');
});

$routes->group('profile', ['filter' => 'auth'], function ($routes) {
    $routes->get('/', 'Profile::index');
    $routes->post('update-password', 'Profile::updatePassword');
});

