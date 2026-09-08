package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.SkillsSaveRequestDto
import online.educoreng.educore.core.network.dto.SkillsSaveResponseDto
import online.educoreng.educore.core.network.dto.SkillsSheetDto
import online.educoreng.educore.core.network.dto.SkillsWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.PUT
import retrofit2.http.Query

interface SkillsApi {
    @GET("skills")
    suspend fun index(): SkillsWorkspaceDto

    @GET("skills/sheet")
    suspend fun sheet(
        @Query("class_arm_id") classArmId: Long,
        @Query("term_id") termId: Long,
    ): SkillsSheetDto

    @PUT("skills/sheet")
    suspend fun save(@Body body: SkillsSaveRequestDto): SkillsSaveResponseDto
}
