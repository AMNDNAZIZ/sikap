<?php

namespace App\Libraries;

/**
 * EspataEducationalKnowledge — Komponen Pengetahuan Pendidikan Terstruktur (Stage 2E.2-B)
 *
 * Komponen ini menyediakan fakta pedagogis, kurikulum (Kurikulum Merdeka),
 * alur pembelajaran, asesmen, diferensiasi, kriteria ketuntasan (KKTP), dan profil lulusan
 * yang terkurasi untuk mendampingi guru dan pengelola sekolah SD/SMP.
 *
 * BATAS KEWENANGAN (MANDAT EAO-HO-2026-063):
 * 1. Komponen ini HANYA berwenang menyediakan pengetahuan pendidikan / pedagogik.
 * 2. DILARANG menentukan hak akses, RBAC, API Key, kuota, atau Operational Context pengguna.
 * 3. DILARANG mengklaim atau menyebutkan menu/fitur spesifik aplikasi ESPATA (itu kewenangan EAKB).
 * 4. Komponen bekerja secara mandiri, deterministik, tanpa API eksternal, dan aman dari regresi.
 */
class EspataEducationalKnowledge
{
    public const SOURCE_VERIFIED_REGULATION = 'VERIFIED_REGULATION';
    public const SOURCE_PEDAGOGICAL_PRINCIPLE = 'PEDAGOGICAL_PRINCIPLE';
    public const SOURCE_UNVERIFIED           = 'UNVERIFIED';

    /**
     * Basis data pengetahuan pendidikan kanonikal
     * Terbagi dalam 10 cakupan pedagogik sesuai Mandat EAO-HO-2026-063
     *
     * @var array<string, array<string, mixed>>
     */
    protected static array $knowledgeBase = [
        'edu_kurikulum_merdeka_prinsip' => [
            'id'              => 'edu_kurikulum_merdeka_prinsip',
            'title'           => 'Prinsip Pembelajaran Kurikulum Merdeka',
            'category'        => 'Kurikulum & Prinsip Pembelajaran',
            'relevant_levels' => ['SD', 'SMP', 'SMA'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D', 'Fase E', 'Fase F'],
            'content'         => 'Pembelajaran Kurikulum Merdeka dirancang dengan mempertimbangkan tahap perkembangan dan tingkat pencapaian peserta didik saat ini, sesuai dengan kebutuhan belajar, serta mencerminkan karakteristik dan perkembangan peserta didik yang beragam sehingga pembelajaran menjadi bermakna dan menyenangkan.',
            'principles'      => [
                'Pembelajaran berpusat pada peserta didik (student-centered learning).',
                'Mempertimbangkan kesiapan belajar, minat, dan latar belakang budaya murid.',
                'Membangun kapasitas untuk menjadi pembelajar sepanjang hayat.',
                'Mendukung perkembangan kompetensi dan karakter peserta didik secara holistik.',
                'Pembelajaran yang relevan, kontekstual, dan berorientasi pada masa depan berkelanjutan.',
            ],
            'source'          => 'Permendikbudristek No. 12 Tahun 2024 & Panduan Pembelajaran dan Asesmen (PPA) BSKAP',
            'source_status'   => self::SOURCE_VERIFIED_REGULATION,
            'keywords'        => ['kurikulum merdeka', 'prinsip pembelajaran', 'student centered', 'pembelajaran bermakna', 'filosofi kurikulum merdeka'],
        ],

        'edu_cp_tp_atp_konstruksi' => [
            'id'              => 'edu_cp_tp_atp_konstruksi',
            'title'           => 'Hierarki Penurunan CP, TP, dan Penyusunan ATP',
            'category'        => 'Capaian & Alur Pembelajaran',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'Capaian Pembelajaran (CP) merupakan kompetensi pembelajaran yang harus dicapai peserta didik pada setiap fase. Dari CP, pendidik merumuskan Tujuan Pembelajaran (TP) yang memuat kompetensi dan lingkup materi, kemudian menyusunnya secara logis dan kronologis menjadi Alur Tujuan Pembelajaran (ATP) sebagai peta jalan pembelajaran dalam satu fase.',
            'principles'      => [
                'CP ditetapkan oleh pemerintah per fase; pendidik tidak mengubah redaksi CP.',
                'TP dirumuskan pendidik dengan membedah kompetensi (kata kerja operasional) dan konten/materi utama dari CP.',
                'ATP disusun linier dari awal fase hingga akhir fase tanpa tumpang tindih.',
                'Pendidik berhak menentukan alokasi waktu dan urutan TP dalam ATP sesuai kesiapan peserta didik.',
                'Perumusan TP dapat menggunakan pendekatan Taksonomi Bloom yang direvisi atau Teori Dimensi Pemahaman Tighe & Wiggins.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP & Keputusan Kepala BSKAP No. 032/H/KR/2024',
            'source_status'   => self::SOURCE_VERIFIED_REGULATION,
            'keywords'        => ['cp', 'tp', 'atp', 'capaian pembelajaran', 'tujuan pembelajaran', 'alur tujuan pembelajaran', 'rumusan tp', 'urutan atp'],
        ],

        'edu_perencanaan_modul_ajar' => [
            'id'              => 'edu_perencanaan_modul_ajar',
            'title'           => 'Komponen Esensial Modul Ajar dan Rencana Pelaksanaan Pembelajaran',
            'category'        => 'Perencanaan Pembelajaran',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'Modul ajar merupakan dokumen rencana pembelajaran lengkap yang setara dengan RPP namun dilengkapi materi ajar, lembar kerja, dan instrumen asesmen. Pendidik memiliki kemerdekaan menyusun modul ajar sendiri, memodifikasi modul ajar yang tersedia, atau menggunakan modul ajar yang disediakan pemerintah sesuai kebutuhan kelas.',
            'principles'      => [
                'Komponen minimum modul ajar: tujuan pembelajaran, langkah-langkah pembelajaran, rencana asesmen awal, dan rencana asesmen akhir.',
                'Langkah pembelajaran terbagi menjadi: kegiatan pendahuluan (apersepsi & pemantik), kegiatan inti (aktivitas bermakna), dan kegiatan penutup (refleksi & simpulan).',
                'Memuat pertanyaan pemantik untuk menumbuhkan rasa ingin tahu dan nalar kritis murid.',
                'Dilengkapi lembar aktivitas peserta didik (LKPD) yang kontekstual dan asesmen formatif berkala.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP Kemendikbudristek',
            'source_status'   => self::SOURCE_VERIFIED_REGULATION,
            'keywords'        => ['modul ajar', 'perencanaan pembelajaran', 'rpp', 'komponen modul ajar', 'kegiatan inti', 'pertanyaan pemantik', 'lkpd'],
        ],

        'edu_diferensiasi_adaptif' => [
            'id'              => 'edu_diferensiasi_adaptif',
            'title'           => 'Strategi Pembelajaran Terdiferensiasi (Konten, Proses, Produk)',
            'category'        => 'Diferensiasi & Adaptasi',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'Pembelajaran terdiferensiasi adalah pendekatan pedagogis di mana guru memfasilitasi kebutuhan belajar murid yang beragam berdasarkan kesiapan belajar (readiness), minat, dan profil belajar, tanpa harus membuat rencana pembelajaran individual yang terpisah untuk setiap murid.',
            'principles'      => [
                'Diferensiasi Konten: Menyesuaikan materi ajar, tingkat kesulitan bacaan, atau format representasi (teks, visual, audio, konkret).',
                'Diferensiasi Proses: Memberikan variasi scaffolding (bimbingan bertahap), waktu pengerjaan, atau pembagian kelompok belajar (mandiri vs kolaboratif).',
                'Diferensiasi Produk: Memberikan kebebasan bentuk unjuk kerja murid (laporan tertulis, presentasi lisan, video, poster, atau model fisik).',
                'Diferensiasi didasarkan pada hasil pemetaan asesmen diagnostik awal, bukan asumsi stereotip.',
            ],
            'source'          => 'Prinsip Pedagogis Tomlinson (Differentiated Instruction) & Panduan Pembelajaran dan Asesmen',
            'source_status'   => self::SOURCE_PEDAGOGICAL_PRINCIPLE,
            'keywords'        => ['diferensiasi', 'pembelajaran terdiferensiasi', 'konten proses produk', 'kesiapan belajar', 'profil belajar', 'scaffolding'],
        ],

        'edu_asesmen_tiga_jenis' => [
            'id'              => 'edu_asesmen_tiga_jenis',
            'title'           => 'Fungsi dan Penerapan Asesmen Diagnostik, Formatif, dan Sumatif',
            'category'        => 'Asesmen & Penilaian',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'Asesmen dalam Kurikulum Merdeka menitikberatkan pada fungsi formatif untuk meningkatkan mutu pembelajaran (assessment for & as learning), bukan semata-mata mengukur hasil akhir (assessment of learning).',
            'principles'      => [
                'Asesmen Diagnostik / Asesmen Awal: Dilakukan di awal bab/tahun untuk memetakan kesiapan murid guna merancang diferensiasi.',
                'Asesmen Formatif: Terintegrasi dalam kegiatan belajar untuk memantau kemajuan, mendiagnosis kesulitan, dan memberi umpan balik deskriptif seketika.',
                'Asesmen Sumatif: Dilakukan di akhir lingkup materi atau akhir semester untuk mengukur ketercapaian seluruh tujuan pembelajaran.',
                'Umpan balik formatif tidak berorientasi nilai angka, melainkan komentar konstruktif yang membimbing perbaikan langkah murid.',
            ],
            'source'          => 'Permendikbudristek No. 21 Tahun 2022 tentang Standar Penilaian Pendidikan & PPA BSKAP',
            'source_status'   => self::SOURCE_VERIFIED_REGULATION,
            'keywords'        => ['asesmen diagnostik', 'asesmen formatif', 'asesmen sumatif', 'umpan balik', 'assessment for learning', 'asesmen awal'],
        ],

        'edu_kktp_penentuan' => [
            'id'              => 'edu_kktp_penentuan',
            'title'           => 'Pendekatan Penentuan Kriteria Ketercapaian Tujuan Pembelajaran (KKTP)',
            'category'        => 'Asesmen & Penilaian',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'KKTP menggantikan konsep KKM tunggal masa lalu. KKTP adalah serangkaian kriteria atau indikator konkret yang menunjukkan sejauh mana peserta didik telah mencapai tujuan pembelajaran tertentu, berfungsi mengidentifikasi murid yang membutuhkan bimbingan lanjutan atau pengayaan.',
            'principles'      => [
                'Pendekatan 1 - Deskripsi Kriteria: Menentukan daftar kriteria kualitatif (misal: "mampu menyebutkan fakta", "mampu menguraikan proses"). Jika 3 dari 4 terpenuhi, dinyatakan tuntas.',
                'Pendekatan 2 - Rubrik Bertingkat: Menggunakan kriteria bertingkat (misal: Perlu Bimbingan, Cukup, Baik, Sangat Baik) dengan deskriptor capaian jelas.',
                'Pendekatan 3 - Skala / Interval Nilai: Menggunakan interval nilai berbasis persentase ketercapaian kriteria rubrik (misal: 0-40% remedial total, 41-65% remedial bagian tertentu, 66-85% tuntas, 86-100% pengayaan).',
                'KKTP tidak diseragamkan satu angka untuk semua mata pelajaran di satu sekolah; ditentukan oleh pendidik pengampu.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP Kemendikbudristek',
            'source_status'   => self::SOURCE_VERIFIED_REGULATION,
            'keywords'        => ['kktp', 'kriteria ketercapaian', 'kkm', 'rubrik kktp', 'deskripsi kriteria', 'interval nilai', 'ketuntasan belajar'],
        ],

        'edu_pengolahan_hasil_asesmen' => [
            'id'              => 'edu_pengolahan_hasil_asesmen',
            'title'           => 'Teknik Pengolahan Nilai Formatif dan Sumatif Menjadi Nilai Rapor',
            'category'        => 'Pengolahan Nilai & Pelaporan',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'Pengolahan hasil belajar dalam Kurikulum Merdeka memisahkan peran nilai formatif dari nilai sumatif. Nilai akhir rapor dihitung dari asesmen sumatif, sedangkan data formatif digunakan untuk memperkaya deskripsi capaian kompetensi murid.',
            'principles'      => [
                'Nilai rapor kuantitatif bersumber dari akumulasi asesmen sumatif (sumatif materi dan/atau sumatif akhir semester jika ada).',
                'Nilai formatif TIDAK dirata-ratakan ke nilai rapor karena berfungsi sebagai asesmen proses pembinaan.',
                'Deskripsi rapor dirumuskan dari kompetensi yang paling dikuasai (capaian tertinggi) dan kompetensi yang paling membutuhkan penguatan/bantuan.',
                'Deskripsi menggunakan kalimat positif, memotivasi, dan spesifik mengacu pada tujuan pembelajaran yang dinilai.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP Kemendikbudristek',
            'source_status'   => self::SOURCE_VERIFIED_REGULATION,
            'keywords'        => ['pengolahan nilai', 'nilai rapor', 'deskripsi capaian', 'rapor kurikulum merdeka', 'rekap sumatif', 'deskripsi rapor'],
        ],

        'edu_profil_lulusan_dimensi' => [
            'id'              => 'edu_profil_lulusan_dimensi',
            'title'           => 'Delapan Dimensi Karakter Profil Lulusan Pendidikan Holistik',
            'category'        => 'Karakter & Profil Lulusan',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'Profil Lulusan merupakan kompas karakter dan kompetensi yang dibangun secara terintegrasi melalui pembelajaran intrakurikuler, kokurikuler, dan budaya satuan pendidikan guna mewujudkan lulusan yang berakhlak mulia, berdaya saing, dan tangguh.',
            'principles'      => [
                'Dimensi 1 - Keimanan & Ketaqwaan: Hubungan spiritual dengan Tuhan YME, integritas moral, dan akhlak mulia.',
                'Dimensi 2 - Kewargaan & Kebinekaan: Menghargai keragaman budaya, toleransi, cinta tanah air, dan kepedulian sosial.',
                'Dimensi 3 - Gotong Royong & Kolaborasi: Bekerja sama secara sukarela untuk kepentingan bersama.',
                'Dimensi 4 - Kemandirian & Regulasi Diri: Bertanggung jawab atas proses dan hasil belajar diri sendiri.',
                'Dimensi 5 - Penalaran Kritis & Problem Solving: Memproses informasi secara obyektif, menganalisis, dan mengambil keputusan berbasis logika.',
                'Dimensi 6 - Kreativitas & Inovasi: Menghasilkan gagasan dan karya orisinal yang bermakna dan berdampak.',
                'Dimensi 7 - Komunikasi & Literasi Digital: Memanfaatkan teknologi secara bijak, etis, dan mampu mengekspresikan gagasan secara efektif.',
                'Dimensi 8 - Ketahanan Diri & Kesejahteraan Fisik (Wellbeing): Menjaga kesehatan jasmani-rohani serta memiliki daya lentur (resilience) dalam menghadapi tantangan.',
            ],
            'source'          => 'Permendikbudristek No. 12 Tahun 2024 & Kerangka Profil Lulusan Holistik Jenjang Pendidikan Dasar',
            'source_status'   => self::SOURCE_PEDAGOGICAL_PRINCIPLE,
            'keywords'        => ['profil lulusan', 'profil pelajar', 'dimensi profil', 'karakter', 'gotong royong', 'nalar kritis', 'kemandirian', 'wellbeing'],
        ],

        'edu_fase_a_sd_awal' => [
            'id'              => 'edu_fase_a_sd_awal',
            'title'           => 'Karakteristik dan Strategi Pembelajaran Fase A (Kelas 1–2 SD)',
            'category'        => 'Strategi Pembelajaran per Fase',
            'relevant_levels' => ['SD'],
            'relevant_phases' => ['Fase A'],
            'content'         => 'Fase A merupakan fondasi transisi dari PAUD ke jenjang pendidikan dasar. Pembelajaran berfokus pada pembangunan rasa aman, kenyamanan belajar, penguatan literasi-numerasi awal, dan pemantapan motorik kasar serta halus melalui kegiatan berbasis pengalaman langsung.',
            'principles'      => [
                'Transisi PAUD-SD yang menyenangkan: Tanpa pemaksaan calistung tes hafalan, melainkan literasi-numerasi kontekstual.',
                'Pendekatan visual, auditif, dan kinestetik dengan media manipulatif konkret (benda nyata, kartu bergambar, balok).',
                'Instruksi singkat, repetitif secara positif, dan pembiasaan aturan kelas melalui lagu atau isyarat gerak.',
                'Durasi fokus anak usia 6-8 tahun berkisar 15-20 menit per segmen aktivitas terarah.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP & Teori Perkembangan Kognitif Anak',
            'source_status'   => self::SOURCE_PEDAGOGICAL_PRINCIPLE,
            'keywords'        => ['fase a', 'kelas 1 sd', 'kelas 2 sd', 'transisi paud sd', 'literasi awal', 'numerasi awal', 'media konkret'],
        ],

        'edu_fase_b_sd_menengah' => [
            'id'              => 'edu_fase_b_sd_menengah',
            'title'           => 'Karakteristik dan Strategi Pembelajaran Fase B (Kelas 3–4 SD)',
            'category'        => 'Strategi Pembelajaran per Fase',
            'relevant_levels' => ['SD'],
            'relevant_phases' => ['Fase B'],
            'content'         => 'Fase B merupakan masa transisi dari berpikir konkret menuju berpikir representasional dan semi-abstrak. Peserta didik mulai mampu mengorganisasi informasi, mengamati pola sebab-akibat sederhana, dan bekerja dalam kelompok kecil.',
            'principles'      => [
                'Penguatan literasi membaca pemahaman (membaca untuk belajar / reading to learn) dan teks informasional sederhana.',
                'Numerasi operasi hitung bilangan bulat, pecahan visual, dan pengukuran lingkungan sekitar.',
                'Strategi kolaborasi kelompok kecil 3-4 anak dengan peran terdefinisi (pencatat, juru bicara, penanggung jawab alat).',
                'Pengenalan proyek sederhana berbasis lingkungan sekolah dan pengamatan sosial terdekat.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP',
            'source_status'   => self::SOURCE_PEDAGOGICAL_PRINCIPLE,
            'keywords'        => ['fase b', 'kelas 3 sd', 'kelas 4 sd', 'membaca pemahaman', 'kolaborasi kecil', 'semi abstrak'],
        ],

        'edu_fase_c_sd_tinggi' => [
            'id'              => 'edu_fase_c_sd_tinggi',
            'title'           => 'Karakteristik dan Strategi Pembelajaran Fase C (Kelas 5–6 SD)',
            'category'        => 'Strategi Pembelajaran per Fase',
            'relevant_levels' => ['SD'],
            'relevant_phases' => ['Fase C'],
            'content'         => 'Fase C ditandai dengan kemampuan berpikir logis terstruktur dan awal penalaran abstrak. Murid siap melakukan penyelidikan ilmiah sederhana, analisis perbandingan, dan pemecahan masalah kontekstual sebelum bertransisi ke SMP.',
            'principles'      => [
                'Pembelajaran berbasis inkuiri dan pemecahan masalah (Problem-Based Learning tingkat dasar).',
                'Analisis multi-sumber bacaan, penyusunan rangkuman, dan penyajian data dalam bentuk tabel atau grafik sederhana.',
                'Penanaman tanggung jawab mandiri terhadap tenggat waktu dan kualitas portofolio belajar.',
                'Penguatan regulasi emosi dan rasa percaya diri menghadapi transisi menuju jenjang remaja awal.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP',
            'source_status'   => self::SOURCE_PEDAGOGICAL_PRINCIPLE,
            'keywords'        => ['fase c', 'kelas 5 sd', 'kelas 6 sd', 'inkuiri', 'pembelajaran berbasis masalah', 'transisi smp'],
        ],

        'edu_fase_d_smp' => [
            'id'              => 'edu_fase_d_smp',
            'title'           => 'Karakteristik dan Strategi Pembelajaran Fase D (Kelas 7–9 SMP)',
            'category'        => 'Strategi Pembelajaran per Fase',
            'relevant_levels' => ['SMP'],
            'relevant_phases' => ['Fase D'],
            'content'         => 'Fase D mencakup masa remaja awal dengan perkembangan kognitif operasi formal (Piaget). Peserta didik mampu bernalar abstrak, menguji hipotesis, mengevaluasi argumen, dan sangat dipengaruhi oleh dinamika pertemanan sebaya (peer group).',
            'principles'      => [
                'Strategi Problem-Based Learning (PBL) dan Project-Based Learning (PjBL) yang mengangkat isu nyata (lingkungan, sosial, teknologi).',
                'Pemberian ruang diskusi debat terarah, analisis sudut pandang beragam, dan verifikasi informasi digital/kritis.',
                'Scaffolding untuk manajemen waktu tugas jangka panjang dan kemandirian riset perpustakaan/internet.',
                'Pendekatan komunikasi guru yang suportif, menghargai privasi dan otonomi remaja, serta menumbuhkan motivasi intrinsik.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP & Psikologi Perkembangan Remaja',
            'source_status'   => self::SOURCE_PEDAGOGICAL_PRINCIPLE,
            'keywords'        => ['fase d', 'kelas 7 smp', 'kelas 8 smp', 'kelas 9 smp', 'pbl', 'pjbl', 'remaja awal', 'penalaran abstrak'],
        ],

        'edu_fase_e_f_sma' => [
            'id'              => 'edu_fase_e_f_sma',
            'title'           => 'Karakteristik dan Strategi Pembelajaran Fase E & F (Kelas 10–12 SMA/SMK)',
            'category'        => 'Strategi Pembelajaran per Fase',
            'relevant_levels' => ['SMA'],
            'relevant_phases' => ['Fase E', 'Fase F'],
            'content'         => 'Fase E (Kelas 10) dan Fase F (Kelas 11-12) merupakan tahapan pendalaman minat dan spesialisasi keilmuan menuju pendidikan tinggi atau dunia kerja. Peserta didik dituntut memiliki kecakapan berpikir tingkat tinggi (HOTS) dan metakognisi mandiri.',
            'principles'      => [
                'Eksplorasi mata pelajaran pilihan sesuai bakat, minat, dan aspirasi karier masa depan.',
                'Penelitian ilmiah terapan, penulisan esai argumentatif berbasis bukti, dan karya inovasi mandiri.',
                'Pengembangan kepemimpinan, kemampuan negosiasi, dan etika profesional.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) BSKAP',
            'source_status'   => self::SOURCE_PEDAGOGICAL_PRINCIPLE,
            'keywords'        => ['fase e', 'fase f', 'sma', 'smk', 'kelas 10', 'kelas 11', 'kelas 12', 'hots', 'spesialisasi'],
        ],

        'edu_remedial_dan_pengayaan' => [
            'id'              => 'edu_remedial_dan_pengayaan',
            'title'           => 'Tata Cara Pelaksanaan Remedial dan Pengayaan Berdasarkan Hasil Asesmen',
            'category'        => 'Remedial & Pengayaan',
            'relevant_levels' => ['SD', 'SMP'],
            'relevant_phases' => ['Fase A', 'Fase B', 'Fase C', 'Fase D'],
            'content'         => 'Remedial dan pengayaan adalah bentuk tindak lanjut diferensiasi pasca asesmen. Remedial ditujukan untuk membantu peserta didik yang belum mencapai KKTP melalui intervensi belajar terarah, sedangkan pengayaan memberikan tantangan lanjutan bagi peserta didik yang melampaui KKTP.',
            'principles'      => [
                'Remedial bukan sekadar mengerjakan ulang tes yang sama, melainkan pemberian bimbingan ulang pada materi yang belum dikuasai (tutor sebaya, media alternatif, atau penjelasan mandiri guru).',
                'Materi remedial hanya difokuskan pada indikator TP yang belum tercapai, bukan mengulang seluruh materi bab.',
                'Pengayaan TIDAK menambah beban hafalan berulang, melainkan eksplorasi mendalam, proyek investigasi mandiri, atau memecahkan masalah kontekstual yang lebih kompleks.',
                'Hasil remedial didokumentasikan untuk merefleksikan efektivitas strategi mengajar guru.',
            ],
            'source'          => 'Panduan Pembelajaran dan Asesmen (PPA) Kemendikbudristek',
            'source_status'   => self::SOURCE_VERIFIED_REGULATION,
            'keywords'        => ['remedial', 'pengayaan', 'tindak lanjut asesmen', 'tutor sebaya', 'intervensi belajar', 'bimbingan khusus'],
        ],
    ];

    /**
     * Mengambil seluruh entri pengetahuan pendidikan
     *
     * @return array<string, array<string, mixed>>
     */
    public static function getAllEntries(): array
    {
        return self::$knowledgeBase;
    }

    /**
     * Mengambil satu entri spesifik berdasarkan ID
     *
     * @param string $id
     * @return array<string, mixed>|null
     */
    public static function getEntry(string $id): ?array
    {
        return self::$knowledgeBase[$id] ?? null;
    }

    /**
     * Mengambil daftar seluruh kategori unik yang tersedia
     *
     * @return array<int, string>
     */
    public static function getCategories(): array
    {
        $categories = [];
        foreach (self::$knowledgeBase as $item) {
            if (!empty($item['category']) && !in_array($item['category'], $categories, true)) {
                $categories[] = $item['category'];
            }
        }
        return $categories;
    }

    /**
     * Mengambil entri berdasarkan kategori spesifik
     *
     * @param string $category
     * @return array<string, array<string, mixed>>
     */
    public static function getByCategory(string $category): array
    {
        $categoryLower = strtolower(trim($category));
        $results = [];

        foreach (self::$knowledgeBase as $id => $item) {
            if (strtolower(trim($item['category'])) === $categoryLower) {
                $results[$id] = $item;
            }
        }

        return $results;
    }

    /**
     * Menyaring entri berdasarkan jenjang sekolah (misal: 'SD', 'SMP', 'SMA')
     *
     * @param string $level
     * @return array<string, array<string, mixed>>
     */
    public static function filterByLevel(string $level): array
    {
        $targetLevel = strtoupper(trim($level));
        $results = [];

        foreach (self::$knowledgeBase as $id => $item) {
            $levels = array_map('strtoupper', $item['relevant_levels'] ?? []);
            if (in_array($targetLevel, $levels, true)) {
                $results[$id] = $item;
            }
        }

        return $results;
    }

    /**
     * Menyaring entri berdasarkan fase kurikulum (misal: 'Fase A', 'Fase D')
     *
     * @param string $phase
     * @return array<string, array<string, mixed>>
     */
    public static function filterByPhase(string $phase): array
    {
        $targetPhase = strtolower(trim($phase));
        $results = [];

        foreach (self::$knowledgeBase as $id => $item) {
            $phases = array_map(function ($p) {
                return strtolower(trim($p));
            }, $item['relevant_phases'] ?? []);

            if (in_array($targetPhase, $phases, true)) {
                $results[$id] = $item;
            }
        }

        return $results;
    }

    /**
     * Pencarian deterministik pengetahuan pendidikan berbasis skor relevansi token
     *
     * @param string $query Kueri pencarian dalam Bahasa Indonesia
     * @param array<string, mixed> $options Opsi tambahan:
     *        - 'level' (string|null): Filter jenjang ('SD', 'SMP')
     *        - 'phase' (string|null): Filter fase ('Fase A' .. 'Fase D')
     *        - 'category' (string|null): Filter kategori
     *        - 'limit' (int): Batas entri teratas (default 3)
     *        - 'min_score' (int): Skor minimum relevansi (default 15)
     * @return array<int, array<string, mixed>>
     */
    public static function search(string $query, array $options = []): array
    {
        $cleanQuery = strtolower(trim(preg_replace('/[^a-zA-Z0-9\s]/u', ' ', $query)));
        if ($cleanQuery === '') {
            return [];
        }

        // Tokenisasi kata henti umum Bahasa Indonesia
        $stopWords = [
            'yang', 'di', 'dan', 'ke', 'dari', 'pada', 'untuk', 'ini', 'itu', 'dengan',
            'adalah', 'sebagai', 'cara', 'bagaimana', 'apa', 'mengapa', 'kapan', 'siapa',
            'tolong', 'bisa', 'mohon', 'jelaskan', 'tentang', 'buat', 'membuat', 'saya'
        ];

        $tokens = array_filter(explode(' ', $cleanQuery), function ($t) use ($stopWords) {
            return mb_strlen($t) >= 2 && !in_array($t, $stopWords, true);
        });

        if (empty($tokens)) {
            return [];
        }

        $filterLevel    = !empty($options['level']) ? strtoupper(trim($options['level'])) : null;
        $filterPhase    = !empty($options['phase']) ? strtolower(trim($options['phase'])) : null;
        $filterCategory = !empty($options['category']) ? strtolower(trim($options['category'])) : null;
        $limit          = isset($options['limit']) ? max(1, (int) $options['limit']) : 3;
        $minScore       = isset($options['min_score']) ? (int) $options['min_score'] : 15;

        $scoredEntries = [];

        foreach (self::$knowledgeBase as $id => $item) {
            // 1. Evaluasi Filter Jenjang
            if ($filterLevel !== null) {
                $levels = array_map('strtoupper', $item['relevant_levels'] ?? []);
                if (!in_array($filterLevel, $levels, true)) {
                    continue;
                }
            }

            // 2. Evaluasi Filter Fase
            if ($filterPhase !== null) {
                $phases = array_map(function ($p) {
                    return strtolower(trim($p));
                }, $item['relevant_phases'] ?? []);
                if (!in_array($filterPhase, $phases, true)) {
                    continue;
                }
            }

            // 3. Evaluasi Filter Kategori
            if ($filterCategory !== null) {
                if (strtolower(trim($item['category'])) !== $filterCategory) {
                    continue;
                }
            }

            // 4. Pembobotan Skor Relevansi Deterministik
            $score = 0;
            $titleLower   = strtolower($item['title']);
            $contentLower = strtolower($item['content']);
            $keywords     = array_map('strtolower', $item['keywords'] ?? []);

            // A. Exact phrase matching pada keywords (+40)
            foreach ($keywords as $kw) {
                if (strpos($cleanQuery, $kw) !== false) {
                    $score += 40;
                }
            }

            // B. Token matching
            foreach ($tokens as $token) {
                // Match di title (+25)
                if (strpos($titleLower, $token) !== false) {
                    $score += 25;
                }
                // Match di keywords (+15)
                foreach ($keywords as $kw) {
                    if (strpos($kw, $token) !== false) {
                        $score += 15;
                    }
                }
                // Match di content (+5)
                if (strpos($contentLower, $token) !== false) {
                    $score += 5;
                }
            }

            if ($score >= $minScore) {
                $entryWithScore = $item;
                $entryWithScore['_match_score'] = $score;
                $scoredEntries[] = $entryWithScore;
            }
        }

        // Urutkan secara deterministik menurun berdasarkan skor, lalu judul
        usort($scoredEntries, function ($a, $b) {
            if ($a['_match_score'] === $b['_match_score']) {
                return strcmp($a['id'], $b['id']);
            }
            return ($a['_match_score'] > $b['_match_score']) ? -1 : 1;
        });

        return array_slice($scoredEntries, 0, $limit);
    }

    /**
     * Membangun konteks pengetahuan pendidikan terstruktur untuk keperluan perakitan prompt AI
     *
     * @param string $query Kueri pengguna
     * @param array<string, mixed> $options Opsi pencarian & pemfilteran
     * @return array<string, mixed>|null Struktur konteks atau null jika tidak ada yang relevan
     */
    public static function buildEducationalContext(string $query, array $options = []): ?array
    {
        $matches = self::search($query, $options);
        if (empty($matches)) {
            return null;
        }

        $topMatch = $matches[0];
        $selectedEntries = [];

        foreach ($matches as $m) {
            $selectedEntries[] = [
                'id'            => $m['id'],
                'title'         => $m['title'],
                'category'      => $m['category'],
                'source'        => $m['source'],
                'source_status' => $m['source_status'],
                'content'       => $m['content'],
                'principles'    => $m['principles'] ?? [],
            ];
        }

        return [
            'status'         => 'success',
            'query'          => $query,
            'primary_topic'  => $topMatch['title'],
            'category'       => $topMatch['category'],
            'total_matches'  => count($matches),
            'entries'        => $selectedEntries,
        ];
    }

    /**
     * Memformat konteks pengetahuan pendidikan ke dalam blok tag XML <educational_guidance>
     * siap pakai untuk blok prompt Asis AI
     *
     * @param array<string, mixed> $context Hasil kembalian buildEducationalContext()
     * @param int $maxChars Batas maksimum karakter (default 1200)
     * @return string
     */
    public static function formatContextForPrompt(array $context, int $maxChars = 1200): string
    {
        if (empty($context['entries'])) {
            return '';
        }

        $lines = [];
        $lines[] = '<educational_guidance>';
        $lines[] = 'PANDUAN PEDAGOGIK KURIKULUM MERDEKA:';

        foreach ($context['entries'] as $idx => $entry) {
            $num = $idx + 1;
            $sourceTag = ($entry['source_status'] === self::SOURCE_VERIFIED_REGULATION)
                ? '[Regulasi Resmi: ' . $entry['source'] . ']'
                : '[Prinsip Pedagogis: ' . $entry['source'] . ']';

            $lines[] = "{$num}. {$entry['title']} {$sourceTag}";
            $lines[] = "   Konsep: {$entry['content']}";

            if (!empty($entry['principles'])) {
                $lines[] = "   Prinsip Utama:";
                foreach (array_slice($entry['principles'], 0, 3) as $pr) {
                    $lines[] = "   - {$pr}";
                }
            }
        }

        $lines[] = 'ATURAN PENERAPAN:';
        $lines[] = '- Sesuaikan rekomendasi draf ajar dengan jenjang, fase, dan karakteristik murid.';
        $lines[] = '- Jangan mengarang regulasi atau nomor keputusan baru di luar ketentuan di atas.';
        $lines[] = '</educational_guidance>';

        $output = implode("\n", $lines);

        // Jika melampaui batas karakter, lakukan pemotongan aman
        if (mb_strlen($output) > $maxChars) {
            $output = mb_substr($output, 0, $maxChars - 30) . "\n...\n</educational_guidance>";
        }

        return $output;
    }
}
