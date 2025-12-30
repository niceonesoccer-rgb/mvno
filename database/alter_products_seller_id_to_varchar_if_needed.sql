-- ============================================
-- products 테이블의 seller_id를 VARCHAR(50)로 변경 (필요한 경우)
-- 주의: 이 스크립트는 products 테이블에 데이터가 없을 때만 안전합니다
-- 데이터가 있다면 먼저 데이터를 백업하고 마이그레이션 계획을 세워야 합니다
-- ============================================

USE `mvno_db`;

-- 1단계: 외래 키 제약 조건 확인 및 임시 제거
-- (product_applications 등에서 products.seller_id를 참조하는 경우)

-- 2단계: seller_id 타입 변경
-- 주의: INT에서 VARCHAR로 변경 시 데이터 변환이 필요할 수 있습니다
-- ALTER TABLE `products` MODIFY COLUMN `seller_id` VARCHAR(50) NOT NULL COMMENT '판매자 user_id';

-- 3단계: 인덱스 재생성 (필요한 경우)

-- 현재는 주석 처리되어 있습니다.
-- 실제 사용하기 전에 다음을 확인하세요:
-- 1. products 테이블에 데이터가 있는지
-- 2. seller_id가 INT인지 VARCHAR인지
-- 3. 외래 키 제약 조건이 있는지
-- 4. 다른 테이블에서 seller_id를 참조하는지

-- 확인 후 필요하면 주석을 해제하고 실행하세요.


