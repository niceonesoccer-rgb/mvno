<?php
require_once __DIR__ . '/data/site-settings.php';
$siteSettings = getSiteSettings();
$footer = $siteSettings['footer'] ?? [];
$site = $siteSettings['site'] ?? [];
?>

<?php if (isset($is_main_page) && $is_main_page): ?>
    <footer class="footer">
        <div class="footer-body">
            <!-- 브랜드 로고 -->
            <a href="/MVNO/" aria-label="<?php echo htmlspecialchars($site['name_ko'] ?? '유심킹'); ?>" class="footer-brand"></a>
            
            <!-- 사이트맵 -->
            <nav class="footer-sitemap">
                <a href="/MVNO/qna/qna.php" class="footer-sitemap-link">자주 묻는 질문</a>
                <a href="/MVNO/notice/notice.php" class="footer-sitemap-link">공지사항</a>
            </nav>
            
            <!-- 회사 정보 및 고객센터 -->
            <div class="footer-info-wrapper">
                <address class="footer-contact">
                    <strong class="font-bold"><?php echo htmlspecialchars($footer['company_name'] ?? '(주)유심킹'); ?></strong>
                    <?php if (!empty($footer['business_number'])): ?>
                        사업자등록번호 : <?php echo htmlspecialchars($footer['business_number']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($footer['mail_order_number'])): ?>
                        통신판매업 신고번호 : <?php echo htmlspecialchars($footer['mail_order_number']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($footer['address'])): ?>
                        주소 : <?php echo htmlspecialchars($footer['address']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($footer['email'])): ?>
                        이메일 : <?php echo htmlspecialchars($footer['email']); ?>
                    <?php endif; ?>
                </address>
                
                <div class="footer-customer-service">
                    <strong>고객센터</strong>
                    <?php if (!empty($footer['kakao'])): ?>
                        카카오톡 : <?php echo htmlspecialchars($footer['kakao']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($footer['phone'])): ?>
                        전화번호 : <?php echo htmlspecialchars($footer['phone']); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($footer['cs_notice'])): ?>
                        <?php echo nl2br(htmlspecialchars($footer['cs_notice'])); ?>
                    <?php endif; ?>
                    
                    <strong>운영시간</strong>
                    <?php if (!empty($footer['hours']['weekday']) || !empty($footer['hours']['hours'])): ?>
                        - <?php echo htmlspecialchars(($footer['hours']['weekday'] ?? '월~금')); ?> : <?php echo htmlspecialchars(($footer['hours']['hours'] ?? '')); ?><br>
                    <?php endif; ?>
                    <?php if (!empty($footer['hours']['lunch'])): ?>
                        - <?php echo htmlspecialchars($footer['hours']['lunch']); ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- 약관 링크 -->
            <div class="footer-terms">
                <a href="/terms/terms-of-service" target="_blank" rel="noopener noreferrer" class="footer-terms-link">이용약관</a>
                <a href="/terms/privacy-policy" target="_blank" rel="noopener noreferrer" class="footer-terms-link-bold">개인정보처리방침</a>
                <a href="/information-security" target="_blank" rel="noopener noreferrer" class="footer-terms-link">정보보호현황</a>
            </div>
        </div>
    </footer>
<?php endif; ?>

<?php
// 휴대폰 상담 신청 모달 포함
include __DIR__ . '/components/phone-consultation-modal.php';
?>
</body>
</html>

