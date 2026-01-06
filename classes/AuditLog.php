<?php
/**
 * AuditLog Class
 * 
 * Comprehensive audit trail for tracking all critical changes
 * across the application for compliance and debugging.
 */

require_once __DIR__ . '/Database.php';

class AuditLog
{
    private $db;
    public $id;
    public $company_id;
    public $table_name;
    public $record_id;
    public $action;
    public $field_name;
    public $old_value;
    public $new_value;
    public $change_summary;
    public $reference_type;
    public $reference_number;
    public $user_id;
    public $user_name;
    public $ip_address;
    public $user_agent;
    public $created_at;

    public function __construct($id = null)
    {
        $this->db = new Database();
        if ($id) {
            $this->load($id);
        }
    }

    /**
     * Load audit log entry by ID
     */
    public function load($id)
    {
        $query = "SELECT * FROM audit_log WHERE id = ?";
        $stmt = $this->db->prepareSelect($query, [$id]);
        if ($stmt && $row = $stmt->fetch()) {
            foreach ($row as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
            return true;
        }
        return false;
    }

    /**
     * Log a create action
     */
    public static function logCreate($companyId, $tableName, $recordId, $summary, $refType = null, $refNumber = null)
    {
        return self::log($companyId, $tableName, $recordId, 'create', null, null, null, $summary, $refType, $refNumber);
    }

    /**
     * Log an update action
     */
    public static function logUpdate($companyId, $tableName, $recordId, $fieldName, $oldValue, $newValue, $summary = null, $refType = null, $refNumber = null)
    {
        return self::log($companyId, $tableName, $recordId, 'update', $fieldName, $oldValue, $newValue, $summary, $refType, $refNumber);
    }

    /**
     * Log a delete action
     */
    public static function logDelete($companyId, $tableName, $recordId, $summary, $refType = null, $refNumber = null)
    {
        return self::log($companyId, $tableName, $recordId, 'delete', null, null, null, $summary, $refType, $refNumber);
    }

    /**
     * Log a cancel action
     */
    public static function logCancel($companyId, $tableName, $recordId, $summary, $refType = null, $refNumber = null)
    {
        return self::log($companyId, $tableName, $recordId, 'cancel', null, null, null, $summary, $refType, $refNumber);
    }

    /**
     * Log an approve action
     */
    public static function logApprove($companyId, $tableName, $recordId, $summary, $refType = null, $refNumber = null)
    {
        return self::log($companyId, $tableName, $recordId, 'approve', null, null, null, $summary, $refType, $refNumber);
    }

    /**
     * Log a reject action
     */
    public static function logReject($companyId, $tableName, $recordId, $summary, $refType = null, $refNumber = null)
    {
        return self::log($companyId, $tableName, $recordId, 'reject', null, null, null, $summary, $refType, $refNumber);
    }

    /**
     * Core logging method
     */
    public static function log($companyId, $tableName, $recordId, $action, $fieldName = null, $oldValue = null, $newValue = null, $summary = null, $refType = null, $refNumber = null)
    {
        $db = new Database();
        
        // Get current user info
        $userId = $_SESSION['id'] ?? 0;
        $userName = $_SESSION['username'] ?? 'System';
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

        $query = "INSERT INTO audit_log 
                  (company_id, table_name, record_id, action, field_name, old_value, new_value, 
                   change_summary, reference_type, reference_number, user_id, user_name, ip_address, user_agent)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        return $db->prepareExecute($query, [
            $companyId,
            $tableName,
            $recordId,
            $action,
            $fieldName,
            is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            is_array($newValue) ? json_encode($newValue) : $newValue,
            $summary,
            $refType,
            $refNumber,
            $userId,
            $userName,
            $ipAddress,
            $userAgent
        ]);
    }

    /**
     * Get audit history for a specific record
     */
    public static function getHistory($companyId, $tableName, $recordId, $limit = 50)
    {
        $db = new Database();
        $query = "SELECT * FROM audit_log 
                  WHERE company_id = ? AND table_name = ? AND record_id = ?
                  ORDER BY created_at DESC
                  LIMIT ?";
        $stmt = $db->prepareSelect($query, [$companyId, $tableName, $recordId, $limit]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get recent activity for a company
     */
    public static function getRecentActivity($companyId, $limit = 100)
    {
        $db = new Database();
        $query = "SELECT * FROM audit_log 
                  WHERE company_id = ?
                  ORDER BY created_at DESC
                  LIMIT ?";
        $stmt = $db->prepareSelect($query, [$companyId, $limit]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get activity by user
     */
    public static function getByUser($companyId, $userId, $limit = 100)
    {
        $db = new Database();
        $query = "SELECT * FROM audit_log 
                  WHERE company_id = ? AND user_id = ?
                  ORDER BY created_at DESC
                  LIMIT ?";
        $stmt = $db->prepareSelect($query, [$companyId, $userId, $limit]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get activity by reference (e.g., all logs for a specific GRN)
     */
    public static function getByReference($companyId, $refType, $refNumber)
    {
        $db = new Database();
        $query = "SELECT * FROM audit_log 
                  WHERE company_id = ? AND reference_type = ? AND reference_number = ?
                  ORDER BY created_at DESC";
        $stmt = $db->prepareSelect($query, [$companyId, $refType, $refNumber]);
        return $stmt ? $stmt->fetchAll() : [];
    }

    /**
     * Get activity by date range
     */
    public static function getByDateRange($companyId, $startDate, $endDate, $limit = 500)
    {
        $db = new Database();
        $query = "SELECT * FROM audit_log 
                  WHERE company_id = ? AND DATE(created_at) BETWEEN ? AND ?
                  ORDER BY created_at DESC
                  LIMIT ?";
        $stmt = $db->prepareSelect($query, [$companyId, $startDate, $endDate, $limit]);
        return $stmt ? $stmt->fetchAll() : [];
    }
}

