<?php

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use App\Models\CbtTestItemModel;
use App\Models\CbtTestItemVersionModel;
use App\Models\CbtSessionItemModel;
use App\Models\CbtAnswerV3Model;
use App\Models\CbtAnswerQuarantineModel;
use App\Models\CbtMigrationManifestModel;

/**
 * CbtV3FoundationTest
 * Static and unit test suite for ESPATA Assessment Intelligence V3 Stage A.
 */
class CbtV3FoundationTest extends CIUnitTestCase
{
    public function testMigrationFileExistsAndCompiles()
    {
        $migrationPath = APPPATH . 'Database/Migrations/2026-11-01-090001_CreateCbtAssessmentIntelligenceV3Foundation.php';
        $this->assertFileExists($migrationPath);

        $content = file_get_contents($migrationPath);
        $this->assertStringContainsString('class CreateCbtAssessmentIntelligenceV3Foundation', $content);
        $this->assertStringContainsString('cbt_test_items', $content);
        $this->assertStringContainsString('cbt_test_item_versions', $content);
        $this->assertStringContainsString('cbt_session_items', $content);
        $this->assertStringContainsString('cbt_answers_v3', $content);
        $this->assertStringContainsString('cbt_answers_quarantine', $content);
        $this->assertStringContainsString('cbt_migration_manifest', $content);
        $this->assertStringContainsString('ON DELETE RESTRICT ON UPDATE RESTRICT', $content);
        $this->assertStringContainsString('trg_cbt_tiv_immutable', $content);
        $this->assertStringContainsString('trg_cbt_si_immutable', $content);
    }

    public function testModelsCanBeInstantiated()
    {
        $m1 = new CbtTestItemModel();
        $this->assertSame('cbt_test_items', $m1->table);

        $m2 = new CbtTestItemVersionModel();
        $this->assertSame('cbt_test_item_versions', $m2->table);

        $m3 = new CbtSessionItemModel();
        $this->assertSame('cbt_session_items', $m3->table);

        $m4 = new CbtAnswerV3Model();
        $this->assertSame('cbt_answers_v3', $m4->table);

        $m5 = new CbtAnswerQuarantineModel();
        $this->assertSame('cbt_answers_quarantine', $m5->table);

        $m6 = new CbtMigrationManifestModel();
        $this->assertSame('cbt_migration_manifest', $m6->table);
    }

    public function testCompositeKeysAreSpecifiedCorrectly()
    {
        $migrationPath = APPPATH . 'Database/Migrations/2026-11-01-090001_CreateCbtAssessmentIntelligenceV3Foundation.php';
        $content = file_get_contents($migrationPath);

        // Test item composite uniqueness
        $this->assertStringContainsString("UNIQUE KEY `uq_ti_id_test` (`id`, `test_id`)", $content);

        // Item version composite uniqueness
        $this->assertStringContainsString("UNIQUE KEY `uq_tiv_id_item_test` (`id`, `test_item_id`, `test_id`)", $content);

        // Session item composite foreign keys
        $this->assertStringContainsString("CONSTRAINT `fk_si_session_test` FOREIGN KEY (`session_id`, `test_id`)", $content);
        $this->assertStringContainsString("CONSTRAINT `fk_si_version_item_test` FOREIGN KEY (`item_version_id`, `test_item_id`, `test_id`)", $content);

        // Answer composite foreign keys
        $this->assertStringContainsString("CONSTRAINT `fk_ans_session_item` FOREIGN KEY (`session_id`, `item_version_id`)", $content);
        $this->assertStringContainsString("CONSTRAINT `fk_ans_session_student` FOREIGN KEY (`session_id`, `student_id`)", $content);
    }

    public function testQuarantineLosslessColumnsArePresent()
    {
        $migrationPath = APPPATH . 'Database/Migrations/2026-11-01-090001_CreateCbtAssessmentIntelligenceV3Foundation.php';
        $content = file_get_contents($migrationPath);

        $expectedColumns = [
            'legacy_answer_id',
            'student_id',
            'test_id',
            'question_id',
            'answer_raw',
            'score_raw',
            'is_doubtful_raw',
            'version_raw',
            'created_at_raw',
            'updated_at_raw',
            'updated_at_micro_raw',
            'quarantine_reason',
            'quarantine_details_json',
            'canonical_hash',
            'quarantined_at'
        ];

        foreach ($expectedColumns as $col) {
            $this->assertStringContainsString("`{$col}`", $content, "Missing lossless column: {$col}");
        }
    }
}

