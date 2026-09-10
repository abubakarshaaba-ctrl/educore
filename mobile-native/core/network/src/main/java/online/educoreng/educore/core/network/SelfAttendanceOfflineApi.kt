package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdminAttendanceMutationResponseDto
import online.educoreng.educore.core.network.dto.AdminAttendanceOfflineSyncRequestDto
import retrofit2.http.Body
import retrofit2.http.POST

/** Durable replay endpoint used by ordinary staff for My Attendance. */
interface SelfAttendanceOfflineApi {
    @POST("staff-attendance/offline/sync")
    suspend fun sync(@Body body: AdminAttendanceOfflineSyncRequestDto): AdminAttendanceMutationResponseDto
}
