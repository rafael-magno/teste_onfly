$ErrorActionPreference = "Stop"

Set-Location $PSScriptRoot

if (-not (Test-Path .env)) {
    Copy-Item .env.example .env
    Write-Host ".env criado a partir de .env.example"
}

Write-Host "Subindo os containers..."
docker compose up -d --build

Write-Host "Instalando dependências..."
docker compose exec -T app composer install --no-interaction

$envContent = Get-Content .env -Raw
if ($envContent -notmatch "(?m)^APP_KEY=base64") {
    Write-Host "Gerando APP_KEY..."
    docker compose exec -T app php artisan key:generate
}

$envContent = Get-Content .env -Raw
if ($envContent -notmatch "(?m)^JWT_SECRET=.+") {
    Write-Host "Gerando JWT_SECRET..."
    docker compose exec -T app php artisan jwt:secret --force
}

Write-Host "Rodando migrations e seeders..."
docker compose exec -T app php artisan migrate --seed

Write-Host ""
Write-Host "Pronto!"
Write-Host "API:     http://localhost:8000/api"
Write-Host "Docs:    http://localhost:8000/docs"
Write-Host "Mailpit: http://localhost:8025"
