<?php
/**
 * 1:1 Q&A 관련 함수
 * DB 기반 데이터 저장
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/db-config.php';

// 사용자 ID 가져오기 (세션에서, 임시로 'default' 사용)
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        // 실제로는 세션에서 가져옴
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 'default';
    }
}

// Q&A 목록 가져오기 (사용자별)
function getQnaList($user_id = null) {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    if ($user_id !== null) {
        $stmt = $pdo->prepare("SELECT * FROM qna WHERE user_id = :user ORDER BY created_at DESC");
        $stmt->execute([':user' => (string)$user_id]);
    } else {
        $stmt = $pdo->query("SELECT * FROM qna ORDER BY created_at DESC");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Q&A 상세 가져오기
function getQnaById($id, $user_id = null) {
    $pdo = getDBConnection();
    if (!$pdo) return null;
    $stmt = $pdo->prepare("SELECT * FROM qna WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => (string)$id]);
    $qna = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$qna) return null;
    if ($user_id !== null && isset($qna['user_id']) && $qna['user_id'] != $user_id) return null;
    return $qna;
}

// Q&A 질문 생성
function createQna($user_id, $title, $content) {
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

    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("
        INSERT INTO qna (id, user_id, title, content, status, created_at, updated_at)
        VALUES (:id,:user,:title,:content,'pending',:ca,:ua)
    ");
    $stmt->execute([
        ':id' => $qna['id'],
        ':user' => (string)$user_id,
        ':title' => (string)$title,
        ':content' => (string)$content,
        ':ca' => $qna['created_at'],
        ':ua' => $qna['updated_at'],
    ]);
    return $qna;
}

// Q&A 답변 작성 (관리자용)
function answerQna($id, $answer, $answered_by = 'admin') {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    $stmt = $pdo->prepare("
        UPDATE qna
        SET answer = :answer,
            answered_at = NOW(),
            answered_by = :by,
            status = 'answered',
            updated_at = NOW()
        WHERE id = :id
    ");
    $stmt->execute([
        ':id' => (string)$id,
        ':answer' => (string)$answer,
        ':by' => (string)$answered_by
    ]);
    return $stmt->rowCount() > 0;
}

// Q&A 삭제
function deleteQna($id, $user_id = null) {
    $pdo = getDBConnection();
    if (!$pdo) return false;
    if ($user_id !== null) {
        $stmt = $pdo->prepare("DELETE FROM qna WHERE id = :id AND user_id = :user");
        $stmt->execute([':id' => (string)$id, ':user' => (string)$user_id]);
    } else {
        $stmt = $pdo->prepare("DELETE FROM qna WHERE id = :id");
        $stmt->execute([':id' => (string)$id]);
    }
    return $stmt->rowCount() > 0;
}

// 모든 Q&A 가져오기 (관리자용)
function getAllQnaForAdmin() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    $stmt = $pdo->query("SELECT * FROM qna ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 답변 대기 중인 Q&A 개수 (관리자용)
function getPendingQnaCount() {
    $pdo = getDBConnection();
    if (!$pdo) return 0;
    $stmt = $pdo->query("SELECT COUNT(*) FROM qna WHERE status = 'pending'");
    return (int)($stmt->fetchColumn() ?? 0);
}

