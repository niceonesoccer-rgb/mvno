<?php
/**
 * 5년 경과한 탈퇴자의 개인정보 삭제 처리 스케줄러
 * 
 * 사용법:
 * - 매일 실행: cron job 또는 스케줄러로 설정
 * - 수동 실행: php process-scheduled-deletions.php
 * 
 * 개인정보보호법에 따라 탈퇴 후 5년 경과 시 개인정보를 삭제합니다.
 */

require_once __DIR__ . '/includes/data/auth-functions.php';

echo "=== 5년 경과 탈퇴자 개인정보 삭제 처리 ===\n\n";

$result = processScheduledDeletions();

echo "처리 결과:\n";
echo "- 처리된 건수: {$result['processed']}건\n";
echo "- 오류 건수: {$result['errors']}건\n\n";

if ($result['processed'] > 0) {
    echo "✅ {$result['processed']}명의 탈퇴자 개인정보가 삭제 처리되었습니다.\n";
} else {
    echo "ℹ️ 처리할 탈퇴자가 없습니다.\n";
}

if ($result['errors'] > 0) {
    echo "⚠️ 오류가 발생했습니다. 로그를 확인해주세요.\n";
    exit(1);
}

exit(0);
