# 페이지 새로고침 방식의 스크롤 동작

## 기본 동작

### 일반적인 페이지 새로고침
```
1. 더보기 버튼 클릭 → URL 변경 (?page=2)
2. 페이지 새로고침
3. 스크롤 위치: 맨 위로 이동 (기본 동작)
```

**문제점**: 사용자가 스크롤한 위치에서 맨 위로 이동하여 불편함

---

## 해결 방법

### 방법 1: JavaScript로 스크롤 위치 저장/복원 ✅ (추천)

```javascript
// 더보기 버튼 클릭 전에 현재 스크롤 위치 저장
localStorage.setItem('scrollPosition', window.pageYOffset);

// 페이지 로드 후 저장된 위치로 이동
window.addEventListener('DOMContentLoaded', function() {
    const savedPosition = localStorage.getItem('scrollPosition');
    if (savedPosition) {
        window.scrollTo(0, parseInt(savedPosition));
        localStorage.removeItem('scrollPosition');
    }
});
```

**장점:**
- 사용자가 있던 위치로 자동 이동
- UX 개선

**단점:**
- 약간의 깜빡임 (맨 위로 갔다가 이동)

---

### 방법 2: 앵커 링크 사용

```html
<a href="?page=2#products-list">더보기</a>

<!-- 페이지 로드 후 -->
<div id="products-list">상품 목록</div>

<script>
// URL에 앵커가 있으면 해당 위치로 이동
if (window.location.hash) {
    const element = document.querySelector(window.location.hash);
    if (element) {
        element.scrollIntoView({ behavior: 'smooth' });
    }
}
</script>
```

**장점:**
- URL에 위치 정보 포함
- 북마크/공유 시에도 위치 유지

**단점:**
- 더보기 버튼 근처로 이동 (정확한 위치는 아님)

---

### 방법 3: 첫 번째 새 아이템 위치로 이동

```javascript
// 더보기 버튼 위에 앵커 추가
<a href="?page=2#load-more-anchor">더보기</a>

<!-- 더보기 버튼 위에 앵커 -->
<div id="load-more-anchor"></div>
<button>더보기</button>

// 페이지 로드 후 앵커 위치로 이동
```

**장점:**
- 첫 번째 새 아이템 근처로 이동
- 자연스러운 경험

---

## 최종 추천

### 방법 1 + 방법 3 조합 (하이브리드)

1. **더보기 버튼 위에 앵커 추가**
2. **JavaScript로 정확한 위치 복원**

이렇게 하면:
- 페이지 새로고침 후 더보기 버튼 위치로 이동
- 사용자가 있던 위치 근처로 이동
- 자연스러운 경험

---

## 구현 예시

```php
<!-- 더보기 버튼 -->
<a href="?page=2#products-list" class="load-more-btn">더보기</a>

<script>
// 페이지 로드 후
window.addEventListener('DOMContentLoaded', function() {
    // URL에 앵커가 있으면
    if (window.location.hash === '#products-list') {
        const element = document.querySelector('#products-list');
        if (element) {
            // 약간 위로 (더보기 버튼이 보이도록)
            const offset = 100;
            const elementPosition = element.getBoundingClientRect().top;
            const offsetPosition = elementPosition + window.pageYOffset - offset;
            
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    }
});
</script>
```

---

## 결론

**페이지 새로고침 후:**
- 기본적으로는 **맨 위로 이동**
- 하지만 JavaScript로 **원하는 위치로 이동 가능**
- 추천: **더보기 버튼 위치 근처로 이동**





