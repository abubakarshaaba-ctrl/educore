# EduCore Native Android

This is the incremental Kotlin and Jetpack Compose replacement for the current
Flutter client in `../mobile`. The Flutter application remains intact as the
rollback implementation until the native client passes release acceptance.

## Build variants

- `debug` installs as `online.educoreng.educore.nativepreview` so it can coexist
  with the production app during validation.
- `release` retains `online.educoreng.educore` for Play upgrade continuity, but
  cannot be published until production signing and migration checks are complete.

## Local build

Use JDK 17 and Android SDK 36:

```powershell
.\gradlew.bat :app:assembleDebug
```

The default API endpoint is `https://educoreng.online/api/v1/`. Override it for
local development with the Gradle property `EDUCORE_API_BASE_URL`.
