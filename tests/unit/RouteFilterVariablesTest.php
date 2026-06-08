<?php

use CodeIgniter\Test\CIUnitTestCase;

/**
 * Verifikasi bahwa variabel filter $auth17, $auth127, $auth1237
 * terdefinisi dengan nilai yang benar di app/Config/Routes.php.
 *
 * Validates: Requirements 1.2, 1.3
 *
 * @internal
 */
final class RouteFilterVariablesTest extends CIUnitTestCase
{
    private string $routesContent;

    protected function setUp(): void
    {
        parent::setUp();
        $this->routesContent = file_get_contents(APPPATH . 'Config/Routes.php');
    }

    public function testAuth17FilterIsDefined(): void
    {
        $this->assertStringContainsString(
            "'filter' => 'auth:1,7'",
            $this->routesContent,
            'Filter auth:1,7 (untuk $auth17) harus terdefinisi di Routes.php'
        );
    }

    public function testAuth127FilterIsDefined(): void
    {
        $this->assertStringContainsString(
            "'filter' => 'auth:1,2,7'",
            $this->routesContent,
            'Filter auth:1,2,7 (untuk $auth127) harus terdefinisi di Routes.php'
        );
    }

    public function testAuth1237FilterIsDefined(): void
    {
        $this->assertStringContainsString(
            "'filter' => 'auth:1,2,3,7'",
            $this->routesContent,
            'Filter auth:1,2,3,7 (untuk $auth1237) harus terdefinisi di Routes.php'
        );
    }
}
