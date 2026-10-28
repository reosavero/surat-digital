<?php

/**
 * Disposisi Controller - Surat Digital TVRI Jawa Timur
 * Main view for Kapel role and disposisi workflow
 */

class DisposisiController extends Controller
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
        $userJabatan = Session::get('user_jabatan', '');

        $dispoModel = new Disposisi();
        $disposisiList = $dispoModel->getDisposisiForUser($userId);

        $this->view('disposisi/index', [
            'title' => 'Disposisi Surat - Surat Digital TVRI Jawa Timur',
            'user' => [
                'id' => $userId,
                'name' => $userName,
                'role' => $userRole,
                'jabatan' => $userJabatan
            ],
            'disposisiList' => $disposisiList
        ]);
    }
}
