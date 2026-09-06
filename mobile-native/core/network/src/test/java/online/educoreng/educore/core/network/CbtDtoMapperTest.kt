package online.educoreng.educore.core.network

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import online.educoreng.educore.core.network.dto.CbtAttemptResponseDto
import online.educoreng.educore.core.network.dto.CbtQuestionDto
import online.educoreng.educore.core.network.dto.CbtSectionDto
import online.educoreng.educore.core.network.dto.CbtSessionDto
import online.educoreng.educore.core.network.dto.toDomain

class CbtDtoMapperTest {
    @Test
    fun `attempt mapping preserves server version answers and paper theory boundary`() {
        val response = CbtAttemptResponseDto(
            contractVersion = 1,
            session = CbtSessionDto(
                id = 41,
                examId = 8,
                status = "in_progress",
                attemptNumber = 2,
                version = "3",
                remainingSeconds = 2_400,
                serverTime = "2026-08-31T10:00:00+01:00",
                answers = mapOf("11" to "b"),
                flaggedQuestions = listOf(11),
            ),
            sections = listOf(
                CbtSectionDto(
                    id = 2,
                    code = "B",
                    name = "Section B",
                    answerMode = "paper",
                    sectionType = "theory",
                    maxMarks = 20.0,
                    questions = listOf(
                        CbtQuestionDto(
                            id = 12,
                            displayPath = "1",
                            level = 0,
                            type = "essay",
                            text = "Explain the process.",
                            options = emptyMap(),
                            marks = 20.0,
                            requiresAnswer = true,
                            instructionOnly = false,
                            hasImage = false,
                        ),
                    ),
                ),
            ),
        )

        val attempt = response.toDomain()

        assertEquals("3", attempt.session.version)
        assertEquals("b", attempt.session.answers[11])
        assertTrue(11L in attempt.session.flaggedQuestions)
        assertEquals("paper", attempt.sections.single().answerMode)
        assertTrue(attempt.sections.single().questions.single().options.isEmpty())
    }
}
