#!/usr/bin/env bash
set -Eeuo pipefail
APP_DIR="/var/www/church-platform"; APP_USER="churchadmin"; BRANCH="main"; PHP_VERSION="8.3"; DOMAIN=""; REPO=""; ENV_FILE=""; SSL=0; FIREWALL=0
die(){ echo "ERROR: $*" >&2; exit 1; }
usage(){ echo "Usage: sudo $0 --domain NAME --repo URL --env-file PATH [--app-dir PATH] [--branch NAME] [--enable-ssl] [--enable-firewall]"; }
while (($#)); do case "$1" in --domain) DOMAIN="$2";shift 2;; --repo) REPO="$2";shift 2;; --env-file) ENV_FILE="$2";shift 2;; --app-dir) APP_DIR="$2";shift 2;; --branch) BRANCH="$2";shift 2;; --enable-ssl) SSL=1;shift;; --enable-firewall) FIREWALL=1;shift;; -h|--help) usage;exit;; *) die "unknown option $1";; esac; done
[[ $EUID -eq 0 ]] || die "run as root"; [[ "$DOMAIN" =~ ^[A-Za-z0-9.-]+$ ]] || die "valid domain required"; [[ -n "$REPO" ]] || die "repo required"; [[ -f "$ENV_FILE" ]] || die "env file required"; [[ "$APP_DIR" == /var/www/* ]] || die "app dir must be under /var/www"
export DEBIAN_FRONTEND=noninteractive
apt-get update
apt-get install -y nginx mariadb-server mariadb-client git curl rsync ufw certbot python3-certbot-nginx "php$PHP_VERSION-fpm" "php$PHP_VERSION-cli" "php$PHP_VERSION-mysql" "php$PHP_VERSION-mbstring" "php$PHP_VERSION-xml" "php$PHP_VERSION-curl" "php$PHP_VERSION-gd" "php$PHP_VERSION-zip"
id "$APP_USER" >/dev/null 2>&1 || adduser --disabled-password --gecos "" "$APP_USER"; usermod -aG www-data "$APP_USER"; install -d -o "$APP_USER" -g www-data -m 2775 "$APP_DIR"
if [[ -d "$APP_DIR/.git" ]]; then [[ "$(git -C "$APP_DIR" remote get-url origin)" == "$REPO" ]] || die "origin mismatch"; sudo -u "$APP_USER" git -C "$APP_DIR" fetch origin; sudo -u "$APP_USER" git -C "$APP_DIR" checkout "$BRANCH"; sudo -u "$APP_USER" git -C "$APP_DIR" pull --ff-only origin "$BRANCH"; elif [[ -z "$(find "$APP_DIR" -mindepth 1 -maxdepth 1 -print -quit)" ]]; then sudo -u "$APP_USER" git clone -b "$BRANCH" --single-branch "$REPO" "$APP_DIR"; else die "app directory is not empty"; fi
install -o "$APP_USER" -g www-data -m 640 "$ENV_FILE" "$APP_DIR/.env"; install -d -o "$APP_USER" -g www-data -m 2770 "$APP_DIR/storage/uploads" "$APP_DIR/storage/logs"
set -a; source "$APP_DIR/.env"; set +a; : "${DB_DATABASE:?}" "${DB_USERNAME:?}" "${DB_PASSWORD:?}"; [[ "$DB_DATABASE" =~ ^[A-Za-z0-9_]+$ && "$DB_USERNAME" =~ ^[A-Za-z0-9_]+$ ]] || die "unsafe DB identifier"; escaped="${DB_PASSWORD//\'/\'\'}"
mariadb -e "CREATE DATABASE IF NOT EXISTS \`$DB_DATABASE\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; CREATE USER IF NOT EXISTS '$DB_USERNAME'@'127.0.0.1' IDENTIFIED BY '$escaped'; ALTER USER '$DB_USERNAME'@'127.0.0.1' IDENTIFIED BY '$escaped'; GRANT SELECT,INSERT,UPDATE,DELETE,CREATE,ALTER,INDEX,DROP,REFERENCES ON \`$DB_DATABASE\`.* TO '$DB_USERNAME'@'127.0.0.1'; FLUSH PRIVILEGES;"
if grep -Eq '^APP_KEY=(|base64:replace-with)' "$APP_DIR/.env"; then key="$(sudo -u "$APP_USER" php "$APP_DIR/bin/generate-key.php")"; sed -i "s|^APP_KEY=.*|APP_KEY=$key|" "$APP_DIR/.env"; fi
sudo -u "$APP_USER" php "$APP_DIR/bin/migrate.php"
cat > /etc/nginx/sites-available/church-platform <<EOF
server {
 listen 80; listen [::]:80; server_name $DOMAIN www.$DOMAIN;
 root $APP_DIR/public; index index.php; client_max_body_size 2m;
 location / { try_files \$uri \$uri/ /index.php?\$query_string; }
 location ~ \.php$ { include snippets/fastcgi-php.conf; fastcgi_pass unix:/run/php/php$PHP_VERSION-fpm.sock; }
 location ~ /\. { deny all; }
}
EOF
ln -sfn /etc/nginx/sites-available/church-platform /etc/nginx/sites-enabled/church-platform; rm -f /etc/nginx/sites-enabled/default; nginx -t; systemctl enable --now nginx mariadb "php$PHP_VERSION-fpm"; systemctl reload nginx
install -o root -g root -m 700 "$APP_DIR/ops/church-platform-backup" /usr/local/sbin/church-platform-backup; install -o root -g root -m 700 "$APP_DIR/ops/church-platform-health" /usr/local/sbin/church-platform-health
for name in backup health; do cat > "/etc/systemd/system/church-platform-$name.service" <<EOF
[Unit]
Description=Church Platform $name
[Service]
Type=oneshot
Environment=APP_DIR=$APP_DIR
ExecStart=/usr/local/sbin/church-platform-$name
EOF
done
cat > /etc/systemd/system/church-platform-backup.timer <<'EOF'
[Timer]
OnCalendar=*-*-* 03:30:00
RandomizedDelaySec=15m
Persistent=true
[Install]
WantedBy=timers.target
EOF
cat > /etc/systemd/system/church-platform-health.timer <<'EOF'
[Timer]
OnBootSec=2m
OnUnitActiveSec=5m
Persistent=true
[Install]
WantedBy=timers.target
EOF
systemctl daemon-reload; systemctl enable --now church-platform-backup.timer church-platform-health.timer
if ((FIREWALL)); then ufw allow OpenSSH; ufw allow 'Nginx Full'; ufw --force enable; fi
if ((SSL)); then certbot --nginx --redirect --non-interactive --agree-tos --register-unsafely-without-email -d "$DOMAIN" -d "www.$DOMAIN"; fi
curl --fail --silent --show-error --max-time 15 -H "Host: $DOMAIN" http://127.0.0.1/login >/dev/null
echo "INSTALL_OK. Next: sudo -u $APP_USER php $APP_DIR/bin/create-platform-admin.php"
