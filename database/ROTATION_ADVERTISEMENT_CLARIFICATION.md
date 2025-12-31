# 광고 시스템 요구사항 명확화

## ✅ 최종 확정 요구사항

### 광고와 상품 상태의 관계

**핵심 원칙:**
- ✅ **광고는 광고 기간이 끝날 때까지 계속 진행됨 (광고 종료 시간은 연장되지 않음)**
- ✅ **상품이 판매종료(`inactive`/`deleted`)되면 해당 상품은 광고에서 노출되지 않음**
- ✅ **광고 종료 시간(`end_datetime`)은 광고 신청 시 결정되고 변경되지 않음**
- ✅ **상품 상태와 무관하게 광고 종료 시간은 연장되지 않음**

---

## 📋 상세 설명

### 시나리오 예시

**상황:**
- 광고 신청: 2025-12-21 15:16:15
- 광고 기간: 1일 (86400초)
- 광고 종료 시간: 2025-12-22 15:16:15
- 상품 상태: active (판매중)

**시나리오 1: 상품이 광고 기간 중에 판매종료**
- 2025-12-21 20:00:00에 상품 상태가 `inactive`로 변경됨
- **결과**: 
  - ✅ 광고는 계속 진행됨 (2025-12-22 15:16:15까지, 광고 상태는 `active` 유지)
  - ✅ 광고 종료 시간은 변경되지 않음 (연장되지 않음)
  - ✅ **하지만 상품이 `inactive`이므로 광고 목록에서 노출되지 않음**
  - ✅ 2025-12-22 15:16:15에 광고 자동 종료

**시나리오 2: 상품이 광고 기간 중에 판매종료 후 다시 활성화**
- 2025-12-21 20:00:00에 상품 상태가 `inactive`로 변경됨
  - **결과**: 광고는 계속 진행되지만 상품이 `inactive`이므로 광고 목록에서 노출되지 않음
- 2025-12-22 10:00:00에 상품 상태가 다시 `active`로 변경됨 (광고 기간이 아직 남아있음)
  - **결과**: 
    - ✅ 광고는 계속 진행됨 (2025-12-22 15:16:15까지)
    - ✅ 상품이 `active`로 복귀했으므로 광고 목록에 다시 노출됨
    - ✅ 광고 종료 시간은 변경되지 않음 (2025-12-22 15:16:15 그대로)

**시나리오 3: 상품이 광고 종료 후에 판매종료**
- 2025-12-22 20:00:00에 상품 상태가 `inactive`로 변경됨
- **결과**: 
  - ✅ 이미 광고가 종료된 상태 (2025-12-22 15:16:15에 종료)
  - ✅ 상품 상태 변경은 광고에 영향을 주지 않음

---

## 🔧 구현 로직

### 광고 신청 시 (변경 없음)

```php
// 광고 신청 시점의 현재 시간을 시작 시간으로 설정
$start_datetime = date('Y-m-d H:i:s');

// 종료 시간 계산 (광고 기간 × 86400초)
$seconds = $advertisement_days * 86400;
$end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + $seconds);

// DB에 저장 (한 번 저장되면 변경되지 않음)
INSERT INTO rotation_advertisements (
    product_id,
    start_datetime,  // 예: 2025-12-21 15:16:15
    end_datetime,    // 예: 2025-12-22 15:16:15
    status = 'active'
)
```

### 광고 조회 시 (프론트엔드)

```php
// 활성화된 광고 조회
// 광고 종료 시간 체크 + 상품이 판매중(active)인 것만 노출
SELECT 
    ra.*,
    p.status AS product_status
FROM rotation_advertisements ra
INNER JOIN products p ON ra.product_id = p.id
WHERE ra.product_type = :type 
AND ra.status = 'active' 
AND ra.start_datetime <= NOW() 
AND ra.end_datetime > NOW()  // 광고 종료 시간이 지나지 않았으면
AND p.status = 'active'  // 상품이 판매중인 것만 노출
ORDER BY ra.display_order, ra.created_at ASC
```

**중요:**
- 광고는 `end_datetime`까지 계속 진행됨 (광고 상태는 `active` 유지)
- 하지만 상품이 판매종료(`inactive`/`deleted`)되면 광고 목록에서 제외되어 노출되지 않음
- 판매종료된 상품도 광고 기간이 남아있으면, 상품을 다시 활성화(`active`)하면 광고 목록에 다시 노출됨

### 광고 만료 체크 (크론잡)

```php
// 광고 종료 시간이 지났으면 만료 처리
// 상품 상태와 무관하게 시간만 체크
SELECT * FROM rotation_advertisements 
WHERE status = 'active' 
AND end_datetime < NOW()  // 종료 시간이 지났으면 만료

UPDATE rotation_advertisements 
SET status = 'expired' 
WHERE id = :id
```

### 상품 상태 변경 시 (자동 반영)

```php
// 상품 상태 변경 시 별도 처리 불필요 (광고 목록 조회 시 자동으로 반영됨)

// 예1: 상품 판매종료
UPDATE products 
SET status = 'inactive' 
WHERE id = :product_id;
// → 광고는 계속 진행되지만, 광고 목록 조회 시 products.status = 'active' 조건으로 제외됨

// 예2: 상품 다시 활성화 (광고 기간이 남아있는 경우)
UPDATE products 
SET status = 'active' 
WHERE id = :product_id;
// → 광고 목록 조회 시 다시 노출됨 (광고 기간이 남아있으면)
```

---

## ❌ 필요 없는 로직

### 1. 광고 일시정지 기능 (제거됨)
- ~~상품 상태 변경 시 광고 일시정지~~
- ~~상품 판매중 복귀 시 광고 재개~~
- ~~`paused` 상태~~
- ~~`pause_reason` 컬럼~~

### 2. 광고 종료 시간 연장 (제거됨)
- ~~상품 판매종료 시 광고 종료 시간 연장~~
- ~~광고 기간 보상~~

---

## ✅ 최종 데이터베이스 구조

```sql
CREATE TABLE `rotation_advertisements` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `product_id` INT UNSIGNED NOT NULL,
    `seller_id` VARCHAR(50) NOT NULL,
    `product_type` ENUM('mvno', 'mno', 'internet', 'mno_sim') NOT NULL,
    `rotation_duration` INT NOT NULL,
    `advertisement_days` INT NOT NULL,
    `price` DECIMAL(12,2) NOT NULL,
    `start_datetime` DATETIME NOT NULL,  -- 광고 시작 시간 (변경 불가)
    `end_datetime` DATETIME NOT NULL,    -- 광고 종료 시간 (변경 불가)
    `status` ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    `display_order` INT NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);
```

**중요:**
- `start_datetime`, `end_datetime`는 광고 신청 시 한 번만 설정되고 변경되지 않음
- 상품 상태(`products.status`)와 무관하게 광고는 `end_datetime`까지 진행됨
- `end_datetime`이 지나면 자동으로 `expired` 상태로 변경

---

## 💡 핵심 정리

1. **광고는 광고 기간이 끝날 때까지 계속 진행됨** (광고 상태는 `active` 유지)
2. **상품이 판매종료되면 해당 상품은 광고에서 노출되지 않음**
3. **판매종료된 상품도 광고 기간이 남아있으면, 상품을 다시 활성화하면 광고가 다시 노출됨**
4. **광고 종료 시간은 변경/연장되지 않음** (광고 신청 시 결정된 시간 그대로)
5. **광고 목록 조회 시**: 광고가 활성화되어 있고 + 광고 종료 시간이 지나지 않았고 + 상품이 판매중(`active`)인 것만 노출

위 요구사항이 맞는지 확인 부탁드립니다.