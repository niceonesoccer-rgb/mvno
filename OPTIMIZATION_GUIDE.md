# 최적화 실행 가이드

## ✅ 1단계: 즉시 적용 (완료)

### 1-1. gzip 압축 활성화 ✅
- `.htaccess` 파일 생성 완료
- 효과: 트래픽 70-80% 감소, 연결 시간 단축

### 1-2. 브라우저 캐싱 설정 ✅
- 이미지/ CSS/JS 파일 캐싱 활성화
- 효과: 재방문 시 서버 요청 감소

---

## 🔄 2단계: 캐싱 시스템 적용 (다음 단계)

### 2-1. 캐시 시스템 준비 완료
- `includes/cache.php` 파일 생성 완료
- 파일 기반 캐싱 (Redis 없이도 작동)

### 2-2. plans.php에 캐싱 적용 방법

**현재 plans.php가 하드코딩된 HTML이라면:**
- 정적 페이지이므로 캐싱 불필요
- 이미 최적화된 상태

**DB 쿼리가 있다면 (예시):**

```php
<?php
// plans.php 상단에 추가
require_once 'includes/cache.php';
$cache = new SimpleCache();

// 페이지 번호 가져오기
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page); // 최소 1페이지

// 캐시 키 생성
$cacheKey = "plans_page_{$page}";

// 캐시에서 가져오기
$plans = $cache->get($cacheKey);

if ($plans === false) {
    // 캐시에 없으면 DB에서 가져오기
    $offset = ($page - 1) * 10;
    
    // DB 쿼리 (실제 DB 연결 코드로 교체)
    // $plans = $db->query("SELECT * FROM plans LIMIT 10 OFFSET $offset")->fetchAll();
    
    // 예시: 하드코딩된 데이터라면 그대로 사용
    // $plans = getPlansData($page);
    
    // 캐시에 저장 (5분)
    $cache->set($cacheKey, $plans, 300);
}

// $plans 데이터로 HTML 생성
?>
```

---

## 📊 3단계: 모니터링 시스템 구축

### 3-1. 동시 접속 수 추적 파일 생성

`includes/monitor.php` 파일을 만들어서:
- 현재 접속 중인 사용자 수 추적
- 로그 파일에 기록
- 관리자 페이지에서 확인 가능

### 3-2. 간단한 모니터링 스크립트

```php
<?php
// includes/monitor.php
function logConnection() {
    $logFile = 'logs/connections.log';
    $data = [
        'time' => date('Y-m-d H:i:s'),
        'ip' => $_SERVER['REMOTE_ADDR'],
        'page' => $_SERVER['REQUEST_URI']
    ];
    
    file_put_contents($logFile, json_encode($data) . "\n", FILE_APPEND);
}

function getCurrentConnections() {
    // 간단한 방법: 최근 5초 내 접속 수
    $logFile = 'logs/connections.log';
    if (!file_exists($logFile)) return 0;
    
    $lines = file($logFile);
    $recent = 0;
    $now = time();
    
    foreach (array_reverse($lines) as $line) {
        $data = json_decode($line, true);
        if (!$data) continue;
        
        $logTime = strtotime($data['time']);
        if ($now - $logTime < 5) {
            $recent++;
        } else {
            break;
        }
    }
    
    return $recent;
}
?>
```

---

## 🗄️ 4단계: DB 최적화 (DB 사용 시)

### 4-1. 인덱스 확인 및 추가

```sql
-- plans 테이블에 인덱스 추가
CREATE INDEX idx_plans_id ON plans(id);
CREATE INDEX idx_plans_status ON plans(status);
CREATE INDEX idx_plans_created ON plans(created_at);
```

### 4-2. 쿼리 최적화

```sql
-- 나쁜 예: 전체 스캔
SELECT * FROM plans ORDER BY id LIMIT 10 OFFSET 0;

-- 좋은 예: 인덱스 활용
SELECT * FROM plans 
WHERE status = 'active' 
ORDER BY id 
LIMIT 10 OFFSET 0;
```

---

## 📈 5단계: 점진적 개선

### 5-1. CDN 활용 (선택사항)
- 정적 리소스(이미지, CSS, JS)를 CDN으로 이동
- Cloudflare 무료 플랜 사용 가능
- 효과: 서버 부하 50% 감소

### 5-2. 이미지 최적화
- WebP 포맷 사용
- 이미지 압축
- 효과: 트래픽 30-40% 감소

---

## 🎯 우선순위 요약

### 즉시 적용 (오늘)
1. ✅ gzip 압축 (.htaccess)
2. ✅ 브라우저 캐싱 (.htaccess)
3. ✅ 캐시 시스템 준비 (cache.php)

### 이번 주 내
4. plans.php에 캐싱 적용 (DB 사용 시)
5. 모니터링 시스템 구축
6. DB 인덱스 추가 (DB 사용 시)

### 여유 있을 때
7. CDN 활용
8. 이미지 최적화
9. 연결 풀링 (고급)

---

## 📊 예상 효과

### 최적화 전
- 동시 접속: 20-25명 (안전)
- 트래픽: 사용자당 0.63GB/월
- 서버 부하: 중간

### 최적화 후
- 동시 접속: 28-30명 (안전)
- 트래픽: 사용자당 0.2-0.3GB/월 (50% 감소)
- 서버 부하: 낮음

---

## ⚠️ 주의사항

1. **캐시 디렉토리 권한**
   - `cache/` 디렉토리 생성 필요
   - 쓰기 권한 필요 (755 또는 777)

2. **로그 디렉토리**
   - `logs/` 디렉토리 생성 필요
   - 주기적으로 로그 정리 필요

3. **캐시 정리**
   - 주기적으로 만료된 캐시 삭제
   - cron job 설정 권장

---

## 🔧 문제 해결

### 캐시가 작동하지 않을 때
1. `cache/` 디렉토리 권한 확인
2. PHP 에러 로그 확인
3. 캐시 파일이 생성되는지 확인

### 동시 접속 수가 계속 초과될 때
1. 캐싱이 제대로 작동하는지 확인
2. 불필요한 요청이 있는지 확인
3. 호스팅 업그레이드 고려




