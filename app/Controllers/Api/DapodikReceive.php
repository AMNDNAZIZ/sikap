<?php

namespace App\Controllers\Api;

use CodeIgniter\Controller;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Models\StudentRecordModel;

/**
 * API Endpoint untuk menerima data dari SIAKAD Sync Agent (PowerShell bridge)
 *
 * Dipanggil oleh: bridge/siakad_sync_agent.ps1
 * Method: POST
 * Header: X-Siakad-Sync-Token: <token>
 * Body JSON: { "type": "students|teachers", "sync_mode": "skip|update|merge", "data": [...] }
 */
class DapodikReceive extends Controller
{
    public function index()
    {
        // 1. Validasi token
        $token = $this->request->getHeaderLine('X-Siakad-Sync-Token');
        $validToken = env('SIAKAD_SYNC_TOKEN', 'siakad_sync_2026_9b1a2b3c4d5e6f7g8h9i0j');

        if (empty($token) || $token !== $validToken) {
            return $this->response
                ->setStatusCode(401)
                ->setJSON(['status' => 'error', 'message' => 'Token tidak valid.']);
        }

        // 2. Parse body JSON
        $body = $this->request->getJSON(true);
        if (!$body || !isset($body['type']) || !isset($body['data'])) {
            return $this->response
                ->setStatusCode(400)
                ->setJSON(['status' => 'error', 'message' => 'Payload tidak valid. Butuh: type, data.']);
        }

        $type     = $body['type'];
        $syncMode = $body['sync_mode'] ?? 'skip';
        $data     = $body['data'];

        if (!is_array($data) || empty($data)) {
            return $this->response->setJSON(['status' => 'success', 'message' => 'Tidak ada data untuk diproses.']);
        }

        // 3. Proses berdasarkan type
        switch ($type) {
            case 'students':
                $result = $this->processStudents($data, $syncMode);
                break;
            case 'teachers':
                $result = $this->processTeachers($data, $syncMode);
                break;
            default:
                return $this->response
                    ->setStatusCode(400)
                    ->setJSON(['status' => 'error', 'message' => "Type '$type' tidak dikenal. Gunakan: students, teachers."]);
        }

        return $this->response->setJSON([
            'status'  => 'success',
            'message' => "Sinkronisasi '$type' selesai.",
            'detail'  => $result,
        ]);
    }

    // =========================================================
    // STUDENTS
    // =========================================================
    private function processStudents(array $rows, string $syncMode): array
    {
        helper('security');

        $studentModel = new StudentModel();
        $userModel    = new UserModel();
        $classModel   = new ClassModel();
        $yearModel    = new AcademicYearModel();
        $recordModel  = new StudentRecordModel();

        $allClasses = $classModel->findAll();
        $classMap   = [];
        foreach ($allClasses as $c) {
            $classMap[strtolower(trim($c['name']))] = $c['id'];
        }

        $inserted = 0;
        $updated  = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($rows as $d) {
            $d = (array) $d;

            // Normalisasi field — NIS dan NISN TIDAK boleh saling menggantikan
            $nisn     = trim($d['nisn'] ?? '');
            $nis      = trim($d['nis'] ?? '');   // NIS lokal, biarkan kosong jika tidak ada
            $nik      = trim($d['nik'] ?? '');
            $name     = strtoupper(trim($d['nama'] ?? '-'));
            $gender   = $d['jenis_kelamin'] ?? 'L';
            $tmpLahir = $d['tempat_lahir'] ?? '-';
            $tglLahir = $d['tanggal_lahir'] ?? '2000-01-01';
            $rombel   = $d['nama_rombel'] ?? '';

            // Deteksi duplikat berlapis (sama dengan logika di Dapodik.php)
            $existing = null;

            if (!empty($nisn)) {
                $existing = $studentModel->where('nisn', $nisn)->first();
            }
            if (!$existing && !empty($nis)) {
                $existing = $studentModel
                    ->where('nis', $nis)
                    ->where('nis !=', $nisn)
                    ->first();
            }
            if (!$existing && !empty($nik)) {
                $existing = $studentModel->where('nik', $nik)->first();
            }
            if (!$existing && !empty($name) && !empty($tglLahir) && $tglLahir !== '2000-01-01') {
                $candidates = $studentModel->where('name', $name)->where('birth_date', $tglLahir)->findAll();
                if (count($candidates) === 1) {
                    $existing = $candidates[0];
                } elseif (count($candidates) > 1 && !empty($nisn)) {
                    foreach ($candidates as $c) {
                        if ($c['nisn'] === $nisn) { $existing = $c; break; }
                    }
                }
            }

            if ($existing) {
                if ($syncMode === 'skip') {
                    $skipped++;
                    continue;
                } elseif ($syncMode === 'update') {
                    $updateData = $this->mapStudentData($d, $name, $gender, $tmpLahir, $tglLahir);
                    // Jangan timpa NIS jika Dapodik tidak mengirim NIS
                    if (empty($updateData['nis'])) {
                        unset($updateData['nis']);
                    }
                    $studentModel->update($existing['id'], $updateData);
                    $updated++;
                    continue;
                } elseif ($syncMode === 'merge') {
                    $newData    = $this->mapStudentData($d, $name, $gender, $tmpLahir, $tglLahir);
                    $mergedData = [];
                    foreach ($newData as $key => $value) {
                        if (empty($existing[$key]) && !empty($value)) {
                            $mergedData[$key] = $value;
                        }
                    }
                    if (!empty($mergedData)) {
                        $studentModel->update($existing['id'], $mergedData);
                    }
                    $updated++;
                    continue;
                }
            }

            // Insert baru
            try {
                // Validasi: siswa tanpa NISN tidak bisa dibuat akun
                if (empty($nisn)) {
                    log_message('warning', "DapodikReceive: Siswa '$name' tidak punya NISN, dilewati.");
                    $skipped++;
                    continue;
                }

                // Cek collision username
                $usernameBase = strtolower($nisn);
                $existingUser = $userModel->where('username', $usernameBase)->first();
                if ($existingUser) {
                    $usernameBase = strtolower($nisn . '_' . substr(preg_replace('/[^a-z0-9]/', '', strtolower($name)), 0, 4));
                }

                $defaultPassword = function_exists('generate_default_password')
                    ? generate_default_password('siswa', $nisn)
                    : 'siswa' . substr($nisn, -4);

                $userModel->insert([
                    'username'             => $usernameBase,
                    'password'             => password_hash($defaultPassword, PASSWORD_BCRYPT),
                    'fullname'             => $name,
                    'email'                => $usernameBase . '@siswa.local',
                    'role_id'              => 5,
                    'related_type'         => 'student',
                    'must_change_password' => 1,
                ]);
                $userId = $userModel->getInsertID();

                $studentData = $this->mapStudentData($d, $name, $gender, $tmpLahir, $tglLahir, $userId);
                $studentModel->insert($studentData);
                $studentId = $studentModel->getInsertID();

                if ($userId && $studentId) {
                    $userModel->update($userId, ['related_id' => $studentId]);

                    // ── Buat akun Orang Tua otomatis ──
                    $nisSiswa     = trim($studentData['nis'] ?: $nisn);
                    $usernameOrtu = 'ortu_' . $nisSiswa;
                    $ortuCheck    = $userModel->where('username', $usernameOrtu)->first();
                    if (!$ortuCheck) {
                        $passwordOrtu = function_exists('generate_default_password')
                            ? generate_default_password('ortu', $nisSiswa)
                            : 'ortu' . substr($nisSiswa, -4);
                        $userModel->insert([
                            'username'             => $usernameOrtu,
                            'password'             => password_hash($passwordOrtu, PASSWORD_BCRYPT),
                            'fullname'             => 'Orang Tua ' . $name,
                            'email'                => $nisSiswa . '@ortu.local',
                            'role_id'              => 4,
                            'related_id'           => $studentId,
                            'related_type'         => 'student',
                            'must_change_password' => 1,
                        ]);
                    }

                    // Auto-detect kelas dari nama rombel
                    $targetClassId = null;
                    if (!empty($rombel)) {
                        $cleanRombel   = strtolower(trim($rombel));
                        $targetClassId = $classMap[$cleanRombel] ?? null;
                    }

                    $year = $yearModel->where('is_active', 1)->first();
                    if ($year && !empty($targetClassId)) {
                        $recordModel->insert([
                            'student_id'       => $studentId,
                            'class_id'         => $targetClassId,
                            'academic_year_id' => $year['id'],
                            'status'           => 'aktif',
                        ]);
                    }
                    $inserted++;
                }
            } catch (\Exception $e) {
                log_message('error', 'DapodikReceive::processStudents error: ' . $e->getMessage() . ' | NISN=' . $nisn);
                $errors++;
            }
        }

        log_message('info', "DapodikReceive: students sync done. inserted=$inserted, updated=$updated, skipped=$skipped, errors=$errors");

        return compact('inserted', 'updated', 'skipped', 'errors');
    }

    private function mapStudentData(array $d, string $name, string $gender, string $tmpLahir, string $tglLahir, ?int $userId = null): array
    {
        // NIS lokal: ambil dari field 'nis' saja, JANGAN fallback ke nisn
        $nis = trim($d['nis'] ?? '');

        $data = [
            'nisn'             => $d['nisn'] ?? '',
            'nis'              => $nis,
            'name'             => $name,
            'nik'              => $d['nik'] ?? '',
            'gender'           => $gender,
            'birth_place'      => $tmpLahir,
            'birth_date'       => $tglLahir,
            'child_order'      => $d['anak_keberapa'] ?? '',
            'religion'         => $d['agama'] ?? '',
            'nationality'      => $d['kewarganegaraan'] ?? 'WNI',
            'address'          => $d['alamat_jalan'] ?? '',
            'residence_type'   => $d['jenis_tinggal'] ?? '',
            'transportation'   => $d['moda_transportasi'] ?? '',
            'distance'         => $d['jarak_rumah_ke_sekolah'] ?? '',
            'latitude'         => $d['latitude'] ?? '',
            'longitude'        => $d['longitude'] ?? '',
            'special_needs'    => $d['kebutuhan_khusus'] ?? '',
            'father_name'      => $d['nama_ayah'] ?? '',
            'father_nik'       => $d['nik_ayah'] ?? '',
            'father_birth_year'=> $d['tahun_lahir_ayah'] ?? '',
            'father_education' => $d['pendidikan_ayah'] ?? '',
            'father_job'       => $d['pekerjaan_ayah'] ?? '',
            'father_income'    => $d['penghasilan_ayah'] ?? '',
            'mother_name'      => $d['nama_ibu_kandung'] ?? '',
            'mother_nik'       => $d['nik_ibu'] ?? '',
            'mother_birth_year'=> $d['tahun_lahir_ibu'] ?? '',
            'mother_education' => $d['pendidikan_ibu'] ?? '',
            'mother_job'       => $d['pekerjaan_ibu'] ?? '',
            'mother_income'    => $d['penghasilan_ibu'] ?? '',
            'guardian_name'    => $d['nama_wali'] ?? '',
            'guardian_education'=> $d['pendidikan_wali'] ?? '',
            'guardian_job'     => $d['pekerjaan_wali'] ?? '',
            'guardian_income'  => $d['penghasilan_wali'] ?? '',
        ];

        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        return $data;
    }

    // =========================================================
    // TEACHERS
    // =========================================================
    private function processTeachers(array $rows, string $syncMode): array
    {
        helper('security');

        $teacherModel = new TeacherModel();
        $userModel    = new UserModel();

        $inserted = 0;
        $skipped  = 0;
        $errors   = 0;

        foreach ($rows as $d) {
            $d = (array) $d;

            $nuptk      = trim($d['nuptk'] ?? '');
            $nip        = trim($d['nip'] ?? '');
            $name       = strtoupper(trim($d['nama'] ?? '-'));
            $gender     = $d['jenis_kelamin'] ?? 'L';
            $email      = $d['email'] ?? strtolower(str_replace(' ', '', $name)) . '@guru.local';
            $birthPlace = $d['tempat_lahir'] ?? '-';
            $birthDate  = $d['tanggal_lahir'] ?? '1980-01-01';

            // Cek duplikat berdasarkan NUPTK atau NIP (satu per satu, bukan orWhere)
            $existing = null;
            if (!empty($nuptk)) {
                $existing = $teacherModel->where('nuptk', $nuptk)->first();
            }
            if (!$existing && !empty($nip)) {
                $existing = $teacherModel->where('nip', $nip)->first();
            }
            if (!$existing && empty($nuptk) && empty($nip) && !empty($name)) {
                $existing = $teacherModel->where('name', $name)->first();
            }

            if ($existing) {
                $skipped++;
                continue;
            }

            try {
                $username        = !empty($nip) ? strtolower($nip) : (!empty($nuptk) ? strtolower($nuptk) : strtolower(str_replace(' ', '', $name)));
                $defaultPassword = function_exists('generate_default_password')
                    ? generate_default_password('guru', $username)
                    : 'guru' . substr($username, -4);

                $userModel->insert([
                    'username'             => $username,
                    'password'             => password_hash($defaultPassword, PASSWORD_BCRYPT),
                    'fullname'             => $name,
                    'email'                => $email,
                    'role_id'              => 3,
                    'related_type'         => 'teacher',
                    'must_change_password' => 1,
                ]);
                $userId = $userModel->getInsertID();

                $teacherModel->insert([
                    'name'        => $name,
                    'nip'         => $nip ?: null,
                    'nuptk'       => $nuptk ?: null,
                    'gender'      => $gender,
                    'email'       => $email,
                    'birth_place' => $birthPlace,
                    'birth_date'  => $birthDate,
                    'user_id'     => $userId,
                ]);
                $teacherId = $teacherModel->getInsertID();

                if ($userId && $teacherId) {
                    $userModel->update($userId, ['related_id' => $teacherId]);
                    $inserted++;
                }
            } catch (\Exception $e) {
                log_message('error', 'DapodikReceive::processTeachers error: ' . $e->getMessage() . ' | NUPTK=' . $nuptk);
                $errors++;
            }
        }

        log_message('info', "DapodikReceive: teachers sync done. inserted=$inserted, skipped=$skipped, errors=$errors");

        return compact('inserted', 'skipped', 'errors');
    }
}
