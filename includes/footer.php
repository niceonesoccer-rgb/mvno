<?php
require_once __DIR__ . '/data/path-config.php';
require_once __DIR__ . '/data/site-settings.php';
$siteSettings = getSiteSettings();
$footer = $siteSettings['footer'] ?? [];
$site = $siteSettings['site'] ?? [];
?>

<?php if (isset($is_main_page) && $is_main_page): ?>
    <footer class="footer">
        <div class="footer-body">
            <!-- 약관 링크 및 기타 링크 (상단) -->
            <?php 
            $terms = $footer['terms'] ?? [];
            $termsOfServiceText = !empty($terms['terms_of_service']['text']) ? $terms['terms_of_service']['text'] : '이용약관';
            $privacyPolicyText = !empty($terms['privacy_policy']['text']) ? $terms['privacy_policy']['text'] : '개인정보처리방침';
            $hasTerms = !empty($termsOfServiceText) || !empty($privacyPolicyText);
            ?>
            <?php if ($hasTerms): ?>
            <div class="footer-terms">
                <?php 
                $termsOfServiceText = !empty($terms['terms_of_service']['text']) ? $terms['terms_of_service']['text'] : '이용약관';
                $termsOfServiceUrl = !empty($terms['terms_of_service']['url']) ? getAssetPath($terms['terms_of_service']['url']) : getAssetPath('/terms/view.php?type=terms_of_service');
                ?>
                <?php if (!empty($termsOfServiceText)): ?>
                    <a href="<?php echo htmlspecialchars($termsOfServiceUrl); ?>" class="footer-terms-link"><?php echo htmlspecialchars($termsOfServiceText); ?></a>
                <?php endif; ?>
                <?php 
                $privacyPolicyText = !empty($terms['privacy_policy']['text']) ? $terms['privacy_policy']['text'] : '개인정보처리방침';
                $privacyPolicyUrl = !empty($terms['privacy_policy']['url']) ? getAssetPath($terms['privacy_policy']['url']) : getAssetPath('/terms/view.php?type=privacy_policy');
                ?>
                <?php if (!empty($privacyPolicyText)): ?>
                    <a href="<?php echo htmlspecialchars($privacyPolicyUrl); ?>" class="footer-terms-link-bold"><?php echo htmlspecialchars($privacyPolicyText); ?></a>
                <?php endif; ?>
                <?php 
                // 외부 링크 표시
                $externalLinks = $terms['external_links'] ?? [];
                if (!empty($externalLinks['spam_center']['url'])): ?>
                    <a href="<?php echo htmlspecialchars($externalLinks['spam_center']['url']); ?>" target="_blank" rel="noopener noreferrer" class="footer-terms-link"><?php echo htmlspecialchars($externalLinks['spam_center']['text'] ?? '불법스팸대응센터'); ?></a>
                <?php endif; ?>
                <?php if (!empty($externalLinks['msafer']['url'])): ?>
                    <a href="<?php echo htmlspecialchars($externalLinks['msafer']['url']); ?>" target="_blank" rel="noopener noreferrer" class="footer-terms-link"><?php echo htmlspecialchars($externalLinks['msafer']['text'] ?? '명의도용방지서비스'); ?></a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            
            <!-- 브랜드 로고 -->
            <a href="<?php echo getBasePath(); ?>/" aria-label="<?php echo htmlspecialchars($site['name_ko'] ?? '유심킹'); ?>" class="footer-brand"></a>
            
            <!-- 회사 정보 및 고객센터 -->
            <div class="footer-info-wrapper">
                <address class="footer-contact">
                    <strong class="font-bold"><?php echo htmlspecialchars($footer['company_name'] ?? '(주)유심킹'); ?></strong><br>
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
        </div>
    </footer>
<?php endif; ?>

<?php
// 휴대폰 상담 신청 모달 포함
include __DIR__ . '/components/phone-consultation-modal.php';
?>
</body>
</html>

