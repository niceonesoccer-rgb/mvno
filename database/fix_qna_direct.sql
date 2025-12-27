-- QnA 테이블의 'default' user_id를 'q2222222'로 수정하는 SQL
-- 로그인 문제로 인해 잘못 저장된 데이터 복구

USE `mvno_db`;

-- 수정 전 확인
SELECT COUNT(*) as '수정 전 default 개수' FROM qna WHERE user_id = 'default';
SELECT COUNT(*) as '수정 전 q2222222 개수' FROM qna WHERE user_id = 'q2222222';

-- 수정 실행
UPDATE qna 
SET user_id = 'q2222222', updated_at = NOW() 
WHERE user_id = 'default';

-- 수정 후 확인
SELECT COUNT(*) as '수정 후 q2222222 개수' FROM qna WHERE user_id = 'q2222222';
SELECT COUNT(*) as '수정 후 default 개수' FROM qna WHERE user_id = 'default';

