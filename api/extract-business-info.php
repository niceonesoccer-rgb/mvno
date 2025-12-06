<?php
/**
 * 사업자등록증 이미지에서 정보 추출 API
 * OCR을 사용하여 사업자등록번호, 회사명 등을 추출
 */

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST 요청만 허용됩니다.']);
    exit;
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '파일 업로드에 실패했습니다.']);
    exit;
}

$file = $_FILES['image'];
$allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => '이미지 파일만 업로드 가능합니다.']);
    exit;
}

// 파일 크기 확인 (5MB)
if ($file['size'] > 5 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => '파일 크기는 5MB 이하여야 합니다.']);
    exit;
}

// 임시 파일 경로
$tmpPath = $file['tmp_name'];

// TODO: 실제 OCR API 연동 (네이버 Clova OCR, 카카오 OCR 등)
// 현재는 더미 데이터 반환 (실제 구현 시 OCR API로 교체 필요)

// OCR 처리 시뮬레이션 (실제로는 OCR API 호출)
// 예시: 네이버 Clova OCR 또는 카카오 OCR API 사용
// $ocrResult = callOcrApi($tmpPath);

// OCR 결과를 저장할 배열
$extractedData = [
    'business_number' => '',
    'company_name' => '',
    'representative' => '',
    'business_type' => '',
    'business_item' => '',
    'address' => ''
];

// 실제 OCR API 연동 (네이버 Clova OCR 또는 카카오 OCR)
// API 키는 환경 변수나 설정 파일에서 가져오는 것을 권장합니다
$ocrApiKey = getenv('OCR_API_KEY') ?: '';
$ocrSecretKey = getenv('OCR_SECRET_KEY') ?: '';

// OCR API가 설정되어 있으면 실제 OCR 처리
if (!empty($ocrApiKey) && !empty($ocrSecretKey)) {
    // 네이버 Clova OCR 사용
    $apiUrl = 'https://naveropenapi.apigw.ntruss.com/ocr/v1/business';
    $headers = [
        'X-OCR-SECRET: ' . $ocrSecretKey,
        'Content-Type: application/json'
    ];
    
    $imageData = base64_encode(file_get_contents($tmpPath));
    $imageFormat = pathinfo($file['name'], PATHINFO_EXTENSION);
    if ($imageFormat === 'jpg') $imageFormat = 'jpeg';
    
    $data = [
        'version' => 'V1',
        'requestId' => uniqid(),
        'timestamp' => time(),
        'images' => [
            [
                'format' => $imageFormat,
                'name' => 'business_license',
                'data' => $imageData
            ]
        ]
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $ocrResult = json_decode($response, true);
        
        // OCR 결과 파싱하여 정보 추출
        if (isset($ocrResult['images'][0]['fields'])) {
            foreach ($ocrResult['images'][0]['fields'] as $field) {
                $name = $field['name'] ?? '';
                $value = trim($field['inferText'] ?? '');
                
                // 필드명에 따라 매핑
                if (strpos($name, '사업자등록번호') !== false || (strpos($value, '-') !== false && preg_match('/\d{3}-\d{2}-\d{5}/', $value))) {
                    $extractedData['business_number'] = $value;
                } elseif (strpos($name, '상호') !== false || strpos($name, '회사명') !== false || strpos($name, '법인명') !== false) {
                    $extractedData['company_name'] = $value;
                } elseif (strpos($name, '대표자') !== false || strpos($name, '성명') !== false) {
                    $extractedData['representative'] = $value;
                } elseif (strpos($name, '업종') !== false) {
                    $extractedData['business_type'] = $value;
                } elseif (strpos($name, '업태') !== false) {
                    $extractedData['business_item'] = $value;
                } elseif (strpos($name, '주소') !== false || strpos($name, '소재지') !== false) {
                    $extractedData['address'] = $value;
                }
            }
        }
    }
}

// OCR API 연동 예시 (주석 처리)
/*
// 네이버 Clova OCR 사용 예시
$apiUrl = 'https://naveropenapi.apigw.ntruss.com/ocr/v1/business';
$headers = [
    'X-OCR-SECRET: ' . $ocrSecretKey,
    'Content-Type: application/json'
];
$data = [
    'version' => 'V1',
    'requestId' => uniqid(),
    'timestamp' => time(),
    'images' => [
        [
            'format' => 'jpg',
            'name' => 'business_license',
            'data' => base64_encode(file_get_contents($tmpPath))
        ]
    ]
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$ocrResult = json_decode($response, true);

// OCR 결과 파싱하여 정보 추출
if (isset($ocrResult['images'][0]['fields'])) {
    foreach ($ocrResult['images'][0]['fields'] as $field) {
        $name = $field['name'] ?? '';
        $value = $field['inferText'] ?? '';
        
        // 필드명에 따라 매핑
        if (strpos($name, '사업자등록번호') !== false || strpos($value, '-') !== false) {
            $extractedData['business_number'] = $value;
        } elseif (strpos($name, '상호') !== false || strpos($name, '회사명') !== false) {
            $extractedData['company_name'] = $value;
        } elseif (strpos($name, '대표자') !== false) {
            $extractedData['representative'] = $value;
        } elseif (strpos($name, '업종') !== false) {
            $extractedData['business_type'] = $value;
        } elseif (strpos($name, '업태') !== false) {
            $extractedData['business_item'] = $value;
        } elseif (strpos($name, '주소') !== false) {
            $extractedData['address'] = $value;
        }
    }
}
*/

// 추출된 정보가 있는지 확인
$hasData = false;
foreach ($extractedData as $value) {
    if (!empty($value)) {
        $hasData = true;
        break;
    }
}

if ($hasData) {
    echo json_encode([
        'success' => true,
        'data' => $extractedData,
        'message' => '정보가 성공적으로 추출되었습니다.'
    ]);
} else {
    echo json_encode([
        'success' => true,
        'data' => $extractedData,
        'message' => 'OCR API가 설정되지 않았거나 정보를 추출할 수 없습니다. 수동으로 입력해주세요.'
    ]);
}

