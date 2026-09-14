package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.GeneratedKnowledgeResponseDto
import online.educoreng.educore.core.network.dto.KnowledgeCatalogueResponseDto
import online.educoreng.educore.core.network.dto.KnowledgeMutationResponseDto
import online.educoreng.educore.core.network.dto.KnowledgeTopicResponseDto
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface AcademicKnowledgeApi {
    @GET("academic-repository/knowledge")
    suspend fun topics(
        @Query("class") className: String? = null,
        @Query("term") term: String? = null,
        @Query("subject") subject: String? = null,
        @Query("query") query: String? = null,
        @Query("ready") ready: Boolean? = null,
        @Query("per_page") perPage: Int = 50,
        @Query("page") page: Int = 1,
    ): KnowledgeCatalogueResponseDto

    @GET("academic-repository/knowledge/{topic}")
    suspend fun topic(@Path("topic") topicId: Long): KnowledgeTopicResponseDto

    @GET("academic-repository/knowledge/{topic}/generate/{type}")
    suspend fun generate(
        @Path("topic") topicId: Long,
        @Path("type") type: String,
    ): GeneratedKnowledgeResponseDto

    @POST("academic-repository/knowledge/{topic}/save-lesson-plan")
    suspend fun saveLessonPlan(@Path("topic") topicId: Long): KnowledgeMutationResponseDto

    @POST("academic-repository/knowledge/{topic}/save-student-note")
    suspend fun saveStudentNote(@Path("topic") topicId: Long): KnowledgeMutationResponseDto
}
