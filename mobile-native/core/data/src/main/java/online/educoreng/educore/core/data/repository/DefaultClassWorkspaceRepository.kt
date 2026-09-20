package online.educoreng.educore.core.data.repository

import com.squareup.moshi.JsonAdapter
import com.squareup.moshi.Moshi
import java.util.UUID
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.flow.first
import kotlinx.coroutines.withContext
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.local.AttendanceDraftEntity
import online.educoreng.educore.core.data.local.CachedClassWorkspaceEntity
import online.educoreng.educore.core.data.local.EduCoreDatabase
import online.educoreng.educore.core.data.local.SyncOperationEntity
import online.educoreng.educore.core.data.preferences.TenantContextStore
import online.educoreng.educore.core.model.AttendanceSheet
import online.educoreng.educore.core.model.AttendanceStatus
import online.educoreng.educore.core.model.ClassCatalogue
import online.educoreng.educore.core.model.ClassStudentsSnapshot
import online.educoreng.educore.core.model.ProxyAttendanceColleague
import online.educoreng.educore.core.model.StaffAttendanceSnapshot
import online.educoreng.educore.core.model.StudentProfile
import online.educoreng.educore.core.model.SyncState
import online.educoreng.educore.core.network.EduCoreApi
import online.educoreng.educore.core.network.dto.AttendanceRecordRequestDto
import online.educoreng.educore.core.network.dto.AttendanceSheetResponseDto
import online.educoreng.educore.core.network.dto.ClassListResponseDto
import online.educoreng.educore.core.network.dto.ClassStudentsResponseDto
import online.educoreng.educore.core.network.dto.ClockInRequestDto
import online.educoreng.educore.core.network.dto.ProxyClockInRequestDto
import online.educoreng.educore.core.network.dto.SaveAttendanceRequestDto
import online.educoreng.educore.core.network.safeApiCall
import online.educoreng.educore.core.network.toDomain

class DefaultClassWorkspaceRepository(
    private val api: EduCoreApi,
    private val moshi: Moshi,
    private val database: EduCoreDatabase,
    private val tenantContextStore: TenantContextStore,
    private val nowEpochMs: () -> Long = System::currentTimeMillis,
) : ClassWorkspaceRepository {
    private val classListAdapter by lazy { moshi.adapter(ClassListResponseDto::class.java) }
    private val studentsAdapter by lazy { moshi.adapter(ClassStudentsResponseDto::class.java) }
    private val attendanceAdapter by lazy { moshi.adapter(AttendanceSheetResponseDto::class.java) }
    private val attendanceRequestAdapter by lazy { moshi.adapter(SaveAttendanceRequestDto::class.java) }

    override suspend fun loadClasses(forceRefresh: Boolean): AppResult<ClassCatalogue> = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
        loadCachedContract(
            scope = scope,
            cacheKey = CLASSES_CACHE_KEY,
            adapter = classListAdapter,
            network = ::loadAllClasses,
            generatedAt = ClassListResponseDto::generatedAt,
            domain = { dto, cachedAt, fromCache -> dto.toDomain(cachedAt, fromCache) },
        )
    }

    override suspend fun loadStudents(classId: Long, search: String?, page: Int): AppResult<ClassStudentsSnapshot> =
        withContext(Dispatchers.IO) {
            val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            val normalizedSearch = search?.trim()?.takeIf(String::isNotEmpty)
            val safePage = page.coerceAtLeast(1)
            val cacheKey = "students:$classId:${normalizedSearch.orEmpty().lowercase()}:$safePage"
            loadCachedContract(
                scope = scope,
                cacheKey = cacheKey,
                adapter = studentsAdapter,
                network = { api.classStudents(classId, normalizedSearch, perPage = STUDENT_PAGE_SIZE, page = safePage) },
                generatedAt = ClassStudentsResponseDto::generatedAt,
                domain = { dto, cachedAt, fromCache -> dto.toDomain(cachedAt, fromCache) },
            )
        }

    override suspend fun loadStudentProfile(classId: Long, studentId: Long): AppResult<StudentProfile> =
        withContext(Dispatchers.IO) {
            when (val result = safeApiCall(moshi) { api.studentProfile(classId, studentId) }) {
                is AppResult.Success -> AppResult.Success(result.value.toDomain())
                is AppResult.Failure -> result
            }
        }

    override suspend fun loadAttendance(classId: Long, date: String): AppResult<AttendanceSheet> =
        withContext(Dispatchers.IO) {
            val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            val cacheKey = "attendance:$classId:$date"
            val result = loadCachedContract(
                scope = scope,
                cacheKey = cacheKey,
                adapter = attendanceAdapter,
                network = { api.attendanceSheet(classId, date) },
                generatedAt = AttendanceSheetResponseDto::generatedAt,
                domain = { dto, _, _ -> dto.toDomain() },
            )
            when (result) {
                is AppResult.Success -> AppResult.Success(withSync(scope, mergeDraft(scope, result.value)))
                is AppResult.Failure -> result
            }
        }

    override suspend fun saveAttendanceDraft(sheet: AttendanceSheet): AppResult<AttendanceSheet> =
        withContext(Dispatchers.IO) {
            val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            val updatedAt = nowEpochMs()
            val entities = sheet.students.map { row ->
                AttendanceDraftEntity(
                    tenantKey = scope.tenantKey,
                    userId = scope.userId,
                    classId = sheet.classId,
                    attendanceDate = sheet.date,
                    studentId = row.student.id,
                    status = row.status?.wireValue,
                    remark = row.remark,
                    serverVersion = sheet.version,
                    updatedAtEpochMs = updatedAt,
                )
            }
            database.classWorkspaceDao().replaceAttendanceDraft(
                tenantKey = scope.tenantKey,
                userId = scope.userId,
                classId = sheet.classId,
                date = sheet.date,
                entities = entities,
            )
            AppResult.Success(sheet.copy(hasLocalDraft = true, draftUpdatedAtEpochMs = updatedAt))
        }

    override suspend fun discardAttendanceDraft(classId: Long, date: String): AppResult<Unit> =
        withContext(Dispatchers.IO) {
            val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            database.classWorkspaceDao().deleteAttendanceDraft(scope.tenantKey, scope.userId, classId, date)
            database.syncOperationDao().delete(scope.tenantKey, scope.userId, attendanceKey(classId, date))
            AppResult.Success(Unit)
        }

    override suspend fun submitAttendance(sheet: AttendanceSheet): AppResult<AttendanceSheet> =
        withContext(Dispatchers.IO) {
            if (sheet.isDraftStale) {
                return@withContext AppResult.Failure(
                    AppError.Conflict("This draft is based on an older attendance sheet. Reload before saving."),
                )
            }
            if (sheet.students.any { it.status == null }) {
                return@withContext AppResult.Failure(
                    AppError.Validation(
                        userMessage = "Choose an attendance status for every student before saving.",
                        fieldErrors = mapOf("attendance" to listOf("Some students do not have a status.")),
                    ),
                )
            }
            val scope = scope() ?: return@withContext AppResult.Failure(AppError.Unauthenticated())
            val request = SaveAttendanceRequestDto(
                date = sheet.date,
                version = sheet.version,
                requestId = UUID.randomUUID().toString(),
                records = sheet.students.map { row ->
                    AttendanceRecordRequestDto(
                        studentId = row.student.id,
                        status = requireNotNull(row.status).wireValue,
                        remark = row.remark,
                    )
                },
            )
            val key = attendanceKey(sheet.classId, sheet.date)
            val existing = database.syncOperationDao().get(scope.tenantKey, scope.userId, key)
            if (existing?.state == "conflict") return@withContext AppResult.Failure(AppError.Conflict(existing.lastError ?: "Reload this attendance sheet before retrying."))
            val operation = existing ?: SyncOperationEntity(
                scope.tenantKey, scope.userId, key, ATTENDANCE_KIND, request.requestId,
                attendanceRequestAdapter.toJson(request), "pending", 0, null, nowEpochMs(), nowEpochMs(),
            ).also { database.syncOperationDao().upsert(it) }
            val stableRequest = attendanceRequestAdapter.fromJson(operation.payloadJson)
                ?: return@withContext AppResult.Failure(AppError.Unexpected("The saved attendance request could not be read."))
            when (val result = safeApiCall(moshi) { api.saveAttendance(sheet.classId, stableRequest) }) {
                is AppResult.Success -> {
                    database.classWorkspaceDao().deleteAttendanceDraft(
                        scope.tenantKey,
                        scope.userId,
                        sheet.classId,
                        sheet.date,
                    )
                    database.syncOperationDao().delete(scope.tenantKey, scope.userId, key)
                    AppResult.Success(
                        sheet.copy(
                            version = result.value.version,
                            hasLocalDraft = false,
                            isDraftStale = false,
                            draftUpdatedAtEpochMs = null,
                        ),
                    )
                }
                is AppResult.Failure -> when (result.error) {
                    is AppError.NetworkUnavailable, is AppError.Timeout, is AppError.Server, is AppError.RateLimited -> {
                        database.syncOperationDao().upsert(operation.copy(state = "pending", attemptCount = operation.attemptCount + 1, lastError = result.error.userMessage, updatedAtEpochMs = nowEpochMs()))
                        AppResult.Success(sheet.copy(syncState = SyncState.QUEUED, syncMessage = "Queued securely. EduCore will retry when the connection is stable."))
                    }
                    is AppError.Conflict -> {
                        database.syncOperationDao().upsert(operation.copy(state = "conflict", lastError = result.error.userMessage, updatedAtEpochMs = nowEpochMs()))
                        result
                    }
                    else -> {
                        database.syncOperationDao().upsert(operation.copy(state = "failed", lastError = result.error.userMessage, updatedAtEpochMs = nowEpochMs()))
                        result
                    }
                }
            }
        }

    override suspend fun syncPendingAttendance(): Boolean = withContext(Dispatchers.IO) {
        val scope = scope() ?: return@withContext false
        var retry = false
        database.syncOperationDao().actionable(scope.tenantKey, scope.userId, ATTENDANCE_KIND).forEach { operation ->
            val request = attendanceRequestAdapter.fromJson(operation.payloadJson) ?: return@forEach
            val classId = operation.operationKey.split(':').getOrNull(1)?.toLongOrNull() ?: return@forEach
            when (val result = safeApiCall(moshi) { api.saveAttendance(classId, request) }) {
                is AppResult.Success -> {
                    database.classWorkspaceDao().deleteAttendanceDraft(scope.tenantKey, scope.userId, classId, request.date)
                    database.syncOperationDao().delete(scope.tenantKey, scope.userId, operation.operationKey)
                }
                is AppResult.Failure -> {
                    val transient = result.error is AppError.NetworkUnavailable || result.error is AppError.Timeout || result.error is AppError.Server || result.error is AppError.RateLimited
                    val state = if (result.error is AppError.Conflict) "conflict" else if (transient) "pending" else "failed"
                    database.syncOperationDao().upsert(operation.copy(state = state, attemptCount = operation.attemptCount + 1, lastError = result.error.userMessage, updatedAtEpochMs = nowEpochMs()))
                    retry = retry || transient
                }
            }
        }
        retry
    }

    override suspend fun loadStaffAttendance(): AppResult<StaffAttendanceSnapshot> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.staffAttendance() }) {
            is AppResult.Success -> AppResult.Success(result.value.toDomain())
            is AppResult.Failure -> result
        }
    }

    override suspend fun clockIn(
        token: String,
        latitude: Double?,
        longitude: Double?,
    ): AppResult<String> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.clockIn(ClockInRequestDto(token, latitude, longitude)) }) {
            is AppResult.Success -> AppResult.Success(result.value.message)
            is AppResult.Failure -> result
        }
    }

    override suspend fun clockOut(): AppResult<String> = withContext(Dispatchers.IO) {
        when (val result = safeApiCall(moshi) { api.clockOut() }) {
            is AppResult.Success -> AppResult.Success(result.value.message)
            is AppResult.Failure -> result
        }
    }

    override suspend fun loadProxyColleagues(search: String?): AppResult<List<ProxyAttendanceColleague>> =
        withContext(Dispatchers.IO) {
            when (val result = safeApiCall(moshi) { api.proxyAttendanceColleagues(search?.trim()?.takeIf(String::isNotEmpty)) }) {
                is AppResult.Success -> AppResult.Success(
                    result.value.staff.map { staff ->
                        ProxyAttendanceColleague(
                            id = staff.id,
                            name = staff.name,
                            staffId = staff.emp.orEmpty(),
                            photoUrl = staff.photo,
                        )
                    },
                )
                is AppResult.Failure -> result
            }
        }

    override suspend fun proxyClockIn(
        staffId: Long,
        token: String,
        photoDataUrl: String,
        latitude: Double?,
        longitude: Double?,
    ): AppResult<String> = withContext(Dispatchers.IO) {
        val request = ProxyClockInRequestDto(
            staffId = staffId,
            token = token.trim(),
            photo = photoDataUrl,
            lat = latitude,
            lng = longitude,
        )
        when (val result = safeApiCall(moshi) { api.proxyClockIn(request) }) {
            is AppResult.Success -> AppResult.Success(result.value.message)
            is AppResult.Failure -> result
        }
    }

    private suspend fun mergeDraft(scope: UserScope, sheet: AttendanceSheet): AttendanceSheet {
        val draft = database.classWorkspaceDao().attendanceDraft(
            scope.tenantKey,
            scope.userId,
            sheet.classId,
            sheet.date,
        )
        if (draft.isEmpty()) return sheet
        val draftByStudent = draft.associateBy(AttendanceDraftEntity::studentId)
        val baseVersion = draft.first().serverVersion

        return sheet.copy(
            students = sheet.students.map { row ->
                val saved = draftByStudent[row.student.id] ?: return@map row
                row.copy(status = AttendanceStatus.fromWire(saved.status), remark = saved.remark)
            },
            hasLocalDraft = true,
            isDraftStale = baseVersion != sheet.version,
            draftUpdatedAtEpochMs = draft.maxOf(AttendanceDraftEntity::updatedAtEpochMs),
        )
    }

    private suspend fun withSync(scope: UserScope, sheet: AttendanceSheet): AttendanceSheet {
        val operation = database.syncOperationDao().get(scope.tenantKey, scope.userId, attendanceKey(sheet.classId, sheet.date)) ?: return sheet
        val state = when (operation.state) { "conflict" -> SyncState.CONFLICT; "failed" -> SyncState.FAILED; "syncing" -> SyncState.SYNCING; else -> SyncState.QUEUED }
        return sheet.copy(syncState = state, syncMessage = operation.lastError)
    }

    private suspend fun <Dto : Any, Domain> loadCachedContract(
        scope: UserScope,
        cacheKey: String,
        adapter: JsonAdapter<Dto>,
        network: suspend () -> Dto,
        generatedAt: (Dto) -> String,
        domain: (Dto, Long?, Boolean) -> Domain,
    ): AppResult<Domain> {
        return when (val result = safeApiCall(moshi, network)) {
            is AppResult.Success -> {
                val cachedAt = nowEpochMs()
                database.classWorkspaceDao().replaceCache(
                    CachedClassWorkspaceEntity(
                        tenantKey = scope.tenantKey,
                        userId = scope.userId,
                        cacheKey = cacheKey,
                        payloadJson = adapter.toJson(result.value),
                        sourceGeneratedAt = generatedAt(result.value),
                        cachedAtEpochMs = cachedAt,
                    ),
                )
                AppResult.Success(domain(result.value, cachedAt, false))
            }
            is AppResult.Failure -> {
                val cache = database.classWorkspaceDao().cache(scope.tenantKey, scope.userId, cacheKey)
                val dto = cache?.let { runCatching { adapter.fromJson(it.payloadJson) }.getOrNull() }
                if (cache != null && dto != null) {
                    AppResult.Success(domain(dto, cache.cachedAtEpochMs, true))
                } else {
                    result
                }
            }
        }
    }

    private suspend fun scope(): UserScope? {
        val tenantKey = tenantContextStore.activeTenantKey.first() ?: return null
        val userId = database.sessionDao().get(tenantKey)?.userId ?: return null
        return UserScope(tenantKey, userId)
    }

    private suspend fun loadAllClasses(): ClassListResponseDto {
        val firstPage = api.classes(page = 1)
        if (firstPage.meta.lastPage <= 1) return firstPage

        val allClasses = firstPage.classes.toMutableList()
        for (page in 2..firstPage.meta.lastPage) {
            allClasses += api.classes(page = page).classes
        }

        return firstPage.copy(
            classes = allClasses,
            meta = firstPage.meta.copy(
                currentPage = firstPage.meta.lastPage,
                perPage = allClasses.size,
            ),
        )
    }

    private data class UserScope(val tenantKey: String, val userId: Long)

    private companion object {
        const val CLASSES_CACHE_KEY = "classes"
        const val ATTENDANCE_KIND = "attendance"
        const val STUDENT_PAGE_SIZE = 50
        fun attendanceKey(classId: Long, date: String) = "attendance:$classId:$date"
    }
}
