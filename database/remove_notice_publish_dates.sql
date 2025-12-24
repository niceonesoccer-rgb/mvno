-- notices 테이블에서 publish_start_at과 publish_end_at 필드 제거
-- 이 필드들은 start_at과 end_at으로 통합되었습니다.

USE `mvno_db`;

-- publish_start_at 컬럼 제거
ALTER TABLE `notices` DROP COLUMN `publish_start_at`;

-- publish_end_at 컬럼 제거
ALTER TABLE `notices` DROP COLUMN `publish_end_at`;

