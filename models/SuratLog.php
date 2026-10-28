<?php

/**
 * SuratLog Model - Surat Digital TVRI Jawa Timur
 * Handles Audit Trail & Visual Tracking Logs
 */

class SuratLog extends Model
{
    protected string $table = 'surat_logs';

    public static function log(int $suratId, int $userId, string $action, string $statusTo, ?string $note = null): bool
    {
        return (bool) Database::query(
            "INSERT INTO surat_logs (surat_id, user_id, action, status_to, note) VALUES (?, ?, ?, ?, ?)",
            [$suratId, $userId, $action, $statusTo, $note]
        );
    }

    public static function getLogs(int $suratId): array
    {
        return Database::fetchAll(
            "SELECT l.*, u.name as user_nama, u.role as user_role, u.jabatan as user_jabatan
             FROM surat_logs l
             LEFT JOIN users u ON l.user_id = u.id
             WHERE l.surat_id = ?
             ORDER BY l.id ASC",
            [$suratId]
        );
    }
}
