<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\ClassModel;
use App\Models\SchoolModel;

class StudentMap extends BaseController
{
    protected $studentModel;
    protected $classModel;
    protected $schoolModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->classModel = new ClassModel();
        $this->schoolModel = new SchoolModel();
    }

    /**
     * Halaman utama peta sebaran siswa
     */
    public function index()
    {
        // Check if user is logged in
        $user = session()->get('user');
        if (!$user) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Only Admin (1), Kepsek (2), and Teacher (3) can access
        $roleId = $user['role_id'] ?? null;
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Get filter parameters
        $classId = $this->request->getGet('class_id');
        $level = $this->request->getGet('level');
        $maxDistance = $this->request->getGet('max_distance');
        $hasCoordinates = $this->request->getGet('has_coordinates');

        // Get school coordinates as center point
        $school = $this->schoolModel->first();
        $schoolLat = $school['latitude'] ?? -6.200000;
        $schoolLng = $school['longitude'] ?? 106.816666;

        // Get all classes for filter
        $classes = $this->classModel->findAll();
        
        // Get unique levels from classes
        $levels = $this->classModel->select('level')->distinct()->orderBy('level', 'ASC')->findAll();

        return view('admin/student_map/index', [
            'title' => 'Peta Sebaran Siswa',
            'classes' => $classes,
            'levels' => $levels,
            'schoolLat' => $schoolLat,
            'schoolLng' => $schoolLng,
            'filters' => [
                'class_id' => $classId,
                'level' => $level,
                'max_distance' => $maxDistance,
                'has_coordinates' => $hasCoordinates
            ]
        ]);
    }

    /**
     * API endpoint untuk mendapatkan data siswa dalam format JSON
     */
    public function getData()
    {
        // Check if user is logged in
        $user = session()->get('user');
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Unauthorized'
            ])->setStatusCode(401);
        }

        // Only Admin (1), Kepsek (2), and Teacher (3) can access
        $roleId = $user['role_id'] ?? null;
        if (!in_array($roleId, [1, 2, 3])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        // Get filter parameters
        $classId = $this->request->getGet('class_id');
        $level = $this->request->getGet('level');
        $maxDistance = $this->request->getGet('max_distance');
        $hasCoordinates = $this->request->getGet('has_coordinates');

        // Build query
        $builder = $this->studentModel
            ->select('students.id, students.nis, students.name, students.address, 
                      students.latitude, students.longitude, students.gender,
                      classes.name as class_name, classes.level')
            ->join('student_records', 'student_records.student_id = students.id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('student_records.status', 'aktif');

        // Apply filters
        if ($classId) {
            $builder->where('student_records.class_id', $classId);
        }

        if ($level) {
            $builder->where('classes.level', $level);
        }

        if ($hasCoordinates === 'yes') {
            $builder->where('students.latitude IS NOT NULL');
            $builder->where('students.longitude IS NOT NULL');
        } elseif ($hasCoordinates === 'no') {
            $builder->groupStart()
                ->where('students.latitude IS NULL')
                ->orWhere('students.longitude IS NULL')
                ->groupEnd();
        }

        $students = $builder->findAll();

        // Get school coordinates for distance calculation
        $school = $this->schoolModel->first();
        $schoolLat = $school['latitude'] ?? -6.200000;
        $schoolLng = $school['longitude'] ?? 106.816666;

        // Process data
        $markers = [];
        $studentsWithoutCoordinates = [];

        foreach ($students as $student) {
            if (!empty($student['latitude']) && !empty($student['longitude'])) {
                // Calculate distance from school
                $distance = $this->calculateDistance(
                    $schoolLat,
                    $schoolLng,
                    $student['latitude'],
                    $student['longitude']
                );

                // Apply distance filter
                if ($maxDistance && $distance > $maxDistance) {
                    continue;
                }

                $markers[] = [
                    'id' => $student['id'],
                    'nis' => $student['nis'],
                    'name' => $student['name'],
                    'class' => $student['class_name'] ?? '-',
                    'level' => $student['level'] ?? '-',
                    'address' => $student['address'] ?? '-',
                    'latitude' => (float) $student['latitude'],
                    'longitude' => (float) $student['longitude'],
                    'distance' => round($distance, 2),
                    'gender' => $student['gender']
                ];
            } else {
                $studentsWithoutCoordinates[] = [
                    'id' => $student['id'],
                    'nis' => $student['nis'],
                    'name' => $student['name'],
                    'class' => $student['class_name'] ?? '-',
                    'level' => $student['level'] ?? '-',
                    'address' => $student['address'] ?? '-'
                ];
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'school' => [
                'latitude' => (float) $schoolLat,
                'longitude' => (float) $schoolLng,
                'name' => $school['name'] ?? 'Sekolah'
            ],
            'markers' => $markers,
            'studentsWithoutCoordinates' => $studentsWithoutCoordinates,
            'statistics' => [
                'total' => count($markers) + count($studentsWithoutCoordinates),
                'withCoordinates' => count($markers),
                'withoutCoordinates' => count($studentsWithoutCoordinates),
                'averageDistance' => count($markers) > 0 
                    ? round(array_sum(array_column($markers, 'distance')) / count($markers), 2)
                    : 0
            ]
        ]);
    }

    /**
     * Halaman statistik sebaran siswa
     */
    public function statistics()
    {
        // Check if user is logged in
        $user = session()->get('user');
        if (!$user) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Only Admin (1), Kepsek (2), and Teacher (3) can access
        $roleId = $user['role_id'] ?? null;
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Get all students with coordinates
        $students = $this->studentModel
            ->select('students.*, classes.name as class_name, classes.level')
            ->join('student_records', 'student_records.student_id = students.id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('student_records.status', 'aktif')
            ->findAll();

        $school = $this->schoolModel->first();
        $schoolLat = $school['latitude'] ?? -6.200000;
        $schoolLng = $school['longitude'] ?? 106.816666;

        // Calculate statistics
        $withCoordinates = 0;
        $withoutCoordinates = 0;
        $distances = [];
        $byClass = [];
        $byLevel = [];
        $byDistance = [
            '0-1' => 0,
            '1-3' => 0,
            '3-5' => 0,
            '5-10' => 0,
            '10+' => 0
        ];

        foreach ($students as $student) {
            if (!empty($student['latitude']) && !empty($student['longitude'])) {
                $withCoordinates++;
                
                $distance = $this->calculateDistance(
                    $schoolLat,
                    $schoolLng,
                    $student['latitude'],
                    $student['longitude']
                );
                $distances[] = $distance;

                // Group by distance
                if ($distance < 1) {
                    $byDistance['0-1']++;
                } elseif ($distance < 3) {
                    $byDistance['1-3']++;
                } elseif ($distance < 5) {
                    $byDistance['3-5']++;
                } elseif ($distance < 10) {
                    $byDistance['5-10']++;
                } else {
                    $byDistance['10+']++;
                }

                // Group by class
                $className = $student['class_name'] ?? 'Tidak ada kelas';
                $byClass[$className] = ($byClass[$className] ?? 0) + 1;

                // Group by level
                $levelName = 'Tingkat ' . ($student['level'] ?? 'Tidak diketahui');
                $byLevel[$levelName] = ($byLevel[$levelName] ?? 0) + 1;
            } else {
                $withoutCoordinates++;
            }
        }

        $statistics = [
            'total' => count($students),
            'withCoordinates' => $withCoordinates,
            'withoutCoordinates' => $withoutCoordinates,
            'percentage' => count($students) > 0 
                ? round(($withCoordinates / count($students)) * 100, 1)
                : 0,
            'minDistance' => !empty($distances) ? round(min($distances), 2) : 0,
            'maxDistance' => !empty($distances) ? round(max($distances), 2) : 0,
            'avgDistance' => !empty($distances) 
                ? round(array_sum($distances) / count($distances), 2)
                : 0,
            'byDistance' => $byDistance,
            'byClass' => $byClass,
            'byLevel' => $byLevel
        ];

        return view('admin/student_map/statistics', [
            'title' => 'Statistik Sebaran Siswa',
            'statistics' => $statistics
        ]);
    }

    /**
     * Calculate distance between two coordinates using Haversine formula
     * Returns distance in kilometers
     */
    private function calculateDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371; // Earth radius in kilometers

        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Export data to Excel
     */
    public function export()
    {
        // Check if user is logged in
        $user = session()->get('user');
        if (!$user) {
            return redirect()->to('/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Only Admin (1), Kepsek (2), and Teacher (3) can access
        $roleId = $user['role_id'] ?? null;
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->to('/dashboard')->with('error', 'Anda tidak memiliki akses ke halaman ini.');
        }

        // Get all students with coordinates
        $students = $this->studentModel
            ->select('students.nis, students.name, students.address, 
                      students.latitude, students.longitude,
                      classes.name as class_name, classes.level')
            ->join('student_records', 'student_records.student_id = students.id', 'left')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->where('student_records.status', 'aktif')
            ->findAll();

        $school = $this->schoolModel->first();
        $schoolLat = $school['latitude'] ?? -6.200000;
        $schoolLng = $school['longitude'] ?? 106.816666;

        // Prepare CSV data
        $filename = 'sebaran_siswa_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for Excel UTF-8 support
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Header
        fputcsv($output, [
            'NIS',
            'Nama',
            'Kelas',
            'Tingkat',
            'Alamat',
            'Latitude',
            'Longitude',
            'Jarak dari Sekolah (km)',
            'Status Koordinat'
        ]);

        // Data
        foreach ($students as $student) {
            $distance = '';
            $status = 'Belum diisi';
            
            if (!empty($student['latitude']) && !empty($student['longitude'])) {
                $distance = round($this->calculateDistance(
                    $schoolLat,
                    $schoolLng,
                    $student['latitude'],
                    $student['longitude']
                ), 2);
                $status = 'Sudah diisi';
            }

            fputcsv($output, [
                $student['nis'],
                $student['name'],
                $student['class_name'] ?? '-',
                $student['level'] ?? '-',
                $student['address'] ?? '-',
                $student['latitude'] ?? '',
                $student['longitude'] ?? '',
                $distance,
                $status
            ]);
        }

        fclose($output);
        exit;
    }
}
