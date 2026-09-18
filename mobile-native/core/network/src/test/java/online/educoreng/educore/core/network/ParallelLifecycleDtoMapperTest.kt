package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.ParallelLifecycleArmDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleClassDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleCurriculumDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleEnrolmentDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleResponseDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleSessionDto
import online.educoreng.educore.core.network.dto.ParallelPromotionPreviewCountsDto
import online.educoreng.educore.core.network.dto.ParallelPromotionPreviewResponseDto
import online.educoreng.educore.core.network.dto.ParallelPromotionPreviewRowDto
import online.educoreng.educore.core.network.dto.toDomain
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test

class ParallelLifecycleDtoMapperTest {
    @Test
    fun `maps class arms and learner placements without losing hierarchy`() {
        val domain = ParallelLifecycleResponseDto(
            selectedCurriculumId = 10,
            selectedSessionId = 20,
            curricula = listOf(
                ParallelLifecycleCurriculumDto(
                    id = 10,
                    name = "Islamiyyah",
                    code = "ISL",
                    classes = listOf(
                        ParallelLifecycleClassDto(
                            id = 30,
                            name = "Mutawassitah 1",
                            code = "M1",
                            arms = listOf(
                                ParallelLifecycleArmDto(
                                    id = 40,
                                    name = "A",
                                    code = "A",
                                    capacity = 35,
                                    isActive = true,
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            sessions = listOf(ParallelLifecycleSessionDto(20, "2026/2027", true)),
            enrolments = listOf(
                ParallelLifecycleEnrolmentDto(
                    id = 50,
                    studentId = 60,
                    studentName = "Amina Bello",
                    admissionNumber = "STU001",
                    classId = 30,
                    className = "Mutawassitah 1",
                    armId = 40,
                    armName = "A",
                ),
            ),
            generatedAt = "2026-09-18T15:00:00+01:00",
        ).toDomain()

        assertEquals("Islamiyyah", domain.selectedCurriculum?.name)
        assertEquals("A", domain.selectedCurriculum?.classes?.single()?.arms?.single()?.name)
        assertEquals(35, domain.selectedCurriculum?.classes?.single()?.arms?.single()?.capacity)
        assertEquals("A", domain.enrolments.single().armName)
        assertTrue(domain.sessions.single().isCurrent)
    }

    @Test
    fun `maps promotion preview decisions and destination arms`() {
        val preview = ParallelPromotionPreviewResponseDto(
            sourceSession = ParallelLifecycleSessionDto(1, "2026/2027", false),
            targetSession = ParallelLifecycleSessionDto(2, "2027/2028", true),
            counts = ParallelPromotionPreviewCountsDto(
                total = 1,
                promoted = 1,
                blocked = 0,
            ),
            rows = listOf(
                ParallelPromotionPreviewRowDto(
                    studentId = 60,
                    studentName = "Amina Bello",
                    admissionNumber = "STU001",
                    sourceClass = "Mutawassitah 1",
                    sourceArm = "A",
                    average = 82.5,
                    failedSubjects = 0,
                    decision = "promoted",
                    destinationClass = "Mutawassitah 2",
                    destinationArm = "A",
                    reason = "Promotion criteria satisfied.",
                ),
            ),
        ).toDomain()

        assertEquals(1, preview.counts.promoted)
        assertEquals("Mutawassitah 2", preview.rows.single().destinationClass)
        assertEquals("A", preview.rows.single().destinationArm)
    }
}
