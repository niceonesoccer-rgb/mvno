/**
 * 포인트 사용 모달 연동 스크립트
 * 신청하기 버튼 클릭 시 포인트 모달을 먼저 표시하고, 확인 후 기존 신청 모달로 이동
 */

(function() {
    'use strict';
    
    // 포인트 사용 확인 이벤트 리스너
    document.addEventListener('pointUsageConfirmed', function(e) {
        const { type, itemId, usedPoint } = e.detail;
        
        // 포인트 차감 API 호출
        fetch('/MVNO/api/point-deduct.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_id: 'default', // 실제로는 세션에서 가져옴
                type: type,
                item_id: itemId,
                amount: usedPoint,
                description: type === 'mvno' ? '알뜰폰 신청' : type === 'mno' ? '통신사폰 신청' : '인터넷 신청'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 포인트 차감 성공
                console.log('포인트 차감 완료:', data);
                
                // 기존 신청 모달 열기 (각 페이지에서 구현)
                // 예: window.openApplicationModal(type, itemId);
                
                // 포인트 잔액 업데이트
                updatePointBalance();
            } else {
                showAlert(data.message || '포인트 차감에 실패했습니다.');
            }
        })
        .catch(error => {
            console.error('포인트 차감 오류:', error);
            showAlert('포인트 차감 중 오류가 발생했습니다.');
        });
    });
    
    // 포인트 잔액 업데이트
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
    
    // 알뜰폰 신청하기 버튼에 포인트 모달 연동
    function initMvnoPointIntegration() {
        const applyBtns = document.querySelectorAll('.plan-apply-btn, [data-apply-type="mvno"]');
        
        applyBtns.forEach(btn => {
            // 이미 이벤트 리스너가 추가되었는지 확인
            if (btn.hasAttribute('data-point-integration-added')) {
                return;
            }
            btn.setAttribute('data-point-integration-added', 'true');
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // 요금제 ID 가져오기
                const planId = this.getAttribute('data-plan-id') || 
                              this.closest('[data-plan-id]')?.getAttribute('data-plan-id') ||
                              new URLSearchParams(window.location.search).get('id') ||
                              0;
                
                // 포인트 모달 열기
                if (typeof openPointUsageModal === 'function') {
                    openPointUsageModal('mvno', planId);
                } else {
                    // 포인트 모달이 없으면 기존 신청 모달 열기 함수 호출
                    if (typeof openMvnoApplicationModal === 'function') {
                        openMvnoApplicationModal(planId);
                    } else {
                        console.warn('포인트 모달 함수를 찾을 수 없습니다. 기존 신청 모달을 열 수 없습니다.');
                    }
                }
            });
        });
    }
    
    // 통신사단독유심 신청하기 버튼에 포인트 모달 연동
    function initMnoSimPointIntegration() {
        const applyBtns = document.querySelectorAll('#planApplyBtn, [data-apply-type="mno-sim"]');
        
        applyBtns.forEach(btn => {
            // 이미 이벤트 리스너가 추가되었는지 확인
            if (btn.hasAttribute('data-point-integration-added')) {
                return;
            }
            btn.setAttribute('data-point-integration-added', 'true');
            
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // 상품 ID 가져오기
                const productId = this.getAttribute('data-product-id') || 
                                this.closest('[data-product-id]')?.getAttribute('data-product-id') ||
                                new URLSearchParams(window.location.search).get('id') ||
                                0;
                
                // 포인트 모달 열기
                if (typeof openPointUsageModal === 'function') {
                    openPointUsageModal('mno-sim', productId);
                } else if (typeof openApplyModal === 'function') {
                    // 포인트 모달이 없으면 통신사단독유심 신청 모달 직접 열기
                    console.warn('포인트 모달 함수를 찾을 수 없습니다. 통신사단독유심 신청 모달을 직접 엽니다.');
                    openApplyModal();
                } else {
                    console.error('포인트 모달 함수와 통신사단독유심 신청 모달 함수를 모두 찾을 수 없습니다.');
                }
            });
        });
    }
    
    // 통신사폰 신청하기 버튼에 포인트 모달 연동
    function initMnoPointIntegration() {
        const applyBtns = document.querySelectorAll('[data-apply-type="mno"], .mno-apply-btn');
        
        applyBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const phoneId = this.getAttribute('data-phone-id') || 
                               this.closest('[data-phone-id]')?.getAttribute('data-phone-id') ||
                               0;
                
                if (typeof openPointUsageModal === 'function') {
                    openPointUsageModal('mno', phoneId);
                }
            });
        });
    }
    
    // 인터넷 신청하기 버튼에 포인트 모달 연동
    function initInternetPointIntegration() {
        const applyBtns = document.querySelectorAll('[data-apply-type="internet"], .internet-apply-btn');
        
        applyBtns.forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                const internetId = this.getAttribute('data-internet-id') || 
                                  this.closest('[data-internet-id]')?.getAttribute('data-internet-id') ||
                                  0;
                
                if (typeof openPointUsageModal === 'function') {
                    openPointUsageModal('internet', internetId);
                }
            });
        });
    }
    
    // 페이지 로드 시 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function() {
            initMvnoPointIntegration();
            initMnoPointIntegration();
            initMnoSimPointIntegration();
            initInternetPointIntegration();
        });
    } else {
        initMvnoPointIntegration();
        initMnoPointIntegration();
        initMnoSimPointIntegration();
        initInternetPointIntegration();
    }
})();



