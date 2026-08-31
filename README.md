# Church Platform

작은 교회를 위한 홈페이지, 온라인 주보, 모바일 초대장 및 신청 관리 통합 서비스입니다.

## 현재 상태

현재 저장소는 제품 기획과 서버·데이터·운영 구조를 정리하는 초기 설계 단계입니다.

- 제품 범위와 요금제
- 카페24 기반 분산 서버 구성
- 교회 20개 단위 서버 확장
- PHP·MariaDB 중심 기술 구성
- 홈페이지 및 모바일 초대장 레이아웃
- 교회별 트래픽 측정과 제한
- 서버 설치·백업·보안·자동화 계획

자세한 내용은 [PRODUCT_PLAN.md](PRODUCT_PLAN.md)를 참고하세요.

## 예정 기술 구성

- Ubuntu Server 22.04
- Nginx
- PHP-FPM / Laravel
- MariaDB
- Blade 중심 화면, 필요 시 React
- PDF·이미지·자동화 작업용 Python
- Cloudflare DNS·SSL·CDN

## 서버 구성

- 메인: 카페24 비즈니스 VPS 1대
- 교회 서버: 카페24 자이언트 VPS, 서버당 최대 20개 교회
- 교회가 늘어날 때 자이언트 VPS를 순차 추가

## 문서

- [제품 및 인프라 기획서](PRODUCT_PLAN.md)

