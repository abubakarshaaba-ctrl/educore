package online.educoreng.educore.presentation

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import online.educoreng.educore.core.model.CbtQuestion
import online.educoreng.educore.core.model.CbtSection

class CbtQuestionUnitsTest {
    @Test
    fun `online section presents one objective per view`() {
        val section = section("online", listOf(question(1, null, "1"), question(2, null, "2")))

        val units = questionUnits(section)

        assertEquals(2, units.size)
        assertEquals(listOf(1L), units.first().questions.map { it.id })
    }

    @Test
    fun `paper section keeps all branches under the whole parent question`() {
        val section = section(
            "paper",
            listOf(
                question(1, null, "1"),
                question(2, 1, "1.a"),
                question(3, 2, "1.a.i"),
                question(4, null, "2"),
            ),
        )

        val units = questionUnits(section)

        assertEquals(2, units.size)
        assertEquals(listOf(1L, 2L, 3L), units.first().questions.map { it.id })
        assertTrue(units.none { unit -> unit.questions.any { it.options.isNotEmpty() } })
    }

    private fun section(answerMode: String, questions: List<CbtQuestion>) = CbtSection(
        1, "A", "Section A", null, null, answerMode, if (answerMode == "online") "objective" else "theory", 10.0, questions,
    )

    private fun question(id: Long, parentId: Long?, path: String) = CbtQuestion(
        id, parentId, path, path.count { it == '.' }, "essay", "Question $path", null, emptyMap(), 2.0, true, false, false,
    )
}
