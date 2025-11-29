// 요금제 아코디언 토글 기능
document.addEventListener('DOMContentLoaded', function() {
    const accordionTriggers = document.querySelectorAll('.plan-accordion-trigger');
    
    accordionTriggers.forEach(trigger => {
        trigger.addEventListener('click', function() {
            const isExpanded = this.getAttribute('aria-expanded') === 'true';
            const content = this.nextElementSibling;
            
            // aria-expanded 상태 토글
            this.setAttribute('aria-expanded', !isExpanded);
            
            // 콘텐츠 표시/숨김
            if (isExpanded) {
                content.style.display = 'none';
            } else {
                content.style.display = 'block';
            }
        });
    });
});



