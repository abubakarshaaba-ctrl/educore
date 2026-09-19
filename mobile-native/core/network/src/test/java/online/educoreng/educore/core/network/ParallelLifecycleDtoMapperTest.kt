package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.ParallelLifecycleArmDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleArmSubjectTeacherDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleAssessmentTemplateDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleClassDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleCurriculumDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleEnrolmentDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleConventionalClassArmDto
import online.educoreng.educore.core.network.dto.ParallelLifecyclePaginationDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleProgrammeSubjectDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleGradeDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleStudentAssignmentDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleStudentDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleStudentPageDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleResponseDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleSessionDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleStaffDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleSubjectAssignmentDto
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
            assessmentTemplates = listOf(
                ParallelLifecycleAssessmentTemplateDto(100, "Parallel 40/60"),
            ),
            curricula = listOf(
                ParallelLifecycleCurriculumDto(
                    id = 10,
                    name = "Islamiyyah",
                    code = "ISL",
                    defaultAssessmentTemplateId = 100,
                    defaultAssessmentTemplateName = "Parallel 40/60",
                    subjects = listOf(
                        ParallelLifecycleProgrammeSubjectDto(
                            id = 80,
                            name = "Qur'an",
                            code = "QRN",
                        ),
                    ),
                    grades = listOf(
                        ParallelLifecycleGradeDto(
                            id = 110,
                            gradeLetter = "A",
                            minScore = 70.0,
                            maxScore = 100.0,
                            remark = "Excellent",
                        ),
                    ),
                    classes = listOf(
                        ParallelLifecycleClassDto(
                            id = 30,
                            name = "Mutawassitah 1",
                            code = "M1",
                            sortOrder = 1,
                            assessmentTemplateId = 100,
                            assessmentTemplateName = "Parallel 40/60",
                            subjects = listOf(
                                ParallelLifecycleSubjectAssignmentDto(
                                    assignmentId = 70,
                                    subjectId = 80,
                                    subjectName = "Qur'an",
                                    defaultTeacherId = 90,
                                    defaultTeacherName = "Teacher Default",
                                ),
                            ),
                            arms = listOf(
                                ParallelLifecycleArmDto(
                                    id = 40,
                                    name = "A",
                                    code = "A",
                                    capacity = 35,
                                    isActive = true,
                                    subjectTeachers = listOf(
                                        ParallelLifecycleArmSubjectTeacherDto(
                                            subjectId = 80,
                                            teacherId = 91,
                                            teacherName = "Teacher Override",
                                        ),
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),
            sessions = listOf(ParallelLifecycleSessionDto(20, "2026/2027", true)),
            staff = listOf(
                ParallelLifecycleStaffDto(90, "Teacher Default"),
                ParallelLifecycleStaffDto(91, "Teacher Override"),
            ),
            armTeacherOverridesReady = true,
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
        assertEquals("Parallel 40/60", domain.assessmentTemplates.single().name)
        assertEquals("Qur'an", domain.selectedCurriculum?.subjects?.single()?.name)
        assertEquals("A", domain.selectedCurriculum?.grades?.single()?.gradeLetter)
        assertEquals(100, domain.selectedCurriculum?.defaultAssessmentTemplateId)
        val level = domain.selectedCurriculum?.classes?.single()
        assertEquals(1, level?.sortOrder)
        assertEquals("Parallel 40/60", level?.assessmentTemplateName)
        val arm = level?.arms?.single()
        assertEquals("A", arm?.name)
        assertEquals(35, arm?.capacity)
        assertEquals("Qur'an", level?.subjects?.single()?.subjectName)
        assertEquals("Teacher Default", level?.subjects?.single()?.defaultTeacherName)
        assertEquals("Teacher Override", arm?.subjectTeachers?.single()?.teacherName)
        assertEquals(2, domain.staff.size)
        assertTrue(domain.armTeacherOverridesReady)
        assertEquals("A", domain.enrolments.single().armName)
        assertTrue(domain.sessions.single().isCurrent)
    }

    @Test
    fun `maps student assignment catalogue with current parallel placement`() {
        val page = ParallelLifecycleStudentPageDto(
            parallelCurriculumId = 10,
            sessionId = 20,
            conventionalClassArms = listOf(
                ParallelLifecycleConventionalClassArmDto(5, "JSS 1 A"),
            ),
            students = listOf(
                ParallelLifecycleStudentDto(
                    id = 60,
                    name = "Amina Bello",
                    admissionNumber = "STU001",
                    gender = "female",
                    conventionalClassArmId = 5,
                    conventionalClassName = "JSS 1 A",
                    assignment = ParallelLifecycleStudentAssignmentDto(
                        enrolmentId = 50,
                        classId = 30,
                        className = "Mutawassitah 1",
                        armId = 40,
                        armName = "A",
                    ),
                ),
            ),
            pagination = ParallelLifecyclePaginationDto(
                currentPage = 1,
                lastPage = 1,
                perPage = 50,
                total = 1,
            ),
        ).toDomain()

        assertEquals(10, page.curriculumId)
        assertEquals("JSS 1 A", page.conventionalClassArms.single().name)
        assertEquals("Amina Bello", page.students.single().name)
        assertEquals("Mutawassitah 1", page.students.single().assignment?.className)
        assertEquals("A", page.students.single().assignment?.armName)
        assertEquals(1, page.pagination.total)
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
