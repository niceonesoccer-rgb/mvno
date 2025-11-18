<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('/');
}

$planId = (int)($_POST['plan_id'] ?? 0);
$name = trim((string)($_POST['customer_name'] ?? ''));
$phone = trim((string)($_POST['customer_phone'] ?? ''));
$email = trim((string)($_POST['customer_email'] ?? ''));
$agency = trim((string)($_POST['agency_name'] ?? ''));
$agreeNotifications = isset($_POST['agree_notifications']) ? 1 : 0;

$errors = [];
if ($planId < 1) {
    $errors[] = '유효한 요금제를 선택해주세요.';
}
if ($name === '') {
    $errors[] = '이름을 입력해주세요.';
}
if ($phone === '') {
    $errors[] = '연락처를 입력해주세요.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = '올바른 이메일 주소를 입력해주세요.';
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
    $pdo = db_connect();
    $pdo->beginTransaction();

    $planStmt = $pdo->prepare(
        'SELECT id, name, join_url FROM plans WHERE id = :id LIMIT 1'
    );
    $planStmt->execute([':id' => $planId]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);

    if (!$plan) {
        throw new RuntimeException('선택한 요금제를 찾을 수 없습니다.');
    }

    $today = date('Ymd');
    $prefix = $today . '-';

    $orderStmt = $pdo->prepare(
        'SELECT order_no 
         FROM applications 
         WHERE order_no LIKE :prefix 
         ORDER BY order_no DESC 
         LIMIT 1'
    );
    $orderStmt->execute([':prefix' => $prefix . '%']);
    $lastOrder = $orderStmt->fetchColumn();

    $sequence = 1;
    if ($lastOrder) {
        $sequence = (int)substr($lastOrder, -6) + 1;
    }
    $orderNo = sprintf('%s%06d', $prefix, $sequence);

    $insert = $pdo->prepare(
        'INSERT INTO applications (
            order_no, plan_id, customer_name, customer_phone, customer_email,
            agency_name, agree_notifications, created_at, updated_at
        ) VALUES (
            :order_no, :plan_id, :name, :phone, :email,
            :agency, :agree_notifications, NOW(), NOW()
        )'
    );

    $insert->execute([
        ':order_no' => $orderNo,
        ':plan_id' => $planId,
        ':name' => $name,
        ':phone' => $phone,
        ':email' => $email,
        ':agency' => $agency !== '' ? $agency : null,
        ':agree_notifications' => $agreeNotifications,
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

