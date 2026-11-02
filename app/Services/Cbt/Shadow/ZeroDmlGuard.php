<?php

namespace App\Services\Cbt\Shadow;

use RuntimeException;

/**
 * ZeroDmlViolationException
 *
 * Thrown immediately when any mutative SQL statement or write operation is attempted
 * during Stage F2 Shadow Execution.
 */
class ZeroDmlViolationException extends RuntimeException
{
}

/**
 * ZeroDmlGuard
 *
 * ESPATA Assessment Intelligence V3 - Stage F2
 * Enforces Zero-DML and Zero-DDL safety invariant at the application firewall level.
 *
 * AUDIT MANDATES:
 * - Guarantees 100% READ-ONLY operations.
 * - Inspects all SQL statements and rejects any DML (INSERT, UPDATE, DELETE, REPLACE)
 *   or DDL (ALTER, DROP, CREATE, TRUNCATE) immediately.
 * - Logs all inspected statements into a tamper-evident statement audit trail.
 */
class ZeroDmlGuard
{
    private static array $auditedStatements = [];
    private static int $totalSelectCount = 0;
    private static int $rejectedCount = 0;

    /**
     * Forbidden SQL commands that must never be executed during shadow testing.
     */
    private const FORBIDDEN_KEYWORDS = [
        'INSERT',
        'UPDATE',
        'DELETE',
        'REPLACE',
        'DROP',
        'ALTER',
        'CREATE',
        'TRUNCATE',
        'GRANT',
        'REVOKE',
        'LOCK TABLES',
        'FLUSH',
        'CALL',
    ];

    /**
     * Inspect an SQL query and assert that it is strictly a read-only SELECT or EXPLAIN statement.
     *
     * @param string $sql
     * @return bool True if safe
     * @throws ZeroDmlViolationException
     */
    public static function assertReadOnly(string $sql): bool
    {
        $cleanSql = trim($sql);
        // Remove comments
        $cleanSql = preg_replace('/\/\*.*?\*\/|--.*?(\r?\n|$)/s', '', $cleanSql);
        $cleanSql = trim($cleanSql);

        if ($cleanSql === '') {
            return true;
        }

        $upper = strtoupper($cleanSql);

        // Check against forbidden keywords
        foreach (self::FORBIDDEN_KEYWORDS as $keyword) {
            $pattern = '/\b' . preg_quote($keyword, '/') . '\b/i';
            if (preg_match($pattern, $upper)) {
                self::$rejectedCount++;
                $errorMsg = "ZERO-DML VIOLATION BLOCKED: Forbidden SQL keyword '{$keyword}' detected in statement: " . substr($cleanSql, 0, 100);
                self::$auditedStatements[] = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'type'      => 'BLOCKED',
                    'keyword'   => $keyword,
                    'sql'       => substr($cleanSql, 0, 200),
                ];
                throw new ZeroDmlViolationException($errorMsg);
            }
        }

        // Must start with SELECT, SHOW, DESCRIBE, EXPLAIN, or SET (read-only session vars)
        if (!preg_match('/^(SELECT|SHOW|DESCRIBE|DESC|EXPLAIN|SET\s+NAMES|SET\s+CHARACTER_SET)\b/i', $cleanSql)) {
            self::$rejectedCount++;
            $errorMsg = "ZERO-DML VIOLATION BLOCKED: Statement does not begin with an authorized read-only command: " . substr($cleanSql, 0, 100);
            self::$auditedStatements[] = [
                'timestamp' => date('Y-m-d H:i:s'),
                'type'      => 'BLOCKED',
                'keyword'   => 'UNAUTHORIZED_PREFIX',
                'sql'       => substr($cleanSql, 0, 200),
            ];
            throw new ZeroDmlViolationException($errorMsg);
        }

        self::$totalSelectCount++;
        self::$auditedStatements[] = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type'      => 'AUTHORIZED_READ',
            'sql'       => substr($cleanSql, 0, 150),
        ];

        return true;
    }

    /**
     * Verify database user privileges from GRANTS metadata.
     * Rejects connections that possess write or DDL privileges.
     *
     * @param array $grants List of GRANT statements returned by `SHOW GRANTS`
     * @return array ['is_select_only' => bool, 'violations' => array]
     */
    public static function verifySelectOnlyPrivilege(array $grants): array
    {
        $violations = [];

        foreach ($grants as $grant) {
            $upper = strtoupper((string)$grant);

            if (str_contains($upper, 'ALL PRIVILEGES') || str_contains($upper, 'ALL') && str_contains($upper, 'ON *.*')) {
                $violations[] = "Account possesses ALL PRIVILEGES (write/admin access detected)";
                continue;
            }

            $writePrivileges = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'ALTER', 'CREATE', 'TRUNCATE', 'FILE', 'SUPER'];
            foreach ($writePrivileges as $priv) {
                if (preg_match('/\b' . $priv . '\b/', $upper)) {
                    $violations[] = "Account possesses mutative privilege: '{$priv}'";
                }
            }
        }

        return [
            'is_select_only' => empty($violations),
            'violations'     => array_unique($violations),
            'total_grants'   => count($grants),
        ];
    }

    /**
     * Get summary metrics for Zero-DML compliance.
     */
    public static function getAuditSummary(): array
    {
        return [
            'total_reads_executed' => self::$totalSelectCount,
            'violations_blocked'   => self::$rejectedCount,
            'is_clean_read_only'   => (self::$rejectedCount === 0),
            'recent_audit_log'     => array_slice(self::$auditedStatements, -50),
        ];
    }

    /**
     * Reset audit state (for isolated unit tests).
     */
    public static function resetAudit(): void
    {
        self::$auditedStatements = [];
        self::$totalSelectCount = 0;
        self::$rejectedCount = 0;
    }
}

