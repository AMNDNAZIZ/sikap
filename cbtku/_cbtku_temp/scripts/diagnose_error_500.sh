#!/bin/bash

# ═══════════════════════════════════════════════════════════════
#  CBT Error 500 Diagnostic Tool
#  Jalankan: sudo bash scripts/diagnose_error_500.sh
# ═══════════════════════════════════════════════════════════════

APP_PATH="/www/wwwroot/cbtsmpistora.sch.id"
PHP_BIN="/www/server/php/82/bin/php"
LOG_DATE=$(date +%Y-%m-%d)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}  CBT Error 500 Diagnostic Tool${NC}"
echo -e "${CYAN}  $(date)${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo ""

# ─── 1. Redis ─────────────────────────────────────────────────
echo -e "${CYAN}1️⃣  Checking Redis...${NC}"
if command -v redis-cli &>/dev/null; then
    if redis-cli ping 2>/dev/null | grep -q "PONG"; then
        echo -e "   ${GREEN}✅ Redis is running${NC}"
        REDIS_KEYS=$(redis-cli DBSIZE 2>/dev/null)
        echo "   Keys in Redis: $REDIS_KEYS"
        REDIS_MEM=$(redis-cli INFO memory 2>/dev/null | grep used_memory_human | cut -d: -f2 | tr -d '\r')
        echo "   Memory used: $REDIS_MEM"
    else
        echo -e "   ${RED}❌ Redis is NOT responding${NC}"
        echo -e "   ${YELLOW}Fix: systemctl start redis${NC}"
        REDIS_STATUS=$(systemctl is-active redis 2>/dev/null || echo "unknown")
        echo "   Service status: $REDIS_STATUS"
    fi
else
    echo -e "   ${YELLOW}⚠️  redis-cli not found${NC}"
fi
echo ""

# ─── 2. PHP-FPM ───────────────────────────────────────────────
echo -e "${CYAN}2️⃣  Checking PHP-FPM...${NC}"
if systemctl is-active --quiet php-fpm-82 2>/dev/null; then
    echo -e "   ${GREEN}✅ PHP-FPM 8.2 is running${NC}"
elif [ -f "/etc/init.d/php-fpm-82" ]; then
    STATUS=$(/etc/init.d/php-fpm-82 status 2>/dev/null | head -1)
    echo -e "   ${YELLOW}⚠️  PHP-FPM status: $STATUS${NC}"
else
    echo -e "   ${YELLOW}⚠️  Cannot determine PHP-FPM status${NC}"
fi

# Check PHP extensions
if [ -f "$PHP_BIN" ]; then
    echo "   PHP version: $($PHP_BIN -r 'echo PHP_VERSION;' 2>/dev/null)"
    
    for EXT in redis intl mbstring json pdo_mysql; do
        if $PHP_BIN -m 2>/dev/null | grep -q "^$EXT$"; then
            echo -e "   ${GREEN}✅ Extension $EXT: loaded${NC}"
        else
            echo -e "   ${RED}❌ Extension $EXT: NOT loaded${NC}"
        fi
    done
fi
echo ""

# ─── 3. MySQL ─────────────────────────────────────────────────
echo -e "${CYAN}3️⃣  Checking MySQL...${NC}"
if command -v mysql &>/dev/null; then
    # Try to connect without password first
    if mysql -u root --connect-timeout=3 -e "SELECT 1" > /dev/null 2>&1; then
        echo -e "   ${GREEN}✅ MySQL is running${NC}"
        THREADS=$(mysql -u root -e "SHOW STATUS LIKE 'Threads_connected';" 2>/dev/null | awk 'NR==2{print $2}')
        MAX_CONN=$(mysql -u root -e "SHOW VARIABLES LIKE 'max_connections';" 2>/dev/null | awk 'NR==2{print $2}')
        echo "   Active connections: $THREADS / $MAX_CONN"
        
        if [ -n "$THREADS" ] && [ -n "$MAX_CONN" ] && [ "$MAX_CONN" -gt 0 ]; then
            PCT=$((THREADS * 100 / MAX_CONN))
            if [ $PCT -gt 80 ]; then
                echo -e "   ${RED}⚠️  Connection usage: ${PCT}% (HIGH!)${NC}"
            else
                echo -e "   ${GREEN}   Connection usage: ${PCT}%${NC}"
            fi
        fi
    else
        echo -e "   ${YELLOW}⚠️  MySQL check requires password (skipping detailed check)${NC}"
        if systemctl is-active --quiet mysql 2>/dev/null || systemctl is-active --quiet mysqld 2>/dev/null; then
            echo -e "   ${GREEN}✅ MySQL service is running${NC}"
        else
            echo -e "   ${RED}❌ MySQL service may not be running${NC}"
        fi
    fi
else
    echo -e "   ${YELLOW}⚠️  mysql client not found${NC}"
fi
echo ""

# ─── 4. File Permissions ──────────────────────────────────────
echo -e "${CYAN}4️⃣  Checking file permissions...${NC}"
DIRS=(
    "$APP_PATH/writable"
    "$APP_PATH/writable/cache"
    "$APP_PATH/writable/logs"
    "$APP_PATH/writable/session"
    "$APP_PATH/writable/uploads"
)

for DIR in "${DIRS[@]}"; do
    if [ -d "$DIR" ]; then
        if [ -w "$DIR" ]; then
            echo -e "   ${GREEN}✅ $DIR (writable)${NC}"
        else
            echo -e "   ${RED}❌ $DIR (NOT writable)${NC}"
            echo -e "   ${YELLOW}   Fix: chmod -R 777 $DIR${NC}"
        fi
    else
        echo -e "   ${RED}❌ $DIR (does not exist)${NC}"
        echo -e "   ${YELLOW}   Fix: mkdir -p $DIR && chmod 777 $DIR${NC}"
    fi
done
echo ""

# ─── 5. Recent Error Logs ─────────────────────────────────────
echo -e "${CYAN}5️⃣  Recent application errors (last 20 lines)...${NC}"
LOG_FILE="$APP_PATH/writable/logs/log-${LOG_DATE}.php"
if [ -f "$LOG_FILE" ]; then
    echo "   Log file: $LOG_FILE"
    echo ""
    grep -i "ERROR\|CRITICAL\|mulai\|CBT" "$LOG_FILE" | tail -20 | while read line; do
        echo "   $line"
    done
else
    echo -e "   ${YELLOW}⚠️  No log file for today: $LOG_FILE${NC}"
    # Try to find most recent log
    LATEST_LOG=$(ls -t "$APP_PATH/writable/logs/log-"*.php 2>/dev/null | head -1)
    if [ -n "$LATEST_LOG" ]; then
        echo "   Most recent log: $LATEST_LOG"
        grep -i "ERROR\|CRITICAL\|mulai\|CBT" "$LATEST_LOG" | tail -10 | while read line; do
            echo "   $line"
        done
    fi
fi
echo ""

# ─── 6. Nginx/Apache Error Log ────────────────────────────────
echo -e "${CYAN}6️⃣  Checking web server error log...${NC}"
NGINX_LOG="$APP_PATH/log/error.log"
if [ -f "$NGINX_LOG" ]; then
    echo "   Nginx log: $NGINX_LOG"
    tail -10 "$NGINX_LOG" | while read line; do
        echo "   $line"
    done
else
    # Try common locations
    for LOG in "/var/log/nginx/error.log" "/www/server/nginx/logs/error.log"; do
        if [ -f "$LOG" ]; then
            echo "   Found: $LOG"
            tail -5 "$LOG" | while read line; do
                echo "   $line"
            done
            break
        fi
    done
fi
echo ""

# ─── 7. .env Check ────────────────────────────────────────────
echo -e "${CYAN}7️⃣  Checking .env configuration...${NC}"
ENV_FILE="$APP_PATH/.env"
if [ -f "$ENV_FILE" ]; then
    # Check baseURL
    BASE_URL=$(grep "^app.baseURL" "$ENV_FILE" | cut -d= -f2 | tr -d "' ")
    echo "   baseURL: $BASE_URL"
    
    # Check session driver
    SESSION_DRIVER=$(grep "^session.driver" "$ENV_FILE" | cut -d= -f2 | tr -d "' ")
    echo "   Session driver: $SESSION_DRIVER"
    
    # Check session savePath
    SESSION_PATH=$(grep "^session.savePath" "$ENV_FILE" | cut -d= -f2 | tr -d "' ")
    echo "   Session savePath: $SESSION_PATH"
    
    # Check environment
    CI_ENV=$(grep "^CI_ENVIRONMENT" "$ENV_FILE" | cut -d= -f2 | tr -d ' ')
    echo "   CI_ENVIRONMENT: $CI_ENV"
    
    if [ "$CI_ENV" = "production" ]; then
        echo -e "   ${YELLOW}⚠️  Running in production mode - errors are hidden${NC}"
        echo -e "   ${YELLOW}   Tip: Temporarily set CI_ENVIRONMENT = development to see errors${NC}"
    fi
else
    echo -e "   ${RED}❌ .env file not found at $ENV_FILE${NC}"
fi
echo ""

# ─── Summary ──────────────────────────────────────────────────
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}  QUICK FIX COMMANDS${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "# 1. Restart Redis (if not running):"
echo "   systemctl restart redis"
echo ""
echo "# 2. Fix permissions:"
echo "   chmod -R 777 $APP_PATH/writable"
echo ""
echo "# 3. Clear cache:"
echo "   rm -rf $APP_PATH/writable/cache/*"
echo "   redis-cli FLUSHALL"
echo ""
echo "# 4. Restart PHP-FPM:"
echo "   /etc/init.d/php-fpm-82 restart"
echo ""
echo "# 5. View live logs:"
echo "   tail -f $APP_PATH/writable/logs/log-${LOG_DATE}.php"
echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
