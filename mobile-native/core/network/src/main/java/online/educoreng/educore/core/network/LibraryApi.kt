package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.LibraryIssueRequestDto
import online.educoreng.educore.core.network.dto.LibraryMutationResponseDto
import online.educoreng.educore.core.network.dto.LibraryOptionsDto
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.Path

interface LibraryApi {
    @GET("library/options")
    suspend fun options(): LibraryOptionsDto

    @POST("library/loans")
    suspend fun issue(@Body request: LibraryIssueRequestDto): LibraryMutationResponseDto

    @POST("library/loans/{loan}/return")
    suspend fun returnLoan(@Path("loan") loanId: Long): LibraryMutationResponseDto
}
