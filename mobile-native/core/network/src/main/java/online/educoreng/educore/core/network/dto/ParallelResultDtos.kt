package online.educoreng.educore.core.network.dto

import com.squareup.moshi.Json
import online.educoreng.educore.core.model.ParallelResultClassOption
import online.educoreng.educore.core.model.ParallelResultComponent
import online.educoreng.educore.core.model.ParallelResultRegister
import online.educoreng.educore.core.model.ParallelResultRegisterRow
import online.educoreng.educore.core.model.ParallelResultSubject
import online.educoreng.educore.core.model.ParallelResultTermOption
import online.educoreng.educore.core.model.ParallelResultWorkspace
import online.educoreng.educore.core.model.ParallelStudentResultDetail

data class ParallelResultWorkspaceDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    @param:Json(name = "selected_class_id") val selectedClassId: Long? = null,
    @param:Json(name = "selected_term_id") val selectedTermId: Long? = null,
    val classes: List<ParallelResultClassOptionDto> = emptyList(),
    val terms: List<ParallelResultTermOptionDto> = emptyList(),
    val report: ParallelResultRegisterDto? = null,
)

data class ParallelResultClassOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "curriculum_id") val curriculumId: Long,
    @param:Json(name = "curriculum_name") val curriculumName: String? = null,
    val label: String,
)

data class ParallelResultTermOptionDto(
    val id: Long,
    val name: String,
    @param:Json(name = "session_id") val sessionId: Long,
    @param:Json(name = "session_name") val sessionName: String? = null,
    @param:Json(name = "is_current") val isCurrent: Boolean = false,
    val label: String,
)

data class ParallelResultRegisterDto(
    @param:Json(name = "curriculum_id") val curriculumId: Long,
    @param:Json(name = "curriculum_name") val curriculumName: String? = null,
    @param:Json(name = "class_id") val classId: Long,
    @param:Json(name = "class_name") val className: String,
    @param:Json(name = "term_id") val termId: Long,
    val term: String,
    val session: String? = null,
    @param:Json(name = "template_name") val templateName: String? = null,
    @param:Json(name = "component_weight") val componentWeight: Double = 0.0,
    @param:Json(name = "grading_source") val gradingSource: String = "programme",
    @param:Json(name = "grading_scale_complete") val gradingScaleComplete: Boolean = false,
    @param:Json(name = "is_published") val isPublished: Boolean = false,
    @param:Json(name = "can_publish") val canPublish: Boolean = false,
    val blockers: List<String> = emptyList(),
    @param:Json(name = "students_count") val studentsCount: Int = 0,
    @param:Json(name = "subjects_count") val subjectsCount: Int = 0,
    @param:Json(name = "complete_students_count") val completeStudentsCount: Int = 0,
    @param:Json(name = "ungraded_subject_results_count") val ungradedSubjectResultsCount: Int = 0,
    val rows: List<ParallelResultRegisterRowDto> = emptyList(),
)

data class ParallelResultRegisterRowDto(
    @param:Json(name = "student_id") val studentId: Long,
    @param:Json(name = "student_name") val studentName: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "arm_name") val armName: String? = null,
    @param:Json(name = "completed_subject_count") val completedSubjectCount: Int = 0,
    @param:Json(name = "subject_count") val subjectCount: Int = 0,
    @param:Json(name = "grand_total") val grandTotal: Double = 0.0,
    @param:Json(name = "maximum_total") val maximumTotal: Double = 0.0,
    val average: Double? = null,
    val position: Int? = null,
    @param:Json(name = "failed_subjects") val failedSubjects: Int = 0,
    val complete: Boolean = false,
)

data class ParallelStudentResultDetailDto(
    @param:Json(name = "contract_version") val contractVersion: Int = 1,
    @param:Json(name = "class_id") val classId: Long,
    @param:Json(name = "class_name") val className: String,
    @param:Json(name = "curriculum_name") val curriculumName: String? = null,
    @param:Json(name = "term_id") val termId: Long,
    val term: String,
    val session: String? = null,
    @param:Json(name = "is_published") val isPublished: Boolean = false,
    @param:Json(name = "grading_source") val gradingSource: String = "programme",
    val student: ParallelStudentResultRowDto,
)

data class ParallelStudentResultRowDto(
    val id: Long,
    val name: String,
    @param:Json(name = "admission_number") val admissionNumber: String? = null,
    @param:Json(name = "arm_name") val armName: String? = null,
    @param:Json(name = "subject_count") val subjectCount: Int = 0,
    @param:Json(name = "completed_subject_count") val completedSubjectCount: Int = 0,
    val complete: Boolean = false,
    @param:Json(name = "grand_total") val grandTotal: Double = 0.0,
    @param:Json(name = "maximum_total") val maximumTotal: Double = 0.0,
    val average: Double? = null,
    @param:Json(name = "failed_subjects") val failedSubjects: Int = 0,
    val position: Int? = null,
    val subjects: List<ParallelResultSubjectDto> = emptyList(),
)

data class ParallelResultSubjectDto(
    @param:Json(name = "subject_id") val subjectId: Long,
    val subject: String,
    val complete: Boolean = false,
    @param:Json(name = "raw_total") val rawTotal: Double = 0.0,
    val percentage: Double? = null,
    val grade: String? = null,
    val remark: String? = null,
    @param:Json(name = "is_pass") val isPass: Boolean? = null,
    val components: List<ParallelResultComponentDto> = emptyList(),
)

data class ParallelResultComponentDto(
    val id: Long,
    val name: String,
    val maximum: Double,
    val score: Double? = null,
)

data class ParallelResultPublicationRequestDto(
    @param:Json(name = "class_id") val classId: Long,
    @param:Json(name = "term_id") val termId: Long,
)

fun ParallelResultWorkspaceDto.toDomain(): ParallelResultWorkspace =
    ParallelResultWorkspace(
        selectedClassId = selectedClassId,
        selectedTermId = selectedTermId,
        classes = classes.map {
            ParallelResultClassOption(
                id = it.id,
                name = it.name,
                curriculumId = it.curriculumId,
                curriculumName = it.curriculumName,
                label = it.label,
            )
        },
        terms = terms.map {
            ParallelResultTermOption(
                id = it.id,
                name = it.name,
                sessionId = it.sessionId,
                sessionName = it.sessionName,
                isCurrent = it.isCurrent,
                label = it.label,
            )
        },
        report = report?.toDomain(),
    )

private fun ParallelResultRegisterDto.toDomain(): ParallelResultRegister =
    ParallelResultRegister(
        curriculumId = curriculumId,
        curriculumName = curriculumName,
        classId = classId,
        className = className,
        termId = termId,
        term = term,
        session = session,
        templateName = templateName,
        componentWeight = componentWeight,
        gradingSource = gradingSource,
        gradingScaleComplete = gradingScaleComplete,
        isPublished = isPublished,
        canPublish = canPublish,
        blockers = blockers,
        studentsCount = studentsCount,
        subjectsCount = subjectsCount,
        completeStudentsCount = completeStudentsCount,
        ungradedSubjectResultsCount = ungradedSubjectResultsCount,
        rows = rows.map {
            ParallelResultRegisterRow(
                studentId = it.studentId,
                studentName = it.studentName,
                admissionNumber = it.admissionNumber,
                armName = it.armName,
                completedSubjectCount = it.completedSubjectCount,
                subjectCount = it.subjectCount,
                grandTotal = it.grandTotal,
                maximumTotal = it.maximumTotal,
                average = it.average,
                position = it.position,
                failedSubjects = it.failedSubjects,
                complete = it.complete,
            )
        },
    )

fun ParallelStudentResultDetailDto.toDomain(): ParallelStudentResultDetail =
    ParallelStudentResultDetail(
        classId = classId,
        className = className,
        curriculumName = curriculumName,
        termId = termId,
        term = term,
        session = session,
        isPublished = isPublished,
        gradingSource = gradingSource,
        studentId = student.id,
        studentName = student.name,
        admissionNumber = student.admissionNumber,
        armName = student.armName,
        subjectCount = student.subjectCount,
        completedSubjectCount = student.completedSubjectCount,
        complete = student.complete,
        grandTotal = student.grandTotal,
        maximumTotal = student.maximumTotal,
        average = student.average,
        failedSubjects = student.failedSubjects,
        position = student.position,
        subjects = student.subjects.map { subject ->
            ParallelResultSubject(
                subjectId = subject.subjectId,
                subject = subject.subject,
                complete = subject.complete,
                rawTotal = subject.rawTotal,
                percentage = subject.percentage,
                grade = subject.grade,
                remark = subject.remark,
                isPass = subject.isPass,
                components = subject.components.map {
                    ParallelResultComponent(
                        id = it.id,
                        name = it.name,
                        maximum = it.maximum,
                        score = it.score,
                    )
                },
            )
        },
    )
