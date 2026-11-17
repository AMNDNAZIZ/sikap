<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Config\Database;
use App\Services\Ai\OperationalContextProvider;
use App\Services\Ai\DTO\OperationalContextDTO;

/**
 * Unit & Integration Test untuk OperationalContextProvider (Fase 1D.3 - Tahap 1)
 * 
 * Menguji sekurang-kurangnya 15 skenario pemetaan konteks operasional,
 * ketahanan isolasi multi-pengguna, resolusi multi-penugasan, dan penanganan data kosong.
 */
class OperationalContextProviderTest extends CIUnitTestCase
{
    protected $db;
    protected OperationalContextProvider $provider;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = Database::connect('default');
        $this->provider = new OperationalContextProvider($this->db);
    }

    /**
     * Skenario 1: Guru dengan satu penugasan aktif (Guru Fallback: User ID 63, Teacher ID 3)
     */
    public function testTeacherWithSingleAssignment()
    {
        $context = $this->provider->resolveContext(63);

        $this->assertInstanceOf(OperationalContextDTO::class, $context);
        $this->assertEquals(63, $context->userId);
        $this->assertEquals(3, $context->roleId);
        $this->assertTrue($context->isTeacher);
        $this->assertTrue($context->hasAssignments);
        $this->assertCount(1, $context->assignments);
        
        $this->assertFalse($context->isMultiAssignment);
        $this->assertNotNull($context->activeAssignment);
        $this->assertFalse($context->isAmbiguous);
        $this->assertFalse($context->clarificationNeeded);
    }

    /**
     * Skenario 2: Guru dengan beberapa mata pelajaran (User ID 2: Salma Wijaya, Teacher ID 1)
     * Menguji deteksi kata kunci kueri yang mengarah ke mapel spesifik
     */
    public function testTeacherWithMultipleSubjectsAndQueryFocus()
    {
        // 1. Kueri dengan kata kunci spesifik "Matematika"
        $ctxMtk = $this->provider->resolveContext(2, 'Buatkan 5 soal pecahan aljabar untuk Matematika');

        $this->assertTrue($ctxMtk->isTeacher);
        $this->assertTrue($ctxMtk->hasAssignments);
        $this->assertTrue($ctxMtk->isMultiAssignment);
        $this->assertGreaterThan(1, count($ctxMtk->assignments));
        $this->assertContains('Kelas 6A', $ctxMtk->assignedClasses);

        // Harus memilih penugasan Matematika sebagai activeAssignment
        $this->assertNotNull($ctxMtk->activeAssignment);
        $this->assertStringContainsString('Matematika', $ctxMtk->activeAssignment['subject_name']);
        $this->assertFalse($ctxMtk->isAmbiguous);

        // 2. Kueri ambigu tanpa menyebut mapel pada guru multi-penugasan
        $this->provider->clearCache();
        $ctxAmbigu = $this->provider->resolveContext(2, 'Buatkan kisi-kisi soal untuk ujian besok');

        $this->assertTrue($ctxAmbigu->isAmbiguous);
        $this->assertTrue($ctxAmbigu->clarificationNeeded);
        $this->assertNull($ctxAmbigu->activeAssignment);
        $this->assertNotEmpty($ctxAmbigu->clarificationPrompt);
    }

    /**
     * Skenario 3: Guru dengan beberapa kelas berbeda (Teacher ID 5 / User ID 390 di AY 1)
     */
    public function testTeacherWithMultipleClasses()
    {
        $ctx = $this->provider->resolveContext(390, null, [], 1);
        $this->assertTrue($ctx->isTeacher);
        $this->assertGreaterThan(1, count($ctx->assignedClasses));
    }

    /**
     * Skenario 4: Guru tanpa penugasan aktif (User ID 389: Afifah Noer Fauziah, Role 3, 0 KBM di AY 2)
     */
    public function testTeacherWithoutActiveAssignments()
    {
        $context = $this->provider->resolveContext(389);

        $this->assertTrue($context->isTeacher);
        $this->assertFalse($context->hasAssignments);
        $this->assertEquals('NO_ASSIGNMENT', $context->status);
        $this->assertEmpty($context->assignments);
        $this->assertNull($context->activeAssignment);

        // Pastikan teks prompt tidak mengarang mapel
        $promptBlock = $context->toPromptBlock();
        $this->assertStringContainsString('BELUM MEMILIKI PENUGASAN AKTIF', $promptBlock);
    }

    /**
     * Skenario 5: Penugasan dari tahun ajaran sebelumnya tidak tercampur
     */
    public function testAssignmentsFromPastAcademicYearsAreExcluded()
    {
        // Guru 3 memiliki penugasan di AY 1 (lampau) dan AY 2 (aktif)
        $context = $this->provider->resolveContext(63);

        $activeYearId = $context->academicYearId;
        $this->assertNotNull($activeYearId);
        $this->assertEquals(2, $activeYearId);

        foreach ($context->assignments as $a) {
            $this->assertEquals($activeYearId, $a['academic_year_id'], 'Hanya penugasan pada tahun ajaran aktif yang boleh dimasukkan.');
        }
    }

    /**
     * Skenario 6: Penugasan dengan relasi tidak aktif atau hilang diabaikan
     */
    public function testInvalidOrMissingClassSubjectRelationsHandledSafely()
    {
        $context = $this->provider->resolveContext(63);

        foreach ($context->assignments as $a) {
            $this->assertNotEmpty($a['class_name']);
            $this->assertNotEmpty($a['subject_name']);
            $this->assertGreaterThan(0, $a['class_id']);
            $this->assertGreaterThan(0, $a['subject_id']);
        }
    }

    /**
     * Skenario 7: Pengguna Kepala Sekolah (User ID 61, Role 2)
     */
    public function testHeadmasterRoleContext()
    {
        $context = $this->provider->resolveContext(61);

        $this->assertEquals(2, $context->roleId);
        $this->assertEquals('Kepala Sekolah', $context->roleName);
        $this->assertFalse($context->isTeacher);
        $this->assertEquals('NON_TEACHER', $context->status);

        $promptBlock = $context->toPromptBlock();
        $this->assertStringContainsString('Manajerial & Supervisi', $promptBlock);
    }

    /**
     * Skenario 8: Pengguna dengan role lain (Administrator: User ID 1, Role 1)
     */
    public function testAdminRoleContext()
    {
        $context = $this->provider->resolveContext(1);

        $this->assertEquals(1, $context->roleId);
        $this->assertContains($context->roleName, ['Admin', 'Administrator']);
        $this->assertFalse($context->isTeacher);
        $this->assertEquals('NON_TEACHER', $context->status);

        $promptBlock = $context->toPromptBlock();
        $this->assertStringContainsString('Tata Kelola Sistem', $promptBlock);
    }

    /**
     * Skenario 9: Sesi pengguna tidak tersedia (User ID 0 atau invalid)
     */
    public function testAnonymousOrInvalidUserSession()
    {
        $context = $this->provider->resolveContext(0);

        $this->assertEquals(0, $context->userId);
        $this->assertEquals('ANONYMOUS', $context->status);
        $this->assertFalse($context->isTeacher);
    }

    /**
     * Skenario 10: Pemalsuan role / user_id melalui array tidak memengaruhi data server
     */
    public function testPayloadSpoofingRejectedByServerValidation()
    {
        // Pengguna asli adalah User ID 63 (Guru, Role 3)
        // Penyerang mencoba mengirim role_id: 1 (Admin) pada array sesi
        $spoofedSession = [
            'id'       => 63,
            'role_id'  => 1, // Palsu
            'username' => 'hacked_admin',
        ];

        $context = $this->provider->resolveContext($spoofedSession);

        // Server WAJIB mengambil role asli dari DB (Role 3: Guru)
        $this->assertEquals(3, $context->roleId);
        $this->assertEquals('Guru', $context->roleName);
        $this->assertTrue($context->isTeacher);
    }

    /**
     * Skenario 11: Penugasan pengguna lain tidak dapat diakses (Data Isolation)
     */
    public function testCrossTeacherIsolation()
    {
        $ctxA = $this->provider->resolveContext(63); // Guru 3
        $this->provider->clearCache();
        $ctxB = $this->provider->resolveContext(2); // Guru 1

        $this->assertNotEquals($ctxA->teacherId, $ctxB->teacherId);

        $assignmentsA = array_column($ctxA->assignments, 'assignment_id');
        $assignmentsB = array_column($ctxB->assignments, 'assignment_id');

        // Tidak boleh ada penugasan yang tumpang tindih
        $intersection = array_intersect($assignmentsA, $assignmentsB);
        $this->assertEmpty($intersection, 'Data penugasan guru A dan guru B tidak boleh bocor.');
    }

    /**
     * Skenario 12: Kelas dan mapel yang tidak cocok tidak digabungkan
     */
    public function testClassAndSubjectPairingIntegrity()
    {
        $context = $this->provider->resolveContext(2);

        foreach ($context->assignments as $a) {
            $this->assertArrayHasKey('class_id', $a);
            $this->assertArrayHasKey('subject_id', $a);
            $this->assertArrayHasKey('fase', $a);
        }
    }

    /**
     * Skenario 13: Data profil sekolah terisi aman
     */
    public function testSchoolProfileDataPopulated()
    {
        $context = $this->provider->resolveContext(63);

        $this->assertNotEmpty($context->schoolName);
        $this->assertGreaterThan(0, $context->schoolLevel);
        $this->assertNotEmpty($context->schoolLevelLabel);
    }

    /**
     * Skenario 14: Fase kurikulum berhasil dipetakan secara sah
     */
    public function testCurriculumPhaseMapping()
    {
        $context = $this->provider->resolveContext(2);

        foreach ($context->assignments as $a) {
            $level = (int) $a['class_level'];
            $fase = $a['fase'];

            if ($level >= 1 && $level <= 2) {
                $this->assertEquals('A', $fase);
            } elseif ($level >= 3 && $level <= 4) {
                $this->assertEquals('B', $fase);
            } elseif ($level >= 5 && $level <= 6) {
                $this->assertEquals('C', $fase);
            } elseif ($level >= 7 && $level <= 9) {
                $this->assertEquals('D', $fase);
            }
        }
    }

    /**
     * Skenario 15: Penentuan semester menggunakan kalender sah atau fallback eksplisit
     */
    public function testSemesterDeterminationAndFallbackSource()
    {
        $context = $this->provider->resolveContext(63);

        $this->assertContains($context->semester, [1, 2]);
        $this->assertContains($context->semesterLabel, ['Ganjil', 'Genap']);
        $this->assertContains($context->semesterSource, ['academic_calendar', 'calendar_estimate_fallback']);
    }

    /**
     * Skenario 16: Zero Student PII & Zero Secret dalam DTO
     */
    public function testZeroStudentPiiAndZeroSecret()
    {
        $context = $this->provider->resolveContext(2);
        $array = $context->toArray();
        $json = json_encode($array);

        // Tidak boleh ada kunci rahasia
        $this->assertStringNotContainsString('password', $json);
        $this->assertStringNotContainsString('gemini_api_key', $json);
        $this->assertStringNotContainsString('gsk_', $json);

        // Tidak boleh ada NISN atau data siswa
        $this->assertStringNotContainsString('nisn', $json);
        $this->assertStringNotContainsString('students', $json);
    }

    /**
     * Skenario 17: Request-scoped cache mengembalikan objek instan tanpa query berulang
     */
    public function testRequestScopedCache()
    {
        $ctx1 = $this->provider->resolveContext(2, 'soal matematika');
        $ctx2 = $this->provider->resolveContext(2, 'soal matematika');

        $this->assertSame($ctx1, $ctx2, 'Objek DTO dari cache request harus berupa instans yang identik.');
    }
}
