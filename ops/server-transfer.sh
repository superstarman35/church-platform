#!/usr/bin/env bash
set -Eeuo pipefail
umask 077
APP_DIR="${APP_DIR:-/var/www/church-platform}"
usage(){ echo "Usage: sudo $0 export OUTPUT_DIR | verify BUNDLE_DIR | restore BUNDLE_DIR --replace"; }
die(){ echo "ERROR: $*" >&2; exit 1; }
load_env(){ [[ -r "$APP_DIR/.env" ]] || die ".env unreadable"; set -a; source "$APP_DIR/.env"; set +a; : "${DB_HOST:?}" "${DB_PORT:?}" "${DB_DATABASE:?}" "${DB_USERNAME:?}" "${DB_PASSWORD:?}"; [[ "$DB_DATABASE" =~ ^[A-Za-z0-9_]+$ ]] || die "unsafe DB name"; }
make_cnf(){ CNF="$(mktemp)"; trap 'rm -f -- "$CNF"' EXIT; printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword=%s\n' "$DB_HOST" "$DB_PORT" "$DB_USERNAME" "$DB_PASSWORD" > "$CNF"; }
verify(){ [[ -d "$1" && -f "$1/SHA256SUMS" ]] || die "invalid bundle"; (cd "$1" && sha256sum -c SHA256SUMS); gzip -t "$1/database.sql.gz"; tar -tzf "$1/uploads.tar.gz" | awk '/(^|\/)\.\.($|\/)|^\//{bad=1}END{exit bad}' || die "unsafe archive"; }
[[ $EUID -eq 0 ]] || die "run as root"; cmd="${1:-}"; bundle="${2:-}"
case "$cmd" in
 export)
  [[ -n "$bundle" ]] || { usage; exit 2; }; load_env; make_cnf; target="$bundle/$(date +%Y%m%d_%H%M%S)"; mkdir -p "$target"
  mariadb-dump --defaults-extra-file="$CNF" --single-transaction --routines --triggers "$DB_DATABASE" | gzip -9 > "$target/database.sql.gz"
  tar -czf "$target/uploads.tar.gz" -C "$APP_DIR/storage" uploads; (cd "$target" && sha256sum database.sql.gz uploads.tar.gz > SHA256SUMS); echo "EXPORT_OK $target" ;;
 verify) verify "$bundle"; echo "VERIFY_OK $bundle" ;;
 restore)
  [[ "${3:-}" == "--replace" ]] || die "restore requires --replace"; verify "$bundle"; load_env; make_cnf; test_db="${DB_DATABASE}_restore_test_$$"
  mariadb -e "CREATE DATABASE \`$test_db\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
  trap 'mariadb -e "DROP DATABASE IF EXISTS \`$test_db\`"; rm -f -- "$CNF"' EXIT
  gzip -dc "$bundle/database.sql.gz" | mariadb "$test_db"
  tables="$(mariadb -Nse "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$test_db'")"; ((tables > 0)) || die "empty test restore"
  pre="/var/backups/church-platform/pre-restore"; "$0" export "$pre"
  mariadb -e "DROP DATABASE \`$DB_DATABASE\`; CREATE DATABASE \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci"
  gzip -dc "$bundle/database.sql.gz" | mariadb "$DB_DATABASE"
  rm -rf -- "$APP_DIR/storage/uploads"; tar -xzf "$bundle/uploads.tar.gz" -C "$APP_DIR/storage"; chown -R churchadmin:www-data "$APP_DIR/storage/uploads"
  echo "RESTORE_OK tables=$tables pre_restore=$pre" ;;
 *) usage; exit 2 ;;
esac
