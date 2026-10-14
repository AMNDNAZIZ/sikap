<?php

if (!function_exists('audit_log')) {
    /**
     * Rekam log aktivitas secara manual
     *
     * @param string $action Contoh: 'login', 'logout', 'download_rapor'
     * @param string|null $tableName Tabel terkait jika ada
     * @param string|null $recordId ID record terkait jika ada
     * @param mixed $oldData Data lama
     * @param mixed $newData Data baru
     * @return bool
     */
    function audit_log($action, $tableName = null, $recordId = null, $oldData = null, $newData = null)
    {
        $auditModel = new \App\Models\AuditLogModel();
        return $auditModel->logAction($action, $tableName, $recordId, $oldData, $newData);
    }
}
