<?php
namespace App\Commands;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class CheckLevel extends BaseCommand
{
    protected $group       = 'Custom';
    protected $name        = 'custom:checklevel';
    protected $description = 'Checks school level.';

    public function run(array $params)
    {
        $db = \Config\Database::connect();
        $school = $db->table('school_profile')->select('level')->get()->getRowArray();
        CLI::write("School Level in DB: " . ($school['level'] ?? 'NULL'));
        
        helper('authorization');
        CLI::write("Helper get_school_level(): " . get_school_level());
        CLI::write("Helper is_school_sd(): " . (is_school_sd() ? 'true' : 'false'));
    }
}
