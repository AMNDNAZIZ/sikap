<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Database\Exceptions\DatabaseException;

class BackupRestore extends Controller
{
    public function index()
    {
        $data['title'] = 'Backup & Restore Database';
        return view('admin/backup_restore/index', $data);
    }

    public function backup()
    {
        helper('filesystem');

        try {
            $db = \Config\Database::connect();
            $tables = $db->listTables();
            $backupSQL = '';

            foreach ($tables as $table) {
                // Dapatkan struktur tabel
                $query = $db->query("SHOW CREATE TABLE `$table`")->getRowArray();
                $createTableSQL = $query['Create Table'] . ";\n\n";
                $backupSQL .= "-- TABEL: $table\n" . $createTableSQL;

                // Dapatkan data isi tabel
                $data = $db->query("SELECT * FROM `$table`")->getResultArray();
                if (!empty($data)) {
                    foreach ($data as $row) {
                        $escapedValues = array_map(fn($v) => $db->escape($v), $row);
                        $columns = '`' . implode('`, `', array_keys($row)) . '`';
                        $values = implode(', ', $escapedValues);
                        $backupSQL .= "INSERT INTO `$table` ($columns) VALUES ($values);\n";
                    }
                    $backupSQL .= "\n";
                }
            }

            $filename = 'backup_' . date('Ymd_His') . '.sql';
            $filepath = WRITEPATH . 'backups/' . $filename;

            if (!is_dir(WRITEPATH . 'backups')) {
                mkdir(WRITEPATH . 'backups', 0777, true);
            }

            write_file($filepath, $backupSQL);

            // Zip file
            $zip = new \ZipArchive();
            $zipName = $filename . '.zip';
            $zipPath = WRITEPATH . 'backups/' . $zipName;

            if ($zip->open($zipPath, \ZipArchive::CREATE) === true) {
                $zip->addFile($filepath, $filename);
                $zip->close();
                unlink($filepath);

                return $this->response->download($zipPath, null);
            } else {
                return redirect()->back()->with('error', 'Gagal membuat arsip zip.');
            }

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Backup gagal: ' . $e->getMessage());
        }
    }



    public function restore()
    {
        helper('filesystem');

        $file = $this->request->getFile('backup_file');

        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }

        if ($file->getExtension() !== 'zip') {
            return redirect()->back()->with('error', 'Format tidak didukung. Harus berupa file ZIP.');
        }

        try {
            $zip = new \ZipArchive();
            $tempName = $file->getTempName();

            $extractPath = WRITEPATH . 'backups/';

            if ($zip->open($tempName) === TRUE) {
                $zip->extractTo($extractPath);
                $zip->close();

                // Cari file SQL hasil ekstrak
                $sqlPath = null;
                $files = get_filenames($extractPath);

                foreach ($files as $f) {
                    if (pathinfo($f, PATHINFO_EXTENSION) === 'sql') {
                        $sqlPath = $extractPath . $f;
                        break;
                    }
                }

                if (!$sqlPath || !file_exists($sqlPath)) {
                    return redirect()->back()->with('error', 'File SQL tidak ditemukan setelah ekstraksi.');
                }

                $db = \Config\Database::connect();
                $db->transBegin();

                $sql = file_get_contents($sqlPath);

                // Hapus komentar dan line kosong
                $sql = preg_replace('/--.*\n/', '', $sql); // hapus komentar --
                $sql = preg_replace('/\/\*.*?\*\//s', '', $sql); // hapus blok komentar /* */

                // Pecah berdasarkan delimiter ; (yang bukan bagian dari prosedur/stored func)
                $queries = explode(";\n", $sql);

                foreach ($queries as $query) {
                    $query = trim($query);
                    if (!empty($query)) {
                        try {
                            $db->query($query);
                        } catch (\Exception $e) {
                            log_message('error', $e->getMessage());
                            $db->transRollback();
                            return redirect()->back()->with('error', 'Restore gagal. Transaksi dibatalkan.');
                        }
                    }
                }

                $db->transCommit();

                // Hapus file SQL hasil ekstrak
                unlink($sqlPath);

                return redirect()->back()->with('success', 'Database berhasil direstore.');
            } else {
                return redirect()->back()->with('error', 'Gagal membuka file ZIP.');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Restore gagal: ' . $e->getMessage());
        }
    }
}
