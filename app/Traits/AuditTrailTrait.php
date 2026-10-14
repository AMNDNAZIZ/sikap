<?php

namespace App\Traits;

use App\Models\AuditLogModel;

trait AuditTrailTrait
{
    // Caching old data before update/delete
    protected $auditOldData = [];

    /**
     * Boot the trait to hook into model events
     * CI4 Models check for initializeTraitName methods automatically 
     * if we register them in model's constructors, OR we can just inject into event arrays.
     * Since CI4 doesn't auto-register trait callbacks easily without explicit definition,
     * we advise models using this trait to merge these into their $afterInsert, $beforeUpdate, etc.
     */
    
    protected function setupAuditTrail()
    {
        $this->afterInsert[] = 'auditAfterInsert';
        $this->beforeUpdate[] = 'auditBeforeUpdate';
        $this->afterUpdate[] = 'auditAfterUpdate';
        $this->beforeDelete[] = 'auditBeforeDelete';
        $this->afterDelete[] = 'auditAfterDelete';
    }

    public function auditAfterInsert(array $data)
    {
        if (isset($data['result']) && $data['result']) {
            $recordId = $data['id'] ?? $this->getInsertID();
            $newData = $data['data'] ?? [];

            $auditModel = new AuditLogModel();
            $auditModel->logAction('insert', $this->table, $recordId, null, $newData);
        }
        return $data;
    }

    public function auditBeforeUpdate(array $data)
    {
        // $data['id'] contains array of IDs being updated
        if (isset($data['id'])) {
            $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
            foreach ($ids as $id) {
                // Fetch old data
                $oldRecord = $this->find($id);
                if ($oldRecord) {
                    $this->auditOldData[$id] = $oldRecord;
                }
            }
        }
        return $data;
    }

    public function auditAfterUpdate(array $data)
    {
        if (isset($data['result']) && $data['result'] && isset($data['id'])) {
            $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
            $newData = $data['data'] ?? [];

            $auditModel = new AuditLogModel();
            
            foreach ($ids as $id) {
                $old = $this->auditOldData[$id] ?? null;
                $auditModel->logAction('update', $this->table, $id, $old, $newData);
                unset($this->auditOldData[$id]); // Clean up
            }
        }
        return $data;
    }

    public function auditBeforeDelete(array $data)
    {
        if (isset($data['id'])) {
            $ids = is_array($data['id']) ? $data['id'] : [$data['id']];
            foreach ($ids as $id) {
                $oldRecord = $this->find($id);
                if ($oldRecord) {
                    $this->auditOldData[$id] = $oldRecord;
                }
            }
        }
        return $data;
    }

    public function auditAfterDelete(array $data)
    {
        if (isset($data['result']) && $data['result'] && isset($data['id'])) {
            $ids = is_array($data['id']) ? $data['id'] : [$data['id']];

            $auditModel = new AuditLogModel();
            
            foreach ($ids as $id) {
                $old = $this->auditOldData[$id] ?? null;
                $auditModel->logAction('delete', $this->table, $id, $old, null);
                unset($this->auditOldData[$id]);
            }
        }
        return $data;
    }
}
