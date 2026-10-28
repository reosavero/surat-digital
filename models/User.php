<?php

/**
 * User Model - Surat Digital
 */

class User extends Model
{
    protected string $table = 'users';

    public function findByUsernameOrEmail(string $identifier): array|false
    {
        $trimmed = trim($identifier);
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE (LOWER(name) = LOWER(?) OR LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) OR nip = ?) AND deleted_at IS NULL",
            [$trimmed, $trimmed, $trimmed, $trimmed]
        );
    }

    public function findAllByUsernameOrEmail(string $identifier): array
    {
        $trimmed = trim($identifier);
        return Database::fetchAll(
            "SELECT * FROM {$this->table} WHERE (LOWER(name) = LOWER(?) OR LOWER(username) = LOWER(?) OR LOWER(email) = LOWER(?) OR nip = ?) AND deleted_at IS NULL ORDER BY id ASC",
            [$trimmed, $trimmed, $trimmed, $trimmed]
        );
    }

    public function findById(int $id): array|false
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND deleted_at IS NULL",
            [$id]
        );
    }

    public function getAllUsers(): array
    {
        self::syncAutoUsernames();
        return Database::fetchAll(
            "SELECT id, name, username, email, nip, divisi, role, jabatan, status, avatar, created_at FROM {$this->table} WHERE (status = 'active' OR status IS NULL OR status = '') AND deleted_at IS NULL ORDER BY role ASC, name ASC"
        );
    }

    public function getKppUsers(): array
    {
        return Database::fetchAll(
            "SELECT id, name, username, email, nip, divisi, role, jabatan, signature FROM {$this->table} WHERE (role = 'kpp_bidang' OR role = 'kpp') AND (status = 'active' OR status IS NULL OR status = '') AND deleted_at IS NULL ORDER BY divisi ASC, name ASC"
        );
    }

    public function getPendingUsers(): array
    {
        return Database::fetchAll(
            "SELECT id, name, username, email, nip, divisi, role, jabatan, status, created_at FROM {$this->table} WHERE status = 'pending' AND deleted_at IS NULL ORDER BY created_at DESC"
        );
    }

    public function approveUser(int $id): bool
    {
        return (bool) Database::query(
            "UPDATE {$this->table} SET status = 'active' WHERE id = ?",
            [$id]
        );
    }

    public function rejectUser(int $id): bool
    {
        return (bool) Database::query(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    public function purgeRejectedUserByEmailOrNip(string $email, ?string $nip = null): void
    {
        if (!empty($nip)) {
            Database::query(
                "DELETE FROM {$this->table} WHERE (email = ? OR nip = ?) AND (status = 'rejected' OR deleted_at IS NOT NULL)",
                [$email, $nip]
            );
        } else {
            Database::query(
                "DELETE FROM {$this->table} WHERE email = ? AND (status = 'rejected' OR deleted_at IS NOT NULL)",
                [$email]
            );
        }
    }

    public function createUser(array $data): int
    {
        if (!empty($data['email'])) {
            $this->purgeRejectedUserByEmailOrNip($data['email'], $data['nip'] ?? null);
        }

        Database::query(
            "INSERT INTO {$this->table} (name, username, email, password, role, jabatan, nip, divisi, status, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $data['name'],
                $data['username'] ?? $data['email'],
                $data['email'],
                Security::hashPassword($data['password']),
                $data['role'] ?? 'admin',
                $data['jabatan'] ?? $data['divisi'] ?? null,
                $data['nip'] ?? null,
                $data['divisi'] ?? null,
                $data['status'] ?? 'active',
                $data['signature'] ?? null
            ]
        );

        return (int) Database::lastInsertId();
    }

    public function updateUser(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        if (isset($data['name'])) {
            $fields[] = "name = ?";
            $params[] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = "email = ?";
            $params[] = $data['email'];
        }
        if (isset($data['username'])) {
            $fields[] = "username = ?";
            $params[] = $data['username'];
        }
        if (isset($data['nip'])) {
            $fields[] = "nip = ?";
            $params[] = $data['nip'];
        }
        if (isset($data['divisi'])) {
            $fields[] = "divisi = ?";
            $params[] = $data['divisi'];
        }
        if (!empty($data['password'])) {
            $fields[] = "password = ?";
            $params[] = Security::hashPassword($data['password']);
        }
        if (isset($data['role'])) {
            $fields[] = "role = ?";
            $params[] = $data['role'];
        }
        if (isset($data['jabatan'])) {
            $fields[] = "jabatan = ?";
            $params[] = $data['jabatan'];
        }
        if (isset($data['signature'])) {
            $fields[] = "signature = ?";
            $params[] = $data['signature'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(", ", $fields) . " WHERE id = ?";
        return (bool) Database::query($sql, $params);
    }

    public function deleteUser(int $id): bool
    {
        return (bool) Database::query(
            "DELETE FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    public static function isSingleUserRole(string $role): bool
    {
        return in_array($role, ['tata_usaha', 'tu', 'ktu', 'kepala_stasiun', 'kepsta']);
    }

    public function countUsersByRole(string $role): int
    {
        $roles = match ($role) {
            'tata_usaha', 'tu', 'ktu' => ['tata_usaha', 'tu'],
            'kepala_stasiun', 'kepsta' => ['kepala_stasiun', 'kepsta'],
            default => [$role, $role]
        };

        return (int)(Database::fetch(
            "SELECT COUNT(*) as total FROM {$this->table} WHERE role IN (?, ?) AND deleted_at IS NULL",
            [$roles[0], $roles[1]]
        )['total'] ?? 0);
    }

    public static function getSingleUserByRole(string $role): array|false
    {
        $roles = match ($role) {
            'tata_usaha', 'tu', 'ktu' => ['tata_usaha', 'tu'],
            'kepala_stasiun', 'kepsta' => ['kepala_stasiun', 'kepsta'],
            default => [$role, $role]
        };

        return Database::fetch(
            "SELECT * FROM users WHERE role IN (?, ?) AND deleted_at IS NULL ORDER BY id ASC LIMIT 1",
            [$roles[0], $roles[1]]
        );
    }

    public function getSingleRoleCounts(): array
    {
        return [
            'tata_usaha' => $this->countUsersByRole('tata_usaha'),
            'kepala_stasiun' => $this->countUsersByRole('kepala_stasiun')
        ];
    }

    public function getKapelUsers(): array
    {
        return Database::fetchAll(
            "SELECT id, name, username, email, nip, divisi, role, jabatan, signature FROM {$this->table} WHERE (role = 'kapel_bidang' OR role = 'kapel') AND (status = 'active' OR status IS NULL OR status = '') AND deleted_at IS NULL ORDER BY divisi ASC, name ASC"
        );
    }

    public static function generateAutoUsername(string $role, ?string $divisi = null, ?string $defaultNip = null): string
    {
        $role = strtolower(trim($role));
        $divisi = trim($divisi ?? '');

        switch ($role) {
            case 'superadmin':
                return 'superadmin';
            case 'admin':
                return 'admin';
            case 'tata_usaha':
            case 'tu':
            case 'ktu':
                return 'tu';
            case 'sekretaris':
            case 'sekre':
                return 'sekre';
            case 'kepala_stasiun':
            case 'kepsta':
                return 'kepsta';
            case 'kpp_bidang':
            case 'kpp':
                return self::generateKppUsername($divisi);
            case 'kapel_bidang':
            case 'kapel':
                return self::generateKapelUsername($divisi);
            default:
                return !empty($defaultNip) ? $defaultNip : ('user_' . mt_rand(1000, 9999));
        }
    }

    public static function generateKppUsername(string $divisi): string
    {
        $divisi = html_entity_decode(trim($divisi), ENT_QUOTES, 'UTF-8');
        $map = [
            'Program' => 'kpp_program',
            'Berita' => 'kpp_berita',
            'Konten Media Baru (KMB)' => 'kpp_kmb',
            'Teknik' => 'kpp_teknik',
            'Pengembangan dan Usaha (PU)' => 'kpp_pu',
            'Umum' => 'kpp_umum',
            'Keuangan' => 'kpp_keuangan',
        ];

        if (isset($map[$divisi])) {
            return $map[$divisi];
        }

        $slug = self::slugifyBidang($divisi);
        return 'kpp_' . ($slug ?: 'bidang');
    }

    public static function generateKapelUsername(string $divisi): string
    {
        $divisi = html_entity_decode(trim($divisi), ENT_QUOTES, 'UTF-8');
        $map = [
            'Kapel Monev Program' => 'kapel_monev_program',
            'Kapel Pengelola SDM & RB' => 'kapel_sdm',
            'Kapel Dokumentasi & Distribusi Audio Video' => 'kapel_dokumentasi',
            'Kapel Perlengkapan & Sarpras' => 'kapel_sarpras',
            'Kapel CA & OR' => 'kapel_caor',
            'Kapel Pengelola BMN' => 'kapel_bmn',
            'Kapel Berita' => 'kapel_berita',
            'Kapel Humas & Protokol' => 'kapel_humas',
            'Kapel Monev Berita' => 'kapel_monev_berita',
            'Kapel Konten, Kreatif Digital' => 'kapel_kreatif',
            'Kapel Penjualan & Pemasaran' => 'kapel_pemasaran',
            'Kapel Analitik & Distribusi Konten' => 'kapel_analitik',
            'Kapel IT' => 'kapel_it',
            'PPK I' => 'kapel_ppk1',
            'Kapel Produksi & Penyiaran Teknik' => 'kapel_produksi_teknik',
            'PPK II' => 'kapel_ppk2',
            'Kapel Produksi Program' => 'kapel_produksi_program',
            'Kapel Transmisi' => 'kapel_transmisi',
            'Kapel Promo' => 'kapel_promo',
            'Kapel Evaluasi & Pelaporan Keuangan' => 'kapel_evaluasi_keuangan',
        ];

        if (isset($map[$divisi])) {
            return $map[$divisi];
        }

        $cleanDivisi = preg_replace('/^Kapel\s+/i', '', $divisi);
        $slug = self::slugifyBidang($cleanDivisi);
        return 'kapel_' . ($slug ?: 'bidang');
    }

    private static function slugifyBidang(string $text): string
    {
        $text = str_replace('&', ' ', $text);
        $text = preg_replace('/[^a-zA-Z0-9\s_]/', '', $text);
        $text = preg_replace('/\s+/', '_', strtolower(trim($text)));
        return $text;
    }

    public static function getUniqueUsername(string $baseUsername, int $userId = 0): string
    {
        return $baseUsername;
    }

    public static function syncAutoUsernames(): void
    {
        try {
            $users = Database::fetchAll("SELECT id, role, divisi, nip, username FROM users WHERE deleted_at IS NULL ORDER BY id ASC");
            foreach ($users as $u) {
                $base = self::generateAutoUsername($u['role'] ?? '', $u['divisi'] ?? '', $u['nip'] ?? '');
                if (!empty($base)) {
                    $unique = self::getUniqueUsername($base, (int)$u['id']);
                    if (($u['username'] ?? '') !== $unique) {
                        Database::query("UPDATE users SET username = ? WHERE id = ?", [$unique, $u['id']]);
                    }
                }
            }
        } catch (Throwable $e) {
            // Ignore exception if DB not reachable or table missing
        }
    }

    public static function getRoleLabel(string $role): string
    {
        return match ($role) {
            'admin' => 'Admin',
            'kpp_bidang', 'kpp' => 'KPP Bidang',
            'kapel_bidang', 'kapel' => 'Kapel Bidang',
            'tata_usaha', 'tu' => 'Tata Usaha',
            'sekretaris', 'sekre' => 'Sekretaris',
            'kepala_stasiun', 'kepsta' => 'Kepala Stasiun',
            'superadmin' => 'Superadmin',
            default => ucfirst($role)
        };
    }

    public static function getRoleBadgeClass(string $role): string
    {
        return match ($role) {
            'admin' => 'bg-primary-subtle text-primary border-primary',
            'kpp_bidang', 'kpp' => 'bg-indigo-subtle text-indigo border-indigo',
            'kapel_bidang', 'kapel' => 'bg-success-subtle text-success border-success',
            'tata_usaha', 'tu' => 'bg-info-subtle text-info border-info',
            'sekretaris', 'sekre' => 'bg-warning-subtle text-warning-emphasis border-warning',
            'kepala_stasiun', 'kepsta' => 'bg-success-subtle text-success border-success',
            'superadmin' => 'bg-danger-subtle text-danger border-danger',
            default => 'bg-secondary-subtle text-secondary'
        };
    }
}
