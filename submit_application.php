<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/database.php';
require_once __DIR__ . '/includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$planId = (int)($_POST['plan_id'] ?? 0);
$name = trim((string)($_POST['customer_name'] ?? ''));
$phone = trim((string)($_POST['customer_phone'] ?? ''));
$rrn = trim((string)($_POST['customer_rrn'] ?? ''));

$errors = [];

if ($planId < 1) {
    $errors[] = '요금제를 선택하세요.';
}

if ($name === '') {
    $errors[] = '이름을 입력하세요.';
}

if ($phone === '') {
    $errors[] = '연락처를 입력하세요.';
}

if ($rrn === '' || strlen($rrn) !== 6) {
    $errors[] = '주민등록번호 앞 6자리를 입력하세요.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo '<h1>신청 오류</h1><ul>';
    foreach ($errors as $error) {
        echo '<li>' . h($error) . '</li>';
    }
    echo '</ul><a href="/">뒤로가기</a>';
    exit;
}

try {
    $pdo = get_db_connection();

    $pdo->beginTransaction();

    $planStmt = $pdo->prepare('SELECT join_url FROM plans WHERE id = :id LIMIT 1');
    $planStmt->execute([':id' => $planId]);
    $plan = $planStmt->fetch();

    if (!$plan) {
        throw new RuntimeException('선택한 요금제를 찾을 수 없습니다.');
    }

    $insert = $pdo->prepare(
        'INSERT INTO applications (plan_id, customer_name, customer_phone, customer_rrn, created_at)
         VALUES (:plan_id, :name, :phone, :rrn, NOW())'
    );

    $insert->execute([
        ':plan_id' => $planId,
        ':name' => $name,
        ':phone' => $phone,
        ':rrn' => $rrn,
    ]);

    $pdo->commit();

    $redirectUrl = $plan['join_url'] ?? '/';
    redirect($redirectUrl);
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }

    http_response_code(500);
    echo '<h1>신청 처리 중 오류가 발생했습니다.</h1>';
    echo '<p>' . h($e->getMessage()) . '</p>';
    echo '<a href="/">뒤로가기</a>';
}

