package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.SelfAttendanceOfflineSyncRequestDto
import online.educoreng.educore.core.network.dto.SelfAttendanceOfflineSyncResponseDto
import retrofit2.http.Body
import retrofit2.http.POST

/** Durable replay endpoint used by ordinary staff for My Attendance. */
interface SelfAttendanceOfflineApi {
    @POST("staff-attendance/clock-in")
    suspend fun sync(@Body body: SelfAttendanceOfflineSyncRequestDto): SelfAttendanceOfflineSyncResponseDto
}
