<?php

/**
 * Dashboard Controller - Surat Digital TVRI Jawa Timur
 */

class DashboardController extends Controller
{
    public function index(): void
    {
        if (!Session::isLoggedIn()) {
            $this->redirect('/login');
            return;
        }

        $userId = (int) Session::get('user_id');
        $userRole = Session::get('user_role', 'admin');
        $userName = Session::get('user_name', 'User');

        if ($userRole === 'superadmin') {
            $this->redirect('/users');
            return;
        }

        if (in_array($userRole, ['kapel_bidang', 'kapel'])) {
            $this->redirect('/disposisi');
            return;
        }

        $suratModel = new Surat();
        $stats = $suratModel->getStatsForRole($userRole, $userId);
        $recentSurat = $suratModel->getSuratListForRole($userRole, $userId);

        $this->view('dashboard/index', [
            'title' => 'Dashboard - Surat Digital TVRI Jawa Timur',
            'user' => [
                'id' => $userId,
                'name' => $userName,
                'role' => $userRole,
                'jabatan' => Session::get('user_jabatan', '')
            ],
            'stats' => $stats,
            'recentSurat' => array_slice($recentSurat, 0, 10)
        ]);
    }

    public function index2(): void
    {
        $userRole = Session::get('user_role', 'admin');
        if ($userRole === 'superadmin') {
            $this->redirect('/users');
            return;
        }
        $this->redirect('/dashboard');
    }
}
