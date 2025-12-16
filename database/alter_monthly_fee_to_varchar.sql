-- ============================================
-- monthly_fee 컬럼을 DECIMAL에서 VARCHAR로 변경
-- 입력한 값을 그대로 텍스트로 저장하기 위함
-- ============================================

-- 기존 테이블의 monthly_fee 컬럼을 VARCHAR로 변경
ALTER TABLE `product_internet_details` 
MODIFY COLUMN `monthly_fee` VARCHAR(50) NOT NULL DEFAULT '' COMMENT '월 요금제 (텍스트 형식)';

-- 변경 확인
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, COLUMN_COMMENT 
-- FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_SCHEMA = DATABASE() 
-- AND TABLE_NAME = 'product_internet_details' 
-- AND COLUMN_NAME = 'monthly_fee';
