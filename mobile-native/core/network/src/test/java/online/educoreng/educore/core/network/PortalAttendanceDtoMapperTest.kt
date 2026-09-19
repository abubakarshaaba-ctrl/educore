package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.PortalAttendanceChildDto
import online.educoreng.educore.core.network.dto.PortalAttendanceProgrammeDto
import online.educoreng.educore.core.network.dto.PortalAttendanceRecordDto
import online.educoreng.educore.core.network.dto.PortalAttendanceResponseDto
import online.educoreng.educore.core.network.dto.PortalAttendanceSectionDto
import online.educoreng.educore.core.network.dto.PortalAttendanceStudentDto
import online.educoreng.educore.core.network.dto.PortalAttendanceSummaryDto
import online.educoreng.educore.core.network.dto.PortalAttendanceTermDto
import online.educoreng.educore.core.network.dto.toDomain
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class PortalAttendanceDtoMapperTest {
    @Test
    fun maps_conventional_parallel_child_and_term_data() {
        val dto = PortalAttendanceResponseDto(
            student = PortalAttendanceStudentDto(8, "Amina Parent", "STD008"),
            children = listOf(
                PortalAttendanceChildDto(8, "Amina Parent"),
                PortalAttendanceChildDto(9, "Bilal Parent"),
            ),
            terms = listOf(
                PortalAttendanceTermDto(3, "First Term", "2026/2027", true),
            ),
            selectedTermId = 3,
            conventional = PortalAttendanceSectionDto(
                stats = PortalAttendanceSummaryDto(
                    total = 2,
                    present = 1,
                    absent = 1,
                    rate = 50.0,
                ),
                records = listOf(
                    PortalAttendanceRecordDto("2026-09-14", "present", "On time"),
                ),
            ),
            parallelProgrammes = listOf(
                PortalAttendanceProgrammeDto(
                    curriculumId = 4,
                    curriculumName = "Islamiyyah",
                    className = "Mutawassitah 1",
                    armName = "B",
                    stats = PortalAttendanceSummaryDto(
                        total = 1,
                        present = 1,
                        rate = 100.0,
                    ),
                    records = listOf(
                        PortalAttendanceRecordDto("2026-09-14", "present"),
                    ),
                ),
            ),
        )

        val workspace = dto.toDomain()

        assertEquals(8L, workspace.student.id)
        assertEquals("STD008", workspace.student.admissionNumber)
        assertEquals(2, workspace.children.size)
        assertEquals(3L, workspace.selectedTermId)
        assertEquals(2, workspace.conventional.stats.total)
        assertEquals(50.0, workspace.conventional.stats.rate, 0.0)
        assertEquals("Islamiyyah", workspace.parallelProgrammes.single().curriculumName)
        assertEquals("B", workspace.parallelProgrammes.single().armName)
        assertFalse(workspace.isFromCache)
    }

    @Test
    fun marks_workspace_as_cached_when_repository_uses_offline_payload() {
        val dto = PortalAttendanceResponseDto(
            student = PortalAttendanceStudentDto(8, "Amina Parent"),
            conventional = PortalAttendanceSectionDto(
                stats = PortalAttendanceSummaryDto(),
            ),
        )

        assertTrue(dto.toDomain(fromCache = true).isFromCache)
    }
}
