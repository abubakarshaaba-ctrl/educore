package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdminStaffAttendanceResponseDto
import retrofit2.http.GET
import retrofit2.http.Query

interface AdminStaffAttendanceApi {
    @GET("admin/staff-attendance")
    suspend fun daily(@Query("date") date: String? = null): AdminStaffAttendanceResponseDto
}
