# EduCore Native Android builds without GitHub Actions

GitHub Actions is optional for the EduCore native Android application.

The primary cloud fallback is Codemagic using the repository-root `codemagic.yaml`. The workflow builds the Kotlin/Jetpack Compose application under `mobile-native/`, runs unit tests and Android lint, and produces a debug APK. If protected signing variables are configured, the same workflow also produces a signed release APK and SHA-256 checksum.

## Codemagic setup

1. Sign in to Codemagic with the repository owner account.
2. Add the `abubakarshaaba-ctrl/educore` repository.
3. Select configuration from `codemagic.yaml`.
4. Run the `native-android-validation` workflow against `mobile-overhaul`.
5. Download `app-debug.apk` from the build artifacts after a successful validation build.

## Optional signed release

Create protected/secret environment variables in Codemagic with these exact names:

- `KEYSTORE_BASE64`
- `KEYSTORE_STORE_PASSWORD`
- `KEYSTORE_KEY_PASSWORD`
- `KEYSTORE_KEY_ALIAS`

`KEYSTORE_BASE64` must contain the base64 representation of the existing EduCore production signing keystore. Never commit the keystore, passwords, aliases or generated `release.properties` file to GitHub.

When all four variables are available, the workflow creates a temporary signing configuration inside `.ci-signing/`, runs release lint and `assembleRelease`, and publishes:

- `mobile-native/app/build/outputs/apk/release/app-release.apk`
- `mobile-native/app/build/outputs/apk/release/app-release.apk.sha256`

If the signing variables are absent, the workflow deliberately succeeds as a debug validation build instead of fabricating an unsigned production APK.

## Local Windows fallback

A cloud service is not required for development builds. From PowerShell at the repository root, run:

```powershell
powershell -ExecutionPolicy Bypass -File .\mobile-native\tools\verify-native.ps1 -SkipServerTests
```

To include Laravel mobile compatibility tests, omit `-SkipServerTests`. The script expects Android Studio's bundled Java runtime, the standard Android SDK location and XAMPP PHP by default; all three paths can be supplied as parameters when installed elsewhere.

For release verification, provide the existing production signing properties explicitly:

```powershell
powershell -ExecutionPolicy Bypass -File .\mobile-native\tools\verify-native.ps1 `
  -Release `
  -ReleasePropertiesPath "C:\secure\educore\release.properties" `
  -ProductionApkPath "C:\secure\educore\current-production.apk"
```

Release verification must preserve the production package name and signing certificate and must use a version code greater than the currently published APK.

## Repository policy

- `mobile-native/` is the only maintained mobile application source tree.
- The former Flutter `mobile/` project has been retired and removed.
- Native Firebase configuration is owned by `mobile-native/app/firebase/google-services.json`; generated debug/release copies are ignored by Git.
- `/deploy/validate-source` remains available for source-contract checks that do not execute Gradle.
- `/deploy/validate-android` and its GitHub-Actions-specific validator have been retired.
- A source-only check is never equivalent to a successful compile, lint, test and signed APK verification.
