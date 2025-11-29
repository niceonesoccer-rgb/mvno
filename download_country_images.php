<?php
/**
 * 국가 이미지 다운로드 스크립트
 * 브라우저에서 실행: http://localhost/mvno/download_country_images.php
 */

// 이미지 URL 목록
$images = [
    // 인기국가
    'japan.svg' => 'https://asset.usimsa.com/images/country/179d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'china.svg' => 'https://asset.usimsa.com/images/country/1b9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'taiwan.svg' => 'https://asset.usimsa.com/images/country/189d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'philippines.svg' => 'https://asset.usimsa.com/images/country/1f9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'thailand.svg' => 'https://asset.usimsa.com/images/country/199d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'vietnam.svg' => 'https://asset.usimsa.com/images/country/1a9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'malaysia.svg' => 'https://asset.usimsa.com/images/country/1e9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'singapore.svg' => 'https://asset.usimsa.com/images/country/1d9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'usa.svg' => 'https://asset.usimsa.com/images/country/1c9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'australia.svg' => 'https://asset.usimsa.com/images/country/2a9d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'indonesia.svg' => 'https://asset.usimsa.com/images/country/239d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'uae.svg' => 'https://asset.usimsa.com/images/country/289d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'hongkong.svg' => 'https://asset.usimsa.com/images/country/219d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'guam.svg' => 'https://asset.usimsa.com/images/country/209d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'canada.svg' => 'https://asset.usimsa.com/images/country/b3578299-836e-ee11-bbf0-28187860d6d3.svg',
    'cambodia.svg' => 'https://asset.usimsa.com/images/country/bd578299-836e-ee11-bbf0-28187860d6d3.svg',
    'italy.svg' => 'https://asset.usimsa.com/images/country/299d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'macau.svg' => 'https://asset.usimsa.com/images/country/229d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'france.svg' => 'https://asset.usimsa.com/images/country/269d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'spain.svg' => 'https://asset.usimsa.com/images/country/b4578299-836e-ee11-bbf0-28187860d6d3.svg',
    'turkey.svg' => 'https://asset.usimsa.com/images/country/d68aecfe-93db-ee11-85f9-002248f774ee.svg',
    'uk.svg' => 'https://asset.usimsa.com/images/country/279d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'germany.svg' => 'https://asset.usimsa.com/images/country/259d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'qatar.svg' => 'https://asset.usimsa.com/images/country/bb578299-836e-ee11-bbf0-28187860d6d3.svg',
    'portugal.svg' => 'https://asset.usimsa.com/images/country/b5578299-836e-ee11-bbf0-28187860d6d3.svg',
    'india.svg' => 'https://asset.usimsa.com/images/country/bf578299-836e-ee11-bbf0-28187860d6d3.svg',
    'mexico.svg' => 'https://asset.usimsa.com/images/country/be578299-836e-ee11-bbf0-28187860d6d3.svg',
    'laos.svg' => 'https://asset.usimsa.com/images/country/b83e155f-8bae-ee11-be9e-002248f7dbdd.svg',
    'southkorea.svg' => 'https://asset.usimsa.com/images/country/249d30e5-6a69-ee11-bbf0-28187860d6d3.svg',
    'denmark.svg' => 'https://asset.usimsa.com/images/country/b8578299-836e-ee11-bbf0-28187860d6d3.svg',
    'maldives.svg' => 'https://asset.usimsa.com/images/country/1aa267d1-99aa-ee11-be9e-002248f7dbdd.svg',
    'sweden.svg' => 'https://asset.usimsa.com/images/country/b7578299-836e-ee11-bbf0-28187860d6d3.svg',
    'austria.svg' => 'https://asset.usimsa.com/images/country/b9578299-836e-ee11-bbf0-28187860d6d3.svg',
    'newzealand.svg' => 'https://asset.usimsa.com/images/country/a2472a10-7bdb-ee11-85f9-002248f774ee.svg',
    'ireland.svg' => 'https://asset.usimsa.com/images/country/b6578299-836e-ee11-bbf0-28187860d6d3.svg',
    'mongolia.svg' => 'https://asset.usimsa.com/images/country/b2578299-836e-ee11-bbf0-28187860d6d3.svg',
    'kazakhstan.svg' => 'https://asset.usimsa.com/images/country/D732E6AD-D4F4-EF11-90CB-6045BD4556A6.svg',
    
    // 다국가
    'global-151.svg' => 'https://asset.usimsa.com/crm/country/ABBB2654-909A-F011-B3CD-002248F7DF6B-1758892918652.svg',
    'europe-42.svg' => 'https://asset.usimsa.com/images/country/b4c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'europe-36.svg' => 'https://asset.usimsa.com/images/country/4086995C-1709-F011-AAA7-002248F7D829.svg',
    'europe-33.svg' => 'https://asset.usimsa.com/images/country/b3c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'hongkong-macau.svg' => 'https://asset.usimsa.com/images/country/b1c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'china-hongkong-macau.svg' => 'https://asset.usimsa.com/images/country/352ADA47-CA02-F011-AAA7-002248F7D829.svg',
    'southeast-asia-3.svg' => 'https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'usa-canada.svg' => 'https://asset.usimsa.com/images/country/b0c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'australia-newzealand.svg' => 'https://asset.usimsa.com/images/country/b2c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'asia-13.svg' => 'https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'guam-saipan.svg' => 'https://asset.usimsa.com/images/country/b6c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'north-central-america-3.svg' => 'https://asset.usimsa.com/images/country/b0c9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'southeast-asia-8.svg' => 'https://asset.usimsa.com/images/country/afc9012b-7f69-ee11-bbf0-28187860d6d3.svg',
    'south-america-11.png' => 'https://asset.usimsa.com/images/country/9788CAF5-48C3-EF11-88CF-002248F770DC.png',
];

$dir = __DIR__ . '/images/country/';
if (!is_dir($dir)) {
    mkdir($dir, 0755, true);
}

$downloaded = 0;
$failed = 0;
$results = [];

foreach ($images as $filename => $url) {
    $filepath = $dir . $filename;
    
    // 이미 파일이 있으면 스킵
    if (file_exists($filepath)) {
        $results[] = "✓ 이미 존재: $filename";
        continue;
    }
    
    // 이미지 다운로드
    $imageData = @file_get_contents($url);
    
    if ($imageData !== false) {
        if (file_put_contents($filepath, $imageData)) {
            $downloaded++;
            $results[] = "✓ 다운로드 완료: $filename";
        } else {
            $failed++;
            $results[] = "✗ 저장 실패: $filename";
        }
    } else {
        $failed++;
        $results[] = "✗ 다운로드 실패: $filename";
    }
}

?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>국가 이미지 다운로드</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
        }
        h1 {
            color: #333;
        }
        .result {
            padding: 10px;
            margin: 5px 0;
            border-radius: 4px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
        }
        .summary {
            margin-top: 20px;
            padding: 15px;
            background-color: #e7f3ff;
            border-radius: 4px;
        }
    </style>
</head>
<body>
    <h1>국가 이미지 다운로드 결과</h1>
    
    <div class="summary">
        <strong>요약:</strong><br>
        다운로드 완료: <?php echo $downloaded; ?>개<br>
        실패: <?php echo $failed; ?>개<br>
        총 이미지: <?php echo count($images); ?>개
    </div>
    
    <h2>상세 결과:</h2>
    <?php foreach ($results as $result): ?>
        <div class="result <?php echo strpos($result, '✗') !== false ? 'error' : 'success'; ?>">
            <?php echo htmlspecialchars($result); ?>
        </div>
    <?php endforeach; ?>
    
    <p style="margin-top: 30px;">
        <a href="esim.php">해외이심 페이지로 이동</a>
    </p>
</body>
</html>

