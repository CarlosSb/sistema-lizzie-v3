#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

echo -e "${BLUE}=== Atualizador de Ambiente Lizzie ===${NC}\n"

# Check current PHP version
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")

echo -e "${BLUE}PHP atual:${NC} $PHP_VERSION"

if [ "$PHP_MAJOR" -ge 8 ] && [ "$(php -r "echo PHP_MINOR_VERSION;")" -ge 4 ]; then
    echo -e "${YELLOW}⚠ PHP 8.4+ detectado${NC}"
    echo -e "${YELLOW}Lumen 10 funciona melhor com PHP 8.1-8.2${NC}"
    echo ""
    echo "Opções:"
    echo "  1. Atualizar Composer e tentar mesmo assim"
    echo "  2. Instalar PHP 8.2"
    echo "  3. Sair"
    read -p "Escolha [1-3]: " choice
    
    case $choice in
        2) 
            echo -e "${CYAN}Instalando PHP 8.2...${NC}"
            echo "Execute:"
            echo "  sudo add-apt-repository ppa:ondrej/php"
            echo "  sudo apt install php8.2 php8.2-mysql php8.2-cli php8.2-xml php8.2-mbstring"
            exit 0
            ;;
        3) exit 0 ;;
    esac
fi

# Update Composer
echo -e "\n${BLUE}Verificando Composer...${NC}"
COMPOSER_VERSION=$(composer --version 2>/dev/null | grep -oP '\d+\.\d+' | head -1)
echo -e "Composer atual: $COMPOSER_VERSION"

COMPOSER_MAJOR=$(echo $COMPOSER_VERSION | cut -d. -f1)

if [ "$COMPOSER_MAJOR" -lt 2 ]; then
    echo -e "${YELLOW}Atualizando Composer...${NC}"
    
    # Try self-update first
    if sudo composer self-update --stable 2>/dev/null; then
        echo -e "${GREEN}✓ Composer atualizado${NC}"
    else
        echo -e "${YELLOW}Tentando método alternativo...${NC}"
        
        # Download fresh composer
        cd /tmp
        curl -sS https://getcomposer.org/installer -o composer-setup.php
        
        if [ -w /usr/local/bin ]; then
            php composer-setup.php --install-dir=/usr/local/bin --filename=composer 2>&1 | grep -v "^Deprecation" || true
        else
            php composer-setup.php --install-dir=$HOME/.local/bin --filename=composer 2>&1 | grep -v "^Deprecation" || true
            echo -e "${YELLOW}Adicione ~/.local/bin ao PATH se necessário${NC}"
        fi
    fi
else
    echo -e "${GREEN}✓ Composer já é 2.x${NC}"
fi

# Check for composer.phar in project
COMPOSER_CMD="composer"
if [ -f "./api/composer.phar" ]; then
    COMPOSER_CMD="php ./api/composer.phar"
fi

# Install dependencies
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$PROJECT_DIR/api"

echo -e "\n${BLUE}Instalando dependências PHP...${NC}"
cd "$API_DIR"

if $COMPOSER_CMD install --ignore-platform-reqs 2>&1 | tail -10; then
    echo -e "${GREEN}✓ Dependências instaladas!${NC}"
    
    echo -e "\n${BLUE}Gerando autoload...${NC}"
    $COMPOSER_CMD dump-autoload 2>&1 | tail -5
    
    # Check if Lumen is installed
    if [ -d "vendor/laravel/lumen-framework" ]; then
        LUMEN_VER=$(composer show laravel/lumen-framework 2>/dev/null | grep -oP 'versions : \K.*' | head -1)
        echo -e "${GREEN}✓ Lumen instalado!${NC}"
    else
        echo -e "${RED}✗ Lumen não foi instalado${NC}"
    fi
else
    echo -e "${RED}✗ Erro na instalação${NC}"
fi

echo -e "\n${GREEN}Pronto!${NC}"
echo "Execute: ./lizzie.sh dev"