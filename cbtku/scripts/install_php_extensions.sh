#!/bin/bash

###############################################################################
# PHP Extensions Installer for CodeIgniter 4
# 
# This script installs all required PHP extensions for CBT application
# 
# Usage: sudo bash install_php_extensions.sh
###############################################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  PHP Extensions Installer for CodeIgniter 4${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Please run as root: sudo bash install_php_extensions.sh${NC}"
    exit 1
fi

# Detect PHP version
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;" 2>/dev/null)

if [ -z "$PHP_VERSION" ]; then
    echo -e "${RED}❌ PHP not found! Please install PHP first.${NC}"
    exit 1
fi

echo -e "${GREEN}✅ Detected PHP version: $PHP_VERSION${NC}"
echo ""

# Detect OS
if [ -f /etc/os-release ]; then
    . /etc/os-release
    OS=$ID
    OS_VERSION=$VERSION_ID
    echo -e "${GREEN}✅ Detected OS: $OS $OS_VERSION${NC}"
else
    echo -e "${RED}❌ Cannot detect OS${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}Installing PHP extensions...${NC}"
echo ""

# Install based on OS
case $OS in
    ubuntu|debian)
        echo "Updating package list..."
        apt-get update -qq
        
        echo "Installing PHP extensions..."
        apt-get install -y \
            php${PHP_VERSION}-intl \
            php${PHP_VERSION}-mbstring \
            php${PHP_VERSION}-xml \
            php${PHP_VERSION}-curl \
            php${PHP_VERSION}-zip \
            php${PHP_VERSION}-gd \
            php${PHP_VERSION}-mysql \
            php${PHP_VERSION}-redis \
            php${PHP_VERSION}-opcache
        ;;
    
    centos|rhel|almalinux|rocky)
        echo "Installing PHP extensions..."
        yum install -y \
            php-intl \
            php-mbstring \
            php-xml \
            php-curl \
            php-zip \
            php-gd \
            php-mysqlnd \
            php-redis \
            php-opcache
        ;;
    
    *)
        echo -e "${RED}❌ Unsupported OS: $OS${NC}"
        echo "Please install extensions manually:"
        echo "  - php-intl"
        echo "  - php-mbstring"
        echo "  - php-xml"
        echo "  - php-curl"
        echo "  - php-zip"
        echo "  - php-gd"
        echo "  - php-mysql"
        echo "  - php-redis"
        echo "  - php-opcache"
        exit 1
        ;;
esac

echo ""
echo -e "${YELLOW}Restarting services...${NC}"

# Restart PHP-FPM
if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    systemctl restart php${PHP_VERSION}-fpm
    echo -e "${GREEN}✅ PHP-FPM restarted${NC}"
elif systemctl is-active --quiet php-fpm; then
    systemctl restart php-fpm
    echo -e "${GREEN}✅ PHP-FPM restarted${NC}"
fi

# Restart web server
if systemctl is-active --quiet nginx; then
    systemctl restart nginx
    echo -e "${GREEN}✅ Nginx restarted${NC}"
elif systemctl is-active --quiet apache2; then
    systemctl restart apache2
    echo -e "${GREEN}✅ Apache restarted${NC}"
fi

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Verifying Installation${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Verify extensions
EXTENSIONS=("intl" "mbstring" "xml" "curl" "zip" "gd" "mysqli" "redis" "Zend OPcache")
ALL_OK=true

for ext in "${EXTENSIONS[@]}"; do
    if php -m | grep -q "^${ext}$"; then
        echo -e "${GREEN}✅ ${ext}${NC}"
    else
        echo -e "${RED}❌ ${ext} - NOT INSTALLED${NC}"
        ALL_OK=false
    fi
done

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"

if [ "$ALL_OK" = true ]; then
    echo -e "${GREEN}🎉 All extensions installed successfully!${NC}"
    echo ""
    echo -e "${YELLOW}Next steps:${NC}"
    echo "1. Test commands:"
    echo "   cd /www/wwwroot/cbtsmpistora.sch.id"
    echo "   php spark test:performance"
    echo "   php spark monitor:sessions"
    echo "   php spark redis:check"
    echo ""
    echo "2. Check web application:"
    echo "   https://cbtsmpistora.sch.id"
else
    echo -e "${RED}⚠️  Some extensions failed to install!${NC}"
    echo ""
    echo "Please install manually or check error messages above."
fi

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
