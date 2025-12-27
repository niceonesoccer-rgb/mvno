# 인터넷 리뷰 기능 추가

## 개요
인터넷 상품에 대한 리뷰 작성 기능을 추가했습니다. 이제 통신사폰(MNO), 알뜰폰(MVNO), 인터넷(Internet) 세 가지 카테고리 모두 리뷰를 작성할 수 있습니다.

## 데이터베이스 업데이트

### 1. product_reviews 테이블 수정
`product_reviews` 테이블의 `product_type` ENUM에 'internet' 타입을 추가해야 합니다.

**실행 방법:**

#### 방법 1: phpMyAdmin 사용 (권장)
1. 브라우저에서 `http://localhost/phpmyadmin` 접속
2. 왼쪽에서 `mvno_db` 데이터베이스 선택
3. 상단 "SQL" 탭 클릭
4. 다음 SQL 쿼리 실행:
```sql
ALTER TABLE `product_reviews` 
MODIFY COLUMN `product_type` ENUM('mvno', 'mno', 'internet') NOT NULL COMMENT '상품 타입';
```

#### 방법 2: SQL 파일 실행
`database/alter_product_reviews_add_internet.sql` 파일을 phpMyAdmin에서 import하거나 직접 실행하세요.

## 기능 설명

### 1. 리뷰 작성 조건
`internet-order.php` 페이지에서 진행상황이 다음 중 하나일 때 리뷰를 작성할 수 있습니다:
- **개통중**: `activating`, `processing`
- **설치완료**: `installation_completed`, `completed`
- **종료**: `closed`, `terminated`

### 2. 판매자별 리뷰 분류
판매자가 판매하는 상품들을 3가지 카테고리로 분류하여 리뷰를 표시합니다:

#### 통신사폰 (MNO)
- 판매자가 판매하는 모든 통신사폰 상품의 리뷰가 통합되어 표시됩니다.
- 예: 상품1, 상품2, 상품3의 리뷰가 모두 "판매자 통신사폰 리뷰" 섹션에 표시

#### 알뜰폰 (MVNO)
- 판매자가 판매하는 모든 알뜰폰 상품의 리뷰가 통합되어 표시됩니다.
- 예: 상품4, 상품5의 리뷰가 모두 "판매자 알뜰폰 리뷰" 섹션에 표시

#### 인터넷 (Internet)
- 판매자가 판매하는 모든 인터넷 상품의 리뷰가 통합되어 표시됩니다.
- 예: 상품6, 상품7, 상품8의 리뷰가 모두 "판매자 인터넷 리뷰" 섹션에 표시

### 3. 리뷰 통합 표시 로직
다음 함수들이 같은 판매자의 같은 타입의 모든 상품 리뷰를 자동으로 통합합니다:
- `getProductReviews($productId, $productType)`: 리뷰 목록 가져오기
- `getProductAverageRating($productId, $productType)`: 평균 별점 계산
- `getProductReviewCount($productId, $productType)`: 리뷰 개수 계산

## 수정된 파일 목록

1. **데이터베이스**
   - `database/alter_product_reviews_add_internet.sql`: 스키마 수정 SQL

2. **백엔드 함수**
   - `includes/data/product-functions.php`: `addProductReview()` 함수 수정
   - `includes/data/plan-data.php`: `getProductReviews()` 함수 주석 업데이트
   - `includes/data/review-settings.php`: 리뷰 작성 가능 상태 추가

3. **API**
   - `api/submit-review.php`: 인터넷 리뷰 제출 지원 추가

4. **프론트엔드**
   - `mypage/internet-order.php`: 리뷰 작성 버튼 및 모달 추가

## 사용 방법

1. **데이터베이스 업데이트 실행** (위의 "데이터베이스 업데이트" 섹션 참조)

2. **인터넷 주문 페이지에서 리뷰 작성**
   - `http://localhost/MVNO/mypage/internet-order.php` 접속
   - 진행상황이 "개통중", "설치완료", "종료"인 주문에 "리뷰 작성" 버튼이 표시됩니다.
   - 버튼을 클릭하여 리뷰 작성 모달을 열고 리뷰를 작성할 수 있습니다.

3. **판매자 리뷰 확인**
   - 상품 상세 페이지에서 해당 판매자의 같은 타입의 모든 상품 리뷰가 통합되어 표시됩니다.
   - 예: 통신사폰 상품 상세 페이지에서는 해당 판매자가 판매하는 모든 통신사폰 상품의 리뷰가 표시됩니다.

## 주의사항

- 데이터베이스 스키마를 업데이트하지 않으면 인터넷 리뷰 작성 시 오류가 발생할 수 있습니다.
- 리뷰는 작성 후 관리자 승인을 거쳐야 표시됩니다 (status = 'approved').








