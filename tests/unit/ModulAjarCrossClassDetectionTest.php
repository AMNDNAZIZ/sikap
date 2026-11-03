<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ModulAjarCrossClassDetectionTest extends TestCase
{
    /**
     * Test logic validating that a Modul Ajar created in one class (e.g. 6A)
     * is valid and accepted when creating a teaching journal in another class (e.g. 6B)
     * as long as both classes are of the same level and the teacher matches.
     */
    public function testModulValidationAcrossSameLevelClasses()
    {
        $user = ['role_id' => 3, 'related_id' => 15]; // Teacher ID 15

        $modul = [
            'id'         => 101,
            'class_id'   => 1, // Kelas 6A
            'subject_id' => 5, // Matematika
            'teacher_id' => 15,
            'content'    => '<h2>F. Langkah Pembelajaran</h2><h3>Pertemuan 1 (2 JP)</h3><p>Kegiatan awal...</p>'
        ];

        // Journal being created in Kelas 6B (class_id = 2)
        $journalData = [
            'class_id'   => 2,
            'subject_id' => 5,
        ];

        $classInfoMap = [
            1 => ['id' => 1, 'name' => '6A', 'level' => 6],
            2 => ['id' => 2, 'name' => '6B', 'level' => 6],
            3 => ['id' => 3, 'name' => '5A', 'level' => 5],
        ];

        // 1. Direct class match check
        $matchClassDirect = ((int)$modul['class_id'] === (int)$journalData['class_id']);
        $this->assertFalse($matchClassDirect, 'Direct class ID match should be false between 6A and 6B');

        // 2. Same-level class match check
        $matchClassLevel = false;
        if (!$matchClassDirect) {
            $modulClass = $classInfoMap[$modul['class_id']] ?? null;
            $targetClass = $classInfoMap[$journalData['class_id']] ?? null;
            if ($modulClass && $targetClass && (int)$modulClass['level'] === (int)$targetClass['level']) {
                $matchClassLevel = true;
            }
        }
        $this->assertTrue($matchClassLevel, 'Modul from 6A should match target class 6B because both are level 6');

        // 3. Subject and Teacher authorization check
        $matchSubject = ((int)$modul['subject_id'] === (int)$journalData['subject_id']);
        $matchTeacher = ((int)$modul['teacher_id'] === (int)$user['related_id']);

        $isValidModul = $matchClassLevel && $matchSubject && $matchTeacher;
        $this->assertTrue($isValidModul, 'Modul from 6A should be fully valid for 6B journal by same teacher');

        // 4. Incompatible level test (e.g. 5A vs 6A)
        $journalData5A = ['class_id' => 3, 'subject_id' => 5];
        $matchLevelDiff = false;
        $modulClass = $classInfoMap[$modul['class_id']] ?? null;
        $targetClassDiff = $classInfoMap[$journalData5A['class_id']] ?? null;
        if ($modulClass && $targetClassDiff && (int)$modulClass['level'] === (int)$targetClassDiff['level']) {
            $matchLevelDiff = true;
        }
        $this->assertFalse($matchLevelDiff, 'Modul from Level 6 should NOT match target class in Level 5');
    }

    /**
     * Test that different teachers teaching parallel classes cannot cross-access each other's moduls.
     */
    public function testDifferentTeacherBoundaryPreserved()
    {
        $user = ['role_id' => 3, 'related_id' => 20]; // Different Teacher ID 20

        $modul = [
            'id'         => 101,
            'class_id'   => 1, // Kelas 6A
            'subject_id' => 5,
            'teacher_id' => 15 // Owned by Teacher 15
        ];

        $journalData = [
            'class_id'   => 2, // Kelas 6B
            'subject_id' => 5
        ];

        $classInfoMap = [
            1 => ['id' => 1, 'name' => '6A', 'level' => 6],
            2 => ['id' => 2, 'name' => '6B', 'level' => 6],
        ];

        $modulClass = $classInfoMap[$modul['class_id']];
        $targetClass = $classInfoMap[$journalData['class_id']];
        $matchClass = ((int)$modulClass['level'] === (int)$targetClass['level']);

        $matchTeacher = ((int)$modul['teacher_id'] === (int)$user['related_id']);
        $this->assertFalse($matchTeacher, 'Teacher 20 must NOT be allowed to claim Teacher 15 modul');

        $isValidModul = $matchClass && $matchTeacher;
        $this->assertFalse($isValidModul, 'Cross-teacher modul access must be rejected');
    }

    /**
     * Test detection of Modul Ajar matching by Lingkup Materi when ATP IDs differ between parallel classes.
     */
    public function testLingkupMateriMatchingAcrossDifferentAtpIds()
    {
        // 6A has ATP 12 with "Operasi Hitung Pecahan"
        $atp6A = ['id' => 12, 'class_id' => 1, 'subject_id' => 5, 'lingkup_materi' => 'Operasi Hitung Pecahan', 'semester' => 1];
        // 6B has ATP 45 with "Operasi Hitung Pecahan"
        $atp6B = ['id' => 45, 'class_id' => 2, 'subject_id' => 5, 'lingkup_materi' => 'Operasi Hitung Pecahan ', 'semester' => 1];

        // Modul was created in 6A referencing ATP 12
        $modul6A = ['id' => 101, 'atp_id' => 12, 'class_id' => 1, 'subject_id' => 5, 'lingkup_materi' => 'Operasi Hitung Pecahan'];

        // When user in 6B selects ATP 45:
        $selectedAtpId = 45;
        $materiSelected = trim($atp6B['lingkup_materi']);

        // Direct ID match fails
        $this->assertNotEquals($modul6A['atp_id'], $selectedAtpId);

        // Lingkup Materi semantic match succeeds
        $isMatched = (trim($modul6A['lingkup_materi']) === $materiSelected);
        $this->assertTrue($isMatched, 'Modul Ajar from 6A should match selection in 6B via trimmed Lingkup Materi');
    }
}
