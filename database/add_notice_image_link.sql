-- notices 테이블에 image_url과 link_url 필드 추가
USE `mvno_db`;

ALTER TABLE `notices` 
ADD COLUMN `image_url` VARCHAR(500) DEFAULT NULL COMMENT '공지사항 이미지 URL' AFTER `content`,
ADD COLUMN `link_url` VARCHAR(500) DEFAULT NULL COMMENT '공지사항 링크 URL' AFTER `image_url`;








