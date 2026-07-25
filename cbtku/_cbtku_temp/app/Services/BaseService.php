<?php

namespace App\Services;

/**
 * Base Service Class
 * 
 * Provides common functionality for all service classes
 */
class BaseService
{
    /**
     * Database connection
     * @var \CodeIgniter\Database\BaseConnection
     */
    protected $db;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * Log error message
     * 
     * @param string $context Service context
     * @param string $message Error message
     * @param \Throwable|null $exception Exception object
     */
    protected function logError(string $context, string $message, ?\Throwable $exception = null): void
    {
        $logMessage = "[{$context}] {$message}";
        
        if ($exception) {
            $logMessage .= " | Exception: " . $exception->getMessage();
            $logMessage .= " | File: " . $exception->getFile() . ":" . $exception->getLine();
        }
        
        log_message('error', $logMessage);
    }

    /**
     * Log debug message
     * 
     * @param string $context Service context
     * @param string $message Debug message
     */
    protected function logDebug(string $context, string $message): void
    {
        log_message('debug', "[{$context}] {$message}");
    }

    /**
     * Create success response
     * 
     * @param mixed $data Response data
     * @param string $message Success message
     * @return array
     */
    protected function successResponse($data = null, string $message = 'Success'): array
    {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }

    /**
     * Create error response
     * 
     * @param string $message Error message
     * @param mixed $errors Error details
     * @return array
     */
    protected function errorResponse(string $message, $errors = null): array
    {
        return [
            'success' => false,
            'message' => $message,
            'errors' => $errors
        ];
    }
}
