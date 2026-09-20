package online.educoreng.educore.core.network

import online.educoreng.educore.core.network.dto.BootstrapResponseDto
import online.educoreng.educore.core.network.dto.DashboardResponseDto
import online.educoreng.educore.core.network.dto.ForgotPasswordRequestDto
import online.educoreng.educore.core.network.dto.LoginRequestDto
import online.educoreng.educore.core.network.dto.LoginResponseDto
import online.educoreng.educore.core.network.dto.MessageDto
import online.educoreng.educore.core.network.dto.AttendanceSheetResponseDto
import online.educoreng.educore.core.network.dto.ClassDetailResponseDto
import online.educoreng.educore.core.network.dto.ClassListResponseDto
import online.educoreng.educore.core.network.dto.ClassStudentsResponseDto
import online.educoreng.educore.core.network.dto.ClockInRequestDto
import online.educoreng.educore.core.network.dto.ProxyAttendanceColleaguesResponseDto
import online.educoreng.educore.core.network.dto.ProxyClockInRequestDto
import online.educoreng.educore.core.network.dto.SaveAttendanceRequestDto
import online.educoreng.educore.core.network.dto.SaveAttendanceResponseDto
import online.educoreng.educore.core.network.dto.StaffAttendanceResponseDto
import online.educoreng.educore.core.network.dto.StudentProfileResponseDto
import online.educoreng.educore.core.network.dto.PublishedResultsResponseDto
import online.educoreng.educore.core.network.dto.PortalAttendanceResponseDto
import online.educoreng.educore.core.network.dto.ParallelTransferRequestDto
import online.educoreng.educore.core.network.dto.ParallelPromotionRuleMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelPromotionRequestDto
import online.educoreng.educore.core.network.dto.ParallelPromotionPreviewResponseDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleResponseDto
import online.educoreng.educore.core.network.dto.ParallelLifecycleStudentPageDto
import online.educoreng.educore.core.network.dto.ParallelOperationsResponseDto
import online.educoreng.educore.core.network.dto.ParallelPeriodMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelPeriodMutationResponseDto
import online.educoreng.educore.core.network.dto.ParallelAttendanceMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelAttendanceMutationResponseDto
import online.educoreng.educore.core.network.dto.ParallelWorkingDaysMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelWorkingDaysMutationResponseDto
import online.educoreng.educore.core.network.dto.ParallelStaffClockRequestDto
import online.educoreng.educore.core.network.dto.ParallelStaffClockResponseDto
import online.educoreng.educore.core.network.dto.ParallelArmTeachingModeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelResultWorkspaceDto
import online.educoreng.educore.core.network.dto.ParallelStudentResultDetailDto
import online.educoreng.educore.core.network.dto.ParallelResultPublicationRequestDto
import online.educoreng.educore.core.network.dto.ParallelStudentAssignmentRequestDto
import online.educoreng.educore.core.network.dto.ParallelSkillWorkspaceDto
import online.educoreng.educore.core.network.dto.ParallelSkillSaveRequestDto
import online.educoreng.educore.core.network.dto.ParallelSkillSaveResponseDto
import online.educoreng.educore.core.network.dto.ParallelProgrammeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelClassMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelSubjectMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelClassSubjectMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelProgrammeGradeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelGradeMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelArmMutationRequestDto
import online.educoreng.educore.core.network.dto.ParallelArmTeacherMutationRequestDto
import online.educoreng.educore.core.network.dto.SaveScoresRequestDto
import online.educoreng.educore.core.network.dto.SaveScoresResponseDto
import online.educoreng.educore.core.network.dto.ScoreAssignmentsResponseDto
import online.educoreng.educore.core.network.dto.ScoreSheetResponseDto
import online.educoreng.educore.core.network.dto.ScheduleResponseDto
import online.educoreng.educore.core.network.dto.RepositoryHierarchyResponseDto
import online.educoreng.educore.core.network.dto.RepositoryResourcesResponseDto
import online.educoreng.educore.core.network.dto.RepositoryResourceResponseDto
import online.educoreng.educore.core.network.dto.LessonOptionsResponseDto
import online.educoreng.educore.core.network.dto.LessonPlansResponseDto
import online.educoreng.educore.core.network.dto.LessonPlanResponseDto
import online.educoreng.educore.core.network.dto.LessonPlanMutationRequestDto
import online.educoreng.educore.core.network.dto.VersionedRequestDto
import online.educoreng.educore.core.network.dto.LessonNoteMutationRequestDto
import online.educoreng.educore.core.network.dto.CbtAttemptResponseDto
import online.educoreng.educore.core.network.dto.CbtBeginRequestDto
import online.educoreng.educore.core.network.dto.CbtExamsResponseDto
import online.educoreng.educore.core.network.dto.CbtIntegrityRequestDto
import online.educoreng.educore.core.network.dto.CbtIntegrityResponseDto
import online.educoreng.educore.core.network.dto.CbtPreflightResponseDto
import online.educoreng.educore.core.network.dto.CbtSaveRequestDto
import online.educoreng.educore.core.network.dto.CbtSubmitRequestDto
import online.educoreng.educore.core.network.dto.OperationsResponseDto
import online.educoreng.educore.core.network.dto.EventsResponseDto
import online.educoreng.educore.core.network.dto.CreateEventRequestDto
import online.educoreng.educore.core.network.dto.CreateEventResponseDto
import online.educoreng.educore.core.network.dto.MessageMutationResponseDto
import online.educoreng.educore.core.network.dto.MessageRecipientsResponseDto
import online.educoreng.educore.core.network.dto.MessagesResponseDto
import online.educoreng.educore.core.network.dto.MessageThreadResponseDto
import online.educoreng.educore.core.network.dto.NotificationResponseDto
import online.educoreng.educore.core.network.dto.NotificationsResponseDto
import online.educoreng.educore.core.network.dto.PushTokenRequestDto
import online.educoreng.educore.core.network.dto.PortalSessionRequestDto
import online.educoreng.educore.core.network.dto.PortalSessionResponseDto
import online.educoreng.educore.core.network.dto.ReadAllResponseDto
import okhttp3.MultipartBody
import okhttp3.RequestBody
import okhttp3.ResponseBody
import retrofit2.http.DELETE
import retrofit2.http.PATCH
import retrofit2.http.Streaming
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Path
import retrofit2.http.POST
import retrofit2.http.Query
import retrofit2.http.PUT
import retrofit2.http.Multipart
import retrofit2.http.Part

interface EduCoreApi {
    @POST("auth/login")
    suspend fun login(@Body request: LoginRequestDto): LoginResponseDto

    @POST("auth/forgot-password")
    suspend fun forgotPassword(@Body request: ForgotPasswordRequestDto): MessageDto

    @GET("bootstrap")
    suspend fun bootstrap(): BootstrapResponseDto

    @GET("dashboard")
    suspend fun dashboard(): DashboardResponseDto

    @Streaming
    @GET("id-card/photo-file")
    suspend fun staffPhoto(): ResponseBody

    @POST("portal/session")
    suspend fun portalSession(@Body request: PortalSessionRequestDto): PortalSessionResponseDto

    @GET("classes")
    suspend fun classes(
        @Query("search") search: String? = null,
        @Query("per_page") perPage: Int = 100,
        @Query("page") page: Int = 1,
    ): ClassListResponseDto

    @GET("classes/{classArm}")
    suspend fun classDetail(@Path("classArm") classArmId: Long): ClassDetailResponseDto

    @GET("classes/{classArm}/students")
    suspend fun classStudents(
        @Path("classArm") classArmId: Long,
        @Query("search") search: String? = null,
        @Query("per_page") perPage: Int = 100,
        @Query("page") page: Int = 1,
    ): ClassStudentsResponseDto

    @GET("classes/{classArm}/students/{student}")
    suspend fun studentProfile(
        @Path("classArm") classArmId: Long,
        @Path("student") studentId: Long,
    ): StudentProfileResponseDto

    @GET("classes/{classArm}/attendance")
    suspend fun attendanceSheet(
        @Path("classArm") classArmId: Long,
        @Query("date") date: String,
    ): AttendanceSheetResponseDto

    @POST("classes/{classArm}/attendance")
    suspend fun saveAttendance(
        @Path("classArm") classArmId: Long,
        @Body request: SaveAttendanceRequestDto,
    ): SaveAttendanceResponseDto

    @GET("staff-attendance")
    suspend fun staffAttendance(): StaffAttendanceResponseDto

    @POST("staff-attendance/clock-in")
    suspend fun clockIn(@Body request: ClockInRequestDto): MessageDto

    @POST("staff-attendance/clock-out")
    suspend fun clockOut(): MessageDto

    @GET("staff-attendance/colleagues")
    suspend fun proxyAttendanceColleagues(
        @Query("q") search: String? = null,
    ): ProxyAttendanceColleaguesResponseDto

    @POST("staff-attendance/proxy-clock-in")
    suspend fun proxyClockIn(@Body request: ProxyClockInRequestDto): MessageDto

    @GET("scores/teaching")
    suspend fun scoreAssignments(): ScoreAssignmentsResponseDto

    @GET("scores/sheet")
    suspend fun scoreSheet(
        @Query("class_arm_id") classId: Long,
        @Query("subject_id") subjectId: Long,
        @Query("term_id") termId: Long? = null,
    ): ScoreSheetResponseDto

    @POST("scores/save")
    suspend fun saveScores(@Body request: SaveScoresRequestDto): SaveScoresResponseDto

    @GET("scores/cumulative-broadsheet")
    suspend fun cumulativeBroadsheet(
        @Query("class_arm_id") classArmId: Long? = null,
        @Query("session_id") sessionId: Long? = null,
    ): ResponseBody

    @Streaming
    @GET("scores/cumulative-broadsheet/pdf")
    suspend fun downloadCumulativeBroadsheetPdf(
        @Query("class_arm_id") classArmId: Long,
        @Query("session_id") sessionId: Long,
    ): ResponseBody

    @GET("parallel-scores/teaching")
    suspend fun parallelScoreAssignments(): ScoreAssignmentsResponseDto

    @GET("parallel-scores/sheet")
    suspend fun parallelScoreSheet(
        @Query("class_arm_id") classId: Long,
        @Query("subject_id") subjectId: Long,
        @Query("term_id") termId: Long? = null,
    ): ScoreSheetResponseDto

    @POST("parallel-scores/save")
    suspend fun saveParallelScores(@Body request: SaveScoresRequestDto): SaveScoresResponseDto

    @GET("parallel-curriculum/skills")
    suspend fun parallelSkills(
        @Query("arm_id") armId: Long? = null,
        @Query("term_id") termId: Long? = null,
    ): ParallelSkillWorkspaceDto

    @PUT("parallel-curriculum/skills")
    suspend fun saveParallelSkills(
        @Body request: ParallelSkillSaveRequestDto,
    ): ParallelSkillSaveResponseDto

    @GET("parallel-curriculum/results")
    suspend fun parallelResults(
        @Query("class_id") classId: Long? = null,
        @Query("term_id") termId: Long? = null,
    ): ParallelResultWorkspaceDto

    @GET("parallel-curriculum/results/classes/{class}/students/{student}")
    suspend fun parallelStudentResult(
        @Path("class") classId: Long,
        @Path("student") studentId: Long,
        @Query("term_id") termId: Long,
    ): ParallelStudentResultDetailDto

    @Streaming
    @GET("parallel-curriculum/results/export")
    suspend fun downloadParallelResultExport(
        @Query("class_id") classId: Long,
        @Query("term_id") termId: Long,
        @Query("format") format: String,
    ): ResponseBody

    @Streaming
    @GET("parallel-curriculum/results/classes/{class}/students/{student}/pdf")
    suspend fun downloadParallelStudentResultPdf(
        @Path("class") classId: Long,
        @Path("student") studentId: Long,
        @Query("term_id") termId: Long,
    ): ResponseBody

    @GET("parallel-curriculum/results/broadsheet")
    suspend fun parallelBroadsheet(
        @Query("mode") mode: String = "termly",
        @Query("class_id") classId: Long? = null,
        @Query("arm_id") armId: Long? = null,
        @Query("term_id") termId: Long? = null,
        @Query("session_id") sessionId: Long? = null,
    ): ResponseBody

    @Streaming
    @GET("parallel-curriculum/results/broadsheet/pdf")
    suspend fun downloadParallelBroadsheetPdf(
        @Query("mode") mode: String = "termly",
        @Query("class_id") classId: Long,
        @Query("arm_id") armId: Long? = null,
        @Query("term_id") termId: Long? = null,
        @Query("session_id") sessionId: Long? = null,
    ): ResponseBody

    @GET("parallel-curriculum/results/classes/{class}/students/{student}/cumulative")
    suspend fun parallelCumulativeStudentResult(
        @Path("class") classId: Long,
        @Path("student") studentId: Long,
        @Query("session_id") sessionId: Long,
    ): ResponseBody

    @Streaming
    @GET("parallel-curriculum/results/classes/{class}/students/{student}/cumulative/pdf")
    suspend fun downloadParallelCumulativeStudentResultPdf(
        @Path("class") classId: Long,
        @Path("student") studentId: Long,
        @Query("session_id") sessionId: Long,
    ): ResponseBody

    @POST("parallel-curriculum/results/publish")
    suspend fun publishParallelResult(
        @Body request: ParallelResultPublicationRequestDto,
    ): MessageDto

    @POST("parallel-curriculum/results/unpublish")
    suspend fun unpublishParallelResult(
        @Body request: ParallelResultPublicationRequestDto,
    ): MessageDto

    @GET("parallel-curriculum/operations")
    suspend fun parallelOperations(
        @Query("parallel_curriculum_id") curriculumId: Long? = null,
        @Query("session_id") sessionId: Long? = null,
        @Query("term_id") termId: Long? = null,
        @Query("class_id") classId: Long? = null,
        @Query("arm_id") armId: Long? = null,
        @Query("date") date: String? = null,
    ): ParallelOperationsResponseDto

    @POST("parallel-curriculum/operations/periods")
    suspend fun createParallelTimetablePeriod(
        @Body request: ParallelPeriodMutationRequestDto,
    ): ParallelPeriodMutationResponseDto

    @DELETE("parallel-curriculum/operations/periods/{period}")
    suspend fun deleteParallelTimetablePeriod(
        @Path("period") periodId: Long,
    ): MessageDto

    @POST("parallel-curriculum/operations/working-days")
    suspend fun saveParallelWorkingDays(
        @Body request: ParallelWorkingDaysMutationRequestDto,
    ): ParallelWorkingDaysMutationResponseDto

    @POST("parallel-curriculum/operations/staff-attendance/clock-in")
    suspend fun clockInParallelStaff(
        @Body request: ParallelStaffClockRequestDto,
    ): ParallelStaffClockResponseDto

    @POST("parallel-curriculum/operations/staff-attendance/clock-out")
    suspend fun clockOutParallelStaff(
        @Body request: ParallelStaffClockRequestDto,
    ): ParallelStaffClockResponseDto

    @POST("parallel-curriculum/operations/attendance")
    suspend fun saveParallelAttendance(
        @Body request: ParallelAttendanceMutationRequestDto,
    ): ParallelAttendanceMutationResponseDto

    @Streaming
    @GET("parallel-curriculum/operations/attendance/export")
    suspend fun downloadParallelAttendanceExport(
        @Query("arm_id") armId: Long,
        @Query("term_id") termId: Long,
        @Query("format") format: String,
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
    ): ResponseBody

    @GET("parallel-curriculum/lifecycle")
    suspend fun parallelLifecycle(
        @Query("parallel_curriculum_id") curriculumId: Long? = null,
        @Query("session_id") sessionId: Long? = null,
    ): ParallelLifecycleResponseDto

    @POST("parallel-curriculum/lifecycle/programmes")
    suspend fun createParallelProgramme(
        @Body request: ParallelProgrammeMutationRequestDto,
    ): MessageDto

    @PUT("parallel-curriculum/lifecycle/programmes/{curriculum}")
    suspend fun updateParallelProgramme(
        @Path("curriculum") curriculumId: Long,
        @Body request: ParallelProgrammeMutationRequestDto,
    ): MessageDto

    @POST("parallel-curriculum/lifecycle/classes")
    suspend fun createParallelClass(
        @Body request: ParallelClassMutationRequestDto,
    ): MessageDto

    @PUT("parallel-curriculum/lifecycle/classes/{class}")
    suspend fun updateParallelClass(
        @Path("class") classId: Long,
        @Body request: ParallelClassMutationRequestDto,
    ): MessageDto

    @POST("parallel-curriculum/lifecycle/subjects")
    suspend fun createParallelSubject(
        @Body request: ParallelSubjectMutationRequestDto,
    ): MessageDto

    @PUT("parallel-curriculum/lifecycle/subjects/{subject}")
    suspend fun updateParallelSubject(
        @Path("subject") subjectId: Long,
        @Body request: ParallelSubjectMutationRequestDto,
    ): MessageDto

    @POST("parallel-curriculum/lifecycle/class-subjects")
    suspend fun saveParallelClassSubject(
        @Body request: ParallelClassSubjectMutationRequestDto,
    ): MessageDto

    @DELETE("parallel-curriculum/lifecycle/class-subjects/{assignment}")
    suspend fun removeParallelClassSubject(
        @Path("assignment") assignmentId: Long,
    ): MessageDto

    @POST("parallel-curriculum/lifecycle/programme-grades")
    suspend fun saveParallelProgrammeGrade(
        @Body request: ParallelProgrammeGradeMutationRequestDto,
    ): MessageDto

    @DELETE("parallel-curriculum/lifecycle/programme-grades/{grade}")
    suspend fun deleteParallelProgrammeGrade(
        @Path("grade") gradeId: Long,
    ): MessageDto

    @GET("parallel-curriculum/lifecycle/students")
    suspend fun parallelLifecycleStudents(
        @Query("parallel_curriculum_id") curriculumId: Long,
        @Query("session_id") sessionId: Long,
        @Query("conventional_class_arm_id") conventionalClassArmId: Long? = null,
        @Query("assignment_status") assignmentStatus: String = "all",
        @Query("gender") gender: String? = null,
        @Query("q") search: String? = null,
        @Query("per_page") perPage: Int = 50,
        @Query("page") page: Int = 1,
    ): ParallelLifecycleStudentPageDto

    @Streaming
    @GET("parallel-curriculum/lifecycle/assignments/template")
    suspend fun downloadParallelStudentAssignmentTemplate(): ResponseBody

    @Multipart
    @POST("parallel-curriculum/lifecycle/assignments/import")
    suspend fun importParallelStudentAssignments(
        @Part("parallel_curriculum_id") curriculumId: RequestBody,
        @Part("session_id") sessionId: RequestBody,
        @Part assignmentFile: MultipartBody.Part,
    ): MessageDto

    @POST("parallel-curriculum/lifecycle/assignments")
    suspend fun assignParallelStudents(
        @Body request: ParallelStudentAssignmentRequestDto,
    ): MessageDto

    @DELETE("parallel-curriculum/lifecycle/assignments/{enrolment}")
    suspend fun removeParallelStudent(
        @Path("enrolment") enrolmentId: Long,
    ): MessageDto

    @GET("parallel-curriculum/lifecycle/promotion-preview")
    suspend fun parallelPromotionPreview(
        @Query("parallel_curriculum_id") curriculumId: Long,
        @Query("source_session_id") sourceSessionId: Long,
        @Query("target_session_id") targetSessionId: Long,
        @Query("source_class_ids[]") sourceClassIds: List<Long> = emptyList(),
    ): ParallelPromotionPreviewResponseDto

    @POST("parallel-curriculum/lifecycle/promotions/execute")
    suspend fun executeParallelPromotion(@Body request: ParallelPromotionRequestDto): MessageDto

    @POST("parallel-curriculum/lifecycle/transfers")
    suspend fun parallelTransfer(@Body request: ParallelTransferRequestDto): MessageDto

    @POST("parallel-curriculum/lifecycle/arms")
    suspend fun createParallelArm(@Body request: ParallelArmMutationRequestDto): MessageDto

    @PUT("parallel-curriculum/lifecycle/arms/{arm}")
    suspend fun updateParallelArm(
        @Path("arm") armId: Long,
        @Body request: ParallelArmMutationRequestDto,
    ): MessageDto

    @DELETE("parallel-curriculum/lifecycle/arms/{arm}")
    suspend fun archiveParallelArm(@Path("arm") armId: Long): MessageDto

    @POST("parallel-curriculum/lifecycle/arm-teachers")
    suspend fun saveParallelArmTeacher(
        @Body request: ParallelArmTeacherMutationRequestDto,
    ): MessageDto

    @POST("parallel-curriculum/lifecycle/arm-teaching-mode")
    suspend fun saveParallelArmTeachingMode(
        @Body request: ParallelArmTeachingModeMutationRequestDto,
    ): MessageDto

    @POST("parallel-curriculum/lifecycle/grades")
    suspend fun createParallelGrade(@Body request: ParallelGradeMutationRequestDto): MessageDto

    @DELETE("parallel-curriculum/lifecycle/grades/{grade}")
    suspend fun deleteParallelGrade(@Path("grade") gradeId: Long): MessageDto

    @POST("parallel-curriculum/lifecycle/promotion-rules")
    suspend fun saveParallelPromotionRule(@Body request: ParallelPromotionRuleMutationRequestDto): MessageDto

    @GET("student/results")
    suspend fun studentResults(): PublishedResultsResponseDto

    @GET("parent/results")
    suspend fun parentResults(@Query("child_id") childId: Long? = null): PublishedResultsResponseDto

    @GET("portal-attendance")
    suspend fun portalAttendance(
        @Query("child_id") childId: Long? = null,
        @Query("term_id") termId: Long? = null,
    ): PortalAttendanceResponseDto

    @GET("schedule")
    suspend fun schedule(
        @Query("class_arm_id") classId: Long? = null,
        @Query("child_id") childId: Long? = null,
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
    ): ScheduleResponseDto

    @GET("operations/{module}")
    suspend fun operations(@Path("module") module: String): OperationsResponseDto

    @GET("notifications")
    suspend fun notifications(@Query("status") status: String = "all", @Query("per_page") perPage: Int = 50): NotificationsResponseDto

    @POST("notifications/{notification}/read")
    suspend fun markNotificationRead(@Path("notification") id: Long): NotificationResponseDto

    @POST("notifications/read-all")
    suspend fun markAllNotificationsRead(): ReadAllResponseDto

    @GET("calendar/events")
    suspend fun communicationEvents(@Query("from") from: String? = null, @Query("to") to: String? = null): EventsResponseDto

    @POST("calendar/events")
    suspend fun createCommunicationEvent(@Body request: CreateEventRequestDto): CreateEventResponseDto

    @GET("messages")
    suspend fun messages(@Query("per_page") perPage: Int = 50): MessagesResponseDto

    @GET("messages/recipients")
    suspend fun messageRecipients(): MessageRecipientsResponseDto

    @GET("messages/{thread}")
    suspend fun messageThread(@Path("thread") threadId: Long): MessageThreadResponseDto

    @Multipart
    @POST("messages")
    suspend fun composeMessage(
        @Part("student_id") studentId: RequestBody,
        @Part("subject") subject: RequestBody,
        @Part("body") body: RequestBody,
        @Part attachment: MultipartBody.Part? = null,
    ): MessageMutationResponseDto

    @Multipart
    @POST("messages/{thread}/reply")
    suspend fun replyMessage(
        @Path("thread") threadId: Long,
        @Part("body") body: RequestBody,
        @Part attachment: MultipartBody.Part? = null,
    ): MessageMutationResponseDto

    @Streaming
    @GET("messages/replies/{reply}/attachment")
    suspend fun downloadMessageAttachment(@Path("reply") replyId: Long): ResponseBody

    @POST("push/register")
    suspend fun registerPushToken(@Body request: PushTokenRequestDto): MessageDto

    @POST("push/unregister")
    suspend fun unregisterPushToken(@Body request: PushTokenRequestDto): MessageDto

    @GET("academic-repository/classes")
    suspend fun repositoryHierarchy(): RepositoryHierarchyResponseDto

    @GET("academic-repository/resources")
    suspend fun repositoryResources(
        @Query("class") className: String? = null,
        @Query("term") term: String? = null,
        @Query("subject") subject: String? = null,
        @Query("query") query: String? = null,
        @Query("per_page") perPage: Int = 100,
        @Query("page") page: Int = 1,
    ): RepositoryResourcesResponseDto

    @GET("academic-repository/resources/{source}")
    suspend fun repositoryResource(@Path("source") sourceId: Long): RepositoryResourceResponseDto

    @Streaming
    @GET("academic-repository/resources/{source}/download")
    suspend fun downloadRepositoryResource(@Path("source") sourceId: Long): ResponseBody

    @GET("lesson-plans/options")
    suspend fun lessonOptions(): LessonOptionsResponseDto

    @GET("lesson-plans")
    suspend fun lessonPlans(@Query("per_page") perPage: Int = 100, @Query("page") page: Int = 1): LessonPlansResponseDto

    @GET("lesson-plans/{lessonPlan}")
    suspend fun lessonPlan(@Path("lessonPlan") lessonPlanId: Long): LessonPlanResponseDto

    @POST("lesson-plans")
    suspend fun createLessonPlan(@Body request: LessonPlanMutationRequestDto): LessonPlanResponseDto

    @PATCH("lesson-plans/{lessonPlan}")
    suspend fun updateLessonPlan(@Path("lessonPlan") lessonPlanId: Long, @Body request: LessonPlanMutationRequestDto): LessonPlanResponseDto

    @POST("lesson-plans/{lessonPlan}/generate")
    suspend fun generateLessonPlan(@Path("lessonPlan") lessonPlanId: Long, @Body request: VersionedRequestDto): LessonPlanResponseDto

    @POST("lesson-plans/{lessonPlan}/generate-note")
    suspend fun generateLessonNote(@Path("lessonPlan") lessonPlanId: Long, @Body request: VersionedRequestDto): LessonPlanResponseDto

    @PATCH("lesson-plans/{lessonPlan}/note")
    suspend fun updateLessonNote(@Path("lessonPlan") lessonPlanId: Long, @Body request: LessonNoteMutationRequestDto): LessonPlanResponseDto

    @POST("lesson-plans/{lessonPlan}/publish")
    suspend fun publishLessonPlan(@Path("lessonPlan") lessonPlanId: Long, @Body request: VersionedRequestDto): LessonPlanResponseDto

    @Streaming
    @GET("lesson-plans/{lessonPlan}/pdf")
    suspend fun downloadLessonPlanPdf(@Path("lessonPlan") lessonPlanId: Long): ResponseBody

    @Streaming
    @GET("lesson-plans/{lessonPlan}/note/pdf")
    suspend fun downloadLessonNotePdf(@Path("lessonPlan") lessonPlanId: Long): ResponseBody

    @GET("cbt/exams")
    suspend fun cbtExams(): CbtExamsResponseDto

    @GET("cbt/exams/{exam}/preflight")
    suspend fun cbtPreflight(@Path("exam") examId: Long): CbtPreflightResponseDto

    @POST("cbt/exams/{exam}/begin")
    suspend fun beginCbt(@Path("exam") examId: Long, @Body request: CbtBeginRequestDto): CbtAttemptResponseDto

    @GET("cbt/sessions/{session}")
    suspend fun cbtAttempt(@Path("session") sessionId: Long): CbtAttemptResponseDto

    @PUT("cbt/sessions/{session}/answers")
    suspend fun saveCbt(@Path("session") sessionId: Long, @Body request: CbtSaveRequestDto): CbtAttemptResponseDto

    @POST("cbt/sessions/{session}/integrity")
    suspend fun recordCbtIntegrity(@Path("session") sessionId: Long, @Body request: CbtIntegrityRequestDto): CbtIntegrityResponseDto

    @POST("cbt/sessions/{session}/submit")
    suspend fun submitCbt(@Path("session") sessionId: Long, @Body request: CbtSubmitRequestDto): CbtAttemptResponseDto

    @Streaming
    @GET("cbt/sessions/{session}/questions/{question}/image")
    suspend fun cbtQuestionImage(@Path("session") sessionId: Long, @Path("question") questionId: Long): ResponseBody

    // Low-level transport coverage for mobile administration/operations modules.
    // Typed repositories may wrap these endpoints incrementally, but keeping
    // them in the native API contract prevents backend/mobile route drift.
    @GET("academic-cycle")
    suspend fun mobileAcademicCycle(): ResponseBody

    @GET("subjects")
    suspend fun mobileSubjects(): ResponseBody

    @GET("curriculum")
    suspend fun mobileCurriculum(): ResponseBody

    @GET("skills")
    suspend fun mobileSkills(): ResponseBody

    @GET("skills/sheet")
    suspend fun mobileSkillsSheet(
        @Query("class_arm_id") classArmId: Long? = null,
        @Query("term_id") termId: Long? = null,
    ): ResponseBody

    @PUT("skills/sheet")
    suspend fun saveMobileSkillsSheet(@Body request: RequestBody): ResponseBody

    @GET("gradebook")
    suspend fun mobileGradebook(): ResponseBody

    @GET("reports")
    suspend fun mobileReports(): ResponseBody

    @GET("portal-accounts")
    suspend fun mobilePortalAccounts(): ResponseBody

    @GET("school-settings")
    suspend fun mobileSchoolSettings(): ResponseBody

    @GET("risk")
    suspend fun mobileRisk(): ResponseBody

    @GET("library/options")
    suspend fun mobileLibraryOptions(): ResponseBody

    @GET("inventory")
    suspend fun mobileInventory(): ResponseBody

    @GET("hostels")
    suspend fun mobileHostels(): ResponseBody

    @GET("transfers")
    suspend fun mobileTransfers(): ResponseBody

    @GET("admin/staff-attendance")
    suspend fun adminStaffAttendance(): ResponseBody

    @GET("admin/staff-attendance/report")
    suspend fun adminStaffAttendanceReport(
        @Query("from") from: String? = null,
        @Query("to") to: String? = null,
    ): ResponseBody

    @POST("admin/staff-attendance/manual")
    suspend fun adminStaffAttendanceManual(@Body request: RequestBody): ResponseBody

    @GET("admin/staff-attendance/offline")
    suspend fun adminOfflineAttendance(): ResponseBody

    @POST("admin/staff-attendance/offline/{record}")
    suspend fun processAdminOfflineAttendance(
        @Path("record") recordId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("admin/staff-attendance/proxy-reviews")
    suspend fun adminProxyAttendanceReviews(): ResponseBody

    @POST("admin/staff-attendance/proxy-reviews/{record}")
    suspend fun decideAdminProxyAttendance(
        @Path("record") recordId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("admin/staff-attendance/qr")
    suspend fun adminStaffAttendanceQr(): ResponseBody

    @POST("admin/staff-attendance/reset-qr")
    suspend fun resetAdminStaffAttendanceQr(): ResponseBody

    @PUT("admin/staff-attendance/settings")
    suspend fun updateAdminStaffAttendanceSettings(@Body request: RequestBody): ResponseBody

    @GET("platform/broadcasts")
    suspend fun platformBroadcasts(): ResponseBody

    @POST("platform/broadcasts")
    suspend fun createPlatformBroadcast(@Body request: RequestBody): ResponseBody

    @POST("platform/broadcasts/{broadcast}/expire")
    suspend fun expirePlatformBroadcast(
        @Path("broadcast") broadcastId: Long,
    ): ResponseBody

    // Complete transport parity for routes registered in routes/mobile-*.php.
    // These raw endpoints keep every native backend capability reachable even
    // before a feature-specific repository promotes it to a typed contract.

    @POST("academic-cycle/sessions")
    suspend fun createAcademicSession(@Body request: RequestBody): ResponseBody

    @PATCH("academic-cycle/sessions/{session}")
    suspend fun updateAcademicSession(
        @Path("session") sessionId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("academic-cycle/sessions/{session}/activate")
    suspend fun activateAcademicSession(@Path("session") sessionId: Long): ResponseBody

    @GET("academic-cycle/sessions/{session}/readiness")
    suspend fun academicSessionReadiness(@Path("session") sessionId: Long): ResponseBody

    @POST("academic-cycle/sessions/{session}/close")
    suspend fun closeAcademicSession(@Path("session") sessionId: Long): ResponseBody

    @DELETE("academic-cycle/sessions/{session}")
    suspend fun deleteAcademicSession(@Path("session") sessionId: Long): ResponseBody

    @POST("academic-cycle/terms")
    suspend fun createAcademicTerm(@Body request: RequestBody): ResponseBody

    @PATCH("academic-cycle/terms/{term}")
    suspend fun updateAcademicTerm(
        @Path("term") termId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("academic-cycle/terms/{term}/activate")
    suspend fun activateAcademicTerm(@Path("term") termId: Long): ResponseBody

    @GET("academic-cycle/terms/{term}/readiness")
    suspend fun academicTermReadiness(@Path("term") termId: Long): ResponseBody

    @POST("academic-cycle/terms/{term}/close")
    suspend fun closeAcademicTerm(@Path("term") termId: Long): ResponseBody

    @DELETE("academic-cycle/terms/{term}")
    suspend fun deleteAcademicTerm(@Path("term") termId: Long): ResponseBody

    @POST("curriculum/tracks")
    suspend fun createCurriculumTrack(@Body request: RequestBody): ResponseBody

    @PATCH("curriculum/tracks/{track}")
    suspend fun updateCurriculumTrack(
        @Path("track") trackId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @DELETE("curriculum/tracks/{track}")
    suspend fun deleteCurriculumTrack(@Path("track") trackId: Long): ResponseBody

    @POST("curriculum/rules")
    suspend fun createCurriculumRule(@Body request: RequestBody): ResponseBody

    @PATCH("curriculum/rules/{rule}")
    suspend fun updateCurriculumRule(
        @Path("rule") ruleId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @DELETE("curriculum/rules/{rule}")
    suspend fun deleteCurriculumRule(@Path("rule") ruleId: Long): ResponseBody

    @POST("subjects")
    suspend fun createMobileSubject(@Body request: RequestBody): ResponseBody

    @PATCH("subjects/{subject}")
    suspend fun updateMobileSubject(
        @Path("subject") subjectId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @DELETE("subjects/{subject}")
    suspend fun deleteMobileSubject(@Path("subject") subjectId: Long): ResponseBody

    @GET("classes/{classArm}/students/{student}/results")
    suspend fun classStudentResults(
        @Path("classArm") classArmId: Long,
        @Path("student") studentId: Long,
    ): ResponseBody

    @GET("fees")
    suspend fun mobileFees(): ResponseBody

    @POST("fees/generate")
    suspend fun generateMobileFees(@Body request: RequestBody): ResponseBody

    @POST("fees/invoices/{invoice}/payments")
    suspend fun recordMobileFeePayment(
        @Path("invoice") invoiceId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("expenses")
    suspend fun mobileExpenses(): ResponseBody

    @POST("expenses")
    suspend fun createMobileExpense(@Body request: RequestBody): ResponseBody

    @PATCH("expenses/{expense}")
    suspend fun updateMobileExpense(
        @Path("expense") expenseId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @DELETE("expenses/{expense}")
    suspend fun deleteMobileExpense(@Path("expense") expenseId: Long): ResponseBody

    @GET("payroll")
    suspend fun mobilePayroll(): ResponseBody

    @POST("payroll")
    suspend fun generateMobilePayroll(@Body request: RequestBody): ResponseBody

    @GET("payroll/{period}")
    suspend fun mobilePayrollPeriod(@Path("period") periodId: Long): ResponseBody

    @POST("payroll/{period}/approve")
    suspend fun approveMobilePayroll(@Path("period") periodId: Long): ResponseBody

    @POST("payroll/{period}/paid")
    suspend fun markMobilePayrollPaid(@Path("period") periodId: Long): ResponseBody

    @PUT("gradebook/remarks/{summary}")
    suspend fun updateMobileGradebookRemark(
        @Path("summary") summaryId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("risk/compute")
    suspend fun computeMobileRisk(@Body request: RequestBody): ResponseBody

    @PUT("risk/config")
    suspend fun updateMobileRiskConfig(@Body request: RequestBody): ResponseBody

    @GET("risk/{flag}")
    suspend fun mobileRiskFlag(@Path("flag") flagId: Long): ResponseBody

    @POST("risk/{flag}/acknowledge")
    suspend fun acknowledgeMobileRiskFlag(@Path("flag") flagId: Long): ResponseBody

    @POST("risk/{flag}/resolve")
    suspend fun resolveMobileRiskFlag(
        @Path("flag") flagId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("library/loans")
    suspend fun issueMobileLibraryLoan(@Body request: RequestBody): ResponseBody

    @POST("library/loans/{loan}/return")
    suspend fun returnMobileLibraryLoan(@Path("loan") loanId: Long): ResponseBody

    @POST("inventory")
    suspend fun createMobileInventoryAsset(@Body request: RequestBody): ResponseBody

    @PATCH("inventory/{asset}")
    suspend fun updateMobileInventoryAsset(
        @Path("asset") assetId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @DELETE("inventory/{asset}")
    suspend fun deleteMobileInventoryAsset(@Path("asset") assetId: Long): ResponseBody

    @POST("hostels")
    suspend fun createMobileHostel(@Body request: RequestBody): ResponseBody

    @POST("hostels/{hostel}/rooms")
    suspend fun createMobileHostelRoom(
        @Path("hostel") hostelId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("hostels/allocations")
    suspend fun allocateMobileHostel(@Body request: RequestBody): ResponseBody

    @POST("hostels/allocations/{allocation}/vacate")
    suspend fun vacateMobileHostelAllocation(
        @Path("allocation") allocationId: Long,
    ): ResponseBody

    @POST("portal-accounts/students/bulk")
    suspend fun createBulkStudentPortalAccounts(@Body request: RequestBody): ResponseBody

    @POST("portal-accounts/students/{student}")
    suspend fun createStudentPortalAccount(@Path("student") studentId: Long): ResponseBody

    @POST("portal-accounts/guardians/{guardian}")
    suspend fun createGuardianPortalAccount(@Path("guardian") guardianId: Long): ResponseBody

    @POST("portal-accounts/users/{portalUser}/reset-password")
    suspend fun resetPortalAccountPassword(
        @Path("portalUser") portalUserId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("portal-accounts/users/{portalUser}/toggle")
    suspend fun togglePortalAccount(@Path("portalUser") portalUserId: Long): ResponseBody

    @GET("mobile-release/latest")
    suspend fun latestMobileRelease(): ResponseBody

    @POST("mobile-release/notify")
    suspend fun notifyMobileRelease(@Body request: RequestBody): ResponseBody

    @POST("reports/compute")
    suspend fun computeMobileReports(@Body request: RequestBody): ResponseBody

    @POST("reports/publish")
    suspend fun publishMobileReports(@Body request: RequestBody): ResponseBody

    @POST("reports/unpublish")
    suspend fun unpublishMobileReports(@Body request: RequestBody): ResponseBody

    @Streaming
    @GET("reports/{summary}/pdf")
    suspend fun downloadMobileReportPdf(@Path("summary") summaryId: Long): ResponseBody

    @GET("school-messages")
    suspend fun mobileSchoolMessages(): ResponseBody

    @GET("school-messages/recipients")
    suspend fun mobileSchoolMessageRecipients(): ResponseBody

    @POST("school-messages")
    suspend fun createMobileSchoolMessage(@Body request: RequestBody): ResponseBody

    @GET("school-messages/{thread}")
    suspend fun mobileSchoolMessageThread(@Path("thread") threadId: Long): ResponseBody

    @POST("school-messages/{thread}/reply")
    suspend fun replyMobileSchoolMessage(
        @Path("thread") threadId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @PUT("school-settings")
    suspend fun saveMobileSchoolSettings(@Body request: RequestBody): ResponseBody

    @GET("staff/cbt/options")
    suspend fun staffCbtOptions(): ResponseBody

    @POST("staff/cbt/exams")
    suspend fun createStaffCbtExam(@Body request: RequestBody): ResponseBody

    @GET("staff/cbt/exams")
    suspend fun staffCbtExams(): ResponseBody

    @GET("staff/cbt/exams/{exam}")
    suspend fun staffCbtExam(@Path("exam") examId: Long): ResponseBody

    @POST("staff/cbt/exams/{exam}/publish")
    suspend fun publishStaffCbtExam(@Path("exam") examId: Long): ResponseBody

    @POST("staff/cbt/exams/{exam}/close")
    suspend fun closeStaffCbtExam(@Path("exam") examId: Long): ResponseBody

    @PATCH("staff/cbt/exams/{exam}/schedule")
    suspend fun rescheduleStaffCbtExam(
        @Path("exam") examId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("transfers/cross-school")
    suspend fun requestCrossSchoolTransfer(@Body request: RequestBody): ResponseBody

    @POST("transfers/cross-school/{transfer}/approve")
    suspend fun approveCrossSchoolTransfer(@Path("transfer") transferId: Long): ResponseBody

    @POST("transfers/cross-school/{transfer}/reject")
    suspend fun rejectCrossSchoolTransfer(
        @Path("transfer") transferId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("transfers/intra-class")
    suspend fun requestIntraClassTransfer(@Body request: RequestBody): ResponseBody

    @POST("transfers/intra-class/{transfer}/approve")
    suspend fun approveIntraClassTransfer(@Path("transfer") transferId: Long): ResponseBody

    @POST("transfers/intra-class/{transfer}/reject")
    suspend fun rejectIntraClassTransfer(
        @Path("transfer") transferId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("transfers/intra-class/{transfer}/cancel")
    suspend fun cancelIntraClassTransfer(@Path("transfer") transferId: Long): ResponseBody

    @POST("transfers/interclass")
    suspend fun requestInterclassTransfer(@Body request: RequestBody): ResponseBody

    @POST("transfers/interclass/{transfer}/approve")
    suspend fun approveInterclassTransfer(@Path("transfer") transferId: Long): ResponseBody

    @POST("transfers/interclass/{transfer}/reject")
    suspend fun rejectInterclassTransfer(
        @Path("transfer") transferId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("transfers/interclass/{transfer}/cancel")
    suspend fun cancelInterclassTransfer(@Path("transfer") transferId: Long): ResponseBody

    // Complete transport parity for routes declared directly in routes/api.php.
    @GET("academic-repository/knowledge")
    suspend fun academicRepositoryKnowledge(
        @Query("q") query: String? = null,
    ): ResponseBody

    @GET("academic-repository/knowledge/{topic}")
    suspend fun academicRepositoryKnowledgeTopic(
        @Path("topic") topic: String,
    ): ResponseBody

    @GET("academic-repository/knowledge/{topic}/generate/{type}")
    suspend fun generateAcademicRepositoryKnowledge(
        @Path("topic") topic: String,
        @Path("type") type: String,
    ): ResponseBody

    @POST("academic-repository/knowledge/{topic}/save-lesson-plan")
    suspend fun saveKnowledgeLessonPlan(
        @Path("topic") topic: String,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("academic-repository/knowledge/{topic}/save-student-note")
    suspend fun saveKnowledgeStudentNote(
        @Path("topic") topic: String,
        @Body request: RequestBody,
    ): ResponseBody

    @Streaming
    @GET("academic-repository/resources/{source}/content")
    suspend fun academicRepositoryResourceContent(
        @Path("source") source: Long,
    ): ResponseBody

    @GET("me")
    suspend fun mobileMe(): ResponseBody

    @GET("profile")
    suspend fun mobileProfile(): ResponseBody

    @PATCH("profile")
    suspend fun updateMobileProfile(@Body request: RequestBody): ResponseBody

    @PUT("profile/password")
    suspend fun updateMobilePassword(@Body request: RequestBody): ResponseBody

    @POST("profile/passport")
    suspend fun uploadMobilePassport(@Body request: RequestBody): ResponseBody

    @Streaming
    @GET("profile/passport-file")
    suspend fun mobilePassportFile(): ResponseBody

    @GET("portal/modules")
    suspend fun portalModules(): ResponseBody

    @GET("announcements")
    suspend fun mobileAnnouncements(): ResponseBody

    @POST("notifications/{announcement}/read")
    suspend fun markMobileAnnouncementRead(
        @Path("announcement") announcementId: Long,
    ): ResponseBody

    @GET("timetable/mine")
    suspend fun myMobileTimetable(): ResponseBody

    @GET("timetable/form-class")
    suspend fun mobileFormClassTimetable(): ResponseBody

    @GET("id-card")
    suspend fun mobileIdCard(): ResponseBody

    @Streaming
    @GET("id-card/pdf")
    suspend fun mobileIdCardPdf(): ResponseBody

    @Streaming
    @GET("id-card/signature-file")
    suspend fun mobileIdCardSignatureFile(): ResponseBody

    @POST("id-card/photo")
    suspend fun uploadMobileIdCardPhoto(@Body request: RequestBody): ResponseBody

    @GET("payslips")
    suspend fun mobilePayslips(): ResponseBody

    @GET("payslips/{item}")
    suspend fun mobilePayslip(@Path("item") payslipId: Long): ResponseBody

    @Streaming
    @GET("payslips/{item}/pdf")
    suspend fun mobilePayslipPdf(@Path("item") payslipId: Long): ResponseBody

    @GET("exam-duties")
    suspend fun mobileExamDuties(): ResponseBody

    @POST("devices/register")
    suspend fun registerMobileDevice(@Body request: RequestBody): ResponseBody

    @POST("devices/unregister")
    suspend fun unregisterMobileDevice(@Body request: RequestBody): ResponseBody

    @GET("student/dashboard")
    suspend fun studentDashboardRaw(): ResponseBody

    @GET("student/timetable")
    suspend fun studentTimetableRaw(): ResponseBody

    @GET("student/attendance")
    suspend fun studentAttendanceRaw(): ResponseBody

    @GET("parent/dashboard")
    suspend fun parentDashboardRaw(): ResponseBody

    @GET("parent/invoices")
    suspend fun parentInvoicesRaw(): ResponseBody

    @GET("parent/attendance")
    suspend fun parentAttendanceRaw(): ResponseBody

    @GET("parent/fees")
    suspend fun parentFeesRaw(): ResponseBody

    @GET("parent/fees/children/{student}")
    suspend fun parentChildFeesRaw(
        @Path("student") studentId: Long,
    ): ResponseBody

    @GET("parent/fees/invoices/{invoice}")
    suspend fun parentFeeInvoiceRaw(
        @Path("invoice") invoiceId: Long,
    ): ResponseBody

    @POST("parent/fees/{invoice}/checkout")
    suspend fun parentFeeCheckoutRaw(
        @Path("invoice") invoiceId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("parent/fees/verify")
    suspend fun verifyParentFeeRaw(@Body request: RequestBody): ResponseBody

    @GET("parent/fees/invoices/{invoice}/status")
    suspend fun parentFeeStatusRaw(
        @Path("invoice") invoiceId: Long,
    ): ResponseBody

    @GET("parent/fees/payments")
    suspend fun parentFeePaymentsRaw(): ResponseBody

    @GET("admin/dashboard")
    suspend fun adminDashboardRaw(): ResponseBody

    @GET("admin/students")
    suspend fun adminStudentsRaw(): ResponseBody

    @GET("admin/staff")
    suspend fun adminStaffRaw(): ResponseBody

    @GET("admin/academics")
    suspend fun adminAcademicsRaw(): ResponseBody

    @GET("admin/finance")
    suspend fun adminFinanceRaw(): ResponseBody

    @PATCH("admin/students/{student}")
    suspend fun updateAdminStudentRaw(
        @Path("student") studentId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @PATCH("admin/staff/{member}")
    suspend fun updateAdminStaffRaw(
        @Path("member") memberId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("admin/management")
    suspend fun adminManagementRaw(): ResponseBody

    @POST("admin/students")
    suspend fun createAdminStudentRaw(@Body request: RequestBody): ResponseBody

    @POST("admin/staff")
    suspend fun createAdminStaffRaw(@Body request: RequestBody): ResponseBody

    @POST("admin/classes")
    suspend fun createAdminClassRaw(@Body request: RequestBody): ResponseBody

    @PATCH("admin/classes/{classArm}")
    suspend fun updateAdminClassRaw(
        @Path("classArm") classArmId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("admin/subjects")
    suspend fun createAdminSubjectRaw(@Body request: RequestBody): ResponseBody

    @PATCH("admin/subjects/{subject}")
    suspend fun updateAdminSubjectRaw(
        @Path("subject") subjectId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("admin/subscription")
    suspend fun adminSubscriptionRaw(): ResponseBody

    @GET("admin/subscription/invoices")
    suspend fun adminSubscriptionInvoicesRaw(): ResponseBody

    @POST("admin/subscription/invoices")
    suspend fun createAdminSubscriptionInvoiceRaw(
        @Body request: RequestBody,
    ): ResponseBody

    @POST("admin/subscription/invoices/{invoice}/checkout")
    suspend fun adminSubscriptionCheckoutRaw(
        @Path("invoice") invoiceId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("admin/subscription/invoices/{invoice}/bank-transfer")
    suspend fun adminSubscriptionBankTransferRaw(
        @Path("invoice") invoiceId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("admin/subscription/invoices/{invoice}/status")
    suspend fun adminSubscriptionInvoiceStatusRaw(
        @Path("invoice") invoiceId: Long,
    ): ResponseBody

    @POST("admin/subscription/verify")
    suspend fun verifyAdminSubscriptionRaw(@Body request: RequestBody): ResponseBody

    @GET("accountant/dashboard")
    suspend fun accountantDashboardRaw(): ResponseBody

    @GET("accountant/payroll")
    suspend fun accountantPayrollRaw(): ResponseBody

    @GET("accountant/preparation-options")
    suspend fun accountantPreparationOptionsRaw(): ResponseBody

    @POST("accountant/fees/generate")
    suspend fun accountantGenerateFeesRaw(@Body request: RequestBody): ResponseBody

    @POST("accountant/payroll/generate")
    suspend fun accountantGeneratePayrollRaw(@Body request: RequestBody): ResponseBody

    @GET("platform/dashboard")
    suspend fun platformDashboardRaw(): ResponseBody

    @GET("platform/tenants")
    suspend fun platformTenantsRaw(): ResponseBody

    @GET("platform/billing")
    suspend fun platformBillingRaw(): ResponseBody

    @GET("platform/plans")
    suspend fun platformPlansRaw(): ResponseBody

    @PATCH("platform/tenants/{tenant}")
    suspend fun updatePlatformTenantRaw(
        @Path("tenant") tenantId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @DELETE("platform/tenants/{tenant}")
    suspend fun deletePlatformTenantRaw(
        @Path("tenant") tenantId: Long,
    ): ResponseBody

    @POST("platform/tenants")
    suspend fun createPlatformTenantRaw(@Body request: RequestBody): ResponseBody

    @GET("platform/agents")
    suspend fun platformAgentsRaw(): ResponseBody

    @POST("platform/agents")
    suspend fun createPlatformAgentRaw(@Body request: RequestBody): ResponseBody

    @PATCH("platform/agents/{agent}")
    suspend fun updatePlatformAgentRaw(
        @Path("agent") agentId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("admissions")
    suspend fun mobileAdmissionsRaw(): ResponseBody

    @POST("admissions")
    suspend fun createMobileAdmissionRaw(@Body request: RequestBody): ResponseBody

    @GET("admissions/{admission}")
    suspend fun mobileAdmissionRaw(
        @Path("admission") admissionId: Long,
    ): ResponseBody

    @PATCH("admissions/{admission}/status")
    suspend fun updateMobileAdmissionStatusRaw(
        @Path("admission") admissionId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("admissions/{admission}/interview")
    suspend fun scheduleMobileAdmissionInterviewRaw(
        @Path("admission") admissionId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("admissions/{admission}/interview/result")
    suspend fun recordMobileAdmissionInterviewRaw(
        @Path("admission") admissionId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("admissions/{admission}/offer")
    suspend fun sendMobileAdmissionOfferRaw(
        @Path("admission") admissionId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @GET("transport-officer/dashboard")
    suspend fun transportOfficerDashboardRaw(): ResponseBody

    @GET("transport-officer/routes/{route}/manifest")
    suspend fun transportRouteManifestRaw(
        @Path("route") routeId: Long,
    ): ResponseBody

    @POST("transport-officer/assignments")
    suspend fun createTransportAssignmentRaw(@Body request: RequestBody): ResponseBody

    @DELETE("transport-officer/assignments/{student}")
    suspend fun deleteTransportAssignmentRaw(
        @Path("student") studentId: Long,
    ): ResponseBody

    @GET("health-officer/dashboard")
    suspend fun healthOfficerDashboardRaw(): ResponseBody

    @GET("health-officer/students/{student}")
    suspend fun healthOfficerStudentRaw(
        @Path("student") studentId: Long,
    ): ResponseBody

    @POST("health-officer/students/{student}")
    suspend fun saveHealthOfficerStudentRaw(
        @Path("student") studentId: Long,
        @Body request: RequestBody,
    ): ResponseBody

    @POST("auth/logout")
    suspend fun logout(): MessageDto
}
