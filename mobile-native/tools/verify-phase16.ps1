param(
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk",
    [string]$PhpPath = 'C:\xampp\php\php.exe',
    [switch]$ProfileConnectedDevice
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$laravelRoot = Join-Path (Split-Path -Parent $projectRoot) 'educore'
$appId = 'online.educoreng.educore.nativepreview'

foreach ($required in @((Join-Path $JavaHome 'bin\java.exe'), $AndroidHome, $PhpPath)) {
    if (-not (Test-Path -LiteralPath $required)) { throw "Required validation dependency not found: $required" }
}

$heapDumps = @(Get-ChildItem -LiteralPath $projectRoot -Filter '*.hprof' -File -Recurse -ErrorAction SilentlyContinue)
if ($heapDumps.Count -gt 0) { throw "Heap dumps must not remain in the mobile source tree: $($heapDumps.FullName -join ', ')" }

$roomSchemaRoot = Join-Path $projectRoot 'core\data\schemas'
Get-ChildItem -LiteralPath $roomSchemaRoot -Filter '*.json' -File -Recurse | ForEach-Object {
    try {
        Get-Content -LiteralPath $_.FullName -Raw | ConvertFrom-Json | Out-Null
    } catch {
        throw "Invalid Room schema JSON: $($_.FullName). $($_.Exception.Message)"
    }
}

$manifestPath = Join-Path $projectRoot 'app\src\main\AndroidManifest.xml'
$manifest = Get-Content -LiteralPath $manifestPath -Raw
foreach ($requiredSetting in @('android:allowBackup="false"', 'android:usesCleartextTraffic="false"')) {
    if (-not $manifest.Contains($requiredSetting)) { throw "Missing Android security setting: $requiredSetting" }
}
foreach ($forbiddenPermission in @('READ_EXTERNAL_STORAGE', 'WRITE_EXTERNAL_STORAGE', 'MANAGE_EXTERNAL_STORAGE')) {
    if ($manifest.Contains($forbiddenPermission)) { throw "Broad storage permission is forbidden: $forbiddenPermission" }
}

$sourceFiles = Get-ChildItem -LiteralPath $projectRoot -Recurse -File |
    Where-Object {
        $_.Extension -in @('.kt', '.kts', '.xml', '.properties') -and
        $_.FullName -notmatch '[\\/](build|\.gradle|\.codex-release)[\\/]' -and
        $_.Name -ne 'google-services.json' -and
        $_.Name -ne 'local.properties' -and
        $_.Name -notlike '*.example'
    }
$secretPatterns = @(
    '-----BEGIN (RSA |EC |OPENSSH )?PRIVATE KEY-----',
    '(?i)(client_secret|api_secret|private_key)\s*[=:]\s*["''][^"'']{8,}',
    '(?<![A-Za-z0-9])sk-(?:live|proj)-[A-Za-z0-9_-]{16,}'
)
foreach ($pattern in $secretPatterns) {
    $match = $sourceFiles | Select-String -Pattern $pattern | Select-Object -First 1
    if ($match) { throw "Potential hardcoded credential found at $($match.Path):$($match.LineNumber)." }
}

$env:JAVA_HOME = $JavaHome
$env:ANDROID_HOME = $AndroidHome
$env:ANDROID_SDK_ROOT = $AndroidHome

$connectedDeviceProfiling = $false
if ($ProfileConnectedDevice) {
    $adb = Join-Path $AndroidHome 'platform-tools\adb.exe'
    if (-not (Test-Path -LiteralPath $adb)) {
        Write-Warning "Connected-device profiling was skipped because ADB was not found at $adb."
    } else {
        $deviceLines = @(& $adb devices | Select-Object -Skip 1 | Where-Object { $_ -match '\sdevice$' })
        if ($deviceLines.Count -eq 0) {
            Write-Warning 'Connected-device profiling was skipped because no authorized Android device or emulator is connected.'
        } else {
            $connectedDeviceProfiling = $true
        }
    }
}

$localPropertiesPath = Join-Path $projectRoot 'local.properties'
$escapedAndroidHome = $AndroidHome.Replace('\', '/').Replace(':', '\:')
$existingLocalProperties = if (Test-Path -LiteralPath $localPropertiesPath) {
    Get-Content -LiteralPath $localPropertiesPath | Where-Object { $_ -notmatch '^sdk\.dir=' }
} else { @() }
@("sdk.dir=$escapedAndroidHome") + $existingLocalProperties |
    Set-Content -LiteralPath $localPropertiesPath -Encoding Ascii

Push-Location $projectRoot
try {
    $gradleTasks = @(
        '--no-daemon',
        '--no-parallel',
        ':core:common:testDebugUnitTest',
        ':core:designsystem:testDebugUnitTest',
        ':core:model:testDebugUnitTest',
        ':core:network:testDebugUnitTest',
        ':core:security:testDebugUnitTest',
        ':core:data:testDebugUnitTest',
        ':app:testDebugUnitTest',
        ':app:compileReleaseKotlin',
        ':app:lintDebug',
        ':app:lintRelease',
        ':app:assembleDebug',
        ':app:minifyReleaseWithR8'
    )
    if ($connectedDeviceProfiling) { $gradleTasks += ':app:installDebug' }
    & .\gradlew.bat $gradleTasks
    if ($LASTEXITCODE -ne 0) { throw "Phase 16 Android validation failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }

if ($connectedDeviceProfiling) {
    & $adb shell am force-stop $appId | Out-Null
    $startup = & $adb shell am start -W -n "$appId/online.educoreng.educore.MainActivity"
    if ($LASTEXITCODE -ne 0) { throw 'Connected-device startup profiling failed.' }
    $startup | ForEach-Object { Write-Host $_ }
    if (-not ($startup -match 'TotalTime:')) { throw 'ADB did not return a startup TotalTime measurement.' }
}

Push-Location $laravelRoot
try {
    & $PhpPath artisan test `
        tests/Feature/MobileCommunicationTest.php `
        tests/Feature/MobileOperationsTest.php `
        tests/Feature/CbtRestructuringTest.php `
        tests/Feature/MobileBootstrapTest.php `
        tests/Feature/MobileClassWorkspaceTest.php `
        tests/Feature/MobileScoresAndResultsTest.php `
        tests/Feature/MobileScheduleTest.php `
        tests/Feature/MobileLessonRepositoryTest.php `
        tests/Feature/AcademicRepositoryReaderTest.php `
        tests/Feature/PushRegistrationTest.php `
        tests/Feature/TenantAccessEnforcementTest.php
    if ($LASTEXITCODE -ne 0) { throw "Phase 16 Laravel regression failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }

Write-Host 'Phase 16 validation passed: bounded files/images, paged large lists, Room index, startup marker, security checks, debug build and release R8 path are green.' -ForegroundColor Green
