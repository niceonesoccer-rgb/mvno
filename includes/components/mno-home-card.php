<?php
/**
 * 메인페이지 전용 통신사폰 카드 컴포넌트
 * 
 * @param array $phone 통신사폰 데이터
 */
if (!isset($phone)) {
    $phone = [];
}

$phone_id = $phone['id'] ?? 0;
$is_link = ($phone_id > 0);
$link_url = '/MVNO/mno/mno-phone-detail.php?id=' . $phone_id;

// 단말기명
$deviceName = $phone['device_name'] ?? '';

// 용량
$deviceStorage = $phone['device_storage'] ?? '';

// 할인방법 데이터 준비
$maintenancePeriod = $phone['maintenance_period'] ?? '';
$contractSupport = $phone['contract_support'] ?? [];
$commonSupport = $phone['common_support'] ?? [];

// 디버깅: 데이터 확인 (Galaxy S23 등 특정 상품 확인용)
if ($phone_id == 33 || (empty($contractSupport) && empty($commonSupport))) {
    error_log("MNO Home Card - Phone ID: {$phone_id}, Device: {$deviceName}, contract_support count: " . count($contractSupport) . ", common_support count: " . count($commonSupport));
    if (!empty($commonSupport)) {
        error_log("MNO Home Card - Phone ID: {$phone_id}, common_support data: " . json_encode($commonSupport, JSON_UNESCAPED_UNICODE));
    }
    if (!empty($contractSupport)) {
        error_log("MNO Home Card - Phone ID: {$phone_id}, contract_support data: " . json_encode($contractSupport, JSON_UNESCAPED_UNICODE));
    }
}

// 공통지원할인: 표시될 행들 확인
$common_display_rows = [];
if (!empty($commonSupport) && is_array($commonSupport)) {
    foreach ($commonSupport as $row) {
        $new_value = $row['new_subscription'] ?? 9999;
        $port_value = $row['number_port'] ?? 9999;
        $change_value = $row['device_change'] ?? 9999;
        
        // 모두 9999가 아닌 행만 수집
        if (!($new_value == 9999 && $port_value == 9999 && $change_value == 9999)) {
            $common_display_rows[] = $row;
        }
    }
}

// 선택약정할인: 표시될 행들 확인
$contract_display_rows = [];
if (!empty($contractSupport) && is_array($contractSupport)) {
    foreach ($contractSupport as $row) {
        $new_value = $row['new_subscription'] ?? 9999;
        $port_value = $row['number_port'] ?? 9999;
        $change_value = $row['device_change'] ?? 9999;
        
        // 모두 9999가 아닌 행만 수집
        if (!($new_value == 9999 && $port_value == 9999 && $change_value == 9999)) {
            $contract_display_rows[] = $row;
        }
    }
}

// 열 표시 여부 확인 (common_display_rows와 contract_display_rows를 기준으로)
$show_common_new_column = false;
$show_common_port_column = false;
$show_common_change_column = false;
foreach ($common_display_rows as $row) {
    if (($row['new_subscription'] ?? 9999) != 9999) {
        $show_common_new_column = true;
    }
    if (($row['number_port'] ?? 9999) != 9999) {
        $show_common_port_column = true;
    }
    if (($row['device_change'] ?? 9999) != 9999) {
        $show_common_change_column = true;
    }
}

$show_contract_new_column = false;
$show_contract_port_column = false;
$show_contract_change_column = false;
foreach ($contract_display_rows as $row) {
    if (($row['new_subscription'] ?? 9999) != 9999) {
        $show_contract_new_column = true;
    }
    if (($row['number_port'] ?? 9999) != 9999) {
        $show_contract_port_column = true;
    }
    if (($row['device_change'] ?? 9999) != 9999) {
        $show_contract_change_column = true;
    }
}

// 공통지원할인 섹션 전체 표시 여부
$show_common_section = !empty($common_display_rows);

// 선택약정할인 섹션 전체 표시 여부
$show_contract_section = !empty($contract_display_rows);

// 디버깅: 표시 여부 확인
if ($phone_id == 33) {
    error_log("MNO Home Card - Phone ID: {$phone_id}, show_common_section: " . ($show_common_section ? 'true' : 'false') . ", show_contract_section: " . ($show_contract_section ? 'true' : 'false'));
    error_log("MNO Home Card - Phone ID: {$phone_id}, common_display_rows count: " . count($common_display_rows) . ", contract_display_rows count: " . count($contract_display_rows));
}
?>

<article class="mno-home-card" data-phone-id="<?php echo $phone_id; ?>">
    <?php if ($is_link): ?>
    <a href="<?php echo htmlspecialchars($link_url); ?>" class="mno-home-card-link">
    <?php else: ?>
    <div class="mno-home-card-link">
    <?php endif; ?>
        <div class="mno-home-card-content">
            <!-- 1. 단말기명 -->
            <?php if (!empty($deviceName)): ?>
            <div class="mno-home-device-name">
                <?php echo htmlspecialchars($deviceName); ?>
            </div>
            <?php endif; ?>
            
            <!-- 2. 용량 -->
            <?php if (!empty($deviceStorage)): ?>
            <div class="mno-home-device-storage">
                <?php echo htmlspecialchars($deviceStorage); ?>
            </div>
            <?php endif; ?>
            
            <!-- 3. 할인방법 -->
            <?php if ($show_contract_section || $show_common_section): ?>
            <div class="mno-home-discount-section">
                <?php if ($show_contract_section): ?>
                <!-- 선택약정할인 -->
                <div class="mno-support-card">
                    <div class="mno-support-card-title">선택약정할인</div>
                    <div class="mno-support-card-content">
                        <table class="mno-support-table">
                            <thead>
                                <tr>
                                    <th>통신사</th>
                                    <?php if ($show_contract_new_column): ?>
                                    <th>신규</th>
                                    <?php endif; ?>
                                    <?php if ($show_contract_port_column): ?>
                                    <th>번이</th>
                                    <?php endif; ?>
                                    <?php if ($show_contract_change_column): ?>
                                    <th>기변</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($contract_display_rows as $row): 
                                    $new_value = $row['new_subscription'] ?? 9999;
                                    $port_value = $row['number_port'] ?? 9999;
                                    $change_value = $row['device_change'] ?? 9999;
                                ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $provider_display = $row['provider'] ?? '';
                                        if ($provider_display === 'SKT') {
                                            $provider_display = 'SK';
                                        } elseif ($provider_display === 'LG U+') {
                                            $provider_display = 'LG';
                                        }
                                        ?>
                                        <span class="mno-support-provider-text"><?php echo htmlspecialchars($provider_display); ?></span>
                                    </td>
                                    <?php if ($show_contract_new_column): ?>
                                    <td>
                                        <?php 
                                        $new_display = '';
                                        if ($new_value === '' || $new_value === null || $new_value == 9999 || $new_value === '9999') {
                                            $new_display = '-';
                                        } else {
                                            $new_display = htmlspecialchars($new_value);
                                        }
                                        $new_text_class = ($new_display == '-') ? 'mno-support-text mno-support-text-empty' : 'mno-support-text';
                                        ?>
                                        <span class="<?php echo htmlspecialchars($new_text_class); ?>"><?php echo $new_display; ?></span>
                                    </td>
                                    <?php endif; ?>
                                    <?php if ($show_contract_port_column): ?>
                                    <td>
                                        <?php 
                                        $port_display = '';
                                        if ($port_value === '' || $port_value === null || $port_value == 9999 || $port_value === '9999') {
                                            $port_display = '-';
                                        } else {
                                            $port_display = htmlspecialchars($port_value);
                                        }
                                        $port_color_class = '';
                                        if ($port_value !== '' && $port_value !== null && $port_value != 9999 && $port_value !== '9999') {
                                            $port_str = (string)$port_value;
                                            if (strpos($port_str, '-') === 0 || floatval($port_value) < 0) {
                                                $port_color_class = 'mno-support-text-negative';
                                            } else {
                                                $port_color_class = 'mno-support-text-positive';
                                            }
                                        }
                                        $port_text_class = ($port_display == '-') ? 'mno-support-text mno-support-text-empty' : 'mno-support-text';
                                        ?>
                                        <span class="<?php echo htmlspecialchars($port_text_class); ?> <?php echo htmlspecialchars($port_color_class); ?>"><?php echo $port_display; ?></span>
                                    </td>
                                    <?php endif; ?>
                                    <?php if ($show_contract_change_column): ?>
                                    <td>
                                        <?php 
                                        $change_display = '';
                                        if ($change_value === '' || $change_value === null || $change_value == 9999 || $change_value === '9999') {
                                            $change_display = '-';
                                        } else {
                                            $change_display = htmlspecialchars($change_value);
                                        }
                                        $change_color_class = '';
                                        if ($change_value !== '' && $change_value !== null && $change_value != 9999 && $change_value !== '9999') {
                                            $change_str = (string)$change_value;
                                            if (strpos($change_str, '-') === 0 || floatval($change_value) < 0) {
                                                $change_color_class = 'mno-support-text-negative';
                                            } else {
                                                $change_color_class = 'mno-support-text-positive';
                                            }
                                        }
                                        $change_text_class = ($change_display == '-') ? 'mno-support-text mno-support-text-empty' : 'mno-support-text';
                                        ?>
                                        <span class="<?php echo htmlspecialchars($change_text_class); ?> <?php echo htmlspecialchars($change_color_class); ?>"><?php echo $change_display; ?></span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php elseif ($show_common_section): ?>
                <!-- 공통지원할인 -->
                <div class="mno-support-card">
                    <div class="mno-support-card-title">공통지원할인</div>
                    <div class="mno-support-card-content">
                        <table class="mno-support-table">
                            <thead>
                                <tr>
                                    <th>통신사</th>
                                    <?php if ($show_common_new_column): ?>
                                    <th>신규</th>
                                    <?php endif; ?>
                                    <?php if ($show_common_port_column): ?>
                                    <th>번이</th>
                                    <?php endif; ?>
                                    <?php if ($show_common_change_column): ?>
                                    <th>기변</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($common_display_rows as $row): 
                                    $new_value = $row['new_subscription'] ?? 9999;
                                    $port_value = $row['number_port'] ?? 9999;
                                    $change_value = $row['device_change'] ?? 9999;
                                ?>
                                <tr>
                                    <td>
                                        <?php 
                                        $provider_display = $row['provider'] ?? '';
                                        if ($provider_display === 'SKT') {
                                            $provider_display = 'SK';
                                        } elseif ($provider_display === 'LG U+') {
                                            $provider_display = 'LG';
                                        }
                                        ?>
                                        <span class="mno-support-provider-text"><?php echo htmlspecialchars($provider_display); ?></span>
                                    </td>
                                    <?php if ($show_common_new_column): ?>
                                    <td>
                                        <?php 
                                        $new_display = '';
                                        if ($new_value === '' || $new_value === null || $new_value == 9999 || $new_value === '9999') {
                                            $new_display = '-';
                                        } else {
                                            $new_display = htmlspecialchars($new_value);
                                        }
                                        $new_text_class = ($new_display == '-') ? 'mno-support-text mno-support-text-empty' : 'mno-support-text';
                                        ?>
                                        <span class="<?php echo htmlspecialchars($new_text_class); ?>"><?php echo $new_display; ?></span>
                                    </td>
                                    <?php endif; ?>
                                    <?php if ($show_common_port_column): ?>
                                    <td>
                                        <?php 
                                        $port_display = '';
                                        if ($port_value === '' || $port_value === null || $port_value == 9999 || $port_value === '9999') {
                                            $port_display = '-';
                                        } else {
                                            $port_display = htmlspecialchars($port_value);
                                        }
                                        $port_color_class = '';
                                        if ($port_value !== '' && $port_value !== null && $port_value != 9999 && $port_value !== '9999') {
                                            $port_str = (string)$port_value;
                                            if (strpos($port_str, '-') === 0 || floatval($port_value) < 0) {
                                                $port_color_class = 'mno-support-text-negative';
                                            } else {
                                                $port_color_class = 'mno-support-text-positive';
                                            }
                                        }
                                        $port_text_class = ($port_display == '-') ? 'mno-support-text mno-support-text-empty' : 'mno-support-text';
                                        ?>
                                        <span class="<?php echo htmlspecialchars($port_text_class); ?> <?php echo htmlspecialchars($port_color_class); ?>"><?php echo $port_display; ?></span>
                                    </td>
                                    <?php endif; ?>
                                    <?php if ($show_common_change_column): ?>
                                    <td>
                                        <?php 
                                        $change_display = '';
                                        if ($change_value === '' || $change_value === null || $change_value == 9999 || $change_value === '9999') {
                                            $change_display = '-';
                                        } else {
                                            $change_display = htmlspecialchars($change_value);
                                        }
                                        $change_color_class = '';
                                        if ($change_value !== '' && $change_value !== null && $change_value != 9999 && $change_value !== '9999') {
                                            $change_str = (string)$change_value;
                                            if (strpos($change_str, '-') === 0 || floatval($change_value) < 0) {
                                                $change_color_class = 'mno-support-text-negative';
                                            } else {
                                                $change_color_class = 'mno-support-text-positive';
                                            }
                                        }
                                        $change_text_class = ($change_display == '-') ? 'mno-support-text mno-support-text-empty' : 'mno-support-text';
                                        ?>
                                        <span class="<?php echo htmlspecialchars($change_text_class); ?> <?php echo htmlspecialchars($change_color_class); ?>"><?php echo $change_display; ?></span>
                                    </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    <?php if ($is_link): ?>
    </a>
    <?php else: ?>
    </div>
    <?php endif; ?>
</article>
