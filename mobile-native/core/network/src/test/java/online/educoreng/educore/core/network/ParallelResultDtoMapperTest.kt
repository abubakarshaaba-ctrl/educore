package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.ParallelResultClassOptionDto
import online.educoreng.educore.core.network.dto.ParallelResultComponentDto
import online.educoreng.educore.core.network.dto.ParallelResultRegisterDto
import online.educoreng.educore.core.network.dto.ParallelResultRegisterRowDto
import online.educoreng.educore.core.network.dto.ParallelResultSubjectDto
import online.educoreng.educore.core.network.dto.ParallelResultTermOptionDto
import online.educoreng.educore.core.network.dto.ParallelResultWorkspaceDto
import online.educoreng.educore.core.network.dto.ParallelStudentResultDetailDto
import online.educoreng.educore.core.network.dto.ParallelStudentResultRowDto
import online.educoreng.educore.core.network.dto.toDomain
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

class ParallelResultDtoMapperTest {
    @Test
    fun `maps parallel result register and publication state`() {
        val domain = ParallelResultWorkspaceDto(
            selectedClassId = 30,
            selectedTermId = 20,
            classes = listOf(
                ParallelResultClassOptionDto(
                    id = 30,
                    name = "Mutawassitah 1",
                    curriculumId = 10,
                    curriculumName = "Islamiyyah",
                    label = "Islamiyyah · Mutawassitah 1",
                ),
            ),
            terms = listOf(
                ParallelResultTermOptionDto(
                    id = 20,
                    name = "First Term",
                    sessionId = 15,
                    sessionName = "2026/2027",
                    isCurrent = true,
                    label = "2026/2027 · First Term",
                ),
            ),
            report = ParallelResultRegisterDto(
                curriculumId = 10,
                curriculumName = "Islamiyyah",
                classId = 30,
                className = "Mutawassitah 1",
                termId = 20,
                term = "First Term",
                session = "2026/2027",
                templateName = "Parallel 40/60",
                componentWeight = 100.0,
                gradingSource = "programme",
                gradingScaleComplete = true,
                isPublished = false,
                canPublish = true,
                studentsCount = 1,
                subjectsCount = 1,
                completeStudentsCount = 1,
                rows = listOf(
                    ParallelResultRegisterRowDto(
                        studentId = 60,
                        studentName = "Amina Bello",
                        admissionNumber = "STU001",
                        armName = "A",
                        completedSubjectCount = 1,
                        subjectCount = 1,
                        grandTotal = 83.0,
                        maximumTotal = 100.0,
                        average = 83.0,
                        position = 1,
                        failedSubjects = 0,
                        complete = true,
                    ),
                ),
            ),
        ).toDomain()

        assertEquals("Islamiyyah · Mutawassitah 1", domain.classes.single().label)
        assertEquals("2026/2027 · First Term", domain.terms.single().label)
        assertTrue(domain.report?.canPublish == true)
        assertFalse(domain.report?.isPublished == true)
        assertEquals("Amina Bello", domain.report?.rows?.single()?.studentName)
        assertEquals(83.0, domain.report?.rows?.single()?.average ?: 0.0, 0.001)
    }

    @Test
    fun `maps detailed learner parallel result`() {
        val domain = ParallelStudentResultDetailDto(
            classId = 30,
            className = "Mutawassitah 1",
            curriculumName = "Islamiyyah",
            termId = 20,
            term = "First Term",
            session = "2026/2027",
            isPublished = true,
            gradingSource = "programme",
            student = ParallelStudentResultRowDto(
                id = 60,
                name = "Amina Bello",
                admissionNumber = "STU001",
                armName = "A",
                subjectCount = 1,
                completedSubjectCount = 1,
                complete = true,
                grandTotal = 83.0,
                maximumTotal = 100.0,
                average = 83.0,
                failedSubjects = 0,
                position = 1,
                subjects = listOf(
                    ParallelResultSubjectDto(
                        subjectId = 80,
                        subject = "Qur'an",
                        complete = true,
                        rawTotal = 83.0,
                        percentage = 83.0,
                        grade = "A",
                        remark = "Excellent",
                        isPass = true,
                        components = listOf(
                            ParallelResultComponentDto(1, "CA", 40.0, 35.0),
                            ParallelResultComponentDto(2, "Exam", 60.0, 48.0),
                        ),
                    ),
                ),
            ),
        ).toDomain()

        assertEquals("Amina Bello", domain.studentName)
        assertEquals("Qur'an", domain.subjects.single().subject)
        assertEquals("A", domain.subjects.single().grade)
        assertEquals(2, domain.subjects.single().components.size)
        assertTrue(domain.isPublished)
    }
}
