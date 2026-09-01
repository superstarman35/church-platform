#!/usr/bin/env bash
set -euo pipefail
umask 077

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ -f "$project_dir/.env" ]]; then set -a; source "$project_dir/.env"; set +a; fi
: "${BACKUP_PASSPHRASE:?BACKUP_PASSPHRASE is required}"
: "${RESTORE_TEST_DB:?RESTORE_TEST_DB is required and must be a dedicated empty test database}"
backup_file="${1:?Usage: bin/restore-test.sh /external/path/church-backup-*.tar.gz.enc}"
[[ -f "$backup_file" && -f "$backup_file.sha256" ]] || { echo 'Backup or checksum file not found.' >&2; exit 2; }
[[ "$RESTORE_TEST_DB" != "${DB_DATABASE:-}" ]] || { echo 'Refusing to restore into the application database.' >&2; exit 2; }
(cd "$(dirname "$backup_file")" && sha256sum -c "$(basename "$backup_file").sha256")
work_dir="$(mktemp -d)"; trap 'rm -rf -- "$work_dir"' EXIT
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$backup_file" -out "$work_dir/payload.tar.gz"
tar -xzf "$work_dir/payload.tar.gz" -C "$work_dir"
defaults="$work_dir/mysql.cnf"
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' "${DB_HOST:-127.0.0.1}" "${DB_PORT:-3306}" "${DB_USERNAME:?}" "${DB_PASSWORD:?}" > "$defaults"
mysql --defaults-extra-file="$defaults" "$RESTORE_TEST_DB" < "$work_dir/database.sql"
mysql --defaults-extra-file="$defaults" --batch --skip-column-names "$RESTORE_TEST_DB" -e 'SELECT COUNT(*) FROM schema_migrations;'
printf 'Restore test succeeded in dedicated database: %s\n' "$RESTORE_TEST_DB"
