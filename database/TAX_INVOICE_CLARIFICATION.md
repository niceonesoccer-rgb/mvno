# 세금계산서 발행 시스템 명확화

## 📋 목적 명확화

세금계산서 발행 페이지(`/admin/tax-invoice/issue.php`)는:

1. **기간별 입금 금액을 확인**하기 위한 페이지입니다.
2. 실제 세금계산서 발행은 사이트에서 불가능하며, 외부에서 발행합니다.
3. 외부에서 세금계산서를 발행한 후, 해당 입금 건들에 대해 **발행 완료 처리**만 합니다.

---

## 🗄️ 데이터베이스 구조

### deposit_requests 테이블의 세금계산서 관련 필드

```sql
`tax_invoice_status` ENUM('unissued', 'issued', 'cancelled') NOT NULL DEFAULT 'unissued' 
COMMENT '세금계산서 발행 상태 (미발행, 발행, 취소)',
`tax_invoice_issued_at` DATETIME DEFAULT NULL COMMENT '세금계산서 발행 일시',
`tax_invoice_issued_by` VARCHAR(50) DEFAULT NULL COMMENT '세금계산서 발행 처리한 관리자 ID',
```

**상태 설명:**
- `unissued`: 미발행 (기본값)
- `issued`: 발행 (외부에서 발행 후 체크 처리)
- `cancelled`: 취소

---

## 📄 페이지 기능

### 세금계산서 발행 페이지 (`/admin/tax-invoice/issue.php`)

**기능:**
1. 기간별 입금 내역 조회
2. 입금 건별로 세금계산서 발행 상태 표시 및 변경
3. 입금 상태(입금/미입금)로 필터링

**필터링:**
- **입금 상태**: 전체, 입금(confirmed), 미입금(unpaid)
- **세금계산서 발행 상태**: 전체, 발행(issued), 미발행(unissued), 취소(cancelled)

**표시 정보:**
- 입금 신청 ID
- 판매자 ID
- 입금자명
- 입금 금액 (부가세 포함)
- 공급가액
- 부가세
- 입금 상태 (입금/미입금)
- 입금 확인일시
- 세금계산서 발행 상태 (발행/미발행/취소) - 드롭다운으로 선택 가능

**액션:**
- 각 입금 건별로 세금계산서 발행 상태를 변경할 수 있음
- 상태 변경 시 `tax_invoice_status`, `tax_invoice_issued_at`, `tax_invoice_issued_by` 업데이트

---

## 🔄 처리 플로우

```
1. 관리자가 세금계산서 발행 페이지 접속
   └─> 기간 설정 및 필터 선택
       └─> [조회] 버튼 클릭
           └─> 입금 건 목록 표시

2. 입금 건별 세금계산서 발행 상태 확인
   └─> 외부에서 세금계산서 발행 완료
       └─> 관리자가 해당 입금 건의 상태를 "발행"으로 변경
           └─> tax_invoice_status = 'issued'
               └─> tax_invoice_issued_at = NOW()
               └─> tax_invoice_issued_by = 현재 관리자 ID

3. 취소 처리 (필요 시)
   └─> tax_invoice_status = 'cancelled'
```

---

## ✅ 확인사항

- ✅ 세금계산서 발행 페이지는 입금 금액 확인용
- ✅ 실제 발행은 외부에서 처리
- ✅ 입금 건별로 발행 상태만 체크 처리
- ✅ 입금 상태(입금/미입금) 필터 제공
- ✅ 세금계산서 발행 상태(발행/미발행/취소) 필터 제공
