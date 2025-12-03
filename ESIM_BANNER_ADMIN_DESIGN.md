# eSIM 상세 페이지 배너 관리 설계 문서

## 개요
eSIM 국가 상세 페이지의 프로모션 배너와 Google Maps 섹션을 관리자 페이지에서 관리할 수 있도록 설계합니다.

## 배너 섹션

### 1. 프로모션 배너 (esim-promo-banner)

#### 기능
- 국가별로 다른 프로모션 배너 텍스트와 링크를 설정 가능
- 배너가 입력되지 않으면 해당 영역은 노출되지 않음

#### 데이터 구조
```php
// 데이터베이스 테이블: esim_promo_banners
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- country_code (VARCHAR(50)) // 'japan', 'china' 등
- banner_text (TEXT) // "일본 여행 필수템! 로컬 eSIM, USIM 출시!"
- banner_link (VARCHAR(500)) // 클릭 시 이동할 URL
- is_active (TINYINT(1), DEFAULT 1) // 활성화 여부
- created_at (DATETIME)
- updated_at (DATETIME)
```

#### 관리자 페이지 필드
- **국가 선택**: 드롭다운 (japan, china, taiwan 등)
- **배너 텍스트**: 텍스트 입력 필드
- **배너 링크**: URL 입력 필드 (선택사항)
- **활성화 여부**: 체크박스

#### 프론트엔드 구현
```php
<?php
// esim/esim.php에서 사용
$promo_banner = getEsimPromoBanner($selected_country);

if ($promo_banner && !empty($promo_banner['banner_text'])): ?>
    <div class="esim-promo-banner">
        <div class="esim-promo-content">
            <svg class="esim-promo-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M21 16v-2l-8-5V3.5c0-.83-.67-1.5-1.5-1.5S10 2.67 10 3.5V9l-8 5v2l8-2.5V19l-2 1.5V22l3.5-1 3.5 1v-1.5L13 19v-5.5l8 2.5z" fill="currentColor"/>
            </svg>
            <?php if (!empty($promo_banner['banner_link'])): ?>
                <a href="<?php echo htmlspecialchars($promo_banner['banner_link']); ?>" class="esim-promo-link">
            <?php endif; ?>
            <span class="esim-promo-text"><?php echo htmlspecialchars($promo_banner['banner_text']); ?></span>
            <?php if (!empty($promo_banner['banner_link'])): ?>
                </a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
```

---

### 2. Google Maps 데이터 무료 섹션 (esim-google-maps-section)

#### 기능
- 국가별로 Google Maps 정보 카드의 제목, 설명, 링크를 설정 가능
- 배너가 입력되지 않으면 해당 영역은 노출되지 않음

#### 데이터 구조
```php
// 데이터베이스 테이블: esim_google_maps_info
- id (INT, PRIMARY KEY, AUTO_INCREMENT)
- country_code (VARCHAR(50)) // 'japan', 'china' 등
- title (VARCHAR(200)) // "Google Maps 데이터 무료"
- description (TEXT) // "데이터 차감 없이 앱을 사용해 보세요.(로밍망 선택 시)"
- detail_link (VARCHAR(500)) // "자세히" 링크 URL (선택사항)
- is_active (TINYINT(1), DEFAULT 1) // 활성화 여부
- created_at (DATETIME)
- updated_at (DATETIME)
```

#### 관리자 페이지 필드
- **국가 선택**: 드롭다운 (japan, china, taiwan 등)
- **제목**: 텍스트 입력 필드 (예: "Google Maps 데이터 무료")
- **설명**: 텍스트 영역 (예: "데이터 차감 없이 앱을 사용해 보세요.(로밍망 선택 시)")
- **자세히 링크**: URL 입력 필드 (선택사항)
- **활성화 여부**: 체크박스

#### 프론트엔드 구현
```php
<?php
// esim/esim.php에서 사용
$google_maps_info = getEsimGoogleMapsInfo($selected_country);

if ($google_maps_info && !empty($google_maps_info['title'])): ?>
    <div class="esim-google-maps-section">
        <div class="esim-google-maps-card">
            <div class="esim-google-maps-header">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2C8.13 2 5 5.13 5 9c0 5.25 7 13 7 13s7-7.75 7-13c0-3.87-3.13-7-7-7zm0 9.5c-1.38 0-2.5-1.12-2.5-2.5s1.12-2.5 2.5-2.5 2.5 1.12 2.5 2.5-1.12 2.5-2.5 2.5z" fill="#4285F4"/>
                </svg>
                <span class="esim-google-maps-title"><?php echo htmlspecialchars($google_maps_info['title']); ?></span>
                <?php if (!empty($google_maps_info['detail_link'])): ?>
                    <a href="<?php echo htmlspecialchars($google_maps_info['detail_link']); ?>" class="esim-google-maps-desc">
                        자세히 <span class="esim-arrow">></span>
                    </a>
                <?php endif; ?>
            </div>
            <?php if (!empty($google_maps_info['description'])): ?>
                <p class="esim-google-maps-note"><?php echo htmlspecialchars($google_maps_info['description']); ?></p>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
```

---

## 관리자 페이지 구현

### 파일 위치
- 관리자 페이지: `admin/esim-banner-management.php`
- API 엔드포인트: `admin/api/esim-banner.php` (또는 같은 파일 내 처리)

### 관리자 페이지 UI

#### 1. 프로모션 배너 관리
```
┌─────────────────────────────────────────┐
│ 프로모션 배너 관리                        │
├─────────────────────────────────────────┤
│ 국가: [드롭다운 ▼]                        │
│ 배너 텍스트: [입력 필드]                  │
│ 배너 링크: [URL 입력 필드] (선택사항)    │
│ 활성화: [✓] 체크박스                      │
│ [저장] [취소]                            │
└─────────────────────────────────────────┘
```

#### 2. Google Maps 정보 관리
```
┌─────────────────────────────────────────┐
│ Google Maps 정보 관리                     │
├─────────────────────────────────────────┤
│ 국가: [드롭다운 ▼]                        │
│ 제목: [입력 필드]                         │
│ 설명: [텍스트 영역]                       │
│ 자세히 링크: [URL 입력 필드] (선택사항)   │
│ 활성화: [✓] 체크박스                      │
│ [저장] [취소]                            │
└─────────────────────────────────────────┘
```

### 데이터베이스 함수 예시

```php
// includes/functions/esim-banner.php

/**
 * 국가별 프로모션 배너 가져오기
 */
function getEsimPromoBanner($country_code) {
    global $conn; // 데이터베이스 연결
    
    $stmt = $conn->prepare("
        SELECT banner_text, banner_link 
        FROM esim_promo_banners 
        WHERE country_code = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param("s", $country_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}

/**
 * 국가별 Google Maps 정보 가져오기
 */
function getEsimGoogleMapsInfo($country_code) {
    global $conn; // 데이터베이스 연결
    
    $stmt = $conn->prepare("
        SELECT title, description, detail_link 
        FROM esim_google_maps_info 
        WHERE country_code = ? AND is_active = 1
        LIMIT 1
    ");
    $stmt->bind_param("s", $country_code);
    $stmt->execute();
    $result = $stmt->get_result();
    
    return $result->fetch_assoc();
}
```

---

## 구현 우선순위

1. **데이터베이스 테이블 생성**
   - `esim_promo_banners` 테이블
   - `esim_google_maps_info` 테이블

2. **관리자 페이지 구현**
   - 배너 CRUD 기능
   - 국가별 필터링

3. **프론트엔드 수정**
   - `esim/esim.php`에서 데이터베이스 연동
   - 조건부 렌더링 (데이터가 있을 때만 표시)

4. **스타일 추가**
   - 프로모션 배너 링크 스타일 (필요시)

---

## 주의사항

1. **보안**
   - 관리자 페이지 접근 권한 확인
   - SQL Injection 방지 (Prepared Statement 사용)
   - XSS 방지 (htmlspecialchars 사용)

2. **성능**
   - 국가별 배너 정보 캐싱 고려
   - 데이터베이스 인덱스 추가 (country_code)

3. **사용자 경험**
   - 링크가 없을 때는 텍스트만 표시
   - 링크가 있을 때는 클릭 가능한 링크로 표시
   - 배너가 없으면 해당 영역 완전히 숨김

---

## 수정 필요 파일

1. `esim/esim.php` - 프론트엔드 배너 표시 로직 수정
2. `admin/esim-banner-management.php` - 관리자 페이지 생성 (신규)
3. `includes/functions/esim-banner.php` - 데이터베이스 함수 생성 (신규)
4. 데이터베이스 마이그레이션 스크립트 생성 (신규)

