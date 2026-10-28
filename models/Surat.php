<?php

/**
 * Surat Model - Surat Digital TVRI Jawa Timur
 */

class Surat extends Model
{
    protected string $table = 'surat';

    public static function generateKodeSurat(): string
    {
        return 'SR-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid((string)mt_rand(), true)), 0, 4));
    }

    public function createSurat(array $data): int
    {
        $kode = self::generateKodeSurat();
        $metaJson = isset($data['meta_data']) ? json_encode($data['meta_data']) : null;
        $gdriveWebLink = $data['gdrive_web_link'] ?? null;
        $kppUserId = !empty($data['kpp_user_id']) ? (int)$data['kpp_user_id'] : null;

        Database::query(
            "INSERT INTO {$this->table} 
            (kode_surat, type, user_id, kpp_user_id, perihal, tanggal_surat, lampiran, kepada, isi_surat, meta_data, status, gdrive_web_link) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $kode,
                $data['type'],
                $data['user_id'],
                $kppUserId,
                $data['perihal'],
                $data['tanggal_surat'] ?? date('Y-m-d'),
                $data['lampiran'] ?? '1 (satu) Berkas',
                $data['kepada'],
                $data['isi_surat'] ?? null,
                $metaJson,
                $data['status'] ?? 'submitted_to_tu',
                $gdriveWebLink
            ]
        );

        return (int) Database::lastInsertId();
    }

    public function getById(int $id): array|false
    {
        $surat = Database::fetch(
            "SELECT s.*, u.name as pembuat_nama, u.email as pembuat_email, u.jabatan as pembuat_jabatan,
                    kpp.name as kpp_nama, kpp.jabatan as kpp_jabatan,
                    tu.name as tu_nama, tu.jabatan as tu_jabatan
             FROM {$this->table} s
             LEFT JOIN users u ON s.user_id = u.id
             LEFT JOIN users kpp ON s.kpp_user_id = kpp.id
             LEFT JOIN users tu ON s.tu_user_id = tu.id
             WHERE s.id = ?",
            [$id]
        );

        if ($surat && !empty($surat['meta_data'])) {
            $surat['meta'] = json_decode($surat['meta_data'], true) ?? [];
        } else if ($surat) {
            $surat['meta'] = [];
        }

        return $surat;
    }

    public function getByKode(string $kode): array|false
    {
        return Database::fetch(
            "SELECT * FROM {$this->table} WHERE kode_surat = ?",
            [$kode]
        );
    }

    public function updateSurat(int $id, array $data): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($key === 'meta_data' && is_array($value)) {
                $fields[] = "`meta_data` = ?";
                $params[] = json_encode($value);
            } else if (in_array($key, [
                'type', 'nomor_surat', 'perihal', 'tanggal_surat', 'lampiran', 
                'kepada', 'isi_surat', 'status', 'kpp_user_id', 'kpp_signature', 
                'kpp_signed_at', 'tu_user_id', 'tu_signature', 
                'tu_signed_at', 'pdf_path', 'gdrive_file_id', 'gdrive_web_link'
            ])) {
                $fields[] = "`{$key}` = ?";
                $params[] = $value;
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $id;
        $sql = "UPDATE {$this->table} SET " . implode(", ", $fields) . " WHERE id = ?";
        return (bool) Database::query($sql, $params);
    }

    public function deleteSurat(int $id): bool
    {
        Database::query("DELETE FROM disposisi WHERE surat_id = ?", [$id]);
        Database::query("DELETE FROM surat_logs WHERE surat_id = ?", [$id]);
        return (bool) Database::query("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }

    public function getSuratListForRole(string $role, int $userId = 0, string $statusFilter = ''): array
    {
        $sql = "SELECT s.*, u.name as pembuat_nama, d.sifat_surat, d.catatan_kepsta, d.kepsta_signed_at
                FROM {$this->table} s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN disposisi d ON s.id = d.surat_id
                WHERE 1=1";

        $params = [];

        // Role-based visibility logic
        if ($role === 'admin') {
            // Admin sees only letters created by themselves
            $sql .= " AND s.user_id = ?";
            $params[] = $userId;
        } else if (in_array($role, ['kpp_bidang', 'kpp'])) {
            // KPP sees only letters assigned specifically to this KPP user
            $sql .= " AND s.kpp_user_id = ?";
            $params[] = $userId;
            if (empty($statusFilter)) {
                // Pending action for KPP Bidang (TTD & Paraf)
                $sql .= " AND s.status = 'submitted_to_kpp'";
            }
        } else if (in_array($role, ['tata_usaha', 'tu', 'ktu'])) {
            // TU / KTU sees incoming letters from KPP (excluding letters still in KPP process)
            $sql .= " AND s.status NOT IN ('submitted_to_kpp', 'draft')";
            if (empty($statusFilter)) {
                // Pending action for KTU / Tata Usaha (TTD Nota Dinas)
                $sql .= " AND s.status IN ('kpp_signed', 'submitted_to_tu')";
            } else if ($statusFilter !== 'all') {
                $sql .= " AND s.status IN ('kpp_signed', 'submitted_to_tu', 'tu_signed', 'sekre_approved', 'completed')";
            }
        } else if ($role === 'sekretaris' || $role === 'sekre') {
            // Sekre sees incoming letters from TU (excluding letters still in KPP or TU process)
            $sql .= " AND s.status NOT IN ('submitted_to_kpp', 'kpp_signed', 'submitted_to_tu', 'draft')";
            if (empty($statusFilter)) {
                // Pending action for Sekretaris (Disposisi)
                $sql .= " AND s.status = 'tu_signed'";
            } else if ($statusFilter !== 'all') {
                $sql .= " AND s.status IN ('tu_signed', 'sekre_approved', 'completed')";
            }
        } else if ($role === 'kepala_stasiun' || $role === 'kepsta') {
            // Kepsta sees letters approved by Sekre (excluding letters still in KPP, TU, or Sekre review)
            $sql .= " AND s.status NOT IN ('submitted_to_kpp', 'kpp_signed', 'submitted_to_tu', 'tu_signed', 'revisi', 'draft')";
            if (empty($statusFilter)) {
                // Pending action for Kepsta (TTD Disposisi)
                $sql .= " AND s.status = 'sekre_approved'";
            } else if ($statusFilter !== 'all') {
                $sql .= " AND s.status IN ('sekre_approved', 'completed')";
            }
        }

        if (!empty($statusFilter) && $statusFilter !== 'all') {
            $sql .= " AND s.status = ?";
            $params[] = $statusFilter;
        }

        $sql .= " ORDER BY s.id DESC";

        return Database::fetchAll($sql, $params);
    }

    public function getStatsForRole(string $role, int $userId = 0): array
    {
        $whereUser = "";
        $params = [];
        if ($role === 'admin' && $userId > 0) {
            $whereUser = " AND user_id = ?";
            $params[] = $userId;
        } else if (in_array($role, ['kpp_bidang', 'kpp']) && $userId > 0) {
            $whereUser = " AND kpp_user_id = ?";
            $params[] = $userId;
        } else if (in_array($role, ['tata_usaha', 'tu', 'ktu'])) {
            $whereUser = " AND status NOT IN ('submitted_to_kpp', 'draft')";
        } else if (in_array($role, ['sekretaris', 'sekre'])) {
            $whereUser = " AND status NOT IN ('submitted_to_kpp', 'kpp_signed', 'submitted_to_tu', 'draft')";
        } else if (in_array($role, ['kepala_stasiun', 'kepsta'])) {
            $whereUser = " AND status NOT IN ('submitted_to_kpp', 'kpp_signed', 'submitted_to_tu', 'tu_signed', 'revisi', 'draft')";
        }

        $all = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1{$whereUser}", $params)['total'] ?? 0;
        $pending_kpp = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'submitted_to_kpp'{$whereUser}", $params)['total'] ?? 0;
        $pending_tu = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE status IN ('kpp_signed', 'submitted_to_tu'){$whereUser}", $params)['total'] ?? 0;
        $pending_sekre = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'tu_signed'{$whereUser}", $params)['total'] ?? 0;
        $pending_kepsta = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'sekre_approved'{$whereUser}", $params)['total'] ?? 0;
        $revisi = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'revisi'{$whereUser}", $params)['total'] ?? 0;
        $completed = Database::fetch("SELECT COUNT(*) as total FROM {$this->table} WHERE status = 'completed'{$whereUser}", $params)['total'] ?? 0;

        return [
            'total' => $all,
            'pending_kpp' => $pending_kpp,
            'pending_tu' => $pending_tu,
            'pending_sekre' => $pending_sekre,
            'pending_kepsta' => $pending_kepsta,
            'revisi' => $revisi,
            'completed' => $completed
        ];
    }

    public static function getTypeLabel(?string $type, array $meta = []): string
    {
        if (!empty($meta['from_upload_lampiran']) || (($type ?? '') === 'upload_lampiran')) {
            return 'Upload Lampiran';
        }
        $kat = $meta['kategori_surat'] ?? '';
        if (($type ?? '') === 'nota_dinas' || $kat === 'Surat Nota Dinas') {
            return 'Surat Nota Dinas';
        }
        if (($type ?? '') === 'izin_produksi' || str_contains(strtolower($kat), 'produksi')) {
            return 'Surat Izin Produksi';
        }
        if (($type ?? '') === 'izin_prinsip' || str_contains(strtolower($kat), 'prinsip')) {
            return 'Surat Izin Prinsip';
        }
        return match ($type) {
            'izin_prinsip' => 'Surat Izin Prinsip',
            'izin_produksi' => 'Surat Izin Produksi',
            'nota_dinas' => 'Surat Nota Dinas',
            'upload_lampiran' => 'Upload Lampiran',
            default => !empty($kat) ? $kat : ucfirst((string)$type)
        };
    }

    public static function getStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => 'Draft Admin',
            'submitted_to_kpp' => 'Proses KPP Bidang',
            'kpp_signed' => 'Di-TTD & Paraf KPP (Ke TU)',
            'submitted_to_tu' => 'Proses Tata Usaha',
            'tu_signed' => 'Di-TTD Nota Dinas TU',
            'sekre_approved' => 'Disetujui Sekre (Ada Disposisi)',
            'revisi' => 'Perlu Revisi',
            'completed' => 'Selesai & Disetujui Kepsta',
            'rejected' => 'Dihapus / Ditolak',
            default => ucfirst($status)
        };
    }

    public static function getStatusBadgeHtml(string $status): string
    {
        return match ($status) {
            'draft' => '<span class="status-chip status-draft"><i class="bi bi-file-earmark"></i> Draft</span>',
            'submitted_to_kpp' => '<span class="status-chip" style="background: #EEF2FF; color: #4338CA; border: 1px solid #C7D2FE;"><i class="bi bi-clock-history"></i> Proses KPP Bidang</span>',
            'kpp_signed' => '<span class="status-chip" style="background: #F0FDF4; color: #15803D; border: 1px solid #BBF7D0;"><i class="bi bi-check2-circle"></i> TTD & Paraf KPP (Ke TU)</span>',
            'submitted_to_tu' => '<span class="status-chip status-tu"><i class="bi bi-clock-history"></i> Proses Tata Usaha</span>',
            'tu_signed' => '<span class="status-chip status-sekre"><i class="bi bi-pen"></i> Review Sekretaris</span>',
            'sekre_approved' => '<span class="status-chip status-kepsta"><i class="bi bi-person-badge"></i> Menunggu Kepsta</span>',
            'revisi' => '<span class="status-chip status-revisi"><i class="bi bi-exclamation-triangle"></i> Revisi Admin</span>',
            'completed' => '<span class="status-chip status-completed"><i class="bi bi-check-circle-fill"></i> Completed & GDrive</span>',
            'rejected' => '<span class="status-chip" style="background: #FEF2F2; color: #DC2626; border: 1px solid #FCA5A5;"><i class="bi bi-x-circle-fill"></i> Dihapus</span>',
            default => '<span class="status-chip">' . htmlspecialchars($status) . '</span>'
        };
    }

    public function getCompletedHistoryYears(): array
    {
        $sql = "SELECT YEAR(COALESCE(d.kepsta_signed_at, s.tanggal_surat)) as `year`, COUNT(s.id) as total
                FROM {$this->table} s
                LEFT JOIN disposisi d ON s.id = d.surat_id
                WHERE s.status = 'completed'
                GROUP BY `year`
                HAVING `year` IS NOT NULL AND total > 0
                ORDER BY `year` DESC";
        return Database::fetchAll($sql);
    }

    public function getCompletedHistoryMonths(int $year): array
    {
        $sql = "SELECT MONTH(COALESCE(d.kepsta_signed_at, s.tanggal_surat)) as `month_num`, COUNT(s.id) as total
                FROM {$this->table} s
                LEFT JOIN disposisi d ON s.id = d.surat_id
                WHERE s.status = 'completed' AND YEAR(COALESCE(d.kepsta_signed_at, s.tanggal_surat)) = ?
                GROUP BY `month_num`
                HAVING `month_num` IS NOT NULL AND total > 0
                ORDER BY `month_num` ASC";
        return Database::fetchAll($sql, [$year]);
    }

    public function getCompletedHistoryByMonth(int $year, int $month): array
    {
        $sql = "SELECT s.*, u.name as pembuat_nama, d.sifat_surat, d.catatan_kepsta, d.kepsta_signed_at
                FROM {$this->table} s
                LEFT JOIN users u ON s.user_id = u.id
                LEFT JOIN disposisi d ON s.id = d.surat_id
                WHERE s.status = 'completed' 
                  AND YEAR(COALESCE(d.kepsta_signed_at, s.tanggal_surat)) = ?
                  AND MONTH(COALESCE(d.kepsta_signed_at, s.tanggal_surat)) = ?
                ORDER BY s.tanggal_surat DESC, s.id DESC";
        $results = Database::fetchAll($sql, [$year, $month]);
        foreach ($results as &$surat) {
            if (!empty($surat['meta_data'])) {
                $surat['meta'] = json_decode($surat['meta_data'], true) ?? [];
            } else {
                $surat['meta'] = [];
            }
        }
        return $results;
    }
}
