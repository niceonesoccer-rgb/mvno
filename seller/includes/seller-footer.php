        </div>
    </div>
<?php if (isset($showSellerNameModal) && $showSellerNameModal): 
    require_once __DIR__ . '/../../includes/data/path-config.php';
?>
<script>
    // 판매자명 중복 검사 (실시간)
    let sellerNameCheckTimeout = null;
    let sellerNameValid = false;
    const currentUserId = '<?php echo htmlspecialchars($currentUser['user_id'] ?? ''); ?>';
    const checkSellerDuplicateApiUrl = '<?php echo getApiPath('/api/check-seller-duplicate.php'); ?>';
    const updateSellerNameApiUrl = '<?php echo getApiPath('/api/update-seller-name.php'); ?>';
    const sellerProfileUrl = '<?php echo getAssetPath('/seller/profile.php'); ?>';
    
    function checkModalSellerNameDuplicate() {
        const sellerNameInput = document.getElementById('modalSellerName');
        const resultDiv = document.getElementById('modalSellerNameCheckResult');
        const goToEditBtn = document.getElementById('goToEditBtn');
        
        if (!sellerNameInput || !resultDiv) {
            return;
        }
        
        const value = sellerNameInput.value.trim();
        
        // 이전 타이머 취소
        if (sellerNameCheckTimeout) {
            clearTimeout(sellerNameCheckTimeout);
        }
        
        // 빈 값이면 결과 초기화
        if (value === '') {
            resultDiv.innerHTML = '';
            resultDiv.className = 'check-result';
            sellerNameInput.classList.remove('checked-valid', 'checked-invalid');
            sellerNameValid = false;
            if (goToEditBtn) {
                goToEditBtn.disabled = true;
            }
            return;
        }
        
        // 최소 길이 체크
        if (value.length < 2) {
            resultDiv.innerHTML = '<span style="color: #ef4444;">판매자명은 최소 2자 이상 입력해주세요.</span>';
            resultDiv.className = 'check-result error';
            sellerNameInput.classList.remove('checked-valid');
            sellerNameInput.classList.add('checked-invalid');
            sellerNameValid = false;
            if (goToEditBtn) {
                goToEditBtn.disabled = true;
            }
            return;
        }
        
        // 500ms 후 검사 (디바운싱)
        sellerNameCheckTimeout = setTimeout(() => {
            resultDiv.innerHTML = '<span style="color: #6b7280;">확인 중...</span>';
            resultDiv.className = 'check-result checking';
            
            fetch(`${checkSellerDuplicateApiUrl}?type=seller_name&value=${encodeURIComponent(value)}&current_user_id=${encodeURIComponent(currentUserId)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && !data.duplicate) {
                        resultDiv.innerHTML = '<span style="color: #10b981;">✓ ' + data.message + '</span>';
                        resultDiv.className = 'check-result success';
                        sellerNameInput.classList.remove('checked-invalid');
                        sellerNameInput.classList.add('checked-valid');
                        sellerNameValid = true;
                        const saveBtn = document.getElementById('saveSellerNameBtn');
                        if (saveBtn) {
                            saveBtn.disabled = false;
                        }
                    } else {
                        resultDiv.innerHTML = '<span style="color: #ef4444;">✗ ' + data.message + '</span>';
                        resultDiv.className = 'check-result error';
                        sellerNameInput.classList.remove('checked-valid');
                        sellerNameInput.classList.add('checked-invalid');
                        sellerNameValid = false;
                        const saveBtn = document.getElementById('saveSellerNameBtn');
                        if (saveBtn) {
                            saveBtn.disabled = true;
                        }
                    }
                })
                .catch(error => {
                    console.error('판매자명 중복 검사 오류:', error);
                    resultDiv.innerHTML = '<span style="color: #ef4444;">검사 중 오류가 발생했습니다.</span>';
                    resultDiv.className = 'check-result error';
                    sellerNameValid = false;
                    const saveBtn = document.getElementById('saveSellerNameBtn');
                    if (saveBtn) {
                        saveBtn.disabled = true;
                    }
                });
        }, 500);
    }
    
    // 판매자명 저장
    function saveSellerName() {
        if (!sellerNameValid) {
            alert('사용 가능한 판매자명을 입력해주세요.');
            return;
        }
        
        const sellerNameInput = document.getElementById('modalSellerName');
        const saveBtn = document.getElementById('saveSellerNameBtn');
        const sellerName = sellerNameInput.value.trim();
        
        if (sellerName.length < 2) {
            alert('판매자명은 최소 2자 이상 입력해주세요.');
            return;
        }
        
        // 버튼 비활성화 및 로딩 표시
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.textContent = '저장 중...';
        }
        
        // 판매자명 저장 API 호출
        const formData = new FormData();
        formData.append('seller_name', sellerName);
        
        fetch(updateSellerNameApiUrl, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 저장 성공 시: 모달 닫기 -> 계정정보 페이지로 이동
                const overlay = document.getElementById('sellerNameModalOverlay');
                if (overlay) {
                    overlay.style.display = 'none';
                }
                document.body.classList.remove('modal-open');

                // 저장 확인용 파라미터를 붙여서 이동(페이지에서 메시지 표시 등에 활용 가능)
                window.location.assign(sellerProfileUrl + '?seller_name_saved=1');
            } else {
                alert(data.message || '판매자명 저장에 실패했습니다.');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.textContent = '저장';
                }
            }
        })
        .catch(error => {
            console.error('판매자명 저장 오류:', error);
            alert('판매자명 저장 중 오류가 발생했습니다.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.textContent = '저장';
            }
        });
    }
    
    // 페이지 로드 시 모달 표시 및 입력 필드 이벤트 리스너 등록
    document.addEventListener('DOMContentLoaded', function() {
        const sellerNameInput = document.getElementById('modalSellerName');
        const saveBtn = document.getElementById('saveSellerNameBtn');
        
        if (sellerNameInput) {
            sellerNameInput.addEventListener('input', checkModalSellerNameDuplicate);
            // Enter 키로 저장
            sellerNameInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter' && sellerNameValid) {
                    e.preventDefault();
                    saveSellerName();
                }
            });
            // 초기 상태: 버튼 비활성화
            if (saveBtn) {
                saveBtn.disabled = true;
            }
        }
    });
</script>
<?php endif; ?>
</body>
</html>
















