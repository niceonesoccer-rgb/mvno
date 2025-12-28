<?php
/**
 * 이벤트 이미지 마이그레이션 스크립트
 * uploads/events/ -> images/upload/event/YYYY/MM/
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    die('권한이 없습니다.');
}

$pdo = getDBConnection();
if (!$pdo) {
    die('데이터베이스 연결 실패');
}

$migrated = 0;
$errors = [];
$skipped = 0;

try {
    // events 테이블에서 모든 이벤트 이미지 경로 가져오기
    $stmt = $pdo->query("SELECT id, main_image, image_url FROM events");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>이벤트 이미지 마이그레이션</h2>";
    echo "<pre>";
    
    foreach ($events as $event) {
        // main_image 처리
        if (!empty($event['main_image'])) {
            $result = migrateImage($event['main_image'], $pdo, $event['id'], 'main_image');
            if ($result['success']) {
                $migrated++;
                echo "✓ [{$event['id']}] main_image: {$event['main_image']} -> {$result['new_path']}\n";
            } elseif ($result['skipped']) {
                $skipped++;
                echo "- [{$event['id']}] main_image: 이미 올바른 경로 ({$event['main_image']})\n";
            } else {
                $errors[] = "[{$event['id']}] main_image: {$result['error']}";
                echo "✗ [{$event['id']}] main_image: {$result['error']}\n";
            }
        }
        
        // image_url 처리
        if (!empty($event['image_url'])) {
            $result = migrateImage($event['image_url'], $pdo, $event['id'], 'image_url');
            if ($result['success']) {
                $migrated++;
                echo "✓ [{$event['id']}] image_url: {$event['image_url']} -> {$result['new_path']}\n";
            } elseif ($result['skipped']) {
                $skipped++;
                echo "- [{$event['id']}] image_url: 이미 올바른 경로 ({$event['image_url']})\n";
            } else {
                $errors[] = "[{$event['id']}] image_url: {$result['error']}";
                echo "✗ [{$event['id']}] image_url: {$result['error']}\n";
            }
        }
    }
    
    // event_detail_images 테이블 처리
    $detailStmt = $pdo->query("SELECT id, event_id, image_path FROM event_detail_images");
    $detailImages = $detailStmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($detailImages as $detailImage) {
        if (!empty($detailImage['image_path'])) {
            $result = migrateDetailImage($detailImage['image_path'], $pdo, $detailImage['id']);
            if ($result['success']) {
                $migrated++;
                echo "✓ [상세 이미지 {$detailImage['id']}] image_path: {$detailImage['image_path']} -> {$result['new_path']}\n";
            } elseif ($result['skipped']) {
                $skipped++;
                echo "- [상세 이미지 {$detailImage['id']}] image_path: 이미 올바른 경로 ({$detailImage['image_path']})\n";
            } else {
                $errors[] = "[상세 이미지 {$detailImage['id']}] image_path: {$result['error']}";
                echo "✗ [상세 이미지 {$detailImage['id']}] image_path: {$result['error']}\n";
            }
        }
    }
    
    echo "\n=== 마이그레이션 완료 ===\n";
    echo "이동된 이미지: {$migrated}개\n";
    echo "건너뛴 이미지: {$skipped}개\n";
    echo "오류: " . count($errors) . "개\n";
    
    if (!empty($errors)) {
        echo "\n오류 목록:\n";
        foreach ($errors as $error) {
            echo "  - {$error}\n";
        }
    }
    
    echo "</pre>";
    echo "<p><a href='event-manage.php'>이벤트 관리로 돌아가기</a></p>";
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}

/**
 * 이미지 파일 마이그레이션
 */
function migrateImage($oldPath, $pdo, $eventId, $column) {
    // 이미 올바른 경로인지 확인 (images/upload/event/로 시작)
    if (strpos($oldPath, '/images/upload/event/') === 0 || strpos($oldPath, 'images/upload/event/') === 0) {
        return ['success' => false, 'skipped' => true, 'new_path' => $oldPath];
    }
    
    // 기존 경로에서 파일명 추출
    // /MVNO/uploads/events/filename 또는 /uploads/events/filename 형식
    $oldPathCleaned = str_replace('/MVNO/', '/', $oldPath);
    $oldPathCleaned = ltrim($oldPathCleaned, '/');
    
    // 서버 상의 실제 파일 경로
    $baseDir = dirname(__DIR__) . '/../';
    $oldFilePath = $baseDir . $oldPathCleaned;
    
    if (!file_exists($oldFilePath)) {
        return ['success' => false, 'skipped' => false, 'error' => "파일을 찾을 수 없음: {$oldFilePath}"];
    }
    
    // 새 경로 결정 (파일의 수정 시간 기준으로 연도/월 추출)
    $fileMtime = filemtime($oldFilePath);
    $year = date('Y', $fileMtime);
    $month = date('m', $fileMtime);
    
    // 새 디렉토리 생성
    $newDir = $baseDir . "images/upload/event/{$year}/{$month}/";
    if (!is_dir($newDir)) {
        mkdir($newDir, 0755, true);
    }
    
    // 파일명 추출
    $filename = basename($oldFilePath);
    
    // 새 파일 경로
    $newFilePath = $newDir . $filename;
    $newWebPath = "/images/upload/event/{$year}/{$month}/{$filename}";
    
    // 이미 존재하는 경우 건너뛰기
    if (file_exists($newFilePath)) {
        // 데이터베이스 경로만 업데이트
        updateImagePath($pdo, $eventId, $column, $newWebPath);
        return ['success' => true, 'skipped' => false, 'new_path' => $newWebPath, 'note' => '이미 존재하여 경로만 업데이트'];
    }
    
    // 파일 이동
    if (!copy($oldFilePath, $newFilePath)) {
        return ['success' => false, 'skipped' => false, 'error' => "파일 복사 실패"];
    }
    
    // 데이터베이스 경로 업데이트
    if (updateImagePath($pdo, $eventId, $column, $newWebPath)) {
        // 원본 파일 삭제 (선택사항 - 안전을 위해 주석 처리)
        // @unlink($oldFilePath);
        return ['success' => true, 'skipped' => false, 'new_path' => $newWebPath];
    } else {
        // DB 업데이트 실패 시 새 파일 삭제
        @unlink($newFilePath);
        return ['success' => false, 'skipped' => false, 'error' => "데이터베이스 업데이트 실패"];
    }
}

/**
 * 상세 이미지 마이그레이션
 */
function migrateDetailImage($oldPath, $pdo, $detailImageId) {
    // 이미 올바른 경로인지 확인
    if (strpos($oldPath, '/images/upload/event/') === 0 || strpos($oldPath, 'images/upload/event/') === 0) {
        return ['success' => false, 'skipped' => true, 'new_path' => $oldPath];
    }
    
    $oldPathCleaned = str_replace('/MVNO/', '/', $oldPath);
    $oldPathCleaned = ltrim($oldPathCleaned, '/');
    
    $baseDir = dirname(__DIR__) . '/../';
    $oldFilePath = $baseDir . $oldPathCleaned;
    
    if (!file_exists($oldFilePath)) {
        return ['success' => false, 'skipped' => false, 'error' => "파일을 찾을 수 없음: {$oldFilePath}"];
    }
    
    $fileMtime = filemtime($oldFilePath);
    $year = date('Y', $fileMtime);
    $month = date('m', $fileMtime);
    
    $newDir = $baseDir . "images/upload/event/{$year}/{$month}/";
    if (!is_dir($newDir)) {
        mkdir($newDir, 0755, true);
    }
    
    $filename = basename($oldFilePath);
    $newFilePath = $newDir . $filename;
    $newWebPath = "/images/upload/event/{$year}/{$month}/{$filename}";
    
    if (file_exists($newFilePath)) {
        updateDetailImagePath($pdo, $detailImageId, $newWebPath);
        return ['success' => true, 'skipped' => false, 'new_path' => $newWebPath, 'note' => '이미 존재하여 경로만 업데이트'];
    }
    
    if (!copy($oldFilePath, $newFilePath)) {
        return ['success' => false, 'skipped' => false, 'error' => "파일 복사 실패"];
    }
    
    if (updateDetailImagePath($pdo, $detailImageId, $newWebPath)) {
        return ['success' => true, 'skipped' => false, 'new_path' => $newWebPath];
    } else {
        @unlink($newFilePath);
        return ['success' => false, 'skipped' => false, 'error' => "데이터베이스 업데이트 실패"];
    }
}

/**
 * events 테이블의 이미지 경로 업데이트
 */
function updateImagePath($pdo, $eventId, $column, $newPath) {
    try {
        $stmt = $pdo->prepare("UPDATE events SET {$column} = :new_path WHERE id = :id");
        $stmt->execute([':new_path' => $newPath, ':id' => $eventId]);
        return true;
    } catch (PDOException $e) {
        error_log("Update image path error: " . $e->getMessage());
        return false;
    }
}

/**
 * event_detail_images 테이블의 이미지 경로 업데이트
 */
function updateDetailImagePath($pdo, $detailImageId, $newPath) {
    try {
        $stmt = $pdo->prepare("UPDATE event_detail_images SET image_path = :new_path WHERE id = :id");
        $stmt->execute([':new_path' => $newPath, ':id' => $detailImageId]);
        return true;
    } catch (PDOException $e) {
        error_log("Update detail image path error: " . $e->getMessage());
        return false;
    }
}
?>

