package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.CurriculumMessageDto
import online.educoreng.educore.core.network.dto.CurriculumRuleCreateRequestDto
import online.educoreng.educore.core.network.dto.CurriculumRuleMutationResponseDto
import online.educoreng.educore.core.network.dto.CurriculumRuleUpdateRequestDto
import online.educoreng.educore.core.network.dto.CurriculumTrackMutationResponseDto
import online.educoreng.educore.core.network.dto.CurriculumTrackRequestDto
import online.educoreng.educore.core.network.dto.CurriculumWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface CurriculumApi {
    @GET("curriculum")
    suspend fun index(
        @Query("q") search: String? = null,
        @Query("level_id") levelId: Long? = null,
        @Query("track_id") trackId: Long? = null,
        @Query("status") status: String? = null,
    ): CurriculumWorkspaceDto

    @POST("curriculum/tracks")
    suspend fun createTrack(@Body body: CurriculumTrackRequestDto): CurriculumTrackMutationResponseDto

    @PATCH("curriculum/tracks/{track}")
    suspend fun updateTrack(
        @Path("track") track: Long,
        @Body body: CurriculumTrackRequestDto,
    ): CurriculumTrackMutationResponseDto

    @DELETE("curriculum/tracks/{track}")
    suspend fun deleteTrack(@Path("track") track: Long): CurriculumMessageDto

    @POST("curriculum/rules")
    suspend fun createRule(@Body body: CurriculumRuleCreateRequestDto): CurriculumRuleMutationResponseDto

    @PATCH("curriculum/rules/{rule}")
    suspend fun updateRule(
        @Path("rule") rule: Long,
        @Body body: CurriculumRuleUpdateRequestDto,
    ): CurriculumRuleMutationResponseDto

    @DELETE("curriculum/rules/{rule}")
    suspend fun deleteRule(@Path("rule") rule: Long): CurriculumMessageDto
}
