package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PublishedResultDto
import online.educoreng.educore.core.network.dto.PublishedResultsResponseDto
import online.educoreng.educore.core.network.dto.toDomain
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class PublishedParallelResultsMapperTest {
    @Test
    fun published_parallel_results_keep_curriculum_and_class_context() {
        val response = PublishedResultsResponseDto(
            results = emptyList(),
            parallelResults = listOf(
                PublishedResultDto(
                    id = 41,
                    term = "First Term",
                    session = "2026/2027",
                    average = 82.5,
                    totalScore = 330.0,
                    subjectsOffered = 4,
                    subjectsFailed = 0,
                    resultType = "parallel",
                    curriculumName = "Tahfiz",
                    resultClassName = "Level 2",
                    resultClassArmName = "A",
                    maximumTotal = 400.0,
                    publicationStatus = "published",
                ),
            ),
        )

        val mapped = response.toDomain()

        assertTrue(mapped.results.isEmpty())
        assertEquals(1, mapped.parallelResults.size)
        with(mapped.parallelResults.single()) {
            assertEquals("parallel", resultType)
            assertEquals("Tahfiz", curriculumName)
            assertEquals("Level 2", resultClassName)
            assertEquals("A", resultClassArmName)
            assertEquals(400.0, maximumTotal!!, 0.001)
        }
    }
}
