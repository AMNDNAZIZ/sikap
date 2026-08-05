<?= $this->extend('layouts/cbt') ?>
<?= $this->section('content') ?>

<?php
use App\Models\StudentModel;

// Ambil data user dari session
$user = session()->get('user');

// Pastikan user yang login adalah siswa dan memiliki relasi student_id
$studentId = $user['related_type'] === 'student' ? ($user['related_id'] ?? null) : null;

$student = null;

if ($studentId) {
    $studentModel = new StudentModel();
    $student = $studentModel->find($studentId);
}
?>

<?php if ($student): ?>
  <h4>Selamat datang, <?= esc($student['name']) ?> 👋</h4>
  <p><strong>NIS:</strong> <?= esc($student['nis']) ?></p>
  <p><strong>Username:</strong> <?= esc($student['username']) ?></p>
  <p><strong>Kelas:</strong> <?= esc($student['class_id']) ?></p>
  <p><strong>Agama:</strong> <?= esc($student['religion']) ?></p>
<?php else: ?>
  <div class="alert alert-warning">
    Data siswa tidak ditemukan atau akun ini tidak terkait dengan siswa.
  </div>
<?php endif; ?>

<?= $this->endSection() ?>
