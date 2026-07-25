#!/bin/bash

# ═══════════════════════════════════════════════════════════════
#  Smart Install PHP Intl Extension for aaPanel PHP 8.2
#  Metode: Copy dari system PHP (tanpa compile manual)
# ═══════════════════════════════════════════════════════════════

PHP_VERSION="82"
PHP_BIN="/www/server/php/${PHP_VERSION}/bin/php"
PHP_INI="/www/server/php/${PHP_VERSION}/etc/php.ini"
PHP_EXT_DIR=$($PHP_BIN -r "echo ini_get('extension_dir');" 2>/dev/null)

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

echo ""
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${CYAN}  Smart Install PHP Intl Extension - aaPanel PHP 8.2${NC}"
echo -e "${CYAN}═══════════════════════════════════════════════════════════${NC}"
echo ""

# ─── Check root ───────────────────────────────────────────────
if [ "$EUID" -ne 0 ]; then
    echo -e "${RED}❌ Harus dijalankan sebagai root!${NC}"
    echo "   Gunakan: sudo bash scripts/install_intl_smart.sh"
    exit 1
fi

# ─── Check aaPanel PHP 8.2 ────────────────────────────────────
if [ ! -f "$PHP_BIN" ]; then
    echo -e "${RED}❌ aaPanel PHP 8.2 tidak ditemukan di: $PHP_BIN${NC}"
    exit 1
fi

echo -e "${GREEN}✅ aaPanel PHP 8.2 ditemukan${NC}"
echo "   Binary: $PHP_BIN"
echo "   Extension dir: $PHP_EXT_DIR"
echo ""

# ─── Check apakah intl sudah terinstall ───────────────────────
if $PHP_BIN -m 2>/dev/null | grep -q "^intl$"; then
    echo -e "${GREEN}✅ intl extension sudah terinstall!${NC}"
    echo ""
    echo "Test command:"
    echo "  cd /www/wwwroot/cbtsmpistora.sch.id"
    echo "  $PHP_BIN spark test:performance"
    exit 0
fi

echo -e "${YELLOW}⚠️  intl extension belum terinstall. Mencoba install...${NC}"
echo ""

# ═══════════════════════════════════════════════════════════════
#  METODE 1: aaPanel Python CLI Tool
# ═══════════════════════════════════════════════════════════════
echo -e "${CYAN}[METODE 1] Mencoba via aaPanel Python CLI...${NC}"

AAPANEL_PYTHON="/www/server/panel/pyenv/bin/python3"
AAPANEL_PHP_PLUGIN="/www/server/panel/plugin/php/index.py"

if [ -f "$AAPANEL_PYTHON" ] && [ -f "$AAPANEL_PHP_PLUGIN" ]; then
    echo "   Menjalankan aaPanel install_ext..."
    $AAPANEL_PYTHON $AAPANEL_PHP_PLUGIN install_ext $PHP_VERSION intl 2>&1
    
    sleep 3
    
    if $PHP_BIN -m 2>/dev/null | grep -q "^intl$"; then
        echo -e "${GREEN}✅ BERHASIL via aaPanel CLI!${NC}"
        /etc/init.d/php-fpm-${PHP_VERSION} restart 2>/dev/null
        echo ""
        echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
        echo -e "${GREEN}✅ intl extension berhasil diinstall!${NC}"
        echo ""
        echo "Test dengan:"
        echo "  cd /www/wwwroot/cbtsmpistora.sch.id"
        echo "  $PHP_BIN spark test:performance"
        echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
        exit 0
    fi
    echo -e "${YELLOW}   Metode 1 tidak berhasil, mencoba metode lain...${NC}"
else
    echo -e "${YELLOW}   aaPanel Python CLI tidak ditemukan, skip...${NC}"
fi

echo ""

# ═══════════════════════════════════════════════════════════════
#  METODE 2: Copy dari system PHP (apt-get)
# ═══════════════════════════════════════════════════════════════
echo -e "${CYAN}[METODE 2] Install via apt-get dan copy .so file...${NC}"

# Detect OS
if command -v apt-get &>/dev/null; then
    PKG_MANAGER="apt-get"
    PHP_PKG="php8.2-intl"
    SYSTEM_EXT_DIRS=(
        "/usr/lib/php/20220829"
        "/usr/lib/php/20210902"
        "/usr/lib/php/20200930"
    )
elif command -v yum &>/dev/null; then
    PKG_MANAGER="yum"
    PHP_PKG="php82-php-intl"
    SYSTEM_EXT_DIRS=(
        "/usr/lib64/php/modules"
        "/usr/lib/php/modules"
    )
else
    echo -e "${YELLOW}   Package manager tidak dikenali, skip metode 2...${NC}"
    PKG_MANAGER=""
fi

if [ -n "$PKG_MANAGER" ]; then
    echo "   Menambahkan PHP repository..."
    
    if [ "$PKG_MANAGER" = "apt-get" ]; then
        # Add ondrej/php repository
        apt-get install -y software-properties-common 2>/dev/null
        add-apt-repository -y ppa:ondrej/php 2>/dev/null || true
        apt-get update -qq 2>/dev/null
        
        echo "   Menginstall $PHP_PKG..."
        apt-get install -y $PHP_PKG 2>&1 | tail -5
        
        # Cari intl.so dari system PHP
        INTL_SO=""
        for dir in "${SYSTEM_EXT_DIRS[@]}"; do
            if [ -f "$dir/intl.so" ]; then
                INTL_SO="$dir/intl.so"
                break
            fi
        done
        
        # Juga cari di semua kemungkinan path
        if [ -z "$INTL_SO" ]; then
            INTL_SO=$(find /usr/lib/php -name "intl.so" 2>/dev/null | head -1)
        fi
        
    elif [ "$PKG_MANAGER" = "yum" ]; then
        yum install -y epel-release 2>/dev/null
        yum install -y $PHP_PKG 2>&1 | tail -5
        
        INTL_SO=$(find /usr/lib64/php /usr/lib/php -name "intl.so" 2>/dev/null | head -1)
    fi
    
    if [ -n "$INTL_SO" ] && [ -f "$INTL_SO" ]; then
        echo "   Ditemukan intl.so di: $INTL_SO"
        
        # Check compatibility (versi PHP harus sama)
        SYSTEM_PHP_VER=$(php8.2 -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;" 2>/dev/null || echo "")
        AAPANEL_PHP_VER=$($PHP_BIN -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;" 2>/dev/null)
        
        echo "   System PHP version: $SYSTEM_PHP_VER"
        echo "   aaPanel PHP version: $AAPANEL_PHP_VER"
        
        if [ "$SYSTEM_PHP_VER" = "$AAPANEL_PHP_VER" ] || [ -z "$SYSTEM_PHP_VER" ]; then
            echo "   Menyalin intl.so ke aaPanel extension dir..."
            cp "$INTL_SO" "$PHP_EXT_DIR/intl.so"
            chmod 755 "$PHP_EXT_DIR/intl.so"
            
            # Enable extension
            if ! grep -q "extension=intl" "$PHP_INI"; then
                echo "extension=intl.so" >> "$PHP_INI"
                echo "   Menambahkan extension=intl.so ke php.ini"
            fi
            
            # Restart PHP-FPM
            /etc/init.d/php-fpm-${PHP_VERSION} restart 2>/dev/null
            sleep 2
            
            if $PHP_BIN -m 2>/dev/null | grep -q "^intl$"; then
                echo -e "${GREEN}✅ BERHASIL via copy dari system PHP!${NC}"
                echo ""
                echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
                echo -e "${GREEN}✅ intl extension berhasil diinstall!${NC}"
                echo ""
                echo "Test dengan:"
                echo "  cd /www/wwwroot/cbtsmpistora.sch.id"
                echo "  $PHP_BIN spark test:performance"
                echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
                exit 0
            else
                echo -e "${YELLOW}   Copy berhasil tapi extension belum terdeteksi${NC}"
                echo "   Cek: $PHP_BIN -m | grep intl"
            fi
        else
            echo -e "${YELLOW}   ⚠️  Versi PHP berbeda, tidak aman untuk copy .so${NC}"
            echo "   System: $SYSTEM_PHP_VER vs aaPanel: $AAPANEL_PHP_VER"
        fi
    else
        echo -e "${YELLOW}   intl.so tidak ditemukan setelah install${NC}"
    fi
fi

echo ""

# ═══════════════════════════════════════════════════════════════
#  METODE 3: Compile dengan C++14 flag (fix dari error sebelumnya)
# ═══════════════════════════════════════════════════════════════
echo -e "${CYAN}[METODE 3] Compile dengan C++14 flag (fix ICU 76 error)...${NC}"

# Install dependencies
echo "   Menginstall build dependencies..."
if command -v apt-get &>/dev/null; then
    apt-get install -y libicu-dev pkg-config build-essential autoconf 2>&1 | tail -3
elif command -v yum &>/dev/null; then
    yum install -y libicu-devel pkgconfig gcc-c++ autoconf 2>&1 | tail -3
fi

# Check ICU version
ICU_VERSION=$(pkg-config --modversion icu-i18n 2>/dev/null || echo "unknown")
echo "   ICU Version: $ICU_VERSION"

# Get aaPanel PHP version string
PHP_FULL_VER=$($PHP_BIN -r "echo PHP_VERSION;" 2>/dev/null)
echo "   PHP Version: $PHP_FULL_VER"

# Download matching PHP source
PHP_MAJOR_MINOR=$(echo $PHP_FULL_VER | cut -d. -f1,2)
PHP_PATCH=$(echo $PHP_FULL_VER | cut -d. -f3 | cut -d- -f1)
PHP_SOURCE_VER="${PHP_MAJOR_MINOR}.${PHP_PATCH}"

echo "   Mendownload PHP source $PHP_SOURCE_VER..."
cd /tmp

# Try to download exact version
if ! wget -q --timeout=60 "https://www.php.net/distributions/php-${PHP_SOURCE_VER}.tar.gz" -O "php-${PHP_SOURCE_VER}.tar.gz"; then
    # Fallback to common version
    echo "   Versi exact tidak ada, mencoba 8.2.12..."
    wget -q --timeout=60 "https://www.php.net/distributions/php-8.2.12.tar.gz" -O "php-8.2.12.tar.gz"
    PHP_SOURCE_VER="8.2.12"
fi

if [ ! -f "php-${PHP_SOURCE_VER}.tar.gz" ]; then
    echo -e "${RED}   ❌ Gagal download PHP source${NC}"
    echo ""
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
    echo -e "${YELLOW}⚠️  Semua metode otomatis gagal.${NC}"
    echo ""
    echo "SOLUSI MANUAL:"
    echo "1. Login ke aaPanel: https://your-server-ip:7800"
    echo "2. App Store → PHP 8.2 → Settings (⚙️)"
    echo "3. Tab 'Install Extensions' → Cari 'intl' → Install"
    echo ""
    echo "Atau downgrade ke PHP 8.1 yang lebih stabil."
    echo -e "${YELLOW}═══════════════════════════════════════════════════════════${NC}"
    exit 1
fi

echo "   Mengekstrak source..."
tar -xzf "php-${PHP_SOURCE_VER}.tar.gz" 2>/dev/null
cd "php-${PHP_SOURCE_VER}/ext/intl"

echo "   Menjalankan phpize..."
$PHP_BIN/../phpize 2>&1 | tail -3

echo "   Menjalankan configure..."
./configure --with-php-config=$PHP_BIN/../php-config 2>&1 | tail -5

echo "   Compiling dengan C++14 flag (fix untuk ICU 76)..."
# Tambahkan CXXFLAGS untuk C++14 - ini fix untuk error sebelumnya
make CXXFLAGS="-std=c++14" 2>&1 | tail -10

if [ $? -eq 0 ]; then
    echo "   Menginstall extension..."
    make install 2>&1 | tail -3
    
    # Enable extension
    if ! grep -q "extension=intl" "$PHP_INI"; then
        echo "extension=intl.so" >> "$PHP_INI"
    fi
    
    # Restart PHP-FPM
    /etc/init.d/php-fpm-${PHP_VERSION} restart 2>/dev/null
    sleep 2
    
    if $PHP_BIN -m 2>/dev/null | grep -q "^intl$"; then
        echo ""
        echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
        echo -e "${GREEN}✅ BERHASIL! intl extension berhasil diinstall via compile!${NC}"
        echo ""
        echo "Test dengan:"
        echo "  cd /www/wwwroot/cbtsmpistora.sch.id"
        echo "  $PHP_BIN spark test:performance"
        echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
        
        # Cleanup
        cd /tmp
        rm -rf "php-${PHP_SOURCE_VER}" "php-${PHP_SOURCE_VER}.tar.gz"
        exit 0
    else
        echo -e "${RED}   ❌ Compile berhasil tapi extension tidak terdeteksi${NC}"
    fi
else
    echo -e "${RED}   ❌ Compile gagal${NC}"
fi

# Cleanup
cd /tmp
rm -rf "php-${PHP_SOURCE_VER}" "php-${PHP_SOURCE_VER}.tar.gz" 2>/dev/null

echo ""
echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
echo -e "${RED}❌ Semua metode otomatis gagal.${NC}"
echo ""
echo -e "${YELLOW}SOLUSI YANG HARUS DICOBA MANUAL:${NC}"
echo ""
echo "1. Via aaPanel GUI (PALING MUDAH):"
echo "   - Login: https://your-server-ip:7800"
echo "   - App Store → PHP 8.2 → Settings (⚙️)"
echo "   - Tab 'Install Extensions' → intl → Install"
echo ""
echo "2. Downgrade ke PHP 8.1 (PALING STABIL):"
echo "   - App Store → Install PHP 8.1"
echo "   - Website → cbtsmpistora.sch.id → Settings → PHP 8.1"
echo "   - Install intl untuk PHP 8.1"
echo ""
echo "3. Cek log error aaPanel:"
echo "   tail -50 /www/server/panel/logs/error.log"
echo -e "${RED}═══════════════════════════════════════════════════════════${NC}"
exit 1
