package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.SubjectMutationRequestDto
import online.educoreng.educore.core.network.dto.SubjectMutationResponseDto
import online.educoreng.educore.core.network.dto.SubjectsWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface SubjectsApi {
    @GET("subjects")
    suspend fun index(
        @Query("q") search: String? = null,
        @Query("status") status: String? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null,
    ): SubjectsWorkspaceDto

    @POST("subjects")
    suspend fun create(@Body request: SubjectMutationRequestDto): SubjectMutationResponseDto

    @PATCH("subjects/{subject}")
    suspend fun update(
        @Path("subject") subject: Long,
        @Body request: SubjectMutationRequestDto,
    ): SubjectMutationResponseDto

    @DELETE("subjects/{subject}")
    suspend fun delete(@Path("subject") subject: Long): SubjectMutationResponseDto
}
