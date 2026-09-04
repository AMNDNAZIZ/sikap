<?php

namespace App\Models;

use CodeIgniter\Model;

class SubjectMaterialModel extends Model
{
    protected $table         = 'subject_materials';
    protected $primaryKey    = 'id';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'parent_id', 'level', 'subject_id', 'year_id', 'semester',
        'title', 'description',
        'content_type', 'content', 'file_path', 'video_url', 'external_link',
        'estimated_minutes', 'sort_order',
        'is_published', 'published_by', 'published_at', 'created_by',
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // HIERARKI
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Semua Materi induk (parent_id IS NULL) per mapel + level + tahun ajaran.
     * Dipakai admin saat membuat sub materi.
     */
    public function getParentsBySubjectLevel(int $subjectId, int $level, int $yearId): array
    {
        return $this->where('subject_id', $subjectId)
            ->where('year_id',     $yearId)
            ->where('level',       $level)
            ->where('parent_id IS NULL')
            ->orderBy('semester',   'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id',         'ASC')
            ->findAll();
    }

    /**
     * Semua Materi induk per mapel + tahun (tanpa filter level).
     * Dipakai dropdown pada form sub materi untuk admin.
     */
    public function getParentsBySubject(int $subjectId, int $yearId): array
    {
        return $this->where('subject_id', $subjectId)
            ->where('year_id',     $yearId)
            ->where('parent_id IS NULL')
            ->orderBy('level',      'ASC')
            ->orderBy('semester',   'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id',         'ASC')
            ->findAll();
    }

    /**
     * Semua Sub Materi (children) dari satu Materi induk, termasuk draft.
     */
    public function getAllChildren(int $parentId): array
    {
        return $this->where('parent_id', $parentId)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id',         'ASC')
            ->findAll();
    }

    /**
     * Sub Materi published dari satu Materi induk.
     */
    public function getChildren(int $parentId): array
    {
        return $this->where('parent_id',   $parentId)
            ->where('is_published', 1)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id',         'ASC')
            ->findAll();
    }

    /**
     * Materi induk dari satu sub materi.
     */
    public function getParent(int $childId): ?array
    {
        $child = $this->find($childId);
        if (!$child || !$child['parent_id']) return null;
        return $this->find($child['parent_id']);
    }

    /**
     * Semua Materi (parent + children) per mapel untuk admin — hierarkis.
     */
    public function getAllWithHierarchy(int $subjectId): array
    {
        $all = $this->select('subject_materials.*, academic_years.year as year_name')
            ->join('academic_years', 'academic_years.id = subject_materials.year_id', 'left')
            ->where('subject_materials.subject_id', $subjectId)
            ->orderBy('subject_materials.level',      'ASC')
            ->orderBy('subject_materials.semester',   'ASC')
            ->orderBy('subject_materials.sort_order', 'ASC')
            ->orderBy('subject_materials.id',         'ASC')
            ->findAll();

        return self::buildHierarchy($all);
    }

    /**
     * Bangun hierarki dari daftar flat.
     * Return: [ [ ...materi, 'children' => [...sub] ], ... ]
     */
    public static function buildHierarchy(array $flat): array
    {
        $parents  = [];
        $children = [];

        foreach ($flat as $item) {
            if (empty($item['parent_id'])) {
                $parents[$item['id']]             = $item;
                $parents[$item['id']]['children'] = [];
            } else {
                $children[(int)$item['parent_id']][] = $item;
            }
        }

        foreach ($children as $pid => $kids) {
            if (isset($parents[$pid])) {
                $parents[$pid]['children'] = $kids;
            }
        }

        return array_values($parents);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PUBLIKASI PER KELAS  (subject_material_publishes)
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Ambil daftar kelas yang sudah/belum dipublish untuk satu sub materi.
     * Return: [ class_id => ['class_name'=>..., 'is_active'=>0/1, 'published_at'=>...], ... ]
     */
    public function getPublishStatusForClasses(int $subMatId, array $classes): array
    {
        $db = \Config\Database::connect();

        $published = $db->table('subject_material_publishes')
            ->where('material_id', $subMatId)
            ->get()->getResultArray();

        $map = [];
        foreach ($published as $p) {
            $map[(int)$p['class_id']] = $p;
        }

        $result = [];
        foreach ($classes as $c) {
            $cid          = (int) $c['id'];
            $result[$cid] = [
                'class_id'     => $cid,
                'class_name'   => $c['name'],
                'is_active'    => isset($map[$cid]) ? (int)$map[$cid]['is_active'] : 0,
                'published_at' => $map[$cid]['published_at'] ?? null,
                'published_by' => $map[$cid]['published_by'] ?? null,
            ];
        }

        return $result;
    }

    /**
     * Publish sub materi ke satu kelas.
     * Jika sudah ada record → update is_active=1.
     * Sekaligus auto-create forum thread sistem (shared, class_id=NULL).
     */
    public function publishToClass(
        int $subMatId,
        int $classId,
        int $userId,
        int $academicYearId
    ): void {
        $db  = \Config\Database::connect();
        $now = date('Y-m-d H:i:s');

        $existing = $db->table('subject_material_publishes')
            ->where('material_id', $subMatId)
            ->where('class_id',    $classId)
            ->get()->getRowArray();

        if ($existing) {
            $db->table('subject_material_publishes')
                ->where('material_id', $subMatId)
                ->where('class_id',    $classId)
                ->set(['is_active' => 1, 'published_at' => $now, 'published_by' => $userId])
                ->update();
        } else {
            $db->table('subject_material_publishes')->insert([
                'material_id'  => $subMatId,
                'class_id'     => $classId,
                'published_by' => $userId,
                'published_at' => $now,
                'is_active'    => 1,
            ]);
        }

        // Auto-create forum thread sistem (shared — class_id = NULL)
        $this->_ensureForumThread($subMatId, $userId, $academicYearId);
    }

    /**
     * Unpublish sub materi dari satu kelas (set is_active = 0).
     */
    public function unpublishFromClass(int $subMatId, int $classId): void
    {
        \Config\Database::connect()
            ->table('subject_material_publishes')
            ->where('material_id', $subMatId)
            ->where('class_id',    $classId)
            ->set(['is_active' => 0])
            ->update();
    }

    /**
     * Publish ke banyak kelas sekaligus.
     */
    public function publishToClasses(
        int   $subMatId,
        array $classIds,
        int   $userId,
        int   $academicYearId
    ): void {
        foreach ($classIds as $cid) {
            $this->publishToClass($subMatId, (int)$cid, $userId, $academicYearId);
        }
    }

    /**
     * Ambil class_id yang aktif untuk satu sub materi.
     */
    public function getPublishedClassIds(int $subMatId): array
    {
        return array_column(
            \Config\Database::connect()
                ->table('subject_material_publishes')
                ->select('class_id')
                ->where('material_id', $subMatId)
                ->where('is_active',   1)
                ->get()->getResultArray(),
            'class_id'
        );
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PORTAL SISWA — materi yang bisa diakses berdasarkan publish record
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Semua sub materi yang dipublish ke kelas siswa, dikelompokkan hierarkis per mapel.
     * Acuan: subject_material_publishes.class_id = class_id siswa, is_active = 1.
     *
     * Return: [
     *   subject_id => [
     *     'name'    => ...,
     *     'parents' => [ [...materi, 'children' => [...sub_materi] ], ... ]
     *   ]
     * ]
     */
    public function getPublishedForClass(int $classId, int $academicYearId): array
    {
        $db = \Config\Database::connect();

        // Ambil level kelas siswa
        $classInfo = $db->table('classes')->where('id', $classId)->get()->getRowArray();
        $classLevel = (int) ($classInfo['level'] ?? 0);

        // Ambil semua sub materi yang dipublish ke kelas ini
        $publishedIds = array_column(
            $db->table('subject_material_publishes')
                ->select('material_id')
                ->where('class_id', $classId)
                ->where('is_active', 1)
                ->get()->getResultArray(),
            'material_id'
        );

        if (empty($publishedIds)) return [];

        // Ambil data sub materi + materi induknya sekaligus
        $subMats = $db->table('subject_materials sm')
            ->select('sm.*, s.name as subject_name')
            ->join('subjects s', 's.id = sm.subject_id')
            ->whereIn('sm.id', $publishedIds)
            ->where('sm.is_published', 1)
            ->orderBy('s.name',          'ASC')
            ->orderBy('sm.sort_order',   'ASC')
            ->orderBy('sm.id',           'ASC')
            ->get()->getResultArray();

        if (empty($subMats)) return [];

        // Kumpulkan parent_id yang dibutuhkan
        $parentIds = array_unique(array_filter(array_column($subMats, 'parent_id')));

        $parents = [];
        if (!empty($parentIds)) {
            $parentRows = $db->table('subject_materials sm')
                ->select('sm.*, s.name as subject_name')
                ->join('subjects s', 's.id = sm.subject_id')
                ->whereIn('sm.id', $parentIds)
                ->get()->getResultArray();
            foreach ($parentRows as $p) {
                $parents[$p['id']] = $p;
            }
        }

        // Kelompokkan per mapel, bangun hierarki
        $bySubject = [];

        // Masukkan parent ke bySubject terlebih dahulu
        foreach ($parents as $p) {
            $sid = $p['subject_id'];
            if (!isset($bySubject[$sid])) {
                $bySubject[$sid] = ['name' => $p['subject_name'], 'items' => []];
            }
            if (!isset($bySubject[$sid]['items'][$p['id']])) {
                $bySubject[$sid]['items'][$p['id']] = array_merge($p, ['children' => []]);
            }
        }

        // Masukkan sub materi sebagai children
        foreach ($subMats as $sm) {
            $sid = $sm['subject_id'];
            if (!isset($bySubject[$sid])) {
                $bySubject[$sid] = ['name' => $sm['subject_name'], 'items' => []];
            }
            $pid = (int) ($sm['parent_id'] ?? 0);
            if ($pid && isset($bySubject[$sid]['items'][$pid])) {
                $bySubject[$sid]['items'][$pid]['children'][] = $sm;
            } elseif ($pid && !isset($bySubject[$sid]['items'][$pid])) {
                // Parent belum ada di list (mungkin level berbeda) — tambahkan dummy
                $parentData = $parents[$pid] ?? ['id' => $pid, 'title' => '—', 'subject_id' => $sid,
                    'subject_name' => $sm['subject_name'], 'semester' => $sm['semester'],
                    'level' => $sm['level'], 'sort_order' => 0];
                $bySubject[$sid]['items'][$pid] = array_merge($parentData, ['children' => [$sm]]);
            } else {
                // Tidak punya parent → tampilkan sebagai item tunggal
                $bySubject[$sid]['items'][$sm['id']] = array_merge($sm, ['children' => []]);
            }
        }

        // Format final
        $result = [];
        foreach ($bySubject as $sid => $group) {
            $sortedParents = array_values($group['items']);
            usort($sortedParents, fn($a, $b) =>
                ($a['semester'] <=> $b['semester']) ?: ($a['sort_order'] <=> $b['sort_order']) ?: ($a['id'] <=> $b['id'])
            );
            $result[$sid] = [
                'name'       => $group['name'],
                'subject_id' => $sid,
                'parents'    => $sortedParents,
            ];
        }

        return $result;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // FITUR "GUNAKAN MATERI" — guru lain menggunakan materi yang sudah ada
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Daftar sub materi yang bisa "digunakan" oleh seorang guru.
     * Kriteria:
     *   - Sama subject_id
     *   - Sama level dengan kelas yang diajar guru
     *   - is_published = 1
     *   - Bukan buatan guru sendiri (created_by != userId)
     *
     * Return: array sub materi dengan info pembuat
     */
    public function getAvailableForTeacher(
        int $subjectId,
        int $level,
        int $yearId,
        int $currentUserId
    ): array {
        $db = \Config\Database::connect();

        return $db->table('subject_materials sm')
            ->select('sm.*, u.fullname as creator_name, p.title as parent_title')
            ->join('users u',            'u.id = sm.created_by', 'left')
            ->join('subject_materials p','p.id = sm.parent_id',  'left')
            ->where('sm.subject_id',    $subjectId)
            ->where('sm.year_id',       $yearId)
            ->where('sm.level',         $level)
            ->where('sm.is_published',  1)
            ->where('sm.parent_id IS NOT NULL')
            ->where('sm.created_by !=', $currentUserId)
            ->orderBy('p.sort_order', 'ASC')
            ->orderBy('sm.sort_order', 'ASC')
            ->orderBy('sm.id',         'ASC')
            ->get()->getResultArray();
    }

    /**
     * "Gunakan" sub materi milik guru lain:
     * Simpan ke subject_material_publishes untuk kelas yang dipilih.
     * Tidak menyalin data — hanya membuat publish record.
     */
    public function useSubMaterial(
        int   $subMatId,
        array $classIds,
        int   $userId,
        int   $academicYearId
    ): bool {
        $sub = $this->find($subMatId);
        if (!$sub || !$sub['is_published'] || empty($sub['parent_id'])) {
            return false;
        }

        $this->publishToClasses($subMatId, $classIds, $userId, $academicYearId);
        return true;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // FORUM & KUIS PER SUB MATERI
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Ambil forum thread sistem untuk sub materi (class_id = NULL = shared).
     */
    public function getForumThread(int $subMatId): ?array
    {
        return \Config\Database::connect()
            ->table('forum_threads')
            ->where('related_type', 'material')
            ->where('related_id',   $subMatId)
            ->where('is_system',    1)
            ->where('class_id IS NULL')   // thread shared, bukan per kelas
            ->get()->getRowArray() ?: null;
    }

    /**
     * Ambil kuis yang di-pin ke sub materi ini, dengan filter kelas.
     */
    public function getQuizzesForSubMaterial(int $subMatId, int $classId): array
    {
        $db  = \Config\Database::connect();

        $all = $db->table('quiz_configs qc')
            ->select('qc.*, qb.code as bank_code, s.name as subject_name')
            ->join('cbt_question_banks qb', 'qb.id = qc.bank_id',     'left')
            ->join('subjects s',            's.id = qb.subject_id',   'left')
            ->where('qc.material_id', $subMatId)
            ->where('qc.is_published', 1)
            ->get()->getResultArray();

        return array_values(array_filter($all, function ($q) use ($classId) {
            if (empty($q['class_ids'])) return true;
            $ids = json_decode($q['class_ids'], true) ?? [];
            return in_array($classId, $ids);
        }));
    }

    // ═══════════════════════════════════════════════════════════════════════
    // ADMIN: daftar mapel+level yang bisa dikelola guru
    // ═══════════════════════════════════════════════════════════════════════

    /**
     * Daftar {subject_id, level} unik yang diajarkan guru ini.
     * Dipakai untuk menampilkan daftar materi yang relevan.
     */
    public function getSubjectLevelsForTeacher(int $teacherId, int $yearId): array
    {
        $db = \Config\Database::connect();

        return $db->table('teaching_assignments ta')
            ->select('ta.subject_id, s.name as subject_name, c.level,
                      COUNT(DISTINCT ta.class_id) as total_classes')
            ->join('subjects s', 's.id = ta.subject_id')
            ->join('classes c',  'c.id = ta.class_id')
            ->where('ta.teacher_id',       $teacherId)
            ->where('ta.academic_year_id', $yearId)
            ->groupBy(['ta.subject_id', 'c.level'])
            ->orderBy('s.name', 'ASC')
            ->orderBy('c.level','ASC')
            ->get()->getResultArray();
    }

    /**
     * Kelas-kelas yang level-nya sama dengan kelas yang diajar guru
     * untuk mapel tertentu. Dipakai di halaman publish.
     */
    public function getClassesForPublish(int $subjectId, int $level, int $yearId, ?int $teacherId = null): array
    {
        $db      = \Config\Database::connect();
        $builder = $db->table('teaching_assignments ta')
            ->select('c.id, c.name, c.level, t.name as teacher_name')
            ->join('classes c',  'c.id = ta.class_id')
            ->join('teachers t', 't.id = ta.teacher_id', 'left')
            ->where('ta.subject_id',       $subjectId)
            ->where('ta.academic_year_id', $yearId)
            ->groupBy('c.id')
            ->orderBy('c.level', 'ASC')
            ->orderBy('c.name',  'ASC');

        // level = 0 berarti "Semua Level" → tidak filter level
        if ($level > 0) {
            $builder->where('c.level', $level);
        }

        // Jika bukan admin, filter hanya kelas yang guru ini ampu
        if ($teacherId !== null) {
            $builder->where('ta.teacher_id', $teacherId);
        }

        return $builder->get()->getResultArray();
    }

    // ═══════════════════════════════════════════════════════════════════════
    // PRIVATE: auto-create forum thread sistem
    // ═══════════════════════════════════════════════════════════════════════

    private function _ensureForumThread(
        int $subMatId,
        int $userId,
        int $academicYearId
    ): void {
        $db  = \Config\Database::connect();
        $sub = $this->find($subMatId);
        if (!$sub) return;

        // Cek apakah thread shared sudah ada
        $existing = $db->table('forum_threads')
            ->where('related_type', 'material')
            ->where('related_id',   $subMatId)
            ->where('is_system',    1)
            ->where('class_id IS NULL')
            ->get()->getRowArray();

        if ($existing) return;

        $now = date('Y-m-d H:i:s');
        $db->table('forum_threads')->insert([
            'subject_id'       => $sub['subject_id'],
            'class_id'         => null,          // shared — tidak terikat kelas
            'academic_year_id' => $academicYearId,
            'related_type'     => 'material',
            'related_id'       => $subMatId,
            'title'            => $sub['title'],
            'body'             => 'Diskusi untuk sub materi: <strong>' . htmlspecialchars($sub['title']) . '</strong>. '
                                . 'Thread ini terbuka untuk semua kelas yang mendapat akses materi ini.',
            'user_id'          => $userId,
            'is_pinned'        => 0,
            'is_locked'        => 0,
            'is_answered'      => 0,
            'is_system'        => 1,
            'reply_count'      => 0,
            'view_count'       => 0,
            'created_at'       => $now,
            'updated_at'       => $now,
        ]);
    }

    // ═══════════════════════════════════════════════════════════════════════
    // HELPERS LAMA (backward compatible)
    // ═══════════════════════════════════════════════════════════════════════

    public function getDetail(int $id): ?array
    {
        return $this->select('subject_materials.*, subjects.name as subject_name, academic_years.year as year_name')
            ->join('subjects',       'subjects.id = subject_materials.subject_id')
            ->join('academic_years', 'academic_years.id = subject_materials.year_id')
            ->where('subject_materials.id', $id)
            ->first();
    }

    public function getAllBySubject(int $subjectId): array
    {
        return $this->select('subject_materials.*, academic_years.year as year_name')
            ->join('academic_years', 'academic_years.id = subject_materials.year_id', 'left')
            ->where('subject_materials.subject_id', $subjectId)
            ->orderBy('level',      'ASC')
            ->orderBy('semester',   'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('subject_materials.id', 'ASC')
            ->findAll();
    }

    public function getPublishedByClass(int $classId, int $academicYearId): array
    {
        $db       = \Config\Database::connect();
        $classInfo = $db->table('classes')->where('id', $classId)->get()->getRowArray();
        $level    = (int) ($classInfo['level'] ?? 0);

        // Sub materi yang dipublish ke kelas ini
        $publishedIds = array_column(
            $db->table('subject_material_publishes')
                ->select('material_id')
                ->where('class_id', $classId)
                ->where('is_active', 1)
                ->get()->getResultArray(),
            'material_id'
        );

        if (empty($publishedIds)) return [];

        return $db->table('subject_materials sm')
            ->select('sm.*, s.name as subject_name')
            ->join('subjects s', 's.id = sm.subject_id')
            ->whereIn('sm.id', $publishedIds)
            ->where('sm.is_published', 1)
            ->orderBy('s.name', 'ASC')
            ->orderBy('sm.sort_order', 'ASC')
            ->get()->getResultArray();
    }

    /** @deprecated */
    public function getForStudent(int $studentId, int $academicYearId): array
    {
        $db = \Config\Database::connect();
        $record = $db->table('student_records')
            ->where('student_id',       $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('status',           'aktif')
            ->get()->getRowArray();
        if (!$record) return [];
        $published = $this->getPublishedForClass((int)$record['class_id'], $academicYearId);
        // flatten untuk backward compat
        $flat = [];
        foreach ($published as $group) {
            foreach ($group['parents'] as $p) {
                foreach (($p['children'] ?? []) as $c) {
                    $flat[] = $c;
                }
            }
        }
        return $flat;
    }

    // ═══════════════════════════════════════════════════════════════════════
    // STATIC HELPERS
    // ═══════════════════════════════════════════════════════════════════════

    public static function getContentTypeLabel(string $type): string
    {
        return match ($type) {
            'pdf'   => 'File PDF',
            'video' => 'Video',
            'link'  => 'Link Eksternal',
            default => 'Teks',
        };
    }

    public static function getContentTypeIcon(string $type): string
    {
        return match ($type) {
            'pdf'   => 'bi-file-earmark-pdf text-danger',
            'video' => 'bi-play-circle text-danger',
            'link'  => 'bi-link-45deg text-primary',
            default => 'bi-file-text text-secondary',
        };
    }

    public static function toEmbedUrl(string $url): string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([A-Za-z0-9_-]{11})/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1] . '?rel=0';
        }
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return $url;
    }

    /** Label level kelas */
    public static function getLevelLabel(int $level): string
    {
        if ($level === 0) return 'Semua Level';
        return 'Kelas ' . $level;
    }
}
