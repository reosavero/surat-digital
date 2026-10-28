<?php

/**
 * Setting Model - Surat Digital TVRI Jawa Timur
 */

class Setting extends Model
{
    protected string $table = 'settings';

    public static function get(string $key, ?string $default = null): ?string
    {
        $res = Database::fetch("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $res ? $res['setting_value'] : $default;
    }

    public static function set(string $key, ?string $value): bool
    {
        $stmt = Database::query(
            "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
            [$key, $value]
        );
        return $stmt instanceof PDOStatement;
    }

    public static function getAll(): array
    {
        $rows = Database::fetchAll("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($rows as $r) {
            $settings[$r['setting_key']] = $r['setting_value'];
        }
        return $settings;
    }
}
