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
        'mno_phones' => [],
        'internet_products' => []
    ];

    $settings = getAppSettings('home', $defaults);

    // 과거 키(main_banner) -> main_banners로 1회 마이그레이션
    if (isset($settings['main_banner']) && !isset($settings['main_banners'])) {
        $settings['main_banners'] = $settings['main_banner'] ? [$settings['main_banner']] : [];
        unset($settings['main_banner']);
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

// 통신사폰 설정
function setMnoPhones($phone_ids) {
    $settings = getHomeSettings();
    $settings['mno_phones'] = $phone_ids;
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

    $sql = "
        SELECT id, title, image_url AS image, link_url AS link, category,
               start_at AS start_date, end_at AS end_date,
               is_published AS is_active,
               created_at, updated_at
        FROM events
        WHERE is_published = 1
          AND (start_at IS NULL OR start_at <= CURDATE())
          AND (end_at IS NULL OR end_at >= CURDATE())
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
function getEventById($id) {
    $pdo = getDBConnection();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("
        SELECT id, title, image_url AS image, link_url AS link, category,
               start_at AS start_date, end_at AS end_date,
               is_published AS is_active,
               created_at, updated_at
        FROM events
        WHERE id = :id
        LIMIT 1
    ");
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
    $stmt = $pdo->query("
        SELECT id, title, image_url AS image, link_url AS link, category,
               start_at AS start_date, end_at AS end_date,
               is_published AS is_active,
               created_at, updated_at
        FROM events
        ORDER BY created_at DESC
    ");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

