# 로테이션 광고 시스템 요구사항 변경 사항

## 🔄 변경된 요구사항

### 1. 광고 일시정지 기능 제거 ❌

**변경 전:**
- 상품이 판매종료(`inactive`/`deleted`) → 광고 일시정지 (`paused`)
- 상품이 판매중(`active`)으로 복귀 → 광고 재개 (`active`)

**변경 후:**
- ✅ **상품 상태와 광고는 독립적으로 운영됨**
- ✅ 상품이 판매종료되어도 광고는 계속 진행됨
- ✅ 광고는 광고 기간이 종료될 때까지 계속 노출됨

**데이터베이스 변경:**
- `status` ENUM에서 `'paused'` 제거
- `pause_reason` 컬럼 제거
- `status` ENUM: `'active'`, `'expired'`, `'cancelled'`만 유지

---

### 2. 광고 시간 초 단위 정확한 계산 ⏰

**변경 전:**
- `start_date` (DATE 타입): 광고 시작일
- `end_date` (DATE 타입): 광고 종료일
- 일 단위로만 계산

**변경 후:**
- ✅ `start_datetime` (DATETIME 타입): 광고 시작 시간 (초 단위)
- ✅ `end_datetime` (DATETIME 타입): 광고 종료 시간 (초 단위)
- ✅ 광고 신청 시점의 정확한 시간부터 시작
- ✅ 광고 기간을 초 단위로 정확히 계산

**계산 방식:**
```
시작 시간: 광고 신청 시점의 현재 시간
예: 2025-12-21 15:16:15

종료 시간: 시작 시간 + (광고 기간 × 86400초)
1일 광고: 86400초 (24시간 × 60분 × 60초)
2일 광고: 172800초
3일 광고: 259200초

예시:
- 시작: 2025-12-21 15:16:15
- 기간: 1일
- 종료: 2025-12-22 15:16:15 (정확히 86400초 후)
```

**PHP 코드 예시:**
```php
// 광고 신청 시
$start_datetime = date('Y-m-d H:i:s'); // 현재 시간 (초 단위)
$seconds = $advertisement_days * 86400; // 일수를 초로 변환
$end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + $seconds);

// 예시: 2025-12-21 15:16:15 + 1일 = 2025-12-22 15:16:15
```

---

### 3. 등록 상품별 광고 신청 📦

**확정:**
- ✅ 등록한 상품(`product_id`)별로 광고 신청
- ✅ 각 상품은 독립적으로 광고 신청 가능

---

## 📊 데이터베이스 스키마 변경

### rotation_advertisements 테이블 변경

**변경 전:**
```sql
`start_date` DATE NOT NULL COMMENT '광고 시작일',
`end_date` DATE NOT NULL COMMENT '광고 종료일',
`status` ENUM('active', 'paused', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
`pause_reason` VARCHAR(200) DEFAULT NULL COMMENT '일시정지 사유',
KEY `idx_start_end_date` (`start_date`, `end_date`),
```

**변경 후:**
```sql
`start_datetime` DATETIME NOT NULL COMMENT '광고 시작 시간 (초 단위)',
`end_datetime` DATETIME NOT NULL COMMENT '광고 종료 시간 (초 단위)',
`status` ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
-- pause_reason 컬럼 제거
KEY `idx_start_end_datetime` (`start_datetime`, `end_datetime`),
```

---

## 🔧 구현 로직 변경

### 1. 광고 신청 로직

**변경 전:**
```php
$start_date = date('Y-m-d');
$end_date = date('Y-m-d', strtotime("+{$advertisement_days} days"));
```

**변경 후:**
```php
$start_datetime = date('Y-m-d H:i:s'); // 현재 시간 (초 단위)
$seconds = $advertisement_days * 86400; // 일수를 초로 변환
$end_datetime = date('Y-m-d H:i:s', strtotime($start_datetime) + $seconds);
```

---

### 2. 광고 만료 체크 로직

**변경 전:**
```php
// 크론잡에서 매일 자정 실행
$today = date('Y-m-d');
$stmt = $pdo->prepare("
    SELECT * FROM rotation_advertisements 
    WHERE status = 'active' 
    AND end_date < :today
");
$stmt->execute([':today' => $today]);
```

**변경 후:**
```php
// 크론잡에서 1시간마다 또는 더 자주 실행 권장
$stmt = $pdo->prepare("
    SELECT * FROM rotation_advertisements 
    WHERE status = 'active' 
    AND end_datetime < NOW()
");
$stmt->execute();
```

---

### 3. 상품 상태 변경 감지 로직 제거 ❌

**변경 전:**
- 상품 상태 변경 시 광고 일시정지/재개 로직 필요
- 트리거 또는 애플리케이션 레벨에서 구현

**변경 후:**
- ✅ **상품 상태 변경 감지 로직 불필요**
- ✅ 상품 상태와 광고는 독립적으로 운영
- ✅ 광고는 `end_datetime`이 지나면 자동으로 `expired`로 변경

---

## ✅ 수정된 파일 목록

1. **ROTATION_ADVERTISEMENT_SYSTEM_DESIGN.md**
   - 광고 상태 관리 섹션 수정
   - 시스템 플로우 수정
   - 문제점 및 보강 사항 수정

2. **rotation_advertisement_schema.sql**
   - `start_date`, `end_date` → `start_datetime`, `end_datetime`로 변경
   - `status` ENUM에서 `'paused'` 제거
   - `pause_reason` 컬럼 제거
   - 인덱스명 변경: `idx_start_end_date` → `idx_start_end_datetime`

3. **ROTATION_ADVERTISEMENT_SUMMARY.md**
   - 요구사항 정리 수정

---

## 📝 구현 시 주의사항

### 1. 시간대 설정
- PHP에서 `date_default_timezone_set('Asia/Seoul')` 설정 확인
- MySQL 서버 시간대 설정 확인

### 2. 크론잡 실행 주기
- 광고 만료 체크를 정확히 하려면 1시간마다 또는 더 자주 실행 권장
- 매일 자정 실행 시: 다음날 자정까지 만료된 광고가 계속 노출될 수 있음

### 3. 광고 목록 조회 (프론트엔드)
```php
// 활성화된 광고만 조회
$stmt = $pdo->prepare("
    SELECT * FROM rotation_advertisements 
    WHERE product_type = :type 
    AND status = 'active' 
    AND start_datetime <= NOW() 
    AND end_datetime > NOW()
    ORDER BY display_order, created_at ASC
");
```

---

## 🎯 핵심 변경 사항 요약

1. ✅ **광고 일시정지 제거**: 상품 상태와 무관하게 광고 계속 진행
2. ✅ **시간 초 단위 계산**: `start_datetime`, `end_datetime`로 정확한 시간 관리
3. ✅ **등록 상품별 신청**: 각 상품별로 독립적으로 광고 신청
4. ✅ **상품 상태 변경 감지 불필요**: 광고는 시간 기반으로만 관리

위 변경 사항을 반영하여 시스템을 재설계했습니다.