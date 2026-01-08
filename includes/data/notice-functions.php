<?php
/**
 * 공지사항 관련 함수
 * DB 기반 데이터 저장
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/db-config.php';

// 공지사항 목록 가져오기 (발행 기간 내, 판매자 전용 제외)
function getNotices($limit = null, $offset = 0) {
    ensureShowOnMainColumn();
    
    $pdo = getDBConnection();
    if (!$pdo) return [];

    $sql = "SELECT * FROM notices 
            WHERE (target_audience IS NULL OR target_audience = 'all' OR target_audience = 'user')
            AND (start_at IS NULL OR start_at <= CURDATE())
            AND (end_at IS NULL OR end_at >= CURDATE())
            ORDER BY created_at DESC";
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 공지사항 상세 가져오기
function getNoticeById($id) {
    $pdo = getDBConnection();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM notices WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (string)$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

// 이미지 업로드 함수
function uploadNoticeImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // 파일 타입 확인
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }
    
    // 업로드 디렉토리 생성
    $uploadDir = __DIR__ . '/../../uploads/notices/';
    $year = date('Y');
    $month = date('m');
    $uploadPath = $uploadDir . $year . '/' . $month . '/';
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // 파일명 생성
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadPath . $fileName;
    
    // 파일 이동
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        require_once __DIR__ . '/path-config.php';
        return getUploadPath('/uploads/notices/' . $year . '/' . $month . '/' . $fileName);
    }
    
    return null;
}

// 판매자 전용 공지사항 이미지 업로드 함수
function uploadSellerNoticeImage($file) {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    
    // 파일 타입 확인
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($mimeType, $allowedTypes)) {
        return null;
    }
    
    // 파일 크기 제한 (10MB)
    if ($file['size'] > 10 * 1024 * 1024) {
        return null;
    }
    
    // 업로드 디렉토리 생성
    $uploadDir = __DIR__ . '/../../uploads/notices/seller/';
    $year = date('Y');
    $month = date('m');
    $uploadPath = $uploadDir . $year . '/' . $month . '/';
    
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }
    
    // 파일명 생성
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $fileName = date('YmdHis') . '_' . uniqid() . '.' . $extension;
    $filePath = $uploadPath . $fileName;
    
    // 파일 이동
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        require_once __DIR__ . '/path-config.php';
        return getUploadPath('/uploads/notices/seller/' . $year . '/' . $month . '/' . $fileName);
    }
    
    return null;
}

// 공지사항 생성 (관리자용)
function createNotice($title, $content, $show_on_main = false, $image_url = null, $link_url = null, $start_at = null, $end_at = null) {
    ensureShowOnMainColumn(); // 컬럼 확인 및 추가
    
    $notice = [
        'id' => uniqid('notice_'),
        'title' => $title,
        'content' => $content,
        'image_url' => $image_url,
        'link_url' => $link_url,
        'show_on_main' => $show_on_main,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'views' => 0
    ];

    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 메인공지로 설정하는 경우, 기존 메인공지 모두 취소
        if ($show_on_main) {
            $pdo->exec("UPDATE notices SET show_on_main = 0 WHERE show_on_main = 1");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notices (id, title, content, image_url, link_url, show_on_main, start_at, end_at, views, created_at, updated_at)
            VALUES (:id,:title,:content,:img_url,:link_url,:show_main,:start_at,:end_at,0,:ca,:ua)
        ");
        $stmt->execute([
            ':id' => $notice['id'],
            ':title' => $notice['title'],
            ':content' => $notice['content'],
            ':img_url' => $image_url,
            ':link_url' => $link_url,
            ':show_main' => $show_on_main ? 1 : 0,
            ':start_at' => $start_at ? $start_at : null,
            ':end_at' => $end_at ? $end_at : null,
            ':ca' => $notice['created_at'],
            ':ua' => $notice['updated_at'],
        ]);
        return $notice;
    } catch (PDOException $e) {
        // 컬럼이 없으면 다시 시도
        if (strpos($e->getMessage(), 'show_on_main') !== false || strpos($e->getMessage(), 'image_url') !== false || strpos($e->getMessage(), 'link_url') !== false) {
            ensureShowOnMainColumn();
            // 재시도
            try {
                // 메인공지로 설정하는 경우, 기존 메인공지 모두 취소
                if ($show_on_main) {
                    $pdo->exec("UPDATE notices SET show_on_main = 0 WHERE show_on_main = 1");
                }
                
                $stmt = $pdo->prepare("
                    INSERT INTO notices (id, title, content, image_url, link_url, show_on_main, views, created_at, updated_at)
                    VALUES (:id,:title,:content,:img_url,:link_url,:show_main,0,:ca,:ua)
                ");
                $stmt->execute([
                    ':id' => $notice['id'],
                    ':title' => $notice['title'],
                    ':content' => $notice['content'],
                    ':img_url' => $image_url,
                    ':link_url' => $link_url,
                    ':show_main' => $show_on_main ? 1 : 0,
                    ':ca' => $notice['created_at'],
                    ':ua' => $notice['updated_at'],
                ]);
                return $notice;
            } catch (PDOException $e2) {
                error_log('createNotice retry error: ' . $e2->getMessage());
                return false;
            }
        }
        error_log('createNotice error: ' . $e->getMessage());
        return false;
    }
}

// 필요한 컬럼들 존재 여부 확인 및 추가
function ensureShowOnMainColumn() {
    static $checked = false;
    if ($checked) return true;
    
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 현재 컬럼 목록 가져오기
        $stmt = $pdo->query("SHOW COLUMNS FROM notices");
        $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        $existingColumns = array_flip($columns);
        
        // show_on_main 컬럼 확인 및 추가
        if (!isset($existingColumns['show_on_main'])) {
            $pdo->exec("ALTER TABLE notices ADD COLUMN show_on_main TINYINT(1) NOT NULL DEFAULT 0 COMMENT '메인페이지 새창 표시 여부' AFTER content");
        }
        
        // image_url 컬럼 확인 및 추가
        if (!isset($existingColumns['image_url'])) {
            $pdo->exec("ALTER TABLE notices ADD COLUMN image_url VARCHAR(500) DEFAULT NULL COMMENT '공지사항 이미지 URL' AFTER content");
        }
        
        // link_url 컬럼 확인 및 추가
        if (!isset($existingColumns['link_url'])) {
            $pdo->exec("ALTER TABLE notices ADD COLUMN link_url VARCHAR(500) DEFAULT NULL COMMENT '공지사항 링크 URL' AFTER image_url");
        }
        
        // start_at 컬럼 확인 및 추가
        if (!isset($existingColumns['start_at'])) {
            $pdo->exec("ALTER TABLE notices ADD COLUMN start_at DATE DEFAULT NULL COMMENT '메인공지 시작일' AFTER show_on_main");
        }
        
        // end_at 컬럼 확인 및 추가
        if (!isset($existingColumns['end_at'])) {
            $pdo->exec("ALTER TABLE notices ADD COLUMN end_at DATE DEFAULT NULL COMMENT '메인공지 종료일' AFTER start_at");
        }
        
        // target_audience 컬럼 확인 및 추가
        if (!isset($existingColumns['target_audience'])) {
            $pdo->exec("ALTER TABLE notices ADD COLUMN target_audience ENUM('all', 'seller', 'user') DEFAULT 'all' COMMENT '대상 사용자 (all: 전체, seller: 판매자만, user: 일반 사용자만)' AFTER show_on_main");
        }
        
        // banner_type 컬럼 확인 및 추가
        if (!isset($existingColumns['banner_type'])) {
            $pdo->exec("ALTER TABLE notices ADD COLUMN banner_type ENUM('text', 'image', 'both') DEFAULT 'text' COMMENT '배너 타입 (text: 텍스트만, image: 이미지만, both: 둘 다)' AFTER image_url");
        }
        
        
        $checked = true;
        return true;
    } catch (PDOException $e) {
        error_log('ensureShowOnMainColumn error: ' . $e->getMessage());
        return false;
    }
}

// 공지사항 수정 (관리자용)
function updateNotice($id, $title, $content, $show_on_main = false, $image_url = null, $link_url = null, $start_at = null, $end_at = null) {
    ensureShowOnMainColumn(); // 컬럼 확인 및 추가
    
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 메인공지로 설정하는 경우, 기존 메인공지 모두 취소 (현재 공지사항 제외)
        if ($show_on_main) {
            $pdo->exec("UPDATE notices SET show_on_main = 0 WHERE show_on_main = 1 AND id != " . $pdo->quote((string)$id));
        }
        
        // image_url과 link_url이 null이면 기존 값 유지
        $sql = "
            UPDATE notices
            SET title = :title,
                content = :content,
                show_on_main = :show_main";
        
        $params = [
            ':id' => (string)$id,
            ':title' => (string)$title,
            ':content' => (string)$content,
            ':show_main' => $show_on_main ? 1 : 0,
        ];
        
        // image_url이 null이 아니고 빈 문자열이 아니면 업데이트
        if ($image_url !== null && $image_url !== '') {
            $sql .= ", image_url = :img_url";
            $params[':img_url'] = $image_url;
        }
        
        // link_url이 null이 아니고 빈 문자열이 아니면 업데이트
        if ($link_url !== null && $link_url !== '') {
            $sql .= ", link_url = :link_url";
            $params[':link_url'] = $link_url;
        } elseif ($link_url === '') {
            // 빈 문자열이면 NULL로 설정
            $sql .= ", link_url = NULL";
        }
        
        // start_at 처리
        if ($start_at !== null) {
            $sql .= ", start_at = :start_at";
            $params[':start_at'] = $start_at ? $start_at : null;
        } elseif ($start_at === '') {
            $sql .= ", start_at = NULL";
        }
        
        // end_at 처리
        if ($end_at !== null) {
            $sql .= ", end_at = :end_at";
            $params[':end_at'] = $end_at ? $end_at : null;
        } elseif ($end_at === '') {
            $sql .= ", end_at = NULL";
        }
        
        $sql .= ", updated_at = NOW() WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        // 컬럼이 없으면 다시 시도
        if (strpos($e->getMessage(), 'show_on_main') !== false || strpos($e->getMessage(), 'image_url') !== false || strpos($e->getMessage(), 'link_url') !== false) {
            ensureShowOnMainColumn();
            // 재시도
            try {
                // 메인공지로 설정하는 경우, 기존 메인공지 모두 취소 (현재 공지사항 제외)
                if ($show_on_main) {
                    $pdo->exec("UPDATE notices SET show_on_main = 0 WHERE show_on_main = 1 AND id != " . $pdo->quote((string)$id));
                }
                
                $sql = "
                    UPDATE notices
                    SET title = :title,
                        content = :content,
                        show_on_main = :show_main";
                
                $params = [
                    ':id' => (string)$id,
                    ':title' => (string)$title,
                    ':content' => (string)$content,
                    ':show_main' => $show_on_main ? 1 : 0,
                ];
                
                if ($image_url !== null) {
                    $sql .= ", image_url = :img_url";
                    $params[':img_url'] = $image_url;
                }
                
                if ($link_url !== null) {
                    $sql .= ", link_url = :link_url";
                    $params[':link_url'] = $link_url;
                }
                
                if ($start_at !== null) {
                    $sql .= ", start_at = :start_at";
                    $params[':start_at'] = $start_at ? $start_at : null;
                }
                
                if ($end_at !== null) {
                    $sql .= ", end_at = :end_at";
                    $params[':end_at'] = $end_at ? $end_at : null;
                }
                
                $sql .= ", updated_at = NOW() WHERE id = :id";
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                return $stmt->rowCount() > 0;
            } catch (PDOException $e2) {
                error_log('updateNotice retry error: ' . $e2->getMessage());
                return false;
            }
        }
        error_log('updateNotice error: ' . $e->getMessage());
        return false;
    }
}

// 공지사항 삭제 (관리자용) - 이미지 파일도 함께 삭제
function deleteNotice($id) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    // 삭제 전에 이미지 URL 가져오기
    $notice = getNoticeById($id);
    if ($notice && !empty($notice['image_url'])) {
        deleteNoticeImageFile($notice['image_url']);
    }
    
    $stmt = $pdo->prepare("DELETE FROM notices WHERE id = :id");
    $stmt->execute([':id' => (string)$id]);
    return $stmt->rowCount() > 0;
}

// 이미지 파일 삭제 함수
function deleteNoticeImageFile($image_url) {
    if (empty($image_url)) return false;
    
    // 경로를 실제 파일 경로로 변환
    require_once __DIR__ . '/path-config.php';
    $basePath = getBasePath();
    if ($basePath && strpos($image_url, $basePath) === 0) {
        $filePath = str_replace($basePath, __DIR__ . '/../../', $image_url);
    } elseif (strpos($image_url, '/MVNO/') === 0) {
        $filePath = str_replace('/MVNO/', __DIR__ . '/../../', $image_url);
    } elseif (strpos($image_url, '/uploads/') === 0) {
        // 프로덕션 루트 경로 처리
        $filePath = __DIR__ . '/../../' . $image_url;
    } else {
        $filePath = __DIR__ . '/../../' . ltrim($image_url, '/');
    }
    
    // 파일이 존재하면 삭제
    if (file_exists($filePath)) {
        return unlink($filePath);
    }
    
    return false;
}

// 조회수 증가
function incrementNoticeViews($id) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("UPDATE notices SET views = views + 1 WHERE id = :id");
    $stmt->execute([':id' => (string)$id]);
    return $stmt->rowCount() > 0;
}

// 모든 공지사항 가져오기 (관리자용, 비공개 포함)
function getAllNoticesForAdmin() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    $stmt = $pdo->query("SELECT * FROM notices ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 메인페이지에 표시할 공지사항 가져오기 (show_on_main = 1이고 발행된 것만, 기간 내)
// 일반회원용 공지사항만 가져옴 (판매자 전용 제외)
function getMainPageNotice() {
    ensureShowOnMainColumn(); // 컬럼 확인 및 추가
    
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log('getMainPageNotice: DB 연결 실패');
        return null;
    }
    
    try {
        // 현재 날짜 가져오기 (한국 시간 기준)
        $currentDate = date('Y-m-d');
        
        // show_on_main이 1인 모든 공지사항 먼저 확인 (디버깅용, 판매자 전용 제외)
        $debugStmt = $pdo->query("SELECT id, title, show_on_main, start_at, end_at, image_url, target_audience FROM notices WHERE (show_on_main = 1 OR show_on_main = '1') AND (target_audience IS NULL OR target_audience = 'all' OR target_audience = 'user') ORDER BY created_at DESC");
        $debugNotices = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 단계별로 조건을 확인하여 쿼리 실행
        // 1단계: show_on_main = 1인 공지사항 가져오기 (판매자 전용 제외)
        // 일반회원용 메인공지는 target_audience가 'all', 'user', 또는 NULL인 것만 가져옴
        $sql = "SELECT * FROM notices 
                WHERE (show_on_main = 1 OR show_on_main = '1' OR CAST(show_on_main AS UNSIGNED) = 1)
                AND (target_audience IS NULL OR target_audience = 'all' OR target_audience = 'user')
                ORDER BY created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $allMainNotices = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 2단계: PHP에서 날짜와 이미지 조건 필터링
        $row = null;
        foreach ($allMainNotices as $notice) {
            $startAt = $notice['start_at'] ?? null;
            $endAt = $notice['end_at'] ?? null;
            $imageUrl = $notice['image_url'] ?? null;
            $targetAudience = $notice['target_audience'] ?? null;
            
            // 판매자 전용 공지 제외 (추가 안전 장치)
            if ($targetAudience === 'seller') {
                continue;
            }
            
            // 날짜 조건 체크
            $dateOk = true;
            if ($startAt && $startAt > $currentDate) {
                $dateOk = false;
            }
            if ($endAt && $endAt < $currentDate) {
                $dateOk = false;
            }
            
            // 이미지 조건 체크
            $imageOk = !empty($imageUrl) && $imageUrl !== null && $imageUrl !== '';
            
            // 모든 조건 만족하면 선택
            if ($dateOk && $imageOk) {
                $row = $notice;
                break; // 가장 최근 것 선택
            }
        }
        
        // 디버깅: 쿼리 결과 로깅
        if (isset($_GET['debug_notice']) && $_GET['debug_notice'] == '1') {
            error_log('getMainPageNotice: 현재 날짜 = ' . $currentDate);
            error_log('getMainPageNotice: show_on_main=1인 공지사항 수 (판매자 제외) = ' . count($allMainNotices));
            foreach ($allMainNotices as $debugNotice) {
                $startAt = $debugNotice['start_at'] ?? null;
                $endAt = $debugNotice['end_at'] ?? null;
                $imageUrl = $debugNotice['image_url'] ?? null;
                
                $dateOk = true;
                if ($startAt && $startAt > $currentDate) {
                    $dateOk = false;
                }
                if ($endAt && $endAt < $currentDate) {
                    $dateOk = false;
                }
                $imageOk = !empty($imageUrl) && $imageUrl !== null && $imageUrl !== '';
                
                $targetAudience = $debugNotice['target_audience'] ?? 'NULL';
                error_log('getMainPageNotice: 공지사항 - ID=' . ($debugNotice['id'] ?? 'N/A') . 
                         ', 제목=' . ($debugNotice['title'] ?? 'N/A') . 
                         ', show_on_main=' . ($debugNotice['show_on_main'] ?? 'N/A') . 
                         ', target_audience=' . $targetAudience .
                         ', start_at=' . ($startAt ?: 'NULL') . 
                         ', end_at=' . ($endAt ?: 'NULL') . 
                         ', image_url=' . ($imageOk ? '있음' : '없음') .
                         ', 날짜조건=' . ($dateOk ? 'OK' : 'FAIL') .
                         ', 이미지조건=' . ($imageOk ? 'OK' : 'FAIL'));
            }
            error_log('getMainPageNotice: 최종 결과 = ' . ($row ? '있음 (ID: ' . ($row['id'] ?? 'N/A') . ')' : '없음'));
            if ($row) {
                error_log('getMainPageNotice: 선택된 공지사항 - target_audience=' . ($row['target_audience'] ?? 'NULL') .
                         ', show_on_main=' . ($row['show_on_main'] ?? 'N/A') . 
                         ', start_at=' . ($row['start_at'] ?? 'NULL') . 
                         ', end_at=' . ($row['end_at'] ?? 'NULL') . 
                         ', image_url=' . (!empty($row['image_url']) ? '있음' : '없음'));
            } else {
                error_log('getMainPageNotice: 조건을 만족하는 공지사항이 없습니다.');
            }
        }
        
        return $row ?: null;
    } catch (PDOException $e) {
        // 컬럼이 없으면 null 반환
        if (strpos($e->getMessage(), 'show_on_main') !== false || strpos($e->getMessage(), 'start_at') !== false || strpos($e->getMessage(), 'end_at') !== false || strpos($e->getMessage(), 'target_audience') !== false) {
            error_log('getMainPageNotice: 컬럼 누락 - ' . $e->getMessage());
            return null;
        }
        error_log('getMainPageNotice error: ' . $e->getMessage());
        return null;
    }
}

// 판매자 전용 메인공지 가져오기
function getSellerMainBanner() {
    ensureShowOnMainColumn();
    
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log('getSellerMainBanner: DB 연결 실패');
        return null;
    }
    
    try {
        $currentDate = date('Y-m-d');
        
        // 디버깅: 쿼리 실행 전 상태 확인
        $debugMode = isset($_GET['debug_banner']);
        
        if ($debugMode) {
            // 모든 판매자 공지사항 확인
            $allNotices = $pdo->query("SELECT id, title, target_audience, show_on_main, start_at, end_at, banner_type FROM notices WHERE target_audience = 'seller' ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
            error_log('getSellerMainBanner: 판매자 공지사항 총 개수 = ' . count($allNotices));
            foreach ($allNotices as $notice) {
                $showOnMain = $notice['show_on_main'] ?? 0;
                $startAt = $notice['start_at'] ?? null;
                $endAt = $notice['end_at'] ?? null;
                $dateOk = true;
                if ($startAt && $startAt > $currentDate) $dateOk = false;
                if ($endAt && $endAt < $currentDate) $dateOk = false;
                error_log('  공지사항: ' . ($notice['title'] ?? 'N/A') . 
                         ' | show_on_main=' . $showOnMain . 
                         ' | start_at=' . ($startAt ?: 'NULL') . 
                         ' | end_at=' . ($endAt ?: 'NULL') . 
                         ' | 날짜조건=' . ($dateOk ? 'OK' : 'FAIL'));
            }
        }
        
        // 쿼리 수정: 파라미터 바인딩 문제 해결
        // 쿼리 수정: 파라미터 바인딩 문제 해결을 위해 직접 값 사용
        $sql = "SELECT * FROM notices 
                WHERE target_audience = 'seller'
                AND (show_on_main = 1 OR CAST(show_on_main AS UNSIGNED) = 1)
                AND (start_at IS NULL OR start_at <= " . $pdo->quote($currentDate) . ")
                AND (end_at IS NULL OR end_at >= " . $pdo->quote($currentDate) . ")
                ORDER BY created_at DESC
                LIMIT 1";
        
        $stmt = $pdo->query($sql);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($debugMode) {
            error_log('getSellerMainBanner: 쿼리 결과 = ' . ($row ? '있음 (ID: ' . ($row['id'] ?? 'N/A') . ')' : '없음'));
            if (!$row) {
                // 왜 조회되지 않는지 확인
                $testStmt = $pdo->query("SELECT id, title, target_audience, show_on_main, start_at, end_at FROM notices WHERE target_audience = 'seller' AND (show_on_main = 1 OR CAST(show_on_main AS UNSIGNED) = 1) ORDER BY created_at DESC LIMIT 5");
                $testResults = $testStmt->fetchAll(PDO::FETCH_ASSOC);
                error_log('getSellerMainBanner: show_on_main=1인 공지사항 수 = ' . count($testResults));
                foreach ($testResults as $test) {
                    $startAt = $test['start_at'] ?? null;
                    $endAt = $test['end_at'] ?? null;
                    $dateOk = true;
                    if ($startAt && $startAt > $currentDate) $dateOk = false;
                    if ($endAt && $endAt < $currentDate) $dateOk = false;
                    error_log('  테스트: ' . ($test['title'] ?? 'N/A') . ' | 날짜조건=' . ($dateOk ? 'OK' : 'FAIL'));
                }
            }
        }
        
        return $row ?: null;
    } catch (PDOException $e) {
        error_log('getSellerMainBanner error: ' . $e->getMessage());
        if (isset($_GET['debug_banner'])) {
            error_log('getSellerMainBanner error trace: ' . $e->getTraceAsString());
        }
        return null;
    }
}

// 판매자 전용 공지사항 목록 가져오기 (관리자용, 페이지네이션 지원)
function getSellerNoticesForAdmin($limit = null, $offset = 0) {
    ensureShowOnMainColumn();
    
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $sql = "SELECT * FROM notices 
            WHERE target_audience = 'seller'
            ORDER BY created_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    
    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 판매자 전용 공지사항 목록 가져오기 (판매자용, 모든 공지사항 표시)
// 참고: start_at, end_at은 메인 배너 표시 기간이며, 리스트 표시와는 무관
function getSellerNotices($limit = null, $offset = 0) {
    ensureShowOnMainColumn();
    
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    $sql = "SELECT * FROM notices 
            WHERE target_audience = 'seller'
            ORDER BY created_at DESC";
    
    if ($limit !== null) {
        $sql .= " LIMIT :limit OFFSET :offset";
    }
    
    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 판매자 전용 공지사항 총 개수 가져오기
function getSellerNoticesCount() {
    ensureShowOnMainColumn();
    
    $pdo = getDBConnection();
    if (!$pdo) return 0;
    
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM notices WHERE target_audience = 'seller'");
        return (int)$stmt->fetchColumn();
    } catch (PDOException $e) {
        error_log('getSellerNoticesCount error: ' . $e->getMessage());
        return 0;
    }
}

// 판매자 전용 공지사항 생성
function createSellerNotice($title, $content, $banner_type = 'text', $image_url = null, $link_url = null, $show_on_main = false, $start_at = null, $end_at = null) {
    ensureShowOnMainColumn();
    
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 메인배너로 설정하는 경우, 기존 메인배너 모두 취소
        if ($show_on_main) {
            $pdo->exec("UPDATE notices SET show_on_main = 0 WHERE target_audience = 'seller' AND show_on_main = 1");
        }
        
        $id = uniqid('notice_');
        $created_at = date('Y-m-d H:i:s');
        $updated_at = date('Y-m-d H:i:s');
        
        $stmt = $pdo->prepare("
            INSERT INTO notices (id, title, content, banner_type, image_url, link_url, target_audience, show_on_main, start_at, end_at, views, created_at, updated_at)
            VALUES (:id, :title, :content, :banner_type, :img_url, :link_url, 'seller', :show_main, :start_at, :end_at, 0, :ca, :ua)
        ");
        
        $stmt->execute([
            ':id' => $id,
            ':title' => $title,
            ':content' => $content,
            ':banner_type' => $banner_type,
            ':img_url' => $image_url,
            ':link_url' => $link_url,
            ':show_main' => $show_on_main ? 1 : 0,
            ':start_at' => $start_at ? $start_at : null,
            ':end_at' => $end_at ? $end_at : null,
            ':ca' => $created_at,
            ':ua' => $updated_at,
        ]);
        
        return ['id' => $id, 'created_at' => $created_at];
    } catch (PDOException $e) {
        error_log('createSellerNotice error: ' . $e->getMessage());
        return false;
    }
}

// 판매자 전용 공지사항 수정
function updateSellerNotice($id, $title, $content, $banner_type = 'text', $image_url = null, $link_url = null, $show_on_main = false, $start_at = null, $end_at = null) {
    ensureShowOnMainColumn();
    
    $pdo = getDBConnection();
    if (!$pdo) return false;
    
    try {
        // 메인배너로 설정하는 경우, 기존 메인배너 모두 취소 (현재 공지사항 제외)
        if ($show_on_main) {
            $pdo->exec("UPDATE notices SET show_on_main = 0 WHERE target_audience = 'seller' AND show_on_main = 1 AND id != " . $pdo->quote((string)$id));
        }
        
        // 기존 이미지 URL 가져오기 (이미지 변경 시 기존 이미지 삭제용)
        $oldNotice = getNoticeById($id);
        $oldImageUrl = $oldNotice['image_url'] ?? null;
        
        // 새 이미지가 업로드되었고 기존 이미지와 다르면 기존 이미지 삭제
        if ($image_url && $oldImageUrl && $image_url !== $oldImageUrl) {
            deleteNoticeImageFile($oldImageUrl);
        }
        
        $sql = "UPDATE notices SET 
                title = :title,
                content = :content,
                banner_type = :banner_type,
                show_on_main = :show_main";
        
        $params = [
            ':id' => (string)$id,
            ':title' => $title,
            ':content' => $content,
            ':banner_type' => $banner_type,
            ':show_main' => $show_on_main ? 1 : 0,
        ];
        
        if ($image_url !== null) {
            $sql .= ", image_url = :img_url";
            $params[':img_url'] = $image_url;
        }
        
        if ($link_url !== null) {
            $sql .= ", link_url = :link_url";
            $params[':link_url'] = $link_url;
        } elseif ($link_url === '') {
            $sql .= ", link_url = NULL";
        }
        
        if ($start_at !== null) {
            $sql .= ", start_at = :start_at";
            $params[':start_at'] = $start_at ? $start_at : null;
        } elseif ($start_at === '') {
            $sql .= ", start_at = NULL";
        }
        
        if ($end_at !== null) {
            $sql .= ", end_at = :end_at";
            $params[':end_at'] = $end_at ? $end_at : null;
        } elseif ($end_at === '') {
            $sql .= ", end_at = NULL";
        }
        
        $sql .= ", updated_at = NOW() WHERE id = :id";
        $params[':id'] = (string)$id;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log('updateSellerNotice error: ' . $e->getMessage());
        return false;
    }
}






