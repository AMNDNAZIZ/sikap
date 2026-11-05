<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class TeachingJournalPaginationTest extends TestCase
{
    public function testPaginationOffsetAndRowNumberCalculation()
    {
        $perPage = 20;

        // Page 1
        $currentPage = 1;
        $startNoPage1 = ($currentPage - 1) * $perPage + 1;
        $this->assertEquals(1, $startNoPage1, 'Page 1 first row should be 1');

        // Page 2
        $currentPage = 2;
        $startNoPage2 = ($currentPage - 1) * $perPage + 1;
        $this->assertEquals(21, $startNoPage2, 'Page 2 first row should be 21');

        // Page 3
        $currentPage = 3;
        $startNoPage3 = ($currentPage - 1) * $perPage + 1;
        $this->assertEquals(41, $startNoPage3, 'Page 3 first row should be 41');
    }

    public function testPaginationRangeDisplay()
    {
        $totalItems = 45;
        $perPage = 20;

        // Page 1: 20 items loaded
        $page = 1;
        $itemsOnPage = 20;
        $from = ($page - 1) * $perPage + 1;
        $to = ($page - 1) * $perPage + $itemsOnPage;
        $this->assertEquals(1, $from);
        $this->assertEquals(20, $to);

        // Page 2: 20 items loaded
        $page = 2;
        $itemsOnPage = 20;
        $from = ($page - 1) * $perPage + 1;
        $to = ($page - 1) * $perPage + $itemsOnPage;
        $this->assertEquals(21, $from);
        $this->assertEquals(40, $to);

        // Page 3: 5 items loaded
        $page = 3;
        $itemsOnPage = 5;
        $from = ($page - 1) * $perPage + 1;
        $to = ($page - 1) * $perPage + $itemsOnPage;
        $this->assertEquals(41, $from);
        $this->assertEquals(45, $to);
    }
}
