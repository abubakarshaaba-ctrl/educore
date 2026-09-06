package online.educoreng.educore.core.data.local

import androidx.room.Database
import androidx.room.RoomDatabase

@Database(
    entities = [
        CachedSessionEntity::class,
        CachedRoleEntity::class,
        CachedPermissionEntity::class,
        CachedFeatureEntity::class,
        CachedModuleEntity::class,
        CachedDashboardEntity::class,
        CachedClassWorkspaceEntity::class,
        AttendanceDraftEntity::class,
        CachedScoreContractEntity::class,
        ScoreDraftEntity::class,
        CachedScheduleEntity::class,
        CachedAcademicContentEntity::class,
        LessonPlanDraftEntity::class,
        SyncOperationEntity::class,
    ],
    version = 8,
    exportSchema = true,
)
abstract class EduCoreDatabase : RoomDatabase() {
    abstract fun sessionDao(): SessionDao
    abstract fun dashboardDao(): DashboardDao
    abstract fun classWorkspaceDao(): ClassWorkspaceDao
    abstract fun scoreWorkspaceDao(): ScoreWorkspaceDao
    abstract fun scheduleDao(): ScheduleDao
    abstract fun academicContentDao(): AcademicContentDao
    abstract fun syncOperationDao(): SyncOperationDao
}
