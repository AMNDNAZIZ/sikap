#!/bin/bash

###############################################################################
# Fix PHP Intl Extension for aaPanel PHP 8.2
# 
# This script installs intl extension for aaPanel PHP installation
# 
# Usage: sudo bash fix_intl_aapanel.sh
###############################################################################

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Fix PHP Intl Extension for aaPanel PHP 8.2${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Please run as root: sudo bash fix_intl_aapanel.sh${NC}"
    exit 1
fi

# Check if aaPanel is installed
if [ ! -d "/www/server/php/82" ]; then
    echo -e "${RED}❌ aaPanel PHP 8.2 not found!${NC}"
    echo ""
    echo "Please install PHP 8.2 via aaPanel first:"
    echo "1. Login to aaPanel web interface"
    echo "2. Go to App Store"
    echo "3. Search for 'PHP 8.2'"
    echo "4. Click Install"
    echo ""
    exit 1
fi

echo -e "${GREEN}✅ aaPanel PHP 8.2 detected${NC}"
echo ""

# Check if intl already installed
if /www/server/php/82/bin/php -m | grep -q "intl"; then
    echo -e "${GREEN}✅ intl extension already installed!${NC}"
    echo ""
    echo "Test with:"
    echo "  cd /www/wwwroot/cbtsmpistora.sch.id"
    echo "  /www/server/php/82/bin/php spark test:performance"
    exit 0
fi

echo -e "${YELLOW}⚠️  intl extension not found. Installing...${NC}"
echo ""

# Install dependencies
echo -e "${YELLOW}Step 1/5: Installing dependencies...${NC}"
apt-get update -qq
apt-get install -y libicu-dev pkg-config build-essential autoconf

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to install dependencies${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Dependencies installed${NC}"
echo ""

# Download PHP source
echo -e "${YELLOW}Step 2/5: Downloading PHP 8.2 source...${NC}"
cd /tmp
rm -rf php-8.2*

# Try multiple PHP versions
PHP_VERSIONS=("8.2.12" "8.2.11" "8.2.10" "8.2.9")
DOWNLOADED=false

for VERSION in "${PHP_VERSIONS[@]}"; do
    echo "Trying PHP ${VERSION}..."
    if wget -q https://www.php.net/distributions/php-${VERSION}.tar.gz; then
        tar -xzf php-${VERSION}.tar.gz
        DOWNLOADED=true
        PHP_SOURCE_DIR="php-${VERSION}"
        break
    fi
done

if [ "$DOWNLOADED" = false ]; then
    echo -e "${RED}❌ Failed to download PHP source${NC}"
    echo "Please download manually from https://www.php.net/downloads"
    exit 1
fi

echo -e "${GREEN}✅ PHP source downloaded${NC}"
echo ""

# Compile intl extension
echo -e "${YELLOW}Step 3/5: Compiling intl extension...${NC}"
cd /tmp/${PHP_SOURCE_DIR}/ext/intl

/www/server/php/82/bin/phpize

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ phpize failed${NC}"
    exit 1
fi

./configure --with-php-config=/www/server/php/82/bin/php-config

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ configure failed${NC}"
    echo ""
    echo "Check error log:"
    echo "  cat /tmp/${PHP_SOURCE_DIR}/ext/intl/config.log"
    exit 1
fi

make

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ make failed${NC}"
    exit 1
fi

make install

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ make install failed${NC}"
    exit 1
fi

echo -e "${GREEN}✅ intl extension compiled${NC}"
echo ""

# Enable extension
echo -e "${YELLOW}Step 4/5: Enabling extension...${NC}"

# Check if already in php.ini
if ! grep -q "extension=intl.so" /www/server/php/82/etc/php.ini; then
    echo "extension=intl.so" >> /www/server/php/82/etc/php.ini
    echo -e "${GREEN}✅ Extension added to php.ini${NC}"
else
    echo -e "${GREEN}✅ Extension already in php.ini${NC}"
fi
echo ""

# Restart PHP-FPM
echo -e "${YELLOW}Step 5/5: Restarting PHP-FPM...${NC}"
/etc/init.d/php-fpm-82 restart

if [ $? -ne 0 ]; then
    echo -e "${RED}❌ Failed to restart PHP-FPM${NC}"
    exit 1
fi

sleep 2
echo -e "${GREEN}✅ PHP-FPM restarted${NC}"
echo ""

# Verify installation
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  Verifying Installation${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

if /www/server/php/82/bin/php -m | grep -q "intl"; then
    echo -e "${GREEN}✅ intl extension installed successfully!${NC}"
    
    # Show intl info
    echo ""
    echo "Extension details:"
    /www/server/php/82/bin/php -r "echo 'ICU Version: ' . INTL_ICU_VERSION . PHP_EOL;"
    /www/server/php/82/bin/php -r "echo 'ICU Data Version: ' . INTL_ICU_DATA_VERSION . PHP_EOL;"
else
    echo -e "${RED}❌ intl extension installation failed!${NC}"
    echo ""
    echo "Troubleshooting:"
    echo "1. Check if extension file exists:"
    echo "   ls -la /www/server/php/82/lib/php/extensions/*/intl.so"
    echo ""
    echo "2. Check PHP error log:"
    echo "   tail -f /www/server/php/82/var/log/php-fpm.log"
    echo ""
    echo "3. Try manual installation via aaPanel GUI:"
    echo "   App Store → PHP 8.2 → Settings → Install Extensions → intl"
    exit 1
fi

echo ""
echo -e "${YELLOW}Cleaning up...${NC}"
cd /tmp
rm -rf php-8.2*
echo -e "${GREEN}✅ Cleanup complete${NC}"

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}🎉 Installation Complete!${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo "Next steps:"
echo ""
echo "1. Test commands:"
echo "   cd /www/wwwroot/cbtsmpistora.sch.id"
echo "   /www/server/php/82/bin/php spark test:performance"
echo "   /www/server/php/82/bin/php spark monitor:sessions"
echo "   /www/server/php/82/bin/php spark redis:check"
echo ""
echo "2. Create alias (optional):"
echo "   echo 'alias php82=\"/www/server/php/82/bin/php\"' >> ~/.bashrc"
echo "   echo 'alias spark82=\"cd /www/wwwroot/cbtsmpistora.sch.id && /www/server/php/82/bin/php spark\"' >> ~/.bashrc"
echo "   source ~/.bashrc"
echo ""
echo "3. Install other extensions via aaPanel GUI:"
echo "   - redis (IMPORTANT!)"
echo "   - opcache (IMPORTANT!)"
echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
