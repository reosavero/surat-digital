<?php

/**
 * ActivityLog Controller - Surat Digital TVRI Jawa Timur
 * Exclusive for Superadmin to view, search, and audit system activities
 */

class ActivityLogController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            exit;
        }

        $role = Session::get('user_role', '');
        if ($role !== 'superadmin') {
            Session::setFlash('error', 'Akses ditolak. Fitur Activity Log hanya dapat diakses oleh Superadmin.');
            $this->redirect('/dashboard');
            exit;
        }
    }

    public function index(): void
    {
        $search = Security::sanitize($_GET['search'] ?? '');
        $action = Security::sanitize($_GET['action'] ?? '');
        $roleFilter = Security::sanitize($_GET['role'] ?? '');
        $dateStart = Security::sanitize($_GET['date_start'] ?? '');
        $dateEnd = Security::sanitize($_GET['date_end'] ?? '');
        $page = (int)($_GET['page'] ?? 1);

        $filters = [
            'search' => $search,
            'action' => $action,
            'role' => $roleFilter,
            'date_start' => $dateStart,
            'date_end' => $dateEnd
        ];

        $paginated = ActivityLog::getPaginated($page, 10, $filters);
        $stats = ActivityLog::getStats();

        $this->view('activity_logs/index', [
            'title' => 'Log Aktivitas Sistem - Superadmin',
            'logs' => $paginated['logs'],
            'pagination' => $paginated,
            'stats' => $stats,
            'filters' => $filters
        ]);
    }

    public function clear(): void
    {
        ActivityLog::clearLogs();
        ActivityLog::log('CLEAR_ACTIVITY_LOGS', 'Superadmin membersihkan seluruh data Log Aktivitas Sistem');
        Session::setFlash('success', 'Seluruh data Log Aktivitas Sistem telah berhasil dibersihkan.');
        $this->redirect('/activity-logs');
    }
}
