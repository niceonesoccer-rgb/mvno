<?php
// 현재 페이지 설정 (헤더에서 활성 링크 표시용)
$current_page = 'home';
// 메인 페이지 여부 (하단 메뉴 및 푸터 표시용)
$is_main_page = true;

// 경로 설정 파일 먼저 로드 (헤더에서 사용)
require_once __DIR__ . '/includes/data/path-config.php';

// 디버깅 모드 확인 (헤더 로드 전에)
$debug_mode = isset($_GET['debug']) && $_GET['debug'] == '1';
$debug_info = [];

// 디버깅을 위해 필요한 파일 먼저 로드
if ($debug_mode) {
    require_once __DIR__ . '/includes/data/db-config.php';
}

// 헤더 포함 (절대 경로 사용으로 웹서버 환경 호환성 확보)
$headerPath = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'header.php';
if (file_exists($headerPath)) {
    include $headerPath;
} else {
    error_log("index.php: Cannot find header.php. __DIR__: " . __DIR__);
    die("헤더 파일을 찾을 수 없습니다. 서버 관리자에게 문의하세요.");
}

// 메인 페이지 데이터 함수 포함 (절대 경로 사용)
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'home-functions.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'plan-data.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'phone-data.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . 'notice-functions.php';

// 이미지 경로 정규화 함수
function normalizeImagePathForDisplay($path) {
    if (empty($path)) {
        return '';
    }
    
    $imagePath = trim($path);
    
    // 이미 전체 URL이면 그대로 사용
    if (preg_match('/^https?:\/\//', $imagePath)) {
        return $imagePath;
    }
    
    // DB에 저장된 경로에서 하드코딩된 /MVNO/ 제거 후 getAssetPath 사용
    // 여러 번 반복해서 모든 /MVNO/ 제거
    while (strpos($imagePath, '/MVNO/') !== false) {
        $imagePath = str_replace('/MVNO/', '/', $imagePath);
    }
    // 경로 시작 부분의 /MVNO 제거
    if (strpos($imagePath, '/MVNO') === 0) {
        $imagePath = substr($imagePath, 5); // '/MVNO' 제거
    }
    // MVNO/로 시작하는 경우도 처리
    if (strpos($imagePath, 'MVNO/') === 0) {
        $imagePath = substr($imagePath, 5); // 'MVNO/' 제거
    }
    
    // 이미 /로 시작하는 절대 경로면 getAssetPath 사용
    if (strpos($imagePath, '/') === 0) {
        return getAssetPath($imagePath);
    }
    
    // 파일명만 있는 경우 (확장자가 있고 슬래시가 없음)
    if (strpos($imagePath, '/') === false && preg_match('/\.(webp|jpg|jpeg|png|gif)$/i', $imagePath)) {
        return getAssetPath('/uploads/notices/' . $imagePath);
    }
    
    // 상대 경로인 경우
    return getAssetPath('/' . $imagePath);
}

if ($debug_mode) {
    // 데이터베이스 연결 정보 확인
    $dbConfigLocalFile = __DIR__ . '/includes/data/db-config-local.php';
    $dbConfigFile = __DIR__ . '/includes/data/db-config.php';
    
    $debug_info['db_config_local_exists'] = file_exists($dbConfigLocalFile);
    $debug_info['db_config_exists'] = file_exists($dbConfigFile);
    
    // DB 설정 읽기
    if (file_exists($dbConfigLocalFile)) {
        $content = file_get_contents($dbConfigLocalFile);
        if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $matches)) {
            $debug_info['db_host'] = $matches[1];
        }
        if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $matches)) {
            $debug_info['db_name'] = $matches[1];
        }
        if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $matches)) {
            $debug_info['db_user'] = $matches[1];
        }
    } else {
        $content = file_get_contents($dbConfigFile);
        if (preg_match("/define\('DB_HOST',\s*'([^']+)'\)/", $content, $matches)) {
            $debug_info['db_host'] = $matches[1];
        }
        if (preg_match("/define\('DB_NAME',\s*'([^']+)'\)/", $content, $matches)) {
            $debug_info['db_name'] = $matches[1];
        }
        if (preg_match("/define\('DB_USER',\s*'([^']+)'\)/", $content, $matches)) {
            $debug_info['db_user'] = $matches[1];
        }
    }
    
    // 데이터베이스 연결 테스트
    $pdo = getDBConnection();
    if (!$pdo) {
        $debug_info['db_connection'] = 'FAILED';
        $debug_info['db_error'] = isset($GLOBALS['lastDbConnectionError']) ? $GLOBALS['lastDbConnectionError'] : 'Unknown error';
    } else {
        $debug_info['db_connection'] = 'SUCCESS';
        
        // 상품 개수 확인
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE status = 'active'");
            $debug_info['total_active_products'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE product_type = 'mno-sim' AND status = 'active'");
            $debug_info['mno_sim_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE product_type = 'mvno' AND status = 'active'");
            $debug_info['mvno_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE product_type = 'mno' AND status = 'active'");
            $debug_info['mno_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM products WHERE product_type = 'internet' AND status = 'active'");
            $debug_info['internet_count'] = $stmt->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
            
            // app_settings 확인
            $stmt = $pdo->query("SELECT namespace, json_value FROM app_settings WHERE namespace = 'home' LIMIT 1");
            $home_settings_row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($home_settings_row) {
                $home_settings_data = json_decode($home_settings_row['json_value'], true);
                $debug_info['home_settings_exists'] = true;
                $debug_info['home_settings_mno_sim'] = count($home_settings_data['mno_sim_plans'] ?? []);
                $debug_info['home_settings_mvno'] = count($home_settings_data['mvno_plans'] ?? []);
                $debug_info['home_settings_mno'] = count($home_settings_data['mno_phones'] ?? []);
                $debug_info['home_settings_internet'] = count($home_settings_data['internet_products'] ?? []);
                $debug_info['home_settings_large_banners'] = count($home_settings_data['site_large_banners'] ?? []);
                $debug_info['home_settings_small_banners'] = count($home_settings_data['site_small_banners'] ?? []);
            } else {
                $debug_info['home_settings_exists'] = false;
            }
        } catch (PDOException $e) {
            $debug_info['db_query_error'] = $e->getMessage();
        }
    }
}

// 메인 페이지 설정 가져오기
$home_settings = getHomeSettings();

if ($debug_mode) {
    $debug_info['home_settings_loaded'] = !empty($home_settings);
    $debug_info['home_settings_mno_sim_plans'] = count($home_settings['mno_sim_plans'] ?? []);
    $debug_info['home_settings_mvno_plans'] = count($home_settings['mvno_plans'] ?? []);
    $debug_info['home_settings_mno_phones'] = count($home_settings['mno_phones'] ?? []);
    $debug_info['home_settings_internet_products'] = count($home_settings['internet_products'] ?? []);
    
    // 자동 채우기 결과 확인
    $debug_info['mno_sim_plans_loaded'] = count($mno_sim_plans ?? []);
    $debug_info['mvno_plans_loaded'] = count($mvno_plans ?? []);
    $debug_info['mno_phones_loaded'] = count($mno_phones ?? []);
    $debug_info['internet_products_loaded'] = count($internet_products ?? []);
}

// 메인 배너 이벤트 가져오기 (3개)
$main_banner_events = [];
if (!empty($home_settings['main_banners']) && is_array($home_settings['main_banners'])) {
    foreach ($home_settings['main_banners'] as $event_id) {
        $event = getEventById($event_id);
        if ($event) {
            // 이미지 경로 정규화
            if (!empty($event['image'])) {
                $event['image'] = normalizeImagePathForDisplay($event['image']);
            }
            $main_banner_events[] = $event;
        }
    }
}

// 알뜰폰 요금제 가져오기 (판매종료 제외) - 원본 데이터 사용
$mvno_plans = [];
if (!empty($home_settings['mvno_plans']) && is_array($home_settings['mvno_plans'])) {
    require_once __DIR__ . '/includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $plan_ids = array_unique(array_map('intval', $home_settings['mvno_plans']));
            if (!empty($plan_ids)) {
                $placeholders = implode(',', array_fill(0, count($plan_ids), '?'));
                $stmt = $pdo->prepare("
                    SELECT 
                        p.id,
                        p.seller_id,
                        p.status,
                        p.view_count,
                        p.favorite_count,
                        p.review_count,
                        p.share_count,
                        p.application_count,
                        mvno.provider,
                        mvno.service_type,
                        mvno.plan_name,
                        mvno.contract_period,
                        mvno.contract_period_days,
                        mvno.discount_period,
                        mvno.price_main,
                        mvno.price_after,
                        mvno.data_amount,
                        mvno.data_amount_value,
                        mvno.data_unit,
                        mvno.data_additional,
                        mvno.data_additional_value,
                        mvno.data_exhausted,
                        mvno.data_exhausted_value,
                        mvno.call_type,
                        mvno.call_amount,
                        mvno.additional_call_type,
                        mvno.additional_call,
                        mvno.sms_type,
                        mvno.sms_amount,
                        mvno.promotion_title,
                        mvno.promotions
                    FROM products p
                    INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
                    WHERE p.product_type = 'mvno' 
                    AND p.id IN ($placeholders)
                    AND p.status = 'active'
                    ORDER BY FIELD(p.id, $placeholders)
                ");
                $stmt->execute(array_merge($plan_ids, $plan_ids));
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($products)) {
                    // 메인페이지 전용 카드 컴포넌트를 사용하므로 원본 데이터 그대로 저장
                    $mvno_plans = $products;
                }
            }
        } catch (PDOException $e) {
            error_log("Error fetching MVNO plans: " . $e->getMessage());
        }
    }
}
// 메인에 추가된 상품이 없거나 모두 판매종료인 경우 주문수가 높은 상품으로 자동 채우기
if (empty($mvno_plans)) {
    require_once __DIR__ . '/includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.view_count,
                    p.favorite_count,
                    p.review_count,
                    p.share_count,
                    p.application_count,
                    mvno.provider,
                    mvno.service_type,
                    mvno.plan_name,
                    mvno.contract_period,
                    mvno.contract_period_days,
                    mvno.discount_period,
                    mvno.price_main,
                    mvno.price_after,
                    mvno.data_amount,
                    mvno.data_amount_value,
                    mvno.data_unit,
                    mvno.data_additional,
                    mvno.data_additional_value,
                    mvno.data_exhausted,
                    mvno.data_exhausted_value,
                    mvno.call_type,
                    mvno.call_amount,
                    mvno.additional_call_type,
                    mvno.additional_call,
                    mvno.sms_type,
                    mvno.sms_amount,
                    mvno.promotion_title,
                    mvno.promotions
                FROM products p
                INNER JOIN product_mvno_details mvno ON p.id = mvno.product_id
                WHERE p.product_type = 'mvno' 
                AND p.status = 'active'
                ORDER BY p.application_count DESC, p.id DESC
                LIMIT 3
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($products)) {
                // 메인페이지 전용 카드 컴포넌트를 사용하므로 원본 데이터 그대로 저장
                $mvno_plans = $products;
            } else {
                error_log("Warning: Auto-fill mvno_plans returned empty. Check if product_mvno_details table has data.");
            }
        } catch (PDOException $e) {
            error_log("Error fetching top MVNO plans: " . $e->getMessage());
            if ($debug_mode) {
                $debug_info['mvno_auto_fill_error'] = $e->getMessage();
            }
        }
    } else {
        if ($debug_mode) {
            $debug_info['mvno_auto_fill_pdo_null'] = true;
        }
    }
}

// 사이트 전체 섹션 배너 가져오기
// 배너는 관리자가 명시적으로 설정한 것이므로 기간 체크를 건너뜀
$site_large_banners = [];
$valid_large_banner_ids = [];
if (!empty($home_settings['site_large_banners']) && is_array($home_settings['site_large_banners'])) {
    foreach ($home_settings['site_large_banners'] as $event_id) {
        $event = getEventById($event_id, true); // 기간 체크 건너뛰기
        if ($event) {
            // 이미지 경로 정규화
            if (!empty($event['image'])) {
                $event['image'] = normalizeImagePathForDisplay($event['image']);
            }
            $site_large_banners[] = $event;
            $valid_large_banner_ids[] = (string)$event_id;
        }
    }
}

$site_small_banners = [];
$valid_small_banner_ids = [];
if (!empty($home_settings['site_small_banners']) && is_array($home_settings['site_small_banners'])) {
    foreach ($home_settings['site_small_banners'] as $event_id) {
        $event = getEventById($event_id, true); // 기간 체크 건너뛰기
        if ($event) {
            // 이미지 경로 정규화
            if (!empty($event['image'])) {
                $event['image'] = normalizeImagePathForDisplay($event['image']);
            }
            $site_small_banners[] = $event;
            $valid_small_banner_ids[] = (string)$event_id;
        }
    }
}

// 유효하지 않은 이벤트가 있으면 배너 설정에서 자동 제거
$needs_save = false;

// 메인배너에서 유효하지 않은 ID 제거
if (!empty($home_settings['site_large_banners'])) {
    $original_count = count($home_settings['site_large_banners']);
    $home_settings['site_large_banners'] = array_values(array_filter(
        $home_settings['site_large_banners'],
        function($id) use ($valid_large_banner_ids) {
            return in_array((string)$id, $valid_large_banner_ids, true);
        }
    ));
    if (count($home_settings['site_large_banners']) !== $original_count) {
        $needs_save = true;
    }
}

// 서브배너에서 유효하지 않은 ID 제거
if (!empty($home_settings['site_small_banners'])) {
    $original_count = count($home_settings['site_small_banners']);
    $home_settings['site_small_banners'] = array_values(array_filter(
        $home_settings['site_small_banners'],
        function($id) use ($valid_small_banner_ids) {
            return in_array((string)$id, $valid_small_banner_ids, true);
        }
    ));
    if (count($home_settings['site_small_banners']) !== $original_count) {
        $needs_save = true;
    }
}

// 설정 저장
if ($needs_save) {
    saveHomeSettings($home_settings);
}

// 통신사폰 가져오기 (판매종료 제외)
$mno_phones = [];
if (!empty($home_settings['mno_phones']) && is_array($home_settings['mno_phones'])) {
    $mno_phones = getPhonesByIds($home_settings['mno_phones']);
}
// 메인에 추가된 상품이 없거나 모두 판매종료인 경우 주문수가 높은 상품으로 자동 채우기
if (empty($mno_phones)) {
    require_once __DIR__ . '/includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.application_count,
                    p.favorite_count,
                    p.view_count,
                    mno.device_name,
                    mno.device_price,
                    mno.device_capacity,
                    mno.common_provider,
                    mno.common_discount_new,
                    mno.common_discount_port,
                    mno.common_discount_change,
                    mno.contract_provider,
                    mno.contract_discount_new,
                    mno.contract_discount_port,
                    mno.contract_discount_change,
                    mno.price_main,
                    mno.contract_period_value,
                    mno.promotion_title,
                    mno.promotions,
                    mno.delivery_method,
                    mno.visit_region
                FROM products p
                INNER JOIN product_mno_details mno ON p.id = mno.product_id
                WHERE p.product_type = 'mno' 
                AND p.status = 'active'
                ORDER BY p.application_count DESC, p.id DESC
                LIMIT 3
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($products)) {
                // getPhonesByIds와 동일한 변환 로직 적용
                require_once __DIR__ . '/includes/data/phone-data.php';
                $productIds = array_column($products, 'id');
                // getPhonesByIds를 사용하여 변환된 형태로 가져오기
                $mno_phones = getPhonesByIds($productIds);
            }
        } catch (PDOException $e) {
            error_log("Error fetching top MNO phones: " . $e->getMessage());
        }
    }
}

// 알짜 통신사단독유심 가져오기 (판매종료 제외)
$mno_sim_plans = [];
if (!empty($home_settings['mno_sim_plans']) && is_array($home_settings['mno_sim_plans'])) {
    require_once __DIR__ . '/includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $plan_ids = array_unique(array_map('intval', $home_settings['mno_sim_plans']));
            if (!empty($plan_ids)) {
                $placeholders = implode(',', array_fill(0, count($plan_ids), '?'));
                $stmt = $pdo->prepare("
                    SELECT 
                        p.id,
                        p.seller_id,
                        p.status,
                        p.view_count,
                        p.favorite_count,
                        p.review_count,
                        p.share_count,
                        p.application_count,
                        mno_sim.provider,
                        mno_sim.service_type,
                        mno_sim.plan_name,
                        mno_sim.contract_period,
                        mno_sim.contract_period_discount_value,
                        mno_sim.contract_period_discount_unit,
                        mno_sim.discount_period,
                        mno_sim.discount_period_value,
                        mno_sim.discount_period_unit,
                        mno_sim.price_main,
                        mno_sim.price_main_unit,
                        mno_sim.price_after,
                        mno_sim.price_after_unit,
                        mno_sim.data_amount,
                        mno_sim.data_amount_value,
                        mno_sim.data_unit,
                        mno_sim.data_additional,
                        mno_sim.data_additional_value,
                        mno_sim.data_exhausted,
                        mno_sim.data_exhausted_value,
                        mno_sim.call_type,
                        mno_sim.call_amount,
                        mno_sim.call_amount_unit,
                        mno_sim.additional_call_type,
                        mno_sim.additional_call,
                        mno_sim.additional_call_unit,
                        mno_sim.sms_type,
                        mno_sim.sms_amount,
                        mno_sim.sms_amount_unit,
                        mno_sim.promotion_title,
                        mno_sim.promotions
                    FROM products p
                    INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                    WHERE p.product_type = 'mno-sim' 
                    AND p.id IN ($placeholders)
                    AND p.status = 'active'
                    ORDER BY FIELD(p.id, $placeholders)
                ");
                $stmt->execute(array_merge($plan_ids, $plan_ids));
                $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if (!empty($products)) {
                    // 메인페이지 전용 카드 컴포넌트를 사용하므로 원본 데이터 그대로 저장
                    $mno_sim_plans = $products;
                }
            }
        } catch (PDOException $e) {
            error_log("Error fetching MNO-SIM plans: " . $e->getMessage());
        }
    }
}
// 메인에 추가된 상품이 없거나 모두 판매종료인 경우 주문수가 높은 상품으로 자동 채우기
if (empty($mno_sim_plans)) {
    require_once __DIR__ . '/includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.view_count,
                    p.favorite_count,
                    p.review_count,
                    p.share_count,
                    p.application_count,
                    mno_sim.provider,
                    mno_sim.service_type,
                    mno_sim.plan_name,
                    mno_sim.contract_period,
                    mno_sim.contract_period_discount_value,
                    mno_sim.contract_period_discount_unit,
                    mno_sim.discount_period,
                    mno_sim.discount_period_value,
                    mno_sim.discount_period_unit,
                    mno_sim.price_main,
                    mno_sim.price_main_unit,
                    mno_sim.price_after,
                    mno_sim.price_after_unit,
                    mno_sim.data_amount,
                    mno_sim.data_amount_value,
                    mno_sim.data_unit,
                    mno_sim.data_additional,
                    mno_sim.data_additional_value,
                    mno_sim.data_exhausted,
                    mno_sim.data_exhausted_value,
                    mno_sim.call_type,
                    mno_sim.call_amount,
                    mno_sim.call_amount_unit,
                    mno_sim.additional_call_type,
                    mno_sim.additional_call,
                    mno_sim.additional_call_unit,
                    mno_sim.sms_type,
                    mno_sim.sms_amount,
                    mno_sim.sms_amount_unit,
                    mno_sim.promotion_title,
                    mno_sim.promotions
                FROM products p
                INNER JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id
                WHERE p.product_type = 'mno-sim' 
                AND p.status = 'active'
                ORDER BY p.application_count DESC, p.id DESC
                LIMIT 3
            ");
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($products)) {
                // 메인페이지 전용 카드 컴포넌트를 사용하므로 원본 데이터 그대로 저장
                $mno_sim_plans = $products;
            } else {
                error_log("Warning: Auto-fill mno_sim_plans returned empty. Check if product_mno_sim_details table has data.");
            }
        } catch (PDOException $e) {
            error_log("Error fetching top MNO-SIM plans: " . $e->getMessage());
            if ($debug_mode) {
                $debug_info['mno_sim_auto_fill_error'] = $e->getMessage();
            }
        }
    } else {
        if ($debug_mode) {
            $debug_info['mno_sim_auto_fill_pdo_null'] = true;
        }
    }
}

// 인터넷 상품 가져오기 (판매종료 제외)
$internet_products = [];
if (!empty($home_settings['internet_products']) && is_array($home_settings['internet_products'])) {
    require_once __DIR__ . '/includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $product_ids = array_unique(array_map('intval', $home_settings['internet_products']));
            if (!empty($product_ids)) {
                $placeholders = implode(',', array_fill(0, count($product_ids), '?'));
                $stmt = $pdo->prepare("
                    SELECT 
                        p.id,
                        p.seller_id,
                        p.status,
                        p.application_count,
                        inet.registration_place,
                        inet.service_type,
                        inet.speed_option,
                        inet.monthly_fee,
                        inet.cash_payment_names,
                        inet.cash_payment_prices,
                        inet.gift_card_names,
                        inet.gift_card_prices,
                        inet.equipment_names,
                        inet.equipment_prices,
                        inet.installation_names,
                        inet.installation_prices
                    FROM products p
                    INNER JOIN product_internet_details inet ON p.id = inet.product_id
                    WHERE p.product_type = 'internet' 
                    AND p.id IN ($placeholders)
                    AND p.status = 'active'
                    ORDER BY FIELD(p.id, $placeholders)
                ");
                $stmt->execute(array_merge($product_ids, $product_ids));
                $internet_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            error_log("Error fetching Internet products: " . $e->getMessage());
        }
    }
}
// 메인에 추가된 상품이 없거나 모두 판매종료인 경우 주문수가 높은 상품으로 자동 채우기
if (empty($internet_products)) {
    require_once __DIR__ . '/includes/data/db-config.php';
    $pdo = getDBConnection();
    if ($pdo) {
        try {
            $stmt = $pdo->prepare("
                SELECT 
                    p.id,
                    p.seller_id,
                    p.status,
                    p.application_count,
                    inet.registration_place,
                    inet.service_type,
                    inet.speed_option,
                    inet.monthly_fee,
                    inet.cash_payment_names,
                    inet.cash_payment_prices,
                    inet.gift_card_names,
                    inet.gift_card_prices,
                    inet.equipment_names,
                    inet.equipment_prices,
                    inet.installation_names,
                    inet.installation_prices
                FROM products p
                INNER JOIN product_internet_details inet ON p.id = inet.product_id
                WHERE p.product_type = 'internet' 
                AND p.status = 'active'
                ORDER BY p.application_count DESC, p.id DESC
                LIMIT 3
            ");
            $stmt->execute();
            $internet_products = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error fetching top Internet products: " . $e->getMessage());
        }
    }
}
?>

<?php if ($debug_mode): ?>
<div style="position: fixed; top: 0; left: 0; right: 0; background: #1f2937; color: #f9fafb; padding: 20px; z-index: 99999; max-height: 80vh; overflow-y: auto; font-family: monospace; font-size: 12px; border-bottom: 3px solid #ef4444;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="margin: 0; color: #fbbf24; font-size: 16px;">🔍 디버깅 정보 (index.php)</h2>
        <button onclick="this.parentElement.parentElement.style.display='none'" style="background: #ef4444; color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer;">닫기</button>
    </div>
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
        <div>
            <h3 style="color: #60a5fa; margin-top: 0; margin-bottom: 8px; font-size: 14px;">📁 파일 존재 여부</h3>
            <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                <div>db-config-local.php: <span style="color: <?= $debug_info['db_config_local_exists'] ? '#10b981' : '#ef4444' ?>"><?= $debug_info['db_config_local_exists'] ? '✅ 존재' : '❌ 없음' ?></span></div>
                <div>db-config.php: <span style="color: <?= $debug_info['db_config_exists'] ? '#10b981' : '#ef4444' ?>"><?= $debug_info['db_config_exists'] ? '✅ 존재' : '❌ 없음' ?></span></div>
            </div>
            
            <h3 style="color: #60a5fa; margin-top: 12px; margin-bottom: 8px; font-size: 14px;">🔌 데이터베이스 설정</h3>
            <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                <div>Host: <span style="color: #fbbf24;"><?= htmlspecialchars($debug_info['db_host'] ?? 'N/A') ?></span></div>
                <div>Database: <span style="color: #fbbf24;"><?= htmlspecialchars($debug_info['db_name'] ?? 'N/A') ?></span></div>
                <div>User: <span style="color: #fbbf24;"><?= htmlspecialchars($debug_info['db_user'] ?? 'N/A') ?></span></div>
            </div>
            
            <h3 style="color: #60a5fa; margin-top: 12px; margin-bottom: 8px; font-size: 14px;">🔗 데이터베이스 연결</h3>
            <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                <div>상태: <span style="color: <?= $debug_info['db_connection'] === 'SUCCESS' ? '#10b981' : '#ef4444' ?>"><?= $debug_info['db_connection'] ?? 'N/A' ?></span></div>
                <?php if (isset($debug_info['db_error'])): ?>
                    <div style="color: #ef4444; margin-top: 4px;">에러: <?= htmlspecialchars($debug_info['db_error']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        
        <div>
            <?php if ($debug_info['db_connection'] === 'SUCCESS'): ?>
                <h3 style="color: #60a5fa; margin-top: 0; margin-bottom: 8px; font-size: 14px;">📦 상품 데이터</h3>
                <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                    <div>전체 활성 상품: <span style="color: #10b981;"><?= $debug_info['total_active_products'] ?? 0 ?></span></div>
                    <div>통신사단독유심: <span style="color: #10b981;"><?= $debug_info['mno_sim_count'] ?? 0 ?></span></div>
                    <div>알뜰폰: <span style="color: #10b981;"><?= $debug_info['mvno_count'] ?? 0 ?></span></div>
                    <div>통신사폰: <span style="color: #10b981;"><?= $debug_info['mno_count'] ?? 0 ?></span></div>
                    <div>인터넷: <span style="color: #10b981;"><?= $debug_info['internet_count'] ?? 0 ?></span></div>
                </div>
                
                <h3 style="color: #60a5fa; margin-top: 12px; margin-bottom: 8px; font-size: 14px;">⚙️ 홈 설정 (app_settings)</h3>
                <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                    <div>설정 존재: <span style="color: <?= $debug_info['home_settings_exists'] ? '#10b981' : '#ef4444' ?>"><?= $debug_info['home_settings_exists'] ? '✅ 있음' : '❌ 없음' ?></span></div>
                    <?php if ($debug_info['home_settings_exists']): ?>
                        <div style="margin-top: 4px; font-size: 11px;">
                            <div>통신사단독유심 설정: <?= $debug_info['home_settings_mno_sim'] ?? 0 ?>개</div>
                            <div>알뜰폰 설정: <?= $debug_info['home_settings_mvno'] ?? 0 ?>개</div>
                            <div>통신사폰 설정: <?= $debug_info['home_settings_mno'] ?? 0 ?>개</div>
                            <div>인터넷 설정: <?= $debug_info['home_settings_internet'] ?? 0 ?>개</div>
                            <div>큰 배너: <?= $debug_info['home_settings_large_banners'] ?? 0 ?>개</div>
                            <div>작은 배너: <?= $debug_info['home_settings_small_banners'] ?? 0 ?>개</div>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <h3 style="color: #60a5fa; margin-top: 12px; margin-bottom: 8px; font-size: 14px;">📋 로드된 설정</h3>
            <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                <div>설정 로드: <span style="color: <?= $debug_info['home_settings_loaded'] ? '#10b981' : '#ef4444' ?>"><?= $debug_info['home_settings_loaded'] ? '✅ 성공' : '❌ 실패' ?></span></div>
                <div style="margin-top: 4px; font-size: 11px;">
                    <div>통신사단독유심 설정: <?= $debug_info['home_settings_mno_sim_plans'] ?? 0 ?>개</div>
                    <div>알뜰폰 설정: <?= $debug_info['home_settings_mvno_plans'] ?? 0 ?>개</div>
                    <div>통신사폰 설정: <?= $debug_info['home_settings_mno_phones'] ?? 0 ?>개</div>
                    <div>인터넷 설정: <?= $debug_info['home_settings_internet_products'] ?? 0 ?>개</div>
                </div>
            </div>
            
            <h3 style="color: #60a5fa; margin-top: 12px; margin-bottom: 8px; font-size: 14px;">🔄 자동 채우기 결과</h3>
            <div style="background: rgba(255,255,255,0.1); padding: 8px; border-radius: 4px; margin-bottom: 8px;">
                <div style="margin-top: 4px; font-size: 11px;">
                    <div>통신사단독유심 로드: <span style="color: <?= ($debug_info['mno_sim_plans_loaded'] ?? 0) > 0 ? '#10b981' : '#ef4444' ?>"><?= $debug_info['mno_sim_plans_loaded'] ?? 0 ?>개</span></div>
                    <div>알뜰폰 로드: <span style="color: <?= ($debug_info['mvno_plans_loaded'] ?? 0) > 0 ? '#10b981' : '#ef4444' ?>"><?= $debug_info['mvno_plans_loaded'] ?? 0 ?>개</span></div>
                    <div>통신사폰 로드: <span style="color: <?= ($debug_info['mno_phones_loaded'] ?? 0) > 0 ? '#10b981' : '#ef4444' ?>"><?= $debug_info['mno_phones_loaded'] ?? 0 ?>개</span></div>
                    <div>인터넷 로드: <span style="color: <?= ($debug_info['internet_products_loaded'] ?? 0) > 0 ? '#10b981' : '#ef4444' ?>"><?= $debug_info['internet_products_loaded'] ?? 0 ?>개</span></div>
                </div>
                <?php if (isset($debug_info['mno_sim_auto_fill_error'])): ?>
                    <div style="color: #ef4444; margin-top: 4px; font-size: 10px;">통신사단독유심 에러: <?= htmlspecialchars($debug_info['mno_sim_auto_fill_error']) ?></div>
                <?php endif; ?>
                <?php if (isset($debug_info['mvno_auto_fill_error'])): ?>
                    <div style="color: #ef4444; margin-top: 4px; font-size: 10px;">알뜰폰 에러: <?= htmlspecialchars($debug_info['mvno_auto_fill_error']) ?></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php if (isset($debug_info['db_query_error'])): ?>
        <div style="background: rgba(239,68,68,0.2); padding: 12px; border-radius: 4px; margin-top: 16px; border: 1px solid #ef4444;">
            <div style="color: #ef4444; font-weight: bold; margin-bottom: 4px;">❌ 쿼리 에러:</div>
            <div style="color: #fca5a5;"><?= htmlspecialchars($debug_info['db_query_error']) ?></div>
        </div>
    <?php endif; ?>
</div>
<div style="margin-top: 200px;"></div>
<?php endif; ?>

<main class="main-content">
    <!-- 첫 번째 섹션: 메인 배너 레이아웃 (왼쪽 큰 배너 1개 + 오른쪽 작은 배너 2개) -->
    <div class="content-layout">
        <section class="main-banner-layout-section" style="margin-bottom: 2rem;">
            <?php if (!empty($site_large_banners) || !empty($site_small_banners)): ?>
                <div class="main-banner-grid">
                    <!-- 왼쪽: 큰 배너 (롤링) -->
                    <div class="main-banner-left">
                        <?php if (!empty($site_large_banners)): ?>
                            <div class="main-banner-carousel" id="main-banner-carousel">
                                <?php foreach ($site_large_banners as $index => $banner): 
                                    $banner_image = $banner['image'] ?? '';
                                    $banner_title = $banner['title'] ?? '';
                                    $banner_id = $banner['id'] ?? '';
                                    
                                    // 배너 이미지 경로 정규화
                                    if (!empty($banner_image)) {
                                        $banner_image = normalizeImagePathForDisplay($banner_image);
                                    }
                                    
                                    // 이벤트 상세 페이지 링크 생성
                                    if (!empty($banner_id)) {
                                        $banner_link = getAssetPath('/event/event-detail.php?id=' . urlencode($banner_id));
                                    } else {
                                        $banner_link = $banner['link'] ?? '#';
                                        // 외부 링크가 아닌 경우 getAssetPath 적용
                                        if (!empty($banner_link) && $banner_link !== '#' && !preg_match('/^https?:\/\//', $banner_link)) {
                                            $banner_link = getAssetPath($banner_link);
                                        }
                                    }
                                    
                                    if (empty($banner_image)) continue;
                                ?>
                                    <div class="main-banner-slide <?php echo $index === 0 ? 'active' : ''; ?>">
                                        <a href="<?php echo htmlspecialchars($banner_link); ?>" class="main-banner-card large">
                                            <img src="<?php echo htmlspecialchars($banner_image); ?>" 
                                                 alt="<?php echo htmlspecialchars($banner_title); ?>" 
                                                 class="main-banner-image">
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                                
                                <?php if (count($site_large_banners) > 1): ?>
                                    <div class="main-banner-controls">
                                        <button class="main-banner-prev" onclick="changeMainBanner(-1)">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M14 20L10 12L14 4" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                        <button class="main-banner-next" onclick="changeMainBanner(1)">
                                            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M10 20L14 12L10 4" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="main-banner-indicators">
                                        <?php foreach ($site_large_banners as $index => $banner): ?>
                                            <span class="main-banner-dot <?php echo $index === 0 ? 'active' : ''; ?>" 
                                                  onclick="goToMainBanner(<?php echo $index; ?>)"></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="main-banner-placeholder large">
                                <div class="placeholder-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3;">
                                        <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- 오른쪽: 작은 배너 2개 (16:9, 세로 배열) -->
                    <div class="main-banner-right">
                        <?php if (isset($site_small_banners[0])): 
                            $banner = $site_small_banners[0];
                            $banner_image = $banner['image'] ?? '';
                            $banner_title = $banner['title'] ?? '';
                            $banner_id = $banner['id'] ?? '';
                            
                            // 배너 이미지 경로 정규화
                            if (!empty($banner_image)) {
                                $banner_image = normalizeImagePathForDisplay($banner_image);
                            }
                            
                            // 이벤트 상세 페이지 링크 생성
                            if (!empty($banner_id)) {
                                $banner_link = getAssetPath('/event/event-detail.php?id=' . urlencode($banner_id));
                            } else {
                                $banner_link = $banner['link'] ?? '#';
                                // 외부 링크가 아닌 경우 getAssetPath 적용
                                if (!empty($banner_link) && $banner_link !== '#' && !preg_match('/^https?:\/\//', $banner_link)) {
                                    $banner_link = getAssetPath($banner_link);
                                }
                            }
                        ?>
                            <a href="<?php echo htmlspecialchars($banner_link); ?>" class="main-banner-card small">
                                <img src="<?php echo htmlspecialchars($banner_image); ?>" 
                                     alt="<?php echo htmlspecialchars($banner_title); ?>" 
                                     class="main-banner-image">
                            </a>
                        <?php else: ?>
                            <div class="main-banner-placeholder small">
                                <div class="placeholder-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3;">
                                        <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (isset($site_small_banners[1])): 
                            $banner = $site_small_banners[1];
                            $banner_image = $banner['image'] ?? '';
                            $banner_title = $banner['title'] ?? '';
                            $banner_id = $banner['id'] ?? '';
                            
                            // 배너 이미지 경로 정규화
                            if (!empty($banner_image)) {
                                $banner_image = normalizeImagePathForDisplay($banner_image);
                            }
                            
                            // 이벤트 상세 페이지 링크 생성
                            if (!empty($banner_id)) {
                                $banner_link = getAssetPath('/event/event-detail.php?id=' . urlencode($banner_id));
                            } else {
                                $banner_link = $banner['link'] ?? '#';
                                // 외부 링크가 아닌 경우 getAssetPath 적용
                                if (!empty($banner_link) && $banner_link !== '#' && !preg_match('/^https?:\/\//', $banner_link)) {
                                    $banner_link = getAssetPath($banner_link);
                                }
                            }
                        ?>
                            <a href="<?php echo htmlspecialchars($banner_link); ?>" class="main-banner-card small">
                                <img src="<?php echo htmlspecialchars($banner_image); ?>" 
                                     alt="<?php echo htmlspecialchars($banner_title); ?>" 
                                     class="main-banner-image">
                            </a>
                        <?php else: ?>
                            <div class="main-banner-placeholder small">
                                <div class="placeholder-content">
                                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3;">
                                        <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                    </svg>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <!-- 데이터가 없을 때 플레이스홀더 -->
                <div class="main-banner-grid">
                    <div class="main-banner-left">
                        <div class="main-banner-placeholder large">
                            <div class="placeholder-content">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3; margin-bottom: 16px;">
                                    <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <p style="color: #9ca3af; font-size: 12px; margin: 0;">큰 배너</p>
                            </div>
                        </div>
                    </div>
                    <div class="main-banner-right">
                        <div class="main-banner-placeholder small">
                            <div class="placeholder-content">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3; margin-bottom: 16px;">
                                    <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <p style="color: #9ca3af; font-size: 12px; margin: 0;">작은 배너 1</p>
                            </div>
                        </div>
                        <div class="main-banner-placeholder small">
                            <div class="placeholder-content">
                                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" style="opacity: 0.3; margin-bottom: 16px;">
                                    <path d="M4 4H20C21.1 4 22 4.9 22 6V18C22 19.1 21.1 20 20 20H4C2.9 20 2 19.1 2 18V6C2 4.9 2.9 4 4 4Z" stroke="currentColor" stroke-width="2"/>
                                </svg>
                                <p style="color: #9ca3af; font-size: 12px; margin: 0;">작은 배너 2</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </section>
    </div>

    <!-- 두 번째 섹션: 알짜 통신사단독유심 -->
    <div class="home-section bg-section-1">
        <div class="content-layout">
            <section class="home-product-section">
                <div class="home-section-header">
                    <h2 class="home-section-title">알짜 통신사단독유심</h2>
                    <a href="<?php echo getAssetPath('/mno-sim/mno-sim.php'); ?>" class="home-section-more">더보기 &gt;</a>
                </div>
                
                <!-- 상품 목록 -->
                <?php if (!empty($mno_sim_plans)): ?>
                    <div class="home-product-grid home-product-grid-single-row mno-sim-home-grid">
                        <?php foreach (array_slice($mno_sim_plans, 0, 3) as $product): ?>
                            <?php include 'includes/components/mno-sim-home-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="home-empty-state">
                        <p style="color: #9ca3af; text-align: center; padding: 3rem 1rem;">
                            관리자 페이지에서 통신사단독유심을 선택해주세요
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- 세 번째 섹션: 알뜰폰 요금제 -->
    <div class="home-section bg-section-2">
        <div class="content-layout">
            <section class="home-product-section">
                <div class="home-section-header">
                    <h2 class="home-section-title">추천 알뜰폰</h2>
                    <a href="<?php echo getAssetPath('/mvno/mvno.php'); ?>" class="home-section-more">더보기 &gt;</a>
                </div>
                
                
                <!-- 상품 목록 -->
                <?php if (!empty($mvno_plans)): ?>
                    <div class="home-product-grid home-product-grid-single-row mvno-home-grid">
                        <?php foreach (array_slice($mvno_plans, 0, 3) as $product): ?>
                            <?php include 'includes/components/mvno-home-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="home-empty-state">
                        <p style="color: #9ca3af; text-align: center; padding: 3rem 1rem;">
                            관리자 페이지에서 알뜰폰 요금제를 선택해주세요
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- 네 번째 섹션: 통신사폰 -->
    <div class="home-section bg-section-3">
        <div class="content-layout">
            <section class="home-product-section">
                <div class="home-section-header">
                    <h2 class="home-section-title">인기 통신사폰</h2>
                    <a href="<?php echo getAssetPath('/mno/mno.php'); ?>" class="home-section-more">더보기 &gt;</a>
                </div>
                <?php if (!empty($mno_phones)): ?>
                    <div class="home-product-grid home-product-grid-single-row mno-home-grid">
                        <?php foreach (array_slice($mno_phones, 0, 3) as $phone): ?>
                            <?php include 'includes/components/mno-home-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="home-empty-state">
                        <p style="color: #9ca3af; text-align: center; padding: 3rem 1rem;">
                            관리자 페이지에서 통신사폰을 선택해주세요
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

    <!-- 다섯 번째 섹션: 인터넷 상품 -->
    <div class="home-section bg-section-4">
        <div class="content-layout">
            <section class="home-internet-section">
                <div class="home-section-header">
                    <div>
                        <h2 class="home-section-title">최대할인 인터넷</h2>
                        <p class="home-section-subtitle">현금성 상품받고, 최대혜택 누리기</p>
                    </div>
                    <a href="<?php echo getAssetPath('/internets/internets.php'); ?>" class="home-section-more">더보기 &gt;</a>
                </div>
                <?php if (!empty($internet_products)): ?>
                    <div class="home-product-grid home-product-grid-single-row internet-home-grid">
                        <?php foreach (array_slice($internet_products, 0, 3) as $product): ?>
                            <?php include 'includes/components/internet-home-card.php'; ?>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="home-empty-state">
                        <p style="color: #9ca3af; text-align: center; padding: 3rem 1rem;">
                            관리자 페이지에서 인터넷 상품을 설정해주세요
                        </p>
                    </div>
                <?php endif; ?>
            </section>
        </div>
    </div>

</main>

<style>
/* 메인 배너 레이아웃 */
.main-banner-layout-section {
    margin-top: 1.5rem;
}

.main-banner-grid {
    display: grid;
    grid-template-columns: 2fr 1fr;
    gap: 1.5rem;
    width: 100%;
    align-items: stretch;
}

.main-banner-left {
    display: flex;
    flex-direction: column;
}

.main-banner-right {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
}

.main-banner-card {
    display: block;
    width: 100%;
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid #e5e7eb;
    background: #f9fafb;
    transition: transform 0.2s, box-shadow 0.2s;
    position: relative;
}

.main-banner-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.main-banner-card.large {
    aspect-ratio: 16 / 9;
    width: 100%;
}

/* 메인 배너 캐러셀 */
.main-banner-carousel {
    position: relative;
    width: 100%;
    aspect-ratio: 16 / 9;
    border-radius: 12px;
    overflow: hidden;
}

.main-banner-slide {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    opacity: 0;
    transition: opacity 0.5s ease-in-out;
}

.main-banner-slide.active {
    opacity: 1;
    z-index: 1;
}

.main-banner-controls {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 100%;
    display: flex;
    justify-content: space-between;
    padding: 0;
    z-index: 2;
    pointer-events: none;
}

.main-banner-prev {
    margin-left: 1rem;
}

.main-banner-next {
    margin-right: 1rem;
}

.main-banner-prev,
.main-banner-next {
    background: rgba(255, 255, 255, 0.5);
    color: #1a1a1a;
    border: none;
    width: 64px;
    height: 64px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    pointer-events: all;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
}

.main-banner-prev svg,
.main-banner-next svg {
    width: 28px;
    height: 28px;
    transition: transform 0.3s ease;
}

.main-banner-prev:hover,
.main-banner-next:hover {
    background: rgba(255, 255, 255, 0.7);
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.2);
}

.main-banner-prev:hover svg,
.main-banner-next:hover svg {
    transform: scale(1.15);
}

.main-banner-prev:active,
.main-banner-next:active {
    transform: scale(0.95);
}

.main-banner-indicators {
    position: absolute;
    bottom: 1rem;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 0.5rem;
    z-index: 2;
}

.main-banner-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.5);
    cursor: pointer;
    transition: background 0.2s;
}

.main-banner-dot.active {
    background: white;
}

.main-banner-card.small {
    aspect-ratio: 16 / 9;
    width: 100%;
    flex: 0 0 auto;
}

/* 작은 배너 2개가 큰 배너 높이에 맞추기 */
.main-banner-right {
    display: flex;
    flex-direction: column;
    gap: 1.5rem;
    height: 100%;
}

.main-banner-right .main-banner-card.small {
    aspect-ratio: 16 / 9;
    width: 100%;
}

.main-banner-right .main-banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.main-banner-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* 배너 플레이스홀더 */
.main-banner-placeholder {
    border-radius: 12px;
    border: 2px dashed #e5e7eb;
    background: #f9fafb;
    display: flex;
    align-items: center;
    justify-content: center;
}

.main-banner-placeholder.large {
    aspect-ratio: 16 / 9;
    width: 100%;
}

.main-banner-placeholder.small {
    aspect-ratio: 16 / 9;
    width: 100%;
    flex: 0 0 auto;
}

.main-banner-right .main-banner-placeholder.small {
    aspect-ratio: 16 / 9;
    width: 100%;
    height: auto;
}

.placeholder-content {
    text-align: center;
    color: #9ca3af;
    padding: 1rem;
}

/* 홈 섹션 공통 스타일 */
.home-section {
    padding: 3rem 0;
    margin-top: 2rem;
}

.bg-white {
    background-color: #ffffff;
}

.bg-gray-100 {
    background-color: #f9fafb;
}

.bg-gray-200 {
    background-color: #f3f4f6;
}

/* 섹션별 배경색 */
.bg-section-1 {
    background-color: #ffffff; /* 알짜 통신사단독유심 - 흰색 */
}

.bg-section-2 {
    background-color: #f8fafc; /* 추천 알뜰폰 - 연한 회색 */
}

.bg-section-3 {
    background-color: #f1f5f9; /* 인기 통신사폰 - 회색 */
}

.bg-section-4 {
    background-color: #eef2ff; /* 최대할인 인터넷 - 연한 보라색 */
}

.home-section-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 1.5rem;
    padding: 0 1rem;
    gap: 1rem;
}

/* 인터넷 섹션 헤더는 subtitle이 있으므로 제목과 subtitle을 감싸는 div 추가 */
.home-internet-section .home-section-header {
    align-items: flex-start;
}

.home-section-title {
    font-size: 1.5rem;
    font-weight: 700;
    color: #111827;
    margin: 0;
    white-space: nowrap;
    flex-shrink: 0;
}

.home-section-subtitle {
    font-size: 0.875rem;
    color: #6b7280;
    margin: 0.5rem 0 0 0;
    width: 100%;
}

.home-section-more {
    font-size: 0.875rem;
    color: #6366f1;
    text-decoration: none;
    font-weight: 500;
    transition: color 0.2s;
    white-space: nowrap;
    margin-left: 1rem;
}

.home-section-more:hover {
    color: #4f46e5;
}

/* 홈 제품 그리드 */
.home-product-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.5rem;
    padding: 0 1rem;
}

/* 한 줄로 3개만 표시 */
.home-product-grid-single-row {
    grid-template-columns: repeat(3, 1fr);
    max-width: 100%;
}

/* 빈 상태 스타일 */
.home-empty-state {
    padding: 3rem 1rem;
    text-align: center;
    color: #9ca3af;
}

/* 인터넷 섹션 */
.home-internet-section {
    width: 100%;
}

.home-internet-carousel {
    position: relative;
    padding: 0 1rem;
}

.home-internet-swiper {
    display: flex;
    gap: 1.5rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 1rem;
}

.home-internet-swiper::-webkit-scrollbar {
    display: none;
}

.home-internet-slide {
    flex: 0 0 320px;
    scroll-snap-align: start;
}

.home-section-footer {
    text-align: center;
    margin-top: 2rem;
    padding: 0 1rem;
}

.home-section-more-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.5rem;
    background: white;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    color: #374151;
    text-decoration: none;
    font-weight: 500;
    transition: all 0.2s;
}

.home-section-more-btn:hover {
    border-color: #6366f1;
    color: #6366f1;
}

.home-section-more-btn svg {
    width: 16px;
    height: 16px;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .home-section {
        padding: 2rem 0;
    }
    
    .home-section-header {
        flex-direction: column;
        gap: 0.5rem;
    }
    
    .home-section-more {
        margin-left: 0;
        align-self: flex-end;
    }
    
    .home-section-title {
        font-size: 1.25rem;
    }
    
    .home-product-grid:not(.home-product-grid-single-row) {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    .home-product-grid-single-row {
        grid-template-columns: repeat(3, 1fr);
        gap: 1rem;
        overflow-x: auto;
    }
    
    /* 통신사단독유심 카드는 모바일에서 하나씩 */
    .mno-sim-home-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        overflow-x: visible;
    }
    
    /* 알뜰폰 요금제 카드는 모바일에서 하나씩 */
    .mvno-home-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        overflow-x: visible;
    }
    
    /* 통신사폰 카드는 모바일에서 하나씩 */
    .mno-home-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        overflow-x: visible;
    }
    
    /* 인터넷 카드는 모바일에서 하나씩 */
    .internet-home-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        overflow-x: visible;
    }
    
    .home-internet-slide {
        flex: 0 0 280px;
    }
    
    .main-banner-grid {
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    
    .main-banner-card.large,
    .main-banner-card.small,
    .main-banner-placeholder.large,
    .main-banner-placeholder.small {
        aspect-ratio: 16 / 9;
    }
}

@media (min-width: 768px) and (max-width: 1023px) {
    .home-product-grid:not(.home-product-grid-single-row) {
        grid-template-columns: repeat(2, 1fr);
    }
    .home-product-grid-single-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1024px) {
    .home-product-grid:not(.home-product-grid-single-row) {
        grid-template-columns: repeat(3, 1fr);
    }
    .home-product-grid-single-row {
        grid-template-columns: repeat(3, 1fr);
    }
}

/* 메인페이지 전용 통신사단독유심 카드 스타일 */
.mno-sim-home-grid {
    /* 모바일에서 카드 하나씩 표시 */
}

.mno-sim-home-card {
    display: block;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    transition: all 0.2s;
    height: 100%;
}

.mno-sim-home-card:hover {
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.mno-sim-home-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.mno-sim-home-card-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 0.875rem;
}

.mno-sim-home-provider {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.125rem;
    letter-spacing: -0.01em;
}

.mno-sim-home-discount-method {
    font-size: 0.8125rem;
    color: #6366f1;
    font-weight: 500;
    margin-bottom: 0.25rem;
    background-color: #eef2ff;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    display: inline-block;
    width: fit-content;
}

.mno-sim-home-plan-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.5rem;
    line-height: 1.4;
    letter-spacing: -0.02em;
}

.mno-sim-home-data {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.9375rem;
    margin-bottom: 0.75rem;
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: 8px;
}

.mno-sim-home-data-label {
    font-weight: 700;
    color: #6366f1;
    font-size: 0.875rem;
}

.mno-sim-home-data-value {
    color: #1f2937;
    font-weight: 600;
}

.mno-sim-home-promotion-price-row {
    display: flex;
    align-items: baseline;
    gap: 0.875rem;
    margin-top: 0.5rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

.mno-sim-home-promotion-period {
    font-size: 0.8125rem;
    color: #6b7280;
    font-weight: 500;
    background-color: #f3f4f6;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
}

.mno-sim-home-price {
    font-size: 1.25rem;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .mno-sim-home-card-content {
        padding: 1.5rem;
        gap: 1rem;
    }
    
    .mno-sim-home-provider {
        font-size: 1rem;
        font-weight: 700;
    }
    
    .mno-sim-home-discount-method {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
    }
    
    .mno-sim-home-plan-name {
        font-size: 1.25rem;
        font-weight: 700;
    }
    
    .mno-sim-home-data {
        font-size: 1rem;
        gap: 0.75rem;
        padding: 1rem;
    }
    
    .mno-sim-home-data-label {
        font-size: 0.9375rem;
    }
    
    .mno-sim-home-data-value {
        font-size: 1rem;
    }
    
    .mno-sim-home-promotion-price-row {
        gap: 1rem;
        flex-wrap: nowrap;
        padding-top: 1rem;
        margin-top: 0.75rem;
    }
    
    .mno-sim-home-price {
        font-size: 1.5rem;
        font-weight: 800;
    }
    
    .mno-sim-home-promotion-period {
        font-size: 0.875rem;
        padding: 0.5rem 0.875rem;
    }
}

/* 메인페이지 전용 알뜰폰 요금제 카드 스타일 */
.mvno-home-grid {
    /* 모바일에서 카드 하나씩 표시 */
}

.mvno-home-card {
    display: block;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    transition: all 0.2s;
    height: 100%;
}

.mvno-home-card:hover {
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.mvno-home-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.mvno-home-card-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mvno-home-provider {
    font-size: 0.9375rem;
    font-weight: 700;
    color: #1f2937;
    margin-bottom: 0.125rem;
    letter-spacing: -0.01em;
}

.mvno-home-plan-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.25rem;
    line-height: 1.4;
    letter-spacing: -0.02em;
}

.mvno-home-data {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-size: 0.9375rem;
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: 8px;
}

.mvno-home-data-label {
    font-weight: 700;
    color: #6366f1;
    font-size: 0.875rem;
}

.mvno-home-data-value {
    color: #1f2937;
    font-weight: 600;
}

.mvno-home-data-additional {
    color: #6366f1;
    font-weight: 600;
    font-size: 0.875rem;
}

.mvno-home-call-sms {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.875rem;
    color: #374151;
    font-weight: 500;
    padding: 0.5rem 0;
}

.mvno-home-call,
.mvno-home-sms {
    color: #374151;
}

.mvno-home-separator {
    color: #d1d5db;
    font-weight: 300;
}

.mvno-home-promotion-price-row {
    display: flex;
    align-items: baseline;
    gap: 0.875rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
    margin-top: 0.5rem;
}

.mvno-home-promotion-period {
    font-size: 0.8125rem;
    color: #6b7280;
    font-weight: 500;
    background-color: #f3f4f6;
    padding: 0.375rem 0.75rem;
    border-radius: 6px;
}

.mvno-home-promotion-price {
    font-size: 1.25rem;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .mvno-home-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        overflow-x: visible;
    }
    
    .mvno-home-card-content {
        padding: 1.5rem;
        gap: 1.125rem;
    }
    
    .mvno-home-provider {
        font-size: 1rem;
        font-weight: 700;
    }
    
    .mvno-home-plan-name {
        font-size: 1.25rem;
        font-weight: 700;
    }
    
    .mvno-home-data {
        font-size: 1rem;
        gap: 0.75rem;
        padding: 1rem;
    }
    
    .mvno-home-data-label {
        font-size: 0.9375rem;
    }
    
    .mvno-home-data-value {
        font-size: 1rem;
    }
    
    .mvno-home-data-additional {
        font-size: 0.9375rem;
    }
    
    .mvno-home-call-sms {
        font-size: 0.9375rem;
    }
    
    .mvno-home-promotion-price-row {
        gap: 1rem;
        flex-wrap: nowrap;
        padding-top: 1rem;
        margin-top: 0.75rem;
    }
    
    .mvno-home-promotion-period {
        font-size: 0.875rem;
        padding: 0.5rem 0.875rem;
    }
    
    .mvno-home-promotion-price {
        font-size: 1.5rem;
        font-weight: 800;
    }
}

/* 메인페이지 전용 통신사폰 카드 스타일 */
.mno-home-grid {
    /* 모바일에서 카드 하나씩 표시 */
}

.mno-home-card {
    display: block;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    transition: all 0.2s;
    height: 100%;
}

.mno-home-card:hover {
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.mno-home-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.mno-home-card-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.mno-home-device-name {
    font-size: 1.125rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.25rem;
    line-height: 1.4;
    letter-spacing: -0.02em;
}

.mno-home-device-storage {
    font-size: 0.9375rem;
    color: #374151;
    font-weight: 500;
    margin-bottom: 0.5rem;
}

.mno-home-discount-section {
    margin-top: 0.5rem;
}

.mno-home-discount-section .mno-support-card {
    background-color: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 0.75rem;
    margin: 0;
}

.mno-home-discount-section .mno-support-card-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #374151;
    margin-bottom: 0.5rem;
    text-align: left;
}

.mno-home-discount-section .mno-support-card-content {
    padding: 0;
}

.mno-home-discount-section .mno-support-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.875rem;
}

.mno-home-discount-section .mno-support-table thead th {
    font-size: 0.75rem;
    font-weight: 600;
    color: #6b7280;
    padding: 0.375rem 0.5rem;
    text-align: left;
    border-bottom: 1px solid #e5e7eb;
}

.mno-home-discount-section .mno-support-table tbody td {
    padding: 0.5rem;
    border-bottom: 1px solid #f3f4f6;
}

.mno-home-discount-section .mno-support-table tbody tr:last-child td {
    border-bottom: none;
}

.mno-home-discount-section .mno-support-provider-text {
    font-weight: 600;
    color: #1f2937;
}

.mno-home-discount-section .mno-support-text {
    color: #374151;
}

.mno-home-discount-section .mno-support-text-positive {
    color: #3b82f6;
}

.mno-home-discount-section .mno-support-text-negative {
    color: #ef4444;
}

.mno-home-discount-section .mno-support-text-empty {
    color: #9ca3af;
}

/* 메인페이지 전용 인터넷 카드 스타일 */
.internet-home-grid {
    /* 모바일에서 카드 하나씩 표시 */
}

.internet-home-card {
    display: block;
    background-color: #ffffff;
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    box-shadow: 0 2px 4px 0 rgba(0, 0, 0, 0.06);
    transition: all 0.2s;
    height: 100%;
}

.internet-home-card:hover {
    box-shadow: 0 4px 12px 0 rgba(0, 0, 0, 0.12);
    transform: translateY(-2px);
}

.internet-home-card-link {
    display: block;
    text-decoration: none;
    color: inherit;
    height: 100%;
}

.internet-home-card-content {
    padding: 1.5rem;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.internet-home-provider-speed {
    display: flex;
    align-items: baseline;
    gap: 0.5rem;
    margin-bottom: 0.75rem;
}

.internet-home-provider {
    font-size: 1.125rem;
    font-weight: 700;
    color: #1f2937;
    letter-spacing: -0.01em;
}

.internet-home-speed {
    font-size: 1.125rem;
    font-weight: 600;
    color: #374151;
}

.internet-home-combined {
    font-size: 0.8125rem;
    color: #6366f1;
    font-weight: 500;
    margin-bottom: 0.5rem;
    background-color: #eef2ff;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    display: inline-block;
    width: fit-content;
}

.internet-home-section-label {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #6b7280;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}

.internet-home-section-items {
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    gap: 0.5rem;
}

.internet-home-item {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 0.75rem;
    background-color: #f9fafb;
    border-radius: 6px;
    font-size: 0.875rem;
    white-space: nowrap;
}

.internet-home-item-name {
    color: #374151;
    font-weight: 500;
}

.internet-home-item-price {
    color: #1f2937;
    font-weight: 600;
}

.internet-home-cash-payment,
.internet-home-gift-card,
.internet-home-equipment {
    margin-top: 0.5rem;
}

.internet-home-price {
    font-size: 1.25rem;
    font-weight: 800;
    color: #111827;
    letter-spacing: -0.02em;
    margin-top: 0.75rem;
    padding-top: 0.75rem;
    border-top: 1px solid #e5e7eb;
}

/* 모바일 반응형 */
@media (max-width: 767px) {
    .mno-home-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        overflow-x: visible;
    }
    
    .mno-home-card-content {
        padding: 1.5rem;
        gap: 1.125rem;
    }
    
    .mno-home-device-name {
        font-size: 1.25rem;
        font-weight: 700;
    }
    
    .mno-home-device-storage {
        font-size: 1rem;
    }
    
    .mno-home-discount-section .mno-support-card {
        padding: 0.625rem;
    }
    
    .mno-home-discount-section .mno-support-card-title {
        font-size: 0.875rem;
        margin-bottom: 0.625rem;
    }
    
    .mno-home-discount-section .mno-support-table {
        font-size: 0.9375rem;
    }
    
    .mno-home-discount-section .mno-support-table thead th {
        font-size: 0.8125rem;
        padding: 0.5rem;
    }
    
    .mno-home-discount-section .mno-support-table tbody td {
        padding: 0.625rem;
    }
    
    /* 인터넷 홈 카드 모바일 스타일 */
    .internet-home-grid {
        grid-template-columns: 1fr !important;
        gap: 1rem;
        overflow-x: visible;
    }
    
    .internet-home-card-content {
        padding: 1.5rem;
        gap: 1.125rem;
    }
    
    .internet-home-provider-speed {
        gap: 1rem;
        margin-bottom: 0.75rem;
    }
    
    .internet-home-provider {
        font-size: 1rem;
        font-weight: 700;
    }
    
    .internet-home-speed {
        font-size: 1.125rem;
    }
    
    .internet-home-combined {
        font-size: 0.875rem;
        padding: 0.375rem 0.75rem;
        margin-bottom: 0.75rem;
    }
    
    .internet-home-item {
        font-size: 0.9375rem;
        padding: 0.625rem 0.875rem;
    }
    
    .internet-home-price {
        font-size: 1.375rem;
        margin-top: 1rem;
        padding-top: 1rem;
    }
}

</style>

<script>
// 배너 높이 자동 조정: 작은 배너 2개를 큰 배너 높이에 맞추고 gap 조절
document.addEventListener('DOMContentLoaded', function() {
    function adjustBannerHeights() {
        const bannerGrid = document.querySelector('.main-banner-grid');
        if (!bannerGrid) return;
        
        // 큰 배너 (캐러셀 또는 단일 배너)
        const largeBannerCarousel = bannerGrid.querySelector('.main-banner-left .main-banner-carousel');
        const largeBannerPlaceholder = bannerGrid.querySelector('.main-banner-left .main-banner-placeholder.large');
        const largeBanner = largeBannerCarousel || largeBannerPlaceholder;
        const rightContainer = bannerGrid.querySelector('.main-banner-right');
        const smallBanners = rightContainer ? rightContainer.querySelectorAll('.main-banner-card.small, .main-banner-placeholder.small') : [];
        
        if (!largeBanner || !rightContainer || smallBanners.length !== 2) return;
        
        // 큰 배너의 실제 높이 측정
        const largeBannerHeight = largeBanner.offsetHeight;
        
        // 작은 배너의 폭 (오른쪽 영역의 폭)
        const smallBannerWidth = rightContainer.offsetWidth;
        
        // 작은 배너가 16:9 비율을 유지할 때의 높이 계산
        const smallBannerHeight16to9 = smallBannerWidth / (16 / 9);
        
        // 작은 배너 2개의 총 높이 (16:9 비율 기준)
        const totalSmallBannerHeight = smallBannerHeight16to9 * 2;
        
        // 필요한 gap 계산 (큰 배너 높이에서 작은 배너 2개 높이를 뺀 값)
        const requiredGap = largeBannerHeight - totalSmallBannerHeight;
        
        // gap 설정 (최소 8px 이상)
        const finalGap = Math.max(requiredGap, 8);
        rightContainer.style.gap = finalGap + 'px';
        
        // 작은 배너는 16:9 비율 유지 (aspect-ratio 사용)
        smallBanners.forEach(banner => {
            banner.style.aspectRatio = '16 / 9';
            banner.style.height = 'auto';
        });
    }
    
    // 초기 실행 (약간의 지연을 두어 DOM 렌더링 완료 후 실행)
    setTimeout(adjustBannerHeights, 50);
    
    // 리사이즈 시 재조정
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(adjustBannerHeights, 100);
    });
    
    // 이미지 로드 후 재조정
    const bannerImages = document.querySelectorAll('.main-banner-image');
    bannerImages.forEach(img => {
        if (img.complete) {
            setTimeout(adjustBannerHeights, 50);
        } else {
            img.addEventListener('load', function() {
                setTimeout(adjustBannerHeights, 50);
            }, { once: true });
        }
    });
});

// 메인 배너 캐러셀
let mainBannerCurrentIndex = 0;
let mainBannerInterval = null;

function initMainBannerCarousel() {
    const carousel = document.getElementById('main-banner-carousel');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.main-banner-slide');
    if (slides.length <= 1) return;
    
    // 자동 롤링 시작
    mainBannerInterval = setInterval(() => {
        changeMainBanner(1);
    }, 2000); // 2초마다 변경
}

function changeMainBanner(direction) {
    const carousel = document.getElementById('main-banner-carousel');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.main-banner-slide');
    const dots = carousel.querySelectorAll('.main-banner-dot');
    
    if (slides.length === 0) return;
    
    // 현재 슬라이드 숨기기
    slides[mainBannerCurrentIndex].classList.remove('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.remove('active');
    }
    
    // 다음 인덱스 계산
    mainBannerCurrentIndex += direction;
    if (mainBannerCurrentIndex >= slides.length) {
        mainBannerCurrentIndex = 0;
    } else if (mainBannerCurrentIndex < 0) {
        mainBannerCurrentIndex = slides.length - 1;
    }
    
    // 다음 슬라이드 표시
    slides[mainBannerCurrentIndex].classList.add('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.add('active');
    }
    
    // 자동 롤링 재시작
    if (mainBannerInterval) {
        clearInterval(mainBannerInterval);
    }
    mainBannerInterval = setInterval(() => {
        changeMainBanner(1);
    }, 2000);
}

function goToMainBanner(index) {
    const carousel = document.getElementById('main-banner-carousel');
    if (!carousel) return;
    
    const slides = carousel.querySelectorAll('.main-banner-slide');
    const dots = carousel.querySelectorAll('.main-banner-dot');
    
    if (index < 0 || index >= slides.length) return;
    
    // 현재 슬라이드 숨기기
    slides[mainBannerCurrentIndex].classList.remove('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.remove('active');
    }
    
    // 선택한 슬라이드 표시
    mainBannerCurrentIndex = index;
    slides[mainBannerCurrentIndex].classList.add('active');
    if (dots[mainBannerCurrentIndex]) {
        dots[mainBannerCurrentIndex].classList.add('active');
    }
    
    // 자동 롤링 재시작
    if (mainBannerInterval) {
        clearInterval(mainBannerInterval);
    }
    mainBannerInterval = setInterval(() => {
        changeMainBanner(1);
    }, 2000);
}

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    initMainBannerCarousel();
});
</script>

<?php
// 푸터 포함
// 푸터 포함 (절대 경로 사용)
$footerPath = __DIR__ . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'footer.php';
if (file_exists($footerPath)) {
    include $footerPath;
} else {
    error_log("index.php: Cannot find footer.php. __DIR__: " . __DIR__);
}
?>

<?php
// 메인페이지 공지사항 새창 표시
$mainNotice = getMainPageNotice();
// getMainPageNotice()에서 이미 경로 정규화가 완료되었으므로 추가 처리 불필요
// 디버깅: 메인공지 정보 확인 (개발 환경에서만)
if (isset($_GET['debug_notice']) && $_GET['debug_notice'] == '1') {
    echo "<!-- 메인공지 디버깅 정보:\n";
    echo "getMainPageNotice() 반환값: " . ($mainNotice ? "있음" : "없음") . "\n";
    if ($mainNotice) {
        $originalImageUrl = getMainPageNotice(); // 원본 경로 확인
        if ($originalImageUrl && !empty($originalImageUrl['image_url'])) {
            echo "원본 image_url (DB): " . htmlspecialchars($originalImageUrl['image_url']) . "\n";
        }
        echo "ID: " . htmlspecialchars($mainNotice['id'] ?? 'N/A') . "\n";
        echo "제목: " . htmlspecialchars($mainNotice['title'] ?? 'N/A') . "\n";
        echo "show_on_main: " . ($mainNotice['show_on_main'] ?? 'N/A') . "\n";
        echo "정규화된 image_url: " . htmlspecialchars($mainNotice['image_url'] ?? '없음') . "\n";
        echo "start_at: " . htmlspecialchars($mainNotice['start_at'] ?? 'NULL') . "\n";
        echo "end_at: " . htmlspecialchars($mainNotice['end_at'] ?? 'NULL') . "\n";
        echo "쿠키 확인: " . (isset($_COOKIE['notice_viewed_' . $mainNotice['id']]) ? "설정됨" : "없음") . "\n";
    }
    echo "-->";
}
if ($mainNotice && !empty($mainNotice['image_url']) && !isset($_COOKIE['notice_viewed_' . $mainNotice['id']])): ?>
<div id="mainNoticeModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background-color: rgba(0, 0, 0, 0.4); z-index: 10000; align-items: center; justify-content: center;">
    <div style="position: relative; width: 90%; max-width: 800px; display: flex; flex-direction: column; align-items: center; border-radius: 12px; overflow: hidden;">
        <!-- 이미지 영역 (클릭 시 링크 이동) -->
        <?php if (!empty($mainNotice['link_url'])): ?>
            <div id="mainNoticeImage" style="display: block; width: 100%; cursor: pointer; position: relative;">
                <img src="<?php echo htmlspecialchars($mainNotice['image_url']); ?>" 
                     alt="<?php echo htmlspecialchars($mainNotice['title']); ?>" 
                     style="width: 100%; height: auto; display: block; border-radius: 12px 12px 0 0;">
            </div>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($mainNotice['image_url']); ?>" 
                 alt="<?php echo htmlspecialchars($mainNotice['title']); ?>" 
                 style="width: 100%; height: auto; display: block; border-radius: 12px 12px 0 0;">
        <?php endif; ?>
        
        <!-- 하단 버튼 영역 -->
        <div style="width: 100%; padding: 16px 24px; display: flex; justify-content: space-between; align-items: center; background: rgba(0, 0, 0, 0.5); border-radius: 0 0 12px 12px;">
            <label style="display: flex; align-items: center; gap: 8px; font-size: 14px; color: white; cursor: pointer;">
                <input type="checkbox" id="dontShowAgain" style="width: auto; margin: 0;">
                <span>오늘 그만보기</span>
            </label>
            <button type="button" id="closeMainNoticeBtn" style="padding: 8px 20px; background: rgba(255, 255, 255, 0.2); color: white; border: 1px solid rgba(255, 255, 255, 0.3); border-radius: 6px; font-weight: 500; font-size: 14px; cursor: pointer; transition: background 0.2s;">
                창닫기
            </button>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('mainNoticeModal');
    const closeBtn = document.getElementById('closeMainNoticeBtn');
    const dontShowAgain = document.getElementById('dontShowAgain');
    const noticeImage = document.getElementById('mainNoticeImage');
    const noticeId = '<?php echo htmlspecialchars($mainNotice['id']); ?>';
    const linkUrl = '<?php echo !empty($mainNotice['link_url']) ? htmlspecialchars($mainNotice['link_url'], ENT_QUOTES) : ''; ?>';
    
    // 현재 스크롤 위치 저장
    let scrollPosition = 0;
    
    // 모달 표시
    if (modal) {
        // 현재 스크롤 위치 저장
        scrollPosition = window.pageYOffset || document.documentElement.scrollTop;
        
        modal.style.display = 'flex';
        // body 스크롤 고정
        document.body.style.overflow = 'hidden';
        document.body.style.position = 'fixed';
        document.body.style.top = '-' + scrollPosition + 'px';
        document.body.style.width = '100%';
    }
    
    function closeModal(saveCookie, redirectUrl) {
        if (modal) {
            modal.style.display = 'none';
            // body 스크롤 복원
            document.body.style.overflow = '';
            document.body.style.position = '';
            document.body.style.top = '';
            document.body.style.width = '';
            // 스크롤 위치 복원
            window.scrollTo(0, scrollPosition);
        }
        
        // 쿠키 설정 (체크박스가 체크되었거나 saveCookie가 true일 때)
        if (saveCookie || (dontShowAgain && dontShowAgain.checked)) {
            const expires = new Date();
            expires.setHours(23, 59, 59, 999); // 오늘 자정까지
            document.cookie = 'notice_viewed_' + noticeId + '=1; expires=' + expires.toUTCString() + '; path=/';
        }
        
        // 리다이렉트 URL이 있으면 이동
        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    }
    
    // 이미지 클릭 시 링크로 이동
    if (noticeImage && linkUrl) {
        noticeImage.addEventListener('click', function(e) {
            e.preventDefault();
            closeModal(true, linkUrl); // 모달 닫고 쿠키 설정 후 리다이렉트
        });
    }
    
    // 오늘 그만보기 체크박스 클릭 시 즉시 모달 닫기
    if (dontShowAgain) {
        dontShowAgain.addEventListener('change', function() {
            if (this.checked) {
                // 체크박스가 선택되면 즉시 모달 닫기 및 쿠키 설정
                closeModal(true);
            }
        });
    }
    
    if (closeBtn) {
        closeBtn.addEventListener('click', function() {
            closeModal(false); // 닫기 버튼은 쿠키 설정 안 함
        });
        // 호버 효과
        closeBtn.addEventListener('mouseenter', function() {
            this.style.background = 'rgba(255, 255, 255, 0.3)';
        });
        closeBtn.addEventListener('mouseleave', function() {
            this.style.background = 'rgba(255, 255, 255, 0.2)';
        });
    }
    
    // 배경 클릭 시 닫기
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeModal(false); // 배경 클릭은 쿠키 설정 안 함
            }
        });
    }
    
    // ESC 키로 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && modal && modal.style.display === 'flex') {
            closeModal(false); // ESC 키는 쿠키 설정 안 함
        }
    });
});
</script>
<?php endif; ?>

<?php
// show_login 파라미터가 있으면 로그인 모달 자동 열기
if (isset($_GET['show_login']) && $_GET['show_login'] == '1'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    if (typeof openLoginModal === 'function') {
        openLoginModal(true);
    } else {
        setTimeout(() => {
            if (typeof openLoginModal === 'function') {
                openLoginModal(true);
            }
        }, 100);
    }
});
</script>
<?php endif; ?>
