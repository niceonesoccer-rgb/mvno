-- 모든 주문 데이터 삭제
-- 주의: 이 스크립트는 모든 주문(신청) 정보와 고객 정보를 영구적으로 삭제합니다.
-- 실행 전 백업을 권장합니다.

USE `mvno_db`;

-- 외래키 제약조건 일시적으로 비활성화 (성능 최적화)
SET FOREIGN_KEY_CHECKS = 0;

-- 모든 주문(신청) 데이터 삭제
-- application_customers는 CASCADE로 자동 삭제됩니다.
DELETE FROM `product_applications`;

-- products 테이블의 application_count 초기화
UPDATE `products` SET `application_count` = 0;

-- 외래키 제약조건 다시 활성화
SET FOREIGN_KEY_CHECKS = 1;

-- 삭제 완료 확인
SELECT COUNT(*) AS remaining_applications FROM `product_applications`;
SELECT COUNT(*) AS remaining_customers FROM `application_customers`;
