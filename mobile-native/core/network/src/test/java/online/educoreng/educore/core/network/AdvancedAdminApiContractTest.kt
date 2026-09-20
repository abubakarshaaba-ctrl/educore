package online.educoreng.educore.core.network

import org.junit.Assert.assertEquals
import org.junit.Test
import retrofit2.http.GET
import retrofit2.http.POST
import retrofit2.http.PUT

class AdvancedAdminApiContractTest {
    @Test
    fun advanced_administration_endpoints_match_laravel_mobile_contract() {
        val methods = AdvancedAdminApi::class.java.declaredMethods.associateBy { it.name }

        assertEquals("advanced-admin", methods.getValue("overview").getAnnotation(GET::class.java).value)
        assertEquals("advanced-admin/migrations", methods.getValue("createMigration").getAnnotation(POST::class.java).value)
        assertEquals("advanced-admin/migrations/{migration}/ingest", methods.getValue("ingest").getAnnotation(POST::class.java).value)
        assertEquals("advanced-admin/migrations/{migration}/verify", methods.getValue("verify").getAnnotation(POST::class.java).value)
        assertEquals("advanced-admin/migrations/{migration}/blueprint", methods.getValue("reconstructBlueprint").getAnnotation(POST::class.java).value)
        assertEquals("advanced-admin/migration-requests/{migrationRequest}/approve", methods.getValue("approve").getAnnotation(POST::class.java).value)
        assertEquals("advanced-admin/migration-requests/{migrationRequest}/reject", methods.getValue("reject").getAnnotation(POST::class.java).value)
        assertEquals("advanced-admin/backup", methods.getValue("backup").getAnnotation(POST::class.java).value)
    }

    @Test
    fun parallel_skills_and_cumulative_endpoints_are_native_contracts() {
        val methods = EduCoreApi::class.java.declaredMethods.associateBy { it.name }

        assertEquals("parallel-curriculum/skills", methods.getValue("parallelSkills").getAnnotation(GET::class.java).value)
        assertEquals("parallel-curriculum/skills", methods.getValue("saveParallelSkills").getAnnotation(PUT::class.java).value)
        assertEquals("scores/cumulative-broadsheet/pdf", methods.getValue("downloadCumulativeBroadsheetPdf").getAnnotation(GET::class.java).value)
        assertEquals("parallel-curriculum/results/broadsheet/pdf", methods.getValue("downloadParallelBroadsheetPdf").getAnnotation(GET::class.java).value)
        assertEquals(
            "parallel-curriculum/results/classes/{class}/students/{student}/cumulative/pdf",
            methods.getValue("downloadParallelCumulativeStudentResultPdf").getAnnotation(GET::class.java).value,
        )
    }
}
