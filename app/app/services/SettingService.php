<?php
declare(strict_types=1);

class SettingService
{
    public static function get(PDO $pdo, string $key, string $default = ''): string
    {
        $stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $row = $stmt->fetch();

        return $row ? (string) $row['setting_value'] : $default;
    }

    public static function set(PDO $pdo, string $key, string $value): void
    {
        $stmt = $pdo->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES (?, ?)
            ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                updated_at = NOW()
        ");

        $stmt->execute([$key, $value]);
    }
}