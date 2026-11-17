<?php

namespace App\Services\Ai\DTO;

use JsonSerializable;

/**
 * Data Transfer Object (DTO) Konteks Operasional Akademik ESPATA (Fase 1D.3)
 * 
 * Merepresentasikan fakta operasional satuan pendidikan, jenjang, kalender akademik,
 * dan penugasan mengajar guru yang terautentikasi dari server secara aman dan konsisten.
 * 
 * PRINSIP INTEGRITAS & PRIVASI:
 * 1. Read-Only & Server-Authoritative: Bersumber murni dari database server, bukan payload klien.
 * 2. Zero Student PII: Tidak menyimpan data pribadi siswa (nama, NISN, nilai individual).
 * 3. Zero Secret: Tidak menyimpan API key, password, hash, atau rahasia sistem.
 * 4. Faktual & Jujur: Membedakan nilai kosong, tidak berlaku, dan kondisi ambigu tanpa halusinasi.
 */
class OperationalContextDTO implements JsonSerializable
{
    // Identitas & Role Pengguna Server
    public int $userId;
    public string $username;
    public string $fullname;
    public int $roleId;
    public string $roleName;

    // Konteks Satuan Pendidikan
    public ?string $schoolName = null;
    public ?string $npsn = null;
    public int $schoolLevel = 0;          // 1=SD, 2=SMP, 3=SMA, 0=Tidak Terdefinisi
    public string $schoolLevelLabel = 'Tidak terdefinisi';

    // Konteks Kalender Akademik
    public ?int $academicYearId = null;
    public ?string $academicYear = null;  // Contoh: '2026/2027'
    public bool $isAcademicYearActive = false;
    public ?int $semester = null;         // 1 (Ganjil) atau 2 (Genap)
    public string $semesterLabel = 'Tidak terdefinisi';
    public string $semesterSource = 'unknown'; // 'academic_calendar' | 'calendar_estimate_fallback' | 'unknown'

    // Konteks Guru & Penugasan Mengajar
    public ?int $teacherId = null;
    public bool $isTeacher = false;
    public bool $hasAssignments = false;
    public bool $isMultiAssignment = false;
    
    /** @var array<int, array> Daftar seluruh penugasan KBM aktif terperinci */
    public array $assignments = [];
    
    /** @var array<string> Daftar nama kelas unik yang diampu */
    public array $assignedClasses = [];
    
    /** @var array<string> Daftar nama mata pelajaran unik yang diampu */
    public array $assignedSubjects = [];

    // Evaluasi Fokus Konteks Aktif & Ambiguity
    public ?array $activeAssignment = null;
    public bool $isAmbiguous = false;
    public ?string $ambiguityReason = null;
    public bool $clarificationNeeded = false;
    public ?string $clarificationPrompt = null;

    // Status Agregat Konteks
    // Nilai: 'COMPLETE' | 'PARTIAL' | 'NO_ASSIGNMENT' | 'NON_TEACHER' | 'ANONYMOUS' | 'DATA_INCONSISTENT'
    public string $status = 'PARTIAL';

    public function __construct(
        int $userId,
        string $username,
        string $fullname,
        int $roleId,
        string $roleName
    ) {
        $this->userId   = $userId;
        $this->username = $username;
        $this->fullname = $fullname;
        $this->roleId   = $roleId;
        $this->roleName = $roleName;
    }

    /**
     * Memformat konteks operasional menjadi blok teks ringkas untuk diinjeksikan ke prompt LLM
     */
    public function toPromptBlock(): string
    {
        $lines = [];
        $lines[] = "[KONTEKS OPERASIONAL PENGGUNA & SEKOLAH]";
        $lines[] = "- Pengguna: " . ($this->fullname ?: $this->username) . " (Peran: " . $this->roleName . ")";

        if ($this->schoolName) {
            $lines[] = "- Satuan Pendidikan: " . $this->schoolName . " (Jenjang: " . $this->schoolLevelLabel . ")";
        } else {
            $lines[] = "- Satuan Pendidikan: Belum terkonfigurasi di sistem";
        }

        if ($this->academicYear) {
            $semText = $this->semester ? "Semester " . $this->semester . " (" . $this->semesterLabel . ")" : "Semester tidak terdefinisi";
            if ($this->semesterSource === 'calendar_estimate_fallback') {
                $semText .= " [Estimasi Kalender Standar]";
            }
            $lines[] = "- Tahun Ajaran: " . $this->academicYear . " | " . $semText;
        } else {
            $lines[] = "- Tahun Ajaran: Belum ada tahun ajaran aktif di sistem";
        }

        if ($this->isTeacher) {
            if ($this->hasAssignments) {
                $summaryList = [];
                foreach ($this->assignments as $a) {
                    $faseText = !empty($a['fase']) && $a['fase'] !== '-' ? "Fase " . $a['fase'] : "Tingkat " . ($a['class_level'] ?? '?');
                    $summaryList[] = $a['subject_name'] . " - " . $a['class_name'] . " (" . $faseText . ")";
                }
                $lines[] = "- Penugasan KBM Aktif (" . count($this->assignments) . " Penugasan):";
                foreach ($summaryList as $item) {
                    $lines[] = "  * " . $item;
                }

                if ($this->activeAssignment !== null) {
                    $actFase = !empty($this->activeAssignment['fase']) && $this->activeAssignment['fase'] !== '-' 
                        ? "Fase " . $this->activeAssignment['fase'] 
                        : "Tingkat " . ($this->activeAssignment['class_level'] ?? '?');
                    $lines[] = "- Fokus Konteks Terdeteksi: " . $this->activeAssignment['subject_name'] . " - " . $this->activeAssignment['class_name'] . " (" . $actFase . ")";
                } elseif ($this->isAmbiguous) {
                    $lines[] = "- Fokus Konteks: AMBIGU (" . $this->ambiguityReason . ")";
                    if ($this->clarificationPrompt) {
                        $lines[] = "- Instruksi Klarifikasi: " . $this->clarificationPrompt;
                    }
                }
            } else {
                $lines[] = "- Penugasan KBM: BELUM MEMILIKI PENUGASAN AKTIF pada sistem untuk tahun ajaran ini.";
                $lines[] = "- Catatan: Jangan mengasumsikan mapel/kelas yang diampu. Layani pertanyaan secara normatif.";
            }
        } elseif ($this->roleId === 2) {
            $lines[] = "- Fungsi: Manajerial & Supervisi Akademik Sekolah.";
        } elseif ($this->roleId === 1) {
            $lines[] = "- Fungsi: Tata Kelola Sistem & Administrasi Akademik Global.";
        }

        return implode("\n", $lines);
    }

    /**
     * Mengubah DTO menjadi array representasi data
     */
    public function toArray(): array
    {
        return [
            'user' => [
                'id'       => $this->userId,
                'username' => $this->username,
                'fullname' => $this->fullname,
                'role_id'  => $this->roleId,
                'role_name'=> $this->roleName,
            ],
            'school' => [
                'name'        => $this->schoolName,
                'npsn'        => $this->npsn,
                'level'       => $this->schoolLevel,
                'level_label' => $this->schoolLevelLabel,
            ],
            'academic_year' => [
                'id'        => $this->academicYearId,
                'year'      => $this->academicYear,
                'is_active' => $this->isAcademicYearActive,
                'semester'  => $this->semester,
                'semester_label'  => $this->semesterLabel,
                'semester_source' => $this->semesterSource,
            ],
            'teaching' => [
                'teacher_id'        => $this->teacherId,
                'is_teacher'        => $this->isTeacher,
                'has_assignments'   => $this->hasAssignments,
                'is_multi_assignment' => $this->isMultiAssignment,
                'assignments'       => $this->assignments,
                'assigned_classes'  => $this->assignedClasses,
                'assigned_subjects' => $this->assignedSubjects,
                'active_assignment' => $this->activeAssignment,
                'is_ambiguous'      => $this->isAmbiguous,
                'ambiguity_reason'  => $this->ambiguityReason,
                'clarification_needed' => $this->clarificationNeeded,
                'clarification_prompt' => $this->clarificationPrompt,
            ],
            'status' => $this->status,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
