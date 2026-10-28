<?php

/**
 * Disposisi Model - Surat Digital TVRI Jawa Timur
 */

class Disposisi extends Model
{
    protected string $table = 'disposisi';

    public function getDisposisiForUser(int $userId): array
    {
        $userModel = new User();
        $user = $userModel->findById($userId);
        if (!$user) return [];

        $role = $user['role'] ?? '';
        $divisi = html_entity_decode(trim($user['divisi'] ?? ''), ENT_QUOTES, 'UTF-8');
        $jabatan = html_entity_decode(trim($user['jabatan'] ?? ''), ENT_QUOTES, 'UTF-8');

        // Determine matching recipient keys for this user
        $matchingKeys = [];

        if (in_array($role, ['kapel_bidang', 'kapel'])) {
            if (!empty($divisi)) {
                $matchingKeys[] = $divisi;
                if (str_contains($divisi, 'Konten') && str_contains($divisi, 'Kreatif')) {
                    $matchingKeys[] = 'Kapel Konten Kreatif Digital';
                    $matchingKeys[] = 'Kapel Konten, Kreatif Digital';
                }
            }
            if (!empty($jabatan) && $jabatan !== $divisi) {
                $matchingKeys[] = $jabatan;
            }
        } elseif (in_array($role, ['kpp_bidang', 'kpp'])) {
            if (str_contains($divisi, 'Program')) {
                $matchingKeys[] = 'KPP Program';
            } elseif (str_contains($divisi, 'Berita')) {
                $matchingKeys[] = 'KPP Berita';
            } elseif (str_contains($divisi, 'Media Baru') || str_contains($divisi, 'KMB')) {
                $matchingKeys[] = 'KPP Media Baru';
            } elseif (str_contains($divisi, 'Teknik')) {
                $matchingKeys[] = 'KPP Teknik';
            } elseif (str_contains($divisi, 'Pengembangan') || str_contains($divisi, 'PU')) {
                $matchingKeys[] = 'KPP PU';
            } elseif (str_contains($divisi, 'Umum')) {
                $matchingKeys[] = 'KPP Umum';
            } elseif (str_contains($divisi, 'Keuangan')) {
                $matchingKeys[] = 'KPP Keuangan';
            }
            if (!empty($divisi)) {
                $matchingKeys[] = $divisi;
                $matchingKeys[] = 'KPP ' . $divisi;
            }
        } elseif (in_array($role, ['tata_usaha', 'tu', 'ktu'])) {
            $matchingKeys[] = 'Kasubbag Tata Usaha';
            $matchingKeys[] = 'Tata Usaha';
        }

        $matchingKeys = array_unique(array_filter($matchingKeys));

        if (empty($matchingKeys)) {
            return [];
        }

        // Fetch all completed disposisi records
        $allDisposisi = Database::fetchAll(
            "SELECT d.*, s.id as surat_id_real, s.kode_surat, s.type, s.perihal, s.kepada, s.tanggal_surat, s.status as surat_status, s.gdrive_web_link, s.pdf_path, s.meta_data,
                    u.name as pembuat_nama, k.name as kepsta_nama
             FROM {$this->table} d
             JOIN surat s ON d.surat_id = s.id
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN users k ON d.kepsta_user_id = k.id
             WHERE (s.status = 'completed' OR d.kepsta_signed_at IS NOT NULL)
             ORDER BY d.updated_at DESC, d.id DESC"
        );

        $results = [];
        foreach ($allDisposisi as $row) {
            $checklist = [];
            if (!empty($row['checklist_json'])) {
                $checklist = json_decode($row['checklist_json'], true) ?? [];
            }

            $isMatched = false;
            foreach ($matchingKeys as $key) {
                foreach ($checklist as $checkedItem) {
                    $itemTrim = trim($checkedItem);
                    $keyTrim = trim($key);
                    if (strcasecmp($itemTrim, $keyTrim) === 0 || str_contains(strtolower($itemTrim), strtolower($keyTrim)) || str_contains(strtolower($keyTrim), strtolower($itemTrim))) {
                        $isMatched = true;
                        break 2;
                    }
                }
            }

            if ($isMatched) {
                if (!empty($row['meta_data'])) {
                    $row['meta'] = json_decode($row['meta_data'], true) ?? [];
                } else {
                    $row['meta'] = [];
                }
                $row['checklist'] = $checklist;
                $row['id'] = (int)$row['surat_id_real'];
                $results[] = $row;
            }
        }

        return $results;
    }

    public function getBySuratId(int $suratId): array|false
    {
        $dispo = Database::fetch(
            "SELECT d.*, s.name as sekre_nama, k.name as kepsta_nama, k.jabatan as kepsta_jabatan
             FROM {$this->table} d
             LEFT JOIN users s ON d.sekre_user_id = s.id
             LEFT JOIN users k ON d.kepsta_user_id = k.id
             WHERE d.surat_id = ?",
            [$suratId]
        );

        if ($dispo && !empty($dispo['checklist_json'])) {
            $dispo['checklist'] = json_decode($dispo['checklist_json'], true) ?? [];
        } else if ($dispo) {
            $dispo['checklist'] = [];
        }

        return $dispo;
    }

    public function createOrUpdate(int $suratId, array $data): bool
    {
        $existing = $this->getBySuratId($suratId);
        $checklistJson = isset($data['checklist']) ? json_encode($data['checklist']) : null;

        if ($existing) {
            $fields = [];
            $params = [];

            if (isset($data['sekre_user_id'])) {
                $fields[] = "`sekre_user_id` = ?";
                $params[] = $data['sekre_user_id'];
            }
            if (isset($data['sifat_surat'])) {
                $fields[] = "`sifat_surat` = ?";
                $params[] = $data['sifat_surat'];
            }
            if (isset($data['catatan_sekre'])) {
                $fields[] = "`catatan_sekre` = ?";
                $params[] = $data['catatan_sekre'];
            }
            if (isset($data['sekre_approved_at'])) {
                $fields[] = "`sekre_approved_at` = ?";
                $params[] = $data['sekre_approved_at'];
            }
            if (isset($data['kepsta_user_id'])) {
                $fields[] = "`kepsta_user_id` = ?";
                $params[] = $data['kepsta_user_id'];
            }
            if (isset($data['checklist'])) {
                $fields[] = "`checklist_json` = ?";
                $params[] = $checklistJson;
            }
            if (isset($data['catatan_kepsta'])) {
                $fields[] = "`catatan_kepsta` = ?";
                $params[] = $data['catatan_kepsta'];
            }
            if (isset($data['kepsta_signature'])) {
                $fields[] = "`kepsta_signature` = ?";
                $params[] = $data['kepsta_signature'];
            }
            if (isset($data['kepsta_signed_at'])) {
                $fields[] = "`kepsta_signed_at` = ?";
                $params[] = $data['kepsta_signed_at'];
            }

            if (empty($fields)) return false;

            $params[] = $existing['id'];
            $sql = "UPDATE {$this->table} SET " . implode(", ", $fields) . " WHERE id = ?";
            return (bool) Database::query($sql, $params);
        } else {
            return (bool) Database::query(
                "INSERT INTO {$this->table} 
                (surat_id, sekre_user_id, sifat_surat, catatan_sekre, sekre_approved_at, kepsta_user_id, checklist_json, catatan_kepsta, kepsta_signature, kepsta_signed_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $suratId,
                    $data['sekre_user_id'] ?? null,
                    $data['sifat_surat'] ?? null,
                    $data['catatan_sekre'] ?? null,
                    $data['sekre_approved_at'] ?? date('Y-m-d H:i:s'),
                    $data['kepsta_user_id'] ?? null,
                    $checklistJson,
                    $data['catatan_kepsta'] ?? null,
                    $data['kepsta_signature'] ?? null,
                    $data['kepsta_signed_at'] ?? null
                ]
            );
        }
    }

    public static function getSifatSuratLabel(?string $sifat = null): string
    {
        if (empty($sifat)) return '-';
        $s = strtolower(trim($sifat));
        return match ($s) {
            'biasa' => 'Biasa',
            'segera' => 'Segera',
            'sangat_segera', 'sangat segera' => 'Sangat Segera',
            'penting' => 'Penting',
            'rahasia' => 'Rahasia',
            default => ucfirst($sifat)
        };
    }

    public static function getSifatBadgeClass(?string $sifat = null): string
    {
        if (empty($sifat)) return 'badge-secondary';
        $s = strtolower(trim($sifat));
        return match ($s) {
            'biasa' => 'badge-sifat-biasa',
            'segera' => 'badge-sifat-segera',
            'sangat_segera', 'sangat segera' => 'badge-sifat-segera',
            'penting' => 'badge-sifat-penting',
            'rahasia' => 'badge-sifat-rahasia',
            default => 'badge-sifat-biasa'
        };
    }

    public static function getDefaultKepstaChecklistItems(): array
    {
        return [
            'Tindak Lanjuti',
            'Selesaikan',
            'Arsipkan / File',
            'Jadwalkan / Hadir',
            'Pelajari & Pertimbangkan',
            'Edarkan ke Bagian Terkait',
            'Bicarakan dengan Saya',
            'Untuk Diketahui / Catatan'
        ];
    }
}
