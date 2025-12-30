# 입찰 시스템 테이블 삭제 가이드

## 📋 개요

광고 시스템을 구축하기 전에 기존 입찰 시스템 관련 테이블을 삭제하는 가이드입니다.

---

## 🗂️ 삭제되는 테이블 목록

1. **`bidding_rounds`** - 입찰 라운드 정보
2. **`bidding_participations`** - 입찰 참여 정보
3. **`bidding_product_assignments`** - 낙찰자 게시물 배정
4. **`seller_deposits`** - 판매자 예치금 계정
5. **`seller_deposit_transactions`** - 예치금 거래 내역

---

## ⚠️ 주의사항

### ⛔ 삭제 전 필수 확인사항

1. **데이터 백업 필수**
   - 삭제 전에 반드시 데이터베이스를 백업하세요
   - 이 작업은 **되돌릴 수 없습니다**

2. **입찰 시스템 사용 중인지 확인**
   - 현재 입찰 시스템을 사용하고 있다면 삭제하지 마세요
   - 광고 시스템으로 완전히 전환하기 전까지는 유지하는 것을 권장합니다

3. **예치금 환불 처리**
   - `seller_deposits` 테이블에 잔액이 있는 판매자가 있다면
   - 삭제 전에 반드시 환불 처리를 완료하세요

---

## 📝 삭제 방법

### 방법 1: PHP 스크립트 사용 (권장)

1. 브라우저에서 다음 URL 접속:
   ```
   http://localhost/MVNO/database/remove_bidding_system_tables.php
   ```

2. 테이블 상태 확인
   - 각 테이블의 존재 여부와 데이터 개수 확인

3. 삭제 확인
   - "DELETE"를 정확히 입력
   - 확인 버튼 클릭

4. 삭제 완료 확인
   - 모든 테이블이 "삭제됨" 상태로 변경되었는지 확인

### 방법 2: SQL 스크립트 직접 실행

1. phpMyAdmin 접속:
   ```
   http://localhost/phpmyadmin
   ```

2. `mvno_db` 데이터베이스 선택

3. SQL 탭 클릭

4. `remove_bidding_system_tables.sql` 파일 내용 복사하여 붙여넣기

5. "실행" 버튼 클릭

### 방법 3: 명령줄 사용

```bash
# Windows (XAMPP)
C:\xampp\mysql\bin\mysql.exe -u root mvno_db < database/remove_bidding_system_tables.sql
```

---

## 🔍 삭제 확인

삭제가 정상적으로 완료되었는지 확인:

```sql
USE mvno_db;

-- 입찰 관련 테이블 확인 (결과가 없어야 함)
SHOW TABLES LIKE 'bidding%';
SHOW TABLES LIKE 'seller_deposits%';

-- 예상 결과: Empty set (0.00 sec)
```

---

## 📊 삭제 순서

외래키 제약조건 때문에 다음 순서로 삭제됩니다:

1. `seller_deposit_transactions` (외래키 없음)
2. `seller_deposits` (외래키 없음)
3. `bidding_product_assignments` (bidding_rounds, bidding_participations, products 참조)
4. `bidding_participations` (bidding_rounds 참조)
5. `bidding_rounds` (최상위 테이블)

---

## 🔄 삭제 후 작업

### 1. 입찰 관련 코드 제거 (선택사항)

다음 디렉토리와 파일들을 확인하고 필요시 제거:

- `/admin/bidding/` - 관리자 입찰 관리 페이지
- `/seller/bidding/` - 판매자 입찰 페이지
- 입찰 관련 PHP 파일들

### 2. 광고 시스템 구축

입찰 시스템 삭제 후 광고 시스템을 구축할 수 있습니다:

1. `database/CATEGORY_ADVERTISEMENT_SYSTEM.md` 참고
2. 광고 시스템 테이블 생성
3. 광고 시스템 코드 구현

---

## ❓ FAQ

### Q: 삭제 후 복구할 수 있나요?
A: 아니요. 삭제된 데이터는 복구할 수 없습니다. 반드시 삭제 전에 백업하세요.

### Q: 입찰 시스템과 광고 시스템을 동시에 사용할 수 있나요?
A: 가능하지만, 같은 상품에 대해 두 시스템이 충돌할 수 있습니다. 하나의 시스템으로 통일하는 것을 권장합니다.

### Q: seller_deposits 테이블도 삭제되나요?
A: 네. 입찰 시스템의 예치금 관리용 테이블이므로 함께 삭제됩니다. 광고 시스템에서는 별도의 예치금 관리가 필요하지 않습니다.

### Q: products 테이블은 영향받나요?
A: 아니요. `products` 테이블은 삭제되지 않습니다. 다만 `bidding_product_assignments` 테이블의 외래키 참조만 제거됩니다.

---

## 📚 관련 문서

- [입찰 시스템 설치 가이드](./README_BIDDING_SYSTEM.md)
- [광고 시스템 구현 가이드](./CATEGORY_ADVERTISEMENT_SYSTEM.md)
- [광고 시스템 제안서](./PRODUCT_ADVERTISEMENT_SYSTEM_PROPOSAL.md)
