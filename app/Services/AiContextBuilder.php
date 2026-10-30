<?php

namespace App\Services;

use App\Models\AcademicYearModel;
use App\Models\AlurTujuanPembelajaranModel;
use App\Models\AtpElemenModel;
use App\Models\ClassModel;
use App\Models\CpMasterModel;
use App\Models\FacilityModel;
use App\Models\KktpModel;
use App\Models\SchoolModel;
use App\Models\StudentRecordModel;
use App\Models\SubjectModel;
use App\Models\TujuanPembelajaranModel;
use Config\SubDimensiProfilLulusan;

/**
 * AI Context Builder Service
 *
 * Mengumpulkan, memvalidasi, menormalisasi, dan menyediakan konteks faktual
 * dari database ESPATA untuk generator AI (Modul Ajar dan Kokurikuler).
 *
 * Prinsip:
 * 1. Faktual & Terverifikasi: Hanya menyertakan data yang benar-benar tersimpan.
 * 2. Anti-Halusinasi: Menyediakan instruksi pembatas saat sarana/fasilitas tidak tersedia.
 * 3. Privasi Mutlak (Zero PII): Tidak pernah mengekspos nama, NISN, NIK, alamat, atau atribut individual siswa.
 * 4. Deterministic & Token-Efficient: Konteks terstruktur padat untuk efisiensi token.
 */
class AiContextBuilder extends BaseService
{
    protected ?object $schoolModel = null;
    protected ?object $facilityModel = null;
    protected ?object $classModel = null;
    protected ?object $studentRecordModel = null;
    protected ?object $academicYearModel = null;
    protected ?object $subjectModel = null;
    protected ?object $atpModel = null;
    protected ?object $atpElemenModel = null;
    protected ?object $cpMasterModel = null;
    protected ?object $tpModel = null;
    protected ?object $kktpModel = null;

    /**
     * Constructor
     *
     * @param \CodeIgniter\Database\BaseConnection|null $db Optional database connection injection
     */
    public function __construct($db = null)
    {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    /**
     * Setter untuk menginjeksi mock/model untuk unit testing
     */
    public function setModel(string $name, object $model): self
    {
        $property = lcfirst($name) . 'Model';
        if (property_exists($this, $property)) {
            $this->{$property} = $model;
        }
        return $this;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // MODEL RESOLVERS (Lazy Loaded)
    // ─────────────────────────────────────────────────────────────────────────

    protected function getSchoolModel(): object
    {
        return $this->schoolModel ??= new SchoolModel();
    }

    protected function getFacilityModel(): object
    {
        return $this->facilityModel ??= new FacilityModel();
    }

    protected function getClassModel(): object
    {
        return $this->classModel ??= new ClassModel();
    }

    protected function getStudentRecordModel(): object
    {
        return $this->studentRecordModel ??= new StudentRecordModel();
    }

    protected function getAcademicYearModel(): object
    {
        return $this->academicYearModel ??= new AcademicYearModel();
    }

    protected function getSubjectModel(): object
    {
        return $this->subjectModel ??= new SubjectModel();
    }

    protected function getAtpModel(): object
    {
        return $this->atpModel ??= new AlurTujuanPembelajaranModel();
    }

    protected function getAtpElemenModel(): object
    {
        return $this->atpElemenModel ??= new AtpElemenModel();
    }

    protected function getCpMasterModel(): object
    {
        return $this->cpMasterModel ??= new CpMasterModel();
    }

    protected function getTpModel(): object
    {
        return $this->tpModel ??= new TujuanPembelajaranModel();
    }

    protected function getKktpModel(): object
    {
        return $this->kktpModel ??= new KktpModel();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 1. MODUL AJAR CONTEXT BUILDER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Membangun payload konteks komprehensif untuk generator Modul Ajar
     *
     * @param int $atpId ID Alur Tujuan Pembelajaran
     * @param int|null $classId ID Kelas yang dipilih (otoritatif)
     * @param int $subjectId ID Mata Pelajaran
     * @param int|null $teacherId ID Guru Pengampu (otoritatif)
     * @param int|null $academicYearId ID Tahun Ajaran (opsional, default aktif)
     * @return array
     */
    public function buildModulAjarContext(
        int $atpId,
        ?int $classId,
        int $subjectId,
        ?int $teacherId = null,
        ?int $academicYearId = null
    ): array {
        try {
            // 1. Validasi & Ambil Dokumen ATP
            $atpRaw = $this->getAtpModel()->find($atpId);
            if (!$atpRaw) {
                return $this->errorResponse('Dokumen ATP tidak ditemukan.', ['error_code' => 'ATP_NOT_FOUND']);
            }

            // Validasi integritas relasi: ATP harus cocok dengan Subject yang diminta
            if ((int)($atpRaw['subject_id'] ?? 0) !== $subjectId) {
                return $this->errorResponse('Relasi dokumen ATP tidak cocok dengan mata pelajaran yang diminta.', [
                    'error_code' => 'SUBJECT_MISMATCH',
                    'atp_subject_id' => $atpRaw['subject_id'] ?? null,
                    'requested_subject_id' => $subjectId,
                ]);
            }

            // Tentukan class_id otoritatif
            $resolvedClassId = $classId;
            if (empty($resolvedClassId) && !empty($atpRaw['class_id'])) {
                $resolvedClassId = (int)$atpRaw['class_id'];
            }

            // 2. Tahun Ajaran Aktif
            $activeYear = null;
            if ($academicYearId) {
                $activeYear = $this->getAcademicYearModel()->find($academicYearId);
            }
            if (!$activeYear) {
                $activeYear = $this->getAcademicYearModel()->getActiveYear();
            }
            $resolvedYearId = $activeYear ? (int)$activeYear['id'] : null;

            // 3. Profil Satuan Pendidikan
            $school = $this->getSchoolModel()->getProfile() ?? [];
            $schoolLevel = (int)($school['level'] ?? 1);
            $jenjang = ($schoolLevel === 1) ? 'SD' : (($schoolLevel === 2) ? 'SMP' : 'SMA/SMK');
            $schoolName = trim((string)($school['name'] ?? 'Satuan Pendidikan'));
            $schoolCity = trim((string)($school['city_regency'] ?? ''));

            // Ekstrak Visi & Misi jika tersedia (ringkas, hapus HTML & spasi berlebih)
            $visionText = !empty($school['vision']) ? trim(strip_tags((string)$school['vision'])) : null;
            $missionText = !empty($school['mission']) ? trim(strip_tags((string)$school['mission'])) : null;

            // 4. Informasi Kelas & Ukuran Rombel Agregat
            $classInfo = null;
            $className = '-';
            $classLevel = null;
            if ($resolvedClassId) {
                $classData = $this->getClassModel()->find($resolvedClassId);
                if ($classData) {
                    $classInfo = $classData;
                    $className = $classData['name'] ?? '-';
                    $classLevel = $classData['level'] ?? null;
                }
            }
            $classSizeInfo = $this->getClassSizeInfo($resolvedClassId, $resolvedYearId);

            // 5. Informasi Mata Pelajaran
            $subject = $this->getSubjectModel()->find($subjectId);
            $subjectName = $subject['name'] ?? '-';
            $subjectCode = $subject['code'] ?? null;

            // 6. Elemen CP dan TP Terkait
            $cpList = $this->loadCpListBySubjectAndAtp($subjectId, $atpId, $atpRaw);
            $tpList = $this->loadTpListByAtp($atpId, $subjectId);

            // 7. KKTP (Kriteria Ketercapaian Tujuan Pembelajaran) jika ada
            $kktpList = [];
            if ($resolvedClassId) {
                $kktpList = $this->loadKktpByClassSubjectTp($resolvedClassId, $subjectId, array_column($tpList, 'id'));
            }

            // 8. Fasilitas Fisik Terkonfirmasi
            $facilityInfo = $this->getConfirmedFacilities();

            // 9. Rencana Jadwal & Alokasi Sesi
            $totalJp = (int)($atpRaw['alokasi_waktu'] ?? 4);
            $alokasiJadwal = $this->resolveAlokasiJadwal(
                $resolvedClassId,
                $subjectId,
                $atpId,
                $totalJp,
                $schoolLevel,
                $resolvedYearId
            );

            // 10. Susun Panduan Kontekstual Anti-Halusinasi
            $guidelines = $this->buildContextualGuidelines(
                $jenjang,
                $facilityInfo,
                $classSizeInfo,
                !empty($kktpList)
            );

            return $this->successResponse([
                'sekolah' => [
                    'nama'          => $schoolName,
                    'jenjang'       => $jenjang,
                    'level_code'    => $schoolLevel,
                    'wilayah'       => $schoolCity,
                    'visi'          => $visionText,
                    'misi'          => $missionText,
                    'fasilitas'     => $facilityInfo['facilities'],
                    'has_fasilitas' => $facilityInfo['has_data'],
                ],
                'kelas' => [
                    'id'               => $resolvedClassId,
                    'nama'             => $className,
                    'tingkat'          => $classLevel,
                    'jumlah_siswa'     => $classSizeInfo['count'] ?? null,
                    'has_jumlah_siswa' => $classSizeInfo['available'] ?? false,
                ],
                'akademik' => [
                    'tahun_ajaran_id' => $resolvedYearId,
                    'tahun_ajaran'    => $activeYear['year'] ?? null,
                    'semester'        => $activeYear['semester'] ?? ($atpRaw['semester'] ?? 1),
                    'mapel_id'        => $subjectId,
                    'mapel_nama'      => $subjectName,
                    'mapel_kode'      => $subjectCode,
                    'guru_id'         => $teacherId,
                ],
                'kurikulum' => [
                    'atp_id'          => $atpId,
                    'materi_pokok'    => $atpRaw['lingkup_materi'] ?? '-',
                    'total_jp'        => $totalJp,
                    'cp_list'         => $cpList,
                    'tp_list'         => $tpList,
                    'kktp_list'       => $kktpList,
                    'has_kktp'        => !empty($kktpList),
                ],
                'jadwal' => $alokasiJadwal,
                'panduan_kontekstual' => $guidelines,
            ], 'Konteks Modul Ajar berhasil dibangun.');
        } catch (\Throwable $e) {
            $this->logError('AiContextBuilder::buildModulAjarContext', $e->getMessage(), $e);
            return $this->errorResponse('Terjadi kesalahan internal saat membangun konteks Modul Ajar.', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 2. KOKURIKULER CONTEXT BUILDER
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Membangun payload konteks komprehensif untuk generator Kokurikuler
     *
     * @param array $document Data dokumen kokurikuler dari tabel kokurikuler_documents
     * @return array
     */
    public function buildKokurikulerContext(array $document): array
    {
        try {
            if (empty($document['id'])) {
                return $this->errorResponse('Dokumen Kokurikuler tidak valid.', ['error_code' => 'INVALID_DOCUMENT']);
            }

            // 1. Profil Satuan Pendidikan
            $school = $this->getSchoolModel()->getProfile() ?? [];
            $schoolLevel = (int)($school['level'] ?? 1);
            $jenjang = ($schoolLevel === 1) ? 'SD' : (($schoolLevel === 2) ? 'SMP' : 'SMA/SMK');
            $schoolName = trim((string)($school['name'] ?? 'Satuan Pendidikan'));
            $schoolCity = trim((string)($school['city_regency'] ?? ''));

            // 2. Normalisasi Dimensi Profil Lulusan & Subdimensi Resmi
            $rawDimensi = is_string($document['dimensi_profil'] ?? '')
                ? json_decode($document['dimensi_profil'], true)
                : ($document['dimensi_profil'] ?? []);

            $selectedDimensi = is_array($rawDimensi) ? array_values(array_filter(array_map('trim', $rawDimensi))) : [];
            $officialSubDimensiMap = class_exists(SubDimensiProfilLulusan::class)
                ? SubDimensiProfilLulusan::getSubDimensi()
                : [];

            $dimensiDetails = [];
            foreach ($selectedDimensi as $dimName) {
                $subDimList = $officialSubDimensiMap[$dimName] ?? [];
                $dimensiDetails[] = [
                    'dimensi'      => $dimName,
                    'sub_dimensi'  => $subDimList,
                    'has_official_sub' => !empty($subDimList),
                ];
            }

            // 3. Normalisasi Kemitraan
            $kemitraanList = [];
            if (!empty($document['kemitraan'])) {
                $rawK = $document['kemitraan'];
                $decodedK = is_string($rawK) ? json_decode($rawK, true) : $rawK;
                if (is_array($decodedK)) {
                    $kemitraanList = array_values(array_filter(array_map('trim', $decodedK)));
                } elseif (is_string($rawK) && trim($rawK) !== '') {
                    $kemitraanList = array_values(array_filter(array_map('trim', explode(',', $rawK))));
                }
            }
            $isInternalOnly = empty($kemitraanList);
            $kemitraanText = !$isInternalOnly
                ? implode(', ', $kemitraanList)
                : 'Internal warga sekolah (guru, tenaga kependidikan, dan sesama siswa)';

            // 4. Normalisasi Teknologi Digital
            $teknologiList = [];
            if (!empty($document['teknologi_digital'])) {
                $rawT = $document['teknologi_digital'];
                $decodedT = is_string($rawT) ? json_decode($rawT, true) : $rawT;
                if (is_array($decodedT)) {
                    $teknologiList = array_values(array_filter(array_map('trim', $decodedT)));
                } elseif (is_string($rawT) && trim($rawT) !== '') {
                    $teknologiList = array_values(array_filter(array_map('trim', explode(',', $rawT))));
                }
            }
            $isPhysicalMediaOnly = empty($teknologiList);
            $teknologiText = !$isPhysicalMediaOnly
                ? implode(', ', $teknologiList)
                : 'Media fisik konvensional / tanpa kewajiban perangkat digital mandiri bagi siswa';

            // 5. Fasilitas Sekolah Terdaftar
            $facilityInfo = $this->getConfirmedFacilities();

            // 6. Detail Kegiatan & Penanganan Khusus Berdasarkan Jenis
            $jenisKokurikuler = $document['jenis_kokurikuler'] ?? ($document['bentuk_kegiatan'] ?? 'lainnya');
            $kegiatanDetailInfo = $this->resolveKokurikulerDetail($jenisKokurikuler, $document['kegiatan_detail'] ?? null);

            $jumlahPertemuan = max(1, (int)($document['jumlah_pertemuan'] ?? 1));

            // 7. Panduan Anti-Halusinasi Khusus Kokurikuler
            $antiHallucinationRules = [
                'kemitraan_strict' => $isInternalOnly
                    ? 'Mitra HANYA lingkungan internal warga sekolah. DILARANG KERAS merancang kunjungan ke luar, instansi dinas, atau narasumber profesional berbayar.'
                    : "Libatkan HANYA mitra yang terdaftar ({$kemitraanText}). DILARANG mengarang keterlibatan instansi atau pihak luar lain.",
                'teknologi_strict' => $isPhysicalMediaOnly
                    ? 'HANYA gunakan media fisik/bahan konvensional di lingkungan sekolah. DILARANG mewajibkan HP/laptop pribadi siswa, editing Canva mandiri, atau pembuatan konten medsos berbayar.'
                    : "Dukungan teknologi dibatasi pada yang terdaftar ({$teknologiText}). Sertakan alternatif non-digital jika ada kendala perangkat.",
                'sarana_sekolah' => $facilityInfo['has_data']
                    ? 'Fasilitas pendukung sekolah yang dapat diintegrasikan: ' . implode(', ', $facilityInfo['facilities']) . '.'
                    : 'Gunakan fasilitas ruang kelas standar dan area terbuka sekolah. DILARANG mengasumsikan ketersediaan laboratorium/peralatan khusus yang tidak tercatat.',
            ];

            return $this->successResponse([
                'sekolah' => [
                    'nama'          => $schoolName,
                    'jenjang'       => $jenjang,
                    'level_code'    => $schoolLevel,
                    'wilayah'       => $schoolCity,
                    'fasilitas'     => $facilityInfo['facilities'],
                    'has_fasilitas' => $facilityInfo['has_data'],
                ],
                'dokumen' => [
                    'id'                      => (int)$document['id'],
                    'fase'                    => $document['fase'] ?? '-',
                    'level_kelas'             => $document['level_kelas'] ?? '-',
                    'class_id'                => !empty($document['class_id']) ? (int)$document['class_id'] : null,
                    'jumlah_pertemuan'        => $jumlahPertemuan,
                    'tema'                    => $document['tema'] ?? '-',
                    'jenis_kokurikuler'       => $jenisKokurikuler,
                    'bentuk_kegiatan_konkret' => trim((string)($document['bentuk_kegiatan_konkret'] ?? '')),
                ],
                'profil_lulusan' => [
                    'dimensi_list'    => $selectedDimensi,
                    'dimensi_details' => $dimensiDetails,
                ],
                'sumber_daya' => [
                    'kemitraan_list'         => $kemitraanList,
                    'kemitraan_text'         => $kemitraanText,
                    'is_internal_only'       => $isInternalOnly,
                    'teknologi_list'         => $teknologiList,
                    'teknologi_text'         => $teknologiText,
                    'is_physical_media_only' => $isPhysicalMediaOnly,
                ],
                'detail_kegiatan' => $kegiatanDetailInfo,
                'panduan_anti_halusinasi' => $antiHallucinationRules,
            ], 'Konteks Kokurikuler berhasil dibangun.');
        } catch (\Throwable $e) {
            $this->logError('AiContextBuilder::buildKokurikulerContext', $e->getMessage(), $e);
            return $this->errorResponse('Terjadi kesalahan internal saat membangun konteks Kokurikuler.', [
                'exception' => $e->getMessage()
            ]);
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 3. GET CONFIRMED FACILITIES
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mengambil daftar fasilitas fisik sekolah yang terkonfirmasi dari database
     *
     * @return array ['success' => bool, 'facilities' => string[], 'count' => int, 'has_data' => bool]
     */
    public function getConfirmedFacilities(): array
    {
        try {
            // Periksa keberadaan tabel landing_facilities
            if (!$this->db->tableExists('landing_facilities')) {
                return [
                    'success'    => true,
                    'facilities' => [],
                    'count'      => 0,
                    'has_data'   => false,
                    'status'     => 'TABLE_NOT_FOUND',
                ];
            }

            $rows = $this->db->table('landing_facilities')
                ->select('name, description')
                ->where('name IS NOT NULL')
                ->get()
                ->getResultArray();

            $facilities = [];
            foreach ($rows as $row) {
                $name = trim((string)($row['name'] ?? ''));
                if ($name !== '') {
                    $facilities[] = $name;
                }
            }

            $uniqueFacilities = array_values(array_unique($facilities));

            return [
                'success'    => true,
                'facilities' => $uniqueFacilities,
                'count'      => count($uniqueFacilities),
                'has_data'   => !empty($uniqueFacilities),
                'status'     => !empty($uniqueFacilities) ? 'DATA_FOUND' : 'DATA_EMPTY',
            ];
        } catch (\Throwable $e) {
            $this->logError('AiContextBuilder::getConfirmedFacilities', $e->getMessage(), $e);
            return [
                'success'    => false,
                'facilities' => [],
                'count'      => 0,
                'has_data'   => false,
                'status'     => 'QUERY_ERROR',
                'error'      => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // 4. GET CLASS SIZE INFO (Zero PII Aggregate)
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Mengambil jumlah siswa agregat dalam rombel tanpa memuat data pribadi apapun
     *
     * @param int|null $classId ID Rombel / Kelas
     * @param int|null $academicYearId ID Tahun Ajaran (opsional)
     * @return array
     */
    public function getClassSizeInfo(?int $classId, ?int $academicYearId = null): array
    {
        if (empty($classId)) {
            return [
                'available' => false,
                'count'     => null,
                'reason'    => 'CLASS_ID_NULL',
            ];
        }

        try {
            if (!$this->db->tableExists('student_records')) {
                return [
                    'available' => false,
                    'count'     => null,
                    'reason'    => 'TABLE_NOT_FOUND',
                ];
            }

            // Tentukan tahun ajaran jika belum di-passing
            $yearId = $academicYearId;
            if (!$yearId) {
                $activeYear = $this->getAcademicYearModel()->getActiveYear();
                $yearId = $activeYear ? (int)$activeYear['id'] : null;
            }

            $builder = $this->db->table('student_records')
                ->where('class_id', $classId)
                ->where('status', 'aktif');

            if ($yearId) {
                $builder->where('academic_year_id', $yearId);
            }

            $count = (int)$builder->countAllResults();

            if ($count > 0) {
                return [
                    'available'        => true,
                    'count'            => $count,
                    'class_id'         => $classId,
                    'academic_year_id' => $yearId,
                    'guideline'        => "Rombongan belajar ini memiliki {$count} peserta didik aktif. Rancang manajemen kelas dan alokasi tugas agar seluruh {$count} siswa terlibat secara terukur dalam alokasi waktu yang tersedia.",
                ];
            }

            return [
                'available' => false,
                'count'     => null,
                'reason'    => 'NO_ACTIVE_RECORDS',
            ];
        } catch (\Throwable $e) {
            $this->logError('AiContextBuilder::getClassSizeInfo', $e->getMessage(), $e);
            return [
                'available' => false,
                'count'     => null,
                'reason'    => 'QUERY_ERROR',
                'error'     => $e->getMessage(),
            ];
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // PRIVATE / PROTECTED HELPER METHODS
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Memuat daftar Capaian Pembelajaran (CP) terkait ATP
     */
    protected function loadCpListBySubjectAndAtp(int $subjectId, int $atpId, array $atpRaw): array
    {
        $cpList = [];

        // 1. Cek relasi via tabel atp_elemen (struktur baru multi-elemen)
        if ($this->db->tableExists('atp_elemen')) {
            $elements = $this->db->table('atp_elemen')
                ->select('cp_master.elemen, cp_master.deskripsi as cp_deskripsi')
                ->join('cp_master', 'cp_master.id = atp_elemen.cp_master_id', 'left')
                ->where('atp_elemen.atp_id', $atpId)
                ->orderBy('atp_elemen.urutan', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($elements as $el) {
                if (!empty($el['elemen']) || !empty($el['cp_deskripsi'])) {
                    $cpList[] = [
                        'elemen'       => trim((string)($el['elemen'] ?? '')),
                        'cp_deskripsi' => trim((string)($el['cp_deskripsi'] ?? '')),
                    ];
                }
            }
        }

        // 2. Fallback via cp_master_id langsung pada tabel alur_tujuan_pembelajaran (struktur lama)
        if (empty($cpList) && !empty($atpRaw['cp_master_id'])) {
            $cpRow = $this->getCpMasterModel()->find($atpRaw['cp_master_id']);
            if ($cpRow) {
                $cpList[] = [
                    'elemen'       => trim((string)($cpRow['elemen'] ?? '')),
                    'cp_deskripsi' => trim((string)($cpRow['deskripsi'] ?? '')),
                ];
            }
        }

        return $cpList;
    }

    /**
     * Memuat daftar Tujuan Pembelajaran (TP) terkait ATP
     */
    protected function loadTpListByAtp(int $atpId, int $subjectId): array
    {
        $tpList = [];

        // Cari TP yang terhubung ke ATP ini
        $rows = $this->getTpModel()
            ->where('atp_id', $atpId)
            ->orderBy('id', 'ASC')
            ->findAll();

        foreach ($rows as $tp) {
            $tpList[] = [
                'id'        => (int)$tp['id'],
                'kode_tp'   => trim((string)($tp['kode_tp'] ?? '')),
                'deskripsi' => trim((string)($tp['deskripsi'] ?? '')),
                'elemen'    => trim((string)($tp['elemen'] ?? '')),
                'fase'      => trim((string)($tp['fase'] ?? '')),
                'kelas'     => trim((string)($tp['kelas'] ?? '')),
            ];
        }

        return $tpList;
    }

    /**
     * Memuat data KKTP yang cocok dengan kelas, mapel, dan TP
     */
    protected function loadKktpByClassSubjectTp(int $classId, int $subjectId, array $tpIds): array
    {
        if (empty($tpIds) || !$this->db->tableExists('kktp')) {
            return [];
        }

        $validTpIds = array_values(array_filter(array_map('intval', $tpIds)));
        if (empty($validTpIds)) {
            return [];
        }

        $rows = $this->db->table('kktp')
            ->where('class_id', $classId)
            ->where('subject_id', $subjectId)
            ->whereIn('tp_id', $validTpIds)
            ->get()
            ->getResultArray();

        $kktpList = [];
        foreach ($rows as $row) {
            $kktpList[] = [
                'tp_id'               => (int)$row['tp_id'],
                'tujuan_pembelajaran' => trim((string)($row['tujuan_pembelajaran'] ?? '')),
                'kriteria' => array_filter([
                    [
                        'interval'   => $row['kriteria_1_interval'] ?? '',
                        'label'      => $row['kriteria_1_label'] ?? '',
                        'intervensi' => $row['kriteria_1_intervensi'] ?? '',
                    ],
                    [
                        'interval'   => $row['kriteria_2_interval'] ?? '',
                        'label'      => $row['kriteria_2_label'] ?? '',
                        'intervensi' => $row['kriteria_2_intervensi'] ?? '',
                    ],
                    [
                        'interval'   => $row['kriteria_3_interval'] ?? '',
                        'label'      => $row['kriteria_3_label'] ?? '',
                        'intervensi' => $row['kriteria_3_intervensi'] ?? '',
                    ],
                    [
                        'interval'   => $row['kriteria_4_interval'] ?? '',
                        'label'      => $row['kriteria_4_label'] ?? '',
                        'intervensi' => $row['kriteria_4_intervensi'] ?? '',
                    ],
                ], fn($k) => !empty($k['label']) || !empty($k['interval'])),
            ];
        }

        return $kktpList;
    }

    /**
     * Menghitung dan menyelesaikan alokasi jadwal pelajaran
     */
    protected function resolveAlokasiJadwal(
        ?int $classId,
        int $subjectId,
        int $atpId,
        int $totalJp,
        int $schoolLevel,
        ?int $academicYearId
    ): array {
        if (function_exists('helper')) {
            helper('jp');
        }

        $jenjang = ($schoolLevel === 1) ? 'SD' : (($schoolLevel === 2) ? 'SMP' : 'SMA/SMK');
        $menitPerJpStandard = ($schoolLevel === 1) ? 35 : (($schoolLevel === 2) ? 40 : 45);

        $namaHariIndo = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        // TIER 1: Cek Jadwal Riil di Tabel schedules
        $schedules = [];
        if (!empty($classId) && !empty($subjectId) && $this->db->tableExists('schedules')) {
            $builder = $this->db->table('schedules')
                ->where('class_id', $classId)
                ->where('subject_id', $subjectId);
            if ($academicYearId) {
                $builder->where('academic_year_id', $academicYearId);
            }
            $schedules = $builder
                ->orderBy('day_of_week', 'ASC')
                ->orderBy('start_time', 'ASC')
                ->get()
                ->getResultArray();
        }

        $jadwalSlots = [];
        foreach ($schedules as $sc) {
            $slotMenit = 0;
            if (!empty($sc['start_time']) && !empty($sc['end_time'])) {
                $slotMenit = max(0, (strtotime($sc['end_time']) - strtotime($sc['start_time'])) / 60);
            }

            if (function_exists('hitung_jp_dari_waktu') && !empty($sc['start_time']) && !empty($sc['end_time'])) {
                $slotJp = hitung_jp_dari_waktu($sc['start_time'], $sc['end_time'], $schoolLevel);
            } elseif ($slotMenit > 0) {
                $slotJp = max(1, (int)round($slotMenit / $menitPerJpStandard));
            } else {
                $slotJp = 2;
            }

            if ($slotMenit <= 0) {
                $slotMenit = $slotJp * $menitPerJpStandard;
            }

            $hariNama = $namaHariIndo[(int)($sc['day_of_week'] ?? 1)] ?? 'Hari Belajar';
            $jadwalSlots[] = [
                'hari'  => $hariNama,
                'jp'    => $slotJp,
                'menit' => (int)$slotMenit,
                'start' => substr((string)$sc['start_time'], 0, 5),
                'end'   => substr((string)$sc['end_time'], 0, 5),
            ];
        }

        $pertemuanList = [];
        $tierDigunakan = 'standar_jenjang';

        if (!empty($jadwalSlots)) {
            $tierDigunakan = 'jadwal_riil';
            $sisaJp = $totalJp;
            $slotIndex = 0;
            $slotCount = count($jadwalSlots);
            $pNo = 1;

            while ($sisaJp > 0) {
                if ($pNo > 40) break;
                $currentSlot = $jadwalSlots[$slotIndex % $slotCount];
                if (($currentSlot['jp'] ?? 0) <= 0) break;

                $jpThis = min($currentSlot['jp'], $sisaJp);
                $menitThis = (int)round(($jpThis / max(1, $currentSlot['jp'])) * $currentSlot['menit']);
                if ($menitThis < 25) {
                    $menitThis = $jpThis * $menitPerJpStandard;
                }

                $pertemuanList[] = [
                    'pertemuan' => $pNo,
                    'hari'      => $currentSlot['hari'],
                    'jp'        => $jpThis,
                    'menit'     => $menitThis,
                ];

                $sisaJp -= $jpThis;
                $slotIndex++;
                $pNo++;
            }

            $jumlahPertemuan = count($pertemuanList);
            $jpPerPertemuan = (int)round(array_sum(array_column($pertemuanList, 'jp')) / max(1, $jumlahPertemuan));
            if ($jpPerPertemuan < 1) $jpPerPertemuan = 1;

            $slotDesc = implode(', ', array_map(function ($s) {
                return "{$s['hari']} ({$s['jp']} JP / {$s['menit']} menit, {$s['start']}-{$s['end']})";
            }, $jadwalSlots));
            $jadwalKonteksInfo = "Dihitung dari Jadwal Pelajaran Riil Kelas ({$slotCount} sesi tatap muka per pekan: {$slotDesc}). Total materi {$totalJp} JP diselesaikan dalam {$jumlahPertemuan} Pertemuan.";
        } else {
            // TIER 2: Cek Distribusi Program Semester (Promes) jika ada
            $prosemRows = [];
            if ($this->db->tableExists('prosem_distributions')) {
                $prosemRows = $this->db->table('prosem_distributions')
                    ->where('atp_id', $atpId)
                    ->where('jp >', 0)
                    ->orderBy('id', 'ASC')
                    ->get()
                    ->getResultArray();
            }

            if (!empty($prosemRows)) {
                $tierDigunakan = 'promes';
                $sumJp = (int)array_sum(array_column($prosemRows, 'jp'));
                $totalWeeks = count($prosemRows);
                $jpPerMinggu = (int)round($sumJp / max(1, $totalWeeks));
                if ($jpPerMinggu <= 0) $jpPerMinggu = 2;

                $pertemuanPerMinggu = ($jpPerMinggu <= 3) ? 1 : 2;
                $jpPerPertemuan = max(1, (int)round($jpPerMinggu / $pertemuanPerMinggu));

                $sisaJp = $totalJp;
                $pNo = 1;
                while ($sisaJp > 0) {
                    if ($pNo > 40) break;
                    $jpThis = min($jpPerPertemuan, $sisaJp);
                    $menitThis = $jpThis * $menitPerJpStandard;
                    $pertemuanList[] = [
                        'pertemuan' => $pNo,
                        'hari'      => 'Pertemuan ke-' . $pNo,
                        'jp'        => $jpThis,
                        'menit'     => $menitThis,
                    ];
                    $sisaJp -= $jpThis;
                    $pNo++;
                }
                $jumlahPertemuan = count($pertemuanList);
                $jadwalKonteksInfo = "Dihitung dari Program Semester ({$totalWeeks} pekan efektif, rata-rata {$jpPerMinggu} JP/minggu). Materi {$totalJp} JP dialokasikan ke {$jumlahPertemuan} Pertemuan.";
            } else {
                // TIER 3: Standar Kurikulum berdasarkan Jenjang
                $tierDigunakan = 'standar_jenjang';
                if ($totalJp <= 3) {
                    $jpPerPertemuan = $totalJp;
                } elseif ($totalJp % 2 === 0) {
                    $jpPerPertemuan = 2;
                } elseif ($totalJp % 3 === 0) {
                    $jpPerPertemuan = 3;
                } else {
                    $jpPerPertemuan = 2;
                }

                $sisaJp = $totalJp;
                $pNo = 1;
                while ($sisaJp > 0) {
                    if ($pNo > 40) break;
                    $jpThis = min($jpPerPertemuan, $sisaJp);
                    $menitThis = $jpThis * $menitPerJpStandard;
                    $pertemuanList[] = [
                        'pertemuan' => $pNo,
                        'hari'      => 'Pertemuan ke-' . $pNo,
                        'jp'        => $jpThis,
                        'menit'     => $menitThis,
                    ];
                    $sisaJp -= $jpThis;
                    $pNo++;
                }
                $jumlahPertemuan = count($pertemuanList);
                $jadwalKonteksInfo = "Dihitung dari Standar Kurikulum Jenjang {$jenjang} (1 JP = {$menitPerJpStandard} menit). Materi {$totalJp} JP dialokasikan ke {$jumlahPertemuan} Pertemuan.";
            }
        }

        foreach ($pertemuanList as &$pItem) {
            $pMenit = (int)$pItem['menit'];
            if ($pMenit < 35) {
                $pItem['meaningfulMenit'] = max(5, $pMenit - 10);
            } else {
                $pItem['meaningfulMenit'] = max(10, $pMenit - 25);
            }
        }
        unset($pItem);

        $isUniform = true;
        $firstMenit = $pertemuanList[0]['menit'] ?? ($menitPerJpStandard * 2);
        foreach ($pertemuanList as $p) {
            if ($p['menit'] !== $firstMenit) {
                $isUniform = false;
                break;
            }
        }

        $menitPerPertemuan = $firstMenit;

        return [
            'jumlah_pertemuan'    => $jumlahPertemuan,
            'jp_per_pertemuan'     => $jpPerPertemuan,
            'menit_per_pertemuan'  => $menitPerPertemuan,
            'pertemuan_list'       => $pertemuanList,
            'is_uniform'          => $isUniform,
            'jadwal_info'         => $jadwalKonteksInfo,
            'tier'                => $tierDigunakan,
        ];
    }

    /**
     * Menyelesaikan rincian detail kegiatan Kokurikuler
     */
    protected function resolveKokurikulerDetail(string $jenis, $rawDetail): array
    {
        $detail = is_string($rawDetail ?? '') ? json_decode($rawDetail, true) : ($rawDetail ?? []);
        $summary = '';

        if ($jenis === 'lintas_disiplin') {
            $subjectNames = [];
            $validSubjects = is_array($detail['subjects'] ?? null)
                ? array_values(array_filter($detail['subjects']))
                : [];

            if (!empty($validSubjects)) {
                $subList = $this->getSubjectModel()->whereIn('id', $validSubjects)->findAll();
                foreach ($subList as $s) {
                    $subjectNames[] = $s['name'];
                }
            }

            $summary = !empty($subjectNames)
                ? 'Kolaborasi lintas mata pelajaran: ' . implode(', ', $subjectNames) . '.'
                : 'Kegiatan melibatkan kolaborasi lintas disiplin ilmu dari beberapa mata pelajaran.';

            $tpDescs = [];
            if (!empty($detail['items']) && is_array($detail['items'])) {
                $tpIds = array_values(array_unique(array_filter(array_column($detail['items'], 'tp_id'))));
                if (!empty($tpIds)) {
                    $tpList = $this->getTpModel()->whereIn('id', $tpIds)->findAll();
                    foreach ($tpList as $t) {
                        $tpDescs[] = ($t['kode_tp'] ? $t['kode_tp'] . '. ' : '') . $t['deskripsi'];
                    }
                }
            }

            return [
                'jenis'          => 'lintas_disiplin',
                'summary'        => $summary,
                'subjects'       => $subjectNames,
                'tp_sasaran'     => $tpDescs,
                'raw_items'      => $detail['items'] ?? [],
            ];
        }

        if ($jenis === '7kaih') {
            $kaihItems = [];
            if (isset($detail['items']) && is_array($detail['items'])) {
                $kaihItems = array_values(array_filter(array_column($detail['items'], 'kaih')));
            }
            $summary = !empty($kaihItems)
                ? 'Kegiatan 7 KAIH yang dipilih: ' . implode(', ', $kaihItems)
                : 'Kegiatan pembiasaan 7 Karakter Anak Indonesia Hebat (KAIH).';

            return [
                'jenis'     => '7kaih',
                'summary'   => $summary,
                'kaih_list' => $kaihItems,
            ];
        }

        // Jenis 'lainnya'
        $textDetail = is_array($detail) ? ($detail['text'] ?? '-') : (string)($rawDetail ?? '-');
        return [
            'jenis'   => 'lainnya',
            'summary' => trim($textDetail) !== '' ? trim($textDetail) : '-',
        ];
    }

    /**
     * Membangun string panduan kontekstual anti-halusinasi untuk Modul Ajar
     */
    protected function buildContextualGuidelines(
        string $jenjang,
        array $facilityInfo,
        array $classSizeInfo,
        bool $hasKktp
    ): array {
        // Panduan Sarana Fisik
        $saranaGuideline = $facilityInfo['has_data']
            ? 'Sarana fisik pembelajaran nyata yang terkonfirmasi tersedia di sekolah: ' . implode(', ', $facilityInfo['facilities']) . '. Prioritaskan rancangan aktivitas yang memanfaatkan sarana tersebut.'
            : 'Sekolah ini beroperasi dengan fasilitas ruang kelas standar (papan tulis, buku pegangan, LKPD cetak/tulis, dan lingkungan sekitar kelas). DILARANG merancang aktivitas yang mensyaratkan laboratorium khusus, perangkat gawai per siswa, atau internet berkecepatan tinggi.';

        // Panduan Skala Kelas
        $skalaKelasGuideline = ($classSizeInfo['available'] && !empty($classSizeInfo['count']))
            ? "Rombongan belajar ini memiliki {$classSizeInfo['count']} peserta didik aktif. Rancang kelompok belajar dan durasi giliran kerja secara proporsional agar seluruh {$classSizeInfo['count']} siswa tuntas menyelesaikan tahap Meaningful dalam alokasi waktu yang tersedia."
            : 'Rancang manajemen kelompok belajar fleksibel berukuran 4-5 peserta didik yang dapat disesuaikan dengan kapasitas kelas jenjang ' . $jenjang . '.';

        // Panduan Kriteria Asesmen
        $kktpGuideline = $hasKktp
            ? 'Kriteria Ketercapaian Tujuan Pembelajaran (KKTP) resmi telah terdaftar di sistem. Selaraskan deskripsi kriteria kelulusan dan asesmen dengan indikator ketuntasan yang ditentukan.'
            : 'Rumuskan kriteria ketercapaian secara realistis berdasarkan rubrik observasi proses kerja kelompok dan bukti karya formatif.';

        return [
            'sarana'      => $saranaGuideline,
            'skala_kelas' => $skalaKelasGuideline,
            'kktp'        => $kktpGuideline,
        ];
    }
}
