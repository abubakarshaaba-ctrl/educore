package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PortalAttendanceResponseDto
import retrofit2.http.GET
import retrofit2.http.Query

interface PortalAttendanceApi {
    @GET("student/attendance")
    suspend fun studentAttendance(
        @Query("term_id") termId: Long? = null,
    ): PortalAttendanceResponseDto

    @GET("parent/attendance")
    suspend fun parentAttendance(
        @Query("child_id") childId: Long? = null,
        @Query("term_id") termId: Long? = null,
    ): PortalAttendanceResponseDto
}
