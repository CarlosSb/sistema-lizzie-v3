#!/bin/bash

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
CYAN='\033[0;36m'
NC='\033[0m'

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
API_DIR="$PROJECT_DIR/api"
FRONTEND_DIR="$PROJECT_DIR/frontend"

check_phpbrew() {
    if [ -f "$HOME/bin/phpbrew" ]; then
        source ~/.phpbrew/bashrc 2>/dev/null
        PHPBREW_VERSION=$(phpbrew --version 2>/dev/null | head -1)
        echo -e "${BLUE}phpbrew:${NC} $PHPBREW_VERSION"
        echo -e "${GREEN}✓ Instalado${NC}"
        return 0
    elif command -v phpbrew &> /dev/null; then
        PHPBREW_VERSION=$(phpbrew --version 2>/dev/null | head -1)
        echo -e "${BLUE}phpbrew:${NC} $PHPBREW_VERSION"
        echo -e "${GREEN}✓ Instalado${NC}"
        return 0
    else
        echo -e "${YELLOW}⚠ phpbrew não instalado${NC}"
        return 1
    fi
}

install_phpbrew() {
    echo -e "${CYAN}Instalando phpbrew...${NC}"
    
    mkdir -p ~/bin
    cd ~/bin
    curl -L -o phpbrew "https://github.com/phpbrew/phpbrew/releases/download/2.2.0/phpbrew.phar"
    chmod +x phpbrew
    
    phpbrew init
    
    echo -e "\n${GREEN}phpbrew instalado!${NC}"
    echo -e "\nAdicione ao seu ~/.zshrc:"
    echo -e "  ${YELLOW}source ~/.phpbrew/bashrc${NC}"
    echo -e "\nOu execute:"
    echo -e "  ${YELLOW}source ~/.zshrc${NC}"
}

check_php() {
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    PHP_MAJOR=$(php -r "echo PHP_MAJOR_VERSION;")
    PHP_MINOR=$(php -r "echo PHP_MINOR_VERSION;")
    
    echo -e "${BLUE}PHP:${NC} $PHP_VERSION"
    
    if [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -le 2 ]; then
        echo -e "${GREEN}✓ Compatível com Lumen 10${NC}"
        return 0
    elif [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -le 3 ]; then
        echo -e "${YELLOW}⚠ PHP 8.3 pode ter Warnings${NC}"
        return 0
    elif [ "$PHP_MAJOR" -eq 8 ] && [ "$PHP_MINOR" -ge 4 ]; then
        echo -e "${YELLOW}⚠ PHP 8.4+ pode ter incompatibilidades${NC}"
        
        if check_phpbrew; then
            echo -e "\n${CYAN}Instalar PHP 8.2:${NC}"
            echo -e "  ${YELLOW}phpbrew install 8.2 +default${NC}"
            echo -e "  ${YELLOW}phpbrew use 8.2${NC}"
        else
            echo -e "\n${CYAN}Para instalar PHP 8.2:${NC}"
            echo -e "  1. Execute: ${YELLOW}./lizzie.sh install-phpbrew${NC}"
            echo -e "  2. Instale dependências (veja abaixo)"
            echo -e "  3. Execute: ${YELLOW}phpbrew install 8.2 +default${NC}"
        fi
        return 1
    else
        echo -e "${RED}✗ PHP 8.1+ necessário${NC}"
        return 1
    fi
}

check_composer() {
    COMPOSER_VERSION=$(composer --version 2>/dev/null | grep -oP '\d+\.\d+' | head -1)
    COMPOSER_MAJOR=$(echo $COMPOSER_VERSION | cut -d. -f1)
    COMPOSER_MINOR=$(echo $COMPOSER_VERSION | cut -d. -f2)
    
    echo -e "${BLUE}Composer:${NC} $COMPOSER_VERSION"
    
    if [ "$COMPOSER_MAJOR" -ge 2 ]; then
        echo -e "${GREEN}✓ OK${NC}"
        return 0
    else
        echo -e "${RED}✗ Composer 2.x necessário${NC}"
        echo -e "${CYAN}Atualize com:${NC}"
        echo -e "  ${YELLOW}sudo composer self-update --stable${NC}"
        return 1
    fi
}

check_build_deps() {
    echo -e "\n${BLUE}Dependências de Build PHP:${NC}"
    
    local missing=()
    local required=("make" "gcc" "libxml2-dev" "libsqlite3-dev" "pkg-config" "libonig-dev" "libcurl4-openssl-dev" "libssl-dev" "libzip-dev")
    
    for dep in "${required[@]}"; do
        if dpkg -l | grep -q "^ii.*$dep"; then
            echo -e "  ${GREEN}✓${NC} $dep"
        else
            echo -e "  ${RED}✗${NC} $dep"
            missing+=("$dep")
        fi
    done
    
    if [ ${#missing[@]} -gt 0 ]; then
        echo ""
        echo -e "${YELLOW}Faltam dependências de build!${NC}"
        echo -e "${CYAN}Para PHP 8.2 via phpbrew, instale:${NC}"
        echo -e "${YELLOW}  sudo apt install build-essential autoconf bison re2c \\${NC}"
        echo -e "${YELLOW}    libxml2-dev libsqlite3-dev pkg-config libonig-dev \\${NC}"
        echo -e "${YELLOW}    libcurl4-openssl-dev libssl-dev libzip-dev libreadline-dev libicu-dev${NC}"
        echo ""
        echo -e "${CYAN}Ou instale todas de uma vez:${NC}"
        echo -e "${YELLOW}  sudo apt install build-essential autoconf bison re2c libxml2-dev libsqlite3-dev pkg-config libonig-dev libcurl4-openssl-dev libssl-dev libzip-dev libreadline-dev libicu-dev${NC}"
        return 1
    fi
    
    return 0
}

check_extensions() {
    echo -e "\n${BLUE}Extensões PHP (runtime):${NC}"
    
    local missing=()
    local required=("pdo_mysql" "mbstring" "xml" "json")
    
    for ext in "${required[@]}"; do
        if php -m | grep -qi "^$ext$"; then
            echo -e "  ${GREEN}✓${NC} $ext"
        else
            echo -e "  ${RED}✗${NC} $ext"
            missing+=("$ext")
        fi
    done
    
    if [ ${#missing[@]} -gt 0 ]; then
        echo ""
        echo -e "${YELLOW}Faltam extensões runtime:${NC}"
        echo -e "${CYAN}Instale com:${NC}"
        echo -e "  ${YELLOW}sudo apt install php-mysql php-mbstring php-xml php-json${NC}"
        return 1
    fi
    
    return 0
}

check_node() {
    if command -v node &> /dev/null; then
        NODE_VERSION=$(node -v)
        echo -e "${BLUE}Node.js:${NC} $NODE_VERSION"
        
        NODE_MAJOR=$(echo $NODE_VERSION | cut -d. -f1 | tr -d 'v')
        if [ "$NODE_MAJOR" -ge 18 ]; then
            echo -e "${GREEN}✓ OK${NC}"
            return 0
        else
            echo -e "${YELLOW}⚠ Node 18+ recomendado${NC}"
            return 1
        fi
    else
        echo -e "${RED}✗ Node.js não encontrado${NC}"
        return 1
    fi
}

check_npm() {
    if command -v npm &> /dev/null; then
        NPM_VERSION=$(npm -v)
        echo -e "${BLUE}npm:${NC} $NPM_VERSION"
        echo -e "${GREEN}✓ OK${NC}"
        return 0
    else
        echo -e "${RED}✗ npm não encontrado${NC}"
        return 1
    fi
}

check_vendor() {
    echo -e "\n${BLUE}Dependências:${NC}"
    
    if [ -d "$API_DIR/vendor" ]; then
        if [ -d "$API_DIR/vendor/laravel/lumen-framework" ]; then
            echo -e "  ${GREEN}✓${NC} Lumen"
        else
            echo -e "  ${YELLOW}⚠ vendor existe mas Lumen não instalado${NC}"
            return 1
        fi
    else
        echo -e "  ${YELLOW}⚠ vendor não instalado${NC}"
        return 1
    fi
    
    if [ -d "$FRONTEND_DIR/node_modules" ]; then
        echo -e "  ${GREEN}✓${NC} Frontend deps"
    else
        echo -e "  ${YELLOW}⚠ Frontend deps não instaladas${NC}"
        return 1
    fi
    
    return 0
}

show_help() {
    echo -e "${BLUE}=== Sistema Lizzie v3 - CLI ===${NC}"
    echo ""
    echo "Uso: ./lizzie.sh [comando]"
    echo ""
    echo -e "${CYAN}Comandos:${NC}"
    echo "  check              Verifica ambiente e dependências"
    echo "  install            Instala todas as dependências"
    echo "  dev                Inicia desenvolvimento (API + Frontend)"
    echo "  serve              Inicia API (PHP built-in server)"
    echo "  frontend           Inicia apenas o Frontend"
    echo "  build              Build para produção"
    echo "  migrate            Executa migrations"
    echo "  rollback           Desfaz migrations (drop tables)"
    echo ""
    echo -e "${CYAN}PHP Version Manager:${NC}"
    echo "  install-phpbrew    Instala phpbrew (para múltiplas versões PHP)"
    echo "  install-php82      Instala PHP 8.2 via phpbrew"
    echo ""
    echo -e "${CYAN}Requisitos:${NC}"
    echo "  • PHP 8.1 - 8.2 (preferencialmente)"
    echo "  • Composer 2.x"
    echo "  • Node.js 18+"
    echo "  • Extensões: pdo_mysql, mbstring, xml, json"
    echo ""
    echo -e "${YELLOW}Para PHP 8.4+ ou Composer antigo, use:${NC}"
    echo "  ./lizzie.sh install-phpbrew"
    echo "  ./lizzie.sh install-php82"
}

cmd_check() {
    echo -e "${BLUE}=== Verificação do Ambiente ===${NC}\n"
    
    local errors=0
    
    check_phpbrew
    echo ""
    check_php || errors=$((errors+1))
    echo ""
    check_composer || errors=$((errors+1))
    echo ""
    check_build_deps || errors=$((errors+1))
    echo ""
    check_extensions || errors=$((errors+1))
    echo ""
    check_node
    echo ""
    check_npm
    echo ""
    check_vendor
    
    echo ""
    if [ $errors -eq 0 ]; then
        echo -e "${GREEN}✓ Ambiente OK!${NC}"
        echo -e "\nExecute: ${YELLOW}./lizzie.sh dev${NC}"
    else
        echo -e "${YELLOW}⚠ $errors problema(s) encontrado(s)${NC}"
        echo -e "\nExecute: ${YELLOW}./lizzie.sh help${NC} para ver soluções"
    fi
}

cmd_install_phpbrew() {
    install_phpbrew
}

cmd_install_php82() {
    if [ ! -f "$HOME/bin/phpbrew" ]; then
        echo -e "${RED}phpbrew não instalado${NC}"
        echo -e "Execute: ${YELLOW}./lizzie.sh install-phpbrew${NC}"
        return 1
    fi
    
    source ~/.phpbrew/bashrc 2>/dev/null
    
    echo -e "${CYAN}Instalando PHP 8.2...${NC}"
    echo -e "${YELLOW}Isso pode levar vários minutos...${NC}\n"
    
    phpbrew install 8.2 +default
    
    if [ $? -eq 0 ]; then
        echo -e "\n${GREEN}PHP 8.2 instalado!${NC}"
        echo -e "\nPara usar:"
        echo -e "  ${YELLOW}phpbrew use 8.2${NC}"
        echo -e "  ${YELLOW}php -v${NC}"
    else
        echo -e "${RED}Falha na instalação${NC}"
        echo -e "\nVerifique se as dependências de build estão instaladas:"
        check_build_deps
    fi
}

cmd_install() {
    echo -e "${YELLOW}Instalando dependências...${NC}\n"
    
    cd "$API_DIR"
    echo -e "${BLUE}>> Install PHP dependencies${NC}"
    composer install --ignore-platform-reqs
    
    echo -e "\n${BLUE}>> Install Node dependencies${NC}"
    cd "$FRONTEND_DIR"
    npm install
    
    echo -e "\n${GREEN}✓ Instalação concluída!${NC}"
}

cmd_dev() {
    echo -e "${YELLOW}Iniciando ambiente de desenvolvimento...${NC}"
    
    if [ ! -d "$API_DIR/vendor" ]; then
        echo -e "${RED}Execute './lizzie.sh install' primeiro${NC}"
        exit 1
    fi
    
    echo -e "${BLUE}Iniciando API (Lumen) na porta 8000...${NC}"
    cd "$API_DIR"
    php -S localhost:8000 -t public &
    API_PID=$!
    
    sleep 2
    
    echo -e "${BLUE}Iniciando Frontend (Vue) na porta 5173...${NC}"
    cd "$FRONTEND_DIR"
    npm run dev &
    FRONTEND_PID=$!
    
    echo -e "\n${GREEN}=== Ambiente rodando ===${NC}"
    echo -e "API:     ${BLUE}http://localhost:8000${NC}"
    echo -e "Frontend:${BLUE} http://localhost:5173${NC}"
    echo -e "\nPressione Ctrl+C para parar"
    
    trap "kill $API_PID $FRONTEND_PID 2>/dev/null" EXIT
    
    wait
}

cmd_api() {
    cd "$API_DIR"
    php -S localhost:8000 -t public
}

cmd_frontend() {
    cd "$FRONTEND_DIR"
    npm run dev
}

cmd_build() {
    echo -e "${YELLOW}Build para produção...${NC}"
    cd "$FRONTEND_DIR"
    npm run build
    echo -e "${GREEN}✓ Build concluído em public/${NC}"
}

cmd_migrate() {
    cd "$API_DIR"
    php lizzie.php migrate
}

cmd_rollback() {
    cd "$API_DIR"
    php lizzie.php rollback
}

# Main
case "${1:-help}" in
    check)            cmd_check ;;
    install)          cmd_install ;;
    install-phpbrew)  cmd_install_phpbrew ;;
    install-php82)    cmd_install_php82 ;;
    dev)              cmd_dev ;;
    serve)            cmd_api ;;
    api)              cmd_api ;;
    frontend)         cmd_frontend ;;
    build)            cmd_build ;;
    migrate)          cmd_migrate ;;
    rollback)         cmd_rollback ;;
    help|--help|-h)   show_help ;;
    *)                echo -e "${RED}Comando desconhecido: $1${NC}"; show_help; exit 1 ;;
esac