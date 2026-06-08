<?php

/**
 * Holiday Helper
 * 
 * Helper untuk mengecek dan menampilkan informasi hari libur
 */

if (!function_exists('is_holiday')) {
    /**
     * Check if a given date is a holiday
     * 
     * @param string $date Date in Y-m-d format
     * @return array|false Returns holiday data if it's a holiday, false otherwise
     */
    function is_holiday(string $date)
    {
        static $cache = [];
        
        // Check cache first
        if (isset($cache[$date])) {
            return $cache[$date];
        }
        
        try {
            $db = \Config\Database::connect();
            $holiday = $db->table('holidays')
                ->where('date', $date)
                ->get()
                ->getRowArray();
            
            $cache[$date] = $holiday ?: false;
            return $cache[$date];
            
        } catch (\Exception $e) {
            log_message('error', 'Failed to check holiday: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('is_weekend')) {
    /**
     * Check if a given date is weekend based on school_days setting
     * 
     * @param string $date Date in Y-m-d format
     * @param int|null $schoolDays Number of school days (5 or 6), if null will get from active academic year
     * @return bool
     */
    function is_weekend(string $date, ?int $schoolDays = null): bool
    {
        // Get school_days from active academic year if not provided
        if ($schoolDays === null) {
            try {
                $academicYearModel = new \App\Models\AcademicYearModel();
                $activeYear = $academicYearModel->getActiveYear();
                $schoolDays = $activeYear['school_days'] ?? 5;
            } catch (\Exception $e) {
                log_message('error', 'Failed to get school_days from academic year: ' . $e->getMessage());
                $schoolDays = 5; // Default to 5 days
            }
        }
        
        $dayOfWeek = date('N', strtotime($date));
        
        // If 5 days school: Saturday (6) and Sunday (7) are weekends
        // If 6 days school: Only Sunday (7) is weekend
        if ($schoolDays == 6) {
            return ($dayOfWeek == 7); // Only Sunday
        } else {
            return ($dayOfWeek >= 6); // Saturday and Sunday
        }
    }
}

if (!function_exists('get_holiday_info')) {
    /**
     * Get holiday information for a given date
     * Returns array with 'is_holiday', 'type', 'description'
     * 
     * @param string $date Date in Y-m-d format
     * @param int|null $schoolDays Number of school days (5 or 6), if null will get from active academic year
     * @return array
     */
    function get_holiday_info(string $date, ?int $schoolDays = null): array
    {
        $holiday = is_holiday($date);
        
        if ($holiday) {
            return [
                'is_holiday' => true,
                'type' => 'holiday',
                'description' => $holiday['description'] ?? 'Hari Libur',
                'icon' => 'bi-calendar-x',
                'color' => 'danger'
            ];
        }
        
        if (is_weekend($date, $schoolDays)) {
            $dayOfWeek = date('N', strtotime($date));
            $dayName = date('l', strtotime($date));
            
            // Get school_days if not provided
            if ($schoolDays === null) {
                try {
                    $academicYearModel = new \App\Models\AcademicYearModel();
                    $activeYear = $academicYearModel->getActiveYear();
                    $schoolDays = $activeYear['school_days'] ?? 5;
                } catch (\Exception $e) {
                    $schoolDays = 5;
                }
            }
            
            $dayNameIndo = $dayName == 'Saturday' ? 'Sabtu' : 'Minggu';
            
            return [
                'is_holiday' => true,
                'type' => 'weekend',
                'description' => 'Akhir Pekan (' . $dayNameIndo . ')',
                'icon' => 'bi-calendar2-week',
                'color' => 'secondary'
            ];
        }
        
        return [
            'is_holiday' => false,
            'type' => 'regular',
            'description' => null,
            'icon' => null,
            'color' => null
        ];
    }
}

if (!function_exists('format_holiday_date')) {
    /**
     * Format date for holiday display
     * 
     * @param string $date Date in Y-m-d format
     * @return string Formatted date
     */
    function format_holiday_date(string $date): string
    {
        $days = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu'
        ];
        
        $months = [
            'January' => 'Januari',
            'February' => 'Februari',
            'March' => 'Maret',
            'April' => 'April',
            'May' => 'Mei',
            'June' => 'Juni',
            'July' => 'Juli',
            'August' => 'Agustus',
            'September' => 'September',
            'October' => 'Oktober',
            'November' => 'November',
            'December' => 'Desember'
        ];
        
        $dayName = date('l', strtotime($date));
        $day = date('d', strtotime($date));
        $monthName = date('F', strtotime($date));
        $year = date('Y', strtotime($date));
        
        return $days[$dayName] . ', ' . $day . ' ' . $months[$monthName] . ' ' . $year;
    }
}

if (!function_exists('get_school_days')) {
    /**
     * Get school days setting from active academic year
     * 
     * @return int Number of school days (5 or 6), defaults to 5
     */
    function get_school_days(): int
    {
        static $schoolDays = null;
        
        if ($schoolDays !== null) {
            return $schoolDays;
        }
        
        try {
            $academicYearModel = new \App\Models\AcademicYearModel();
            $activeYear = $academicYearModel->getActiveYear();
            $schoolDays = (int) ($activeYear['school_days'] ?? 5);
            return $schoolDays;
        } catch (\Exception $e) {
            log_message('error', 'Failed to get school_days: ' . $e->getMessage());
            return 5; // Default to 5 days
        }
    }
}
