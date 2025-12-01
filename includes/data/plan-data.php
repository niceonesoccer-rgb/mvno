<?php
/**
 * 요금제 데이터 처리
 * 나중에 DB 연결 시 이 부분만 수정하면 됨
 */

/**
 * 요금제 목록 데이터 가져오기
 * @param int $limit 가져올 개수
 * @return array 요금제 배열
 */
function getPlansData($limit = 10) {
    // 임시 하드코딩 데이터 (나중에 DB 쿼리로 교체)
    $allPlans = [
        [
            'id' => 32627,
            'provider' => '쉐이크모바일',
            'rating' => '4.3',
            'title' => '11월한정 LTE 100GB+밀리+Data쿠폰60GB',
            'data_main' => '월 100GB + 5Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 17,000원',
            'price_after' => '7개월 이후 42,900원',
            'selection_count' => '29,448명이 선택',
            'gifts' => [
                'SOLO결합(+20GB)',
                '밀리의서재 평생 무료 구독권',
                '데이터쿠폰 20GB',
                '[11월 한정]네이버페이 10,000원',
                '3대 마트 상품권 2만원'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '데이터쿠폰 20GB'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg', 'alt' => '밀리의 서재'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/subscription.svg', 'alt' => 'SOLO결합(+20GB)']
            ]
        ],
        [
            'id' => 32632,
            'provider' => '고고모바일',
            'rating' => '4.2',
            'title' => 'LTE무제한 100GB+5M(CU20%할인)_11월',
            'data_main' => '월 100GB + 5Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 17,000원',
            'price_after' => '7개월 이후 42,900원',
            'selection_count' => '12,353명이 선택',
            'gifts' => [
                'KT유심&배송비 무료',
                '데이터쿠폰 20GB x 3회',
                '추가데이터 20GB 제공',
                '이마트 상품권',
                'CU 상품권',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => 'KT유심&배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '데이터쿠폰 20GB x 3회'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가데이터 20GB 제공'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/cu.svg', 'alt' => 'CU 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 29290,
            'provider' => '이야기모바일',
            'rating' => '4.5',
            'title' => '이야기 완전 무제한 100GB+',
            'data_main' => '월 100GB + 5Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 17,000원',
            'price_after' => '7개월 이후 49,500원',
            'selection_count' => '17,816명이 선택',
            'gifts' => [
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32628,
            'provider' => '핀다이렉트',
            'rating' => '4.2',
            'title' => '[S] 핀다이렉트Z _2511',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 14,960원',
            'price_after' => '7개월 이후 39,600원',
            'selection_count' => '4,420명이 선택',
            'gifts' => [
                '매월 20GB 추가 데이터',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가 데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32629,
            'provider' => '고고모바일',
            'rating' => '4.2',
            'title' => '무제한 11GB+3M(밀리의서재 Free)_11월',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 36,300원',
            'selection_count' => '6,970명이 선택',
            'gifts' => [
                'KT유심&배송비 무료',
                '데이터쿠폰 20GB x 3회',
                '추가데이터 20GB 제공',
                '이마트 상품권',
                '네이버페이',
                '밀리의 서재'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => 'KT유심&배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '데이터쿠폰'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/millie.svg', 'alt' => '밀리의 서재']
            ]
        ],
        [
            'id' => 32630,
            'provider' => '찬스모바일',
            'rating' => '4.5',
            'title' => '음성기본 11GB+일 2GB+',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'LG U+망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 38,500원',
            'selection_count' => '31,315명이 선택',
            'gifts' => [
                '유심/배송비 무료',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => '유심/배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32631,
            'provider' => '이야기모바일',
            'rating' => '4.5',
            'title' => '이야기 완전 무제한 11GB+',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 39,600원',
            'selection_count' => '13,651명이 선택',
            'gifts' => [
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32633,
            'provider' => '찬스모바일',
            'rating' => '4.5',
            'title' => '100분 15GB+',
            'data_main' => '월 15GB + 3Mbps',
            'features' => ['통화 100분', '문자 100건', 'LG U+망', 'LTE'],
            'price_main' => '월 14,000원',
            'price_after' => '7개월 이후 30,580원',
            'selection_count' => '7,977명이 선택',
            'gifts' => [
                '유심/배송비 무료',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/etc.svg', 'alt' => '유심/배송비 무료'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32634,
            'provider' => '이야기모바일',
            'rating' => '4.5',
            'title' => '이야기 완전 무제한 11GB+',
            'data_main' => '월 11GB + 매일 2GB + 3Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'SKT망', 'LTE'],
            'price_main' => '월 15,000원',
            'price_after' => '7개월 이후 39,600원',
            'selection_count' => '13,651명이 선택',
            'gifts' => [
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ],
        [
            'id' => 32635,
            'provider' => '핀다이렉트',
            'rating' => '4.2',
            'title' => '[K] 핀다이렉트Z 7GB+(네이버페이) _2511',
            'data_main' => '월 7GB + 1Mbps',
            'features' => ['통화 무제한', '문자 무제한', 'KT망', 'LTE'],
            'price_main' => '월 8,000원',
            'price_after' => '7개월 이후 26,400원',
            'selection_count' => '4,407명이 선택',
            'gifts' => [
                '추가 데이터',
                '매월 5GB 추가 데이터',
                '이마트 상품권',
                '네이버페이',
                '네이버페이'
            ],
            'gift_icons' => [
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가 데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/ticket.svg', 'alt' => '추가 데이터'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/emart.svg', 'alt' => '이마트 상품권'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이'],
                ['src' => 'https://assets.moyoplan.com/image/mvno-gifts/badge/naverpay.svg', 'alt' => '네이버페이']
            ]
        ]
    ];
    
    // limit만큼만 반환
    return array_slice($allPlans, 0, $limit);
}

/**
 * 요금제 상세 데이터 가져오기
 * @param int $plan_id 요금제 ID
 * @return array|null 요금제 데이터 또는 null
 */
function getPlanDetailData($plan_id) {
    // 임시 하드코딩 데이터 (나중에 DB 쿼리로 교체)
    $plans = getPlansData();
    foreach ($plans as $plan) {
        if ($plan['id'] == $plan_id) {
            return $plan;
        }
    }
    return null;
}

