<?php
/**
 * 상품 관련 데이터베이스 함수
 */

require_once __DIR__ . '/db-config.php';

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
        
        // 1. 기본 상품 정보 저장
        $stmt = $pdo->prepare("
            INSERT INTO products (seller_id, product_type, status)
            VALUES (:seller_id, 'mvno', 'active')
        ");
        $stmt->execute([
            ':seller_id' => $productData['seller_id']
        ]);
        $productId = $pdo->lastInsertId();
        
        // 2. MVNO 상세 정보 저장
        $stmt = $pdo->prepare("
            INSERT INTO product_mvno_details (
                product_id, provider, service_type, plan_name, contract_period,
                contract_period_days, discount_period, price_main, price_after,
                data_amount, data_amount_value, data_unit, data_additional, data_additional_value, data_exhausted, data_exhausted_value,
                call_type, call_amount, additional_call_type, additional_call,
                sms_type, sms_amount, mobile_hotspot, mobile_hotspot_value,
                regular_sim_available, regular_sim_price, nfc_sim_available, nfc_sim_price,
                esim_available, esim_price, over_data_price, over_voice_price,
                over_video_price, over_sms_price, over_lms_price, over_mms_price,
                promotion_title, promotions, benefits
            ) VALUES (
                :product_id, :provider, :service_type, :plan_name, :contract_period,
                :contract_period_days, :discount_period, :price_main, :price_after,
                :data_amount, :data_amount_value, :data_unit, :data_additional, :data_additional_value, :data_exhausted, :data_exhausted_value,
                :call_type, :call_amount, :additional_call_type, :additional_call,
                :sms_type, :sms_amount, :mobile_hotspot, :mobile_hotspot_value,
                :regular_sim_available, :regular_sim_price, :nfc_sim_available, :nfc_sim_price,
                :esim_available, :esim_price, :over_data_price, :over_voice_price,
                :over_video_price, :over_sms_price, :over_lms_price, :over_mms_price,
                :promotion_title, :promotions, :benefits
            )
        ");
        
        $stmt->execute([
            ':product_id' => $productId,
            ':provider' => $productData['provider'] ?? '',
            ':service_type' => $productData['service_type'] ?? null,
            ':plan_name' => $productData['plan_name'] ?? '',
            ':contract_period' => $productData['contract_period'] ?? null,
            ':contract_period_days' => $productData['contract_period_days'] ?? null,
            ':discount_period' => $productData['discount_period'] ?? null,
            ':price_main' => isset($productData['price_main']) ? floatval($productData['price_main']) : 0,
            ':price_after' => isset($productData['price_after']) && $productData['price_after'] !== '' ? floatval($productData['price_after']) : 0,
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
        ]);
        
        $pdo->commit();
        return $productId;
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error saving MVNO product: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        error_log("Product data: " . json_encode($productData));
        return false;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Unexpected error saving MVNO product: " . $e->getMessage());
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
    
    try {
        $pdo->beginTransaction();
        
        // 1. 기본 상품 정보 저장
        $stmt = $pdo->prepare("
            INSERT INTO products (seller_id, product_type, status)
            VALUES (:seller_id, 'mno', 'active')
        ");
        $stmt->execute([
            ':seller_id' => $productData['seller_id']
        ]);
        $productId = $pdo->lastInsertId();
        
        // 2. MNO 상세 정보 저장
        $stmt = $pdo->prepare("
            INSERT INTO product_mno_details (
                product_id, device_name, device_price, device_capacity, device_colors,
                common_provider, common_discount_new, common_discount_port, common_discount_change,
                contract_provider, contract_discount_new, contract_discount_port, contract_discount_change,
                service_type, contract_period, contract_period_value, price_main,
                data_amount, data_amount_value, data_unit, data_exhausted, data_exhausted_value,
                call_type, call_amount, additional_call_type, additional_call,
                sms_type, sms_amount, mobile_hotspot, mobile_hotspot_value,
                regular_sim_available, regular_sim_price, nfc_sim_available, nfc_sim_price,
                esim_available, esim_price, over_data_price, over_voice_price,
                over_video_price, over_sms_price, over_lms_price, over_mms_price,
                promotion_title, promotions, benefits, delivery_method, visit_region
            ) VALUES (
                :product_id, :device_name, :device_price, :device_capacity, :device_colors,
                :common_provider, :common_discount_new, :common_discount_port, :common_discount_change,
                :contract_provider, :contract_discount_new, :contract_discount_port, :contract_discount_change,
                :service_type, :contract_period, :contract_period_value, :price_main,
                :data_amount, :data_amount_value, :data_unit, :data_exhausted, :data_exhausted_value,
                :call_type, :call_amount, :additional_call_type, :additional_call,
                :sms_type, :sms_amount, :mobile_hotspot, :mobile_hotspot_value,
                :regular_sim_available, :regular_sim_price, :nfc_sim_available, :nfc_sim_price,
                :esim_available, :esim_price, :over_data_price, :over_voice_price,
                :over_video_price, :over_sms_price, :over_lms_price, :over_mms_price,
                :promotion_title, :promotions, :benefits, :delivery_method, :visit_region
            )
        ");
        
        $stmt->execute([
            ':product_id' => $productId,
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
        ]);
        
        $pdo->commit();
        return $productId;
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error saving MNO product: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        error_log("Product data: " . json_encode($productData));
        return false;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Unexpected error saving MNO product: " . $e->getMessage());
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
    
    try {
        $pdo->beginTransaction();
        
        // 1. 기본 상품 정보 저장
        $stmt = $pdo->prepare("
            INSERT INTO products (seller_id, product_type, status)
            VALUES (:seller_id, 'internet', 'active')
        ");
        $stmt->execute([
            ':seller_id' => $productData['seller_id']
        ]);
        $productId = $pdo->lastInsertId();
        
        // 2. Internet 상세 정보 저장
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
        
        $stmt->execute([
            ':product_id' => $productId,
            ':registration_place' => $productData['registration_place'] ?? '',
            ':speed_option' => $productData['speed_option'] ?? null,
            ':monthly_fee' => $productData['monthly_fee'] ?? 0,
            ':cash_payment_names' => !empty($productData['cash_payment_names']) ? json_encode($productData['cash_payment_names']) : null,
            ':cash_payment_prices' => !empty($productData['cash_payment_prices']) ? json_encode($productData['cash_payment_prices']) : null,
            ':gift_card_names' => !empty($productData['gift_card_names']) ? json_encode($productData['gift_card_names']) : null,
            ':gift_card_prices' => !empty($productData['gift_card_prices']) ? json_encode($productData['gift_card_prices']) : null,
            ':equipment_names' => !empty($productData['equipment_names']) ? json_encode($productData['equipment_names']) : null,
            ':equipment_prices' => !empty($productData['equipment_prices']) ? json_encode($productData['equipment_prices']) : null,
            ':installation_names' => !empty($productData['installation_names']) ? json_encode($productData['installation_names']) : null,
            ':installation_prices' => !empty($productData['installation_prices']) ? json_encode($productData['installation_prices']) : null,
        ]);
        
        $pdo->commit();
        return $productId;
    } catch (PDOException $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Error saving Internet product: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        error_log("Product data: " . json_encode($productData));
        return false;
    } catch (Exception $e) {
        if (isset($pdo)) {
            $pdo->rollBack();
        }
        error_log("Unexpected error saving Internet product: " . $e->getMessage());
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

