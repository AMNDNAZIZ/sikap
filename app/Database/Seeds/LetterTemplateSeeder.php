<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\LetterTemplateModel;

class LetterTemplateSeeder extends Seeder
{
    public function run()
    {
        $model = new LetterTemplateModel();
        $model->ensureDefaultTemplates();
        echo "Default Letter Templates seeded successfully!\n";
    }
}
