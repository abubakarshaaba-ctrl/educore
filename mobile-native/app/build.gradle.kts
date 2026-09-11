import java.util.Properties

plugins {
    alias(libs.plugins.android.application)
    alias(libs.plugins.compose.compiler)
    alias(libs.plugins.hilt)
    alias(libs.plugins.ksp)
    alias(libs.plugins.google.services)
}

val approvedFirebaseConfig = listOf(
    file("firebase/google-services.json"),
    rootProject.file("../mobile/android/app/google-services.json"),
).firstOrNull { it.exists() }

approvedFirebaseConfig?.let { firebaseConfig ->
    val releaseFirebaseConfig = file("src/release/google-services.json")
    val debugFirebaseConfig = file("src/debug/google-services.json")
    releaseFirebaseConfig.parentFile.mkdirs()
    debugFirebaseConfig.parentFile.mkdirs()
    firebaseConfig.copyTo(releaseFirebaseConfig, overwrite = true)
    debugFirebaseConfig.writeText(
        firebaseConfig.readText().replace(
            "\"package_name\": \"online.educoreng.educore\"",
            "\"package_name\": \"online.educoreng.educore.nativepreview\"",
        ),
    )
}

val releaseProperties = Properties()
val releasePropertiesFile = providers.gradleProperty("EDUCORE_RELEASE_PROPERTIES")
    .orNull
    ?.let { rootProject.file(it) }
    ?: rootProject.file("release.properties")
if (releasePropertiesFile.exists()) {
    releasePropertiesFile.inputStream().use(releaseProperties::load)
}

val apiBaseUrl = providers.gradleProperty("EDUCORE_API_BASE_URL")
    .orElse("https://educoreng.online/api/v1/")
    .get()

gradle.taskGraph.addTaskExecutionGraphListener { _ ->
    val requestedTasks = gradle.startParameter.taskNames.map { it.substringAfterLast(':') }
    val requestsReleaseArtifact = requestedTasks.any { taskName ->
        taskName.equals("assembleRelease", ignoreCase = true) ||
            taskName.equals("bundleRelease", ignoreCase = true) ||
            taskName.startsWith("packageRelease", ignoreCase = true) ||
            taskName.startsWith("publishRelease", ignoreCase = true) ||
            taskName.equals("build", ignoreCase = true) ||
            taskName.equals("assemble", ignoreCase = true) ||
            taskName.equals("bundle", ignoreCase = true)
    }

    if (requestsReleaseArtifact && !releasePropertiesFile.exists()) {
        throw GradleException(
            "Release signing is required. Provide release.properties or set " +
                "-PEDUCORE_RELEASE_PROPERTIES to the approved protected signing configuration.",
        )
    }
}

android {
    namespace = "online.educoreng.educore"
    compileSdk = 36

    defaultConfig {
        applicationId = "online.educoreng.educore"
        minSdk = 23
        targetSdk = 36
        versionCode = 15
        versionName = "2.0.0-alpha02"

        testInstrumentationRunner = "androidx.test.runner.AndroidJUnitRunner"
        vectorDrawables.useSupportLibrary = true
        buildConfigField("String", "API_BASE_URL", "\"$apiBaseUrl\"")
    }

    signingConfigs {
        if (releasePropertiesFile.exists()) {
            create("release") {
                val configuredStore = requireNotNull(releaseProperties.getProperty("storeFile"))
                storeFile = releasePropertiesFile.parentFile.resolve(configuredStore).canonicalFile
                storePassword = requireNotNull(releaseProperties.getProperty("storePassword"))
                keyAlias = requireNotNull(releaseProperties.getProperty("keyAlias"))
                keyPassword = requireNotNull(releaseProperties.getProperty("keyPassword"))
            }
        }
    }

    buildTypes {
        debug {
            applicationIdSuffix = ".nativepreview"
            versionNameSuffix = "-debug"
            isDebuggable = true
        }
        release {
            signingConfigs.findByName("release")?.let { signingConfig = it }
            isMinifyEnabled = true
            isShrinkResources = true
            proguardFiles(
                getDefaultProguardFile("proguard-android-optimize.txt"),
                "proguard-rules.pro",
            )
        }
    }

    buildFeatures {
        compose = true
        buildConfig = true
    }

    compileOptions {
        sourceCompatibility = JavaVersion.VERSION_17
        targetCompatibility = JavaVersion.VERSION_17
        isCoreLibraryDesugaringEnabled = true
    }

    packaging.resources.excludes += "/META-INF/{AL2.0,LGPL2.1}"
}

dependencies {
    coreLibraryDesugaring(libs.desugar.jdk.libs)

    implementation(libs.moshi.core)
    implementation(libs.retrofit.core)
    implementation(libs.androidx.room.runtime)
    implementation(libs.androidx.work.runtime)
    implementation(project(":core:common"))
    implementation(project(":core:designsystem"))
    implementation(project(":core:model"))
    implementation(project(":core:network"))
    implementation(project(":core:security"))
    implementation(project(":core:data"))

    implementation(libs.androidx.core.ktx)
    implementation(libs.androidx.activity.compose)
    implementation(libs.androidx.lifecycle.runtime.ktx)
    implementation(libs.androidx.lifecycle.runtime.compose)
    implementation(libs.androidx.lifecycle.viewmodel.compose)
    implementation(libs.androidx.navigation.compose)
    implementation(libs.androidx.hilt.navigation.compose)

    implementation(platform(libs.androidx.compose.bom))
    implementation(libs.androidx.compose.ui)
    implementation(libs.androidx.compose.foundation)
    implementation(libs.androidx.compose.material3)
    implementation(libs.androidx.compose.icons)
    implementation(libs.androidx.compose.ui.tooling.preview)
    debugImplementation(libs.androidx.compose.ui.tooling)

    implementation(libs.hilt.android)
    ksp(libs.hilt.compiler)

    implementation(libs.kotlinx.coroutines.android)
    implementation(libs.zxing.embedded)
    implementation(libs.play.services.location)
    implementation(libs.firebase.messaging)

    testImplementation(libs.junit)
    androidTestImplementation(libs.androidx.junit)
    androidTestImplementation(libs.androidx.espresso.core)
    androidTestImplementation(platform(libs.androidx.compose.bom))
    androidTestImplementation(libs.androidx.compose.ui.test.junit4)
    debugImplementation(libs.androidx.compose.ui.test.manifest)
}
