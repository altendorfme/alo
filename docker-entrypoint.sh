#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log_success() {
    echo -e "${GREEN}[✓] $1${NC}"
}

log_error() {
    echo -e "${RED}[✗] $1${NC}"
    exit 1
}

log_info() {
    echo -e "${YELLOW}[i] $1${NC}"
}

check_nginx() {
    log_info "Checking Nginx process status..."
    local max_attempts=5
    local attempt=0

    while [ $attempt -lt $max_attempts ]; do
        if pgrep nginx > /dev/null; then
            log_success "Nginx started successfully"
            return 0
        fi
        
        log_info "Waiting for Nginx to start... (Attempt $((attempt+1))/$max_attempts)"
        sleep 3
        attempt=$((attempt+1))
    done

    log_error "Failed to start Nginx after $max_attempts attempts"
}

check_php_fpm() {
    log_info "Checking PHP-FPM process status..."
    local max_attempts=5
    local attempt=0

    while [ $attempt -lt $max_attempts ]; do
        if pgrep php-fpm > /dev/null; then
            log_success "PHP-FPM started successfully"
            return 0
        fi
        
        log_info "Waiting for PHP-FPM to start... (Attempt $((attempt+1))/$max_attempts)"
        sleep 3
        attempt=$((attempt+1))
    done

    log_error "Failed to start PHP-FPM after $max_attempts attempts"
}

check_supervisord() {
    log_info "Checking Supervisord process status..."
    
    log_info "Restarting supervisord..."
    pkill supervisord || true
    sleep 2
    /usr/bin/supervisord -c /etc/supervisor/supervisord.conf
    
    local max_attempts=30
    local attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if supervisorctl -c /etc/supervisor/supervisord.conf status | grep -q "RUNNING"; then
            log_success "Supervisord configuration is valid and processes are running"
            return 0
        fi
        
        log_info "Waiting for Supervisord processes to start... (Attempt $((attempt+1))/$max_attempts)"
        sleep 3
        attempt=$((attempt+1))
    done

    log_error "Not all Supervisord processes are running after $max_attempts attempts"
}

echo -e "\n${YELLOW}Alô: Starting${NC}\n"

# Set timezone
if [ -n "$TZ" ]; then
    log_info "Setting timezone to $TZ..."
    ln -snf /usr/share/zoneinfo/$TZ /etc/localtime
    echo $TZ > /etc/timezone
    
    echo "date.timezone = $TZ" > /usr/local/etc/php/conf.d/99-timezone.ini
    log_success "Timezone set to $TZ for both system and PHP"
else
    log_info "No TZ environment variable set, using UTC as default timezone"

    ln -snf /usr/share/zoneinfo/UTC /etc/localtime
    echo "UTC" > /etc/timezone
    
    echo "date.timezone = UTC" > /usr/local/etc/php/conf.d/99-timezone.ini
    log_success "Timezone set to UTC for both system and PHP"
fi

# Set Workers
if [ -z "$WORKERS" ]; then
    log_info "No WORKERS environment variable set, using 1 as default"
    export WORKERS=1
else
    log_success "Using $WORKERS worker(s)"
fi

# PHP-FPM
if [ ! -d /var/run/php ]; then
    log_info "Creating PHP-FPM directory..."
    mkdir -p /var/run/php
    chown -R www-data:www-data /var/run/php
    log_success "PHP-FPM directory created"
fi

log_info "Starting PHP-FPM..."
php-fpm &
check_php_fpm

# NGINX
log_info "Checking Nginx configuration..."
nginx -t
if [ $? -ne 0 ]; then
    log_error "Invalid Nginx configuration"
else
    log_success "Valid Nginx configuration"
fi

log_info "Starting Nginx..."
nginx -g "daemon off;" &
check_nginx

# Cron
log_info "Setting up cron jobs..."

(
    echo "0 0 1 * * /usr/local/bin/php /app/bin/alo geoip:update 2>&1 | tee -a /proc/1/fd/1"
    echo "*/5 * * * * /usr/local/bin/php /app/bin/alo campaign:queue 2>&1 | tee -a /proc/1/fd/1"
    echo "0 4 * * 1 /usr/local/bin/php /app/bin/alo optimize:database --innodb-only 5 2>&1 | tee -a /proc/1/fd/1"
    echo "0 3 * * * /usr/local/bin/php /app/bin/alo optimize:analytics:subscribers 31 2>&1 | tee -a /proc/1/fd/1"
    echo "0 2 * * * /usr/local/bin/php /app/bin/alo campaign:draft:cleanup 31 2>&1 | tee -a /proc/1/fd/1"
) | crontab -

service cron restart

log_success "Cron jobs added"

# GeoIP
log_info "Checking GeoIP database..."
if [ ! -f /app/config/GeoLite2-City.mmdb ]; then
    log_info "GeoLite2-City.mmdb not found. Updating GeoIP database..."
    php /app/bin/alo geoip:update
    if [ $? -eq 0 ]; then
        log_success "GeoIP database updated successfully"
    else
        log_error "Failed to update GeoIP database"
    fi
else
    log_success "GeoIP database exists"
fi

# Permissions
log_info "Adjusting directory permissions..."

mkdir -p /app/config
chown -R www-data:www-data /app/config
chmod -R 775 /app/config

log_success "Permissions adjusted"

# Check for .env file
check_env_file() {
    log_info "Checking for config file..."
    local attempt=1

    while true; do
        if [ -f /app/config/.env ]; then
            return 0
        fi
        
        log_info "While waiting for the installation to finish, access /install... (Attempt $attempt)"
        sleep 60
        attempt=$((attempt+1))
    done
}

# Run database migrations
log_info "Running database migrations..."
php /app/bin/alo app:migration
if [ $? -eq 0 ]; then
    log_success "Database migrations completed successfully"
else
    log_error "Failed to run database migrations"
fi

# Supervisord (campaign:send)
log_info "Start supervisord (campaign:send)..."

/usr/bin/supervisord -c /etc/supervisor/supervisord.conf &
check_env_file
check_supervisord

log_success "Supervisord started"

echo -e "\n${GREEN}Alô: Initialized ===${NC}\n"

wait -n

exit $?