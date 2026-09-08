package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PlatformAgentsDto
import online.educoreng.educore.core.network.dto.PlatformAnalyticsDto
import online.educoreng.educore.core.network.dto.PlatformBillingDto
import online.educoreng.educore.core.network.dto.PlatformBroadcastsDto
import online.educoreng.educore.core.network.dto.PlatformDashboardDto
import online.educoreng.educore.core.network.dto.PlatformGatewaysDto
import online.educoreng.educore.core.network.dto.PlatformGroupsDto
import online.educoreng.educore.core.network.dto.PlatformPlansDto
import online.educoreng.educore.core.network.dto.PlatformSettingsDto
import online.educoreng.educore.core.network.dto.PlatformSupportDto
import online.educoreng.educore.core.network.dto.PlatformTenantsDto
import retrofit2.http.GET
import retrofit2.http.Query

interface PlatformApi {
    @GET("platform/dashboard")
    suspend fun dashboard(): PlatformDashboardDto

    @GET("platform/tenants")
    suspend fun tenants(
        @Query("search") search: String? = null,
        @Query("status") status: String? = null,
    ): PlatformTenantsDto

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

    @GET("platform/support")
    suspend fun support(): PlatformSupportDto

    @GET("platform/broadcasts")
    suspend fun broadcasts(): PlatformBroadcastsDto

    @GET("platform/settings")
    suspend fun settings(): PlatformSettingsDto

    @GET("platform/gateways")
    suspend fun gateways(): PlatformGatewaysDto
}
