-- 판매자 문의에서 closed 상태 제거 및 answered로 변경
-- closed 상태를 answered로 변경
UPDATE seller_inquiries 
SET status = 'answered' 
WHERE status = 'closed';

-- ENUM 타입에서 closed 제거 (MySQL 8.0 이상)
-- 주의: 이 작업은 테이블 구조를 변경하므로 백업 후 실행하세요
ALTER TABLE seller_inquiries 
MODIFY COLUMN status ENUM('pending', 'answered') NOT NULL DEFAULT 'pending' 
COMMENT '상태: pending=답변대기, answered=답변완료';



