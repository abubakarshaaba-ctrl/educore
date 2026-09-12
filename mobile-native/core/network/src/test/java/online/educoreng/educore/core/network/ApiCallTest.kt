package online.educoreng.educore.core.network

import com.squareup.moshi.Moshi
import com.squareup.moshi.kotlin.reflect.KotlinJsonAdapterFactory
import kotlinx.coroutines.runBlocking
import okhttp3.MediaType.Companion.toMediaType
import okhttp3.ResponseBody.Companion.toResponseBody
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import retrofit2.HttpException
import retrofit2.Response

class ApiCallTest {
    private val moshi = Moshi.Builder()
        .addLast(KotlinJsonAdapterFactory())
        .build()

    @Test
    fun `server error preserves bootstrap request reference`() = runBlocking {
        val body = """{"message":"Workspace unavailable.","request_id":"BOOT-AB12CD34"}"""
            .toResponseBody("application/json".toMediaType())
        val response = Response.error<Unit>(503, body)

        val result = safeApiCall<Unit>(moshi) {
            throw HttpException(response)
        }

        assertTrue(result is AppResult.Failure)
        val error = (result as AppResult.Failure).error
        assertTrue(error is AppError.Server)
        error as AppError.Server
        assertEquals(503, error.statusCode)
        assertEquals("BOOT-AB12CD34", error.requestId)
        assertEquals("Workspace unavailable.", error.userMessage)
    }

    @Test
    fun `untrusted request reference is discarded`() = runBlocking {
        val body = """{"message":"Workspace unavailable.","request_id":"bad reference with spaces"}"""
            .toResponseBody("application/json".toMediaType())
        val response = Response.error<Unit>(500, body)

        val result = safeApiCall<Unit>(moshi) {
            throw HttpException(response)
        }

        val error = (result as AppResult.Failure).error as AppError.Server
        assertNull(error.requestId)
    }
}
