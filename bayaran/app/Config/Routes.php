<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

$routes->get('/', 'Publik::index');
$routes->get('cari', 'Publik::cari');
$routes->get('/login', 'Auth::login');
$routes->get('/admin', 'Auth::login');
$routes->post('/login', 'Auth::doLogin');
$routes->get('/logout', 'Auth::logout');

$routes->group('admin', ['filter' => 'auth'], function ($routes) {
	$routes->get('dashboard', 'Dashboard::index');
	$routes->get('pos-bayar', 'PosBayar::index');

	$routes->get('user', 'User::index');
	$routes->post('user/save', 'User::save');
	$routes->get('user/edit/(:num)', 'User::edit/$1');
	$routes->post('user/update/(:num)', 'User::update/$1');
	$routes->get('user/delete/(:num)', 'User::delete/$1');

	$routes->post('pos-bayar/save', 'PosBayar::save');
	$routes->get('pos-bayar/delete/(:num)', 'PosBayar::delete/$1');
	$routes->get('pos-bayar/edit/(:num)', 'PosBayar::edit/$1');

	$routes->get('jenis-bayar', 'JenisBayar::index');
	$routes->post('jenis-bayar/save', 'JenisBayar::save');
	$routes->get('jenis-bayar/delete/(:num)', 'JenisBayar::delete/$1');

	$routes->get('tagihan/seting/(:num)', 'Tagihan::setingTarif/$1');
	$routes->post('tagihan/seting/(:num)', 'Tagihan::generateWithTarif/$1');

	$routes->get('tahun-ajaran', 'TahunAjaran::index');
	$routes->post('tahun-ajaran/save', 'TahunAjaran::save');
	$routes->get('tahun-ajaran/edit/(:num)', 'TahunAjaran::edit/$1');
	$routes->post('tahun-ajaran/update/(:num)', 'TahunAjaran::update/$1');
	$routes->get('tahun-ajaran/set-aktif/(:num)', 'TahunAjaran::setAktif/$1');
	$routes->get('tahun-ajaran/delete/(:num)', 'TahunAjaran::delete/$1');

	$routes->get('kelas', 'Kelas::index');
	$routes->post('kelas/save', 'Kelas::save');
	$routes->get('kelas/delete/(:num)', 'Kelas::delete/$1');

	$routes->get('siswa', 'Siswa::index');
	$routes->post('siswa/save', 'Siswa::save');
	$routes->get('siswa/delete/(:num)', 'Siswa::delete/$1');
	$routes->get('siswa/edit/(:num)', 'Siswa::edit/$1');
	$routes->get('siswa/import', 'Siswa::importForm');
	$routes->post('siswa/import', 'Siswa::import');

	$routes->get('penempatan', 'Penempatan::index');
	$routes->post('penempatan/save', 'Penempatan::save');
	$routes->get('penempatan/delete/(:num)', 'Penempatan::delete/$1');

	$routes->get('kenaikan', 'Kenaikan::index');
	$routes->post('kenaikan/proses', 'Kenaikan::proses');
	$routes->get('kenaikan/perbaiki', 'Kenaikan::perbaiki');
	$routes->post('kenaikan/perbaiki-simpan', 'Kenaikan::perbaikiSimpan');

	$routes->get('kelas-aktif', 'KelasAktif::index');
	$routes->post('kelas-aktif/pindah', 'KelasAktif::pindah');
	$routes->get('kelas-aktif/delete/(:num)', 'KelasAktif::delete/$1');

	$routes->get('arsip-siswa', 'ArsipSiswa::index');
	$routes->get('arsip-siswa/aktifkan/(:num)', 'ArsipSiswa::aktifkan/$1');


	//$routes->get('tagihan/generate', 'Tagihan::generate');
	//$routes->post('tagihan/generate-form/(:num)/(:num)', 'Tagihan::generatePerSiswa/$1/$2');
	//$routes->post('tagihan/generate', 'Tagihan::generate');
	//$routes->post('tagihan/update', 'Tagihan::updateTagihan');
	//$routes->get('tagihan/delete/(:num)/(:num)', 'Tagihan::deleteBySiswa/$1/$2'); // jenis_bayar_id / siswa_id
	//$routes->post('tagihan/generate-siswa/(:num)/(:num)', 'Tagihan::generatePerSiswa/$1/$2');
	//$routes->post('tagihan/seting', 'Tagihan::handleGenerateRedirect');

	$routes->get('tagihan/seting/(:num)', 'Tagihan::setingTarif/$1'); // view utama seting per jenis
	$routes->post('tagihan/seting/(:num)', 'Tagihan::generateWithTarif/$1'); // generate massal
	$routes->post('tagihan/update', 'Tagihan::updateTagihan'); // update jumlah
	$routes->post('tagihan/generate-siswa/(:num)/(:num)', 'Tagihan::generatePerSiswa/$1/$2'); // generate per siswa
	$routes->get('tagihan/delete/(:num)/(:num)', 'Tagihan::deleteBySiswa/$1/$2'); // reset siswa



	$routes->get('pembayaran', 'Pembayaran::index');
	$routes->post('pembayaran/cari', 'Pembayaran::cariSiswa');
	$routes->get('pembayaran/(:num)', 'Pembayaran::detail/$1');
	$routes->post('pembayaran/bayar', 'Pembayaran::bayar');
	$routes->get('pembayaran/cetak/(:num)', 'Pembayaran::cetak/$1'); // ID transaksi
	$routes->get('pembayaran/search/siswa', 'Pembayaran::searchSiswa');
	$routes->post('pembayaran/delete-bayar/(:num)', 'Pembayaran::deleteBayar/$1');
	$routes->get('pembayaran/cetak/(:num)', 'Pembayaran::cetak/$1');
	$routes->post('pembayaran/cetak-banyak', 'Pembayaran::cetakBanyak');
	$routes->get('pembayaran/cetak-bukti', 'Pembayaran::cetakBukti');



	$routes->get('setting', 'Setting::index');
	$routes->post('setting/save', 'Setting::save');

	$routes->get('pengumuman', 'Pengumuman::index');
	$routes->post('pengumuman/save', 'Pengumuman::save');
	$routes->get('pengumuman/edit/(:num)', 'Pengumuman::edit/$1');
	$routes->post('pengumuman/update/(:num)', 'Pengumuman::update/$1');
	$routes->get('pengumuman/delete/(:num)', 'Pengumuman::delete/$1');

	$routes->get('kasbon', 'Kasbon::index');
	$routes->post('kasbon/save', 'Kasbon::save');
	$routes->post('kasbon/delete/(:num)', 'Kasbon::delete/$1');
	$routes->post('kasbon/bayar', 'Kasbon::bayar');
	$routes->get('kasbon/riwayat/(:num)', 'Kasbon::riwayat/$1');
	$routes->post('kasbon/hapus-angsuran/(:num)', 'Kasbon::hapusAngsuran/$1');
	$routes->get('kasbon/laporan', 'Kasbon::laporan');

	$routes->get('transaksi-pos', 'TransaksiPos::index', ['filter' => 'role:superadmin,admin,operator,staf']);
	$routes->post('transaksi-pos/sinkron/(:num)', 'TransaksiPos::sinkron/$1', ['filter' => 'role:superadmin,admin,operator']);
	$routes->post('transaksi-pos/keluar', 'TransaksiPos::keluar', ['filter' => 'role:superadmin,admin,operator']);
	$routes->post('transaksi-pos/masuk', 'TransaksiPos::masuk', ['filter' => 'role:superadmin,admin']);
	$routes->get('transaksi-pos/detail/(:num)', 'TransaksiPos::detail/$1', ['filter' => 'role:superadmin,admin,operator,staf']);
	$routes->get('transaksi-pos/edit/(:num)', 'TransaksiPos::edit/$1', ['filter' => 'role:superadmin,admin,operator']);
	$routes->post('transaksi-pos/update/(:num)', 'TransaksiPos::update/$1', ['filter' => 'role:superadmin,admin,operator']);
	$routes->post('transaksi-pos/delete/(:num)', 'TransaksiPos::delete/$1', ['filter' => 'role:superadmin,admin,operator']);
	$routes->get('transaksi-pos/export/(:num)', 'TransaksiPos::exportExcel/$1');

	$routes->get('laporan/harian', 'Laporan::harian');
	$routes->get('laporan/periode', 'Laporan::periode');
	$routes->get('laporan/tagihan', 'Laporan::tagihan');
	$routes->get('laporan/tagihan/cetak/(:num)', 'Laporan::cetakTagihan/$1');
	$routes->get('laporan/tagihan-detail/(:num)', 'Laporan::tagihanDetail/$1');
	$routes->get('laporan/tagihan-detail-kelas/(:num)', 'Laporan::tagihanDetailKelas/$1');
	$routes->get('laporan/harian/print', 'Laporan::harianPrint');
	$routes->get('laporan/harian/export', 'Laporan::harianExport');
	$routes->get('laporan/periode/print', 'Laporan::periodePrint');
	$routes->get('laporan/periode/export', 'Laporan::periodeExport');
	$routes->get('laporan/tagihan/print', 'Laporan::tagihanPrint');
	$routes->get('laporan/tagihan/export', 'Laporan::tagihanExport');
	$routes->get('laporan/filter-tagihan', 'Laporan::filterTagihan');
	$routes->get('laporan/filter-tagihan/print', 'Laporan::printFilterTagihan');
	$routes->get('laporan/rekap-tunggakan', 'Laporan::rekapTunggakan');
	$routes->get('laporan/rekap-tunggakan/print', 'Laporan::rekapTunggakanPrint');
	$routes->get('laporan/rekap-tunggakan/print-siswa', 'Laporan::rekapTunggakanSiswaPrint');
	$routes->get('laporan/filter-tagihan/excel', 'Laporan::excelTagihan');

	$routes->get('laporanlain', 'LaporanLain::index');
	$routes->get('laporanlain/per-tahun', 'LaporanLain::terimaPerTahun');
	$routes->get('laporanlain/per-bulan', 'LaporanLain::terimaBulanan');
	$routes->get('laporanlain/rentang', 'LaporanLain::terimaRentang');
	$routes->get('laporanlain/export-bulanan', 'LaporanLain::exportPerBulan');
	$routes->get('laporanlain/export-pertahun', 'LaporanLain::exportPerTahun');
	$routes->get('laporanlain/export-rentang', 'LaporanLain::exportRentang');
	$routes->get('laporanlain/export-bulanan', 'LaporanLain::exportPerBulan');


	$routes->get('backup-restore', 'BackupRestore::index');
	$routes->get('backup-restore/backup', 'BackupRestore::backup');
	$routes->post('backup-restore/restore', 'BackupRestore::restore');
});

