package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AdminStudentsResponseDto
import retrofit2.http.GET

interface AdminDirectoryApi {
    @GET("admin/students")
    suspend fun students(): AdminStudentsResponseDto
}
