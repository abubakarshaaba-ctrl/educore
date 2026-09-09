package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.SchoolSettingsUpdateRequestDto
import online.educoreng.educore.core.network.dto.SchoolSettingsWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.PUT

interface SchoolSettingsApi {
    @GET("school-settings")
    suspend fun show(): SchoolSettingsWorkspaceDto

    @PUT("school-settings")
    suspend fun update(@Body body: SchoolSettingsUpdateRequestDto): SchoolSettingsWorkspaceDto
}
