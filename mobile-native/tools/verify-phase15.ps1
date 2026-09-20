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
    if ($LASTEXITCODE -ne 0) { throw "Phase 15 Android validation failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }
Push-Location $laravelRoot
try {
    & $PhpPath artisan test tests/Feature/MobileBootstrapTest.php tests/Feature/TenantAccessEnforcementTest.php tests/Feature/MobileClassWorkspaceTest.php tests/Feature/MobileScoresAndResultsTest.php
    if ($LASTEXITCODE -ne 0) { throw "Phase 15 Laravel regression failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }
Write-Host 'Phase 15 validation passed: adaptive margins, shared headers, narrow actions, keyboard polish, tests, Lint and debug assembly are green.' -ForegroundColor Green
