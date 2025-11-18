<?php
declare(strict_types=1);

/**
 * 가비아 웹호스팅 DB 접속용 PDO 헬퍼
 * 실제 운영 시에는 환경변수나 별도 비밀설정 파일로 분리해 관리하세요.
 */

$dbConfig = [
    'host' => 'db.ganadamobile.co.kr',
    'name' => 'danora',
    'user' => 'danora',
    'pass' => 'damas#a231',
    'port' => 3306,
    'charset' => 'utf8mb4',
];

/**
 * @throws PDOException
 */
function db_connect(): PDO
{
    static $pdo = null;
    global $dbConfig;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $dbConfig['host'],
        $dbConfig['port'],
        $dbConfig['name'],
        $dbConfig['charset']
    );

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], $options);

    return $pdo;
}

