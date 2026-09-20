param(
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk",
    [string]$PhpPath = 'C:\xampp\php\php.exe'
)
$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$laravelRoot = Join-Path (Split-Path -Parent $projectRoot) 'educore'
$env:JAVA_HOME = $JavaHome; $env:ANDROID_HOME = $AndroidHome; $env:ANDROID_SDK_ROOT = $AndroidHome
Push-Location $projectRoot
try {
    & .\gradlew.bat --no-daemon :core:common:testDebugUnitTest :core:designsystem:testDebugUnitTest :core:model:testDebugUnitTest :core:network:testDebugUnitTest :core:security:testDebugUnitTest :core:data:testDebugUnitTest :app:testDebugUnitTest :app:lintDebug :app:assembleDebug
    if ($LASTEXITCODE -ne 0) { throw "Phase 13 Android validation failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }
Push-Location $laravelRoot
try {
    & $PhpPath artisan test tests/Feature/MobileClassWorkspaceTest.php tests/Feature/MobileScoresAndResultsTest.php tests/Feature/MobileBootstrapTest.php tests/Feature/TenantAccessEnforcementTest.php
    if ($LASTEXITCODE -ne 0) { throw "Phase 13 Laravel validation failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }
Write-Host 'Phase 13 validation passed: durable drafts, idempotent queued writes, conflicts, WorkManager retry, Lint and debug assembly are green.' -ForegroundColor Green
