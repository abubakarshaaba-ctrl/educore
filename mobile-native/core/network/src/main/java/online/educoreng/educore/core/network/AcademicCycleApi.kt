package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.AcademicCycleMessageDto
import online.educoreng.educore.core.network.dto.AcademicCycleReadinessDto
import online.educoreng.educore.core.network.dto.AcademicCycleWorkspaceDto
import online.educoreng.educore.core.network.dto.AcademicSessionCreateRequestDto
import online.educoreng.educore.core.network.dto.AcademicSessionMutationResponseDto
import online.educoreng.educore.core.network.dto.AcademicSessionUpdateRequestDto
import online.educoreng.educore.core.network.dto.AcademicTermCreateRequestDto
import online.educoreng.educore.core.network.dto.AcademicTermMutationResponseDto
import online.educoreng.educore.core.network.dto.AcademicTermUpdateRequestDto
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path

interface AcademicCycleApi {
    @GET("academic-cycle")
    suspend fun index(): AcademicCycleWorkspaceDto

    @POST("academic-cycle/sessions")
    suspend fun createSession(@Body body: AcademicSessionCreateRequestDto): AcademicSessionMutationResponseDto

    @PATCH("academic-cycle/sessions/{session}")
    suspend fun updateSession(
        @Path("session") session: Long,
        @Body body: AcademicSessionUpdateRequestDto,
    ): AcademicSessionMutationResponseDto

    @POST("academic-cycle/sessions/{session}/activate")
    suspend fun activateSession(@Path("session") session: Long): AcademicSessionMutationResponseDto

    @GET("academic-cycle/sessions/{session}/readiness")
    suspend fun sessionReadiness(@Path("session") session: Long): AcademicCycleReadinessDto

    @POST("academic-cycle/sessions/{session}/close")
    suspend fun closeSession(@Path("session") session: Long): AcademicSessionMutationResponseDto

    @DELETE("academic-cycle/sessions/{session}")
    suspend fun deleteSession(@Path("session") session: Long): AcademicCycleMessageDto

    @POST("academic-cycle/terms")
    suspend fun createTerm(@Body body: AcademicTermCreateRequestDto): AcademicTermMutationResponseDto

    @PATCH("academic-cycle/terms/{term}")
    suspend fun updateTerm(
        @Path("term") term: Long,
        @Body body: AcademicTermUpdateRequestDto,
    ): AcademicTermMutationResponseDto

    @POST("academic-cycle/terms/{term}/activate")
    suspend fun activateTerm(@Path("term") term: Long): AcademicTermMutationResponseDto

    @GET("academic-cycle/terms/{term}/readiness")
    suspend fun termReadiness(@Path("term") term: Long): AcademicCycleReadinessDto

    @POST("academic-cycle/terms/{term}/close")
    suspend fun closeTerm(@Path("term") term: Long): AcademicTermMutationResponseDto

    @DELETE("academic-cycle/terms/{term}")
    suspend fun deleteTerm(@Path("term") term: Long): AcademicCycleMessageDto
}
