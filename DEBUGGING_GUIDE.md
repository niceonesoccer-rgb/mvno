# 인터넷 상담신청 버튼 디버깅 가이드

## 브라우저 개발자 도구 열기

### 방법 1: 키보드 단축키
- **Windows/Linux**: `F12` 또는 `Ctrl + Shift + I`
- **Mac**: `Cmd + Option + I`

### 방법 2: 마우스 우클릭
1. 페이지에서 아무 곳이나 **우클릭**
2. **"검사"** 또는 **"Inspect"** 선택

### 방법 3: 브라우저 메뉴
- **Chrome/Edge**: 메뉴(⋮) → 도구 더보기 → 개발자 도구
- **Firefox**: 메뉴(☰) → 웹 개발자 → 개발자 도구
- **Safari**: 환경설정 → 고급 → "메뉴 막대에서 개발자용 메뉴 보기" 체크 후 개발자 메뉴 사용

## 콘솔 탭 확인하기

1. 개발자 도구가 열리면 상단의 **"Console"** 탭 클릭
2. 콘솔 창에서 다음과 같은 로그를 확인할 수 있습니다:

```
checkAllAgreements - Validation: {
  isNameValid: true/false,
  isPhoneValid: true/false,
  isEmailValid: true/false,
  isAgreementsChecked: true/false,
  name: "...",
  phone: "...",
  email: "...",
  agreePurpose: true/false,
  agreeItems: true/false,
  agreePeriod: true/false,
  agreeThirdParty: true/false
}
```

3. 버튼 상태에 따라 다음 중 하나가 표시됩니다:
   - `checkAllAgreements - Button ENABLED` (활성화됨)
   - `checkAllAgreements - Button DISABLED - Reasons: {...}` (비활성화됨)

## 확인해야 할 항목

### 버튼이 활성화되지 않을 때

콘솔에서 `Button DISABLED - Reasons`를 확인하고 다음을 체크하세요:

1. **name: 'invalid'** 
   - 이름 필드가 비어있음
   - 해결: 이름을 입력하세요

2. **phone: 'invalid'**
   - 전화번호가 010으로 시작하지 않음
   - 전화번호가 11자리가 아님
   - 해결: 010-XXXX-XXXX 형식으로 입력하세요

3. **email: 'invalid'**
   - 이메일이 비어있거나 형식이 잘못됨
   - 해결: 이메일을 입력하거나 @ 기호가 포함된 형식으로 입력하세요

4. **agreements: 'not checked'**
   - 개인정보 동의 체크박스가 모두 체크되지 않음
   - 해결: 모든 개인정보 동의 항목을 체크하세요

## 실시간 디버깅

1. 개발자 도구를 열어둔 상태에서
2. 인터넷 상담신청 모달을 엽니다
3. 정보를 입력하거나 체크박스를 클릭할 때마다
4. 콘솔에 자동으로 로그가 출력됩니다

## 필터링 사용하기

콘솔 상단의 필터 입력란에 `checkAllAgreements`를 입력하면
해당 로그만 필터링하여 볼 수 있습니다.

## 스크린샷 찍기

문제가 발생하면:
1. 개발자 도구의 Console 탭을 열어둔 상태
2. `Print Screen` 키로 스크린샷 찍기
3. 또는 브라우저 확장 프로그램 사용

## 추가 팁

- 콘솔 로그가 너무 많으면 `Ctrl + L` (또는 `Cmd + K` on Mac)로 콘솔 지우기
- 특정 로그를 클릭하면 해당 코드 위치로 이동할 수 있습니다
- 오른쪽 클릭 → "Save as..."로 로그를 파일로 저장할 수 있습니다




