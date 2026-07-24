<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\TeacherModel;
use App\Models\UserModel;
use App\Models\ClassModel;
use App\Models\AcademicYearModel;
use App\Models\StudentRecordModel;

class Dapodik extends BaseController
{
    protected $studentModel;
    protected $teacherModel;
    protected $userModel;
    protected $classModel;
    protected $yearModel;
    protected $recordModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->teacherModel = new TeacherModel();
        $this->userModel = new UserModel();
        $this->classModel = new ClassModel();
        $this->yearModel = new AcademicYearModel();
        $this->recordModel = new StudentRecordModel();
    }

    public function index()
    {
        $data = [
            'title' => 'Integrasi Dapodik',
            'api_url' => env('DAPODIK_API_URL'),
            'api_key' => env('DAPODIK_API_KEY'),
            'npsn' => env('DAPODIK_NPSN'),
        ];

        return view('admin/dapodik/index', $data);
    }

    public function testConnection()
    {
        $url = $this->request->getPost('api_url');
        $key = $this->request->getPost('api_key');
        $npsn = $this->request->getPost('npsn');

        if (empty($url) || empty($key) || empty($npsn)) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Harap isi URL, Key, dan NPSN di form atau file .env.'
            ]);
        }

        $endpoint = rtrim($url, '/') . '/getSekolah?npsn=' . $npsn;

        $response = $this->callApi($endpoint, $key);

        $hasData = ($response && (isset($response['rows']) || isset($response['data']) || (isset($response['success']) && $response['success'] == true)));

        if ($hasData) {
            $rows = $response['rows'] ?? $response['data'] ?? [];
            // getSekolah rows is usually a single object, wrap it if needed or access directly
            $sekolah = (isset($rows['nama'])) ? $rows : ($rows[0] ?? null);
            $namaSekolah = $sekolah['nama'] ?? 'Sekolah Ditemukan';

            // Simpan konfigurasi ke .env otomatis jika berhasil
            $this->updateEnv([
                'DAPODIK_API_URL' => $url,
                'DAPODIK_API_KEY' => $key,
                'DAPODIK_NPSN' => $npsn
            ]);

            return $this->response->setJSON([
                'status' => 'success',
                'message' => 'Terhubung dengan Dapodik: ' . $namaSekolah . '. Konfigurasi telah disimpan otomatis.',
                'data' => $sekolah
            ]);
        }

        $errorMsg = 'Gagal terhubung ke Dapodik. ';
        if ($response && (isset($response['message']) || isset($response['success']))) {
            $errorMsg .= 'Detail: ' . ($response['message'] ?? (isset($response['success']) ? 'Success is ' . ($response['success'] ? 'true' : 'false') : 'Unknown error'));
        } else {
            $errorMsg .= 'Pastikan URL, Key, dan NPSN benar. ';
            $errorMsg .= 'Raw Debug: ' . json_encode($response);
        }

        return $this->response->setJSON([
            'status' => 'error',
            'message' => $errorMsg
        ]);
    }

    public function fetchStudents()
    {
        // Tingkatkan execution time limit untuk data banyak
        // set_time_limit() boleh dipanggil kapan saja, berbeda dengan ini_set session
        set_time_limit(180); // 3 menit
        
        $url = env('DAPODIK_API_URL');
        $key = env('DAPODIK_API_KEY');
        $npsn = env('DAPODIK_NPSN');

        if (empty($url) || empty($key) || empty($npsn)) {
            return redirect()->to('/admin/dapodik')->with('error', 'Konfigurasi Dapodik API di .env belum lengkap.');
        }

        $endpoint = rtrim($url, '/') . '/getPesertaDidik?npsn=' . $npsn;
        
        // Gunakan timeout 120 detik untuk data siswa (biasanya banyak)
        $response = $this->callApi($endpoint, $key, 120);

        $hasData = ($response && (isset($response['rows']) || isset($response['data']) || (isset($response['success']) && $response['success'] == true)));

        if ($hasData) {
            $students = $response['rows'] ?? $response['data'] ?? [];
            // Ensure $students is an array of students
            if (isset($students['nisn'])) {
                $students = [$students]; // Wrap if it's a single object
            }

            log_message('info', 'Dapodik: Successfully fetched ' . count($students) . ' students');

            $data = [
                'title' => 'Pratinjau Data Siswa Dapodik',
                'students' => $students,
                'classes' => $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll(),
            ];
            return view('admin/dapodik/preview_students', $data);
        }

        $errorMsg = 'Gagal mengambil data siswa dari Dapodik.';
        if ($response && isset($response['message'])) {
            $errorMsg .= ' Detail: ' . $response['message'];
        }
        
        log_message('error', 'Dapodik: Failed to fetch students - ' . $errorMsg);
        return redirect()->back()->with('error', $errorMsg);
    }

    public function fetchTeachers()
    {
        $url = env('DAPODIK_API_URL');
        $key = env('DAPODIK_API_KEY');
        $npsn = env('DAPODIK_NPSN');

        if (empty($url) || empty($key) || empty($npsn)) {
            return redirect()->to('/admin/dapodik')->with('error', 'Konfigurasi Dapodik API di .env belum lengkap.');
        }

        $endpoint = rtrim($url, '/') . '/getGtk?npsn=' . $npsn;
        
        // Gunakan timeout 60 detik untuk data guru
        $response = $this->callApi($endpoint, $key, 60);

        $hasData = ($response && (isset($response['rows']) || isset($response['data']) || (isset($response['success']) && $response['success'] == true)));

        if ($hasData) {
            $teachers = $response['rows'] ?? $response['data'] ?? [];
            // Ensure $teachers is an array of teachers
            if (isset($teachers['nama'])) {
                $teachers = [$teachers]; // Wrap if it's a single object
            }

            $data = [
                'title' => 'Pratinjau Data Guru Dapodik',
                'teachers' => $teachers,
            ];
            return view('admin/dapodik/preview_teachers', $data);
        }

        $errorMsg = 'Gagal mengambil data guru dari Dapodik.';
        if ($response && isset($response['message'])) {
            $errorMsg .= ' Detail: ' . $response['message'];
        }
        return redirect()->back()->with('error', $errorMsg);
    }

    public function syncStudents()
    {
        // Tingkatkan execution time limit — proses insert banyak siswa bisa lama
        set_time_limit(300); // 5 menit

        helper('security');
        $selected = $this->request->getPost('selected_students');
        $classId = $this->request->getPost('class_id');
        $syncMode = $this->request->getPost('sync_mode') ?? 'skip'; // skip, update, merge

        // Baca data siswa dari JSON (satu field) bukan dari ratusan hidden fields
        // Ini menghindari masalah max_input_vars PHP yang default hanya 1000
        // sedangkan 325 siswa x 25 fields = ~8125 vars
        $studentsJson = $this->request->getPost('students_json');
        $studentsArray = $studentsJson ? json_decode($studentsJson, true) : null;

        if (empty($selected) || empty($studentsArray)) {
            return redirect()->back()->with('error', 'Tidak ada data siswa yang dipilih.');
        }

        // Konversi array indexed (dari JSON) ke format yang bisa diakses by index
        // $studentsArray adalah array numerik: [0 => {...}, 1 => {...}, ...]
        $studentsData = $studentsArray;

        $success = 0;
        $skipped = 0;
        $updated = 0;

        // Ambil semua kelas aktif untuk pemetaan otomatis
        $allClasses = $this->classModel->where('is_active', 1)->orderBy('level', 'ASC')->orderBy('name', 'ASC')->findAll();
        $classMap = [];
        foreach ($allClasses as $c) {
            $classMap[strtolower(trim($c['name']))] = $c['id'];
        }

        foreach ($selected as $id) {
            if (!isset($studentsData[$id]))
                continue;

            $d = $studentsData[$id];
            $nisn = trim($d['nisn'] ?? '');
            $nis  = trim($d['nis'] ?? '');
            $nik  = trim($d['nik'] ?? '');
            $name = strtoupper($d['nama'] ?? '-');
            $gender = $d['jenis_kelamin'] ?? 'L';
            $tmpLahir = $d['tempat_lahir'] ?? '-';
            $tglLahir = $d['tanggal_lahir'] ?? '2000-01-01';
            $dapodikRombel = $d['nama_rombel'] ?? '';

            // Deteksi duplikat berlapis:
            // 1. Cari berdasarkan NISN (paling akurat — nomor nasional unik)
            // 2. Cari berdasarkan NIS lokal (jika NISN kosong)
            // 3. Cari berdasarkan NIK (jika keduanya kosong)
            // 4. Cari berdasarkan nama + tanggal lahir (last resort)
            // PENTING: NIS tidak boleh disamakan dengan NISN — keduanya field berbeda
            $existing = null;

            if (!empty($nisn)) {
                $existing = $this->studentModel->where('nisn', $nisn)->first();
            }

            if (!$existing && !empty($nis)) {
                // Cari berdasarkan NIS lokal, tapi pastikan NIS bukan sama dengan NISN
                // (menghindari false match dari data lama yang salah input)
                $existing = $this->studentModel
                    ->where('nis', $nis)
                    ->where('nis !=', $nisn)  // jangan match jika NIS = NISN (data lama yang salah)
                    ->first();
            }

            if (!$existing && !empty($nik)) {
                $existing = $this->studentModel->where('nik', $nik)->first();
            }

            if (!$existing && !empty($name) && !empty($tglLahir) && $tglLahir !== '2000-01-01') {
                // Hanya gunakan nama+tglLahir sebagai fallback jika tidak ada identifier lain
                // DAN tidak ada siswa lain dengan NISN yang sama (untuk mencegah false match kembar)
                $candidates = $this->studentModel
                    ->where('name', $name)
                    ->where('birth_date', $tglLahir)
                    ->findAll();
                if (count($candidates) === 1) {
                    $existing = $candidates[0];
                } elseif (count($candidates) > 1 && !empty($nisn)) {
                    // Ada lebih dari satu kandidat — cari yang NISN-nya sama
                    foreach ($candidates as $c) {
                        if ($c['nisn'] === $nisn) { $existing = $c; break; }
                    }
                }
            }
            
            if ($existing) {
                if ($syncMode === 'skip') {
                    // Mode Skip: Lewati data yang sudah ada
                    $skipped++;
                    continue;
                } elseif ($syncMode === 'update') {
                    // Mode Update: Timpa semua data dengan data Dapodik
                    $this->updateStudentData($existing['id'], $d, $name, $gender, $tmpLahir, $tglLahir);
                    $updated++;
                    continue;
                } elseif ($syncMode === 'merge') {
                    // Mode Merge: Hanya isi field yang kosong
                    $this->mergeStudentData($existing['id'], $d, $name, $gender, $tmpLahir, $tglLahir);
                    $updated++;
                    continue;
                }
            }

            // Data baru - Insert
            // Validasi: siswa tanpa NISN tidak bisa dibuat akun (username akan kosong)
            if (empty($nisn)) {
                log_message('warning', "Dapodik Sync: Siswa '$name' tidak punya NISN, dilewati.");
                $skipped++;
                continue;
            }

            // Cek apakah username sudah ada (hindari collision)
            $usernameBase = strtolower($nisn);
            $existingUser = $this->userModel->where('username', $usernameBase)->first();
            if ($existingUser) {
                // Username sudah ada tapi bukan siswa ini — gunakan nisn+nama
                $usernameBase = strtolower($nisn . '_' . substr(preg_replace('/[^a-z0-9]/', '', strtolower($name)), 0, 4));
            }

            // Buat akun Siswa
            $email = $usernameBase . '@siswa.local';
            $defaultPassword = generate_default_password('siswa', $nisn);

            $this->userModel->insert([
                'username' => $usernameBase,
                'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                'fullname' => $name,
                'email' => $email,
                'role_id' => 5,
                'related_type' => 'student',
                'must_change_password' => 1,
            ]);
            $userId = $this->userModel->getInsertID();

            // Mapping data lengkap dari Dapodik
            $studentData = $this->mapStudentData($d, $name, $gender, $tmpLahir, $tglLahir, $userId);

            // Simpan Siswa dengan data lengkap
            $this->studentModel->insert($studentData);
            $studentId = $this->studentModel->getInsertID();

            if ($userId && $studentId) {
                $this->userModel->update($userId, ['related_id' => $studentId]);

                // ── Buat akun Orang Tua otomatis ──
                $nisSiswa = $studentData['nis'] ?: $nisn; // gunakan NIS jika ada, fallback NISN
                $usernameOrtu = 'ortu_' . $nisSiswa;
                // Pastikan username ortu unik
                $ortuCheck = $this->userModel->where('username', $usernameOrtu)->first();
                if (!$ortuCheck) {
                    $passwordOrtu = generate_default_password('ortu', $nisSiswa);
                    $this->userModel->insert([
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

                // Deteksi Kelas Otomatis
                $targetClassId = $classId; // Default ke pilihan manual
                if (!empty($dapodikRombel)) {
                    $cleanRombel = strtolower(trim($dapodikRombel));
                    if (isset($classMap[$cleanRombel])) {
                        $targetClassId = $classMap[$cleanRombel];
                    }
                }

                // Tambah ke Record Akademik jika kelas ditemukan (otomatis atau manual)
                $year = $this->yearModel->where('is_active', 1)->first();
                if ($year && !empty($targetClassId)) {
                    $this->recordModel->insert([
                        'student_id' => $studentId,
                        'class_id' => $targetClassId,
                        'academic_year_id' => $year['id'],
                        'status' => 'aktif'
                    ]);
                }
                $success++;
            }
        }

        $message = "Berhasil menambahkan $success siswa baru";
        if ($updated > 0) {
            $message .= ", memperbarui $updated siswa";
        }
        if ($skipped > 0) {
            $message .= ", melewati $skipped siswa";
        }
        $message .= " dengan data lengkap.";

        return redirect()->to('/admin/students')->with('success', $message);
    }

    /**
     * Map data siswa dari Dapodik ke format SIAKAD
     */
    private function mapStudentData($d, $name, $gender, $tmpLahir, $tglLahir, $userId = null)
    {
        // NIS (Nomor Induk Siswa) adalah nomor lokal sekolah — ambil dari field 'nis' Dapodik.
        // JANGAN fallback ke NISN karena keduanya adalah nomor yang berbeda:
        //   - NISN = Nomor Induk Siswa Nasional (10 digit, unik nasional)
        //   - NIS  = Nomor Induk Siswa lokal sekolah (bisa berbeda format)
        // Jika Dapodik tidak mengirim NIS, biarkan kosong — jangan isi dengan NISN.
        $nis = trim($d['nis'] ?? '');

        $data = [
            'nisn' => $d['nisn'] ?? '',
            'nis'  => $nis,  // kosong jika Dapodik tidak punya NIS lokal
            'name' => $name,
            'nik' => $d['nik'] ?? '',
            'gender' => $gender,
            'birth_place' => $tmpLahir,
            'birth_date' => $tglLahir,
            'child_order' => $d['anak_keberapa'] ?? '',
            'religion' => $d['agama'] ?? '',
            'nationality' => $d['kewarganegaraan'] ?? 'WNI',
            'address' => $d['alamat_jalan'] ?? '',
            'residence_type' => $d['jenis_tinggal'] ?? '',
            'transportation' => $d['moda_transportasi'] ?? '',
            'distance' => $d['jarak_rumah_ke_sekolah'] ?? '',
            'latitude' => $d['latitude'] ?? '',
            'longitude' => $d['longitude'] ?? '',
            'special_needs' => $d['kebutuhan_khusus'] ?? '',
            // Data Orang Tua - Ayah
            'father_name' => $d['nama_ayah'] ?? '',
            'father_nik' => $d['nik_ayah'] ?? '',
            'father_birth_year' => $d['tahun_lahir_ayah'] ?? '',
            'father_education' => $d['pendidikan_ayah'] ?? '',
            'father_job' => $d['pekerjaan_ayah'] ?? '',
            'father_income' => $d['penghasilan_ayah'] ?? '',
            // Data Orang Tua - Ibu
            'mother_name' => $d['nama_ibu_kandung'] ?? '',
            'mother_nik' => $d['nik_ibu'] ?? '',
            'mother_birth_year' => $d['tahun_lahir_ibu'] ?? '',
            'mother_education' => $d['pendidikan_ibu'] ?? '',
            'mother_job' => $d['pekerjaan_ibu'] ?? '',
            'mother_income' => $d['penghasilan_ibu'] ?? '',
            // Data Wali
            'guardian_name' => $d['nama_wali'] ?? '',
            'guardian_education' => $d['pendidikan_wali'] ?? '',
            'guardian_job' => $d['pekerjaan_wali'] ?? '',
            'guardian_income' => $d['penghasilan_wali'] ?? '',
        ];

        if ($userId !== null) {
            $data['user_id'] = $userId;
        }

        return $data;
    }

    /**
     * Update mode: Timpa semua data dengan data Dapodik
     * NIS lokal yang sudah ada TIDAK ditimpa jika Dapodik tidak mengirim NIS
     * (mencegah NIS yang valid terhapus karena Dapodik tidak punya data NIS lokal)
     */
    private function updateStudentData($studentId, $d, $name, $gender, $tmpLahir, $tglLahir)
    {
        $studentData = $this->mapStudentData($d, $name, $gender, $tmpLahir, $tglLahir);

        // Jika Dapodik tidak mengirim NIS (kosong), pertahankan NIS yang sudah ada
        // agar tidak menghapus NIS lokal yang sudah diinput manual
        if (empty($studentData['nis'])) {
            unset($studentData['nis']);
        }

        $this->studentModel->update($studentId, $studentData);
        log_message('info', 'Dapodik Sync: Updated student NISN ' . ($d['nisn'] ?? '') . ' (mode: update)');
    }

    /**
     * Merge mode: Hanya isi field yang kosong, jangan timpa yang sudah ada
     */
    private function mergeStudentData($studentId, $d, $name, $gender, $tmpLahir, $tglLahir)
    {
        $existing = $this->studentModel->find($studentId);
        $newData = $this->mapStudentData($d, $name, $gender, $tmpLahir, $tglLahir);
        
        $mergedData = [];
        foreach ($newData as $key => $value) {
            // Hanya update jika field existing kosong dan data baru tidak kosong
            if (empty($existing[$key]) && !empty($value)) {
                $mergedData[$key] = $value;
            }
        }
        
        if (!empty($mergedData)) {
            $this->studentModel->update($studentId, $mergedData);
            log_message('info', 'Dapodik Sync: Merged student NISN ' . ($d['nisn'] ?? '') . ' - Updated ' . count($mergedData) . ' fields (mode: merge)');
        }
    }

    public function syncTeachers()
    {
        // Tingkatkan execution time limit
        set_time_limit(300); // 5 menit

        helper('security');
        $selected = $this->request->getPost('selected_teachers');
        $teachersData = $this->request->getPost('teachers_data');

        if (empty($selected) || empty($teachersData)) {
            return redirect()->back()->with('error', 'Tidak ada data guru yang dipilih.');
        }

        $success = 0;
        $skipped = 0;

        foreach ($selected as $id) {
            if (!isset($teachersData[$id]))
                continue;

            $d = $teachersData[$id];
            $nuptk = $d['nuptk'] ?? null;
            $nip = $d['nip'] ?? null;
            $name = strtoupper($d['nama'] ?? '-');
            $gender = $d['jenis_kelamin'] ?? 'L';
            $email = $d['email'] ?? strtolower(str_replace(' ', '', $name)) . '@guru.local';
            $birthPlace = $d['tempat_lahir'] ?? '-';
            $birthDate = $d['tanggal_lahir'] ?? '1980-01-01';

            // Cek apakah guru sudah ada (berdasarkan NUPTK atau NIP)
            // Penting: cek satu per satu, bukan orWhere sekaligus (bisa false match jika keduanya kosong)
            $existing = null;
            if (!empty($nuptk)) {
                $existing = $this->teacherModel->where('nuptk', $nuptk)->first();
            }
            if (!$existing && !empty($nip)) {
                $existing = $this->teacherModel->where('nip', $nip)->first();
            }
            // Fallback: cek berdasarkan nama jika keduanya kosong
            if (!$existing && empty($nuptk) && empty($nip) && !empty($name)) {
                $existing = $this->teacherModel->where('name', $name)->first();
            }
            if ($existing) {
                $skipped++;
                continue;
            }

            // Buat User
            $username = !empty($nip) ? strtolower($nip) : (!empty($nuptk) ? strtolower($nuptk) : strtolower(str_replace(' ', '', $name)));
            $defaultPassword = generate_default_password('guru', $username);

            $this->userModel->insert([
                'username' => $username,
                'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                'fullname' => $name,
                'email' => $email,
                'role_id' => 3,
                'related_type' => 'teacher',
                'must_change_password' => 1,
            ]);
            $userId = $this->userModel->getInsertID();

            // Simpan Guru
            $this->teacherModel->insert([
                'name' => $name,
                'nip' => $nip,
                'nuptk' => $nuptk,
                'gender' => $gender,
                'email' => $email,
                'birth_place' => $birthPlace,
                'birth_date' => $birthDate,
                'user_id' => $userId
            ]);
            $teacherId = $this->teacherModel->getInsertID();

            if ($userId && $teacherId) {
                $this->userModel->update($userId, ['related_id' => $teacherId]);
                $success++;
            }
        }

        return redirect()->to('/admin/teachers')->with('success', "Berhasil sinkronisasi $success guru. Melompati $skipped guru (sudah ada).");
    }

    private function callApi($url, $key, $timeout = 60)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $key,
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            log_message('error', 'Dapodik API cURL Error: ' . $curlError . ' URL: ' . $url);
            return ['success' => false, 'message' => 'cURL Error: ' . $curlError];
        }

        if ($httpCode !== 200) {
            log_message('error', 'Dapodik API HTTP Error: ' . $httpCode . ' URL: ' . $url . ' Body: ' . substr($response, 0, 500));
            return ['success' => false, 'message' => 'HTTP Error ' . $httpCode];
        }

        // Strip out leaky headers in body (common in some legacy Delphi/Indy servers)
        if (strpos($response, 'HTTP/') === 0) {
            $parts = explode("\r\n\r\n", $response);
            // The JSON is usually in the last part
            $response = end($parts);

            // If still starts with HTTP, try single newline
            if (strpos($response, 'HTTP/') === 0) {
                $parts = explode("\n\n", $response);
                $response = end($parts);
            }
        }

        $data = json_decode(trim($response), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', 'Dapodik API JSON Parse Error: ' . json_last_error_msg() . ' Body after cleaning: ' . substr($response, 0, 200));
            return ['success' => false, 'message' => 'Format respons bukan JSON'];
        }

        return $data;
    }

    private function updateEnv($data = [])
    {
        $path = ROOTPATH . '.env';

        if (!file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);

        foreach ($data as $key => $value) {
            // Kita cari baris yang dimulai dengan KEY = atau KEY=
            $pattern = "/^" . preg_quote($key) . "\s*=\s*(.*)$/m";
            $replace = $key . ' = "' . $value . '"';

            if (preg_match($pattern, $content)) {
                $content = preg_replace($pattern, $replace, $content);
            } else {
                $content .= "\n" . $replace;
            }
        }

        return file_put_contents($path, $content);
    }
}
