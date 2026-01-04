<?php
/**
 * 이벤트 상세 페이지
 * 경로: /MVNO/event/event-detail.php?id=evt_xxx
 */

$current_page = 'event';
$is_main_page = false;

require_once '../includes/data/auth-functions.php';
require_once '../includes/data/db-config.php';

// 이미지 경로 정규화 함수
function normalizeImagePathForDisplay($path) {
    if (empty($path)) {
        return '';
    }
    
    $imagePath = trim($path);
    
    // 이미 /MVNO/로 시작하면 그대로 사용
    if (strpos($imagePath, '/MVNO/') === 0) {
        return $imagePath;
    }
    // /uploads/events/ 또는 /uploads/events/로 시작하는 경우
    elseif (preg_match('#^/uploads/events/#', $imagePath)) {
        return '/MVNO' . $imagePath;
    }
    // /uploads/ 또는 /images/로 시작하면 /MVNO/ 추가
    elseif (strpos($imagePath, '/uploads/') === 0 || strpos($imagePath, '/images/') === 0) {
        return '/MVNO' . $imagePath;
    }
    // 파일명만 있는 경우 (확장자가 있고 슬래시가 없음)
    elseif (strpos($imagePath, '/') === false && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $imagePath)) {
        return '/MVNO/uploads/events/' . $imagePath;
    }
    // 상대 경로인데 파일명이 아닌 경우
    elseif (strpos($imagePath, '/') !== 0) {
        return '/MVNO/' . $imagePath;
    }
    
    return $imagePath;
}

$eventId = $_GET['id'] ?? '';
$event = null;
$detailImages = [];
$eventProducts = [];

// 이벤트 ID가 없으면 리다이렉트 (header() 호출 전에 처리)
if (empty($eventId)) {
    header('Location: /MVNO/event/event.php');
    exit;
}

$pdo = getDBConnection();
if ($pdo) {
    try {
        // 이벤트 기본 정보 조회
        $stmt = $pdo->prepare("
            SELECT * FROM events 
            WHERE id = :id
        ");
        $stmt->execute([':id' => $eventId]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // 이벤트가 없으면 리다이렉트
        if (!$event) {
            header('Location: /MVNO/event/event.php');
            exit;
        }
        
        // is_published가 0이면 기간과 상관없이 비공개
        $isPublished = false;
        if (isset($event['is_published']) && $event['is_published'] == 0) {
            $isPublished = false;
        } else {
            // is_published가 1이거나 null이면 기간을 기준으로 판단
            $now = time();
            $startAt = $event['start_at'] ? strtotime($event['start_at']) : null;
            $endAt = $event['end_at'] ? strtotime($event['end_at']) : null;
            
            if ($startAt && $endAt) {
                $isPublished = ($now >= $startAt && $now <= $endAt);
            } elseif ($startAt) {
                $isPublished = ($now >= $startAt);
            } elseif ($endAt) {
                $isPublished = ($now <= $endAt);
            } else {
                // 시작일과 종료일이 모두 없으면 비공개로 처리
                $isPublished = false;
            }
        }
        
        if (!$isPublished) {
            header('Location: /MVNO/event/event.php');
            exit;
        }
        
        // 상세 이미지 조회
        $stmt = $pdo->prepare("
            SELECT * FROM event_detail_images 
            WHERE event_id = :event_id 
            ORDER BY display_order ASC
        ");
        $stmt->execute([':event_id' => $eventId]);
        $detailImages = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 디버깅: event_products 테이블 확인
        $checkStmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM event_products WHERE event_id = :event_id");
        $checkStmt->execute([':event_id' => $eventId]);
        $eventProductsCount = $checkStmt->fetch(PDO::FETCH_ASSOC)['cnt'];
        error_log('Event detail - event_products count: ' . $eventProductsCount);
        
        // 연결된 상품 조회 (각 상품 타입별 필요한 모든 데이터 포함)
        $stmt = $pdo->prepare("
            SELECT 
                ep.*,
                p.id as product_id,
                p.product_type,
                p.seller_id,
                p.application_count,
                p.status,
                p.view_count,
                p.favorite_count,
                -- MVNO 상품 데이터
                mvno.plan_name,
                mvno.provider,
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
                mvno.additional_call_type,
                mvno.additional_call,
                mvno.service_type,
                mvno.price_main,
                mvno.price_after,
                mvno.discount_period,
                mvno.contract_period,
                mvno.contract_period_days,
                mvno.promotions,
                mvno.promotion_title,
                -- MNO 상품 데이터
                mno.device_name as mno_device_name,
                mno.device_price as mno_device_price,
                mno.device_capacity as mno_device_capacity,
                mno.device_colors as mno_device_colors,
                mno.common_provider as mno_common_provider,
                mno.common_discount_new as mno_common_discount_new,
                mno.common_discount_port as mno_common_discount_port,
                mno.common_discount_change as mno_common_discount_change,
                mno.contract_provider as mno_contract_provider,
                mno.contract_discount_new as mno_contract_discount_new,
                mno.contract_discount_port as mno_contract_discount_port,
                mno.contract_discount_change as mno_contract_discount_change,
                mno.price_main as mno_price_main,
                mno.contract_period_value as mno_contract_period_value,
                mno.promotion_title as mno_promotion_title,
                mno.promotions as mno_promotions,
                mno.delivery_method as mno_delivery_method,
                mno.visit_region as mno_visit_region,
                -- MNO-SIM 상품 데이터
                mno_sim.plan_name as mno_sim_plan_name,
                mno_sim.provider as mno_sim_provider,
                mno_sim.service_type as mno_sim_service_type,
                mno_sim.contract_period,
                mno_sim.contract_period_discount_value,
                mno_sim.contract_period_discount_unit,
                mno_sim.data_amount as mno_sim_data_amount,
                mno_sim.data_amount_value as mno_sim_data_amount_value,
                mno_sim.data_unit as mno_sim_data_unit,
                mno_sim.data_additional as mno_sim_data_additional,
                mno_sim.data_additional_value as mno_sim_data_additional_value,
                mno_sim.data_exhausted as mno_sim_data_exhausted,
                mno_sim.data_exhausted_value as mno_sim_data_exhausted_value,
                mno_sim.call_type as mno_sim_call_type,
                mno_sim.call_amount as mno_sim_call_amount,
                mno_sim.call_amount_unit as mno_sim_call_amount_unit,
                mno_sim.sms_type as mno_sim_sms_type,
                mno_sim.sms_amount as mno_sim_sms_amount,
                mno_sim.sms_amount_unit as mno_sim_sms_amount_unit,
                mno_sim.additional_call_type as mno_sim_additional_call_type,
                mno_sim.additional_call as mno_sim_additional_call,
                mno_sim.additional_call_unit as mno_sim_additional_call_unit,
                mno_sim.price_main as mno_sim_price_main,
                mno_sim.price_after as mno_sim_price_after,
                mno_sim.price_main_unit as mno_sim_price_main_unit,
                mno_sim.price_after_unit as mno_sim_price_after_unit,
                mno_sim.discount_period as mno_sim_discount_period,
                mno_sim.discount_period_value as mno_sim_discount_period_value,
                mno_sim.discount_period_unit as mno_sim_discount_period_unit,
                mno_sim.promotions as mno_sim_promotions,
                mno_sim.promotion_title as mno_sim_promotion_title,
                mno_sim.benefits as mno_sim_benefits,
                -- Internet 상품 데이터
                inet.registration_place,
                inet.speed_option,
                inet.service_type as internet_service_type,
                inet.monthly_fee as internet_monthly_fee,
                inet.cash_payment_names,
                inet.cash_payment_prices,
                inet.gift_card_names,
                inet.gift_card_prices,
                inet.equipment_names,
                inet.equipment_prices,
                inet.installation_names,
                inet.installation_prices,
                inet.promotion_title as internet_promotion_title,
                inet.promotions as internet_promotions
            FROM event_products ep
            INNER JOIN products p ON ep.product_id = p.id
            LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
            LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
            LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND (p.product_type = 'mno-sim' OR p.product_type = 'mno')
            LEFT JOIN product_internet_details inet ON p.id = inet.product_id AND p.product_type = 'internet'
            WHERE ep.event_id = :event_id AND p.status != 'deleted'
            ORDER BY ep.display_order ASC
        ");
        $stmt->execute([':event_id' => $eventId]);
        $eventProductsRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 디버깅: 쿼리 결과 로그
        error_log('Event detail - Event ID: ' . $eventId);
        error_log('Event detail - Products found: ' . count($eventProductsRaw));
        if (!empty($eventProductsRaw)) {
            foreach ($eventProductsRaw as $idx => $prod) {
                error_log('Event detail - Product ' . $idx . ': ID=' . ($prod['product_id'] ?? 'N/A') . ', Type=' . ($prod['product_type'] ?? 'N/A') . ', Status=' . ($prod['status'] ?? 'N/A'));
            }
        } else {
            // 상품이 없는 경우 원인 확인
            $debugStmt = $pdo->prepare("
                SELECT ep.*, p.id as product_id, p.product_type, p.status 
                FROM event_products ep
                INNER JOIN products p ON ep.product_id = p.id
                WHERE ep.event_id = :event_id
            ");
            $debugStmt->execute([':event_id' => $eventId]);
            $debugProducts = $debugStmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('Event detail - All products (including inactive): ' . count($debugProducts));
            foreach ($debugProducts as $idx => $prod) {
                error_log('Event detail - Debug Product ' . $idx . ': ID=' . ($prod['product_id'] ?? 'N/A') . ', Type=' . ($prod['product_type'] ?? 'N/A') . ', Status=' . ($prod['status'] ?? 'N/A'));
            }
        }
        
        // 상품 데이터를 각 타입별로 저장 (원본 데이터 유지)
        $eventProducts = [];
        foreach ($eventProductsRaw as $product) {
            $product['link_url'] = '';
            $productType = $product['product_type'] ?? '';
            error_log('Event detail - Processing product: ID=' . ($product['product_id'] ?? 'N/A') . ', Type=' . $productType);
            
            if ($productType === 'mvno') {
                $product['link_url'] = '/MVNO/mvno/mvno-plan-detail.php?id=' . $product['product_id'];
            } elseif ($productType === 'mno') {
                $product['link_url'] = '/MVNO/mno/mno-phone-detail.php?id=' . $product['product_id'];
            } elseif ($productType === 'mno-sim') {
                $product['link_url'] = '/MVNO/mno-sim/mno-sim-detail.php?id=' . $product['product_id'];
            } elseif ($productType === 'internet') {
                $product['link_url'] = '/MVNO/internets/internet-detail.php?id=' . $product['product_id'];
            } elseif ($productType === 'internet') {
                $product['link_url'] = '/MVNO/internets/internet-detail.php?id=' . $product['product_id'];
            }
            $eventProducts[] = $product;
        }
        
        error_log('Event detail - Final eventProducts count: ' . count($eventProducts));
        
    } catch (PDOException $e) {
        error_log('Event detail error: ' . $e->getMessage());
    }
}

// 이벤트가 없으면 리다이렉트 (header() 호출 전에 처리)
if (!$event) {
    header('Location: /MVNO/event/event.php');
    exit;
}

// 모든 리다이렉트 처리가 끝난 후에만 header.php 포함
include '../includes/header.php';
?>

<main class="main-content">
    <div class="event-detail-container">
        <!-- 목록으로 버튼 -->
        <div class="event-detail-actions-top">
            <a href="/MVNO/event/event.php" class="btn-back">목록</a>
            </div>
            
        <!-- 시작일 ~ 종료일 -->
            <?php if ($event['start_at'] || $event['end_at']): ?>
                <div class="event-detail-date">
                    <?php if ($event['start_at']): ?>
                    <span><?php echo date('Y-m-d', strtotime($event['start_at'])); ?></span>
                <?php endif; ?>
                <?php if ($event['start_at'] && $event['end_at']): ?>
                    <span> ~ </span>
                    <?php endif; ?>
                    <?php if ($event['end_at']): ?>
                    <span><?php echo date('Y-m-d', strtotime($event['end_at'])); ?></span>
        <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- 상세 이미지 -->
        <?php if (!empty($detailImages)): ?>
            <div class="event-detail-images">
                <div class="detail-images-grid">
                    <?php foreach ($detailImages as $image): 
                        // 이미지 경로 처리
                        $imagePath = normalizeImagePathForDisplay($image['image_path'] ?? '');
                    ?>
                        <div class="detail-image-item">
                            <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="상세 이미지">
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- 연결된 상품 -->
        <?php if (!empty($eventProducts)): ?>
            <div class="event-products-section">
                <div class="event-products-container">
                    <?php error_log('Event detail - Rendering ' . count($eventProducts) . ' products'); ?>
                    <?php 
                    require_once __DIR__ . '/../includes/data/plan-data.php';
                    require_once __DIR__ . '/../includes/data/phone-data.php';
                    
                    foreach ($eventProducts as $index => $product): 
                        $productType = $product['product_type'] ?? '';
                        error_log('Event detail - Rendering product: Index=' . $index . ', ID=' . ($product['product_id'] ?? 'N/A') . ', Type=' . $productType);
                    ?>
                        <?php if ($productType === 'mvno' || $productType === 'mno-sim'): ?>
                            <?php error_log('Event detail - Rendering as MVNO/MNO-SIM card'); ?>
                            <?php
                            // MVNO 또는 MNO-SIM: plan-card 사용
                            if ($productType === 'mvno') {
                                // convertMvnoProductToPlanCard가 기대하는 필드명으로 매핑
                                $mvnoProduct = $product;
                                $mvnoProduct['id'] = $product['product_id']; // id 필드 추가
                                $plan = convertMvnoProductToPlanCard($mvnoProduct);
                                $plan['link_url'] = $product['link_url'];
                            } else {
                                // MNO-SIM: convertMnoSimProductToPlanCard 함수 사용
                                $mnoSimProduct = [
                                    'id' => $product['product_id'],
                                    'seller_id' => $product['seller_id'] ?? 0,
                                    'status' => $product['status'] ?? 'active',
                                    'application_count' => $product['application_count'] ?? 0,
                                    'provider' => $product['mno_sim_provider'] ?? '',
                                    'service_type' => $product['mno_sim_service_type'] ?? '',
                                    'contract_period' => $product['contract_period'] ?? '',
                                    'contract_period_discount_value' => $product['contract_period_discount_value'] ?? '',
                                    'contract_period_discount_unit' => $product['contract_period_discount_unit'] ?? '',
                                    'data_amount' => $product['mno_sim_data_amount'] ?? '',
                                    'data_amount_value' => $product['mno_sim_data_amount_value'] ?? '',
                                    'data_unit' => $product['mno_sim_data_unit'] ?? 'GB',
                                    'data_additional' => $product['mno_sim_data_additional'] ?? '',
                                    'data_additional_value' => $product['mno_sim_data_additional_value'] ?? '',
                                    'data_exhausted' => $product['mno_sim_data_exhausted'] ?? '',
                                    'data_exhausted_value' => $product['mno_sim_data_exhausted_value'] ?? '',
                                    'call_type' => $product['mno_sim_call_type'] ?? '',
                                    'call_amount' => $product['mno_sim_call_amount'] ?? '',
                                    'call_amount_unit' => $product['mno_sim_call_amount_unit'] ?? '분',
                                    'sms_type' => $product['mno_sim_sms_type'] ?? '',
                                    'sms_amount' => $product['mno_sim_sms_amount'] ?? '',
                                    'sms_amount_unit' => $product['mno_sim_sms_amount_unit'] ?? '건',
                                    'additional_call_type' => $product['mno_sim_additional_call_type'] ?? '',
                                    'additional_call' => $product['mno_sim_additional_call'] ?? '',
                                    'additional_call_unit' => $product['mno_sim_additional_call_unit'] ?? '분',
                                    'price_main' => $product['mno_sim_price_main'] ?? 0,
                                    'price_after' => $product['mno_sim_price_after'] ?? 0,
                                    'price_main_unit' => $product['mno_sim_price_main_unit'] ?? '원',
                                    'price_after_unit' => $product['mno_sim_price_after_unit'] ?? '원',
                                    'discount_period' => $product['mno_sim_discount_period'] ?? '',
                                    'discount_period_value' => $product['mno_sim_discount_period_value'] ?? '',
                                    'discount_period_unit' => $product['mno_sim_discount_period_unit'] ?? '',
                                    'plan_name' => $product['mno_sim_plan_name'] ?? '통신사단독유심',
                                    'promotions' => $product['mno_sim_promotions'] ?? '',
                                    'promotion_title' => $product['mno_sim_promotion_title'] ?? '',
                                    'benefits' => $product['mno_sim_benefits'] ?? ''
                                ];
                                $plan = convertMnoSimProductToPlanCard($mnoSimProduct);
                                $plan['link_url'] = $product['link_url'];
                            }
                            $layout_type = 'list';
                            $card_wrapper_class = 'event-product-card';
                            ?>
                            <div class="event-product-card-wrapper" data-product-id="<?php echo $product['product_id']; ?>">
                                <?php include __DIR__ . '/../includes/components/plan-card.php'; ?>
                            </div>
                            <hr class="plan-card-divider">
                            
                        <?php elseif ($productType === 'mno'): ?>
                            <?php error_log('Event detail - Rendering as MNO phone card'); ?>
                            <?php
                            // MNO: phone-card 사용
                            // phone-card가 기대하는 형식으로 변환 (getPhonesData 로직 참고)
                            
                            // 판매자 정보 가져오기
                            require_once __DIR__ . '/../includes/data/product-functions.php';
                            $sellerId = (string)($product['seller_id'] ?? '');
                            $seller = getSellerById($sellerId);
                            $companyName = getSellerDisplayName($seller);
                            
                            // 통신사 정보 추출
                            $provider = 'SKT';
                            $commonProviders = [];
                            $contractProviders = [];
                            
                            if (!empty($product['mno_common_provider'])) {
                                $commonProviders = is_string($product['mno_common_provider']) 
                                    ? json_decode($product['mno_common_provider'], true) 
                                    : $product['mno_common_provider'];
                                if (is_array($commonProviders) && !empty($commonProviders)) {
                                    $provider = $commonProviders[0];
                                }
                            }
                            if ($provider === 'SKT' && !empty($product['mno_contract_provider'])) {
                                $contractProviders = is_string($product['mno_contract_provider']) 
                                    ? json_decode($product['mno_contract_provider'], true) 
                                    : $product['mno_contract_provider'];
                                if (is_array($contractProviders) && !empty($contractProviders)) {
                                    $provider = $contractProviders[0];
                                }
                            }
                            
                            // 공통지원할인 데이터 변환
                            $commonSupport = [];
                            if (!empty($commonProviders) && is_array($commonProviders)) {
                                $commonDiscountNew = [];
                                $commonDiscountPort = [];
                                $commonDiscountChange = [];
                                
                                if (!empty($product['mno_common_discount_new'])) {
                                    $decoded = is_string($product['mno_common_discount_new']) 
                                        ? json_decode($product['mno_common_discount_new'], true) 
                                        : $product['mno_common_discount_new'];
                                    if (is_array($decoded)) $commonDiscountNew = $decoded;
                                }
                                if (!empty($product['mno_common_discount_port'])) {
                                    $decoded = is_string($product['mno_common_discount_port']) 
                                        ? json_decode($product['mno_common_discount_port'], true) 
                                        : $product['mno_common_discount_port'];
                                    if (is_array($decoded)) $commonDiscountPort = $decoded;
                                }
                                if (!empty($product['mno_common_discount_change'])) {
                                    $decoded = is_string($product['mno_common_discount_change']) 
                                        ? json_decode($product['mno_common_discount_change'], true) 
                                        : $product['mno_common_discount_change'];
                                    if (is_array($decoded)) $commonDiscountChange = $decoded;
                                }
                                
                                foreach ($commonProviders as $index => $prov) {
                                    $newVal = isset($commonDiscountNew[$index]) ? trim($commonDiscountNew[$index]) : '9999';
                                    $portVal = isset($commonDiscountPort[$index]) ? trim($commonDiscountPort[$index]) : '9999';
                                    $changeVal = isset($commonDiscountChange[$index]) ? trim($commonDiscountChange[$index]) : '9999';
                                    
                                    if ($newVal === '') $newVal = '9999';
                                    if ($portVal === '') $portVal = '9999';
                                    if ($changeVal === '') $changeVal = '9999';
                                    
                                    $commonSupport[] = [
                                        'provider' => $prov,
                                        'plan_name' => '',
                                        'new_subscription' => ($newVal === '9999' || $newVal === 9999) ? 9999 : (int)$newVal,
                                        'number_port' => ($portVal === '9999' || $portVal === 9999) ? 9999 : (float)$portVal,
                                        'device_change' => ($changeVal === '9999' || $changeVal === 9999) ? 9999 : (float)$changeVal
                                    ];
                                }
                            }
                            
                            // 선택약정할인 데이터 변환
                            $contractSupport = [];
                            $contractDiscountNew = [];
                            $contractDiscountPort = [];
                            $contractDiscountChange = [];
                            
                            if (!empty($product['mno_contract_discount_new'])) {
                                $decoded = is_string($product['mno_contract_discount_new']) 
                                    ? json_decode($product['mno_contract_discount_new'], true) 
                                    : $product['mno_contract_discount_new'];
                                if (is_array($decoded)) $contractDiscountNew = $decoded;
                            }
                            if (!empty($product['mno_contract_discount_port'])) {
                                $decoded = is_string($product['mno_contract_discount_port']) 
                                    ? json_decode($product['mno_contract_discount_port'], true) 
                                    : $product['mno_contract_discount_port'];
                                if (is_array($decoded)) $contractDiscountPort = $decoded;
                            }
                            if (!empty($product['mno_contract_discount_change'])) {
                                $decoded = is_string($product['mno_contract_discount_change']) 
                                    ? json_decode($product['mno_contract_discount_change'], true) 
                                    : $product['mno_contract_discount_change'];
                                if (is_array($decoded)) $contractDiscountChange = $decoded;
                            }
                            
                            if (!empty($contractProviders) && is_array($contractProviders)) {
                                foreach ($contractProviders as $index => $prov) {
                                    $newVal = isset($contractDiscountNew[$index]) ? trim($contractDiscountNew[$index]) : '9999';
                                    $portVal = isset($contractDiscountPort[$index]) ? trim($contractDiscountPort[$index]) : '9999';
                                    $changeVal = isset($contractDiscountChange[$index]) ? trim($contractDiscountChange[$index]) : '9999';
                                    
                                    if ($newVal === '') $newVal = '9999';
                                    if ($portVal === '') $portVal = '9999';
                                    if ($changeVal === '') $changeVal = '9999';
                                    
                                    $contractSupport[] = [
                                        'provider' => $prov,
                                        'plan_name' => '',
                                        'new_subscription' => ($newVal === '9999' || $newVal === 9999) ? 9999 : (int)$newVal,
                                        'number_port' => ($portVal === '9999' || $portVal === 9999) ? 9999 : (float)$portVal,
                                        'device_change' => ($changeVal === '9999' || $changeVal === 9999) ? 9999 : (float)$changeVal
                                    ];
                                }
                            }
                            
                            // 부가서비스 변환
                            $additionalSupports = [];
                            $deliveryMethod = $product['mno_delivery_method'] ?? 'delivery';
                            $visitRegion = $product['mno_visit_region'] ?? '';
                            
                            if (!empty($product['mno_promotions'])) {
                                $promotions = is_string($product['mno_promotions']) 
                                    ? json_decode($product['mno_promotions'], true) 
                                    : $product['mno_promotions'];
                                if (is_array($promotions)) {
                                    foreach ($promotions as $promo) {
                                        if (!empty($promo)) {
                                            $additionalSupports[] = $promo;
                                        }
                                    }
                                }
                            }
                            
                            $promotionTitle = $product['mno_promotion_title'] ?? '부가서비스 없음';
                            if ($deliveryMethod === 'visit' && !empty($visitRegion)) {
                                if (empty($additionalSupports)) {
                                    $additionalSupports[] = $visitRegion . ' | ' . $promotionTitle;
                                } else {
                                    $additionalSupports[0] = $visitRegion . ' | ' . $additionalSupports[0];
                                }
                            } else if (!empty($additionalSupports)) {
                                $additionalSupports[0] = '택배 | ' . $additionalSupports[0];
                            }
                            
                            // 가격 포맷팅
                            $monthlyPrice = '';
                            if (!empty($product['mno_price_main'])) {
                                $monthlyPrice = number_format($product['mno_price_main']) . '원';
                            }
                            
                            $releasePrice = '';
                            if (!empty($product['mno_device_price'])) {
                                $releasePrice = number_format($product['mno_device_price']);
                            }
                            
                            $maintenancePeriod = '';
                            if (!empty($product['mno_contract_period_value'])) {
                                $maintenancePeriod = $product['mno_contract_period_value'] . '일';
                            }
                            
                            $applicationCount = (int)($product['application_count'] ?? 0);
                            $selectionCount = number_format($applicationCount) . '명이 선택';
                            
                            // 평균 별점 가져오기
                            $productId = (int)$product['product_id'];
                            $averageRating = getProductAverageRating($productId, 'mno');
                            $displayRating = $averageRating > 0 ? number_format($averageRating, 1) : '';
                            
                            $phone = [
                                'id' => $product['product_id'],
                                'provider' => $provider,
                                'company_name' => $companyName,
                                'seller_name' => $seller['seller_name'] ?? null,
                                'rating' => $displayRating,
                                'device_name' => $product['mno_device_name'] ?? '',
                                'device_storage' => $product['mno_device_capacity'] ?? '',
                                'release_price' => $releasePrice,
                                'plan_name' => $provider . ' 요금제',
                                'monthly_price' => $monthlyPrice,
                                'maintenance_period' => $maintenancePeriod,
                                'selection_count' => $selectionCount,
                                'application_count' => $applicationCount,
                                'common_support' => $commonSupport,
                                'contract_support' => $contractSupport,
                                'additional_supports' => $additionalSupports,
                                'delivery_method' => $deliveryMethod,
                                'visit_region' => $visitRegion,
                                'promotion_title' => $promotionTitle,
                                'is_favorited' => false,
                                'link_url' => $product['link_url']
                            ];
                            $layout_type = 'list';
                            $card_wrapper_class = 'event-product-card';
                            ?>
                            <div class="event-product-card-wrapper" data-product-id="<?php echo $product['product_id']; ?>">
                                <?php include __DIR__ . '/../includes/components/phone-card.php'; ?>
                            </div>
                            <hr class="plan-card-divider">
                            
                        <?php elseif ($productType === 'internet'): ?>
                            <?php error_log('Event detail - Rendering as Internet card'); ?>
                            <?php
                            // Internet: internets.php의 카드 구조 사용
                            // 아이콘 경로 함수 (internets.php와 동일) - 중복 선언 방지
                            if (!function_exists('getInternetIconPath')) {
                                function getInternetIconPath($registrationPlace) {
                                    $iconMap = [
                                        'KT' => '/MVNO/assets/images/internets/kt.svg',
                                        'SKT' => '/MVNO/assets/images/internets/broadband.svg',
                                        'LG U+' => '/MVNO/assets/images/internets/lgu.svg',
                                        'KT skylife' => '/MVNO/assets/images/internets/ktskylife.svg',
                                        'LG헬로비전' => '/MVNO/assets/images/internets/hellovision.svg',
                                        'BTV' => '/MVNO/assets/images/internets/btv.svg',
                                        'DLIVE' => '/MVNO/assets/images/internets/dlive.svg',
                                    ];
                                    return $iconMap[$registrationPlace] ?? '';
                                }
                            }
                            
                            $internetProduct = $product;
                            // 월 요금 포맷팅 (internets.php와 동일한 로직)
                            $monthlyFeeRaw = $product['internet_monthly_fee'] ?? '';
                            $monthlyFee = '0원';
                            if (!empty($monthlyFeeRaw)) {
                                if (is_numeric($monthlyFeeRaw)) {
                                    $numericValue = (int)floatval($monthlyFeeRaw);
                                    $monthlyFee = number_format($numericValue, 0, '', ',') . '원';
                                } elseif (preg_match('/^(\d+)(.+)$/', $monthlyFeeRaw, $matches)) {
                                    $numericValue = (int)$matches[1];
                                    $monthlyFee = number_format($numericValue, 0, '', ',') . $matches[2];
                                } else {
                                    $numericValue = (int)floatval($monthlyFeeRaw);
                                    $monthlyFee = number_format($numericValue, 0, '', ',') . '원';
                                }
                            }
                            
                            // 혜택 정보 파싱 (JSON 문자열인 경우 디코딩)
                            $cashNames = $product['cash_payment_names'] ?? [];
                            if (is_string($cashNames)) {
                                $cashNames = json_decode($cashNames, true) ?: [];
                            }
                            if (!is_array($cashNames)) $cashNames = [];
                            
                            $cashPrices = $product['cash_payment_prices'] ?? [];
                            if (is_string($cashPrices)) {
                                $cashPrices = json_decode($cashPrices, true) ?: [];
                            }
                            if (!is_array($cashPrices)) $cashPrices = [];
                            
                            $giftNames = $product['gift_card_names'] ?? [];
                            if (is_string($giftNames)) {
                                $giftNames = json_decode($giftNames, true) ?: [];
                            }
                            if (!is_array($giftNames)) $giftNames = [];
                            
                            $giftPrices = $product['gift_card_prices'] ?? [];
                            if (is_string($giftPrices)) {
                                $giftPrices = json_decode($giftPrices, true) ?: [];
                            }
                            if (!is_array($giftPrices)) $giftPrices = [];
                            
                            $equipNames = $product['equipment_names'] ?? [];
                            if (is_string($equipNames)) {
                                $equipNames = json_decode($equipNames, true) ?: [];
                            }
                            if (!is_array($equipNames)) $equipNames = [];
                            
                            $equipPrices = $product['equipment_prices'] ?? [];
                            if (is_string($equipPrices)) {
                                $equipPrices = json_decode($equipPrices, true) ?: [];
                            }
                            if (!is_array($equipPrices)) $equipPrices = [];
                            
                            $installNames = $product['installation_names'] ?? [];
                            if (is_string($installNames)) {
                                $installNames = json_decode($installNames, true) ?: [];
                            }
                            if (!is_array($installNames)) $installNames = [];
                            
                            $installPrices = $product['installation_prices'] ?? [];
                            if (is_string($installPrices)) {
                                $installPrices = json_decode($installPrices, true) ?: [];
                            }
                            if (!is_array($installPrices)) $installPrices = [];
                            
                            // 속도 옵션 포맷팅
                            $speedOption = htmlspecialchars($product['speed_option'] ?? '');
                            
                            // 서비스 타입 표시
                            $serviceType = $product['internet_service_type'] ?? '인터넷';
                            $serviceTypeDisplay = $serviceType;
                            if ($serviceType === '인터넷+TV') {
                                $serviceTypeDisplay = '인터넷 + TV 결합';
                            } elseif ($serviceType === '인터넷+TV+핸드폰') {
                                $serviceTypeDisplay = '인터넷 + TV + 핸드폰 결합';
                            }
                            
                            // 회사 아이콘 경로 (getInternetIconPath 함수 사용)
                            $registrationPlace = $product['registration_place'] ?? '';
                            $iconPath = getInternetIconPath($registrationPlace);
                            
                            // 신청 개수 포맷팅
                            $applicationCount = number_format($product['application_count'] ?? 0);
                            
                            // 프로모션 정보 파싱 (인터넷 상품은 internet_promotion_title, internet_promotions 사용)
                            $promotionTitle = $product['internet_promotion_title'] ?? '';
                            $promotions = $product['internet_promotions'] ?? [];
                            if (is_string($promotions)) {
                                $promotions = json_decode($promotions, true) ?: [];
                            }
                            if (!is_array($promotions)) $promotions = [];
                            $promotionCount = count(array_filter($promotions, function($p) { return !empty(trim($p)); }));
                            ?>
                            <div class="event-product-card-wrapper" data-product-id="<?php echo $product['product_id']; ?>">
                                <a href="<?php echo htmlspecialchars($product['link_url']); ?>" class="internet-card-link">
                                    <div class="css-58gch7 e82z5mt0">
                                        <div class="css-1kjyj6z e82z5mt1">
                                            <?php if ($iconPath): ?>
                                                <img data-testid="internet-company-logo" src="<?php echo htmlspecialchars($iconPath); ?>" 
                                                     alt="<?php echo htmlspecialchars($registrationPlace); ?>" 
                                                     class="css-1pg8bi e82z5mt15"
                                                     style="<?php echo ($registrationPlace === 'KT') ? 'height: 24px;' : (($registrationPlace === 'DLIVE') ? 'height: 35px; object-fit: cover;' : 'max-height: 40px; object-fit: contain;'); ?>">
                                            <?php else: ?>
                                                <span><?php echo htmlspecialchars($registrationPlace); ?></span>
                                            <?php endif; ?>
                                            <span style="margin-left: 0.5em; margin-right: 0.5em; font-size: 1.0584rem; color: #9ca3af;">|</span>
                                            <span style="font-size: 1.0584rem; color: #6b7280; text-align: left; display: inline-block; white-space: nowrap;"><?php echo htmlspecialchars($serviceTypeDisplay); ?></span>
                                            <div class="css-huskxe e82z5mt13" style="margin-left: auto;">
                                                <div class="css-1fd5u73 e82z5mt14">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <rect x="2" y="3" width="20" height="14" rx="2" fill="#E9D5FF" stroke="#A855F7" stroke-width="1.5"/>
                                                        <rect x="4" y="5" width="16" height="10" rx="1" fill="white"/>
                                                        <rect x="2" y="17" width="20" height="4" rx="1" fill="#C084FC" stroke="#A855F7" stroke-width="1"/>
                                                        <g transform="translate(17, -2) scale(1.5)">
                                                            <path d="M0 0L-2 5H0L-1 10L2 5H0L0 0Z" fill="#6366F1" stroke="#4F46E5" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/>
                                                        </g>
                                                    </svg>
                                                    <?php echo $speedOption; ?>
                                                </div>
                                                <div class="css-1fd5u73 e82z5mt14">
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                        <defs>
                                                            <linearGradient id="userGradient<?php echo $product['product_id']; ?>" x1="0%" y1="0%" x2="100%" y2="100%">
                                                                <stop offset="0%" style="stop-color:#6366f1;stop-opacity:1" />
                                                                <stop offset="100%" style="stop-color:#8b5cf6;stop-opacity:1" />
                                                            </linearGradient>
                                                        </defs>
                                                        <circle cx="12" cy="8" r="4" fill="url(#userGradient<?php echo $product['product_id']; ?>)"/>
                                                        <path d="M6 21c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="url(#userGradient<?php echo $product['product_id']; ?>)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                    <?php echo $applicationCount; ?>개 신청
                                                </div>
                                            </div>
                                        </div>
                                        <div class="css-174t92n e82z5mt7">
                                            <?php
                                            // 현금 지급 혜택
                                            $cashCount = is_array($cashNames) ? count($cashNames) : 0;
                                            for ($i = 0; $i < $cashCount; $i++):
                                                if (isset($cashNames[$i]) && !empty($cashNames[$i])):
                                                    $priceRaw = (isset($cashPrices[$i]) && is_array($cashPrices)) ? $cashPrices[$i] : '';
                                                    if (!empty($priceRaw) && preg_match('/^(\d+)(.+)$/', $priceRaw, $matches)) {
                                                        $priceDisplay = number_format((int)$matches[1]) . $matches[2];
                                                        $hasPrice = true;
                                                    } elseif (!empty($priceRaw) && is_numeric($priceRaw)) {
                                                        $priceDisplay = number_format((int)$priceRaw) . '원';
                                                        $hasPrice = true;
                                                    } else {
                                                        $hasPrice = false;
                                                    }
                                            ?>
                                            <div class="css-12zfa6z e82z5mt8">
                                                <img src="/MVNO/assets/images/icons/cash.svg" alt="현금" class="css-xj5cz0 e82z5mt9">
                                                <div class="css-0 e82z5mt10">
                                                    <p class="css-2ht76o e82z5mt12 item-name-text"><?php echo htmlspecialchars($cashNames[$i]); ?></p>
                                                    <?php if ($hasPrice): ?>
                                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;"><?php echo htmlspecialchars($priceDisplay); ?></p>
                                                    <?php else: ?>
                                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;">무료</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php
                                                endif;
                                            endfor;
                                            
                                            // 상품권 지급 혜택
                                            $giftCount = is_array($giftNames) ? count($giftNames) : 0;
                                            for ($i = 0; $i < $giftCount; $i++):
                                                if (isset($giftNames[$i]) && !empty($giftNames[$i])):
                                                    $priceRaw = (isset($giftPrices[$i]) && is_array($giftPrices)) ? $giftPrices[$i] : '';
                                                    if (!empty($priceRaw) && preg_match('/^(\d+)(.+)$/', $priceRaw, $matches)) {
                                                        $priceDisplay = number_format((int)$matches[1]) . $matches[2];
                                                        $hasPrice = true;
                                                    } elseif (!empty($priceRaw) && is_numeric($priceRaw)) {
                                                        $priceDisplay = number_format((int)$priceRaw) . '원';
                                                        $hasPrice = true;
                                                    } else {
                                                        $hasPrice = false;
                                                    }
                                            ?>
                                            <div class="css-12zfa6z e82z5mt8">
                                                <img src="/MVNO/assets/images/icons/gift-card.svg" alt="상품권" class="css-xj5cz0 e82z5mt9">
                                                <div class="css-0 e82z5mt10">
                                                    <p class="css-2ht76o e82z5mt12 item-name-text"><?php echo htmlspecialchars($giftNames[$i]); ?></p>
                                                    <?php if ($hasPrice): ?>
                                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;"><?php echo htmlspecialchars($priceDisplay); ?></p>
                                                    <?php else: ?>
                                                        <p class="css-2ht76o e82z5mt12 item-price-text" style="margin-top: 1.28px;">무료</p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php
                                                endif;
                                            endfor;
                                            
                                            // 장비 제공 혜택
                                            $equipCount = is_array($equipNames) ? count($equipNames) : 0;
                                            for ($i = 0; $i < $equipCount; $i++):
                                                if (isset($equipNames[$i]) && !empty($equipNames[$i])):
                                                    $priceText = (isset($equipPrices[$i]) && is_array($equipPrices) && !empty($equipPrices[$i])) ? $equipPrices[$i] : '';
                                            ?>
                                            <div class="css-12zfa6z e82z5mt8">
                                                <img src="/MVNO/assets/images/icons/equipment.svg" alt="장비" class="css-xj5cz0 e82z5mt9">
                                                <div class="css-0 e82z5mt10">
                                                    <p class="css-2ht76o e82z5mt12 item-name-text"><?php echo htmlspecialchars($equipNames[$i]); ?></p>
                                                    <?php if (!empty($priceText)): ?>
                                                        <p class="css-2ht76o e82z5mt12" style="margin-top: 1.28px;"><?php echo htmlspecialchars($priceText); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php
                                                endif;
                                            endfor;
                                            
                                            // 설치 및 기타 서비스 혜택
                                            $installCount = is_array($installNames) ? count($installNames) : 0;
                                            for ($i = 0; $i < $installCount; $i++):
                                                if (isset($installNames[$i]) && !empty($installNames[$i])):
                                                    $priceText = (isset($installPrices[$i]) && is_array($installPrices) && !empty($installPrices[$i])) ? $installPrices[$i] : '';
                                            ?>
                                            <div class="css-12zfa6z e82z5mt8">
                                                <img src="/MVNO/assets/images/icons/installation.svg" alt="설치" class="css-xj5cz0 e82z5mt9">
                                                <div class="css-0 e82z5mt10">
                                                    <p class="css-2ht76o e82z5mt12 item-name-text"><?php echo htmlspecialchars($installNames[$i]); ?></p>
                                                    <?php if (!empty($priceText)): ?>
                                                        <p class="css-2ht76o e82z5mt12" style="margin-top: 1.28px;"><?php echo htmlspecialchars($priceText); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php
                                                endif;
                                            endfor;
                                            ?>
                                        </div>
                                        <div data-testid="full-price-information" class="css-rkh09p e82z5mt2">
                                            <p class="css-16qot29 e82z5mt6">월 <?php echo htmlspecialchars($monthlyFee); ?></p>
                                        </div>
                                        
                                        <!-- 프로모션 아코디언 (internets.php와 동일) -->
                                        <?php
                                        if ($promotionCount > 0 || !empty($promotionTitle)):
                                            // 아코디언 제목: 프로모션 제목이 있으면 사용, 없으면 기본 텍스트
                                            $accordionTitle = '';
                                            if (!empty($promotionTitle)) {
                                                $accordionTitle = $promotionTitle;
                                            } elseif ($promotionCount > 0) {
                                                $accordionTitle = '프로모션 최대 ' . $promotionCount . '개';
                                            }
                                            
                                            // 색상 배열 (무지개 순서: 빨강, 노랑, 초록, 파랑, 보라)
                                            $giftColors = ['#EF4444', '#EAB308', '#10B981', '#3B82F6', '#8B5CF6'];
                                            $giftTextColor = '#FFFFFF';
                                        ?>
                                        <div class="plan-accordion-box" style="margin-top: 12px; padding: 12px 0;" onclick="event.stopPropagation();">
                                            <div class="plan-accordion">
                                                <button type="button" class="plan-accordion-trigger" aria-expanded="false" style="padding: 12px 16px;" onclick="event.stopPropagation();">
                                                    <div class="plan-gifts-accordion-content">
                                                        <!-- 각 항목의 첫 글자를 원 안에 표시 -->
                                                        <?php if ($promotionCount > 0): ?>
                                                        <div class="plan-gifts-indicator-dots">
                                                            <?php 
                                                            $filteredPromotions = array_filter($promotions, function($p) { return !empty(trim($p)); });
                                                            $index = 0;
                                                            foreach ($filteredPromotions as $promotion): 
                                                                $firstChar = mb_substr(trim($promotion), 0, 1, 'UTF-8'); // 첫 글자 추출
                                                                // 색상 배열에서 순환하여 사용 (5개 이상일 경우 반복)
                                                                $colorIndex = $index % count($giftColors);
                                                                $bgColor = $giftColors[$colorIndex] ?? '#6366F1';
                                                                $index++;
                                                            ?>
                                                                <span class="plan-gift-indicator-dot" style="background-color: <?php echo htmlspecialchars($bgColor); ?>;">
                                                                    <span class="plan-gift-indicator-text" style="color: <?php echo htmlspecialchars($giftTextColor); ?>;"><?php echo htmlspecialchars($firstChar); ?></span>
                                                                </span>
                                                            <?php endforeach; ?>
                                                        </div>
                                                        <?php endif; ?>
                                                        <span class="plan-gifts-text-accordion"><?php echo htmlspecialchars($accordionTitle); ?></span>
                                                    </div>
                                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" class="plan-accordion-arrow">
                                                        <path d="M6 9L12 15L18 9" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                                    </svg>
                                                </button>
                                                <div class="plan-accordion-content" style="display: none;" onclick="event.stopPropagation();">
                                                    <div class="plan-gifts-detail-list">
                                                        <?php if ($promotionCount > 0): ?>
                                                            <?php foreach ($filteredPromotions as $promotion): ?>
                                                            <div class="plan-gift-detail-item">
                                                                <span class="plan-gift-detail-text"><?php echo htmlspecialchars(trim($promotion)); ?></span>
                                                            </div>
                                                            <?php endforeach; ?>
                                                        <?php else: ?>
                                                            <div class="plan-gift-detail-item">
                                                                <span class="plan-gift-detail-text">프로모션 정보 없음</span>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <hr class="plan-card-divider">
                        <?php else: ?>
                            <?php error_log('Event detail - Unknown product type: ' . $productType); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php else: ?>
            <?php error_log('Event detail - No products to render'); ?>
            <div class="event-products-section">
                <p style="text-align: center; color: #6b7280; padding: 2rem;">연결된 상품이 없습니다.</p>
            </div>
        <?php endif; ?>
        
    </div>
</main>

<style>
.event-detail-container {
    max-width: 40%;
    margin: 0 auto;
    padding: 2rem 1rem;
}

.event-detail-actions-top {
    margin-bottom: 1.5rem;
}

.event-detail-date {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 2rem;
    text-align: center;
}

.event-detail-images {
    margin-bottom: 3rem;
}

.detail-images-grid {
    display: grid;
    gap: 0;
}

.detail-image-item {
    width: 100%;
    border-radius: 0;
    overflow: hidden;
    background: #f3f4f6;
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

.detail-image-item img {
    width: 100%;
    height: auto;
    display: block;
}

.event-products-section {
    margin-bottom: 3rem;
}

.event-products-container {
    width: 100%;
    max-width: 100%;
}

.event-products-container .plans-list-container {
    width: 100%;
    max-width: 100%;
}

.internet-card-link {
    text-decoration: none;
    color: inherit;
    display: block;
}

/* 인터넷 카드 스타일 (internets.php와 동일) */
.css-58gch7.e82z5mt0 {
    border: 1px solid #e5e7eb;
    border-radius: 0.75rem;
    padding: 1.5rem;
    background-color: #ffffff;
    box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
    transition: box-shadow 0.3s ease, transform 0.3s ease, border-color 0.3s ease;
    cursor: pointer;
    width: 100%;
    box-sizing: border-box;
    display: flex;
    flex-direction: column;
}

.css-58gch7.e82z5mt0:hover {
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.15);
    transform: translateY(-2px);
    border-color: #d1d5db;
}

.css-1kjyj6z.e82z5mt1 {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1rem;
    width: 100%;
    flex-wrap: wrap;
}

.css-1kjyj6z.e82z5mt1 > *:first-child,
.css-1kjyj6z.e82z5mt1 > span {
    flex-shrink: 0;
}

.css-1pg8bi.e82z5mt15 {
    width: auto;
    height: auto;
    object-fit: contain;
}

.css-huskxe.e82z5mt13 {
    display: flex;
    gap: 0.75rem;
    flex-wrap: nowrap;
    flex-shrink: 0;
    align-items: center;
    justify-content: flex-end;
    margin-left: auto;
}

.css-1fd5u73.e82z5mt14 {
    display: flex;
    align-items: center;
    gap: 0.25rem;
    font-size: 1.2rem;
    color: #6b7280;
    white-space: nowrap;
    flex-shrink: 0;
}

.css-1fd5u73.e82z5mt14 img {
    width: 16px;
    height: 16px;
}

/* Benefits section */
.css-174t92n.e82z5mt7 {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
    margin: 1rem 0;
    padding: 1rem;
    background-color: #f9fafb;
    border-radius: 0.5rem;
    width: 100%;
    box-sizing: border-box;
}

.css-12zfa6z.e82z5mt8 {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    width: 100%;
}

.css-174t92n.e82z5mt7 .css-xj5cz0.e82z5mt9,
.css-xj5cz0.e82z5mt9 {
    width: auto !important;
    height: auto !important;
    max-width: 4rem !important;
    max-height: 4rem !important;
    flex-shrink: 0;
    object-fit: contain;
}

.css-0.e82z5mt10 {
    flex: 1;
}

.css-2ht76o.e82z5mt12 {
    font-size: 1.3125rem;
    font-weight: 500;
    color: #4b5563;
    margin: 0;
    line-height: 1.5;
}

.css-2ht76o.e82z5mt12.item-name-text {
    color: #6b7280 !important;
    font-weight: 400 !important;
    font-size: 1.0584rem !important;
}

.item-price-text {
    color: #4b5563;
    font-weight: 600;
}

/* Price section */
.css-rkh09p.e82z5mt2 {
    margin-top: auto;
    padding-top: 1rem;
    border-top: 1px solid #e5e7eb;
    width: 100%;
}

.css-16qot29.e82z5mt6 {
    font-size: 1.5rem;
    font-weight: 700;
    color: #4b5563;
    margin: 0;
    text-align: right;
}

@media (max-width: 767px) {
    .css-58gch7.e82z5mt0 {
        padding: 1rem;
        width: 100%;
    }
    
    .css-1kjyj6z.e82z5mt1 {
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
        justify-content: space-between;
    }
    
    .css-huskxe.e82z5mt13 {
        gap: 0.5rem;
        flex-shrink: 0;
        flex-wrap: nowrap;
        justify-content: flex-end;
        margin-left: auto;
        width: auto;
    }
    
    .css-1fd5u73.e82z5mt14 {
        white-space: nowrap;
        font-size: 1rem;
    }
    
    .css-1pg8bi.e82z5mt15 {
        width: auto;
        max-width: 80px;
        flex-shrink: 1;
    }
}

.event-product-card-wrapper {
    position: relative;
    margin-bottom: 10px;
}

.event-product-card-wrapper:last-child {
    margin-bottom: 0;
}

.event-product-card {
    height: 100%;
}

.btn-back {
    display: inline-block;
    padding: 0.5rem 1rem;
    background: #6366f1;
    color: white;
    text-decoration: none;
    border-radius: 0.5rem;
    font-weight: 500;
    font-size: 0.875rem;
    transition: background 0.2s;
}

.btn-back:hover {
    background: #4f46e5;
}

@media (max-width: 768px) {
    .event-detail-container {
        max-width: 100%;
        padding: 1rem 0;
        margin: 0;
        width: 100%;
    }
}
</style>


<?php include '../includes/footer.php'; ?>

<!-- 아코디언 스크립트 -->
<script src="/MVNO/assets/js/plan-accordion.js"></script>

