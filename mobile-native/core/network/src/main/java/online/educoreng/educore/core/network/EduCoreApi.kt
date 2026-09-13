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
import retrofit2.http.PATCH
import retrofit2.http.Streaming
import retrofit2.http.Body
import retrofit2.http.GET
import retrofit2.http.Path
import retrofit2.http.POST
import retrofit2.http.Query
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

    @GET("student/results")
    suspend fun studentResults(): PublishedResultsResponseDto

    @GET("parent/results")
    suspend fun parentResults(@Query("child_id") childId: Long? = null): PublishedResultsResponseDto

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

    @POST("auth/logout")
    suspend fun logout(): MessageDto
}
