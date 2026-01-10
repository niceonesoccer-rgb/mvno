<?php
/**
 * 1:1 Q&A 관련 함수
 * DB 기반 데이터 저장
 */

// 한국 시간대 설정 (KST, UTC+9)
date_default_timezone_set('Asia/Seoul');

require_once __DIR__ . '/db-config.php';

// 사용자 ID 가져오기 (세션에서)
if (!function_exists('getCurrentUserId')) {
    function getCurrentUserId() {
        // auth-functions.php의 getCurrentUser()를 사용하도록 변경
        // 이 함수는 로그인 체크 후에만 호출되어야 함
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    }
}

// Q&A 목록 가져오기 (사용자별, 소프트 삭제 제외)
function getQnaList($user_id = null) {
    $pdo = getDBConnection();
    if (!$pdo) return [];

    // deleted_at 컬럼 존재 여부 확인
    $hasDeletedAt = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
        $hasDeletedAt = $checkStmt->fetch() !== false;
    } catch (Exception $e) {
        // 컬럼이 없으면 false로 유지
    }

    if ($user_id !== null) {
        if ($hasDeletedAt) {
            $stmt = $pdo->prepare("SELECT * FROM qna WHERE user_id = :user AND deleted_at IS NULL ORDER BY created_at DESC");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM qna WHERE user_id = :user ORDER BY created_at DESC");
        }
        $stmt->execute([':user' => (string)$user_id]);
    } else {
        if ($hasDeletedAt) {
            $stmt = $pdo->query("SELECT * FROM qna WHERE deleted_at IS NULL ORDER BY created_at DESC");
        } else {
            $stmt = $pdo->query("SELECT * FROM qna ORDER BY created_at DESC");
        }
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Q&A 상세 가져오기
function getQnaById($id, $user_id = null) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("QnA 조회 실패: DB 연결 실패 - ID: " . $id);
            return null;
        }
        
        // deleted_at 컬럼 존재 여부 확인
        $hasDeletedAt = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
            $hasDeletedAt = $checkStmt->fetch() !== false;
        } catch (Exception $e) {
            // 컬럼이 없으면 false로 유지
        }
        
        if ($hasDeletedAt) {
            $stmt = $pdo->prepare("SELECT * FROM qna WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM qna WHERE id = :id LIMIT 1");
        }
        $stmt->execute([':id' => (string)$id]);
        $qna = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$qna) {
            error_log("QnA 조회 실패: QnA를 찾을 수 없음 - ID: " . $id);
            return null;
        }
        
        // 필수 필드 검증 및 기본값 설정
        if (!isset($qna['title']) || $qna['title'] === null || trim($qna['title']) === '') {
            error_log("QnA 데이터 불완전: title이 비어있음 - ID: " . $id);
            // title이 비어있으면 기본값 설정 (데이터 복구 시도)
            $qna['title'] = '(제목 없음)';
        }
        
        if (!isset($qna['content']) || $qna['content'] === null || trim($qna['content']) === '') {
            error_log("QnA 데이터 불완전: content가 비어있음 - ID: " . $id);
            // content가 비어있으면 기본값 설정 (데이터 복구 시도)
            $qna['content'] = '(내용 없음)';
        }
        
        // user_id 체크 (관리자는 체크하지 않음)
        if ($user_id !== null && isset($qna['user_id']) && $qna['user_id'] != $user_id) {
            error_log("QnA 조회 실패: 권한 없음 - ID: " . $id . ", 요청 user_id: " . $user_id . ", 실제 user_id: " . $qna['user_id']);
            return null;
        }
        
        return $qna;
    } catch (Exception $e) {
        error_log("QnA 조회 실패: " . $e->getMessage() . " - ID: " . $id);
        return null;
    }
}

// Q&A 질문 생성
function createQna($user_id, $title, $content) {
    try {
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
        if (!$pdo) {
            error_log("QnA 생성 실패: DB 연결 실패");
            return false;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO qna (id, user_id, title, content, status, created_at, updated_at)
            VALUES (:id, :user, :title, :content, 'pending', :ca, :ua)
        ");
        
        $result = $stmt->execute([
            ':id' => $qna['id'],
            ':user' => (string)$user_id,
            ':title' => (string)$title,
            ':content' => (string)$content,
            ':ca' => $qna['created_at'],
            ':ua' => $qna['updated_at'],
        ]);
        
        if (!$result) {
            error_log("QnA 생성 실패: INSERT 실행 실패 - " . implode(', ', $stmt->errorInfo()));
            return false;
        }
        
        return $qna;
    } catch (Exception $e) {
        error_log("QnA 생성 실패: " . $e->getMessage());
        return false;
    }
}

// Q&A 답변 작성 (관리자용)
function answerQna($id, $answer, $answered_by = 'admin') {
    $debugId = uniqid('debug_');
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("[{$debugId}] QnA 답변 작성 실패: DB 연결 실패 - ID: {$id}");
            return false;
        }
        
        error_log("[{$debugId}] ===== QnA 답변 작성 시작 =====");
        error_log("[{$debugId}] QnA ID: {$id}, Answer length: " . strlen($answer) . ", Answered by: {$answered_by}");
        
        // 트랜잭션 시작
        $pdo->beginTransaction();
        error_log("[{$debugId}] 트랜잭션 시작");
        
        // deleted_at 컬럼 존재 여부 확인
        $hasDeletedAt = false;
        try {
            $checkColStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
            $hasDeletedAt = $checkColStmt->fetch() !== false;
            error_log("[{$debugId}] deleted_at 컬럼 존재 여부: " . ($hasDeletedAt ? 'YES' : 'NO'));
        } catch (Exception $e) {
            error_log("[{$debugId}] deleted_at 컬럼 확인 실패: " . $e->getMessage());
        }
        
        // 삭제되지 않은 QnA만 조회 (FOR UPDATE로 잠금)
        if ($hasDeletedAt) {
            $checkStmt = $pdo->prepare("SELECT id, status, deleted_at, title FROM qna WHERE id = :id AND deleted_at IS NULL LIMIT 1 FOR UPDATE");
        } else {
            $checkStmt = $pdo->prepare("SELECT id, status, title FROM qna WHERE id = :id LIMIT 1 FOR UPDATE");
        }
        $checkStmt->execute([':id' => (string)$id]);
        $existingQna = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingQna) {
            $pdo->rollBack();
            error_log("[{$debugId}] QnA 답변 작성 실패: QnA를 찾을 수 없거나 삭제됨 - ID: {$id}");
            
            // 삭제되었는지 확인
            if ($hasDeletedAt) {
                $checkDeletedStmt = $pdo->prepare("SELECT id, deleted_at FROM qna WHERE id = :id LIMIT 1");
                $checkDeletedStmt->execute([':id' => (string)$id]);
                $deletedCheck = $checkDeletedStmt->fetch(PDO::FETCH_ASSOC);
                if ($deletedCheck) {
                    error_log("[{$debugId}] QnA가 삭제된 상태임 - deleted_at: " . ($deletedCheck['deleted_at'] ?? 'NULL'));
                } else {
                    error_log("[{$debugId}] QnA가 존재하지 않음");
                }
            }
            return false;
        }
        
        error_log("[{$debugId}] QnA 조회 성공 - Title: " . ($existingQna['title'] ?? 'NULL') . ", Status: " . ($existingQna['status'] ?? 'NULL'));
        
        // deleted_at이 설정되어 있는지 다시 한 번 확인 (이중 체크)
        if ($hasDeletedAt && isset($existingQna['deleted_at']) && !empty($existingQna['deleted_at'])) {
            $pdo->rollBack();
            error_log("[{$debugId}] QnA 답변 작성 실패: 이미 삭제된 항목 - ID: {$id}, deleted_at: " . $existingQna['deleted_at']);
            return false;
        }
        
        if ($hasDeletedAt) {
            error_log("[{$debugId}] deleted_at 확인: " . ($existingQna['deleted_at'] ?? 'NULL'));
        }
        
        // 답변 내용 정리
        $answer = trim($answer);
        $isEmptyAnswer = empty($answer);
        error_log("[{$debugId}] 답변 내용 정리 완료 - IsEmpty: " . ($isEmptyAnswer ? 'YES' : 'NO'));
        
        // UPDATE 쿼리 구성
        // 중요: deleted_at은 절대 SET 절에 포함하지 않음
        // WHERE 절에 deleted_at 조건을 추가하여 삭제된 항목은 업데이트하지 않음
        if ($isEmptyAnswer) {
            error_log("[{$debugId}] 빈 답변 처리 모드");
            // 빈 답변: 답변 삭제 (status를 pending으로 변경)
            if ($hasDeletedAt) {
                $stmt = $pdo->prepare("
                    UPDATE qna
                    SET answer = NULL,
                        answered_at = NULL,
                        answered_by = NULL,
                        status = 'pending',
                        updated_at = NOW()
                    WHERE id = :id
                        AND deleted_at IS NULL
                ");
            } else {
                $stmt = $pdo->prepare("
                    UPDATE qna
                    SET answer = NULL,
                        answered_at = NULL,
                        answered_by = NULL,
                        status = 'pending',
                        updated_at = NOW()
                    WHERE id = :id
                ");
            }
            $params = [':id' => (string)$id];
        } else {
            error_log("[{$debugId}] 답변 작성/수정 모드");
            // 답변 작성/수정
            if ($hasDeletedAt) {
                $stmt = $pdo->prepare("
                    UPDATE qna
                    SET answer = :answer,
                        answered_at = NOW(),
                        answered_by = :by,
                        status = 'answered',
                        updated_at = NOW()
                    WHERE id = :id
                        AND deleted_at IS NULL
                ");
                error_log("[{$debugId}] UPDATE 쿼리 준비 (deleted_at 조건 포함)");
            } else {
                $stmt = $pdo->prepare("
                    UPDATE qna
                    SET answer = :answer,
                        answered_at = NOW(),
                        answered_by = :by,
                        status = 'answered',
                        updated_at = NOW()
                    WHERE id = :id
                ");
                error_log("[{$debugId}] UPDATE 쿼리 준비 (deleted_at 조건 없음)");
            }
            $params = [
                ':id' => (string)$id,
                ':answer' => (string)$answer,
                ':by' => (string)$answered_by
            ];
        }
        
        // UPDATE 실행 전 상태 확인
        if ($hasDeletedAt) {
            $beforeUpdateStmt = $pdo->prepare("SELECT id, deleted_at, answer, status FROM qna WHERE id = :id LIMIT 1");
            $beforeUpdateStmt->execute([':id' => (string)$id]);
            $beforeUpdate = $beforeUpdateStmt->fetch(PDO::FETCH_ASSOC);
            error_log("[{$debugId}] UPDATE 실행 전 상태 - deleted_at: " . ($beforeUpdate['deleted_at'] ?? 'NULL') . ", answer: " . (!empty($beforeUpdate['answer']) ? 'EXISTS' : 'NULL') . ", status: " . ($beforeUpdate['status'] ?? 'NULL'));
        }
        
        // UPDATE 실행
        error_log("[{$debugId}] UPDATE 쿼리 실행 시작");
        $result = $stmt->execute($params);
        
        if (!$result) {
            $pdo->rollBack();
            error_log("[{$debugId}] QnA 답변 작성 실패: UPDATE 실행 실패 - " . implode(', ', $stmt->errorInfo()));
            return false;
        }
        
        $rowCount = $stmt->rowCount();
        error_log("[{$debugId}] UPDATE 실행 완료 - 영향받은 행 수: {$rowCount}");
        
        if ($rowCount === 0) {
            $pdo->rollBack();
            error_log("[{$debugId}] QnA 답변 작성 실패: 업데이트된 행이 없음 (이미 삭제되었을 수 있음) - ID: {$id}");
            
            // 삭제되었는지 확인
            if ($hasDeletedAt) {
                $checkDeletedStmt = $pdo->prepare("SELECT id, deleted_at FROM qna WHERE id = :id LIMIT 1");
                $checkDeletedStmt->execute([':id' => (string)$id]);
                $deletedCheck = $checkDeletedStmt->fetch(PDO::FETCH_ASSOC);
                if ($deletedCheck) {
                    error_log("[{$debugId}] UPDATE 실패 후 확인 - deleted_at: " . ($deletedCheck['deleted_at'] ?? 'NULL'));
                }
            }
            return false;
        }
        
        // 업데이트 후 최종 확인: deleted_at이 변경되지 않았는지 확인
        if ($hasDeletedAt) {
            error_log("[{$debugId}] 업데이트 후 검증 시작");
            $verifyStmt = $pdo->prepare("SELECT id, deleted_at, answer, status FROM qna WHERE id = :id LIMIT 1");
            $verifyStmt->execute([':id' => (string)$id]);
            $verified = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$verified) {
                $pdo->rollBack();
                error_log("[{$debugId}] QnA 답변 작성 실패: 업데이트 후 QnA를 찾을 수 없음 - ID: {$id}");
                error_log("[{$debugId}] ===== QnA 답변 작성 실패 (QnA 없음) =====");
                return false;
            }
            
            error_log("[{$debugId}] 업데이트 후 상태 - deleted_at: " . ($verified['deleted_at'] ?? 'NULL') . ", answer: " . (!empty($verified['answer']) ? 'EXISTS(' . strlen($verified['answer']) . ' chars)' : 'NULL') . ", status: " . ($verified['status'] ?? 'NULL'));
            
            // deleted_at이 설정되었는지 확인
            if (isset($verified['deleted_at']) && !empty($verified['deleted_at'])) {
                $pdo->rollBack();
                error_log("[{$debugId}] QnA 답변 작성 실패: 업데이트 후 deleted_at이 설정됨 - ID: {$id}, deleted_at: " . $verified['deleted_at']);
                error_log("[{$debugId}] ===== QnA 답변 작성 실패 (deleted_at 설정됨) =====");
                return false;
            }
            
            // 답변이 제대로 저장되었는지 확인
            if (!$isEmptyAnswer) {
                if (empty($verified['answer']) || trim($verified['answer']) !== trim($answer)) {
                    $pdo->rollBack();
                    error_log("[{$debugId}] QnA 답변 작성 실패: 답변이 제대로 저장되지 않음 - ID: {$id}");
                    error_log("[{$debugId}] ===== QnA 답변 작성 실패 (답변 저장 실패) =====");
                    return false;
                }
                if (trim($verified['status']) !== 'answered') {
                    $pdo->rollBack();
                    error_log("[{$debugId}] QnA 답변 작성 실패: status가 'answered'가 아님 - ID: {$id}, status: " . $verified['status']);
                    error_log("[{$debugId}] ===== QnA 답변 작성 실패 (status 불일치) =====");
                    return false;
                }
            }
            
            error_log("[{$debugId}] 업데이트 후 검증 통과");
        }
        
        // 트랜잭션 커밋
        error_log("[{$debugId}] 트랜잭션 커밋 시작");
        $pdo->commit();
        error_log("[{$debugId}] 트랜잭션 커밋 완료");
        
        error_log("[{$debugId}] QnA 답변 작성 성공 - ID: {$id}, Action: " . ($isEmptyAnswer ? '답변 삭제' : '답변 작성'));
        error_log("[{$debugId}] ===== QnA 답변 작성 완료 =====");
        
        return true;
    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log("QnA 답변 작성 실패: " . $e->getMessage() . " - ID: " . $id);
        return false;
    }
}

// Q&A 삭제 (소프트 삭제 - 실제로 삭제하지 않고 deleted_at만 설정)
function deleteQna($id, $user_id = null) {
    $debugId = uniqid('delete_');
    try {
        // 스택 트레이스로 호출 위치 확인
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 5);
        $caller = '';
        if (isset($backtrace[1])) {
            $caller = ($backtrace[1]['file'] ?? 'unknown') . ':' . ($backtrace[1]['line'] ?? 'unknown') . ' in ' . ($backtrace[1]['function'] ?? 'unknown');
        }
        
        error_log("[{$debugId}] ===== QnA 삭제 시작 =====");
        error_log("[{$debugId}] QnA ID: {$id}, User ID: " . ($user_id ?? 'NULL'));
        error_log("[{$debugId}] 호출 위치: {$caller}");
        
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("[{$debugId}] QnA 삭제 실패: DB 연결 실패 - ID: {$id}");
            return false;
        }
        
        // deleted_at 컬럼 존재 여부 확인
        $hasDeletedAt = false;
        try {
            $checkColStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
            $hasDeletedAt = $checkColStmt->fetch() !== false;
            error_log("[{$debugId}] deleted_at 컬럼 존재 여부: " . ($hasDeletedAt ? 'YES' : 'NO'));
        } catch (Exception $e) {
            error_log("[{$debugId}] deleted_at 컬럼 확인 실패: " . $e->getMessage());
        }
        
        // 삭제 전 데이터 확인 및 로깅
        if ($hasDeletedAt) {
            $checkStmt = $pdo->prepare("SELECT id, title, user_id, status, answer, deleted_at FROM qna WHERE id = :id AND deleted_at IS NULL LIMIT 1");
        } else {
            $checkStmt = $pdo->prepare("SELECT id, title, user_id, status, answer FROM qna WHERE id = :id LIMIT 1");
        }
        $checkStmt->execute([':id' => (string)$id]);
        $existingQna = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$existingQna) {
            error_log("[{$debugId}] QnA 삭제 실패: QnA를 찾을 수 없거나 이미 삭제됨 - ID: {$id}");
            
            // 이미 삭제되었는지 확인
            if ($hasDeletedAt) {
                $checkDeletedStmt = $pdo->prepare("SELECT id, deleted_at FROM qna WHERE id = :id LIMIT 1");
                $checkDeletedStmt->execute([':id' => (string)$id]);
                $deletedCheck = $checkDeletedStmt->fetch(PDO::FETCH_ASSOC);
                if ($deletedCheck && isset($deletedCheck['deleted_at']) && !empty($deletedCheck['deleted_at'])) {
                    error_log("[{$debugId}] QnA가 이미 삭제된 상태 - deleted_at: " . $deletedCheck['deleted_at']);
                }
            }
            return false;
        }
        
        error_log("[{$debugId}] QnA 조회 성공 - Title: " . ($existingQna['title'] ?? 'NULL') . ", Status: " . ($existingQna['status'] ?? 'NULL') . ", Answer: " . (!empty($existingQna['answer']) ? 'EXISTS' : 'NULL'));
        
        // 권한 확인
        if ($user_id !== null && $existingQna['user_id'] != $user_id) {
            error_log("[{$debugId}] QnA 삭제 실패: 권한 없음 - ID: {$id}, 요청 user_id: {$user_id}, 실제 user_id: " . ($existingQna['user_id'] ?? 'NULL'));
            return false;
        }
        
        // 소프트 삭제: deleted_at만 설정 (실제 데이터는 유지)
        if ($hasDeletedAt) {
            // 소프트 삭제 사용
            if ($user_id !== null) {
                $stmt = $pdo->prepare("UPDATE qna SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND user_id = :user AND deleted_at IS NULL");
                error_log("[{$debugId}] 소프트 삭제 실행 (user_id 조건 포함)");
                $stmt->execute([':id' => (string)$id, ':user' => (string)$user_id]);
            } else {
                $stmt = $pdo->prepare("UPDATE qna SET deleted_at = NOW(), updated_at = NOW() WHERE id = :id AND deleted_at IS NULL");
                error_log("[{$debugId}] 소프트 삭제 실행 (user_id 조건 없음)");
                $stmt->execute([':id' => (string)$id]);
            }
        } else {
            // deleted_at 컬럼이 없으면 실제 삭제 (하위 호환성)
            if ($user_id !== null) {
                $stmt = $pdo->prepare("DELETE FROM qna WHERE id = :id AND user_id = :user");
                error_log("[{$debugId}] 영구 삭제 실행 (user_id 조건 포함)");
                $stmt->execute([':id' => (string)$id, ':user' => (string)$user_id]);
            } else {
                $stmt = $pdo->prepare("DELETE FROM qna WHERE id = :id");
                error_log("[{$debugId}] 영구 삭제 실행 (user_id 조건 없음)");
                $stmt->execute([':id' => (string)$id]);
            }
        }
        
        $rowCount = $stmt->rowCount();
        error_log("[{$debugId}] 삭제 쿼리 실행 완료 - 영향받은 행 수: {$rowCount}");
        
        if ($rowCount > 0) {
            error_log("[{$debugId}] QnA 삭제 성공 - ID: {$id}, Title: " . ($existingQna['title'] ?? 'NULL') . ", Type: " . ($hasDeletedAt ? '소프트 삭제' : '영구 삭제'));
            error_log("[{$debugId}] ===== QnA 삭제 완료 =====");
            return true;
        } else {
            error_log("[{$debugId}] QnA 삭제 실패: 업데이트된 행이 없음 - ID: {$id}");
            error_log("[{$debugId}] ===== QnA 삭제 실패 =====");
            return false;
        }
    } catch (Exception $e) {
        error_log("[{$debugId}] QnA 삭제 실패: " . $e->getMessage() . " - ID: {$id}");
        error_log("[{$debugId}] 스택 트레이스: " . $e->getTraceAsString());
        error_log("[{$debugId}] ===== QnA 삭제 실패 (예외) =====");
        return false;
    }
}

// Q&A 복구 (소프트 삭제된 항목 복구)
function restoreQna($id) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("QnA 복구 실패: DB 연결 실패 - ID: " . $id);
            return false;
        }
        
        $stmt = $pdo->prepare("UPDATE qna SET deleted_at = NULL, updated_at = NOW() WHERE id = :id AND deleted_at IS NOT NULL");
        $stmt->execute([':id' => (string)$id]);
        
        if ($stmt->rowCount() > 0) {
            error_log("QnA 복구 성공 - ID: " . $id);
            return true;
        } else {
            error_log("QnA 복구 실패: 복구할 항목이 없음 - ID: " . $id);
            return false;
        }
    } catch (Exception $e) {
        error_log("QnA 복구 실패: " . $e->getMessage() . " - ID: " . $id);
        return false;
    }
}

// 영구 삭제 (실제로 DB에서 삭제 - 관리자만 사용)
function permanentlyDeleteQna($id) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("QnA 영구 삭제 실패: DB 연결 실패 - ID: " . $id);
            return false;
        }
        
        // 삭제 전 데이터 백업 (로깅)
        $checkStmt = $pdo->prepare("SELECT * FROM qna WHERE id = :id LIMIT 1");
        $checkStmt->execute([':id' => (string)$id]);
        $qnaData = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($qnaData) {
            error_log("QnA 영구 삭제 - ID: " . $id . ", Title: " . ($qnaData['title'] ?? 'NULL') . ", Data: " . json_encode($qnaData));
        }
        
        $stmt = $pdo->prepare("DELETE FROM qna WHERE id = :id");
        $stmt->execute([':id' => (string)$id]);
        
        if ($stmt->rowCount() > 0) {
            error_log("QnA 영구 삭제 성공 - ID: " . $id);
            return true;
        } else {
            error_log("QnA 영구 삭제 실패: 삭제된 행이 없음 - ID: " . $id);
            return false;
        }
    } catch (Exception $e) {
        error_log("QnA 영구 삭제 실패: " . $e->getMessage() . " - ID: " . $id);
        return false;
    }
}

// 모든 Q&A 가져오기 (관리자용, 소프트 삭제 제외)
function getAllQnaForAdmin() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    // deleted_at 컬럼 존재 여부 확인
    $hasDeletedAt = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
        $hasDeletedAt = $checkStmt->fetch() !== false;
    } catch (Exception $e) {
        // 컬럼이 없으면 false로 유지
    }
    
    if ($hasDeletedAt) {
        $stmt = $pdo->query("SELECT * FROM qna WHERE deleted_at IS NULL ORDER BY created_at DESC");
    } else {
        $stmt = $pdo->query("SELECT * FROM qna ORDER BY created_at DESC");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// 삭제된 Q&A 가져오기 (관리자용, 복구용)
function getDeletedQnaForAdmin() {
    $pdo = getDBConnection();
    if (!$pdo) return [];
    
    // deleted_at 컬럼 존재 여부 확인
    $hasDeletedAt = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
        $hasDeletedAt = $checkStmt->fetch() !== false;
    } catch (Exception $e) {
        // 컬럼이 없으면 빈 배열 반환
        return [];
    }
    
    if ($hasDeletedAt) {
        // DATETIME 컬럼은 빈 문자열 비교 불가 - IS NOT NULL만 사용
        $stmt = $pdo->query("SELECT * FROM qna WHERE deleted_at IS NOT NULL ORDER BY deleted_at DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } else {
        // deleted_at 컬럼이 없으면 삭제된 항목 없음
        return [];
    }
}

// 답변 대기 중인 Q&A 개수 (관리자용, 소프트 삭제 제외)
function getPendingQnaCount() {
    $pdo = getDBConnection();
    if (!$pdo) return 0;
    
    // deleted_at 컬럼 존재 여부 확인
    $hasDeletedAt = false;
    try {
        $checkStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'deleted_at'");
        $hasDeletedAt = $checkStmt->fetch() !== false;
    } catch (Exception $e) {
        // 컬럼이 없으면 false로 유지
    }
    
    if ($hasDeletedAt) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM qna WHERE status = 'pending' AND deleted_at IS NULL");
    } else {
        $stmt = $pdo->query("SELECT COUNT(*) FROM qna WHERE status = 'pending'");
    }
    return (int)($stmt->fetchColumn() ?? 0);
}

// 관리자가 Q&A 조회 시 admin_viewed_at 업데이트
function markQnaAsViewedByAdmin($id) {
    try {
        $pdo = getDBConnection();
        if (!$pdo) {
            error_log("QnA 관리자 조회 표시 실패: DB 연결 실패 - ID: " . $id);
            return false;
        }
        
        // admin_viewed_at 컬럼 존재 여부 확인
        $hasAdminViewedAt = false;
        try {
            $checkStmt = $pdo->query("SHOW COLUMNS FROM qna LIKE 'admin_viewed_at'");
            $hasAdminViewedAt = $checkStmt->fetch() !== false;
        } catch (Exception $e) {
            // 컬럼이 없으면 false로 유지
        }
        
        if (!$hasAdminViewedAt) {
            // 컬럼이 없으면 업데이트하지 않음 (에러 아님)
            return true;
        }
        
        // admin_viewed_at이 NULL인 경우에만 업데이트 (중복 업데이트 방지)
        $stmt = $pdo->prepare("UPDATE qna SET admin_viewed_at = NOW(), updated_at = NOW() WHERE id = :id AND admin_viewed_at IS NULL");
        $stmt->execute([':id' => (string)$id]);
        
        if ($stmt->rowCount() > 0) {
            error_log("QnA 관리자 조회 표시 성공 - ID: " . $id);
            return true;
        }
        
        return true; // 이미 조회된 경우도 성공으로 처리
    } catch (Exception $e) {
        error_log("QnA 관리자 조회 표시 실패: " . $e->getMessage() . " - ID: " . $id);
        return false;
    }
}

