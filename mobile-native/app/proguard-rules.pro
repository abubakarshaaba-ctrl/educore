-keepattributes Signature,*Annotation*
-dontwarn javax.annotation.**

# Retrofit reads service annotations at runtime.
-keepclasseswithmembers,allowshrinking,allowobfuscation interface * {
    @retrofit2.http.* <methods>;
}

# Moshi reflects over API DTO constructors in the foundation build.
-keep class online.educoreng.educore.core.network.dto.** { *; }
