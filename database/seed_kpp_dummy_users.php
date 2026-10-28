<?php

/**
 * Seed Dummy KPP Users for Each Division - Surat Digital TVRI Jawa Timur
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Security.php';
require_once __DIR__ . '/../helpers/Model.php';
require_once __DIR__ . '/../models/User.php';

$kppDummyUsers = [
    [
        'name' => 'Ir. Sepna Yuhana',
        'username' => 'kpp_program',
        'email' => 'kpp.program@tvrijatim.go.id',
        'nip' => '196809161993032006',
        'divisi' => 'Program',
        'role' => 'kpp_bidang',
        'jabatan' => 'Ketua Perencana dan Pengendalian Program',
        'password' => 'password123',
        'status' => 'active'
    ],
    [
        'name' => 'Drs. H. M. Bambang Sugipto, M.Si',
        'username' => 'kpp_berita',
        'email' => 'kpp.berita@tvrijatim.go.id',
        'nip' => '197204151998031003',
        'divisi' => 'Berita',
        'role' => 'kpp_bidang',
        'jabatan' => 'Ketua Perencana dan Pengendalian Berita',
        'password' => 'password123',
        'status' => 'active'
    ],
    [
        'name' => 'Candra Purnama Tri Mahendra, SH',
        'username' => 'kpp_teknik',
        'email' => 'kpp.teknik@tvrijatim.go.id',
        'nip' => '197508201998031002',
        'divisi' => 'Teknik',
        'role' => 'kpp_bidang',
        'jabatan' => 'Ketua Perencana dan Pengendalian Teknik',
        'password' => 'password123',
        'status' => 'active'
    ],
    [
        'name' => 'Dra. Nining Lestari, MM',
        'username' => 'kpp_pu',
        'email' => 'kpp.pu@tvrijatim.go.id',
        'nip' => '197311101999032001',
        'divisi' => 'Pengembangan dan Usaha (PU)',
        'role' => 'kpp_bidang',
        'jabatan' => 'Ketua Perencana dan Pengendalian Pengembangan dan Usaha',
        'password' => 'password123',
        'status' => 'active'
    ],
    [
        'name' => 'Rina Wahyuni, SE, Ak., M.Ak',
        'username' => 'kpp_keuangan',
        'email' => 'kpp.keuangan@tvrijatim.go.id',
        'nip' => '198106202005012004',
        'divisi' => 'Keuangan',
        'role' => 'kpp_bidang',
        'jabatan' => 'Ketua Perencana dan Pengendalian Keuangan',
        'password' => 'password123',
        'status' => 'active'
    ],
    [
        'name' => 'H. Agus Suprianto, S.Sos',
        'username' => 'kpp_umum',
        'email' => 'kpp.umum@tvrijatim.go.id',
        'nip' => '197803122003121002',
        'divisi' => 'Umum',
        'role' => 'kpp_bidang',
        'jabatan' => 'Ketua Perencana dan Pengendalian Umum',
        'password' => 'password123',
        'status' => 'active'
    ],
    [
        'name' => 'Ahmad Riza Kurniawan, S.ST, M.T',
        'username' => 'kpp_kmb',
        'email' => 'kpp.kmb@tvrijatim.go.id',
        'nip' => '198709052010121001',
        'divisi' => 'Konten Media Baru (KMB)',
        'role' => 'kpp_bidang',
        'jabatan' => 'Ketua Perencana dan Pengendalian Konten Media Baru',
        'password' => 'password123',
        'status' => 'active'
    ]
];

$userModel = new User();
$createdCount = 0;
$updatedCount = 0;

foreach ($kppDummyUsers as $userData) {
    // Check by email or divisi or username
    $existing = Database::fetch(
        "SELECT * FROM users WHERE (email = ? OR username = ? OR (role = 'kpp_bidang' AND divisi = ?)) AND deleted_at IS NULL",
        [$userData['email'], $userData['username'], $userData['divisi']]
    );

    $hashedPassword = Security::hashPassword($userData['password']);

    if ($existing) {
        Database::query(
            "UPDATE users SET name = ?, username = ?, email = ?, nip = ?, divisi = ?, role = ?, jabatan = ?, password = ?, status = 'active' WHERE id = ?",
            [
                $userData['name'],
                $userData['username'],
                $userData['email'],
                $userData['nip'],
                $userData['divisi'],
                $userData['role'],
                $userData['jabatan'],
                $hashedPassword,
                $existing['id']
            ]
        );
        echo "Updated existing user for KPP " . $userData['divisi'] . ": " . $userData['name'] . " (ID: " . $existing['id'] . ")\n";
        $updatedCount++;
    } else {
        Database::query(
            "INSERT INTO users (name, username, email, password, role, jabatan, nip, divisi, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $userData['name'],
                $userData['username'],
                $userData['email'],
                $hashedPassword,
                $userData['role'],
                $userData['jabatan'],
                $userData['nip'],
                $userData['divisi'],
                'active'
            ]
        );
        $newId = Database::lastInsertId();
        echo "Created new user for KPP " . $userData['divisi'] . ": " . $userData['name'] . " (ID: " . $newId . ")\n";
        $createdCount++;
    }
}

echo "\nDone seeding KPP Bidang users. Total created: $createdCount, Total updated: $updatedCount.\n";
