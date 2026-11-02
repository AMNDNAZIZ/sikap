<?php

namespace App\Models;

use CodeIgniter\Model;

class LetterTemplateModel extends Model
{
    protected $table         = 'letter_templates';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $allowedFields = [
        'title',
        'code',
        'category',
        'header_style',
        'default_subject',
        'body_html',
        'is_system',
        'is_active',
        'created_by',
    ];

    /**
     * Get all active templates grouped by category or list
     */
    public function getActiveTemplates(): array
    {
        $this->ensureDefaultTemplates();

        return $this->where('is_active', 1)
                    ->orderBy('is_system', 'DESC')
                    ->orderBy('title', 'ASC')
                    ->findAll();
    }

    /**
     * Pastikan template standar bawaan sekolah sudah di-seed
     */
    public function ensureDefaultTemplates(): void
    {
        $count = $this->where('is_system', 1)->countAllResults();
        if ($count > 0) {
            return;
        }

        $defaults = [
            [
                'title'           => 'Surat Keterangan Berkelakuan Baik',
                'code'            => 'ket_kelakuan_baik',
                'category'        => 'siswa',
                'header_style'    => 'tengah',
                'default_subject' => 'SURAT KETERANGAN BERKELAKUAN BAIK',
                'body_html'       => '<p>Yang bertanda tangan di bawah ini, Kepala <strong>{nama_sekolah}</strong>, dengan ini menerangkan bahwa:</p>
<table style="width:100%; border-collapse:collapse; margin: 10px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 3px 0;">Nama Lengkap</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 3px 0;"><strong>{nama_penerima}</strong></td></tr>
    <tr><td style="padding: 3px 0;">NISN / NIS</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{nisn} / {nis}</td></tr>
    <tr><td style="padding: 3px 0;">Tempat, Tanggal Lahir</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{ttl}</td></tr>
    <tr><td style="padding: 3px 0;">Jenis Kelamin</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{jenis_kelamin}</td></tr>
    <tr><td style="padding: 3px 0;">Kelas / Rombel</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{kelas}</td></tr>
    <tr><td style="padding: 3px 0;">Nama Orang Tua / Wali</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{nama_ayah}</td></tr>
    <tr><td style="padding: 3px 0;">Alamat Tempat Tinggal</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{alamat}</td></tr>
  </tbody>
</table>
<p style="margin-top: 12px; text-align: justify;">Berdasarkan catatan disiplin dan kepribadian pada buku induk sekolah kami, peserta didik tersebut di atas selama menempuh pendidikan di <strong>{nama_sekolah}</strong> berkelakuan <strong>BAIK</strong>, tidak pernah terlibat dalam tindakan kriminalitas, penyalahgunaan narkotika, tawuran pelajar, maupun pelanggaran berat terhadap tata tertib sekolah.</p>
<p style="margin-top: 12px; text-align: justify;">Surat keterangan ini diberikan atas permintaan yang bersangkutan untuk keperluan <strong>persyaratan melanjutkan pendidikan / administrasi kependudukan</strong>.</p>
<p style="margin-top: 12px; text-align: justify;">Demikian surat keterangan ini kami buat dengan sebenarnya dan penuh rasa tanggung jawab agar dapat dipergunakan sebagaimana mestinya.</p>',
                'is_system'       => 1,
                'is_active'       => 1,
            ],
            [
                'title'           => 'Surat Keterangan Penghasilan & Tidak Mampu (KJP / PIP)',
                'code'            => 'ket_penghasilan_ortu',
                'category'        => 'siswa',
                'header_style'    => 'tengah',
                'default_subject' => 'SURAT KETERANGAN PENGHASILAN ORANG TUA / WALI',
                'body_html'       => '<p>Yang bertanda tangan di bawah ini, Kepala <strong>{nama_sekolah}</strong>, menerangkan bahwa:</p>
<table style="width:100%; border-collapse:collapse; margin: 8px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Nama Siswa</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{nama_penerima}</strong></td></tr>
    <tr><td style="padding: 2px 0;">NISN / NIS</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{nisn} / {nis}</td></tr>
    <tr><td style="padding: 2px 0;">Kelas</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{kelas}</td></tr>
  </tbody>
</table>
<p style="margin-top: 8px;">Adalah benar anak dari orang tua / wali murid dengan rincian data sebagai berikut:</p>
<table style="width:100%; border-collapse:collapse; margin: 8px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Nama Ayah / Wali</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{nama_ayah}</strong></td></tr>
    <tr><td style="padding: 2px 0;">Pekerjaan Ayah</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{pekerjaan_ayah}</td></tr>
    <tr><td style="padding: 2px 0;">Penghasilan Rata-rata</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{penghasilan_ayah}</td></tr>
    <tr><td style="padding: 2px 0;">Nama Ibu</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{nama_ibu}</td></tr>
    <tr><td style="padding: 2px 0;">Pekerjaan Ibu</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{pekerjaan_ibu}</td></tr>
    <tr><td style="padding: 2px 0;">Alamat Tempat Tinggal</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{alamat_ortu}</td></tr>
  </tbody>
</table>
<p style="margin-top: 10px; text-align: justify;">Berdasarkan surat pernyataan dari orang tua dan verifikasi data di lingkungan sekolah, benar bahwa keluarga yang bersangkutan tergolong keluarga yang berpenghasilan rendah dan layak diajukan untuk mendapatkan bantuan keringanan biaya pendidikan (KJP Plus / PIP / Beasiswa).</p>
<p style="margin-top: 10px; text-align: justify;">Demikian surat keterangan ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagai kelengkapan dokumen pengajuan bantuan pendidikan.</p>',
                'is_system'       => 1,
                'is_active'       => 1,
            ],
            [
                'title'           => 'Surat Dispensasi Mengikuti Lomba / Kegiatan',
                'code'            => 'dispensasi_lomba',
                'category'        => 'siswa',
                'header_style'    => 'kiri_kanan',
                'default_subject' => 'Permohonan Dispensasi Mengikuti Kegiatan / Lomba',
                'body_html'       => '<p>Dengan hormat,</p>
<p style="text-align: justify;">Sehubungan dengan keikutsertaan peserta didik <strong>{nama_sekolah}</strong> dalam kegiatan perlombaan / kompetisi, dengan ini kami memohonkan dispensasi / izin tidak mengikuti Kegiatan Belajar Mengajar (KBM) untuk peserta didik di bawah ini:</p>
<table style="width:100%; border-collapse:collapse; margin: 10px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Nama Siswa</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{nama_penerima}</strong></td></tr>
    <tr><td style="padding: 2px 0;">NISN / NIS</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{nisn} / {nis}</td></tr>
    <tr><td style="padding: 2px 0;">Kelas</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{kelas}</td></tr>
  </tbody>
</table>
<p>{tabel_penerima}</p>
<p style="margin-top: 8px;">Adapun kegiatan tersebut akan dilaksanakan pada:</p>
<table style="width:100%; border-collapse:collapse; margin: 8px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Hari / Tanggal</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{hari_tanggal_kegiatan}</strong></td></tr>
    <tr><td style="padding: 2px 0;">Waktu</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{waktu_kegiatan}</td></tr>
    <tr><td style="padding: 2px 0;">Tempat Pelaksanaan</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{tempat_kegiatan}</td></tr>
  </tbody>
</table>
<p style="margin-top: 10px; text-align: justify;">Kami mohon Bapak/Ibu Guru berkenan memberikan izin dispensasi kepada siswa tersebut serta memberikan kesempatan mengikuti penilaian susulan atas materi pelajaran yang tertinggal.</p>
<p style="margin-top: 10px; text-align: justify;">Demikian surat permohonan dispensasi ini kami sampaikan. Atas perhatian, dukungan, dan kerja sama Bapak/Ibu, kami ucapkan terima kasih.</p>',
                'is_system'       => 1,
                'is_active'       => 1,
            ],
            [
                'title'           => 'Surat Rekomendasi Siswa Berprestasi',
                'code'            => 'rekomendasi_siswa',
                'category'        => 'siswa',
                'header_style'    => 'tengah',
                'default_subject' => 'SURAT REKOMENDASI KEPALA SEKOLAH',
                'body_html'       => '<p>Yang bertanda tangan di bawah ini:</p>
<table style="width:100%; border-collapse:collapse; margin: 8px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Nama</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{kepala_sekolah}</strong></td></tr>
    <tr><td style="padding: 2px 0;">NIP</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{nip_kepala_sekolah}</td></tr>
    <tr><td style="padding: 2px 0;">Jabatan</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">Kepala {nama_sekolah}</td></tr>
  </tbody>
</table>
<p style="margin-top: 8px;">Dengan ini memberikan rekomendasi penuh kepada:</p>
<table style="width:100%; border-collapse:collapse; margin: 8px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Nama Siswa</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{nama_penerima}</strong></td></tr>
    <tr><td style="padding: 2px 0;">NISN / NIS</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{nisn} / {nis}</td></tr>
    <tr><td style="padding: 2px 0;">Kelas</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{kelas}</td></tr>
    <tr><td style="padding: 2px 0;">Tempat, Tanggal Lahir</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{ttl}</td></tr>
  </tbody>
</table>
<p style="margin-top: 10px; text-align: justify;">Untuk mengikuti program seleksi / beasiswa / penerimaan peserta didik baru jenjang selanjutnya. Siswa yang bersangkutan telah menunjukkan pencapaian akademik dan kepribadian yang sangat membanggakan di sekolah kami, serta memiliki motivasi belajar yang tinggi.</p>
<p style="margin-top: 10px; text-align: justify;">Demikian surat rekomendasi ini kami berikan dengan penuh keyakinan dan tanpa ada paksaan dari pihak mana pun agar dapat dipergunakan sebagaimana mestinya.</p>',
                'is_system'       => 1,
                'is_active'       => 1,
            ],
            [
                'title'           => 'Surat Keterangan Pengalaman Mengajar / Kerja Guru',
                'code'            => 'ket_pengalaman_guru',
                'category'        => 'guru',
                'header_style'    => 'tengah',
                'default_subject' => 'SURAT KETERANGAN PENGALAMAN MENGAJAR',
                'body_html'       => '<p>Yang bertanda tangan di bawah ini, Kepala <strong>{nama_sekolah}</strong>, dengan ini menerangkan dengan sesungguhnya bahwa:</p>
<table style="width:100%; border-collapse:collapse; margin: 10px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 3px 0;">Nama Lengkap</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 3px 0;"><strong>{nama_penerima}</strong></td></tr>
    <tr><td style="padding: 3px 0;">NIP / NUPTK</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{nip} / {nuptk}</td></tr>
    <tr><td style="padding: 3px 0;">Pangkat / Golongan</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{pangkat_golongan}</td></tr>
    <tr><td style="padding: 3px 0;">Jabatan / Tugas</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{jabatan}</td></tr>
    <tr><td style="padding: 3px 0;">Status Kepegawaian</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{status_kepegawaian}</td></tr>
    <tr><td style="padding: 3px 0;">Alamat Domisili</td><td style="text-align: center;">:</td><td style="padding: 3px 0;">{alamat}</td></tr>
  </tbody>
</table>
<p style="margin-top: 12px; text-align: justify;">Adalah benar yang bersangkutan aktif bertugas sebagai Tenaga Pendidik / Guru di <strong>{nama_sekolah}</strong> dan senantiasa melaksanakan tugas pembelajaran dengan dedikasi, integritas, dan kedisiplinan yang tinggi.</p>
<p style="margin-top: 12px; text-align: justify;">Surat keterangan ini diberikan atas permohonan yang bersangkutan untuk keperluan <strong>persyaratan administrasi sertifikasi pendidik (PPG) / kenaikan jenjang karier / pendataan kepegawaian</strong>.</p>
<p style="margin-top: 12px; text-align: justify;">Demikian surat keterangan ini kami terbitkan dengan sebenarnya agar dapat dipergunakan sebagaimana mestinya.</p>',
                'is_system'       => 1,
                'is_active'       => 1,
            ],
            [
                'title'           => 'Surat Panggilan Orang Tua / Wali Murid',
                'code'            => 'panggilan_ortu',
                'category'        => 'undangan',
                'header_style'    => 'kiri_kanan',
                'default_subject' => 'Panggilan Orang Tua / Wali Murid',
                'body_html'       => '<p>Dengan hormat,</p>
<p style="text-align: justify;">Guna menjalin sinergi antara pihak sekolah dan keluarga demi kemajuan perkembangan belajar dan pembinaan peserta didik, melalui surat ini kami mengharapkan kehadiran Bapak/Ibu Orang Tua / Wali dari siswa:</p>
<table style="width:100%; border-collapse:collapse; margin: 10px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Nama Siswa</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{nama_penerima}</strong></td></tr>
    <tr><td style="padding: 2px 0;">NISN / NIS</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{nisn} / {nis}</td></tr>
    <tr><td style="padding: 2px 0;">Kelas</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{kelas}</td></tr>
  </tbody>
</table>
<p style="margin-top: 8px;">Untuk hadir pada pertemuan yang akan dilaksanakan pada:</p>
<table style="width:100%; border-collapse:collapse; margin: 8px 0;">
  <tbody>
    <tr><td style="width: 170px; padding: 2px 0;">Hari / Tanggal</td><td style="width: 15px; text-align: center;">:</td><td style="padding: 2px 0;"><strong>{hari_tanggal_kegiatan}</strong></td></tr>
    <tr><td style="padding: 2px 0;">Waktu</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">{waktu_kegiatan}</td></tr>
    <tr><td style="padding: 2px 0;">Tempat</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">Ruang Bimbingan Konseling (BK) / Kepala Sekolah {nama_sekolah}</td></tr>
    <tr><td style="padding: 2px 0;">Agenda / Keperluan</td><td style="text-align: center;">:</td><td style="padding: 2px 0;">Konsultasi Perkembangan Belajar & Pembinaan Kedisiplinan</td></tr>
  </tbody>
</table>
<p style="margin-top: 10px; text-align: justify;">Mengingat pentingnya agenda tersebut, kami sangat mengharapkan kehadiran Bapak/Ibu tepat pada waktu yang telah ditentukan.</p>
<p style="margin-top: 10px; text-align: justify;">Demikian surat panggilan ini kami sampaikan. Atas perhatian, kehadiran, dan kerja sama Bapak/Ibu, kami sampaikan terima kasih.</p>',
                'is_system'       => 1,
                'is_active'       => 1,
            ],
        ];

        foreach ($defaults as $d) {
            $d['created_at'] = date('Y-m-d H:i:s');
            $d['updated_at'] = date('Y-m-d H:i:s');
            $this->insert($d);
        }
    }
}

