package online.educoreng.educore.di

import android.content.Context
import androidx.room.Room
import androidx.room.migration.Migration
import androidx.sqlite.SQLiteConnection
import androidx.work.WorkManager
import com.squareup.moshi.Moshi
import dagger.Module
import dagger.Provides
import dagger.hilt.InstallIn
import dagger.hilt.android.qualifiers.ApplicationContext
import dagger.hilt.components.SingletonComponent
import javax.inject.Singleton
import online.educoreng.educore.BuildConfig
import online.educoreng.educore.core.data.connectivity.AndroidConnectivityMonitor
import online.educoreng.educore.core.data.connectivity.ConnectivityMonitor
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.data.repository.DefaultSessionRepository
import online.educoreng.educore.core.data.repository.ClassWorkspaceRepository
import online.educoreng.educore.core.data.repository.DefaultClassWorkspaceRepository
import online.educoreng.educore.core.data.repository.DashboardRepository
import online.educoreng.educore.core.data.repository.DefaultDashboardRepository
import online.educoreng.educore.core.data.repository.SessionRepository
import online.educoreng.educore.core.data.repository.DefaultScoreWorkspaceRepository
import online.educoreng.educore.core.data.repository.ScoreWorkspaceRepository
import online.educoreng.educore.core.data.repository.DefaultScheduleRepository
import online.educoreng.educore.core.data.repository.ScheduleRepository
import online.educoreng.educore.core.data.repository.AcademicContentRepository
import online.educoreng.educore.core.data.repository.DefaultAcademicContentRepository
import online.educoreng.educore.core.data.repository.CbtRepository
import online.educoreng.educore.core.data.repository.DefaultCbtRepository
import online.educoreng.educore.core.data.repository.DefaultOperationsRepository
import online.educoreng.educore.core.data.repository.OperationsRepository
import online.educoreng.educore.core.data.repository.CommunicationRepository
import online.educoreng.educore.core.data.repository.DefaultCommunicationRepository
import online.educoreng.educore.core.data.sync.BackgroundSyncScheduler
import online.educoreng.educore.core.model.AppEnvironment
import online.educoreng.educore.core.network.ApiClientFactory
import online.educoreng.educore.core.network.AuthInterceptor
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.security.AndroidKeystoreTokenVault
import online.educoreng.educore.core.security.TokenVault

@Module
@InstallIn(SingletonComponent::class)
object FoundationModule {
    @Provides
    @Singleton
    fun provideEnvironment(): AppEnvironment = AppEnvironment(
        apiBaseUrl = BuildConfig.API_BASE_URL,
        debugLogging = BuildConfig.DEBUG,
    )

    @Provides
    @Singleton
    fun provideTokenVault(@ApplicationContext context: Context): TokenVault =
        AndroidKeystoreTokenVault(context)

    @Provides
    @Singleton
    fun provideApiClientFactory(
        environment: AppEnvironment,
        tokenVault: TokenVault,
    ): ApiClientFactory = ApiClientFactory(
        baseUrl = environment.apiBaseUrl,
        authInterceptor = AuthInterceptor(tokenVault),
        debugLogging = environment.debugLogging,
    )

    @Provides
    @Singleton
    fun provideMoshi(factory: ApiClientFactory): Moshi = factory.moshi

    @Provides
    @Singleton
    fun provideApi(factory: ApiClientFactory): EduCoreApi = factory.create()

    @Provides
    @Singleton
    fun provideDatabase(@ApplicationContext context: Context): EduCoreDatabase =
        Room.databaseBuilder(context, EduCoreDatabase::class.java, "educore-native.db")
            .addMigrations(
                DATABASE_MIGRATION_1_2,
                DATABASE_MIGRATION_2_3,
                DATABASE_MIGRATION_3_4,
                DATABASE_MIGRATION_4_5,
                DATABASE_MIGRATION_5_6,
                DATABASE_MIGRATION_6_7,
                DATABASE_MIGRATION_7_8,
            )
            .build()

    @Provides
    @Singleton
    fun provideTenantContext(@ApplicationContext context: Context): TenantContextStore =
        TenantContextStore(context)

    @Provides
    @Singleton
    fun provideConnectivity(@ApplicationContext context: Context): ConnectivityMonitor =
        AndroidConnectivityMonitor(context)

    @Provides
    @Singleton
    fun provideBackgroundSync(@ApplicationContext context: Context): BackgroundSyncScheduler =
        BackgroundSyncScheduler(WorkManager.getInstance(context))

    @Provides
    @Singleton
    fun provideSessionRepository(
        api: EduCoreApi,
        moshi: Moshi,
        tokenVault: TokenVault,
        database: EduCoreDatabase,
        tenantContextStore: TenantContextStore,
    ): SessionRepository = DefaultSessionRepository(
        api = api,
        moshi = moshi,
        tokenVault = tokenVault,
        database = database,
        tenantContextStore = tenantContextStore,
    )

    @Provides
    @Singleton
    fun provideDashboardRepository(
        api: EduCoreApi,
        moshi: Moshi,
        database: EduCoreDatabase,
        tenantContextStore: TenantContextStore,
    ): DashboardRepository = DefaultDashboardRepository(
        api = api,
        moshi = moshi,
        database = database,
        tenantContextStore = tenantContextStore,
    )

    @Provides
    @Singleton
    fun provideClassWorkspaceRepository(
        api: EduCoreApi,
        moshi: Moshi,
        database: EduCoreDatabase,
        tenantContextStore: TenantContextStore,
    ): ClassWorkspaceRepository = DefaultClassWorkspaceRepository(
        api = api,
        moshi = moshi,
        database = database,
        tenantContextStore = tenantContextStore,
    )

    @Provides
    @Singleton
    fun provideScoreWorkspaceRepository(
        api: EduCoreApi,
        moshi: Moshi,
        database: EduCoreDatabase,
        tenantContextStore: TenantContextStore,
    ): ScoreWorkspaceRepository = DefaultScoreWorkspaceRepository(api, moshi, database, tenantContextStore)

    @Provides
    @Singleton
    fun provideScheduleRepository(
        api: EduCoreApi,
        moshi: Moshi,
        database: EduCoreDatabase,
        tenantContextStore: TenantContextStore,
    ): ScheduleRepository = DefaultScheduleRepository(api, moshi, database, tenantContextStore)

    @Provides
    @Singleton
    fun provideAcademicContentRepository(
        @ApplicationContext context: Context,
        api: EduCoreApi,
        moshi: Moshi,
        database: EduCoreDatabase,
        tenantContextStore: TenantContextStore,
    ): AcademicContentRepository = DefaultAcademicContentRepository(context, api, moshi, database, tenantContextStore)

    @Provides
    @Singleton
    fun provideCbtRepository(api: EduCoreApi, moshi: Moshi): CbtRepository = DefaultCbtRepository(api, moshi)

    @Provides
    @Singleton
    fun provideOperationsRepository(api: EduCoreApi, moshi: Moshi): OperationsRepository = DefaultOperationsRepository(api, moshi)

    @Provides
    @Singleton
    fun provideCommunicationRepository(
        @ApplicationContext context: Context,
        api: EduCoreApi,
        moshi: Moshi,
    ): CommunicationRepository = DefaultCommunicationRepository(context, api, moshi)

    private val DATABASE_MIGRATION_1_2 = object : Migration(1, 2) {
        override fun migrate(connection: SQLiteConnection) {
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `cached_dashboards` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `scope` TEXT NOT NULL,
                    `payload_json` TEXT NOT NULL,
                    `source_generated_at` TEXT NOT NULL,
                    `cached_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`)
                )
                """.trimIndent(),
            ).use { statement -> statement.step() }
        }
    }

    private val DATABASE_MIGRATION_2_3 = object : Migration(2, 3) {
        override fun migrate(connection: SQLiteConnection) {
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `cached_class_workspaces` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `cache_key` TEXT NOT NULL,
                    `payload_json` TEXT NOT NULL,
                    `source_generated_at` TEXT NOT NULL,
                    `cached_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `cache_key`)
                )
                """.trimIndent(),
            ).use { statement -> statement.step() }
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `attendance_drafts` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `class_id` INTEGER NOT NULL,
                    `attendance_date` TEXT NOT NULL,
                    `student_id` INTEGER NOT NULL,
                    `status` TEXT,
                    `remark` TEXT,
                    `server_version` TEXT NOT NULL,
                    `updated_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `class_id`, `attendance_date`, `student_id`)
                )
                """.trimIndent(),
            ).use { statement -> statement.step() }
        }
    }

    private val DATABASE_MIGRATION_3_4 = object : Migration(3, 4) {
        override fun migrate(connection: SQLiteConnection) {
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `cached_score_contracts` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `cache_key` TEXT NOT NULL,
                    `payload_json` TEXT NOT NULL,
                    `cached_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `cache_key`)
                )
                """.trimIndent(),
            ).use { it.step() }
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `score_drafts` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `class_id` INTEGER NOT NULL,
                    `subject_id` INTEGER NOT NULL,
                    `term_id` INTEGER NOT NULL,
                    `student_id` INTEGER NOT NULL,
                    `assessment_id` INTEGER NOT NULL,
                    `value` REAL,
                    `server_version` TEXT NOT NULL,
                    `updated_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `class_id`, `subject_id`, `term_id`, `student_id`, `assessment_id`)
                )
                """.trimIndent(),
            ).use { it.step() }
        }
    }

    private val DATABASE_MIGRATION_4_5 = object : Migration(4, 5) {
        override fun migrate(connection: SQLiteConnection) {
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `cached_schedules` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `cache_key` TEXT NOT NULL,
                    `payload_json` TEXT NOT NULL,
                    `cached_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `cache_key`)
                )
                """.trimIndent(),
            ).use { it.step() }
        }
    }

    private val DATABASE_MIGRATION_5_6 = object : Migration(5, 6) {
        override fun migrate(connection: SQLiteConnection) {
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `cached_academic_content` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `cache_key` TEXT NOT NULL,
                    `payload_json` TEXT NOT NULL,
                    `cached_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `cache_key`)
                )
                """.trimIndent(),
            ).use { it.step() }
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `lesson_plan_drafts` (
                    `tenant_key` TEXT NOT NULL,
                    `user_id` INTEGER NOT NULL,
                    `draft_key` TEXT NOT NULL,
                    `payload_json` TEXT NOT NULL,
                    `updated_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `draft_key`)
                )
                """.trimIndent(),
            ).use { it.step() }
        }
    }

    private val DATABASE_MIGRATION_6_7 = object : Migration(6, 7) {
        override fun migrate(connection: SQLiteConnection) {
            connection.prepare(
                """
                CREATE TABLE IF NOT EXISTS `pending_sync_operations` (
                    `tenant_key` TEXT NOT NULL, `user_id` INTEGER NOT NULL, `operation_key` TEXT NOT NULL,
                    `kind` TEXT NOT NULL, `request_id` TEXT NOT NULL, `payload_json` TEXT NOT NULL,
                    `state` TEXT NOT NULL, `attempt_count` INTEGER NOT NULL, `last_error` TEXT,
                    `created_at_epoch_ms` INTEGER NOT NULL, `updated_at_epoch_ms` INTEGER NOT NULL,
                    PRIMARY KEY(`tenant_key`, `user_id`, `operation_key`)
                )
                """.trimIndent(),
            ).use { it.step() }
        }
    }

    private val DATABASE_MIGRATION_7_8 = object : Migration(7, 8) {
        override fun migrate(connection: SQLiteConnection) {
            connection.prepare(
                """
                CREATE INDEX IF NOT EXISTS `index_pending_sync_scope_kind_state_created`
                ON `pending_sync_operations` (`tenant_key`, `user_id`, `kind`, `state`, `created_at_epoch_ms`)
                """.trimIndent(),
            ).use { it.step() }
        }
    }
}
