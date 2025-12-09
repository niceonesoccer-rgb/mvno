-- devices 테이블의 color 필드를 TEXT로 변경하고 color_values 필드 추가
USE `mvno_db`;

-- color 필드를 TEXT로 변경
ALTER TABLE `devices` 
MODIFY COLUMN `color` TEXT DEFAULT NULL COMMENT '색상 (쉼표로 구분 또는 JSON)';

-- color_values 필드 추가 (색상값 저장용)
ALTER TABLE `devices` 
ADD COLUMN `color_values` TEXT DEFAULT NULL COMMENT '색상값 (JSON 형태: [{"name":"블랙","value":"#000000"}]' 
AFTER `color`;

