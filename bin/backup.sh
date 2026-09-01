#!/usr/bin/env bash
set -euo pipefail
umask 077

project_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
if [[ -f "$project_dir/.env" ]]; then set -a; source "$project_dir/.env"; set +a; fi
: "${BACKUP_EXTERNAL_DIR:?BACKUP_EXTERNAL_DIR is required}"
: "${BACKUP_PASSPHRASE:?BACKUP_PASSPHRASE is required}"
: "${DB_DATABASE:?DB_DATABASE is required}"
: "${DB_USERNAME:?DB_USERNAME is required}"
: "${DB_PASSWORD:?DB_PASSWORD is required}"

case "$BACKUP_EXTERNAL_DIR" in /|"$project_dir"|"$project_dir"/*) echo 'Backup directory must be external to the project.' >&2; exit 2;; esac
mkdir -p -- "$BACKUP_EXTERNAL_DIR"
work_dir="$(mktemp -d)"
trap 'rm -rf -- "$work_dir"' EXIT
stamp="$(date -u +%Y%m%dT%H%M%SZ)"
name="church-backup-$stamp"
defaults="$work_dir/mysql.cnf"
printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' "${DB_HOST:-127.0.0.1}" "${DB_PORT:-3306}" "$DB_USERNAME" "$DB_PASSWORD" > "$defaults"

mysqldump --defaults-extra-file="$defaults" --single-transaction --routines --triggers --events --hex-blob "$DB_DATABASE" > "$work_dir/database.sql"
tar -C "$work_dir" -rf "$work_dir/payload.tar" database.sql
if [[ -d "$project_dir/storage/uploads" ]]; then tar -C "$project_dir" -rf "$work_dir/payload.tar" storage/uploads; fi
gzip -f "$work_dir/payload.tar"
openssl enc -aes-256-cbc -salt -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$work_dir/payload.tar.gz" -out "$work_dir/$name.tar.gz.enc"
openssl enc -d -aes-256-cbc -pbkdf2 -iter 200000 -pass env:BACKUP_PASSPHRASE -in "$work_dir/$name.tar.gz.enc" | tar -tzf - >/dev/null
(cd "$work_dir" && sha256sum "$name.tar.gz.enc" > "$name.tar.gz.enc.sha256")
mv -- "$work_dir/$name.tar.gz.enc" "$BACKUP_EXTERNAL_DIR/"
mv -- "$work_dir/$name.tar.gz.enc.sha256" "$BACKUP_EXTERNAL_DIR/"
find "$BACKUP_EXTERNAL_DIR" -maxdepth 1 -type f -name 'church-backup-*.tar.gz.enc' -mtime +7 -print -delete
find "$BACKUP_EXTERNAL_DIR" -maxdepth 1 -type f -name 'church-backup-*.tar.gz.enc.sha256' -mtime +7 -print -delete
printf 'Backup verified: %s\n' "$BACKUP_EXTERNAL_DIR/$name.tar.gz.enc"
