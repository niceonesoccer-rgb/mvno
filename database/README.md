# 상품 관리 데이터베이스 스키마

## 개요
MVNO, MNO, Internet 상품을 관리하기 위한 데이터베이스 스키마입니다.

## 주요 기능
- ✅ 상품 등록 및 관리 (MVNO, MNO, Internet)
- ✅ 찜 기능
- ✅ 리뷰 기능 (MVNO, MNO만, Internet 제외)
- ✅ 공유 기능
- ✅ 신청 기능
- ✅ 신청 고객 정보 관리

## 설치 방법

### 방법 1: phpMyAdmin 사용 (권장)
1. 브라우저에서 `http://localhost/phpmyadmin` 접속
2. 왼쪽 상단 "새로 만들기" 클릭
3. 데이터베이스 이름: `mvno_db`
4. 정렬 규칙: `utf8mb4_unicode_ci`
5. "만들기" 클릭
6. 생성된 `mvno_db` 선택
7. 상단 "가져오기" 탭 클릭
8. `products_schema.sql` 파일 선택 후 "실행" 클릭

### 방법 2: 명령줄 사용
```bash
# Windows (XAMPP)
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS mvno_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
C:\xampp\mysql\bin\mysql.exe -u root mvno_db < products_schema.sql

# 또는 install.bat 파일 실행
install.bat
```

### 방법 3: products_schema.sql 파일 직접 실행
`products_schema.sql` 파일에는 데이터베이스 생성 명령이 포함되어 있습니다.
phpMyAdmin에서 이 파일을 직접 import하면 데이터베이스와 모든 테이블이 자동으로 생성됩니다.

## 테이블 구조

### 1. products (상품 기본 정보)
모든 상품의 공통 정보를 저장합니다.
- `id`: 상품 ID
- `seller_id`: 판매자 ID
- `product_type`: 상품 타입 (mvno, mno, internet)
- `status`: 상품 상태 (active, inactive, deleted)
- `view_count`: 조회수
- `favorite_count`: 찜 수
- `review_count`: 리뷰 수 (자동 업데이트)
- `share_count`: 공유 수 (자동 업데이트)
- `application_count`: 신청 수 (자동 업데이트)

### 2. product_mvno_details (MVNO 상품 상세)
알뜰폰 상품의 상세 정보를 저장합니다.

### 3. product_mno_details (MNO 상품 상세)
통신사폰 상품의 상세 정보를 저장합니다.

### 4. product_internet_details (Internet 상품 상세)
인터넷 상품의 상세 정보를 저장합니다.

### 5. product_reviews (상품 리뷰)
**MVNO, MNO만 사용** (Internet 제외)
- `rating`: 평점 (1-5)
- `content`: 리뷰 내용
- `is_verified`: 구매 인증 여부
- `helpful_count`: 도움됨 수

### 6. product_favorites (상품 찜)
사용자가 찜한 상품 목록입니다.

### 7. product_shares (상품 공유)
상품 공유 이력을 저장합니다.
- `share_method`: 공유 방법 (kakao, facebook, twitter, link 등)

### 8. product_applications (상품 신청)
상품 신청 정보를 저장합니다.
- `application_status`: 신청 상태 (pending, processing, completed, cancelled, rejected)

### 9. application_customers (신청 고객 정보)
신청한 고객의 상세 정보를 저장합니다.
- 비회원 신청도 가능 (`user_id`는 NULL 가능)

## 자동 업데이트 트리거

다음 카운트는 자동으로 업데이트됩니다:
- `review_count`: 리뷰 추가/삭제 시
- `favorite_count`: 찜 추가/삭제 시
- `share_count`: 공유 추가 시
- `application_count`: 신청 추가/삭제 시

## 사용 예제

### 상품 등록 (MVNO)
```php
// 1. 기본 상품 정보 등록
INSERT INTO products (seller_id, product_type, status) 
VALUES (1, 'mvno', 'active');

// 2. MVNO 상세 정보 등록
INSERT INTO product_mvno_details (product_id, provider, plan_name, price_main, price_after)
VALUES (LAST_INSERT_ID(), 'KT', '5G 슈퍼플랜', 75000, 55000);
```

### 리뷰 작성 (MVNO, MNO만)
```php
INSERT INTO product_reviews (product_id, user_id, product_type, rating, content)
VALUES (1, 10, 'mvno', 5, '정말 좋은 요금제입니다!');
```

### 찜 추가
```php
INSERT INTO product_favorites (product_id, user_id, product_type)
VALUES (1, 10, 'mvno');
```

### 공유 기록
```php
INSERT INTO product_shares (product_id, user_id, product_type, share_method, ip_address)
VALUES (1, 10, 'mvno', 'kakao', '192.168.1.1');
```

### 신청 및 고객 정보
```php
// 1. 신청 등록
INSERT INTO product_applications (product_id, seller_id, product_type, application_status)
VALUES (1, 1, 'mvno', 'pending');

// 2. 고객 정보 등록
INSERT INTO application_customers (application_id, user_id, name, phone, email)
VALUES (LAST_INSERT_ID(), 10, '홍길동', '010-1234-5678', 'hong@example.com');
```

## 주의사항

1. **리뷰는 MVNO, MNO만 가능**: Internet 상품에는 리뷰를 작성할 수 없습니다.
2. **카운트 자동 업데이트**: 트리거로 자동 업데이트되므로 수동으로 변경하지 마세요.
3. **외래키 제약조건**: 상품 삭제 시 관련된 모든 데이터가 CASCADE로 삭제됩니다.

## 쿼리 예제

### 상품 목록 조회 (타입별)
```sql
SELECT p.*, 
       CASE p.product_type
           WHEN 'mvno' THEN m.plan_name
           WHEN 'mno' THEN mno.device_name
           WHEN 'internet' THEN i.registration_place
       END AS product_name
FROM products p
LEFT JOIN product_mvno_details m ON p.id = m.product_id AND p.product_type = 'mvno'
LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
LEFT JOIN product_internet_details i ON p.id = i.product_id AND p.product_type = 'internet'
WHERE p.product_type = 'mvno' AND p.status = 'active'
ORDER BY p.created_at DESC;
```

### 리뷰 통계 조회
```sql
SELECT 
    p.id,
    p.product_type,
    COUNT(r.id) AS total_reviews,
    AVG(r.rating) AS avg_rating,
    p.review_count AS cached_count
FROM products p
LEFT JOIN product_reviews r ON p.id = r.product_id AND r.status = 'approved'
WHERE p.product_type IN ('mvno', 'mno')
GROUP BY p.id;
```

### 찜한 상품 목록
```sql
SELECT p.*, f.created_at AS favorited_at
FROM products p
INNER JOIN product_favorites f ON p.id = f.product_id
WHERE f.user_id = 10
ORDER BY f.created_at DESC;
```

### 신청 고객 목록
```sql
SELECT 
    a.*,
    c.name,
    c.phone,
    c.email,
    p.product_type
FROM product_applications a
INNER JOIN application_customers c ON a.id = c.application_id
INNER JOIN products p ON a.product_id = p.id
WHERE a.seller_id = 1
ORDER BY a.created_at DESC;
```


