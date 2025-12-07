<?php
/**
 * 1:1 Q&A 관련 함수
 * JSON 파일 기반 데이터 저장
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

// Q&A 데이터 파일 경로
function getQnaDataFile() {
    return __DIR__ . '/qna.json';
}

// 사용자 ID 가져오기 (세션에서, 임시로 'default' 사용)
function getCurrentUserId() {
    // 실제로는 세션에서 가져옴
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'default';
}

// Q&A 목록 가져오기 (사용자별)
function getQnaList($user_id = null) {
    $file = getQnaDataFile();
    
    if (!file_exists($file)) {
        return [];
    }
    
    $qnas = json_decode(file_get_contents($file), true);
    if (!is_array($qnas)) {
        return [];
    }
    
    // 사용자별 필터링
    if ($user_id !== null) {
        $qnas = array_filter($qnas, function($qna) use ($user_id) {
            return isset($qna['user_id']) && $qna['user_id'] == $user_id;
        });
    }
    
    // 날짜순 정렬 (최신순)
    usort($qnas, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return array_values($qnas);
}

// Q&A 상세 가져오기
function getQnaById($id, $user_id = null) {
    $file = getQnaDataFile();
    
    if (!file_exists($file)) {
        return null;
    }
    
    $qnas = json_decode(file_get_contents($file), true);
    if (!is_array($qnas)) {
        return null;
    }
    
    foreach ($qnas as $qna) {
        if (isset($qna['id']) && $qna['id'] == $id) {
            // 사용자 확인 (관리자가 아니면 본인 것만)
            if ($user_id !== null && isset($qna['user_id']) && $qna['user_id'] != $user_id) {
                return null;
            }
            return $qna;
        }
    }
    
    return null;
}

// Q&A 질문 생성
function createQna($user_id, $title, $content) {
    $file = getQnaDataFile();
    $qnas = [];
    
    if (file_exists($file)) {
        $qnas = json_decode(file_get_contents($file), true);
        if (!is_array($qnas)) {
            $qnas = [];
        }
    }
    
    $qna = [
        'id' => uniqid('qna_'),
        'user_id' => $user_id,
        'title' => $title,
        'content' => $content,
        'answer' => null,
        'answered_at' => null,
        'answered_by' => null,
        'status' => 'pending', // pending, answered
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ];
    
    $qnas[] = $qna;
    file_put_contents($file, json_encode($qnas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    
    return $qna;
}

// Q&A 답변 작성 (관리자용)
function answerQna($id, $answer, $answered_by = 'admin') {
    $file = getQnaDataFile();
    
    if (!file_exists($file)) {
        return false;
    }
    
    $qnas = json_decode(file_get_contents($file), true);
    if (!is_array($qnas)) {
        return false;
    }
    
    $found = false;
    foreach ($qnas as &$qna) {
        if (isset($qna['id']) && $qna['id'] == $id) {
            $qna['answer'] = $answer;
            $qna['answered_at'] = date('Y-m-d H:i:s');
            $qna['answered_by'] = $answered_by;
            $qna['status'] = 'answered';
            $qna['updated_at'] = date('Y-m-d H:i:s');
            $found = true;
            break;
        }
    }
    
    if ($found) {
        file_put_contents($file, json_encode($qnas, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return true;
    }
    
    return false;
}

// Q&A 삭제
function deleteQna($id, $user_id = null) {
    $file = getQnaDataFile();
    
    if (!file_exists($file)) {
        return false;
    }
    
    $qnas = json_decode(file_get_contents($file), true);
    if (!is_array($qnas)) {
        return false;
    }
    
    $qnas = array_filter($qnas, function($qna) use ($id, $user_id) {
        if (isset($qna['id']) && $qna['id'] == $id) {
            // 사용자 확인 (관리자가 아니면 본인 것만 삭제 가능)
            if ($user_id !== null && isset($qna['user_id']) && $qna['user_id'] != $user_id) {
                return true; // 삭제하지 않음
            }
            return false; // 삭제
        }
        return true; // 유지
    });
    
    file_put_contents($file, json_encode(array_values($qnas), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    return true;
}

// 모든 Q&A 가져오기 (관리자용)
function getAllQnaForAdmin() {
    $file = getQnaDataFile();
    
    if (!file_exists($file)) {
        return [];
    }
    
    $qnas = json_decode(file_get_contents($file), true);
    if (!is_array($qnas)) {
        return [];
    }
    
    // 날짜순 정렬 (최신순)
    usort($qnas, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });
    
    return $qnas;
}

// 답변 대기 중인 Q&A 개수 (관리자용)
function getPendingQnaCount() {
    $qnas = getAllQnaForAdmin();
    $count = 0;
    
    foreach ($qnas as $qna) {
        if (isset($qna['status']) && $qna['status'] == 'pending') {
            $count++;
        }
    }
    
    return $count;
}

