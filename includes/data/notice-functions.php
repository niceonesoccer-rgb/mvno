<?php
/**
 * 공지사항 관련 함수
 * JSON 파일 기반 데이터 저장
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

// 공지사항 데이터 파일 경로
function getNoticeDataFile() {
    return __DIR__ . '/notices.json';
}

// 공지사항 목록 가져오기
function getNotices($limit = null, $offset = 0) {
    $file = getNoticeDataFile();
    
    if (!file_exists($file)) {
        return [];
    }
    
    $notices = json_decode(file_get_contents($file), true);
    if (!is_array($notices)) {
        return [];
    }
    
    // 날짜순 정렬 (최신순)
    usort($notices, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    // 공개된 공지사항만 필터링
    $notices = array_filter($notices, function($notice) {
        return isset($notice['is_published']) && $notice['is_published'] === true;
    });
    
    if ($limit !== null) {
        return array_slice($notices, $offset, $limit);
    }
    
    return $notices;
}

// 공지사항 상세 가져오기
function getNoticeById($id) {
    $file = getNoticeDataFile();
    
    if (!file_exists($file)) {
        return null;
    }
    
    $notices = json_decode(file_get_contents($file), true);
    if (!is_array($notices)) {
        return null;
    }
    
    foreach ($notices as $notice) {
        if (isset($notice['id']) && $notice['id'] == $id) {
            return $notice;
        }
    }
    
    return null;
}

// 공지사항 생성 (관리자용)
function createNotice($title, $content, $is_important = false) {
    $file = getNoticeDataFile();
    $notices = [];
    
    if (file_exists($file)) {
        $notices = json_decode(file_get_contents($file), true);
        if (!is_array($notices)) {
            $notices = [];
        }
    }
    
    $notice = [
        'id' => uniqid('notice_'),
        'title' => $title,
        'content' => $content,
        'is_important' => $is_important,
        'is_published' => true,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
        'views' => 0
    ];
    
    $notices[] = $notice;
    file_put_contents($file, json_encode($notices, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return $notice;
}

// 공지사항 수정 (관리자용)
function updateNotice($id, $title, $content, $is_important = false, $is_published = true) {
    $file = getNoticeDataFile();
    
    if (!file_exists($file)) {
        return false;
    }
    
    $notices = json_decode(file_get_contents($file), true);
    if (!is_array($notices)) {
        return false;
    }
    
    $found = false;
    foreach ($notices as &$notice) {
        if (isset($notice['id']) && $notice['id'] == $id) {
            $notice['title'] = $title;
            $notice['content'] = $content;
            $notice['is_important'] = $is_important;
            $notice['is_published'] = $is_published;
            $notice['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    if ($found) {
        file_put_contents($file, json_encode($notices, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }
    
    return false;
}

// 공지사항 삭제 (관리자용)
function deleteNotice($id) {
    $file = getNoticeDataFile();
    
    if (!file_exists($file)) {
        return false;
    }
    
    $notices = json_decode(file_get_contents($file), true);
    if (!is_array($notices)) {
        return false;
    }
    
    $notices = array_filter($notices, function($notice) use ($id) {
        return !isset($notice['id']) || $notice['id'] != $id;
    });
    
    file_put_contents($file, json_encode(array_values($notices), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// 조회수 증가
function incrementNoticeViews($id) {
    $file = getNoticeDataFile();
    
    if (!file_exists($file)) {
        return false;
    }
    
    $notices = json_decode(file_get_contents($file), true);
    if (!is_array($notices)) {
        return false;
    }
    
    foreach ($notices as &$notice) {
        if (isset($notice['id']) && $notice['id'] == $id) {
            $notice['views'] = ($notice['views'] ?? 0) + 1;
            break;
        }
    }
    
    file_put_contents($file, json_encode($notices, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// 모든 공지사항 가져오기 (관리자용, 비공개 포함)
function getAllNoticesForAdmin() {
    $file = getNoticeDataFile();
    
    if (!file_exists($file)) {
        return [];
    }
    
    $notices = json_decode(file_get_contents($file), true);
    if (!is_array($notices)) {
        return [];
    }
    
    // 날짜순 정렬 (최신순)
    usort($notices, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $notices;
}






