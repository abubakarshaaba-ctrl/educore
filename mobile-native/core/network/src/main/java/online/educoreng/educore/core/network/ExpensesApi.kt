package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.ExpenseMutationResponseDto
import online.educoreng.educore.core.network.dto.ExpenseRequestDto
import online.educoreng.educore.core.network.dto.ExpensesWorkspaceDto
import retrofit2.http.Body
import retrofit2.http.DELETE
import retrofit2.http.GET
import retrofit2.http.PATCH
import retrofit2.http.POST
import retrofit2.http.Path
import retrofit2.http.Query

interface ExpensesApi {
    @GET("expenses")
    suspend fun index(
        @Query("q") search: String? = null,
        @Query("category") category: String? = null,
        @Query("page") page: Int? = null,
        @Query("per_page") perPage: Int? = null,
    ): ExpensesWorkspaceDto

    @POST("expenses")
    suspend fun create(@Body body: ExpenseRequestDto): ExpenseMutationResponseDto

    @PATCH("expenses/{expense}")
    suspend fun update(
        @Path("expense") expense: Long,
        @Body body: ExpenseRequestDto,
    ): ExpenseMutationResponseDto

    @DELETE("expenses/{expense}")
    suspend fun delete(@Path("expense") expense: Long): ExpenseMutationResponseDto
}
