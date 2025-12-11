<?php
/**
 * 통신사폰 카드 본문 컴포넌트
 * 제목, 요금제 정보, 지원금 정보, 가격 정보
 * 
 * @param array $phone 통신사폰 데이터
 */
if (!isset($phone)) {
    $phone = [];
}
$device_name = $phone['device_name'] ?? '기기명';
$device_storage = $phone['device_storage'] ?? '';
// 출고가: release_price가 있으면 사용, 없으면 device_price를 포맷팅
if (!empty($phone['release_price'])) {
    $release_price = $phone['release_price'];
} elseif (!empty($phone['device_price'])) {
    $release_price = number_format($phone['device_price']);
} else {
    $release_price = '0';
}
$provider = $phone['provider'] ?? 'SKT';
$plan_name = $phone['plan_name'] ?? '요금제명';
$price_main = $phone['monthly_price'] ?? $phone['price'] ?? '월 0원';
$maintenance_period = $phone['maintenance_period'] ?? '0일';
$selection_count = $phone['selection_count'] ?? '29,448명이 신청';

// 요금제명에서 통신사명 제거 (이미 provider에 있음)
$plan_name_clean = str_replace([$provider . ' ', 'SKT ', 'KT ', 'LG U+ '], '', $plan_name);

// 지원금 정보 (DB에서 가져온 데이터 사용)
// 공통지원할인 데이터 (여러 행 가능)
$common_support = $phone['common_support'] ?? [];

// 선택약정할인 데이터 (여러 행 가능)
$contract_support = $phone['contract_support'] ?? [];

// 공통지원할인: 표시될 행들에서 신규가입 열 표시 여부 확인
$common_display_rows = [];
foreach ($common_support as $row) {
    $new_value = $row['new_subscription'] ?? 9999;
    $port_value = $row['number_port'] ?? 9999;
    $change_value = $row['device_change'] ?? 9999;
    
    // 모두 9999가 아닌 행만 수집
    if (!($new_value == 9999 && $port_value == 9999 && $change_value == 9999)) {
        $common_display_rows[] = $row;
    }
}

// 신규가입 열 표시 여부: 전체 데이터에서 신규가입 값이 9999가 아닌 값이 하나라도 있으면 표시
$show_common_new_column = false;
foreach ($common_support as $row) {
    if (($row['new_subscription'] ?? 9999) != 9999) {
        $show_common_new_column = true;
        break;
    }
}

// 번호이동 열 표시 여부: 전체 데이터에서 번호이동 값이 9999가 아닌 값이 하나라도 있으면 표시
$show_common_port_column = false;
foreach ($common_support as $row) {
    if (($row['number_port'] ?? 9999) != 9999) {
        $show_common_port_column = true;
        break;
    }
}

// 기기변경 열 표시 여부: 전체 데이터에서 기기변경 값이 9999가 아닌 값이 하나라도 있으면 표시
$show_common_change_column = false;
foreach ($common_support as $row) {
    if (($row['device_change'] ?? 9999) != 9999) {
        $show_common_change_column = true;
        break;
    }
}

// 선택약정할인: 표시될 행들에서 신규가입 열 표시 여부 확인
$contract_display_rows = [];
foreach ($contract_support as $row) {
    $new_value = $row['new_subscription'] ?? 9999;
    $port_value = $row['number_port'] ?? 9999;
    $change_value = $row['device_change'] ?? 9999;
    
    // 모두 9999가 아닌 행만 수집
    if (!($new_value == 9999 && $port_value == 9999 && $change_value == 9999)) {
        $contract_display_rows[] = $row;
    }
}

// 신규가입 열 표시 여부: 전체 데이터에서 신규가입 값이 9999가 아닌 값이 하나라도 있으면 표시
$show_contract_new_column = false;
foreach ($contract_support as $row) {
    if (($row['new_subscription'] ?? 9999) != 9999) {
        $show_contract_new_column = true;
        break;
    }
}

// 번호이동 열 표시 여부: 전체 데이터에서 번호이동 값이 9999가 아닌 값이 하나라도 있으면 표시
$show_contract_port_column = false;
foreach ($contract_support as $row) {
    if (($row['number_port'] ?? 9999) != 9999) {
        $show_contract_port_column = true;
        break;
    }
}

// 기기변경 열 표시 여부: 전체 데이터에서 기기변경 값이 9999가 아닌 값이 하나라도 있으면 표시
$show_contract_change_column = false;
foreach ($contract_support as $row) {
    if (($row['device_change'] ?? 9999) != 9999) {
        $show_contract_change_column = true;
        break;
    }
}

// 공통지원할인 섹션 전체 표시 여부 확인 (모든 칸이 9999인지)
$show_common_section = false;
foreach ($common_support as $row) {
    if (($row['new_subscription'] ?? 9999) != 9999 ||
        ($row['number_port'] ?? 9999) != 9999 ||
        ($row['device_change'] ?? 9999) != 9999) {
        $show_common_section = true;
        break;
    }
}

// 선택약정할인 섹션 전체 표시 여부 확인 (모든 칸이 9999인지)
$show_contract_section = false;
foreach ($contract_support as $row) {
    if (($row['new_subscription'] ?? 9999) != 9999 ||
        ($row['number_port'] ?? 9999) != 9999 ||
        ($row['device_change'] ?? 9999) != 9999) {
        $show_contract_section = true;
        break;
    }
}

// 하나만 표시되는 경우를 위한 플래그
$show_only_one_section = ($show_common_section && !$show_contract_section) || (!$show_common_section && $show_contract_section);
?>

<!-- 제목: 기기명 | 용량 | 출고가 -->
<div class="phone-title-row">
    <span class="phone-title-text">
        <?php echo htmlspecialchars($device_name); ?> 
        <span class="phone-title-separator">|</span> 
        <?php echo htmlspecialchars($device_storage); ?> 
        <span class="phone-title-separator">|</span> 
        출고가 <?php echo htmlspecialchars($release_price); ?>원
    </span>
</div>

<!-- 요금제 정보 -->
<div class="phone-info-section">
    <div class="plan-features-row">
        <div class="mno-support-amount-section <?php echo $show_only_one_section ? 'mno-support-single-section' : ''; ?>">
            <!-- 공통지원할인 카드 -->
            <?php if ($show_common_section): ?>
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
                            <?php foreach ($common_support as $row): 
                                // 해당 행의 신규가입, 번호이동, 기기변경이 모두 9999인지 확인
                                $new_value = $row['new_subscription'] ?? 9999;
                                $port_value = $row['number_port'] ?? 9999;
                                $change_value = $row['device_change'] ?? 9999;
                                
                                // 모두 9999면 해당 행을 표시하지 않음
                                if ($new_value == 9999 && $port_value == 9999 && $change_value == 9999) {
                                    continue;
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                    $provider_display = $row['provider'] ?? '';
                                    // 통신사 이름 표시 형식 변경
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
                                    // 9999 값은 "-"로 표시 (숫자/문자열 모두 처리)
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
                                    // 9999 값은 "-"로 표시 (숫자/문자열 모두 처리)
                                    $port_display = '';
                                    if ($port_value === '' || $port_value === null || $port_value == 9999 || $port_value === '9999') {
                                        $port_display = '-';
                                    } else {
                                        $port_display = htmlspecialchars($port_value);
                                    }
                                    $port_color_class = '';
                                    if ($port_value !== '' && $port_value !== null && $port_value != 9999 && $port_value !== '9999') {
                                        // 음수면 빨강, 양수면 파랑
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
                                    // 9999 값은 "-"로 표시 (숫자/문자열 모두 처리)
                                    $change_display = '';
                                    if ($change_value === '' || $change_value === null || $change_value == 9999 || $change_value === '9999') {
                                        $change_display = '-';
                                    } else {
                                        $change_display = htmlspecialchars($change_value);
                                    }
                                    $change_color_class = '';
                                    if ($change_value !== '' && $change_value !== null && $change_value != 9999 && $change_value !== '9999') {
                                        // 음수면 빨강, 양수면 파랑
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
            
            <!-- 선택약정할인 카드 -->
            <?php if ($show_contract_section): ?>
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
                            <?php foreach ($contract_support as $row): 
                                // 해당 행의 신규가입, 번호이동, 기기변경이 모두 9999인지 확인
                                $new_value = $row['new_subscription'] ?? 9999;
                                $port_value = $row['number_port'] ?? 9999;
                                $change_value = $row['device_change'] ?? 9999;
                                
                                // 모두 9999면 해당 행을 표시하지 않음
                                if ($new_value == 9999 && $port_value == 9999 && $change_value == 9999) {
                                    continue;
                                }
                            ?>
                            <tr>
                                <td>
                                    <?php 
                                    $provider_display = $row['provider'] ?? '';
                                    // 통신사 이름 표시 형식 변경
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
                                    // 9999 값은 "-"로 표시 (숫자/문자열 모두 처리)
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
                                    // 9999 값은 "-"로 표시 (숫자/문자열 모두 처리)
                                    $port_display = '';
                                    if ($port_value === '' || $port_value === null || $port_value == 9999 || $port_value === '9999') {
                                        $port_display = '-';
                                    } else {
                                        $port_display = htmlspecialchars($port_value);
                                    }
                                    $port_color_class = '';
                                    if ($port_value !== '' && $port_value !== null && $port_value != 9999 && $port_value !== '9999') {
                                        // 음수면 빨강, 양수면 파랑
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
                                    // 9999 값은 "-"로 표시 (숫자/문자열 모두 처리)
                                    $change_display = '';
                                    if ($change_value === '' || $change_value === null || $change_value == 9999 || $change_value === '9999') {
                                        $change_display = '-';
                                    } else {
                                        $change_display = htmlspecialchars($change_value);
                                    }
                                    $change_color_class = '';
                                    if ($change_value !== '' && $change_value !== null && $change_value != 9999 && $change_value !== '9999') {
                                        // 음수면 빨강, 양수면 파랑
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
    </div>
</div>
