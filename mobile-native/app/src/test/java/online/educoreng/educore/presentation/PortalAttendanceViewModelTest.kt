package online.educoreng.educore.presentation

import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.ExperimentalCoroutinesApi
import kotlinx.coroutines.test.StandardTestDispatcher
import kotlinx.coroutines.test.TestDispatcher
import kotlinx.coroutines.test.advanceUntilIdle
import kotlinx.coroutines.test.resetMain
import kotlinx.coroutines.test.runTest
import kotlinx.coroutines.test.setMain
import online.educoreng.educore.core.common.AppError
import online.educoreng.educore.core.common.AppResult
import online.educoreng.educore.core.data.repository.PortalAttendanceRepository
import online.educoreng.educore.core.model.PortalAttendanceChild
import online.educoreng.educore.core.model.PortalAttendanceSection
import online.educoreng.educore.core.model.PortalAttendanceStudent
import online.educoreng.educore.core.model.PortalAttendanceSummary
import online.educoreng.educore.core.model.PortalAttendanceTerm
import online.educoreng.educore.core.model.PortalAttendanceWorkspace
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test

@OptIn(ExperimentalCoroutinesApi::class)
class PortalAttendanceViewModelTest {
    private val dispatcher: TestDispatcher = StandardTestDispatcher()

    @Before
    fun setUp() {
        Dispatchers.setMain(dispatcher)
    }

    @After
    fun tearDown() {
        Dispatchers.resetMain()
    }

    @Test
    fun load_then_child_and_term_selection_preserve_context() = runTest(dispatcher) {
        val repository = FakePortalAttendanceRepository()
        val first = workspace(studentId = 8, selectedTermId = 3)
        repository.result = AppResult.Success(first)

        val viewModel = PortalAttendanceViewModel(repository)
        viewModel.load()
        advanceUntilIdle()

        assertEquals(first, viewModel.uiState.value.workspace)
        assertFalse(viewModel.uiState.value.isLoading)
        assertEquals(PortalAttendanceRequest(null, null), repository.requests.single())

        val childWorkspace = workspace(studentId = 9, selectedTermId = 3)
        repository.result = AppResult.Success(childWorkspace)
        viewModel.selectChild(9)
        advanceUntilIdle()

        assertEquals(PortalAttendanceRequest(9, 3), repository.requests.last())
        assertEquals(9L, viewModel.uiState.value.workspace?.student?.id)

        val secondTermWorkspace = workspace(studentId = 9, selectedTermId = 4)
        repository.result = AppResult.Success(secondTermWorkspace)
        viewModel.selectTerm(4)
        advanceUntilIdle()

        assertEquals(PortalAttendanceRequest(9, 4), repository.requests.last())
        assertEquals(4L, viewModel.uiState.value.workspace?.selectedTermId)
    }

    @Test
    fun failed_refresh_preserves_existing_workspace_and_exposes_error() = runTest(dispatcher) {
        val repository = FakePortalAttendanceRepository()
        val cached = workspace(studentId = 8, selectedTermId = 3, isFromCache = true)
        repository.result = AppResult.Success(cached)

        val viewModel = PortalAttendanceViewModel(repository)
        viewModel.load()
        advanceUntilIdle()

        repository.result = AppResult.Failure(
            AppError.NetworkUnavailable("Offline while refreshing attendance.")
        )
        viewModel.selectTerm(4)
        advanceUntilIdle()

        assertNotNull(viewModel.uiState.value.workspace)
        assertEquals(cached, viewModel.uiState.value.workspace)
        assertEquals("Offline while refreshing attendance.", viewModel.uiState.value.errorMessage)
        assertFalse(viewModel.uiState.value.isLoading)
        assertTrue(viewModel.uiState.value.workspace?.isFromCache == true)
    }

    private fun workspace(
        studentId: Long,
        selectedTermId: Long,
        isFromCache: Boolean = false,
    ) = PortalAttendanceWorkspace(
        student = PortalAttendanceStudent(studentId, "Learner $studentId", "STD$studentId"),
        children = listOf(
            PortalAttendanceChild(8, "Amina"),
            PortalAttendanceChild(9, "Bilal"),
        ),
        terms = listOf(
            PortalAttendanceTerm(3, "First Term", "2026/2027", true),
            PortalAttendanceTerm(4, "Second Term", "2026/2027", false),
        ),
        selectedTermId = selectedTermId,
        conventional = PortalAttendanceSection(
            stats = PortalAttendanceSummary(1, 1, 0, 0, 0, 100.0),
            records = emptyList(),
        ),
        parallelProgrammes = emptyList(),
        isFromCache = isFromCache,
    )
}

private data class PortalAttendanceRequest(
    val childId: Long?,
    val termId: Long?,
)

private class FakePortalAttendanceRepository : PortalAttendanceRepository {
    val requests = mutableListOf<PortalAttendanceRequest>()
    var result: AppResult<PortalAttendanceWorkspace> =
        AppResult.Failure(AppError.Unexpected("No fake result configured."))

    override suspend fun load(
        childId: Long?,
        termId: Long?,
    ): AppResult<PortalAttendanceWorkspace> {
        requests += PortalAttendanceRequest(childId, termId)
        return result
    }
}
