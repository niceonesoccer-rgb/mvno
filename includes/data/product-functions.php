<?php
/**
 * 상품 관련 데이터베이스 함수
 */

require_once __DIR__ . '/db-config.php';

/**
 * products 테이블 생성 (공통 함수)
 * @param PDO $pdo 데이터베이스 연결
 * @return bool 성공 여부
 */
function ensureProductsTable($pdo) {
    try {
        if (!$pdo->query("SHOW TABLES LIKE 'products'")->fetch()) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `products` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `seller_id` INT(11) UNSIGNED NOT NULL COMMENT '판매자 ID',
                    `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입',
                    `status` ENUM('active', 'inactive', 'deleted') NOT NULL DEFAULT 'active' COMMENT '상품 상태',
                    `view_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '조회수',
                    `favorite_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '찜 수',
                    `review_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '리뷰 수',
                    `share_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '공유 수',
                    `application_count` INT(11) UNSIGNED NOT NULL DEFAULT 0 COMMENT '신청 수',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '생성일시',
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '수정일시',
                    PRIMARY KEY (`id`),
                    KEY `idx_seller_id` (`seller_id`),
                    KEY `idx_product_type` (`product_type`),
                    KEY `idx_status` (`status`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='상품 기본 정보'
            ");
            error_log("products 테이블이 자동으로 생성되었습니다.");
        }
        return true;
    } catch (PDOException $e) {
        error_log("products 테이블 생성 중 오류: " . $e->getMessage());
        return false;
    }
}

/**
 * MVNO 상품 저장
 * @param array $productData 상품 데이터
 * @return int|false 상품 ID 또는 false
 */
function saveMvnoProduct($productData) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        $isEditMode = isset($productData['product_id']) && $productData['product_id'] > 0;
        $productId = $isEditMode ? $productData['product_id'] : null;
        
        try {
            if ($isEditMode) {
                // 수정 모드: 기존 상품 정보 업데이트
                $status = isset($productData['status']) && in_array($productData['status'], ['active', 'inactive']) ? $productData['status'] : null;
                if ($status) {
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET seller_id = :seller_id, product_type = 'mvno', status = :status
                        WHERE id = :product_id AND seller_id = :seller_id_check
                    ");
                    $stmt->execute([
                        ':seller_id' => $productData['seller_id'],
                        ':product_id' => $productId,
                        ':seller_id_check' => $productData['seller_id'],
                        ':status' => $status
                    ]);
                } else {
                    $stmt = $pdo->prepare("
                        UPDATE products 
                        SET seller_id = :seller_id, product_type = 'mvno'
                        WHERE id = :product_id AND seller_id = :seller_id_check
                    ");
                    $stmt->execute([
                        ':seller_id' => $productData['seller_id'],
                        ':product_id' => $productId,
                        ':seller_id_check' => $productData['seller_id']
                    ]);
                }
            } else {
                // 등록 모드: 새 상품 정보 저장
                $status = isset($productData['status']) && in_array($productData['status'], ['active', 'inactive']) ? $productData['status'] : 'active';
                $stmt = $pdo->prepare("
                    INSERT INTO products (seller_id, product_type, status, view_count)
                    VALUES (:seller_id, 'mvno', :status, 0)
                ");
                $stmt->execute([
                    ':seller_id' => $productData['seller_id'],
                    ':status' => $status
                ]);
                $productId = $pdo->lastInsertId();
            }
        } catch (PDOException $e) {
            $errorMsg = "Error in first query (products table): " . $e->getMessage();
            $errorMsg .= "\nQuery type: " . ($isEditMode ? "UPDATE" : "INSERT");
            $errorMsg .= "\nProduct ID: " . $productId;
            error_log($errorMsg);
            
            global $lastDbError;
            $lastDbError = $errorMsg;
            throw $e;
        }
        
        // 상세 정보 존재 여부 확인 (수정 모드일 경우)
        $detailExists = false;
        if ($isEditMode) {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product_mvno_details WHERE product_id = :product_id");
            $checkStmt->execute([':product_id' => $productId]);
            $detailExists = $checkStmt->fetchColumn() > 0;
            
            // redirect_url 컬럼 확인 및 추가
            $checkRedirectUrl = $pdo->query("SHOW COLUMNS FROM product_mvno_details LIKE 'redirect_url'");
            if (!$checkRedirectUrl->fetch()) {
                $pdo->exec("ALTER TABLE product_mvno_details ADD COLUMN redirect_url VARCHAR(500) DEFAULT NULL COMMENT '신청 후 리다이렉트 URL'");
                error_log("product_mvno_details 테이블에 redirect_url 컬럼이 추가되었습니다.");
            }
        } else {
            // 등록 모드에서도 redirect_url 컬럼 확인
            $checkRedirectUrl = $pdo->query("SHOW COLUMNS FROM product_mvno_details LIKE 'redirect_url'");
            if (!$checkRedirectUrl->fetch()) {
                $pdo->exec("ALTER TABLE product_mvno_details ADD COLUMN redirect_url VARCHAR(500) DEFAULT NULL COMMENT '신청 후 리다이렉트 URL'");
                error_log("product_mvno_details 테이블에 redirect_url 컬럼이 추가되었습니다.");
            }
        }
        
        // price_after 처리: price_after_type_hidden이 'free'이면 null, 그 외에는 숫자로 변환 (0도 포함)
        $priceAfterValue = null;
        if (isset($productData['price_after_type_hidden']) && $productData['price_after_type_hidden'] === 'free') {
            // 공짜 선택 시 null로 저장
            $priceAfterValue = null;
        } elseif (isset($productData['price_after']) && $productData['price_after'] !== '' && $productData['price_after'] !== null && $productData['price_after'] !== 'free') {
            // 직접입력 시 숫자로 변환 (0도 포함)
            $priceAfterValue = floatval($productData['price_after']);
        }
        
        // 파라미터 배열 준비 (공통)
        $params = [
            ':provider' => $productData['provider'] ?? '',
            ':service_type' => $productData['service_type'] ?? null,
            ':plan_name' => $productData['plan_name'] ?? '',
            ':contract_period' => $productData['contract_period'] ?? null,
            ':contract_period_days' => $productData['contract_period_days'] ?? null,
            ':discount_period' => $productData['discount_period'] ?? null,
            ':price_main' => isset($productData['price_main']) ? floatval($productData['price_main']) : 0,
            ':price_after' => $priceAfterValue,
            ':data_amount' => $productData['data_amount'] ?? null,
            ':data_amount_value' => $productData['data_amount_value'] ?? null,
            ':data_unit' => $productData['data_unit'] ?? null,
            ':data_additional' => $productData['data_additional'] ?? null,
            ':data_additional_value' => $productData['data_additional_value'] ?? null,
            ':data_exhausted' => $productData['data_exhausted'] ?? null,
            ':data_exhausted_value' => $productData['data_exhausted_value'] ?? null,
            ':call_type' => $productData['call_type'] ?? null,
            ':call_amount' => $productData['call_amount'] ?? null,
            ':additional_call_type' => $productData['additional_call_type'] ?? null,
            ':additional_call' => $productData['additional_call'] ?? null,
            ':sms_type' => $productData['sms_type'] ?? null,
            ':sms_amount' => $productData['sms_amount'] ?? null,
            ':mobile_hotspot' => $productData['mobile_hotspot'] ?? null,
            ':mobile_hotspot_value' => $productData['mobile_hotspot_value'] ?? null,
            ':regular_sim_available' => $productData['regular_sim_available'] ?? null,
            ':regular_sim_price' => $productData['regular_sim_price'] ?? null,
            ':nfc_sim_available' => $productData['nfc_sim_available'] ?? null,
            ':nfc_sim_price' => $productData['nfc_sim_price'] ?? null,
            ':esim_available' => $productData['esim_available'] ?? null,
            ':esim_price' => $productData['esim_price'] ?? null,
            ':over_data_price' => $productData['over_data_price'] ?? null,
            ':over_voice_price' => $productData['over_voice_price'] ?? null,
            ':over_video_price' => $productData['over_video_price'] ?? null,
            ':over_sms_price' => $productData['over_sms_price'] ?? null,
            ':over_lms_price' => $productData['over_lms_price'] ?? null,
            ':over_mms_price' => $productData['over_mms_price'] ?? null,
            ':promotion_title' => $productData['promotion_title'] ?? null,
            ':promotions' => !empty($productData['promotions']) ? json_encode($productData['promotions']) : null,
            ':benefits' => !empty($productData['benefits']) ? json_encode($productData['benefits']) : null,
            ':redirect_url' => !empty($productData['redirect_url']) ? trim($productData['redirect_url']) : null,
        ];
        
        // 쿼리별 파라미터 배열 준비
        if ($isEditMode && $detailExists) {
            // 수정 모드: 상세 정보 업데이트
            $updateParams = array_merge($params, [':product_id' => $productId]);
            $queryString = "
                UPDATE product_mvno_details SET
                provider = :provider, service_type = :service_type, plan_name = :plan_name, contract_period = :contract_period,
                contract_period_days = :contract_period_days, discount_period = :discount_period, price_main = :price_main, price_after = :price_after,
                data_amount = :data_amount, data_amount_value = :data_amount_value, data_unit = :data_unit, data_additional = :data_additional, data_additional_value = :data_additional_value, data_exhausted = :data_exhausted, data_exhausted_value = :data_exhausted_value,
                call_type = :call_type, call_amount = :call_amount, additional_call_type = :additional_call_type, additional_call = :additional_call,
                sms_type = :sms_type, sms_amount = :sms_amount, mobile_hotspot = :mobile_hotspot, mobile_hotspot_value = :mobile_hotspot_value,
                regular_sim_available = :regular_sim_available, regular_sim_price = :regular_sim_price, nfc_sim_available = :nfc_sim_available, nfc_sim_price = :nfc_sim_price,
                esim_available = :esim_available, esim_price = :esim_price, over_data_price = :over_data_price, over_voice_price = :over_voice_price,
                over_video_price = :over_video_price, over_sms_price = :over_sms_price, over_lms_price = :over_lms_price, over_mms_price = :over_mms_price,
                promotion_title = :promotion_title, promotions = :promotions, benefits = :benefits, redirect_url = :redirect_url
                WHERE product_id = :product_id
            ";
            $stmt = $pdo->prepare($queryString);
            $executeParams = $updateParams;
        } else {
            // 등록 모드: 상세 정보 저장
            $insertParams = array_merge($params, [':product_id' => $productId]);
            $queryString = "
                INSERT INTO product_mvno_details (
                product_id, provider, service_type, plan_name, contract_period,
                contract_period_days, discount_period, price_main, price_after,
                data_amount, data_amount_value, data_unit, data_additional, data_additional_value, data_exhausted, data_exhausted_value,
                call_type, call_amount, additional_call_type, additional_call,
                sms_type, sms_amount, mobile_hotspot, mobile_hotspot_value,
                regular_sim_available, regular_sim_price, nfc_sim_available, nfc_sim_price,
                esim_available, esim_price, over_data_price, over_voice_price,
                over_video_price, over_sms_price, over_lms_price, over_mms_price,
                promotion_title, promotions, benefits, redirect_url
            ) VALUES (
                :product_id, :provider, :service_type, :plan_name, :contract_period,
                :contract_period_days, :discount_period, :price_main, :price_after,
                :data_amount, :data_amount_value, :data_unit, :data_additional, :data_additional_value, :data_exhausted, :data_exhausted_value,
                :call_type, :call_amount, :additional_call_type, :additional_call,
                :sms_type, :sms_amount, :mobile_hotspot, :mobile_hotspot_value,
                :regular_sim_available, :regular_sim_price, :nfc_sim_available, :nfc_sim_price,
                :esim_available, :esim_price, :over_data_price, :over_voice_price,
                :over_video_price, :over_sms_price, :over_lms_price, :over_mms_price,
                :promotion_title, :promotions, :benefits, :redirect_url
            )
        ";
            $stmt = $pdo->prepare($queryString);
            $executeParams = $insertParams;
        }
        
        try {
            $stmt->execute($executeParams);
        } catch (PDOException $e) {
            // 에러 정보 로깅
            $errorMsg = "Error executing query: " . $e->getMessage();
            $errorMsg .= "\nSQL State: " . $e->getCode();
            $errorMsg .= "\nQuery type: " . ($isEditMode && $detailExists ? "UPDATE" : "INSERT");
            $errorMsg .= "\nProduct ID: " . $productId;
            error_log($errorMsg);
            
            // 전역 변수에 저장하여 API에서 접근 가능하도록
            global $lastDbError;
            $lastDbError = $errorMsg;
            
            throw $e;
        }
        
        $pdo->commit();
        return $productId;
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        global $lastDbError;
        // $lastDbError가 이미 상세 정보로 설정되어 있으면 그대로 사용
        if (!isset($lastDbError) || empty($lastDbError) || $lastDbError === $e->getMessage()) {
            $errorMsg = "Error saving MVNO product: " . $e->getMessage();
            $errorMsg .= "\nSQL State: " . $e->getCode();
            $errorMsg .= "\nStack trace: " . $e->getTraceAsString();
            $errorMsg .= "\nProduct data: " . json_encode($productData);
            error_log($errorMsg);
            $lastDbError = $errorMsg;
        }
        
        return false;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Unexpected error saving MVNO product: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        return false;
    }
}

/**
 * MNO 상품 저장
 * @param array $productData 상품 데이터
 * @return int|false 상품 ID 또는 false
 */
function saveMnoProduct($productData) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    // 테이블 존재 여부 확인 및 생성
    try {
        $checkTable = $pdo->query("SHOW TABLES LIKE 'product_mno_details'");
        $tableExists = $checkTable->fetch();
        
        // 테이블이 존재하면 device_id 컬럼 확인 및 추가
        if ($tableExists) {
            $checkColumn = $pdo->query("SHOW COLUMNS FROM product_mno_details LIKE 'device_id'");
            if (!$checkColumn->fetch()) {
                // device_id 컬럼 추가
                $pdo->exec("ALTER TABLE product_mno_details ADD COLUMN device_id INT(11) UNSIGNED DEFAULT NULL COMMENT '단말기 ID' AFTER product_id");
                error_log("product_mno_details 테이블에 device_id 컬럼이 추가되었습니다.");
            }
            
            // redirect_url 컬럼 확인 및 추가
            $checkRedirectUrl = $pdo->query("SHOW COLUMNS FROM product_mno_details LIKE 'redirect_url'");
            if (!$checkRedirectUrl->fetch()) {
                // redirect_url 컬럼 추가
                $pdo->exec("ALTER TABLE product_mno_details ADD COLUMN redirect_url VARCHAR(500) DEFAULT NULL COMMENT '신청 후 리다이렉트 URL' AFTER visit_region");
                error_log("product_mno_details 테이블에 redirect_url 컬럼이 추가되었습니다.");
            }
        }
        
        if (!$tableExists) {
            // product_mno_details 테이블 생성
            $createTableSQL = "
            CREATE TABLE IF NOT EXISTS `product_mno_details` (
                `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                `device_id` INT(11) UNSIGNED DEFAULT NULL COMMENT '단말기 ID',
                `device_name` VARCHAR(100) NOT NULL COMMENT '단말기명',
                `device_price` DECIMAL(12,2) DEFAULT NULL COMMENT '단말기 출고가',
                `device_capacity` VARCHAR(20) DEFAULT NULL COMMENT '용량',
                `device_colors` TEXT DEFAULT NULL COMMENT '단말기 색상 목록 (JSON)',
                `common_provider` TEXT DEFAULT NULL COMMENT '공통지원할인 통신사 (JSON)',
                `common_discount_new` TEXT DEFAULT NULL COMMENT '공통지원할인 신규가입 (JSON)',
                `common_discount_port` TEXT DEFAULT NULL COMMENT '공통지원할인 번호이동 (JSON)',
                `common_discount_change` TEXT DEFAULT NULL COMMENT '공통지원할인 기기변경 (JSON)',
                `contract_provider` TEXT DEFAULT NULL COMMENT '선택약정할인 통신사 (JSON)',
                `contract_discount_new` TEXT DEFAULT NULL COMMENT '선택약정할인 신규가입 (JSON)',
                `contract_discount_port` TEXT DEFAULT NULL COMMENT '선택약정할인 번호이동 (JSON)',
                `contract_discount_change` TEXT DEFAULT NULL COMMENT '선택약정할인 기기변경 (JSON)',
                `service_type` VARCHAR(50) DEFAULT NULL COMMENT '서비스 타입',
                `contract_period` VARCHAR(50) DEFAULT NULL COMMENT '약정기간',
                `contract_period_value` VARCHAR(20) DEFAULT NULL COMMENT '약정기간 값',
                `price_main` DECIMAL(10,2) DEFAULT NULL COMMENT '기본 요금',
                `data_amount` VARCHAR(50) DEFAULT NULL COMMENT '데이터량',
                `data_amount_value` VARCHAR(20) DEFAULT NULL COMMENT '데이터량 값',
                `data_unit` VARCHAR(10) DEFAULT NULL COMMENT '데이터 단위',
                `data_exhausted` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시',
                `data_exhausted_value` VARCHAR(50) DEFAULT NULL COMMENT '데이터 소진 시 값',
                `call_type` VARCHAR(50) DEFAULT NULL COMMENT '통화 타입',
                `call_amount` VARCHAR(20) DEFAULT NULL COMMENT '통화량',
                `additional_call_type` VARCHAR(50) DEFAULT NULL COMMENT '추가 통화 타입',
                `additional_call` VARCHAR(20) DEFAULT NULL COMMENT '추가 통화량',
                `sms_type` VARCHAR(50) DEFAULT NULL COMMENT 'SMS 타입',
                `sms_amount` VARCHAR(20) DEFAULT NULL COMMENT 'SMS량',
                `mobile_hotspot` VARCHAR(50) DEFAULT NULL COMMENT '모바일 핫스팟',
                `mobile_hotspot_value` VARCHAR(20) DEFAULT NULL COMMENT '모바일 핫스팟 값',
                `regular_sim_available` VARCHAR(10) DEFAULT NULL COMMENT '일반 SIM 가능 여부',
                `regular_sim_price` VARCHAR(20) DEFAULT NULL COMMENT '일반 SIM 가격',
                `nfc_sim_available` VARCHAR(10) DEFAULT NULL COMMENT 'NFC SIM 가능 여부',
                `nfc_sim_price` VARCHAR(20) DEFAULT NULL COMMENT 'NFC SIM 가격',
                `esim_available` VARCHAR(10) DEFAULT NULL COMMENT 'eSIM 가능 여부',
                `esim_price` VARCHAR(20) DEFAULT NULL COMMENT 'eSIM 가격',
                `over_data_price` VARCHAR(20) DEFAULT NULL COMMENT '데이터 초과 시 가격',
                `over_voice_price` VARCHAR(20) DEFAULT NULL COMMENT '음성 초과 시 가격',
                `over_video_price` VARCHAR(20) DEFAULT NULL COMMENT '영상통화 초과 시 가격',
                `over_sms_price` VARCHAR(20) DEFAULT NULL COMMENT 'SMS 초과 시 가격',
                `over_lms_price` VARCHAR(20) DEFAULT NULL COMMENT 'LMS 초과 시 가격',
                `over_mms_price` VARCHAR(20) DEFAULT NULL COMMENT 'MMS 초과 시 가격',
                `promotion_title` VARCHAR(200) DEFAULT NULL COMMENT '프로모션 제목',
                `promotions` TEXT DEFAULT NULL COMMENT '프로모션 목록 (JSON)',
                `benefits` TEXT DEFAULT NULL COMMENT '혜택 목록 (JSON)',
                `delivery_method` VARCHAR(20) DEFAULT 'delivery' COMMENT '배송 방법',
                `visit_region` VARCHAR(50) DEFAULT NULL COMMENT '방문 지역',
                `redirect_url` VARCHAR(500) DEFAULT NULL COMMENT '신청 후 리다이렉트 URL',
                `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_product_id` (`product_id`),
                KEY `idx_device_name` (`device_name`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='MNO 상품 상세 정보';
            ";
            $pdo->exec($createTableSQL);
            error_log("product_mno_details 테이블이 자동으로 생성되었습니다.");
        }
        
        // products 테이블 확인 및 생성
        ensureProductsTable($pdo);
    } catch (PDOException $e) {
        error_log("테이블 생성 중 오류: " . $e->getMessage());
        // 테이블 생성 실패해도 계속 진행 (외래키 제약조건이 있을 수 있음)
    }
    
    try {
        $pdo->beginTransaction();
        
        $isEditMode = isset($productData['product_id']) && $productData['product_id'] > 0;
        
        if ($isEditMode) {
            // 수정 모드: 기존 상품 업데이트
            $productId = $productData['product_id'];
            
            // 상품 소유권 확인
            $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ? AND product_type = 'mno'");
            $checkStmt->execute([$productId, $productData['seller_id']]);
            if (!$checkStmt->fetch()) {
                throw new Exception("상품을 찾을 수 없거나 수정 권한이 없습니다.");
            }
            
            // 상태 업데이트 (제공된 경우)
            if (isset($productData['status']) && in_array($productData['status'], ['active', 'inactive'])) {
                $statusStmt = $pdo->prepare("UPDATE products SET status = ? WHERE id = ?");
                $statusStmt->execute([$productData['status'], $productId]);
            }
        } else {
            // 1. 기본 상품 정보 저장
            $status = isset($productData['status']) && in_array($productData['status'], ['active', 'inactive']) ? $productData['status'] : 'active';
            $stmt = $pdo->prepare("
                INSERT INTO products (seller_id, product_type, status, view_count)
                VALUES (:seller_id, 'mno', :status, 0)
            ");
            $stmt->execute([
                ':seller_id' => $productData['seller_id'],
                ':status' => $status
            ]);
            $productId = $pdo->lastInsertId();
        }
        
        // 2. MNO 상세 정보 저장/업데이트
        // 공통 파라미터 배열 생성
        $executeParams = [
            ':product_id' => $productId,
            ':device_id' => $productData['device_id'] ?? null,
            ':device_name' => $productData['device_name'] ?? '',
            ':device_price' => isset($productData['device_price']) && $productData['device_price'] !== '' ? floatval($productData['device_price']) : null,
            ':device_capacity' => $productData['device_capacity'] ?? null,
            ':device_colors' => !empty($productData['device_colors']) ? json_encode($productData['device_colors']) : null,
            ':common_provider' => !empty($productData['common_provider']) ? json_encode($productData['common_provider']) : null,
            ':common_discount_new' => !empty($productData['common_discount_new']) ? json_encode($productData['common_discount_new']) : null,
            ':common_discount_port' => !empty($productData['common_discount_port']) ? json_encode($productData['common_discount_port']) : null,
            ':common_discount_change' => !empty($productData['common_discount_change']) ? json_encode($productData['common_discount_change']) : null,
            ':contract_provider' => !empty($productData['contract_provider']) ? json_encode($productData['contract_provider']) : null,
            ':contract_discount_new' => !empty($productData['contract_discount_new']) ? json_encode($productData['contract_discount_new']) : null,
            ':contract_discount_port' => !empty($productData['contract_discount_port']) ? json_encode($productData['contract_discount_port']) : null,
            ':contract_discount_change' => !empty($productData['contract_discount_change']) ? json_encode($productData['contract_discount_change']) : null,
            ':service_type' => $productData['service_type'] ?? null,
            ':contract_period' => $productData['contract_period'] ?? null,
            ':contract_period_value' => $productData['contract_period_value'] ?? null,
            ':price_main' => isset($productData['price_main']) && $productData['price_main'] !== '' ? floatval($productData['price_main']) : null,
            ':data_amount' => $productData['data_amount'] ?? null,
            ':data_amount_value' => $productData['data_amount_value'] ?? null,
            ':data_unit' => $productData['data_unit'] ?? null,
            ':data_exhausted' => $productData['data_exhausted'] ?? null,
            ':data_exhausted_value' => $productData['data_exhausted_value'] ?? null,
            ':call_type' => $productData['call_type'] ?? null,
            ':call_amount' => $productData['call_amount'] ?? null,
            ':additional_call_type' => $productData['additional_call_type'] ?? null,
            ':additional_call' => $productData['additional_call'] ?? null,
            ':sms_type' => $productData['sms_type'] ?? null,
            ':sms_amount' => $productData['sms_amount'] ?? null,
            ':mobile_hotspot' => $productData['mobile_hotspot'] ?? null,
            ':mobile_hotspot_value' => $productData['mobile_hotspot_value'] ?? null,
            ':regular_sim_available' => $productData['regular_sim_available'] ?? null,
            ':regular_sim_price' => $productData['regular_sim_price'] ?? null,
            ':nfc_sim_available' => $productData['nfc_sim_available'] ?? null,
            ':nfc_sim_price' => $productData['nfc_sim_price'] ?? null,
            ':esim_available' => $productData['esim_available'] ?? null,
            ':esim_price' => $productData['esim_price'] ?? null,
            ':over_data_price' => $productData['over_data_price'] ?? null,
            ':over_voice_price' => $productData['over_voice_price'] ?? null,
            ':over_video_price' => $productData['over_video_price'] ?? null,
            ':over_sms_price' => $productData['over_sms_price'] ?? null,
            ':over_lms_price' => $productData['over_lms_price'] ?? null,
            ':over_mms_price' => $productData['over_mms_price'] ?? null,
            ':promotion_title' => $productData['promotion_title'] ?? null,
            ':promotions' => !empty($productData['promotions']) ? json_encode($productData['promotions']) : null,
            ':benefits' => !empty($productData['benefits']) ? json_encode($productData['benefits']) : null,
            ':delivery_method' => $productData['delivery_method'] ?? 'delivery',
            ':visit_region' => $productData['visit_region'] ?? null,
            ':redirect_url' => !empty($productData['redirect_url']) ? trim($productData['redirect_url']) : null,
        ];
        
        if ($isEditMode) {
            // 업데이트
            $stmt = $pdo->prepare("
                UPDATE product_mno_details SET
                    device_id = :device_id,
                    device_name = :device_name,
                    device_price = :device_price,
                    device_capacity = :device_capacity,
                    device_colors = :device_colors,
                    common_provider = :common_provider,
                    common_discount_new = :common_discount_new,
                    common_discount_port = :common_discount_port,
                    common_discount_change = :common_discount_change,
                    contract_provider = :contract_provider,
                    contract_discount_new = :contract_discount_new,
                    contract_discount_port = :contract_discount_port,
                    contract_discount_change = :contract_discount_change,
                    service_type = :service_type,
                    contract_period = :contract_period,
                    contract_period_value = :contract_period_value,
                    price_main = :price_main,
                    data_amount = :data_amount,
                    data_amount_value = :data_amount_value,
                    data_unit = :data_unit,
                    data_exhausted = :data_exhausted,
                    data_exhausted_value = :data_exhausted_value,
                    call_type = :call_type,
                    call_amount = :call_amount,
                    additional_call_type = :additional_call_type,
                    additional_call = :additional_call,
                    sms_type = :sms_type,
                    sms_amount = :sms_amount,
                    mobile_hotspot = :mobile_hotspot,
                    mobile_hotspot_value = :mobile_hotspot_value,
                    regular_sim_available = :regular_sim_available,
                    regular_sim_price = :regular_sim_price,
                    nfc_sim_available = :nfc_sim_available,
                    nfc_sim_price = :nfc_sim_price,
                    esim_available = :esim_available,
                    esim_price = :esim_price,
                    over_data_price = :over_data_price,
                    over_voice_price = :over_voice_price,
                    over_video_price = :over_video_price,
                    over_sms_price = :over_sms_price,
                    over_lms_price = :over_lms_price,
                    over_mms_price = :over_mms_price,
                    promotion_title = :promotion_title,
                    promotions = :promotions,
                    benefits = :benefits,
                    delivery_method = :delivery_method,
                    visit_region = :visit_region,
                    redirect_url = :redirect_url
                WHERE product_id = :product_id
            ");
            $stmt->execute($executeParams);
        } else {
            // 신규 등록
            $stmt = $pdo->prepare("
                INSERT INTO product_mno_details (
                product_id, device_id, device_name, device_price, device_capacity, device_colors,
                common_provider, common_discount_new, common_discount_port, common_discount_change,
                contract_provider, contract_discount_new, contract_discount_port, contract_discount_change,
                service_type, contract_period, contract_period_value, price_main,
                data_amount, data_amount_value, data_unit, data_exhausted, data_exhausted_value,
                call_type, call_amount, additional_call_type, additional_call,
                sms_type, sms_amount, mobile_hotspot, mobile_hotspot_value,
                regular_sim_available, regular_sim_price, nfc_sim_available, nfc_sim_price,
                esim_available, esim_price, over_data_price, over_voice_price,
                over_video_price, over_sms_price, over_lms_price, over_mms_price,
                promotion_title, promotions, benefits, delivery_method, visit_region, redirect_url
            ) VALUES (
                :product_id, :device_id, :device_name, :device_price, :device_capacity, :device_colors,
                :common_provider, :common_discount_new, :common_discount_port, :common_discount_change,
                :contract_provider, :contract_discount_new, :contract_discount_port, :contract_discount_change,
                :service_type, :contract_period, :contract_period_value, :price_main,
                :data_amount, :data_amount_value, :data_unit, :data_exhausted, :data_exhausted_value,
                :call_type, :call_amount, :additional_call_type, :additional_call,
                :sms_type, :sms_amount, :mobile_hotspot, :mobile_hotspot_value,
                :regular_sim_available, :regular_sim_price, :nfc_sim_available, :nfc_sim_price,
                :esim_available, :esim_price, :over_data_price, :over_voice_price,
                :over_video_price, :over_sms_price, :over_lms_price, :over_mms_price,
                :promotion_title, :promotions, :benefits, :delivery_method, :visit_region, :redirect_url
            )
        ");
            $stmt->execute($executeParams);
        }
        
        $pdo->commit();
        return $productId;
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $errorMsg = "Error saving MNO product: " . $e->getMessage();
        $errorMsg .= "\nSQL State: " . $e->getCode();
        $errorMsg .= "\nError Info: " . json_encode($pdo->errorInfo() ?? []);
        $errorMsg .= "\nStack trace: " . $e->getTraceAsString();
        $errorMsg .= "\nProduct data: " . json_encode($productData);
        
        error_log($errorMsg);
        
        // 전역 변수에 에러 정보 저장
        global $lastDbError;
        $lastDbError = "PDO 오류: " . $e->getMessage() . " (SQL State: " . $e->getCode() . ")";
        
        return false;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $errorMsg = "Unexpected error saving MNO product: " . $e->getMessage();
        $errorMsg .= "\nStack trace: " . $e->getTraceAsString();
        error_log($errorMsg);
        
        global $lastDbError;
        $lastDbError = "예외 발생: " . $e->getMessage();
        
        return false;
    }
}

/**
 * Internet 상품 저장
 * @param array $productData 상품 데이터
 * @return int|false 상품 ID 또는 false
 */
function saveInternetProduct($productData) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    // 테이블 자동 생성
    try {
        if (!$pdo->query("SHOW TABLES LIKE 'product_internet_details'")->fetch()) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `product_internet_details` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                    `registration_place` VARCHAR(50) NOT NULL COMMENT '인터넷가입처',
                    `speed_option` VARCHAR(20) DEFAULT NULL COMMENT '가입속도',
                    `monthly_fee` DECIMAL(10,2) NOT NULL DEFAULT 0.00 COMMENT '월 요금제',
                    `cash_payment_names` TEXT DEFAULT NULL COMMENT '현금지급 항목명 (JSON)',
                    `cash_payment_prices` TEXT DEFAULT NULL COMMENT '현금지급 가격 (JSON)',
                    `gift_card_names` TEXT DEFAULT NULL COMMENT '상품권 지급 항목명 (JSON)',
                    `gift_card_prices` TEXT DEFAULT NULL COMMENT '상품권 지급 가격 (JSON)',
                    `equipment_names` TEXT DEFAULT NULL COMMENT '장비 제공 항목명 (JSON)',
                    `equipment_prices` TEXT DEFAULT NULL COMMENT '장비 제공 가격 (JSON)',
                    `installation_names` TEXT DEFAULT NULL COMMENT '설치 및 기타 서비스 항목명 (JSON)',
                    `installation_prices` TEXT DEFAULT NULL COMMENT '설치 및 기타 서비스 가격 (JSON)',
                    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`),
                    UNIQUE KEY `uk_product_id` (`product_id`),
                    KEY `idx_registration_place` (`registration_place`),
                    KEY `idx_speed_option` (`speed_option`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Internet 상품 상세 정보'
            ");
        }
        
        // products 테이블 확인 및 생성
        ensureProductsTable($pdo);
    } catch (PDOException $e) {
        error_log("테이블 생성 중 오류: " . $e->getMessage());
    }
    
    try {
        $pdo->beginTransaction();
        
        $isEditMode = isset($productData['product_id']) && $productData['product_id'] > 0;
        $productId = $isEditMode ? $productData['product_id'] : null;
        
        if ($isEditMode) {
            $status = isset($productData['status']) && in_array($productData['status'], ['active', 'inactive']) ? $productData['status'] : 'active';
            
            $checkStmt = $pdo->prepare("SELECT id FROM products WHERE id = ? AND seller_id = ? AND product_type = 'internet'");
            $checkStmt->execute([$productId, $productData['seller_id']]);
            if (!$checkStmt->fetch()) {
                throw new Exception("상품을 찾을 수 없거나 수정 권한이 없습니다.");
            }
            
            $stmt = $pdo->prepare("UPDATE products SET status = :status WHERE id = :product_id AND seller_id = :seller_id");
            $stmt->execute([
                ':status' => $status,
                ':product_id' => $productId,
                ':seller_id' => $productData['seller_id']
            ]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO products (seller_id, product_type, status, view_count) VALUES (:seller_id, 'internet', 'active', 0)");
            $stmt->execute([':seller_id' => $productData['seller_id']]);
            $productId = $pdo->lastInsertId();
        }
        
        // 가격 데이터 처리 및 필터링
        $processPrices = function($prices) {
            if (empty($prices) || !is_array($prices)) return [];
            return array_map(function($price) {
                if (empty($price)) return '';
                return preg_replace('/[^0-9.-]/', '', str_replace(',', '', $price)) ?: '';
            }, $prices);
        };
        
        $filterArrays = function($names, $prices) {
            $filtered = ['names' => [], 'prices' => []];
            if (empty($names) || !is_array($names)) return $filtered;
            foreach ($names as $index => $name) {
                if (!empty(trim($name))) {
                    $filtered['names'][] = trim($name);
                    $filtered['prices'][] = $prices[$index] ?? '';
                }
            }
            return $filtered;
        };
        
        $cashData = $filterArrays(
            $productData['cash_payment_names'] ?? [],
            $processPrices($productData['cash_payment_prices'] ?? [])
        );
        $giftCardData = $filterArrays(
            $productData['gift_card_names'] ?? [],
            $processPrices($productData['gift_card_prices'] ?? [])
        );
        $equipmentData = $filterArrays(
            $productData['equipment_names'] ?? [],
            $processPrices($productData['equipment_prices'] ?? [])
        );
        $installationData = $filterArrays(
            $productData['installation_names'] ?? [],
            $processPrices($productData['installation_prices'] ?? [])
        );
        
        // 상세 정보 저장/업데이트
        $detailExists = false;
        if ($isEditMode) {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product_internet_details WHERE product_id = :product_id");
            $checkStmt->execute([':product_id' => $productId]);
            $detailExists = $checkStmt->fetchColumn() > 0;
        }
        
        if ($isEditMode && $detailExists) {
            $stmt = $pdo->prepare("
                UPDATE product_internet_details SET
                    registration_place = :registration_place,
                    speed_option = :speed_option,
                    monthly_fee = :monthly_fee,
                    cash_payment_names = :cash_payment_names,
                    cash_payment_prices = :cash_payment_prices,
                    gift_card_names = :gift_card_names,
                    gift_card_prices = :gift_card_prices,
                    equipment_names = :equipment_names,
                    equipment_prices = :equipment_prices,
                    installation_names = :installation_names,
                    installation_prices = :installation_prices
                WHERE product_id = :product_id
            ");
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO product_internet_details (
                    product_id, registration_place, speed_option, monthly_fee,
                    cash_payment_names, cash_payment_prices,
                    gift_card_names, gift_card_prices,
                    equipment_names, equipment_prices,
                    installation_names, installation_prices
                ) VALUES (
                    :product_id, :registration_place, :speed_option, :monthly_fee,
                    :cash_payment_names, :cash_payment_prices,
                    :gift_card_names, :gift_card_prices,
                    :equipment_names, :equipment_prices,
                    :installation_names, :installation_prices
                )
            ");
        }
        
        $stmt->execute([
            ':product_id' => $productId,
            ':registration_place' => $productData['registration_place'] ?? '',
            ':speed_option' => $productData['speed_option'] ?? null,
            ':monthly_fee' => $productData['monthly_fee'] ?? 0,
            ':cash_payment_names' => json_encode($cashData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':cash_payment_prices' => json_encode($cashData['prices'] ?? [], JSON_UNESCAPED_UNICODE),
            ':gift_card_names' => json_encode($giftCardData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':gift_card_prices' => json_encode($giftCardData['prices'] ?? [], JSON_UNESCAPED_UNICODE),
            ':equipment_names' => json_encode($equipmentData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':equipment_prices' => json_encode($equipmentData['prices'] ?? [], JSON_UNESCAPED_UNICODE),
            ':installation_names' => json_encode($installationData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':installation_prices' => json_encode($installationData['prices'] ?? [], JSON_UNESCAPED_UNICODE),
        ]);
        
        $pdo->commit();
        return $productId;
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        $errorMsg = "Error saving Internet product: " . $e->getMessage();
        $errorMsg .= "\nSQL State: " . $e->getCode();
        $errorMsg .= "\nError Info: " . json_encode($pdo->errorInfo() ?? []);
        $errorMsg .= "\nStack trace: " . $e->getTraceAsString();
        error_log($errorMsg);
        
        global $lastDbError;
        $lastDbError = "PDO 오류: " . $e->getMessage();
        
        return false;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Unexpected error saving Internet product: " . $e->getMessage());
        
        global $lastDbError;
        $lastDbError = "예외 발생: " . $e->getMessage();
        
        return false;
    }
}

/**
 * 리뷰 추가 (MVNO, MNO만)
 * @param int $productId 상품 ID
 * @param int $userId 사용자 ID
 * @param string $productType 상품 타입 (mvno, mno)
 * @param int $rating 평점 (1-5)
 * @param string $content 리뷰 내용
 * @param string $title 리뷰 제목 (선택)
 * @return int|false 리뷰 ID 또는 false
 */
function addProductReview($productId, $userId, $productType, $rating, $content, $title = null) {
    if ($productType === 'internet') {
        return false; // Internet은 리뷰 불가
    }
    
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO product_reviews (product_id, user_id, product_type, rating, title, content, status)
            VALUES (:product_id, :user_id, :product_type, :rating, :title, :content, 'pending')
        ");
        $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':product_type' => $productType,
            ':rating' => $rating,
            ':title' => $title,
            ':content' => $content
        ]);
        
        return $pdo->lastInsertId();
    } catch (PDOException $e) {
        error_log("Error adding review: " . $e->getMessage());
        return false;
    }
}

/**
 * 찜 추가/삭제
 * @param int $productId 상품 ID
 * @param int $userId 사용자 ID
 * @param string $productType 상품 타입
 * @param bool $isFavorite 찜 추가(true) 또는 삭제(false)
 * @return bool
 */
function toggleProductFavorite($productId, $userId, $productType, $isFavorite = true) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        if ($isFavorite) {
            $stmt = $pdo->prepare("
                INSERT INTO product_favorites (product_id, user_id, product_type)
                VALUES (:product_id, :user_id, :product_type)
                ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
            ");
        } else {
            $stmt = $pdo->prepare("
                DELETE FROM product_favorites
                WHERE product_id = :product_id AND user_id = :user_id
            ");
        }
        
        $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':product_type' => $productType
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error toggling favorite: " . $e->getMessage());
        return false;
    }
}

/**
 * 공유 기록 추가
 * @param int $productId 상품 ID
 * @param string $productType 상품 타입
 * @param string $shareMethod 공유 방법
 * @param int|null $userId 사용자 ID (비회원 가능)
 * @param string|null $ipAddress IP 주소
 * @return bool
 */
function addProductShare($productId, $productType, $shareMethod, $userId = null, $ipAddress = null) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO product_shares (product_id, user_id, product_type, share_method, ip_address, user_agent)
            VALUES (:product_id, :user_id, :product_type, :share_method, :ip_address, :user_agent)
        ");
        $stmt->execute([
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':product_type' => $productType,
            ':share_method' => $shareMethod,
            ':ip_address' => $ipAddress ?? $_SERVER['REMOTE_ADDR'] ?? null,
            ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
        
        return true;
    } catch (PDOException $e) {
        error_log("Error adding share: " . $e->getMessage());
        return false;
    }
}

/**
 * 상품 신청
 * @param int $productId 상품 ID
 * @param int $sellerId 판매자 ID
 * @param string $productType 상품 타입
 * @param array $customerData 고객 정보
 * @return int|false 신청 ID 또는 false
 */
function addProductApplication($productId, $sellerId, $productType, $customerData) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 1. 신청 등록
        $stmt = $pdo->prepare("
            INSERT INTO product_applications (product_id, seller_id, product_type, application_status)
            VALUES (:product_id, :seller_id, :product_type, 'pending')
        ");
        $stmt->execute([
            ':product_id' => $productId,
            ':seller_id' => $sellerId,
            ':product_type' => $productType
        ]);
        $applicationId = $pdo->lastInsertId();
        
        // 2. 고객 정보 등록
        $stmt = $pdo->prepare("
            INSERT INTO application_customers (
                application_id, user_id, name, phone, email, address, address_detail,
                birth_date, gender, additional_info
            ) VALUES (
                :application_id, :user_id, :name, :phone, :email, :address, :address_detail,
                :birth_date, :gender, :additional_info
            )
        ");
        $stmt->execute([
            ':application_id' => $applicationId,
            ':user_id' => $customerData['user_id'] ?? null,
            ':name' => $customerData['name'] ?? '',
            ':phone' => $customerData['phone'] ?? '',
            ':email' => $customerData['email'] ?? null,
            ':address' => $customerData['address'] ?? null,
            ':address_detail' => $customerData['address_detail'] ?? null,
            ':birth_date' => $customerData['birth_date'] ?? null,
            ':gender' => $customerData['gender'] ?? null,
            ':additional_info' => !empty($customerData['additional_info']) ? json_encode($customerData['additional_info']) : null
        ]);
        
        $pdo->commit();
        return $applicationId;
    } catch (PDOException $e) {
        $pdo->rollBack();
        error_log("Error adding application: " . $e->getMessage());
        return false;
    }
}

/**
 * 상품 타입별 순번 조회 (각 타입별로 1번부터 시작, 가장 오래된 상품이 1번)
 * @param int $productId 상품 ID
 * @param string $productType 상품 타입 ('mvno', 'mno', 'internet')
 * @return int|false 순번 또는 false
 */
function getProductNumberByType($productId, $productType) {
    $pdo = getDBConnection();
    if (!$pdo || empty($productId) || empty($productType)) {
        return false;
    }
    
    try {
        // 해당 타입의 상품 중에서 id가 작은 순서대로 순번 계산 (가장 오래된 상품이 1번)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) + 1 as product_number
            FROM products
            WHERE product_type = :product_type 
            AND id < :product_id 
            AND status != 'deleted'
        ");
        $stmt->execute([
            ':product_type' => $productType,
            ':product_id' => $productId
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result ? (int)$result['product_number'] : 1;
    } catch (PDOException $e) {
        error_log("Error getting product number: " . $e->getMessage());
        return false;
    }
}

/**
 * 조회수 증가
 * @param int $productId 상품 ID
 * @return bool
 */
function incrementProductView($productId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $stmt = $pdo->prepare("
            UPDATE products SET view_count = view_count + 1 WHERE id = :product_id
        ");
        $stmt->execute([':product_id' => $productId]);
        return true;
    } catch (PDOException $e) {
        error_log("Error incrementing view: " . $e->getMessage());
        return false;
    }
}

