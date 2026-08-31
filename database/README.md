# Database migrations

`database/migrations`의 SQL 파일은 파일명 순서로 한 번씩 실행된다.

```bash
php bin/migrate.php
```

MariaDB의 DDL은 일부 버전에서 암묵적 커밋이 발생할 수 있으므로 운영 적용 전 백업하고 스테이징에서 검증한다. 이미 적용된 마이그레이션 파일은 수정하지 않고 새 파일을 추가한다.
