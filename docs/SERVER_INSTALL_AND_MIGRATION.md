# 서버 자동 설치 및 이전

Ubuntu 22.04/24.04 LTS용 운영 도구다. 실제 비밀번호, 토큰, 서버 IP와 운영 `.env`는 저장소에 넣지 않는다.

## 새 서버 설치

DNS를 새 서버에 연결하고 운영 `.env`를 서버의 `/root/church.env` 등에 별도로 준비한다.

```bash
sudo bash ops/install-server.sh \
  --domain example.org \
  --repo git@github.com:OWNER/REPOSITORY.git \
  --env-file /root/church.env \
  --enable-firewall
```

DNS 전파와 HTTP 접속을 확인한 뒤 SSL 옵션을 사용하거나 Certbot을 별도로 실행한다. 최초 플랫폼 관리자는 설치 완료 후 안내되는 PHP 명령으로 만든다.

## 이전 자료 내보내기

```bash
sudo bash ops/server-transfer.sh export /var/backups/church-transfer
sudo bash ops/server-transfer.sh verify /var/backups/church-transfer/날짜_시간
```

생성된 디렉터리를 `rsync` 또는 `scp`로 새 서버에 전송한다. 운영 `.env`는 보안 채널로 별도 전달한다.

## 새 서버에서 복원

먼저 설치 스크립트로 앱과 빈 DB를 준비한다. 복원은 체크섬·압축경로를 검사하고 임시 DB 복원에 성공한 뒤에만 진행한다. 실제 DB 교체에는 `--replace`가 반드시 필요하며, 교체 직전 백업도 만든다.

```bash
sudo bash ops/server-transfer.sh restore /secure/path/날짜_시간 --replace
```

복원 후 로그인, 교회 간 `church_id` 격리, 초대장 공개 URL, 업로드 파일, 백업 타이머를 확인한다.

## 자동 작업

- DB와 업로드는 매일 백업하며 정상 백업이 완성된 뒤에만 7일보다 오래된 백업을 삭제한다.
- 상태 점검은 Nginx, PHP-FPM, MariaDB, DB 마이그레이션, 로그인 URL, 디스크 사용률을 검사한다.
- 경로가 다르면 `APP_DIR`, 보관일은 `RETENTION_DAYS`, 디스크 경고 기준은 `MAX_DISK_PERCENT` 환경변수로 조정한다.
