<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\StudentRecordModel;
use App\Models\AcademicYearModel;
use App\Libraries\PdfGenerator;

class Alumni extends BaseController
{
    protected $recordModel;
    protected $yearModel;

    public function __construct()
    {
        $this->recordModel = new StudentRecordModel();
        $this->yearModel = new AcademicYearModel();
    }

    public function index()
    {
        $yearFilter = $this->request->getGet('year');
        $search = $this->request->getGet('search');

        $builder = $this->recordModel
            ->select('student_records.*, students.nisn, students.nis, students.name, students.gender, classes.name as class_name, academic_years.year as academic_year')
            ->join('students', 'students.id = student_records.student_id')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->where('student_records.status', 'lulus');

        if ($yearFilter) {
            $builder->where('academic_years.year', $yearFilter);
        }

        if ($search) {
            $builder->groupStart()
                ->like('students.name', $search)
                ->orLike('students.nis', $search)
                ->orLike('students.nisn', $search)
                ->groupEnd();
        }

        $years = $this->yearModel
            ->select('academic_years.year')
            ->join('student_records', 'student_records.academic_year_id = academic_years.id')
            ->where('student_records.status', 'lulus')
            ->groupBy('academic_years.year')
            ->orderBy('academic_years.year', 'DESC')
            ->findAll();

        $data = [
            'title' => 'Data Alumni',
            'alumni' => $builder->orderBy('academic_years.year', 'DESC')
                                ->orderBy('students.name', 'ASC')
                                ->paginate(20),
            'pager' => $builder->pager,
            'years' => $years,
            'selectedYear' => $yearFilter,
            'search' => $search,
        ];

        return view('admin/alumni/index', $data);
    }

    public function exportPdf()
    {
        $yearFilter = $this->request->getGet('year');

        $builder = $this->recordModel
            ->select('student_records.*, students.nisn, students.nis, students.name, students.gender, classes.name as class_name, academic_years.year as academic_year')
            ->join('students', 'students.id = student_records.student_id')
            ->join('classes', 'classes.id = student_records.class_id', 'left')
            ->join('academic_years', 'academic_years.id = student_records.academic_year_id', 'left')
            ->where('student_records.status', 'lulus');

        if ($yearFilter) {
            $builder->where('academic_years.year', $yearFilter);
        }

        $alumni = $builder->orderBy('academic_years.year', 'DESC')
                          ->orderBy('students.name', 'ASC')
                          ->findAll();

        $data = [
            'alumni' => $alumni,
            'yearFilter' => $yearFilter,
            'school' => $this->school ?? [],
        ];

        $filename = 'daftar_alumni' . ($yearFilter ? '_' . str_replace('/', '-', $yearFilter) : '') . '.pdf';

        $pdfGen = new PdfGenerator();
        $pdfGen->stream('admin/alumni/pdf_template', $data, $filename, 'landscape', true);
    }
}
