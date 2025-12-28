<?php
/**
 * 평균별점 표시 방식 테스트
 * 현재: ceil() 사용 (올림)
 */

header('Content-Type: text/html; charset=utf-8');

echo "<h1>평균별점 표시 방식 테스트</h1>";

// 테스트 케이스
$testCases = [
    2.30 => "정확히 2.30",
    2.31 => "2.31",
    2.33 => "2.33",
    2.35 => "2.35",
    2.39 => "2.39",
    2.20 => "2.20",
    2.25 => "2.25",
    2.29 => "2.29",
    2.00 => "정확히 2.00",
    2.01 => "2.01",
    4.66 => "4.66",
    4.67 => "4.67",
    4.99 => "4.99",
    5.00 => "정확히 5.00"
];

echo "<h2>현재 방식: ceil() 사용 (소수 둘째자리에서 올림)</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>실제 평균</th><th>표시 결과</th><th>설명</th></tr>";

foreach ($testCases as $average => $description) {
    $displayed = ceil($average * 10) / 10;
    $color = ($displayed != $average) ? 'color: red;' : 'color: green;';
    echo "<tr>";
    echo "<td><strong>$average</strong></td>";
    echo "<td style='$color'><strong>$displayed</strong></td>";
    echo "<td>$description</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>다른 방식 비교</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; margin: 10px 0;'>";
echo "<tr><th>실제 평균</th><th>올림 (ceil)</th><th>반올림 (round)</th><th>내림 (floor)</th></tr>";

$testValues = [2.30, 2.31, 2.33, 2.35, 2.39, 4.66, 4.67, 4.99];

foreach ($testValues as $avg) {
    $ceil = ceil($avg * 10) / 10;
    $round = round($avg * 10) / 10;
    $floor = floor($avg * 10) / 10;
    
    echo "<tr>";
    echo "<td><strong>$avg</strong></td>";
    echo "<td>$ceil</td>";
    echo "<td style='color: blue;'>$round</td>";
    echo "<td>$floor</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #3b82f6; margin-top: 20px;'>";
echo "<h3>현재 방식 (올림)의 특징:</h3>";
echo "<ul>";
echo "<li><strong>2.30</strong> → <strong>2.3</strong> (그대로 표시)</li>";
echo "<li><strong>2.31</strong> → <strong>2.4</strong> (올림)</li>";
echo "<li><strong>2.33</strong> → <strong>2.4</strong> (올림)</li>";
echo "<li><strong>4.66</strong> → <strong>4.7</strong> (올림)</li>";
echo "<li><strong>4.67</strong> → <strong>4.7</strong> (올림)</li>";
echo "</ul>";
echo "<p><strong>장점:</strong> 항상 높은 점수로 표시되어 긍정적</p>";
echo "<p><strong>단점:</strong> 실제 평균보다 약간 높게 표시될 수 있음</p>";
echo "</div>";

echo "<div style='background: #f0fdf4; padding: 20px; border-radius: 8px; border-left: 4px solid #16a34a; margin-top: 20px;'>";
echo "<h3>반올림 방식 (round)의 특징:</h3>";
echo "<ul>";
echo "<li><strong>2.30</strong> → <strong>2.3</strong> (그대로 표시)</li>";
echo "<li><strong>2.31</strong> → <strong>2.3</strong> (반올림)</li>";
echo "<li><strong>2.35</strong> → <strong>2.4</strong> (반올림)</li>";
echo "<li><strong>4.66</strong> → <strong>4.7</strong> (반올림)</li>";
echo "<li><strong>4.65</strong> → <strong>4.7</strong> (반올림)</li>";
echo "</ul>";
echo "<p><strong>장점:</strong> 가장 일반적이고 자연스러운 방식</p>";
echo "<p><strong>단점:</strong> 없음</p>";
echo "</div>";

echo "<h3>권장사항</h3>";
echo "<p>일반적으로 <strong>반올림(round)</strong> 방식을 사용하는 것이 더 자연스럽습니다.</p>";
echo "<p>현재 올림 방식이 의도된 것인지 확인이 필요합니다.</p>";







