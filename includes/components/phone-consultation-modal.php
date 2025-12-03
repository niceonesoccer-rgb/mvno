<?php
/**
 * 휴대폰 상담 신청 모달 컴포넌트
 */
?>

<div class="phone-consultation-modal" id="phoneConsultationModal">
    <div class="phone-consultation-modal-overlay" id="phoneConsultationModalOverlay"></div>
    <div class="phone-consultation-modal-content">
        <div class="phone-consultation-modal-header">
            <h3 class="phone-consultation-modal-title">상담 신청</h3>
            <button class="phone-consultation-modal-close" aria-label="닫기" id="phoneConsultationModalClose">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M18 6L6 18M6 6L18 18" stroke="#868E96" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </button>
        </div>
        <div class="phone-consultation-modal-body">
            <!-- 안내 문구 -->
            <div class="phone-consultation-info">
                <div class="phone-consultation-info-icon">
                    <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path fill-rule="evenodd" clip-rule="evenodd" d="M22 12C22 17.5228 17.5228 22 12 22C6.47715 22 2 17.5228 2 12C2 6.47715 6.47715 2 12 2C17.5228 2 22 6.47715 22 12ZM13.3 7.8C13.3 8.51797 12.718 9.1 12 9.1C11.282 9.1 10.7 8.51797 10.7 7.8C10.7 7.08203 11.282 6.5 12 6.5C12.718 6.5 13.3 7.08203 13.3 7.8ZM13.1 11.5C13.1 10.8925 12.6075 10.4 12 10.4C11.3925 10.4 10.9 10.8925 10.9 11.5V16C10.9 16.6075 11.3925 17.1 12 17.1C12.6075 17.1 13.1 16.6075 13.1 16L13.1 11.5Z" fill="#3F4750"></path>
                    </svg>
                </div>
                <div class="phone-consultation-info-text">
                    <p>이미 ktskylife 인터넷을 쓰고 계신다면 사은품은 지급되지 않아요.<br>사은품은 모요에서 인터넷을 가입한 분들께만 드려요.</p>
                </div>
            </div>

            <!-- 폼 -->
            <form class="phone-consultation-form" id="phoneConsultationForm">
                <div class="phone-consultation-form-group">
                    <label for="consultationName" class="phone-consultation-label">이름</label>
                    <div class="phone-consultation-input-wrapper">
                        <input id="consultationName" type="text" inputmode="text" name="name" class="phone-consultation-input" value="" required>
                    </div>
                </div>
                
                <div class="phone-consultation-form-group">
                    <label for="consultationPhone" class="phone-consultation-label">휴대폰 번호</label>
                    <div class="phone-consultation-input-wrapper">
                        <input id="consultationPhone" type="tel" inputmode="tel" name="phoneNumber" class="phone-consultation-input" value="" required>
                    </div>
                </div>

                <!-- 동의 체크박스 -->
                <div class="phone-consultation-agreement">
                    <div class="phone-consultation-agreement-item">
                        <label class="phone-consultation-agreement-checkbox-label">
                            <input type="checkbox" class="phone-consultation-checkbox" id="consultationAgreeAll">
                            <span class="phone-consultation-checkbox-text">전체 동의</span>
                        </label>
                    </div>
                    <div class="phone-consultation-agreement-list">
                        <div class="phone-consultation-agreement-item">
                            <label class="phone-consultation-agreement-checkbox-label">
                                <input type="checkbox" class="phone-consultation-checkbox" id="consultationAgree1" name="agree1" required>
                                <span class="phone-consultation-checkbox-text">개인정보 수집 이용</span>
                            </label>
                            <a href="/terms/10081" target="_blank" rel="noreferrer" class="phone-consultation-agreement-link">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M5.46967 12.4697C5.17678 12.7626 5.17678 13.2374 5.46967 13.5303C5.76256 13.8232 6.23744 13.8232 6.53033 13.5303L5.46967 12.4697ZM11 8L11.5303 8.53033C11.8232 8.23744 11.8232 7.76256 11.5303 7.46967L11 8ZM6.53033 2.46967C6.23744 2.17678 5.76256 2.17678 5.46967 2.46967C5.17678 2.76256 5.17678 3.23744 5.46967 3.53033L6.53033 2.46967ZM6.53033 13.5303L11.5303 8.53033L10.4697 7.46967L5.46967 12.4697L6.53033 13.5303ZM11.5303 7.46967L6.53033 2.46967L5.46967 3.53033L10.4697 8.53033L11.5303 7.46967Z"></path>
                                </svg>
                            </a>
                        </div>
                        <div class="phone-consultation-agreement-item">
                            <label class="phone-consultation-agreement-checkbox-label">
                                <input type="checkbox" class="phone-consultation-checkbox" id="consultationAgree2" name="agree2" required>
                                <span class="phone-consultation-checkbox-text">개인정보 제3자 제공</span>
                            </label>
                            <a href="/terms/10092" target="_blank" rel="noreferrer" class="phone-consultation-agreement-link">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor">
                                    <path d="M5.46967 12.4697C5.17678 12.7626 5.17678 13.2374 5.46967 13.5303C5.76256 13.8232 6.23744 13.8232 6.53033 13.5303L5.46967 12.4697ZM11 8L11.5303 8.53033C11.8232 8.23744 11.8232 7.76256 11.5303 7.46967L11 8ZM6.53033 2.46967C6.23744 2.17678 5.76256 2.17678 5.46967 2.46967C5.17678 2.76256 5.17678 3.23744 5.46967 3.53033L6.53033 2.46967ZM6.53033 13.5303L11.5303 8.53033L10.4697 7.46967L5.46967 12.4697L6.53033 13.5303ZM11.5303 7.46967L6.53033 2.46967L5.46967 3.53033L10.4697 8.53033L11.5303 7.46967Z"></path>
                                </svg>
                            </a>
                        </div>
                    </div>
                </div>

                <button type="submit" class="phone-consultation-submit-btn" id="phoneConsultationSubmitBtn" disabled>상담 신청</button>
            </form>
        </div>
    </div>
</div>

