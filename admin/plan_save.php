<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('plan_form.php');
}

$fields = [
    'carrier',
    'join_type',
    'customer_type',
    'plan_type',
    'sim_type',
    'data_type',
    'plan_name',
    'data_allowance',
    'voice_allowance',
    'video_allowance',
    'sms_allowance',
    'price',
    'discount_price',
    'min_period_days',
    'benefit1',
    'benefit2',
    'benefit3',
    'benefit4',
    'benefit5',
    'description_title1',
    'description_body1',
    'description_title2',
    'description_body2',
    'join_url',
];

$data = [];
foreach ($fields as $field) {
    $value = $_POST[$field] ?? null;
    $data[$field] = is_string($value) ? trim($value) : $value;
}

$required = [
    'carrier',
    'join_type',
    'customer_type',
    'plan_type',
    'sim_type',
    'data_type',
    'plan_name',
];

$errors = [];
foreach ($required as $field) {
    if ($data[$field] === '' || $data[$field] === null) {
        $errors[] = $field . ' 값을 입력하세요.';
    }
}

if (!empty($data['price']) && !is_numeric($data['price'])) {
    $errors[] = '판매가는 숫자여야 합니다.';
}

if (!empty($data['discount_price']) && !is_numeric($data['discount_price'])) {
    $errors[] = '할인가는 숫자여야 합니다.';
}

if (!empty($data['min_period_days']) && !ctype_digit((string)$data['min_period_days'])) {
    $errors[] = '요금제 유지기간은 정수여야 합니다.';
}

if (!empty($errors)) {
    http_response_code(400);
    echo '<h1>저장 실패</h1><ul>';
    foreach ($errors as $error) {
        echo '<li>' . h($error) . '</li>';
    }
    echo '</ul><a href="plan_form.php">뒤로가기</a>';
    exit;
}

try {
    $pdo = db_connect();

    $sql = 'INSERT INTO plans (
                carrier, join_type, customer_type, plan_type, sim_type, data_type,
                name, data_allowance, voice_allowance, video_allowance, sms_allowance,
                price, discount_price, min_period_days,
                benefit1, benefit2, benefit3, benefit4, benefit5,
                description_title1, description_body1, description_title2, description_body2,
                join_url, created_at, updated_at
            ) VALUES (
                :carrier, :join_type, :customer_type, :plan_type, :sim_type, :data_type,
                :name, :data_allowance, :voice_allowance, :video_allowance, :sms_allowance,
                :price, :discount_price, :min_period_days,
                :benefit1, :benefit2, :benefit3, :benefit4, :benefit5,
                :description_title1, :description_body1, :description_title2, :description_body2,
                :join_url, NOW(), NOW()
            )';

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':carrier' => $data['carrier'],
        ':join_type' => $data['join_type'],
        ':customer_type' => $data['customer_type'],
        ':plan_type' => $data['plan_type'],
        ':sim_type' => $data['sim_type'],
        ':data_type' => $data['data_type'],
        ':name' => $data['plan_name'],
        ':data_allowance' => $data['data_allowance'],
        ':voice_allowance' => $data['voice_allowance'],
        ':video_allowance' => $data['video_allowance'],
        ':sms_allowance' => $data['sms_allowance'],
        ':price' => $data['price'] !== '' ? (float)$data['price'] : null,
        ':discount_price' => $data['discount_price'] !== '' ? (float)$data['discount_price'] : null,
        ':min_period_days' => $data['min_period_days'] !== '' ? (int)$data['min_period_days'] : null,
        ':benefit1' => $data['benefit1'],
        ':benefit2' => $data['benefit2'],
        ':benefit3' => $data['benefit3'],
        ':benefit4' => $data['benefit4'],
        ':benefit5' => $data['benefit5'],
        ':description_title1' => $data['description_title1'],
        ':description_body1' => $data['description_body1'],
        ':description_title2' => $data['description_title2'],
        ':description_body2' => $data['description_body2'],
        ':join_url' => $data['join_url'],
    ]);

    redirect('index.php');
} catch (Throwable $e) {
    http_response_code(500);
    echo '<h1>DB 저장 중 오류가 발생했습니다.</h1>';
    echo '<p>' . h($e->getMessage()) . '</p>';
    echo '<a href="plan_form.php">뒤로가기</a>';
}

