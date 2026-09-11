package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PlatformNoticeFeedDto
import online.educoreng.educore.core.network.dto.PlatformNoticeMutationDto
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path

interface PlatformNoticeApi {
    @GET("platform-notices")
    suspend fun notices(): PlatformNoticeFeedDto

    @POST("platform-notices/{broadcast}/read")
    suspend fun markRead(@Path("broadcast") broadcastId: Long): PlatformNoticeMutationDto

    @POST("platform-notices/{broadcast}/dismiss")
    suspend fun dismiss(@Path("broadcast") broadcastId: Long): PlatformNoticeMutationDto
}
