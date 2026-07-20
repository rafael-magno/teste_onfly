#!/usr/bin/env bash
set -e

cd "$(dirname "$0")"

if [ ! -f .env ]; then
    cp .env.example .env
    echo ".env criado a partir de .env.example"
fi

echo "Subindo os containers..."
docker compose up -d --build

echo "Instalando dependências..."
docker compose exec -T app composer install --no-interaction

if ! grep -q "^APP_KEY=base64" .env; then
    echo "Gerando APP_KEY..."
    docker compose exec -T app php artisan key:generate
fi

if ! grep -qE "^JWT_SECRET=.+" .env; then
    echo "Gerando JWT_SECRET..."
    docker compose exec -T app php artisan jwt:secret --force
fi

echo "Rodando migrations e seeders..."
docker compose exec -T app php artisan migrate --seed

echo ""
echo "Pronto!"
echo "API:     http://localhost:8000/api"
echo "Docs:    http://localhost:8000/docs"
echo "Mailpit: http://localhost:8025"
