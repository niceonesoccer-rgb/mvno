<?php
/**
 * 서버에서 데이터베이스를 덤프하는 스크립트
 * 
 * 사용 방법:
 * 1. 이 파일을 서버에 업로드 (웹 루트 디렉토리 또는 임시 폴더)
 * 2. 브라우저에서 접속: http://서버주소/export_database.php
 * 3. SQL 파일이 자동으로 다운로드됨
 * 4. 다운로드 후 보안을 위해 이 파일을 삭제하세요!
 */

// 보안: IP 체크 (선택사항 - 필요시 활성화)
// $allowed_ips = ['127.0.0.1', 'YOUR_IP_ADDRESS'];
// if (!in_array($_SERVER['REMOTE_ADDR'], $allowed_ips)) {
//     die('Access Denied');
// }

// 데이터베이스 설정
$db_host = 'db.ganadamobile.co.kr';
$db_name = 'dbdanora';
$db_user = 'danora';
$db_pass = '2leosim@*ly';

// 출력 버퍼 시작
ob_start();

try {
    // mysqldump 명령어 경로 확인 (일반적인 경로들)
    $mysqldump_paths = [
        '/usr/bin/mysqldump',
        '/usr/local/bin/mysqldump',
        'mysqldump', // PATH에 있는 경우
    ];
    
    $mysqldump = null;
    foreach ($mysqldump_paths as $path) {
        if (is_executable($path) || shell_exec("which $path")) {
            $mysqldump = $path;
            break;
        }
    }
    
    if ($mysqldump === null) {
        // mysqldump 명령어가 없으면 PHP로 직접 덤프
        echo "mysqldump 명령어를 찾을 수 없습니다. PHP로 직접 덤프합니다...\n";
        
        // PDO 연결
        $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
        $pdo = new PDO($dsn, $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 헤더 출력
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="dbdanora_' . date('Y-m-d_H-i-s') . '.sql"');
        
        echo "-- phpMyAdmin SQL Dump\n";
        echo "-- Database: $db_name\n";
        echo "-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
        echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
        echo "START TRANSACTION;\n";
        echo "SET time_zone = \"+00:00\";\n\n";
        echo "/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;\n";
        echo "/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;\n";
        echo "/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;\n";
        echo "/*!40101 SET NAMES utf8mb4 */;\n\n";
        
        // 데이터베이스 생성 (없는 경우)
        echo "-- CREATE DATABASE IF NOT EXISTS `$db_name` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\n";
        echo "USE `$db_name`;\n\n";
        
        // 테이블 목록 가져오기
        $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $table) {
            echo "-- --------------------------------------------------------\n\n";
            echo "-- 테이블 구조 `$table`\n\n";
            
            // CREATE TABLE 문 가져오기
            $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
            echo "DROP TABLE IF EXISTS `$table`;\n";
            echo $create_table['Create Table'] . ";\n\n";
            
            // 데이터 가져오기
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($rows) > 0) {
                echo "-- 테이블의 덤프 데이터 `$table`\n\n";
                echo "INSERT INTO `$table` VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $row_values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $row_values[] = 'NULL';
                        } else {
                            $row_values[] = $pdo->quote($value);
                        }
                    }
                    $values[] = '(' . implode(',', $row_values) . ')';
                }
                echo implode(",\n", $values) . ";\n\n";
            }
        }
        
        echo "-- --------------------------------------------------------\n\n";
        echo "COMMIT;\n";
        
    } else {
        // mysqldump 명령어 사용
        $filename = 'dbdanora_' . date('Y-m-d_H-i-s') . '.sql';
        
        // 헤더 설정
        header('Content-Type: text/plain; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        // mysqldump 명령어 실행
        $command = escapeshellcmd($mysqldump) . 
                   ' -h ' . escapeshellarg($db_host) . 
                   ' -u ' . escapeshellarg($db_user) . 
                   ' -p' . escapeshellarg($db_pass) . 
                   ' ' . escapeshellarg($db_name) . 
                   ' 2>&1';
        
        passthru($command);
    }
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: text/html; charset=utf-8');
    echo '<html><body>';
    echo '<h1>오류 발생</h1>';
    echo '<p>데이터베이스 덤프 중 오류가 발생했습니다:</p>';
    echo '<pre>' . htmlspecialchars($e->getMessage()) . '</pre>';
    echo '<p>PHP PDO Extension이 활성화되어 있는지 확인하세요.</p>';
    echo '</body></html>';
}

ob_end_flush();
