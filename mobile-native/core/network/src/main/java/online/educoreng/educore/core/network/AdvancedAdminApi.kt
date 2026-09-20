package online.educoreng.educore.core.network

import okhttp3.MultipartBody
import okhttp3.RequestBody
import online.educoreng.educore.core.network.dto.AdvancedAdminConfirmationDto
import online.educoreng.educore.core.network.dto.AdvancedAdminDecisionDto
import online.educoreng.educore.core.network.dto.AdvancedAdminMessageDto
import online.educoreng.educore.core.network.dto.AdvancedAdminMigrationCreatedDto
import online.educoreng.educore.core.network.dto.AdvancedAdminOverviewDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Multipart
import retrofit2.http.POST
import retrofit2.http.Part
import retrofit2.http.Path
import retrofit2.http.Query

interface AdvancedAdminApi {
    @GET("advanced-admin")
    suspend fun overview(@Query("tenant_id") tenantId: Long? = null): AdvancedAdminOverviewDto

    @Multipart
    @POST("advanced-admin/migrations")
    suspend fun createMigration(
        @Part("tenant_id") tenantId: RequestBody?,
        @Part("direction") direction: RequestBody,
        @Part("migration_type") migrationType: RequestBody,
        @Part("source_platform") sourcePlatform: RequestBody,
        @Part("destination_system") destinationSystem: RequestBody,
        @Part("business_justification") businessJustification: RequestBody,
        @Part("data_scope[]") dataScope: List<RequestBody>,
        @Part sourceFiles: List<MultipartBody.Part>,
    ): AdvancedAdminMigrationCreatedDto

    @POST("advanced-admin/migrations/{migration}/ingest")
    suspend fun ingest(@Path("migration") migrationId: Long): AdvancedAdminMessageDto

    @POST("advanced-admin/migrations/{migration}/verify")
    suspend fun verify(@Path("migration") migrationId: Long): AdvancedAdminMessageDto

    @POST("advanced-admin/migrations/{migration}/blueprint")
    suspend fun reconstructBlueprint(
        @Path("migration") migrationId: Long,
        @Body body: AdvancedAdminConfirmationDto = AdvancedAdminConfirmationDto("RECONSTRUCT"),
    ): AdvancedAdminMessageDto

    @POST("advanced-admin/migration-requests/{request}/approve")
    suspend fun approve(
        @Path("request") requestId: Long,
        @Body body: AdvancedAdminDecisionDto,
    ): AdvancedAdminMessageDto

    @POST("advanced-admin/migration-requests/{request}/reject")
    suspend fun reject(
        @Path("request") requestId: Long,
        @Body body: AdvancedAdminDecisionDto,
    ): AdvancedAdminMessageDto

    @POST("advanced-admin/backup")
    suspend fun backup(
        @Body body: AdvancedAdminConfirmationDto = AdvancedAdminConfirmationDto("BACKUP"),
    ): AdvancedAdminMessageDto
}
