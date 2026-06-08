<?php

namespace App\Controllers\Siswa;

use App\Controllers\BaseController;
use App\Models\{
    StudentModel,
    StudentNoteModel,
    StudentNoteBehaviorModel
};

class StudentNotes extends BaseController
{
    protected $studentModel;
    protected $noteModel;
    protected $noteBehaviorModel;

    public function __construct()
    {
        $this->studentModel = new StudentModel();
        $this->noteModel = new StudentNoteModel();
        $this->noteBehaviorModel = new StudentNoteBehaviorModel();
    }

    public function index()
    {
        $user = session()->get('user');
        if (!$user || !in_array((int) ($user['role_id'] ?? 0), [4, 5])) {
            return redirect()->to('/login')->with('error', 'Akses ditolak.');
        }

        $studentId = $user['student_id'] ?? null;
        $student = $this->studentModel->find($studentId);

        if (!$student) {
            return redirect()->to('/')->with('error', 'Data siswa tidak ditemukan.');
        }

        $notes = $this->noteModel
            ->where('student_id', $studentId)
            ->orderBy('created_at', 'DESC')
            ->findAll();

        foreach ($notes as &$note) {
            $note['behaviors'] = $this->noteBehaviorModel
                ->select('behaviors.*')
                ->join('behaviors', 'behaviors.id = student_note_behaviors.behavior_id')
                ->where('note_id', $note['id'])
                ->findAll();
        }

        return view('siswa/student-notes/index', [
            'student' => $student,
            'notes' => $notes,
        ]);
    }
}
