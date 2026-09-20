param(
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk",
    [string]$PhpPath = 'C:\xampp\php\php.exe'
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$laravelRoot = Join-Path (Split-Path -Parent $projectRoot) 'educore'

if (-not (Test-Path -LiteralPath (Join-Path $JavaHome 'bin\java.exe'))) {
    throw "JDK not found at $JavaHome"
}

if (-not (Test-Path -LiteralPath $AndroidHome)) {
    throw "Android SDK not found at $AndroidHome"
}

if (-not (Test-Path -LiteralPath $PhpPath)) {
    throw "PHP not found at $PhpPath"
}

$env:JAVA_HOME = $JavaHome
$env:ANDROID_HOME = $AndroidHome
$env:ANDROID_SDK_ROOT = $AndroidHome

$localPropertiesPath = Join-Path $projectRoot 'local.properties'
$escapedAndroidHome = $AndroidHome.Replace('\', '/').Replace(':', '\:')
$existingLocalProperties = if (Test-Path -LiteralPath $localPropertiesPath) {
    Get-Content -LiteralPath $localPropertiesPath | Where-Object { $_ -notmatch '^sdk\.dir=' }
} else {
    @()
}
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

    if ($LASTEXITCODE -ne 0) {
        throw "Phase 6 Android validation failed with exit code $LASTEXITCODE."
    }
} finally {
    Pop-Location
}

Push-Location $laravelRoot
try {
    & $PhpPath artisan test `
        tests/Feature/MobileBootstrapTest.php `
        tests/Feature/MobileClassWorkspaceTest.php `
        tests/Feature/PushRegistrationTest.php `
        tests/Feature/TenantAccessEnforcementTest.php

    if ($LASTEXITCODE -ne 0) {
        throw "Phase 6 Laravel validation failed with exit code $LASTEXITCODE."
    }
} finally {
    Pop-Location
}

Write-Host 'Phase 6 validation passed: classes, students, attendance drafts, Android Lint, debug assembly and Laravel contracts are green.' -ForegroundColor Green
