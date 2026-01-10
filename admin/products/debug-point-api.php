<?php
/**
 * 포인트 API 디버깅 페이지
 * 웹에서 API 호출이 실패하는 원인을 찾기 위한 디버깅 도구
 */

require_once __DIR__ . '/../includes/admin-header.php';
require_once __DIR__ . '/../../includes/data/path-config.php';

// API 경로 설정
$apiUpdatePointPath = getAssetPath("/api/admin/update-product-point.php");
if (strpos($apiUpdatePointPath, 'http') !== 0 && isset($_SERVER['HTTP_HOST'])) {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $apiUpdatePointPath = $protocol . '://' . $_SERVER['HTTP_HOST'] . $apiUpdatePointPath;
}
?>

<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>포인트 API 디버깅</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f9fafb;
        }
        .debug-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .debug-section h2 {
            margin-top: 0;
            color: #1f2937;
            border-bottom: 2px solid #e5e7eb;
            padding-bottom: 10px;
        }
        .info-item {
            margin: 10px 0;
            padding: 10px;
            background: #f3f4f6;
            border-radius: 4px;
            font-family: monospace;
            word-break: break-all;
        }
        .info-label {
            font-weight: 600;
            color: #374151;
            margin-bottom: 5px;
        }
        .test-button {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            margin: 5px;
        }
        .test-button:hover {
            background: #2563eb;
        }
        .result-box {
            margin-top: 20px;
            padding: 15px;
            border-radius: 6px;
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            max-height: 400px;
            overflow-y: auto;
        }
        .result-box pre {
            margin: 0;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        .success {
            background: #d1fae5;
            border-color: #10b981;
        }
        .error {
            background: #fee2e2;
            border-color: #ef4444;
        }
    </style>
</head>
<body>
    <h1>포인트 API 디버깅 도구</h1>
    
    <div class="debug-section">
        <h2>1. 서버 환경 정보</h2>
        <div class="info-item">
            <div class="info-label">HTTP_HOST:</div>
            <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A'); ?>
        </div>
        <div class="info-item">
            <div class="info-label">REQUEST_METHOD:</div>
            <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD'] ?? 'N/A'); ?>
        </div>
        <div class="info-item">
            <div class="info-label">HTTPS:</div>
            <?php echo (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'Yes' : 'No'; ?>
        </div>
        <div class="info-item">
            <div class="info-label">SERVER_SOFTWARE:</div>
            <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'N/A'); ?>
        </div>
        <div class="info-item">
            <div class="info-label">PHP Version:</div>
            <?php echo phpversion(); ?>
        </div>
    </div>
    
    <div class="debug-section">
        <h2>2. API 경로 정보</h2>
        <div class="info-item">
            <div class="info-label">API URL:</div>
            <?php echo htmlspecialchars($apiUpdatePointPath); ?>
        </div>
        <div class="info-item">
            <div class="info-label">API 파일 존재 여부:</div>
            <?php 
            $apiFile = __DIR__ . '/../../api/admin/update-product-point.php';
            echo file_exists($apiFile) ? '✓ 존재함' : '✗ 없음';
            ?>
        </div>
        <div class="info-item">
            <div class="info-label">API 파일 경로:</div>
            <?php echo htmlspecialchars($apiFile); ?>
        </div>
    </div>
    
    <div class="debug-section">
        <h2>3. API 테스트</h2>
        <p>다음 버튼들을 클릭하여 API 호출을 테스트하세요:</p>
        
        <button class="test-button" onclick="testGetRequest()">GET 요청 테스트</button>
        <button class="test-button" onclick="testPostRequest()">POST 요청 테스트 (JSON)</button>
        <button class="test-button" onclick="testPostRequestFormData()">POST 요청 테스트 (FormData)</button>
        <button class="test-button" onclick="testPostRequestWithData()">POST 요청 테스트 (실제 데이터)</button>
        
        <div id="testResult" class="result-box" style="display: none;">
            <pre id="testResultContent"></pre>
        </div>
    </div>
    
    <div class="debug-section">
        <h2>4. 직접 API 호출 테스트</h2>
        <p>아래 폼을 사용하여 직접 API를 호출할 수 있습니다:</p>
        <form id="directTestForm" style="margin-top: 15px;">
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Product ID:</label>
                <input type="number" id="testProductId" value="1" style="width: 200px; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Point Setting:</label>
                <input type="number" id="testPointSetting" value="1000" step="1000" style="width: 200px; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px;">
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display: block; margin-bottom: 5px; font-weight: 600;">Benefit Description:</label>
                <textarea id="testBenefitDescription" style="width: 100%; padding: 8px; border: 1px solid #d1d5db; border-radius: 4px; min-height: 60px;">테스트 혜택 내용</textarea>
            </div>
            <button type="button" class="test-button" onclick="testDirectAPI()">API 호출 테스트</button>
        </form>
        
        <div id="directTestResult" class="result-box" style="display: none; margin-top: 15px;">
            <pre id="directTestResultContent"></pre>
        </div>
    </div>

    <script>
        const API_URL = '<?php echo htmlspecialchars($apiUpdatePointPath, ENT_QUOTES, 'UTF-8'); ?>';
        
        function showResult(elementId, contentId, data, isError = false) {
            const element = document.getElementById(elementId);
            const content = document.getElementById(contentId);
            element.style.display = 'block';
            element.className = 'result-box ' + (isError ? 'error' : 'success');
            content.textContent = typeof data === 'object' ? JSON.stringify(data, null, 2) : data;
        }
        
        async function testGetRequest() {
            try {
                console.log('Testing GET request to:', API_URL);
                const response = await fetch(API_URL, {
                    method: 'GET'
                });
                const text = await response.text();
                showResult('testResult', 'testResultContent', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries()),
                    body: text
                }, response.status >= 400);
            } catch (error) {
                showResult('testResult', 'testResultContent', {
                    error: error.message,
                    stack: error.stack
                }, true);
            }
        }
        
        async function testPostRequest() {
            try {
                console.log('Testing POST request (JSON) to:', API_URL);
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({})
                });
                const text = await response.text();
                showResult('testResult', 'testResultContent', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries()),
                    body: text
                }, response.status >= 400);
            } catch (error) {
                showResult('testResult', 'testResultContent', {
                    error: error.message,
                    stack: error.stack
                }, true);
            }
        }
        
        async function testPostRequestFormData() {
            try {
                console.log('Testing POST request (FormData) to:', API_URL);
                const formData = new FormData();
                formData.append('product_id', '1');
                formData.append('point_setting', '1000');
                formData.append('point_benefit_description', '테스트');
                
                const response = await fetch(API_URL, {
                    method: 'POST',
                    body: formData
                });
                const text = await response.text();
                showResult('testResult', 'testResultContent', {
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries()),
                    body: text
                }, response.status >= 400);
            } catch (error) {
                showResult('testResult', 'testResultContent', {
                    error: error.message,
                    stack: error.stack
                }, true);
            }
        }
        
        async function testPostRequestWithData() {
            try {
                const testData = {
                    product_id: 1,
                    point_setting: 1000,
                    point_benefit_description: '테스트 혜택 내용'
                };
                
                console.log('Testing POST request with data:', testData);
                console.log('API URL:', API_URL);
                
                const requestOptions = {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    body: JSON.stringify(testData)
                };
                
                console.log('Request options:', requestOptions);
                
                const response = await fetch(API_URL, requestOptions);
                console.log('Response status:', response.status);
                console.log('Response headers:', Object.fromEntries(response.headers.entries()));
                
                const contentType = response.headers.get('content-type');
                let body;
                if (contentType && contentType.includes('application/json')) {
                    body = await response.json();
                } else {
                    body = await response.text();
                }
                
                showResult('testResult', 'testResultContent', {
                    request: requestOptions,
                    status: response.status,
                    statusText: response.statusText,
                    headers: Object.fromEntries(response.headers.entries()),
                    body: body
                }, response.status >= 400);
            } catch (error) {
                console.error('Error:', error);
                showResult('testResult', 'testResultContent', {
                    error: error.message,
                    stack: error.stack
                }, true);
            }
        }
        
        async function testDirectAPI() {
            const productId = parseInt(document.getElementById('testProductId').value) || 0;
            const pointSetting = parseInt(document.getElementById('testPointSetting').value) || 0;
            const benefitDescription = document.getElementById('testBenefitDescription').value.trim();
            
            if (productId <= 0) {
                alert('유효한 Product ID를 입력하세요.');
                return;
            }
            
            try {
                const testData = {
                    product_id: productId,
                    point_setting: pointSetting,
                    point_benefit_description: benefitDescription
                };
                
                console.log('Direct API Test - URL:', API_URL);
                console.log('Direct API Test - Data:', testData);
                
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json; charset=utf-8'
                    },
                    body: JSON.stringify(testData)
                });
                
                const contentType = response.headers.get('content-type');
                let body;
                if (contentType && contentType.includes('application/json')) {
                    body = await response.json();
                } else {
                    body = await response.text();
                }
                
                showResult('directTestResult', 'directTestResultContent', {
                    request: {
                        url: API_URL,
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json; charset=utf-8'
                        },
                        body: testData
                    },
                    response: {
                        status: response.status,
                        statusText: response.statusText,
                        headers: Object.fromEntries(response.headers.entries()),
                        body: body
                    }
                }, response.status >= 400);
            } catch (error) {
                console.error('Error:', error);
                showResult('directTestResult', 'directTestResultContent', {
                    error: error.message,
                    stack: error.stack
                }, true);
            }
        }
    </script>
</body>
</html>
