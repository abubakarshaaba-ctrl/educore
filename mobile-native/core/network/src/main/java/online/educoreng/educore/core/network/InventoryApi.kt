package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.InventoryAssetRequestDto
import online.educoreng.educore.core.network.dto.InventoryAssetResponseDto
import online.educoreng.educore.core.network.dto.InventoryMutationResponseDto
import online.educoreng.educore.core.network.dto.InventoryWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface InventoryApi {
    @GET("inventory")
    suspend fun index(
        @Query("search") search: String? = null,
        @Query("status") status: String = "all",
        @Query("page") page: Int = 1,
        @Query("per_page") perPage: Int = 40,
    ): InventoryWorkspaceDto

    @POST("inventory")
    suspend fun create(@Body request: InventoryAssetRequestDto): InventoryAssetResponseDto

    @PATCH("inventory/{asset}")
    suspend fun update(
        @Path("asset") assetId: Long,
        @Body request: InventoryAssetRequestDto,
    ): InventoryAssetResponseDto

    @DELETE("inventory/{asset}")
    suspend fun delete(@Path("asset") assetId: Long): InventoryMutationResponseDto
}
