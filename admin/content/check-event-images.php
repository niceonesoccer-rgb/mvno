<?php
/**
 * 이벤트 이미지 경로 확인 스크립트
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

echo "<h2>이벤트 이미지 경로 확인</h2>";
echo "<pre>";

try {
    $stmt = $pdo->query("SELECT id, title, main_image, image_url FROM events ORDER BY created_at DESC LIMIT 20");
    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "총 " . count($events) . "개의 이벤트\n";
    echo str_repeat("=", 100) . "\n\n";
    
    foreach ($events as $event) {
        echo "ID: {$event['id']}\n";
        echo "제목: {$event['title']}\n";
        echo "main_image: " . ($event['main_image'] ?? 'NULL') . "\n";
        echo "image_url: " . ($event['image_url'] ?? 'NULL') . "\n";
        
        // 경로 정규화 테스트 (event-manage.php와 동일한 로직)
        $imagePath = '';
        $rawImagePath = '';
        if (!empty($event['main_image'])) {
            $rawImagePath = $event['main_image'];
        } elseif (!empty($event['image_url'])) {
            $rawImagePath = $event['image_url'];
        }
        
        if ($rawImagePath) {
            $imagePath = trim($rawImagePath);
            
            // 이미 /MVNO/로 시작하면 그대로 사용
            if (strpos($imagePath, '/MVNO/') === 0) {
                // 그대로 사용
            }
            // /uploads/ 또는 /images/로 시작하면 /MVNO/ 추가
            elseif (strpos($imagePath, '/uploads/') === 0 || strpos($imagePath, '/images/') === 0) {
                $imagePath = '/MVNO' . $imagePath;
            }
            // 상대 경로(슬래시로 시작하지 않음)이면 /MVNO/uploads/events/ 추가 (파일명만 있는 경우)
            elseif (strpos($imagePath, '/') !== 0 && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', basename($imagePath))) {
                $imagePath = '/MVNO/uploads/events/' . basename($imagePath);
            }
            // 상대 경로인데 파일명이 아닌 경우
            elseif (strpos($imagePath, '/') !== 0) {
                $imagePath = '/MVNO/' . $imagePath;
            }
        }
        
        echo "정규화된 경로: " . ($imagePath ?: '없음') . "\n";
        
        // 실제 파일 존재 여부 확인
        if ($imagePath) {
            $baseDir = dirname(__DIR__) . '/../';
            // /MVNO/ 경로 제거하여 실제 파일 시스템 경로로 변환
            $fileSystemPath = str_replace('/MVNO/', '/', $imagePath);
            $filePath = $baseDir . ltrim($fileSystemPath, '/');
            $exists = file_exists($filePath);
            echo "파일 시스템 경로: {$filePath}\n";
            echo "파일 존재: " . ($exists ? 'YES' : 'NO') . "\n";
            
            // uploads/events 폴더에서 파일명으로 직접 확인
            if (!$exists) {
                $filename = basename($imagePath);
                $uploadsPath = $baseDir . 'uploads/events/' . $filename;
                $uploadsExists = file_exists($uploadsPath);
                echo "uploads/events/ 직접 확인: {$uploadsPath}\n";
                echo "파일 존재: " . ($uploadsExists ? 'YES' : 'NO') . "\n";
            }
        }
        
        echo str_repeat("-", 100) . "\n\n";
    }
    
} catch (Exception $e) {
    echo "오류 발생: " . $e->getMessage();
}

echo "</pre>";
echo "<p><a href='event-manage.php'>이벤트 관리로 돌아가기</a></p>";
?>

