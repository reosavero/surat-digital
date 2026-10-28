<?php

/**
 * ActivityLog Model - Surat Digital TVRI Jawa Timur
 * Handles System Audit Logging for Superadmin Tracking
 */

class ActivityLog extends Model
{
    protected string $table = 'activity_logs';

    /**
     * Ensure table activity_logs exists in database
     */
    public static function ensureTableExists(): void
    {
        static $checked = false;
        if ($checked) return;
        $checked = true;

        $sql = "CREATE TABLE IF NOT EXISTS `activity_logs` (
          `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
          `user_id` INT UNSIGNED NULL,
          `action` VARCHAR(100) NOT NULL,
          `description` TEXT NOT NULL,
          `ip_address` VARCHAR(45) NULL,
          `user_agent` TEXT NULL,
          `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
          INDEX `idx_user_id` (`user_id`),
          INDEX `idx_action` (`action`),
          INDEX `idx_created_at` (`created_at`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

        try {
            Database::query($sql);
        } catch (\Throwable $e) {
            error_log("Error creating activity_logs table: " . $e->getMessage());
        }
    }

    /**
     * Purge logs older than 7 days
     */
    public static function purgeOldLogs(): void
    {
        self::ensureTableExists();
        try {
            Database::query("DELETE FROM activity_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY)");
        } catch (\Throwable $e) {
            error_log("Failed to purge old activity logs: " . $e->getMessage());
        }
    }

    /**
     * Insert a new activity log entry
     */
    public static function log(string $action, string $description, ?int $userId = null): bool
    {
        self::purgeOldLogs();

        if ($userId === null && class_exists('Session')) {
            $userId = Session::get('user_id');
        }

        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ipAddress = trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
        }
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

        try {
            return (bool) Database::query(
                "INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)",
                [$userId, $action, $description, $ipAddress, $userAgent]
            );
        } catch (\Throwable $e) {
            error_log("Failed to log activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get paginated activity logs with filtering
     */
    public static function getPaginated(int $page = 1, int $limit = 10, array $filters = []): array
    {
        self::purgeOldLogs();

        $where = [
            "l.action NOT IN ('LOGIN', 'LOGOUT', 'LOGIN_FAILED')",
            "l.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        ];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = "(l.description LIKE ? OR l.action LIKE ? OR u.name LIKE ? OR u.username LIKE ? OR l.ip_address LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        if (!empty($filters['action'])) {
            $where[] = "l.action = ?";
            $params[] = $filters['action'];
        }

        if (!empty($filters['role'])) {
            $where[] = "u.role = ?";
            $params[] = $filters['role'];
        }

        if (!empty($filters['date_start'])) {
            $where[] = "DATE(l.created_at) >= ?";
            $params[] = $filters['date_start'];
        }

        if (!empty($filters['date_end'])) {
            $where[] = "DATE(l.created_at) <= ?";
            $params[] = $filters['date_end'];
        }

        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";

        // Count total rows
        $countSql = "SELECT COUNT(*) as total FROM activity_logs l LEFT JOIN users u ON l.user_id = u.id {$whereClause}";
        $countResult = Database::fetch($countSql, $params);
        $totalRows = (int)($countResult['total'] ?? 0);

        $totalPages = (int)ceil($totalRows / $limit);
        if ($totalPages < 1) $totalPages = 1;
        if ($page < 1) $page = 1;
        if ($page > $totalPages) $page = $totalPages;

        $offset = ($page - 1) * $limit;

        // Fetch paginated logs
        $sql = "SELECT l.*, u.name as user_nama, u.username as user_username, u.role as user_role, u.jabatan as user_jabatan, u.avatar as user_avatar
                FROM activity_logs l
                LEFT JOIN users u ON l.user_id = u.id
                {$whereClause}
                ORDER BY l.id DESC
                LIMIT {$limit} OFFSET {$offset}";

        $logs = Database::fetchAll($sql, $params);

        return [
            'logs' => $logs,
            'total_rows' => $totalRows,
            'total_pages' => $totalPages,
            'current_page' => $page,
            'limit' => $limit
        ];
    }

    /**
     * Get summary stats for activity logs
     */
    public static function getStats(): array
    {
        self::ensureTableExists();

        $totalLogs = (int)(Database::fetch("SELECT COUNT(*) as c FROM activity_logs")['c'] ?? 0);
        $todayLogs = (int)(Database::fetch("SELECT COUNT(*) as c FROM activity_logs WHERE DATE(created_at) = CURDATE()")['c'] ?? 0);
        $loginLogs = (int)(Database::fetch("SELECT COUNT(*) as c FROM activity_logs WHERE action = 'LOGIN'")['c'] ?? 0);
        $suratLogs = (int)(Database::fetch("SELECT COUNT(*) as c FROM activity_logs WHERE action LIKE 'CREATE_SURAT%' OR action LIKE 'PROCESS_%'")['c'] ?? 0);

        return [
            'total' => $totalLogs,
            'today' => $todayLogs,
            'logins' => $loginLogs,
            'surat_actions' => $suratLogs
        ];
    }

    /**
     * Clear old activity logs
     */
    public static function clearLogs(): bool
    {
        self::ensureTableExists();
        return (bool) Database::query("TRUNCATE TABLE activity_logs");
    }
}
