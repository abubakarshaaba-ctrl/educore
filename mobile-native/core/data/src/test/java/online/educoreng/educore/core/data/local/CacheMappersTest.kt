package online.educoreng.educore.core.data.local

import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import online.educoreng.educore.core.model.AcademicPeriod
import online.educoreng.educore.core.model.ModuleDescriptor
import online.educoreng.educore.core.model.SchoolIdentity
import online.educoreng.educore.core.model.SessionSnapshot
import online.educoreng.educore.core.model.TenantAccess
import online.educoreng.educore.core.model.UserIdentity

class CacheMappersTest {
    @Test
    fun `cached session round trip remains tenant scoped and ordered`() {
        val original = SessionSnapshot(
            user = UserIdentity(
                id = 12,
                name = "Teacher One",
                email = null,
                staffId = "STF012",
                roleKey = "form_teacher",
                roleLabel = "Form Teacher",
                roles = listOf("form_teacher"),
                portal = "staff",
            ),
            school = SchoolIdentity(9, "Greenfield Academy", "greenfield"),
            academicPeriod = AcademicPeriod(1, "2026/2027", 2, "Second Term"),
            access = TenantAccess(true, "trial", "Free plan", "warning", null),
            permissions = setOf("attendance", "reports.remarks"),
            modules = listOf(
                ModuleDescriptor("attendance", "Attendance", "/attendance", "attendance"),
                ModuleDescriptor("reports", "Report Cards", "/reports", "reports"),
            ),
            serverTime = "2026-08-27T13:00:00+01:00",
        )

        val cached = original.toCache(nowEpochMs = 1234L)
        val restored = cached.toDomain()

        assertEquals("9", cached.session.tenantKey)
        assertEquals(1234L, cached.session.cachedAtEpochMs)
        assertEquals(listOf("attendance", "reports"), restored.modules.map { it.key })
        assertTrue(restored.permissions.contains("attendance"))
        assertEquals(original.school, restored.school)
    }
}
