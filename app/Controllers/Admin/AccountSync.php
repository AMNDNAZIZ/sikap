<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentModel;
use App\Models\UserModel;

class AccountSync extends BaseController
{
    public function syncParents()
    {
        helper('security'); // Load security helper
        
        $db = \Config\Database::connect();
        $studentModel = new StudentModel();
        $userModel = new UserModel();

        $students = $studentModel->findAll();
        $countCreated = 0;
        $countSkipped = 0;

        foreach ($students as $student) {
            $username = 'ortu_' . $student['nis'];

            // Check if parent user already exists for this student
            $existing = $userModel->where([
                'related_id' => $student['id'],
                'role_id' => 4,
                'related_type' => 'student'
            ])->first();

            // Generate password dengan pattern: ortu[NIS]
            $defaultPassword = generate_default_password('ortu', $student['nis']);

            $userData = [
                'username' => $username,
                'password' => password_hash($defaultPassword, PASSWORD_BCRYPT),
                'fullname' => 'Orang Tua ' . $student['name'],
                'email' => $student['nis'] . '@ortu.com',
                'role_id' => 4,
                'related_id' => $student['id'],
                'related_type' => 'student',
                'must_change_password' => 1, // Wajib ganti password
            ];

            if (!$existing) {
                $userModel->insert($userData);
                $countCreated++;
            } else {
                $userModel->update($existing['id'], $userData);
                $countSkipped++; // We'll count this as "Updated" in the message
            }
        }

        return "Sync Completed. Created: $countCreated, Updated: $countSkipped. <br><br> <a href='/dashboard'>Back to Dashboard</a>";
    }
}
