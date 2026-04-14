#!/bin/bash

# Script para instalar dependências com Composer correto

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$PROJECT_DIR"

echo "=== Instalando Composer 2.x ==="

# Baixar Composer mais recente
curl -sS https://getcomposer.org/installer -o /tmp/composer-setup.php
php /tmp/composer-setup.php --install-dir=/tmp --filename=composer-new 2>&1 | grep -v "^Deprecation" || true

COMPOSER_NEW="/tmp/composer-new"

if [ ! -f "$COMPOSER_NEW" ]; then
    echo "Erro ao baixar Composer"
    exit 1
fi

echo "Composer atualizado!"

echo ""
echo "=== Instalando dependências do Lumen ==="

cd "$PROJECT_DIR/api"

php "$COMPOSER_NEW" install --ignore-platform-reqs 2>&1 | tail -20

echo ""
echo "=== Gerando/autoload ==="

php "$COMPOSER_NEW" dump-autoload 2>&1 | tail -5

echo ""
echo "=== Verificando instalação ==="

if [ -d "vendor/laravel/lumen-framework" ]; then
    echo "✓ Lumen instalado com sucesso!"
    ls vendor/laravel/lumen-framework/
else
    echo "✗ Problema na instalação"
fi