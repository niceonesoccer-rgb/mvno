# 통신사유심 찜 기능 추가 마이그레이션

## 개요
통신사유심 상품에 찜 기능을 추가하기 위한 데이터베이스 마이그레이션입니다.

## 실행 방법

### 방법 1: MySQL 명령줄에서 실행
```bash
mysql -u [사용자명] -p [데이터베이스명] < database/add-mno-sim-to-favorites.sql
```

### 방법 2: phpMyAdmin에서 실행
1. phpMyAdmin에 로그인
2. `mvno_db` 데이터베이스 선택
3. "SQL" 탭 클릭
4. `database/add-mno-sim-to-favorites.sql` 파일의 내용을 복사하여 붙여넣기
5. "실행" 버튼 클릭

### 방법 3: 직접 SQL 실행
```sql
USE `mvno_db`;

ALTER TABLE `product_favorites` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet', 'mno-sim') NOT NULL COMMENT '상품 타입';
```

## 변경 사항
- `product_favorites` 테이블의 `product_type` ENUM에 `'mno-sim'` 타입 추가
- 통신사유심 상품도 찜할 수 있도록 지원

## 확인 방법
마이그레이션 후 다음 쿼리로 확인:
```sql
SHOW COLUMNS FROM `product_favorites` LIKE 'product_type';
```

결과에서 `Type` 컬럼에 `'mno-sim'`이 포함되어 있어야 합니다.


