<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\StudentModel;

class Location extends BaseController
{
    protected $studentModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
    }

    /**
     * Halaman input/update lokasi siswa
     */
    public function index()
    {
        $userSession = session()->get('user');
        
        if (!$userSession || !isset($userSession['role_id']) || $userSession['role_id'] != 5) {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak');
        }

        $studentId = $userSession['student_id'] ?? $userSession['related_id'] ?? null;
        
        if (!$studentId) {
            return redirect()->to('/dashboard')->with('error', 'Data siswa tidak ditemukan');
        }

        $student = $this->studentModel->find($studentId);

        if (!$student) {
            return redirect()->to('/dashboard')->with('error', 'Data siswa tidak ditemukan');
        }

        return view('siswa/location/index', [
            'title' => 'Lokasi Rumah Saya',
            'student' => $student
        ]);
    }

    /**
     * Update koordinat siswa
     */
    public function update()
    {
        $userSession = session()->get('user');
        
        if (!$userSession || !isset($userSession['role_id']) || $userSession['role_id'] != 5) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Akses ditolak'
            ]);
        }

        $studentId = $userSession['student_id'] ?? $userSession['related_id'] ?? null;
        
        if (!$studentId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ]);
        }

        $student = $this->studentModel->find($studentId);

        if (!$student) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data siswa tidak ditemukan'
            ]);
        }

        // Validate input
        $latitude = $this->request->getPost('latitude');
        $longitude = $this->request->getPost('longitude');
        $address = $this->request->getPost('address');

        if (empty($latitude) || empty($longitude)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Latitude dan longitude harus diisi'
            ]);
        }

        // Validate coordinate ranges
        if ($latitude < -90 || $latitude > 90) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Latitude tidak valid (harus antara -90 dan 90)'
            ]);
        }

        if ($longitude < -180 || $longitude > 180) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Longitude tidak valid (harus antara -180 dan 180)'
            ]);
        }

        // Update student data
        $updateData = [
            'latitude' => $latitude,
            'longitude' => $longitude
        ];

        if (!empty($address)) {
            $updateData['address'] = $address;
        }

        $this->studentModel->update($student['id'], $updateData);

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Lokasi berhasil disimpan'
        ]);
    }

    /**
     * Get current location from browser
     */
    public function getCurrentLocation()
    {
        // This is handled by JavaScript Geolocation API
        // This endpoint is just for reference
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Use browser geolocation API'
        ]);
    }
}
