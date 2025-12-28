<?php
/**
 * 이벤트 등록 페이지
 * 경로: /MVNO/admin/content/event-register.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';
require_once __DIR__ . '/../../includes/data/db-config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = getCurrentUser();
if (!$currentUser || !isAdmin($currentUser['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$success = '';
$pdo = getDBConnection();

// 이벤트 등록 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_event') {
    $title = trim($_POST['title'] ?? '');
    $event_type = $_POST['event_type'] ?? 'promotion';
    $start_at = !empty($_POST['start_at']) ? $_POST['start_at'] : null;
    $end_at = !empty($_POST['end_at']) ? $_POST['end_at'] : null;
    $description = trim($_POST['description'] ?? '');
    
    // 유효성 검사
    if (empty($title)) {
        $error = '이벤트 제목을 입력해주세요.';
    } elseif (!in_array($event_type, ['plan', 'promotion', 'card'])) {
        $error = '올바른 이벤트 타입을 선택해주세요.';
    } elseif ($start_at && $end_at && strtotime($start_at) > strtotime($end_at)) {
        $error = '시작일은 종료일보다 이전이어야 합니다.';
    }
    
    if (!$error && $pdo) {
        try {
            // events 테이블에 필요한 컬럼이 있는지 확인하고 없으면 추가
            ensureEventsTableColumns($pdo);
            
            $pdo->beginTransaction();
            
            // 이벤트 ID 생성 (타임스탬프 기반)
            $eventId = 'evt_' . time() . '_' . bin2hex(random_bytes(4));
            
            // 메인 이미지 업로드 처리
            $main_image = null;
            if (isset($_FILES['main_image']) && $_FILES['main_image']['error'] === UPLOAD_ERR_OK) {
                $main_image = uploadEventImage($_FILES['main_image'], $eventId, 'main', true); // true = 16:9 비율 강제
                if (!$main_image) {
                    throw new Exception('메인 이미지 업로드에 실패했습니다.');
                }
            }
            
            // 이벤트 기본 정보 저장
            // events 테이블의 실제 컬럼 확인 후 INSERT
            $stmt = $pdo->query("SHOW COLUMNS FROM events");
            $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // 사용 가능한 컬럼만 포함하여 INSERT 쿼리 구성
            $insertColumns = [];
            $insertValues = [];
            $params = [];
            
            // 필수 컬럼
            $insertColumns[] = 'id';
            $insertValues[] = ':id';
            $params[':id'] = $eventId;
            
            $insertColumns[] = 'title';
            $insertValues[] = ':title';
            $params[':title'] = $title;
            
            // 선택적 컬럼들
            if (in_array('event_type', $columns)) {
                $insertColumns[] = 'event_type';
                $insertValues[] = ':event_type';
                $params[':event_type'] = $event_type;
            }
            
            // category 컬럼 처리 (기본값 'all' 사용)
            if (in_array('category', $columns)) {
                $insertColumns[] = 'category';
                $insertValues[] = ':category';
                $params[':category'] = 'all'; // 기본값
            }
            
            // link_url 컬럼 처리 (기본값 NULL)
            if (in_array('link_url', $columns)) {
                $insertColumns[] = 'link_url';
                $insertValues[] = ':link_url';
                $params[':link_url'] = null;
            }
            
            if (in_array('main_image', $columns)) {
                $insertColumns[] = 'main_image';
                $insertValues[] = ':main_image';
                $params[':main_image'] = $main_image ?: null;
            } elseif (in_array('image_url', $columns)) {
                // main_image 컬럼이 없으면 image_url에 저장
                $insertColumns[] = 'image_url';
                $insertValues[] = ':image_url';
                $params[':image_url'] = $main_image ?: null;
            }
            
            if (in_array('description', $columns)) {
                $insertColumns[] = 'description';
                $insertValues[] = ':description';
                $params[':description'] = $description;
            }
            
            if (in_array('start_at', $columns)) {
                $insertColumns[] = 'start_at';
                $insertValues[] = ':start_at';
                $params[':start_at'] = $start_at ?: null;
            }
            
            if (in_array('end_at', $columns)) {
                $insertColumns[] = 'end_at';
                $insertValues[] = ':end_at';
                $params[':end_at'] = $end_at ?: null;
            }
            
            // created_at와 updated_at는 파라미터로 바인딩 (NOW() 함수 사용 시 PDO 오류 발생 가능)
            $currentTimestamp = date('Y-m-d H:i:s');
            if (in_array('created_at', $columns)) {
                $insertColumns[] = 'created_at';
                $insertValues[] = ':created_at';
                $params[':created_at'] = $currentTimestamp;
            }
            
            if (in_array('updated_at', $columns)) {
                $insertColumns[] = 'updated_at';
                $insertValues[] = ':updated_at';
                $params[':updated_at'] = $currentTimestamp;
            }
            
            // SQL 쿼리 생성 및 실행
            if (empty($insertColumns)) {
                throw new Exception('저장할 컬럼이 없습니다.');
            }
            
            // SQL 쿼리 생성 - 플레이스홀더를 명시적으로 순서대로 배치
            $sql = "INSERT INTO events (" . implode(', ', $insertColumns) . ") VALUES (" . implode(', ', $insertValues) . ")";
            
            // SQL에서 실제 플레이스홀더 추출 및 검증
            preg_match_all('/:(\w+)/', $sql, $sqlPlaceholders);
            $sqlPlaceholderList = $sqlPlaceholders[0]; // :id, :title 등
            
            // 디버깅: SQL과 파라미터 로그 (상세) - 파일에 직접 기록
            $debugLogFile = __DIR__ . '/../../logs/event_debug.log';
            $debugDir = dirname($debugLogFile);
            if (!is_dir($debugDir)) {
                @mkdir($debugDir, 0755, true);
            }
            
            $debugInfo = [
                'timestamp' => date('Y-m-d H:i:s'),
                'SQL' => $sql,
                'Columns' => $insertColumns,
                'Values' => $insertValues,
                'Params' => $params,
                'ParamCount' => count($params),
                'PlaceholderCount' => substr_count($sql, ':'),
                'ColumnCount' => count($insertColumns),
                'ValueCount' => count($insertValues)
            ];
            
            $debugLog = "=== Event INSERT Debug Info ===\n";
            $debugLog .= "Time: " . date('Y-m-d H:i:s') . "\n";
            $debugLog .= "SQL: " . $sql . "\n";
            $debugLog .= "Columns (" . count($insertColumns) . "): " . implode(', ', $insertColumns) . "\n";
            $debugLog .= "Values (" . count($insertValues) . "): " . implode(', ', $insertValues) . "\n";
            $debugLog .= "Params (" . count($params) . "): " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
            $debugLog .= "Placeholder count: " . substr_count($sql, ':') . "\n";
            $debugLog .= "==============================\n\n";
            
            @file_put_contents($debugLogFile, $debugLog, FILE_APPEND | LOCK_EX);
            error_log($debugLog);
            
            // VALUES의 플레이스홀더 개수와 파라미터 개수 확인
            preg_match_all('/:(\w+)/', $sql, $matches);
            $placeholdersInSql = $matches[0]; // :id, :title 등
            $placeholderCount = count($placeholdersInSql);
            $paramCount = count($params);
            
            if ($placeholderCount !== $paramCount) {
                $errorMsg = "SQL 파라미터 개수가 일치하지 않습니다.\n";
                $errorMsg .= "플레이스홀더: {$placeholderCount} (" . implode(', ', $placeholdersInSql) . ")\n";
                $errorMsg .= "파라미터: {$paramCount} (" . implode(', ', array_keys($params)) . ")\n";
                $errorMsg .= "SQL: {$sql}\n";
                $errorMsg .= "Params: " . json_encode($params, JSON_UNESCAPED_UNICODE);
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            // 각 플레이스홀더가 파라미터에 존재하는지 확인 (null 값도 허용하므로 array_key_exists 사용)
            $missingParams = [];
            foreach ($placeholdersInSql as $placeholder) {
                if (!array_key_exists($placeholder, $params)) {
                    $missingParams[] = $placeholder;
                }
            }
            if (!empty($missingParams)) {
                $errorMsg = "누락된 파라미터: " . implode(', ', $missingParams);
                error_log($errorMsg);
                throw new Exception($errorMsg);
            }
            
            // 파라미터에 있지만 SQL에 없는 것 확인
            $extraParams = [];
            foreach (array_keys($params) as $paramKey) {
                if (!in_array($paramKey, $placeholdersInSql)) {
                    $extraParams[] = $paramKey;
                }
            }
            if (!empty($extraParams)) {
                error_log("SQL에 없는 파라미터 (무시됨): " . implode(', ', $extraParams));
                // SQL에 없는 파라미터는 제거
                foreach ($extraParams as $extra) {
                    unset($params[$extra]);
                }
            }
            
            // boundParams를 try 블록 밖에서 초기화 (catch 블록에서 접근 가능하도록)
            $boundParams = [];
            
            try {
                // SQL에 있는 플레이스홀더만 포함하는 파라미터 배열 생성 (순서 보장)
                // 빈 문자열은 NULL로 변환 (데이터베이스 호환성)
                foreach ($placeholdersInSql as $placeholder) {
                    if (array_key_exists($placeholder, $params)) {
                        $value = $params[$placeholder];
                        // 빈 문자열을 NULL로 변환 (description 등)
                        if ($value === '') {
                            $boundParams[$placeholder] = null;
                        } else {
                            $boundParams[$placeholder] = $value;
                        }
                    } else {
                        // 플레이스홀더가 있지만 파라미터가 없으면 NULL로 설정
                        $boundParams[$placeholder] = null;
                    }
                }
                
                // 디버깅: 바인딩할 파라미터 확인
                $debugLogFile = __DIR__ . '/../../logs/event_debug.log';
                $debugDir = dirname($debugLogFile);
                if (!is_dir($debugDir)) {
                    @mkdir($debugDir, 0755, true);
                }
                $bindLog = "=== Parameter Binding Debug ===\n";
                $bindLog .= "SQL: " . $sql . "\n";
                $bindLog .= "SQL Placeholders (" . count($placeholdersInSql) . "): " . implode(', ', $placeholdersInSql) . "\n";
                $bindLog .= "Original Params Keys (" . count($params) . "): " . implode(', ', array_keys($params)) . "\n";
                $bindLog .= "Bound Params Keys (" . count($boundParams) . "): " . implode(', ', array_keys($boundParams)) . "\n";
                $bindLog .= "Bound Params: " . json_encode($boundParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
                
                // 각 플레이스홀더가 boundParams에 있는지 확인 (null 값도 허용)
                $missingInBound = [];
                foreach ($placeholdersInSql as $ph) {
                    if (!array_key_exists($ph, $boundParams)) {
                        $missingInBound[] = $ph;
                    }
                }
                if (!empty($missingInBound)) {
                    $bindLog .= "WARNING: Missing in boundParams: " . implode(', ', $missingInBound) . "\n";
                }
                $bindLog .= "================================\n\n";
                @file_put_contents($debugLogFile, $bindLog, FILE_APPEND | LOCK_EX);
                error_log($bindLog);
                
                // PDO prepare 및 execute - 배열을 직접 전달
                $stmt = $pdo->prepare($sql);
                $stmt->execute($boundParams);
            } catch (PDOException $e) {
                $errorDetails = [
                    'message' => $e->getMessage(),
                    'code' => $e->getCode(),
                    'sql' => $sql,
                    'params' => $params,
                    'param_count' => count($params),
                    'placeholder_count' => substr_count($sql, ':'),
                    'columns' => $insertColumns,
                    'values' => $insertValues
                ];
                
                // 디버깅 로그 파일에 기록
                $debugLogFile = __DIR__ . '/../../logs/event_debug.log';
                $debugDir = dirname($debugLogFile);
                if (!is_dir($debugDir)) {
                    @mkdir($debugDir, 0755, true);
                }
                
                $errorLog = "=== PDO Exception Details ===\n";
                $errorLog .= "Time: " . date('Y-m-d H:i:s') . "\n";
                $errorLog .= "Message: " . $e->getMessage() . "\n";
                $errorLog .= "Code: " . $e->getCode() . "\n";
                $errorLog .= "SQL: " . $sql . "\n";
                $errorLog .= "Original Params: " . json_encode($params, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
                if (isset($boundParams)) {
                    $errorLog .= "Bound Params: " . json_encode($boundParams, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
                }
                $errorLog .= "Param Count: " . count($params) . "\n";
                $errorLog .= "Placeholder Count: " . substr_count($sql, ':') . "\n";
                $errorLog .= "Columns: " . implode(', ', $insertColumns) . "\n";
                $errorLog .= "Values: " . implode(', ', $insertValues) . "\n";
                $errorLog .= "============================\n\n";
                
                @file_put_contents($debugLogFile, $errorLog, FILE_APPEND | LOCK_EX);
                error_log($errorLog);
                
                // 사용자에게 보여줄 에러 메시지 생성 (디버깅 정보 포함)
                $userErrorMsg = '이벤트 등록 중 오류가 발생했습니다: ' . $e->getMessage();
                if ($e->getCode() == 'HY093') {
                    $userErrorMsg .= "\n\n[디버깅 정보]\n";
                    $userErrorMsg .= "파라미터 개수: " . count($params) . "\n";
                    $userErrorMsg .= "플레이스홀더 개수: " . substr_count($sql, ':') . "\n";
                    $userErrorMsg .= "컬럼 개수: " . count($insertColumns) . "\n";
                    $userErrorMsg .= "값 개수: " . count($insertValues) . "\n";
                    $userErrorMsg .= "\n컬럼: " . implode(', ', $insertColumns) . "\n";
                    $userErrorMsg .= "값: " . implode(', ', $insertValues) . "\n";
                    $userErrorMsg .= "파라미터 키: " . implode(', ', array_keys($params)) . "\n";
                    $userErrorMsg .= "\n자세한 내용은 logs/event_debug.log 파일을 확인하세요.";
                }
                
                throw new Exception($userErrorMsg);
            }
            
            // 상세 이미지 업로드 처리
            if (isset($_FILES['detail_images']) && is_array($_FILES['detail_images']['name'])) {
                $detailImages = $_FILES['detail_images'];
                
                // 순서 정보 가져오기 (사용자가 드래그로 변경한 순서)
                $orderData = [];
                if (!empty($_POST['detail_images_data'])) {
                    $orderDataJson = json_decode($_POST['detail_images_data'], true);
                    if (is_array($orderDataJson)) {
                        // 파일명과 크기로 매칭하여 순서 정보 생성
                        foreach ($orderDataJson as $orderItem) {
                            $orderData[$orderItem['name'] . '_' . $orderItem['size']] = $orderItem['order'];
                        }
                    }
                }
                
                // 파일들을 순서대로 정렬
                $filesWithOrder = [];
                for ($i = 0; $i < count($detailImages['name']); $i++) {
                    if ($detailImages['error'][$i] === UPLOAD_ERR_OK) {
                        $fileKey = $detailImages['name'][$i] . '_' . $detailImages['size'][$i];
                        $order = isset($orderData[$fileKey]) ? $orderData[$fileKey] : $i;
                        
                        $filesWithOrder[] = [
                            'order' => $order,
                            'index' => $i,
                            'file' => [
                                'name' => $detailImages['name'][$i],
                                'type' => $detailImages['type'][$i],
                                'tmp_name' => $detailImages['tmp_name'][$i],
                                'error' => $detailImages['error'][$i],
                                'size' => $detailImages['size'][$i]
                            ]
                        ];
                    }
                }
                
                // 순서대로 정렬
                usort($filesWithOrder, function($a, $b) {
                    return $a['order'] - $b['order'];
                });
                
                // 순서대로 저장
                $displayOrder = 0;
                $currentTimestamp = date('Y-m-d H:i:s');
                foreach ($filesWithOrder as $fileData) {
                    $imagePath = uploadEventImage($fileData['file'], $eventId, 'detail', false);
                    if ($imagePath) {
                        $stmt = $pdo->prepare("
                            INSERT INTO event_detail_images (event_id, image_path, display_order, created_at)
                            VALUES (:event_id, :image_path, :display_order, :created_at)
                        ");
                        $stmt->execute([
                            ':event_id' => $eventId,
                            ':image_path' => $imagePath,
                            ':display_order' => $displayOrder++,
                            ':created_at' => $currentTimestamp
                        ]);
                    }
                }
            }
            
            // 연결된 상품 저장
            if (isset($_POST['product_ids']) && is_array($_POST['product_ids'])) {
                $productIds = array_filter($_POST['product_ids'], function($id) {
                    return is_numeric($id) && $id > 0;
                });
                
                $displayOrder = 0;
                $currentTimestamp = date('Y-m-d H:i:s');
                foreach ($productIds as $productId) {
                    // ON DUPLICATE KEY UPDATE에서 같은 플레이스홀더를 두 번 사용할 수 없으므로 별도 플레이스홀더 사용
                    $stmt = $pdo->prepare("
                        INSERT INTO event_products (event_id, product_id, display_order, created_at)
                        VALUES (:event_id, :product_id, :display_order, :created_at)
                        ON DUPLICATE KEY UPDATE display_order = :display_order_update
                    ");
                    $stmt->execute([
                        ':event_id' => $eventId,
                        ':product_id' => (int)$productId,
                        ':display_order' => $displayOrder,
                        ':display_order_update' => $displayOrder, // UPDATE 절용 별도 플레이스홀더
                        ':created_at' => $currentTimestamp
                    ]);
                    $displayOrder++;
                }
            }
            
            $pdo->commit();
            $success = '이벤트가 등록되었습니다.';
            header('Location: event-manage.php?success=created');
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = '이벤트 등록 중 오류가 발생했습니다: ' . $e->getMessage();
            error_log('Event registration error: ' . $e->getMessage());
        }
    }
}

/**
 * events 테이블에 필요한 컬럼이 있는지 확인하고 없으면 추가
 */
function ensureEventsTableColumns($pdo) {
    try {
        // event_type 컬럼 확인 및 추가
        $stmt = $pdo->query("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'events' 
            AND COLUMN_NAME = 'event_type'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['cnt'] == 0) {
            $pdo->exec("
                ALTER TABLE events 
                ADD COLUMN event_type ENUM('plan', 'promotion', 'card') NOT NULL DEFAULT 'promotion' 
                COMMENT '이벤트 타입 (요금제/프로모션/제휴카드)' 
                AFTER category
            ");
        }
        
        // main_image 컬럼 확인 및 추가
        $stmt = $pdo->query("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'events' 
            AND COLUMN_NAME = 'main_image'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['cnt'] == 0) {
            $pdo->exec("
                ALTER TABLE events 
                ADD COLUMN main_image VARCHAR(1000) DEFAULT NULL 
                COMMENT '메인 이미지 (16:9 비율)' 
                AFTER image_url
            ");
        }
        
        // description 컬럼 확인 및 추가
        $stmt = $pdo->query("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'events' 
            AND COLUMN_NAME = 'description'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['cnt'] == 0) {
            $pdo->exec("
                ALTER TABLE events 
                ADD COLUMN description TEXT DEFAULT NULL 
                COMMENT '이벤트 설명' 
                AFTER title
            ");
        }
        
        // event_detail_images 테이블 확인 및 생성
        $stmt = $pdo->query("SHOW TABLES LIKE 'event_detail_images'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS event_detail_images (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    event_id VARCHAR(64) NOT NULL COMMENT '이벤트 ID',
                    image_path VARCHAR(1000) NOT NULL COMMENT '이미지 경로',
                    display_order INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '표시 순서',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    KEY idx_event_id_order (event_id, display_order),
                    CONSTRAINT fk_event_detail_images_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이벤트 상세 이미지'
            ");
        }
        
        // event_products 테이블 확인 및 생성
        $stmt = $pdo->query("SHOW TABLES LIKE 'event_products'");
        if ($stmt->rowCount() == 0) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS event_products (
                    id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    event_id VARCHAR(64) NOT NULL COMMENT '이벤트 ID',
                    product_id INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                    display_order INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '표시 순서 (드래그로 변경 가능)',
                    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (id),
                    UNIQUE KEY unique_event_product (event_id, product_id),
                    KEY idx_event_order (event_id, display_order),
                    KEY idx_product_id (product_id),
                    CONSTRAINT fk_event_products_event FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE,
                    CONSTRAINT fk_event_products_product FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE CASCADE
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='이벤트 연결 상품'
            ");
        }
        
        // is_published 컬럼이 있으면 삭제 (시작일/종료일로 관리하므로 불필요)
        $stmt = $pdo->query("
            SELECT COUNT(*) as cnt 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'events' 
            AND COLUMN_NAME = 'is_published'
        ");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result['cnt'] > 0) {
            try {
                $pdo->exec("ALTER TABLE events DROP COLUMN is_published");
                error_log('is_published column removed from events table');
            } catch (PDOException $e) {
                error_log('Error removing is_published column: ' . $e->getMessage());
            }
        }
    } catch (PDOException $e) {
        error_log('Error ensuring events table columns: ' . $e->getMessage());
        // 컬럼 추가 실패해도 계속 진행 (이미 존재하는 경우일 수 있음)
    }
}

/**
 * 이벤트 이미지 업로드 함수
 * 사이트 전체 이미지 관리 폴더: images/upload/event/YYYY/MM/
 */
function uploadEventImage($file, $eventId, $type = 'main', $force16to9 = false) {
    $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $maxSize = 10 * 1024 * 1024; // 10MB
    
    if (!in_array($file['type'], $allowedTypes)) {
        return false;
    }
    
    if ($file['size'] > $maxSize) {
        return false;
    }
    
    // 연도/월별 폴더 구조: images/upload/event/YYYY/MM/
    $year = date('Y');
    $month = date('m');
    $uploadDir = __DIR__ . '/../../images/upload/event/' . $year . '/' . $month . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    // 파일명: 타임스탬프_랜덤문자열.확장자 (기존 images/upload/event 폴더 형식과 유사하게)
    $filename = date('Ymd') . '_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // 16:9 비율 강제 리사이징 (메인 이미지인 경우)
        if ($force16to9) {
            resizeImageTo16to9($filepath);
        }
        
        // 웹 경로: /images/upload/event/YYYY/MM/filename
        return '/images/upload/event/' . $year . '/' . $month . '/' . $filename;
    }
    
    return false;
}

/**
 * 이미지를 16:9 비율로 리사이징
 */
function resizeImageTo16to9($filepath) {
    // GD 라이브러리 지원 여부 확인
    if (!function_exists('imagecreatefromjpeg') || !function_exists('imagecreatetruecolor')) {
        error_log('GD library is not available. Skipping image resize.');
        return true; // GD가 없어도 업로드는 성공으로 처리
    }
    
    $imageInfo = getimagesize($filepath);
    if ($imageInfo === false) {
        return false;
    }
    
    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mimeType = $imageInfo['mime'];
    
    // 목표 비율: 16:9
    $targetRatio = 16 / 9;
    $currentRatio = $width / $height;
    
    // 이미 16:9 비율이면 리사이징 불필요
    if (abs($currentRatio - $targetRatio) < 0.01) {
        return true;
    }
    
    // 이미지 리소스 생성
    $sourceImage = false;
    switch ($mimeType) {
        case 'image/jpeg':
            if (function_exists('imagecreatefromjpeg')) {
                $sourceImage = imagecreatefromjpeg($filepath);
            }
            break;
        case 'image/png':
            if (function_exists('imagecreatefrompng')) {
                $sourceImage = imagecreatefrompng($filepath);
            }
            break;
        case 'image/gif':
            if (function_exists('imagecreatefromgif')) {
                $sourceImage = imagecreatefromgif($filepath);
            }
            break;
        case 'image/webp':
            // WebP 지원 여부 확인
            if (function_exists('imagecreatefromwebp')) {
                $sourceImage = imagecreatefromwebp($filepath);
            } else {
                // WebP가 지원되지 않으면 JPEG로 변환 시도
                error_log('WebP is not supported. Converting to JPEG.');
                if (function_exists('imagecreatefromjpeg')) {
                    $sourceImage = imagecreatefromjpeg($filepath);
                }
                if ($sourceImage === false && function_exists('imagecreatefrompng')) {
                    // JPEG도 실패하면 PNG 시도
                    $sourceImage = imagecreatefrompng($filepath);
                }
            }
            break;
        default:
            error_log('Unsupported image type: ' . $mimeType);
            return false;
    }
    
    if ($sourceImage === false) {
        error_log('Failed to create image resource from: ' . $filepath);
        return false;
    }
    
    // 16:9 비율로 크롭/리사이징
    if ($currentRatio > $targetRatio) {
        // 너비가 더 넓음 - 높이 기준으로 크롭
        $newHeight = $height;
        $newWidth = (int)($height * $targetRatio);
        $x = (int)(($width - $newWidth) / 2);
        $y = 0;
    } else {
        // 높이가 더 높음 - 너비 기준으로 크롭
        $newWidth = $width;
        $newHeight = (int)($width / $targetRatio);
        $x = 0;
        $y = (int)(($height - $newHeight) / 2);
    }
    
    // 새 이미지 생성
    $newImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // PNG 투명도 유지
    if ($mimeType === 'image/png') {
        imagesavealpha($newImage, true);
        $transparent = imagecolorallocatealpha($newImage, 255, 255, 255, 127);
        imagefilledrectangle($newImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // 이미지 크롭 및 리사이징
    imagecopyresampled($newImage, $sourceImage, 0, 0, $x, $y, $newWidth, $newHeight, $newWidth, $newHeight);
    
    // 저장
    $saved = false;
    switch ($mimeType) {
        case 'image/jpeg':
            if (function_exists('imagejpeg')) {
                $saved = imagejpeg($newImage, $filepath, 90);
            }
            break;
        case 'image/png':
            if (function_exists('imagepng')) {
                $saved = imagepng($newImage, $filepath, 9);
            }
            break;
        case 'image/gif':
            if (function_exists('imagegif')) {
                $saved = imagegif($newImage, $filepath);
            }
            break;
        case 'image/webp':
            // WebP 저장 지원 여부 확인
            if (function_exists('imagewebp')) {
                $saved = imagewebp($newImage, $filepath, 90);
            } else {
                // WebP가 지원되지 않으면 JPEG로 저장
                error_log('WebP save is not supported. Saving as JPEG.');
                if (function_exists('imagejpeg')) {
                    $saved = imagejpeg($newImage, $filepath, 90);
                }
            }
            break;
    }
    
    if ($sourceImage) {
        imagedestroy($sourceImage);
    }
    if ($newImage) {
        imagedestroy($newImage);
    }
    
    if (!$saved) {
        error_log('Failed to save resized image: ' . $filepath);
        return false;
    }
    
    return true;
}

include __DIR__ . '/../includes/admin-header.php';
?>

<div class="admin-page-header">
    <h1>이벤트 등록</h1>
    <a href="event-manage.php" class="btn-back">목록으로</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data" id="eventForm" class="event-form">
    <input type="hidden" name="action" value="create_event">
    <input type="hidden" id="detail_images_data" name="detail_images_data" value="">
    
    <div class="form-section">
        <h2 class="section-title">기본 정보</h2>
        
        <div class="form-group">
            <label for="title">이벤트 제목 <span class="required">*</span></label>
            <input type="text" id="title" name="title" required class="form-control" placeholder="이벤트 제목을 입력하세요" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>">
        </div>
        
        <div class="form-group">
            <label for="event_type">이벤트 타입 <span class="required">*</span></label>
            <select id="event_type" name="event_type" required class="form-control">
                <option value="promotion" <?php echo (($_POST['event_type'] ?? 'promotion') === 'promotion') ? 'selected' : ''; ?>>프로모션</option>
                <option value="plan" <?php echo (($_POST['event_type'] ?? '') === 'plan') ? 'selected' : ''; ?>>요금제</option>
                <option value="card" <?php echo (($_POST['event_type'] ?? '') === 'card') ? 'selected' : ''; ?>>제휴카드</option>
            </select>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label for="start_at">시작일</label>
                <input type="date" id="start_at" name="start_at" class="form-control" value="<?php echo htmlspecialchars($_POST['start_at'] ?? ''); ?>">
            </div>
            
            <div class="form-group">
                <label for="end_at">종료일</label>
                <input type="date" id="end_at" name="end_at" class="form-control" value="<?php echo htmlspecialchars($_POST['end_at'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label for="description">이벤트 설명</label>
            <textarea id="description" name="description" class="form-control" rows="4" placeholder="이벤트 설명을 입력하세요"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
        </div>
        
    </div>
    
    <div class="form-section">
        <h2 class="section-title">메인 이미지 (16:9 비율)</h2>
        <div class="form-group">
            <label for="main_image">메인 이미지 <span class="required">*</span></label>
            <div class="file-upload-area" id="main_image_upload_area">
                <input type="file" id="main_image" name="main_image" accept="image/*" required class="file-input-hidden">
                <div class="file-upload-content">
                    <div class="file-upload-icon-wrapper">
                        <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <polyline points="17 8 12 3 7 8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <line x1="12" y1="3" x2="12" y2="15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="file-upload-text">
                        <span class="file-upload-primary">클릭하여 파일 선택</span>
                        <span class="file-upload-secondary">또는 여기에 파일을 드래그하세요</span>
                    </div>
                    <div class="file-upload-hint">
                        <span class="file-upload-badge">JPG, PNG, GIF, WEBP</span>
                        <span class="file-upload-badge">최대 10MB</span>
                        <span class="file-upload-badge">16:9 자동 리사이징</span>
                    </div>
                </div>
            </div>
            <div id="main_image_preview" class="image-preview"></div>
        </div>
    </div>
    
    <div class="form-section">
        <h2 class="section-title">상세 이미지</h2>
        <div class="form-group">
            <label for="detail_images">상세 이미지 (여러 장 선택 가능)</label>
            <div class="file-upload-area" id="detail_images_upload_area">
                <input type="file" id="detail_images" name="detail_images[]" accept="image/*" multiple class="file-input-hidden">
                <div class="file-upload-content">
                    <div class="file-upload-icon-wrapper">
                        <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                            <polyline points="21 15 16 10 5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="file-upload-text">
                        <span class="file-upload-primary">클릭하여 파일 선택</span>
                        <span class="file-upload-secondary">또는 여기에 파일을 드래그하세요</span>
                    </div>
                    <div class="file-upload-hint">
                        <span class="file-upload-badge">여러 장 선택 가능</span>
                        <span class="file-upload-badge">JPG, PNG, GIF, WEBP</span>
                        <span class="file-upload-badge">최대 10MB</span>
                    </div>
                </div>
            </div>
            <div id="detail_images_preview" class="images-preview sortable-images"></div>
            <div id="detail_images_order" style="display: none;"></div>
        </div>
    </div>
    
    <div class="form-section">
        <h2 class="section-title">연결 상품</h2>
        <div class="form-group">
            <label>연결할 상품 선택</label>
            <button type="button" id="open_product_search_modal" class="btn btn-primary" style="margin-bottom: 16px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-right: 8px; vertical-align: middle;">
                    <circle cx="11" cy="11" r="8" stroke="currentColor" stroke-width="2"/>
                    <path d="M21 21L16.65 16.65" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
                검색하기
            </button>
        </div>
        
        <div class="form-group">
            <label>추가된 상품 (드래그로 순서 변경 가능)</label>
            <div id="selected_products" class="selected-products-list" data-sortable="true">
                <p class="empty-message">추가된 상품이 없습니다.</p>
            </div>
            <input type="hidden" id="product_ids" name="product_ids[]" value="">
        </div>
    </div>
    
    <!-- 상품 검색 모달 -->
    <div id="product_search_modal" class="modal-overlay" style="display: none;">
        <div class="modal-content product-search-modal">
            <div class="modal-header">
                <h2>상품 검색</h2>
                <button type="button" class="modal-close" onclick="closeProductSearchModal()">&times;</button>
            </div>
            <div class="modal-body">
                <div class="search-container">
                    <div class="search-left-panel">
                        <div class="search-fields">
                            <div class="form-group">
                                <label for="modal_product_category">카테고리</label>
                                <select id="modal_product_category" class="form-control">
                                    <option value="mvno" selected>알뜰폰</option>
                                    <option value="mno">통신사폰</option>
                                    <option value="mno_sim">통신사단독유심</option>
                                    <option value="internet">인터넷</option>
                                    <option value="">전체</option>
                                </select>
                            </div>
                            
                            <!-- 카테고리별 필터 영역 -->
                            <div id="category_filters" class="category-filters">
                                <!-- 동적으로 필터가 표시됩니다 -->
                            </div>
                            
                            <div class="form-group">
                                <button type="button" id="modal_search_btn" class="btn btn-primary" style="width: 100%;">검색</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="search-right-panel">
                        <div id="modal_search_results" class="modal-search-results">
                            <p class="empty-message">검색어를 입력하고 검색 버튼을 클릭하세요.</p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeProductSearchModal()">취소</button>
                <button type="button" class="btn btn-primary" id="modal_add_selected_btn">선택한 상품 추가</button>
            </div>
        </div>
    </div>
    
    <div class="form-actions">
        <button type="submit" class="btn btn-primary">이벤트 등록</button>
        <a href="event-manage.php" class="btn btn-secondary">취소</a>
    </div>
</form>

<style>
.admin-page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 2px solid #e5e7eb;
}

.admin-page-header h1 {
    font-size: 24px;
    font-weight: 700;
    color: #1f2937;
    margin: 0;
}

.btn-back {
    padding: 8px 16px;
    background: #f3f4f6;
    color: #374151;
    text-decoration: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    transition: all 0.2s;
}

.btn-back:hover {
    background: #e5e7eb;
}

.alert {
    padding: 12px 16px;
    border-radius: 6px;
    margin-bottom: 20px;
}

.alert-error {
    background: #fee2e2;
    color: #991b1b;
    border: 1px solid #fecaca;
}

.alert-success {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #a7f3d0;
}

.event-form {
    background: #ffffff;
    padding: 24px;
    border-radius: 8px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    max-width: 50%;
    margin: 0 auto;
}

.form-section {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid #e5e7eb;
}

.form-section:last-child {
    border-bottom: none;
}

.section-title {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 20px;
}

.form-group {
    margin-bottom: 20px;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.required {
    color: #ef4444;
}

.form-control {
    width: 100%;
    padding: 10px 12px;
    border: 1px solid #d1d5db;
    border-radius: 6px;
    font-size: 14px;
    transition: border-color 0.2s;
}

.form-control:focus {
    outline: none;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
}

.form-help {
    display: block;
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

/* 파일 업로드 영역 스타일 */
.file-upload-area {
    position: relative;
    border: 2px dashed #d1d5db;
    border-radius: 12px;
    padding: 48px 24px;
    text-align: center;
    background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    margin-top: 8px;
    overflow: hidden;
}

.file-upload-area::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.file-upload-area:hover {
    border-color: #6366f1;
    background: linear-gradient(135deg, #f0f4ff 0%, #eef2ff 100%);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    transform: translateY(-2px);
}

.file-upload-area:hover::before {
    opacity: 1;
}

.file-upload-area.dragover {
    border-color: #6366f1;
    background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
    box-shadow: 0 8px 24px rgba(99, 102, 241, 0.25);
    transform: scale(1.01) translateY(-2px);
    border-style: solid;
}

.file-upload-area.dragover::before {
    opacity: 1;
}

.file-upload-area.has-file {
    border-color: #10b981;
    background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    padding: 32px 24px;
    box-shadow: 0 4px 12px rgba(16, 185, 129, 0.15);
}

.file-upload-area.has-file::before {
    background: linear-gradient(135deg, rgba(16, 185, 129, 0.05) 0%, rgba(5, 150, 105, 0.05) 100%);
    opacity: 1;
}

.file-input-hidden {
    position: absolute;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    opacity: 0;
    cursor: pointer;
    z-index: 10;
}

.file-upload-content {
    pointer-events: none;
    position: relative;
    z-index: 1;
}

.file-upload-icon-wrapper {
    margin-bottom: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
}

.file-upload-icon {
    width: 56px;
    height: 56px;
    color: #6366f1;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    filter: drop-shadow(0 2px 4px rgba(99, 102, 241, 0.2));
}

.file-upload-area:hover .file-upload-icon {
    color: #4f46e5;
    transform: translateY(-4px) scale(1.1);
    filter: drop-shadow(0 4px 8px rgba(99, 102, 241, 0.3));
}

.file-upload-area.has-file .file-upload-icon {
    color: #10b981;
    width: 48px;
    height: 48px;
}

.file-upload-text {
    display: flex;
    flex-direction: column;
    gap: 6px;
    margin-bottom: 16px;
}

.file-upload-primary {
    font-size: 18px;
    font-weight: 600;
    color: #1f2937;
    letter-spacing: -0.02em;
}

.file-upload-secondary {
    font-size: 14px;
    color: #6b7280;
    font-weight: 400;
}

.file-upload-area.has-file .file-upload-primary {
    color: #10b981;
    font-size: 16px;
}

.file-upload-area.has-file .file-upload-secondary {
    color: #059669;
    font-size: 13px;
}

.file-upload-hint {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    justify-content: center;
    align-items: center;
}

.file-upload-badge {
    display: inline-flex;
    align-items: center;
    padding: 6px 12px;
    background: rgba(99, 102, 241, 0.1);
    color: #6366f1;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 500;
    letter-spacing: -0.01em;
    transition: all 0.2s ease;
}

.file-upload-area.has-file .file-upload-badge {
    background: rgba(16, 185, 129, 0.1);
    color: #059669;
}

.file-upload-area:hover .file-upload-badge {
    background: rgba(99, 102, 241, 0.15);
    transform: translateY(-1px);
}

.checkbox-label {
    display: flex;
    align-items: center;
    cursor: pointer;
}

.checkbox-label input[type="checkbox"] {
    margin-right: 8px;
}

.image-preview, .images-preview {
    margin-top: 16px;
}

.image-preview-wrapper {
    position: relative;
    display: inline-block;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.image-preview-wrapper:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.image-preview-wrapper img {
    display: block;
    max-width: 100%;
    max-height: 400px;
    width: auto;
    height: auto;
    border-radius: 12px;
}

.image-preview-info {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    background: linear-gradient(to top, rgba(0, 0, 0, 0.7), transparent);
    padding: 16px 12px 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: white;
    font-size: 12px;
}

.image-preview-name {
    font-weight: 500;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    flex: 1;
    margin-right: 8px;
}

.image-preview-size {
    font-weight: 600;
    background: rgba(255, 255, 255, 0.2);
    padding: 4px 8px;
    border-radius: 12px;
    backdrop-filter: blur(4px);
}

.images-preview img {
    max-width: 100%;
    max-height: 300px;
    border-radius: 6px;
    margin-right: 12px;
    margin-bottom: 12px;
    border: 1px solid #e5e7eb;
}

/* 상세 이미지 드래그 앤 드롭 스타일 */
.images-preview.sortable-images {
    display: grid;
    grid-template-columns: 1fr;
    gap: 0;
    margin-top: 16px;
}

.detail-image-item {
    position: relative;
    background: #ffffff;
    border: none;
    border-radius: 0;
    padding: 0;
    cursor: move;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    overflow: hidden;
}

.detail-image-item:first-child {
    border-radius: 0.75rem 0.75rem 0 0;
}

.detail-image-item:last-child {
    border-radius: 0 0 0.75rem 0.75rem;
}

.detail-image-item:only-child {
    border-radius: 0.75rem;
}

.detail-image-item::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(135deg, rgba(99, 102, 241, 0.05) 0%, rgba(139, 92, 246, 0.05) 100%);
    opacity: 0;
    transition: opacity 0.3s ease;
    pointer-events: none;
}

.detail-image-item:hover {
    border-color: #6366f1;
    box-shadow: 0 8px 16px rgba(99, 102, 241, 0.2);
    transform: translateY(-4px);
}

.detail-image-item:hover::before {
    opacity: 1;
}

.detail-image-item.sortable-ghost {
    opacity: 0.4;
    background: #f3f4f6;
}

.detail-image-item .drag-handle {
    position: absolute;
    top: 8px;
    left: 8px;
    background: linear-gradient(135deg, #6366f1 0%, #8b5cf6 100%);
    color: white;
    padding: 6px;
    border-radius: 8px;
    font-size: 16px;
    cursor: move;
    z-index: 10;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 32px;
    height: 32px;
    box-shadow: 0 2px 8px rgba(99, 102, 241, 0.3);
    transition: all 0.2s ease;
}

.detail-image-item .drag-handle:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.4);
}

.detail-image-item .image-wrapper {
    width: 100%;
    padding-top: 75%; /* 4:3 비율 */
    position: relative;
    overflow: hidden;
    border-radius: 0;
    background: #f9fafb;
    margin-bottom: 0;
}

.detail-image-item:first-child .image-wrapper {
    border-radius: 0.75rem 0.75rem 0 0;
}

.detail-image-item:last-child .image-wrapper {
    border-radius: 0 0 0.75rem 0.75rem;
}

.detail-image-item:only-child .image-wrapper {
    border-radius: 0.75rem;
}

.detail-image-item .image-wrapper img {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    object-fit: cover;
    margin: 0;
    border: none;
    border-radius: 0;
}

.detail-image-item .remove-image-btn {
    width: 100%;
    padding: 10px;
    background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    color: white;
    border: none;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.2s ease;
    box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
    letter-spacing: -0.01em;
}

.detail-image-item .remove-image-btn:hover {
    background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(239, 68, 68, 0.3);
}

.detail-image-item .remove-image-btn:active {
    transform: translateY(0);
}


.selected-products-list {
    min-height: 100px;
    padding: 12px;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    background: #f9fafb;
}

.selected-products-list .empty-message {
    color: #9ca3af;
    text-align: center;
    padding: 20px;
    margin: 0;
}

.selected-product-item {
    display: flex;
    align-items: center;
    padding: 12px;
    background: white;
    border-radius: 4px;
    margin-bottom: 10px;
    border: 1px solid #e5e7eb;
    cursor: move;
}

.selected-product-item:hover {
    background: #f3f4f6;
}

.selected-product-item .drag-handle {
    margin-right: 12px;
    color: #9ca3af;
    cursor: move;
}

.selected-product-item .product-info {
    flex: 1;
}

.selected-product-item .product-name {
    font-weight: 500;
    color: #1f2937;
}

.selected-product-item .product-type {
    font-size: 12px;
    color: #6b7280;
}

.selected-product-item .remove-btn {
    padding: 4px 8px;
    background: #ef4444;
    color: white;
    border: none;
    border-radius: 4px;
    font-size: 12px;
    cursor: pointer;
}

.form-actions {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid #e5e7eb;
}

.btn {
    padding: 12px 24px;
    border-radius: 6px;
    font-size: 14px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-block;
    border: none;
}

.btn-primary {
    background: #6366f1;
    color: white;
}

.btn-primary:hover {
    background: #4f46e5;
}

.btn-secondary {
    background: #f3f4f6;
    color: #374151;
}

.btn-secondary:hover {
    background: #e5e7eb;
}

/* 상품 검색 모달 스타일 */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
    padding: 20px;
}

.product-search-modal {
    background: white;
    border-radius: 12px;
    width: 100%;
    max-width: 1400px;
    max-height: 90vh;
    display: flex;
    flex-direction: column;
    box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    border-bottom: 1px solid #e5e7eb;
}

.modal-header h2 {
    margin: 0;
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
}

.modal-close {
    background: none;
    border: none;
    font-size: 28px;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 6px;
    transition: all 0.2s;
}

.modal-close:hover {
    background: #f3f4f6;
    color: #374151;
}

.modal-body {
    padding: 24px;
    overflow-y: auto;
    flex: 1;
}

.modal-footer {
    display: flex;
    justify-content: flex-end;
    gap: 12px;
    padding: 20px 24px;
    border-top: 1px solid #e5e7eb;
}

.search-container {
    display: grid;
    grid-template-columns: 400px 1fr;
    gap: 24px;
    height: calc(90vh - 160px);
}

.search-left-panel {
    overflow-y: auto;
    padding-right: 12px;
}

.search-right-panel {
    overflow-y: auto;
    padding-left: 12px;
}

.search-fields {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.category-filters {
    margin-top: 8px;
    padding-top: 16px;
    border-top: 2px solid #e5e7eb;
}

.category-filters .form-group {
    margin-bottom: 16px;
}

.category-filters .filter-section-title {
    font-weight: 600;
    color: #374151;
    margin-bottom: 12px;
    font-size: 14px;
}

.modal-search-results {
    height: 100%;
    overflow-y: auto;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    background: #f9fafb;
    min-height: 500px;
    padding: 16px;
}

.modal-search-results {
    display: flex;
    flex-direction: column;
    gap: 0;
    padding: 0;
}

.modal-product-item {
    padding: 16px;
    background: white;
    border-bottom: 1px solid #e5e7eb;
    transition: background 0.2s;
}

.modal-product-item:last-child {
    border-bottom: none;
}

.modal-product-item:hover {
    background: #f9fafb;
}

.modal-product-item.selected {
    background: #eef2ff;
    border-left: 3px solid #6366f1;
}

.modal-product-item.already-added {
    background: #f3f4f6;
    opacity: 0.7;
}

/* 카드 형태 스타일 */
.modal-product-card {
    background: white;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    overflow: hidden;
    transition: all 0.2s;
    cursor: pointer;
    position: relative;
}

.modal-product-card:hover {
    border-color: #6366f1;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.15);
    transform: translateY(-2px);
}

.modal-product-card.selected {
    border-color: #6366f1;
    background: #eef2ff;
    box-shadow: 0 4px 12px rgba(99, 102, 241, 0.25);
}

.modal-product-card.already-added {
    opacity: 0.6;
    cursor: not-allowed;
}

.modal-product-card-label {
    display: block;
    cursor: pointer;
    margin: 0;
}

.modal-product-checkbox {
    position: absolute;
    top: 12px;
    right: 12px;
    width: 20px;
    height: 20px;
    cursor: pointer;
    z-index: 10;
    margin: 0;
}

.modal-product-card.already-added .modal-product-checkbox {
    cursor: not-allowed;
}

.modal-product-card-content {
    padding: 16px;
}

.modal-product-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 12px;
}

.modal-product-provider {
    font-size: 14px;
    font-weight: 600;
    color: #6b7280;
}

.modal-product-card-body {
    margin-bottom: 16px;
}

.modal-product-title {
    font-size: 18px;
    font-weight: 700;
    color: #1a1a1a;
    margin-bottom: 8px;
    line-height: 1.4;
}

.modal-product-data {
    font-size: 16px;
    font-weight: 600;
    color: #374151;
    margin-bottom: 8px;
}

.modal-product-features {
    font-size: 13px;
    color: #6b7280;
    line-height: 1.6;
}

.modal-product-card-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding-top: 12px;
    border-top: 1px solid #e5e7eb;
}

.modal-product-price {
    font-size: 20px;
    font-weight: 700;
    color: #6366f1;
}

.modal-product-type-badge {
    display: inline-block;
    font-size: 12px;
    font-weight: 500;
    color: #4338ca;
    background: #e0e7ff;
    padding: 4px 8px;
    border-radius: 4px;
    margin-left: 8px;
    vertical-align: middle;
}

.modal-product-info {
    position: relative;
}

.modal-product-info .already-added-badge {
    position: absolute;
    top: 0;
    right: 0;
    margin-top: 0;
    background: #fee2e2;
    color: #991b1b;
}

.modal-product-checkbox-label {
    display: flex;
    align-items: flex-start;
    cursor: pointer;
    margin: 0;
}

.modal-product-checkbox {
    margin-right: 12px;
    margin-top: 2px;
    width: 18px;
    height: 18px;
    cursor: pointer;
    flex-shrink: 0;
}

.modal-product-checkbox:disabled {
    cursor: not-allowed;
}

.modal-product-info {
    flex: 1;
}

.modal-product-name {
    font-size: 16px;
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 8px;
    display: inline-block;
    margin-right: 8px;
}

.modal-product-specs {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    margin-top: 8px;
    padding-top: 8px;
    border-top: 1px solid #f3f4f6;
}

.modal-product-spec-item {
    display: flex;
    align-items: center;
    gap: 4px;
    font-size: 13px;
}

.modal-product-spec-item .spec-label {
    font-weight: 500;
    color: #6b7280;
}

.modal-product-spec-item .spec-value {
    color: #1f2937;
    font-weight: 600;
}

.modal-product-details {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6b7280;
    flex-wrap: wrap;
}

.modal-product-type {
    padding: 4px 8px;
    background: #e0e7ff;
    color: #4338ca;
    border-radius: 4px;
    font-weight: 500;
}

.modal-product-separator {
    color: #d1d5db;
}

.modal-product-seller,
.modal-product-seller-id {
    color: #6b7280;
}

.already-added-badge {
    display: inline-block;
    padding: 4px 8px;
    background: #f3f4f6;
    color: #6b7280;
    border-radius: 4px;
    font-size: 12px;
    font-weight: 500;
    margin-top: 8px;
}

.modal-product-card-header .already-added-badge {
    margin-top: 0;
    background: #fee2e2;
    color: #991b1b;
}

.selected-product-item .product-details {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 13px;
    color: #6b7280;
    margin-top: 4px;
}

.selected-product-item .product-separator {
    color: #d1d5db;
}

.selected-product-item .product-seller {
    color: #6b7280;
}

@media (max-width: 768px) {
    .event-form {
        max-width: 100%;
        padding: 16px;
    }
    
    .form-row {
        grid-template-columns: 1fr;
    }
    
    .product-search-box {
        flex-direction: column;
    }
    
    .search-fields {
        grid-template-columns: 1fr;
    }
    
    .product-search-modal {
        max-width: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .modal-body {
        padding: 16px;
    }
}
</style>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // 메인 이미지 미리보기 및 드래그 앤 드롭
    const mainImageInput = document.getElementById('main_image');
    const mainImagePreview = document.getElementById('main_image_preview');
    const mainImageUploadArea = document.getElementById('main_image_upload_area');
    
    // 파일 선택 시
    mainImageInput.addEventListener('change', function(e) {
        handleMainImageFile(e.target.files[0]);
    });
    
    // 드래그 앤 드롭 이벤트
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        mainImageUploadArea.addEventListener(eventName, preventDefaults, false);
    });
    
    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        mainImageUploadArea.addEventListener(eventName, function() {
            mainImageUploadArea.classList.add('dragover');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        mainImageUploadArea.addEventListener(eventName, function() {
            mainImageUploadArea.classList.remove('dragover');
        }, false);
    });
    
    mainImageUploadArea.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files.length > 0) {
            handleMainImageFile(files[0]);
            // input에도 파일 설정
            const dataTransfer = new DataTransfer();
            dataTransfer.items.add(files[0]);
            mainImageInput.files = dataTransfer.files;
        }
    }, false);
    
    function handleMainImageFile(file) {
        if (file) {
            // 파일 유효성 검사
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
            const maxSize = 10 * 1024 * 1024; // 10MB
            
            if (!allowedTypes.includes(file.type)) {
                alert('지원하지 않는 파일 형식입니다. JPG, PNG, GIF, WEBP 파일만 업로드 가능합니다.');
                return;
            }
            
            if (file.size > maxSize) {
                alert('파일 크기는 10MB를 초과할 수 없습니다.');
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const fileSize = (file.size / 1024 / 1024).toFixed(2);
                mainImagePreview.innerHTML = `
                    <div class="image-preview-wrapper">
                        <img src="${e.target.result}" alt="메인 이미지 미리보기">
                        <div class="image-preview-info">
                            <span class="image-preview-name">${escapeHtml(file.name)}</span>
                            <span class="image-preview-size">${fileSize} MB</span>
                        </div>
                    </div>
                `;
                mainImageUploadArea.classList.add('has-file');
                mainImageUploadArea.querySelector('.file-upload-content').innerHTML = `
                    <div class="file-upload-icon-wrapper">
                        <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                    </div>
                    <div class="file-upload-text">
                        <span class="file-upload-primary">파일이 선택되었습니다</span>
                        <span class="file-upload-secondary">다른 파일을 선택하려면 클릭하세요</span>
                    </div>
                    <div class="file-upload-hint">
                        <span class="file-upload-badge">${escapeHtml(file.name)}</span>
                        <span class="file-upload-badge">${fileSize} MB</span>
                    </div>
                `;
            };
            reader.readAsDataURL(file);
        } else {
            mainImagePreview.innerHTML = '';
            mainImageUploadArea.classList.remove('has-file');
        }
    }
    
    // 상세 이미지 미리보기 및 드래그 앤 드롭
    const detailImagesInput = document.getElementById('detail_images');
    const detailImagesPreview = document.getElementById('detail_images_preview');
    const detailImagesUploadArea = document.getElementById('detail_images_upload_area');
    
    let detailImageFiles = []; // 업로드된 파일들을 순서대로 저장
    
    // 파일 선택 시
    detailImagesInput.addEventListener('change', function(e) {
        handleDetailImageFiles(Array.from(e.target.files));
    });
    
    // 드래그 앤 드롭 이벤트
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        detailImagesUploadArea.addEventListener(eventName, preventDefaultsDetail, false);
    });
    
    function preventDefaultsDetail(e) {
        e.preventDefault();
        e.stopPropagation();
    }
    
    ['dragenter', 'dragover'].forEach(eventName => {
        detailImagesUploadArea.addEventListener(eventName, function() {
            detailImagesUploadArea.classList.add('dragover');
        }, false);
    });
    
    ['dragleave', 'drop'].forEach(eventName => {
        detailImagesUploadArea.addEventListener(eventName, function() {
            detailImagesUploadArea.classList.remove('dragover');
        }, false);
    });
    
    detailImagesUploadArea.addEventListener('drop', function(e) {
        const dt = e.dataTransfer;
        const files = Array.from(dt.files);
        if (files.length > 0) {
            handleDetailImageFiles(files);
            // input에도 파일 설정
            const dataTransfer = new DataTransfer();
            files.forEach(file => {
                // 중복 체크
                const isDuplicate = detailImageFiles.some(f => f.file.name === file.name && f.file.size === file.size);
                if (!isDuplicate) {
                    dataTransfer.items.add(file);
                }
            });
            detailImagesInput.files = dataTransfer.files;
        }
    }, false);
    
    function handleDetailImageFiles(files) {
        const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
        const maxSize = 10 * 1024 * 1024; // 10MB
        
        files.forEach(file => {
            // 파일 유효성 검사
            if (!allowedTypes.includes(file.type)) {
                alert(`"${file.name}"은(는) 지원하지 않는 파일 형식입니다. JPG, PNG, GIF, WEBP 파일만 업로드 가능합니다.`);
                return;
            }
            
            if (file.size > maxSize) {
                alert(`"${file.name}"의 파일 크기는 10MB를 초과할 수 없습니다.`);
                return;
            }
            
            // 중복 체크
            const isDuplicate = detailImageFiles.some(f => f.file.name === file.name && f.file.size === file.size);
            if (isDuplicate) {
                return;
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const fileId = 'img_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
                
                // 파일 정보 저장
                const fileData = {
                    id: fileId,
                    file: file,
                    preview: e.target.result
                };
                detailImageFiles.push(fileData);
                
                // 이미지 아이템 생성
                const imageItem = document.createElement('div');
                imageItem.className = 'detail-image-item';
                imageItem.dataset.fileId = fileId;
                imageItem.innerHTML = `
                    <span class="drag-handle">☰</span>
                    <div class="image-wrapper">
                        <img src="${e.target.result}" alt="상세 이미지 미리보기">
                    </div>
                    <button type="button" class="remove-image-btn" onclick="removeDetailImage('${fileId}')">삭제</button>
                `;
                detailImagesPreview.appendChild(imageItem);
                
                // Sortable 초기화/업데이트
                initDetailImagesSortable();
                
                // 업로드 영역 업데이트
                updateDetailImagesUploadArea();
            };
            reader.readAsDataURL(file);
        });
    }
    
    function updateDetailImagesUploadArea() {
        if (detailImageFiles.length > 0) {
            const totalSize = detailImageFiles.reduce((sum, f) => sum + f.file.size, 0);
            const totalSizeMB = (totalSize / 1024 / 1024).toFixed(2);
            detailImagesUploadArea.classList.add('has-file');
            detailImagesUploadArea.querySelector('.file-upload-content').innerHTML = `
                <div class="file-upload-icon-wrapper">
                    <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="file-upload-text">
                    <span class="file-upload-primary">${detailImageFiles.length}개의 파일이 선택되었습니다</span>
                    <span class="file-upload-secondary">추가 파일을 선택하려면 클릭하세요</span>
                </div>
                <div class="file-upload-hint">
                    <span class="file-upload-badge">${detailImageFiles.length}개 파일</span>
                    <span class="file-upload-badge">${totalSizeMB} MB</span>
                </div>
            `;
        } else {
            detailImagesUploadArea.classList.remove('has-file');
            detailImagesUploadArea.querySelector('.file-upload-content').innerHTML = `
                <div class="file-upload-icon-wrapper">
                    <svg class="file-upload-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="8.5" cy="8.5" r="1.5" fill="currentColor"/>
                        <polyline points="21 15 16 10 5 21" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
                <div class="file-upload-text">
                    <span class="file-upload-primary">클릭하여 파일 선택</span>
                    <span class="file-upload-secondary">또는 여기에 파일을 드래그하세요</span>
                </div>
                <div class="file-upload-hint">
                    <span class="file-upload-badge">여러 장 선택 가능</span>
                    <span class="file-upload-badge">JPG, PNG, GIF, WEBP</span>
                    <span class="file-upload-badge">최대 10MB</span>
                </div>
            `;
        }
    }
    
    // 상세 이미지 삭제 함수
    window.removeDetailImage = function(fileId) {
        // 파일 목록에서 제거
        detailImageFiles = detailImageFiles.filter(f => f.id !== fileId);
        
        // DOM에서 제거
        const imageItem = detailImagesPreview.querySelector(`[data-file-id="${fileId}"]`);
        if (imageItem) {
            imageItem.remove();
        }
        
        // FileList 업데이트
        updateDetailImagesFileList();
        
        // 업로드 영역 업데이트
        updateDetailImagesUploadArea();
        
        // Sortable 재초기화
        if (detailImageFiles.length > 0) {
            initDetailImagesSortable();
        }
    };
    
    // FileList 업데이트 함수 및 순서 정보 저장
    function updateDetailImagesFileList() {
        // 순서 정보를 JSON으로 저장 (서버에서 참조용)
        const orderData = detailImageFiles.map((fileData, index) => ({
            id: fileData.id,
            name: fileData.file.name,
            size: fileData.file.size,
            type: fileData.file.type,
            order: index
        }));
        document.getElementById('detail_images_data').value = JSON.stringify(orderData);
    }
    
    // 폼 제출 시 순서 정보 저장
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        // 순서 정보를 JSON으로 저장
        if (detailImageFiles.length > 0) {
            const orderData = detailImageFiles.map((fileData, index) => ({
                name: fileData.file.name,
                size: fileData.file.size,
                type: fileData.file.type,
                order: index
            }));
            document.getElementById('detail_images_data').value = JSON.stringify(orderData);
        }
    });
    
    // 상세 이미지 Sortable 초기화
    let detailImagesSortable = null;
    function initDetailImagesSortable() {
        if (detailImagesSortable) {
            detailImagesSortable.destroy();
        }
        
        if (detailImageFiles.length > 0) {
            detailImagesSortable = new Sortable(detailImagesPreview, {
                handle: '.drag-handle',
                animation: 150,
                onEnd: function(evt) {
                    const oldIndex = evt.oldIndex;
                    const newIndex = evt.newIndex;
                    
                    // 배열 재정렬
                    const movedFile = detailImageFiles.splice(oldIndex, 1)[0];
                    detailImageFiles.splice(newIndex, 0, movedFile);
                    
                    // FileList 업데이트
                    updateDetailImagesFileList();
                }
            });
        }
    }
    
    // 상품 검색 모달
    const productSearchModal = document.getElementById('product_search_modal');
    const openProductSearchModalBtn = document.getElementById('open_product_search_modal');
    const modalProductCategory = document.getElementById('modal_product_category');
    const modalSearchBtn = document.getElementById('modal_search_btn');
    const modalSearchResults = document.getElementById('modal_search_results');
    const selectedProductsList = document.getElementById('selected_products');
    const productIdsInput = document.getElementById('product_ids');
    
    let selectedProducts = [];
    let modalSelectedProducts = []; // 모달에서 선택한 상품들 (임시)
    let currentSearchResults = []; // 현재 검색 결과 저장
    
    // 모달 열기
    openProductSearchModalBtn.addEventListener('click', function() {
        productSearchModal.style.display = 'flex';
        document.body.style.overflow = 'hidden';
        // 모달 열 때 기존 선택 초기화
        modalSelectedProducts = [];
        modalProductCategory.value = 'mvno'; // 기본값: 알뜰폰
        updateCategoryFilters('mvno'); // 알뜰폰 필터 표시
        modalSearchResults.innerHTML = '<p class="empty-message" style="padding: 20px; text-align: center; color: #6b7280;">검색어를 입력하고 검색 버튼을 클릭하세요.</p>';
    });
    
    // 모달 닫기
    window.closeProductSearchModal = function() {
        productSearchModal.style.display = 'none';
        document.body.style.overflow = '';
        // 모달 닫을 때 선택 초기화
        modalSelectedProducts = [];
        modalProductCategory.value = 'mvno'; // 기본값: 알뜰폰
        updateCategoryFilters('mvno'); // 알뜰폰 필터 표시
    };
    
    // 모달 외부 클릭 시 닫기
    productSearchModal.addEventListener('click', function(e) {
        if (e.target === productSearchModal) {
            closeProductSearchModal();
        }
    });
    
    // 카테고리 변경 시 필터 업데이트
    modalProductCategory.addEventListener('change', function() {
        updateCategoryFilters(modalProductCategory.value);
    });
    
    // 카테고리별 필터 생성 함수
    function updateCategoryFilters(category) {
        const filtersContainer = document.getElementById('category_filters');
        if (!filtersContainer) return;
        
        filtersContainer.innerHTML = '';
        
        if (!category) {
            return;
        }
        
        let filterHTML = '<div class="filter-section-title">카테고리별 필터</div>';
        
        switch(category) {
            case 'mvno':
                filterHTML += `
                    <div class="form-group">
                        <label for="filter_provider">통신사</label>
                        <select id="filter_provider" class="form-control">
                            <option value="">전체</option>
                            <option value="SKT">SKT</option>
                            <option value="KT">KT</option>
                            <option value="LG U+">LG U+</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_contract_period">약정기간</label>
                        <select id="filter_contract_period" class="form-control">
                            <option value="">전체</option>
                            <option value="무약정">무약정</option>
                            <option value="12개월">12개월</option>
                            <option value="24개월">24개월</option>
                            <option value="36개월">36개월</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_price_min">할인 후 요금 (최소)</label>
                        <input type="number" id="filter_price_min" class="form-control" placeholder="최소 금액" min="0">
                    </div>
                    <div class="form-group">
                        <label for="filter_price_max">할인 후 요금 (최대)</label>
                        <input type="number" id="filter_price_max" class="form-control" placeholder="최대 금액" min="0">
                    </div>
                    <div class="form-group">
                        <label for="filter_service_type">서비스 타입</label>
                        <select id="filter_service_type" class="form-control">
                            <option value="">전체</option>
                            <option value="LTE">LTE</option>
                            <option value="5G">5G</option>
                        </select>
                    </div>
                `;
                break;
                
            case 'mno':
                filterHTML += `
                    <div class="form-group">
                        <label for="filter_device_name">단말기명</label>
                        <input type="text" id="filter_device_name" class="form-control" placeholder="단말기명을 입력하세요">
                    </div>
                    <div class="form-group">
                        <label for="filter_delivery_method">배송방법</label>
                        <select id="filter_delivery_method" class="form-control">
                            <option value="">전체</option>
                            <option value="delivery">택배</option>
                            <option value="visit">내방</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_provider_mno">통신사</label>
                        <select id="filter_provider_mno" class="form-control">
                            <option value="">전체</option>
                            <option value="SKT">SKT</option>
                            <option value="KT">KT</option>
                            <option value="LG U+">LG U+</option>
                        </select>
                    </div>
                `;
                break;
                
            case 'mno_sim':
                filterHTML += `
                    <div class="form-group">
                        <label for="filter_provider_sim">통신사</label>
                        <select id="filter_provider_sim" class="form-control">
                            <option value="">전체</option>
                            <option value="SKT">SKT</option>
                            <option value="KT">KT</option>
                            <option value="LG U+">LG U+</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="filter_contract_period_sim">약정기간</label>
                        <select id="filter_contract_period_sim" class="form-control">
                            <option value="">전체</option>
                            <option value="무약정">무약정</option>
                            <option value="12개월">12개월</option>
                            <option value="24개월">24개월</option>
                        </select>
                    </div>
                `;
                break;
                
            case 'internet':
                filterHTML += `
                    <div class="form-group">
                        <label for="filter_registration_place">등록지역</label>
                        <input type="text" id="filter_registration_place" class="form-control" placeholder="등록지역을 입력하세요">
                    </div>
                    <div class="form-group">
                        <label for="filter_speed_option">속도</label>
                        <input type="text" id="filter_speed_option" class="form-control" placeholder="속도를 입력하세요">
                    </div>
                    <div class="form-group">
                        <label for="filter_service_type_internet">서비스 타입</label>
                        <select id="filter_service_type_internet" class="form-control">
                            <option value="">전체</option>
                            <option value="인터넷">인터넷</option>
                            <option value="인터넷+TV">인터넷+TV</option>
                        </select>
                    </div>
                `;
                break;
        }
        
        filtersContainer.innerHTML = filterHTML;
    }
    
    // 검색 버튼 클릭
    modalSearchBtn.addEventListener('click', function() {
        searchProductsInModal();
    });
    
    // 선택한 상품 추가 버튼 클릭
    const modalAddSelectedBtn = document.getElementById('modal_add_selected_btn');
    if (modalAddSelectedBtn) {
        modalAddSelectedBtn.addEventListener('click', function() {
            addSelectedProducts();
        });
    }
    
    // 카테고리 변경 시 자동 검색 (선택사항)
    // modalProductCategory.addEventListener('change', function() {
    //     if (modalProductCategory.value) {
    //         searchProductsInModal();
    //     }
    // });
    
    function searchProductsInModal() {
        const productCategory = modalProductCategory.value.trim();
        
        // 카테고리 선택이 없으면 오류
        if (!productCategory) {
            modalSearchResults.innerHTML = '<p class="empty-message" style="padding: 20px; text-align: center; color: #ef4444;">카테고리를 선택해주세요.</p>';
            return;
        }
        
        // 로딩 표시
        modalSearchResults.innerHTML = '<p class="empty-message" style="padding: 20px; text-align: center; color: #6b7280;">검색 중...</p>';
        
        // 검색 파라미터 구성
        const params = new URLSearchParams();
        if (productCategory) params.append('product_type', productCategory);
        
        // 카테고리별 필터 파라미터 수집
        if (productCategory === 'mvno') {
            const provider = document.getElementById('filter_provider')?.value || '';
            const contractPeriod = document.getElementById('filter_contract_period')?.value || '';
            const priceMin = document.getElementById('filter_price_min')?.value || '';
            const priceMax = document.getElementById('filter_price_max')?.value || '';
            const serviceType = document.getElementById('filter_service_type')?.value || '';
            
            if (provider) params.append('provider', provider);
            if (contractPeriod) params.append('contract_period', contractPeriod);
            if (priceMin) params.append('price_min', priceMin);
            if (priceMax) params.append('price_max', priceMax);
            if (serviceType) params.append('service_type', serviceType);
        } else if (productCategory === 'mno') {
            const deviceName = document.getElementById('filter_device_name')?.value || '';
            const deliveryMethod = document.getElementById('filter_delivery_method')?.value || '';
            const providerMno = document.getElementById('filter_provider_mno')?.value || '';
            
            if (deviceName) params.append('device_name', deviceName);
            if (deliveryMethod) params.append('delivery_method', deliveryMethod);
            if (providerMno) params.append('provider', providerMno);
        } else if (productCategory === 'mno_sim') {
            const providerSim = document.getElementById('filter_provider_sim')?.value || '';
            const contractPeriodSim = document.getElementById('filter_contract_period_sim')?.value || '';
            
            if (providerSim) params.append('provider', providerSim);
            if (contractPeriodSim) params.append('contract_period', contractPeriodSim);
        } else if (productCategory === 'internet') {
            const registrationPlace = document.getElementById('filter_registration_place')?.value || '';
            const speedOption = document.getElementById('filter_speed_option')?.value || '';
            const serviceTypeInternet = document.getElementById('filter_service_type_internet')?.value || '';
            
            if (registrationPlace) params.append('registration_place', registrationPlace);
            if (speedOption) params.append('speed_option', speedOption);
            if (serviceTypeInternet) params.append('service_type', serviceTypeInternet);
        }
        
        fetch(`../../api/search-products.php?${params.toString()}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Search response:', data);
                if (data.success && data.products && data.products.length > 0) {
                    displayModalSearchResults(data.products);
                } else {
                    const message = data.message || '검색 결과가 없습니다.';
                    modalSearchResults.innerHTML = `<p class="empty-message" style="padding: 20px; text-align: center; color: #6b7280;">${escapeHtml(message)}</p>`;
                }
            })
            .catch(error => {
                console.error('Search error:', error);
                modalSearchResults.innerHTML = '<p class="empty-message" style="padding: 20px; text-align: center; color: #ef4444;">검색 중 오류가 발생했습니다. 콘솔을 확인해주세요.</p>';
            });
    }
    
    function displayModalSearchResults(products) {
        if (products.length === 0) {
            modalSearchResults.innerHTML = '<p class="empty-message" style="padding: 20px; text-align: center; color: #6b7280;">검색 결과가 없습니다.</p>';
            currentSearchResults = [];
            return;
        }
        
        // 현재 검색 결과 저장
        currentSearchResults = products;
        
        modalSearchResults.innerHTML = '';
        modalSearchResults.className = 'modal-search-results';
        
        products.forEach(product => {
            const isAlreadyAdded = selectedProducts.some(p => p.id === product.id);
            const isModalSelected = modalSelectedProducts.some(p => p.id === product.id);
            
            // 상품 타입별 특성 정보 수집
            const productSpecs = [];
            
            // MVNO / MNO-SIM 상품 특성
            if (product.product_type === 'mvno' || product.product_type === 'mno-sim') {
                if (product.provider) {
                    productSpecs.push({ label: '통신사', value: product.provider });
                }
                if (product.data_amount) {
                    let dataValue = '';
                    if (product.data_amount === '무제한') {
                        dataValue = '무제한';
                    } else if (product.data_amount === '직접입력' && product.data_amount_value) {
                        dataValue = parseInt(product.data_amount_value).toLocaleString() + (product.data_unit || 'GB');
                    } else {
                        dataValue = product.data_amount;
                    }
                    if (dataValue) productSpecs.push({ label: '데이터', value: dataValue });
                }
                if (product.call_type) {
                    let callValue = '';
                    if (product.call_type === '무제한') {
                        callValue = '무제한';
                    } else if (product.call_type === '직접입력' && product.call_amount) {
                        callValue = parseInt(product.call_amount).toLocaleString() + '분';
                    }
                    if (callValue) productSpecs.push({ label: '통화', value: callValue });
                }
                if (product.sms_type) {
                    let smsValue = '';
                    if (product.sms_type === '무제한') {
                        smsValue = '무제한';
                    } else if (product.sms_type === '직접입력' && product.sms_amount) {
                        smsValue = parseInt(product.sms_amount).toLocaleString() + '건';
                    }
                    if (smsValue) productSpecs.push({ label: '문자', value: smsValue });
                }
                if (product.service_type) {
                    productSpecs.push({ label: '서비스', value: product.service_type });
                }
                if (product.price_after && product.price_after > 0) {
                    productSpecs.push({ label: '월 요금', value: parseInt(product.price_after).toLocaleString() + '원' });
                } else if (product.price_main && product.price_main > 0) {
                    productSpecs.push({ label: '월 요금', value: parseInt(product.price_main).toLocaleString() + '원' });
                } else {
                    productSpecs.push({ label: '월 요금', value: '공짜' });
                }
            }
            // MNO 상품 특성
            else if (product.product_type === 'mno') {
                if (product.price_main && product.price_main > 0) {
                    productSpecs.push({ label: '가격', value: parseInt(product.price_main).toLocaleString() + '원' });
                }
            }
            // Internet 상품 특성
            else if (product.product_type === 'internet') {
                if (product.registration_place) {
                    productSpecs.push({ label: '등록지역', value: product.registration_place });
                }
                if (product.speed_option) {
                    productSpecs.push({ label: '속도', value: product.speed_option });
                }
                if (product.service_type) {
                    productSpecs.push({ label: '서비스', value: product.service_type });
                }
                if (product.monthly_fee && product.monthly_fee > 0) {
                    productSpecs.push({ label: '월 요금', value: parseInt(product.monthly_fee).toLocaleString() + '원' });
                }
            }
            
            // 판매자 정보
            if (product.seller_name) {
                productSpecs.push({ label: '판매자', value: product.seller_name });
            }
            
            const item = document.createElement('div');
            item.className = 'modal-product-item' + (isAlreadyAdded ? ' already-added' : '') + (isModalSelected ? ' selected' : '');
            item.innerHTML = `
                <label class="modal-product-checkbox-label">
                    <input type="checkbox" 
                           class="modal-product-checkbox" 
                           value="${product.id}"
                           ${isAlreadyAdded ? 'disabled' : ''}
                           ${isModalSelected ? 'checked' : ''}
                           data-product-id="${product.id}">
                    <div class="modal-product-info">
                        <div class="modal-product-name">${escapeHtml(product.name)}</div>
                        <div class="modal-product-type-badge">${escapeHtml(product.type)}</div>
                        ${productSpecs.length > 0 ? `
                            <div class="modal-product-specs">
                                ${productSpecs.map(spec => `
                                    <div class="modal-product-spec-item">
                                        <span class="spec-label">${escapeHtml(spec.label)}:</span>
                                        <span class="spec-value">${escapeHtml(spec.value)}</span>
                                    </div>
                                `).join('')}
                            </div>
                        ` : ''}
                        ${isAlreadyAdded ? '<span class="already-added-badge">이미 추가됨</span>' : ''}
                    </div>
                </label>
            `;
            modalSearchResults.appendChild(item);
            
            // 체크박스 이벤트 리스너 추가
            const checkbox = item.querySelector('input[type="checkbox"]');
            if (checkbox && !isAlreadyAdded) {
                checkbox.addEventListener('change', function(e) {
                    toggleModalProduct(parseInt(this.value), this.checked);
                });
            }
        });
    }
    
    // 모달에서 상품 선택/해제
    window.toggleModalProduct = function(productId, checked) {
        const productElement = modalSearchResults.querySelector(`input[value="${productId}"]`)?.closest('.modal-product-item');
        if (!productElement) return;
        
        // 이미 추가된 상품은 선택 불가
        if (productElement.classList.contains('already-added')) {
            return;
        }
        
        // 현재 검색 결과에서 해당 상품 찾기
        const targetProduct = currentSearchResults.find(p => p.id === productId);
        if (!targetProduct) {
            console.error('Product not found in search results:', productId);
            return;
        }
        
        // 상품 정보 구성
        const productData = {
            id: targetProduct.id,
            name: targetProduct.name || '',
            type: targetProduct.type || '',
            seller_id: targetProduct.seller_id || '',
            seller_name: targetProduct.seller_name || ''
        };
        
        if (checked) {
            // 선택 추가
            if (!modalSelectedProducts.some(p => p.id === productId)) {
                modalSelectedProducts.push(productData);
            }
            productElement.classList.add('selected');
        } else {
            // 선택 해제
            modalSelectedProducts = modalSelectedProducts.filter(p => p.id !== productId);
            productElement.classList.remove('selected');
        }
    };
    
    // 선택한 상품 추가
    window.addSelectedProducts = function() {
        if (modalSelectedProducts.length === 0) {
            alert('추가할 상품을 선택해주세요.');
            return;
        }
        
        // 이미 추가된 상품 제외
        const newProducts = modalSelectedProducts.filter(modalProduct => 
            !selectedProducts.some(selected => selected.id === modalProduct.id)
        );
        
        if (newProducts.length === 0) {
            alert('추가할 새로운 상품이 없습니다.');
            return;
        }
        
        // 선택한 상품들을 추가
        newProducts.forEach(product => {
            selectedProducts.push(product);
        });
        
        updateSelectedProductsList();
        updateProductIdsInput();
        
        // 모달 닫기
        if (typeof closeProductSearchModal === 'function') {
            closeProductSearchModal();
        } else if (typeof window.closeProductSearchModal === 'function') {
            window.closeProductSearchModal();
        } else {
            productSearchModal.style.display = 'none';
            document.body.style.overflow = '';
        }
        
        // 성공 메시지
        alert(`${newProducts.length}개의 상품이 추가되었습니다.`);
    };
    
    function removeProduct(productId) {
        selectedProducts = selectedProducts.filter(p => p.id !== productId);
        updateSelectedProductsList();
        updateProductIdsInput();
    }
    
    function updateSelectedProductsList() {
        if (selectedProducts.length === 0) {
            selectedProductsList.innerHTML = '<p class="empty-message">추가된 상품이 없습니다.</p>';
            return;
        }
        
        selectedProductsList.innerHTML = '';
        
        selectedProducts.forEach((product, index) => {
            const item = document.createElement('div');
            item.className = 'selected-product-item';
            item.dataset.productId = product.id;
            item.innerHTML = `
                <span class="drag-handle">☰</span>
                <div class="product-info">
                    <div class="product-name">${escapeHtml(product.name)}</div>
                    <div class="product-details">
                        <span class="product-type">${escapeHtml(product.type)}</span>
                        ${product.seller_name ? `<span class="product-separator">|</span><span class="product-seller">${escapeHtml(product.seller_name)}</span>` : ''}
                    </div>
                </div>
                <button type="button" class="remove-btn" onclick="removeProductById(${product.id})">삭제</button>
            `;
            selectedProductsList.appendChild(item);
        });
        
        // Sortable 초기화
        if (selectedProducts.length > 0) {
            initSortable();
        }
    }
    
    function updateProductIdsInput() {
        const ids = selectedProducts.map(p => p.id);
        productIdsInput.value = ids.join(',');
        
        // hidden input 업데이트 (폼 제출용)
        const hiddenInputs = document.querySelectorAll('input[name="product_ids[]"]');
        hiddenInputs.forEach(input => {
            if (input !== productIdsInput) {
                input.remove();
            }
        });
        
        ids.forEach(id => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'product_ids[]';
            input.value = id;
            productIdsInput.parentNode.appendChild(input);
        });
    }
    
    function initSortable() {
        if (selectedProductsList.sortableInstance) {
            selectedProductsList.sortableInstance.destroy();
        }
        
        selectedProductsList.sortableInstance = new Sortable(selectedProductsList, {
            handle: '.drag-handle',
            animation: 150,
            onEnd: function(evt) {
                const oldIndex = evt.oldIndex;
                const newIndex = evt.newIndex;
                
                // 배열 재정렬
                const movedProduct = selectedProducts.splice(oldIndex, 1)[0];
                selectedProducts.splice(newIndex, 0, movedProduct);
                
                updateProductIdsInput();
            }
        });
    }
    
    window.removeProductById = function(productId) {
        removeProduct(productId);
    };
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
});
</script>

<?php include __DIR__ . '/../includes/admin-footer.php'; ?>

