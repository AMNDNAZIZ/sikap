<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\TeachingJournalModel;
use App\Models\ClassModel;
use App\Models\SubjectModel;
use App\Models\AlurTujuanPembelajaranModel;
use App\Models\TeachingAssignmentModel;
use App\Models\TeacherModel;
use App\Models\ModulAjarModel;
use App\Models\SchoolModel;
use Dompdf\Dompdf;
use Dompdf\Options;

class TeachingJournalController extends BaseController
{
    protected $journalModel;
    protected $classModel;
    protected $subjectModel;
    protected $atpModel;
    protected $teachingAssignmentModel;
    protected $teacherModel;
    protected $modulModel;
    protected $schoolModel;
    protected $db;

    public function __construct()
    {
        $this->journalModel = new TeachingJournalModel();
        $this->classModel = new ClassModel();
        $this->subjectModel = new SubjectModel();
        $this->atpModel = new AlurTujuanPembelajaranModel();
        $this->teachingAssignmentModel = new TeachingAssignmentModel();
        $this->teacherModel = new TeacherModel();
        $this->modulModel = new ModulAjarModel();
        $this->schoolModel = new SchoolModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $this->ensureSchemaUpToDate();

        $data['title'] = 'Jurnal Mengajar';
        $filters = $this->getAvailableFilters();
        
        $classId = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');
        if ((int)$filters['role_id'] === 3) {
            // SEC-03: Guru di-hardlock ke identitas guru dari sesi. Abaikan parameter GET teacher_id sepenuhnya.
            $teacherId = (int)(session()->get('user')['related_id'] ?? 0);
        } else {
            // Admin & Kepala Sekolah dapat memfilter berdasarkan guru
            $teacherId = $this->request->getGet('teacher_id') ?: null;
        }
        $dateFrom = $this->request->getGet('date_from');
        $dateTo = $this->request->getGet('date_to');

        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['teachers'] = in_array($filters['role_id'], [1, 2]) ? $this->teacherModel->findAll() : [];
        $data['readonly'] = $filters['readonly'];
        
        $data['selected_class'] = $classId;
        $data['selected_subject'] = $subjectId;
        $data['selected_teacher'] = $teacherId;
        $data['date_from'] = $dateFrom;
        $data['date_to'] = $dateTo;

        $journalFilters = [];
        if ($classId) $journalFilters['class_id'] = $classId;
        if ($subjectId) $journalFilters['subject_id'] = $subjectId;
        if ($teacherId) $journalFilters['teacher_id'] = $teacherId;
        if ($dateFrom) $journalFilters['date_from'] = $dateFrom;
        if ($dateTo) $journalFilters['date_to'] = $dateTo;

        $data['journals'] = $this->journalModel->getJournals($journalFilters);

        return view('admin/teaching_journal/index', $data);
    }

    public function add()
    {
        $this->ensureSchemaUpToDate();

        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah data.');
        }

        $data['title'] = 'Tambah Jurnal Mengajar';
        $filters = $this->getAvailableFilters();
        
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];
        $data['today'] = date('Y-m-d');
        
        $data['selected_class'] = $this->request->getGet('class_id');
        $data['selected_subject'] = $this->request->getGet('subject_id');

        // Deteksi apakah guru kelas (wali kelas)
        $data['is_homeroom'] = false;
        if ($user['role_id'] == 3) {
            $teacherId = $user['related_id'] ?? null;
            $homeroomClass = $this->classModel->where('teacher_id', $teacherId)->first();
            $data['is_homeroom'] = !empty($homeroomClass);
        } elseif (in_array($user['role_id'], [1, 2])) {
            // Admin: cek berdasarkan class_id yang dipilih
            $data['is_homeroom'] = false; // admin pilih sendiri mode-nya
        }

        return view('admin/teaching_journal/form', $data);
    }

    public function edit($id)
    {
        $this->ensureSchemaUpToDate();

        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk mengubah data.');
        }

        $journal = $this->journalModel->find($id);
        if (!$journal) {
            return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Data tidak ditemukan.');
        }

        // Ownership check for teachers
        if ($user['role_id'] == 3 && $journal['teacher_id'] != $user['related_id']) {
            return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Anda tidak memiliki akses ke data ini.');
        }

        $data['title'] = 'Edit Jurnal Mengajar';
        $data['journal'] = $journal;
        $filters = $this->getAvailableFilters();
        
        $data['classes'] = $filters['classes'];
        $data['subjects'] = $filters['subjects'];

        // Deteksi guru kelas
        $data['is_homeroom'] = false;
        if ($user['role_id'] == 3) {
            $teacherId = $user['related_id'] ?? null;
            $homeroomClass = $this->classModel->where('teacher_id', $teacherId)->first();
            $data['is_homeroom'] = !empty($homeroomClass);
        }

        // Load existing multi-subjects jika ada
        $data['journal_subjects'] = $this->db->table('teaching_journal_subjects')
            ->where('journal_id', $id)
            ->get()->getResultArray();
        
        return view('admin/teaching_journal/form', $data);
    }

    public function store()
    {
        $this->ensureSchemaUpToDate();

        $user = session()->get('user');
        $roleId = (int)($user['role_id'] ?? 0);
        $currentTeacherId = (int)($user['related_id'] ?? 0);

        // SEC-04: Kepala Sekolah (role_id = 2) read-only, dilarang menambah atau mengubah jurnal
        if ($roleId === 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menambah atau mengubah data.');
        }

        if (!in_array($roleId, [1, 3], true)) {
            return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Anda tidak memiliki hak akses untuk operasi ini.');
        }

        $id = $this->request->getPost('id');
        $existingJournal = null;

        // SEC-04: Validasi kepemilikan record lama saat operasi update
        if ($id) {
            $existingJournal = $this->journalModel->find($id);
            if (!$existingJournal) {
                return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Data jurnal tidak ditemukan.');
            }

            if ($roleId === 3 && (int)$existingJournal['teacher_id'] !== $currentTeacherId) {
                return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Anda tidak memiliki hak akses untuk mengubah jurnal ini.');
            }
        }

        // Cek apakah mode multi-subject (guru kelas)
        $multiSubjects = $this->request->getPost('subjects'); // array of {subject_id, atp_id}
        $subjectId = $this->request->getPost('subject_id');
        $atpId = $this->request->getPost('atp_id');

        // Resolusi subject_id & atp_id di awal agar validasi modul mencocokkan mapel yang benar
        if (empty($subjectId) && !empty($multiSubjects) && is_array($multiSubjects)) {
            $firstSubject = $multiSubjects[0] ?? null;
            if ($firstSubject && !empty($firstSubject['subject_id'])) {
                $subjectId = $firstSubject['subject_id'];
                $atpId = $firstSubject['atp_id'] ?? $atpId;
            }
        }

        $data = [
            'date'       => $this->request->getPost('date'),
            'class_id'   => $this->request->getPost('class_id'),
            'subject_id' => $subjectId,
            'atp_id'     => $atpId,
            'notes'      => $this->request->getPost('notes'),
        ];

        // Deteksi ketersediaan kolom pada tabel database
        $hasStatusCol    = $this->db->fieldExists('status_realisasi', 'teaching_journals');
        $hasModulCol     = $this->db->fieldExists('modul_id', 'teaching_journals');
        $hasPertemuanCol = $this->db->fieldExists('pertemuan_ke', 'teaching_journals');
        $hasSnapshotCol  = $this->db->fieldExists('rencana_snapshot', 'teaching_journals');

        // Validasi status realisasi pembelajaran
        $statusRealisasi = $this->request->getPost('status_realisasi');
        $validStatuses = ['terlaksana', 'sebagian', 'tertunda'];
        if (!in_array($statusRealisasi, $validStatuses, true)) {
            $statusRealisasi = 'terlaksana';
        }
        if ($hasStatusCol) {
            $data['status_realisasi'] = $statusRealisasi;
        }

        // Validasi dan pemrosesan referensi Modul Ajar (Tahap 13C)
        $rawModulId = $this->request->getPost('modul_id');
        $rawPertemuanKe = $this->request->getPost('pertemuan_ke');

        $modulId = (!empty($rawModulId) && is_numeric($rawModulId) && (int)$rawModulId > 0) ? (int)$rawModulId : null;
        $pertemuanKe = (!empty($rawPertemuanKe) && is_numeric($rawPertemuanKe) && (int)$rawPertemuanKe > 0) ? (int)$rawPertemuanKe : null;

        // Deteksi cerdas cadangan jika modul_id atau pertemuan_ke tidak terkirim via form POST
        // namun catatan refleksi diawali rujukan rencana Modul Ajar (misal: "[Rencana Modul Ajar — Pertemuan X")
        if (!$modulId && !empty($data['notes']) && preg_match('/\[Rencana Modul Ajar\s*[-—]\s*Pertemuan\s*(\d+)/iu', $data['notes'], $pm)) {
            $inferredPertemuan = (int)$pm[1];
            $candModulQuery = $this->modulModel
                ->where('class_id', $data['class_id'])
                ->where('subject_id', $data['subject_id']);
            if ($roleId === 3) {
                $candModulQuery->where('teacher_id', $currentTeacherId);
            }
            $candModul = $candModulQuery
                ->orderBy('updated_at', 'DESC')
                ->first();
            if ($candModul) {
                $modulId = (int)$candModul['id'];
                if (!$pertemuanKe) {
                    $pertemuanKe = $inferredPertemuan;
                }
            }
        } elseif ($modulId && !$pertemuanKe && !empty($data['notes']) && preg_match('/\[Rencana Modul Ajar\s*[-—]\s*Pertemuan\s*(\d+)/iu', $data['notes'], $pm)) {
            $pertemuanKe = (int)$pm[1];
        }

        $existingJournal = $id ? $this->journalModel->find($id) : null;

        if ($modulId) {
            $modul = $this->modulModel->find($modulId);
            $isValidModul = false;

            if ($modul) {
                // Kecocokan kelas & mapel
                $matchClass = ((int)$modul['class_id'] === (int)$data['class_id']);
                $matchSubject = ((int)$modul['subject_id'] === (int)$data['subject_id']);

                // Otorisasi guru: jika guru (role 3), modul harus miliknya sendiri
                $matchTeacher = true;
                if ((int)($user['role_id'] ?? 0) === 3) {
                    $matchTeacher = ((int)$modul['teacher_id'] === (int)($user['related_id'] ?? 0));
                }

                if ($matchClass && $matchSubject && $matchTeacher) {
                    $isValidModul = true;
                }
            }

            if ($isValidModul) {
                if ($hasModulCol) {
                    $data['modul_id'] = $modulId;
                }

                // Ekstrak pertemuan dari Modul Ajar
                $meetings = $this->parseMeetingsFromHtml($modul['content'] ?? '');
                $matchedMeeting = null;

                if ($pertemuanKe) {
                    foreach ($meetings as $m) {
                        if ((int)$m['pertemuan_ke'] === $pertemuanKe) {
                            $matchedMeeting = $m;
                            break;
                        }
                    }
                }

                if ($matchedMeeting) {
                    if ($hasPertemuanCol) {
                        $data['pertemuan_ke'] = $pertemuanKe;
                    }

                    // Jika jurnal lama sudah memiliki snapshot untuk modul dan pertemuan yang sama persis,
                    // pertahankan snapshot historis lama agar perubahan modul di kemudian hari tidak mengubah arsip
                    if ($existingJournal &&
                        (int)($existingJournal['modul_id'] ?? 0) === $modulId &&
                        (int)($existingJournal['pertemuan_ke'] ?? 0) === $pertemuanKe &&
                        !empty($existingJournal['rencana_snapshot'])) {
                        if ($hasSnapshotCol) {
                            $data['rencana_snapshot'] = $existingJournal['rencana_snapshot'];
                        }
                    } else {
                        // Buat snapshot baru di server dari modul yang tervalidasi
                        $snapshot = [
                            'modul_id'     => $modulId,
                            'pertemuan_ke' => $pertemuanKe,
                            'judul'        => $matchedMeeting['judul'],
                            'model'        => $matchedMeeting['model'],
                            'durasi'       => $matchedMeeting['durasi'],
                            'tahap_list'   => $matchedMeeting['tahap_list'],
                            'rencana_teks' => $matchedMeeting['rencana_teks'],
                            'captured_at'  => date('Y-m-d H:i:s'),
                        ];
                        if ($hasSnapshotCol) {
                            $data['rencana_snapshot'] = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
                        }
                    }
                } else {
                    if ($hasPertemuanCol) $data['pertemuan_ke'] = null;
                    if ($hasSnapshotCol) $data['rencana_snapshot'] = null;
                }
            } else {
                if ($hasModulCol) $data['modul_id'] = null;
                if ($hasPertemuanCol) $data['pertemuan_ke'] = null;
                if ($hasSnapshotCol) $data['rencana_snapshot'] = null;
            }
        } else {
            // Mode manual tanpa Modul Ajar
            if ($hasModulCol) $data['modul_id'] = null;
            if ($hasPertemuanCol) $data['pertemuan_ke'] = null;
            if ($hasSnapshotCol) $data['rencana_snapshot'] = null;
        }

        // Jika mode multi-subject, gunakan subject pertama sebagai subject_id utama
        if (!empty($multiSubjects) && is_array($multiSubjects)) {
            $firstSubject = $multiSubjects[0] ?? null;
            if ($firstSubject) {
                $data['subject_id'] = $firstSubject['subject_id'] ?? $data['subject_id'];
                $data['atp_id'] = $firstSubject['atp_id'] ?? $data['atp_id'];
            }
        }

        if (!$id) {
            $data['teacher_id'] = $roleId === 3 ? $currentTeacherId : $this->request->getPost('teacher_id');
            if (empty($data['teacher_id'])) {
                $assignment = $this->teachingAssignmentModel
                    ->where('class_id', $data['class_id'])
                    ->where('subject_id', $data['subject_id'])
                    ->first();
                $data['teacher_id'] = $assignment ? $assignment['teacher_id'] : null;
            }
            
            if (empty($data['teacher_id'])) {
                return redirect()->back()->withInput()->with('error', 'ID Guru tidak dapat ditentukan.');
            }

            $this->journalModel->insert($data);
            $journalId = $this->journalModel->getInsertID();

            // Simpan multi-subject ke tabel pivot
            if (!empty($multiSubjects) && is_array($multiSubjects)) {
                $this->saveJournalSubjects($journalId, $multiSubjects);
            }

            $msg = 'Jurnal berhasil disimpan.';
        } else {
            // SEC-04: teacher_id tidak boleh diubah saat update
            unset($data['teacher_id']);

            $this->journalModel->update($id, $data);

            // Update multi-subject jika ada
            if (!empty($multiSubjects) && is_array($multiSubjects)) {
                // Hapus yang lama, insert yang baru
                $this->db->table('teaching_journal_subjects')->where('journal_id', $id)->delete();
                $this->saveJournalSubjects((int)$id, $multiSubjects);
            }

            $msg = 'Jurnal berhasil diperbarui.';
        }

        return redirect()->to(base_url('admin/teaching-journal?class_id=' . $data['class_id']))->with('success', $msg);
    }

    /**
     * Simpan multiple subjects untuk satu jurnal (guru kelas)
     */
    private function saveJournalSubjects(int $journalId, array $subjects): void
    {
        $batch = [];
        foreach ($subjects as $item) {
            if (empty($item['subject_id'])) continue;
            $batch[] = [
                'journal_id' => $journalId,
                'subject_id' => (int) $item['subject_id'],
                'atp_id'     => !empty($item['atp_id']) ? (int) $item['atp_id'] : null,
            ];
        }
        if (!empty($batch)) {
            $this->db->table('teaching_journal_subjects')->insertBatch($batch);
        }
    }

    public function delete($id)
    {
        $user = session()->get('user');
        if ($user['role_id'] == 2) {
            return redirect()->back()->with('error', 'Kepala Sekolah tidak memiliki akses untuk menghapus data.');
        }

        $journal = $this->journalModel->find($id);
        if ($journal) {
            if ($user['role_id'] == 3 && $journal['teacher_id'] != $user['related_id']) {
                return redirect()->to(base_url('admin/teaching-journal'))->with('error', 'Anda tidak memiliki akses ke data ini.');
            }
            $this->journalModel->delete($id);
            return redirect()->back()->with('success', 'Jurnal berhasil dihapus.');
        }
        return redirect()->back();
    }

    public function getAtps()
    {
        $classId   = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');

        if (!$classId || !$subjectId) {
            return $this->response->setJSON([]);
        }

        $classInfo = $this->classModel->find($classId);
        if (!$classInfo) return $this->response->setJSON([]);

        // Query ATP berdasarkan level kelas
        // Pakai subquery WHERE IN untuk filter level agar tidak ada baris yang hilang
        $classIdsAtLevel = $this->db->table('classes')
            ->select('id')
            ->where('level', $classInfo['level'])
            ->get()->getResultArray();
        $classIdsAtLevel = array_column($classIdsAtLevel, 'id');

        if (empty($classIdsAtLevel)) {
            return $this->response->setJSON([]);
        }

        $atps = $this->db->table('alur_tujuan_pembelajaran atp')
            ->select('atp.id, atp.lingkup_materi, atp.semester, atp.urutan')
            ->whereIn('atp.class_id', $classIdsAtLevel)
            ->where('atp.subject_id', $subjectId)
            ->groupBy('atp.id')
            ->orderBy('atp.semester', 'ASC')
            ->orderBy('atp.urutan', 'ASC')
            ->get()->getResultArray();

        // Untuk setiap ATP, ambil daftar elemen CP dari atp_elemen
        foreach ($atps as &$atp) {
            $elemens = $this->db->table('atp_elemen ae')
                ->select('cp.elemen')
                ->join('cp_master cp', 'cp.id = ae.cp_master_id')
                ->where('ae.atp_id', $atp['id'])
                ->orderBy('ae.urutan', 'ASC')
                ->get()->getResultArray();

            // Gabungkan nama elemen jadi satu string
            $elemenNames = array_column($elemens, 'elemen');
            $atp['elemen'] = !empty($elemenNames)
                ? implode(', ', $elemenNames)
                : '-';
        }
        unset($atp);

        return $this->response->setJSON($atps);
    }

    /**
     * Mengambil daftar Modul Ajar yang relevan berdasarkan kelas, mapel, dan opsional atp.
     * Menerapkan otorisasi ketat kepemilikan guru untuk mencegah IDOR / kebocoran lintas guru.
     */
    public function getModuls()
    {
        $user = session()->get('user');
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'moduls'  => [],
                'message' => 'Sesi telah berakhir, silakan login kembali.',
            ]);
        }

        $classId   = $this->request->getGet('class_id');
        $subjectId = $this->request->getGet('subject_id');
        $atpId     = $this->request->getGet('atp_id');

        if (!$classId || !$subjectId || !is_numeric($classId) || !is_numeric($subjectId)) {
            return $this->response->setJSON([
                'success' => false,
                'moduls'  => [],
                'message' => 'Parameter kelas dan mata pelajaran diperlukan.',
            ]);
        }

        $classId = (int)$classId;
        $subjectId = (int)$subjectId;
        $atpId = (!empty($atpId) && is_numeric($atpId)) ? (int)$atpId : null;

        $classInfo = $this->classModel->find($classId);
        if (!$classInfo) {
            return $this->response->setJSON([
                'success' => false,
                'moduls'  => [],
                'message' => 'Kelas tidak ditemukan.',
            ]);
        }

        $builder = $this->db->table('modul_ajar ma')
            ->select('ma.id, ma.atp_id, ma.subject_id, ma.class_id, ma.teacher_id, ma.tipe, ma.updated_at, atp.lingkup_materi, atp.semester, atp.urutan')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = ma.atp_id', 'left')
            ->where('ma.class_id', $classId)
            ->where('ma.subject_id', $subjectId);

        // Otorisasi kepemilikan guru: guru hanya dapat melihat Modul Ajar miliknya sendiri
        if ((int)($user['role_id'] ?? 0) === 3) {
            $teacherId = (int)($user['related_id'] ?? 0);
            $builder->where('ma.teacher_id', $teacherId);
        }

        // Filter opsional berdasarkan ATP jika dipilih
        if ($atpId) {
            $builder->where('ma.atp_id', $atpId);
        }

        $builder->orderBy('ma.updated_at', 'DESC');
        $moduls = $builder->get()->getResultArray();

        $result = [];
        foreach ($moduls as $m) {
            $materi = !empty($m['lingkup_materi']) ? $m['lingkup_materi'] : 'Materi Umum';
            $sem = !empty($m['semester']) ? "Sem {$m['semester']}" : "";
            $tipe = strtoupper($m['tipe'] ?? 'MANUAL');
            $date = !empty($m['updated_at']) ? date('d/m/Y', strtotime($m['updated_at'])) : '';

            $labelParts = array_filter([$materi, $sem, $tipe, $date]);
            $label = implode(' | ', $labelParts);

            $result[] = [
                'id'         => (int)$m['id'],
                'atp_id'     => $m['atp_id'] ? (int)$m['atp_id'] : null,
                'materi'     => $materi,
                'semester'   => $m['semester'] ? (int)$m['semester'] : null,
                'tipe'       => $m['tipe'] ?? 'manual',
                'updated_at' => $m['updated_at'] ?? null,
                'label'      => $label,
            ];
        }

        return $this->response->setJSON([
            'success' => true,
            'moduls'  => $result,
            'message' => empty($result) ? 'Tidak ada Modul Ajar yang sesuai untuk kelas dan mata pelajaran ini.' : 'Daftar modul berhasil dimuat.',
        ]);
    }

    /**
     * Mengambil rincian pertemuan terstruktur dari suatu Modul Ajar.
     * Mencegah IDOR dengan memvalidasi kepemilikan dokumen jika role adalah guru.
     */
    public function getModulMeetings()
    {
        $user = session()->get('user');
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Sesi telah berakhir, silakan login kembali.',
            ]);
        }

        $modulId = $this->request->getGet('modul_id');
        if (!$modulId || !is_numeric($modulId) || (int)$modulId <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Parameter modul_id tidak valid.',
            ]);
        }

        $modulId = (int)$modulId;
        $modul = $this->modulModel->find($modulId);

        if (!$modul) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Modul Ajar tidak ditemukan.',
            ]);
        }

        // Proteksi IDOR ketat: Guru hanya boleh membaca Modul Ajar milik dirinya sendiri
        if ((int)($user['role_id'] ?? 0) === 3) {
            $userTeacherId = (int)($user['related_id'] ?? 0);
            if ((int)$modul['teacher_id'] !== $userTeacherId) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses ke Modul Ajar ini.',
                ]);
            }
        }

        // Parsing konten HTML dokumen secara defensif
        $meetings = $this->parseMeetingsFromHtml($modul['content'] ?? '');

        // Ambil info materi dari ATP jika ada
        $materiInfo = '';
        if (!empty($modul['atp_id'])) {
            $atp = $this->atpModel->find($modul['atp_id']);
            if ($atp) {
                $materiInfo = $atp['lingkup_materi'] ?? '';
            }
        }

        return $this->response->setJSON([
            'success'     => true,
            'modul_id'    => $modulId,
            'materi'      => $materiInfo,
            'total'       => count($meetings),
            'pertemuan'   => $meetings,
            'message'     => empty($meetings) ? 'Tidak ada rincian pertemuan terdeteksi dalam Modul Ajar ini.' : 'Rincian pertemuan berhasil dimuat.',
        ]);
    }

    /**
     * Parser defensif untuk mengekstrak rincian pertemuan (nomor, alokasi waktu, model, tahapan kegiatan)
     * dari HTML Bagian F Modul Ajar tanpa mengekspos HTML mentah ke response.
     */
    private function parseMeetingsFromHtml(?string $html): array
    {
        if (empty($html) || !is_string($html) || strlen(trim($html)) < 20) {
            return [];
        }

        libxml_use_internal_errors(true);
        $dom = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $dom->loadHTML('<?xml encoding="UTF-8"><div id="__parser_root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        if (!$loaded || !($root = $dom->getElementById('__parser_root'))) {
            return [];
        }

        $meetings = [];
        $tables = $root->getElementsByTagName('table');
        $meetingIndex = 0;

        foreach ($tables as $table) {
            $tableHtml = $dom->saveHTML($table);

            // Filter tabel langkah pembelajaran
            $isStepTable = (
                stripos($tableHtml, 'Tahap') !== false ||
                stripos($tableHtml, 'Sintaks') !== false ||
                stripos($tableHtml, 'Aktivitas') !== false ||
                stripos($tableHtml, 'Deskripsi') !== false ||
                stripos($tableHtml, 'Mindful') !== false ||
                stripos($tableHtml, 'Meaningful') !== false ||
                stripos($tableHtml, 'Pendahuluan') !== false ||
                stripos($tableHtml, 'Kegiatan Inti') !== false
            );

            // Kecualikan tabel identitas atau tabel kisi-kisi asesmen
            if (stripos($tableHtml, 'Satuan Pendidikan') !== false || stripos($tableHtml, 'Nama Penyusun') !== false) {
                $isStepTable = false;
            }

            if (!$isStepTable) {
                continue;
            }

            $meetingIndex++;

            // Cari heading / informasi sebelum tabel
            $headingText = '';
            $prevNode = $table->previousSibling;
            while ($prevNode) {
                if ($prevNode->nodeType === XML_ELEMENT_NODE) {
                    $txt = trim(preg_replace('/\s+/', ' ', $prevNode->textContent));
                    if (preg_match('/pertemuan/i', $txt)) {
                        $headingText = $txt;
                        break;
                    }
                    if (in_array(strtolower($prevNode->nodeName), ['h1', 'h2', 'h3'])) {
                        if (preg_match('/pertemuan/i', $txt)) {
                            $headingText = $txt;
                        }
                        break;
                    }
                }
                $prevNode = $prevNode->previousSibling;
            }

            // Ekstrak nomor pertemuan
            $pertemuanKe = $meetingIndex;
            if (!empty($headingText) && preg_match('/(?:Pertemuan\s*(?:ke-?)?\s*(\d+)|\bKe-\s*(\d+))/i', $headingText, $pm)) {
                $pertemuanKe = (int)($pm[1] ?: $pm[2]);
            }

            // Ekstrak durasi / alokasi waktu
            $durasiText = '';
            if (!empty($headingText) && preg_match('/\(?\s*(\d+)\s*(?:x\s*(\d+)\s*)?menit\s*\)?/i', $headingText, $dm)) {
                $menit = !empty($dm[2]) ? ((int)$dm[1] * (int)$dm[2]) : (int)$dm[1];
                $durasiText = "{$menit} menit";
            }

            // Ekstrak model pembelajaran dari judul (dengan flag u untuk unicode dash)
            $modelPembelajaran = '';
            if (!empty($headingText)) {
                if (preg_match('/pertemuan\s*(?:ke-?)?\s*\d+\s*(?:[-—:–]|&mdash;|&ndash;)\s*([^(]+)/iu', $headingText, $mm)) {
                    $modelPembelajaran = trim($mm[1]);
                }
            }

            // Parse baris-baris tabel
            $rows = $table->getElementsByTagName('tr');
            $steps = [];
            $calcMinutes = 0;

            foreach ($rows as $row) {
                $cols = [];
                foreach ($row->childNodes as $child) {
                    if ($child->nodeType === XML_ELEMENT_NODE && in_array(strtolower($child->nodeName), ['td', 'th'])) {
                        $cols[] = trim(preg_replace('/\s+/', ' ', $child->textContent));
                    }
                }

                if (empty($cols)) continue;

                $firstCol = $cols[0] ?? '';
                $allText = implode(' ', $cols);

                // Lewati baris header tabel
                if (preg_match('/^(Tahap|No|Sintaks|Langkah)\b/i', $firstCol) && preg_match('/(Sintaks|Deskripsi|Aktivitas|Waktu)/i', $allText)) {
                    continue;
                }

                $tahap = '';
                $sintaks = '';
                $deskripsi = '';
                $waktu = '';

                if (count($cols) >= 4) {
                    $tahap = $cols[0];
                    $sintaks = $cols[1];
                    $deskripsi = $cols[2];
                    $waktu = $cols[3];
                } elseif (count($cols) === 3) {
                    $tahap = $cols[0];
                    $deskripsi = $cols[1];
                    $waktu = $cols[2];
                } elseif (count($cols) === 2) {
                    $tahap = $cols[0];
                    $deskripsi = $cols[1];
                } else {
                    $deskripsi = $cols[0];
                }

                if (!empty($waktu) && preg_match('/(\d+)\s*menit/i', $waktu, $wm)) {
                    $calcMinutes += (int)$wm[1];
                }

                $steps[] = [
                    'tahap'     => $tahap,
                    'sintaks'   => $sintaks,
                    'deskripsi' => $deskripsi,
                    'waktu'     => $waktu,
                ];
            }

            if (empty($durasiText) && $calcMinutes > 0) {
                $durasiText = "{$calcMinutes} menit";
            }

            $judulDisplay = !empty($headingText) ? $headingText : "Pertemuan ke-{$pertemuanKe}";
            if (empty($modelPembelajaran) && !empty($durasiText)) {
                $labelRingkas = "Pertemuan {$pertemuanKe} ({$durasiText})";
            } elseif (!empty($modelPembelajaran) && !empty($durasiText)) {
                $labelRingkas = "Pertemuan {$pertemuanKe} — {$modelPembelajaran} ({$durasiText})";
            } else {
                $labelRingkas = "Pertemuan {$pertemuanKe}";
            }

            // Susun teks rencana terformat untuk dimasukkan ke textarea notes jurnal
            $rencanaLines = [];
            $headerRencana = "[Rencana Modul Ajar — " . $labelRingkas . "]";
            $rencanaLines[] = $headerRencana;

            foreach ($steps as $st) {
                $lineParts = [];
                if (!empty($st['tahap'])) {
                    $lineParts[] = $st['tahap'];
                }
                if (!empty($st['sintaks'])) {
                    $lineParts[] = "(" . $st['sintaks'] . ")";
                }
                if (!empty($st['waktu'])) {
                    $lineParts[] = "[" . $st['waktu'] . "]";
                }
                $prefix = !empty($lineParts) ? implode(' ', $lineParts) . ": " : "• ";
                $rencanaLines[] = "• " . $prefix . $st['deskripsi'];
            }

            $rencanaTeks = implode("\n", $rencanaLines);

            $meetings[] = [
                'pertemuan_ke' => $pertemuanKe,
                'judul'        => $judulDisplay,
                'label'        => $labelRingkas,
                'model'        => $modelPembelajaran,
                'durasi'       => $durasiText,
                'tahap_list'   => $steps,
                'rencana_teks' => $rencanaTeks,
            ];
        }

        // Fallback jika tidak ada tabel (misal berbasis heading & list/paragraf)
        if (empty($meetings)) {
            $h4Nodes = $root->getElementsByTagName('h4');
            $hIndex = 0;
            foreach ($h4Nodes as $h4) {
                $hText = trim(preg_replace('/\s+/', ' ', $h4->textContent));
                if (preg_match('/pertemuan/i', $hText)) {
                    $hIndex++;
                    $pKe = $hIndex;
                    if (preg_match('/(?:Pertemuan\s*(?:ke-?)?\s*(\d+)|\bKe-\s*(\d+))/i', $hText, $pm)) {
                        $pKe = (int)($pm[1] ?: $pm[2]);
                    }

                    $steps = [];
                    $curr = $h4->nextSibling;
                    while ($curr) {
                        if ($curr->nodeType === XML_ELEMENT_NODE) {
                            if (in_array(strtolower($curr->nodeName), ['h1', 'h2', 'h3', 'h4'])) {
                                break;
                            }
                            $cText = trim(preg_replace('/\s+/', ' ', $curr->textContent));
                            if (!empty($cText)) {
                                $steps[] = [
                                    'tahap'     => '',
                                    'sintaks'   => '',
                                    'deskripsi' => $cText,
                                    'waktu'     => '',
                                ];
                            }
                        }
                        $curr = $curr->nextSibling;
                    }

                    if (!empty($steps)) {
                        $rencanaLines = ["[Rencana Modul Ajar — {$hText}]"];
                        foreach ($steps as $st) {
                            $rencanaLines[] = "• " . $st['deskripsi'];
                        }

                        $meetings[] = [
                            'pertemuan_ke' => $pKe,
                            'judul'        => $hText,
                            'label'        => $hText,
                            'model'        => '',
                            'durasi'       => '',
                            'tahap_list'   => $steps,
                            'rencana_teks' => implode("\n", $rencanaLines),
                        ];
                    }
                }
            }
        }

        return $meetings;
    }

    /**
     * Menyiapkan seluruh data monitoring keterlaksanaan pembelajaran
     * Digunakan bersama secara konsisten oleh Dashboard, Cetak Laporan (Print), dan Ekspor PDF.
     */
    public function prepareMonitoringData(): array
    {
        $user = session()->get('user');
        $roleId = (int)($user['role_id'] ?? 0);
        $currentTeacherId = (int)($user['related_id'] ?? 0);

        $data['title'] = 'Monitoring Keterlaksanaan Pembelajaran';
        $data['role_id'] = $roleId;
        $data['current_teacher_id'] = $currentTeacherId;

        // 1. Tahun Ajaran
        $academicYearModel = new \App\Models\AcademicYearModel();
        $data['academic_years'] = $academicYearModel->orderBy('start_date', 'DESC')->findAll();
        $activeYear = $academicYearModel->getActiveYear();

        $selectedYearId = (int)($this->request->getGet('academic_year_id') ?: ($activeYear['id'] ?? 0));
        $data['selected_academic_year_id'] = $selectedYearId;

        // 2. Semester Filter (null/semua, 1, 2)
        $selectedSemester = $this->request->getGet('semester');
        $selectedSemester = ($selectedSemester === '1' || $selectedSemester === '2') ? (int)$selectedSemester : null;
        $data['selected_semester'] = $selectedSemester;

        // 3. Guru Filter & Otorisasi Ketat
        if (in_array($roleId, [1, 2])) {
            $data['teachers'] = $this->teacherModel->orderBy('name', 'ASC')->findAll();
            $selectedTeacherId = (int)$this->request->getGet('teacher_id') ?: null;
        } else {
            // Guru: hard-locked to own teacher_id
            $data['teachers'] = [];
            $selectedTeacherId = $currentTeacherId;
        }
        $data['selected_teacher_id'] = $selectedTeacherId;

        // 4. Kelas Filter
        if (in_array($roleId, [1, 2])) {
            $data['classes'] = $this->classModel->getClassesByAcademicYear($selectedYearId);
        } else {
            $homeroomClass = $this->classModel->where('teacher_id', $currentTeacherId)->first();
            $assignedClasses = $this->db->table('teaching_assignments ta')
                ->select('c.id, c.name, c.level')
                ->join('classes c', 'c.id = ta.class_id')
                ->where('ta.teacher_id', $currentTeacherId)
                ->where('ta.academic_year_id', $selectedYearId)
                ->groupBy('c.id')
                ->orderBy('c.level', 'ASC')
                ->orderBy('c.name', 'ASC')
                ->get()->getResultArray();

            $mergedClasses = [];
            foreach ($assignedClasses as $ac) {
                $mergedClasses[$ac['id']] = $ac;
            }
            if ($homeroomClass && !isset($mergedClasses[$homeroomClass['id']])) {
                $mergedClasses[$homeroomClass['id']] = [
                    'id'    => $homeroomClass['id'],
                    'name'  => $homeroomClass['name'],
                    'level' => $homeroomClass['level']
                ];
            }
            $data['classes'] = array_values($mergedClasses);
        }

        $selectedClassId = (int)$this->request->getGet('class_id') ?: null;
        if ($roleId === 3 && $selectedClassId) {
            $allowedClassIds = array_column($data['classes'], 'id');
            if (!in_array($selectedClassId, $allowedClassIds)) {
                $selectedClassId = null;
            }
        }
        $data['selected_class_id'] = $selectedClassId;

        // 5. Mata Pelajaran Filter
        $data['subjects'] = [];
        if ($selectedClassId) {
            $subjBuilder = $this->db->table('teaching_assignments ta')
                ->select('s.id, s.name, s.code')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.class_id', $selectedClassId)
                ->where('ta.academic_year_id', $selectedYearId)
                ->where('s.is_bk', 0);

            if ($roleId === 3) {
                $subjBuilder->where('ta.teacher_id', $currentTeacherId);
            } elseif ($selectedTeacherId) {
                $subjBuilder->where('ta.teacher_id', $selectedTeacherId);
            }

            $subjBuilder->groupBy('s.id')->orderBy('s.name', 'ASC');
            $data['subjects'] = $subjBuilder->get()->getResultArray();

            if (empty($data['subjects']) && $roleId === 3) {
                $homeroomClass = $this->classModel->where('teacher_id', $currentTeacherId)->first();
                if ($homeroomClass && $homeroomClass['id'] == $selectedClassId) {
                    $data['subjects'] = $this->subjectModel->where('is_bk', 0)->orderBy('name', 'ASC')->findAll();
                }
            }
        }

        $selectedSubjectId = (int)$this->request->getGet('subject_id') ?: null;
        if ($selectedSubjectId && !empty($data['subjects'])) {
            $allowedSubjectIds = array_column($data['subjects'], 'id');
            if (!in_array($selectedSubjectId, $allowedSubjectIds)) {
                $selectedSubjectId = null;
            }
        }
        $data['selected_subject_id'] = $selectedSubjectId;

        // 6. Modul Ajar Options
        $data['moduls'] = [];
        if ($selectedClassId && $selectedSubjectId) {
            $modulBuilder = $this->db->table('modul_ajar ma')
                ->select('ma.id, ma.atp_id, ma.subject_id, ma.class_id, ma.teacher_id, ma.tipe, ma.updated_at, atp.lingkup_materi, atp.semester, atp.urutan')
                ->join('alur_tujuan_pembelajaran atp', 'atp.id = ma.atp_id', 'left')
                ->where('ma.class_id', $selectedClassId)
                ->where('ma.subject_id', $selectedSubjectId);

            if ($roleId === 3) {
                $modulBuilder->where('ma.teacher_id', $currentTeacherId);
            } elseif ($selectedTeacherId) {
                $modulBuilder->where('ma.teacher_id', $selectedTeacherId);
            }

            if ($selectedSemester) {
                $modulBuilder->where('atp.semester', $selectedSemester);
            }

            $modulBuilder->orderBy('ma.updated_at', 'DESC');
            $rawModuls = $modulBuilder->get()->getResultArray();

            foreach ($rawModuls as $rm) {
                $materi = !empty($rm['lingkup_materi']) ? $rm['lingkup_materi'] : 'Materi Umum';
                $sem = !empty($rm['semester']) ? "Sem {$rm['semester']}" : "";
                $tipe = strtoupper($rm['tipe'] ?? 'MANUAL');
                $date = !empty($rm['updated_at']) ? date('d/m/Y', strtotime($rm['updated_at'])) : '';
                $label = implode(' | ', array_filter([$materi, $sem, $tipe, $date]));

                $data['moduls'][] = [
                    'id'            => (int)$rm['id'],
                    'lingkup_materi'=> $materi,
                    'semester'      => $rm['semester'],
                    'tipe'          => $rm['tipe'],
                    'updated_at'    => $rm['updated_at'],
                    'label'         => $label
                ];
            }
        }

        $selectedModulId = (int)$this->request->getGet('modul_id') ?: null;
        if ($selectedModulId) {
            if (!empty($data['moduls'])) {
                $allowedModulIds = array_column($data['moduls'], 'id');
                if (!in_array($selectedModulId, $allowedModulIds)) {
                    $selectedModulId = null;
                }
            } else {
                // Parameter kelas/mapel belum dipilih tetapi modul_id dikirim via query string
                $candModul = $this->modulModel->find($selectedModulId);
                if (!$candModul) {
                    $selectedModulId = null;
                } elseif ($roleId === 3 && (int)$candModul['teacher_id'] !== $currentTeacherId) {
                    $selectedModulId = null;
                } elseif ($selectedTeacherId && (int)$candModul['teacher_id'] !== $selectedTeacherId) {
                    $selectedModulId = null;
                }
            }
        }
        if (!$selectedModulId && count($data['moduls']) === 1) {
            $selectedModulId = (int)$data['moduls'][0]['id'];
        }
        $data['selected_modul_id'] = $selectedModulId;

        // 7. Kalkulasi Matriks & Summary (Hanya jika modul dipilih)
        $data['modul_info'] = null;
        $data['summary'] = [
            'n_total'         => 0,
            'p_terlaksana'    => 0,
            'p_sebagian'      => 0,
            'p_tertunda'      => 0,
            'p_belum'         => 0,
            'p_verifikasi'    => 0,
            'progres_persen'  => 'N/A',
            'progres_raw'     => null,
            'is_parsable'     => true,
            'status_catatan'  => ''
        ];
        $data['meeting_matrix'] = [];
        $data['independent_journals'] = [];

        $linkedHistoricalIds = [];

        if ($selectedModulId) {
            $modul = $this->modulModel->find($selectedModulId);
            // SEC-02: Validasi kepemilikan defensif untuk Guru
            if ($modul && $roleId === 3 && (int)$modul['teacher_id'] !== $currentTeacherId) {
                $modul = null;
                $selectedModulId = null;
                $data['selected_modul_id'] = null;
            }

            if ($modul) {
                $atpInfo = null;
                if (!empty($modul['atp_id'])) {
                    $atpInfo = $this->atpModel->find($modul['atp_id']);
                }
                $data['modul_info'] = array_merge($modul, [
                    'materi'   => $atpInfo['lingkup_materi'] ?? 'Materi Umum',
                    'semester' => $atpInfo['semester'] ?? null
                ]);

                $meetings = $this->parseMeetingsFromHtml($modul['content'] ?? '');
                $nTotal = count($meetings);

                // SEC-02: Fetch ALL journals for this modul in ONE query, terisolasi ke guru bersangkutan
                $journalsQuery = $this->journalModel
                    ->where('modul_id', $selectedModulId);

                if ($roleId === 3) {
                    $journalsQuery->where('teacher_id', $currentTeacherId);
                } elseif ($selectedTeacherId) {
                    $journalsQuery->where('teacher_id', $selectedTeacherId);
                } else {
                    $journalsQuery->where('teacher_id', (int)$modul['teacher_id']);
                }

                $journals = $journalsQuery
                    ->orderBy('date', 'ASC')
                    ->orderBy('id', 'ASC')
                    ->findAll();

                // Cari juga jurnal historis pada kelas & mapel ini yang dibuat berdasarkan modul sebelum migrasi Tahap 13C
                // (modul_id IS NULL tetapi notes diawali dengan rencana modul), terisolasi ke guru bersangkutan
                $histQuery = $this->journalModel
                    ->where('class_id', $modul['class_id'])
                    ->where('subject_id', $modul['subject_id'])
                    ->where('modul_id IS NULL')
                    ->like('notes', '[Rencana Modul Ajar', 'after');

                if ($roleId === 3) {
                    $histQuery->where('teacher_id', $currentTeacherId);
                } elseif ($selectedTeacherId) {
                    $histQuery->where('teacher_id', $selectedTeacherId);
                } else {
                    $histQuery->where('teacher_id', (int)$modul['teacher_id']);
                }

                $matchingNotesJournals = $histQuery
                    ->orderBy('date', 'ASC')
                    ->findAll();

                foreach ($matchingNotesJournals as $mnj) {
                    if (preg_match('/\[Rencana Modul Ajar\s*[-—]\s*Pertemuan\s*(\d+)/iu', $mnj['notes'], $pm)) {
                        $pNum = (int)$pm[1];
                        $mnj['pertemuan_ke'] = $pNum;
                        $mnj['modul_id'] = $selectedModulId;
                        $journals[] = $mnj;
                        $linkedHistoricalIds[] = (int)$mnj['id'];
                    }
                }

                $journalsByMeeting = [];
                foreach ($journals as $j) {
                    $pKe = (int)($j['pertemuan_ke'] ?? 0);
                    if ($pKe > 0) {
                        $journalsByMeeting[$pKe][] = $j;
                    }
                }

                if ($nTotal === 0) {
                    $data['summary']['is_parsable'] = false;
                    $data['summary']['status_catatan'] = 'Rencana pertemuan dalam Modul Ajar ini tidak dapat diparsing (Bagian F tidak ditemukan atau format non-standar). Total pertemuan tidak dapat ditentukan sehingga progres ditampilkan N/A.';
                    $data['summary']['progres_persen'] = 'N/A';
                } else {
                    $pTerlaksana = 0;
                    $pSebagian = 0;
                    $pTertunda = 0;
                    $pBelum = 0;
                    $pVerifikasi = 0;

                    $matrix = [];
                    foreach ($meetings as $m) {
                        $pNum = (int)$m['pertemuan_ke'];
                        $jList = $journalsByMeeting[$pNum] ?? [];

                        $status = 'belum_tercatat';
                        $statusLabel = 'Belum Tercatat';
                        $badgeClass = 'bg-secondary';
                        $hasConflict = false;
                        $conflictNote = '';

                        if (empty($jList)) {
                            $pBelum++;
                        } else {
                            $statuses = array_map(function($j) {
                                $st = strtolower(trim($j['status_realisasi'] ?? ''));
                                return in_array($st, ['terlaksana', 'sebagian', 'tertunda']) ? $st : 'terlaksana';
                            }, $jList);

                            $uniqueStatuses = array_values(array_unique($statuses));

                            if (count($uniqueStatuses) > 1) {
                                $status = 'perlu_verifikasi';
                                $statusLabel = 'Perlu Verifikasi';
                                $badgeClass = 'bg-info text-dark';
                                $hasConflict = true;
                                $conflictNote = 'Terdapat ' . count($jList) . ' catatan jurnal dengan status bertentangan (' . implode(', ', $uniqueStatuses) . ').';
                                $pVerifikasi++;
                            } else {
                                $singleStatus = $uniqueStatuses[0];
                                if ($singleStatus === 'terlaksana') {
                                    $status = 'terlaksana';
                                    $statusLabel = count($jList) > 1 ? 'Terlaksana (' . count($jList) . ' Jurnal)' : 'Terlaksana';
                                    $badgeClass = 'bg-success';
                                    $pTerlaksana++;
                                } elseif ($singleStatus === 'sebagian') {
                                    $status = 'sebagian';
                                    $statusLabel = count($jList) > 1 ? 'Terlaksana Sebagian (' . count($jList) . ' Jurnal)' : 'Terlaksana Sebagian';
                                    $badgeClass = 'bg-warning text-dark';
                                    $pSebagian++;
                                } elseif ($singleStatus === 'tertunda') {
                                    $status = 'tertunda';
                                    $statusLabel = count($jList) > 1 ? 'Tertunda (' . count($jList) . ' Jurnal)' : 'Tertunda';
                                    $badgeClass = 'bg-danger';
                                    $pTertunda++;
                                }
                            }
                        }

                        $dateStrings = [];
                        $notesPreviews = [];
                        foreach ($jList as $j) {
                            if (!empty($j['date'])) {
                                $dateStrings[] = date('d/m/Y', strtotime($j['date']));
                            }
                            if (!empty($j['notes'])) {
                                $cleanNote = trim(strip_tags($j['notes']));
                                $notesPreviews[] = mb_strlen($cleanNote) > 75 ? mb_substr($cleanNote, 0, 75) . '...' : $cleanNote;
                            }
                        }

                        $matrix[] = [
                            'pertemuan_ke'    => $pNum,
                            'judul'           => $m['judul'] ?? "Pertemuan {$pNum}",
                            'label'           => $m['label'] ?? "Pertemuan {$pNum}",
                            'model'           => $m['model'] ?? '-',
                            'durasi'          => $m['durasi'] ?? '-',
                            'tahap_list'      => $m['tahap_list'] ?? [],
                            'rencana_teks'    => $m['rencana_teks'] ?? '',
                            'status'          => $status,
                            'status_label'    => $statusLabel,
                            'badge_class'     => $badgeClass,
                            'has_conflict'    => $hasConflict,
                            'conflict_note'   => $conflictNote,
                            'journal_count'   => count($jList),
                            'dates_str'       => !empty($dateStrings) ? implode(', ', $dateStrings) : '-',
                            'notes_preview'   => !empty($notesPreviews) ? implode(' | ', $notesPreviews) : '-',
                            'journals'        => $jList
                        ];
                    }

                    $data['meeting_matrix'] = $matrix;

                    // Progress Formula: ((P_terlaksana + 0.5 * P_sebagian) / N_total) * 100
                    $effNumerator = $pTerlaksana + (0.5 * $pSebagian);
                    $effProgress = round(($effNumerator / $nTotal) * 100, 1);

                    $data['summary'] = [
                        'n_total'         => $nTotal,
                        'p_terlaksana'    => $pTerlaksana,
                        'p_sebagian'      => $pSebagian,
                        'p_tertunda'      => $pTertunda,
                        'p_belum'         => $pBelum,
                        'p_verifikasi'    => $pVerifikasi,
                        'progres_persen'  => $effProgress . '%',
                        'progres_raw'     => $effProgress,
                        'is_parsable'     => true,
                        'status_catatan'  => ''
                    ];
                }
            }
        }

        // 8. Jurnal Mandiri (Murni tanpa rujukan Modul Ajar apapun)
        if ($selectedClassId && $selectedSubjectId) {
            $indepBuilder = $this->journalModel
                ->select('teaching_journals.*')
                ->where('teaching_journals.class_id', $selectedClassId)
                ->where('teaching_journals.subject_id', $selectedSubjectId)
                ->where('teaching_journals.modul_id IS NULL');

            if ($roleId === 3) {
                $indepBuilder->where('teaching_journals.teacher_id', $currentTeacherId);
            } elseif ($selectedTeacherId) {
                $indepBuilder->where('teaching_journals.teacher_id', $selectedTeacherId);
            }

            // Keluarkan jurnal yang teridentifikasi merujuk rencana Modul Ajar
            if (!empty($linkedHistoricalIds)) {
                $indepBuilder->whereNotIn('teaching_journals.id', $linkedHistoricalIds);
            } else {
                $indepBuilder->notLike('teaching_journals.notes', '[Rencana Modul Ajar', 'after');
            }

            $data['independent_journals'] = $indepBuilder->orderBy('teaching_journals.date', 'DESC')->findAll();
        }

        // 9. Rekapitulasi Supervisi Multi-Modul (Mode B / Supervisi Agregat)
        $rekapBuilder = $this->db->table('modul_ajar ma')
            ->select('ma.id, ma.class_id, ma.subject_id, ma.teacher_id, ma.content, ma.tipe, ma.updated_at, atp.lingkup_materi, atp.semester, c.name as class_name, s.name as subject_name, t.name as teacher_name')
            ->join('alur_tujuan_pembelajaran atp', 'atp.id = ma.atp_id', 'left')
            ->join('classes c', 'c.id = ma.class_id', 'left')
            ->join('subjects s', 's.id = ma.subject_id', 'left')
            ->join('teachers t', 't.id = ma.teacher_id', 'left');

        if ($roleId === 3) {
            $rekapBuilder->where('ma.teacher_id', $currentTeacherId);
        } elseif ($selectedTeacherId) {
            $rekapBuilder->where('ma.teacher_id', $selectedTeacherId);
        }
        if ($selectedClassId) {
            $rekapBuilder->where('ma.class_id', $selectedClassId);
        }
        if ($selectedSubjectId) {
            $rekapBuilder->where('ma.subject_id', $selectedSubjectId);
        }
        if ($selectedSemester) {
            $rekapBuilder->where('atp.semester', $selectedSemester);
        }

        $rekapRaw = $rekapBuilder->orderBy('c.level', 'ASC')->orderBy('c.name', 'ASC')->orderBy('s.name', 'ASC')->get()->getResultArray();

        $rekapModulList = [];
        $totalKumulatif = [
            'n_total'         => 0,
            'p_terlaksana'    => 0,
            'p_sebagian'      => 0,
            'p_tertunda'      => 0,
            'p_belum'         => 0,
            'p_verifikasi'    => 0,
            'progres_persen'  => '0%',
            'progres_raw'     => 0,
        ];

        foreach ($rekapRaw as $rm) {
            $mList = $this->parseMeetingsFromHtml($rm['content'] ?? '');
            $nModul = count($mList);

            // SEC-02: Isolasi jurnal pada rekapitulasi multi-modul
            $jModulQuery = $this->journalModel
                ->where('modul_id', $rm['id']);

            if ($roleId === 3) {
                $jModulQuery->where('teacher_id', $currentTeacherId);
            } elseif ($selectedTeacherId) {
                $jModulQuery->where('teacher_id', $selectedTeacherId);
            } else {
                $jModulQuery->where('teacher_id', (int)$rm['teacher_id']);
            }

            $jModul = $jModulQuery->findAll();

            $matchingNotesQuery = $this->journalModel
                ->where('class_id', $rm['class_id'])
                ->where('subject_id', $rm['subject_id'])
                ->where('modul_id IS NULL')
                ->like('notes', '[Rencana Modul Ajar', 'after');

            if ($roleId === 3) {
                $matchingNotesQuery->where('teacher_id', $currentTeacherId);
            } elseif ($selectedTeacherId) {
                $matchingNotesQuery->where('teacher_id', $selectedTeacherId);
            } else {
                $matchingNotesQuery->where('teacher_id', (int)$rm['teacher_id']);
            }

            $matchingNotes = $matchingNotesQuery->findAll();

            foreach ($matchingNotes as $mnItem) {
                if (preg_match('/\[Rencana Modul Ajar\s*[-—]\s*Pertemuan\s*(\d+)/iu', $mnItem['notes'], $pm)) {
                    $mnItem['pertemuan_ke'] = (int)$pm[1];
                    $jModul[] = $mnItem;
                }
            }

            $byMeet = [];
            foreach ($jModul as $jItem) {
                $pNum = (int)($jItem['pertemuan_ke'] ?? 0);
                if ($pNum > 0) {
                    $byMeet[$pNum][] = $jItem;
                }
            }

            $pt = 0; $ps = 0; $pd = 0; $pb = 0; $pv = 0;
            foreach ($mList as $mItem) {
                $pNum = (int)$mItem['pertemuan_ke'];
                $jMeets = $byMeet[$pNum] ?? [];
                if (empty($jMeets)) {
                    $pb++;
                } else {
                    $stList = array_map(function($j) {
                        $st = strtolower(trim($j['status_realisasi'] ?? ''));
                        return in_array($st, ['terlaksana', 'sebagian', 'tertunda']) ? $st : 'terlaksana';
                    }, $jMeets);
                    $uniqSt = array_values(array_unique($stList));
                    if (count($uniqSt) > 1) {
                        $pv++;
                    } elseif ($uniqSt[0] === 'terlaksana') {
                        $pt++;
                    } elseif ($uniqSt[0] === 'sebagian') {
                        $ps++;
                    } elseif ($uniqSt[0] === 'tertunda') {
                        $pd++;
                    }
                }
            }

            $effModul = $nModul > 0 ? round((($pt + 0.5 * $ps) / $nModul) * 100, 1) : 0;

            $rekapModulList[] = [
                'id'              => (int)$rm['id'],
                'teacher_name'    => $rm['teacher_name'] ?? '-',
                'class_name'      => $rm['class_name'] ?? '-',
                'subject_name'    => $rm['subject_name'] ?? '-',
                'materi'          => !empty($rm['lingkup_materi']) ? $rm['lingkup_materi'] : 'Materi Umum',
                'semester'        => $rm['semester'] ?? '-',
                'tipe'            => strtoupper($rm['tipe'] ?? 'MANUAL'),
                'n_total'         => $nModul,
                'p_terlaksana'    => $pt,
                'p_sebagian'      => $ps,
                'p_tertunda'      => $pd,
                'p_belum'         => $pb,
                'p_verifikasi'    => $pv,
                'progres_persen'  => $nModul > 0 ? $effModul . '%' : 'N/A',
                'progres_raw'     => $nModul > 0 ? $effModul : null,
            ];

            $totalKumulatif['n_total'] += $nModul;
            $totalKumulatif['p_terlaksana'] += $pt;
            $totalKumulatif['p_sebagian'] += $ps;
            $totalKumulatif['p_tertunda'] += $pd;
            $totalKumulatif['p_belum'] += $pb;
            $totalKumulatif['p_verifikasi'] += $pv;
        }

        if ($totalKumulatif['n_total'] > 0) {
            $kumEff = round((($totalKumulatif['p_terlaksana'] + 0.5 * $totalKumulatif['p_sebagian']) / $totalKumulatif['n_total']) * 100, 1);
            $totalKumulatif['progres_persen'] = $kumEff . '%';
            $totalKumulatif['progres_raw'] = $kumEff;
        }

        $data['rekap_modul_list'] = $rekapModulList;
        $data['rekap_kumulatif'] = $totalKumulatif;

        // 10. Resolusi Nama & Detail Entitas Terpilih
        $data['selected_academic_year'] = null;
        if ($selectedYearId) {
            foreach ($data['academic_years'] as $ay) {
                if ($ay['id'] == $selectedYearId) {
                    $data['selected_academic_year'] = $ay;
                    break;
                }
            }
        }

        $data['selected_teacher'] = null;
        if ($selectedTeacherId) {
            $data['selected_teacher'] = $this->teacherModel->find($selectedTeacherId);
        }

        $data['selected_class'] = null;
        if ($selectedClassId) {
            $data['selected_class'] = $this->classModel->find($selectedClassId);
        }

        $data['selected_subject'] = null;
        if ($selectedSubjectId) {
            $data['selected_subject'] = $this->subjectModel->find($selectedSubjectId);
        }

        // 11. Profil Sekolah, Titimangsa & Aset Base64
        $schoolProfile = $this->schoolModel->getProfile() ?? [];
        $data['school'] = $schoolProfile;

        $kopSuratVal = null;
        try {
            $settingsModel = new \App\Models\SettingsModel();
            $kopSuratVal = $settingsModel->getValue('kop_surat');
        } catch (\Throwable $e) {
            $kopSuratVal = null;
        }
        $data['kop_base64'] = !empty($kopSuratVal) ? $this->imageToBase64($kopSuratVal) : '';
        $data['school_logo_base64'] = !empty($schoolProfile['logo']) ? $this->imageToBase64($schoolProfile['logo']) : '';
        $data['principal_sig_base64'] = !empty($schoolProfile['principal_signature']) ? $this->imageToBase64($schoolProfile['principal_signature']) : '';
        $data['teacher_sig_base64'] = !empty($data['selected_teacher']['signature']) ? $this->imageToBase64($data['selected_teacher']['signature']) : '';

        $bulanIndo = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $data['titimangsa_city'] = !empty($schoolProfile['city_regency']) ? $schoolProfile['city_regency'] : (!empty($schoolProfile['city']) ? $schoolProfile['city'] : 'Kota/Kab.');
        $data['titimangsa_date'] = date('j') . ' ' . ($bulanIndo[(int)date('n')] ?? date('F')) . ' ' . date('Y');

        return $data;
    }

    /**
     * Dashboard Monitoring Keterlaksanaan Pembelajaran (Tahap 14B)
     * Membandingkan rencana pertemuan Modul Ajar dengan bukti jurnal pembelajaran aktual.
     */
    public function monitoring()
    {
        $this->ensureSchemaUpToDate();

        $user = session()->get('user');
        if (!$user) {
            return redirect()->to(base_url('login'))->with('error', 'Sesi telah berakhir, silakan login kembali.');
        }
        $roleId = (int)($user['role_id'] ?? 0);

        // Otorisasi peran: Hanya Admin (1), Kepala Sekolah (2), dan Guru (3)
        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Anda tidak memiliki hak akses ke halaman monitoring.');
        }

        $data = $this->prepareMonitoringData();
        return view('admin/teaching_journal/monitoring', $data);
    }

    /**
     * Tampilan Cetak Laporan Supervisi Pembelajaran Formal (Tahap 14C)
     * View HTML ramah cetak browser (A4) lengkap dengan Kop Surat, KPI, Matriks/Rekapitulasi, dan Tanda Tangan Ganda.
     */
    public function monitoringPrint()
    {
        $this->ensureSchemaUpToDate();

        $user = session()->get('user');
        if (!$user) {
            return redirect()->to(base_url('login'))->with('error', 'Sesi telah berakhir, silakan login kembali.');
        }
        $roleId = (int)($user['role_id'] ?? 0);

        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Anda tidak memiliki hak akses ke halaman cetak laporan.');
        }

        $data = $this->prepareMonitoringData();
        $data['title'] = 'Cetak Laporan Supervisi Keterlaksanaan Pembelajaran';

        return view('admin/teaching_journal/monitoring_print', $data);
    }

    /**
     * Ekspor Dokumen Laporan Supervisi Pembelajaran ke PDF Resmi (Tahap 14C)
     * Menghasilkan dokumen PDF A4 dengan Dompdf, menggunakan data kalkulasi yang identik dengan Dashboard dan Print.
     */
    public function monitoringPdf()
    {
        $this->ensureSchemaUpToDate();

        $user = session()->get('user');
        if (!$user) {
            return redirect()->to(base_url('login'))->with('error', 'Sesi telah berakhir, silakan login kembali.');
        }
        $roleId = (int)($user['role_id'] ?? 0);

        if (!in_array($roleId, [1, 2, 3])) {
            return redirect()->to(base_url('dashboard'))->with('error', 'Anda tidak memiliki hak akses untuk mengunduh laporan PDF.');
        }

        $data = $this->prepareMonitoringData();
        $data['title'] = 'Laporan Supervisi Keterlaksanaan Pembelajaran';

        $html = view('admin/teaching_journal/monitoring_pdf', $data);

        if (ob_get_length()) {
            @ob_end_clean();
        }

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $subName = !empty($data['selected_subject']['name']) ? '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['selected_subject']['name']) : '';
        $clsName = !empty($data['selected_class']['name']) ? '_' . preg_replace('/[^A-Za-z0-9_\-]/', '_', $data['selected_class']['name']) : '';
        $filename = 'Laporan_Supervisi' . $clsName . $subName . '_' . date('Ymd_His') . '.pdf';

        $dompdf->stream($filename, ['Attachment' => false]);
        exit;
    }

    /**
     * Helper: Konversi file gambar lokal ke data URI Base64 untuk tampilan PDF / Print
     */
    protected function imageToBase64(?string $path): string
    {
        if (empty($path)) {
            return '';
        }
        $cleanPath = ltrim(str_replace('\\', '/', $path), '/');

        $candidatePaths = [
            FCPATH . $cleanPath,
            FCPATH . 'uploads/signatures/' . basename($cleanPath),
            FCPATH . 'uploads/logo/' . basename($cleanPath),
            FCPATH . 'uploads/settings/' . basename($cleanPath),
            FCPATH . 'uploads/' . $cleanPath,
            ROOTPATH . 'public/' . $cleanPath,
            ROOTPATH . 'public/uploads/signatures/' . basename($cleanPath),
            ROOTPATH . 'public/uploads/logo/' . basename($cleanPath),
            ROOTPATH . 'public/uploads/settings/' . basename($cleanPath),
            ROOTPATH . 'public/uploads/' . $cleanPath,
        ];

        $abs = null;
        foreach ($candidatePaths as $candidate) {
            if (file_exists($candidate) && !is_dir($candidate)) {
                $abs = $candidate;
                break;
            }
        }

        if (!$abs) {
            return '';
        }

        $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = in_array($ext, ['jpg', 'jpeg']) ? 'jpeg' : ($ext === 'png' ? 'png' : ($ext === 'webp' ? 'webp' : 'gif'));
        return 'data:image/' . $mime . ';base64,' . base64_encode(file_get_contents($abs));
    }

    /**
     * Endpoint API JSON untuk detail dan bukti jurnal per pertemuan
     */
    public function monitoringDetail()
    {
        $user = session()->get('user');
        if (!$user) {
            return $this->response->setStatusCode(401)->setJSON([
                'success' => false,
                'message' => 'Sesi telah berakhir, silakan login kembali.'
            ]);
        }
        $roleId = (int)($user['role_id'] ?? 0);
        $currentTeacherId = (int)($user['related_id'] ?? 0);

        if (!in_array($roleId, [1, 2, 3])) {
            return $this->response->setStatusCode(403)->setJSON([
                'success' => false,
                'message' => 'Anda tidak memiliki hak akses untuk data ini.'
            ]);
        }

        $modulId = (int)$this->request->getGet('modul_id');
        $pertemuanKe = (int)$this->request->getGet('pertemuan_ke');

        if (!$modulId || $pertemuanKe <= 0) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Parameter modul_id dan pertemuan_ke tidak valid.'
            ]);
        }

        $modul = $this->modulModel->find($modulId);
        if (!$modul) {
            return $this->response->setStatusCode(404)->setJSON([
                'success' => false,
                'message' => 'Modul Ajar tidak ditemukan.'
            ]);
        }

        // SEC-01: Otorisasi Guru ketat sisi server. Modul wajib milik guru yang sedang login.
        // Hapus fallback penugasan mengajar yang mengizinkan guru paralel melihat modul guru lain.
        if ($roleId === 3) {
            if (!$currentTeacherId || (int)$modul['teacher_id'] !== $currentTeacherId) {
                return $this->response->setStatusCode(403)->setJSON([
                    'success' => false,
                    'message' => 'Anda tidak memiliki hak akses ke detail pertemuan Modul Ajar ini.'
                ]);
            }
        }

        $meetings = $this->parseMeetingsFromHtml($modul['content'] ?? '');
        $selectedMeeting = null;
        foreach ($meetings as $m) {
            if ((int)$m['pertemuan_ke'] === $pertemuanKe) {
                $selectedMeeting = $m;
                break;
            }
        }

        // SEC-02: Query jurnal modul terisolasi ke guru bersangkutan
        $journalBuilder = $this->journalModel
            ->where('modul_id', $modulId)
            ->where('pertemuan_ke', $pertemuanKe);

        if ($roleId === 3) {
            $journalBuilder->where('teacher_id', $currentTeacherId);
        } else {
            // Untuk supervisi Admin/Kepsek, ambil jurnal milik guru pembuat modul
            $journalBuilder->where('teacher_id', (int)$modul['teacher_id']);
        }

        $journals = $journalBuilder
            ->orderBy('date', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        // Jika tidak ada jurnal dengan modul_id, periksa apakah ada jurnal historis dengan notes merujuk pertemuan ini
        if (empty($journals)) {
            $candHistBuilder = $this->journalModel
                ->where('class_id', $modul['class_id'])
                ->where('subject_id', $modul['subject_id'])
                ->where('modul_id IS NULL')
                ->like('notes', '[Rencana Modul Ajar', 'after');

            if ($roleId === 3) {
                $candHistBuilder->where('teacher_id', $currentTeacherId);
            } else {
                $candHistBuilder->where('teacher_id', (int)$modul['teacher_id']);
            }

            $candHist = $candHistBuilder
                ->orderBy('date', 'ASC')
                ->findAll();

            foreach ($candHist as $ch) {
                if (preg_match('/\[Rencana Modul Ajar\s*[-—]\s*Pertemuan\s*' . $pertemuanKe . '\b/iu', $ch['notes'])) {
                    $ch['pertemuan_ke'] = $pertemuanKe;
                    $ch['modul_id'] = $modulId;
                    $journals[] = $ch;
                }
            }
        }

        $formattedJournals = [];
        foreach ($journals as $j) {
            $formattedJournals[] = [
                'id'               => (int)$j['id'],
                'date'             => !empty($j['date']) ? date('d F Y', strtotime($j['date'])) : '-',
                'date_raw'         => $j['date'],
                'status_realisasi' => $j['status_realisasi'] ?? 'terlaksana',
                'rencana_snapshot' => $j['rencana_snapshot'] ?? '',
                'notes'            => $j['notes'] ?? '',
                'created_at'       => !empty($j['created_at']) ? date('d/m/Y H:i', strtotime($j['created_at'])) : '-'
            ];
        }

        $classInfo = $this->classModel->find($modul['class_id']);
        $subjectInfo = $this->subjectModel->find($modul['subject_id']);
        $teacherInfo = $this->teacherModel->find($modul['teacher_id']);

        return $this->response->setJSON([
            'success'        => true,
            'modul_id'       => $modulId,
            'pertemuan_ke'   => $pertemuanKe,
            'class_name'     => $classInfo['name'] ?? '-',
            'subject_name'   => $subjectInfo['name'] ?? '-',
            'teacher_name'   => $teacherInfo['name'] ?? '-',
            'meeting'        => $selectedMeeting ?: [
                'pertemuan_ke' => $pertemuanKe,
                'judul'        => "Pertemuan {$pertemuanKe}",
                'label'        => "Pertemuan {$pertemuanKe}",
                'model'        => '-',
                'durasi'       => '-',
                'tahap_list'   => [],
                'rencana_teks' => ''
            ],
            'total_journals' => count($formattedJournals),
            'journals'       => $formattedJournals,
            'disclaimer'     => 'Catatan Integritas: Snapshot Rencana merekam rencana saat jurnal disimpan. Catatan Aktual merekam realisasi riil pembelajaran di kelas.'
        ]);
    }

    /**
     * Memastikan skema tabel teaching_journals mutakhir (self-healing auto-migration)
     * dan menautkan jurnal historis yang dicatat berdasarkan modul sebelum migrasi Tahap 13C
     */
    private function ensureSchemaUpToDate(): void
    {
        try {
            if (!$this->db->fieldExists('status_realisasi', 'teaching_journals')) {
                $migrate = \Config\Services::migrations();
                $migrate->latest();
            }

            // Self-healing data: Tautkan jurnal historis yang dibuat dari modul sebelum migrasi Tahap 13C
            // di mana kolom modul_id masih bernilai NULL tetapi catatan refleksi diawali rujukan rencana modul
            if ($this->db->fieldExists('modul_id', 'teaching_journals')) {
                $orphans = $this->db->table('teaching_journals')
                    ->where('modul_id IS NULL')
                    ->like('notes', '[Rencana Modul Ajar', 'after')
                    ->get()->getResultArray();

                foreach ($orphans as $orphan) {
                    if (preg_match('/\[Rencana Modul Ajar\s*[-—]\s*Pertemuan\s*(\d+)/iu', $orphan['notes'], $pm)) {
                        $pNum = (int)$pm[1];
                        $candModul = $this->db->table('modul_ajar')
                            ->where('class_id', $orphan['class_id'])
                            ->where('subject_id', $orphan['subject_id'])
                            ->where('teacher_id', $orphan['teacher_id'])
                            ->orderBy('updated_at', 'DESC')
                            ->get()->getRowArray();

                        if ($candModul) {
                            $updateData = [
                                'modul_id'     => (int)$candModul['id'],
                                'pertemuan_ke' => $pNum
                            ];
                            if ($this->db->fieldExists('rencana_snapshot', 'teaching_journals') && empty($orphan['rencana_snapshot'])) {
                                $meetings = $this->parseMeetingsFromHtml($candModul['content'] ?? '');
                                foreach ($meetings as $m) {
                                    if ((int)$m['pertemuan_ke'] === $pNum) {
                                        $snapshot = [
                                            'modul_id'     => (int)$candModul['id'],
                                            'pertemuan_ke' => $pNum,
                                            'judul'        => $m['judul'],
                                            'model'        => $m['model'],
                                            'durasi'       => $m['durasi'],
                                            'tahap_list'   => $m['tahap_list'],
                                            'rencana_teks' => $m['rencana_teks'],
                                            'captured_at'  => date('Y-m-d H:i:s'),
                                        ];
                                        $updateData['rencana_snapshot'] = json_encode($snapshot, JSON_UNESCAPED_UNICODE);
                                        break;
                                    }
                                }
                            }
                            $this->db->table('teaching_journals')
                                ->where('id', $orphan['id'])
                                ->update($updateData);
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            log_message('warning', '[TeachingJournalController] Auto-migrate notice: ' . $e->getMessage());
        }
    }

    private function getAvailableFilters()
    {
        $user = session()->get('user');
        if (!$user) return ['classes' => [], 'subjects' => [], 'readonly' => true, 'role_id' => null, 'auto_class' => false];

        $roleId = $user['role_id'];
        $teacherId = $user['related_id'];
        $readonly = ($roleId == 2);

        $classes = [];
        $subjects = [];

        $classId = $this->request->getGet('class_id');

        if (in_array($roleId, [1, 2])) {
            // Admin/Kepsek: kelas aktif di tahun ajaran aktif
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $activeYearId = $activeYear['id'] ?? 0;
            $classes = $this->classModel->getClassesByAcademicYear($activeYearId);
            if ($classId) {
                $subjects = $this->db->table('teaching_assignments ta')
                    ->select('s.id, s.name')
                    ->join('subjects s', 's.id = ta.subject_id')
                    ->where('ta.class_id', $classId)
                    ->where('ta.academic_year_id', $activeYearId)
                    ->where('s.is_bk', 0)  // Kecualikan BP/BK dari jurnal mengajar
                    ->groupBy('s.id')
                    ->get()->getResultArray();
            } else {
                 $subjects = $this->subjectModel->where('is_bk', 0)->findAll(); // Kecualikan BP/BK
            }
        } elseif ($roleId == 3) {
            // Guru Kelas logic: find class where teacher_id = teacherId
            $homeroomClass = $this->classModel->where('teacher_id', $teacherId)->first();
            
            // Ambil tahun ajaran aktif
            $activeYear = (new \App\Models\AcademicYearModel())->getActiveYear();
            $activeYearId = $activeYear['id'] ?? 0;

            $assignmentQuery = $this->db->table('teaching_assignments ta')
                ->select('c.id as class_id, c.name as class_name, c.level, s.id as subject_id, s.name as subject_name')
                ->join('classes c', 'c.id = ta.class_id')
                ->join('subjects s', 's.id = ta.subject_id')
                ->where('ta.teacher_id', $teacherId)
                ->where('ta.academic_year_id', $activeYearId)
                ->where('s.is_bk', 0);  // Kecualikan BP/BK dari jurnal mengajar
            
            $assignments = $assignmentQuery->get()->getResultArray();

            $tempClasses = [];
            foreach ($assignments as $a) {
                $tempClasses[$a['class_id']] = ['id' => $a['class_id'], 'name' => $a['class_name']];
            }
            if ($homeroomClass) {
                $tempClasses[$homeroomClass['id']] = ['id' => $homeroomClass['id'], 'name' => $homeroomClass['name']];
            }
            $classes = array_values($tempClasses);
            
            if (!$classId && $homeroomClass) {
                $classId = $homeroomClass['id'];
                $_GET['class_id'] = $classId;
            }

            if ($classId) {
                // For a specific class, get subjects this teacher teaches or all subjects if they are homeroom teacher?
                // The user said: "Guru kelas otomatis mendeteksi kelas yang diampu, pilih mapel yang diampu"
                // Usually homeroom teachers also teach some subjects or all subjects (SD).
                $filteredSubjects = array_filter($assignments, fn($a) => $a['class_id'] == $classId);
                $subjects = array_map(fn($s) => ['id' => $s['subject_id'], 'name' => $s['subject_name']], $filteredSubjects);
            }
        }

        return [
            'classes' => $classes,
            'subjects' => $subjects,
            'readonly' => $readonly,
            'role_id' => $roleId,
        ];
    }
}
