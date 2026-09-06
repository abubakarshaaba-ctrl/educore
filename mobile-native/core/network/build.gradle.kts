plugins {
    alias(libs.plugins.android.library)
}

android {
    namespace = "online.educoreng.educore.core.network"
    compileSdk = 36
    defaultConfig { minSdk = 23 }
    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
    }
}

dependencies {
    api(project(":core:common"))
    api(project(":core:model"))
    implementation(libs.retrofit.core)
    implementation(libs.retrofit.moshi)
    api(libs.okhttp.core)
    implementation(libs.okhttp.logging)
    implementation(libs.moshi.core)
    implementation(libs.moshi.kotlin)
    implementation(libs.kotlinx.coroutines.core)
    testImplementation(libs.junit)
}
