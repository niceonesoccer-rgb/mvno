<?php
/**
 * 메인 페이지 관련 함수
 * JSON 파일 기반 데이터 저장
 */

// 메인 페이지 설정 파일 경로
function getHomeDataFile() {
    return __DIR__ . '/home-settings.json';
}

// 이벤트 데이터 파일 경로
function getEventDataFile() {
    return __DIR__ . '/events.json';
}

// 메인 페이지 설정 가져오기
function getHomeSettings() {
    $file = getHomeDataFile();
    
    if (!file_exists($file)) {
        // 기본 설정 반환
        return [
            'main_banners' => [],
            'ranking_banners' => [],
            'data_plans' => [],
            'mvno_plans' => [],
            'mno_phones' => [],
            'internet_products' => []
        ];
    }
    
    $settings = json_decode(file_get_contents($file), true);
    // 기존 main_banner를 main_banners 배열로 마이그레이션
    if (isset($settings['main_banner']) && !isset($settings['main_banners'])) {
        $settings['main_banners'] = $settings['main_banner'] ? [$settings['main_banner']] : [];
        unset($settings['main_banner']);
    }
    return $settings ?: [];
}

// 메인 페이지 설정 저장
function saveHomeSettings($settings) {
    $file = getHomeDataFile();
    file_put_contents($file, json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
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
    $file = getEventDataFile();
    
    if (!file_exists($file)) {
        return [];
    }
    
    $events = json_decode(file_get_contents($file), true);
    if (!is_array($events)) {
        return [];
    }
    
    // 활성 이벤트만 필터링
    $events = array_filter($events, function($event) {
        if (!isset($event['is_active']) || !$event['is_active']) {
            return false;
        }
        
        // 날짜 확인
        $now = time();
        $start = isset($event['start_date']) ? strtotime($event['start_date']) : 0;
        $end = isset($event['end_date']) ? strtotime($event['end_date']) : PHP_INT_MAX;
        
        return $now >= $start && $now <= $end;
    });
    
    // 날짜순 정렬 (최신순)
    usort($events, function($a, $b) {
        return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
    });
    
    if ($limit !== null) {
        return array_slice($events, 0, $limit);
    }
    
    return array_values($events);
}

// 이벤트 ID로 가져오기
function getEventById($id) {
    $events = getEvents();
    foreach ($events as $event) {
        if (isset($event['id']) && $event['id'] == $id) {
            return $event;
        }
    }
    return null;
}

// 이벤트 생성
function createEvent($title, $image, $link, $start_date, $end_date, $category = 'all') {
    $file = getEventDataFile();
    $events = [];
    
    if (file_exists($file)) {
        $events = json_decode(file_get_contents($file), true);
        if (!is_array($events)) {
            $events = [];
        }
    }
    
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
    
    $events[] = $event;
    file_put_contents($file, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return $event;
}

// 이벤트 수정
function updateEvent($id, $title, $image, $link, $start_date, $end_date, $category, $is_active) {
    $file = getEventDataFile();
    
    if (!file_exists($file)) {
        return false;
    }
    
    $events = json_decode(file_get_contents($file), true);
    if (!is_array($events)) {
        return false;
    }
    
    $found = false;
    foreach ($events as &$event) {
        if (isset($event['id']) && $event['id'] == $id) {
            $event['title'] = $title;
            $event['image'] = $image;
            $event['link'] = $link;
            $event['start_date'] = $start_date;
            $event['end_date'] = $end_date;
            $event['category'] = $category;
            $event['is_active'] = $is_active;
            $event['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    if ($found) {
        file_put_contents($file, json_encode($events, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }
    
    return false;
}

// 이벤트 삭제
function deleteEvent($id) {
    $file = getEventDataFile();
    
    if (!file_exists($file)) {
        return false;
    }
    
    $events = json_decode(file_get_contents($file), true);
    if (!is_array($events)) {
        return false;
    }
    
    $events = array_filter($events, function($event) use ($id) {
        return !isset($event['id']) || $event['id'] != $id;
    });
    
    file_put_contents($file, json_encode(array_values($events), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// 모든 이벤트 가져오기 (관리자용)
function getAllEvents() {
    $file = getEventDataFile();
    
    if (!file_exists($file)) {
        return [];
    }
    
    $events = json_decode(file_get_contents($file), true);
    if (!is_array($events)) {
        return [];
    }
    
    // 날짜순 정렬 (최신순)
    usort($events, function($a, $b) {
        return strtotime($b['created_at'] ?? '') - strtotime($a['created_at'] ?? '');
    });
    
    return $events;
}

