<?php
/**
 * 가입 금지어 관리 페이지
 * 경로: /MVNO/admin/settings/forbidden-ids-manage.php
 */

require_once __DIR__ . '/../../includes/data/auth-functions.php';

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 권한 체크
if (!isAdmin()) {
    header('Location: /MVNO/admin/');
    exit;
}

$forbiddenIdsFile = __DIR__ . '/../../includes/data/forbidden-ids.json';
$error = '';
$success = '';

// 금지어 추가 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $newId = trim($_POST['forbidden_id'] ?? '');
    
    if (empty($newId)) {
        $error = '금지어를 입력해주세요.';
    } elseif (!preg_match('/^[a-zA-Z0-9]+$/', $newId)) {
        $error = '영문자와 숫자만 입력 가능합니다.';
    } else {
        $newId = strtolower($newId);
        $data = [];
        if (file_exists($forbiddenIdsFile)) {
            $content = file_get_contents($forbiddenIdsFile);
            $data = json_decode($content, true) ?: ['forbidden_ids' => []];
        } else {
            $data = ['forbidden_ids' => []];
        }
        
        $forbiddenIds = $data['forbidden_ids'] ?? [];
        
        if (in_array($newId, $forbiddenIds)) {
            $error = '이미 등록된 금지어입니다.';
        } else {
            $forbiddenIds[] = $newId;
            // 오름차순 정렬
            sort($forbiddenIds);
            $data['forbidden_ids'] = array_values($forbiddenIds);
            
            if (file_put_contents($forbiddenIdsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $success = '금지어가 추가되었습니다.';
                // 추가 후 금지어 목록 즉시 갱신
                $forbiddenIds = $data['forbidden_ids'];
            } else {
                $error = '금지어 추가 중 오류가 발생했습니다.';
            }
        }
    }
}

// 금지어 삭제 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $deleteId = trim($_POST['forbidden_id'] ?? '');
    
    if (!empty($deleteId)) {
        if (file_exists($forbiddenIdsFile)) {
            $content = file_get_contents($forbiddenIdsFile);
            $data = json_decode($content, true) ?: ['forbidden_ids' => []];
            $forbiddenIds = $data['forbidden_ids'] ?? [];
            
            // 대소문자 구분 없이 삭제
            $forbiddenIds = array_filter($forbiddenIds, function($id) use ($deleteId) {
                return strtolower(trim($id)) !== strtolower(trim($deleteId));
            });
            
            // 오름차순 정렬
            sort($forbiddenIds);
            $data['forbidden_ids'] = array_values($forbiddenIds);
            
            if (file_put_contents($forbiddenIdsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                $success = '금지어가 삭제되었습니다.';
                // 삭제 후 금지어 목록 즉시 갱신
                $forbiddenIds = $data['forbidden_ids'];
            } else {
                $error = '금지어 삭제 중 오류가 발생했습니다.';
            }
        }
    }
}

// CSV 다운로드 처리
if (isset($_GET['action']) && $_GET['action'] === 'download') {
    $format = $_GET['format'] ?? 'csv'; // csv만 지원
    
    if (file_exists($forbiddenIdsFile)) {
        $content = file_get_contents($forbiddenIdsFile);
        $data = json_decode($content, true) ?: ['forbidden_ids' => []];
        $forbiddenIds = $data['forbidden_ids'] ?? [];
        // 오름차순 정렬
        sort($forbiddenIds);
        $forbiddenIds = array_values($forbiddenIds);
    } else {
        $forbiddenIds = [];
    }
    
    // CSV 형식으로 다운로드
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="forbidden_ids_' . date('YmdHis') . '.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // BOM 추가 (엑셀에서 한글 깨짐 방지)
    echo "\xEF\xBB\xBF";
    
    // 헤더
    echo "순번,금지어\n";
    
    // 데이터
    foreach ($forbiddenIds as $index => $id) {
        echo ($index + 1) . ',"' . str_replace('"', '""', $id) . '"' . "\n";
    }
    
    exit;
}

// CSV 업로드 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload') {
    if (isset($_FILES['excel_file']) && $_FILES['excel_file']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['excel_file'];
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // CSV 파일만 허용
        if ($fileExtension !== 'csv') {
            $error = 'CSV 파일만 업로드 가능합니다. 엑셀 파일은 CSV 형식으로 저장하여 업로드해주세요.';
        } else {
            $uploadedIds = [];
            
            // CSV 파일 읽기
            $handle = fopen($file['tmp_name'], 'r');
            if ($handle !== false) {
                // BOM 제거를 위해 첫 줄 확인
                $firstLine = fgets($handle);
                $hasBOM = (substr($firstLine, 0, 3) === "\xEF\xBB\xBF");
                
                // 파일 포인터 리셋
                rewind($handle);
                if ($hasBOM) {
                    fseek($handle, 3); // BOM 건너뛰기
                }
                
                // 헤더 읽기
                $header = fgetcsv($handle);
                if ($header === false) {
                    $error = 'CSV 파일 형식이 올바르지 않습니다.';
                    fclose($handle);
                } else {
                    // 헤더 확인 (순번, 금지어 또는 다른 형식)
                    $headerLower = array_map('strtolower', array_map('trim', $header));
                    $idColumnIndex = 1; // 기본값: B열 (인덱스 1)
                    
                    // "금지어" 또는 "forbidden" 등의 키워드로 열 찾기
                    foreach ($headerLower as $idx => $col) {
                        if (in_array($col, ['금지어', 'forbidden', 'forbidden_id', 'id', '아이디'])) {
                            $idColumnIndex = $idx;
                            break;
                        }
                    }
                    
                    $rowNum = 0;
                    while (($row = fgetcsv($handle)) !== false) {
                        $rowNum++;
                        
                        // 빈 행 스킵
                        if (empty($row) || count($row) <= $idColumnIndex) {
                            continue;
                        }
                        
                        // 지정된 열에서 금지어 추출
                        $id = trim($row[$idColumnIndex] ?? '');
                        if (!empty($id)) {
                            // 헤더나 특수 값 제외 (숫자는 허용)
                            $idLower = strtolower($id);
                            if ($idLower !== '금지어' && $idLower !== '순번' && $idLower !== 'forbidden' && $idLower !== 'number' && $idLower !== 'no') {
                                // 숫자도 금지어로 포함 (원본 형식 유지)
                                $uploadedIds[] = $id;
                            }
                        }
                    }
                    fclose($handle);
                    
                    if (empty($uploadedIds)) {
                        $error = 'CSV 파일에서 금지어를 찾을 수 없습니다. 파일 형식을 확인해주세요.<br>
                        <strong>올바른 형식:</strong><br>
                        - 첫 번째 줄: 헤더 (순번, 금지어)<br>
                        - 두 번째 줄부터: 데이터 (1, admin 형식)<br>
                        - A열: 순번, B열: 금지어<br><br>
                        <strong>팁:</strong> 다운로드한 CSV 파일을 수정하여 업로드하시면 정확한 형식으로 업로드됩니다.';
                    }
                }
            } else {
                $error = 'CSV 파일을 열 수 없습니다.';
            }
            
            // 업로드된 금지어 처리
            if (empty($error) && !empty($uploadedIds)) {
                // 업로드된 금지어 정리 (빈 값 제거, 원본 형식 유지)
                $uploadedIds = array_filter(array_map('trim', $uploadedIds), function($id) {
                    $idLower = strtolower(trim($id));
                    return !empty($id) && $idLower !== '금지어' && $idLower !== '순번' && $idLower !== 'forbidden';
                });
                $uploadedIds = array_unique($uploadedIds);
                
                // 기존 금지어 가져오기 (원본 형식 유지)
                $existingIdsOriginal = [];
                $existingIdsLower = [];
                if (file_exists($forbiddenIdsFile)) {
                    $content = file_get_contents($forbiddenIdsFile);
                    $data = json_decode($content, true) ?: ['forbidden_ids' => []];
                    $existingIdsOriginal = $data['forbidden_ids'] ?? [];
                    $existingIdsLower = array_map('strtolower', array_map('trim', $existingIdsOriginal));
                }
                
                // 중복 체크 (대소문자 구분 없이, 원본 형식 유지)
                $newIds = [];
                $duplicateIds = [];
                foreach ($uploadedIds as $id) {
                    $idLower = strtolower(trim($id));
                    if (in_array($idLower, $existingIdsLower)) {
                        $duplicateIds[] = $id; // 원본 형식 유지
                    } else {
                        $newIds[] = $id; // 원본 형식 유지
                    }
                }
                
                // 기존 금지어는 원본 형식 유지, 새로운 금지어는 소문자로 추가
                $finalIds = $existingIdsOriginal;
                foreach ($newIds as $newId) {
                    if (!in_array($newId, $existingIdsLower)) {
                        $finalIds[] = $newId;
                    }
                }
                
                // 정렬 (오름차순)
                sort($finalIds);
                $data = ['forbidden_ids' => array_values($finalIds)];
                
                if (file_put_contents($forbiddenIdsFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))) {
                    $addedCount = count($newIds);
                    $duplicateCount = count($duplicateIds);
                    $totalCount = count($finalIds);
                    
                    // 업로드 후 금지어 목록 즉시 갱신
                    $forbiddenIds = $finalIds;
                    
                    if ($addedCount > 0) {
                        $success = $addedCount . '개의 금지어가 추가되었습니다. (총 ' . $totalCount . '개)';
                        if ($duplicateCount > 0) {
                            $success .= '<br><span style="color: #f59e0b;">' . $duplicateCount . '개의 중복된 금지어는 제외되었습니다.</span>';
                        }
                    } else {
                        if ($duplicateCount > 0) {
                            $error = '업로드된 ' . count($uploadedIds) . '개의 금지어가 모두 이미 등록되어 있습니다. (중복: ' . $duplicateCount . '개, 총 ' . $totalCount . '개)<br>
                            <details style="margin-top: 12px; padding: 12px; background: #f3f4f6; border-radius: 6px;">
                                <summary style="cursor: pointer; font-weight: 600; color: #374151;">업로드된 금지어 목록 보기</summary>
                                <div style="margin-top: 8px; padding: 8px; background: white; border-radius: 4px; max-height: 200px; overflow-y: auto;">
                                    <div style="display: flex; flex-wrap: wrap; gap: 4px;">
                                        ' . implode(', ', array_map(function($id) use ($existingIdsLower) {
                                            $idLower = strtolower(trim($id));
                                            $isExisting = in_array($idLower, $existingIdsLower);
                                            $color = $isExisting ? '#ef4444' : '#10b981';
                                            $badge = $isExisting ? ' (기존)' : ' (신규)';
                                            return '<span style="padding: 2px 8px; background: ' . $color . '; color: white; border-radius: 4px; font-size: 12px;">' . htmlspecialchars($id) . $badge . '</span>';
                                        }, array_slice($uploadedIds, 0, 50))) . '
                                        ' . (count($uploadedIds) > 50 ? '<span style="color: #6b7280;">... 외 ' . (count($uploadedIds) - 50) . '개</span>' : '') . '
                                    </div>
                                </div>
                            </details>';
                        } else {
                            $error = '금지어를 추가할 수 없습니다.';
                        }
                    }
                } else {
                    $error = '금지어 업로드 중 오류가 발생했습니다.';
                }
            } elseif (empty($error)) {
                $error = '업로드된 파일에서 금지어를 찾을 수 없습니다.';
            }
        }
    } else {
        $error = '파일 업로드에 실패했습니다.';
    }
}


// 금지어 목록 가져오기 (업로드 후 최신 데이터 반영)
// 업로드 처리에서 이미 $forbiddenIds가 설정되지 않은 경우에만 파일에서 읽기
if (!isset($forbiddenIds) || !is_array($forbiddenIds)) {
    $forbiddenIds = [];
    if (file_exists($forbiddenIdsFile)) {
        $content = file_get_contents($forbiddenIdsFile);
        if ($content !== false) {
            $data = json_decode($content, true);
            if ($data && isset($data['forbidden_ids']) && is_array($data['forbidden_ids'])) {
                $forbiddenIds = $data['forbidden_ids'];
                // 빈 값 제거 및 정렬 (오름차순)
                $forbiddenIds = array_filter($forbiddenIds, function($id) {
                    return !empty(trim($id));
                });
                sort($forbiddenIds);
                $forbiddenIds = array_values($forbiddenIds);
            }
        }
    }
} else {
    // 이미 설정된 경우 정렬만 수행 (오름차순)
    sort($forbiddenIds);
    $forbiddenIds = array_values($forbiddenIds);
}

// 현재 페이지 설정
$currentPage = 'forbidden-ids-manage.php';

// 헤더 포함
include '../includes/admin-header.php';
?>

<style>
    .admin-content {
        padding: 32px;
    }
    
    .page-header {
        margin-bottom: 32px;
    }
    
    .page-header h1 {
        font-size: 28px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 8px;
    }
    
    .page-header p {
        font-size: 16px;
        color: #6b7280;
    }
    
    .content-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 24px;
        margin-bottom: 32px;
    }
    
    @media (max-width: 1024px) {
        .content-grid {
            grid-template-columns: 1fr;
        }
    }
    
    .card {
        background: white;
        border-radius: 12px;
        padding: 24px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        border: 1px solid #e5e7eb;
    }
    
    .card-title {
        font-size: 20px;
        font-weight: 700;
        color: #1f2937;
        margin-bottom: 20px;
        padding-bottom: 12px;
        border-bottom: 2px solid #e5e7eb;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        font-size: 14px;
        font-weight: 600;
        color: #374151;
        margin-bottom: 8px;
    }
    
    .form-group input {
        width: 100%;
        padding: 12px 16px;
        border: 1px solid #d1d5db;
        border-radius: 8px;
        font-size: 15px;
        transition: border-color 0.2s;
        box-sizing: border-box;
    }
    
    .form-group input:focus {
        outline: none;
        border-color: #6366f1;
        box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.1);
    }
    
    .form-help {
        font-size: 13px;
        color: #6b7280;
        margin-top: 6px;
    }
    
    .btn {
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        font-size: 15px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }
    
    .btn-primary {
        background: #6366f1;
        color: white;
    }
    
    .btn-primary:hover {
        background: #4f46e5;
    }
    
    .btn-danger {
        background: #ef4444;
        color: white;
        padding: 6px 12px;
        font-size: 13px;
    }
    
    .btn-danger:hover {
        background: #dc2626;
    }
    
    .alert {
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 24px;
        font-size: 14px;
    }
    
    .alert-error {
        background: #fee2e2;
        color: #991b1b;
        border: 1px solid #ef4444;
    }
    
    .alert-success {
        background: #d1fae5;
        color: #065f46;
        border: 1px solid #10b981;
    }
    
    .forbidden-list {
        max-height: 500px;
        overflow-y: auto;
    }
    
    .forbidden-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
        transition: background 0.2s;
    }
    
    .forbidden-item:hover {
        background: #f9fafb;
    }
    
    .forbidden-item:last-child {
        border-bottom: none;
    }
    
    .forbidden-item-text {
        font-size: 14px;
        color: #374151;
        font-family: monospace;
        font-weight: 500;
    }
    
    .empty-state {
        text-align: center;
        padding: 40px;
        color: #9ca3af;
    }
    
    .stats-info {
        background: #f9fafb;
        padding: 16px;
        border-radius: 8px;
        margin-bottom: 20px;
    }
    
    .stats-info p {
        margin: 0;
        font-size: 14px;
        color: #6b7280;
    }
</style>

<div class="admin-content">
    <div class="page-header">
        <h1>가입 금지어 관리</h1>
        <p>판매자 가입 시 사용할 수 없는 아이디를 관리합니다.</p>
    </div>
    
    <?php if ($error): ?>
        <div class="alert alert-error">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="alert alert-success">
            <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <div class="content-grid">
        <!-- 금지어 추가 폼 -->
        <div class="card">
            <h2 class="card-title">금지어 추가</h2>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                
                <div class="form-group">
                    <label for="forbidden_id">금지어 <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="forbidden_id" name="forbidden_id" required 
                           placeholder="예: admin, test, 123" 
                           pattern="[a-zA-Z0-9]+"
                           title="영문자와 숫자만 입력 가능합니다."
                           value="<?php echo htmlspecialchars($_POST['forbidden_id'] ?? ''); ?>">
                    <div class="form-help">영문자와 숫자만 입력 가능합니다. 소문자로 자동 변환됩니다.</div>
                </div>
                
                <button type="submit" class="btn btn-primary">금지어 추가</button>
            </form>
            
            <div style="margin-top: 24px; padding-top: 24px; border-top: 1px solid #e5e7eb;">
                <h3 style="font-size: 16px; font-weight: 600; color: #374151; margin-bottom: 16px;">엑셀 파일 관리</h3>
                
                <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 16px;">
                    <a href="?action=download&format=csv" class="btn" style="background: #10b981; color: white; text-decoration: none; display: inline-block;">
                        📥 CSV 다운로드
                    </a>
                    
                    <form method="POST" enctype="multipart/form-data" style="display: inline-block;">
                        <input type="hidden" name="action" value="upload">
                        <label for="excel_file" class="btn" style="background: #6366f1; color: white; cursor: pointer; display: inline-block; margin: 0;">
                            📤 CSV 업로드
                        </label>
                        <input type="file" id="excel_file" name="excel_file" accept=".csv" required style="display: none;" onchange="this.form.submit();">
                    </form>
                </div>
                
                <div class="form-help" style="margin-top: 12px; font-size: 12px;">
                    <strong>지원 파일 형식:</strong><br>
                    - CSV 파일 (.csv)만 지원합니다<br>
                    - 첫 번째 줄은 헤더(순번,금지어)로 시작<br>
                    - 예: 1,admin<br>
                    - 엑셀 파일은 CSV 형식으로 저장하여 업로드해주세요<br>
                    - 다운로드한 CSV 파일을 수정하여 업로드 가능
                </div>
            </div>
        </div>
        
        <!-- 금지어 목록 -->
        <div class="card">
            <h2 class="card-title">금지어 목록</h2>
            
            <div class="stats-info">
                <p><strong>총 <?php echo count($forbiddenIds); ?>개</strong>의 금지어가 등록되어 있습니다.</p>
            </div>
            
            <?php if (empty($forbiddenIds)): ?>
                <div class="empty-state">
                    등록된 금지어가 없습니다.
                </div>
            <?php else: ?>
                <div class="forbidden-list" id="forbidden-list">
                    <?php foreach ($forbiddenIds as $id): ?>
                        <div class="forbidden-item" data-id="<?php echo htmlspecialchars($id); ?>">
                            <span class="forbidden-item-text"><?php echo htmlspecialchars($id); ?></span>
                            <form method="POST" style="display: inline;" class="delete-form" onsubmit="event.preventDefault(); deleteForbiddenId('<?php echo htmlspecialchars($id); ?>', this); return false;">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="forbidden_id" value="<?php echo htmlspecialchars($id); ?>">
                                <button type="submit" class="btn btn-danger">삭제</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
// 금지어 입력 필드 - 영문자와 숫자만 허용
document.addEventListener('DOMContentLoaded', function() {
    const forbiddenIdInput = document.getElementById('forbidden_id');
    if (forbiddenIdInput) {
        // input 이벤트로 한글 및 특수문자 제거
        forbiddenIdInput.addEventListener('input', function() {
            const cursorPos = this.selectionStart;
            const oldValue = this.value;
            const newValue = oldValue.replace(/[^a-zA-Z0-9]/g, '');
            
            if (oldValue !== newValue) {
                this.value = newValue;
                // 커서 위치 조정
                const diff = oldValue.length - newValue.length;
                const newPos = Math.max(0, cursorPos - diff);
                this.setSelectionRange(newPos, newPos);
            }
        });
        
        // paste 이벤트 처리
        forbiddenIdInput.addEventListener('paste', function(e) {
            e.preventDefault();
            const pastedText = (e.clipboardData || window.clipboardData).getData('text');
            const filteredText = pastedText.replace(/[^a-zA-Z0-9]/g, '');
            const cursorPos = this.selectionStart;
            const textBefore = this.value.substring(0, cursorPos);
            const textAfter = this.value.substring(this.selectionEnd);
            this.value = textBefore + filteredText + textAfter;
            this.setSelectionRange(cursorPos + filteredText.length, cursorPos + filteredText.length);
        });
        
        // 소문자로 자동 변환
        forbiddenIdInput.addEventListener('blur', function() {
            this.value = this.value.toLowerCase();
        });
    }
});

function deleteForbiddenId(id, formElement) {
    // 현재 스크롤 위치 저장
    sessionStorage.setItem('forbiddenListScrollPos', window.pageYOffset || document.documentElement.scrollTop);
    
    showConfirm('정말 이 금지어를 삭제하시겠습니까?', '금지어 삭제').then(result => {
        if (result) {
            // 폼 데이터 준비
            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('forbidden_id', id);
            
            // AJAX로 삭제 요청
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(html => {
                // 페이지 새로고침
                window.location.reload();
            })
            .catch(error => {
                console.error('삭제 중 오류:', error);
                showAlert('삭제 중 오류가 발생했습니다.');
            });
        }
    });
}

// 페이지 로드 시 스크롤 위치 복원
window.addEventListener('load', function() {
    const scrollPos = sessionStorage.getItem('forbiddenListScrollPos');
    if (scrollPos) {
        setTimeout(function() {
            window.scrollTo(0, parseInt(scrollPos));
            sessionStorage.removeItem('forbiddenListScrollPos');
        }, 100);
    }
});
</script>

<?php
// 푸터 포함
include '../includes/admin-footer.php';
?>

