<?php
/**
 * 데이터 삭제 관리 페이지
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/path-config.php';
require_once __DIR__ . '/../../includes/data/db-config.php';
require_once __DIR__ . '/../../includes/data/log-cleanup-functions.php';
require_once __DIR__ . '/../../includes/data/app-settings.php';
require_once __DIR__ . '/../../includes/data/data-delete-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// admin 계정만 접근 가능 (부관리자 제외)
$currentUser = getCurrentUser();
if (!$currentUser || getUserRole($currentUser['user_id']) !== 'admin') {
    header('Location: ' . getAssetPath('/admin/'));
    exit;
}
$error = '';
$success = '';
$deleteResult = null;

// 다운로드 처리 (GET 요청)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];
    
    if ($action === 'download_log') {
        // 개별 로그 파일 다운로드
        require_once __DIR__ . '/../../includes/data/log-download-functions.php';
        
        // 파일 경로 직접 전달 방식
        if (isset($_GET['file_path'])) {
            $filePath = base64_decode($_GET['file_path']);
            if (downloadLogFileByPath($filePath)) {
                exit;
            }
        }
        
        // 기존 방식 (log_type 사용)
        $logType = $_GET['log_type'] ?? '';
        $logFile = getLogFilePath($logType);
        
        if ($logFile && file_exists($logFile)) {
            if (downloadLogFileByPath($logFile)) {
                exit;
            }
        }
        
        $error = '로그 파일을 찾을 수 없습니다.';
    } elseif ($action === 'download_all_logs') {
        // 모든 로그 파일 ZIP 다운로드
        require_once __DIR__ . '/../../includes/data/log-download-functions.php';
        downloadAllLogsAsZip();
        exit;
    }
}

// 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $confirmText = $_POST['confirm_text'] ?? '';
    
    // 로그 정리는 확인 텍스트 불필요
    if ($action === 'cleanup_logs') {
        try {
            $days = isset($_POST['log_days']) ? (int)$_POST['log_days'] : 7;
            if ($days < 1 || $days > 365) {
                throw new Exception('보관 기간은 1일에서 365일 사이여야 합니다.');
            }
            
            // 보관 기간 설정 저장
            $logSettings = getAppSettings('log_cleanup_settings', ['retention_days' => 7]);
            $logSettings['retention_days'] = $days;
            $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
            saveAppSettings('log_cleanup_settings', $logSettings, $updatedBy);
            
            $results = cleanupAllLogs($days);
            
            // MySQL 바이너리 로그 설정
            setMysqlBinlogExpiration($days);
            
            $totalDeleted = 0;
            $totalSizeFreed = 0;
            foreach ($results as $result) {
                $totalDeleted += $result['deleted'] ?? 0;
                $totalSizeFreed += $result['size_freed'] ?? 0;
            }
            
            $totalSizeMB = round($totalSizeFreed / 1024 / 1024, 2);
            $deleteResult = [
                'type' => '로그 파일',
                'count' => $totalDeleted,
                'details' => $results
            ];
            $success = "로그 파일이 정리되었습니다. ({$totalDeleted}건 삭제, {$totalSizeMB}MB 절약, 보관 기간: {$days}일)";
            
            // 리다이렉트하여 성공 메시지 표시 및 선택한 보관 기간 유지
            header('Location: ' . getAssetPath('/admin/settings/data-delete.php') . '?success=' . urlencode($success) . '&days=' . $days);
            exit;
        } catch (Exception $e) {
            $error = '로그 정리 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    } elseif ($action === 'save_log_retention_days') {
        // 보관 기간만 저장
        try {
            $days = isset($_POST['log_days']) ? (int)$_POST['log_days'] : 7;
            if ($days < 1 || $days > 365) {
                throw new Exception('보관 기간은 1일에서 365일 사이여야 합니다.');
            }
            
            $logSettings = getAppSettings('log_cleanup_settings', ['retention_days' => 7]);
            $logSettings['retention_days'] = $days;
            $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
            
            if (saveAppSettings('log_cleanup_settings', $logSettings, $updatedBy)) {
                $success = "보관 기간이 저장되었습니다. ({$days}일)";
                header('Location: ' . getAssetPath('/admin/settings/data-delete.php') . '?success=' . urlencode($success) . '&days=' . $days);
                exit;
            } else {
                $error = '보관 기간 저장에 실패했습니다.';
            }
        } catch (Exception $e) {
            $error = '보관 기간 저장 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    } elseif ($action === 'cleanup_analytics') {
        // 통계 데이터 정리
        try {
                $days = isset($_POST['analytics_days']) ? (int)$_POST['analytics_days'] : 90;
                if ($days < 1 || $days > 365) {
                    throw new Exception('보관 기간은 1일에서 365일 사이여야 합니다.');
                }
                
                $pdo = getDBConnection();
                if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');
                
                $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
                $deletedCounts = [
                    'impressions' => 0,
                    'clicks' => 0,
                    'analytics' => 0
                ];
                
                // 보관 기간 설정 저장
                $analyticsSettings = getAppSettings('analytics_cleanup_settings', ['retention_days' => 90]);
                $analyticsSettings['retention_days'] = $days;
                $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
                saveAppSettings('analytics_cleanup_settings', $analyticsSettings, $updatedBy);
                
                $pdo->beginTransaction();
                
                // advertisement_impressions 삭제
                try {
                    $stmt = $pdo->prepare("DELETE FROM advertisement_impressions WHERE created_at < :cutoff_date");
                    $stmt->execute([':cutoff_date' => $cutoffDate]);
                    $deletedCounts['impressions'] = $stmt->rowCount();
                } catch (PDOException $e) {
                    // 테이블이 없을 수 있음
                }
                
                // advertisement_clicks 삭제
                try {
                    $stmt = $pdo->prepare("DELETE FROM advertisement_clicks WHERE created_at < :cutoff_date");
                    $stmt->execute([':cutoff_date' => $cutoffDate]);
                    $deletedCounts['clicks'] = $stmt->rowCount();
                } catch (PDOException $e) {
                    // 테이블이 없을 수 있음
                }
                
                // advertisement_analytics 삭제
                try {
                    $stmt = $pdo->prepare("DELETE FROM advertisement_analytics WHERE stat_date < DATE(:cutoff_date)");
                    $stmt->execute([':cutoff_date' => $cutoffDate]);
                    $deletedCounts['analytics'] = $stmt->rowCount();
                } catch (PDOException $e) {
                    // 테이블이 없을 수 있음
                }
                
                $pdo->commit();
                
                $totalDeleted = $deletedCounts['impressions'] + $deletedCounts['clicks'] + $deletedCounts['analytics'];
                $success = "통계 데이터가 정리되었습니다. (노출: {$deletedCounts['impressions']}건, 클릭: {$deletedCounts['clicks']}건, 통계: {$deletedCounts['analytics']}건, 총 {$totalDeleted}건 삭제, 보관 기간: {$days}일)";
                header('Location: ' . getAssetPath('/admin/settings/data-delete.php') . '?success=' . urlencode($success) . '&analytics_days=' . $days);
                exit;
        } catch (Exception $e) {
            $error = '통계 데이터 정리 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    } elseif ($action === 'save_analytics_retention_days') {
        // 통계 데이터 보관 기간만 저장
        try {
                $days = isset($_POST['analytics_days']) ? (int)$_POST['analytics_days'] : 90;
                if ($days < 1 || $days > 365) {
                    throw new Exception('보관 기간은 1일에서 365일 사이여야 합니다.');
                }
                $analyticsSettings = getAppSettings('analytics_cleanup_settings', ['retention_days' => 90]);
                $analyticsSettings['retention_days'] = $days;
                $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
                if (saveAppSettings('analytics_cleanup_settings', $analyticsSettings, $updatedBy)) {
                    $success = "통계 데이터 보관 기간이 저장되었습니다. ({$days}일)";
                    header('Location: ' . getAssetPath('/admin/settings/data-delete.php') . '?success=' . urlencode($success) . '&analytics_days=' . $days);
                    exit;
                } else {
                    $error = '통계 데이터 보관 기간 저장에 실패했습니다.';
                }
        } catch (Exception $e) {
            $error = '통계 데이터 보관 기간 저장 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    } elseif ($action === 'cleanup_general_analytics') {
        // 일반 통계 분석 데이터 정리
        try {
            $days = isset($_POST['general_analytics_days']) ? (int)$_POST['general_analytics_days'] : 90;
            if ($days < 1 || $days > 365) {
                throw new Exception('보관 기간은 1일에서 365일 사이여야 합니다.');
            }
            
            require_once __DIR__ . '/../../includes/data/analytics-functions.php';
            
            // 보관 기간 설정 저장
            $generalAnalyticsSettings = getAppSettings('general_analytics_cleanup_settings', ['retention_days' => 90]);
            $generalAnalyticsSettings['retention_days'] = $days;
            $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
            saveAppSettings('general_analytics_cleanup_settings', $generalAnalyticsSettings, $updatedBy);
            
            $data = getAnalyticsData();
            $cutoffTimestamp = strtotime("-{$days} days");
            $cutoffDate = date('Y-m-d', $cutoffTimestamp);
            $cutoffDateTime = date('Y-m-d H:i:s', $cutoffTimestamp);
            
            $deletedCounts = [
                'pageviews' => 0,
                'events' => 0,
                'sessions' => 0,
                'daily_stats' => 0
            ];
            
            // 페이지뷰 데이터 정리
            if (isset($data['pageviews']) && is_array($data['pageviews'])) {
                $beforeCount = count($data['pageviews']);
                $data['pageviews'] = array_filter($data['pageviews'], function($pv) use ($cutoffDate) {
                    return isset($pv['date']) && $pv['date'] >= $cutoffDate;
                });
                $deletedCounts['pageviews'] = $beforeCount - count($data['pageviews']);
            }
            
            // 이벤트 데이터 정리
            if (isset($data['events']) && is_array($data['events'])) {
                $beforeCount = count($data['events']);
                $data['events'] = array_filter($data['events'], function($event) use ($cutoffDateTime) {
                    return isset($event['timestamp']) && $event['timestamp'] >= $cutoffDateTime;
                });
                $deletedCounts['events'] = $beforeCount - count($data['events']);
            }
            
            // 세션 데이터 정리
            if (isset($data['session_data']) && is_array($data['session_data'])) {
                $beforeCount = count($data['session_data']);
                $data['session_data'] = array_filter($data['session_data'], function($session) use ($cutoffDateTime) {
                    return isset($session['start_time']) && $session['start_time'] >= $cutoffDateTime;
                }, ARRAY_FILTER_USE_KEY);
                $deletedCounts['sessions'] = $beforeCount - count($data['session_data']);
            }
            
            // 일별 통계 정리
            if (isset($data['daily_stats']) && is_array($data['daily_stats'])) {
                $beforeCount = count($data['daily_stats']);
                $data['daily_stats'] = array_filter($data['daily_stats'], function($stat) use ($cutoffDate) {
                    return isset($stat['date']) && $stat['date'] >= $cutoffDate;
                }, ARRAY_FILTER_USE_KEY);
                $deletedCounts['daily_stats'] = $beforeCount - count($data['daily_stats']);
            }
            
            // 활성 세션 정리 (5분 이상 비활성 세션은 이미 정리되므로 여기서는 오래된 세션만)
            if (isset($data['active_sessions']) && is_array($data['active_sessions'])) {
                $now = time();
                foreach ($data['active_sessions'] as $sid => $session) {
                    if (isset($session['last_activity']) && ($now - $session['last_activity']) > ($days * 86400)) {
                        unset($data['active_sessions'][$sid]);
                    }
                }
            }
            
            // 정리된 데이터 저장
            saveAnalyticsData($data);
            
            $totalDeleted = $deletedCounts['pageviews'] + $deletedCounts['events'] + $deletedCounts['sessions'] + $deletedCounts['daily_stats'];
            $success = "일반 통계 분석 데이터가 정리되었습니다. (페이지뷰: {$deletedCounts['pageviews']}건, 이벤트: {$deletedCounts['events']}건, 세션: {$deletedCounts['sessions']}건, 일별통계: {$deletedCounts['daily_stats']}건, 총 {$totalDeleted}건 삭제, 보관 기간: {$days}일)";
            header('Location: ' . getAssetPath('/admin/settings/data-delete.php') . '?success=' . urlencode($success) . '&general_analytics_days=' . $days);
            exit;
        } catch (Exception $e) {
            $error = '일반 통계 분석 데이터 정리 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    } elseif ($action === 'save_general_analytics_retention_days') {
        // 일반 통계 분석 데이터 보관 기간만 저장
        try {
            $days = isset($_POST['general_analytics_days']) ? (int)$_POST['general_analytics_days'] : 90;
            if ($days < 1 || $days > 365) {
                throw new Exception('보관 기간은 1일에서 365일 사이여야 합니다.');
            }
            $generalAnalyticsSettings = getAppSettings('general_analytics_cleanup_settings', ['retention_days' => 90]);
            $generalAnalyticsSettings['retention_days'] = $days;
            $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
            if (saveAppSettings('general_analytics_cleanup_settings', $generalAnalyticsSettings, $updatedBy)) {
                $success = "일반 통계 분석 데이터 보관 기간이 저장되었습니다. ({$days}일)";
                header('Location: ' . getAssetPath('/admin/settings/data-delete.php') . '?success=' . urlencode($success) . '&general_analytics_days=' . $days);
                exit;
            } else {
                $error = '일반 통계 분석 데이터 보관 기간 저장에 실패했습니다.';
            }
        } catch (Exception $e) {
            $error = '일반 통계 분석 데이터 보관 기간 저장 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    } else {
        // 다른 삭제 작업은 확인 텍스트 필요
        if ($confirmText !== '삭제') {
            $error = '확인 텍스트가 올바르지 않습니다. "삭제"를 정확히 입력해주세요.';
        } else {
            try {
                switch ($action) {
                case 'delete_users':
                    // 일반회원 삭제
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');

                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'");
                    $beforeCount = (int)$stmt->fetchColumn();

                    // 관련 데이터 개수 확인
                    $qnaCount = 0;
                    $pointAccountCount = 0;
                    $pointLedgerCount = 0;
                    $emailVerificationCount = 0;
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM qna WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                        $qnaCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM user_point_accounts WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                        $pointAccountCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM user_point_ledger WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                        $pointLedgerCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM email_verification WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                        $emailVerificationCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}

                    $pdo->beginTransaction();
                    
                    // 관련 테이블 삭제
                    try {
                        $pdo->exec("DELETE FROM qna WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                    } catch (PDOException $e) {}
                    
                    try {
                        $pdo->exec("DELETE FROM user_point_ledger WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                    } catch (PDOException $e) {}
                    
                    try {
                        $pdo->exec("DELETE FROM user_point_accounts WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                    } catch (PDOException $e) {}
                    
                    try {
                        $pdo->exec("DELETE FROM email_verification WHERE user_id IN (SELECT user_id FROM users WHERE role = 'user')");
                    } catch (PDOException $e) {}
                    
                    $pdo->prepare("DELETE FROM users WHERE role = 'user'")->execute();
                    $pdo->commit();

                    $deleteResult = [
                        'type' => '일반회원', 
                        'count' => $beforeCount,
                        'qna_deleted' => $qnaCount,
                        'point_accounts_deleted' => $pointAccountCount,
                        'point_ledger_deleted' => $pointLedgerCount,
                        'email_verification_deleted' => $emailVerificationCount
                    ];
                    $success = "일반회원 {$beforeCount}명이 삭제되었습니다.";
                    $details = [];
                    if ($qnaCount > 0) $details[] = "Q&A {$qnaCount}건";
                    if ($pointAccountCount > 0) $details[] = "포인트 계정 {$pointAccountCount}건";
                    if ($pointLedgerCount > 0) $details[] = "포인트 원장 {$pointLedgerCount}건";
                    if ($emailVerificationCount > 0) $details[] = "이메일 인증 {$emailVerificationCount}건";
                    if (!empty($details)) {
                        $success .= " (" . implode(', ', $details) . " 삭제)";
                    }
                    break;
                    
                case 'delete_sellers':
                    // 판매자 삭제
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');

                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'");
                    $beforeCount = (int)$stmt->fetchColumn();

                    // 판매자 관련 파일 삭제
                    $fileResult = deleteSellerFiles($pdo);
                    $deletedFileCount = count($fileResult['files']);
                    $deletedDirCount = count($fileResult['dirs']);
                    $fileSizeMB = round($fileResult['total_size'] / 1024 / 1024, 2);

                    $pdo->beginTransaction();
                    
                    // 판매자 관련 테이블 삭제 (CASCADE로 자동 삭제되지만 명시적으로)
                    // 순서: 첨부파일 -> 답변 -> 문의 (외래키 제약조건 고려)
                    $inquiryCount = 0;
                    $replyCount = 0;
                    $attachmentCount = 0;
                    
                    try {
                        // 1. 첨부파일 삭제 (문의 첨부 + 답변 첨부 모두 포함)
                        $stmt = $pdo->query("SELECT COUNT(*) FROM seller_inquiry_attachments");
                        $attachmentCount = (int)$stmt->fetchColumn();
                        $pdo->exec("DELETE FROM seller_inquiry_attachments");
                    } catch (PDOException $e) {
                        $attachmentCount = 0;
                        // 테이블이 없을 수 있음
                    }
                    try {
                        // 2. 관리자 답변 삭제
                        $stmt = $pdo->query("SELECT COUNT(*) FROM seller_inquiry_replies");
                        $replyCount = (int)$stmt->fetchColumn();
                        $pdo->exec("DELETE FROM seller_inquiry_replies");
                    } catch (PDOException $e) {
                        $replyCount = 0;
                        // 테이블이 없을 수 있음
                    }
                    try {
                        // 3. 판매자 문의 삭제
                        $stmt = $pdo->query("SELECT COUNT(*) FROM seller_inquiries");
                        $inquiryCount = (int)$stmt->fetchColumn();
                        $pdo->exec("DELETE FROM seller_inquiries");
                    } catch (PDOException $e) {
                        $inquiryCount = 0;
                        // 테이블이 없을 수 있음
                    }
                    try {
                        $pdo->exec("DELETE FROM seller_deposit_transactions");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    try {
                        $pdo->exec("DELETE FROM seller_deposits");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    try {
                        $pdo->exec("DELETE FROM seller_deposit_accounts");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    // 예치금 관련 테이블 삭제 전 개수 확인
                    $depositAccountCount = 0;
                    $depositLedgerCount = 0;
                    $depositTransactionCount = 0;
                    $depositRequestCount = 0;
                    $taxInvoiceCount = 0;
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM seller_deposit_accounts");
                        $depositAccountCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM seller_deposit_ledger");
                        $depositLedgerCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM seller_deposit_transactions");
                        $depositTransactionCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) FROM deposit_requests");
                        $depositRequestCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    try {
                        // 세금계산서 개수 확인 (판매자와 연관된 세금계산서)
                        $stmt = $pdo->query("SELECT COUNT(*) FROM tax_invoices WHERE seller_id IN (SELECT user_id FROM users WHERE role = 'seller')");
                        $taxInvoiceCount = (int)$stmt->fetchColumn();
                    } catch (PDOException $e) {}
                    
                    // 예치금 관련 테이블 삭제 (순서: 내역 -> 계좌)
                    try {
                        $pdo->exec("DELETE FROM seller_deposit_ledger");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    try {
                        $pdo->exec("DELETE FROM seller_deposit_transactions");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    try {
                        $pdo->exec("DELETE FROM seller_deposits");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    try {
                        $pdo->exec("DELETE FROM seller_deposit_accounts");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    try {
                        // 예치금 충전 신청 삭제
                        $pdo->exec("DELETE FROM deposit_requests");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    try {
                        // 세금계산서 삭제 (판매자와 연관된 세금계산서)
                        $pdo->exec("DELETE FROM tax_invoices WHERE seller_id IN (SELECT user_id FROM users WHERE role = 'seller')");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    $pdo->prepare("DELETE FROM seller_profiles")->execute();
                    $pdo->prepare("DELETE FROM users WHERE role = 'seller'")->execute();
                    $pdo->commit();

                    $deleteResult = [
                        'type' => '판매자', 
                        'count' => $beforeCount,
                        'files_deleted' => $deletedFileCount,
                        'dirs_deleted' => $deletedDirCount,
                        'file_size_mb' => $fileSizeMB,
                        'inquiries_deleted' => $inquiryCount,
                        'replies_deleted' => $replyCount,
                        'attachments_deleted' => $attachmentCount,
                        'deposit_accounts_deleted' => $depositAccountCount,
                        'deposit_ledger_deleted' => $depositLedgerCount,
                        'deposit_transactions_deleted' => $depositTransactionCount,
                        'deposit_requests_deleted' => $depositRequestCount,
                        'tax_invoices_deleted' => $taxInvoiceCount
                    ];
                    $success = "판매자 {$beforeCount}명이 삭제되었습니다.";
                    $details = [];
                    if ($inquiryCount > 0) {
                        $details[] = "문의 {$inquiryCount}건";
                    }
                    if ($replyCount > 0) {
                        $details[] = "답변 {$replyCount}건 (관리자 답변 포함)";
                    }
                    if ($attachmentCount > 0) {
                        $details[] = "첨부파일 {$attachmentCount}개 (문의+답변 첨부 포함)";
                    }
                    if ($depositAccountCount > 0) {
                        $details[] = "예치금 계좌 {$depositAccountCount}건";
                    }
                    if ($depositLedgerCount > 0) {
                        $details[] = "예치금 원장 {$depositLedgerCount}건";
                    }
                    if ($depositTransactionCount > 0) {
                        $details[] = "예치금 거래 {$depositTransactionCount}건";
                    }
                    if ($depositRequestCount > 0) {
                        $details[] = "예치금 충전 신청 {$depositRequestCount}건";
                    }
                    if ($taxInvoiceCount > 0) {
                        $details[] = "세금계산서 {$taxInvoiceCount}건";
                    }
                    if ($deletedFileCount > 0) {
                        $details[] = "파일 {$deletedFileCount}개";
                    }
                    if ($deletedDirCount > 0) {
                        $details[] = "디렉토리 {$deletedDirCount}개";
                    }
                    if ($fileSizeMB > 0) {
                        $details[] = "{$fileSizeMB}MB";
                    }
                    if (!empty($details)) {
                        $success .= " (" . implode(', ', $details) . " 삭제)";
                    }
                    break;
                    
                case 'delete_sub_admins':
                    // 부관리자 삭제 (admin 제외)
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');

                    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'sub_admin'");
                    $subAdminCount = (int)$stmt->fetchColumn();

                    $pdo->beginTransaction();
                    $pdo->prepare("DELETE FROM admin_profiles WHERE user_id <> 'admin'")->execute();
                    $pdo->prepare("DELETE FROM users WHERE role = 'sub_admin'")->execute();
                    $pdo->commit();

                    $deleteResult = ['type' => '부관리자', 'count' => $subAdminCount];
                    $success = "부관리자 {$subAdminCount}명이 삭제되었습니다. (admin 계정은 보존되었습니다)";
                    break;
                    
                case 'delete_orders':
                    // 주문정보 삭제
                    $pdo = getDBConnection();
                    if ($pdo) {
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                        
                        // 삭제 전 개수 확인
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_applications");
                        $appCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                        
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM application_customers");
                        $customerCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
                        
                        // 세금계산서 개수 확인
                        $taxInvoiceCount = 0;
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tax_invoices");
                            $taxInvoiceCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                        } catch (PDOException $e) {}
                        
                        // 포인트 내역 개수 확인 (신청 시 사용한 포인트)
                        $pointLedgerCount = 0;
                        try {
                            $stmt = $pdo->query("SELECT COUNT(*) as count FROM user_point_ledger WHERE description LIKE '%할인혜택%'");
                            $pointLedgerCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                        } catch (PDOException $e) {}
                        
                        // 삭제 실행
                        $pdo->exec('TRUNCATE TABLE application_customers');
                        $pdo->exec('TRUNCATE TABLE product_applications');
                        
                        // 세금계산서 삭제
                        try {
                            $pdo->exec('TRUNCATE TABLE tax_invoices');
                        } catch (PDOException $e) {}
                        
                        // 포인트 내역 삭제 (신청 시 사용한 포인트)
                        try {
                            $pdo->exec("DELETE FROM user_point_ledger WHERE description LIKE '%할인혜택%'");
                        } catch (PDOException $e) {
                            // 테이블이 없을 수 있음
                        }
                        
                        // products 테이블의 application_count 초기화
                        $pdo->exec('UPDATE products SET application_count = 0');
                        
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                        
                        $deleteResult = [
                            'type' => '주문정보',
                            'count' => $appCount + $customerCount,
                            'details' => [
                                'applications' => $appCount,
                                'customers' => $customerCount,
                                'tax_invoices' => $taxInvoiceCount,
                                'point_ledger' => $pointLedgerCount
                            ]
                        ];
                        $success = "주문정보가 삭제되었습니다. (신청: {$appCount}건, 고객정보: {$customerCount}건";
                        if ($taxInvoiceCount > 0) {
                            $success .= ", 세금계산서: {$taxInvoiceCount}건";
                        }
                        if ($pointLedgerCount > 0) {
                            $success .= ", 포인트 내역: {$pointLedgerCount}건";
                        }
                        $success .= ")";
                    }
                    break;
                    
                case 'delete_products':
                    // 등록상품 삭제
                    $pdo = getDBConnection();
                    if ($pdo) {
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 0');
                        
                        // 삭제 전 개수 확인
                        $counts = [];
                        $tables = [
                            'products',
                            'product_mvno_details',
                            'product_mno_details',
                            'product_internet_details',
                            'product_reviews',
                            'product_favorites',
                            'product_shares',
                            'product_review_statistics'
                        ];
                        
                        foreach ($tables as $table) {
                            try {
                                $stmt = $pdo->query("SELECT COUNT(*) as count FROM `{$table}`");
                                $result = $stmt->fetch(PDO::FETCH_ASSOC);
                                $counts[$table] = $result['count'] ?? 0;
                            } catch (PDOException $e) {
                                $counts[$table] = 0;
                            }
                        }
                        
                        // 상품 관련 파일 삭제
                        $fileResult = deleteProductFiles($pdo);
                        $deletedFileCount = count($fileResult['files']);
                        $fileSizeMB = round($fileResult['total_size'] / 1024 / 1024, 2);
                        
                        // 테이블 존재 여부 확인 함수
                        $tableExists = function($tableName) use ($pdo) {
                            try {
                                $stmt = $pdo->query("SHOW TABLES LIKE '{$tableName}'");
                                return $stmt->rowCount() > 0;
                            } catch (PDOException $e) {
                                return false;
                            }
                        };
                        
                        // 삭제 실행
                        if ($tableExists('product_review_statistics')) {
                            $pdo->exec('TRUNCATE TABLE product_review_statistics');
                        }
                        if ($tableExists('product_reviews')) {
                            $pdo->exec('TRUNCATE TABLE product_reviews');
                        }
                        if ($tableExists('product_favorites')) {
                            $pdo->exec('TRUNCATE TABLE product_favorites');
                        }
                        if ($tableExists('product_shares')) {
                            $pdo->exec('TRUNCATE TABLE product_shares');
                        }
                        if ($tableExists('product_mvno_details')) {
                            $pdo->exec('TRUNCATE TABLE product_mvno_details');
                        }
                        if ($tableExists('product_mno_details')) {
                            $pdo->exec('TRUNCATE TABLE product_mno_details');
                        }
                        if ($tableExists('product_internet_details')) {
                            $pdo->exec('TRUNCATE TABLE product_internet_details');
                        }
                        if ($tableExists('products')) {
                            $pdo->exec('TRUNCATE TABLE products');
                        }
                        
                        $pdo->exec('SET FOREIGN_KEY_CHECKS = 1');
                        
                        $totalCount = array_sum($counts);
                        $deleteResult = [
                            'type' => '등록상품',
                            'count' => $totalCount,
                            'details' => $counts,
                            'files_deleted' => $deletedFileCount,
                            'file_size_mb' => $fileSizeMB
                        ];
                        $success = "등록상품이 삭제되었습니다. (총 {$totalCount}건)";
                        if ($deletedFileCount > 0) {
                            $success .= " (파일 {$deletedFileCount}개, {$fileSizeMB}MB 삭제)";
                        }
                    }
                    break;
                    
                case 'delete_dev_logs':
                    // 개발 로그 파일 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $logDir = $baseDir . '/logs';
                    if (is_dir($logDir)) {
                        $logFiles = glob($logDir . '/*');
                        foreach ($logFiles as $file) {
                            if (is_file($file)) {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '개발 로그 파일', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "개발 로그 파일 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_cache_files':
                    // 캐시 파일 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $cacheDir = $baseDir . '/cache';
                    if (is_dir($cacheDir)) {
                        $cacheFiles = glob($cacheDir . '/*');
                        foreach ($cacheFiles as $file) {
                            if (is_file($file)) {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = 'cache/' . basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '캐시 파일', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "캐시 파일 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_test_scripts':
                    // 테스트/디버그 스크립트 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $testPatterns = [
                        'check-*.php', 'debug-*.php', 'test-*.php', 'fix-*.php', 'verify-*.php',
                        'calculate-*.php', 'optimize-*.php', 'monitor-*.php', 'process-*.php',
                        'run-*.php', 'disable-*.php', 'cleanup-*.php', 'quick-*.php',
                        'generate_*.php', 'get_*.php', 'mypage_history*.php',
                        '*debug*.php', '*test*.php', '*check*.php', '*fix*.php',
                        '*verify*.php', '*calculate*.php', '*monitor*.php', '*optimize*.php'
                    ];
                    
                    foreach ($testPatterns as $pattern) {
                        $files = glob($baseDir . '/' . $pattern);
                        foreach ($files as $file) {
                            if (is_file($file) && basename($file) !== 'data-delete.php') {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '테스트/디버그 스크립트', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "테스트/디버그 스크립트 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_debug_pages':
                    // 디버깅 페이지 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $debugPages = [
                        'admin/review-settings-debug.php', 'admin/debug-login-redirect.php',
                        'admin/debug-qna-deletion.php', 'admin/deposit/debug-account-info.php',
                        'admin/products/debug-point-api.php', 'seller/inquiry/inquiry-debug.php',
                        'seller/inquiry/inquiry-edit-debug.php', 'seller/debug-main-notice.php',
                        'api/debug-mno-sim-orders.php'
                    ];
                    
                    foreach ($debugPages as $debugPage) {
                        $file = $baseDir . '/' . $debugPage;
                        if (is_file($file)) {
                            $size = filesize($file);
                            if (@unlink($file)) {
                                $deletedFiles[] = $debugPage;
                                $totalSize += $size;
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '디버깅 페이지', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "디버깅 페이지 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_script_files':
                    // 배치/스크립트 파일 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $scriptFiles = [
                        'check-logs.bat', 'check-logs.ps1', 'check_mypage.bat',
                        'restore_mypage.bat', 'restore_mypage.ps1', 'restore_mypage.py',
                        'check_mypage_history.py', 'generate_mypage_history_html.py',
                        'get_mypage_history.py', 'restore_mypage_simple.bat'
                    ];
                    
                    foreach ($scriptFiles as $script) {
                        $file = $baseDir . '/' . $script;
                        if (is_file($file)) {
                            $size = filesize($file);
                            if (@unlink($file)) {
                                $deletedFiles[] = $script;
                                $totalSize += $size;
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '배치/스크립트 파일', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "배치/스크립트 파일 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_doc_files':
                    // 개발 문서 파일 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $docPatterns = [
                        '*.md', 'README*.md', '*_GUIDE.md', '*_DESIGN.md', '*_PROPOSAL.md',
                        '*_SUMMARY.md', '*_EXPLANATION.md', '*_STATUS.md', '*_CHECK.md',
                        '*_RESULT.md', '*_REPORT.md', '*_ANALYSIS.md', '*_OPTIMIZATION.md',
                        '*_CLARIFICATION.md', '*_IMPLEMENTATION.md', '*_UPDATE.md',
                        '*_CHANGES.md', '*_SYSTEM*.md', '*_JOURNEY.md', '회원가입_정보_정리.md'
                    ];
                    
                    $excludeDocs = ['PRODUCTION_DEPLOYMENT_GUIDE.md', 'README.md'];
                    
                    foreach ($docPatterns as $pattern) {
                        $files = glob($baseDir . '/' . $pattern);
                        foreach ($files as $file) {
                            $basename = basename($file);
                            if (is_file($file) && !in_array($basename, $excludeDocs)) {
                                $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
                                if (strpos($relativePath, 'database' . DIRECTORY_SEPARATOR) === 0) {
                                    continue;
                                }
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '개발 문서 파일', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "개발 문서 파일 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_html_files':
                    // 임시 HTML 파일 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $htmlPatterns = [
                        'mypage_history*.html', 'privacy-*.html', '*_report.html',
                        '*_test.html', '*_debug.html'
                    ];
                    
                    foreach ($htmlPatterns as $pattern) {
                        $files = glob($baseDir . '/' . $pattern);
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '임시 HTML 파일', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "임시 HTML 파일 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_migration_scripts':
                    // 마이그레이션/복구 스크립트 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $migrationPatterns = [
                        'restore-*.php', 'delete-*.php', 'add-*.php', 'rebuild-*.php',
                        '*restore*.php', '*delete*.php', '*add*.php', '*rebuild*.php'
                    ];
                    
                    foreach ($migrationPatterns as $pattern) {
                        $files = glob($baseDir . '/' . $pattern);
                        foreach ($files as $file) {
                            if (is_file($file) && basename($file) !== 'data-delete.php') {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    $deleteResult = ['type' => '마이그레이션/복구 스크립트', 'count' => $fileCount, 'details' => ['files' => $fileCount, 'size_mb' => $sizeMB]];
                    $success = "마이그레이션/복구 스크립트 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_upload_files':
                    // 사이트 업로드 후 삭제해야 할 파일들 (전체 일괄 삭제 - 기존 기능 유지)
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $deletedDirs = [];
                    $totalSize = 0;
                    
                    // 로그 파일 삭제
                    $logDir = $baseDir . '/logs';
                    if (is_dir($logDir)) {
                        $logFiles = glob($logDir . '/*');
                        foreach ($logFiles as $file) {
                            if (is_file($file)) {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    // 캐시 파일 삭제
                    $cacheDir = $baseDir . '/cache';
                    if (is_dir($cacheDir)) {
                        $cacheFiles = glob($cacheDir . '/*');
                        foreach ($cacheFiles as $file) {
                            if (is_file($file)) {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = 'cache/' . basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    // 테스트/디버그 스크립트 삭제
                    $testPatterns = [
                        'check-*.php',
                        'debug-*.php',
                        'test-*.php',
                        'fix-*.php',
                        'verify-*.php',
                        'calculate-*.php',
                        'optimize-*.php',
                        'rebuild-*.php',
                        'monitor-*.php',
                        'process-*.php',
                        'run-*.php',
                        'restore-*.php',
                        'delete-*.php',
                        'add-*.php',
                        'disable-*.php',
                        'cleanup-*.php',
                        'quick-*.php',
                        'generate_*.php',
                        'get_*.php',
                        'mypage_history*.php',
                        'mypage_history*.html'
                    ];
                    
                    foreach ($testPatterns as $pattern) {
                        $files = glob($baseDir . '/' . $pattern);
                        foreach ($files as $file) {
                            if (is_file($file) && basename($file) !== 'data-delete.php') {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    // 배치/스크립트 파일 삭제
                    $scriptFiles = [
                        'check-logs.bat',
                        'check-logs.ps1',
                        'check_mypage.bat',
                        'restore_mypage.bat',
                        'restore_mypage.ps1',
                        'restore_mypage.py',
                        'check_mypage_history.py',
                        'generate_mypage_history_html.py',
                        'get_mypage_history.py',
                        'restore_mypage_simple.bat'
                    ];
                    
                    foreach ($scriptFiles as $script) {
                        $file = $baseDir . '/' . $script;
                        if (is_file($file)) {
                            $size = filesize($file);
                            if (@unlink($file)) {
                                $deletedFiles[] = $script;
                                $totalSize += $size;
                            }
                        }
                    }
                    
                    // 문서 파일 삭제 (프로덕션에서 불필요한 개발 문서)
                    $docPatterns = [
                        '*.md',
                        'README*.md',
                        '*_GUIDE.md',
                        '*_DESIGN.md',
                        '*_PROPOSAL.md',
                        '*_SUMMARY.md',
                        '*_EXPLANATION.md',
                        '*_STATUS.md',
                        '*_CHECK.md',
                        '*_RESULT.md',
                        '*_REPORT.md',
                        '*_ANALYSIS.md',
                        '*_OPTIMIZATION.md',
                        '*_CLARIFICATION.md',
                        '*_IMPLEMENTATION.md',
                        '*_UPDATE.md',
                        '*_CHANGES.md',
                        '*_SYSTEM*.md',
                        '*_JOURNEY.md',
                        '회원가입_정보_정리.md'
                    ];
                    
                    $excludeDocs = [
                        'PRODUCTION_DEPLOYMENT_GUIDE.md',
                        'README.md'
                    ];
                    
                    foreach ($docPatterns as $pattern) {
                        $files = glob($baseDir . '/' . $pattern);
                        foreach ($files as $file) {
                            $basename = basename($file);
                            if (is_file($file) && !in_array($basename, $excludeDocs)) {
                                $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
                                // database 폴더의 문서는 건너뛰기 (선택사항)
                                if (strpos($relativePath, 'database' . DIRECTORY_SEPARATOR) === 0) {
                                    continue;
                                }
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    // 임시 HTML 파일 삭제
                    $htmlPatterns = [
                        'mypage_history*.html',
                        'privacy-*.html',
                        '*_report.html',
                        '*_test.html',
                        '*_debug.html'
                    ];
                    
                    foreach ($htmlPatterns as $pattern) {
                        $files = glob($baseDir . '/' . $pattern);
                        foreach ($files as $file) {
                            if (is_file($file)) {
                                $size = filesize($file);
                                if (@unlink($file)) {
                                    $deletedFiles[] = basename($file);
                                    $totalSize += $size;
                                }
                            }
                        }
                    }
                    
                    // 디버깅 페이지 삭제 (admin 폴더 내)
                    $debugPages = [
                        'admin/review-settings-debug.php',
                        'admin/debug-login-redirect.php',
                        'admin/debug-qna-deletion.php',
                        'admin/deposit/debug-account-info.php',
                        'admin/products/debug-point-api.php',
                        'seller/inquiry/inquiry-debug.php',
                        'seller/inquiry/inquiry-edit-debug.php',
                        'seller/debug-main-notice.php',
                        'api/debug-mno-sim-orders.php'
                    ];
                    
                    foreach ($debugPages as $debugPage) {
                        $file = $baseDir . '/' . $debugPage;
                        if (is_file($file)) {
                            $size = filesize($file);
                            if (@unlink($file)) {
                                $deletedFiles[] = $debugPage;
                                $totalSize += $size;
                            }
                        }
                    }
                    
                    $fileCount = count($deletedFiles);
                    $sizeMB = round($totalSize / 1024 / 1024, 2);
                    
                    $deleteResult = [
                        'type' => '업로드 후 삭제 파일',
                        'count' => $fileCount,
                        'details' => [
                            'files' => $fileCount,
                            'size_mb' => $sizeMB
                        ]
                    ];
                    $success = "업로드 후 삭제 파일 {$fileCount}개가 삭제되었습니다. (총 {$sizeMB}MB)";
                    break;
                    
                case 'delete_notices':
                    // 공지사항 삭제
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');
                    
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // 삭제 전 개수 확인
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notices");
                        $noticeCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {
                        $noticeCount = 0;
                    }
                    
                    // 공지사항 이미지 파일 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $noticeDirs = [
                        $baseDir . '/uploads/notices',
                        $baseDir . '/uploads/notices/seller'
                    ];
                    
                    foreach ($noticeDirs as $dir) {
                        if (is_dir($dir)) {
                            $iterator = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            
                            foreach ($iterator as $file) {
                                if ($file->isFile()) {
                                    $size = $file->getSize();
                                    if (@unlink($file->getRealPath())) {
                                        $deletedFiles[] = basename($file->getRealPath());
                                        $totalSize += $size;
                                    }
                                }
                            }
                        }
                    }
                    
                    // DB 삭제
                    $pdo->beginTransaction();
                    try {
                        $pdo->exec("DELETE FROM notices");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    $pdo->commit();
                    
                    $fileCount = count($deletedFiles);
                    $fileSizeMB = round($totalSize / 1024 / 1024, 2);
                    
                    $deleteResult = [
                        'type' => '공지사항',
                        'count' => $noticeCount,
                        'files_deleted' => $fileCount,
                        'file_size_mb' => $fileSizeMB
                    ];
                    $success = "공지사항 {$noticeCount}건이 삭제되었습니다.";
                    if ($fileCount > 0) {
                        $success .= " (이미지 파일 {$fileCount}개, {$fileSizeMB}MB 삭제)";
                    }
                    break;
                    
                case 'delete_events':
                    // 이벤트 삭제
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');
                    
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // 삭제 전 개수 확인
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM events");
                        $eventCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {
                        $eventCount = 0;
                    }
                    
                    // 이벤트 이미지 파일 삭제
                    $baseDir = __DIR__ . '/../..';
                    $deletedFiles = [];
                    $totalSize = 0;
                    
                    $eventDirs = [
                        $baseDir . '/uploads/events',
                        $baseDir . '/images/upload/event'
                    ];
                    
                    foreach ($eventDirs as $dir) {
                        if (is_dir($dir)) {
                            $iterator = new RecursiveIteratorIterator(
                                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                                RecursiveIteratorIterator::CHILD_FIRST
                            );
                            
                            foreach ($iterator as $file) {
                                if ($file->isFile()) {
                                    $size = $file->getSize();
                                    if (@unlink($file->getRealPath())) {
                                        $deletedFiles[] = basename($file->getRealPath());
                                        $totalSize += $size;
                                    }
                                }
                            }
                        }
                    }
                    
                    // DB 삭제
                    $pdo->beginTransaction();
                    try {
                        $pdo->exec("DELETE FROM events");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    $pdo->commit();
                    
                    $fileCount = count($deletedFiles);
                    $fileSizeMB = round($totalSize / 1024 / 1024, 2);
                    
                    $deleteResult = [
                        'type' => '이벤트',
                        'count' => $eventCount,
                        'files_deleted' => $fileCount,
                        'file_size_mb' => $fileSizeMB
                    ];
                    $success = "이벤트 {$eventCount}건이 삭제되었습니다.";
                    if ($fileCount > 0) {
                        $success .= " (이미지 파일 {$fileCount}개, {$fileSizeMB}MB 삭제)";
                    }
                    break;
                    
                case 'delete_advertisements':
                    // 광고 삭제
                    $pdo = getDBConnection();
                    if (!$pdo) throw new Exception('DB 연결에 실패했습니다.');
                    
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                    
                    // 삭제 전 개수 확인
                    $adCount = 0;
                    $rotationAdCount = 0;
                    $rotationAdCount2 = 0;
                    $impressionCount = 0;
                    $clickCount = 0;
                    $analyticsCount = 0;
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_advertisements");
                        $adCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_rotation_advertisements");
                        $rotationAdCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM rotation_advertisements");
                        $rotationAdCount2 = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {}
                    
                    // 광고 분석 데이터 개수 확인
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM advertisement_impressions");
                        $impressionCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM advertisement_clicks");
                        $clickCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {}
                    
                    try {
                        $stmt = $pdo->query("SELECT COUNT(*) as count FROM advertisement_analytics");
                        $analyticsCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
                    } catch (PDOException $e) {}
                    
                    // DB 삭제 (순서: 분석 데이터 -> 광고 신청 정보)
                    $pdo->beginTransaction();
                    
                    // 광고 분석 데이터 삭제 (외래키 CASCADE로 자동 삭제되지만 명시적으로 삭제)
                    try {
                        $pdo->exec("DELETE FROM advertisement_analytics");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    try {
                        $pdo->exec("DELETE FROM advertisement_clicks");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    try {
                        $pdo->exec("DELETE FROM advertisement_impressions");
                    } catch (PDOException $e) {
                        // 테이블이 없을 수 있음
                    }
                    
                    // 광고 신청 정보 삭제
                    try {
                        $pdo->exec("DELETE FROM rotation_advertisements");
                    } catch (PDOException $e) {}
                    
                    try {
                        $pdo->exec("DELETE FROM product_rotation_advertisements");
                    } catch (PDOException $e) {}
                    
                    try {
                        $pdo->exec("DELETE FROM product_advertisements");
                    } catch (PDOException $e) {}
                    
                    $pdo->commit();
                    
                    $totalCount = $adCount + $rotationAdCount + $rotationAdCount2;
                    $analyticsTotalCount = $impressionCount + $clickCount + $analyticsCount;
                    $deleteResult = [
                        'type' => '광고',
                        'count' => $totalCount,
                        'details' => [
                            'product_advertisements' => $adCount,
                            'product_rotation_advertisements' => $rotationAdCount,
                            'rotation_advertisements' => $rotationAdCount2,
                            'impressions' => $impressionCount,
                            'clicks' => $clickCount,
                            'analytics' => $analyticsCount
                        ]
                    ];
                    $success = "광고가 삭제되었습니다. (상품 광고: {$adCount}건, 회전 광고: " . ($rotationAdCount + $rotationAdCount2) . "건";
                    if ($analyticsTotalCount > 0) {
                        $success .= ", 분석 데이터: 노출 {$impressionCount}건, 클릭 {$clickCount}건, 통계 {$analyticsCount}건";
                    }
                    $success .= ")";
                    break;
            }
        } catch (Exception $e) {
            $error = '삭제 중 오류가 발생했습니다: ' . $e->getMessage();
        }
    }
    }
}

// URL 파라미터에서 성공 메시지 가져오기
if (isset($_GET['success'])) {
    $success = urldecode($_GET['success']);
}

// 디렉토리 크기 계산 함수
function getDirectorySize($directory) {
    $size = 0;
    if (!is_dir($directory)) {
        return 0;
    }
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );
    foreach ($files as $file) {
        if ($file->isFile()) {
            $size += $file->getSize();
        }
    }
    return $size;
}

// DB 용량 및 웹 용량 계산
$dbSize = 0;
$dbSizeMB = 0;
$dbSizeGB = 0;
$webSize = 0;
$webSizeMB = 0;
$webSizeGB = 0;

$pdo = getDBConnection();
if ($pdo) {
    try {
        // DB 용량 계산
        $stmt = $pdo->query("
            SELECT 
                ROUND(SUM(data_length + index_length) / 1024 / 1024, 2) AS size_mb,
                ROUND(SUM(data_length + index_length) / 1024 / 1024 / 1024, 2) AS size_gb
            FROM information_schema.tables 
            WHERE table_schema = DATABASE()
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $dbSizeMB = (float)($result['size_mb'] ?? 0);
            $dbSizeGB = (float)($result['size_gb'] ?? 0);
            $dbSize = $dbSizeMB;
        }
    } catch (PDOException $e) {
        // ignore
    }
}

// 웹 디렉토리 크기 계산 (프로젝트 루트 기준)
$projectRoot = __DIR__ . '/../..';
try {
    $webSize = getDirectorySize($projectRoot);
    $webSizeMB = round($webSize / 1024 / 1024, 2);
    $webSizeGB = round($webSize / 1024 / 1024 / 1024, 2);
} catch (Exception $e) {
    // ignore
}

// 현재 통계 가져오기
$stats = [
    'users' => 0,
    'sellers' => 0,
    'sub_admins' => 0,
    'orders' => 0,
    'products' => 0,
    'upload_files' => 0,
    'notices' => 0,
    'events' => 0,
    'advertisements' => 0
];

// 사용자 통계 (DB-only)
$pdo = getDBConnection();
if ($pdo) {
    try {
        $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
        $stats['sellers'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'seller'")->fetchColumn();
        $stats['sub_admins'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'sub_admin'")->fetchColumn();
    } catch (PDOException $e) {
        // ignore
    }
}

// 주문 수
$pdo = getDBConnection();
if ($pdo) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_applications");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['orders'] = $result['count'] ?? 0;
    } catch (PDOException $e) {
        $stats['orders'] = 0;
    }
    
    // 상품 수
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM products");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['products'] = $result['count'] ?? 0;
    } catch (PDOException $e) {
        $stats['products'] = 0;
    }
    
    // 공지사항 수
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM notices");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['notices'] = $result['count'] ?? 0;
    } catch (PDOException $e) {
        $stats['notices'] = 0;
    }
    
    // 이벤트 수
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM events");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['events'] = $result['count'] ?? 0;
    } catch (PDOException $e) {
        $stats['events'] = 0;
    }
    
    // 광고 수
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_advertisements");
        $adCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    } catch (PDOException $e) {
        $adCount = 0;
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM product_rotation_advertisements");
        $rotationAdCount = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    } catch (PDOException $e) {
        $rotationAdCount = 0;
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM rotation_advertisements");
        $rotationAdCount2 = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    } catch (PDOException $e) {
        $rotationAdCount2 = 0;
    }
    
    $stats['advertisements'] = $adCount + $rotationAdCount + $rotationAdCount2;
    
    // 일반 통계 분석 데이터 개수 확인
    try {
        require_once __DIR__ . '/../../includes/data/analytics-functions.php';
        $analyticsData = getAnalyticsData();
        $stats['general_analytics_pageviews'] = count($analyticsData['pageviews'] ?? []);
        $stats['general_analytics_events'] = count($analyticsData['events'] ?? []);
        $stats['general_analytics_sessions'] = count($analyticsData['session_data'] ?? []);
        $stats['general_analytics_daily_stats'] = count($analyticsData['daily_stats'] ?? []);
    } catch (Exception $e) {
        $stats['general_analytics_pageviews'] = 0;
        $stats['general_analytics_events'] = 0;
        $stats['general_analytics_sessions'] = 0;
        $stats['general_analytics_daily_stats'] = 0;
    }
    
    // 통계 데이터 개수 확인
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM advertisement_impressions");
        $stats['analytics_impressions'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    } catch (PDOException $e) {
        $stats['analytics_impressions'] = 0;
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM advertisement_clicks");
        $stats['analytics_clicks'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    } catch (PDOException $e) {
        $stats['analytics_clicks'] = 0;
    }
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM advertisement_analytics");
        $stats['analytics_aggregated'] = (int)($stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0);
    } catch (PDOException $e) {
        $stats['analytics_aggregated'] = 0;
    }
}

// 업로드 후 삭제 파일 통계 (카테고리별)
$baseDir = __DIR__ . '/../..';
$uploadFileCount = 0;
$uploadFileSize = 0;

// 각 카테고리별 통계
$uploadStats = [
    'dev_logs' => ['count' => 0, 'size' => 0, 'name' => '개발 로그 파일'],
    'cache' => ['count' => 0, 'size' => 0, 'name' => '캐시 파일'],
    'test_scripts' => ['count' => 0, 'size' => 0, 'name' => '테스트/디버그 스크립트'],
    'debug_pages' => ['count' => 0, 'size' => 0, 'name' => '디버깅 페이지'],
    'script_files' => ['count' => 0, 'size' => 0, 'name' => '배치/스크립트 파일'],
    'doc_files' => ['count' => 0, 'size' => 0, 'name' => '개발 문서 파일'],
    'html_files' => ['count' => 0, 'size' => 0, 'name' => '임시 HTML 파일'],
    'migration_scripts' => ['count' => 0, 'size' => 0, 'name' => '마이그레이션/복구 스크립트']
];

// 개발 로그 파일 (logs 폴더)
$logDir = $baseDir . '/logs';
if (is_dir($logDir)) {
    $logFiles = glob($logDir . '/*');
    foreach ($logFiles as $file) {
        if (is_file($file)) {
            $uploadStats['dev_logs']['count']++;
            $uploadStats['dev_logs']['size'] += filesize($file);
            $uploadFileCount++;
            $uploadFileSize += filesize($file);
        }
    }
}

// 캐시 파일
$cacheDir = $baseDir . '/cache';
if (is_dir($cacheDir)) {
    $cacheFiles = glob($cacheDir . '/*');
    foreach ($cacheFiles as $file) {
        if (is_file($file)) {
            $uploadStats['cache']['count']++;
            $uploadStats['cache']['size'] += filesize($file);
            $uploadFileCount++;
            $uploadFileSize += filesize($file);
        }
    }
}

// 테스트/디버그 스크립트 (마이그레이션/복구 제외)
$testPatterns = [
    'check-*.php',
    'debug-*.php',
    'test-*.php',
    'fix-*.php',
    'verify-*.php',
    'calculate-*.php',
    'optimize-*.php',
    'monitor-*.php',
    'process-*.php',
    'run-*.php',
    'disable-*.php',
    'cleanup-*.php',
    'quick-*.php',
    'generate_*.php',
    'get_*.php',
    'mypage_history*.php',
    // 추가 개발/디버깅 파일
    '*debug*.php',
    '*test*.php',
    '*check*.php',
    '*fix*.php',
    '*verify*.php',
    '*calculate*.php',
    '*monitor*.php',
    '*optimize*.php'
];

foreach ($testPatterns as $pattern) {
    $files = glob($baseDir . '/' . $pattern);
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'data-delete.php') {
            $uploadStats['test_scripts']['count']++;
            $uploadStats['test_scripts']['size'] += filesize($file);
            $uploadFileCount++;
            $uploadFileSize += filesize($file);
        }
    }
}

// 마이그레이션/복구 스크립트
$migrationPatterns = [
    'restore-*.php',
    'delete-*.php',
    'add-*.php',
    'rebuild-*.php',
    '*restore*.php',
    '*delete*.php',
    '*add*.php',
    '*rebuild*.php'
];

foreach ($migrationPatterns as $pattern) {
    $files = glob($baseDir . '/' . $pattern);
    foreach ($files as $file) {
        if (is_file($file) && basename($file) !== 'data-delete.php') {
            $uploadStats['migration_scripts']['count']++;
            $uploadStats['migration_scripts']['size'] += filesize($file);
            $uploadFileCount++;
            $uploadFileSize += filesize($file);
        }
    }
}

// 배치/스크립트 파일
$scriptFiles = [
    'check-logs.bat',
    'check-logs.ps1',
    'check_mypage.bat',
    'restore_mypage.bat',
    'restore_mypage.ps1',
    'restore_mypage.py',
    'check_mypage_history.py',
    'generate_mypage_history_html.py',
    'get_mypage_history.py',
    'restore_mypage_simple.bat'
];

foreach ($scriptFiles as $script) {
    $file = $baseDir . '/' . $script;
    if (is_file($file)) {
        $uploadStats['script_files']['count']++;
        $uploadStats['script_files']['size'] += filesize($file);
        $uploadFileCount++;
        $uploadFileSize += filesize($file);
    }
}

// 문서 파일 (프로덕션에서 불필요한 개발 문서)
$docPatterns = [
    '*.md',
    'README*.md',
    '*_GUIDE.md',
    '*_DESIGN.md',
    '*_PROPOSAL.md',
    '*_SUMMARY.md',
    '*_EXPLANATION.md',
    '*_STATUS.md',
    '*_CHECK.md',
    '*_RESULT.md',
    '*_REPORT.md',
    '*_ANALYSIS.md',
    '*_OPTIMIZATION.md',
    '*_CLARIFICATION.md',
    '*_IMPLEMENTATION.md',
    '*_UPDATE.md',
    '*_CHANGES.md',
    '*_SYSTEM*.md',
    '*_JOURNEY.md',
    '회원가입_정보_정리.md'
];

// 문서 파일 제외 목록 (유지해야 할 문서)
$excludeDocs = [
    'PRODUCTION_DEPLOYMENT_GUIDE.md',
    'README.md' // 루트 README는 유지 가능
];

foreach ($docPatterns as $pattern) {
    $files = glob($baseDir . '/' . $pattern);
    foreach ($files as $file) {
        $basename = basename($file);
        // 제외 목록에 없고, database 폴더의 일부 중요한 문서는 유지
        if (is_file($file) && !in_array($basename, $excludeDocs)) {
            // database 폴더의 스키마 관련 문서는 유지 (선택사항)
            $relativePath = str_replace($baseDir . DIRECTORY_SEPARATOR, '', $file);
            if (strpos($relativePath, 'database' . DIRECTORY_SEPARATOR) === 0) {
                // database 폴더의 문서는 건너뛰기 (선택사항)
                continue;
            }
            $uploadStats['doc_files']['count']++;
            $uploadStats['doc_files']['size'] += filesize($file);
            $uploadFileCount++;
            $uploadFileSize += filesize($file);
        }
    }
}

// 임시 HTML 파일
$htmlPatterns = [
    'mypage_history*.html',
    'privacy-*.html',
    '*_report.html',
    '*_test.html',
    '*_debug.html'
];

foreach ($htmlPatterns as $pattern) {
    $files = glob($baseDir . '/' . $pattern);
    foreach ($files as $file) {
        if (is_file($file)) {
            $uploadStats['html_files']['count']++;
            $uploadStats['html_files']['size'] += filesize($file);
            $uploadFileCount++;
            $uploadFileSize += filesize($file);
        }
    }
}

// 디버깅 페이지 (admin, seller, api 폴더 내)
$debugPages = [
    'admin/review-settings-debug.php',
    'admin/debug-login-redirect.php',
    'admin/debug-qna-deletion.php',
    'admin/deposit/debug-account-info.php',
    'admin/products/debug-point-api.php',
    'seller/inquiry/inquiry-debug.php',
    'seller/inquiry/inquiry-edit-debug.php',
    'seller/debug-main-notice.php',
    'api/debug-mno-sim-orders.php'
];

foreach ($debugPages as $debugPage) {
    $file = $baseDir . '/' . $debugPage;
    if (is_file($file)) {
        $uploadStats['debug_pages']['count']++;
        $uploadStats['debug_pages']['size'] += filesize($file);
        $uploadFileCount++;
        $uploadFileSize += filesize($file);
    }
}

// 각 카테고리의 MB 단위 계산
foreach ($uploadStats as $key => &$stat) {
    $stat['size_mb'] = round($stat['size'] / 1024 / 1024, 2);
}
unset($stat);

$stats['upload_files'] = $uploadFileCount;
$stats['upload_files_size_mb'] = round($uploadFileSize / 1024 / 1024, 2);

// 로그 파일 크기 확인
$logSizes = getLogSizes();
$totalLogSize = 0;
foreach ($logSizes as $size) {
    if (is_numeric($size)) {
        $totalLogSize += $size;
    } elseif (is_array($size)) {
        foreach ($size as $fileSize) {
            $totalLogSize += $fileSize;
        }
    }
}
$stats['log_files_size_mb'] = round($totalLogSize / 1024 / 1024, 2);

// 현재 페이지 설정
$currentPage = 'data-delete.php';

// 헤더 포함
require_once __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-content">
    <div class="content-header">
        <h1>데이터 삭제 관리</h1>
        <p class="content-description">회원정보, 주문정보, 등록상품을 삭제할 수 있습니다. 삭제된 데이터는 복구할 수 없으니 주의하세요.</p>
    </div>

    <?php if ($error): ?>
    <div class="alert alert-error">
        <strong>오류:</strong> <?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <strong>성공:</strong> <?php echo htmlspecialchars($success); ?>
    </div>
    <?php endif; ?>

    <!-- 용량 정보 섹션 -->
    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 12px; padding: 24px; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2 style="color: #fff; margin: 0 0 20px 0; font-size: 20px; font-weight: 600;">
            💾 저장 공간 현황
        </h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
            <!-- DB 용량 -->
            <div style="background: rgba(255,255,255,0.95); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px;">
                        🗄️
                    </div>
                    <div>
                        <div style="font-size: 14px; color: #666; font-weight: 500;">데이터베이스 용량</div>
                        <div style="font-size: 24px; font-weight: 700; color: #333; margin-top: 4px;">
                            <?php 
                            if ($dbSizeGB >= 1) {
                                echo number_format($dbSizeGB, 2) . ' GB';
                            } else {
                                echo number_format($dbSizeMB, 2) . ' MB';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div style="font-size: 12px; color: #888; margin-top: 8px;">
                    <?php echo number_format($dbSizeMB, 2); ?> MB 
                    <?php if ($dbSizeGB >= 1): ?>
                        (<?php echo number_format($dbSizeGB, 2); ?> GB)
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 웹 용량 -->
            <div style="background: rgba(255,255,255,0.95); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px;">
                        📁
                    </div>
                    <div>
                        <div style="font-size: 14px; color: #666; font-weight: 500;">웹 디렉토리 용량</div>
                        <div style="font-size: 24px; font-weight: 700; color: #333; margin-top: 4px;">
                            <?php 
                            if ($webSizeGB >= 1) {
                                echo number_format($webSizeGB, 2) . ' GB';
                            } else {
                                echo number_format($webSizeMB, 2) . ' MB';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div style="font-size: 12px; color: #888; margin-top: 8px;">
                    <?php echo number_format($webSizeMB, 2); ?> MB 
                    <?php if ($webSizeGB >= 1): ?>
                        (<?php echo number_format($webSizeGB, 2); ?> GB)
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 총 용량 -->
            <div style="background: rgba(255,255,255,0.95); border-radius: 8px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 12px;">
                    <div style="width: 48px; height: 48px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 10px; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 24px;">
                        📊
                    </div>
                    <div>
                        <div style="font-size: 14px; color: #666; font-weight: 500;">총 사용 용량</div>
                        <div style="font-size: 24px; font-weight: 700; color: #333; margin-top: 4px;">
                            <?php 
                            $totalSizeMB = $dbSizeMB + $webSizeMB;
                            $totalSizeGB = $totalSizeMB / 1024;
                            if ($totalSizeGB >= 1) {
                                echo number_format($totalSizeGB, 2) . ' GB';
                            } else {
                                echo number_format($totalSizeMB, 2) . ' MB';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                <div style="font-size: 12px; color: #888; margin-top: 8px;">
                    DB: <?php echo number_format($dbSizeMB, 2); ?> MB + 
                    웹: <?php echo number_format($webSizeMB, 2); ?> MB
                </div>
            </div>
        </div>
    </div>

    <div class="data-delete-container">
        <!-- 회원정보 삭제 -->
        <div class="delete-section">
            <h2>회원정보 삭제</h2>
            <div class="delete-cards">
                <!-- 일반회원 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>일반회원</h3>
                        <span class="count-badge"><?php echo number_format($stats['users']); ?>명</span>
                    </div>
                    <p class="delete-description">일반회원 정보를 모두 삭제합니다.</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_users', '일반회원', <?php echo $stats['users']; ?>)">
                        삭제하기
                    </button>
                </div>

                <!-- 판매자 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>판매자</h3>
                        <span class="count-badge"><?php echo number_format($stats['sellers']); ?>명</span>
                    </div>
                    <p class="delete-description">판매자 정보를 모두 삭제합니다.</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_sellers', '판매자', <?php echo $stats['sellers']; ?>)">
                        삭제하기
                    </button>
                </div>

                <!-- 부관리자 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>부관리자</h3>
                        <span class="count-badge"><?php echo number_format($stats['sub_admins']); ?>명</span>
                    </div>
                    <p class="delete-description">부관리자 정보를 모두 삭제합니다. (admin 계정은 제외)</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_sub_admins', '부관리자', <?php echo $stats['sub_admins']; ?>)">
                        삭제하기
                    </button>
                </div>
            </div>
        </div>

        <!-- 주문정보 삭제 -->
        <div class="delete-section">
            <h2>주문정보 삭제</h2>
            <div class="delete-card">
                <div class="delete-card-header">
                    <h3>주문/신청 내역</h3>
                    <span class="count-badge"><?php echo number_format($stats['orders']); ?>건</span>
                </div>
                <p class="delete-description">모든 주문 및 신청 내역과 고객 정보를 삭제합니다.</p>
                <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_orders', '주문정보', <?php echo $stats['orders']; ?>)">
                    삭제하기
                </button>
            </div>
        </div>

        <!-- 등록상품 삭제 -->
        <div class="delete-section">
            <h2>등록상품 삭제</h2>
            <div class="delete-card">
                <div class="delete-card-header">
                    <h3>등록된 상품</h3>
                    <span class="count-badge"><?php echo number_format($stats['products']); ?>개</span>
                </div>
                <p class="delete-description">모든 등록된 상품과 관련 정보(리뷰, 찜, 공유 등)를 삭제합니다.</p>
                <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_products', '등록상품', <?php echo $stats['products']; ?>)">
                    삭제하기
                </button>
            </div>
        </div>

        <!-- 콘텐츠 삭제 -->
        <div class="delete-section">
            <h2>콘텐츠 삭제</h2>
            <div class="delete-cards">
                <!-- 공지사항 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>공지사항</h3>
                        <span class="count-badge"><?php echo number_format($stats['notices']); ?>건</span>
                    </div>
                    <p class="delete-description">모든 공지사항과 첨부 이미지를 삭제합니다.</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_notices', '공지사항', <?php echo $stats['notices']; ?>)">
                        삭제하기
                    </button>
                </div>

                <!-- 이벤트 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>이벤트</h3>
                        <span class="count-badge"><?php echo number_format($stats['events']); ?>건</span>
                    </div>
                    <p class="delete-description">모든 이벤트와 첨부 이미지를 삭제합니다.</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_events', '이벤트', <?php echo $stats['events']; ?>)">
                        삭제하기
                    </button>
                </div>

                <!-- 광고 삭제 -->
                <div class="delete-card">
                    <div class="delete-card-header">
                        <h3>광고</h3>
                        <span class="count-badge"><?php echo number_format($stats['advertisements']); ?>건</span>
                    </div>
                    <p class="delete-description">모든 상품 광고와 회전 광고를 삭제합니다.</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_advertisements', '광고', <?php echo $stats['advertisements']; ?>)">
                        삭제하기
                    </button>
                </div>
            </div>
        </div>

        <!-- 사이트 업로드 후 삭제해야 할 데이터 -->
        <div class="delete-section" style="background: #fff3cd; border: 2px solid #ffc107;">
            <h2 style="color: #856404;">⚠️ 사이트 업로드 후 삭제해야 할 데이터</h2>
            <p style="color: #856404; margin-bottom: 20px; font-weight: 600;">
                서버에 사이트를 업로드한 후에는 개발/테스트 환경에서 생성된 파일들을 삭제해야 합니다. 각 항목별로 개별 삭제가 가능합니다.
            </p>
            <div class="delete-cards">
                <!-- 개발 로그 파일 -->
                <?php if ($uploadStats['dev_logs']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>개발 로그 파일</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['dev_logs']['count']); ?>개 (<?php echo number_format($uploadStats['dev_logs']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">logs/* 폴더의 모든 로그 파일</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_dev_logs', '개발 로그 파일', <?php echo $uploadStats['dev_logs']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>

                <!-- 캐시 파일 -->
                <?php if ($uploadStats['cache']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>캐시 파일</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['cache']['count']); ?>개 (<?php echo number_format($uploadStats['cache']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">cache/* 폴더의 모든 캐시 파일</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_cache_files', '캐시 파일', <?php echo $uploadStats['cache']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>

                <!-- 테스트/디버그 스크립트 -->
                <?php if ($uploadStats['test_scripts']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>테스트/디버그 스크립트</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['test_scripts']['count']); ?>개 (<?php echo number_format($uploadStats['test_scripts']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">check-*.php, debug-*.php, test-*.php, fix-*.php, verify-*.php, calculate-*.php, optimize-*.php, monitor-*.php 등</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_test_scripts', '테스트/디버그 스크립트', <?php echo $uploadStats['test_scripts']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>

                <!-- 디버깅 페이지 -->
                <?php if ($uploadStats['debug_pages']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>디버깅 페이지</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['debug_pages']['count']); ?>개 (<?php echo number_format($uploadStats['debug_pages']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">admin/review-settings-debug.php, seller/inquiry/inquiry-debug.php 등 디버깅용 페이지</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_debug_pages', '디버깅 페이지', <?php echo $uploadStats['debug_pages']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>

                <!-- 배치/스크립트 파일 -->
                <?php if ($uploadStats['script_files']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>배치/스크립트 파일</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['script_files']['count']); ?>개 (<?php echo number_format($uploadStats['script_files']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">*.bat, *.ps1, *.py 등 배치 및 스크립트 파일</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_script_files', '배치/스크립트 파일', <?php echo $uploadStats['script_files']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>

                <!-- 개발 문서 파일 -->
                <?php if ($uploadStats['doc_files']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>개발 문서 파일</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['doc_files']['count']); ?>개 (<?php echo number_format($uploadStats['doc_files']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">*.md 파일 (database 폴더 제외, PRODUCTION_DEPLOYMENT_GUIDE.md, README.md 제외)</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_doc_files', '개발 문서 파일', <?php echo $uploadStats['doc_files']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>

                <!-- 임시 HTML 파일 -->
                <?php if ($uploadStats['html_files']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>임시 HTML 파일</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['html_files']['count']); ?>개 (<?php echo number_format($uploadStats['html_files']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">mypage_history*.html, privacy-*.html, *_report.html, *_test.html, *_debug.html</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_html_files', '임시 HTML 파일', <?php echo $uploadStats['html_files']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>

                <!-- 마이그레이션/복구 스크립트 -->
                <?php if ($uploadStats['migration_scripts']['count'] > 0): ?>
                <div class="delete-card" style="background: #fff;">
                    <div class="delete-card-header">
                        <h3>마이그레이션/복구 스크립트</h3>
                        <span class="count-badge"><?php echo number_format($uploadStats['migration_scripts']['count']); ?>개 (<?php echo number_format($uploadStats['migration_scripts']['size_mb'], 2); ?>MB)</span>
                    </div>
                    <p class="delete-description">add-*.php, restore-*.php, rebuild-*.php, delete-*.php 등 마이그레이션 및 복구 스크립트</p>
                    <button type="button" class="btn btn-danger" onclick="showDeleteModal('delete_migration_scripts', '마이그레이션/복구 스크립트', <?php echo $uploadStats['migration_scripts']['count']; ?>)">
                        삭제하기
                    </button>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 로그 파일 자동 관리 -->
        <div class="delete-section" style="background: #e7f3ff; border: 2px solid #0066cc;">
            <h2 style="color: #0066cc;">📋 로그 파일 자동 관리</h2>
            <p style="color: #0066cc; margin-bottom: 20px; font-weight: 600;">
                시스템이 자동으로 생성하는 로그 파일들을 정리하여 디스크 공간을 절약합니다.
            </p>
            <div class="delete-card" style="background: #fff;">
                <div class="delete-card-header">
                    <h3>로그 파일 정리</h3>
                    <span class="count-badge" style="background: #0066cc;"><?php echo number_format($stats['log_files_size_mb'], 2); ?>MB</span>
                </div>
                <p class="delete-description">
                    다음 로그 파일들이 정리됩니다:<br>
                    • 커스텀 로그 (logs/connections.log, logs/sessions.log, logs/event_debug.log)<br>
                    • PHP 에러 로그 (php_error_log)<br>
                    • Apache 로그 (error.log, access.log)<br>
                    • MySQL 로그 (*.err)<br>
                    • 캐시 파일 (cache/*.cache)<br>
                    • 세션 파일 (만료된 세션)
                </p>
                <?php
                // 저장된 보관 기간 불러오기
                $logSettings = getAppSettings('log_cleanup_settings', ['retention_days' => 7]);
                $savedDays = (int)($logSettings['retention_days'] ?? 7);
                $selectedDays = isset($_POST['log_days']) ? (int)$_POST['log_days'] : (isset($_GET['days']) ? (int)$_GET['days'] : $savedDays);
                ?>
                <form method="POST" style="margin-top: 16px;" id="logRetentionForm">
                    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
                        <label for="log_days" style="font-weight: 600; color: #333;">보관 기간:</label>
                        <select name="log_days" id="log_days" class="form-control" style="width: auto; padding: 6px 12px;">
                            <?php
                            $options = [3 => '3일', 7 => '7일', 14 => '14일', 30 => '30일', 60 => '60일', 90 => '90일'];
                            foreach ($options as $value => $label) {
                                $selected = ($selectedDays == $value) ? 'selected' : '';
                                echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                            }
                            ?>
                        </select>
                        <span style="color: #666; font-size: 13px;">이 기간보다 오래된 로그는 삭제됩니다.</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="action" value="save_log_retention_days" class="btn btn-primary">
                            설정 저장
                        </button>
                        <button type="submit" name="action" value="cleanup_logs" class="btn btn-danger" onclick="return confirm('로그 파일을 정리하시겠습니까? 지정한 기간(' + document.getElementById('log_days').options[document.getElementById('log_days').selectedIndex].text + ')보다 오래된 로그만 삭제됩니다.');">
                            로그 정리 실행
                        </button>
                    </div>
                </form>
                <div style="margin-top: 16px; padding: 12px; background: #f8f9fa; border-radius: 6px; font-size: 13px; color: #666;">
                    <strong>💡 자동 실행 설정:</strong><br>
                    • Windows 작업 스케줄러: <code>admin/cron/cleanup-logs.php</code>를 매일 실행<br>
                    • Linux Cron: <code>0 2 * * * php /path/to/admin/cron/cleanup-logs.php</code><br>
                    • MySQL 바이너리 로그는 자동으로 설정된 기간 후 삭제됩니다.
                </div>
            </div>
        </div>
        
        <!-- 통계 데이터 자동 관리 -->
        <div class="delete-section" style="background: #f0fdf4; border: 2px solid #10b981;">
            <h2 style="color: #047857;">📊 통계 데이터 자동 관리</h2>
            <p style="color: #047857; margin-bottom: 20px; font-weight: 600;">
                광고 분석 및 통계 데이터를 정리하여 데이터베이스 공간을 절약합니다.
            </p>
            <div class="delete-card" style="background: #fff;">
                <div class="delete-card-header">
                    <h3>통계 데이터 정리</h3>
                    <span class="count-badge" style="background: #10b981;">
                        노출: <?php echo number_format($stats['analytics_impressions']); ?>건, 
                        클릭: <?php echo number_format($stats['analytics_clicks']); ?>건, 
                        통계: <?php echo number_format($stats['analytics_aggregated']); ?>건
                    </span>
                </div>
                <p class="delete-description">
                    다음 통계 데이터가 정리됩니다:<br>
                    • 광고 노출 추적 데이터 (advertisement_impressions)<br>
                    • 광고 클릭 추적 데이터 (advertisement_clicks)<br>
                    • 광고 통계 집계 데이터 (advertisement_analytics)
                </p>
                <?php
                // 저장된 보관 기간 불러오기
                $analyticsSettings = getAppSettings('analytics_cleanup_settings', ['retention_days' => 90]);
                $savedAnalyticsDays = (int)($analyticsSettings['retention_days'] ?? 90);
                $selectedAnalyticsDays = isset($_POST['analytics_days']) ? (int)$_POST['analytics_days'] : (isset($_GET['analytics_days']) ? (int)$_GET['analytics_days'] : $savedAnalyticsDays);
                ?>
                <form method="POST" style="margin-top: 16px;" id="analyticsRetentionForm">
                    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
                        <label for="analytics_days" style="font-weight: 600; color: #333;">보관 기간:</label>
                        <select name="analytics_days" id="analytics_days" class="form-control" style="width: auto; padding: 6px 12px;">
                            <?php
                            $options = [30 => '30일', 60 => '60일', 90 => '90일', 180 => '180일', 365 => '1년'];
                            foreach ($options as $value => $label) {
                                $selected = ($selectedAnalyticsDays == $value) ? 'selected' : '';
                                echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                            }
                            ?>
                        </select>
                        <span style="color: #666; font-size: 13px;">이 기간보다 오래된 통계 데이터는 삭제됩니다.</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="action" value="save_analytics_retention_days" class="btn btn-primary">
                            설정 저장
                        </button>
                        <button type="submit" name="action" value="cleanup_analytics" class="btn btn-danger" onclick="return confirm('통계 데이터를 정리하시겠습니까? 지정한 기간(' + document.getElementById('analytics_days').options[document.getElementById('analytics_days').selectedIndex].text + ')보다 오래된 데이터만 삭제됩니다.');">
                            통계 데이터 정리 실행
                        </button>
                    </div>
                </form>
                <div style="margin-top: 16px; padding: 12px; background: #f8f9fa; border-radius: 6px; font-size: 13px; color: #666;">
                    <strong>💡 참고사항:</strong><br>
                    • 통계 데이터는 광고 분석에 사용되므로 적절한 보관 기간을 설정하세요.<br>
                    • 기본 보관 기간은 90일입니다.<br>
                    • 통계 집계 데이터(advertisement_analytics)는 일별 집계 데이터이므로 원본 데이터보다 적은 공간을 사용합니다.<br><br>
                    <strong>🔄 자동 실행 설정:</strong><br>
                    • Windows 작업 스케줄러: <code>admin/cron/cleanup-analytics.php</code>를 매일 실행<br>
                    • Linux Cron: <code>0 2 * * * php /path/to/admin/cron/cleanup-analytics.php</code><br>
                    • 설정한 보관 기간보다 오래된 통계 데이터가 자동으로 삭제됩니다.
                </div>
            </div>
        </div>
        
        <!-- 일반 통계 분석 데이터 자동 관리 -->
        <div class="delete-section" style="background: #fef3c7; border: 2px solid #f59e0b;">
            <h2 style="color: #92400e;">📈 일반 통계 분석 데이터 자동 관리</h2>
            <p style="color: #92400e; margin-bottom: 20px; font-weight: 600;">
                페이지뷰, 이벤트, 세션 등 일반 통계 분석 데이터를 정리하여 데이터베이스 공간을 절약합니다.
            </p>
            <div class="delete-card" style="background: #fff;">
                <div class="delete-card-header">
                    <h3>일반 통계 분석 데이터 정리</h3>
                    <span class="count-badge" style="background: #f59e0b;">
                        페이지뷰: <?php echo number_format($stats['general_analytics_pageviews']); ?>건, 
                        이벤트: <?php echo number_format($stats['general_analytics_events']); ?>건, 
                        세션: <?php echo number_format($stats['general_analytics_sessions']); ?>건, 
                        일별통계: <?php echo number_format($stats['general_analytics_daily_stats']); ?>건
                    </span>
                </div>
                <p class="delete-description">
                    다음 통계 분석 데이터가 정리됩니다:<br>
                    • 페이지뷰 데이터 (pageviews)<br>
                    • 이벤트 데이터 (events: 상품 조회, 신청, 찜, 공유 등)<br>
                    • 세션 데이터 (session_data)<br>
                    • 일별 통계 데이터 (daily_stats)<br>
                    • 활성 세션 데이터 (active_sessions)
                </p>
                <?php
                // 저장된 보관 기간 불러오기
                $generalAnalyticsSettings = getAppSettings('general_analytics_cleanup_settings', ['retention_days' => 90]);
                $savedGeneralAnalyticsDays = (int)($generalAnalyticsSettings['retention_days'] ?? 90);
                $selectedGeneralAnalyticsDays = isset($_POST['general_analytics_days']) ? (int)$_POST['general_analytics_days'] : (isset($_GET['general_analytics_days']) ? (int)$_GET['general_analytics_days'] : $savedGeneralAnalyticsDays);
                ?>
                <form method="POST" style="margin-top: 16px;" id="generalAnalyticsRetentionForm">
                    <div style="display: flex; gap: 12px; align-items: center; margin-bottom: 12px;">
                        <label for="general_analytics_days" style="font-weight: 600; color: #333;">보관 기간:</label>
                        <select name="general_analytics_days" id="general_analytics_days" class="form-control" style="width: auto; padding: 6px 12px;">
                            <?php
                            $options = [30 => '30일', 60 => '60일', 90 => '90일', 180 => '180일', 365 => '1년'];
                            foreach ($options as $value => $label) {
                                $selected = ($selectedGeneralAnalyticsDays == $value) ? 'selected' : '';
                                echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
                            }
                            ?>
                        </select>
                        <span style="color: #666; font-size: 13px;">이 기간보다 오래된 통계 분석 데이터는 삭제됩니다.</span>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" name="action" value="save_general_analytics_retention_days" class="btn btn-primary">
                            설정 저장
                        </button>
                        <button type="submit" name="action" value="cleanup_general_analytics" class="btn btn-danger" onclick="return confirm('일반 통계 분석 데이터를 정리하시겠습니까? 지정한 기간(' + document.getElementById('general_analytics_days').options[document.getElementById('general_analytics_days').selectedIndex].text + ')보다 오래된 데이터만 삭제됩니다.');">
                            통계 분석 데이터 정리 실행
                        </button>
                    </div>
                </form>
                <div style="margin-top: 16px; padding: 12px; background: #f8f9fa; border-radius: 6px; font-size: 13px; color: #666;">
                    <strong>💡 참고사항:</strong><br>
                    • 일반 통계 분석 데이터는 웹사이트 방문 통계에 사용되므로 적절한 보관 기간을 설정하세요.<br>
                    • 기본 보관 기간은 90일입니다.<br>
                    • 이 데이터는 app_settings 테이블의 analytics_data namespace에 JSON 형식으로 저장됩니다.<br><br>
                    <strong>🔄 자동 실행 설정:</strong><br>
                    • Windows 작업 스케줄러: <code>admin/cron/cleanup-analytics.php</code>를 매일 실행<br>
                    • Linux Cron: <code>0 2 * * * php /path/to/admin/cron/cleanup-analytics.php</code><br>
                    • 설정한 보관 기간보다 오래된 통계 분석 데이터가 자동으로 삭제됩니다.
                </div>
            </div>
        </div>
        
        <!-- 로그 파일 다운로드 -->
        <div class="delete-section" style="background: #e7f3ff; border: 2px solid #0066cc;">
            <div class="delete-card" style="background: #fff;">
                <!-- 로그 파일 다운로드 -->
            <div class="delete-card" style="background: #fff; margin-top: 20px;">
                <div class="delete-card-header">
                    <h3>📥 로그 파일 다운로드</h3>
                </div>
                <p class="delete-description">
                    로그 파일을 개별 다운로드하거나 전체를 ZIP으로 다운로드할 수 있습니다.
                </p>
                <?php
                require_once __DIR__ . '/../../includes/data/log-download-functions.php';
                $allLogFiles = getAllLogFiles();
                ?>
                <div style="margin-top: 16px;">
                    <div style="display: flex; gap: 10px; margin-bottom: 16px; flex-wrap: wrap;">
                        <a href="?action=download_all_logs" class="btn" style="background: #17a2b8; color: white; text-decoration: none; display: inline-block;">
                            📦 전체 ZIP 다운로드
                        </a>
                    </div>
                    
                    <?php if (!empty($allLogFiles)): ?>
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 6px;">
                        <h4 style="margin: 0 0 12px 0; font-size: 16px; color: #333;">개별 로그 파일</h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 12px;">
                            <?php foreach ($allLogFiles as $logFile): 
                                $sizeMB = round($logFile['size'] / 1024 / 1024, 2);
                                $sizeKB = round($logFile['size'] / 1024, 2);
                                $sizeDisplay = $logFile['size'] > 1024 * 1024 ? $sizeMB . 'MB' : $sizeKB . 'KB';
                                $modifiedDate = date('Y-m-d H:i:s', $logFile['modified']);
                                
                                // 파일 경로를 base64로 인코딩하여 전달
                                $encodedPath = base64_encode($logFile['path']);
                            ?>
                            <div style="background: white; padding: 12px; border: 1px solid #dee2e6; border-radius: 6px;">
                                <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 8px;">
                                    <div>
                                        <strong style="font-size: 14px; color: #333;"><?php echo htmlspecialchars($logFile['name']); ?></strong>
                                        <div style="font-size: 12px; color: #666; margin-top: 4px;">
                                            <?php echo $sizeDisplay; ?> • <?php echo $modifiedDate; ?>
                                        </div>
                                    </div>
                                </div>
                                <a href="?action=download_log&file_path=<?php echo urlencode($encodedPath); ?>" 
                                   class="btn" 
                                   style="background: #28a745; color: white; text-decoration: none; display: inline-block; padding: 6px 12px; font-size: 13px; width: 100%; text-align: center;">
                                    ⬇️ 다운로드
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <div style="padding: 16px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 6px; color: #856404;">
                        다운로드할 로그 파일이 없습니다.
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h2>⚠️ 삭제 확인</h2>
            <button type="button" class="modal-close" onclick="closeDeleteModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="warning-box">
                <p><strong id="deleteType"></strong>를(을) 삭제하시겠습니까?</p>
                <p>삭제될 데이터: <strong id="deleteCount"></strong></p>
                <p style="color: #dc3545; font-weight: bold; margin-top: 15px;">
                    ⚠️ 이 작업은 되돌릴 수 없습니다!
                </p>
            </div>
            <form id="deleteForm" method="POST">
                <input type="hidden" name="action" id="deleteAction">
                <div class="form-group">
                    <label for="confirm_text">확인을 위해 <strong>"삭제"</strong>를 입력하세요:</label>
                    <input type="text" id="confirm_text" name="confirm_text" class="form-control" required>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">취소</button>
                    <button type="submit" class="btn btn-danger">삭제 실행</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.data-delete-container {
    max-width: 1200px;
    margin: 0 auto;
}

.delete-section {
    margin-bottom: 40px;
    padding: 24px;
    background: #fff;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.delete-section h2 {
    margin: 0 0 20px 0;
    font-size: 20px;
    font-weight: 600;
    color: #333;
    border-bottom: 2px solid #e9ecef;
    padding-bottom: 12px;
}

.delete-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.delete-card {
    padding: 20px;
    border: 1px solid #e9ecef;
    border-radius: 8px;
    background: #f8f9fa;
}

.delete-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.delete-card-header h3 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #333;
}

.count-badge {
    background: #dc3545;
    color: white;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 14px;
    font-weight: 600;
}

.delete-description {
    color: #666;
    font-size: 14px;
    margin-bottom: 16px;
    line-height: 1.5;
}

.btn {
    padding: 10px 20px;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #5a6268;
}

.btn-primary {
    background: #28a745;
    color: white;
}

.btn-primary:hover {
    background: #218838;
}

.modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}

.modal-content {
    background: white;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e9ecef;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    color: #dc3545;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #999;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.modal-close:hover {
    color: #333;
}

.modal-body {
    padding: 20px;
}

.warning-box {
    background: #fff3cd;
    border: 2px solid #ffc107;
    border-radius: 6px;
    padding: 16px;
    margin-bottom: 20px;
}

.warning-box p {
    margin: 8px 0;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: #333;
}

.form-control {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 14px;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 20px;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-error {
    background: #f8d7da;
    border: 1px solid #f5c6cb;
    color: #721c24;
}

.alert-success {
    background: #d4edda;
    border: 1px solid #c3e6cb;
    color: #155724;
}
</style>

<script>
function showDeleteModal(action, type, count) {
    document.getElementById('deleteAction').value = action;
    document.getElementById('deleteType').textContent = type;
    var unit = '건';
    if (type.includes('회원') || type.includes('관리자')) {
        unit = '명';
    } else if (type.includes('상품') || type.includes('파일')) {
        unit = '개';
    }
    document.getElementById('deleteCount').textContent = count + unit;
    document.getElementById('confirm_text').value = '';
    document.getElementById('deleteModal').style.display = 'flex';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    document.getElementById('deleteForm').reset();
}

// 모달 외부 클릭 시 닫기
document.getElementById('deleteModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});
</script>

<?php
require_once __DIR__ . '/../includes/admin-footer.php';
?>
























