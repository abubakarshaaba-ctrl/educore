param(
    [Parameter(Mandatory = $true)]
    [ValidateSet('small-720p', 'common-1080p', 'android-10-plus', 'modern-android', '8gb-ram', 'lower-memory', 'tablet')]
    [string]$Profile,
    [string]$Serial,
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk",
    [string]$EvidenceRoot,
    [switch]$SkipInstall,
    [switch]$RunMigrationInstrumentation,
    [switch]$NonInteractive
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$repositoryRoot = Split-Path -Parent $projectRoot
$appId = 'online.educoreng.educore.nativepreview'
$activity = 'online.educoreng.educore.MainActivity'
$adb = Join-Path $AndroidHome 'platform-tools\adb.exe'

foreach ($required in @((Join-Path $JavaHome 'bin\java.exe'), $AndroidHome, $adb)) {
    if (-not (Test-Path -LiteralPath $required)) { throw "Required device-test dependency not found: $required" }
}

$connectedDevices = @(
    & $adb devices |
        Select-Object -Skip 1 |
        Where-Object { $_ -match '\sdevice$' } |
        ForEach-Object { ($_ -split '\s+')[0] }
)
if ([string]::IsNullOrWhiteSpace($Serial)) {
    if ($connectedDevices.Count -eq 0) { throw 'No authorized Android device or emulator is connected.' }
    if ($connectedDevices.Count -gt 1) { throw "Multiple devices are connected. Rerun with -Serial. Devices: $($connectedDevices -join ', ')" }
    $Serial = $connectedDevices[0]
} elseif ($Serial -notin $connectedDevices) {
    throw "Device '$Serial' is not connected and authorized. Available devices: $($connectedDevices -join ', ')"
}

function Invoke-Adb {
    param([Parameter(Mandatory = $true)][string[]]$Arguments)
    $output = @(& $adb -s $Serial @Arguments 2>&1)
    if ($LASTEXITCODE -ne 0) { throw "ADB failed: adb -s $Serial $($Arguments -join ' ')`n$($output -join "`n")" }
    return $output
}

function Get-AdbValue {
    param([Parameter(Mandatory = $true)][string[]]$Arguments)
    return ((Invoke-Adb -Arguments $Arguments) -join "`n").Trim()
}

function Read-ManualResult {
    param(
        [Parameter(Mandatory = $true)][string]$Prompt,
        [switch]$AllowNotApplicable
    )
    if ($NonInteractive) { return 'NOT_RUN' }
    $choices = if ($AllowNotApplicable) {
        "$Prompt [P]ass / [F]ail / [N]ot applicable"
    } else {
        "$Prompt [P]ass / [F]ail / [S]kip for later"
    }
    $validAnswers = if ($AllowNotApplicable) { @('P', 'F', 'N') } else { @('P', 'F', 'S') }
    do {
        $answer = (Read-Host $choices).Trim().ToUpperInvariant()
    } while ($answer -notin $validAnswers)
    switch ($answer) {
        'P' { return 'PASS' }
        'F' { return 'FAIL' }
        'N' { return 'NOT_APPLICABLE' }
        default { return 'NOT_RUN' }
    }
}

$manufacturer = Get-AdbValue -Arguments @('shell', 'getprop', 'ro.product.manufacturer')
$model = Get-AdbValue -Arguments @('shell', 'getprop', 'ro.product.model')
$androidVersion = Get-AdbValue -Arguments @('shell', 'getprop', 'ro.build.version.release')
$apiLevel = [int](Get-AdbValue -Arguments @('shell', 'getprop', 'ro.build.version.sdk'))
$sizeText = Get-AdbValue -Arguments @('shell', 'wm', 'size')
$densityText = Get-AdbValue -Arguments @('shell', 'wm', 'density')
$memoryText = Get-AdbValue -Arguments @('shell', 'cat', '/proc/meminfo')
$memoryMatch = [regex]::Match($memoryText, 'MemTotal:\s+(\d+)\s+kB')
$memoryKb = if ($memoryMatch.Success) { [long]$memoryMatch.Groups[1].Value } else { 0L }
$memoryGb = [Math]::Round($memoryKb / 1MB, 1)
$sizeMatches = [regex]::Matches($sizeText, '(\d+)x(\d+)')
if ($sizeMatches.Count -eq 0) { throw "Unable to determine display size from: $sizeText" }
$activeSize = $sizeMatches[$sizeMatches.Count - 1]
$width = [int]$activeSize.Groups[1].Value
$height = [int]$activeSize.Groups[2].Value
$shortEdge = [Math]::Min($width, $height)
$densityMatches = [regex]::Matches($densityText, '(\d+)')
$density = if ($densityMatches.Count -gt 0) { [int]$densityMatches[$densityMatches.Count - 1].Value } else { 0 }
$diagonalInches = if ($density -gt 0) {
    [Math]::Round([Math]::Sqrt(($width * $width) + ($height * $height)) / $density, 1)
} else { 0 }

$profileMatches = switch ($Profile) {
    'small-720p' { $shortEdge -le 800 }
    'common-1080p' { $shortEdge -ge 900 -and $shortEdge -le 1200 }
    'android-10-plus' { $apiLevel -ge 29 }
    'modern-android' { $apiLevel -ge 34 }
    '8gb-ram' { $memoryGb -ge 7.0 }
    'lower-memory' { $memoryGb -le 4.5 }
    'tablet' { $diagonalInches -ge 7.0 -or $shortEdge -ge 1200 }
}
if (-not $profileMatches) {
    throw "Connected device does not satisfy profile '$Profile' (API $apiLevel, ${width}x${height}, $memoryGb GB RAM, $diagonalInches-inch estimated display)."
}

if ([string]::IsNullOrWhiteSpace($EvidenceRoot)) {
    $EvidenceRoot = Join-Path $repositoryRoot 'docs\mobile-rebuild\device-evidence'
}
$safeSerial = $Serial -replace '[^A-Za-z0-9._-]', '_'
$stamp = Get-Date -Format 'yyyyMMdd-HHmmss'
$evidenceDirectory = Join-Path $EvidenceRoot "$stamp-$Profile-$safeSerial"
New-Item -ItemType Directory -Path $evidenceDirectory -Force | Out-Null

$env:JAVA_HOME = $JavaHome
$env:ANDROID_HOME = $AndroidHome
$env:ANDROID_SDK_ROOT = $AndroidHome

if (-not $SkipInstall) {
    Push-Location $projectRoot
    try {
        $gradleTasks = @('--no-daemon', '--no-parallel')
        if ($RunMigrationInstrumentation) {
            $gradleTasks += ':core:security:connectedDebugAndroidTest'
        } else {
            # All application dependencies were proven by Phase 16. Offline
            # mode prevents an unavailable repository from blocking device QA.
            $gradleTasks += '--offline'
        }
        $gradleTasks += ':app:installDebug'
        & .\gradlew.bat $gradleTasks
        if ($LASTEXITCODE -ne 0) { throw "Device preparation or debug installation failed with exit code $LASTEXITCODE." }
    } finally { Pop-Location }
}

$packageDump = Get-AdbValue -Arguments @('shell', 'dumpsys', 'package', $appId)
if ($packageDump -notmatch [regex]::Escape($appId)) { throw "Debug package $appId is not installed." }
$packageDump | Set-Content -LiteralPath (Join-Path $evidenceDirectory 'package-permissions.txt') -Encoding UTF8

Invoke-Adb -Arguments @('logcat', '-c') | Out-Null
Invoke-Adb -Arguments @('shell', 'input', 'keyevent', 'KEYCODE_WAKEUP') | Out-Null
Invoke-Adb -Arguments @('shell', 'wm', 'dismiss-keyguard') | Out-Null
Invoke-Adb -Arguments @('shell', 'am', 'force-stop', $appId) | Out-Null
$coldWatch = [System.Diagnostics.Stopwatch]::StartNew()
$coldStart = Get-AdbValue -Arguments @('shell', 'am', 'start', '-S', '-W', '-n', "$appId/$activity")
$coldWatch.Stop()
if ($coldStart -notmatch 'Status:\s+ok') { throw "Cold startup failed.`n$coldStart" }
Invoke-Adb -Arguments @('shell', 'input', 'keyevent', 'KEYCODE_HOME') | Out-Null
$warmWatch = [System.Diagnostics.Stopwatch]::StartNew()
$warmStart = Get-AdbValue -Arguments @('shell', 'am', 'start', '-W', '-n', "$appId/$activity")
$warmWatch.Stop()
if ($warmStart -notmatch 'Status:\s+ok') { throw "Warm startup failed.`n$warmStart" }

$coldMatch = [regex]::Match($coldStart, 'TotalTime:\s+(\d+)')
$warmMatch = [regex]::Match($warmStart, 'TotalTime:\s+(\d+)')
$coldTotal = if ($coldMatch.Success) { [int]$coldMatch.Groups[1].Value } else { [int]$coldWatch.ElapsedMilliseconds }
$warmTotal = if ($warmMatch.Success) { [int]$warmMatch.Groups[1].Value } else { [int]$warmWatch.ElapsedMilliseconds }
$coldTimingSource = if ($coldMatch.Success) { 'adb-total-time' } else { 'host-elapsed' }
$warmTimingSource = if ($warmMatch.Success) { 'adb-total-time' } else { 'host-elapsed' }
$memoryDump = Get-AdbValue -Arguments @('shell', 'dumpsys', 'meminfo', $appId)
$windowDump = Get-AdbValue -Arguments @('shell', 'dumpsys', 'window', 'windows')
$focusedWindow = Get-AdbValue -Arguments @('shell', 'dumpsys', 'window')
if ($focusedWindow -notmatch "mCurrentFocus=.*$([regex]::Escape($appId))" -and
    $focusedWindow -notmatch "mFocusedApp=.*$([regex]::Escape($appId))") {
    throw 'EduCore is not the focused window. Unlock the device, keep EduCore visible, and rerun Phase 17.'
}
$logcat = Get-AdbValue -Arguments @('logcat', '-d', '-v', 'threadtime', '-t', '2000')
$memoryDump | Set-Content -LiteralPath (Join-Path $evidenceDirectory 'memory.txt') -Encoding UTF8
$windowDump | Set-Content -LiteralPath (Join-Path $evidenceDirectory 'window.txt') -Encoding UTF8
$logcat | Set-Content -LiteralPath (Join-Path $evidenceDirectory 'logcat.txt') -Encoding UTF8

$remoteScreenshot = '/sdcard/Download/educore-phase17.png'
$localScreenshot = Join-Path $evidenceDirectory 'startup-screen.png'
Invoke-Adb -Arguments @('shell', 'screencap', '-p', $remoteScreenshot) | Out-Null
& $adb -s $Serial pull $remoteScreenshot $localScreenshot | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Unable to pull the startup screenshot.' }
Invoke-Adb -Arguments @('shell', 'rm', $remoteScreenshot) | Out-Null

$manualTests = @(
    [PSCustomObject]@{ Area = 'Portrait and adaptive layout'; Required = $true; Result = (Read-ManualResult 'Confirm portrait screens have no clipping, overlap, unreadable text or unreachable actions'); Note = '' },
    [PSCustomObject]@{ Area = 'Keyboard'; Required = $true; Result = (Read-ManualResult 'Confirm login, search, score and message forms remain usable with the keyboard open'); Note = '' },
    [PSCustomObject]@{ Area = 'Rotation'; Required = $false; Result = (Read-ManualResult 'Where supported, rotate the device and confirm state and navigation remain correct' -AllowNotApplicable); Note = '' },
    [PSCustomObject]@{ Area = 'Permissions'; Required = $true; Result = (Read-ManualResult 'Deny and then grant relevant location, camera and notification permissions; confirm safe recovery'); Note = '' },
    [PSCustomObject]@{ Area = 'Camera'; Required = $false; Result = (Read-ManualResult 'Exercise the authorized QR/camera flow and confirm cancellation and retry behavior' -AllowNotApplicable); Note = '' },
    [PSCustomObject]@{ Area = 'Downloads'; Required = $true; Result = (Read-ManualResult 'Download and open an Academic Repository resource; confirm progress, filename and viewer behavior'); Note = '' },
    [PSCustomObject]@{ Area = 'Notifications'; Required = $true; Result = (Read-ManualResult 'Open an EduCore notification and confirm the permitted destination and unread badge synchronize'); Note = '' },
    [PSCustomObject]@{ Area = 'Poor network'; Required = $true; Result = (Read-ManualResult 'Test slow/offline/reconnected states; confirm cached reads, visible pending writes and safe retry'); Note = '' }
)

if (-not $NonInteractive) {
    foreach ($test in @($manualTests | Where-Object Result -eq 'FAIL')) {
        $test.Note = (Read-Host "Briefly describe the $($test.Area) failure").Trim()
    }
}

$failed = @($manualTests | Where-Object Result -eq 'FAIL')
$notRun = @($manualTests | Where-Object Result -eq 'NOT_RUN')
$invalidNotApplicable = @($manualTests | Where-Object { $_.Required -and $_.Result -eq 'NOT_APPLICABLE' })
$evidenceStatus = if ($failed.Count -gt 0) {
    'FAILED'
} elseif (($notRun.Count + $invalidNotApplicable.Count) -gt 0) {
    'INCOMPLETE'
} else {
    'PASSED'
}

$deviceRecord = [PSCustomObject]@{
    captured_at = (Get-Date).ToString('o')
    profile = $Profile
    serial = $Serial
    manufacturer = $manufacturer
    model = $model
    android_version = $androidVersion
    api_level = $apiLevel
    display = "${width}x${height}"
    density_dpi = $density
    estimated_diagonal_inches = $diagonalInches
    memory_gb = $memoryGb
    cold_start_total_ms = if ($coldTotal) { [int]$coldTotal } else { $null }
    warm_start_total_ms = if ($warmTotal) { [int]$warmTotal } else { $null }
    cold_start_timing_source = $coldTimingSource
    warm_start_timing_source = $warmTimingSource
    status = $evidenceStatus
    tests = $manualTests
}
$deviceRecord | ConvertTo-Json -Depth 6 | Set-Content -LiteralPath (Join-Path $evidenceDirectory 'result.json') -Encoding UTF8

$summary = @(
    "# Phase 17 device evidence - $Profile",
    '',
    "- Captured: $($deviceRecord.captured_at)",
    "- Device: $manufacturer $model ($Serial)",
    "- Android: $androidVersion (API $apiLevel)",
    "- Display: ${width}x${height} at $density dpi; estimated $diagonalInches inches",
    "- Memory: $memoryGb GB",
    "- Cold startup: $coldTotal ms ($coldTimingSource)",
    "- Warm startup: $warmTotal ms ($warmTimingSource)",
    "- Evidence status: $evidenceStatus",
    '',
    '| Test | Required | Result | Note |',
    '|---|---|---|---|'
)
$summary += $manualTests | ForEach-Object {
    $note = ($_.Note -replace '[|\r\n]+', ' ').Trim()
    "| $($_.Area) | $($_.Required) | $($_.Result) | $note |"
}
$summary -join "`r`n" | Set-Content -LiteralPath (Join-Path $evidenceDirectory 'SUMMARY.md') -Encoding UTF8

Write-Host "Device evidence saved to $evidenceDirectory"
Write-Host "Cold startup: $coldTotal ms ($coldTimingSource); warm startup: $warmTotal ms ($warmTimingSource)"
if ($failed.Count -gt 0) {
    Write-Error "Phase 17 profile '$Profile' failed $($failed.Count) manual check(s)."
    exit 1
}
if (($notRun.Count + $invalidNotApplicable.Count) -gt 0) {
    Write-Warning "Phase 17 profile '$Profile' evidence is incomplete because required checks remain untested."
    exit 2
}
Write-Host "Phase 17 profile '$Profile' passed." -ForegroundColor Green
