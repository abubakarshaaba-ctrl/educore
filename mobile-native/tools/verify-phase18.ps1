param(
    [string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr',
    [string]$AndroidHome = "$env:LOCALAPPDATA\Android\Sdk",
    [string]$PhpPath = 'C:\xampp\php\php.exe',
    [string]$ReleasePropertiesPath,
    [string]$LegacyApkPath,
    [switch]$PreflightOnly,
    [switch]$SkipServerTests
)

$ErrorActionPreference = 'Stop'
$projectRoot = Split-Path -Parent $PSScriptRoot
$repositoryRoot = Split-Path -Parent $projectRoot
$laravelRoot = Join-Path $repositoryRoot 'educore'
$productionApplicationId = 'online.educoreng.educore'
$previousVersionCode = 14
$productionApi = 'https://educoreng.online/api/v1/'

if ([string]::IsNullOrWhiteSpace($ReleasePropertiesPath)) {
    $ReleasePropertiesPath = Join-Path $repositoryRoot 'mobile\android\key.properties'
}
if ([string]::IsNullOrWhiteSpace($LegacyApkPath)) {
    $LegacyApkPath = Join-Path $laravelRoot 'public\downloads\EduCore.apk'
}
$ReleasePropertiesPath = [System.IO.Path]::GetFullPath($ReleasePropertiesPath)
$LegacyApkPath = [System.IO.Path]::GetFullPath($LegacyApkPath)

foreach ($required in @(
    (Join-Path $JavaHome 'bin\java.exe'),
    $AndroidHome,
    $PhpPath,
    $ReleasePropertiesPath,
    $LegacyApkPath
)) {
    if (-not (Test-Path -LiteralPath $required)) { throw "Required release dependency not found: $required" }
}

$buildTools = Get-ChildItem -LiteralPath (Join-Path $AndroidHome 'build-tools') -Directory |
    Sort-Object Name -Descending |
    Select-Object -First 1
if (-not $buildTools) { throw 'Android SDK build-tools were not found.' }
$apkSigner = Join-Path $buildTools.FullName 'apksigner.bat'
$aapt = Join-Path $buildTools.FullName 'aapt.exe'
$zipAlign = Join-Path $buildTools.FullName 'zipalign.exe'
foreach ($tool in @($apkSigner, $aapt, $zipAlign)) {
    if (-not (Test-Path -LiteralPath $tool)) { throw "Android release tool not found: $tool" }
}

function Get-SignerSha256 {
    param([Parameter(Mandatory = $true)][string]$Apk)
    $previousErrorActionPreference = $ErrorActionPreference
    try {
        # Newer JDKs print a native-access compatibility warning to stderr even
        # when apksigner succeeds. Capture it without allowing PowerShell to
        # convert that informational line into a terminating NativeCommandError.
        $ErrorActionPreference = 'Continue'
        $output = @(& $apkSigner verify --print-certs $Apk 2>&1)
        $exitCode = $LASTEXITCODE
    } finally {
        $ErrorActionPreference = $previousErrorActionPreference
    }
    if ($exitCode -ne 0) { throw "APK signature verification failed for $Apk.`n$($output -join "`n")" }
    $match = $output | Select-String -Pattern 'certificate SHA-256 digest:\s*([a-fA-F0-9]+)' | Select-Object -First 1
    if (-not $match) { throw "No SHA-256 signing certificate was found in $Apk." }
    return $match.Matches[0].Groups[1].Value.ToLowerInvariant()
}

function Get-ApkIdentity {
    param([Parameter(Mandatory = $true)][string]$Apk)
    $badging = @(& $aapt dump badging $Apk 2>&1)
    if ($LASTEXITCODE -ne 0) { throw "Unable to inspect APK identity for $Apk.`n$($badging -join "`n")" }
    $packageLine = $badging | Where-Object { $_ -match "^package: name='" } | Select-Object -First 1
    $match = [regex]::Match($packageLine, "name='([^']+)'\s+versionCode='(\d+)'\s+versionName='([^']+)'" )
    if (-not $match.Success) { throw "Unable to parse APK package metadata: $packageLine" }
    return [PSCustomObject]@{
        PackageName = $match.Groups[1].Value
        VersionCode = [int]$match.Groups[2].Value
        VersionName = $match.Groups[3].Value
        Debuggable = [bool]($badging -match '^application-debuggable')
    }
}

$releasePropertyKeys = @(
    Get-Content -LiteralPath $ReleasePropertiesPath |
        Where-Object { $_ -match '^[^#=]+=' } |
        ForEach-Object { ($_ -split '=', 2)[0].Trim() }
)
foreach ($requiredKey in @('storeFile', 'storePassword', 'keyAlias', 'keyPassword')) {
    if ($requiredKey -notin $releasePropertyKeys) { throw "Signing configuration is missing required key '$requiredKey'." }
}

$buildFile = Get-Content -LiteralPath (Join-Path $projectRoot 'app\build.gradle.kts') -Raw
$manifestFile = Get-Content -LiteralPath (Join-Path $projectRoot 'app\src\main\AndroidManifest.xml') -Raw
$networkSecurity = Get-Content -LiteralPath (Join-Path $projectRoot 'app\src\main\res\xml\network_security_config.xml') -Raw
if ($buildFile -notmatch 'applicationId\s*=\s*"online\.educoreng\.educore"') { throw 'Production application ID is not preserved.' }
if ($buildFile -notmatch [regex]::Escape($productionApi)) { throw 'Production API base URL is missing from build configuration.' }
if ($buildFile -notmatch 'isMinifyEnabled\s*=\s*true' -or $buildFile -notmatch 'isShrinkResources\s*=\s*true') {
    throw 'Release R8 and resource shrinking must remain enabled.'
}
foreach ($requiredManifestSetting in @('android:allowBackup="false"', 'android:usesCleartextTraffic="false"')) {
    if (-not $manifestFile.Contains($requiredManifestSetting)) { throw "Missing release manifest safeguard: $requiredManifestSetting" }
}
if ($networkSecurity -notmatch 'cleartextTrafficPermitted="false"') { throw 'Network security configuration permits cleartext traffic.' }
foreach ($forbiddenPermission in @('READ_EXTERNAL_STORAGE', 'WRITE_EXTERNAL_STORAGE', 'MANAGE_EXTERNAL_STORAGE')) {
    if ($manifestFile.Contains($forbiddenPermission)) { throw "Forbidden broad storage permission found: $forbiddenPermission" }
}

$legacyMigration = Get-Content -LiteralPath (
    Join-Path $projectRoot 'core\security\src\main\java\online\educoreng\educore\core\security\AndroidKeystoreTokenVault.kt'
) -Raw
foreach ($requiredMigrationToken in @('FlutterSharedPreferences', 'flutter.token', 'encryptAndPersist', 'clearLegacySession')) {
    if (-not $legacyMigration.Contains($requiredMigrationToken)) { throw "Legacy secure-session migration is incomplete: $requiredMigrationToken" }
}

$firebasePath = Join-Path $repositoryRoot 'mobile\android\app\google-services.json'
if (-not (Test-Path -LiteralPath $firebasePath)) { throw 'Approved legacy Firebase configuration is missing.' }
$firebase = Get-Content -LiteralPath $firebasePath -Raw | ConvertFrom-Json
$firebasePackages = @($firebase.client.client_info.android_client_info.package_name)
if ($productionApplicationId -notin $firebasePackages) { throw 'The production application ID is not registered in the approved Firebase configuration.' }

$legacySigner = Get-SignerSha256 -Apk $LegacyApkPath
$legacyIdentity = Get-ApkIdentity -Apk $LegacyApkPath
if ($legacyIdentity.PackageName -ne $productionApplicationId) { throw "Unexpected legacy package: $($legacyIdentity.PackageName)" }
if ($legacyIdentity.VersionCode -ne $previousVersionCode) {
    throw "Expected approved legacy versionCode $previousVersionCode but found $($legacyIdentity.VersionCode)."
}
if ($PreflightOnly) {
    Write-Host 'Phase 18 preflight passed: identity, legacy signature, signing properties, Firebase, API, R8, manifest and secure-session migration are ready.' -ForegroundColor Green
    Write-Host "Legacy signing certificate SHA-256: $legacySigner"
    return
}

$env:JAVA_HOME = $JavaHome
$env:ANDROID_HOME = $AndroidHome
$env:ANDROID_SDK_ROOT = $AndroidHome

Push-Location $projectRoot
try {
    & .\gradlew.bat `
        --no-daemon `
        --no-parallel `
        "-PEDUCORE_RELEASE_PROPERTIES=$ReleasePropertiesPath" `
        :app:lintRelease `
        :app:assembleRelease
    if ($LASTEXITCODE -ne 0) { throw "Signed release-candidate build failed with exit code $LASTEXITCODE." }
} finally { Pop-Location }

$builtApk = Join-Path $projectRoot 'app\build\outputs\apk\release\app-release.apk'
if (-not (Test-Path -LiteralPath $builtApk)) { throw 'Gradle completed without producing the expected release APK.' }

& $zipAlign -c -P 16 -v 4 $builtApk | Out-Null
if ($LASTEXITCODE -ne 0) { throw 'Release APK zip alignment verification failed.' }
$candidateSigner = Get-SignerSha256 -Apk $builtApk
if ($legacySigner -ne $candidateSigner) { throw 'Release candidate signing certificate does not match the installed production APK.' }

$candidateIdentity = Get-ApkIdentity -Apk $builtApk
if ($candidateIdentity.PackageName -ne $productionApplicationId) { throw "Unexpected release package: $($candidateIdentity.PackageName)" }
if ($candidateIdentity.VersionCode -le $legacyIdentity.VersionCode -or $candidateIdentity.VersionCode -le $previousVersionCode) {
    throw "Release versionCode $($candidateIdentity.VersionCode) does not safely upgrade versionCode $($legacyIdentity.VersionCode)."
}
if ($candidateIdentity.Debuggable) { throw 'Release candidate is debuggable.' }

if (-not $SkipServerTests) {
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
        if ($LASTEXITCODE -ne 0) { throw "Release backend compatibility tests failed with exit code $LASTEXITCODE." }
    } finally { Pop-Location }
}

$candidateDirectory = Join-Path $projectRoot '.codex-release'
New-Item -ItemType Directory -Path $candidateDirectory -Force | Out-Null
$safeVersion = $candidateIdentity.VersionName -replace '[^A-Za-z0-9._-]', '_'
$candidateName = "EduCore-$safeVersion-v$($candidateIdentity.VersionCode)-candidate.apk"
$candidatePath = Join-Path $candidateDirectory $candidateName
Copy-Item -LiteralPath $builtApk -Destination $candidatePath -Force
$mappingSource = Join-Path $projectRoot 'app\build\outputs\mapping\release\mapping.txt'
$mappingPath = $null
if (Test-Path -LiteralPath $mappingSource) {
    $mappingPath = Join-Path $candidateDirectory "EduCore-$safeVersion-v$($candidateIdentity.VersionCode)-mapping.txt"
    Copy-Item -LiteralPath $mappingSource -Destination $mappingPath -Force
}
$apkHash = (Get-FileHash -LiteralPath $candidatePath -Algorithm SHA256).Hash.ToLowerInvariant()
$manifest = [PSCustomObject]@{
    created_at = (Get-Date).ToString('o')
    status = 'LOCAL_QA_CANDIDATE_NOT_AUTHORIZED_FOR_PUBLICATION'
    artifact = $candidatePath
    mapping = $mappingPath
    sha256 = $apkHash
    signing_certificate_sha256 = $candidateSigner
    application_id = $candidateIdentity.PackageName
    version_code = $candidateIdentity.VersionCode
    version_name = $candidateIdentity.VersionName
    legacy_version_code = $legacyIdentity.VersionCode
    legacy_version_name = $legacyIdentity.VersionName
    api_base_url = $productionApi
    firebase_package_match = $true
    server_tests_skipped = [bool]$SkipServerTests
}
$manifestPath = Join-Path $candidateDirectory 'release-candidate-manifest.json'
$manifest | ConvertTo-Json -Depth 5 | Set-Content -LiteralPath $manifestPath -Encoding UTF8
"$apkHash  $candidateName" | Set-Content -LiteralPath "$candidatePath.sha256" -Encoding Ascii

Write-Host "Release candidate verified: $candidatePath" -ForegroundColor Green
Write-Host "SHA-256: $apkHash"
Write-Host "Signing certificate matches production: $candidateSigner"
if ($candidateIdentity.VersionName -match '(?i)(alpha|beta|rc)') {
    Write-Warning "Version name '$($candidateIdentity.VersionName)' is a pre-release label. Keep this candidate in QA until the final version policy is approved."
}
Write-Warning 'The candidate has not been published and the production EduCore.apk has not been replaced.'
