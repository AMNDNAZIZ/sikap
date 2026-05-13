<?php

namespace App\Models;

use CodeIgniter\Model;

class SettingsModel extends Model
{
    protected $table         = 'settings';
    protected $primaryKey    = 'id';
    protected $allowedFields = ['setting_key', 'setting_value'];
    protected $useTimestamps = true;

    public function getValue(string $key): ?string
    {
        $row = $this->where('setting_key', $key)->first();
        return $row ? $row['setting_value'] : null;
    }

    public function setValue(string $key, string $value): bool
    {
        $existing = $this->where('setting_key', $key)->first();
        if ($existing) {
            return $this->update($existing['id'], ['setting_value' => $value]);
        }
        return (bool) $this->insert(['setting_key' => $key, 'setting_value' => $value]);
    }
}
