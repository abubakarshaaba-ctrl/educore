package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PlatformAgentsDto
import online.educoreng.educore.core.network.dto.PlatformAnalyticsDto
import online.educoreng.educore.core.network.dto.PlatformBillingDto
import online.educoreng.educore.core.network.dto.PlatformBroadcastCreateRequestDto
import online.educoreng.educore.core.network.dto.PlatformBroadcastsDto
import online.educoreng.educore.core.network.dto.PlatformDashboardDto
import online.educoreng.educore.core.network.dto.PlatformGatewayUpdateRequestDto
import online.educoreng.educore.core.network.dto.PlatformGatewaysDto
import online.educoreng.educore.core.network.dto.PlatformGroupCreateRequestDto
import online.educoreng.educore.core.network.dto.PlatformGroupDetailDto
import online.educoreng.educore.core.network.dto.PlatformGroupMemberRequestDto
import online.educoreng.educore.core.network.dto.PlatformGroupsDto
import online.educoreng.educore.core.network.dto.PlatformMutationResponseDto
import online.educoreng.educore.core.network.dto.PlatformPlansDto
import online.educoreng.educore.core.network.dto.PlatformSettingsDto
import online.educoreng.educore.core.network.dto.PlatformSettingsMutationResponseDto
import online.educoreng.educore.core.network.dto.PlatformSettingsUpdateRequestDto
import online.educoreng.educore.core.network.dto.PlatformSupportDto
import online.educoreng.educore.core.network.dto.PlatformSupportReplyRequestDto
import online.educoreng.educore.core.network.dto.PlatformTenantDetailDto
import online.educoreng.educore.core.network.dto.PlatformTenantExtendRequestDto
import online.educoreng.educore.core.network.dto.PlatformTenantMutationResponseDto
import online.educoreng.educore.core.network.dto.PlatformTenantUpdateRequestDto
import online.educoreng.educore.core.network.dto.PlatformTenantsDto
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.PUT
import retrofit2.http.Path
import retrofit2.http.Query

interface PlatformApi {
    @GET("platform/dashboard")
    suspend fun dashboard(): PlatformDashboardDto

    @GET("platform/tenants")
    suspend fun tenants(
        @Query("search") search: String? = null,
        @Query("status") status: String? = null,
    ): PlatformTenantsDto

    @GET("platform/tenants/{tenant}")
    suspend fun tenant(@Path("tenant") tenant: Long): PlatformTenantDetailDto

    @PATCH("platform/tenants/{tenant}")
    suspend fun updateTenant(
        @Path("tenant") tenant: Long,
        @Body body: PlatformTenantUpdateRequestDto,
    ): PlatformTenantMutationResponseDto

    @POST("platform/tenants/{tenant}/extend")
    suspend fun extendTenant(
        @Path("tenant") tenant: Long,
        @Body body: PlatformTenantExtendRequestDto,
    ): PlatformTenantMutationResponseDto

    @GET("platform/billing")
    suspend fun billing(): PlatformBillingDto

    @GET("platform/plans")
    suspend fun plans(): PlatformPlansDto

    @GET("platform/agents")
    suspend fun agents(): PlatformAgentsDto

    @GET("platform/analytics")
    suspend fun analytics(): PlatformAnalyticsDto

    @GET("platform/groups")
    suspend fun groups(): PlatformGroupsDto

    @POST("platform/groups")
    suspend fun createGroup(@Body body: PlatformGroupCreateRequestDto): PlatformMutationResponseDto

    @GET("platform/groups/{group}")
    suspend fun group(@Path("group") group: Long): PlatformGroupDetailDto

    @POST("platform/groups/{group}/members")
    suspend fun addGroupMember(
        @Path("group") group: Long,
        @Body body: PlatformGroupMemberRequestDto,
    ): PlatformMutationResponseDto

    @DELETE("platform/groups/{group}/members/{tenant}")
    suspend fun removeGroupMember(
        @Path("group") group: Long,
        @Path("tenant") tenant: Long,
    ): PlatformMutationResponseDto

    @POST("platform/groups/{group}/members/{tenant}/lead")
    suspend fun setGroupLead(
        @Path("group") group: Long,
        @Path("tenant") tenant: Long,
    ): PlatformMutationResponseDto

    @GET("platform/support")
    suspend fun support(): PlatformSupportDto

    @POST("platform/support/{ticket}/reply")
    suspend fun replySupport(
        @Path("ticket") ticket: Long,
        @Body body: PlatformSupportReplyRequestDto,
    ): PlatformMutationResponseDto

    @POST("platform/support/{ticket}/close")
    suspend fun closeSupport(@Path("ticket") ticket: Long): PlatformMutationResponseDto

    @GET("platform/broadcasts")
    suspend fun broadcasts(): PlatformBroadcastsDto

    @POST("platform/broadcasts")
    suspend fun createBroadcast(@Body body: PlatformBroadcastCreateRequestDto): PlatformMutationResponseDto

    @POST("platform/broadcasts/{broadcast}/expire")
    suspend fun expireBroadcast(@Path("broadcast") broadcast: Long): PlatformMutationResponseDto

    @GET("platform/settings")
    suspend fun settings(): PlatformSettingsDto

    @PUT("platform/settings")
    suspend fun updateSettings(@Body body: PlatformSettingsUpdateRequestDto): PlatformSettingsMutationResponseDto

    @GET("platform/gateways")
    suspend fun gateways(): PlatformGatewaysDto

    @PUT("platform/gateways/{provider}")
    suspend fun updateGateway(
        @Path("provider") provider: String,
        @Body body: PlatformGatewayUpdateRequestDto,
    ): PlatformSettingsMutationResponseDto
}
