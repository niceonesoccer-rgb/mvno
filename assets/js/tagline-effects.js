// 태그라인 효과 처리 (한글자씩 효과 및 타이핑 효과)
(function() {
    'use strict';
    
    function initTaglineEffects() {
        const taglineElements = document.querySelectorAll('.header-center-text');
        
        taglineElements.forEach(element => {
            const effect = element.className.match(/tagline-effect-(\S+)/);
            if (!effect) return;
            
            const effectName = effect[1];
            
            // 한글자씩 오른쪽/왼쪽 효과
            if (effectName === 'slide-right-char' || effectName === 'slide-left-char') {
                const textElement = element.querySelector('span, a');
                if (!textElement) return;
                
                const text = textElement.textContent;
                const chars = text.split('').map((char, index) => {
                    const span = document.createElement('span');
                    span.textContent = char === ' ' ? '\u00A0' : char; // 공백 처리
                    span.style.display = 'inline-block';
                    span.style.animationDelay = (index * 0.1) + 's';
                    return span;
                });
                
                textElement.textContent = '';
                chars.forEach(char => textElement.appendChild(char));
            }
            
            // 타이핑 효과
            if (effectName === 'typing') {
                const textElement = element.querySelector('span, a');
                if (!textElement) return;
                
                const text = textElement.textContent;
                textElement.textContent = '';
                
                let index = 0;
                const typeInterval = setInterval(() => {
                    if (index < text.length) {
                        textElement.textContent += text[index];
                        index++;
                    } else {
                        clearInterval(typeInterval);
                    }
                }, 100);
            }
        });
    }
    
    // DOM 로드 완료 후 실행
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTaglineEffects);
    } else {
        initTaglineEffects();
    }
})();
