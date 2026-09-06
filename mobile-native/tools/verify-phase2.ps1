param(
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk"
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot

if (-not (Test-Path -LiteralPath (Join-Path $JavaHome 'bin\java.exe'))) {
    throw "JDK not found at $JavaHome"
}

if (-not (Test-Path -LiteralPath $AndroidHome)) {
    throw "Android SDK not found at $AndroidHome"
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
        throw "Phase 2 Android validation failed with exit code $LASTEXITCODE."
    }
} finally {
    Pop-Location
}
