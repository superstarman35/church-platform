# Church Invitation Platform

PHP·MariaDB 기반 멀티 교회 전도지·초대장 플랫폼이다. 현재 관리자 개발계획의 0·1단계가 구현되어 있다.

## 현재 구현 범위

- 외부 패키지 없는 PHP 8.1+ 기본 구조
- `.env` 환경설정과 PDO MariaDB 연결
- SQL 마이그레이션과 적용 이력
- 플랫폼 관리자 로그인·TOTP 2단계 인증·로그아웃
- 로그인 실패 누적·잠금과 세션 ID 재발급
- 교회·단체와 대표 관리자 생성
- 초대장 전용 30일 체험 자동 생성
- 추가 교회 관리자 생성
- 플랫폼·교회 관리자 권한 분리
- 세션 기반 `church_id` 격리
- CSRF, 출력 이스케이프와 관리자 감사 로그

## 필요 환경

- PHP 8.1 이상과 `pdo_mysql`, `mbstring`, `json`, `openssl`
- MariaDB 10.5 이상 권장
- Nginx 또는 Apache

## 설치

```bash
cp .env.example .env
```

`.env`의 DB 접속정보와 애플리케이션 주소를 실제 환경에 맞게 변경한다. 다음 명령의 출력으로 `APP_KEY`를 설정한다. `.env`는 Git에 포함되지 않는다.

```bash
php bin/generate-key.php
```

MariaDB에서 DB와 최소 권한 사용자를 만든다.

```sql
CREATE DATABASE church_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'church_app'@'localhost' IDENTIFIED BY '실제-강력한-비밀번호';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP, REFERENCES ON church_platform.* TO 'church_app'@'localhost';
FLUSH PRIVILEGES;
```

마이그레이션과 최초 플랫폼 관리자를 생성한다.

```bash
php bin/migrate.php
php bin/create-platform-admin.php
```

개발 서버를 실행한다.

```bash
php -S 127.0.0.1:8080 -t public
```

브라우저에서 `http://127.0.0.1:8080/login`으로 접속한다.

운영 웹서버의 문서 루트는 반드시 `public/`으로 설정하고 `APP_DEBUG=false`, `SESSION_SECURE=true`를 사용한다.

## 주요 경로

- `/login`: 공통 관리자 로그인
- `/control`: 플랫폼 대시보드
- `/control/churches`: 교회·단체 관리
- `/control/churches/create`: 교회·대표 관리자·30일 체험 생성
- `/admin`: 현재 세션 교회의 초대장 관리자 대시보드

## 테스트

PHP가 설치된 환경:

```bash
php tests/run.php
```

Windows에서 PHP 설치 전 구조·보안 계약 검사:

```powershell
powershell -ExecutionPolicy Bypass -File tests/security-contract.ps1
```

실제 MariaDB 통합 테스트는 `.env`에 테스트 전용 DB를 지정한 뒤 마이그레이션과 로그인·교회 격리 흐름으로 수행한다. 운영 DB를 테스트에 사용하지 않는다.

## 다음 개발 단계

전도지·초대장 전용 관리자와 다중 초대장 생성·공개 기능을 구현한다. 교회 홈페이지는 초대장 MVP 안정화 후 개발한다.
