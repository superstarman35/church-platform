# 초대장 관리자 MVP 실행 및 운영 안내

## 구현 범위

- 플랫폼 관리자: 로그인, TOTP MFA, 교회·단체 및 최초 관리자 생성
- 교회 관리자: 대시보드, 초대장 목록/생성/수정/복제/게시/종료, 신청자 목록
- 공개 사용자: 모바일 초대장 열람, YouTube 영상, 지도 이동, 공유, 참석 신청
- 사용량: 월 트래픽, 저장 용량, 조회·공유·신청 통계
- 구독 제한: 체험/베이직/그로스의 월 생성 수, 동시 게시 수, 신청 수, 관리자 수, 트래픽, 저장 용량
- 이미지: JPG/PNG/WebP 원본 1MB 이하, 최대 1080×1350, WebP 품질 78 자동 변환
- 격리: 모든 초대장·미디어·신청·통계 쿼리에 church_id 적용

## 서버 요구사항

- Linux
- PHP 8.1 이상: pdo_mysql, mbstring, json, openssl, gd
- MariaDB 10.6 이상 권장
- Apache 또는 Nginx의 DocumentRoot는 반드시 public 디렉터리로 지정
- storage/uploads 디렉터리에 웹서버 쓰기 권한 부여
- HTTPS 사용

## 최초 설치

1. 저장소를 서버에 복제한다.
2. .env.example을 .env로 복사하고 운영 DB와 세션 값을 입력한다.
3. MariaDB 데이터베이스와 최소 권한 전용 계정을 만든다.
4. php bin/migrate.php로 모든 마이그레이션을 적용한다.
5. php bin/create-platform-admin.php로 플랫폼 관리자와 MFA 정보를 생성한다.
6. 웹서버의 DocumentRoot를 public으로 설정하고 HTTPS를 적용한다.
7. storage/uploads는 PHP 실행 사용자만 쓰도록 권한을 제한한다.
8. 플랫폼 관리자 로그인 후 교회와 최초 교회 관리자를 생성한다.

실제 비밀번호, DB 비밀번호, APP_KEY, MFA 비밀키는 .env나 서버 비밀 저장소에만 두며 Git에 커밋하지 않는다.

## 개발 실행

로컬에 PHP와 MariaDB가 설치되어 있다는 전제에서 다음을 실행한다.

    Copy-Item .env.example .env
    php bin/migrate.php
    php bin/create-platform-admin.php
    php -S 127.0.0.1:8080 -t public

브라우저에서 http://127.0.0.1:8080/login 으로 접속한다. 운영 환경에서는 내장 서버를 사용하지 않는다.

## 주요 URL

- /login: 관리자 로그인
- /control: 플랫폼 관리자
- /admin: 교회 관리자 대시보드
- /admin/invitations: 초대장 관리
- /i/{교회슬러그}/{초대장슬러그}: 공개 모바일 초대장
- /media/{미디어UUID}: 게시된 초대장 대표 이미지

## DB 마이그레이션

- 202608310001_initial_identity.sql: 교회, 사용자, 역할, MFA, 구독, 감사로그
- 202608310002_invitation_admin.sql: 초대장, 이미지, 신청자, 일별 통계, 베이직/그로스 요금제

업무 테이블은 invitations, invitation_media, invitation_applications, invitation_daily_stats이며 모두 church_id 외래키와 교회별 인덱스를 가진다.

## 검증

GitHub Actions가 다음을 자동 검증한다.

- 전체 PHP 문법
- 빈 MariaDB 11.4에 마이그레이션 적용
- 보안·구독·이미지 정책 계약 테스트
- 두 교회 간 초대장 및 신청자 데이터 격리 통합 테스트
- 공개 URL, 구독 기능값, 통계 집계

## 다음 권장 작업

- 신청자 상태 변경과 CSV 내보내기
- 교회 기본 정보/로고 관리
- 갤러리 다중 이미지 및 정렬
- QR 코드 생성/다운로드
- 트래픽 경고 알림과 플랫폼 측 수동 초기화 기록
- 7일 순환 DB/업로드 백업 스크립트와 복구 리허설
