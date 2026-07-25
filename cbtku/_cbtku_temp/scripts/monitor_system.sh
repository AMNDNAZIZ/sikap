#!/bin/bash

###############################################################################
# System Monitor Script for CBT Application
# 
# This script monitors:
# - Redis sessions
# - Database connections
# - System resources (CPU, Memory, Disk)
# - Application logs
#
# Usage: ./scripts/monitor_system.sh
# Cron: */5 * * * * /path/to/app/scripts/monitor_system.sh >> /var/log/cbt_monitor.log 2>&1
###############################################################################

# Configuration
APP_PATH="/path/to/your/app"  # CHANGE THIS!
LOG_FILE="/var/log/cbt_monitor.log"
ALERT_EMAIL="admin@example.com"  # CHANGE THIS!
SESSION_THRESHOLD=45
CPU_THRESHOLD=80
MEMORY_THRESHOLD=80
DISK_THRESHOLD=85

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Timestamp
TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')

echo "═══════════════════════════════════════════════════════════"
echo "CBT System Monitor - $TIMESTAMP"
echo "═══════════════════════════════════════════════════════════"
echo ""

###############################################################################
# 1. CHECK REDIS SESSIONS
###############################################################################
echo "1️⃣  Checking Redis Sessions..."

if command -v redis-cli &> /dev/null; then
    # Check if Redis is running
    if redis-cli ping &> /dev/null; then
        # Count sessions
        SESSION_COUNT=$(redis-cli KEYS "ci_session:*" | wc -l)
        echo "   Active Sessions: $SESSION_COUNT"
        
        # Check threshold
        if [ "$SESSION_COUNT" -gt "$SESSION_THRESHOLD" ]; then
            PERCENTAGE=$(echo "scale=1; ($SESSION_COUNT / $SESSION_THRESHOLD) * 100" | bc)
            echo -e "   ${RED}⚠️  WARNING: Session count ($SESSION_COUNT) exceeded threshold ($SESSION_THRESHOLD) - ${PERCENTAGE}%${NC}"
            
            # Send alert
            echo "Session Alert: $SESSION_COUNT sessions (threshold: $SESSION_THRESHOLD)" | \
            mail -s "⚠️ CBT Session Alert" "$ALERT_EMAIL"
        else
            PERCENTAGE=$(echo "scale=1; ($SESSION_COUNT / $SESSION_THRESHOLD) * 100" | bc)
            echo -e "   ${GREEN}✅ Session count within limit - ${PERCENTAGE}% of threshold${NC}"
        fi
        
        # Redis memory
        REDIS_MEMORY=$(redis-cli INFO memory | grep used_memory_human | cut -d: -f2 | tr -d '\r')
        echo "   Redis Memory: $REDIS_MEMORY"
        
        # Sessions expiring soon
        EXPIRING_COUNT=0
        for key in $(redis-cli KEYS "ci_session:*"); do
            TTL=$(redis-cli TTL "$key")
            if [ "$TTL" -gt 0 ] && [ "$TTL" -lt 300 ]; then
                EXPIRING_COUNT=$((EXPIRING_COUNT + 1))
            fi
        done
        
        if [ "$EXPIRING_COUNT" -gt 0 ]; then
            echo -e "   ${YELLOW}⚠️  Sessions expiring soon (<5 min): $EXPIRING_COUNT${NC}"
        fi
    else
        echo -e "   ${RED}❌ Redis is not responding${NC}"
        echo "Redis Down Alert" | mail -s "🚨 CBT Redis Down" "$ALERT_EMAIL"
    fi
else
    echo -e "   ${YELLOW}⚠️  redis-cli not found${NC}"
fi

echo ""

###############################################################################
# 2. CHECK DATABASE CONNECTIONS
###############################################################################
echo "2️⃣  Checking Database Connections..."

if command -v mysql &> /dev/null; then
    # Get DB credentials from .env
    if [ -f "$APP_PATH/.env" ]; then
        DB_HOST=$(grep "database.default.hostname" "$APP_PATH/.env" | cut -d= -f2 | tr -d ' ')
        DB_USER=$(grep "database.default.username" "$APP_PATH/.env" | cut -d= -f2 | tr -d ' ')
        DB_PASS=$(grep "database.default.password" "$APP_PATH/.env" | cut -d= -f2 | tr -d ' ')
        DB_NAME=$(grep "database.default.database" "$APP_PATH/.env" | cut -d= -f2 | tr -d ' ')
        
        # Check connections
        CONNECTIONS=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SHOW STATUS LIKE 'Threads_connected';" | grep Threads_connected | awk '{print $2}')
        MAX_CONNECTIONS=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SHOW VARIABLES LIKE 'max_connections';" | grep max_connections | awk '{print $2}')
        
        if [ -n "$CONNECTIONS" ] && [ -n "$MAX_CONNECTIONS" ]; then
            CONN_PERCENTAGE=$(echo "scale=1; ($CONNECTIONS / $MAX_CONNECTIONS) * 100" | bc)
            echo "   Active Connections: $CONNECTIONS / $MAX_CONNECTIONS (${CONN_PERCENTAGE}%)"
            
            if [ "$CONNECTIONS" -gt 140 ]; then
                echo -e "   ${RED}⚠️  WARNING: Database connection pool almost full!${NC}"
                echo "Database Connection Alert: $CONNECTIONS / $MAX_CONNECTIONS" | \
                mail -s "⚠️ CBT Database Alert" "$ALERT_EMAIL"
            else
                echo -e "   ${GREEN}✅ Database connections healthy${NC}"
            fi
            
            # Check slow queries
            SLOW_QUERIES=$(mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SHOW STATUS LIKE 'Slow_queries';" | grep Slow_queries | awk '{print $2}')
            echo "   Slow Queries: $SLOW_QUERIES"
        fi
    else
        echo -e "   ${YELLOW}⚠️  .env file not found${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠️  mysql client not found${NC}"
fi

echo ""

###############################################################################
# 3. CHECK SYSTEM RESOURCES
###############################################################################
echo "3️⃣  Checking System Resources..."

# CPU Usage
CPU_USAGE=$(top -bn1 | grep "Cpu(s)" | sed "s/.*, *\([0-9.]*\)%* id.*/\1/" | awk '{print 100 - $1}')
CPU_USAGE_INT=$(printf "%.0f" "$CPU_USAGE")
echo "   CPU Usage: ${CPU_USAGE}%"

if [ "$CPU_USAGE_INT" -gt "$CPU_THRESHOLD" ]; then
    echo -e "   ${RED}⚠️  WARNING: High CPU usage!${NC}"
else
    echo -e "   ${GREEN}✅ CPU usage normal${NC}"
fi

# Memory Usage
MEMORY_USAGE=$(free | grep Mem | awk '{print ($3/$2) * 100.0}')
MEMORY_USAGE_INT=$(printf "%.0f" "$MEMORY_USAGE")
echo "   Memory Usage: ${MEMORY_USAGE}%"

if [ "$MEMORY_USAGE_INT" -gt "$MEMORY_THRESHOLD" ]; then
    echo -e "   ${RED}⚠️  WARNING: High memory usage!${NC}"
else
    echo -e "   ${GREEN}✅ Memory usage normal${NC}"
fi

# Disk Usage
DISK_USAGE=$(df -h / | tail -1 | awk '{print $5}' | sed 's/%//')
echo "   Disk Usage: ${DISK_USAGE}%"

if [ "$DISK_USAGE" -gt "$DISK_THRESHOLD" ]; then
    echo -e "   ${RED}⚠️  WARNING: High disk usage!${NC}"
    echo "Disk Usage Alert: ${DISK_USAGE}%" | mail -s "⚠️ CBT Disk Alert" "$ALERT_EMAIL"
else
    echo -e "   ${GREEN}✅ Disk usage normal${NC}"
fi

# Load Average
LOAD_AVG=$(uptime | awk -F'load average:' '{print $2}')
echo "   Load Average:$LOAD_AVG"

echo ""

###############################################################################
# 4. CHECK APPLICATION LOGS
###############################################################################
echo "4️⃣  Checking Application Logs..."

if [ -d "$APP_PATH/writable/logs" ]; then
    # Find latest log file
    LATEST_LOG=$(ls -t "$APP_PATH/writable/logs"/log-*.php 2>/dev/null | head -1)
    
    if [ -n "$LATEST_LOG" ]; then
        # Count errors in last 5 minutes
        ERROR_COUNT=$(grep -c "ERROR" "$LATEST_LOG" 2>/dev/null || echo "0")
        CRITICAL_COUNT=$(grep -c "CRITICAL" "$LATEST_LOG" 2>/dev/null || echo "0")
        
        echo "   Recent Errors: $ERROR_COUNT"
        echo "   Critical Errors: $CRITICAL_COUNT"
        
        if [ "$CRITICAL_COUNT" -gt 0 ]; then
            echo -e "   ${RED}⚠️  WARNING: Critical errors found!${NC}"
            
            # Show last 5 critical errors
            echo "   Last critical errors:"
            grep "CRITICAL" "$LATEST_LOG" | tail -5 | while read line; do
                echo "      $line"
            done
            
            # Send alert
            CRITICAL_ERRORS=$(grep "CRITICAL" "$LATEST_LOG" | tail -5)
            echo "Critical Errors Found:\n$CRITICAL_ERRORS" | \
            mail -s "🚨 CBT Critical Error Alert" "$ALERT_EMAIL"
        elif [ "$ERROR_COUNT" -gt 10 ]; then
            echo -e "   ${YELLOW}⚠️  WARNING: High error count!${NC}"
        else
            echo -e "   ${GREEN}✅ No critical errors${NC}"
        fi
    else
        echo -e "   ${YELLOW}⚠️  No log files found${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠️  Log directory not found${NC}"
fi

echo ""

###############################################################################
# 5. CHECK WEB SERVICES
###############################################################################
echo "5️⃣  Checking Web Services..."

# Check PHP-FPM
if systemctl is-active --quiet php8.1-fpm; then
    echo -e "   ${GREEN}✅ PHP-FPM is running${NC}"
else
    echo -e "   ${RED}❌ PHP-FPM is not running${NC}"
    echo "PHP-FPM Down Alert" | mail -s "🚨 CBT PHP-FPM Down" "$ALERT_EMAIL"
fi

# Check Nginx
if systemctl is-active --quiet nginx; then
    echo -e "   ${GREEN}✅ Nginx is running${NC}"
else
    echo -e "   ${RED}❌ Nginx is not running${NC}"
    echo "Nginx Down Alert" | mail -s "🚨 CBT Nginx Down" "$ALERT_EMAIL"
fi

# Check Redis
if systemctl is-active --quiet redis; then
    echo -e "   ${GREEN}✅ Redis is running${NC}"
else
    echo -e "   ${RED}❌ Redis is not running${NC}"
    echo "Redis Down Alert" | mail -s "🚨 CBT Redis Down" "$ALERT_EMAIL"
fi

# Check MySQL
if systemctl is-active --quiet mysql || systemctl is-active --quiet mariadb; then
    echo -e "   ${GREEN}✅ MySQL/MariaDB is running${NC}"
else
    echo -e "   ${RED}❌ MySQL/MariaDB is not running${NC}"
    echo "MySQL Down Alert" | mail -s "🚨 CBT MySQL Down" "$ALERT_EMAIL"
fi

echo ""

###############################################################################
# 6. SUMMARY
###############################################################################
echo "═══════════════════════════════════════════════════════════"
echo "Summary:"
echo "  • Sessions: $SESSION_COUNT / $SESSION_THRESHOLD"
echo "  • CPU: ${CPU_USAGE}%"
echo "  • Memory: ${MEMORY_USAGE}%"
echo "  • Disk: ${DISK_USAGE}%"
echo "═══════════════════════════════════════════════════════════"
echo ""
