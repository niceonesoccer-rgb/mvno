<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/db.php';

$pdo = null;
$applications = [];
$error = null;

try {
    $pdo = db_connect();

    $sql = <<<SQL
        SELECT 
            a.id,
            a.order_no,
            a.customer_name,
            a.customer_phone,
            a.status,
            a.created_at,
            p.name AS plan_name,
            p.carrier
        FROM applications a
        LEFT JOIN plans p ON p.id = a.plan_id
        ORDER BY a.created_at DESC
    SQL;
    $stmt = $pdo->query($sql);
    if ($stmt !== false) {
        $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <title>신청 내역 관리</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@picocss/pico@2/css/pico.min.css">
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            border-bottom: 1px solid #e5e7eb;
            padding: .75rem;
            text-align: left;
        }
        th {
            background: #f8fafc;
            font-size: .95rem;
            color: #374151;
        }
        tbody tr:hover {
            background: #f3f4f6;
        }
        .status-form {
            display: flex;
            align-items: center;
            gap: .5rem;
        }
    </style>
</head>
<body>
<main class="container">
    <header>
        <h1>신청 내역</h1>
        <p>applications 테이블의 데이터를 실시간으로 확인하고 상태를 변경할 수 있습니다.</p>
    </header>

    <?php if ($error): ?>
        <article>
            <strong>DB 조회 중 오류:</strong>
            <p><?= h($error) ?></p>
        </article>
    <?php elseif (empty($applications)): ?>
        <article>
            <p>등록된 신청 내역이 없습니다.</p>
        </article>
    <?php else: ?>
        <div class="table-responsive">
            <table>
                <thead>
                <tr>
                    <th>주문번호</th>
                    <th>고객명</th>
                    <th>연락처</th>
                    <th>통신사</th>
                    <th>요금제명</th>
                    <th>신청일</th>
                    <th>상태</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($applications as $row): ?>
                    <tr>
                        <td><?= h($row['order_no']) ?></td>
                        <td><?= h($row['customer_name']) ?></td>
                        <td><?= h($row['customer_phone']) ?></td>
                        <td><?= h($row['carrier'] ?? '-') ?></td>
                        <td><?= h($row['plan_name'] ?? '-') ?></td>
                        <td><?= h($row['created_at']) ?></td>
                        <td>
                            <form class="status-form" method="post" action="applications_status.php">
                                <input type="hidden" name="application_id" value="<?= (int)$row['id'] ?>">
                                <select name="status">
                                    <?php
                                    $statuses = ['pending' => '대기', 'processing' => '진행중', 'completed' => '완료', 'cancelled' => '취소'];
                                    foreach ($statuses as $value => $label):
                                        $selected = ($row['status'] ?? 'pending') === $value ? 'selected' : '';
                                        ?>
                                        <option value="<?= h($value) ?>" <?= $selected ?>><?= h($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="submit">저장</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</main>
</body>
</html>

