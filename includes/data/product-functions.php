<?php
/**
 * 상품 관련 데이터베이스 함수
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

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
    
    // 트랜잭션 시작 전에 컬럼 확인 및 추가 (ALTER TABLE은 DDL이므로 트랜잭션 밖에서 실행)
    // redirect_url 컬럼 확인 및 추가
    $checkRedirectUrl = $pdo->query("SHOW COLUMNS FROM product_mvno_details LIKE 'redirect_url'");
    if (!$checkRedirectUrl->fetch()) {
        $pdo->exec("ALTER TABLE product_mvno_details ADD COLUMN redirect_url VARCHAR(500) DEFAULT NULL COMMENT '신청 후 리다이렉트 URL'");
        error_log("product_mvno_details 테이블에 redirect_url 컬럼이 추가되었습니다.");
    }
    
    // registration_types 컬럼 확인 및 추가
    $checkRegistrationTypes = $pdo->query("SHOW COLUMNS FROM product_mvno_details LIKE 'registration_types'");
    if (!$checkRegistrationTypes->fetch()) {
        $pdo->exec("ALTER TABLE product_mvno_details ADD COLUMN registration_types TEXT DEFAULT NULL COMMENT '가입 형태 (JSON)'");
        error_log("product_mvno_details 테이블에 registration_types 컬럼이 추가되었습니다.");
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
        
        // 약정기간 단위 처리: 값과 단위를 함께 저장 (예: "181일", "2개월")
        $contractPeriod = $productData['contract_period'] ?? '';
        $contractPeriodDays = null;
        if ($contractPeriod === '직접입력' && !empty($productData['contract_period_days'])) {
            $days = intval($productData['contract_period_days']);
            $unit = $productData['contract_period_unit'] ?? '일';
            // 기존 데이터 호환성: "월"도 "개월"로 처리
            if ($unit === '월') {
                $unit = '개월';
            }
            // 값과 단위를 함께 저장 (예: "181일", "2개월")
            $contractPeriod = $days . $unit;
            // contract_period_days는 하위 호환성을 위해 유지 (일 단위일 때만)
            if ($unit === '일') {
                $contractPeriodDays = $days;
            }
        }
        
        // 할인기간 단위 처리: 직접입력일 때 값과 단위를 조합
        $discountPeriod = $productData['discount_period'] ?? '';
        if ($discountPeriod === '직접입력' && !empty($productData['discount_period_value'])) {
            $discountValue = intval($productData['discount_period_value']);
            $discountUnit = $productData['discount_period_unit'] ?? '개월';
            // 기존 데이터 호환성: "월"도 "개월"로 변환
            if ($discountUnit === '월') {
                $discountUnit = '개월';
            }
            $discountPeriod = $discountValue . $discountUnit;
        }
        
        // 파라미터 배열 준비 (공통)
        $params = [
            ':provider' => $productData['provider'] ?? '',
            ':service_type' => $productData['service_type'] ?? null,
            ':plan_name' => $productData['plan_name'] ?? '',
            ':contract_period' => $contractPeriod ?: null,
            ':contract_period_days' => $contractPeriodDays,
            ':discount_period' => $discountPeriod ?: null,
            ':price_main' => isset($productData['price_main']) ? floatval($productData['price_main']) : 0,
            ':price_after' => $priceAfterValue,
            ':data_amount' => $productData['data_amount'] ?? null,
            ':data_amount_value' => ($productData['data_amount'] === '직접입력' && !empty($productData['data_amount_value'])) ? ($productData['data_amount_value'] . ($productData['data_unit'] ?? 'GB')) : ($productData['data_amount_value'] ?? null),
            ':data_unit' => $productData['data_unit'] ?? null,
            ':data_additional' => $productData['data_additional'] ?? null,
            ':data_additional_value' => $productData['data_additional_value'] ?? null,
            ':data_exhausted' => $productData['data_exhausted'] ?? null,
            ':data_exhausted_value' => $productData['data_exhausted_value'] ?? null,
            ':call_type' => $productData['call_type'] ?? null,
            ':call_amount' => ($productData['call_type'] === '직접입력' && !empty($productData['call_amount'])) ? ($productData['call_amount'] . ($productData['call_amount_unit'] ?? '분')) : ($productData['call_amount'] ?? null),
            ':additional_call_type' => $productData['additional_call_type'] ?? null,
            ':additional_call' => ($productData['additional_call_type'] === '직접입력' && !empty($productData['additional_call'])) ? ($productData['additional_call'] . ($productData['additional_call_unit'] ?? '분')) : ($productData['additional_call'] ?? null),
            ':sms_type' => $productData['sms_type'] ?? null,
            ':sms_amount' => ($productData['sms_type'] === '직접입력' && !empty($productData['sms_amount'])) ? ($productData['sms_amount'] . ($productData['sms_amount_unit'] ?? '건')) : ($productData['sms_amount'] ?? null),
            ':mobile_hotspot' => $productData['mobile_hotspot'] ?? null,
            ':mobile_hotspot_value' => ($productData['mobile_hotspot'] === '직접선택' && !empty($productData['mobile_hotspot_value'])) ? ($productData['mobile_hotspot_value'] . ($productData['mobile_hotspot_unit'] ?? 'GB')) : ($productData['mobile_hotspot_value'] ?? null),
            ':regular_sim_available' => $productData['regular_sim_available'] ?? null,
            ':regular_sim_price' => (!empty($productData['regular_sim_price']) && ($productData['regular_sim_available'] ?? '') === '배송가능') ? ($productData['regular_sim_price'] . ($productData['regular_sim_price_unit'] ?? '원')) : ($productData['regular_sim_price'] ?? null),
            ':nfc_sim_available' => $productData['nfc_sim_available'] ?? null,
            ':nfc_sim_price' => (!empty($productData['nfc_sim_price']) && ($productData['nfc_sim_available'] ?? '') === '배송가능') ? ($productData['nfc_sim_price'] . ($productData['nfc_sim_price_unit'] ?? '원')) : ($productData['nfc_sim_price'] ?? null),
            ':esim_available' => $productData['esim_available'] ?? null,
            ':esim_price' => (!empty($productData['esim_price']) && ($productData['esim_available'] ?? '') === '개통가능') ? ($productData['esim_price'] . ($productData['esim_price_unit'] ?? '원')) : ($productData['esim_price'] ?? null),
            ':over_data_price' => (!empty($productData['over_data_price'])) ? ($productData['over_data_price'] . ($productData['over_data_price_unit'] ?? '원/MB')) : null,
            ':over_voice_price' => (!empty($productData['over_voice_price'])) ? ($productData['over_voice_price'] . ($productData['over_voice_price_unit'] ?? '원/초')) : null,
            ':over_video_price' => (!empty($productData['over_video_price'])) ? ($productData['over_video_price'] . ($productData['over_video_price_unit'] ?? '원/초')) : null,
            ':over_sms_price' => (!empty($productData['over_sms_price'])) ? ($productData['over_sms_price'] . ($productData['over_sms_price_unit'] ?? '원/건')) : null,
            ':over_lms_price' => (!empty($productData['over_lms_price'])) ? ($productData['over_lms_price'] . ($productData['over_lms_price_unit'] ?? '원/건')) : null,
            ':over_mms_price' => (!empty($productData['over_mms_price'])) ? ($productData['over_mms_price'] . ($productData['over_mms_price_unit'] ?? '원/건')) : null,
            ':promotion_title' => $productData['promotion_title'] ?? null,
            ':promotions' => !empty($productData['promotions']) ? json_encode($productData['promotions']) : null,
            ':benefits' => !empty($productData['benefits']) ? json_encode($productData['benefits']) : null,
            ':registration_types' => !empty($productData['registration_types']) ? json_encode($productData['registration_types']) : null,
            ':redirect_url' => !empty($productData['redirect_url']) ? preg_replace('/\s+/', '', trim($productData['redirect_url'])) : null,
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
                promotion_title = :promotion_title, promotions = :promotions, benefits = :benefits, registration_types = :registration_types, redirect_url = :redirect_url
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
                promotion_title, promotions, benefits, registration_types, redirect_url
            ) VALUES (
                :product_id, :provider, :service_type, :plan_name, :contract_period,
                :contract_period_days, :discount_period, :price_main, :price_after,
                :data_amount, :data_amount_value, :data_unit, :data_additional, :data_additional_value, :data_exhausted, :data_exhausted_value,
                :call_type, :call_amount, :additional_call_type, :additional_call,
                :sms_type, :sms_amount, :mobile_hotspot, :mobile_hotspot_value,
                :regular_sim_available, :regular_sim_price, :nfc_sim_available, :nfc_sim_price,
                :esim_available, :esim_price, :over_data_price, :over_voice_price,
                :over_video_price, :over_sms_price, :over_lms_price, :over_mms_price,
                :promotion_title, :promotions, :benefits, :registration_types, :redirect_url
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
    
    // 테이블 자동 생성 (테이블이 없을 때만)
    try {
        if (!$pdo->query("SHOW TABLES LIKE 'product_internet_details'")->fetch()) {
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS `product_internet_details` (
                    `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
                    `product_id` INT(11) UNSIGNED NOT NULL COMMENT '상품 ID',
                    `registration_place` VARCHAR(50) NOT NULL COMMENT '인터넷가입처',
                    `service_type` VARCHAR(20) NOT NULL DEFAULT '인터넷' COMMENT '서비스 타입 (인터넷 또는 인터넷+TV)',
                    `speed_option` VARCHAR(20) DEFAULT NULL COMMENT '가입속도',
                    `monthly_fee` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '월 요금제 (텍스트 형식)',
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
                    KEY `idx_service_type` (`service_type`),
                    KEY `idx_speed_option` (`speed_option`)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Internet 상품 상세 정보'
            ");
            error_log("product_internet_details 테이블이 생성되었습니다.");
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
        
        // 가격 데이터 처리 및 필터링 (입력한 값 그대로 텍스트로 저장, 소수점 제거)
        $processPrices = function($prices, $units = []) {
            if (empty($prices) || !is_array($prices)) return [];
            return array_map(function($price, $index) use ($units) {
                if (empty($price)) return '';
                
                // 이미 단위가 포함된 경우 (예: "50000원")
                if (preg_match('/^(\d+)([가-힣]+)$/', $price, $matches)) {
                    // 숫자 부분의 쉼표와 소수점 제거 후 정수로 변환
                    $numericPart = str_replace([',', '.'], '', $matches[1]);
                    $numericValue = intval($numericPart); // 정수로 변환 (소수점 제거)
                    $unit = $matches[2];
                    return $numericValue . $unit;
                }
                
                // 숫자만 있는 경우 (쉼표, 소수점 제거 후 정수로 변환)
                $cleanNumeric = str_replace([',', '.'], '', preg_replace('/[^0-9.,]/', '', $price));
                $numericValue = intval($cleanNumeric); // 정수로 변환 (소수점 제거)
                $unit = isset($units[$index]) && !empty($units[$index]) ? $units[$index] : '원';
                return $numericValue ? ($numericValue . $unit) : '';
            }, $prices, array_keys($prices));
        };
        
        // 필드명 정리 함수 (인코딩 오류 및 오타 수정)
        $cleanFieldName = function($name) {
            if (empty($name) || !is_string($name)) return '';
            
            // 공백 제거
            $name = trim($name);
            
            // 일반적인 오타 및 인코딩 오류 수정
            $corrections = [
                // 와이파이공유기 관련 오타
                '/와이파이공유기\s*[ㅇㄹㅁㄴㅂㅅ]+/u' => '와이파이공유기',
                '/와이파이공유기\s*[ㅇㄹ]/u' => '와이파이공유기',
                // 설치비 관련 오타
                '/스?\s*설[ㅊㅈ]?이비/u' => '설치비',
                '/설[ㅊㅈ]?이비/u' => '설치비',
            ];
            
            // 패턴 기반 수정
            foreach ($corrections as $pattern => $replacement) {
                $name = preg_replace($pattern, $replacement, $name);
            }
            
            // 특수문자나 이상한 문자 제거 (한글, 숫자, 영문, 공백만 허용)
            $name = preg_replace('/[^\p{Hangul}\p{L}\p{N}\s]/u', '', $name);
            
            // 단어 끝에 의미없는 자음이 붙은 경우 제거
            $name = preg_replace('/\s+[ㅇㄹㅁㄴㅂㅅㅇㄹ]+$/u', '', $name);
            
            // 앞뒤 공백 제거
            $name = trim($name);
            
            return $name;
        };
        
        $filterArrays = function($names, $prices) use ($cleanFieldName) {
            $filtered = ['names' => [], 'prices' => []];
            if (empty($names) || !is_array($names)) return $filtered;
            
            $seen = []; // 중복 체크용
            
            foreach ($names as $index => $name) {
                // 필드명 정리
                $cleanedName = $cleanFieldName($name);
                
                // 빈 이름 제거
                if (empty($cleanedName) || $cleanedName === '-') continue;
                
                // 중복 제거 (이름 기준, 대소문자 구분 없이)
                $key = mb_strtolower($cleanedName, 'UTF-8');
                if (isset($seen[$key])) continue;
                $seen[$key] = true;
                
                $filtered['names'][] = $cleanedName;
                $filtered['prices'][] = $prices[$index] ?? '';
            }
            return $filtered;
        };
        
        // 월 요금 처리 (입력한 값을 그대로 텍스트로 저장)
        $monthlyFee = '';
        if (!empty($productData['monthly_fee'])) {
            // 입력한 값을 그대로 저장 (쉼표는 제거하되, 숫자와 단위는 그대로 유지)
            $inputValue = trim($productData['monthly_fee']);
            
            // 이미 단위가 포함된 경우 그대로 저장 (쉼표만 제거)
            if (preg_match('/^(\d+)([가-힣]+)$/', $inputValue, $matches)) {
                // 숫자 부분의 쉼표만 제거하고 단위는 그대로 유지
                $numericPart = str_replace(',', '', $matches[1]);
                $unit = $matches[2];
                $monthlyFee = $numericPart . $unit;
            } else {
                // 숫자만 있는 경우 쉼표 제거 후 단위 추가
                // 소수점이 포함된 경우(예: 30000.00) 정수 부분만 추출
                $numericPart = str_replace(',', '', preg_replace('/[^0-9.]/', '', $inputValue));
                // 소수점 제거 (정수로 변환)
                if (strpos($numericPart, '.') !== false) {
                    $numericPart = explode('.', $numericPart)[0];
                }
                $unit = $productData['monthly_fee_unit'] ?? '원';
                $monthlyFee = $numericPart ? ($numericPart . $unit) : '';
            }
        }
        
        $cashData = $filterArrays(
            $productData['cash_payment_names'] ?? [],
            $processPrices($productData['cash_payment_prices'] ?? [], $productData['cash_payment_price_units'] ?? [])
        );
        $giftCardData = $filterArrays(
            $productData['gift_card_names'] ?? [],
            $processPrices($productData['gift_card_prices'] ?? [], $productData['gift_card_price_units'] ?? [])
        );
        // 장비 및 설치 가격은 텍스트 그대로 저장 (숫자 변환 없이, 앞뒤 공백만 제거)
        // 빈 값 필터링: 이름이 비어있으면 해당 항목 제거
        $equipmentNames = $productData['equipment_names'] ?? [];
        $equipmentPrices = $productData['equipment_prices'] ?? [];
        $filteredEquipment = [];
        foreach ($equipmentNames as $index => $name) {
            $trimmedName = is_string($name) ? trim($name) : '';
            // 이름이 비어있지 않으면 포함 (가격은 비어있어도 됨)
            if (!empty($trimmedName)) {
                $filteredEquipment['names'][] = $trimmedName;
                $filteredEquipment['prices'][] = isset($equipmentPrices[$index]) ? (is_string($equipmentPrices[$index]) ? trim($equipmentPrices[$index]) : (string)$equipmentPrices[$index]) : '';
            }
        }
        
        $installationNames = $productData['installation_names'] ?? [];
        $installationPrices = $productData['installation_prices'] ?? [];
        $filteredInstallation = [];
        foreach ($installationNames as $index => $name) {
            $trimmedName = is_string($name) ? trim($name) : '';
            // 이름이 비어있지 않으면 포함 (가격은 비어있어도 됨)
            if (!empty($trimmedName)) {
                $filteredInstallation['names'][] = $trimmedName;
                $filteredInstallation['prices'][] = isset($installationPrices[$index]) ? (is_string($installationPrices[$index]) ? trim($installationPrices[$index]) : (string)$installationPrices[$index]) : '';
            }
        }
        
        // 필드명 정리 적용
        $equipmentData = $filterArrays(
            $filteredEquipment['names'] ?? [],
            $filteredEquipment['prices'] ?? []
        );
        $installationData = $filterArrays(
            $filteredInstallation['names'] ?? [],
            $filteredInstallation['prices'] ?? []
        );
        
        // 상세 정보 저장/업데이트
        $detailExists = false;
        if ($isEditMode) {
            $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM product_internet_details WHERE product_id = :product_id");
            $checkStmt->execute([':product_id' => $productId]);
            $detailExists = $checkStmt->fetchColumn() > 0;
        }
        
        // service_type 처리 (인터넷, 인터넷+TV, 인터넷+TV+핸드폰)
        $serviceType = '인터넷'; // 기본값
        if (!empty($productData['service_type']) && in_array($productData['service_type'], ['인터넷', '인터넷+TV', '인터넷+TV+핸드폰'])) {
            $serviceType = $productData['service_type'];
        }
        
        if ($isEditMode && $detailExists) {
            $stmt = $pdo->prepare("
                UPDATE product_internet_details SET
                    registration_place = :registration_place,
                    service_type = :service_type,
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
                    product_id, registration_place, service_type, speed_option, monthly_fee,
                    cash_payment_names, cash_payment_prices,
                    gift_card_names, gift_card_prices,
                    equipment_names, equipment_prices,
                    installation_names, installation_prices
                ) VALUES (
                    :product_id, :registration_place, :service_type, :speed_option, :monthly_fee,
                    :cash_payment_names, :cash_payment_prices,
                    :gift_card_names, :gift_card_prices,
                    :equipment_names, :equipment_prices,
                    :installation_names, :installation_prices
                )
            ");
        }
        
        // 모든 가격 값은 텍스트 형식으로 저장 (입력한 값 그대로, 소수점 없이)
        // DECIMAL 컬럼에 저장될 때 숫자로 변환되지 않도록 확실히 문자열로 보장
        $monthlyFeeValue = (string)$monthlyFee; // 명시적으로 문자열로 변환
        // 빈 문자열이 아닌 경우에만 처리 (빈 문자열은 그대로 유지)
        if ($monthlyFeeValue !== '' && !preg_match('/[가-힣]/', $monthlyFeeValue)) {
            // 단위가 없으면 추가
            $monthlyFeeValue = $monthlyFeeValue . '원';
        }
        
        // 가격 배열도 모두 텍스트 형식으로 보장
        $cashPricesText = array_map(function($price) {
            return (string)$price; // 텍스트로 변환
        }, $cashData['prices'] ?? []);
        
        $giftCardPricesText = array_map(function($price) {
            return (string)$price; // 텍스트로 변환
        }, $giftCardData['prices'] ?? []);
        
        $equipmentPricesText = array_map(function($price) {
            return (string)$price; // 텍스트로 변환
        }, $equipmentData['prices'] ?? []);
        
        $installationPricesText = array_map(function($price) {
            return (string)$price; // 텍스트로 변환
        }, $installationData['prices'] ?? []);
        
        $executeParams = [
            ':product_id' => $productId,
            ':registration_place' => $productData['registration_place'] ?? '',
            ':service_type' => $serviceType, // 인터넷 또는 인터넷+TV
            ':speed_option' => $productData['speed_option'] ?? null,
            ':monthly_fee' => $monthlyFeeValue, // 텍스트 형식으로 저장 (예: "3250원")
            ':cash_payment_names' => json_encode($cashData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':cash_payment_prices' => json_encode($cashPricesText, JSON_UNESCAPED_UNICODE), // 텍스트 배열 (예: ["50000원", "36500원"])
            ':gift_card_names' => json_encode($giftCardData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':gift_card_prices' => json_encode($giftCardPricesText, JSON_UNESCAPED_UNICODE), // 텍스트 배열 (예: ["150000원", "15400원"])
            ':equipment_names' => json_encode($equipmentData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':equipment_prices' => json_encode($equipmentPricesText, JSON_UNESCAPED_UNICODE), // 텍스트 배열 (예: ["10000원", "무료"])
            ':installation_names' => json_encode($installationData['names'] ?? [], JSON_UNESCAPED_UNICODE),
            ':installation_prices' => json_encode($installationPricesText, JSON_UNESCAPED_UNICODE), // 텍스트 배열 (예: ["무료", "설치비"])
        ];
        
        // 디버깅: 실행 파라미터 로그
        error_log("Executing SQL with params - service_type: " . $serviceType . ", product_id: " . $productId);
        
        try {
            $stmt->execute($executeParams);
            $pdo->commit();
            return $productId;
        } catch (PDOException $executeError) {
            $pdo->rollBack();
            $errorMsg = "SQL 실행 오류: " . $executeError->getMessage();
            $errorMsg .= "\nSQL State: " . $executeError->getCode();
            $errorMsg .= "\nError Info: " . json_encode($pdo->errorInfo() ?? []);
            $errorMsg .= "\nService Type: " . $serviceType;
            $errorMsg .= "\nProduct ID: " . $productId;
            error_log($errorMsg);
            
            global $lastDbError;
            $lastDbError = $errorMsg;
            throw $executeError;
        }
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
 * 리뷰 추가 (MVNO, MNO, Internet)
 * @param int $productId 상품 ID
 * @param string $userId 사용자 ID
 * @param string $productType 상품 타입 ('mvno', 'mno', 'internet')
 * @param int $rating 별점 (1-5)
 * @param string $content 리뷰 내용
 * @param string $title 리뷰 제목 (선택)
 * @param int|null $kindnessRating 친절해요 별점 (인터넷 리뷰용, 선택)
 * @param int|null $speedRating 설치 빨라요 별점 (인터넷 리뷰용, 선택)
 * @param int|null $applicationId 신청 ID (application별 리뷰 구분용, 선택)
 * @return int|false 리뷰 ID 또는 false
 */
function addProductReview($productId, $userId, $productType, $rating, $content, $title = null, $kindnessRating = null, $speedRating = null, $applicationId = null) {
    // 인터넷 리뷰도 허용
    if (!in_array($productType, ['mvno', 'mno', 'internet'])) {
        return false; // 지원하지 않는 상품 타입
    }
    
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        // application_id 컬럼 존재 여부 확인 (중복 체크 전에 확인 필요)
        $hasApplicationId = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
            $hasApplicationId = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // 컬럼이 없으면 false
        }
        
        // 중복 리뷰 체크: 같은 사용자가 같은 주문(application)에 이미 리뷰를 작성했는지 확인
        // 인터넷 리뷰의 경우 application_id로 구분 (주문별로 리뷰 작성 가능)
        if ($hasApplicationId && $applicationId !== null && $productType === 'internet') {
            // 인터넷 리뷰: 같은 application_id에 대한 리뷰가 있는지 확인
            $duplicateCheck = $pdo->prepare("
                SELECT id 
                FROM product_reviews 
                WHERE application_id = :application_id 
                AND user_id = :user_id 
                AND product_type = :product_type
                AND status != 'deleted'
                LIMIT 1
            ");
            $duplicateCheck->execute([
                ':application_id' => $applicationId,
                ':user_id' => $userId,
                ':product_type' => $productType
            ]);
        } else {
            // MVNO/MNO 리뷰 또는 application_id가 없는 경우: 같은 상품에 대한 리뷰 확인
            $duplicateCheck = $pdo->prepare("
                SELECT id 
                FROM product_reviews 
                WHERE product_id = :product_id 
                AND user_id = :user_id 
                AND product_type = :product_type
                AND status != 'deleted'
                LIMIT 1
            ");
            $duplicateCheck->execute([
                ':product_id' => $productId,
                ':user_id' => $userId,
                ':product_type' => $productType
            ]);
        }
        
        if ($duplicateCheck->fetch()) {
            error_log("addProductReview: Duplicate review detected - product_id=$productId, user_id=$userId, application_id=" . ($applicationId ?? 'null'));
            return false; // 중복 리뷰
        }
        
        // 인터넷 리뷰는 자동으로 승인, MVNO/MNO는 pending 상태로 저장
        $status = ($productType === 'internet') ? 'approved' : 'pending';
        
        // 인터넷 리뷰의 경우 kindness_rating과 speed_rating으로 rating 계산
        if ($productType === 'internet' && $kindnessRating !== null && $speedRating !== null) {
            $rating = round(($kindnessRating + $speedRating) / 2);
        }
        
        // kindness_rating, speed_rating 컬럼 존재 여부 확인
        $hasKindnessRating = false;
        $hasSpeedRating = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'kindness_rating'");
            $hasKindnessRating = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {}
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'speed_rating'");
            $hasSpeedRating = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {}
        
        // 동적으로 INSERT 쿼리 생성
        $columns = ['product_id', 'user_id', 'product_type', 'rating', 'title', 'content', 'status'];
        $values = [':product_id', ':user_id', ':product_type', ':rating', ':title', ':content', ':status'];
        $executeParams = [
            ':product_id' => $productId,
            ':user_id' => $userId,
            ':product_type' => $productType,
            ':rating' => $rating,
            ':title' => $title,
            ':content' => $content,
            ':status' => $status
        ];
        
        if ($hasApplicationId && $applicationId !== null) {
            $columns[] = 'application_id';
            $values[] = ':application_id';
            $executeParams[':application_id'] = $applicationId;
        }
        
        if ($hasKindnessRating && $hasSpeedRating && $kindnessRating !== null && $speedRating !== null) {
            $columns[] = 'kindness_rating';
            $columns[] = 'speed_rating';
            $values[] = ':kindness_rating';
            $values[] = ':speed_rating';
            $executeParams[':kindness_rating'] = $kindnessRating;
            $executeParams[':speed_rating'] = $speedRating;
        }
        
        $columnsStr = implode(', ', $columns);
        $valuesStr = implode(', ', $values);
        
        $stmt = $pdo->prepare("
            INSERT INTO product_reviews ($columnsStr)
            VALUES ($valuesStr)
        ");
        
        error_log("addProductReview: INSERT 실행 - columns: $columnsStr, params: " . json_encode(array_keys($executeParams)));
        $stmt->execute($executeParams);
        
        $newId = $pdo->lastInsertId();
        error_log("addProductReview: 리뷰 작성 성공 - ID: $newId");
        return $newId;
    } catch (PDOException $e) {
        error_log("addProductReview: PDO 예외 발생 - " . $e->getMessage());
        error_log("addProductReview: SQL State - " . $e->getCode());
        error_log("addProductReview: Columns - " . $columnsStr);
        error_log("addProductReview: Values - " . $valuesStr);
        error_log("addProductReview: Execute params - " . json_encode($executeParams));
        // 데이터베이스 스키마 오류인 경우 (product_type에 'internet'이 없는 경우)
        if (strpos($e->getMessage(), 'product_type') !== false || strpos($e->getMessage(), 'ENUM') !== false) {
            error_log("Database schema error: product_reviews.product_type ENUM may not include 'internet'. Please run: ALTER TABLE product_reviews MODIFY COLUMN product_type ENUM('mvno', 'mno', 'internet') NOT NULL;");
        }
        return false;
    }
}

/**
 * 사용자의 기존 리뷰 가져오기
 * @param int $productId 상품 ID
 * @param string $userId 사용자 ID
 * @param string $productType 상품 타입 ('mvno', 'mno', 'internet')
 * @param int|null $applicationId 신청 ID (application별 리뷰 구분용, 선택)
 * @return array|false 리뷰 데이터 또는 false
 */
function getUserReview($productId, $userId, $productType, $applicationId = null) {
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log("getUserReview: Database connection failed");
        return false;
    }
    
    try {
        // application_id 컬럼 존재 여부 확인
        $hasApplicationId = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
            $hasApplicationId = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // 컬럼이 없으면 false
        }
        
        // kindness_rating, speed_rating 컬럼 존재 여부 확인
        $hasKindnessRating = false;
        $hasSpeedRating = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'kindness_rating'");
            $hasKindnessRating = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {}
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'speed_rating'");
            $hasSpeedRating = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {}
        
        // SELECT 필드 동적 생성
        $selectFields = "id, product_id, user_id, product_type, rating, title, content, status, created_at, updated_at";
        if ($hasApplicationId) {
            $selectFields = str_replace("product_id,", "product_id, application_id,", $selectFields);
        }
        if ($hasKindnessRating && $hasSpeedRating) {
            $selectFields = "id, product_id, " . ($hasApplicationId ? "application_id, " : "") . "user_id, product_type, rating, kindness_rating, speed_rating, title, content, status, created_at, updated_at";
        } elseif ($hasKindnessRating) {
            $selectFields = "id, product_id, " . ($hasApplicationId ? "application_id, " : "") . "user_id, product_type, rating, kindness_rating, title, content, status, created_at, updated_at";
        } elseif ($hasSpeedRating) {
            $selectFields = "id, product_id, " . ($hasApplicationId ? "application_id, " : "") . "user_id, product_type, rating, speed_rating, title, content, status, created_at, updated_at";
        }
        
        error_log("getUserReview: Searching for review - product_id=$productId, user_id=$userId, product_type=$productType, application_id=" . ($applicationId ?? 'null'));
        
        // application_id가 있으면 application_id로 우선 조회, 없으면 product_id로 조회
        if ($hasApplicationId && $applicationId !== null && $applicationId > 0) {
            $stmt = $pdo->prepare("
                SELECT $selectFields
                FROM product_reviews
                WHERE product_id = :product_id 
                AND user_id = :user_id 
                AND product_type = :product_type
                AND application_id = :application_id
                ORDER BY 
                    CASE WHEN status = 'approved' THEN 0 ELSE 1 END,
                    created_at DESC
                LIMIT 1
            ");
            $stmt->execute([
                ':product_id' => $productId,
                ':user_id' => $userId,
                ':product_type' => $productType,
                ':application_id' => $applicationId
            ]);
        } else {
            $stmt = $pdo->prepare("
                SELECT $selectFields
                FROM product_reviews
                WHERE product_id = :product_id 
                AND user_id = :user_id 
                AND product_type = :product_type
                " . ($hasApplicationId && $applicationId === null ? "AND (application_id IS NULL OR application_id = 0)" : "") . "
                ORDER BY 
                    CASE WHEN status = 'approved' THEN 0 ELSE 1 END,
                    created_at DESC
                LIMIT 1
            ");
            $params = [
                ':product_id' => $productId,
                ':user_id' => $userId,
                ':product_type' => $productType
            ];
            $stmt->execute($params);
        }
        
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($review) {
            error_log("getUserReview: Review found - id=" . $review['id'] . ", rating=" . ($review['rating'] ?? 'N/A'));
            return $review;
        }
        
        // 정확한 product_id로 찾지 못한 경우, 같은 판매자의 같은 타입 상품 리뷰 중에서 찾기
        error_log("getUserReview: No review found for exact product_id=$productId, trying to find in same seller's products");
        
        // 상품의 seller_id 가져오기
        $productStmt = $pdo->prepare("
            SELECT seller_id 
            FROM products 
            WHERE id = :product_id 
            AND product_type = :product_type
            LIMIT 1
        ");
        $productStmt->execute([
            ':product_id' => $productId,
            ':product_type' => $productType
        ]);
        $product = $productStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($product && !empty($product['seller_id'])) {
            $sellerId = $product['seller_id'];
            
            // 같은 판매자의 같은 타입의 모든 상품 ID 가져오기
            $productsStmt = $pdo->prepare("
                SELECT id 
                FROM products 
                WHERE seller_id = :seller_id 
                AND product_type = :product_type
                AND status = 'active'
            ");
            $productsStmt->execute([
                ':seller_id' => $sellerId,
                ':product_type' => $productType
            ]);
            $productIds = $productsStmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($productIds)) {
                $placeholders = implode(',', array_fill(0, count($productIds), '?'));
                $stmt = $pdo->prepare("
                    SELECT 
                        id,
                        product_id,
                        user_id,
                        product_type,
                        rating,
                        kindness_rating,
                        speed_rating,
                        title,
                        content,
                        status,
                        created_at,
                        updated_at
                    FROM product_reviews
                    WHERE product_id IN ($placeholders)
                    AND user_id = ?
                    AND product_type = ?
                    ORDER BY 
                        CASE WHEN status = 'approved' THEN 0 ELSE 1 END,
                        created_at DESC
                    LIMIT 1
                ");
                $params = array_merge($productIds, [$userId, $productType]);
                $stmt->execute($params);
                $review = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($review) {
                    error_log("getUserReview: Review found in same seller's products - id=" . $review['id'] . ", product_id=" . $review['product_id']);
                    return $review;
                }
            }
        }
        
        error_log("getUserReview: No review found for product_id=$productId, user_id=$userId, product_type=$productType");
        
        // 디버깅: 해당 조건으로 리뷰가 있는지 확인
        $debugStmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM product_reviews 
            WHERE product_id = :product_id AND product_type = :product_type
        ");
        $debugStmt->execute([
            ':product_id' => $productId,
            ':product_type' => $productType
        ]);
        $totalCount = $debugStmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        $debugStmt2 = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM product_reviews 
            WHERE user_id = :user_id AND product_type = :product_type
        ");
        $debugStmt2->execute([
            ':user_id' => $userId,
            ':product_type' => $productType
        ]);
        $userCount = $debugStmt2->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        error_log("getUserReview: Debug - Total reviews for product_id=$productId: $totalCount, Total reviews for user_id=$userId: $userCount");
        
        return false;
    } catch (PDOException $e) {
        error_log("Error fetching user review: " . $e->getMessage());
        return false;
    }
}

/**
 * 리뷰 수정
 * @param int $reviewId 리뷰 ID
 * @param string $userId 사용자 ID
 * @param int $rating 별점
 * @param string $content 리뷰 내용
 * @param string $title 리뷰 제목 (선택)
 * @param int|null $kindnessRating 친절해요 별점 (인터넷 리뷰용, 선택)
 * @param int|null $speedRating 설치 빨라요 별점 (인터넷 리뷰용, 선택)
 * @return bool 성공 여부
 */
function updateProductReview($reviewId, $userId, $rating, $content, $title = null, $kindnessRating = null, $speedRating = null) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        // 리뷰 소유권 확인 및 product_type 확인
        $checkStmt = $pdo->prepare("
            SELECT id, user_id, product_type 
            FROM product_reviews 
            WHERE id = :review_id AND user_id = :user_id
        ");
        $checkStmt->execute([
            ':review_id' => $reviewId,
            ':user_id' => $userId
        ]);
        $review = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$review) {
            error_log("Review not found or user mismatch: review_id=$reviewId, user_id=$userId");
            return false;
        }
        
        // 인터넷 리뷰인 경우 kindness_rating과 speed_rating으로 rating 계산
        if ($review['product_type'] === 'internet' && $kindnessRating !== null && $speedRating !== null) {
            $rating = round(($kindnessRating + $speedRating) / 2);
        }
        
        // 인터넷 리뷰인 경우 kindness_rating과 speed_rating도 업데이트
        // 컬럼 존재 여부 확인
        $hasKindnessRating = false;
        $hasSpeedRating = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'kindness_rating'");
            $hasKindnessRating = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // 컬럼이 없으면 false
        }
        
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'speed_rating'");
            $hasSpeedRating = $checkStmt->rowCount() > 0;
        } catch (PDOException $e) {
            // 컬럼이 없으면 false
        }
        
        if ($review['product_type'] === 'internet' && $hasKindnessRating && $hasSpeedRating && $kindnessRating !== null && $speedRating !== null) {
            $stmt = $pdo->prepare("
                UPDATE product_reviews
                SET rating = :rating,
                    kindness_rating = :kindness_rating,
                    speed_rating = :speed_rating,
                    content = :content,
                    title = :title,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :review_id
                AND user_id = :user_id
            ");
            $stmt->execute([
                ':review_id' => $reviewId,
                ':user_id' => $userId,
                ':rating' => $rating,
                ':kindness_rating' => $kindnessRating,
                ':speed_rating' => $speedRating,
                ':content' => $content,
                ':title' => $title
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE product_reviews
                SET rating = :rating,
                    content = :content,
                    title = :title,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :review_id
                AND user_id = :user_id
            ");
            $stmt->execute([
                ':review_id' => $reviewId,
                ':user_id' => $userId,
                ':rating' => $rating,
                ':content' => $content,
                ':title' => $title
            ]);
            
            if ($review['product_type'] === 'internet' && (!$hasKindnessRating || !$hasSpeedRating)) {
                error_log("updateProductReview: Warning - kindness_rating or speed_rating columns not found, only rating updated");
            }
        }
        
        return true;
    } catch (PDOException $e) {
        error_log("Error updating review: " . $e->getMessage());
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
/**
 * 찜 추가/삭제 (MVNO, MNO만)
 * @param int $productId 상품 ID
 * @param int $userId 사용자 ID
 * @param string $productType 상품 타입 (mvno, mno)
 * @param bool $isFavorite 찜 추가(true) 또는 삭제(false)
 * @return bool
 */
function toggleProductFavorite($productId, $userId, $productType, $isFavorite = true) {
    // 인터넷 상품은 찜 불가
    if ($productType === 'internet') {
        return false;
    }
    
    $pdo = getDBConnection();
    if (!$pdo) {
        return false;
    }
    
    try {
        $pdo->beginTransaction();
        
        // 먼저 products 테이블에 해당 상품이 존재하는지 확인
        $productIdInt = (int)$productId;
        $checkStmt = $pdo->prepare("
            SELECT id, product_type FROM products 
            WHERE id = ?
        ");
        $checkStmt->execute([$productIdInt]);
        $product = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$product) {
            error_log("toggleProductFavorite: Product not found - product_id=$productId");
            $pdo->rollBack();
            return false;
        }
        
        // product_type 일치 확인
        if ($product['product_type'] !== $productType) {
            error_log("toggleProductFavorite: Product type mismatch - product_id=$productId, expected=$productType, actual={$product['product_type']}");
            $pdo->rollBack();
            return false;
        }
        
        // user_id는 users.user_id(VARCHAR) 기반으로 저장/조회 (DB-only 정합성)
        $userIdStr = (string)$userId;
        
        if ($isFavorite) {
            // 찜 추가
            $stmt = $pdo->prepare("
                INSERT INTO product_favorites (product_id, user_id, product_type)
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
            ");
            $stmt->execute([$productIdInt, $userIdStr, $productType]);
        } else {
            // 찜 삭제
            $stmt = $pdo->prepare("
                DELETE FROM product_favorites
                WHERE product_id = :product_id AND user_id = :user_id
            ");
            $stmt->bindValue(':product_id', $productIdInt, PDO::PARAM_INT);
            $stmt->bindValue(':user_id', $userIdStr, PDO::PARAM_STR);
            $stmt->execute();
        }
        
        // favorite_count 수동 업데이트 (트리거가 없어도 작동하도록)
        $updateStmt = $pdo->prepare("
            UPDATE products 
            SET favorite_count = (
                SELECT COUNT(*) FROM product_favorites 
                WHERE product_id = ?
            )
            WHERE id = ?
        ");
        $updateStmt->execute([$productIdInt, $productIdInt]);
        
        $pdo->commit();
        return true;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("Error toggling favorite: product_id=$productId, user_id=$userId, product_type=$productType, isFavorite=$isFavorite, error=" . $e->getMessage());
        error_log("SQL Error Code: " . $e->getCode() . ", SQL State: " . $e->errorInfo[0] ?? 'N/A');
        error_log("Error Info: " . print_r($e->errorInfo, true));
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
 * 주문번호 생성 (쇼핑몰 일반 형식)
 * 형식: YYMMDDHH-0001 (예: 25121519-0001) - 총 12자리
 * 같은 시간(시 단위)에 여러 주문이 있을 경우 순번 증가
 * 동시성 문제를 방지하기 위해 트랜잭션 내에서 호출되어야 함
 * @param PDO $pdo 데이터베이스 연결 (트랜잭션 내에서)
 * @param DateTime $dateTime 주문 시간
 * @param int $maxRetries 최대 재시도 횟수 (중복 방지)
 * @return string 주문번호
 */
function generateOrderNumber($pdo, $dateTime = null, $maxRetries = 10) {
    if ($dateTime === null) {
        $dateTime = new DateTime('now', new DateTimeZone('Asia/Seoul'));
    }
    
    $attempt = 0;
    while ($attempt < $maxRetries) {
        // 년월일 시간 (YYMMDDHH) - 8자리
        $timePrefix = $dateTime->format('ymdH');
        
        // 같은 시간(시)에 생성된 주문 수 확인
        // FOR UPDATE를 사용하여 동시성 문제 방지 (트랜잭션 내에서만 작동)
        $startOfHour = $dateTime->format('Y-m-d H:00:00');
        $endOfHour = $dateTime->format('Y-m-d H:59:59');
        
        // 주문번호가 이미 존재하는지 확인 (FOR UPDATE로 락)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM product_applications 
            WHERE created_at >= :start_time 
            AND created_at <= :end_time
            AND order_number LIKE :prefix
            FOR UPDATE
        ");
        $stmt->execute([
            ':start_time' => $startOfHour,
            ':end_time' => $endOfHour,
            ':prefix' => $timePrefix . '-%'
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $existingCount = $result['count'] ?? 0;
        
        // 순번 생성 (기존 주문 수 + 1)
        $sequenceNumber = $existingCount + 1;
        
        // 4자리 순번 생성 (0001 ~ 9999)
        // 같은 시간에 9999개 이상의 주문이 있으면 다음 시간으로 넘어감
        if ($sequenceNumber > 9999) {
            $dateTime->modify('+1 hour');
            $timePrefix = $dateTime->format('ymdH');
            $sequenceNumber = 1;
        }
        
        $sequence = str_pad($sequenceNumber, 4, '0', STR_PAD_LEFT);
        $orderNumber = $timePrefix . '-' . $sequence;
        
        // 생성된 주문번호가 이미 존재하는지 확인 (중복 체크)
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as count FROM product_applications WHERE order_number = :order_number");
        $checkStmt->execute([':order_number' => $orderNumber]);
        $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (($checkResult['count'] ?? 0) == 0) {
            // 중복이 없으면 주문번호 반환
            return $orderNumber;
        }
        
        // 중복이 있으면 순번 증가 후 재시도
        $attempt++;
        $sequenceNumber++;
        
        // 재시도 로그
        error_log("Order number duplicate detected: {$orderNumber}, retrying... (attempt {$attempt})");
    }
    
    // 최대 재시도 횟수 초과 시 예외 발생
    throw new Exception("주문번호 생성 실패: 최대 재시도 횟수 초과");
}

/**
 * 신청 상태를 한글로 변환 (공통 함수)
 * 판매자 페이지와 고객 마이페이지에서 동일하게 사용
 * @param string $status 신청 상태 코드
 * @return string 한글 상태명
 */
function getApplicationStatusLabel($status) {
    // 상태 정규화 (빈 문자열, null, pending 등)
    $normalizedStatus = strtolower(trim($status ?? ''));
    if (empty($normalizedStatus) || $normalizedStatus === 'pending') {
        $normalizedStatus = 'received';
    }
    
    // 상태별 한글명 매핑 (판매자 페이지 internet.php의 옵션과 동일)
    // received(접수), activating(개통중), activation_completed(개통완료), installation_completed(설치완료)
    $statusLabels = [
        'received' => '접수',
        'activating' => '개통중',
        'on_hold' => '보류',
        'cancelled' => '취소',
        'activation_completed' => '개통완료',
        'installation_completed' => '설치완료',
        'pending' => '접수',  // pending도 received와 동일하게 처리
        'processing' => '개통중',  // processing도 activating과 동일하게 처리
        'completed' => '설치완료',
        'rejected' => '보류',
        'closed' => '종료',
        'terminated' => '종료'
    ];
    
    return $statusLabels[$normalizedStatus] ?? $status;
}

/**
 * user_id 컬럼 존재 여부 확인 및 추가 (헬퍼 함수)
 * @param PDO $pdo 데이터베이스 연결
 * @return bool user_id 컬럼 존재 여부
 */
function checkAndAddUserIdColumn($pdo) {
    try {
        // 트랜잭션 상태 저장
        $wasInTransaction = $pdo->inTransaction();
        
        // 트랜잭션 밖에서 컬럼 확인 (더 확실함)
        if ($wasInTransaction) {
            $pdo->commit();
        }
        
        // SHOW COLUMNS로 모든 컬럼 가져와서 확인 (가장 확실한 방법)
        $check = $pdo->query("SHOW COLUMNS FROM application_customers");
        $columns = $check->fetchAll(PDO::FETCH_COLUMN);
        $exists = in_array('user_id', $columns);
        
        error_log("addProductApplication - user_id 컬럼 확인: " . ($exists ? '존재함' : '없음'));
        error_log("addProductApplication - 전체 컬럼 목록: " . implode(', ', $columns));
        
        if (!$exists) {
            error_log("addProductApplication - user_id 컬럼이 없습니다. 추가합니다.");
            
            try {
                $pdo->exec("ALTER TABLE application_customers ADD COLUMN user_id VARCHAR(50) DEFAULT NULL COMMENT '회원 user_id (비회원 신청 가능)' AFTER application_id");
                error_log("addProductApplication - user_id 컬럼이 추가되었습니다.");
                $exists = true;
                
                // 인덱스 추가
                try {
                    $pdo->exec("ALTER TABLE application_customers ADD INDEX idx_user_id (user_id)");
                    error_log("addProductApplication - user_id 인덱스 추가 완료");
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate') === false && strpos($e->getMessage(), 'already exists') === false) {
                        error_log("addProductApplication - 인덱스 추가 중 오류: " . $e->getMessage());
                    } else {
                        error_log("addProductApplication - 인덱스가 이미 존재합니다.");
                    }
                }
            } catch (PDOException $e) {
                error_log("addProductApplication - 컬럼 추가 실패: " . $e->getMessage());
                error_log("  SQL State: " . ($e->errorInfo[0] ?? 'unknown'));
                error_log("  Driver Code: " . ($e->errorInfo[1] ?? 'unknown'));
                error_log("  Driver Message: " . ($e->errorInfo[2] ?? 'unknown'));
                
                // 컬럼 추가 실패 시 - 이미 존재하는지 확인
                $checkAgain = $pdo->query("SHOW COLUMNS FROM application_customers");
                $columnsAgain = $checkAgain->fetchAll(PDO::FETCH_COLUMN);
                $exists = in_array('user_id', $columnsAgain);
                
                if (!$exists) {
                    error_log("addProductApplication - 컬럼 추가 실패하고 실제로도 없습니다. false 반환");
                    $exists = false; // 실제로 없으면 false 반환
                } else {
                    error_log("addProductApplication - 컬럼 추가 실패했지만 실제로는 존재합니다.");
                    $exists = true;
                }
            }
        }
        
        // 트랜잭션 재시작
        if ($wasInTransaction) {
            $pdo->beginTransaction();
        }
        
        return $exists;
    } catch (PDOException $e) {
        error_log("addProductApplication - 컬럼 확인 중 오류: " . $e->getMessage());
        error_log("  SQL State: " . ($e->errorInfo[0] ?? 'unknown'));
        error_log("  Driver Code: " . ($e->errorInfo[1] ?? 'unknown'));
        error_log("  Driver Message: " . ($e->errorInfo[2] ?? 'unknown'));
        
        // 트랜잭션 재시작
        if (!$pdo->inTransaction()) {
            $pdo->beginTransaction();
        }
        
        // 확인 실패 시 안전하게 false로 설정 (user_id 없이 진행)
        return false;
    }
}

/**
 * 상품 신청 정보 저장
 * @param int $productId 상품 ID
 * @param int $sellerId 판매자 ID
 * @param string $productType 상품 타입
 * @param array $customerData 고객 정보
 * @return int|false 신청 ID 또는 false
 */
function addProductApplication($productId, $sellerId, $productType, $customerData) {
    error_log("addProductApplication called - productId: {$productId}, sellerId: {$sellerId}, productType: {$productType}");
    
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log("addProductApplication - Database connection failed");
        return false;
    }
    
    try {
        error_log("addProductApplication - Starting transaction");
        $pdo->beginTransaction();
        
        // order_number 컬럼 존재 확인 및 추가 (트랜잭션 밖에서 실행해야 함)
        try {
            $checkOrderNumber = $pdo->query("SHOW COLUMNS FROM product_applications LIKE 'order_number'");
            if (!$checkOrderNumber->fetch()) {
                $pdo->commit(); // ALTER TABLE은 트랜잭션을 커밋함
                $pdo->exec("ALTER TABLE product_applications ADD COLUMN order_number VARCHAR(20) DEFAULT NULL COMMENT '주문번호' AFTER id");
                // UNIQUE 제약조건 추가 (중복 방지)
                try {
                    $pdo->exec("ALTER TABLE product_applications ADD UNIQUE KEY uk_order_number (order_number)");
                } catch (PDOException $e) {
                    // 이미 존재하는 경우 무시
                    if (strpos($e->getMessage(), 'Duplicate key name') === false) {
                        throw $e;
                    }
                }
                $pdo->beginTransaction(); // 트랜잭션 재시작
                error_log("product_applications 테이블에 order_number 컬럼이 추가되었습니다.");
            }
        } catch (PDOException $e) {
            // 컬럼 확인 중 오류 발생 시 무시하고 계속 진행
            error_log("Order number column check error: " . $e->getMessage());
        }
        
        // 1. 신청 등록 (모든 상품 타입은 'pending' 상태로 시작)
        // 한국 시간대(KST, UTC+9)로 현재 시간을 명시적으로 설정하여 주문번호의 시간이 정확하게 표시되도록 함
        $currentDateTime = new DateTime('now', new DateTimeZone('Asia/Seoul'));
        $currentDateTimeStr = $currentDateTime->format('Y-m-d H:i:s');
        $initialStatus = 'pending';

        // 신청자 user_id(users.user_id) - DB-only 정합성
        $userId = $customerData['user_id'] ?? null;
        $userId = ($userId === null) ? null : (string)$userId;
        
        // INSERT 전에 트랜잭션 밖에서 컬럼 확인 (스키마 변경이 확실히 반영되도록)
        $wasInTransaction = $pdo->inTransaction();
        if ($wasInTransaction) {
            $pdo->commit();
        }
        
        // product_applications 테이블에 user_id 컬럼이 있는지 확인
        try {
            $checkColumns = $pdo->query("SHOW COLUMNS FROM product_applications");
            $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
            $hasUserIdInApplications = in_array('user_id', $columns);
            
            error_log("addProductApplication - product_applications 테이블 user_id 컬럼 확인: " . ($hasUserIdInApplications ? '존재함' : '없음'));
            error_log("addProductApplication - product_applications 전체 컬럼 목록: " . implode(', ', $columns));
            
            if (!$hasUserIdInApplications) {
                error_log("addProductApplication - product_applications에 user_id 컬럼이 없습니다. 추가합니다.");
                try {
                    $pdo->exec("ALTER TABLE product_applications ADD COLUMN user_id VARCHAR(50) DEFAULT NULL COMMENT '신청자 user_id (users.user_id)' AFTER product_type");
                    error_log("addProductApplication - product_applications에 user_id 컬럼 추가 완료");
                    
                    // 인덱스 추가
                    try {
                        $pdo->exec("ALTER TABLE product_applications ADD INDEX idx_user_id (user_id)");
                    } catch (PDOException $idxErr) {
                        if (strpos($idxErr->getMessage(), 'Duplicate') === false) {
                            error_log("addProductApplication - 인덱스 추가 실패: " . $idxErr->getMessage());
                        }
                    }
                } catch (PDOException $alterErr) {
                    error_log("addProductApplication - 컬럼 추가 실패: " . $alterErr->getMessage());
                }
            }
        } catch (PDOException $checkErr) {
            error_log("addProductApplication - 컬럼 확인 실패: " . $checkErr->getMessage());
        }
        
        // 트랜잭션 재시작
        if ($wasInTransaction) {
            $pdo->beginTransaction();
        }
        
        // 주문번호 생성 (트랜잭션 내에서, UNIQUE 제약조건으로 중복 방지)
        $orderNumber = null;
        $maxRetries = 10;
        $retryCount = 0;
        
        while ($retryCount < $maxRetries) {
            try {
                $orderNumber = generateOrderNumber($pdo, $currentDateTime);
                
                // 주문번호로 INSERT 시도 (status_changed_at도 함께 설정)
                // user_id 컬럼이 있는지 다시 한 번 확인하여 SQL 결정
                $insertColumns = "order_number, product_id, seller_id, product_type, application_status, created_at, status_changed_at";
                $insertValues = ":order_number, :product_id, :seller_id, :product_type, :application_status, :created_at, :status_changed_at";
                $insertParams = [
                    ':order_number' => $orderNumber,
                    ':product_id' => $productId,
                    ':seller_id' => $sellerId,
                    ':product_type' => $productType,
                    ':application_status' => $initialStatus,
                    ':created_at' => $currentDateTimeStr,
                    ':status_changed_at' => $currentDateTimeStr
                ];
                
                // user_id가 있으면 포함
                if (!empty($userId)) {
                    $insertColumns = "order_number, product_id, seller_id, user_id, product_type, application_status, created_at, status_changed_at";
                    $insertValues = ":order_number, :product_id, :seller_id, :user_id, :product_type, :application_status, :created_at, :status_changed_at";
                    $insertParams[':user_id'] = $userId;
                }
                
                $sql = "INSERT INTO product_applications ({$insertColumns}) VALUES ({$insertValues})";
                
                error_log("addProductApplication - product_applications INSERT SQL: " . $sql);
                error_log("addProductApplication - user_id 포함 여부: " . (!empty($userId) ? 'YES' : 'NO'));
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($insertParams);
                
                // INSERT 성공 시 루프 종료
                break;
                
            } catch (PDOException $e) {
                // user_id 컬럼 관련 에러인 경우 user_id 없이 재시도
                $isUserIdError = ($e->getCode() == 1054 || 
                                 strpos($e->getMessage(), "Unknown column 'user_id'") !== false || 
                                 strpos($e->getMessage(), "Unknown column `user_id`") !== false ||
                                 (strpos($e->getMessage(), "user_id") !== false && strpos($e->getMessage(), "field list") !== false));
                
                if ($isUserIdError) {
                    error_log("addProductApplication - product_applications INSERT 시 user_id 컬럼 에러 감지, user_id 없이 재시도합니다.");
                    error_log("  원본 에러: " . $e->getMessage());
                    
                    // user_id 없이 재시도
                    $sqlRetry = "INSERT INTO product_applications (order_number, product_id, seller_id, product_type, application_status, created_at, status_changed_at) VALUES (:order_number, :product_id, :seller_id, :product_type, :application_status, :created_at, :status_changed_at)";
                    $retryParams = [
                        ':order_number' => $orderNumber,
                        ':product_id' => $productId,
                        ':seller_id' => $sellerId,
                        ':product_type' => $productType,
                        ':application_status' => $initialStatus,
                        ':created_at' => $currentDateTimeStr,
                        ':status_changed_at' => $currentDateTimeStr
                    ];
                    
                    try {
                        error_log("addProductApplication - product_applications INSERT 재시도 (user_id 없이)");
                        $stmtRetry = $pdo->prepare($sqlRetry);
                        $stmtRetry->execute($retryParams);
                        error_log("addProductApplication - product_applications INSERT 성공 (user_id 없이)");
                        break; // 성공했으므로 루프 종료
                    } catch (PDOException $e2) {
                        error_log("addProductApplication - product_applications INSERT 재시도 실패: " . $e2->getMessage());
                        throw $e2;
                    }
                }
                
                // UNIQUE 제약조건 위반 (중복 주문번호)
                if ($e->getCode() == 23000 || strpos($e->getMessage(), 'Duplicate entry') !== false) {
                    $retryCount++;
                    error_log("Order number duplicate detected: {$orderNumber}, retrying... (attempt {$retryCount})");
                    
                    // 다음 순번으로 재시도하기 위해 시간을 약간 조정하거나 순번 증가
                    if ($retryCount >= $maxRetries) {
                        throw new Exception("주문번호 생성 실패: 중복 방지 시도 횟수 초과");
                    }
                    
                    // 다음 순번을 위해 시간을 1초 증가 (같은 시간에 여러 주문이 동시에 들어올 경우)
                    $currentDateTime->modify('+1 second');
                    $currentDateTimeStr = $currentDateTime->format('Y-m-d H:i:s');
                    continue;
                } else {
                    // 다른 오류는 즉시 throw
                    throw $e;
                }
            }
        }
        
        if ($orderNumber === null) {
            throw new Exception("주문번호 생성 실패");
        }
        
        $applicationId = $pdo->lastInsertId();
        error_log("addProductApplication - Application created with ID: {$applicationId}, Order Number: {$orderNumber}");
        
        // application_count 업데이트 (트리거가 작동하지 않을 경우를 대비)
        try {
            $updateStmt = $pdo->prepare("
                UPDATE products 
                SET application_count = application_count + 1 
                WHERE id = :product_id
            ");
            $updateStmt->execute([':product_id' => $productId]);
            error_log("addProductApplication - application_count updated");
        } catch (PDOException $e) {
            // 업데이트 실패해도 계속 진행 (트리거가 처리할 수 있음)
            error_log("Failed to update application_count: " . $e->getMessage());
        }
        
        // 2. 고객 정보 등록
        error_log("addProductApplication - Step 2: Inserting customer data");
        
        // user_id 컬럼 존재 확인 및 추가
        // 테이블 구조 확인: application_customers 테이블에 user_id 컬럼이 존재함 (스키마 확인됨)
        // user_id는 필수 정보이므로 정상적으로 저장해야 함
        $hasUserIdColumn = true; // 기본값을 true로 설정 (컬럼이 존재함)
        
        // 컬럼 확인 (트랜잭션 밖에서 실행하여 정확한 스키마 확인)
        try {
            $wasInTransaction = $pdo->inTransaction();
            if ($wasInTransaction) {
                $pdo->commit();
            }
            
            // 실제로 컬럼이 있는지 확인
            $checkColumns = $pdo->query("SHOW COLUMNS FROM application_customers");
            $columns = $checkColumns->fetchAll(PDO::FETCH_COLUMN);
            $hasUserIdColumn = in_array('user_id', $columns);
            
            error_log("addProductApplication - user_id 컬럼 확인 결과: " . ($hasUserIdColumn ? '존재함' : '없음'));
            error_log("addProductApplication - 전체 컬럼 목록: " . implode(', ', $columns));
            
            if ($wasInTransaction) {
                $pdo->beginTransaction();
            }
        } catch (PDOException $checkErr) {
            error_log("addProductApplication - 컬럼 확인 실패: " . $checkErr->getMessage());
            // 확인 실패 시에도 true로 유지 (컬럼이 존재한다고 가정)
            $hasUserIdColumn = true;
            if (!$pdo->inTransaction()) {
                $pdo->beginTransaction();
            }
        }
        
        error_log("addProductApplication - 최종 hasUserIdColumn: " . ($hasUserIdColumn ? 'true' : 'false'));
        error_log("addProductApplication - userId 값: " . ($userId ?? 'null'));
        
        // user_id 검증 (로그인한 사용자만 신청 가능하므로 user_id는 필수)
        if (empty($userId) || $userId === null) {
            error_log("WARNING: Application created without user_id. Application ID: " . $applicationId);
            // user_id가 없으면 에러 (로그인 필수)
            throw new Exception('사용자 정보를 확인할 수 없습니다. 다시 로그인해주세요.');
        }
        
        // additional_info JSON 인코딩
        $additionalInfoJson = null;
        if (!empty($customerData['additional_info'])) {
            $additionalInfoJson = json_encode($customerData['additional_info'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            if ($additionalInfoJson === false) {
                error_log("ERROR: Failed to encode additional_info to JSON. Error: " . json_last_error_msg());
                $additionalInfoJson = null;
            }
        }
        
        // INSERT 쿼리 및 파라미터 준비
        $baseParams = [
            ':application_id' => $applicationId,
            ':name' => $customerData['name'] ?? '',
            ':phone' => $customerData['phone'] ?? '',
            ':email' => $customerData['email'] ?? null,
            ':address' => $customerData['address'] ?? null,
            ':address_detail' => $customerData['address_detail'] ?? null,
            ':birth_date' => $customerData['birth_date'] ?? null,
            ':gender' => $customerData['gender'] ?? null,
            ':additional_info' => $additionalInfoJson
        ];
        
        // INSERT 전 최종 확인 - user_id 포함하여 정상적으로 저장
        // 테이블에 user_id 컬럼이 존재하고, user_id는 필수 정보이므로 포함하여 저장
        error_log("addProductApplication - ========================================");
        error_log("addProductApplication - 최종 결정: user_id 포함하여 저장합니다.");
        error_log("addProductApplication - hasUserIdColumn: " . ($hasUserIdColumn ? 'true' : 'false'));
        error_log("addProductApplication - userId: " . ($userId ?? 'null'));
        error_log("addProductApplication - ========================================");
        
        // user_id 포함하여 INSERT (정상 방식)
        if ($hasUserIdColumn && !empty($userId)) {
            $sql = "INSERT INTO application_customers (application_id, user_id, name, phone, email, address, address_detail, birth_date, gender, additional_info) VALUES (:application_id, :user_id, :name, :phone, :email, :address, :address_detail, :birth_date, :gender, :additional_info)";
            $baseParams[':user_id'] = $userId;
            error_log("addProductApplication - user_id 포함하여 INSERT 실행");
        } else {
            // user_id 컬럼이 없거나 user_id가 없는 경우 (비상 상황)
            $sql = "INSERT INTO application_customers (application_id, name, phone, email, address, address_detail, birth_date, gender, additional_info) VALUES (:application_id, :name, :phone, :email, :address, :address_detail, :birth_date, :gender, :additional_info)";
            if (isset($baseParams[':user_id'])) {
                unset($baseParams[':user_id']);
            }
            error_log("addProductApplication - 경고: user_id 없이 INSERT 실행 (비상 상황)");
        }
        
        $executeParams = $baseParams;
        
        error_log("addProductApplication - 최종 executeParams 키 목록: " . implode(', ', array_keys($executeParams)));
        error_log("addProductApplication - user_id 포함 여부: " . (isset($executeParams[':user_id']) ? 'YES (정상)' : 'NO'));
        
        // INSERT 실행
        error_log("addProductApplication - ========================================");
        error_log("addProductApplication - INSERT 실행 시작");
        error_log("addProductApplication - SQL: " . $sql);
        error_log("addProductApplication - SQL에 'user_id' 포함 여부: " . (strpos($sql, 'user_id') !== false ? 'YES (정상)' : 'NO'));
        error_log("addProductApplication - Parameter keys: " . implode(', ', array_keys($executeParams)));
        error_log("addProductApplication - ========================================");
        
        // 파라미터 값 로깅 (민감 정보 제외)
        $logParams = $executeParams;
        if (isset($logParams[':phone'])) {
            $logParams[':phone'] = substr($logParams[':phone'], 0, 3) . '****' . substr($logParams[':phone'], -4);
        }
        if (isset($logParams[':email'])) {
            $logParams[':email'] = preg_replace('/(.{2})(.*)(@.*)/', '$1***$3', $logParams[':email'] ?? '');
        }
        if (isset($logParams[':additional_info'])) {
            $logParams[':additional_info'] = '[' . strlen($executeParams[':additional_info'] ?? '') . ' bytes]';
        }
        error_log("addProductApplication - Parameter values (masked): " . json_encode($logParams, JSON_UNESCAPED_UNICODE));
        
        $stmt = $pdo->prepare($sql);
        
        try {
            $stmt->execute($executeParams);
            error_log("addProductApplication - Customer data inserted successfully");
            
            insert_success: // 재시도 성공 시 여기로 이동
            // INSERT 성공 (원래 시도 또는 재시도)
            error_log("addProductApplication - Customer data insert completed successfully");
        } catch (PDOException $e) {
            // 완전한 에러 정보 로깅
            $fullError = [
                'code' => $e->getCode(),
                'message' => $e->getMessage(),
                'sql_state' => $e->errorInfo[0] ?? 'unknown',
                'driver_code' => $e->errorInfo[1] ?? 'unknown',
                'driver_message' => $e->errorInfo[2] ?? 'unknown',
                'sql' => $sql,
                'param_keys' => array_keys($executeParams)
            ];
            error_log("addProductApplication - PDOException FULL ERROR: " . json_encode($fullError, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            
            // 에러 메시지에서 컬럼명 추출 시도
            if (preg_match("/Unknown column ['\"]([^'\"]+)['\"]/i", $e->getMessage(), $matches)) {
                $missingColumn = $matches[1];
                error_log("addProductApplication - 추출된 누락 컬럼명: " . $missingColumn);
            } elseif (preg_match("/Unknown column `([^`]+)`/i", $e->getMessage(), $matches)) {
                $missingColumn = $matches[1];
                error_log("addProductApplication - 추출된 누락 컬럼명 (backtick): " . $missingColumn);
            }
            
            // 컬럼이 없는 경우 - 실제 테이블 구조 확인
            if ($e->getCode() == 1054 || strpos($e->getMessage(), "Unknown column") !== false) {
                error_log("addProductApplication - 컬럼 오류 감지, 테이블 구조 확인 중...");
                try {
                    $columnsStmt = $pdo->query("SHOW COLUMNS FROM application_customers");
                    $columns = $columnsStmt->fetchAll(PDO::FETCH_COLUMN);
                    error_log("application_customers 테이블 컬럼 목록: " . implode(', ', $columns));
                } catch (PDOException $colErr) {
                    error_log("컬럼 목록 조회 실패: " . $colErr->getMessage());
                }
            }
            
            // user_id 컬럼 관련 에러인 경우 무조건 user_id 없이 재시도
            $isUserIdError = ($e->getCode() == 1054 || 
                             strpos($e->getMessage(), "Unknown column 'user_id'") !== false || 
                             strpos($e->getMessage(), "Unknown column `user_id`") !== false ||
                             (strpos($e->getMessage(), "user_id") !== false && strpos($e->getMessage(), "field list") !== false));
            
            if ($isUserIdError) {
                error_log("addProductApplication - user_id 컬럼 에러 감지, user_id 없이 재시도합니다.");
                error_log("  원본 에러: " . $e->getMessage());
                error_log("  hasUserIdColumn: " . ($hasUserIdColumn ? 'true' : 'false'));
                
                // user_id 없이 재시도 (hasUserIdColumn 값과 관계없이)
                $sqlRetry = "INSERT INTO application_customers (application_id, name, phone, email, address, address_detail, birth_date, gender, additional_info) VALUES (:application_id, :name, :phone, :email, :address, :address_detail, :birth_date, :gender, :additional_info)";
                $retryParams = $baseParams;
                unset($retryParams[':user_id']); // user_id 제거
                
                try {
                    error_log("addProductApplication - Retrying without user_id");
                    error_log("  Retry SQL: " . $sqlRetry);
                    error_log("  Retry Parameters: " . implode(', ', array_keys($retryParams)));
                    
                    $stmt2 = $pdo->prepare($sqlRetry);
                    $stmt2->execute($retryParams);
                    error_log("addProductApplication - Customer data inserted successfully (without user_id)");
                    
                    // 재시도 성공 - catch 블록을 빠져나가서 함수가 정상적으로 계속 진행되도록 함
                    // 이제 함수는 정상적으로 계속 진행되어 커밋하고 반환됨
                    goto insert_success; // 성공 후 함수 계속 진행
                } catch (PDOException $e2) {
                    error_log("addProductApplication - ERROR inserting customer data (retry): " . $e2->getMessage());
                    error_log("  Retry SQL State: " . ($e2->errorInfo[0] ?? 'unknown'));
                    error_log("  Retry Driver Code: " . ($e2->errorInfo[1] ?? 'unknown'));
                    error_log("  Retry Driver Message: " . ($e2->errorInfo[2] ?? 'unknown'));
                    throw $e2;
                }
            } else {
                // 다른 컬럼 오류인 경우 - 어떤 컬럼이 문제인지 파악
                $errorMsg = $e->getMessage();
                if (preg_match("/Unknown column ['\"]([^'\"]+)['\"]/i", $errorMsg, $matches)) {
                    $missingColumn = $matches[1];
                    error_log("addProductApplication - 누락된 컬럼 감지: " . $missingColumn);
                    
                    // 누락된 컬럼이 있으면 자동으로 추가 시도
                    if ($missingColumn && $missingColumn !== 'user_id') {
                        try {
                            error_log("addProductApplication - 누락된 컬럼 '{$missingColumn}' 추가 시도 중...");
                            if ($pdo->inTransaction()) {
                                $pdo->commit();
                            }
                            
                            // 컬럼 타입 추정 (일반적인 경우)
                            $columnDef = '';
                            switch ($missingColumn) {
                                case 'additional_info':
                                    $columnDef = "ADD COLUMN additional_info TEXT DEFAULT NULL COMMENT '추가 정보 (JSON)' AFTER gender";
                                    break;
                                case 'address':
                                    $columnDef = "ADD COLUMN address VARCHAR(255) DEFAULT NULL COMMENT '주소' AFTER email";
                                    break;
                                case 'address_detail':
                                    $columnDef = "ADD COLUMN address_detail VARCHAR(255) DEFAULT NULL COMMENT '상세주소' AFTER address";
                                    break;
                                case 'birth_date':
                                    $columnDef = "ADD COLUMN birth_date DATE DEFAULT NULL COMMENT '생년월일' AFTER address_detail";
                                    break;
                                case 'gender':
                                    $columnDef = "ADD COLUMN gender ENUM('male', 'female', 'other') DEFAULT NULL COMMENT '성별' AFTER birth_date";
                                    break;
                                default:
                                    error_log("addProductApplication - 알 수 없는 컬럼: {$missingColumn}");
                            }
                            
                            if ($columnDef) {
                                $pdo->exec("ALTER TABLE application_customers {$columnDef}");
                                error_log("addProductApplication - 컬럼 '{$missingColumn}' 추가 완료");
                                
                                if (!$pdo->inTransaction()) {
                                    $pdo->beginTransaction();
                                }
                                
                                // 재시도
                                $stmt->execute($executeParams);
                                error_log("addProductApplication - Customer data inserted successfully (after adding column)");
                                // 성공했으므로 함수 계속 진행
                            } else {
                                throw new Exception("필수 컬럼 '{$missingColumn}'이 없습니다. 데이터베이스 스키마를 확인하세요.");
                            }
                        } catch (PDOException $alterErr) {
                            error_log("addProductApplication - 컬럼 추가 실패: " . $alterErr->getMessage());
                            if (!$pdo->inTransaction()) {
                                $pdo->beginTransaction();
                            }
                            throw new Exception("필수 컬럼 '{$missingColumn}'이 없습니다. 데이터베이스 스키마를 확인하세요: " . $alterErr->getMessage());
                        }
                    } else {
                        error_log("addProductApplication - ERROR inserting customer data: " . $errorMsg);
                        throw $e;
                    }
                } else {
                    error_log("addProductApplication - ERROR inserting customer data: " . $errorMsg);
                    throw $e;
                }
            }
        }
        
        // 디버깅: 저장된 additional_info 확인
        if (!empty($customerData['additional_info'])) {
            $savedInfo = json_encode($customerData['additional_info'], JSON_UNESCAPED_UNICODE);
            error_log("Saved additional_info length: " . strlen($savedInfo));
            if (isset($customerData['additional_info']['product_snapshot'])) {
                error_log("product_snapshot exists in additional_info");
                error_log("product_snapshot plan_name: " . ($customerData['additional_info']['product_snapshot']['plan_name'] ?? 'NULL'));
            } else {
                error_log("ERROR: product_snapshot NOT found in additional_info!");
            }
        }
        
        error_log("addProductApplication - Committing transaction");
        $pdo->commit();
        error_log("addProductApplication - SUCCESS! Returning application ID: {$applicationId}");
        return $applicationId;
    } catch (PDOException $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            error_log("addProductApplication - Rolling back transaction due to PDOException");
            $pdo->rollBack();
        }
        error_log("addProductApplication - PDOException occurred:");
        error_log("  Message: " . $e->getMessage());
        error_log("  Code: " . $e->getCode());
        error_log("  SQL State: " . ($e->errorInfo[0] ?? 'unknown'));
        error_log("  Driver Error Code: " . ($e->errorInfo[1] ?? 'unknown'));
        error_log("  Driver Error Message: " . ($e->errorInfo[2] ?? 'unknown'));
        error_log("  Stack trace: " . $e->getTraceAsString());
        
        // 전역 변수에 에러 정보 저장
        global $lastDbError;
        $lastDbError = "PDO 오류: " . $e->getMessage() . " (SQL State: " . ($e->errorInfo[0] ?? 'unknown') . ", Driver Code: " . ($e->errorInfo[1] ?? 'unknown') . ")";
        
        return false;
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            error_log("addProductApplication - Rolling back transaction due to Exception");
            $pdo->rollBack();
        }
        error_log("addProductApplication - Exception occurred:");
        error_log("  Message: " . $e->getMessage());
        error_log("  Stack trace: " . $e->getTraceAsString());
        
        // 전역 변수에 에러 정보 저장
        global $lastDbError;
        $lastDbError = "예외 발생: " . $e->getMessage();
        
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

/**
 * 사용자의 MVNO 신청 내역 가져오기
 * @param int $userId 사용자 ID
 * @return array 신청 내역 배열
 */
function getUserMvnoApplications($userId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return [];
    }
    
    try {
        // 단순한 쿼리로 변경: application_customers에서 최신 레코드만 가져오기
        $stmt = $pdo->prepare("
            SELECT 
                a.id as application_id,
                a.order_number,
                a.product_id,
                a.application_status,
                a.created_at as order_date,
                p.seller_id,
                c.name,
                c.phone,
                c.email,
                c.additional_info,
                mvno.plan_name,
                mvno.provider,
                mvno.price_main,
                mvno.price_after,
                mvno.discount_period,
                mvno.data_amount,
                mvno.data_amount_value,
                mvno.data_unit,
                mvno.data_additional,
                mvno.data_additional_value,
                mvno.data_exhausted,
                mvno.data_exhausted_value,
                mvno.call_type,
                mvno.call_amount,
                mvno.sms_type,
                mvno.sms_amount,
                mvno.service_type,
                mvno.promotions,
                p.status as product_status
            FROM product_applications a
            INNER JOIN (
                SELECT c1.application_id, c1.id, c1.user_id, c1.additional_info, c1.name, c1.phone, c1.email
                FROM application_customers c1
                INNER JOIN (
                    SELECT application_id, MAX(id) as max_id
                    FROM application_customers
                    GROUP BY application_id
                ) c2 ON c1.application_id = c2.application_id AND c1.id = c2.max_id
            ) c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
            WHERE c.user_id = :user_id 
            AND a.product_type = 'mvno'
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 디버깅 로그
        error_log("getUserMvnoApplications - User ID: " . $userId);
        error_log("getUserMvnoApplications - Found applications: " . count($applications));
        if (!empty($applications)) {
            error_log("getUserMvnoApplications - First app: " . print_r($applications[0], true));
        }
        
        // 데이터 포맷팅
        $formattedApplications = [];
        $processingIndex = 0;
        foreach ($applications as $app) {
            $processingIndex++;
            try {
            error_log("=== START Processing application #{$processingIndex} ===");
            $appId = $app['application_id'] ?? null;
            if (!$appId) {
                error_log("WARNING: Skipping application with no application_id at index {$processingIndex}");
                error_log("App data keys: " . implode(', ', array_keys($app)));
                continue;
            }
            
            error_log("Processing application_id: {$appId}");
            error_log("App data: " . json_encode(array_keys($app)));
            
            // additional_info 파싱 (신청 당시 상품 정보가 저장된 곳)
            $additionalInfo = [];
            $productSnapshot = null; // 초기화
            
            error_log("Step 1: Starting additional_info parsing for application_id: {$appId}");
            
            if (!empty($app['additional_info'])) {
                error_log("Step 1.1: additional_info is not empty, length: " . strlen($app['additional_info']));
                $decoded = json_decode($app['additional_info'], true);
                $jsonError = json_last_error();
                error_log("Step 1.2: JSON decode result - error code: {$jsonError}, error msg: " . json_last_error_msg());
                
                if ($jsonError === JSON_ERROR_NONE && is_array($decoded)) {
                    $additionalInfo = $decoded;
                    error_log("Step 1.3: JSON decode successful, keys: " . implode(', ', array_keys($additionalInfo)));
                    
                    // product_snapshot 확인 (신청 당시 상품 정보가 여기 저장되어 있음)
                    if (isset($additionalInfo['product_snapshot'])) {
                        error_log("Step 1.4: product_snapshot key exists");
                        if (is_array($additionalInfo['product_snapshot'])) {
                            $productSnapshot = $additionalInfo['product_snapshot'];
                            error_log("Step 1.5: product_snapshot is array with " . count($productSnapshot) . " keys");
                            error_log("Step 1.6: product_snapshot keys: " . implode(', ', array_slice(array_keys($productSnapshot), 0, 10)));
                            error_log("Step 1.7: product_snapshot['plan_name'] = [" . ($productSnapshot['plan_name'] ?? 'NOT_SET') . "]");
                            error_log("Step 1.8: product_snapshot['provider'] = [" . ($productSnapshot['provider'] ?? 'NOT_SET') . "]");
                        } else {
                            error_log("WARNING Step 1.5: product_snapshot is not array, type: " . gettype($additionalInfo['product_snapshot']));
                        }
                    } else {
                        error_log("WARNING Step 1.4: product_snapshot key not found in additional_info");
                        error_log("Available keys: " . implode(', ', array_keys($additionalInfo)));
                    }
                } else {
                    error_log("ERROR Step 1.3: JSON decode failed for application {$appId}");
                    error_log("JSON error code: {$jsonError}, message: " . json_last_error_msg());
                    if (!empty($app['additional_info'])) {
                        $preview = substr($app['additional_info'], 0, 200);
                        error_log("additional_info preview: " . $preview);
                    }
                }
            } else {
                error_log("WARNING Step 1.1: additional_info is empty for application_id: {$appId}");
                error_log("additional_info value: " . var_export($app['additional_info'] ?? null, true));
            }
            
            // 상품 정보 변수 초기화
            $planName = '';
            $provider = '';
            $priceMain = 0;
            $priceAfter = null;
            $contractPeriod = '';
            $discountPeriod = '';
            $dataAmount = '';
            $dataAmountValue = '';
            $dataUnit = '';
            $dataAdditional = '';
            $dataAdditionalValue = '';
            $dataExhausted = '';
            $dataExhaustedValue = '';
            $callType = '';
            $callAmount = '';
            $smsType = '';
            $smsAmount = '';
            $serviceType = '';
            $promotions = '';
            
            // product_snapshot이 있으면 우선 사용 (신청 당시 상품 정보)
            error_log("Step 2: Checking productSnapshot for application_id: {$appId}");
            error_log("Step 2.1: productSnapshot empty check: " . (empty($productSnapshot) ? 'EMPTY' : 'NOT_EMPTY'));
            error_log("Step 2.2: productSnapshot type: " . gettype($productSnapshot));
            error_log("Step 2.3: productSnapshot is_array: " . (is_array($productSnapshot) ? 'YES' : 'NO'));
            
            if (!empty($productSnapshot) && is_array($productSnapshot)) {
                error_log("Step 2.4: Using product_snapshot data");
                // 스냅샷 데이터 사용 - 직접 배열 접근 (간단하고 명확하게)
                if (isset($productSnapshot['plan_name'])) {
                    error_log("Step 2.5: plan_name exists, value: [" . var_export($productSnapshot['plan_name'], true) . "]");
                    if (!empty($productSnapshot['plan_name'])) {
                        $planName = trim((string)$productSnapshot['plan_name']);
                        error_log("Step 2.6: planName assigned: [{$planName}]");
                    } else {
                        error_log("Step 2.6: plan_name is empty, skipping assignment");
                    }
                } else {
                    error_log("Step 2.5: plan_name key does not exist in productSnapshot");
                }
                
                if (isset($productSnapshot['provider'])) {
                    error_log("Step 2.7: provider exists, value: [" . var_export($productSnapshot['provider'], true) . "]");
                    if (!empty($productSnapshot['provider'])) {
                        $provider = trim((string)$productSnapshot['provider']);
                        error_log("Step 2.8: provider assigned: [{$provider}]");
                    } else {
                        error_log("Step 2.8: provider is empty, skipping assignment");
                    }
                } else {
                    error_log("Step 2.7: provider key does not exist in productSnapshot");
                }
                $priceMain = isset($productSnapshot['price_main']) && is_numeric($productSnapshot['price_main']) ? (float)$productSnapshot['price_main'] : 0;
                $priceAfter = isset($productSnapshot['price_after']) && is_numeric($productSnapshot['price_after']) && $productSnapshot['price_after'] != '0' ? (float)$productSnapshot['price_after'] : null;
                $contractPeriod = isset($productSnapshot['contract_period']) && $productSnapshot['contract_period'] !== null ? trim((string)$productSnapshot['contract_period']) : '';
                $discountPeriod = isset($productSnapshot['discount_period']) && $productSnapshot['discount_period'] !== null ? trim((string)$productSnapshot['discount_period']) : '';
                $dataAmount = isset($productSnapshot['data_amount']) && $productSnapshot['data_amount'] !== null ? trim((string)$productSnapshot['data_amount']) : '';
                $dataAmountValue = isset($productSnapshot['data_amount_value']) && $productSnapshot['data_amount_value'] !== null ? trim((string)$productSnapshot['data_amount_value']) : '';
                $dataUnit = isset($productSnapshot['data_unit']) && $productSnapshot['data_unit'] !== null ? trim((string)$productSnapshot['data_unit']) : '';
                $dataAdditional = isset($productSnapshot['data_additional']) && $productSnapshot['data_additional'] !== null ? trim((string)$productSnapshot['data_additional']) : '';
                $dataAdditionalValue = isset($productSnapshot['data_additional_value']) && $productSnapshot['data_additional_value'] !== null ? trim((string)$productSnapshot['data_additional_value']) : '';
                $dataExhausted = isset($productSnapshot['data_exhausted']) && $productSnapshot['data_exhausted'] !== null ? trim((string)$productSnapshot['data_exhausted']) : '';
                $dataExhaustedValue = isset($productSnapshot['data_exhausted_value']) && $productSnapshot['data_exhausted_value'] !== null ? trim((string)$productSnapshot['data_exhausted_value']) : '';
                $callType = isset($productSnapshot['call_type']) && $productSnapshot['call_type'] !== null ? trim((string)$productSnapshot['call_type']) : '';
                $callAmount = isset($productSnapshot['call_amount']) && $productSnapshot['call_amount'] !== null ? trim((string)$productSnapshot['call_amount']) : '';
                $smsType = isset($productSnapshot['sms_type']) && $productSnapshot['sms_type'] !== null ? trim((string)$productSnapshot['sms_type']) : '';
                $smsAmount = isset($productSnapshot['sms_amount']) && $productSnapshot['sms_amount'] !== null ? trim((string)$productSnapshot['sms_amount']) : '';
                $serviceType = isset($productSnapshot['service_type']) && $productSnapshot['service_type'] !== null ? trim((string)$productSnapshot['service_type']) : '';
                $promotions = isset($productSnapshot['promotions']) && $productSnapshot['promotions'] !== null 
                    ? (is_string($productSnapshot['promotions']) ? $productSnapshot['promotions'] : json_encode($productSnapshot['promotions'])) 
                    : '';
                
                error_log("Step 2.9: FINAL - planName=[{$planName}], provider=[{$provider}]");
            } else {
                // product_snapshot이 없으면 product_id로 현재 상품 정보를 직접 조회
                error_log("WARNING: Application " . $appId . " - product_snapshot not found, fetching current product info by product_id");
                
                $currentProduct = null;
                
                // product_id로 현재 상품 정보 직접 조회
                if (!empty($app['product_id'])) {
                    $productStmt = $pdo->prepare("
                        SELECT * FROM product_mvno_details WHERE product_id = :product_id LIMIT 1
                    ");
                    $productStmt->execute([':product_id' => $app['product_id']]);
                    $currentProduct = $productStmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($currentProduct) {
                        error_log("Debug - Fetched current product info by product_id: " . ($currentProduct['plan_name'] ?? 'NULL'));
                    } else {
                        error_log("Debug - No product found for product_id: " . $app['product_id']);
                    }
                }
                
                // 현재 상품 정보 사용 (fallback)
                // 먼저 product_id로 직접 조회한 정보가 있으면 사용, 없으면 JOIN된 정보 사용
                if (!empty($currentProduct)) {
                    $planName = $currentProduct['plan_name'] ?? '';
                    $provider = $currentProduct['provider'] ?? '';
                    $priceMain = isset($currentProduct['price_main']) && $currentProduct['price_main'] !== null && $currentProduct['price_main'] !== '' ? (float)$currentProduct['price_main'] : 0;
                    $priceAfter = isset($currentProduct['price_after']) && $currentProduct['price_after'] !== null && $currentProduct['price_after'] !== '' ? (float)$currentProduct['price_after'] : null;
                    $contractPeriod = $currentProduct['contract_period'] ?? '';
                    $discountPeriod = $currentProduct['discount_period'] ?? '';
                    $dataAmount = $currentProduct['data_amount'] ?? '';
                    $dataAmountValue = $currentProduct['data_amount_value'] ?? '';
                    $dataUnit = $currentProduct['data_unit'] ?? '';
                    $dataAdditional = $currentProduct['data_additional'] ?? '';
                    $dataAdditionalValue = $currentProduct['data_additional_value'] ?? '';
                    $dataExhausted = $currentProduct['data_exhausted'] ?? '';
                    $dataExhaustedValue = $currentProduct['data_exhausted_value'] ?? '';
                    $callType = $currentProduct['call_type'] ?? '';
                    $callAmount = $currentProduct['call_amount'] ?? '';
                    $smsType = $currentProduct['sms_type'] ?? '';
                    $smsAmount = $currentProduct['sms_amount'] ?? '';
                    $serviceType = $currentProduct['service_type'] ?? '';
                    $promotions = $currentProduct['promotions'] ?? '';
                    error_log("Debug - Using directly fetched product info for application " . $appId);
                } else {
                    // JOIN으로 가져온 정보 사용
                    $planName = !empty($app['plan_name']) ? $app['plan_name'] : '';
                    $provider = !empty($app['provider']) ? $app['provider'] : '';
                    $priceMain = isset($app['price_main']) && $app['price_main'] !== null && $app['price_main'] !== '' ? (float)$app['price_main'] : 0;
                    $priceAfter = isset($app['price_after']) && $app['price_after'] !== null && $app['price_after'] !== '' ? (float)$app['price_after'] : null;
                    $contractPeriod = !empty($app['contract_period']) ? $app['contract_period'] : '';
                    $discountPeriod = !empty($app['discount_period']) ? $app['discount_period'] : '';
                    $dataAmount = !empty($app['data_amount']) ? $app['data_amount'] : '';
                    $dataAmountValue = !empty($app['data_amount_value']) ? $app['data_amount_value'] : '';
                    $dataUnit = !empty($app['data_unit']) ? $app['data_unit'] : '';
                    $dataAdditional = !empty($app['data_additional']) ? $app['data_additional'] : '';
                    $dataAdditionalValue = !empty($app['data_additional_value']) ? $app['data_additional_value'] : '';
                    $dataExhausted = !empty($app['data_exhausted']) ? $app['data_exhausted'] : '';
                    $dataExhaustedValue = !empty($app['data_exhausted_value']) ? $app['data_exhausted_value'] : '';
                    $callType = !empty($app['call_type']) ? $app['call_type'] : '';
                    $callAmount = !empty($app['call_amount']) ? $app['call_amount'] : '';
                    $smsType = !empty($app['sms_type']) ? $app['sms_type'] : '';
                    $smsAmount = !empty($app['sms_amount']) ? $app['sms_amount'] : '';
                    $serviceType = !empty($app['service_type']) ? $app['service_type'] : '';
                    $promotions = !empty($app['promotions']) ? $app['promotions'] : '';
                    error_log("Debug - Using JOIN product info for application " . ($app['application_id'] ?? 'unknown'));
                }
                
                error_log("Debug - Final plan_name: " . ($planName ?: 'EMPTY'));
                error_log("Debug - Final provider: " . ($provider ?: 'EMPTY'));
            }
            
            // 데이터 제공량 포맷팅
            $dataMain = '';
            if (!empty($dataAmount)) {
                if ($dataAmount === '무제한') {
                    $dataMain = '무제한';
                } elseif ($dataAmount === '직접입력' && !empty($dataAmountValue)) {
                    $dataMain = '월 ' . number_format((float)$dataAmountValue) . $dataUnit;
                } else {
                    $dataMain = $dataAmount;
                }
                
                if (!empty($dataAdditional) && $dataAdditional !== '없음') {
                    if ($dataAdditional === '직접입력' && !empty($dataAdditionalValue)) {
                        $dataMain .= ' + ' . $dataAdditionalValue;
                    } else {
                        $dataMain .= ' + ' . $dataAdditional;
                    }
                }
                
                if (!empty($dataExhausted) && $dataExhausted !== '직접입력') {
                    $dataMain .= ' + ' . $dataExhausted;
                } elseif (!empty($dataExhausted) && $dataExhausted === '직접입력' && !empty($dataExhaustedValue)) {
                    $dataMain .= ' + ' . $dataExhaustedValue;
                }
            }
            
            // 기능 배열 생성
            $features = [];
            if (!empty($callType)) {
                if ($callType === '무제한') {
                    $features[] = '통화 무제한';
                } elseif ($callType === '기본제공') {
                    $features[] = '통화 기본제공';
                } elseif ($callType === '직접입력' && !empty($callAmount)) {
                    $features[] = '통화 ' . number_format((float)$callAmount) . '분';
                }
            }
            
            if (!empty($smsType)) {
                if ($smsType === '무제한') {
                    $features[] = '문자 무제한';
                } elseif ($smsType === '기본제공') {
                    $features[] = '문자 기본제공';
                } elseif ($smsType === '직접입력' && !empty($smsAmount)) {
                    $features[] = '문자 ' . number_format((float)$smsAmount) . '건';
                }
            }
            
            if (!empty($provider)) {
                $features[] = $provider;
            }
            
            if (!empty($serviceType)) {
                $features[] = $serviceType;
            }
            
            // 가격 포맷팅
            $priceMainFormatted = '';
            if ($priceAfter !== null && $priceAfter !== '' && $priceAfter !== '0') {
                $priceMainFormatted = '월 ' . number_format((float)$priceAfter) . '원';
            } else {
                $priceMainFormatted = '월 ' . number_format((float)$priceMain) . '원';
            }
            
            $priceAfterFormatted = '';
            if (!empty($discountPeriod)) {
                $priceAfterFormatted = $discountPeriod . ' 이후 월 ' . number_format((float)$priceMain) . '원';
            } else {
                $priceAfterFormatted = '월 ' . number_format((float)$priceMain) . '원';
            }
            
            // 프로모션 파싱
            $gifts = [];
            if (!empty($promotions)) {
                $promotionsArray = json_decode($promotions, true);
                if (is_array($promotionsArray)) {
                    $gifts = array_filter($promotionsArray, function($p) {
                        return !empty(trim($p));
                    });
                }
            }
            
            // 리뷰 작성 여부 확인
            error_log("Step 3: Starting review check for application_id: {$appId}");
            $hasReview = false;
            $rating = '';
            if (!empty($app['product_id'])) {
                error_log("Step 3.1: product_id exists: " . $app['product_id']);
                try {
                    $reviewStmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM product_reviews
                        WHERE product_id = :product_id 
                        AND user_id = :user_id 
                        AND status = 'approved'
                    ");
                    $reviewStmt->execute([
                        ':product_id' => $app['product_id'],
                        ':user_id' => $userId
                    ]);
                    $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                    $hasReview = ($reviewResult['count'] ?? 0) > 0;
                    error_log("Step 3.2: Review check completed, hasReview: " . ($hasReview ? 'YES' : 'NO'));
                    
                    // 평균 별점 가져오기
                    error_log("Step 3.3: Starting average rating fetch");
                    if (!function_exists('getProductAverageRating')) {
                        error_log("Step 3.4: getProductAverageRating function does not exist, requiring plan-data.php");
                        $planDataPath = __DIR__ . '/plan-data.php';
                        error_log("Step 3.5: plan-data.php path: {$planDataPath}");
                        error_log("Step 3.6: File exists: " . (file_exists($planDataPath) ? 'YES' : 'NO'));
                        require_once $planDataPath;
                        error_log("Step 3.7: plan-data.php required");
                    }
                    error_log("Step 3.8: Calling getProductAverageRating(" . $app['product_id'] . ", 'mvno')");
                    $averageRating = getProductAverageRating($app['product_id'], 'mvno');
                    error_log("Step 3.9: getProductAverageRating returned: " . var_export($averageRating, true));
                    $rating = $averageRating > 0 ? number_format($averageRating, 1) : '';
                    error_log("Step 3.10: rating formatted: [{$rating}]");
                } catch (Exception $e) {
                    error_log("ERROR Step 3: Exception in review/rating check for application {$appId}");
                    error_log("Exception message: " . $e->getMessage());
                    error_log("Exception code: " . $e->getCode());
                    error_log("Exception file: " . $e->getFile() . ":" . $e->getLine());
                    error_log("Stack trace: " . $e->getTraceAsString());
                    $rating = '';
                }
            } else {
                error_log("Step 3.1: product_id is empty, skipping review check");
            }
            
            // 상태 한글 변환 (공통 함수 사용)
            error_log("Step 4: Converting application status to Korean");
            error_log("Step 4.1: application_status: " . ($app['application_status'] ?? 'NULL'));
            try {
                $statusKor = getApplicationStatusLabel($app['application_status']);
                error_log("Step 4.2: statusKor: [{$statusKor}]");
            } catch (Exception $e) {
                error_log("ERROR Step 4: Exception in getApplicationStatusLabel: " . $e->getMessage());
                $statusKor = '접수완료'; // 기본값
            }
            
            // 최종 값 확인 로그
            error_log("Step 5: Before formatting - planName=[{$planName}], provider=[{$provider}]");
            
            $formattedApplications[] = [
                'id' => (int)$app['product_id'],
                'application_id' => (int)$app['application_id'],
                'order_number' => $app['order_number'] ?? '',
                'provider' => !empty($provider) ? $provider : '알 수 없음',
                'rating' => $rating,
                'title' => !empty($planName) ? $planName : '요금제 정보 없음',
                'data_main' => !empty($dataMain) ? $dataMain : '데이터 정보 없음',
                'features' => $features,
                'price_main' => !empty($priceMainFormatted) ? $priceMainFormatted : '가격 정보 없음',
                'price_after' => $priceAfterFormatted,
                'contract_period' => $contractPeriod,
                'discount_period' => $discountPeriod,
                'gifts' => $gifts,
                'gift_count' => count($gifts),
                'order_date' => !empty($app['order_date']) ? date('Y.m.d', strtotime($app['order_date'])) : date('Y.m.d'),
                'activation_date' => '', // 개통일은 별도 관리 필요
                'has_review' => $hasReview,
                'is_sold_out' => ($app['product_status'] ?? 'active') !== 'active',
                'status' => $statusKor,
                'application_status' => $app['application_status']
            ];
            
            $lastIndex = count($formattedApplications) - 1;
            error_log("Step 6: After formatting - index: {$lastIndex}");
            error_log("Step 6.1: formatted provider: [" . $formattedApplications[$lastIndex]['provider'] . "]");
            error_log("Step 6.2: formatted title: [" . $formattedApplications[$lastIndex]['title'] . "]");
            error_log("=== END Processing application #{$processingIndex} (SUCCESS) ===");
            
            } catch (Exception $e) {
                // 개별 항목 포맷팅 실패 시에도 계속 진행
                error_log("=== ERROR Processing application #{$processingIndex} ===");
                error_log("ERROR: Exception in getUserMvnoApplications");
                error_log("ERROR: application_id: " . ($app['application_id'] ?? 'unknown'));
                error_log("ERROR: Exception class: " . get_class($e));
                error_log("ERROR: Exception message: " . $e->getMessage());
                error_log("ERROR: Exception code: " . $e->getCode());
                error_log("ERROR: Exception file: " . $e->getFile());
                error_log("ERROR: Exception line: " . $e->getLine());
                error_log("ERROR: Stack trace:\n" . $e->getTraceAsString());
                error_log("ERROR: Application data keys: " . implode(', ', array_keys($app)));
                error_log("ERROR: Application data preview: " . json_encode(array_slice($app, 0, 5)));
                error_log("ERROR: additional_info exists: " . (isset($app['additional_info']) ? 'YES' : 'NO'));
                if (isset($app['additional_info'])) {
                    error_log("ERROR: additional_info length: " . strlen($app['additional_info']));
                    error_log("ERROR: additional_info preview: " . substr($app['additional_info'], 0, 100));
                }
                error_log("ERROR: product_id: " . ($app['product_id'] ?? 'NULL'));
                error_log("ERROR: Variables at exception time:");
                error_log("ERROR: - planName: [" . (isset($planName) ? $planName : 'NOT_SET') . "]");
                error_log("ERROR: - provider: [" . (isset($provider) ? $provider : 'NOT_SET') . "]");
                error_log("ERROR: - productSnapshot: " . (isset($productSnapshot) ? (is_array($productSnapshot) ? 'ARRAY(' . count($productSnapshot) . ' keys)' : gettype($productSnapshot)) : 'NOT_SET'));
                // 최소한의 정보라도 표시
                $formattedApplications[] = [
                    'id' => (int)($app['product_id'] ?? 0),
                    'application_id' => (int)($app['application_id'] ?? 0),
                    'provider' => '알 수 없음',
                    'rating' => '',
                    'title' => '요금제 정보 없음',
                    'data_main' => '데이터 정보 없음',
                    'features' => [],
                    'price_main' => '가격 정보 없음',
                    'price_after' => '',
                    'gifts' => [],
                    'gift_count' => 0,
                    'order_date' => !empty($app['order_date']) ? date('Y.m.d', strtotime($app['order_date'])) : date('Y.m.d'),
                    'activation_date' => '',
                    'has_review' => false,
                    'is_sold_out' => false,
                    'status' => getApplicationStatusLabel($app['application_status'] ?? 'pending'),
                    'application_status' => $app['application_status'] ?? 'pending'
                ];
            }
        }
        
        return $formattedApplications;
    } catch (PDOException $e) {
        error_log("Error fetching user MVNO applications: " . $e->getMessage());
        return [];
    }
}

/**
 * 사용자의 통신사폰 신청 내역 조회
 * @param int $userId 사용자 ID
 * @return array 신청 내역 배열
 */
function getUserMnoApplications($userId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log("getUserMnoApplications: DB connection failed");
        return [];
    }
    
    try {
        // 디버깅: user_id 확인
        error_log("getUserMnoApplications: userId = " . var_export($userId, true));
        
        $stmt = $pdo->prepare("
            SELECT 
                a.id as application_id,
                a.order_number,
                a.product_id,
                a.application_status,
                a.created_at as order_date,
                p.seller_id,
                c.name,
                c.phone,
                c.email,
                c.additional_info,
                mno.device_name,
                mno.device_price,
                mno.device_capacity,
                mno.device_colors,
                mno.common_provider,
                mno.common_discount_new,
                mno.common_discount_port,
                mno.common_discount_change,
                mno.contract_provider,
                mno.contract_discount_new,
                mno.contract_discount_port,
                mno.contract_discount_change,
                mno.service_type,
                mno.contract_period,
                mno.contract_period_value,
                mno.price_main,
                mno.data_amount,
                mno.data_amount_value,
                mno.data_unit,
                mno.data_exhausted,
                mno.data_exhausted_value,
                mno.call_type,
                mno.call_amount,
                mno.sms_type,
                mno.sms_amount,
                mno.promotions,
                p.status as product_status
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
            WHERE c.user_id = :user_id 
            AND a.product_type = 'mno'
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 디버깅: 쿼리 결과 확인
        error_log("getUserMnoApplications: Found " . count($applications) . " applications for user_id: " . $userId);
        if (count($applications) > 0) {
            error_log("getUserMnoApplications: First application keys: " . implode(', ', array_keys($applications[0])));
            error_log("getUserMnoApplications: First application_id: " . ($applications[0]['application_id'] ?? 'N/A'));
            error_log("getUserMnoApplications: First product_id: " . ($applications[0]['product_id'] ?? 'N/A'));
            error_log("getUserMnoApplications: First additional_info exists: " . (isset($applications[0]['additional_info']) ? 'YES' : 'NO'));
        } else {
            error_log("getUserMnoApplications: No applications found - checking query...");
            // 쿼리 재실행하여 확인
            $testStmt = $pdo->prepare("
                SELECT COUNT(*) as cnt 
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                WHERE c.user_id = :user_id AND a.product_type = 'mno'
            ");
            $testStmt->execute([':user_id' => $userId]);
            $testResult = $testStmt->fetch(PDO::FETCH_ASSOC);
            error_log("getUserMnoApplications: Test query count: " . ($testResult['cnt'] ?? 0));
        }
        
        // 데이터 포맷팅
        $formattedApplications = [];
        foreach ($applications as $index => $app) {
            try {
            // additional_info 파싱
            $additionalInfo = [];
            if (!empty($app['additional_info'])) {
                $additionalInfo = json_decode($app['additional_info'], true) ?: [];
            }
            
            // 상품 스냅샷이 있으면 사용 (신청 당시 상품 정보)
            $productSnapshot = $additionalInfo['product_snapshot'] ?? null;
            if ($productSnapshot) {
                $deviceName = $productSnapshot['device_name'] ?? $app['device_name'] ?? '';
                $devicePrice = $productSnapshot['device_price'] ?? $app['device_price'] ?? '';
                $deviceCapacity = $productSnapshot['device_capacity'] ?? $app['device_capacity'] ?? '';
                $commonProvider = $productSnapshot['common_provider'] ?? $app['common_provider'] ?? '';
                $priceMain = $productSnapshot['price_main'] ?? $app['price_main'] ?? 0;
                $dataAmount = $productSnapshot['data_amount'] ?? $app['data_amount'] ?? '';
                $callType = $productSnapshot['call_type'] ?? $app['call_type'] ?? '';
                $promotions = $productSnapshot['promotions'] ?? $app['promotions'] ?? '';
            } else {
                $deviceName = $app['device_name'] ?? '';
                $devicePrice = $app['device_price'] ?? '';
                $deviceCapacity = $app['device_capacity'] ?? '';
                $commonProvider = $app['common_provider'] ?? '';
                $priceMain = $app['price_main'] ?? 0;
                $dataAmount = $app['data_amount'] ?? '';
                $callType = $app['call_type'] ?? '';
                $promotions = $app['promotions'] ?? '';
            }
            
            // 통신사 추출 (additional_info에서 carrier 정보 우선 사용)
            $provider = 'SKT';
            if (!empty($additionalInfo['carrier'])) {
                $provider = $additionalInfo['carrier'];
            } else if (!empty($commonProvider)) {
                $commonProviderArray = json_decode($commonProvider, true);
                if (is_array($commonProviderArray) && !empty($commonProviderArray)) {
                    $provider = $commonProviderArray[0] ?? 'SKT';
                }
            }
            
            // 요금제명 생성 (product_snapshot에서 가져온 정보 사용)
            $planName = $provider . ' ' . ($dataAmount ?: '기본 요금제');
            if (empty($dataAmount) && empty($planName)) {
                $planName = $provider . ' 요금제';
            }
            
            // 가격 포맷팅 (additional_info에서 price 정보 우선 사용)
            $displayPrice = $priceMain;
            if (isset($additionalInfo['price']) && $additionalInfo['price'] !== '' && $additionalInfo['price'] !== '0') {
                $displayPrice = (float)$additionalInfo['price'];
            }
            $priceFormatted = '월 ' . number_format((float)$displayPrice) . '원';
            if ($displayPrice == 0 || empty($displayPrice)) {
                $priceFormatted = '요금 정보 없음';
            }
            
            // 색상 정보 추출
            $deviceColor = '';
            if (!empty($additionalInfo['device_colors']) && is_array($additionalInfo['device_colors']) && count($additionalInfo['device_colors']) > 0) {
                $deviceColor = $additionalInfo['device_colors'][0];
            }
            
            // 할인 정보 추출
            $discountType = $additionalInfo['discount_type'] ?? '';
            $subscriptionType = $additionalInfo['subscription_type'] ?? '';
            $discountPrice = $additionalInfo['price'] ?? '';
            $discountInfo = '';
            if ($discountType && $subscriptionType && $discountPrice !== '') {
                // 가격이 0이어도 표시
                $discountInfo = $provider . ' ' . $discountType . ' ' . $subscriptionType . ' ' . $discountPrice;
            }
            
            // 프로모션 파싱
            $gifts = [];
            if (!empty($promotions)) {
                $promotionsArray = json_decode($promotions, true);
                if (is_array($promotionsArray)) {
                    $gifts = array_filter($promotionsArray, function($p) {
                        return !empty(trim($p));
                    });
                }
            }
            
            // 리뷰 작성 여부 확인
            $hasReview = false;
            $rating = '';
            if (!empty($app['product_id'])) {
                try {
                    $reviewStmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM product_reviews
                        WHERE product_id = :product_id 
                        AND user_id = :user_id 
                        AND status = 'approved'
                    ");
                    $reviewStmt->execute([
                        ':product_id' => $app['product_id'],
                        ':user_id' => $userId
                    ]);
                    $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                    $hasReview = ($reviewResult['count'] ?? 0) > 0;
                    
                    // 평균 별점 가져오기 (선택사항, 에러가 발생해도 계속 진행)
                    try {
                        if (!function_exists('getProductAverageRating')) {
                            $planDataFile = __DIR__ . '/plan-data.php';
                            if (file_exists($planDataFile)) {
                                require_once $planDataFile;
                            }
                        }
                        if (function_exists('getProductAverageRating')) {
                            $averageRating = getProductAverageRating($app['product_id'], 'mno');
                            $rating = $averageRating > 0 ? number_format($averageRating, 1) : '';
                        }
                    } catch (Exception $e) {
                        error_log("Error getting average rating for product {$app['product_id']}: " . $e->getMessage());
                        // 에러가 발생해도 계속 진행
                    }
                } catch (Exception $e) {
                    error_log("Error fetching review info for product {$app['product_id']}: " . $e->getMessage());
                    // 에러가 발생해도 계속 진행
                }
            }
            
            // 상태 한글 변환
            $statusMap = [
                'pending' => '접수완료',
                'processing' => '처리중',
                'completed' => '완료',
                'cancelled' => '취소',
                'rejected' => '거부',
                'closed' => '종료',
                'activating' => '개통중',
                'activation_completed' => '개통완료',
                'on_hold' => '보류',
                '' => '접수완료' // 빈 문자열도 접수완료로 처리
            ];
            $applicationStatus = $app['application_status'] ?? '';
            $statusKor = $statusMap[$applicationStatus] ?? ($applicationStatus ?: '접수완료');
            
            $formattedApplications[] = [
                'id' => (int)$app['product_id'],
                'application_id' => (int)$app['application_id'],
                'order_number' => $app['order_number'] ?? null,
                'provider' => $provider,
                'device_name' => $deviceName,
                'device_storage' => $deviceCapacity,
                'device_price' => $devicePrice ? number_format((float)$devicePrice) . '원' : '',
                'device_color' => $deviceColor,
                'discount_info' => $discountInfo,
                'plan_name' => $planName,
                'price' => $priceFormatted,
                'company_name' => '이야기모바일', // 기본값
                'rating' => $rating,
                'order_date' => !empty($app['order_date']) ? date('Y.m.d', strtotime($app['order_date'])) : '',
                'order_time' => !empty($app['order_date']) ? date('H:i', strtotime($app['order_date'])) : '',
                'activation_date' => '', // 개통일은 별도 관리 필요
                'activation_time' => '',
                'has_review' => $hasReview,
                'is_sold_out' => ($app['product_status'] ?? 'active') !== 'active',
                'status' => $statusKor,
                'application_status' => $app['application_status'],
                'gifts' => $gifts,
                'link_url' => '/MVNO/mno/mno-phone-detail.php?id=' . $app['product_id']
            ];
            } catch (Exception $e) {
                error_log("Error formatting application #{$index} (application_id: " . ($app['application_id'] ?? 'unknown') . "): " . $e->getMessage());
                error_log("Application data: " . json_encode($app, JSON_UNESCAPED_UNICODE));
                // 에러가 발생해도 계속 진행
                continue;
            }
        }
        
        error_log("getUserMnoApplications: Formatted " . count($formattedApplications) . " applications");
        error_log("getUserMnoApplications: Returning array with " . count($formattedApplications) . " items");
        if (count($formattedApplications) > 0) {
            error_log("getUserMnoApplications: First formatted item keys: " . implode(', ', array_keys($formattedApplications[0])));
        }
        return $formattedApplications;
    } catch (PDOException $e) {
        error_log("Error fetching user MNO applications: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        return [];
    } catch (Exception $e) {
        error_log("General error in getUserMnoApplications: " . $e->getMessage());
        return [];
    }
}

/**
 * 사용자의 인터넷 신청 내역 조회
 * @param int $userId 사용자 ID
 * @return array 신청 내역 배열
 */
function getUserInternetApplications($userId) {
    $pdo = getDBConnection();
    if (!$pdo) {
        error_log("getUserInternetApplications: Database connection failed for user_id: " . $userId);
        return [];
    }
    
    try {
        error_log("getUserInternetApplications: Querying for user_id: " . $userId);
        $stmt = $pdo->prepare("
            SELECT 
                a.id as application_id,
                a.order_number,
                a.product_id,
                a.application_status,
                a.created_at as order_date,
                p.seller_id,
                c.name,
                c.phone,
                c.email,
                c.additional_info,
                internet.registration_place,
                internet.service_type,
                internet.speed_option,
                internet.monthly_fee,
                internet.cash_payment_names,
                internet.cash_payment_prices,
                internet.gift_card_names,
                internet.gift_card_prices,
                internet.equipment_names,
                internet.equipment_prices,
                internet.installation_names,
                internet.installation_prices,
                p.status as product_status
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            INNER JOIN products p ON a.product_id = p.id
            LEFT JOIN product_internet_details internet ON p.id = internet.product_id
            WHERE c.user_id = :user_id 
            AND a.product_type = 'internet'
            ORDER BY a.created_at DESC
        ");
        $stmt->execute([':user_id' => $userId]);
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        error_log("getUserInternetApplications: Found " . count($applications) . " raw applications for user_id: " . $userId);
        
        if (count($applications) > 0) {
            error_log("getUserInternetApplications: First application keys: " . implode(', ', array_keys($applications[0])));
            error_log("getUserInternetApplications: First application application_id: " . ($applications[0]['application_id'] ?? 'N/A'));
            error_log("getUserInternetApplications: First application has additional_info: " . (!empty($applications[0]['additional_info']) ? 'yes (' . strlen($applications[0]['additional_info']) . ' chars)' : 'no'));
            if (!empty($applications[0]['additional_info'])) {
                error_log("getUserInternetApplications: First application additional_info preview: " . substr($applications[0]['additional_info'], 0, 100));
            }
        } else {
            error_log("getUserInternetApplications: WARNING - No applications found for user_id: " . $userId);
            error_log("getUserInternetApplications: Query executed but returned 0 results. Check if user_id matches.");
        }
        
        // 데이터 포맷팅
        $formattedApplications = [];
        foreach ($applications as $index => $app) {
            try {
                error_log("getUserInternetApplications: Processing application " . ($index + 1) . " (application_id: " . ($app['application_id'] ?? 'unknown') . ")");
                
                // additional_info 파싱
                $additionalInfo = [];
            if (!empty($app['additional_info'])) {
                // JSON 문자열이 이스케이프된 경우 처리
                $additionalInfoStr = $app['additional_info'];
                
                // 실제 줄바꿈 문자 제거 (JSON 파싱 전에)
                $additionalInfoStr = str_replace(["\n", "\r", "\t"], ['', '', ''], $additionalInfoStr);
                
                // JSON 파싱 시도
                $additionalInfo = json_decode($additionalInfoStr, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // JSON 파싱 실패 시 빈 배열
                    error_log("JSON decode error for application_id " . ($app['application_id'] ?? 'unknown') . ": " . json_last_error_msg() . " | JSON: " . substr($additionalInfoStr, 0, 200));
                    $additionalInfo = [];
                } else {
                    error_log("JSON decode success for application_id " . ($app['application_id'] ?? 'unknown'));
                }
            }
            
            // 판매자 페이지와 동일하게 product_internet_details 테이블의 현재 값을 우선 사용
            // product_snapshot은 테이블 값이 없을 때만 fallback으로 사용
            $productSnapshot = $additionalInfo['product_snapshot'] ?? null;
            error_log("getUserInternetApplications: application_id " . ($app['application_id'] ?? 'unknown') . " - productSnapshot exists: " . ($productSnapshot ? 'yes' : 'no'));
            
            // monthly_fee 추출 헬퍼 함수 (숫자 또는 문자열에서 숫자 추출)
            $extractMonthlyFee = function($value) {
                if (empty($value) && $value !== '0' && $value !== 0) {
                    return 0;
                }
                // 숫자인 경우
                if (is_numeric($value)) {
                    $num = (float)$value;
                    return $num > 0 ? $num : 0;
                }
                // 문자열인 경우 (예: "26400원", "26,400원")
                if (is_string($value)) {
                    // 숫자와 쉼표만 추출
                    $numericPart = preg_replace('/[^0-9,]/', '', $value);
                    $numericPart = str_replace(',', '', $numericPart);
                    if (!empty($numericPart) && is_numeric($numericPart)) {
                        $num = (float)$numericPart;
                        return $num > 0 ? $num : 0;
                    }
                }
                return 0;
            };
            
            // 테이블 값이 있으면 우선 사용 (판매자 페이지와 동일)
            if (!empty($app['registration_place']) || !empty($app['service_type']) || !empty($app['speed_option']) || isset($app['monthly_fee'])) {
                $registrationPlace = $app['registration_place'] ?? '';
                $speedOption = $app['speed_option'] ?? '';
                $serviceType = $app['service_type'] ?? '';
                
                // monthly_fee 처리: 테이블에 값이 있고 유효하면 사용, 없거나 0이면 product_snapshot에서 가져오기
                $monthlyFee = $extractMonthlyFee($app['monthly_fee'] ?? 0);
                if ($monthlyFee <= 0 && $productSnapshot && isset($productSnapshot['monthly_fee'])) {
                    // 테이블 값이 없거나 0이면 product_snapshot에서 가져오기
                    $monthlyFee = $extractMonthlyFee($productSnapshot['monthly_fee']);
                }
            } elseif ($productSnapshot) {
                // 테이블 값이 없으면 product_snapshot 사용 (fallback)
                $registrationPlace = $productSnapshot['registration_place'] ?? '';
                $speedOption = $productSnapshot['speed_option'] ?? '';
                $serviceType = $productSnapshot['service_type'] ?? '';
                
                // monthly_fee는 문자열일 수 있으므로 숫자로 변환
                $monthlyFee = $extractMonthlyFee($productSnapshot['monthly_fee'] ?? 0);
            } else {
                $registrationPlace = '';
                $speedOption = '';
                $serviceType = '';
                $monthlyFee = 0;
            }
            
            // 현금지급, 상품권, 장비, 설치 정보도 테이블 값을 우선 사용 (판매자 페이지와 동일)
            $cashPaymentNames = $app['cash_payment_names'] ?? '';
            $giftCardNames = $app['gift_card_names'] ?? '';
            $equipmentNames = $app['equipment_names'] ?? '';
            $installationNames = $app['installation_names'] ?? '';
            
            // 테이블 값이 없으면 product_snapshot에서 가져오기 (fallback)
            if (empty($cashPaymentNames) && empty($giftCardNames) && empty($equipmentNames) && empty($installationNames) && $productSnapshot) {
                // JSON 문자열로 저장된 필드들 파싱
                if (isset($productSnapshot['cash_payment_names'])) {
                    if (is_string($productSnapshot['cash_payment_names'])) {
                        $decoded = json_decode($productSnapshot['cash_payment_names'], true);
                        $cashPaymentNames = is_array($decoded) ? implode(', ', $decoded) : $productSnapshot['cash_payment_names'];
                    } else {
                        $cashPaymentNames = is_array($productSnapshot['cash_payment_names']) ? implode(', ', $productSnapshot['cash_payment_names']) : '';
                    }
                }
                
                if (isset($productSnapshot['gift_card_names'])) {
                    if (is_string($productSnapshot['gift_card_names'])) {
                        $decoded = json_decode($productSnapshot['gift_card_names'], true);
                        $giftCardNames = is_array($decoded) ? implode(', ', $decoded) : $productSnapshot['gift_card_names'];
                    } else {
                        $giftCardNames = is_array($productSnapshot['gift_card_names']) ? implode(', ', $productSnapshot['gift_card_names']) : '';
                    }
                }
                
                if (isset($productSnapshot['equipment_names'])) {
                    if (is_string($productSnapshot['equipment_names'])) {
                        $decoded = json_decode($productSnapshot['equipment_names'], true);
                        $equipmentNames = is_array($decoded) ? implode(', ', $decoded) : $productSnapshot['equipment_names'];
                    } else {
                        $equipmentNames = is_array($productSnapshot['equipment_names']) ? implode(', ', $productSnapshot['equipment_names']) : '';
                    }
                }
                
                if (isset($productSnapshot['installation_names'])) {
                    if (is_string($productSnapshot['installation_names'])) {
                        $decoded = json_decode($productSnapshot['installation_names'], true);
                        $installationNames = is_array($decoded) ? implode(', ', $decoded) : $productSnapshot['installation_names'];
                    } else {
                        $installationNames = is_array($productSnapshot['installation_names']) ? implode(', ', $productSnapshot['installation_names']) : '';
                    }
                }
            }
            
            // 요금제명 생성 (DB에 저장된 service_type 그대로 사용)
            if (!empty($serviceType)) {
                $planName = $serviceType;
            } else {
                // service_type이 없으면 기본 생성
                $planName = '인터넷 ' . ($speedOption ?: '기본');
                if (!empty($cashPaymentNames) || !empty($giftCardNames) || !empty($equipmentNames) || !empty($installationNames)) {
                    $planName .= ' + TV';
                }
            }
            
            // 가격 포맷팅
            $priceFormatted = '월 ' . number_format((float)$monthlyFee) . '원';
            
            // 속도 포맷팅
            $speedFormatted = $speedOption ?: '100MB';
            
            // 리뷰 작성 여부 확인 (테이블이 없을 수 있으므로 try-catch로 처리)
            $hasReview = false;
            $reviewCount = 0;
            if (!empty($app['product_id'])) {
                try {
                    // application_id 컬럼 존재 여부 확인
                    $hasApplicationId = false;
                    try {
                        $checkStmt = $pdo->query("SHOW COLUMNS FROM product_reviews LIKE 'application_id'");
                        $hasApplicationId = $checkStmt->rowCount() > 0;
                    } catch (PDOException $e) {}
                    
                    // application_id가 있으면 application_id로 조회, 없으면 product_id로 조회
                    if ($hasApplicationId && !empty($app['application_id'])) {
                        $reviewStmt = $pdo->prepare("
                            SELECT COUNT(*) as count
                            FROM product_reviews
                            WHERE product_id = :product_id
                            AND user_id = :user_id
                            AND application_id = :application_id
                            AND status = 'approved'
                        ");
                        $reviewStmt->execute([
                            ':product_id' => $app['product_id'],
                            ':user_id' => $userId,
                            ':application_id' => $app['application_id']
                        ]);
                    } else {
                        $reviewStmt = $pdo->prepare("
                            SELECT COUNT(*) as count
                            FROM product_reviews
                            WHERE product_id = :product_id
                            AND user_id = :user_id
                            AND status = 'approved'
                        ");
                        $reviewStmt->execute([
                            ':product_id' => $app['product_id'],
                            ':user_id' => $userId
                        ]);
                    }
                    $reviewResult = $reviewStmt->fetch(PDO::FETCH_ASSOC);
                    $hasReview = ($reviewResult['count'] ?? 0) > 0;
                    
                    // 전체 리뷰 개수
                    $allReviewStmt = $pdo->prepare("
                        SELECT COUNT(*) as count
                        FROM product_reviews
                        WHERE product_id = :product_id 
                        AND status = 'approved'
                    ");
                    $allReviewStmt->execute([':product_id' => $app['product_id']]);
                    $allReviewResult = $allReviewStmt->fetch(PDO::FETCH_ASSOC);
                    $reviewCount = (int)($allReviewResult['count'] ?? 0);
                } catch (PDOException $e) {
                    // product_reviews 테이블이 없거나 에러가 발생해도 계속 진행
                    error_log("getUserInternetApplications: Could not fetch reviews for application_id " . ($app['application_id'] ?? 'unknown') . ": " . $e->getMessage());
                    $hasReview = false;
                    $reviewCount = 0;
                }
            }
            
            // 기존 인터넷 회선 정보 가져오기 (additional_info에서)
            $existingCompany = '';
            if (!empty($additionalInfo)) {
                $existingCompany = $additionalInfo['currentCompany'] ?? $additionalInfo['existing_company'] ?? $additionalInfo['existingCompany'] ?? '';
            }
            
            // 상태 한글 변환 (공통 함수 사용 - 알뜰폰과 동일)
            $statusKor = getApplicationStatusLabel($app['application_status']);
            
            $formattedApplications[] = [
                'id' => (int)$app['product_id'],
                'application_id' => (int)$app['application_id'],
                'order_number' => $app['order_number'] ?? null,
                'provider' => $registrationPlace,
                'plan_name' => $planName,
                'speed' => $speedFormatted,
                'tv_combined' => !empty($cashPaymentNames) || !empty($giftCardNames) || !empty($equipmentNames) || !empty($installationNames),
                'price' => $priceFormatted,
                'installation_fee' => '무료',
                'order_date' => date('Y.m.d', strtotime($app['order_date'])),
                'installation_date' => '', // 설치일은 별도 관리 필요
                'has_review' => $hasReview,
                'review_count' => $reviewCount,
                'is_sold_out' => ($app['product_status'] ?? 'active') !== 'active',
                'status' => $statusKor,
                'application_status' => $app['application_status'],
                'existing_company' => $existingCompany
            ];
            
            error_log("getUserInternetApplications: Successfully formatted application_id " . ($app['application_id'] ?? 'unknown') . " - provider: " . $registrationPlace . ", plan_name: " . $planName);
            } catch (Exception $e) {
                error_log("getUserInternetApplications: Error processing application " . ($app['application_id'] ?? 'unknown') . ": " . $e->getMessage());
                // 에러가 발생해도 계속 진행
                continue;
            }
        }
        
        error_log("getUserInternetApplications: Returning " . count($formattedApplications) . " formatted applications for user_id: " . $userId);
        return $formattedApplications;
    } catch (PDOException $e) {
        error_log("Error fetching user Internet applications for user_id " . $userId . ": " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        return [];
    }
}

/**
 * 관리자용 - 모든 판매자의 알뜰폰 접수건 조회
 * @param array $filters 필터 조건 (seller_id, status, date_from, date_to 등)
 * @param int $page 페이지 번호
 * @param int $perPage 페이지당 항목 수
 * @return array ['applications' => [], 'total' => 0, 'totalPages' => 0]
 */
function getAllAdminMvnoApplications($filters = [], $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['applications' => [], 'total' => 0, 'totalPages' => 0];
    }
    
    try {
        // WHERE 조건 구성
        $whereConditions = ["a.product_type = 'mvno'"];
        $params = [];
        
        // 판매자 필터
        if (!empty($filters['seller_id'])) {
            $whereConditions[] = 'a.seller_id = :seller_id';
            $params[':seller_id'] = $filters['seller_id'];
        }
        
        // 상태 필터 (판매자 페이지와 동일하게)
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'received') {
                // 'received' 필터링 시 빈 문자열, null, 'pending'도 포함
                $whereConditions[] = "(a.application_status = :status OR a.application_status = '' OR a.application_status IS NULL OR LOWER(TRIM(a.application_status)) = 'pending')";
                $params[':status'] = 'received';
            } else {
                $whereConditions[] = 'a.application_status = :status';
                $params[':status'] = $filters['status'];
            }
        }
        
        // 날짜 필터
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(a.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(a.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        
        // 고객정보 검색 필터 (고객명, 전화번호, 이메일, 회원아이디)
        if (!empty($filters['customer_search'])) {
            $whereConditions[] = '(c.name LIKE :customer_search OR c.phone LIKE :customer_search OR c.email LIKE :customer_search OR CAST(c.user_id AS CHAR) LIKE :customer_search)';
            $params[':customer_search'] = '%' . $filters['customer_search'] . '%';
        }
        
        // 판매자정보 검색 필터는 나중에 PHP에서 처리 (users 테이블이 없을 수 있음)
        // seller_search는 쿼리 결과를 가져온 후 PHP에서 필터링
        
        // 주문번호 검색
        if (!empty($filters['order_number'])) {
            $whereConditions[] = 'a.order_number LIKE :order_number';
            $params[':order_number'] = '%' . $filters['order_number'] . '%';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        // COUNT 쿼리용 JOIN 구성 (DB-only: users는 DB 테이블이므로 필요 시 JOIN 가능)
        $countJoins = ["INNER JOIN application_customers c ON a.id = c.application_id"];
        
        // seller_search 필터는 나중에 PHP에서 처리 (users 테이블이 없을 수 있음)
        
        // 전체 개수 조회
        $countSql = "
            SELECT COUNT(*) as total
            FROM product_applications a
            " . implode("\n            ", $countJoins) . "
            WHERE {$whereClause}
        ";
        
        // 디버깅: 쿼리 정보 로깅
        error_log("MVNO COUNT 쿼리 디버깅:");
        error_log("WHERE 절: " . $whereClause);
        error_log("파라미터: " . json_encode($params));
        error_log("SQL: " . $countSql);
        
        try {
            $countStmt = $pdo->prepare($countSql);
            
            // 파라미터 바인딩
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            
            if (!$countStmt->execute()) {
                $errorInfo = $countStmt->errorInfo();
                error_log("MVNO COUNT 쿼리 실행 실패: " . json_encode($errorInfo));
                error_log("SQL: " . $countSql);
                error_log("Params: " . json_encode($params));
                throw new PDOException("COUNT 쿼리 실행 실패: " . ($errorInfo[2] ?? 'Unknown error'));
            }
            
            $countResult = $countStmt->fetch(PDO::FETCH_ASSOC);
            $total = $countResult ? (int)$countResult['total'] : 0;
            error_log("MVNO COUNT 쿼리 결과: " . $total);
        } catch (PDOException $e) {
            error_log("MVNO COUNT 쿼리 예외: " . $e->getMessage());
            error_log("SQL: " . $countSql);
            error_log("Params: " . json_encode($params));
            error_log("WHERE 절: " . $whereClause);
            
            // 오류 발생 시 간단한 COUNT 쿼리로 대체 시도
            try {
                $fallbackSql = "
                    SELECT COUNT(*) as total
                    FROM product_applications a
                    INNER JOIN application_customers c ON a.id = c.application_id
                    WHERE a.product_type = 'mvno'
                ";
                $fallbackStmt = $pdo->query($fallbackSql);
                $fallbackResult = $fallbackStmt->fetch(PDO::FETCH_ASSOC);
                $total = $fallbackResult ? (int)$fallbackResult['total'] : 0;
                error_log("MVNO COUNT 대체 쿼리 결과: " . $total);
            } catch (Exception $fallbackE) {
                error_log("MVNO COUNT 대체 쿼리도 실패: " . $fallbackE->getMessage());
                $total = 0;
            }
        }
        
        $totalPages = ceil($total / $perPage);
        
        // 디버깅: COUNT 결과 확인
        if ($total == 0) {
            error_log("MVNO 접수건 COUNT 쿼리 결과: " . $total);
            error_log("WHERE 절: " . $whereClause);
            error_log("파라미터: " . json_encode($params));
            error_log("SQL: " . $countSql);
            
            // 간단한 COUNT 쿼리로 비교
            try {
                $simpleCountStmt = $pdo->query("
                    SELECT COUNT(*) as cnt 
                    FROM product_applications a
                    INNER JOIN application_customers c ON a.id = c.application_id
                    WHERE a.product_type = 'mvno'
                ");
                $simpleCount = $simpleCountStmt->fetch(PDO::FETCH_ASSOC);
                error_log("간단한 COUNT 결과: " . ($simpleCount['cnt'] ?? 0));
            } catch (Exception $e) {
                error_log("간단한 COUNT 오류: " . $e->getMessage());
            }
        }
        
        // 접수건 목록 조회
        $offset = ($page - 1) * $perPage;
        $applications = [];
        
        try {
            $selectSql = "
                SELECT 
                    a.id as application_id,
                    a.product_id,
                    a.seller_id,
                    a.order_number,
                    a.application_status,
                    a.created_at as order_date,
                    a.updated_at,
                    c.id as customer_id,
                    c.user_id as customer_user_id,
                    c.name as customer_name,
                    c.phone as customer_phone,
                    c.email as customer_email,
                    c.address,
                    c.address_detail,
                    c.birth_date,
                    c.gender,
                    c.additional_info,
                    mvno.plan_name,
                    mvno.provider,
                    mvno.contract_type,
                    mvno.price_main,
                    mvno.price_after,
                    mvno.discount_period,
                    mvno.data_amount,
                    mvno.data_amount_value,
                    mvno.data_unit,
                    mvno.data_additional,
                    mvno.data_additional_value,
                    mvno.data_exhausted,
                    mvno.data_exhausted_value,
                    mvno.call_type,
                    mvno.call_amount,
                    mvno.sms_type,
                    mvno.sms_amount,
                    mvno.service_type,
                    mvno.promotions,
                    p.status as product_status
                FROM product_applications a
                INNER JOIN application_customers c ON a.id = c.application_id
                LEFT JOIN products p ON a.product_id = p.id
                LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id
                WHERE {$whereClause}
                ORDER BY a.created_at DESC
                LIMIT :limit OFFSET :offset
            ";
            
            $stmt = $pdo->prepare($selectSql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            if (!$stmt->execute()) {
                $errorInfo = $stmt->errorInfo();
                error_log("MVNO SELECT 쿼리 실행 실패: " . json_encode($errorInfo));
                error_log("SQL: " . $selectSql);
                error_log("WHERE 절: " . $whereClause);
                error_log("Params: " . json_encode($params));
                throw new PDOException("SELECT 쿼리 실행 실패: " . ($errorInfo[2] ?? 'Unknown error'));
            }
            
            $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("MVNO SELECT 쿼리 예외: " . $e->getMessage());
            error_log("WHERE 절: " . $whereClause);
            error_log("Params: " . json_encode($params));
            $applications = [];
        }
        
        // 디버깅: 쿼리 결과 로깅
        if (empty($applications) && $total > 0) {
            error_log("MVNO 접수건 조회 오류: 총 " . $total . "건인데 결과가 없음. WHERE 조건: " . $whereClause);
            error_log("파라미터: " . json_encode($params));
        }
        
        // 판매자 정보는 DB(users)에서 조회/조인하도록 정리 필요 (DB-only)
        require_once __DIR__ . '/plan-data.php';
        foreach ($applications as &$app) {
            $sellerId = $app['seller_id'] ?? null;
            if ($sellerId) {
                $seller = getSellerById($sellerId);
                if ($seller) {
                    $app['seller_user_id'] = $seller['user_id'] ?? $sellerId;
                    $app['seller_name'] = $seller['name'] ?? ($seller['company_name'] ?? '판매자 정보 없음');
                    $app['seller_company_name'] = $seller['company_name'] ?? '';
                } else {
                    $app['seller_user_id'] = $sellerId;
                    $app['seller_name'] = '판매자 정보 없음';
                    $app['seller_company_name'] = '';
                }
            }
        }
        unset($app);
        
        // seller_search 필터가 있으면 PHP에서 필터링
        if (!empty($filters['seller_search'])) {
            $searchTerm = strtolower($filters['seller_search']);
            $applications = array_filter($applications, function($app) use ($searchTerm) {
                $sellerId = strtolower($app['seller_user_id'] ?? '');
                $sellerName = strtolower($app['seller_name'] ?? '');
                $companyName = strtolower($app['seller_company_name'] ?? '');
                return strpos($sellerId, $searchTerm) !== false || 
                       strpos($sellerName, $searchTerm) !== false || 
                       strpos($companyName, $searchTerm) !== false;
            });
            $applications = array_values($applications);
            // 필터링 후 total 재계산
            $total = count($applications);
            $totalPages = ceil($total / $perPage);
        }
        
        return [
            'applications' => $applications,
            'total' => $total,
            'totalPages' => $totalPages
        ];
    } catch (PDOException $e) {
        error_log("Error fetching admin MVNO applications: " . $e->getMessage());
        error_log("SQL Error Info: " . json_encode($e->errorInfo ?? []));
        error_log("WHERE Clause: " . ($whereClause ?? 'N/A'));
        error_log("Params: " . json_encode($params ?? []));
        return ['applications' => [], 'total' => 0, 'totalPages' => 0];
    }
}

/**
 * 관리자용 - 모든 판매자의 통신사폰 접수건 조회
 */
function getAllAdminMnoApplications($filters = [], $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['applications' => [], 'total' => 0, 'totalPages' => 0];
    }
    
    try {
        $whereConditions = ["a.product_type = 'mno'"];
        $params = [];
        
        if (!empty($filters['seller_id'])) {
            $whereConditions[] = 'a.seller_id = :seller_id';
            $params[':seller_id'] = $filters['seller_id'];
        }
        // 상태 필터 (판매자 페이지와 동일하게)
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'received') {
                // 'received' 필터링 시 빈 문자열, null, 'pending'도 포함
                $whereConditions[] = "(a.application_status = :status OR a.application_status = '' OR a.application_status IS NULL OR LOWER(TRIM(a.application_status)) = 'pending')";
                $params[':status'] = 'received';
            } else {
                $whereConditions[] = 'a.application_status = :status';
                $params[':status'] = $filters['status'];
            }
        }
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(a.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(a.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        // 고객정보 검색 필터
        if (!empty($filters['customer_search'])) {
            $whereConditions[] = '(c.name LIKE :customer_search OR c.phone LIKE :customer_search OR c.email LIKE :customer_search OR CAST(c.user_id AS CHAR) LIKE :customer_search)';
            $params[':customer_search'] = '%' . $filters['customer_search'] . '%';
        }
        
        // 판매자정보 검색 필터
        if (!empty($filters['seller_search'])) {
            $whereConditions[] = '(CAST(u.user_id AS CHAR) LIKE :seller_search OR u.name LIKE :seller_search OR u.company_name LIKE :seller_search)';
            $params[':seller_search'] = '%' . $filters['seller_search'] . '%';
        }
        
        // 주문번호 검색
        if (!empty($filters['order_number'])) {
            $whereConditions[] = 'a.order_number LIKE :order_number';
            $params[':order_number'] = '%' . $filters['order_number'] . '%';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            LEFT JOIN users u ON a.seller_id = u.user_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        $totalPages = ceil($total / $perPage);
        
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT 
                a.id as application_id,
                a.product_id,
                a.seller_id,
                a.order_number,
                a.application_status,
                a.created_at as order_date,
                a.updated_at,
                c.id as customer_id,
                c.user_id as customer_user_id,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                c.address,
                c.address_detail,
                c.birth_date,
                c.gender,
                c.additional_info,
                mno.device_name,
                mno.device_model,
                mno.device_capacity,
                mno.device_colors,
                mno.delivery_method,
                mno.device_price,
                mno.contract_period,
                mno.contract_period_days,
                mno.discount_amount,
                mno.discount_type,
                mno.contract_type,
                mno.contract_provider,
                p.status as product_status,
                u.user_id as seller_user_id,
                u.name as seller_name,
                u.company_name as seller_company_name
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            LEFT JOIN products p ON a.product_id = p.id
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id
            LEFT JOIN users u ON a.seller_id = u.user_id
            WHERE {$whereClause}
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'applications' => $applications,
            'total' => $total,
            'totalPages' => $totalPages
        ];
    } catch (PDOException $e) {
        error_log("Error fetching admin MNO applications: " . $e->getMessage());
        return ['applications' => [], 'total' => 0, 'totalPages' => 0];
    }
}

/**
 * 관리자용 - 모든 판매자의 인터넷 접수건 조회
 */
function getAllAdminInternetApplications($filters = [], $page = 1, $perPage = 20) {
    $pdo = getDBConnection();
    if (!$pdo) {
        return ['applications' => [], 'total' => 0, 'totalPages' => 0];
    }
    
    try {
        $whereConditions = ["a.product_type = 'internet'"];
        $params = [];
        
        if (!empty($filters['seller_id'])) {
            $whereConditions[] = 'a.seller_id = :seller_id';
            $params[':seller_id'] = $filters['seller_id'];
        }
        // 상태 필터 (판매자 페이지와 동일하게)
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'received') {
                // 'received' 필터링 시 빈 문자열, null, 'pending'도 포함
                $whereConditions[] = "(a.application_status = :status OR a.application_status = '' OR a.application_status IS NULL OR LOWER(TRIM(a.application_status)) = 'pending')";
                $params[':status'] = 'received';
            } else {
                $whereConditions[] = 'a.application_status = :status';
                $params[':status'] = $filters['status'];
            }
        }
        if (!empty($filters['date_from'])) {
            $whereConditions[] = 'DATE(a.created_at) >= :date_from';
            $params[':date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $whereConditions[] = 'DATE(a.created_at) <= :date_to';
            $params[':date_to'] = $filters['date_to'];
        }
        // 고객정보 검색 필터
        if (!empty($filters['customer_search'])) {
            $whereConditions[] = '(c.name LIKE :customer_search OR c.phone LIKE :customer_search OR c.email LIKE :customer_search OR CAST(c.user_id AS CHAR) LIKE :customer_search)';
            $params[':customer_search'] = '%' . $filters['customer_search'] . '%';
        }
        
        // 판매자정보 검색 필터
        if (!empty($filters['seller_search'])) {
            $whereConditions[] = '(CAST(u.user_id AS CHAR) LIKE :seller_search OR u.name LIKE :seller_search OR u.company_name LIKE :seller_search)';
            $params[':seller_search'] = '%' . $filters['seller_search'] . '%';
        }
        
        // 주문번호 검색
        if (!empty($filters['order_number'])) {
            $whereConditions[] = 'a.order_number LIKE :order_number';
            $params[':order_number'] = '%' . $filters['order_number'] . '%';
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $countStmt = $pdo->prepare("
            SELECT COUNT(*) as total
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            LEFT JOIN products p ON a.product_id = p.id
            LEFT JOIN users u ON a.seller_id = u.user_id
            WHERE {$whereClause}
        ");
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        $totalPages = ceil($total / $perPage);
        
        $offset = ($page - 1) * $perPage;
        $stmt = $pdo->prepare("
            SELECT 
                a.id as application_id,
                a.product_id,
                a.seller_id,
                a.order_number,
                a.application_status,
                a.created_at as order_date,
                a.updated_at,
                c.id as customer_id,
                c.user_id as customer_user_id,
                c.name as customer_name,
                c.phone as customer_phone,
                c.email as customer_email,
                c.address,
                c.address_detail,
                c.birth_date,
                c.gender,
                c.additional_info,
                internet.service_name,
                internet.service_type,
                internet.speed,
                internet.price,
                internet.contract_period,
                internet.contract_period_days,
                internet.installation_fee,
                internet.promotions,
                internet.existing_line,
                p.status as product_status,
                u.user_id as seller_user_id,
                u.name as seller_name,
                u.company_name as seller_company_name
            FROM product_applications a
            INNER JOIN application_customers c ON a.id = c.application_id
            LEFT JOIN products p ON a.product_id = p.id
            LEFT JOIN product_internet_details internet ON p.id = internet.product_id
            LEFT JOIN users u ON a.seller_id = u.user_id
            WHERE {$whereClause}
            ORDER BY a.created_at DESC
            LIMIT :limit OFFSET :offset
        ");
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        return [
            'applications' => $applications,
            'total' => $total,
            'totalPages' => $totalPages
        ];
    } catch (PDOException $e) {
        error_log("Error fetching admin Internet applications: " . $e->getMessage());
        return ['applications' => [], 'total' => 0, 'totalPages' => 0];
    }
}

