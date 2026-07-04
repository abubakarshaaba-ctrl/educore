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

function Set-EnvValueInMemory {
    param(
        [Parameter(Mandatory = $true)][string]$Content,
        [Parameter(Mandatory = $true)][string]$Name,
        [Parameter(Mandatory = $true)][string]$Value
    )

    $escapedName = [regex]::Escape($Name)
    $line = "$Name=$Value"

    if ($Content -match "(?m)^$escapedName=") {
        return [regex]::Replace($Content, "(?m)^$escapedName=.*$", $line)
    }

    return $Content.TrimEnd() + "`r`n$line`r`n"
}

try {
    Write-Host "`n=== EduCore local validation ===" -ForegroundColor Cyan

    if ($HadOriginalEnv) {
        Copy-Item $OriginalEnv $BackupEnv -Force
    }

    if (Test-Path $ValidationDb) {
        Remove-Item $ValidationDb -Force
    }
    New-Item -ItemType File -Path $ValidationDb | Out-Null

    $sqlitePath = $ValidationDb.Replace('\', '/')
    $envContent = Get-Content (Join-Path $ProjectRoot '.env.example') -Raw

    $envContent = Set-EnvValueInMemory $envContent 'APP_ENV' 'testing'
    $envContent = Set-EnvValueInMemory $envContent 'APP_DEBUG' 'true'
    $envContent = Set-EnvValueInMemory $envContent 'APP_URL' 'http://localhost'
    $envContent = Set-EnvValueInMemory $envContent 'DB_CONNECTION' 'sqlite'
    $envContent = Set-EnvValueInMemory $envContent 'DB_DATABASE' "`"$sqlitePath`""
    $envContent = Set-EnvValueInMemory $envContent 'SESSION_DRIVER' 'array'
    $envContent = Set-EnvValueInMemory $envContent 'CACHE_STORE' 'array'
    $envContent = Set-EnvValueInMemory $envContent 'QUEUE_CONNECTION' 'sync'
    $envContent = Set-EnvValueInMemory $envContent 'MAIL_MAILER' 'array'

    $tempEnv = Join-Path $ProjectRoot '.env.validation.tmp'
    [System.IO.File]::WriteAllText($tempEnv, $envContent, [System.Text.UTF8Encoding]::new($false))
    Move-Item $tempEnv $OriginalEnv -Force

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

    Start-Sleep -Milliseconds 300

    if ($HadOriginalEnv -and (Test-Path $BackupEnv)) {
        Copy-Item $BackupEnv $OriginalEnv -Force
        Remove-Item $BackupEnv -Force
    }
    elseif (Test-Path $OriginalEnv) {
        Remove-Item $OriginalEnv -Force
    }

    $tempEnv = Join-Path $ProjectRoot '.env.validation.tmp'
    if (Test-Path $tempEnv) {
        Remove-Item $tempEnv -Force
    }

    if (Test-Path $ValidationDb) {
        Remove-Item $ValidationDb -Force
    }

    Write-Host "Local .env restored and temporary database removed." -ForegroundColor Cyan
}
