<?php namespace App\Models;

use CodeIgniter\Model;

/**
 * Model nilai PTS (Penilaian Tengah Semester).
 * Terpisah dari summative_scores; TIDAK dipakai dalam perhitungan rapor akhir.
 */
class PtsScoreModel extends Model
{
    protected $table      = 'pts_scores';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'student_id', 'subject_id', 'year_id', 'semester',
        'score', 'source', 'created_by', 'created_at', 'updated_at',
    ];
    protected $useTimestamps = true;
}
