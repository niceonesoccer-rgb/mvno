/**
 * 포인트 잔액 자동 업데이트 스크립트
 * 페이지 로드 시 및 포인트 사용 후 잔액 업데이트
 */

(function() {
    'use strict';
    
    // 포인트 잔액 업데이트 함수
    function updatePointBalance() {
        fetch('/MVNO/api/point-balance.php?user_id=default')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // 마이페이지 포인트 표시 업데이트
                    const balanceElements = document.querySelectorAll('[data-point-balance]');
                    balanceElements.forEach(el => {
                        el.textContent = formatNumber(data.balance) + '원';
                    });
                }
            })
            .catch(error => {
                console.error('포인트 잔액 조회 오류:', error);
            });
    }
    
    // 숫자 포맷팅
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }
    
    // 포인트 사용 후 잔액 업데이트
    document.addEventListener('pointUsageConfirmed', function() {
        setTimeout(updatePointBalance, 500); // 포인트 차감 완료 후 업데이트
    });
    
    // 페이지 로드 시 잔액 업데이트
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updatePointBalance);
    } else {
        updatePointBalance();
    }
})();


















