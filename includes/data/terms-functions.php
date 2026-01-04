<?php
/**
 * 약관/개인정보처리방침 버전 관리 함수
 * - 시행일자별 버전 관리
 * - 5년 경과 시 자동 삭제 지원
 */

require_once __DIR__ . '/db-config.php';

/**
 * 현재 활성 버전 가져오기
 * @param string $type 'terms_of_service' 또는 'privacy_policy'
 * @return array|null
 */
function getActiveTermsVersion(string $type): ?array {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM terms_versions 
            WHERE type = :type AND is_active = 1 
            ORDER BY effective_date DESC 
            LIMIT 1
        ");
        $stmt->execute([':type' => $type]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log('getActiveTermsVersion DB error: ' . $e->getMessage());
        return null;
    }
}

/**
 * 특정 버전 가져오기
 * @param string $type
 * @param string $version
 * @return array|null
 */
function getTermsVersionByVersion(string $type, string $version): ?array {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM terms_versions 
            WHERE type = :type AND version = :version 
            LIMIT 1
        ");
        $stmt->execute([':type' => $type, ':version' => $version]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log('getTermsVersionByVersion DB error: ' . $e->getMessage());
        return null;
    }
}

/**
 * 특정 시행일자 버전 가져오기
 * @param string $type
 * @param string $date Y-m-d 형식
 * @return array|null
 */
function getTermsVersionByDate(string $type, string $date): ?array {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("
            SELECT * FROM terms_versions 
            WHERE type = :type AND effective_date = :date 
            LIMIT 1
        ");
        $stmt->execute([':type' => $type, ':date' => $date]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log('getTermsVersionByDate DB error: ' . $e->getMessage());
        return null;
    }
}

/**
 * 버전 목록 가져오기
 * @param string $type
 * @param bool $includeInactive 비활성 버전 포함 여부
 * @return array
 */
function getTermsVersionList(string $type, bool $includeInactive = true): array {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }

    try {
        $sql = "
            SELECT * FROM terms_versions 
            WHERE type = :type 
        ";
        if (!$includeInactive) {
            $sql .= " AND is_active = 1 ";
        }
        $sql .= " ORDER BY id DESC, created_at DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':type' => $type]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log('getTermsVersionList DB error: ' . $e->getMessage());
        return [];
    }
}

/**
 * 버전 저장 (새 버전 추가)
 * @param string $type
 * @param string $version
 * @param string $effectiveDate Y-m-d 형식
 * @param string $title
 * @param string $content HTML 내용
 * @param string|null $announcementDate Y-m-d 형식
 * @param bool $setAsActive 활성 버전으로 설정할지 여부
 * @param string|null $createdBy 생성자
 * @return bool
 */
function saveTermsVersion(
    string $type,
    string $version,
    string $effectiveDate,
    string $title,
    string $content,
    ?string $announcementDate = null,
    bool $setAsActive = false,
    ?string $createdBy = null
): bool {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $pdo->beginTransaction();

        // 중복 체크: 같은 type과 version이 이미 존재하는지 확인
        $stmt = $pdo->prepare("SELECT id FROM terms_versions WHERE type = :type AND version = :version LIMIT 1");
        $stmt->execute([':type' => $type, ':version' => $version]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $pdo->rollBack();
            error_log("saveTermsVersion: Version already exists - type: {$type}, version: {$version}");
            return false; // 중복 버전이면 false 반환
        }

        // 활성 버전으로 설정하는 경우, 기존 활성 버전 비활성화
        if ($setAsActive) {
            $stmt = $pdo->prepare("
                UPDATE terms_versions 
                SET is_active = 0 
                WHERE type = :type AND is_active = 1
            ");
            $stmt->execute([':type' => $type]);
        }

        // 새 버전 저장
        $stmt = $pdo->prepare("
            INSERT INTO terms_versions 
            (type, version, effective_date, announcement_date, title, content, is_active, created_by)
            VALUES 
            (:type, :version, :effective_date, :announcement_date, :title, :content, :is_active, :created_by)
        ");

        $result = $stmt->execute([
            ':type' => $type,
            ':version' => $version,
            ':effective_date' => $effectiveDate,
            ':announcement_date' => $announcementDate,
            ':title' => $title,
            ':content' => $content,
            ':is_active' => $setAsActive ? 1 : 0,
            ':created_by' => $createdBy
        ]);

        $pdo->commit();
        return $result;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('saveTermsVersion DB error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 버전 업데이트
 * @param int $id
 * @param array $data 업데이트할 데이터
 * @return bool
 */
function updateTermsVersion(int $id, array $data): bool {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        $allowedFields = ['version', 'effective_date', 'announcement_date', 'title', 'content', 'is_active'];
        $updates = [];
        $params = [':id' => $id];

        foreach ($data as $key => $value) {
            if (in_array($key, $allowedFields)) {
                $updates[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
        }

        if (empty($updates)) {
            return false;
        }

        // 활성 버전으로 변경하는 경우, 기존 활성 버전 비활성화
        if (isset($data['is_active']) && $data['is_active'] == 1) {
            // 현재 버전의 타입 가져오기
            $stmt = $pdo->prepare("SELECT type FROM terms_versions WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $current = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($current) {
                $stmt = $pdo->prepare("
                    UPDATE terms_versions 
                    SET is_active = 0 
                    WHERE type = :type AND is_active = 1 AND id != :id
                ");
                $stmt->execute([':type' => $current['type'], ':id' => $id]);
            }
        }

        $sql = "UPDATE terms_versions SET " . implode(', ', $updates) . " WHERE id = :id";
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log('updateTermsVersion DB error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 버전 삭제
 * @param int $id
 * @return bool
 */
function deleteTermsVersion(int $id): bool {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }

    try {
        // 활성 버전은 삭제하지 않음
        $stmt = $pdo->prepare("SELECT is_active FROM terms_versions WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['is_active'] == 1) {
            error_log('Cannot delete active terms version');
            return false;
        }

        $stmt = $pdo->prepare("DELETE FROM terms_versions WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    } catch (PDOException $e) {
        error_log('deleteTermsVersion DB error: ' . $e->getMessage());
        return false;
    }
}

/**
 * 5년 경과한 비활성 버전 자동 삭제
 * @return array ['deleted' => 삭제된 건수, 'errors' => 오류 건수]
 */
function deleteOldTermsVersions(): array {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['deleted' => 0, 'errors' => 1];
    }

    try {
        $pdo->beginTransaction();

        // 5년 경과한 비활성 버전 조회
        $stmt = $pdo->prepare("
            SELECT id, type, version, effective_date 
            FROM terms_versions 
            WHERE effective_date <= DATE_SUB(CURDATE(), INTERVAL 5 YEAR)
            AND is_active = 0
        ");
        $stmt->execute();
        $versionsToDelete = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $deletedCount = 0;
        $errorCount = 0;

        foreach ($versionsToDelete as $version) {
            try {
                $deleteStmt = $pdo->prepare("DELETE FROM terms_versions WHERE id = :id");
                if ($deleteStmt->execute([':id' => $version['id']])) {
                    $deletedCount++;
                    error_log(sprintf(
                        'Deleted old terms version: type=%s, version=%s, effective_date=%s',
                        $version['type'],
                        $version['version'],
                        $version['effective_date']
                    ));
                } else {
                    $errorCount++;
                }
            } catch (PDOException $e) {
                $errorCount++;
                error_log('Error deleting terms version ID ' . $version['id'] . ': ' . $e->getMessage());
            }
        }

        $pdo->commit();
        return ['deleted' => $deletedCount, 'errors' => $errorCount];
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log('deleteOldTermsVersions DB error: ' . $e->getMessage());
        return ['deleted' => 0, 'errors' => 1];
    }
}

/**
 * 버전 ID로 가져오기
 * @param int $id
 * @return array|null
 */
function getTermsVersionById(int $id): ?array {
    $pdo = getDBConnection();
    if (!$pdo) {
        return null;
    }

    try {
        $stmt = $pdo->prepare("SELECT * FROM terms_versions WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    } catch (PDOException $e) {
        error_log('getTermsVersionById DB error: ' . $e->getMessage());
        return null;
    }
}
