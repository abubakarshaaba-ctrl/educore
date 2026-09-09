package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PortalAccountCreateRequestDto
import online.educoreng.educore.core.network.dto.PortalAccountMutationResponseDto
import online.educoreng.educore.core.network.dto.PortalAccountsWorkspaceDto
import online.educoreng.educore.core.network.dto.PortalPasswordResetRequestDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path

interface PortalAccountsApi {
    @GET("portal-accounts")
    suspend fun index(): PortalAccountsWorkspaceDto

    @POST("portal-accounts/students/{student}")
    suspend fun createStudent(
        @Path("student") student: Long,
        @Body body: PortalAccountCreateRequestDto,
    ): PortalAccountMutationResponseDto

    @POST("portal-accounts/guardians/{guardian}")
    suspend fun createGuardian(
        @Path("guardian") guardian: Long,
        @Body body: PortalAccountCreateRequestDto,
    ): PortalAccountMutationResponseDto

    @POST("portal-accounts/users/{portalUser}/reset-password")
    suspend fun resetPassword(
        @Path("portalUser") portalUser: Long,
        @Body body: PortalPasswordResetRequestDto,
    ): PortalAccountMutationResponseDto

    @POST("portal-accounts/users/{portalUser}/toggle")
    suspend fun toggle(@Path("portalUser") portalUser: Long): PortalAccountMutationResponseDto

    @POST("portal-accounts/students/bulk")
    suspend fun bulkStudents(): PortalAccountMutationResponseDto
}
