// 요금제 아코디언 토글 기능
document.addEventListener('DOMContentLoaded', function() {
    const accordionTriggers = document.querySelectorAll('.plan-accordion-trigger');
    
    accordionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function(e) {
            // 찜 버튼이나 공유 버튼 클릭 시 아코디언 동작 방지
            const clickedFavorite = e.target.closest('.plan-favorite-btn-inline');
            const clickedShare = e.target.closest('[data-share-url]');
            if (clickedFavorite || clickedShare) {
                return; // 찜/공유 버튼 클릭 시 아코디언 동작하지 않음
            }
            
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            
            // 부모 요소에서 콘텐츠 찾기
            const accordion = this.closest('.plan-accordion');
            const content = accordion ? accordion.querySelector('.plan-accordion-content') : this.nextElementSibling;
            const arrow = this.querySelector('.plan-accordion-arrow');
            
            if (!content) return;
            
            // 링크 이동 방지
            e.preventDefault();
            e.stopPropagation();
            
            // aria-expanded 상태 토글
            this.setAttribute('aria-expanded', !isExpanded);
            
            // 콘텐츠 표시/숨김
            if (isExpanded) {
                content.style.display = 'none';
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            } else {
                content.style.display = 'block';
                if (arrow) {
                    arrow.style.transform = 'rotate(180deg)';
                }
            }
        });
    });
});







