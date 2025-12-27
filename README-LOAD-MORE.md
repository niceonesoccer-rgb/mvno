# 더보기 기능 구현 가이드

## 구현 완료 사항

### 1. API 엔드포인트
- ✅ `api/load-more-products.php` 생성
- ✅ 인터넷, 통신사단독유심, 알뜰폰, 통신사폰 상품 지원
- ✅ 페이지네이션 지원 (LIMIT/OFFSET)
- ✅ 필터링 지원 (통신사, 서비스 타입)

### 2. 인터넷 페이지 (internets.php)
- ✅ 초기 10개만 로드
- ✅ 더보기 버튼 추가
- ✅ 남은 개수 표시
- ✅ JavaScript 연동

### 3. JavaScript
- ✅ `assets/js/load-more-products.js` 생성
- ✅ 더보기 버튼 클릭 이벤트 처리
- ✅ 무한 스크롤 옵션 (설정으로 변경 가능)

## 아직 구현 필요

### HTML 렌더링
현재 API는 JSON 데이터만 반환합니다. JavaScript에서 HTML을 생성해야 합니다.

**해결 방법:**
1. **옵션 1**: API에서 HTML을 직접 반환하도록 수정 (서버 사이드 렌더링)
2. **옵션 2**: JavaScript에서 JSON 데이터를 HTML로 변환 (클라이언트 사이드 렌더링)

**권장**: 옵션 1 (서버 사이드 렌더링)
- 기존 PHP 템플릿 코드 재사용 가능
- 일관된 HTML 구조 유지
- SEO에 유리

## 다음 단계

### 1. 나머지 페이지 구현
- [ ] `mno-sim/mno-sim.php` - 초기 10개만 로드, 더보기 버튼 추가
- [ ] `mvno/mvno.php` - 초기 10개만 로드, 더보기 버튼 추가  
- [ ] `mno/mno.php` - 초기 10개만 로드, 더보기 버튼 추가

### 2. HTML 렌더링 구현
각 페이지 타입별로 HTML 템플릿을 생성하거나, API에서 HTML을 반환하도록 수정

### 3. 테스트
- 더보기 버튼 클릭 테스트
- 남은 개수 표시 확인
- 로딩 상태 확인
- 에러 처리 확인

## 사용 방법

### 더보기 버튼 모드 (기본)
```javascript
// assets/js/load-more-products.js
const LOAD_MORE_MODE = 'button';
```

### 무한 스크롤 모드
```javascript
// assets/js/load-more-products.js
const LOAD_MORE_MODE = 'infinite';
```

## API 사용법

```
GET /MVNO/api/load-more-products.php?type=internet&page=2&limit=10
```

**파라미터:**
- `type`: 'internet', 'mno-sim', 'mvno', 'mno'
- `page`: 페이지 번호 (1부터 시작)
- `limit`: 한 번에 가져올 개수 (기본값: 10)
- `provider`: 통신사 필터 (선택)
- `service_type`: 서비스 타입 필터 (선택)

**응답:**
```json
{
  "success": true,
  "products": [...],
  "pagination": {
    "page": 2,
    "limit": 10,
    "total": 50,
    "hasMore": true,
    "remaining": 30
  }
}
```

## 비교 문서

자세한 비교는 `docs/load-more-comparison.md` 참고


