-keepattributes Signature,*Annotation*
-dontwarn javax.annotation.**

# Retrofit reads service annotations at runtime.
-keepclasseswithmembers,allowshrinking,allowobfuscation interface * {
    @retrofit2.http.* <methods>;
}

# Moshi reflects over API DTO constructors at runtime. Some network response
# models (for example SubscriptionWorkspaceDto) live directly in the
# core.network package rather than the core.network.dto subpackage, so keep
# the complete network package to prevent R8 from stripping/renaming metadata
# required by Retrofit/Moshi converters in release builds.
-keep class online.educoreng.educore.core.network.** { *; }
