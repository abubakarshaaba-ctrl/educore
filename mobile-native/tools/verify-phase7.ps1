param(
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk",
    [string]$PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$laravelRoot = Join-Path (Split-Path -Parent $projectRoot) 'educore'

foreach ($required in @((Join-Path $JavaHome 'bin\java.exe'), $AndroidHome, $PhpPath)) {
    if (-not (Test-Path -LiteralPath $required)) { throw "Required validation dependency not found: $required" }
}

$env:JAVA_HOME = $JavaHome
$env:ANDROID_HOME = $AndroidHome
$env:ANDROID_SDK_ROOT = $AndroidHome

$localPropertiesPath = Join-Path $projectRoot 'local.properties'
$escapedAndroidHome = $AndroidHome.Replace('\', '/').Replace(':', '\:')
$existingLocalProperties = if (Test-Path -LiteralPath $localPropertiesPath) {
    Get-Content -LiteralPath $localPropertiesPath | Where-Object { $_ -notmatch '^sdk\.dir=' }
} else { @() }
@("sdk.dir=$escapedAndroidHome") + $existingLocalProperties |
    Set-Content -LiteralPath $localPropertiesPath -Encoding Ascii

Push-Location $projectRoot
try {
    & .\gradlew.bat --no-daemon `
        :core:common:testDebugUnitTest `
        :core:designsystem:testDebugUnitTest `
        :core:model:testDebugUnitTest `
        :core:network:testDebugUnitTest `
        :core:security:testDebugUnitTest `
        :core:data:testDebugUnitTest `
        :app:testDebugUnitTest `
        :app:lintDebug `
        :app:assembleDebug

    if ($LASTEXITCODE -ne 0) { throw "Phase 7 Android validation failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }

Push-Location $laravelRoot
try {
    & $PhpPath artisan test `
        tests/Feature/MobileBootstrapTest.php `
        tests/Feature/MobileClassWorkspaceTest.php `
        tests/Feature/MobileScoresAndResultsTest.php `
        tests/Feature/PushRegistrationTest.php `
        tests/Feature/TenantAccessEnforcementTest.php

    if ($LASTEXITCODE -ne 0) { throw "Phase 7 Laravel validation failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }

Write-Host 'Phase 7 validation passed: score contracts, durable drafts, publication locks, official results, Android Lint and debug assembly are green.' -ForegroundColor Green
