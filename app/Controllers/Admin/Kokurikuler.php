<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\KokurikulerDocumentModel;
use App\Models\AcademicYearModel;
use App\Models\SubjectModel;
use App\Models\AlurTujuanPembelajaranModel;
use App\Models\TujuanPembelajaranModel;

class Kokurikuler extends BaseController
{
    protected $documentModel;
    protected $yearModel;
    protected $subjectModel;
    protected $atpModel;
    protected $tpModel;
    protected $pelaksanaanModel;
    protected $rubrikModel;
    protected $penilaianModel;

    public function __construct()
    {
        $this->documentModel = new KokurikulerDocumentModel();
        $this->yearModel = new AcademicYearModel();
        $this->subjectModel = new SubjectModel();
        $this->atpModel = new AlurTujuanPembelajaranModel();
        $this->tpModel = new TujuanPembelajaranModel();
        $this->pelaksanaanModel = new \App\Models\KokurikulerPelaksanaanModel();
        $this->rubrikModel = new \App\Models\KokurikulerRubrikModel();
        $this->penilaianModel = new \App\Models\KokurikulerPenilaianModel();
    }

    /**
     * Helper: Get jenis kokurikuler with backward compatibility
     */
    private function getJenisKokurikuler($document)
    {
        return $document['jenis_kokurikuler'] ?? $document['bentuk_kegiatan'] ?? '';
    }

    /**
     * Index - Daftar Dokumen Rencana Kokurikuler
     */
    public function index()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        // Admin (1), Kepsek (2), dan Guru Kelas/Wali Kelas (3) bisa akses
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
        }

        $documents = $this->documentModel->getDocumentsWithCreator($userId, $roleId);

        return view('admin/kokurikuler/index', [
            'title' => 'Dokumen Rencana Kokurikuler',
            'documents' => $documents,
            'isReadOnly' => ($roleId == 2), // Kepsek read-only
        ]);
    }

    /**
     * Create - Form Tambah Dokumen Rencana
     */
    public function create()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;

        // Kepsek tidak bisa create
        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        // Get active year
        $activeYear = $this->yearModel->getActiveYear();
        
        // Get school level untuk menentukan fase
        $db = \Config\Database::connect();
        $school = $db->table('school_profile')->select('level')->get()->getRow();
        $schoolLevel = $school->level ?? 1;

        // Get subjects untuk lintas disiplin
        if ($roleId == 1) {
            // Admin: akses semua mata pelajaran
            $subjects = $this->subjectModel->orderBy('name', 'ASC')->findAll();
        } else {
            // Guru Kelas (role_id = 3): hanya mata pelajaran di kelas yang ia walikan
            $teacherId = $user['related_id'] ?? 0;
            
            // Get kelas yang diwalikan
            $waliClass = $db->table('classes')
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRow();
            
            if (!$waliClass) {
                return redirect()->back()->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
            }
            
            // Get semua mata pelajaran yang diajarkan di kelas tersebut
            $subjectIds = $db->table('teaching_assignments')
                ->select('subject_id')
                ->distinct()
                ->where('class_id', $waliClass->id)
                ->where('academic_year_id', $activeYear['id'])
                ->get()
                ->getResultArray();
            
            $subjectIdList = array_column($subjectIds, 'subject_id');
            
            if (empty($subjectIdList)) {
                return redirect()->back()->with('error', 'Tidak ada mata pelajaran yang diajarkan di kelas Anda.');
            }
            
            // Get subject details
            $subjects = $this->subjectModel
                ->whereIn('id', $subjectIdList)
                ->orderBy('name', 'ASC')
                ->findAll();
        }

        // Load Sub Dimensi data
        $subDimensiConfig = new \Config\SubDimensiProfilLulusan();
        $subDimensiData = $subDimensiConfig->getSubDimensi();

        return view('admin/kokurikuler/create', [
            'title' => 'Buat Dokumen Rencana Kokurikuler',
            'activeYear' => $activeYear,
            'schoolLevel' => $schoolLevel,
            'subjects' => $subjects,
            'subDimensiData' => $subDimensiData,
        ]);
    }

    /**
     * Store - Simpan Dokumen Rencana (Step 1: Manual Input)
     */
    public function store()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $user = session()->get('user');
        $userId = $user['id'] ?? 0;

        // Validation
        $rules = [
            'semester' => 'required|in_list[1,2]',
            'fase' => 'required',
            'level_kelas' => 'required',
            'jumlah_pertemuan' => 'required|integer|greater_than[0]',
            'dimensi_profil' => 'required',
            'tema' => 'required|min_length[3]',
            'jenis_kokurikuler' => 'required|in_list[lintas_disiplin,7kaih,lainnya]',
            'bentuk_kegiatan_konkret' => 'required|min_length[10]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Get active year
        $activeYear = $this->yearModel->getActiveYear();
        if (!$activeYear) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak ada tahun ajaran aktif'
            ]);
        }

        $semester = $this->request->getPost('semester');
        $levelKelas = $this->request->getPost('level_kelas');
        
        // Cek apakah sudah ada rencana untuk semester dan level ini
        $existingDoc = $this->documentModel
            ->where('year_id', $activeYear['id'])
            ->where('semester', $semester)
            ->where('level_kelas', $levelKelas)
            ->first();
        
        if ($existingDoc) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Sudah ada rencana kokurikuler untuk semester ' . $semester . ' di level kelas ' . $levelKelas . ' pada tahun ajaran ini.'
            ]);
        }

        // Tentukan apakah ini template (dibuat oleh admin)
        $isTemplate = ($user['role_id'] == 1) ? 1 : 0;

        // Auto-assign class_id untuk wali kelas (role_id = 3)
        $classId = null;
        if ($user['role_id'] == 3) {
            $teacherId = $user['related_id'] ?? 0;
            $db = \Config\Database::connect();
            
            // Get kelas yang diwalikan
            $waliClass = $db->table('classes')
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRow();
            
            if ($waliClass) {
                $classId = $waliClass->id;
            }
        }

        // Prepare data
        $data = [
            'year_id' => $activeYear['id'],
            'semester' => $semester,
            'fase' => $this->request->getPost('fase'),
            'level_kelas' => $levelKelas,
            'class_id' => $classId,
            'jumlah_pertemuan' => $this->request->getPost('jumlah_pertemuan'),
            'dimensi_profil' => json_encode($this->request->getPost('dimensi_profil')),
            'tema' => $this->request->getPost('tema'),
            'jenis_kokurikuler' => $this->request->getPost('jenis_kokurikuler'),
            'bentuk_kegiatan_konkret' => $this->request->getPost('bentuk_kegiatan_konkret'),
            'kegiatan_detail' => $this->prepareKegiatanDetail(),
            'kemitraan' => json_encode($this->request->getPost('kemitraan') ?? []),
            'teknologi_digital' => json_encode($this->request->getPost('teknologi_digital') ?? []),
            'status' => 'draft',
            'is_template' => $isTemplate,
            'created_by' => $userId,
        ];

        try {
            $id = $this->documentModel->insert($data);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data berhasil disimpan. Silakan generate konten dengan AI.',
                'document_id' => $id
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Kokurikuler store error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Prepare kegiatan detail based on jenis_kokurikuler
     */
    private function prepareKegiatanDetail()
    {
        $jenisKokurikuler = $this->request->getPost('jenis_kokurikuler');

        if ($jenisKokurikuler === 'lintas_disiplin') {
            $subjects = $this->request->getPost('lintas_subjects'); // array of subject_id
            
            // Get all POST data to find lintas_tujuan_X[], lintas_dimensi_X, and lintas_subdimensi_X fields
            $postData = $this->request->getPost();
            $items = [];
            
            // Log all POST data for debugging
            log_message('info', 'Kokurikuler POST Data: ' . json_encode($postData));
            log_message('info', 'Kokurikuler Subjects array: ' . json_encode($subjects));
            
            // Build mapping of subject index to subject_id
            $subjectMapping = [];
            if (is_array($subjects)) {
                foreach ($subjects as $idx => $subjectId) {
                    $subjectMapping[$idx] = $subjectId;
                    log_message('info', "Subject mapping: index $idx => subject_id $subjectId");
                }
            }
            
            // Extract data for each subject index
            foreach ($postData as $key => $value) {
                // Match lintas_tujuan_X[]
                if (preg_match('/^lintas_tujuan_(\d+)$/', $key, $matches)) {
                    $index = (int)$matches[1];
                    $tpIds = $value; // array of TP IDs
                    $dimensi = $postData['lintas_dimensi_' . $index] ?? '';
                    $subDimensi = $postData['lintas_subdimensi_' . $index] ?? '';
                    
                    // Find subject_id by matching index
                    $subjectId = null;
                    
                    // Try to find subject by checking if lintas_subjects array has this index
                    $subjectIndex = 0;
                    foreach ($postData as $k => $v) {
                        if (preg_match('/^lintas_tujuan_(\d+)$/', $k, $m)) {
                            if ((int)$m[1] == $index) {
                                // Found the matching index, use the corresponding subject
                                if (isset($subjects[$subjectIndex])) {
                                    $subjectId = $subjects[$subjectIndex];
                                }
                                break;
                            }
                            $subjectIndex++;
                        }
                    }
                    
                    log_message('info', "Kokurikuler Lintas Index $index: subject=$subjectId, TPs=" . json_encode($tpIds) . ", dimensi=$dimensi, subdimensi=$subDimensi");
                    
                    if (!empty($tpIds) && !empty($dimensi) && !empty($subjectId)) {
                        // Create one item per TP
                        foreach ($tpIds as $tpId) {
                            $items[] = [
                                'subject_id' => $subjectId,
                                'tp_id' => $tpId,
                                'dimensi_profil' => $dimensi,
                                'sub_dimensi' => $subDimensi,
                            ];
                        }
                    } else {
                        log_message('warning', "Kokurikuler Lintas Index $index: Missing data - subject=$subjectId, TPs=" . json_encode($tpIds) . ", dimensi=$dimensi");
                    }
                }
            }
            
            $result = [
                'subjects' => array_unique(array_column($items, 'subject_id')),
                'items' => $items
            ];
            
            log_message('info', 'Kokurikuler kegiatan_detail (lintas): ' . json_encode($result));
            log_message('info', 'Kokurikuler Total items: ' . count($items));
            
            return json_encode($result);
            
        } elseif ($jenisKokurikuler === '7kaih') {
            $postData = $this->request->getPost();
            $items = [];
            
            // Extract data for each KAIH index
            foreach ($postData as $key => $value) {
                // Match kaih_items_X
                if (preg_match('/^kaih_items_(\d+)$/', $key, $matches)) {
                    $index = (int)$matches[1];
                    $kaihItem = $value; // kebiasaan name
                    $dimensi = $postData['kaih_dimensi_' . $index] ?? '';
                    $subDimensi = $postData['kaih_subdimensi_' . $index] ?? '';
                    
                    if (!empty($kaihItem) && !empty($dimensi)) {
                        $items[] = [
                            'kaih' => $kaihItem,
                            'dimensi_profil' => $dimensi,
                            'sub_dimensi' => $subDimensi,
                        ];
                    }
                    
                    log_message('info', "Kokurikuler KAIH Index $index: kaih=$kaihItem, dimensi=$dimensi, subdimensi=$subDimensi");
                }
            }
            
            $result = ['items' => $items];
            
            log_message('info', 'Kokurikuler kegiatan_detail (7kaih): ' . json_encode($result));
            
            return json_encode($result);
            
        } else {
            $postData = $this->request->getPost();
            $items = [];
            
            foreach ($postData as $key => $value) {
                // Match lainnya_dimensi_X
                if (preg_match('/^lainnya_dimensi_(\d+)$/', $key, $matches)) {
                    $index = (int)$matches[1];
                    $dimensi = $value;
                    $subDimensi = $postData['lainnya_subdimensi_' . $index] ?? '';
                    
                    if (!empty($dimensi) && !empty($subDimensi)) {
                        $items[] = [
                            'dimensi_profil' => $dimensi,
                            'sub_dimensi' => $subDimensi,
                        ];
                    }
                }
            }
            
            $result = [
                'text' => $this->request->getPost('lainnya_text'),
                'items' => $items
            ];
            
            return json_encode($result);
        }
    }

    /**
     * Generate AI Content
     */
    public function generateAI($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $document = $this->documentModel->find($id);
        if (!$document) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Dokumen tidak ditemukan'
            ]);
        }

        // Check ownership
        $user = session()->get('user');
        if ($user['role_id'] != 1 && $document['created_by'] != $user['id']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda tidak memiliki akses'
            ]);
        }

        try {
            // Prepare prompt for AI
            $prompt = $this->buildAIPrompt($document);

            // Call AI API (sama seperti modul ajar)
            $aiResponse = $this->callAIAPI($prompt);

            if ($aiResponse['success']) {
                // Update document dengan hasil AI
                $this->documentModel->update($id, [
                    'tujuan_pembelajaran' => $aiResponse['tujuan_pembelajaran'],
                    'praktik_pedagogis' => $aiResponse['praktik_pedagogis'],
                    'lingkungan_belajar' => $aiResponse['lingkungan_belajar'],
                    'kegiatan_kokurikuler' => $aiResponse['kegiatan_kokurikuler'],
                    'status' => 'completed'
                ]);

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Konten berhasil di-generate',
                    'data' => $aiResponse
                ]);
            } else {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal generate AI: ' . $aiResponse['message']
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'AI Generate error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Build AI Prompt
     */
    private function buildAIPrompt($document)
    {
        $dimensi = json_decode($document['dimensi_profil'], true);
        $dimensiText = implode(', ', $dimensi);
        
        // Get bentuk kegiatan konkret
        $bentukKegiatanKonkret = $document['bentuk_kegiatan_konkret'] ?? '';
        
        // Get detail kegiatan
        $kegiatanDetail = '';
        $jenisKokurikuler = $this->getJenisKokurikuler($document);
        
        if ($jenisKokurikuler === 'lintas_disiplin') {
            $detail = json_decode($document['kegiatan_detail'], true);
            $kegiatanDetail = "Kegiatan melibatkan kolaborasi lintas disiplin ilmu dari beberapa mata pelajaran.";
        } elseif ($jenisKokurikuler === '7kaih') {
            $detail = json_decode($document['kegiatan_detail'], true);
            if ($detail && isset($detail['items'])) {
                $kaihItems = array_column($detail['items'], 'kaih');
                $kegiatanDetail = "Kegiatan 7 KAIH yang dipilih: " . implode(', ', $kaihItems);
            }
        } else {
            $detail = json_decode($document['kegiatan_detail'], true);
            if ($detail && isset($detail['text'])) {
                $kegiatanDetail = $detail['text'];
            } else {
                $kegiatanDetail = $document['kegiatan_detail'];
            }
        }

        $prompt = "Anda adalah ahli kurikulum merdeka. Buatkan dokumen rencana kokurikuler dengan detail berikut:\n\n";
        $prompt .= "INFORMASI DASAR:\n";
        $prompt .= "- Fase: {$document['fase']}\n";
        $prompt .= "- Level Kelas: {$document['level_kelas']}\n";
        $prompt .= "- Jumlah Pertemuan: {$document['jumlah_pertemuan']}\n";
        $prompt .= "- Dimensi Profil Lulusan: {$dimensiText}\n";
        $prompt .= "- Tema: {$document['tema']}\n";
        $prompt .= "- Jenis Kokurikuler: {$jenisKokurikuler}\n";
        
        if (!empty($bentukKegiatanKonkret)) {
            $prompt .= "- Bentuk Kegiatan Konkret: {$bentukKegiatanKonkret}\n";
        }
        
        $prompt .= "- Detail Kegiatan: {$kegiatanDetail}\n\n";

        $prompt .= "INSTRUKSI:\n";
        $prompt .= "Hasilkan HANYA JSON (tanpa teks tambahan) dengan struktur berikut:\n\n";
        $prompt .= "{\n";
        
        // Update instruksi tujuan pembelajaran
        if (!empty($bentukKegiatanKonkret)) {
            $prompt .= '  "tujuan_pembelajaran": "Tujuan pembelajaran yang menggabungkan kompetensi dari dimensi profil dengan konten tema. WAJIB diakhiri dengan kalimat: melalui kegiatan ' . $bentukKegiatanKonkret . '",'."\n";
        } else {
            $prompt .= '  "tujuan_pembelajaran": "Tujuan pembelajaran yang menggabungkan kompetensi dari dimensi profil dengan konten tema",'."\n";
        }
        
        $prompt .= '  "praktik_pedagogis": "Deskripsi praktik pedagogis yang mengutamakan pembelajaran kolaboratif, inquiry, project-based learning",'."\n";
        $prompt .= '  "lingkungan_belajar": "Deskripsi lingkungan belajar yang luas (dalam/luar kelas, komunitas, digital), aman, terbuka, inklusif",'."\n";
        $prompt .= '  "kegiatan_kokurikuler": ['."\n";
        
        // Update instruksi kegiatan per pertemuan
        for ($i = 1; $i <= $document['jumlah_pertemuan']; $i++) {
            if (!empty($bentukKegiatanKonkret)) {
                $prompt .= '    {"pertemuan": '.$i.', "kegiatan": "Judul kegiatan pertemuan '.$i.' yang terkait dengan ' . $bentukKegiatanKonkret . '", "deskripsi": "Deskripsi detail kegiatan yang realistis dan spesifik sesuai dengan bentuk kegiatan ' . $bentukKegiatanKonkret . '"}';
            } else {
                $prompt .= '    {"pertemuan": '.$i.', "kegiatan": "Judul kegiatan pertemuan '.$i.'", "deskripsi": "Deskripsi detail kegiatan"}';
            }
            
            if ($i < $document['jumlah_pertemuan']) {
                $prompt .= ',';
            }
            $prompt .= "\n";
        }
        
        $prompt .= "  ]\n";
        $prompt .= "}\n\n";
        
        if (!empty($bentukKegiatanKonkret)) {
            $prompt .= "PENTING:\n";
            $prompt .= "1. Tujuan pembelajaran HARUS diakhiri dengan: 'melalui kegiatan {$bentukKegiatanKonkret}'\n";
            $prompt .= "2. Setiap rincian kegiatan per pertemuan HARUS realistis dan spesifik sesuai dengan bentuk kegiatan '{$bentukKegiatanKonkret}'\n";
            $prompt .= "3. Deskripsi kegiatan harus menunjukkan langkah-langkah konkret yang dilakukan siswa dalam '{$bentukKegiatanKonkret}'\n";
            $prompt .= "4. Kembalikan HANYA JSON yang valid, tanpa penjelasan atau teks tambahan.\n";
        } else {
            $prompt .= "PENTING: Kembalikan HANYA JSON yang valid, tanpa penjelasan atau teks tambahan.\n";
        }

        return $prompt;
    }

    /**
     * Call AI API (sama seperti modul ajar)
     */
    private function callAIAPI($prompt)
    {
        $user = session()->get('user');
        
        // Get API Key and Provider
        if ($user['role_id'] == 3) {
            // Guru
            $teacherModel = new \App\Models\TeacherModel();
            $teacher = $teacherModel->find($user['related_id']);
            $apiKey = $teacher['gemini_api_key'] ?? '';
            $aiProvider = $teacher['ai_provider'] ?? 'gemini';
        } else {
            // Admin
            $db = \Config\Database::connect();
            $userData = $db->table('users')->where('id', $user['id'])->get()->getRowArray();
            $apiKey = $userData['gemini_api_key'] ?? '';
            $aiProvider = $userData['ai_provider'] ?? 'gemini';
        }

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key belum diatur. Silakan atur API Key terlebih dahulu.'
            ];
        }

        // Call AI API
        if ($aiProvider === 'groq') {
            $url = 'https://api.groq.com/openai/v1/chat/completions';
            $ch = curl_init($url);
            $payload = json_encode([
                "model" => "llama-3.3-70b-versatile",
                "messages" => [
                    ["role" => "user", "content" => $prompt]
                ],
                "temperature" => 0.7
            ]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json'
            ]);
        } else {
            // Gemini
            $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.0-flash-lite:generateContent?key=' . $apiKey;
            $ch = curl_init($url);
            $payload = json_encode([
                "contents" => [
                    ["parts" => [["text" => $prompt]]]
                ]
            ]);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        }
        
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode != 200) {
            log_message('error', "Kokurikuler AI ($aiProvider) Failed. HTTP: $httpCode. Curl Error: $curlError. Response: $response");
            $errorMsg = "Gagal memanggil API $aiProvider (HTTP $httpCode). ";
            if ($response) {
                $errRes = json_decode($response, true);
                if ($aiProvider === 'groq' && isset($errRes['error']['message'])) {
                    $errorMsg .= "Error API: " . $errRes['error']['message'];
                } elseif (isset($errRes['error']['message'])) {
                    $errorMsg .= "Error API: " . $errRes['error']['message'];
                } else {
                    $errorMsg .= "Pastikan API Key valid atau batas kuota.";
                }
            } elseif ($curlError) {
                $errorMsg .= "cURL: $curlError";
            }
            return [
                'success' => false,
                'message' => $errorMsg
            ];
        }

        $resData = json_decode($response, true);
        if ($aiProvider === 'groq') {
            $content = $resData['choices'][0]['message']['content'] ?? '';
        } else {
            $content = $resData['candidates'][0]['content']['parts'][0]['text'] ?? '';
        }

        // Log raw response for debugging
        log_message('info', "Kokurikuler AI Raw Response: " . substr($content, 0, 500));

        // Clean up response - remove markdown code blocks
        $content = preg_replace('/```(?:json)?\s*/s', '', $content);
        $content = preg_replace('/```\s*$/s', '', $content);
        $content = trim($content);
        
        // Try to extract JSON if there's text before/after
        if (preg_match('/\{[\s\S]*\}/s', $content, $matches)) {
            $content = $matches[0];
        }
        
        // Log cleaned content
        log_message('info', "Kokurikuler AI Cleaned Content: " . substr($content, 0, 500));
        
        $aiData = json_decode($content, true);
        
        if (!$aiData || json_last_error() !== JSON_ERROR_NONE) {
            log_message('error', "Kokurikuler AI JSON Parse Error: " . json_last_error_msg() . " | Content: " . substr($content, 0, 1000));
            return [
                'success' => false,
                'message' => 'Gagal parsing response AI. Format tidak valid. Error: ' . json_last_error_msg()
            ];
        }

        return [
            'success' => true,
            'tujuan_pembelajaran' => $aiData['tujuan_pembelajaran'] ?? '',
            'praktik_pedagogis' => $aiData['praktik_pedagogis'] ?? '',
            'lingkungan_belajar' => $aiData['lingkungan_belajar'] ?? '',
            'kegiatan_kokurikuler' => json_encode($aiData['kegiatan_kokurikuler'] ?? [])
        ];
    }

    /**
     * Get ATP by Subject (AJAX)
     */
    public function getATPBySubject($subjectId)
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false]);
        }

        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        
        // Validasi akses untuk Guru Kelas
        if ($roleId == 3) {
            $teacherId = $user['related_id'] ?? 0;
            $db = \Config\Database::connect();
            
            // Get active year
            $activeYear = $this->yearModel->getActiveYear();
            
            // Get kelas yang diwalikan
            $waliClass = $db->table('classes')
                ->where('teacher_id', $teacherId)
                ->get()
                ->getRow();
            
            if (!$waliClass) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Anda belum ditugaskan sebagai wali kelas.'
                ]);
            }
            
            // Cek apakah subject_id ada di kelas yang diwalikan
            $subjectExists = $db->table('teaching_assignments')
                ->where('class_id', $waliClass->id)
                ->where('subject_id', $subjectId)
                ->where('academic_year_id', $activeYear['id'])
                ->countAllResults();
            
            if ($subjectExists == 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Mata pelajaran ini tidak ada di kelas Anda.'
                ]);
            }
        }

        // Get Tujuan Pembelajaran (TP) for the subject
        $tpList = $this->tpModel
            ->where('subject_id', $subjectId)
            ->orderBy('id', 'ASC')
            ->findAll();

        return $this->response->setJSON([
            'success' => true,
            'data' => $tpList
        ]);
    }

    /**
     * View/Detail Document
     */
    public function view($id)
    {
        $document = $this->documentModel->getDocumentWithDetails($id);
        
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        // Check access - Admin, Kepsek, atau creator
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        if (!in_array($roleId, [1, 2]) && $document['created_by'] != $user['id']) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses');
        }

        // Get subject names and TPs if lintas disiplin
        $subjectNames = [];
        $subjectTPs = [];
        $jenisKokurikuler = $this->getJenisKokurikuler($document);
        
        if ($jenisKokurikuler === 'lintas_disiplin') {
            $detail = json_decode($document['kegiatan_detail'], true);
            
            // Log the detail for debugging
            log_message('info', 'Kokurikuler View - kegiatan_detail: ' . $document['kegiatan_detail']);
            log_message('info', 'Kokurikuler View - decoded detail: ' . json_encode($detail));
            
            if ($detail && isset($detail['items']) && is_array($detail['items'])) {
                // New format: items array with subject_id, tp_id, dimensi_profil
                log_message('info', 'Kokurikuler View - Using NEW format with items array');
                
                foreach ($detail['items'] as $item) {
                    $subjectId = $item['subject_id'];
                    $tpId = $item['tp_id'];
                    $dimensi = $item['dimensi_profil'];
                    
                    // Get subject name
                    if (!isset($subjectNames[$subjectId])) {
                        $subject = $this->subjectModel->find($subjectId);
                        if ($subject) {
                            $subjectNames[$subjectId] = $subject['name'];
                            $subjectTPs[$subjectId] = [];
                        }
                    }
                    
                    // Get TP data
                    $tp = $this->tpModel->find($tpId);
                    if ($tp) {
                        $subjectTPs[$subjectId][] = [
                            'id' => $tp['id'],
                            'kode_tp' => $tp['kode_tp'],
                            'deskripsi' => $tp['deskripsi'],
                            'dimensi_profil' => $dimensi,
                            'sub_dimensi' => $item['sub_dimensi'] ?? ''
                        ];
                        log_message('info', "Kokurikuler View - Found TP $tpId for subject $subjectId with dimensi: $dimensi, sub_dimensi: " . ($item['sub_dimensi'] ?? 'none'));
                    }
                }
            } elseif ($detail && isset($detail['subjects']) && is_array($detail['subjects'])) {
                // Old format: subjects array with separate tujuan_pembelajaran array
                log_message('info', 'Kokurikuler View - Using OLD format with subjects array');
                
                $tpData = $detail['tujuan_pembelajaran'] ?? [];
                $isOldFormat = !empty($tpData) && !is_array($tpData[0]);
                
                log_message('info', 'Kokurikuler View - TP Data format: ' . ($isOldFormat ? 'OLD (flat)' : 'NEW (2D)'));
                
                foreach ($detail['subjects'] as $index => $subjectId) {
                    $subject = $this->subjectModel->find($subjectId);
                    if ($subject) {
                        $subjectNames[$subjectId] = $subject['name'];
                        
                        log_message('info', "Kokurikuler View - Subject $index (ID: $subjectId): " . $subject['name']);
                        
                        // Get TPs for this subject
                        $tpIds = [];
                        
                        if ($isOldFormat) {
                            // Old format: flat array, all TPs in one array
                            $tpIds = $tpData;
                            log_message('info', "Kokurikuler View - Using OLD format, all TPs: " . json_encode($tpIds));
                        } else {
                            // New format: 2D array, TPs grouped by subject
                            if (isset($tpData[$index]) && is_array($tpData[$index])) {
                                $tpIds = $tpData[$index];
                                log_message('info', "Kokurikuler View - Using NEW format, TP IDs for subject $index: " . json_encode($tpIds));
                            }
                        }
                        
                        if (!empty($tpIds)) {
                            $tps = [];
                            foreach ($tpIds as $tpId) {
                                $tp = $this->tpModel->find($tpId);
                                if ($tp) {
                                    $tps[] = [
                                        'id' => $tp['id'],
                                        'kode_tp' => $tp['kode_tp'],
                                        'deskripsi' => $tp['deskripsi']
                                    ];
                                    log_message('info', "Kokurikuler View - Found TP $tpId: " . $tp['kode_tp'] . ' - ' . $tp['deskripsi']);
                                } else {
                                    log_message('warning', "Kokurikuler View - TP $tpId not found in database");
                                }
                            }
                            $subjectTPs[$subjectId] = $tps;
                        } else {
                            log_message('warning', "Kokurikuler View - No TPs found for subject index $index");
                        }
                    }
                }
            }
            
            log_message('info', 'Kokurikuler View - Final subjectTPs: ' . json_encode($subjectTPs));
        }

        return view('admin/kokurikuler/view', [
            'title' => 'Detail Dokumen Kokurikuler',
            'document' => $document,
            'subjectNames' => $subjectNames,
            'subjectTPs' => $subjectTPs
        ]);
    }

    /**
     * Export to PDF
     */
    public function exportPDF($id)
    {
        $document = $this->documentModel->getDocumentWithDetails($id);
        
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        // Check access
        $user = session()->get('user');
        if ($user['role_id'] != 1 && $document['created_by'] != $user['id']) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses');
        }

        // Check if document is completed
        if ($document['status'] !== 'completed') {
            return redirect()->back()->with('error', 'Dokumen belum selesai. Silakan generate AI terlebih dahulu.');
        }

        // Get school info including principal
        $db = \Config\Database::connect();
        $school = $db->table('school_profile')->get()->getRowArray();
        $document['school_name'] = $school['name'] ?? 'Nama Sekolah';
        $document['headmaster'] = $school['headmaster'] ?? '';
        $document['principal_nip'] = $school['principal_nip'] ?? '';
        $document['city_regency'] = $school['city_regency'] ?? '';

        // Get teacher info (creator or wali kelas yang menggunakan template)
        $teacherModel = new \App\Models\TeacherModel();
        $userModel = new \App\Models\UserModel();
        
        // Jika dokumen ini digunakan oleh wali kelas (used_by_teacher_id), gunakan data wali kelas
        if (!empty($document['used_by_teacher_id'])) {
            $teacher = $teacherModel->find($document['used_by_teacher_id']);
            if ($teacher) {
                $document['teacher_name'] = $teacher['name'];
                $document['teacher_nip'] = $teacher['nip'] ?? '';
            } else {
                $document['teacher_name'] = 'Wali Kelas';
                $document['teacher_nip'] = '';
            }
        } else {
            // Gunakan data creator
            $creator = $userModel->find($document['created_by']);
            if ($creator && $creator['related_type'] === 'teacher' && $creator['related_id']) {
                $teacher = $teacherModel->find($creator['related_id']);
                if ($teacher) {
                    $document['teacher_name'] = $teacher['name'];
                    $document['teacher_nip'] = $teacher['nip'] ?? '';
                }
            }
            
            // Fallback to creator name if teacher not found
            if (!isset($document['teacher_name'])) {
                $document['teacher_name'] = $document['creator_name'] ?? '';
                $document['teacher_nip'] = '';
            }
        }

        // Get subject names and TPs if lintas disiplin
        $subjectNames = [];
        $subjectTPs = [];
        $jenisKokurikuler = $this->getJenisKokurikuler($document);
        
        if ($jenisKokurikuler === 'lintas_disiplin') {
            $detail = json_decode($document['kegiatan_detail'], true);
            
            if ($detail && isset($detail['items']) && is_array($detail['items'])) {
                // New format: items array with subject_id, tp_id, dimensi_profil
                log_message('info', 'ExportPDF - Using NEW format with items array');
                
                foreach ($detail['items'] as $item) {
                    $subjectId = $item['subject_id'];
                    $tpId = $item['tp_id'];
                    $dimensi = $item['dimensi_profil'];
                    
                    // Get subject name
                    if (!isset($subjectNames[$subjectId])) {
                        $subject = $this->subjectModel->find($subjectId);
                        if ($subject) {
                            $subjectNames[$subjectId] = $subject['name'];
                            $subjectTPs[$subjectId] = [];
                        }
                    }
                    
                    // Get TP data
                    $tp = $this->tpModel->find($tpId);
                    if ($tp) {
                        $subjectTPs[$subjectId][] = [
                            'id' => $tp['id'],
                            'kode_tp' => $tp['kode_tp'],
                            'deskripsi' => $tp['deskripsi'],
                            'dimensi_profil' => $dimensi,
                            'sub_dimensi' => $item['sub_dimensi'] ?? ''
                        ];
                    }
                }
            } elseif ($detail && isset($detail['subjects']) && is_array($detail['subjects'])) {
                // Old format: subjects array with separate tujuan_pembelajaran array
                log_message('info', 'ExportPDF - Using OLD format with subjects array');
                
                $tpData = $detail['tujuan_pembelajaran'] ?? [];
                $isOldFormat = !empty($tpData) && !is_array($tpData[0]);
                
                foreach ($detail['subjects'] as $index => $subjectId) {
                    $subject = $this->subjectModel->find($subjectId);
                    if ($subject) {
                        $subjectNames[$subjectId] = $subject['name'];
                        
                        // Get TPs for this subject
                        $tpIds = [];
                        
                        if ($isOldFormat) {
                            // Old format: flat array, all TPs in one array
                            $tpIds = $tpData;
                        } else {
                            // New format: 2D array, TPs grouped by subject
                            if (isset($tpData[$index]) && is_array($tpData[$index])) {
                                $tpIds = $tpData[$index];
                            }
                        }
                        
                        if (!empty($tpIds)) {
                            $tps = [];
                            foreach ($tpIds as $tpId) {
                                $tp = $this->tpModel->find($tpId);
                                if ($tp) {
                                    $tps[] = [
                                        'id' => $tp['id'],
                                        'kode_tp' => $tp['kode_tp'],
                                        'deskripsi' => $tp['deskripsi']
                                    ];
                                }
                            }
                            $subjectTPs[$subjectId] = $tps;
                        }
                    }
                }
            }
        }

        // Select template based on jenis_kokurikuler
        $template = match($jenisKokurikuler) {
            'lintas_disiplin' => 'admin/kokurikuler/pdf_lintas_disiplin',
            '7kaih' => 'admin/kokurikuler/pdf_7kaih',
            'lainnya' => 'admin/kokurikuler/pdf_lainnya',
            default => 'admin/kokurikuler/pdf_lainnya'
        };

        // Generate PDF
        $dompdf = new \Dompdf\Dompdf();
        $html = view($template, [
            'document' => $document,
            'subjectNames' => $subjectNames,
            'subjectTPs' => $subjectTPs
        ]);
        
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        $filename = 'Kokurikuler_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $document['tema']) . '_' . date('YmdHis') . '.pdf';
        if (ob_get_length()) {
            @ob_end_clean();
        }
        $dompdf->stream($filename, ['Attachment' => true]);
        exit;
    }

    /**
     * Delete Document
     */
    public function delete($id)
    {
        $document = $this->documentModel->find($id);
        
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        // Check access
        $user = session()->get('user');
        if ($user['role_id'] != 1 && $document['created_by'] != $user['id']) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses');
        }

        $this->documentModel->delete($id);
        return redirect()->to('admin/kokurikuler')->with('success', 'Dokumen berhasil dihapus');
    }

    /**
     * Pelaksanaan - Coming Soon (Tahap 2)
     */
    public function pelaksanaan()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        // Admin, Kepsek, dan Guru Kelas bisa akses
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
        }

        // Get documents yang sudah completed (hanya yang bisa dilaksanakan)
        $documents = $this->documentModel
            ->select('kokurikuler_documents.*, academic_years.year as year_name')
            ->join('academic_years', 'academic_years.id = kokurikuler_documents.year_id', 'left')
            ->where('kokurikuler_documents.status', 'completed');

        // Filter untuk guru kelas
        if ($roleId == 3) {
            $db = \Config\Database::connect();
            $userRecord = $db->table('users')->where('id', $userId)->get()->getRow();
            $teacherId = $userRecord->related_id ?? 0;
            
            $documents->groupStart()
                ->where('kokurikuler_documents.created_by', $userId)
                ->orWhere('kokurikuler_documents.used_by_teacher_id', $teacherId)
                ->groupEnd();
        }
        // Kepsek bisa lihat semua (tidak perlu filter)

        $documents = $documents->orderBy('kokurikuler_documents.created_at', 'DESC')->findAll();

        // Get summary untuk setiap dokumen
        foreach ($documents as &$doc) {
            $doc['summary'] = $this->pelaksanaanModel->getSummary($doc['id']);
        }

        return view('admin/kokurikuler/pelaksanaan', [
            'title' => 'Pelaksanaan Kokurikuler',
            'documents' => $documents,
            'isReadOnly' => ($roleId == 2), // Kepsek read-only
        ]);
    }

    /**
     * Detail Pelaksanaan - Form untuk input pelaksanaan per kegiatan
     */
    public function pelaksanaanDetail($documentId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        // Kepsek tidak bisa create/edit pelaksanaan
        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        // Get document
        $document = $this->documentModel->getDocumentWithDetails($documentId);
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        // Check access
        if ($roleId == 3) {
            $db = \Config\Database::connect();
            $userRecord = $db->table('users')->where('id', $userId)->get()->getRow();
            $teacherId = $userRecord->related_id ?? 0;
            
            if ($document['created_by'] != $userId && $document['used_by_teacher_id'] != $teacherId) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke dokumen ini.');
            }
        }

        // Check if document is completed
        if ($document['status'] !== 'completed') {
            return redirect()->back()->with('error', 'Dokumen belum selesai. Hanya dokumen yang sudah selesai yang bisa dilaksanakan.');
        }

        // Initialize pelaksanaan records if not exists
        $this->pelaksanaanModel->initializePelaksanaan($documentId, $document['jumlah_pertemuan'], $userId);

        // Get kegiatan kokurikuler
        $kegiatanList = json_decode($document['kegiatan_kokurikuler'], true);

        // Get pelaksanaan data
        $pelaksanaanData = $this->pelaksanaanModel->getPelaksanaanByDocument($documentId);
        
        // Map pelaksanaan by pertemuan_ke
        $pelaksanaanMap = [];
        foreach ($pelaksanaanData as $p) {
            $pelaksanaanMap[$p['pertemuan_ke']] = $p;
        }

        return view('admin/kokurikuler/pelaksanaan_detail', [
            'title' => 'Detail Pelaksanaan - ' . $document['tema'],
            'document' => $document,
            'kegiatanList' => $kegiatanList,
            'pelaksanaanMap' => $pelaksanaanMap,
        ]);
    }

    /**
     * Save Pelaksanaan - Simpan data pelaksanaan
     */
    public function savePelaksanaan()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $user = session()->get('user');
        $userId = $user['id'] ?? 0;

        $pelaksanaanId = $this->request->getPost('pelaksanaan_id');
        $status = $this->request->getPost('status');

        $data = [
            'status' => $status,
        ];

        if ($status === 'terlaksana') {
            $data['tanggal_pelaksanaan'] = $this->request->getPost('tanggal_pelaksanaan');
            $data['catatan_pelaksanaan'] = $this->request->getPost('catatan_pelaksanaan');
            $data['alasan_tidak_terlaksana'] = null;

            // Handle file upload - hanya simpan satu file (file baru menimpa file lama)
            $files = $this->request->getFiles();
            if (isset($files['dokumentasi'])) {
                $hasNewFile = false;
                foreach ($files['dokumentasi'] as $file) {
                    if ($file->isValid() && !$file->hasMoved()) {
                        $hasNewFile = true;
                        break;
                    }
                }
                
                if ($hasNewFile) {
                    // Hapus file lama jika ada
                    $oldData = $this->pelaksanaanModel->find($pelaksanaanId);
                    if ($oldData && !empty($oldData['dokumentasi'])) {
                        $oldFiles = json_decode($oldData['dokumentasi'], true);
                        if (is_array($oldFiles)) {
                            foreach ($oldFiles as $oldFile) {
                                $oldFilePath = WRITEPATH . $oldFile;
                                if (file_exists($oldFilePath)) {
                                    @unlink($oldFilePath);
                                }
                            }
                        }
                    }
                    
                    // Upload file baru (hanya satu file)
                    $uploadedFile = null;
                    foreach ($files['dokumentasi'] as $file) {
                        if ($file->isValid() && !$file->hasMoved()) {
                            $newName = $file->getRandomName();
                            
                            $uploadPath = FCPATH . 'uploads/kokurikuler/';
                            if (!is_dir($uploadPath)) {
                                mkdir($uploadPath, 0755, true);
                            }
                            
                            $file->move($uploadPath, $newName);
                            $uploadedFile = 'uploads/kokurikuler/' . $newName;
                            break; // Hanya ambil file pertama
                        }
                    }
                    
                    if ($uploadedFile) {
                        $data['dokumentasi'] = json_encode([$uploadedFile]);
                    }
                }
            }
        } elseif ($status === 'tidak_terlaksana') {
            $data['alasan_tidak_terlaksana'] = $this->request->getPost('alasan_tidak_terlaksana');
            $data['tanggal_pelaksanaan'] = null;
            $data['catatan_pelaksanaan'] = null;
            
            // Hapus file dokumentasi jika ada
            $oldData = $this->pelaksanaanModel->find($pelaksanaanId);
            if ($oldData && !empty($oldData['dokumentasi'])) {
                $oldFiles = json_decode($oldData['dokumentasi'], true);
                if (is_array($oldFiles)) {
                    foreach ($oldFiles as $oldFile) {
                        $oldFilePath = WRITEPATH . $oldFile;
                        if (file_exists($oldFilePath)) {
                            @unlink($oldFilePath);
                        }
                    }
                }
            }
            $data['dokumentasi'] = null;
        } elseif ($status === 'belum_dilaksanakan') {
            // Hapus semua data jika kembali ke belum dilaksanakan
            $data['tanggal_pelaksanaan'] = null;
            $data['catatan_pelaksanaan'] = null;
            $data['alasan_tidak_terlaksana'] = null;
            
            // Hapus file dokumentasi jika ada
            $oldData = $this->pelaksanaanModel->find($pelaksanaanId);
            if ($oldData && !empty($oldData['dokumentasi'])) {
                $oldFiles = json_decode($oldData['dokumentasi'], true);
                if (is_array($oldFiles)) {
                    foreach ($oldFiles as $oldFile) {
                        $oldFilePath = WRITEPATH . $oldFile;
                        if (file_exists($oldFilePath)) {
                            @unlink($oldFilePath);
                        }
                    }
                }
            }
            $data['dokumentasi'] = null;
        }

        try {
            $this->pelaksanaanModel->update($pelaksanaanId, $data);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Data pelaksanaan berhasil disimpan.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Save pelaksanaan error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Serve File - Serve uploaded files from writable directory
     */
    public function serveFile($filename)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;

        // Only admin and guru kelas can access
        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        // Decode filename (in case it has special characters)
        $filename = urldecode($filename);
        
        // Security: prevent directory traversal
        $filename = str_replace(['..', '\\', '/'], '', $filename);
        
        $filePath = FCPATH . 'uploads/kokurikuler/' . $filename;

        if (!file_exists($filePath)) {
            log_message('error', 'File not found: ' . $filePath);
            return $this->response->setStatusCode(404, 'File not found');
        }

        // Get mime type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        // Set headers
        $this->response->setHeader('Content-Type', $mimeType);
        $this->response->setHeader('Content-Length', filesize($filePath));
        
        // For images and PDFs, display inline; for others, download
        if (strpos($mimeType, 'image/') === 0 || $mimeType === 'application/pdf') {
            $this->response->setHeader('Content-Disposition', 'inline; filename="' . basename($filePath) . '"');
        } else {
            $this->response->setHeader('Content-Disposition', 'attachment; filename="' . basename($filePath) . '"');
        }

        // Output file
        $this->response->setBody(file_get_contents($filePath));
        return $this->response;
    }

    /**
     * Penilaian/Asesmen - Coming Soon (Tahap 3)
     */
    public function penilaian()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        // Admin, Kepsek, dan Guru Kelas bisa akses
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
        }

        // Get documents yang sudah completed (hanya yang bisa dinilai)
        $documents = $this->documentModel
            ->select('kokurikuler_documents.*, academic_years.year as year_name')
            ->join('academic_years', 'academic_years.id = kokurikuler_documents.year_id', 'left')
            ->where('kokurikuler_documents.status', 'completed');

        // Filter untuk guru kelas
        if ($roleId == 3) {
            $db = \Config\Database::connect();
            $userRecord = $db->table('users')->where('id', $userId)->get()->getRow();
            $teacherId = $userRecord->related_id ?? 0;
            
            $documents->groupStart()
                ->where('kokurikuler_documents.created_by', $userId)
                ->orWhere('kokurikuler_documents.used_by_teacher_id', $teacherId)
                ->groupEnd();
        }
        // Kepsek bisa lihat semua (tidak perlu filter)

        $documents = $documents->orderBy('kokurikuler_documents.created_at', 'DESC')->findAll();

        // Get summary untuk setiap dokumen
        foreach ($documents as &$doc) {
            // Generate rubrik if not exists
            $this->rubrikModel->generateRubrikFromDocument($doc['id']);
            
            // Get penilaian summary
            $doc['summary'] = $this->penilaianModel->getSummary($doc['id']);
        }

        return view('admin/kokurikuler/penilaian', [
            'title' => 'Penilaian/Asesmen Kokurikuler',
            'documents' => $documents,
            'isReadOnly' => ($roleId == 2), // Kepsek read-only
        ]);
    }

    /**
     * Detail Penilaian - Daftar siswa dengan status penilaian
     */
    public function penilaianDetail($documentId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        // Get document
        $document = $this->documentModel->getDocumentWithDetails($documentId);
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        // Check access
        if ($roleId == 3) {
            $db = \Config\Database::connect();
            $userRecord = $db->table('users')->where('id', $userId)->get()->getRow();
            $teacherId = $userRecord->related_id ?? 0;
            
            if ($document['created_by'] != $userId && $document['used_by_teacher_id'] != $teacherId) {
                return redirect()->back()->with('error', 'Anda tidak memiliki akses ke dokumen ini.');
            }
        }

        // Check if document is completed
        if ($document['status'] !== 'completed') {
            return redirect()->back()->with('error', 'Dokumen belum selesai. Hanya dokumen yang sudah selesai yang bisa dinilai.');
        }

        // Generate rubrik if not exists
        $this->rubrikModel->generateRubrikFromDocument($documentId);

        // Get students with penilaian status
        $students = $this->penilaianModel->getStudentsWithPenilaianStatus($documentId);

        // Get summary
        $summary = $this->penilaianModel->getSummary($documentId);

        return view('admin/kokurikuler/penilaian_detail', [
            'title' => 'Penilaian - ' . $document['tema'],
            'document' => $document,
            'students' => $students,
            'summary' => $summary,
        ]);
    }

    /**
     * Form Penilaian Per Siswa
     */
    public function penilaianForm($documentId, $studentId)
    {
        log_message('info', '=== PENILAIAN FORM START === Document: ' . $documentId . ', Student: ' . $studentId);
        
        // Set JSON header
        $this->response->setContentType('application/json');
        
        log_message('info', 'PenilaianForm - Request URI: ' . $this->request->getUri());
        log_message('info', 'PenilaianForm - Is AJAX: ' . ($this->request->isAJAX() ? 'yes' : 'no'));
        
        if (!$this->request->isAJAX()) {
            log_message('warning', 'PenilaianForm - Not an AJAX request');
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request type']);
        }

        $user = session()->get('user');
        log_message('info', 'PenilaianForm - User session: ' . json_encode($user));
        
        if (!$user) {
            log_message('error', 'PenilaianForm - No user session found');
            return $this->response->setJSON(['success' => false, 'message' => 'Session expired. Please login again.']);
        }
        
        $roleId = $user['role_id'] ?? 0;

        if (!in_array($roleId, [1, 3])) {
            log_message('error', 'PenilaianForm - Access denied for role: ' . $roleId);
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        try {
            // Generate rubrik if not exists (skip if already exists)
            log_message('info', 'PenilaianForm - Checking rubrik for document: ' . $documentId);
            
            // Direct query to check rubrik
            $db = \Config\Database::connect();
            $rubrikCount = $db->table('kokurikuler_rubrik')
                ->where('document_id', $documentId)
                ->countAllResults();
            
            log_message('info', 'PenilaianForm - Direct query rubrik count: ' . $rubrikCount);
            
            $existingRubrik = $this->rubrikModel->getRubrikByDocument($documentId);
            
            log_message('info', 'PenilaianForm - Model query rubrik count: ' . count($existingRubrik));
            
            if (empty($existingRubrik)) {
                log_message('info', 'PenilaianForm - No rubrik found, generating for document: ' . $documentId);
                
                // Get document to check if it's completed
                $document = $this->documentModel->find($documentId);
                if (!$document) {
                    log_message('error', 'PenilaianForm - Document not found: ' . $documentId);
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Dokumen tidak ditemukan'
                    ]);
                }
                
                log_message('info', 'PenilaianForm - Document status: ' . $document['status']);
                
                if ($document['status'] !== 'completed') {
                    log_message('error', 'PenilaianForm - Document not completed: ' . $documentId);
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Dokumen belum selesai. Hanya dokumen yang sudah selesai yang bisa dinilai.'
                    ]);
                }
                
                // Delete existing rubrik first (in case of partial insert)
                log_message('info', 'PenilaianForm - Deleting existing rubrik (if any)');
                $db->table('kokurikuler_rubrik')->where('document_id', $documentId)->delete();
                
                $generated = $this->rubrikModel->generateRubrikFromDocument($documentId);
                log_message('info', 'PenilaianForm - Generate result: ' . ($generated ? 'success' : 'failed'));
                
                // Re-fetch rubrik after generation
                $existingRubrik = $this->rubrikModel->getRubrikByDocument($documentId);
                log_message('info', 'PenilaianForm - Rubrik count after generation: ' . count($existingRubrik));
                
                if (empty($existingRubrik)) {
                    log_message('error', 'PenilaianForm - Failed to generate rubrik for document: ' . $documentId);
                    
                    // Check if document has required data
                    log_message('error', 'PenilaianForm - Document data: ' . json_encode($document));
                    
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Gagal membuat rubrik penilaian. Silakan hubungi administrator.'
                    ]);
                }
            }
            
            log_message('info', 'PenilaianForm - Final rubrik count: ' . count($existingRubrik));
            
            if (!empty($existingRubrik)) {
                log_message('info', 'PenilaianForm - First rubrik item: ' . json_encode($existingRubrik[0]));
            }

            // Get existing penilaian
            $penilaian = $this->penilaianModel->getPenilaianByStudentAndDocument($studentId, $documentId);
            log_message('info', 'PenilaianForm - Penilaian data: ' . json_encode($penilaian));

            // Get student info
            $db = \Config\Database::connect();
            $student = $db->table('students')->where('id', $studentId)->get()->getRowArray();
            log_message('info', 'PenilaianForm - Student data: ' . json_encode($student));

            return $this->response->setJSON([
                'success' => true,
                'rubrik' => $existingRubrik,
                'penilaian' => $penilaian,
                'student' => $student,
                'debug' => [
                    'document_id' => $documentId,
                    'student_id' => $studentId,
                    'rubrik_count' => count($existingRubrik),
                    'timestamp' => date('Y-m-d H:i:s'),
                ],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'PenilaianForm error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Save Penilaian
     */
    public function savePenilaian()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        $user = session()->get('user');
        $userId = $user['id'] ?? 0;

        $documentId = $this->request->getPost('document_id');
        $studentId = $this->request->getPost('student_id');
        $penilaianDetail = $this->request->getPost('penilaian_detail'); // JSON string
        $catatanTambahan = $this->request->getPost('catatan_tambahan');

        // Validation
        if (!$documentId || !$studentId || !$penilaianDetail) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak lengkap'
            ]);
        }

        // Check if penilaian already exists
        $existing = $this->penilaianModel->getPenilaianByStudentAndDocument($studentId, $documentId);

        $data = [
            'document_id' => $documentId,
            'student_id' => $studentId,
            'penilaian_detail' => $penilaianDetail,
            'catatan_tambahan' => $catatanTambahan,
            'created_by' => $userId,
        ];

        try {
            if ($existing) {
                // Update
                $this->penilaianModel->update($existing['id'], $data);
            } else {
                // Insert
                $this->penilaianModel->insert($data);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Penilaian berhasil disimpan.'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Save penilaian error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan penilaian: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Get Rubrik for Penilaian (NEW - for batch penilaian)
     */
    /**
     * Get Rubrik for Batch Penilaian (NEW - for batch penilaian)
     */
    public function getRubrik($documentId)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            log_message('info', '=== GET RUBRIK START === Document ID: ' . $documentId);
            
            // Check if rubrik needs regeneration (missing sub_dimensi)
            $rubrik = $this->rubrikModel->getRubrikByDocument($documentId);
            
            log_message('info', 'Rubrik count: ' . count($rubrik));
            
            // Check if any rubrik is missing sub_dimensi
            $needsRegeneration = false;
            $missingCount = 0;
            foreach ($rubrik as $r) {
                if (empty($r['sub_dimensi'])) {
                    $needsRegeneration = true;
                    $missingCount++;
                    log_message('info', 'Rubrik ID ' . $r['id'] . ' missing sub_dimensi');
                }
            }
            
            log_message('info', 'Needs regeneration: ' . ($needsRegeneration ? 'YES' : 'NO') . ', Missing count: ' . $missingCount);
            
            // If needs regeneration, delete and regenerate
            if ($needsRegeneration) {
                log_message('info', 'Starting rubrik regeneration for document: ' . $documentId);
                
                // Delete old rubrik
                $deleted = $this->rubrikModel->where('document_id', $documentId)->delete();
                log_message('info', 'Deleted old rubrik, affected rows: ' . $deleted);
                
                // Regenerate
                $generated = $this->rubrikModel->generateRubrikFromDocument($documentId);
                log_message('info', 'Generate result: ' . ($generated ? 'SUCCESS' : 'FAILED'));
                
                // Reload rubrik
                $rubrik = $this->rubrikModel->getRubrikByDocument($documentId);
                log_message('info', 'Reloaded rubrik count: ' . count($rubrik));
                
                // Log first item for verification
                if (count($rubrik) > 0) {
                    log_message('info', 'First rubrik item: ' . json_encode($rubrik[0]));
                }
            }
            
            log_message('info', '=== GET RUBRIK END === Returning ' . count($rubrik) . ' items');
            
            return $this->response->setJSON([
                'success' => true,
                'rubrik' => $rubrik,
                'regenerated' => $needsRegeneration
            ]);
        } catch (\Exception $e) {
            log_message('error', 'GET RUBRIK ERROR: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * TEST ENDPOINT - to verify routing works
     */
    public function testAjax()
    {
        return $this->response->setJSON([
            'success' => true,
            'message' => 'Test endpoint works!',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Get Students and Penilaian Data for specific rubrik (NEW - for batch penilaian)
     */
    public function getStudentsPenilaian($documentId, $rubrikId)
    {
        // IMMEDIATE RESPONSE TEST - to confirm method is reached
        file_put_contents(WRITEPATH . 'logs/debug_ajax.txt', date('Y-m-d H:i:s') . " - Method called with doc=$documentId, rubrik=$rubrikId\n", FILE_APPEND);
        
        // Force logging with error_log to bypass any log level filtering
        error_log('=== KOKURIKULER GET STUDENTS PENILAIAN CALLED ===');
        error_log('Document ID: ' . $documentId . ', Rubrik ID: ' . $rubrikId);
        
        // Check if AJAX
        $isAjax = $this->request->isAJAX();
        $hasXHR = $this->request->getHeaderLine('X-Requested-With') === 'XMLHttpRequest';
        file_put_contents(WRITEPATH . 'logs/debug_ajax.txt', "  - isAJAX(): " . ($isAjax ? 'true' : 'false') . "\n", FILE_APPEND);
        file_put_contents(WRITEPATH . 'logs/debug_ajax.txt', "  - X-Requested-With: " . $this->request->getHeaderLine('X-Requested-With') . "\n", FILE_APPEND);
        
        // TEMPORARILY DISABLE AJAX CHECK FOR DEBUGGING
        // if (!$this->request->isAJAX()) {
        //     error_log('Not AJAX request, redirecting');
        //     file_put_contents(WRITEPATH . 'logs/debug_ajax.txt', "  - Not AJAX, redirecting\n", FILE_APPEND);
        //     return redirect()->back();
        // }
        
        file_put_contents(WRITEPATH . 'logs/debug_ajax.txt', "  - Proceeding with query\n", FILE_APPEND);

        try {
            $document = $this->documentModel->find($documentId);
            error_log('Document found: ' . json_encode($document));
            file_put_contents(WRITEPATH . 'logs/debug_ajax.txt', "  - Document: " . json_encode($document) . "\n", FILE_APPEND);
            
            if (!$document) {
                error_log('Document not found!');
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Dokumen tidak ditemukan'
                ]);
            }

            log_message('info', '=== GET STUDENTS PENILAIAN START ===');
            log_message('info', 'Document ID: ' . $documentId);
            log_message('info', 'Rubrik ID: ' . $rubrikId);
            log_message('info', 'Document class_id: ' . ($document['class_id'] ?? 'NULL'));
            log_message('info', 'Document year_id: ' . ($document['year_id'] ?? 'NULL'));
            
            error_log('Document class_id: ' . ($document['class_id'] ?? 'NULL'));
            error_log('Document year_id: ' . ($document['year_id'] ?? 'NULL'));

            // Get students
            $db = \Config\Database::connect();
            $students = [];

            if (!empty($document['class_id'])) {
                log_message('info', 'Querying students for class_id: ' . $document['class_id']);
                log_message('info', 'Query parameters: class_id=' . $document['class_id'] . ', academic_year_id=' . $document['year_id'] . ', status=aktif');
                
                // Specific class - get students from student_records
                $builder = $db->table('students')
                    ->select('students.id, students.nis, students.name, classes.name as class_name')
                    ->join('student_records', 'student_records.student_id = students.id')
                    ->join('classes', 'classes.id = student_records.class_id')
                    ->where('student_records.class_id', $document['class_id'])
                    ->where('student_records.academic_year_id', $document['year_id'])
                    ->where('student_records.status', 'aktif') // Status dalam bahasa Indonesia
                    ->orderBy('students.name', 'ASC');
                
                // Log the SQL query
                $sql = $builder->getCompiledSelect(false);
                log_message('info', 'SQL Query: ' . $sql);
                
                $students = $builder->get()->getResultArray();
                
                log_message('info', 'Students found: ' . count($students));
                
                // If no students found, try without status filter to debug
                if (count($students) === 0) {
                    log_message('info', 'No students found with status filter, trying without status...');
                    $studentsNoStatus = $db->table('students')
                        ->select('students.id, students.nis, students.name, student_records.status')
                        ->join('student_records', 'student_records.student_id = students.id')
                        ->where('student_records.class_id', $document['class_id'])
                        ->where('student_records.academic_year_id', $document['year_id'])
                        ->get()
                        ->getResultArray();
                    log_message('info', 'Students without status filter: ' . count($studentsNoStatus));
                    if (count($studentsNoStatus) > 0) {
                        log_message('info', 'Sample student status values: ' . json_encode(array_column($studentsNoStatus, 'status')));
                    }
                }
            } else {
                log_message('info', 'Document has no class_id - cannot get students');
            }

            // Get existing penilaian for this rubrik
            $penilaianData = [];
            $existingPenilaian = $db->table('kokurikuler_penilaian')
                ->where('document_id', $documentId)
                ->get()
                ->getResultArray();

            foreach ($existingPenilaian as $p) {
                $detail = json_decode($p['penilaian_detail'], true) ?: [];
                // Check both string and int key versions
                if (isset($detail[$rubrikId]) || isset($detail[(int)$rubrikId])) {
                    $capaian = $detail[$rubrikId] ?? $detail[(int)$rubrikId];
                    $penilaianData[(int)$p['student_id']] = [
                        'capaian' => $capaian,
                        'catatan' => $p['catatan_tambahan'] ?? ''
                    ];
                }
            }
            
            // Convert to JSON object (not array) so JS can use numeric keys
            $penilaianDataObj = (object)$penilaianData;

            log_message('info', 'Existing penilaian records: ' . count($existingPenilaian));
            log_message('info', 'Penilaian data for rubrik ' . $rubrikId . ': ' . count($penilaianData));
            log_message('info', '=== GET STUDENTS PENILAIAN END ===');
            
            error_log('Returning response: students=' . count($students) . ', penilaian_data=' . count($penilaianData));

            return $this->response->setJSON([
                'success' => true,
                'students' => $students,
                'penilaian_data' => $penilaianDataObj,
                'debug' => [
                    'document_id' => $documentId,
                    'rubrik_id' => $rubrikId,
                    'class_id' => $document['class_id'] ?? null,
                    'year_id' => $document['year_id'] ?? null,
                    'students_count' => count($students),
                    'penilaian_count' => count($penilaianData)
                ]
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Get students penilaian error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Save Batch Penilaian (NEW - for batch penilaian)
     */
    public function saveBatchPenilaian()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $user = session()->get('user');
        $userId = $user['id'] ?? 0;

        $documentId = $this->request->getPost('document_id');
        $rubrikId = $this->request->getPost('rubrik_id');
        $penilaianListJson = $this->request->getPost('penilaian_list');

        if (!$documentId || !$rubrikId || !$penilaianListJson) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak lengkap'
            ]);
        }

        try {
            $penilaianList = json_decode($penilaianListJson, true);
            
            foreach ($penilaianList as $item) {
                $studentId = $item['student_id'];
                $capaian = $item['capaian'];
                $catatan = $item['catatan'] ?? '';

                // Check if penilaian exists
                $existing = $this->penilaianModel
                    ->where('document_id', $documentId)
                    ->where('student_id', $studentId)
                    ->first();

                if ($existing) {
                    // Update existing
                    $existingDetail = json_decode($existing['penilaian_detail'], true);
                    $existingDetail[$rubrikId] = $capaian;

                    $this->penilaianModel->update($existing['id'], [
                        'penilaian_detail' => json_encode($existingDetail),
                        'catatan_tambahan' => $catatan,
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                } else {
                    // Insert new
                    $penilaianDetail = [$rubrikId => $capaian];

                    $this->penilaianModel->insert([
                        'document_id' => $documentId,
                        'student_id' => $studentId,
                        'penilaian_detail' => json_encode($penilaianDetail),
                        'catatan_tambahan' => $catatan,
                        'created_by' => $userId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Penilaian berhasil disimpan untuk semua siswa'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Save batch penilaian error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan penilaian: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Penilaian Deskripsi - Generate deskripsi naratif per siswa
     */
    public function penilaianDeskripsi($documentId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $document = $this->documentModel->getDocumentWithDetails($documentId);
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $rubrik = $db->table('kokurikuler_rubrik')
            ->where('document_id', $documentId)
            ->orderBy('dimensi_profil', 'ASC')
            ->get()->getResultArray();

        // Get students with penilaian
        $studentsWithPenilaian = $this->_getStudentsWithFullPenilaian($documentId, $document, $db);

        // Generate deskripsi for each student
        foreach ($studentsWithPenilaian as &$student) {
            $student['deskripsi'] = $this->_generateDeskripsi($student, $document, $rubrik);
        }

        return view('admin/kokurikuler/penilaian_deskripsi', [
            'title' => 'Deskripsi Penilaian - ' . $document['tema'],
            'document' => $document,
            'rubrik' => $rubrik,
            'students' => $studentsWithPenilaian,
        ]);
    }

    /**
     * Penilaian Cetak - Print view
     */
    public function penilaianCetak($documentId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $document = $this->documentModel->getDocumentWithDetails($documentId);
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $rubrik = $db->table('kokurikuler_rubrik')
            ->where('document_id', $documentId)
            ->orderBy('dimensi_profil', 'ASC')
            ->get()->getResultArray();

        $studentsWithPenilaian = $this->_getStudentsWithFullPenilaian($documentId, $document, $db);

        foreach ($studentsWithPenilaian as &$student) {
            $student['deskripsi'] = $this->_generateDeskripsi($student, $document, $rubrik);
        }

        return view('admin/kokurikuler/penilaian_cetak', [
            'title' => 'Cetak Penilaian - ' . $document['tema'],
            'document' => $document,
            'rubrik' => $rubrik,
            'students' => $studentsWithPenilaian,
        ]);
    }

    /**
     * Helper: Get students with full penilaian data
     */
    private function _getStudentsWithFullPenilaian($documentId, $document, $db)
    {
        $yearModel = new \App\Models\AcademicYearModel();
        $activeYear = $yearModel->getActiveYear();

        $query = $db->table('students')
            ->select('students.id, students.nis, students.name, classes.name as class_name')
            ->join('student_records', 'student_records.student_id = students.id')
            ->join('classes', 'classes.id = student_records.class_id')
            ->where('student_records.academic_year_id', $activeYear['id'])
            ->where('student_records.status', 'aktif');

        if (!empty($document['class_id'])) {
            $query->where('student_records.class_id', $document['class_id']);
        } else {
            $levelKelas = $document['level_kelas'];
            if (strpos($levelKelas, ',') !== false) {
                $query->whereIn('classes.level', array_map('trim', explode(',', $levelKelas)));
            } else {
                $query->where('classes.level', $levelKelas);
            }
        }

        $students = $query->orderBy('students.name', 'ASC')->get()->getResultArray();

        // Attach penilaian detail
        $penilaianRows = $db->table('kokurikuler_penilaian')
            ->where('document_id', $documentId)
            ->get()->getResultArray();

        $penilaianMap = [];
        foreach ($penilaianRows as $p) {
            $penilaianMap[$p['student_id']] = json_decode($p['penilaian_detail'], true) ?: [];
        }

        foreach ($students as &$student) {
            $student['penilaian_detail'] = $penilaianMap[$student['id']] ?? [];
        }

        return $students;
    }

    /**
     * Helper: Generate deskripsi naratif for a student
     */
    private function _generateDeskripsi($student, $document, $rubrik)
    {
        $capaianLabel = [
            'Berkembang' => 'berkembang',
            'Cakap'      => 'cakap',
            'Mahir'      => 'mahir',
        ];

        $kegiatan = $document['bentuk_kegiatan_konkret'] ?: $document['tema'];
        $detail = $student['penilaian_detail'];

        // Count capaian values to determine overall label
        $counts = ['Berkembang' => 0, 'Cakap' => 0, 'Mahir' => 0];
        foreach ($rubrik as $r) {
            $capaian = $detail[(string)$r['id']] ?? null;
            if ($capaian && isset($counts[$capaian])) {
                $counts[$capaian]++;
            }
        }

        // Determine overall capaian: majority wins; tie goes to higher
        if ($counts['Mahir'] >= $counts['Cakap'] && $counts['Mahir'] >= $counts['Berkembang']) {
            $overallLabel = 'sangat baik';
        } elseif ($counts['Cakap'] >= $counts['Berkembang']) {
            $overallLabel = 'baik';
        } else {
            $overallLabel = 'cukup baik';
        }

        $deskripsi = "Pada semester ini, ananda {$student['name']} menunjukkan capaian yang {$overallLabel} dalam penguatan profil lulusan, "
            . "yang ditunjukkan melalui kegiatan kokurikuler {$kegiatan}.";

        foreach ($rubrik as $r) {
            $rubrikId = (string)$r['id'];
            $capaian = $detail[$rubrikId] ?? null;
            if (!$capaian) continue;

            $label = $capaianLabel[$capaian] ?? strtolower($capaian);
            $dimensi = strtolower($r['dimensi_profil']);
            $subDimensi = strtolower($r['sub_dimensi']);

            $deskripsi .= " Pada dimensi {$dimensi}, ananda {$label} dalam subdimensi {$subDimensi}.";
        }

        return $deskripsi;
    }

    /**
     * Pelaporan - Rekap semua dokumen selesai dengan refleksi & rekomendasi
     */
    public function pelaporan()
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        // Admin, Kepsek, dan Guru Kelas bisa akses
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke modul ini.');
        }

        $db = \Config\Database::connect();

        // Get completed documents with 100% penilaian
        $docsQuery = $this->documentModel
            ->select('kokurikuler_documents.*, academic_years.year as year_name')
            ->join('academic_years', 'academic_years.id = kokurikuler_documents.year_id', 'left')
            ->where('kokurikuler_documents.status', 'completed');

        if ($roleId == 3) {
            $userRecord = $db->table('users')->where('id', $userId)->get()->getRow();
            $teacherId = $userRecord->related_id ?? 0;
            $docsQuery->groupStart()
                ->where('kokurikuler_documents.created_by', $userId)
                ->orWhere('kokurikuler_documents.used_by_teacher_id', $teacherId)
                ->groupEnd();
        }
        // Kepsek bisa lihat semua (tidak perlu filter)

        $allDocs = $docsQuery->orderBy('kokurikuler_documents.created_at', 'DESC')->findAll();

        // Filter only 100% assessed and attach data
        $documents = [];
        foreach ($allDocs as $doc) {
            $summary = $this->penilaianModel->getSummary($doc['id']);
            if ($summary['persentase'] < 100) continue;

            $rubrik = $db->table('kokurikuler_rubrik')
                ->where('document_id', $doc['id'])
                ->orderBy('dimensi_profil', 'ASC')
                ->get()->getResultArray();

            $students = $this->_getStudentsWithFullPenilaian($doc['id'], $doc, $db);
            foreach ($students as &$s) {
                $s['deskripsi'] = $this->_generateDeskripsi($s, $doc, $rubrik);
            }

            $laporan = $db->table('kokurikuler_laporan')
                ->where('document_id', $doc['id'])
                ->get()->getRowArray();

            $doc['rubrik']    = $rubrik;
            $doc['students']  = $students;
            $doc['summary']   = $summary;
            $doc['laporan']   = $laporan;
            $documents[] = $doc;
        }

        return view('admin/kokurikuler/pelaporan', [
            'title'     => 'Pelaporan Kokurikuler',
            'documents' => $documents,
            'roleId'    => $roleId,
            'isReadOnly' => ($roleId == 2), // Kepsek read-only
        ]);
    }

    /**
     * Save Refleksi & Rekomendasi
     */
    public function saveLaporan()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $user   = session()->get('user');
        $userId = $user['id'] ?? 0;
        $roleId = $user['role_id'] ?? 0;

        if (!in_array($roleId, [1, 3])) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $documentId  = $this->request->getPost('document_id');
        $refleksi    = $this->request->getPost('refleksi');
        $rekomendasi = $this->request->getPost('rekomendasi');

        if (!$documentId) {
            return $this->response->setJSON(['success' => false, 'message' => 'Document ID tidak valid']);
        }

        try {
            $db       = \Config\Database::connect();
            $existing = $db->table('kokurikuler_laporan')->where('document_id', $documentId)->get()->getRowArray();

            $data = [
                'refleksi'    => $refleksi,
                'rekomendasi' => $rekomendasi,
                'updated_at'  => date('Y-m-d H:i:s'),
            ];

            if ($existing) {
                $db->table('kokurikuler_laporan')->where('document_id', $documentId)->update($data);
            } else {
                $data['document_id'] = $documentId;
                $data['created_by']  = $userId;
                $data['created_at']  = date('Y-m-d H:i:s');
                $db->table('kokurikuler_laporan')->insert($data);
            }

            return $this->response->setJSON(['success' => true, 'message' => 'Laporan berhasil disimpan']);
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Pelaporan Cetak - Print all documents
     */
    public function pelaporanCetak()
    {
        $user   = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Akses ditolak.');
        }

        $db = \Config\Database::connect();

        $docsQuery = $this->documentModel
            ->select('kokurikuler_documents.*, academic_years.year as year_name')
            ->join('academic_years', 'academic_years.id = kokurikuler_documents.year_id', 'left')
            ->where('kokurikuler_documents.status', 'completed');

        if ($roleId == 3) {
            $userRecord = $db->table('users')->where('id', $userId)->get()->getRow();
            $teacherId  = $userRecord->related_id ?? 0;
            $docsQuery->groupStart()
                ->where('kokurikuler_documents.created_by', $userId)
                ->orWhere('kokurikuler_documents.used_by_teacher_id', $teacherId)
                ->groupEnd();
        }

        $allDocs = $docsQuery->orderBy('kokurikuler_documents.created_at', 'DESC')->findAll();

        $documents = [];
        foreach ($allDocs as $doc) {
            $summary = $this->penilaianModel->getSummary($doc['id']);
            if ($summary['persentase'] < 100) continue;

            $rubrik = $db->table('kokurikuler_rubrik')
                ->where('document_id', $doc['id'])
                ->orderBy('dimensi_profil', 'ASC')
                ->get()->getResultArray();

            $students = $this->_getStudentsWithFullPenilaian($doc['id'], $doc, $db);
            foreach ($students as &$s) {
                $s['deskripsi'] = $this->_generateDeskripsi($s, $doc, $rubrik);
            }

            $laporan = $db->table('kokurikuler_laporan')
                ->where('document_id', $doc['id'])
                ->get()->getRowArray();

            $doc['rubrik']   = $rubrik;
            $doc['students'] = $students;
            $doc['laporan']  = $laporan;
            $documents[] = $doc;
        }

        return view('admin/kokurikuler/pelaporan_cetak', [
            'title'     => 'Cetak Laporan Kokurikuler',
            'documents' => $documents,
        ]);
    }

    /**
     * Use Template - Wali Kelas menggunakan template dari Admin
     */
    public function useTemplate($templateId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        log_message('info', "UseTemplate called - Template ID: $templateId, User ID: $userId, Role ID: $roleId");

        // Hanya wali kelas yang bisa menggunakan template
        if ($roleId != 3) {
            log_message('warning', "UseTemplate - Access denied: Not a wali kelas (role_id: $roleId)");
            return redirect()->back()->with('error', 'Hanya wali kelas yang bisa menggunakan template.');
        }

        // Get template
        $template = $this->documentModel->find($templateId);
        
        log_message('info', "UseTemplate - Template data: " . json_encode($template));
        
        if (!$template) {
            log_message('error', "UseTemplate - Template not found: ID $templateId");
            return redirect()->back()->with('error', 'Template tidak ditemukan. ID: ' . $templateId);
        }

        // Validasi: template harus sudah completed
        if ($template['status'] !== 'completed') {
            log_message('warning', "UseTemplate - Template not completed: Status = {$template['status']}");
            return redirect()->back()->with('error', 'Template belum selesai. Hanya template yang sudah completed yang bisa digunakan.');
        }

        // Get teacher info
        $teacherId = $user['related_id'] ?? 0;
        $db = \Config\Database::connect();
        
        // Get kelas yang diwalikan
        $waliClass = $db->table('classes')
            ->where('teacher_id', $teacherId)
            ->get()
            ->getRow();
        
        if (!$waliClass) {
            return redirect()->back()->with('error', 'Anda belum ditugaskan sebagai wali kelas.');
        }

        // Cek apakah level kelas sesuai dengan template
        $templateLevelArray = explode(',', $template['level_kelas']);
        if (!in_array($waliClass->level, $templateLevelArray)) {
            return redirect()->back()->with('error', 'Template ini tidak sesuai dengan level kelas Anda.');
        }

        // Cek apakah sudah menggunakan template ini untuk semester dan tahun yang sama
        $existing = $this->documentModel
            ->where('class_id', $waliClass->id)
            ->where('year_id', $template['year_id'])
            ->where('semester', $template['semester'])
            ->first();
        
        if ($existing) {
            return redirect()->to('admin/kokurikuler/view/' . $existing['id'])
                ->with('info', 'Anda sudah memiliki dokumen untuk semester ini.');
        }

        // Copy template untuk wali kelas
        $newData = [
            'year_id' => $template['year_id'],
            'semester' => $template['semester'],
            'fase' => $template['fase'],
            'level_kelas' => (string)$waliClass->level, // Hanya level kelas wali kelas
            'class_id' => $waliClass->id, // Assign kelas spesifik wali kelas
            'jumlah_pertemuan' => $template['jumlah_pertemuan'],
            'dimensi_profil' => $template['dimensi_profil'],
            'tema' => $template['tema'],
            'jenis_kokurikuler' => $this->getJenisKokurikuler($template),
            'bentuk_kegiatan_konkret' => $template['bentuk_kegiatan_konkret'] ?? '',
            'kegiatan_detail' => $template['kegiatan_detail'],
            'tujuan_pembelajaran' => $template['tujuan_pembelajaran'],
            'praktik_pedagogis' => $template['praktik_pedagogis'],
            'lingkungan_belajar' => $template['lingkungan_belajar'],
            'kemitraan' => $template['kemitraan'],
            'teknologi_digital' => $template['teknologi_digital'],
            'kegiatan_kokurikuler' => $template['kegiatan_kokurikuler'],
            'status' => $template['status'],
            'is_template' => 0,
            'parent_id' => $templateId,
            'used_by_teacher_id' => $teacherId,
            'created_by' => $userId,
        ];

        try {
            $newId = $this->documentModel->insert($newData);
            return redirect()->to('admin/kokurikuler/view/' . $newId)
                ->with('success', 'Template berhasil digunakan. Dokumen ini akan menggunakan tanda tangan Anda saat export PDF.');
        } catch (\Exception $e) {
            log_message('error', 'Use template error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal menggunakan template: ' . $e->getMessage());
        }
    }

    /**
     * Activate Old Plan - Aktifkan rencana lama ke tahun ajaran baru
     */
    public function activateOldPlan($oldDocId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;
        $userId = $user['id'] ?? 0;

        if (!in_array($roleId, [1, 3])) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses.');
        }

        // Get old document
        $oldDoc = $this->documentModel->find($oldDocId);
        if (!$oldDoc) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        // Check ownership for guru kelas
        if ($roleId == 3 && $oldDoc['created_by'] != $userId && $oldDoc['used_by_teacher_id'] != ($user['related_id'] ?? 0)) {
            return redirect()->back()->with('error', 'Anda tidak memiliki akses ke dokumen ini.');
        }

        // Get active year
        $activeYear = $this->yearModel->getActiveYear();
        if (!$activeYear) {
            return redirect()->back()->with('error', 'Tidak ada tahun ajaran aktif.');
        }

        // Cek apakah sudah ada rencana untuk semester dan level ini di tahun aktif
        $existing = $this->documentModel
            ->where('year_id', $activeYear['id'])
            ->where('semester', $oldDoc['semester'])
            ->where('level_kelas', $oldDoc['level_kelas'])
            ->first();
        
        if ($existing) {
            return redirect()->back()->with('error', 'Sudah ada rencana untuk semester dan level ini di tahun ajaran aktif.');
        }

        // Copy document to new year
        $newData = [
            'year_id' => $activeYear['id'],
            'semester' => $oldDoc['semester'],
            'fase' => $oldDoc['fase'],
            'level_kelas' => $oldDoc['level_kelas'],
            'jumlah_pertemuan' => $oldDoc['jumlah_pertemuan'],
            'dimensi_profil' => $oldDoc['dimensi_profil'],
            'tema' => $oldDoc['tema'],
            'jenis_kokurikuler' => $this->getJenisKokurikuler($oldDoc),
            'bentuk_kegiatan_konkret' => $oldDoc['bentuk_kegiatan_konkret'] ?? '',
            'kegiatan_detail' => $oldDoc['kegiatan_detail'],
            'tujuan_pembelajaran' => $oldDoc['tujuan_pembelajaran'],
            'praktik_pedagogis' => $oldDoc['praktik_pedagogis'],
            'lingkungan_belajar' => $oldDoc['lingkungan_belajar'],
            'kemitraan' => $oldDoc['kemitraan'],
            'teknologi_digital' => $oldDoc['teknologi_digital'],
            'kegiatan_kokurikuler' => $oldDoc['kegiatan_kokurikuler'],
            'status' => $oldDoc['status'],
            'is_template' => $oldDoc['is_template'],
            'parent_id' => $oldDocId,
            'used_by_teacher_id' => $oldDoc['used_by_teacher_id'],
            'created_by' => $userId,
        ];

        try {
            $newId = $this->documentModel->insert($newData);
            return redirect()->to('admin/kokurikuler/view/' . $newId)
                ->with('success', 'Rencana berhasil diaktifkan untuk tahun ajaran ' . $activeYear['year']);
        } catch (\Exception $e) {
            log_message('error', 'Activate old plan error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mengaktifkan rencana: ' . $e->getMessage());
        }
    }

    /**
     * Get Available Templates - AJAX untuk wali kelas
     */
    public function getAvailableTemplates()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false]);
        }

        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;

        if ($roleId != 3) {
            return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak']);
        }

        $teacherId = $user['related_id'] ?? 0;
        $db = \Config\Database::connect();
        
        // Get kelas yang diwalikan
        $waliClass = $db->table('classes')
            ->where('teacher_id', $teacherId)
            ->get()
            ->getRow();
        
        if (!$waliClass) {
            return $this->response->setJSON(['success' => false, 'message' => 'Anda belum ditugaskan sebagai wali kelas']);
        }

        // Get active year
        $activeYear = $this->yearModel->getActiveYear();

        log_message('info', "=== GET AVAILABLE TEMPLATES START ===");
        log_message('info', "Teacher ID: {$teacherId}");
        log_message('info', "Wali Class ID: {$waliClass->id}, Name: {$waliClass->name}, Level: {$waliClass->level}");
        log_message('info', "Active Year ID: {$activeYear['id']}, Year: {$activeYear['year']}");

        // Get dokumen yang sudah digunakan wali kelas ini
        $usedTemplateIds = $this->documentModel
            ->select('parent_id')
            ->where('used_by_teacher_id', $teacherId)
            ->where('year_id', $activeYear['id'])
            ->findAll();
        
        $usedIds = array_filter(array_column($usedTemplateIds, 'parent_id'));
        
        log_message('info', "Used template IDs by this teacher: " . json_encode($usedIds));
        
        // Get ALL documents first for debugging
        $allDocs = $this->documentModel
            ->select('id, tema, class_id, status, level_kelas, year_id, created_by')
            ->where('year_id', $activeYear['id'])
            ->findAll();
        
        log_message('info', "Total documents in active year: " . count($allDocs));
        foreach ($allDocs as $doc) {
            $isTemplate = is_null($doc['class_id']) || $doc['class_id'] === '';
            $isCompleted = $doc['status'] === 'completed';
            $levelMatch = strpos($doc['level_kelas'], (string)$waliClass->level) !== false;
            
            log_message('info', "Doc ID {$doc['id']}: tema='{$doc['tema']}', class_id=" . ($doc['class_id'] ?? 'NULL') . 
                ", status={$doc['status']}, level_kelas={$doc['level_kelas']}, " .
                "isTemplate=" . ($isTemplate ? 'YES' : 'NO') . 
                ", isCompleted=" . ($isCompleted ? 'YES' : 'NO') . 
                ", levelMatch=" . ($levelMatch ? 'YES' : 'NO'));
        }
        
        // Get templates yang sesuai dengan level kelas wali kelas
        $builder = $this->documentModel
            ->select('kokurikuler_documents.*, users.fullname as creator_name')
            ->join('users', 'users.id = kokurikuler_documents.created_by', 'left')
            ->where('kokurikuler_documents.year_id', $activeYear['id'])
            ->where('kokurikuler_documents.status', 'completed')
            ->where('kokurikuler_documents.class_id IS NULL', null, false) // HANYA template umum
            ->like('kokurikuler_documents.level_kelas', (string)$waliClass->level);
        
        // Exclude template yang sudah digunakan
        if (!empty($usedIds)) {
            $builder->whereNotIn('kokurikuler_documents.id', $usedIds);
        }
        
        $templates = $builder->orderBy('kokurikuler_documents.created_at', 'DESC')
            ->findAll();

        log_message('info', "Templates found after filter: " . count($templates));
        foreach ($templates as $t) {
            log_message('info', "Template ID {$t['id']}: tema='{$t['tema']}', creator={$t['creator_name']}, level={$t['level_kelas']}");
        }
        log_message('info', "=== GET AVAILABLE TEMPLATES END ===");

        return $this->response->setJSON([
            'success' => true,
            'data' => $templates
        ]);
    }

    /**
     * Regenerate Rubrik (Manual Fix for missing sub_dimensi)
     */
    public function regenerateRubrik($documentId)
    {
        $user = session()->get('user');
        $roleId = $user['role_id'] ?? 0;

        if (!in_array($roleId, [1, 3])) {
            return redirect()->to('/')->with('error', 'Akses ditolak');
        }

        try {
            log_message('info', '=== MANUAL REGENERATE RUBRIK === Document ID: ' . $documentId);
            
            // Check if document exists
            $document = $this->documentModel->find($documentId);
            if (!$document) {
                return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
            }
            
            // Delete old rubrik
            $deleted = $this->rubrikModel->where('document_id', $documentId)->delete();
            log_message('info', 'Deleted old rubrik, affected rows: ' . $deleted);
            
            // Regenerate
            $generated = $this->rubrikModel->generateRubrikFromDocument($documentId);
            log_message('info', 'Generate result: ' . ($generated ? 'SUCCESS' : 'FAILED'));
            
            if ($generated) {
                // Verify sub_dimensi exists
                $rubrik = $this->rubrikModel->getRubrikByDocument($documentId);
                $hasSubDimensi = false;
                foreach ($rubrik as $r) {
                    if (!empty($r['sub_dimensi'])) {
                        $hasSubDimensi = true;
                        break;
                    }
                }
                
                if ($hasSubDimensi) {
                    return redirect()->to('admin/kokurikuler/view/' . $documentId)
                        ->with('success', 'Rubrik berhasil di-regenerate dengan sub dimensi! Total: ' . count($rubrik) . ' rubrik.');
                } else {
                    return redirect()->to('admin/kokurikuler/view/' . $documentId)
                        ->with('warning', 'Rubrik di-regenerate tapi sub_dimensi masih kosong. Periksa kegiatan_detail dokumen.');
                }
            } else {
                return redirect()->back()->with('error', 'Gagal regenerate rubrik. Cek log server.');
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Regenerate rubrik error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }
}

