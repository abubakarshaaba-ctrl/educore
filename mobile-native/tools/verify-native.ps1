param(
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk",
    [string]$PhpPath = 'C:\xampp\php\php.exe',
    [string]$ReleasePropertiesPath,
    [string]$ProductionApkPath,
    [switch]$Release,
    [switch]$SkipServerTests,
    [switch]$PreflightOnly
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$repositoryRoot = Split-Path -Parent $projectRoot
$laravelRoot = Join-Path $repositoryRoot 'educore'
$productionApplicationId = 'online.educoreng.educore'
$productionApi = 'https://educoreng.online/api/v1/'

if ([string]::IsNullOrWhiteSpace($ReleasePropertiesPath)) {
    $ReleasePropertiesPath = Join-Path $repositoryRoot 'mobile\android\key.properties'
}
if ([string]::IsNullOrWhiteSpace($ProductionApkPath)) {
    $ProductionApkPath = Join-Path $laravelRoot 'public\downloads\EduCore.apk'
}

function Assert-Exists {
    param([Parameter(Mandatory = $true)][string]$Path, [Parameter(Mandatory = $true)][string]$Label)
    if (-not (Test-Path -LiteralPath $Path)) { throw "$Label not found: $Path" }
}

function Invoke-Checked {
    param(
        [Parameter(Mandatory = $true)][scriptblock]$Command,
        [Parameter(Mandatory = $true)][string]$FailureMessage
    )
    & $Command
    if ($LASTEXITCODE -ne 0) { throw "$FailureMessage (exit code $LASTEXITCODE)." }
}

Assert-Exists (Join-Path $JavaHome 'bin\java.exe') 'Java runtime'
Assert-Exists $AndroidHome 'Android SDK'
Assert-Exists $PhpPath 'PHP executable'
Assert-Exists (Join-Path $projectRoot 'gradlew.bat') 'Gradle wrapper'
Assert-Exists (Join-Path $projectRoot 'settings.gradle.kts') 'Gradle settings'
Assert-Exists (Join-Path $projectRoot 'app\build.gradle.kts') 'Android app build file'

$env:JAVA_HOME = $JavaHome
$env:ANDROID_HOME = $AndroidHome
$env:ANDROID_SDK_ROOT = $AndroidHome

$buildFilePath = Join-Path $projectRoot 'app\build.gradle.kts'
$manifestPath = Join-Path $projectRoot 'app\src\main\AndroidManifest.xml'
$networkSecurityPath = Join-Path $projectRoot 'app\src\main\res\xml\network_security_config.xml'
$buildFile = Get-Content -LiteralPath $buildFilePath -Raw
$manifestFile = Get-Content -LiteralPath $manifestPath -Raw
$networkSecurity = Get-Content -LiteralPath $networkSecurityPath -Raw

if ($buildFile -notmatch 'applicationId\s*=\s*"online\.educoreng\.educore"') { throw 'Production application ID is not preserved.' }
if ($buildFile -notmatch [regex]::Escape($productionApi)) { throw 'Production API base URL is missing from build configuration.' }
if ($buildFile -notmatch 'compileSdk\s*=\s*36') { throw 'compileSdk 36 is required.' }
if ($buildFile -notmatch 'targetSdk\s*=\s*36') { throw 'targetSdk 36 is required.' }
if ($buildFile -notmatch 'isMinifyEnabled\s*=\s*true' -or $buildFile -notmatch 'isShrinkResources\s*=\s*true') {
    throw 'Release R8 and resource shrinking must remain enabled.'
}
foreach ($requiredManifestSetting in @('android:allowBackup="false"', 'android:usesCleartextTraffic="false"')) {
    if (-not $manifestFile.Contains($requiredManifestSetting)) { throw "Missing Android manifest safeguard: $requiredManifestSetting" }
}
if ($networkSecurity -notmatch 'cleartextTrafficPermitted="false"') { throw 'Network security configuration permits cleartext traffic.' }
foreach ($forbiddenPermission in @('READ_EXTERNAL_STORAGE', 'WRITE_EXTERNAL_STORAGE', 'MANAGE_EXTERNAL_STORAGE')) {
    if ($manifestFile.Contains($forbiddenPermission)) { throw "Forbidden broad storage permission found: $forbiddenPermission" }
}

$settingsFile = Get-Content -LiteralPath (Join-Path $projectRoot 'settings.gradle.kts') -Raw
foreach ($module in @(':app', ':core:common', ':core:designsystem', ':core:model', ':core:network', ':core:security', ':core:data')) {
    if (-not $settingsFile.Contains("include(`"$module`")")) { throw "Gradle module is missing: $module" }
}

$staffShell = Get-Content -LiteralPath (
    Join-Path $projectRoot 'app\src\main\java\online\educoreng\educore\presentation\StaffWorkspaceShell.kt'
) -Raw
if ($staffShell.Contains('ACTION_VIEW') -or $staffShell.Contains('onOpenWebModule')) {
    throw 'StaffWorkspaceShell contains a generic browser fallback. Staff mobile routing must remain explicitly native/RBAC-scoped.'
}

$navigationPolicy = Get-Content -LiteralPath (
    Join-Path $projectRoot 'app\src\main\java\online\educoreng\educore\presentation\ShellNavigationPolicy.kt'
) -Raw
foreach ($marker in @('"cbt"', '"cbt-exams"', '"examinations"', '"student.exams"')) {
    if (-not $navigationPolicy.Contains($marker)) { throw "Removed CBT module key is not covered by mobile navigation policy: $marker" }
}

$noteReader = Get-Content -LiteralPath (
    Join-Path $projectRoot 'app\src\main\java\online\educoreng\educore\presentation\AcademicRepositoryScreens.kt'
) -Raw
foreach ($marker in @('parseAcademicNote', 'AcademicNoteBlock.Heading', 'AcademicNoteBlock.Bullet', 'AcademicNoteBlock.Numbered')) {
    if (-not $noteReader.Contains($marker)) { throw "Standard academic-note rendering marker is missing: $marker" }
}

$retired = @(
    'app\src\main\java\online\educoreng\educore\presentation\StaffAuthorizedShell.kt',
    'app\src\main\java\online\educoreng\educore\presentation\StaffAuthorizedShowcaseShell.kt',
    'app\src\main\java\online\educoreng\educore\presentation\ShowcaseStaffScreens.kt',
    'app\src\main\java\online\educoreng\educore\presentation\ShowcaseClassesScreen.kt',
    'app\src\main\java\online\educoreng\educore\presentation\ShowcaseAcademicResourceDetailScreen.kt',
    'app\src\main\java\online\educoreng\educore\presentation\CbtScreens.kt',
    'app\src\main\java\online\educoreng\educore\presentation\CbtViewModel.kt',
    'app\src\test\java\online\educoreng\educore\presentation\CbtImageSizingTest.kt',
    'app\src\test\java\online\educoreng\educore\presentation\CbtQuestionUnitsTest.kt',
    'core\data\src\main\java\online\educoreng\educore\core\data\repository\CbtRepository.kt',
    'core\data\src\main\java\online\educoreng\educore\core\data\repository\DefaultCbtRepository.kt',
    'core\model\src\main\java\online\educoreng\educore\core\model\CbtModels.kt',
    'core\network\src\main\java\online\educoreng\educore\core\network\dto\CbtDtos.kt',
    'core\network\src\test\java\online\educoreng\educore\core\network\CbtDtoMapperTest.kt'
)
foreach ($relative in $retired) {
    if (Test-Path -LiteralPath (Join-Path $projectRoot $relative)) { throw "Retired native source has reappeared: $relative" }
}

$conflictMarkers = Get-ChildItem -LiteralPath $projectRoot -Recurse -File -Include *.kt,*.kts,*.xml,*.properties |
    Select-String -Pattern '^(<<<<<<< |>>>>>>> )' -List
if ($conflictMarkers) {
    throw "Unresolved merge conflict marker found in: $($conflictMarkers.Path -join ', ')"
}

if ($PreflightOnly) {
    Write-Host 'EduCore native preflight passed.' -ForegroundColor Green
    return
}

Push-Location $projectRoot
try {
    Invoke-Checked -FailureMessage 'Native debug validation failed' -Command {
        & .\gradlew.bat --no-daemon --no-parallel `
            :core:common:testDebugUnitTest `
            :core:designsystem:testDebugUnitTest `
            :core:model:testDebugUnitTest `
            :core:network:testDebugUnitTest `
            :core:security:testDebugUnitTest `
            :core:data:testDebugUnitTest `
            :app:testDebugUnitTest `
            :app:lintDebug `
            :app:assembleDebug
    }
} finally { Pop-Location }

if (-not $SkipServerTests) {
    $serverTests = @(
        'tests/Feature/MobileBootstrapTest.php',
        'tests/Feature/MobileClassWorkspaceTest.php',
        'tests/Feature/MobileScoresAndResultsTest.php',
        'tests/Feature/MobileScheduleTest.php',
        'tests/Feature/MobileCommunicationTest.php',
        'tests/Feature/MobileOperationsTest.php',
        'tests/Feature/MobileLessonRepositoryTest.php',
        'tests/Feature/MobileStaffCbtTest.php',
        'tests/Feature/AcademicRepositoryReaderTest.php',
        'tests/Feature/CbtRestructuringTest.php',
        'tests/Feature/PushRegistrationTest.php',
        'tests/Feature/TenantAccessEnforcementTest.php'
    ) | Where-Object { Test-Path -LiteralPath (Join-Path $laravelRoot $_) }

    if ($serverTests.Count -eq 0) { throw 'No mobile/backend compatibility tests were found.' }
    Push-Location $laravelRoot
    try {
        & $PhpPath artisan test @serverTests
        if ($LASTEXITCODE -ne 0) { throw "Mobile backend compatibility tests failed with exit code $LASTEXITCODE." }
    } finally { Pop-Location }
}

$debugApk = Join-Path $projectRoot 'app\build\outputs\apk\debug\app-debug.apk'
Assert-Exists $debugApk 'Debug APK'
Write-Host "Debug validation passed: $debugApk" -ForegroundColor Green

if (-not $Release) {
    Write-Host 'Release verification skipped. Use -Release only when signing configuration and the currently published EduCore.apk are available.' -ForegroundColor Yellow
    return
}

$ReleasePropertiesPath = [System.IO.Path]::GetFullPath($ReleasePropertiesPath)
$ProductionApkPath = [System.IO.Path]::GetFullPath($ProductionApkPath)
Assert-Exists $ReleasePropertiesPath 'Release signing properties'
Assert-Exists $ProductionApkPath 'Currently published production APK'

$buildTools = Get-ChildItem -LiteralPath (Join-Path $AndroidHome 'build-tools') -Directory |
    Sort-Object { [version]$_.Name } -Descending |
    Select-Object -First 1
if (-not $buildTools) { throw 'Android SDK build-tools were not found.' }
$apkSigner = Join-Path $buildTools.FullName 'apksigner.bat'
$aapt = Join-Path $buildTools.FullName 'aapt.exe'
$zipAlign = Join-Path $buildTools.FullName 'zipalign.exe'
foreach ($tool in @($apkSigner, $aapt, $zipAlign)) { Assert-Exists $tool 'Android release tool' }

function Get-SignerSha256 {
    param([Parameter(Mandatory = $true)][string]$Apk)
    $previousPreference = $ErrorActionPreference
    try {
        $ErrorActionPreference = 'Continue'
        $output = @(& $apkSigner verify --print-certs $Apk 2>&1)
        $exitCode = $LASTEXITCODE
    } finally { $ErrorActionPreference = $previousPreference }
    if ($exitCode -ne 0) { throw "APK signature verification failed for $Apk.`n$($output -join "`n")" }
    $match = $output | Select-String -Pattern 'certificate SHA-256 digest:\s*([a-fA-F0-9]+)' | Select-Object -First 1
    if (-not $match) { throw "No SHA-256 signing certificate was found in $Apk." }
    $match.Matches[0].Groups[1].Value.ToLowerInvariant()
}

function Get-ApkIdentity {
    param([Parameter(Mandatory = $true)][string]$Apk)
    $badging = @(& $aapt dump badging $Apk 2>&1)
    if ($LASTEXITCODE -ne 0) { throw "Unable to inspect APK identity for $Apk.`n$($badging -join "`n")" }
    $packageLine = $badging | Where-Object { $_ -match "^package: name='" } | Select-Object -First 1
    $match = [regex]::Match($packageLine, "name='([^']+)'\s+versionCode='(\d+)'\s+versionName='([^']+)'" )
    if (-not $match.Success) { throw "Unable to parse APK package metadata: $packageLine" }
    [PSCustomObject]@{
        PackageName = $match.Groups[1].Value
        VersionCode = [int]$match.Groups[2].Value
        VersionName = $match.Groups[3].Value
        Debuggable = [bool]($badging -match '^application-debuggable')
    }
}

$releasePropertyKeys = @(Get-Content -LiteralPath $ReleasePropertiesPath |
    Where-Object { $_ -match '^[^#=]+=' } |
    ForEach-Object { ($_ -split '=', 2)[0].Trim() })
foreach ($requiredKey in @('storeFile', 'storePassword', 'keyAlias', 'keyPassword')) {
    if ($requiredKey -notin $releasePropertyKeys) { throw "Signing configuration is missing required key '$requiredKey'." }
}

$productionIdentity = Get-ApkIdentity -Apk $ProductionApkPath
$productionSigner = Get-SignerSha256 -Apk $ProductionApkPath
if ($productionIdentity.PackageName -ne $productionApplicationId) {
    throw "Unexpected package in currently published APK: $($productionIdentity.PackageName)"
}

Push-Location $projectRoot
try {
    & .\gradlew.bat --no-daemon --no-parallel `
        "-PEDUCORE_RELEASE_PROPERTIES=$ReleasePropertiesPath" `
        :app:lintRelease `
        :app:assembleRelease
    if ($LASTEXITCODE -ne 0) { throw "Signed release candidate build failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }

$builtApk = Join-Path $projectRoot 'app\build\outputs\apk\release\app-release.apk'
Assert-Exists $builtApk 'Signed release APK'
& $zipAlign -c -P 16 -v 4 $builtApk | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Release APK zip-alignment verification failed.' }

$candidateIdentity = Get-ApkIdentity -Apk $builtApk
$candidateSigner = Get-SignerSha256 -Apk $builtApk
if ($candidateIdentity.PackageName -ne $productionApplicationId) { throw "Unexpected release package: $($candidateIdentity.PackageName)" }
if ($candidateIdentity.VersionCode -le $productionIdentity.VersionCode) {
    throw "Release versionCode $($candidateIdentity.VersionCode) must be greater than currently published versionCode $($productionIdentity.VersionCode)."
}
if ($candidateIdentity.Debuggable) { throw 'Release candidate is debuggable.' }
if ($candidateSigner -ne $productionSigner) { throw 'Release candidate signing certificate does not match the currently published EduCore APK.' }

$candidateDirectory = Join-Path $projectRoot '.release-candidate'
New-Item -ItemType Directory -Path $candidateDirectory -Force | Out-Null
$safeVersion = $candidateIdentity.VersionName -replace '[^A-Za-z0-9._-]', '_'
$candidateName = "EduCore-$safeVersion-v$($candidateIdentity.VersionCode)-candidate.apk"
$candidatePath = Join-Path $candidateDirectory $candidateName
Copy-Item -LiteralPath $builtApk -Destination $candidatePath -Force
$apkHash = (Get-FileHash -LiteralPath $candidatePath -Algorithm SHA256).Hash.ToLowerInvariant()
$manifest = [PSCustomObject]@{
    created_at = (Get-Date).ToString('o')
    status = 'LOCAL_QA_CANDIDATE_NOT_AUTHORIZED_FOR_PUBLICATION'
    artifact = $candidatePath
    sha256 = $apkHash
    signing_certificate_sha256 = $candidateSigner
    application_id = $candidateIdentity.PackageName
    version_code = $candidateIdentity.VersionCode
    version_name = $candidateIdentity.VersionName
    production_version_code = $productionIdentity.VersionCode
    production_version_name = $productionIdentity.VersionName
    api_base_url = $productionApi
    server_tests_skipped = [bool]$SkipServerTests
}
$manifest | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath (Join-Path $candidateDirectory 'release-candidate-manifest.json') -Encoding UTF8
"$apkHash  $candidateName" | Set-Content -LiteralPath "$candidatePath.sha256" -Encoding Ascii

Write-Host "Release candidate verified: $candidatePath" -ForegroundColor Green
Write-Host "Upgrade: v$($productionIdentity.VersionCode) -> v$($candidateIdentity.VersionCode)"
Write-Host "SHA-256: $apkHash"
Write-Warning 'The candidate has not been published and the production EduCore.apk has not been replaced.'
