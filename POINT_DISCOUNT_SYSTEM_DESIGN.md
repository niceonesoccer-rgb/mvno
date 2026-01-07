# 상품별 포인트 할인 시스템 설계서

## 📋 목차
1. [시스템 개요](#시스템-개요)
2. [데이터베이스 설계](#데이터베이스-설계)
3. [기능 설계](#기능-설계)
4. [페이지별 구현 설계](#페이지별-구현-설계)
5. [API 설계](#api-설계)
6. [데이터 흐름](#데이터-흐름)
7. [구현 우선순위](#구현-우선순위)

---

## 시스템 개요

### 목적
- 판매자가 상품별로 포인트 사용 금액과 할인 혜택 내용을 설정
- 고객이 가입 신청 시 포인트를 사용하여 개통 시 추가 할인 혜택을 받을 수 있도록 함
- 관리자가 주문 관리 시 포인트 사용 내역과 할인 혜택 내용을 확인

### 주요 기능
1. **판매자 기능**
   - 상품 등록/수정 시 포인트 설정 및 할인 혜택 내용 입력
   - 상품별 포인트 사용 가능 여부 설정

2. **고객 기능**
   - 상품 상세 페이지에서 포인트 사용 여부 확인
   - 신청 시 포인트 사용 모달에서 할인 혜택 내용 확인
   - 포인트 사용 시 개통 시 할인 혜택 안내

3. **관리자 기능**
   - 주문 관리 페이지에서 포인트 사용 내역 확인
   - 할인 혜택 내용 확인 및 개통 시 적용

---

## 데이터베이스 설계

### 1. products 테이블 수정

#### 추가 컬럼
```sql
ALTER TABLE `products` 
ADD COLUMN `point_setting` INT(11) UNSIGNED NOT NULL DEFAULT 0 
    COMMENT '포인트 설정 (0이면 포인트 사용 불가)' 
    AFTER `application_count`,
ADD COLUMN `point_benefit_description` TEXT DEFAULT NULL 
    COMMENT '포인트 사용 시 할인 혜택 내용' 
    AFTER `point_setting`;
```

#### 컬럼 설명
- **point_setting**: 
  - 타입: INT(11) UNSIGNED
  - 기본값: 0
  - 설명: 고객이 이 상품 신청 시 사용할 수 있는 포인트 금액
  - 예시: 3000 (3000원 포인트 사용 가능)

- **point_benefit_description**:
  - 타입: TEXT
  - 기본값: NULL
  - 설명: 포인트 사용 시 제공되는 할인 혜택 내용
  - 예시: "네이버페이 5000지급 익월말", "쿠폰 3000원 지급", "추가 할인 5000원"

#### 인덱스
```sql
-- 포인트 설정이 있는 상품 조회를 위한 인덱스 (선택사항)
ALTER TABLE `products` 
ADD INDEX `idx_point_setting` (`point_setting`);
```

---

## 기능 설계

### 1. 판매자 상품 등록/수정 기능

#### 1.1 UI 구성
**위치**: 상품 등록/수정 폼 내 "판매 상태" 섹션 다음 또는 폼 하단

**섹션 제목**: "포인트 할인 혜택 설정"

**입력 필드**:
1. **포인트 설정 (원)**
   - 타입: number input
   - 필수: 아니오
   - 기본값: 0
   - 최소값: 0
   - 단위: 100원 단위
   - 플레이스홀더: "예: 3000"
   - 도움말: "고객이 이 상품 신청 시 사용할 수 있는 포인트 금액입니다. 0으로 설정하면 포인트 사용이 불가능합니다."

2. **할인 혜택 내용**
   - 타입: textarea
   - 필수: 아니오 (포인트 설정이 0보다 클 때 권장)
   - 행 수: 3줄
   - 플레이스홀더: "예: 네이버페이 5000지급 익월말"
   - 도움말: "포인트 사용 시 고객에게 제공되는 할인 혜택 내용을 입력하세요."

**안내 메시지**:
```
💡 안내:
• 포인트 설정이 0보다 크면 고객이 포인트를 사용할 수 있습니다.
• 할인 혜택 내용은 고객이 포인트 사용 모달에서 확인할 수 있습니다.
• 관리자 주문 관리 페이지에서도 할인 혜택 내용이 표시됩니다.
```

#### 1.2 유효성 검증
- 포인트 설정: 0 이상의 정수만 허용
- 할인 혜택 내용: 최대 500자 제한 (선택사항)

#### 1.3 적용 페이지
- `seller/products/mvno.php` (알뜰폰)
- `seller/products/mno.php` (통신사폰)
- `seller/products/mno-sim.php` (통신사단독유심)
- `seller/products/internet.php` (인터넷)

---

### 2. 고객 포인트 사용 기능

#### 2.1 상품 상세 페이지
**변경 사항**: 없음 (기존 신청하기 버튼 유지)

#### 2.2 포인트 사용 모달 (`includes/components/point-usage-modal.php`)

**수정 사항**:
1. **상품별 포인트 설정 조회**
   - 모달 열기 시 해당 상품의 `point_setting`과 `point_benefit_description` 조회
   - `point_setting`이 0이면 포인트 사용 불가 안내

2. **할인 혜택 내용 표시 영역 추가**
   ```
   [개통 시 혜택]
   네이버페이 5000지급 익월말
   ```
   - 배경색: 연한 초록색 (#f0fdf4)
   - 테두리: 초록색 (#86efac)
   - 아이콘: 체크마크
   - 텍스트 색상: 진한 초록색 (#047857)

3. **안내 메시지 수정**
   - 기존: "신청 시 포인트가 차감됩니다."
   - 변경: "포인트를 사용하시면 개통 시 추가 할인을 받으실 수 있습니다."

#### 2.3 포인트 사용 플로우
```
[신청하기 버튼 클릭]
    ↓
[로그인 체크]
    ↓
[상품별 포인트 설정 조회]
    ├─ point_setting = 0 → 포인트 모달 건너뛰고 기존 신청 모달 열기
    └─ point_setting > 0 → 포인트 모달 표시
        ↓
    [포인트 모달]
        - 보유 포인트 표시
        - 최대 사용 가능 포인트 표시
        - 할인 혜택 내용 표시 (있을 경우)
        - 사용할 포인트 입력
        - [확인] 버튼 클릭
            ↓
        [포인트 차감 API 호출]
            ↓
        [기존 신청 모달 열기]
```

---

### 3. 관리자 주문 관리 기능

#### 3.1 주문 목록 페이지
**표시 항목**:
- 포인트 사용 여부 표시 (아이콘 또는 배지)
- 할인 금액 표시

#### 3.2 주문 상세 페이지
**추가 표시 섹션**:
```
[포인트 사용 정보]
포인트 사용: 3,000원
할인 혜택: 네이버페이 5000지급 익월말
```

**표시 위치**: 주문 정보 섹션 내 할인 정보 영역

---

## 페이지별 구현 설계

### 1. 판매자 페이지

#### 1.1 알뜰폰 상품 등록/수정 (`seller/products/mvno.php`)

**추가할 HTML**:
```html
<!-- 포인트 설정 섹션 -->
<div class="form-section">
    <div class="form-section-title">포인트 할인 혜택 설정</div>
    
    <div class="form-group">
        <label class="form-label" for="point_setting">
            포인트 설정 (원)
            <span class="form-help-text">고객이 사용할 수 있는 포인트 금액을 입력하세요</span>
        </label>
        <input 
            type="number" 
            name="point_setting" 
            id="point_setting" 
            class="form-input" 
            value="<?php echo isset($product['point_setting']) ? htmlspecialchars($product['point_setting']) : '0'; ?>"
            min="0" 
            step="100"
            placeholder="예: 3000"
        >
        <div class="form-help">
            고객이 이 상품 신청 시 사용할 수 있는 포인트 금액입니다. 0으로 설정하면 포인트 사용이 불가능합니다.
        </div>
    </div>
    
    <div class="form-group">
        <label class="form-label" for="point_benefit_description">
            할인 혜택 내용
            <span class="form-help-text">포인트 사용 시 제공되는 혜택을 입력하세요</span>
        </label>
        <textarea 
            name="point_benefit_description" 
            id="point_benefit_description" 
            class="form-textarea" 
            rows="3"
            maxlength="500"
            placeholder="예: 네이버페이 5000지급 익월말"
        ><?php echo isset($product['point_benefit_description']) ? htmlspecialchars($product['point_benefit_description']) : ''; ?></textarea>
        <div class="form-help">
            포인트 사용 시 고객에게 제공되는 할인 혜택 내용을 입력하세요. 
            예: "네이버페이 5000지급 익월말", "쿠폰 3000원 지급", "추가 할인 5000원" 등
        </div>
    </div>
    
    <div class="form-notice" style="background: #eef2ff; padding: 12px; border-radius: 8px; margin-top: 12px;">
        <strong>💡 안내:</strong>
        <ul style="margin: 8px 0 0 20px; padding: 0; color: #4338ca;">
            <li>포인트 설정이 0보다 크면 고객이 포인트를 사용할 수 있습니다.</li>
            <li>할인 혜택 내용은 고객이 포인트 사용 모달에서 확인할 수 있습니다.</li>
            <li>관리자 주문 관리 페이지에서도 할인 혜택 내용이 표시됩니다.</li>
        </ul>
    </div>
</div>
```

**적용 위치**: "판매 상태" 섹션 다음 또는 "프로모션" 섹션 전

#### 1.2 다른 상품 타입 페이지
- `seller/products/mno.php` (통신사폰)
- `seller/products/mno-sim.php` (통신사단독유심)
- `seller/products/internet.php` (인터넷)

**동일한 섹션 추가**

---

### 2. 고객 페이지

#### 2.1 포인트 사용 모달 (`includes/components/point-usage-modal.php`)

**수정 사항**:

1. **포인트 설정 조회 로직 추가**
```php
// 상품별 포인트 설정 조회
$point_setting = 0;
$point_benefit_description = '';

if ($item_id > 0) {
    try {
        $pdo = getDBConnection();
        if ($pdo) {
            $stmt = $pdo->prepare("
                SELECT point_setting, point_benefit_description 
                FROM products 
                WHERE id = :id AND product_type = :type AND status != 'deleted'
                LIMIT 1
            ");
            $stmt->execute([':id' => $item_id, ':type' => $type]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $point_setting = intval($product['point_setting'] ?? 0);
                $point_benefit_description = $product['point_benefit_description'] ?? '';
            }
        }
    } catch (PDOException $e) {
        error_log('포인트 설정 조회 오류: ' . $e->getMessage());
    }
}
```

2. **할인 혜택 내용 표시 영역 추가**
```html
<?php if (!empty($point_benefit_description)): ?>
<!-- 할인 혜택 내용 표시 -->
<div class="point-benefit-section" style="background: #f0fdf4; border: 1px solid #86efac; border-radius: 8px; padding: 16px; margin-bottom: 20px;">
    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
            <path d="M9 12L11 14L15 10M21 12C21 16.9706 16.9706 21 12 21C7.02944 21 3 16.9706 3 12C3 7.02944 7.02944 3 12 3C16.9706 3 21 7.02944 21 12Z" stroke="#10b981" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
        <strong style="color: #065f46; font-size: 14px;">개통 시 혜택</strong>
    </div>
    <p style="color: #047857; font-size: 14px; margin: 0; line-height: 1.6;">
        <?php echo nl2br(htmlspecialchars($point_benefit_description)); ?>
    </p>
</div>
<?php endif; ?>
```

3. **안내 메시지 수정**
```php
// point-settings.php에서 가져오는 메시지 수정
'usage_message' => '포인트를 사용하시면 개통 시 추가 할인을 받으실 수 있습니다.'
```

#### 2.2 상세 페이지
**변경 사항**: 없음 (기존 신청하기 버튼 유지)

---

### 3. 관리자 페이지

#### 3.1 주문 목록 페이지

**추가 표시 항목**:
- 포인트 사용 여부 아이콘/배지
- 할인 금액 표시

**예시**:
```
[주문 번호] [고객명] [상품명] [포인트 사용: 3,000원] [상태]
```

#### 3.2 주문 상세 페이지

**추가 표시 섹션**:
```html
<?php if (!empty($order['used_point']) && $order['used_point'] > 0): ?>
<div class="order-detail-section">
    <h3 class="order-detail-section-title">포인트 사용 정보</h3>
    <div class="order-detail-item">
        <span class="order-detail-label">포인트 사용</span>
        <span class="order-detail-value"><?php echo number_format($order['used_point']); ?>원</span>
    </div>
    <?php if (!empty($order['point_benefit_description'])): ?>
    <div class="order-detail-item">
        <span class="order-detail-label">할인 혜택</span>
        <span class="order-detail-value" style="color: #10b981;">
            💎 <?php echo htmlspecialchars($order['point_benefit_description']); ?>
        </span>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>
```

---

## API 설계

### 1. 상품 등록/수정 API 수정

#### 1.1 알뜰폰 상품 등록/수정 (`api/product-register-mvno.php`)

**추가 처리 로직**:
```php
// 포인트 설정 받기
$point_setting = isset($_POST['point_setting']) ? intval($_POST['point_setting']) : 0;
$point_benefit_description = isset($_POST['point_benefit_description']) ? trim($_POST['point_benefit_description']) : '';

// 유효성 검증
if ($point_setting < 0) {
    $point_setting = 0;
}

if (strlen($point_benefit_description) > 500) {
    $point_benefit_description = substr($point_benefit_description, 0, 500);
}

// products 테이블 업데이트
if ($isEditMode) {
    $updateStmt = $pdo->prepare("
        UPDATE products 
        SET point_setting = :point_setting,
            point_benefit_description = :point_benefit_description,
            updated_at = NOW()
        WHERE id = :product_id
    ");
    $updateStmt->execute([
        ':point_setting' => $point_setting,
        ':point_benefit_description' => $point_benefit_description ?: null,
        ':product_id' => $productId
    ]);
} else {
    // 신규 등록 시 INSERT 문에 추가
    // ... 기존 INSERT 문에 point_setting, point_benefit_description 추가
}
```

#### 1.2 다른 상품 타입 API
- `api/product-register-mno.php`
- `api/product-register-mno-sim.php`
- `api/product-register-internet.php`

**동일한 로직 추가**

---

### 2. 포인트 설정 조회 API

#### 2.1 `api/get-product-point-setting.php` (신규 생성)

**요청**:
```
GET /api/get-product-point-setting.php?type=mvno&id=123
```

**파라미터**:
- `type`: 상품 타입 (mvno, mno, mno-sim, internet)
- `id`: 상품 ID

**응답**:
```json
{
    "success": true,
    "point_setting": 3000,
    "point_benefit_description": "네이버페이 5000지급 익월말",
    "product_name": "알뜰폰 요금제명"
}
```

**에러 응답**:
```json
{
    "success": false,
    "message": "상품을 찾을 수 없습니다."
}
```

**구현 코드**:
```php
<?php
require_once __DIR__ . '/../includes/data/db-config.php';

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? '';
$id = intval($_GET['id'] ?? 0);

if (empty($type) || $id <= 0) {
    echo json_encode(['success' => false, 'message' => '잘못된 요청입니다.']);
    exit;
}

try {
    $pdo = getDBConnection();
    if (!$pdo) {
        echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
        exit;
    }
    
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.point_setting,
            p.point_benefit_description,
            CASE p.product_type
                WHEN 'mvno' THEN mvno.plan_name
                WHEN 'mno' THEN mno.device_name
                WHEN 'mno-sim' THEN mno_sim.plan_name
                WHEN 'internet' THEN internet.registration_place
                ELSE NULL
            END as product_name
        FROM products p
        LEFT JOIN product_mvno_details mvno ON p.id = mvno.product_id AND p.product_type = 'mvno'
        LEFT JOIN product_mno_details mno ON p.id = mno.product_id AND p.product_type = 'mno'
        LEFT JOIN product_mno_sim_details mno_sim ON p.id = mno_sim.product_id AND p.product_type = 'mno-sim'
        LEFT JOIN product_internet_details internet ON p.id = internet.product_id AND p.product_type = 'internet'
        WHERE p.id = :id AND p.product_type = :type AND p.status != 'deleted'
        LIMIT 1
    ");
    
    $stmt->execute([':id' => $id, ':type' => $type]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$product) {
        echo json_encode(['success' => false, 'message' => '상품을 찾을 수 없습니다.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'point_setting' => intval($product['point_setting'] ?? 0),
        'point_benefit_description' => $product['point_benefit_description'] ?? '',
        'product_name' => $product['product_name'] ?? ''
    ]);
} catch (PDOException $e) {
    error_log('get-product-point-setting error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '조회 중 오류가 발생했습니다.']);
}
```

---

### 3. 주문 조회 API 수정

#### 3.1 주문 목록 조회 API

**수정 사항**: 주문 조회 시 포인트 사용 정보 포함

```sql
SELECT 
    o.*,
    p.point_setting,
    p.point_benefit_description,
    pl.used_point,
    pl.discount_amount
FROM orders o
LEFT JOIN products p ON o.product_id = p.id
LEFT JOIN point_ledger pl ON pl.item_id = o.product_id 
    AND pl.user_id = o.user_id 
    AND pl.type = o.product_type
WHERE ...
```

---

## 데이터 흐름

### 1. 판매자 상품 등록/수정 플로우

```
[판매자 로그인]
    ↓
[상품 등록/수정 페이지 접속]
    ↓
[포인트 설정 및 할인 혜택 내용 입력]
    ├─ 포인트 설정: 3000
    └─ 할인 혜택 내용: "네이버페이 5000지급 익월말"
    ↓
[상품 저장 버튼 클릭]
    ↓
[API 호출: product-register-{type}.php]
    ↓
[데이터베이스 저장]
    ├─ products.point_setting = 3000
    └─ products.point_benefit_description = "네이버페이 5000지급 익월말"
    ↓
[저장 완료]
```

---

### 2. 고객 포인트 사용 플로우

```
[고객 상품 상세 페이지 접속]
    ↓
[신청하기 버튼 클릭]
    ↓
[로그인 체크]
    ├─ 비로그인 → 로그인 모달
    └─ 로그인 → 다음 단계
    ↓
[포인트 설정 조회 API 호출]
    GET /api/get-product-point-setting.php?type=mvno&id=123
    ↓
[응답 확인]
    ├─ point_setting = 0 → 포인트 모달 건너뛰고 기존 신청 모달 열기
    └─ point_setting > 0 → 포인트 모달 표시
        ↓
    [포인트 사용 모달]
        - 보유 포인트: 10,000원
        - 최대 사용 가능: 3,000원
        - 할인 혜택: "네이버페이 5000지급 익월말"
        - 사용할 포인트 입력: 3,000원
        - [확인] 버튼 클릭
            ↓
        [포인트 차감 API 호출]
            POST /api/point-deduct.php
            {
                user_id: "user123",
                type: "mvno",
                item_id: 123,
                amount: 3000,
                description: "알뜰폰 할인혜택"
            }
            ↓
        [포인트 차감 성공]
            ↓
        [기존 신청 모달 열기]
            - 포인트 사용 정보 포함
            - 신청 폼 제출 시 point_used, discount_amount 포함
```

---

### 3. 관리자 주문 관리 플로우

```
[관리자 로그인]
    ↓
[주문 관리 페이지 접속]
    ↓
[주문 목록 조회]
    - 포인트 사용 여부 표시
    - 할인 금액 표시
    ↓
[주문 상세 보기 클릭]
    ↓
[주문 상세 정보 표시]
    - 포인트 사용: 3,000원
    - 할인 혜택: "네이버페이 5000지급 익월말"
    ↓
[개통 처리 시 할인 혜택 적용]
```

---

## 구현 우선순위

### Phase 1: 기본 기능 구현 (필수)
1. ✅ 데이터베이스 스키마 수정
   - `products` 테이블에 컬럼 추가

2. ✅ 판매자 상품 등록/수정 페이지
   - 포인트 설정 입력 필드 추가
   - 할인 혜택 내용 입력 필드 추가
   - 모든 상품 타입 페이지에 적용

3. ✅ 상품 등록/수정 API 수정
   - 포인트 설정 저장 로직 추가
   - 모든 상품 타입 API에 적용

### Phase 2: 고객 기능 구현 (필수)
4. ✅ 포인트 설정 조회 API 생성
   - `api/get-product-point-setting.php` 생성

5. ✅ 포인트 사용 모달 수정
   - 할인 혜택 내용 표시 영역 추가
   - 안내 메시지 수정

6. ✅ 포인트 사용 플로우 통합
   - 상세 페이지 신청하기 버튼 클릭 시 포인트 체크 로직 추가

### Phase 3: 관리자 기능 구현 (선택)
7. ⚪ 주문 목록 페이지 수정
   - 포인트 사용 여부 표시
   - 할인 금액 표시

8. ⚪ 주문 상세 페이지 수정
   - 포인트 사용 정보 섹션 추가
   - 할인 혜택 내용 표시

### Phase 4: 추가 기능 (선택)
9. ⚪ 포인트 설정 통계
   - 상품별 포인트 사용 통계
   - 할인 혜택별 통계

10. ⚪ 포인트 설정 일괄 수정
    - 여러 상품의 포인트 설정 일괄 변경

---

## 주의사항

### 1. 데이터 일관성
- 포인트 설정이 0이면 할인 혜택 내용도 의미가 없으므로, UI에서 안내 필요
- 포인트 설정이 있는데 할인 혜택 내용이 없으면 기본 메시지 표시

### 2. 보안
- 포인트 설정은 0 이상의 정수만 허용
- 할인 혜택 내용은 XSS 방지를 위해 `htmlspecialchars` 처리 필수
- 판매자만 자신의 상품 포인트 설정 수정 가능

### 3. 사용자 경험
- 포인트 설정이 0인 상품은 포인트 모달을 표시하지 않음
- 할인 혜택 내용이 없어도 포인트 사용은 가능 (기본 안내 메시지 표시)
- 포인트 모달에서 할인 혜택 내용을 명확하게 강조

### 4. 성능
- 포인트 설정 조회는 캐싱 고려 (선택사항)
- 주문 조회 시 JOIN 최적화

---

## 테스트 시나리오

### 1. 판매자 테스트
- [ ] 포인트 설정 입력 및 저장
- [ ] 할인 혜택 내용 입력 및 저장
- [ ] 포인트 설정 0으로 설정 시 저장 확인
- [ ] 할인 혜택 내용 없이 저장 가능 확인

### 2. 고객 테스트
- [ ] 포인트 설정이 있는 상품에서 포인트 모달 표시 확인
- [ ] 포인트 설정이 0인 상품에서 포인트 모달 미표시 확인
- [ ] 할인 혜택 내용 표시 확인
- [ ] 포인트 사용 후 신청 완료 확인

### 3. 관리자 테스트
- [ ] 주문 목록에서 포인트 사용 정보 표시 확인
- [ ] 주문 상세에서 할인 혜택 내용 표시 확인
- [ ] 포인트 사용 내역 정확성 확인

---

## 마이그레이션 가이드

### 1. 데이터베이스 마이그레이션
```sql
-- 1. 컬럼 추가
ALTER TABLE `products` 
ADD COLUMN `point_setting` INT(11) UNSIGNED NOT NULL DEFAULT 0 
    COMMENT '포인트 설정 (0이면 포인트 사용 불가)' 
    AFTER `application_count`,
ADD COLUMN `point_benefit_description` TEXT DEFAULT NULL 
    COMMENT '포인트 사용 시 할인 혜택 내용' 
    AFTER `point_setting`;

-- 2. 인덱스 추가 (선택사항)
ALTER TABLE `products` 
ADD INDEX `idx_point_setting` (`point_setting`);

-- 3. 기존 데이터 확인
SELECT id, product_type, point_setting, point_benefit_description 
FROM products 
LIMIT 10;
```

### 2. 코드 배포 순서
1. 데이터베이스 마이그레이션 실행
2. API 수정 (상품 등록/수정 API)
3. 판매자 페이지 수정
4. 포인트 사용 모달 수정
5. 관리자 페이지 수정

---

## 참고사항

### 1. 기존 시스템과의 호환성
- 기존 상품은 `point_setting = 0`으로 기본값 설정되어 포인트 사용 불가
- 기존 포인트 시스템과 충돌 없이 동작

### 2. 확장 가능성
- 향후 포인트 설정을 상품 타입별 기본값으로 설정 가능
- 할인 혜택 내용을 템플릿으로 관리 가능
- 포인트 사용 통계 및 분석 기능 추가 가능

---

**작성일**: 2025-01-XX  
**버전**: 1.0  
**작성자**: System Designer
