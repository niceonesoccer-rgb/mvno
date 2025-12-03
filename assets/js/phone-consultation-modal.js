/**
 * 휴대폰 상담 신청 모달 제어
 */

(function() {
    'use strict';

    let modalScrollPosition = 0;

    // 모달 열기
    function openConsultationModal(phoneData) {
        const modal = document.getElementById('phoneConsultationModal');
        if (!modal) return;

        // 스크롤 위치 저장
        modalScrollPosition = window.pageYOffset || document.documentElement.scrollTop;

        // body 스크롤 방지
        document.body.style.position = 'fixed';
        document.body.style.top = `-${modalScrollPosition}px`;
        document.body.style.width = '100%';
        document.body.style.overflow = 'hidden';

        // 모달 표시
        modal.classList.add('phone-consultation-modal-active');
        
        // 전화번호 데이터가 있으면 설정
        if (phoneData && phoneData.phoneId) {
            modal.setAttribute('data-phone-id', phoneData.phoneId);
        }
    }

    // 모달 닫기
    function closeConsultationModal() {
        const modal = document.getElementById('phoneConsultationModal');
        if (!modal) return;

        modal.classList.remove('phone-consultation-modal-active');

        // body 스크롤 복원
        document.body.style.position = '';
        document.body.style.top = '';
        document.body.style.width = '';
        document.body.style.overflow = '';
        window.scrollTo(0, modalScrollPosition);
    }

    // 전체 동의 체크박스 처리
    function handleAgreeAll() {
        const agreeAll = document.getElementById('consultationAgreeAll');
        const agree1 = document.getElementById('consultationAgree1');
        const agree2 = document.getElementById('consultationAgree2');
        const submitBtn = document.getElementById('phoneConsultationSubmitBtn');

        if (!agreeAll || !agree1 || !agree2 || !submitBtn) return;

        agreeAll.addEventListener('change', function() {
            agree1.checked = this.checked;
            agree2.checked = this.checked;
            updateSubmitButton();
        });

        agree1.addEventListener('change', function() {
            updateAgreeAll();
            updateSubmitButton();
        });

        agree2.addEventListener('change', function() {
            updateAgreeAll();
            updateSubmitButton();
        });

        function updateAgreeAll() {
            agreeAll.checked = agree1.checked && agree2.checked;
        }

        function updateSubmitButton() {
            submitBtn.disabled = !(agree1.checked && agree2.checked);
        }
    }

    // 폼 제출 처리
    function handleFormSubmit() {
        const form = document.getElementById('phoneConsultationForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const name = document.getElementById('consultationName').value.trim();
            const phone = document.getElementById('consultationPhone').value.trim();
            const agree1 = document.getElementById('consultationAgree1').checked;
            const agree2 = document.getElementById('consultationAgree2').checked;

            if (!name || !phone) {
                alert('이름과 휴대폰 번호를 입력해주세요.');
                return;
            }

            if (!agree1 || !agree2) {
                alert('개인정보 수집 이용 및 제3자 제공에 동의해주세요.');
                return;
            }

            // 여기에 실제 제출 로직 추가
            console.log('상담 신청:', { name, phone, agree1, agree2 });
            
            // 제출 후 모달 닫기
            // closeConsultationModal();
            alert('상담 신청이 완료되었습니다.');
        });
    }

    // 모달 이벤트 리스너 설정
    function initModal() {
        const modal = document.getElementById('phoneConsultationModal');
        if (!modal) return;

        const closeBtn = modal.querySelector('.phone-consultation-modal-close');
        const overlay = modal.querySelector('.phone-consultation-modal-overlay');

        if (closeBtn) {
            closeBtn.addEventListener('click', closeConsultationModal);
        }

        if (overlay) {
            overlay.addEventListener('click', closeConsultationModal);
        }

        // ESC 키로 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && modal.classList.contains('phone-consultation-modal-active')) {
                closeConsultationModal();
            }
        });

        // 모달 내용 클릭 시 닫히지 않도록
        const modalContent = modal.querySelector('.phone-consultation-modal-content');
        if (modalContent) {
            modalContent.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    }

    // 카드 클릭 이벤트 설정
    function initCardClickHandlers() {
        // phone-deal-card 클릭 시 모달 열기
        const phoneDealCards = document.querySelectorAll('.phone-deal-card');
        phoneDealCards.forEach(function(card) {
            card.style.cursor = 'pointer';
            card.addEventListener('click', function(e) {
                // 링크나 버튼 클릭 시에는 모달이 열리지 않도록
                if (e.target.closest('a, button')) {
                    return;
                }
                
                const phoneName = card.querySelector('.phone-name')?.textContent || '';
                const phonePrice = card.querySelector('.phone-price')?.textContent || '';
                
                openConsultationModal({
                    phoneName: phoneName,
                    phonePrice: phonePrice
                });
            });
        });

        // basic-plan-card 클릭 시 모달 열기 (링크가 아닌 경우)
        const planCards = document.querySelectorAll('.basic-plan-card');
        planCards.forEach(function(card) {
            const cardLink = card.querySelector('.plan-card-link');
            if (!cardLink || cardLink.tagName !== 'A') {
                card.style.cursor = 'pointer';
                card.addEventListener('click', function(e) {
                    // 링크나 버튼 클릭 시에는 모달이 열리지 않도록
                    if (e.target.closest('a, button')) {
                        return;
                    }
                    
                    const phoneName = card.querySelector('.plan-phone-name')?.textContent || '';
                    
                    openConsultationModal({
                        phoneName: phoneName
                    });
                });
            }
        });
    }

    // 초기화
    function init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', function() {
                initModal();
                handleAgreeAll();
                handleFormSubmit();
                initCardClickHandlers();
            });
        } else {
            initModal();
            handleAgreeAll();
            handleFormSubmit();
            initCardClickHandlers();
        }
    }

    // 전역 함수로 모달 열기 함수 노출
    window.openPhoneConsultationModal = openConsultationModal;
    window.closePhoneConsultationModal = closeConsultationModal;

    init();
})();

