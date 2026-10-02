<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\BkJournalModel;
use App\Models\BkAppointmentModel;
use App\Models\BkSummonModel;

class CounselingReport extends BaseController
{
    public function index()
    {
        $journalModel     = new BkJournalModel();
        $appointmentModel = new BkAppointmentModel();
        $summonModel      = new BkSummonModel();

        // 1. Statistik Jurnal Berdasarkan Jenis Layanan
        $db = \Config\Database::connect();
        
        $statTypesQuery = $db->query("
            SELECT counseling_type, COUNT(id) as total 
            FROM bk_journals 
            GROUP BY counseling_type
        ");
        $statTypes = $statTypesQuery->getResultArray();

        // 2. Statistik Surat Panggilan (Berdasarkan Status)
        $statSummonsQuery = $db->query("
            SELECT status, COUNT(id) as total 
            FROM bk_summons 
            GROUP BY status
        ");
        $statSummons = $statSummonsQuery->getResultArray();

        // 3. Jurnal Konseling 6 Bulan Terakhir
        $statMonthlyQuery = $db->query("
            SELECT DATE_FORMAT(session_date, '%Y-%m') as month, COUNT(id) as total 
            FROM bk_journals 
            WHERE session_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
            GROUP BY DATE_FORMAT(session_date, '%Y-%m')
            ORDER BY month ASC
        ");
        $statMonthly = $statMonthlyQuery->getResultArray();

        $data = [
            'title'       => 'Rekapitulasi & Laporan BK',
            'statTypes'   => $statTypes,
            'statSummons' => $statSummons,
            'statMonthly' => $statMonthly,
            'totalJournals' => $journalModel->countAllResults(),
            'totalAppointments' => $appointmentModel->countAllResults(),
            'totalSummons' => $summonModel->countAllResults(),
        ];

        return view('admin/counseling_report/index', $data);
    }
}
