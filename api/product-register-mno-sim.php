<?php
/**
 * 통신사단독유심(MNO-SIM) 상품 등록 API
 * 통신사단독유심 전용 - 단위까지 저장
 */

require_once __DIR__ . '/../includes/data/auth-functions.php';
require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json');

// 로그인 체크
if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

$currentUser = getCurrentUser();
if (!$currentUser || $currentUser['role'] !== 'seller') {
    echo json_encode(['success' => false, 'message' => '판매자만 상품을 등록할 수 있습니다.']);
    exit;
}

// 판매자 승인 체크
if (!isSellerApproved()) {
    echo json_encode(['success' => false, 'message' => '판매자 승인이 필요합니다.']);
    exit;
}

// 통신사단독유심 권한 체크 (주석 처리 - DB 설계 후 활성화)
// if (!hasSellerPermission($currentUser['user_id'], 'mno-sim')) {
//     echo json_encode(['success' => false, 'message' => '통신사단독유심 등록 권한이 없습니다. 관리자에게 권한을 요청하세요.']);
//     exit;
// }

// 수정 모드 확인
$isEditMode = isset($_POST['product_id']) && intval($_POST['product_id']) > 0;
$productId = $isEditMode ? intval($_POST['product_id']) : 0;

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        throw new Exception('데이터베이스 연결에 실패했습니다.');
    }
    
    // 테이블 존재 여부 확인
    $checkTable = $pdo->query("SHOW TABLES LIKE 'product_mno_sim_details'");
    if (!$checkTable->fetch()) {
        throw new Exception('데이터베이스 테이블이 생성되지 않았습니다. product_mno_sim_details 테이블을 생성해주세요.');
    }
    
    // 트랜잭션 시작
    $pdo->beginTransaction();
    
    $sellerId = (string)$currentUser['user_id'];
    
    // 1. products 테이블에 기본 정보 저장/수정
    if ($isEditMode) {
        // 수정 모드: 기존 상품 확인
        $checkStmt = $pdo->prepare("
            SELECT id FROM products 
            WHERE id = :product_id AND seller_id = :seller_id AND product_type = 'mno-sim' AND status != 'deleted'
        ");
        $checkStmt->execute([
            ':product_id' => $productId,
            ':seller_id' => $sellerId
        ]);
        
        if (!$checkStmt->fetch()) {
            throw new Exception('수정할 상품을 찾을 수 없습니다.');
        }
        
        // products 테이블 업데이트
        $updateProductStmt = $pdo->prepare("
            UPDATE products 
            SET status = :status, updated_at = NOW()
            WHERE id = :product_id
        ");
        $updateProductStmt->execute([
            ':status' => $_POST['product_status'] ?? 'active',
            ':product_id' => $productId
        ]);
    } else {
        // 등록 모드: products 테이블에 새 레코드 추가
        $insertProductStmt = $pdo->prepare("
            INSERT INTO products (seller_id, product_type, status, created_at, updated_at)
            VALUES (:seller_id, 'mno-sim', :status, NOW(), NOW())
        ");
        $insertProductStmt->execute([
            ':seller_id' => $sellerId,
            ':status' => $_POST['product_status'] ?? 'active'
        ]);
        $productId = $pdo->lastInsertId();
    }
    
    // 2. 상세 정보 수집 (단위 포함)
    $registrationTypes = isset($_POST['registration_types']) && is_array($_POST['registration_types']) 
        ? $_POST['registration_types'] : [];
    
    // 할인기간요금 처리
    $priceAfterType = $_POST['price_after_type'] ?? 'none';
    $priceAfter = null;
    if ($priceAfterType === 'free') {
        $priceAfter = 0;
    } elseif ($priceAfterType === 'custom' && !empty($_POST['price_after'])) {
        $priceAfter = floatval(str_replace(',', '', $_POST['price_after']));
    }
    
    // 프로모션 및 혜택 JSON 변환
    $promotions = [];
    if (isset($_POST['promotions']) && is_array($_POST['promotions'])) {
        $promotions = array_filter(array_map('trim', $_POST['promotions']));
    }
    
    $benefits = [];
    if (isset($_POST['benefits']) && is_array($_POST['benefits'])) {
        $benefits = array_filter(array_map('trim', $_POST['benefits']));
    }
    
    // 상세 정보 데이터 준비
    $detailData = [
        'product_id' => $productId,
        'provider' => $_POST['provider'] ?? '',
        'service_type' => $_POST['service_type'] ?? '',
        'registration_types' => !empty($registrationTypes) ? json_encode($registrationTypes, JSON_UNESCAPED_UNICODE) : null,
        'plan_name' => $_POST['plan_name'] ?? '',
        
        // 할인방법 (약정기간)
        'contract_period' => $_POST['contract_period'] ?? null,
        'contract_period_discount_value' => !empty($_POST['contract_period_discount_value']) ? intval($_POST['contract_period_discount_value']) : null,
        'contract_period_discount_unit' => $_POST['contract_period_discount_unit'] ?? null,
        
        // 요금 정보
        'price_main' => !empty($_POST['price_main']) ? floatval(str_replace(',', '', $_POST['price_main'])) : 0,
        'price_main_unit' => $_POST['price_main_unit'] ?? '원',
        
        // 할인기간(프로모션기간)
        'discount_period' => $_POST['discount_period'] ?? null,
        'discount_period_value' => !empty($_POST['discount_period_value']) ? intval($_POST['discount_period_value']) : null,
        'discount_period_unit' => $_POST['discount_period_unit'] ?? null,
        
        // 할인기간요금(프로모션기간요금)
        'price_after_type' => $priceAfterType,
        'price_after' => $priceAfter,
        'price_after_unit' => $_POST['price_after_unit'] ?? '원',
        
        // 요금제 유지기간
        'plan_maintenance_period_type' => $_POST['plan_maintenance_period_type'] ?? null,
        'plan_maintenance_period_prefix' => $_POST['plan_maintenance_period_prefix'] ?? null,
        'plan_maintenance_period_value' => !empty($_POST['plan_maintenance_period_value']) ? intval($_POST['plan_maintenance_period_value']) : null,
        'plan_maintenance_period_unit' => $_POST['plan_maintenance_period_unit'] ?? null,
        
        // 유심기변 불가기간
        'sim_change_restriction_period_type' => $_POST['sim_change_restriction_period_type'] ?? null,
        'sim_change_restriction_period_prefix' => $_POST['sim_change_restriction_period_prefix'] ?? null,
        'sim_change_restriction_period_value' => !empty($_POST['sim_change_restriction_period_value']) ? intval($_POST['sim_change_restriction_period_value']) : null,
        'sim_change_restriction_period_unit' => $_POST['sim_change_restriction_period_unit'] ?? null,
        
        // 데이터 정보
        'data_amount' => $_POST['data_amount'] ?? null,
        'data_amount_value' => !empty($_POST['data_amount_value']) ? intval($_POST['data_amount_value']) : null,
        'data_unit' => $_POST['data_unit'] ?? null,
        'data_additional' => $_POST['data_additional'] ?? null,
        'data_additional_value' => !empty($_POST['data_additional_value']) ? trim($_POST['data_additional_value']) : null,
        'data_exhausted' => $_POST['data_exhausted'] ?? null,
        'data_exhausted_value' => !empty($_POST['data_exhausted_value']) ? trim($_POST['data_exhausted_value']) : null,
        
        // 통화/문자 정보
        'call_type' => $_POST['call_type'] ?? null,
        'call_amount' => !empty($_POST['call_amount']) ? intval($_POST['call_amount']) : null,
        'call_amount_unit' => $_POST['call_amount_unit'] ?? '분',
        'additional_call_type' => $_POST['additional_call_type'] ?? null,
        'additional_call' => !empty($_POST['additional_call']) ? intval($_POST['additional_call']) : null,
        'additional_call_unit' => $_POST['additional_call_unit'] ?? '분',
        'sms_type' => $_POST['sms_type'] ?? null,
        'sms_amount' => !empty($_POST['sms_amount']) ? intval($_POST['sms_amount']) : null,
        'sms_amount_unit' => $_POST['sms_amount_unit'] ?? '건',
        'mobile_hotspot' => $_POST['mobile_hotspot'] ?? null,
        'mobile_hotspot_value' => !empty($_POST['mobile_hotspot_value']) ? intval($_POST['mobile_hotspot_value']) : null,
        'mobile_hotspot_unit' => $_POST['mobile_hotspot_unit'] ?? null,
        
        // 유심 정보
        'regular_sim_available' => !empty($_POST['regular_sim_available']) ? $_POST['regular_sim_available'] : null,
        'regular_sim_price' => !empty($_POST['regular_sim_price']) ? floatval(str_replace(',', '', $_POST['regular_sim_price'])) : null,
        'regular_sim_price_unit' => $_POST['regular_sim_price_unit'] ?? '원',
        'nfc_sim_available' => !empty($_POST['nfc_sim_available']) ? $_POST['nfc_sim_available'] : null,
        'nfc_sim_price' => !empty($_POST['nfc_sim_price']) ? floatval(str_replace(',', '', $_POST['nfc_sim_price'])) : null,
        'nfc_sim_price_unit' => $_POST['nfc_sim_price_unit'] ?? '원',
        'esim_available' => !empty($_POST['esim_available']) ? $_POST['esim_available'] : null,
        'esim_price' => !empty($_POST['esim_price']) ? floatval(str_replace(',', '', $_POST['esim_price'])) : null,
        'esim_price_unit' => $_POST['esim_price_unit'] ?? '원',
        
        // 기본 제공 초과 시
        'over_data_price' => !empty($_POST['over_data_price']) ? floatval($_POST['over_data_price']) : null,
        'over_data_price_unit' => $_POST['over_data_price_unit'] ?? '원/MB',
        'over_voice_price' => !empty($_POST['over_voice_price']) ? floatval($_POST['over_voice_price']) : null,
        'over_voice_price_unit' => $_POST['over_voice_price_unit'] ?? '원/초',
        'over_video_price' => !empty($_POST['over_video_price']) ? floatval($_POST['over_video_price']) : null,
        'over_video_price_unit' => $_POST['over_video_price_unit'] ?? '원/초',
        'over_sms_price' => !empty($_POST['over_sms_price']) ? intval($_POST['over_sms_price']) : null,
        'over_sms_price_unit' => $_POST['over_sms_price_unit'] ?? '원/건',
        'over_lms_price' => !empty($_POST['over_lms_price']) ? intval($_POST['over_lms_price']) : null,
        'over_lms_price_unit' => $_POST['over_lms_price_unit'] ?? '원/건',
        'over_mms_price' => !empty($_POST['over_mms_price']) ? intval($_POST['over_mms_price']) : null,
        'over_mms_price_unit' => $_POST['over_mms_price_unit'] ?? '원/건',
        
        // 혜택
        'promotion_title' => !empty($_POST['promotion_title']) ? trim($_POST['promotion_title']) : null,
        'promotions' => !empty($promotions) ? json_encode($promotions, JSON_UNESCAPED_UNICODE) : null,
        'benefits' => !empty($benefits) ? json_encode($benefits, JSON_UNESCAPED_UNICODE) : null,
        
        // 기타
        'redirect_url' => !empty($_POST['redirect_url']) ? trim($_POST['redirect_url']) : null
    ];
    
    // 필수 필드 검증
    if (empty($detailData['provider'])) {
        throw new Exception('통신사를 선택해주세요.');
    }
    if (empty($detailData['service_type'])) {
        throw new Exception('데이터 속도를 선택해주세요.');
    }
    if (empty($registrationTypes)) {
        throw new Exception('가입 형태를 최소 하나 이상 선택해주세요.');
    }
    // plan_name 검증 및 정리
    $planName = trim($detailData['plan_name'] ?? '');
    
    if (empty($planName)) {
        throw new Exception('요금제명을 입력해주세요.');
    }
    
    // plan_name 검증 (완화된 버전)
    // 실제 상품명일 수 있으므로 엄격한 검증은 하지 않음
    // 단, 명백히 잘못된 경우만 체크
    
    // 너무 긴 경우만 체크 (50자 이상)
    if (mb_strlen($planName) > 50) {
        throw new Exception('요금제명은 50자 이하로 입력해주세요. (현재: ' . mb_strlen($planName) . '자)');
    }
    
    // 완전히 비어있거나 공백만 있는 경우
    if (trim($planName) === '') {
        throw new Exception('요금제명을 입력해주세요.');
    }
    
    // 정리된 plan_name 저장
    $detailData['plan_name'] = $planName;
    if (empty($detailData['price_main']) || $detailData['price_main'] <= 0) {
        throw new Exception('월 요금을 입력해주세요.');
    }
    
    // 3. product_mno_sim_details 테이블에 저장/수정
    if ($isEditMode) {
        // 수정 모드: UPDATE
        $updateDetailStmt = $pdo->prepare("
            UPDATE product_mno_sim_details SET
                provider = :provider,
                service_type = :service_type,
                registration_types = :registration_types,
                plan_name = :plan_name,
                contract_period = :contract_period,
                contract_period_discount_value = :contract_period_discount_value,
                contract_period_discount_unit = :contract_period_discount_unit,
                price_main = :price_main,
                price_main_unit = :price_main_unit,
                discount_period = :discount_period,
                discount_period_value = :discount_period_value,
                discount_period_unit = :discount_period_unit,
                price_after_type = :price_after_type,
                price_after = :price_after,
                price_after_unit = :price_after_unit,
                plan_maintenance_period_type = :plan_maintenance_period_type,
                plan_maintenance_period_prefix = :plan_maintenance_period_prefix,
                plan_maintenance_period_value = :plan_maintenance_period_value,
                plan_maintenance_period_unit = :plan_maintenance_period_unit,
                sim_change_restriction_period_type = :sim_change_restriction_period_type,
                sim_change_restriction_period_prefix = :sim_change_restriction_period_prefix,
                sim_change_restriction_period_value = :sim_change_restriction_period_value,
                sim_change_restriction_period_unit = :sim_change_restriction_period_unit,
                data_amount = :data_amount,
                data_amount_value = :data_amount_value,
                data_unit = :data_unit,
                data_additional = :data_additional,
                data_additional_value = :data_additional_value,
                data_exhausted = :data_exhausted,
                data_exhausted_value = :data_exhausted_value,
                call_type = :call_type,
                call_amount = :call_amount,
                call_amount_unit = :call_amount_unit,
                additional_call_type = :additional_call_type,
                additional_call = :additional_call,
                additional_call_unit = :additional_call_unit,
                sms_type = :sms_type,
                sms_amount = :sms_amount,
                sms_amount_unit = :sms_amount_unit,
                mobile_hotspot = :mobile_hotspot,
                mobile_hotspot_value = :mobile_hotspot_value,
                mobile_hotspot_unit = :mobile_hotspot_unit,
                regular_sim_available = :regular_sim_available,
                regular_sim_price = :regular_sim_price,
                regular_sim_price_unit = :regular_sim_price_unit,
                nfc_sim_available = :nfc_sim_available,
                nfc_sim_price = :nfc_sim_price,
                nfc_sim_price_unit = :nfc_sim_price_unit,
                esim_available = :esim_available,
                esim_price = :esim_price,
                esim_price_unit = :esim_price_unit,
                over_data_price = :over_data_price,
                over_data_price_unit = :over_data_price_unit,
                over_voice_price = :over_voice_price,
                over_voice_price_unit = :over_voice_price_unit,
                over_video_price = :over_video_price,
                over_video_price_unit = :over_video_price_unit,
                over_sms_price = :over_sms_price,
                over_sms_price_unit = :over_sms_price_unit,
                over_lms_price = :over_lms_price,
                over_lms_price_unit = :over_lms_price_unit,
                over_mms_price = :over_mms_price,
                over_mms_price_unit = :over_mms_price_unit,
                promotion_title = :promotion_title,
                promotions = :promotions,
                benefits = :benefits,
                redirect_url = :redirect_url,
                updated_at = NOW()
            WHERE product_id = :product_id
        ");
        
        $updateDetailStmt->execute(array_merge($detailData, ['product_id' => $productId]));
        
        if ($updateDetailStmt->rowCount() === 0) {
            // 상세 정보가 없으면 INSERT
            $detailData['product_id'] = $productId;
            $insertDetailStmt = $pdo->prepare("
                INSERT INTO product_mno_sim_details (
                    product_id, provider, service_type, registration_types, plan_name,
                    contract_period, contract_period_discount_value, contract_period_discount_unit,
                    price_main, price_main_unit,
                    discount_period, discount_period_value, discount_period_unit,
                    price_after_type, price_after, price_after_unit,
                    plan_maintenance_period_type, plan_maintenance_period_prefix, plan_maintenance_period_value, plan_maintenance_period_unit,
                    sim_change_restriction_period_type, sim_change_restriction_period_prefix, sim_change_restriction_period_value, sim_change_restriction_period_unit,
                    data_amount, data_amount_value, data_unit,
                    data_additional, data_additional_value,
                    data_exhausted, data_exhausted_value,
                    call_type, call_amount, call_amount_unit,
                    additional_call_type, additional_call, additional_call_unit,
                    sms_type, sms_amount, sms_amount_unit,
                    mobile_hotspot, mobile_hotspot_value, mobile_hotspot_unit,
                    regular_sim_available, regular_sim_price, regular_sim_price_unit,
                    nfc_sim_available, nfc_sim_price, nfc_sim_price_unit,
                    esim_available, esim_price, esim_price_unit,
                    over_data_price, over_data_price_unit,
                    over_voice_price, over_voice_price_unit,
                    over_video_price, over_video_price_unit,
                    over_sms_price, over_sms_price_unit,
                    over_lms_price, over_lms_price_unit,
                    over_mms_price, over_mms_price_unit,
                    promotion_title, promotions, benefits, redirect_url
                ) VALUES (
                    :product_id, :provider, :service_type, :registration_types, :plan_name,
                    :contract_period, :contract_period_discount_value, :contract_period_discount_unit,
                    :price_main, :price_main_unit,
                    :discount_period, :discount_period_value, :discount_period_unit,
                    :price_after_type, :price_after, :price_after_unit,
                    :plan_maintenance_period_type, :plan_maintenance_period_prefix, :plan_maintenance_period_value, :plan_maintenance_period_unit,
                    :sim_change_restriction_period_type, :sim_change_restriction_period_prefix, :sim_change_restriction_period_value, :sim_change_restriction_period_unit,
                    :data_amount, :data_amount_value, :data_unit,
                    :data_additional, :data_additional_value,
                    :data_exhausted, :data_exhausted_value,
                    :call_type, :call_amount, :call_amount_unit,
                    :additional_call_type, :additional_call, :additional_call_unit,
                    :sms_type, :sms_amount, :sms_amount_unit,
                    :mobile_hotspot, :mobile_hotspot_value, :mobile_hotspot_unit,
                    :regular_sim_available, :regular_sim_price, :regular_sim_price_unit,
                    :nfc_sim_available, :nfc_sim_price, :nfc_sim_price_unit,
                    :esim_available, :esim_price, :esim_price_unit,
                    :over_data_price, :over_data_price_unit,
                    :over_voice_price, :over_voice_price_unit,
                    :over_video_price, :over_video_price_unit,
                    :over_sms_price, :over_sms_price_unit,
                    :over_lms_price, :over_lms_price_unit,
                    :over_mms_price, :over_mms_price_unit,
                    :promotion_title, :promotions, :benefits, :redirect_url
                )
            ");
            $insertDetailStmt->execute($detailData);
        }
    } else {
        // 등록 모드: INSERT
        $insertDetailStmt = $pdo->prepare("
            INSERT INTO product_mno_sim_details (
                product_id, provider, service_type, registration_types, plan_name,
                contract_period, contract_period_discount_value, contract_period_discount_unit,
                price_main, price_main_unit,
                discount_period, discount_period_value, discount_period_unit,
                price_after_type, price_after, price_after_unit,
                plan_maintenance_period_type, plan_maintenance_period_prefix, plan_maintenance_period_value, plan_maintenance_period_unit,
                sim_change_restriction_period_type, sim_change_restriction_period_prefix, sim_change_restriction_period_value, sim_change_restriction_period_unit,
                data_amount, data_amount_value, data_unit,
                data_additional, data_additional_value,
                data_exhausted, data_exhausted_value,
                call_type, call_amount, call_amount_unit,
                additional_call_type, additional_call, additional_call_unit,
                sms_type, sms_amount, sms_amount_unit,
                mobile_hotspot, mobile_hotspot_value, mobile_hotspot_unit,
                regular_sim_available, regular_sim_price, regular_sim_price_unit,
                nfc_sim_available, nfc_sim_price, nfc_sim_price_unit,
                esim_available, esim_price, esim_price_unit,
                over_data_price, over_data_price_unit,
                over_voice_price, over_voice_price_unit,
                over_video_price, over_video_price_unit,
                over_sms_price, over_sms_price_unit,
                over_lms_price, over_lms_price_unit,
                over_mms_price, over_mms_price_unit,
                promotion_title, promotions, benefits, redirect_url
            ) VALUES (
                :product_id, :provider, :service_type, :registration_types, :plan_name,
                :contract_period, :contract_period_discount_value, :contract_period_discount_unit,
                :price_main, :price_main_unit,
                :discount_period, :discount_period_value, :discount_period_unit,
                :price_after_type, :price_after, :price_after_unit,
                :plan_maintenance_period_type, :plan_maintenance_period_prefix, :plan_maintenance_period_value, :plan_maintenance_period_unit,
                :sim_change_restriction_period_type, :sim_change_restriction_period_prefix, :sim_change_restriction_period_value, :sim_change_restriction_period_unit,
                :data_amount, :data_amount_value, :data_unit,
                :data_additional, :data_additional_value,
                :data_exhausted, :data_exhausted_value,
                :call_type, :call_amount, :call_amount_unit,
                :additional_call_type, :additional_call, :additional_call_unit,
                :sms_type, :sms_amount, :sms_amount_unit,
                :mobile_hotspot, :mobile_hotspot_value, :mobile_hotspot_unit,
                :regular_sim_available, :regular_sim_price, :regular_sim_price_unit,
                :nfc_sim_available, :nfc_sim_price, :nfc_sim_price_unit,
                :esim_available, :esim_price, :esim_price_unit,
                :over_data_price, :over_data_price_unit,
                :over_voice_price, :over_voice_price_unit,
                :over_video_price, :over_video_price_unit,
                :over_sms_price, :over_sms_price_unit,
                :over_lms_price, :over_lms_price_unit,
                :over_mms_price, :over_mms_price_unit,
                :promotion_title, :promotions, :benefits, :redirect_url
            )
        ");
        $insertDetailStmt->execute($detailData);
    }
    
    // 트랜잭션 커밋
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $isEditMode ? '상품이 수정되었습니다.' : '상품이 등록되었습니다.',
        'product_id' => $productId
    ]);
    
} catch (Exception $e) {
    if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    error_log("MNO-SIM Product registration error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}


