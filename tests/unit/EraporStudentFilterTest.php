<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class EraporStudentFilterTest extends TestCase
{
    private string $eraporControllerCode;

    protected function setUp(): void
    {
        parent::setUp();
        $filePath = dirname(__DIR__, 2) . '/app/Controllers/Admin/Erapor.php';
        $this->eraporControllerCode = file_get_contents($filePath);
    }

    public function testInputMethodFiltersActiveStudentsAndAcademicYear(): void
    {
        $this->assertStringContainsString("->where('sr.status', 'aktif')", $this->eraporControllerCode);
        $this->assertStringContainsString('->where(\'sr.academic_year_id\', $yearId)', $this->eraporControllerCode);
    }

    public function testYearIdResolutionSafelyDefaultsToActiveYear(): void
    {
        // Must ensure empty(?year_id=) does not evaluate to blank string
        $pattern = '/\$yearId\s*=\s*\$this->request->getGet\(\'year_id\'\);\s*if\s*\(empty\(\$yearId\)\s*\|\|\s*!is_numeric\(\$yearId\)\)\s*\{\s*\$yearId\s*=\s*\$activeYear\[\'id\'\]\s*\?\?\s*null;\s*\}/';
        $this->assertMatchesRegularExpression($pattern, $this->eraporControllerCode);
    }

    public function testLegerAndImportFilterActiveStudents(): void
    {
        // Check legerView, printLeger and import have active filtering
        $this->assertMatchesRegularExpression('/legerView[\s\S]*?where\(\'sr\.status\',\s*\'aktif\'\)/', $this->eraporControllerCode);
        $this->assertMatchesRegularExpression('/printLeger[\s\S]*?where\(\'sr\.status\',\s*\'aktif\'\)/', $this->eraporControllerCode);
        $this->assertMatchesRegularExpression('/importParse[\s\S]*?where\(\'sr\.status\',\s*\'aktif\'\)/', $this->eraporControllerCode);
    }
}
