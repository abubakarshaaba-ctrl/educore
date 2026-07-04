param(
    [switch]$SkipComposer,
    [switch]$SkipNpm
)

$ErrorActionPreference = 'Stop'
Set-StrictMode -Version Latest

$ProjectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $ProjectRoot

$OriginalEnv = Join-Path $ProjectRoot '.env'
$BackupEnv = Join-Path $ProjectRoot '.env.local-validation-backup'
$ValidationDb = Join-Path $ProjectRoot 'database/validation.sqlite'
$HadOriginalEnv = Test-Path $OriginalEnv

function Set-EnvValue {
    param(
        [Parameter(Mandatory = $true)][string]$Path,
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][string]$Value
    )

    $content = Get-Content $Path -Raw
    $escapedName = [regex]::Escape($Name)
    $line = "$Name=$Value"

    if ($content -match "(?m)^$escapedName=") {
        $content = [regex]::Replace($content, "(?m)^$escapedName=.*$", $line)
    }
    else {
        $content = $content.TrimEnd() + "`r`n$line`r`n"
    }

    Set-Content -Path $Path -Value $content -Encoding UTF8
}

try {
    Write-Host "`n=== EduCore local validation ===" -ForegroundColor Cyan

    if ($HadOriginalEnv) {
        Copy-Item $OriginalEnv $BackupEnv -Force
    }

    Copy-Item (Join-Path $ProjectRoot '.env.example') $OriginalEnv -Force

    if (Test-Path $ValidationDb) {
        Remove-Item $ValidationDb -Force
    }
    New-Item -ItemType File -Path $ValidationDb | Out-Null

    $sqlitePath = $ValidationDb.Replace('\', '/')

    Set-EnvValue $OriginalEnv 'APP_ENV' 'testing'
    Set-EnvValue $OriginalEnv 'APP_DEBUG' 'true'
    Set-EnvValue $OriginalEnv 'APP_URL' 'http://localhost'
    Set-EnvValue $OriginalEnv 'DB_CONNECTION' 'sqlite'
    Set-EnvValue $OriginalEnv 'DB_DATABASE' "`"$sqlitePath`""
    Set-EnvValue $OriginalEnv 'SESSION_DRIVER' 'array'
    Set-EnvValue $OriginalEnv 'CACHE_STORE' 'array'
    Set-EnvValue $OriginalEnv 'QUEUE_CONNECTION' 'sync'
    Set-EnvValue $OriginalEnv 'MAIL_MAILER' 'array'

    if (-not $SkipComposer) {
        Write-Host "`n[1/7] Validating composer.json..." -ForegroundColor Yellow
        composer validate --strict

        Write-Host "`n[2/7] Installing PHP dependencies..." -ForegroundColor Yellow
        composer install --no-interaction --prefer-dist --no-progress
    }
    else {
        Write-Host "`n[1-2/7] Composer steps skipped." -ForegroundColor DarkYellow
    }

    Write-Host "`n[3/7] Preparing Laravel test environment..." -ForegroundColor Yellow
    php artisan key:generate --force
    php artisan optimize:clear

    Write-Host "`n[4/7] Running migrations against temporary SQLite database..." -ForegroundColor Yellow
    php artisan migrate:fresh --force

    Write-Host "`n[5/7] Running Laravel tests..." -ForegroundColor Yellow
    php artisan test

    if (-not $SkipNpm) {
        Write-Host "`n[6/7] Installing frontend dependencies..." -ForegroundColor Yellow
        npm ci

        Write-Host "`n[7/7] Building frontend assets..." -ForegroundColor Yellow
        npm run build
    }
    else {
        Write-Host "`n[6-7/7] NPM steps skipped." -ForegroundColor DarkYellow
    }

    Write-Host "`nValidation completed successfully." -ForegroundColor Green
}
finally {
    Write-Host "`nRestoring local environment..." -ForegroundColor Cyan

    if ($HadOriginalEnv -and (Test-Path $BackupEnv)) {
        Move-Item $BackupEnv $OriginalEnv -Force
    }
    elseif (Test-Path $OriginalEnv) {
        Remove-Item $OriginalEnv -Force
    }

    if (Test-Path $ValidationDb) {
        Remove-Item $ValidationDb -Force
    }

    Write-Host "Local .env restored and temporary database removed." -ForegroundColor Cyan
}
