-- ============================================
-- products 테이블의 seller_id 타입 확인
-- ============================================

USE `mvno_db`;

-- 현재 seller_id 컬럼 타입 확인
SELECT 
    COLUMN_NAME,
    DATA_TYPE,
    COLUMN_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = 'mvno_db'
  AND TABLE_NAME = 'products'
  AND COLUMN_NAME = 'seller_id';

-- products 테이블에 데이터가 있는지 확인
SELECT COUNT(*) as total_products FROM products;

-- seller_id 값 샘플 확인 (최대 10개)
SELECT DISTINCT seller_id, COUNT(*) as count
FROM products
GROUP BY seller_id
LIMIT 10;


