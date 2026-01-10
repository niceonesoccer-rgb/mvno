<?php
/**
 * 메인 페이지 관련 함수
 * DB-only:
 * - home 설정: app_settings(namespace='home')
 * - 이벤트: events 테이블
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/db-config.php';
require_once __DIR__ . '/app-settings.php';

// 메인 페이지 설정 가져오기
function getHomeSettings() {
    $defaults = [
        'main_banners' => [],
        'ranking_banners' => [],
        'data_plans' => [],
        'mvno_plans' => [],
        'site_large_banners' => [], // 사이트 전체 큰 배너 (롤링 배너)
        'site_small_banners' => [], // 사이트 전체 작은 배너 2개
        'mno_phones' => [],
        'mno_sim_plans' => [], // 알짜 통신사단독유심
        'internet_products' => []
    ];

    $settings = getAppSettings('home', $defaults);

    // 과거 키(main_banner) -> main_banners로 1회 마이그레이션
    if (isset($settings['main_banner']) && !isset($settings['main_banners'])) {
        $settings['main_banners'] = $settings['main_banner'] ? [$settings['main_banner']] : [];
        unset($settings['main_banner']);
        saveHomeSettings($settings);
    }
    
    // mvno_large_banners -> site_large_banners 마이그레이션 (1회)
    if (isset($settings['mvno_large_banners']) && !isset($settings['site_large_banners'])) {
        $settings['site_large_banners'] = $settings['mvno_large_banners'];
        unset($settings['mvno_large_banners']);
        saveHomeSettings($settings);
    }
    
    // mvno_small_banners -> site_small_banners 마이그레이션 (1회)
    if (isset($settings['mvno_small_banners']) && !isset($settings['site_small_banners'])) {
        $settings['site_small_banners'] = $settings['mvno_small_banners'];
        unset($settings['mvno_small_banners']);
        saveHomeSettings($settings);
    }

    return $settings;
}

// 메인 페이지 설정 저장
function saveHomeSettings($settings) {
    $updatedBy = function_exists('getCurrentUserId') ? getCurrentUserId() : null;
    return saveAppSettings('home', (array)$settings, $updatedBy);
}

// 메인 배너 설정 (3개)
function setMainBanners($event_ids) {
    $settings = getHomeSettings();
    $settings['main_banners'] = array_slice($event_ids, 0, 3); // 최대 3개
    return saveHomeSettings($settings);
}

// 랭킹 배너 설정
function setRankingBanners($event_ids) {
    $settings = getHomeSettings();
    $settings['ranking_banners'] = array_slice($event_ids, 0, 2); // 최대 2개
    return saveHomeSettings($settings);
}

// 데이터 요금제 설정
function setDataPlans($plans) {
    $settings = getHomeSettings();
    $settings['data_plans'] = $plans;
    return saveHomeSettings($settings);
}

// 알뜰폰 요금제 설정
function setMvnoPlans($plan_ids) {
    $settings = getHomeSettings();
    $settings['mvno_plans'] = $plan_ids;
    return saveHomeSettings($settings);
}

// 사이트 전체 큰 배너 설정 (롤링 배너)
function setSiteLargeBanners($event_ids) {
    $settings = getHomeSettings();
    $settings['site_large_banners'] = is_array($event_ids) ? $event_ids : [];
    return saveHomeSettings($settings);
}

// 사이트 전체 작은 배너 설정 (2개)
function setSiteSmallBanners($event_ids) {
    $settings = getHomeSettings();
    $settings['site_small_banners'] = is_array($event_ids) ? array_slice($event_ids, 0, 2) : [];
    return saveHomeSettings($settings);
}

// 하위 호환성을 위한 별칭 (deprecated)
function setMvnoLargeBanners($event_ids) {
    return setSiteLargeBanners($event_ids);
}

function setMvnoSmallBanners($event_ids) {
    return setSiteSmallBanners($event_ids);
}

// 통신사폰 설정
function setMnoPhones($phone_ids) {
    $settings = getHomeSettings();
    $settings['mno_phones'] = $phone_ids;
    return saveHomeSettings($settings);
}

// 통신사단독유심 설정
function setMnoSimPlans($plan_ids) {
    $settings = getHomeSettings();
    $settings['mno_sim_plans'] = $plan_ids;
    return saveHomeSettings($settings);
}

// 인터넷 상품 설정
function setInternetProducts($product_ids) {
    $settings = getHomeSettings();
    $settings['internet_products'] = $product_ids;
    return saveHomeSettings($settings);
}

// 이벤트 목록 가져오기
function getEvents($limit = null) {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    // is_published 컬럼 존재 여부 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM events LIKE 'is_published'");
    $hasIsPublished = $stmt->rowCount() > 0;
    
    $whereConditions = [];
    if ($hasIsPublished) {
        // is_published가 0이면 기간과 상관없이 비공개
        $whereConditions[] = "(is_published IS NULL OR is_published != 0)";
    }
    
    // 공개 기간 확인
    $whereConditions[] = "(start_at IS NULL OR start_at <= CURDATE())";
    $whereConditions[] = "(end_at IS NULL OR end_at >= CURDATE())";
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // 컬럼 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // SELECT 절 동적 생성
    $selectFields = ['id', 'title'];
    
    // image 필드 처리
    if (in_array('main_image', $columns) && in_array('image_url', $columns)) {
        $selectFields[] = 'COALESCE(main_image, image_url) AS image';
    } elseif (in_array('main_image', $columns)) {
        $selectFields[] = 'main_image AS image';
    } elseif (in_array('image_url', $columns)) {
        $selectFields[] = 'image_url AS image';
    }
    
    // link 필드 처리
    if (in_array('link_url', $columns)) {
        $selectFields[] = 'link_url AS link';
    } else {
        $selectFields[] = 'NULL AS link';
    }
    
    // 나머지 필드
    if (in_array('category', $columns)) {
        $selectFields[] = 'category';
    }
    if (in_array('start_at', $columns)) {
        $selectFields[] = 'start_at AS start_date';
    }
    if (in_array('end_at', $columns)) {
        $selectFields[] = 'end_at AS end_date';
    }
    if (in_array('is_published', $columns)) {
        $selectFields[] = 'is_published AS is_active';
    }
    if (in_array('created_at', $columns)) {
        $selectFields[] = 'created_at';
    }
    if (in_array('updated_at', $columns)) {
        $selectFields[] = 'updated_at';
    }

    $sql = "
        SELECT " . implode(', ', $selectFields) . "
        FROM events
        {$whereClause}
        ORDER BY created_at DESC
    ";
    if ($limit !== null) {
        $sql .= " LIMIT :limit";
    }

    $stmt = $pdo->prepare($sql);
    if ($limit !== null) {
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 이벤트 ID로 가져오기
// $skipDateCheck: true면 기간 체크를 건너뜀 (배너용)
function getEventById($id, $skipDateCheck = false) {
    $pdo = getDBConnection();
    if (!$pdo) return null;
    
    // 컬럼 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // SELECT 절 동적 생성
    $selectFields = ['id', 'title'];
    
    // image 필드 처리
    if (in_array('main_image', $columns) && in_array('image_url', $columns)) {
        $selectFields[] = 'COALESCE(main_image, image_url) AS image';
    } elseif (in_array('main_image', $columns)) {
        $selectFields[] = 'main_image AS image';
    } elseif (in_array('image_url', $columns)) {
        $selectFields[] = 'image_url AS image';
    }
    
    // link 필드 처리
    if (in_array('link_url', $columns)) {
        $selectFields[] = 'link_url AS link';
    } else {
        $selectFields[] = 'NULL AS link';
    }
    
    // 나머지 필드
    if (in_array('category', $columns)) {
        $selectFields[] = 'category';
    }
    if (in_array('start_at', $columns)) {
        $selectFields[] = 'start_at AS start_date';
    }
    if (in_array('end_at', $columns)) {
        $selectFields[] = 'end_at AS end_date';
    }
    if (in_array('is_published', $columns)) {
        $selectFields[] = 'is_published AS is_active';
    }
    if (in_array('created_at', $columns)) {
        $selectFields[] = 'created_at';
    }
    if (in_array('updated_at', $columns)) {
        $selectFields[] = 'updated_at';
    }
    
    // 공개 상태 및 기간 체크를 위한 WHERE 조건 추가
    $whereConditions = ['id = :id'];
    
    // is_published 컬럼 존재 여부 확인
    $stmt = $pdo->query("
        SELECT COUNT(*) as cnt 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = DATABASE() 
        AND TABLE_NAME = 'events' 
        AND COLUMN_NAME = 'is_published'
    ");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $hasIsPublished = $result['cnt'] > 0;
    
    if ($hasIsPublished) {
        // is_published가 0이면 기간과 상관없이 비공개
        $whereConditions[] = "(is_published IS NULL OR is_published != 0)";
    }
    
    // 기간 체크 (배너는 기간 체크 건너뛰기)
    if (!$skipDateCheck) {
        // 공개 기간 확인
        $whereConditions[] = "(start_at IS NULL OR start_at <= CURDATE())";
        $whereConditions[] = "(end_at IS NULL OR end_at >= CURDATE())";
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "SELECT " . implode(', ', $selectFields) . " FROM events WHERE {$whereClause} LIMIT 1";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':id' => (string)$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $row ?: null;
}

// 이벤트 생성
function createEvent($title, $image, $link, $start_date, $end_date, $category = 'all') {
    $event = [
        'id' => uniqid('event_'),
        'title' => $title,
        'image' => $image,
        'link' => $link,
        'start_date' => $start_date,
        'end_date' => $end_date,
        'category' => $category,
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];

    $pdo = getDBConnection();
    if (!$pdo) return false;

    $stmt = $pdo->prepare("
        INSERT INTO events (id, title, image_url, link_url, category, start_at, end_at, is_published, created_at, updated_at)
        VALUES (:id, :title, :img, :link, :cat, :start, :end, :pub, NOW(), NOW())
    ");
    $stmt->execute([
        ':id' => (string)$event['id'],
        ':title' => (string)$event['title'],
        ':img' => (string)$event['image'],
        ':link' => (string)$event['link'],
        ':cat' => (string)$event['category'],
        ':start' => $event['start_date'] ? (string)$event['start_date'] : null,
        ':end' => $event['end_date'] ? (string)$event['end_date'] : null,
        ':pub' => $event['is_active'] ? 1 : 0,
    ]);

    return $event;
}

// 이벤트 수정
function updateEvent($id, $title, $image, $link, $start_date, $end_date, $category, $is_active) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("
        UPDATE events
        SET title = :title,
            image_url = :img,
            link_url = :link,
            category = :cat,
            start_at = :start,
            end_at = :end,
            is_published = :pub,
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => (string)$id,
        ':title' => (string)$title,
        ':img' => (string)$image,
        ':link' => (string)$link,
        ':cat' => (string)$category,
        ':start' => $start_date ? (string)$start_date : null,
        ':end' => $end_date ? (string)$end_date : null,
        ':pub' => $is_active ? 1 : 0,
    ]);
    return $stmt->rowCount() > 0;
}

// 이벤트 삭제
function deleteEvent($id) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("DELETE FROM events WHERE id = :id");
    $stmt->execute([':id' => (string)$id]);
    return $stmt->rowCount() > 0;
}

// 모든 이벤트 가져오기 (관리자용)
function getAllEvents() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    // 컬럼 확인
    $stmt = $pdo->query("SHOW COLUMNS FROM events");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // SELECT 절 동적 생성
    $selectFields = ['id', 'title'];
    
    // image 필드 처리
    if (in_array('main_image', $columns) && in_array('image_url', $columns)) {
        $selectFields[] = 'COALESCE(main_image, image_url) AS image';
    } elseif (in_array('main_image', $columns)) {
        $selectFields[] = 'main_image AS image';
    } elseif (in_array('image_url', $columns)) {
        $selectFields[] = 'image_url AS image';
    }
    
    // link 필드 처리
    if (in_array('link_url', $columns)) {
        $selectFields[] = 'link_url AS link';
    } else {
        $selectFields[] = 'NULL AS link';
    }
    
    // 나머지 필드
    if (in_array('category', $columns)) {
        $selectFields[] = 'category';
    }
    if (in_array('start_at', $columns)) {
        $selectFields[] = 'start_at AS start_date';
    }
    if (in_array('end_at', $columns)) {
        $selectFields[] = 'end_at AS end_date';
    }
    if (in_array('is_published', $columns)) {
        $selectFields[] = 'is_published AS is_active';
    }
    if (in_array('created_at', $columns)) {
        $selectFields[] = 'created_at';
    }
    if (in_array('updated_at', $columns)) {
        $selectFields[] = 'updated_at';
    }
    
    $sql = "SELECT " . implode(', ', $selectFields) . " FROM events ORDER BY created_at DESC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

