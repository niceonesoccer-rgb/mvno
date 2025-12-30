# 입찰 시스템 데이터베이스 설치 가이드

## 📋 개요

입찰 시스템을 사용하기 위해 필요한 데이터베이스 테이블을 생성하는 가이드입니다.

---

## 🗂️ 생성되는 테이블

1. `bidding_rounds` - 입찰 라운드
2. `bidding_participations` - 입찰 참여
3. `bidding_product_assignments` - 낙찰자 게시물 배정
4. `seller_deposits` - 판매자 예치금 계정
5. `seller_deposit_transactions` - 예치금 거래 내역

---

## 📝 설치 방법

### 방법 1: phpMyAdmin 사용 (권장)

1. 브라우저에서 `http://localhost/phpmyadmin` 접속
2. 왼쪽에서 `mvno_db` 데이터베이스 선택
3. 상단 "SQL" 탭 클릭
4. `bidding_system_tables.sql` 파일의 내용을 복사하여 붙여넣기
5. "실행" 버튼 클릭

### 방법 2: 명령줄 사용

```bash
# Windows (XAMPP)
C:\xampp\mysql\bin\mysql.exe -u root mvno_db < database/bidding_system_tables.sql
```

---

## ⚠️ 주의사항

### seller_id 타입 불일치 문제

**문제:**
- `products` 테이블의 `seller_id`는 현재 `INT(11) UNSIGNED`로 정의되어 있습니다
- 하지만 `bidding_participations` 테이블의 `seller_id`는 `VARCHAR(50)`로 정의되어 있습니다
- 실제 코드에서는 `(string)$currentUser['user_id']`로 문자열로 처리하고 있습니다

**현재 상태:**
- MySQL은 INT와 VARCHAR 간의 비교 시 자동 타입 변환을 수행하므로 현재 코드가 작동할 수 있습니다
- 하지만 일관성을 위해 `products.seller_id`를 `VARCHAR(50)`로 변경하는 것을 권장합니다

**해결 방법:**

1. **현재 상태 확인:**
   ```sql
   -- check_products_seller_id_type.sql 실행
   -- seller_id 타입과 데이터 확인
   ```

2. **데이터가 없는 경우:**
   - `alter_products_seller_id_to_varchar_if_needed.sql` 파일의 주석을 해제하고 실행

3. **데이터가 있는 경우:**
   - 먼저 데이터 백업
   - 마이그레이션 계획 수립
   - 타입 변경 실행

**현재는 문제 없음:**
- API 코드에서 이미 `(string)` 캐스팅을 사용하고 있으므로 현재 상태로도 작동합니다
- 나중에 일관성을 위해 변경하는 것을 권장합니다

---

## ✅ 설치 확인

테이블이 정상적으로 생성되었는지 확인:

```sql
USE mvno_db;

-- 테이블 목록 확인
SHOW TABLES LIKE 'bidding%';
SHOW TABLES LIKE 'seller_deposits%';

-- 각 테이블 구조 확인
DESCRIBE bidding_rounds;
DESCRIBE bidding_participations;
DESCRIBE bidding_product_assignments;
DESCRIBE seller_deposits;
DESCRIBE seller_deposit_transactions;
```

---

## 📚 관련 문서

- [입찰 시스템 설계 문서](../docs/BIDDING_SYSTEM_DESIGN.md)
- [낙찰 후 상품 등록/선택 프로세스](../docs/BIDDING_SYSTEM_PRODUCT_ASSIGNMENT.md)


