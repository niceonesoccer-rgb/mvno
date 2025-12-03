<?php
/**
 * 통신사폰 데이터 처리
 * 나중에 DB 연결 시 이 부분만 수정하면 됨
 */

/**
 * 통신사폰 목록 데이터 가져오기
 * @param int $limit 가져올 개수
 * @return array 통신사폰 배열
 */
function getPhonesData($limit = 10) {
    // 임시 하드코딩 데이터 (나중에 DB 쿼리로 교체)
    $allPhones = [
        [
            'id' => 1,
            'provider' => 'SKT',
            'device_name' => 'Galaxy Z Fold7',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/galaxy_z_fold7.png',
            'device_storage' => '256GB',
            'plan_name' => 'SKT 프리미어 슈퍼',
            'common_number_port' => '-198',
            'common_device_change' => '191.6',
            'contract_number_port' => '198',
            'contract_device_change' => '-150',
            'monthly_price' => '109,000원',
            'maintenance_period' => '185일',
            'device_price' => '출고가 2,387,000원',
            'selection_count' => '15,234명이 신청',
            'gifts' => [
                '추가 지원금',
                '부가 서비스 1',
                '부가 서비스 2'
            ]
        ],
        [
            'id' => 2,
            'provider' => 'KT',
            'device_name' => 'iPhone 16 Pro',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/iphone_16_pro.png',
            'device_storage' => '512GB',
            'plan_name' => 'KT 슈퍼플랜',
            'common_number_port' => '180.0',
            'common_device_change' => '180.0',
            'contract_number_port' => '200.0',
            'contract_device_change' => '200.0',
            'monthly_price' => '125,000원',
            'maintenance_period' => '180일',
            'device_price' => '출고가 2,100,000원',
            'selection_count' => '22,156명이 신청',
            'gifts' => [
                '추가 지원금',
                '부가 서비스'
            ]
        ],
        [
            'id' => 3,
            'provider' => 'LG U+',
            'device_name' => 'Galaxy S25',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/galaxy_s_25.png',
            'device_storage' => '256GB',
            'plan_name' => 'LG U+ 5G 슈퍼플랜',
            'common_number_port' => '175.5',
            'common_device_change' => '175.5',
            'contract_number_port' => '195.0',
            'contract_device_change' => '195.0',
            'monthly_price' => '95,000원',
            'maintenance_period' => '200일',
            'device_price' => '출고가 1,250,000원',
            'selection_count' => '18,942명이 신청',
            'gifts' => [
                '추가 지원금'
            ]
        ],
        [
            'id' => 4,
            'provider' => 'SKT',
            'device_name' => 'iPhone 16',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/iphone_16.png',
            'device_storage' => '128GB',
            'plan_name' => 'SKT 스탠다드',
            'common_number_port' => '150.0',
            'common_device_change' => '150.0',
            'contract_number_port' => '170.0',
            'contract_device_change' => '170.0',
            'monthly_price' => '85,000원',
            'maintenance_period' => '150일',
            'device_price' => '출고가 1,350,000원',
            'selection_count' => '12,567명이 신청',
            'gifts' => []
        ],
        [
            'id' => 5,
            'provider' => 'KT',
            'device_name' => 'Galaxy S24 Ultra',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/galaxy_s24_ultra.png',
            'device_storage' => '512GB',
            'plan_name' => 'KT 프리미엄',
            'common_number_port' => '165.0',
            'common_device_change' => '165.0',
            'contract_number_port' => '185.0',
            'contract_device_change' => '185.0',
            'monthly_price' => '115,000원',
            'maintenance_period' => '190일',
            'device_price' => '출고가 1,800,000원',
            'selection_count' => '19,876명이 신청',
            'gifts' => [
                '추가 지원금',
                '부가 서비스'
            ]
        ],
        [
            'id' => 6,
            'provider' => 'LG U+',
            'device_name' => 'iPhone 15 Pro Max',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/iphone_15_pro_max.png',
            'device_storage' => '256GB',
            'plan_name' => 'LG U+ 5G 플랜',
            'common_number_port' => '160.0',
            'common_device_change' => '160.0',
            'contract_number_port' => '180.0',
            'contract_device_change' => '180.0',
            'monthly_price' => '105,000원',
            'maintenance_period' => '175일',
            'device_price' => '출고가 1,950,000원',
            'selection_count' => '16,432명이 신청',
            'gifts' => []
        ],
        [
            'id' => 7,
            'provider' => 'SKT',
            'device_name' => 'Galaxy Z Flip6',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/galaxy_z_flip6.png',
            'device_storage' => '256GB',
            'plan_name' => 'SKT 베이직',
            'common_number_port' => '140.0',
            'common_device_change' => '140.0',
            'contract_number_port' => '160.0',
            'contract_device_change' => '160.0',
            'monthly_price' => '75,000원',
            'maintenance_period' => '140일',
            'device_price' => '출고가 1,400,000원',
            'selection_count' => '9,654명이 신청',
            'gifts' => []
        ],
        [
            'id' => 8,
            'provider' => 'KT',
            'device_name' => 'iPhone 15',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/iphone_15.png',
            'device_storage' => '128GB',
            'plan_name' => 'KT 스탠다드',
            'common_number_port' => '145.0',
            'common_device_change' => '145.0',
            'contract_number_port' => '165.0',
            'contract_device_change' => '165.0',
            'monthly_price' => '80,000원',
            'maintenance_period' => '160일',
            'device_price' => '출고가 1,250,000원',
            'selection_count' => '11,234명이 신청',
            'gifts' => []
        ],
        [
            'id' => 9,
            'provider' => 'LG U+',
            'device_name' => 'Galaxy S23',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/galaxy_s23.png',
            'device_storage' => '256GB',
            'plan_name' => 'LG U+ 5G 베이직',
            'common_number_port' => '130.0',
            'common_device_change' => '130.0',
            'contract_number_port' => '150.0',
            'contract_device_change' => '150.0',
            'monthly_price' => '70,000원',
            'maintenance_period' => '130일',
            'device_price' => '출고가 1,000,000원',
            'selection_count' => '7,891명이 신청',
            'gifts' => []
        ],
        [
            'id' => 10,
            'provider' => 'SKT',
            'device_name' => 'iPhone 14 Pro',
            'device_image' => 'https://assets.moyoplan.com/image/phone/model/iphone_14_pro.png',
            'device_storage' => '256GB',
            'plan_name' => 'SKT 프리미엄',
            'common_number_port' => '155.0',
            'common_device_change' => '155.0',
            'contract_number_port' => '175.0',
            'contract_device_change' => '175.0',
            'monthly_price' => '100,000원',
            'maintenance_period' => '170일',
            'device_price' => '출고가 1,650,000원',
            'selection_count' => '14,567명이 신청',
            'gifts' => [
                '추가 지원금'
            ]
        ]
    ];
    
    // limit만큼만 반환
    return array_slice($allPhones, 0, $limit);
}

/**
 * 통신사폰 상세 데이터 가져오기
 * @param int $phone_id 통신사폰 ID
 * @return array|null 통신사폰 데이터 또는 null
 */
function getPhoneDetailData($phone_id) {
    // 임시 하드코딩 데이터 (나중에 DB 쿼리로 교체)
    $phones = getPhonesData();
    foreach ($phones as $phone) {
        if ($phone['id'] == $phone_id) {
            return $phone;
        }
    }
    return null;
}

