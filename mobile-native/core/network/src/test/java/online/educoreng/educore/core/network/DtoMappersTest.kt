package online.educoreng.educore.core.network

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import online.educoreng.educore.core.network.dto.AcademicContextDto
import online.educoreng.educore.core.network.dto.AcademicPeriodItemDto
import online.educoreng.educore.core.network.dto.BootstrapResponseDto
import online.educoreng.educore.core.network.dto.AttendanceSheetResponseDto
import online.educoreng.educore.core.network.dto.AttendanceStudentDto
import online.educoreng.educore.core.network.dto.ClassCapabilitiesDto
import online.educoreng.educore.core.network.dto.ClassIdentityDto
import online.educoreng.educore.core.network.dto.ClassListResponseDto
import online.educoreng.educore.core.network.dto.ClassSubjectDto
import online.educoreng.educore.core.network.dto.ClassSummaryDto
import online.educoreng.educore.core.network.dto.ClassStudentsResponseDto
import online.educoreng.educore.core.network.dto.DashboardActionDto
import online.educoreng.educore.core.network.dto.DashboardItemDto
import online.educoreng.educore.core.network.dto.DashboardMetricDto
import online.educoreng.educore.core.network.dto.DashboardResponseDto
import online.educoreng.educore.core.network.dto.DashboardSectionDto
import online.educoreng.educore.core.network.dto.ModuleDto
import online.educoreng.educore.core.network.dto.PaginationDto
import online.educoreng.educore.core.network.dto.SchoolDto
import online.educoreng.educore.core.network.dto.TenantAccessDto
import online.educoreng.educore.core.network.dto.UserDto
import online.educoreng.educore.core.network.dto.StudentSummaryDto
import online.educoreng.educore.core.network.dto.toDomain
import online.educoreng.educore.core.model.AttendanceStatus

class DtoMappersTest {
    @Test
    fun `bootstrap mapping preserves tenant and permission boundaries`() {
        val response = BootstrapResponseDto(
            contractVersion = 1,
            user = UserDto(
                id = 7,
                name = "Ada Teacher",
                email = "ada@example.test",
                staffId = "STF007",
                roleKey = "subject_teacher",
                role = "Subject Teacher",
                roles = listOf("subject_teacher"),
                portal = "staff",
            ),
            school = SchoolDto(42, "Unity School", "unity-school"),
            academic = AcademicContextDto(
                session = AcademicPeriodItemDto(3, "2026/2027"),
                term = AcademicPeriodItemDto(8, "First Term"),
            ),
            access = TenantAccessDto(true, "allowed", "Available"),
            permissions = listOf("scores.entry", "timetable.view"),
            modules = listOf(ModuleDto("scores", "Scores", "/scores", "scores")),
            serverTime = "2026-08-27T13:00:00+01:00",
        )

        val session = response.toDomain()

        assertEquals("42", session.school.tenantKey)
        assertEquals("subject_teacher", session.user.roleKey)
        assertEquals("2026/2027", session.academicPeriod.sessionName)
        assertTrue(session.can("scores.entry"))
        assertTrue(session.hasModule("scores"))
    }

    @Test
    fun `dashboard mapping preserves server display values and cache provenance`() {
        val response = DashboardResponseDto(
            contractVersion = 1,
            scope = "staff",
            roleKey = "subject_teacher",
            generatedAt = "2026-08-28T10:00:00+01:00",
            metrics = listOf(
                DashboardMetricDto("students", "Students", "1,248", "brand", "students"),
            ),
            quickActions = listOf(
                DashboardActionDto("classes", "Classes", "classes", "/classes"),
            ),
            sections = listOf(
                DashboardSectionDto(
                    key = "today",
                    title = "Today's schedule",
                    items = listOf(
                        DashboardItemDto("1", "Biology", "SS 2 Gold", "10:20–11:00", moduleKey = "timetable"),
                    ),
                ),
            ),
        )

        val dashboard = response.toDomain(cachedAtEpochMs = 1234L, isFromCache = true)

        assertEquals("1,248", dashboard.metrics.single().displayValue)
        assertEquals("classes", dashboard.quickActions.single().moduleKey)
        assertEquals("timetable", dashboard.sections.single().items.single().moduleKey)
        assertEquals(1234L, dashboard.cachedAtEpochMs)
        assertTrue(dashboard.isFromCache)
    }

    @Test
    fun `class mapping preserves merged roles subjects capabilities and cache provenance`() {
        val response = ClassListResponseDto(
            contractVersion = 2,
            generatedAt = "2026-08-29T08:00:00+01:00",
            classes = listOf(
                ClassSummaryDto(
                    id = 19,
                    name = "SS 2 Gold",
                    level = ClassIdentityDto(4, "SS 2"),
                    formTutor = ClassIdentityDto(7, "Ada Teacher"),
                    roles = listOf("form_tutor", "subject_teacher"),
                    subjects = listOf(ClassSubjectDto(12, "Biology", "BIO")),
                    studentsCount = 31,
                    capabilities = ClassCapabilitiesDto(
                        viewStudents = true,
                        markAttendance = true,
                        enterScores = true,
                        viewResults = true,
                        planLessons = true,
                    ),
                ),
            ),
        )

        val catalogue = response.toDomain(cachedAtEpochMs = 2200L, isFromCache = true)
        val classSummary = catalogue.classes.single()

        assertEquals(listOf("form_tutor", "subject_teacher"), classSummary.roles)
        assertEquals("Biology", classSummary.subjects.single().name)
        assertTrue(classSummary.capabilities.markAttendance)
        assertTrue(classSummary.capabilities.planLessons)
        assertEquals(2200L, catalogue.cachedAtEpochMs)
        assertTrue(catalogue.isFromCache)
    }

    @Test
    fun `attendance mapping keeps unanswered rows explicit and maps saved status`() {
        val response = AttendanceSheetResponseDto(
            contractVersion = 2,
            generatedAt = "2026-08-29T08:00:00+01:00",
            classIdentity = ClassIdentityDto(19, "SS 2 Gold"),
            date = "2026-08-29",
            version = "sheet-v1",
            students = listOf(
                AttendanceStudentDto(1, "Amina Bello", "STU0001", status = "late"),
                AttendanceStudentDto(2, "Musa Haruna", "STU0002"),
            ),
        )

        val sheet = response.toDomain()

        assertEquals(AttendanceStatus.LATE, sheet.students.first().status)
        assertEquals(null, sheet.students.last().status)
        assertEquals("sheet-v1", sheet.version)
    }

    @Test
    fun `student directory mapping preserves server pagination`() {
        val classSummary = ClassSummaryDto(
            id = 19,
            name = "SS 2 Gold",
            studentsCount = 120,
            capabilities = ClassCapabilitiesDto(true, false, false, false, false),
        )
        val response = ClassStudentsResponseDto(
            contractVersion = 2,
            generatedAt = "2026-09-01T08:00:00+01:00",
            classSummary = classSummary,
            students = listOf(StudentSummaryDto(1, "Amina Bello", "STU001")),
            meta = PaginationDto(currentPage = 2, lastPage = 3, perPage = 50, total = 120),
        )

        val snapshot = response.toDomain()

        assertEquals(2, snapshot.currentPage)
        assertEquals(3, snapshot.lastPage)
        assertEquals(120, snapshot.total)
    }

    @Test
    fun `repository mapping preserves class term subject hierarchy and readable content`() {
        val hierarchy = online.educoreng.educore.core.network.dto.RepositoryHierarchyResponseDto(
            contractVersion = 1,
            generatedAt = "2026-08-29T12:00:00+01:00",
            metrics = online.educoreng.educore.core.network.dto.RepositoryMetricsDto(1, 1, 1, 2),
            classes = listOf(
                online.educoreng.educore.core.network.dto.RepositoryClassGroupDto(
                    name = "SS 2",
                    resourceCount = 1,
                    terms = listOf(
                        online.educoreng.educore.core.network.dto.RepositoryTermGroupDto(
                            name = "First Term",
                            resourceCount = 1,
                            subjects = listOf(online.educoreng.educore.core.network.dto.RepositorySubjectGroupDto("Biology", 1)),
                        ),
                    ),
                ),
            ),
        ).toDomain(fromCache = true)

        assertEquals("SS 2", hierarchy.classes.single().name)
        assertEquals("First Term", hierarchy.classes.single().terms.single().name)
        assertEquals("Biology", hierarchy.classes.single().terms.single().subjects.single().name)
        assertTrue(hierarchy.isFromCache)
    }

    @Test
    fun `repository catalogue mapping preserves server pagination`() {
        val dto = online.educoreng.educore.core.network.dto.RepositoryResourcesResponseDto(
            contractVersion = 1,
            generatedAt = "2026-09-01T08:00:00+01:00",
            filters = online.educoreng.educore.core.network.dto.RepositoryFiltersDto(),
            meta = PaginationDto(currentPage = 1, lastPage = 38, perPage = 50, total = 1_886),
        )

        val catalogue = dto.toDomain()

        assertEquals(1, catalogue.currentPage)
        assertEquals(38, catalogue.lastPage)
        assertEquals(1_886, catalogue.total)
    }

    @Test
    fun `lesson mapping keeps teacher editable sections and note state`() {
        val dto = online.educoreng.educore.core.network.dto.LessonPlanDto(
            id = 8,
            version = "2026-08-29T12:00:00.000000Z",
            subject = online.educoreng.educore.core.network.dto.ScoreSubjectDto(2, "Biology"),
            classLevel = online.educoreng.educore.core.network.dto.ScoreSubjectDto(4, "SS 2"),
            curriculumType = "nerdc",
            deliveryType = "regular",
            topic = "Cell Structure",
            durationMinutes = 40,
            status = "draft",
            aiGenerated = true,
            sections = listOf(online.educoreng.educore.core.network.dto.LessonPlanSectionDto("presentation", "Presentation", "STEP I")),
            note = online.educoreng.educore.core.network.dto.LessonNoteDto(
                revision = 1,
                status = "draft",
                depth = "standard",
                html = "<h2>Cell Structure</h2><p>Cells are the basic units of life.</p>",
            ),
        )

        val plan = dto.toDomain()

        assertEquals("Presentation", plan.sections.single().label)
        assertTrue(plan.note?.plainText?.contains("basic units of life") == true)
        assertEquals("draft", plan.status)
    }
}
