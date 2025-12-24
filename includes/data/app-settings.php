<?php
/**
 * app_settings(DB) 기반 설정 저장/조회 유틸
 * - namespace 단위로 JSON 전체 저장
 */

require_once __DIR__ . '/db-config.php';

/**
 * namespace 설정 로드 (DB 우선)
 */
function getAppSettings(string $namespace, array $defaults = []): array {
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare('SELECT json_value FROM app_settings WHERE namespace = :ns LIMIT 1');
            $stmt->execute([':ns' => $namespace]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['json_value'])) {
                $val = $row['json_value'];
                if (is_string($val)) {
                    $decoded = json_decode($val, true);
                    if (is_array($decoded)) {
                        return array_replace_recursive($defaults, $decoded);
                    }
                } elseif (is_array($val)) {
                    return array_replace_recursive($defaults, $val);
                }
            }
        } catch (PDOException $e) {
            error_log('getAppSettings DB error: ' . $e->getMessage());
        }
    }

    return $defaults;
}

/**
 * namespace 설정 저장 (DB)
 */
function saveAppSettings(string $namespace, array $settings, ?string $updatedBy = null): bool {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $json = json_encode($settings, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        $stmt = $pdo->prepare(
            'INSERT INTO app_settings (namespace, json_value, updated_by) VALUES (:ns, :val, :by) '
            . 'ON DUPLICATE KEY UPDATE json_value = VALUES(json_value), updated_by = VALUES(updated_by), updated_at = NOW()'
        );
        return $stmt->execute([
            ':ns' => $namespace,
            ':val' => $json,
            ':by' => $updatedBy
        ]);
    } catch (PDOException $e) {
        error_log('saveAppSettings DB error: ' . $e->getMessage());
        return false;
    }
}










