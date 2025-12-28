-- notices 테이블에서 is_published 필드 제거
-- 이 필드는 항상 1로 설정되었고, 실제 발행 여부는 publish_start_at과 publish_end_at으로 관리됩니다.

USE `mvno_db`;

-- is_published 컬럼 제거
ALTER TABLE `notices` DROP COLUMN `is_published`;






