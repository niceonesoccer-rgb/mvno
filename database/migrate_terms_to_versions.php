<?php
/**
 * 기존 약관 데이터를 terms_versions 테이블로 마이그레이션
 * 
 * 사용법:
 * php database/migrate_terms_to_versions.php
 */

require_once __DIR__ . '/../includes/data/site-settings.php';
require_once __DIR__ . '/../includes/data/terms-functions.php';
require_once __DIR__ . '/../includes/data/auth-functions.php';

date_default_timezone_set('Asia/Seoul');

echo "=== 약관 데이터 마이그레이션 시작 ===\n\n";

try {
    // 사이트 설정 가져오기
    $settings = getSiteSettings();
    $footer = $settings['footer'] ?? [];
    $terms = $footer['terms'] ?? [];
    
    $migratedCount = 0;
    $errorCount = 0;
    
    // 현재 사용자 정보 (관리자)
    $currentUser = getCurrentUser();
    $createdBy = $currentUser['user_id'] ?? 'system';
    
    // 이용약관 마이그레이션
    if (!empty($terms['terms_of_service']['content'])) {
        echo "이용약관 마이그레이션 중...\n";
        
        $title = $terms['terms_of_service']['text'] ?? '이용약관';
        $content = $terms['terms_of_service']['content'];
        $effectiveDate = date('Y-m-d'); // 현재 날짜를 시행일자로 설정
        $version = 'v1.0'; // 초기 버전
        
        if (saveTermsVersion(
            'terms_of_service',
            $version,
            $effectiveDate,
            $title,
            $content,
            null, // 공고일자
            true, // 활성 버전으로 설정
            $createdBy
        )) {
            echo "✓ 이용약관 마이그레이션 완료 (버전: {$version}, 시행일자: {$effectiveDate})\n";
            $migratedCount++;
        } else {
            echo "✗ 이용약관 마이그레이션 실패\n";
            $errorCount++;
        }
    } else {
        echo "- 이용약관 내용이 없어 건너뜀\n";
    }
    
    // 개인정보처리방침 마이그레이션
    if (!empty($terms['privacy_policy']['content'])) {
        echo "\n개인정보처리방침 마이그레이션 중...\n";
        
        $title = $terms['privacy_policy']['text'] ?? '개인정보처리방침';
        $content = $terms['privacy_policy']['content'];
        $effectiveDate = date('Y-m-d'); // 현재 날짜를 시행일자로 설정
        
        // 내용에서 시행일자 추출 시도
        if (preg_match('/시행일자:\s*(\d{4}-\d{2}-\d{2})/i', $content, $matches)) {
            $effectiveDate = $matches[1];
            echo "  내용에서 시행일자 발견: {$effectiveDate}\n";
        }
        
        // 버전 추출 시도
        $version = 'v1.0';
        if (preg_match('/버전:\s*(v[\d.]+)/i', $content, $matches)) {
            $version = $matches[1];
            echo "  내용에서 버전 발견: {$version}\n";
        }
        
        if (saveTermsVersion(
            'privacy_policy',
            $version,
            $effectiveDate,
            $title,
            $content,
            null, // 공고일자
            true, // 활성 버전으로 설정
            $createdBy
        )) {
            echo "✓ 개인정보처리방침 마이그레이션 완료 (버전: {$version}, 시행일자: {$effectiveDate})\n";
            $migratedCount++;
        } else {
            echo "✗ 개인정보처리방침 마이그레이션 실패\n";
            $errorCount++;
        }
    } else {
        echo "- 개인정보처리방침 내용이 없어 건너뜀\n";
    }
    
    echo "\n=== 마이그레이션 완료 ===\n";
    echo "성공: {$migratedCount}건\n";
    echo "실패: {$errorCount}건\n";
    
    if ($migratedCount > 0) {
        echo "\n✅ 마이그레이션이 완료되었습니다.\n";
        echo "관리자 페이지에서 버전을 확인하고 필요시 수정하세요.\n";
    }
    
} catch (Exception $e) {
    echo "\n오류 발생: " . $e->getMessage() . "\n";
    error_log('migrate_terms_to_versions error: ' . $e->getMessage());
    exit(1);
}
