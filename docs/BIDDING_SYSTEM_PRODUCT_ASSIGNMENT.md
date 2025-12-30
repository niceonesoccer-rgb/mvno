# 낙찰 후 상품 등록/선택 프로세스

## 📌 개요

낙찰자가 게시물을 상단에 노출하기 위해서는 **낙찰 후 게시 기간 시작 전까지 상품을 선택하거나 등록**해야 합니다.

---

## 🎯 두 가지 방법

### 방법 1: 기존 등록 상품 중 선택 (권장)

낙찰자가 이미 등록한 상품 중에서 선택하는 방식입니다.

**장점:**
- 기존 상품 정보를 활용
- 빠른 배정 가능
- 상품 정보 수정 가능

### 방법 2: 새 상품 등록 후 선택

낙찰 후 새로운 상품을 등록한 후 선택하는 방식입니다.

**장점:**
- 입찰에 특화된 상품 등록 가능
- 최신 정보로 상품 등록

---

## 📋 프로세스 상세

### Phase 1: 낙찰 알림

```
1. 관리자가 낙찰 처리 완료
2. 낙찰자에게 알림 발송
   - 알림 내용:
     * 낙찰 결과 알림
     * 게시물 선택 기한 안내
     * 게시 기간 시작일시 안내
```

### Phase 2: 상품 선택/등록

#### 시나리오 A: 기존 상품 중 선택

```
1. 낙찰자가 "내 입찰 현황" 페이지 접속
2. 낙찰된 입찰 항목 확인
3. "게시물 선택" 버튼 클릭
4. 내가 등록한 상품 목록 표시
   - 필터: 해당 카테고리 상품만
   - 필터: status='active'인 상품만
5. 상품 선택 (1개만)
6. 선택 확인
7. bidding_product_assignments 테이블에 기록
```

#### 시나리오 B: 새 상품 등록 후 선택

```
1. 낙찰자가 "내 입찰 현황" 페이지 접속
2. 낙찰된 입찰 항목 확인
3. "새 상품 등록" 버튼 클릭
4. 일반 상품 등록 프로세스 진행
   - 카테고리는 입찰 카테고리로 고정
   - 상품 등록 완료
5. 등록 완료 후 "게시물 선택" 페이지로 이동
6. 방금 등록한 상품 선택
7. bidding_product_assignments 테이블에 기록
```

---

## 🔐 권한 및 검증

### 게시물 선택 조건

```
1. 낙찰자 본인만 선택 가능
2. status = 'won'인 입찰에 대해서만 선택 가능
3. 해당 카테고리 상품만 선택 가능
4. 상품 status = 'active'인 것만 선택 가능
5. 게시 기간 시작 전까지 선택 가능
6. 이미 선택된 상품은 다른 낙찰자가 선택 불가
```

### 게시물 등록 조건

```
1. 낙찰자 본인만 등록 가능
2. 해당 카테고리에 권한이 있어야 함
3. 일반 상품 등록 프로세스와 동일한 검증
```

---

## 💾 데이터베이스 처리

### bidding_product_assignments 테이블에 기록

```sql
INSERT INTO bidding_product_assignments (
    bidding_round_id,
    bidding_participation_id,
    product_id,
    display_order,
    bid_amount,
    assigned_at
) VALUES (
    :bidding_round_id,
    :bidding_participation_id,
    :product_id,
    :display_order,  -- 입찰 금액 순으로 자동 계산
    :bid_amount,
    NOW()
);
```

### display_order 자동 계산

```
1. 해당 라운드의 모든 낙찰자 입찰을 조회
2. bid_amount DESC, bid_at ASC 정렬
3. 순위에 따라 display_order 부여:
   - 1위 → display_order = 1
   - 2위 → display_order = 2
   - ...
   - N위 → display_order = N (N ≤ 20)
```

---

## 📱 UI/UX 설계

### 낙찰자 입찰 현황 페이지

```
┌─────────────────────────────────────┐
│ 내 입찰 현황                        │
├─────────────────────────────────────┤
│                                     │
│ [카테고리: 알뜰폰]                  │
│ 입찰 금액: 50,000원                 │
│ 입찰 상태: ✅ 낙찰                  │
│ 낙찰 순위: 3위                      │
│                                     │
│ 게시 기간: 2026-01-01 ~ 2026-01-31  │
│ 게시물 선택 기한: 2025-12-31 23:59  │
│                                     │
│ 게시물 선택 상태:                   │
│ ☐ 미선택                            │
│ ☑ 선택됨: [상품명]                  │
│                                     │
│ [게시물 선택/변경]                  │
│ [새 상품 등록]                      │
└─────────────────────────────────────┘
```

### 게시물 선택 페이지

```
┌─────────────────────────────────────┐
│ 게시물 선택                         │
├─────────────────────────────────────┤
│                                     │
│ 등록된 상품 목록 (알뜰폰)           │
│                                     │
│ ┌─────────────────────────────┐    │
│ │ ☑ 상품명: SKT 5G 슈퍼플랜   │    │
│ │    월 요금: 35,000원         │    │
│ │    데이터: 무제한            │    │
│ │    등록일: 2025-11-15        │    │
│ └─────────────────────────────┘    │
│                                     │
│ ┌─────────────────────────────┐    │
│ │ ☐ 상품명: KT 알뜰플랜       │    │
│ │    월 요금: 28,000원         │    │
│ │    데이터: 10GB             │    │
│ │    등록일: 2025-10-20        │    │
│ └─────────────────────────────┘    │
│                                     │
│ [선택] [취소]                       │
└─────────────────────────────────────┘
```

---

## 🔄 API 엔드포인트

### 1. 낙찰자 게시물 목록 조회

```
GET /api/bidding/my-participations/:participation_id/products

응답:
{
    "success": true,
    "products": [
        {
            "id": 123,
            "name": "SKT 5G 슈퍼플랜",
            "monthly_fee": 35000,
            "created_at": "2025-11-15 10:00:00",
            "status": "active"
        },
        ...
    ]
}
```

### 2. 게시물 선택

```
POST /api/bidding/assign-product

요청:
{
    "bidding_participation_id": 456,
    "product_id": 123
}

응답:
{
    "success": true,
    "message": "게시물이 선택되었습니다.",
    "assignment": {
        "id": 789,
        "bidding_round_id": 1,
        "bidding_participation_id": 456,
        "product_id": 123,
        "display_order": 3,
        "bid_amount": 50000
    }
}
```

### 3. 게시물 선택 해제 (변경)

```
DELETE /api/bidding/assign-product/:assignment_id

응답:
{
    "success": true,
    "message": "게시물 선택이 해제되었습니다."
}
```

### 4. 새 상품 등록 후 선택

```
기존 상품 등록 API 사용:
- POST /api/product-register-mvno.php
- POST /api/product-register-mno.php
- POST /api/product-register-internet.php

등록 완료 후:
- product_id 반환
- 게시물 선택 API 호출
```

---

## ⚠️ 주의사항

### 1. 게시물 선택 기한

```
- 게시 기간 시작일시 전까지 선택 필수
- 미선택 시:
  * 경고 알림 발송 (게시 시작 3일 전, 1일 전)
  * 게시 시작 시점에 자동으로 처리 필요
  * 옵션: 가장 최신 상품 자동 배정
  * 옵션: 선택 불가 상태로 표시 (관리자 알림)
```

### 2. 상품 상태 변경

```
- 게시물 선택 후 상품을 삭제하거나 비활성화하는 경우:
  * 경고 메시지 표시
  * 게시 기간 중에는 삭제/비활성화 제한
  * 또는 게시물 재선택 요구
```

### 3. 중복 선택 방지

```
- 한 상품이 여러 입찰 라운드에 동시에 선택되는 경우:
  * 가능: 다른 라운드, 다른 카테고리
  * 불가: 같은 라운드 내 다른 낙찰자
  * UNIQUE KEY 제약으로 방지
```

---

## 📊 게시물 배정 순서 자동 계산

### 로직

```php
function assignDisplayOrder($biddingRoundId) {
    // 1. 해당 라운드의 모든 낙찰 입찰 조회
    $participations = getWonParticipations($biddingRoundId);
    
    // 2. 입찰 금액 내림차순, 입찰 시간 빠른 순 정렬
    usort($participations, function($a, $b) {
        if ($a['bid_amount'] != $b['bid_amount']) {
            return $b['bid_amount'] - $a['bid_amount']; // DESC
        }
        return strtotime($a['bid_at']) - strtotime($b['bid_at']); // ASC
    });
    
    // 3. display_order 부여
    foreach ($participations as $index => $participation) {
        $displayOrder = $index + 1;
        
        // 이미 배정된 경우 업데이트, 없으면 새로 생성
        updateOrCreateAssignment(
            $biddingRoundId,
            $participation['id'],
            $displayOrder
        );
    }
}
```

---

## 🔄 전체 프로세스 흐름

```
[낙찰 처리 완료]
    ↓
[낙찰자 알림 발송]
    ↓
[낙찰자 "내 입찰 현황" 페이지 접속]
    ↓
[게시물 선택 또는 새 상품 등록]
    ↓
[게시물 선택 완료]
    ↓
[bidding_product_assignments 테이블에 기록]
    ↓
[display_order 자동 계산 및 업데이트]
    ↓
[게시 기간 시작]
    ↓
[상단 노출 시작]
```

---

## ✅ 요약

### 핵심 포인트

1. **낙찰 후 게시 기간 시작 전까지 상품 선택 필수**
2. **기존 상품 중 선택 또는 새 상품 등록 후 선택 가능**
3. **display_order는 입찰 금액 순으로 자동 계산**
4. **게시물 선택 기한 안내 및 알림 필요**
5. **상품 상태 관리 및 중복 선택 방지**

### 구현 우선순위

1. ✅ 낙찰자 게시물 목록 조회 API
2. ✅ 게시물 선택 API
3. ✅ 게시물 선택 해제 API
4. ✅ UI: 내 입찰 현황 페이지
5. ✅ UI: 게시물 선택 페이지
6. ✅ display_order 자동 계산 로직
7. ✅ 알림 시스템 (선택 기한 안내)

