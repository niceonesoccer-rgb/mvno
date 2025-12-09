<?php
/**
 * 단말기 데이터 확인 및 추가 스크립트
 */

require_once __DIR__ . '/../includes/data/db-config.php';

$pdo = getDBConnection();

if (!$pdo) {
    die('데이터베이스 연결에 실패했습니다.');
}

echo "<h2>단말기 데이터 확인 및 추가</h2>";

// 테이블 존재 확인
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'devices'");
    $tableExists = $stmt->fetch();
    
    if (!$tableExists) {
        echo "<p style='color: red;'>❌ devices 테이블이 존재하지 않습니다. 먼저 database/device_tables.sql 파일을 실행하세요.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✅ devices 테이블이 존재합니다.</p>";
    
    // 현재 등록된 단말기 수 확인
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM devices");
    $count = $stmt->fetch();
    echo "<p>현재 등록된 단말기 수: <strong>{$count['cnt']}개</strong></p>";
    
    // 제조사 확인
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM device_manufacturers");
    $manufacturerCount = $stmt->fetch();
    echo "<p>등록된 제조사 수: <strong>{$manufacturerCount['cnt']}개</strong></p>";
    
    // 단말기 목록 표시
    if ($count['cnt'] > 0) {
        echo "<h3>등록된 단말기 목록:</h3>";
        echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'>";
        echo "<th>번호</th><th>제조사</th><th>단말기명</th><th>용량</th><th>출고가</th><th>색상</th><th>출시일</th><th>상태</th>";
        echo "</tr>";
        
        $stmt = $pdo->query("
            SELECT d.*, m.name as manufacturer_name 
            FROM devices d 
            LEFT JOIN device_manufacturers m ON d.manufacturer_id = m.id 
            ORDER BY m.display_order ASC, m.name ASC, d.name ASC
        ");
        $devices = $stmt->fetchAll();
        
        foreach ($devices as $index => $device) {
            echo "<tr>";
            echo "<td>" . ($index + 1) . "</td>";
            echo "<td>" . htmlspecialchars($device['manufacturer_name'] ?? '-') . "</td>";
            echo "<td>" . htmlspecialchars($device['name']) . "</td>";
            echo "<td>" . htmlspecialchars($device['storage'] ?? '-') . "</td>";
            echo "<td>" . ($device['release_price'] ? number_format($device['release_price']) . '원' : '-') . "</td>";
            echo "<td>" . htmlspecialchars($device['color'] ?? '-') . "</td>";
            echo "<td>" . ($device['release_date'] ?? '-') . "</td>";
            echo "<td>" . ($device['status'] === 'active' ? '활성' : '비활성') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>⚠️ 등록된 단말기가 없습니다.</p>";
        echo "<p>데이터를 추가하려면 database/insert_devices.sql 파일을 실행하세요.</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>오류: " . htmlspecialchars($e->getMessage()) . "</p>";
}

