<?php
/**
 * ============================================
 * LICENSE SERVER: Domain Transfer Handler
 * ============================================
 * 
 * File ini untuk ditambahkan ke server lisensi
 * Menangani request transfer domain dari client
 * 
 * Integration: Tambahkan ke file verify.php yang sudah ada
 */

/**
 * Handle domain transfer request
 * 
 * @param PDO $pdo Database connection
 * @param array $data Request data
 * @return array Response
 */
function handleDomainTransfer($pdo, $data)
{
    $licenseKey = $data['license_key'] ?? '';
    $oldDomain = $data['old_domain'] ?? '';
    $newDomain = $data['new_domain'] ?? '';
    $machineId = $data['machine_id'] ?? '';
    
    // Validation
    if (empty($licenseKey) || empty($oldDomain) || empty($newDomain)) {
        return [
            'status' => 'error',
            'message' => 'Parameter tidak lengkap'
        ];
    }
    
    // Validate domain format
    if (!filter_var('http://' . $newDomain, FILTER_VALIDATE_URL)) {
        return [
            'status' => 'error',
            'message' => 'Format domain baru tidak valid'
        ];
    }
    
    // Check if license exists
    $stmt = $pdo->prepare("
        SELECT * FROM licenses 
        WHERE license_key = ? 
        AND status = 'active'
    ");
    $stmt->execute([$licenseKey]);
    $license = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$license) {
        return [
            'status' => 'error',
            'message' => 'Lisensi tidak ditemukan atau tidak aktif'
        ];
    }
    
    // Check if current domain matches
    if ($license['domain'] !== $oldDomain) {
        // Log suspicious activity
        logAudit($pdo, $licenseKey, 'TRANSFER_DOMAIN_MISMATCH', [
            'registered_domain' => $license['domain'],
            'claimed_old_domain' => $oldDomain,
            'new_domain' => $newDomain
        ]);
        
        return [
            'status' => 'error',
            'message' => 'Domain lama tidak cocok dengan yang terdaftar'
        ];
    }
    
    // Check transfer limit (1x per 30 days)
    $stmt = $pdo->prepare("
        SELECT * FROM license_transfers 
        WHERE license_key = ? 
        AND status = 'completed'
        AND created_at > DATE_SUB(NOW(), INTERVAL 30 DAY)
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->execute([$licenseKey]);
    $lastTransfer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($lastTransfer) {
        $nextAllowed = date('Y-m-d H:i:s', strtotime($lastTransfer['created_at'] . ' +30 days'));
        
        return [
            'status' => 'error',
            'message' => 'Transfer domain dibatasi 1x per 30 hari. Transfer terakhir: ' . $lastTransfer['created_at'],
            'data' => [
                'last_transfer' => $lastTransfer['created_at'],
                'next_allowed' => $nextAllowed,
                'days_remaining' => ceil((strtotime($nextAllowed) - time()) / 86400)
            ]
        ];
    }
    
    // Begin transaction
    $pdo->beginTransaction();
    
    try {
        // Update license domain
        $stmt = $pdo->prepare("
            UPDATE licenses 
            SET domain = ?,
                last_transfer_at = NOW(),
                last_check = NOW(),
                updated_at = NOW()
            WHERE license_key = ?
        ");
        $stmt->execute([$newDomain, $licenseKey]);
        
        // Log transfer
        $stmt = $pdo->prepare("
            INSERT INTO license_transfers 
            (license_key, old_domain, new_domain, machine_id, ip_address, user_agent, status)
            VALUES (?, ?, ?, ?, ?, ?, 'completed')
        ");
        $stmt->execute([
            $licenseKey,
            $oldDomain,
            $newDomain,
            $machineId,
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
        
        // Log audit
        logAudit($pdo, $licenseKey, 'TRANSFER_DOMAIN', [
            'old_domain' => $oldDomain,
            'new_domain' => $newDomain,
            'machine_id' => $machineId
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Send notification email (optional)
        sendTransferNotification($license, $oldDomain, $newDomain);
        
        return [
            'status' => 'success',
            'message' => 'Transfer domain berhasil',
            'data' => [
                'old_domain' => $oldDomain,
                'new_domain' => $newDomain,
                'transfer_date' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        
        // Log error
        error_log('Domain transfer failed: ' . $e->getMessage());
        
        return [
            'status' => 'error',
            'message' => 'Gagal melakukan transfer domain. Silakan coba lagi.'
        ];
    }
}

/**
 * Log audit trail
 */
function logAudit($pdo, $licenseKey, $action, $data)
{
    try {
        $stmt = $pdo->prepare("
            INSERT INTO license_audit_log 
            (license_key, action, old_value, new_value, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $licenseKey,
            $action,
            isset($data['old_domain']) ? $data['old_domain'] : null,
            isset($data['new_domain']) ? $data['new_domain'] : json_encode($data),
            $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    } catch (Exception $e) {
        error_log('Audit log failed: ' . $e->getMessage());
    }
}

/**
 * Send notification email (optional)
 */
function sendTransferNotification($license, $oldDomain, $newDomain)
{
    // Implement email notification jika diperlukan
    // Contoh: kirim email ke admin atau client
    
    $to = 'admin@sakasalika.com'; // Email developer
    $subject = 'License Domain Transfer - ' . $license['license_key'];
    $message = "
        License Key: {$license['license_key']}
        Old Domain: {$oldDomain}
        New Domain: {$newDomain}
        Transfer Date: " . date('Y-m-d H:i:s') . "
        IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown') . "
    ";
    
    // Uncomment untuk enable email
    // mail($to, $subject, $message);
    
    // Atau gunakan service lain seperti SendGrid, Mailgun, dll
}

/**
 * ============================================
 * INTEGRATION EXAMPLE
 * ============================================
 * 
 * Tambahkan ke file verify.php yang sudah ada:
 */

/*
// Di file verify.php, tambahkan case baru:

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'activate':
        // existing code...
        break;
        
    case 'verify':
        // existing code...
        break;
        
    case 'check_renewal':
        // existing code...
        break;
        
    case 'transfer_domain':
        // NEW: Handle domain transfer
        $response = handleDomainTransfer($pdo, $_POST);
        break;
        
    case 'deactivate':
        // existing code...
        break;
        
    default:
        $response = [
            'status' => 'error',
            'message' => 'Invalid action'
        ];
}

header('Content-Type: application/json');
echo json_encode($response);
*/

/**
 * ============================================
 * TESTING
 * ============================================
 * 
 * Test dengan curl:
 * 
 * curl -X POST https://lisensi.sakasalika.com/api/verify.php \
 *   -d "action=transfer_domain" \
 *   -d "license_key=XXXX-XXXX-XXXX-XXXX" \
 *   -d "old_domain=old-domain.com" \
 *   -d "new_domain=new-domain.com" \
 *   -d "machine_id=abc123..."
 * 
 * Expected response:
 * {
 *   "status": "success",
 *   "message": "Transfer domain berhasil",
 *   "data": {
 *     "old_domain": "old-domain.com",
 *     "new_domain": "new-domain.com",
 *     "transfer_date": "2026-03-06 10:30:00"
 *   }
 * }
 */

/**
 * ============================================
 * SECURITY NOTES
 * ============================================
 * 
 * 1. Rate Limiting
 *    - Implement rate limiting untuk prevent abuse
 *    - Max 5 requests per IP per minute
 * 
 * 2. IP Whitelist (optional)
 *    - Whitelist IP client untuk extra security
 * 
 * 3. HTTPS Only
 *    - Pastikan server menggunakan HTTPS
 * 
 * 4. Input Validation
 *    - Validate semua input
 *    - Sanitize domain names
 * 
 * 5. Audit Trail
 *    - Log semua transfer attempts
 *    - Monitor suspicious activities
 * 
 * 6. Notification
 *    - Email notification untuk setiap transfer
 *    - Alert jika ada transfer mencurigakan
 */

/**
 * ============================================
 * MONITORING QUERIES
 * ============================================
 */

/*
-- Check recent transfers
SELECT 
    lt.*,
    l.domain as current_domain,
    l.status as license_status
FROM license_transfers lt
LEFT JOIN licenses l ON lt.license_key = l.license_key
ORDER BY lt.created_at DESC
LIMIT 20;

-- Check transfer frequency per license
SELECT 
    license_key,
    COUNT(*) as transfer_count,
    MIN(created_at) as first_transfer,
    MAX(created_at) as last_transfer
FROM license_transfers
WHERE status = 'completed'
GROUP BY license_key
HAVING transfer_count > 3
ORDER BY transfer_count DESC;

-- Check suspicious activities
SELECT 
    license_key,
    action,
    old_value,
    new_value,
    ip_address,
    timestamp
FROM license_audit_log
WHERE action LIKE '%MISMATCH%'
OR action LIKE '%FAILED%'
ORDER BY timestamp DESC
LIMIT 50;
*/
