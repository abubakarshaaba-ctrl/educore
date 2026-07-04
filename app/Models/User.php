<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, SoftDeletes;
    use HasRoles {
        assignRole as private spatieAssignRole;
        syncRoles as private spatieSyncRoles;
        hasRole as private spatieHasRole;
        hasAnyRole as private spatieHasAnyRole;
    }

    const ROLES_STAFF = [
        'admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator',
        'admission_officer',
        'form_teacher', 'asst_form_teacher',
        'subject_teacher', 'form_subject_teacher',
        'accountant', 'health_officer', 'librarian',
        'transport_officer', 'communication_officer',
        'driver', 'bus_assistant',
        // ── Executive / Leadership ────────────────────────────────────
        'chairman', 'head_of_schools',
        'executive_director_administration', 'executive_director_operations',
        'director_of_studies', 'director_of_compliance', 'admin_officer',
        // ── Security ─────────────────────────────────────────────────
        'chief_security', 'security',
        // ── Health & Lab ──────────────────────────────────────────────
        'health_technician', 'laboratory_technician',
        // ── Residential / Pastoral ────────────────────────────────────
        'hostel_minder',
        // ── Stores & Facilities ───────────────────────────────────────
        'store_keeper', 'utility_officer', 'maintenance_officer',
        // ── Front Office & Communication ──────────────────────────────
        'receptionist', 'secretary', 'public_relation_officer', 'information_officer',
        // ── Kitchen & Catering ────────────────────────────────────────
        'head_chef', 'cook',
        // ── Grounds & Cleaning ────────────────────────────────────────
        'sanitation_officer', 'cleaner', 'gardener',
    ];

    const ROLES_TEACHING = [
        'form_teacher', 'asst_form_teacher',
        'subject_teacher', 'form_subject_teacher',
    ];

    const ROLES_PORTAL = ['student', 'parent'];

    public const STAFF_STATUS_ACTIVE = 'active';
    public const STAFF_STATUS_LEFT = 'left';
    public const STAFF_STATUS_RESIGNED = 'resigned';
    public const STAFF_STATUS_TERMINATED = 'terminated';

    public const STAFF_LIFECYCLE_STATUSES = [
        self::STAFF_STATUS_ACTIVE,
        self::STAFF_STATUS_LEFT,
        self::STAFF_STATUS_RESIGNED,
        self::STAFF_STATUS_TERMINATED,
    ];

    public const STAFF_ARCHIVE_STATUSES = [
        self::STAFF_STATUS_LEFT,
        self::STAFF_STATUS_RESIGNED,
        self::STAFF_STATUS_TERMINATED,
    ];

    public const STAFF_STATUS_LABELS = [
        self::STAFF_STATUS_ACTIVE => 'Active',
        self::STAFF_STATUS_LEFT => 'Left',
        self::STAFF_STATUS_RESIGNED => 'Resigned',
        self::STAFF_STATUS_TERMINATED => 'Terminated',
    ];

    public const STAFF_ADMIN_CONTINUITY_ROLES = [
        'admin',
        'principal',
        'head',
        'head_teacher',
    ];

    const ROLE_ALIASES = [
        'super-admin'            => 'super_admin',
        'administrator'          => 'admin',
        'assistant_form_teacher' => 'asst_form_teacher',
        'teacher'                => 'subject_teacher',
    ];

    const ALL_ROLES = [
        'super_admin',
        'admin', 'principal', 'head', 'head_teacher', 'vice_principal', 'academic_administrator',
        'admission_officer',
        'form_teacher', 'asst_form_teacher',
        'subject_teacher', 'form_subject_teacher',
        'accountant', 'health_officer', 'librarian',
        'transport_officer', 'communication_officer',
        'driver', 'bus_assistant',
        // ── Executive / Leadership ────────────────────────────────────
        'chairman', 'head_of_schools',
        'executive_director_administration', 'executive_director_operations',
        'director_of_studies', 'director_of_compliance', 'admin_officer',
        // ── Security ─────────────────────────────────────────────────
        'chief_security', 'security',
        // ── Health & Lab ──────────────────────────────────────────────
        'health_technician', 'laboratory_technician',
        // ── Residential / Pastoral ────────────────────────────────────
        'hostel_minder',
        // ── Stores & Facilities ───────────────────────────────────────
        'store_keeper', 'utility_officer', 'maintenance_officer',
        // ── Front Office & Communication ──────────────────────────────
        'receptionist', 'secretary', 'public_relation_officer', 'information_officer',
        // ── Kitchen & Catering ────────────────────────────────────────
        'head_chef', 'cook',
        // ── Grounds & Cleaning ────────────────────────────────────────
        'sanitation_officer', 'cleaner', 'gardener',
        // ── Portal ───────────────────────────────────────────────────
        'student', 'parent',
    ];

    const ROLE_LABELS = [
        'super_admin'            => 'Super Admin',
        'admin'                 => 'Administrator',
        'administrator'         => 'Administrator',
        'super-admin'           => 'Super Admin',
        'principal'             => 'Principal',
        'head'                  => 'Head',
        'head_teacher'          => 'Head Teacher',
        'vice_principal'        => 'Vice Principal',
        'academic_administrator'=> 'Academic Administrator',
        'admission_officer'     => 'Admission Officer',
        'form_teacher'          => 'Form Teacher',
        'asst_form_teacher'     => 'Asst. Form Teacher',
        'assistant_form_teacher'=> 'Asst. Form Teacher',
        'subject_teacher'       => 'Subject Teacher',
        'teacher'               => 'Subject Teacher',
        'form_subject_teacher'  => 'Form & Subject Teacher',
        'accountant'            => 'Accountant',
        'health_officer'        => 'Health Officer',
        'librarian'             => 'Librarian',
        'transport_officer'     => 'Transport Officer',
        'communication_officer' => 'Communication Officer',
        'driver'                => 'Driver',
        'bus_assistant'         => 'Bus Assistant',
        // ── Executive / Leadership ────────────────────────────────────
        'chairman'                          => 'Chairman',
        'head_of_schools'                   => 'Head of Schools',
        'executive_director_administration' => 'Executive Director (Administration)',
        'executive_director_operations'     => 'Executive Director (Operations)',
        'director_of_studies'               => 'Director of Studies',
        'director_of_compliance'            => 'Director of Compliance',
        'admin_officer'                     => 'Admin Officer',
        // ── Security ─────────────────────────────────────────────────
        'chief_security'                    => 'Chief Security Officer',
        'security'                          => 'Security Officer',
        // ── Health & Lab ──────────────────────────────────────────────
        'health_technician'                 => 'Health Technician',
        'laboratory_technician'             => 'Laboratory Technician',
        // ── Residential / Pastoral ────────────────────────────────────
        'hostel_minder'                     => 'Hostel Minder',
        // ── Stores & Facilities ───────────────────────────────────────
        'store_keeper'                      => 'Store Keeper',
        'utility_officer'                   => 'Utility Officer',
        'maintenance_officer'               => 'Maintenance Officer',
        // ── Front Office & Communication ──────────────────────────────
        'receptionist'                      => 'Receptionist',
        'secretary'                         => 'Secretary',
        'public_relation_officer'           => 'Public Relations Officer',
        'information_officer'               => 'Information Officer',
        // ── Kitchen & Catering ────────────────────────────────────────
        'head_chef'                         => 'Head Chef',
        'cook'                              => 'Cook',
        // ── Grounds & Cleaning ────────────────────────────────────────
        'sanitation_officer'                => 'Sanitation Officer',
        'cleaner'                           => 'Cleaner',
        'gardener'                          => 'Gardener',
        // ── Portal ───────────────────────────────────────────────────
        'student'               => 'Student',
        'parent'                => 'Parent',
    ];

    // ── Sub-module route prefix mapping ───────────────────────────────
    // Keys are "module" names used in ROLE_ACCESS.
    // Values are route name prefixes that belong to each module.
    const MODULE_ROUTES = [
        // core
        'dashboard'              => ['dashboard'],
        // students
        'students'               => ['students'],
        // staff
        'staff'                  => ['staff'],
        // classes
        'classes'                => ['classes'],
        'academic-cycle'         => ['academic-cycle'],
        // subjects
        'subjects'               => ['subjects'],
        // curriculum
        'curriculum'             => ['curriculum'],

        // ── Scores (write vs read-only) ───────────────────────────────
        'scores'                 => ['scores'],          // full score access
        'scores.entry'           => ['scores.index','scores.entry','scores.save','scores.import'], // enter+save only
        'scores.view'            => ['scores.index','scores.broadsheet'],   // read-only

        // ── Timetable (view vs manage) ────────────────────────────────
        'timetable'              => ['timetable'],       // full timetable access
        'timetable.view'         => ['timetable.index','timetable.view','timetable.teacher'], // view only

        // ── Reports (view vs manage) ──────────────────────────────────
        'reports'                => ['reports'],         // full reports (compute, publish, etc.)
        'reports.view'           => ['reports.index','reports.preview','reports.pdf','reports.pdf-class',
                                     'reports.remarks','reports.remarks.page.view','reports.publications'],
        // form teacher: add remarks only — no compute/preview/pdf/publish
        'reports.remarks'        => ['reports.remarks','reports.remarks.page.view','reports.remarks.save','reports.remarks.bulk'],

        // skills
        'skills'                 => ['skills'],

        // ── CBT ───────────────────────────────────────────────────────
        'cbt'                    => ['cbt'],

        // ── Admissions ───────────────────────────────────────────────
        'admissions'             => ['admissions'],

        // ── Finance ───────────────────────────────────────────────────
        'fees'                   => ['fees'],
        'expenses'               => ['expenses'],
        'payroll'                => ['payroll'],

        // ── Operations ───────────────────────────────────────────────
        'health'                 => ['health'],
        'library'                => ['library'],
        'transport'              => ['transport'],

        // ── Annual School Census ──────────────────────────────────────
        'asc'                    => ['asc'],

        // ── Analytics & Reporting ─────────────────────────────────────
        'analytics'              => ['analytics'],
        'risk'                   => ['risk'],
        'exports'                => ['exports'],

        // ── Calendar (view vs manage) ─────────────────────────────────
        'calendar'               => ['calendar'],        // full calendar (add/edit/delete events)
        'calendar.view'          => ['calendar.index','calendar.api'],  // read-only

        // ── Staff Attendance (self-service vs full admin) ──────────────
        // 'staff-attendance'      = full (dashboard, settings, reports, manual override, QR display)
        // 'staff-attendance.self' = every staff member: view own history, clock in/out,
        //                           proxy-clock a colleague, manage own PIN, view own ID card
        'staff-attendance.self'  => [
            'staff-attendance.my', 'staff-attendance.set-pin', 'staff-attendance.proxy',
            'staff-attendance.api.clockin', 'staff-attendance.api.clockout',
            'staff-attendance.api.proxy.initiate', 'staff-attendance.api.proxy.verify',
            'staff-attendance.api.offline', 'staff-attendance.id-card',
        ],

        // ── Notifications (read vs send) ──────────────────────────────
        'notifications'          => ['notifications'],    // full (send, manage, triggers)
        'notifications.view'     => ['notifications.index','notifications.logs'], // read-only inbox
        'notifications.send'     => ['notifications.index','notifications.logs',
                                     'notifications.send','notifications.templates',
                                     'notifications.settings'], // send but NOT triggers

        // ── Auto Triggers (admin-only) ────────────────────────────────
        'notifications.triggers' => ['notifications.triggers','notifications.triggers.save',
                                     'notifications.triggers.test'],

        // ── Messages ──────────────────────────────────────────────────
        'messages'               => ['messages'],

        // ── Announcements ─────────────────────────────────────────────
        'announcements'          => ['announcements'],

        // ── SMS Campaigns ─────────────────────────────────────────────
        'sms'                    => ['sms'],

        // ── Transfers ─────────────────────────────────────────────────
        'transfers'              => ['students.transfers', 'students.class-transfers'],

        // ── Settings ──────────────────────────────────────────────────
        'settings'               => ['settings'],
        'portal-accounts'        => ['portal-accounts'],
        'gradebook'              => ['gradebook'],

        // ── User Profile (everyone) ───────────────────────────────────
        'profile'                => ['profile'],
    ];

    // Feature gates derived from subscription plans. Route prefixes are matched
    // against the named routes used throughout the staff web app.
    const FEATURE_ROUTE_PREFIXES = [
        'school_setup'       => ['settings', 'portal-accounts'],
        'academic_cycle'     => ['academic-cycle'],
        'promotion'          => ['classes.grading', 'classes.promotion', 'classes.bulk-promote'],
        'students'           => ['students'],
        'student_transfer'   => ['students.transfers', 'students.class-transfers', 'transfers'],
        'student_archive'    => ['students.archive'],
        'staff'              => ['staff'],
        'staff_archive'      => ['staff.archive'],
        'classes'            => ['classes'],
        'subjects'           => ['subjects'],
        'curriculum'         => ['curriculum'],
        'timetable'          => ['timetable'],
        'scores'             => ['scores'],
        'report_cards'       => ['reports', 'transcripts', 'students.transcript'],
        'broadsheet'         => ['reports.publications', 'scores.broadsheet'],
        'skill_ratings'      => ['skills'],
        'gradebook'          => ['gradebook'],
        'assessment_types'   => ['scores.assessment-types'],
        'student_attendance' => ['attendance'],
        'staff_attendance'   => ['staff-attendance'],
        'staff_id_cards'     => ['staff-attendance.id-card'],
        'cbt'                => ['cbt'],
        'fees'               => ['fees'],
        'invoices'           => ['fees.generate', 'fees.plans'],
        'payment_plans'      => ['fees.plans'],
        'fee_reminders'      => ['fees.reminders'],
        'online_payments'    => ['fees.gateway'],
        'expenses'           => ['expenses'],
        'payroll'            => ['payroll'],
        'financial_report'   => ['payroll.salary', 'fees.plans.overdue', 'analytics.financial'],
        'messages'           => ['messages'],
        'sms'                => ['sms'],
        'notifications'      => ['notifications'],
        'announcements'      => ['announcements'],
        'auto_triggers'      => ['notifications.triggers'],
        'parent_portal'      => ['portal.parent'],
        'student_portal'     => ['student.portal'],
        'library'            => ['library'],
        'transport'          => ['transport'],
        'health_records'     => ['health'],
        'calendar'           => ['calendar'],
        'risk_flags'         => ['risk'],
        'analytics'          => ['analytics'],
        'export_data'        => ['exports'],
        'admissions'         => ['admissions'],
        'push_notifications' => ['push'],
    ];

    // ── Role → allowed modules ─────────────────────────────────────────
    // Rules:
    //  - 'timetable'       = full management (generate, configure, etc.)
    //  - 'timetable.view'  = view only
    //  - 'reports'         = full (compute, publish, remarks)
    //  - 'reports.view'    = view / print only
    //  - 'calendar'        = full (add / edit / delete events)
    //  - 'calendar.view'   = read-only
    //  - 'notifications'   = full (send + triggers)
    //  - 'notifications.send' = send but NOT triggers
    //  - 'notifications.view' = read-only
    //  - 'notifications.triggers' = admin only
    //  - 'scores.entry'    = enter + save scores only
    //  - 'scores.view'     = view broadsheet only
    //  - 'scores'          = full score management
    //  - 'profile'         = everyone gets this

    const ROLE_ACCESS = [

        'admin' => ['*'],   // full access to everything

        'principal' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','fees','expenses','payroll',
            'health','library','transport',
            'analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications','notifications.send',   // full notifications (not triggers)
            'sms','transfers','settings',
            'portal-accounts','gradebook','profile',
            'asc',
        ],

        'head' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','fees','expenses','payroll',
            'health','library','transport',
            'analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications','notifications.send',
            'sms','transfers','settings',
            'portal-accounts','gradebook','profile',
            'asc',
        ],

        'head_teacher' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','fees','expenses','payroll',
            'health','library','transport',
            'analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications','notifications.send',
            'sms','transfers','settings',
            'portal-accounts','gradebook','profile',
        ],

        'vice_principal' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications.send',
            'transfers','gradebook','profile',
        ],

        'academic_administrator' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications.send',
            'transfers','gradebook','profile',
        ],

        'admission_officer' => [
            'dashboard','admissions','students',
            'announcements','calendar.view','messages',
            'notifications.view','transfers','staff-attendance.self','profile',
        ],

        // ── Form Teacher: mark attendance, rate skills, view broadsheet,
        //    enter form-tutor remark, view class timetable
        'form_teacher' => [
            'dashboard',
            'attendance',            // mark class attendance
            'staff-attendance.self',
            'skills',                // rate psychomotor/affective skills
            'timetable.view',        // view class timetable only — no generate/configure
            'messages',
            'notifications.view',    // read-only
            'calendar.view',         // view only — no add/edit/delete
            'scores.view',           // view broadsheet for own class only
            'reports.remarks',       // enter form_tutor_remark only — no compute/publish
            'profile',
        ],

        'asst_form_teacher' => [
            'dashboard',
            'attendance',
            'staff-attendance.self',
            'skills',
            'timetable.view',
            'messages',
            'notifications.view',
            'calendar.view',
            'scores.view',
            'reports.remarks',       // enter form_tutor_remark only
            'profile',
        ],

        // ── Subject Teacher: enter scores only, view timetable, read notifications
        'subject_teacher' => [
            'dashboard',
            'scores.entry',          // score entry + save only — no broadsheet/assessment-types
            'timetable.view',        // view own subject schedule — no generate
            'cbt',
            'lesson-planner',        // NERDC/British AI lesson planner
            'messages',
            'notifications.view',    // read-only
            'calendar.view',         // view only
            'staff-attendance.self',
            'profile',
        ],

        // ── Form + Subject Teacher: combined duties
        'form_subject_teacher' => [
            'dashboard',
            'scores.entry',          // enter scores for assigned subjects
            'scores.view',           // view broadsheet for own class
            'attendance','staff-attendance.self',
            'skills',
            'timetable.view',        // view class timetable + subject schedule
            'cbt',
            'lesson-planner',        // NERDC/British AI lesson planner
            'messages',
            'notifications.view',
            'calendar.view',
            'reports.remarks',       // enter form_tutor_remark
            'gradebook',
            'profile',
        ],

        'accountant' => [
            'dashboard',
            'fees','expenses','payroll',
            'analytics','exports',
            'messages','students',
            'notifications.view',
            'calendar.view',
            'staff-attendance.self',
            'profile',
        ],

        'health_officer' => [
            'dashboard','health','students',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        'librarian' => [
            'dashboard','library','students',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        'transport_officer' => [
            'dashboard','transport','students',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        'driver' => [
            'dashboard','transport',
            'notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        'bus_assistant' => [
            'dashboard','transport',
            'notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        'communication_officer' => [
            'dashboard',
            'notifications.send',    // can send notifications but NOT triggers
            'sms','messages',
            'announcements',
            'calendar',              // full calendar management
            'staff-attendance.self','profile',
        ],

        // ── Chairman: highest governing authority — broad view access ──
        'chairman' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','fees','expenses','payroll',
            'health','library','transport',
            'analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications.send','sms','transfers','settings',
            'portal-accounts','gradebook','profile',
        ],

        // ── Head of Schools: equivalent to principal ───────────────────
        'head_of_schools' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','fees','expenses','payroll',
            'health','library','transport',
            'analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications','notifications.send',
            'sms','transfers','settings',
            'portal-accounts','gradebook','profile',
        ],

        // ── Executive Director (Administration) ────────────────────────
        'executive_director_administration' => [
            'dashboard','students','staff','classes','subjects',
            'academic-cycle',
            'admissions','fees','expenses','payroll',
            'health','library','transport',
            'analytics','exports',
            'announcements','calendar','messages',
            'notifications.send','sms','transfers','settings',
            'portal-accounts','profile',
        ],

        // ── Executive Director (Operations) ────────────────────────────
        'executive_director_operations' => [
            'dashboard','students','staff',
            'fees','expenses','payroll',
            'health','library','transport',
            'analytics','exports',
            'announcements','calendar','messages',
            'notifications.send','profile',
        ],

        // ── Director of Studies: academic oversight ─────────────────────
        'director_of_studies' => [
            'dashboard','students','staff','classes','subjects','curriculum',
            'academic-cycle',
            'scores','reports','transcript',
            'attendance','staff-attendance','timetable','skills','cbt',
            'admissions','analytics','risk','exports',
            'announcements','calendar','messages',
            'notifications.send','transfers','gradebook','profile',
        ],

        // ── Director of Compliance: audit / oversight read-access ───────
        'director_of_compliance' => [
            'dashboard','students','staff','classes',
            'academic-cycle',
            'scores.view','reports.view',
            'attendance','analytics','risk','exports',
            'announcements','calendar.view','messages',
            'notifications.view','staff-attendance.self','profile',
        ],

        // ── Admin Officer: general administrative support ───────────────
        'admin_officer' => [
            'dashboard','students','admissions',
            'fees','announcements','calendar.view','messages',
            'notifications.view','staff-attendance.self','profile',
        ],

        // ── Chief Security Officer ─────────────────────────────────────
        'chief_security' => [
            'dashboard','students','staff',
            'messages','calendar.view',
            'notifications.view',
            'staff-attendance.self','profile',
        ],

        // ── Security Officer ───────────────────────────────────────────
        'security' => [
            'dashboard',
            'messages','calendar.view',
            'notifications.view',
            'staff-attendance.self','profile',
        ],

        // ── Health Technician ──────────────────────────────────────────
        'health_technician' => [
            'dashboard','health','students',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Laboratory Technician ──────────────────────────────────────
        'laboratory_technician' => [
            'dashboard','students',
            'scores.view','timetable.view',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Hostel Minder ──────────────────────────────────────────────
        'hostel_minder' => [
            'dashboard','students','health',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Store Keeper ────────────────────────────────────────────────
        'store_keeper' => [
            'dashboard',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Utility Officer ─────────────────────────────────────────────
        'utility_officer' => [
            'dashboard',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Maintenance Officer ─────────────────────────────────────────
        'maintenance_officer' => [
            'dashboard',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Receptionist ────────────────────────────────────────────────
        'receptionist' => [
            'dashboard','admissions','students',
            'messages','calendar.view',
            'notifications.view','staff-attendance.self','profile',
        ],

        // ── Secretary ───────────────────────────────────────────────────
        'secretary' => [
            'dashboard','students','admissions',
            'announcements','messages','calendar.view',
            'notifications.view','staff-attendance.self','profile',
        ],

        // ── Public Relations Officer ────────────────────────────────────
        'public_relation_officer' => [
            'dashboard',
            'notifications.send','sms','messages',
            'announcements','calendar',
            'staff-attendance.self','profile',
        ],

        // ── Information Officer ─────────────────────────────────────────
        'information_officer' => [
            'dashboard',
            'notifications.send','sms','messages',
            'announcements','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Head Chef ───────────────────────────────────────────────────
        'head_chef' => [
            'dashboard',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Cook ────────────────────────────────────────────────────────
        'cook' => [
            'dashboard',
            'notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Sanitation Officer ──────────────────────────────────────────
        'sanitation_officer' => [
            'dashboard',
            'messages','notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Cleaner ─────────────────────────────────────────────────────
        'cleaner' => [
            'dashboard',
            'notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],

        // ── Gardener ────────────────────────────────────────────────────
        'gardener' => [
            'dashboard',
            'notifications.view','calendar.view',
            'staff-attendance.self','profile',
        ],
    ];

    protected ?array $cachedSubscriptionFeatures = null;

    protected $fillable = [
        'tenant_id', 'name', 'email', 'password',
        'role', 'phone', 'staff_id', 'student_id', 'attendance_pin', 'qr_secret',
        'date_of_birth', 'gender', 'address', 'passport_photo',
        'qualification', 'qualifications',
        'is_super_admin', 'is_active', 'last_login_at',
        'employment_status', 'employment_started_at', 'employment_ended_at',
        'status_changed_at', 'exit_reason',
        'two_factor_secret', 'two_factor_confirmed_at',
    ];

    // NOTE: do not add a $casts property — casts() method below takes precedence in Laravel 11

    // Qualification hierarchy — highest first (for ASC reporting)
    const QUALIFICATION_ORDER = [
        'PhD','MSc','MA','PGDE','PDE','BEd','BSc','HND','NCE','ND','O\'Level','PSLC',
    ];

    public function qualificationsList(): array
    {
        return $this->qualifications ?? [];
    }

    public function highestQualification(): ?string
    {
        $held = array_map('strtolower', $this->qualificationsList());
        foreach (self::QUALIFICATION_ORDER as $q) {
            if (in_array(strtolower($q), $held)) return $q;
        }
        return $held[0] ?? null;
    }

    protected $hidden = ['password', 'remember_token', 'two_factor_secret'];

    protected function casts(): array
    {
        return [
            'qualifications'          => 'array',
            'email_verified_at'       => 'datetime',
            'last_login_at'           => 'datetime',
            'date_of_birth'           => 'date',
            'employment_started_at'   => 'date',
            'employment_ended_at'     => 'date',
            'status_changed_at'       => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password'                => 'hashed',
            'two_factor_secret'       => 'encrypted',
            'is_super_admin'          => 'boolean',
            'is_active'               => 'boolean',
        ];
    }

    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_secret) && !is_null($this->two_factor_confirmed_at);
    }

    // ── Relationships ─────────────────────────────────────────────────
    public function tenant(): BelongsTo { return $this->belongsTo(Tenant::class); }
    public function student(): HasOne   { return $this->hasOne(Student::class); }
    public function classArms(): HasMany { return $this->hasMany(ClassArm::class, 'form_tutor_id'); }
    public function staffStatusHistories(): HasMany { return $this->hasMany(StaffStatusHistory::class, 'user_id'); }
    public function workHistories(): HasMany { return $this->hasMany(StaffWorkHistory::class, 'user_id'); }
    public function currentWorkHistory(): HasOne
    {
        return $this->hasOne(StaffWorkHistory::class, 'user_id')
            ->whereNull('end_date')
            ->latestOfMany();
    }

    public static function canonicalRole(?string $role): ?string
    {
        $role = trim((string) $role);
        if ($role === '') {
            return null;
        }

        return self::ROLE_ALIASES[$role] ?? $role;
    }

    public function roleKey(): ?string
    {
        if ($this->isSuperAdmin()) {
            return 'super_admin';
        }

        return self::canonicalRole($this->role);
    }

    public static function roleAliasesFor(?string $role): array
    {
        $canonical = self::canonicalRole($role);
        if (!$canonical) {
            return [];
        }

        $aliases = [$canonical];
        foreach (self::ROLE_ALIASES as $alias => $target) {
            if ($target === $canonical) {
                $aliases[] = $alias;
            }
        }

        return array_values(array_unique($aliases));
    }

    public static function staffRoleNames(): array
    {
        $roles = self::ROLES_STAFF;
        foreach (self::ROLE_ALIASES as $alias => $target) {
            if (in_array($target, self::ROLES_STAFF, true)) {
                $roles[] = $alias;
            }
        }

        return array_values(array_unique($roles));
    }

    public static function teachingRoleNames(): array
    {
        $roles = self::ROLES_TEACHING;
        foreach (self::ROLE_ALIASES as $alias => $target) {
            if (in_array($target, self::ROLES_TEACHING, true)) {
                $roles[] = $alias;
            }
        }

        return array_values(array_unique($roles));
    }

    private static function normalizeSpatieRoleInput(mixed $roles): mixed
    {
        if (is_string($roles)) {
            if (str_contains($roles, '|')) {
                return array_values(array_filter(array_map(
                    fn ($role) => self::canonicalRole($role),
                    explode('|', $roles)
                )));
            }

            return self::canonicalRole($roles);
        }

        if (is_array($roles)) {
            return array_values(array_map(
                fn ($role) => is_string($role) ? self::canonicalRole($role) : $role,
                $roles
            ));
        }

        if ($roles instanceof \Illuminate\Support\Collection) {
            return $roles->map(fn ($role) => is_string($role) ? self::canonicalRole($role) : $role);
        }

        return $roles;
    }

    private static function normalizeSpatieRoleArguments(array $roles): array
    {
        return array_values(array_map(fn ($role) => self::normalizeSpatieRoleInput($role), $roles));
    }

    private static function ensureSpatieRolesExist(array $roles): void
    {
        foreach ($roles as $roleSet) {
            if (is_string($roleSet) && $roleSet !== '') {
                Role::findOrCreate($roleSet, 'web');
                continue;
            }

            if (is_array($roleSet)) {
                self::ensureSpatieRolesExist($roleSet);
                continue;
            }

            if ($roleSet instanceof \Illuminate\Support\Collection) {
                self::ensureSpatieRolesExist($roleSet->all());
            }
        }
    }

    public function assignRole(...$roles)
    {
        $roles = self::normalizeSpatieRoleArguments($roles);
        self::ensureSpatieRolesExist($roles);

        return $this->spatieAssignRole(...$roles);
    }

    public function syncRoles(...$roles)
    {
        $roles = self::normalizeSpatieRoleArguments($roles);
        self::ensureSpatieRolesExist($roles);

        return $this->spatieSyncRoles(...$roles);
    }

    public function hasRole($roles, ?string $guard = null): bool
    {
        $normalized = self::normalizeSpatieRoleInput($roles);

        if ($this->spatieHasRole($normalized, $guard)) {
            return true;
        }

        $requested = collect($normalized)->flatten()
            ->filter(fn ($role) => is_string($role) && $role !== '')
            ->map(fn ($role) => self::canonicalRole($role))
            ->all();

        return in_array($this->roleKey(), $requested, true);
    }

    public function hasAnyRole(...$roles): bool
    {
        $normalized = self::normalizeSpatieRoleArguments($roles);

        if ($this->spatieHasAnyRole(...$normalized)) {
            return true;
        }

        $requested = collect($normalized)->flatten()
            ->filter(fn ($role) => is_string($role) && $role !== '')
            ->map(fn ($role) => self::canonicalRole($role))
            ->all();

        return in_array($this->roleKey(), $requested, true);
    }

    public function subscriptionFeatureKeys(): array
    {
        if ($this->isSuperAdmin()) {
            return ['*'];
        }

        if ($this->cachedSubscriptionFeatures !== null) {
            return $this->cachedSubscriptionFeatures;
        }

        $tenant = $this->relationLoaded('tenant')
            ? $this->tenant
            : $this->tenant()->with(['activeSubscription.plan'])->first();

        if ($tenant) {
            $tenant->loadMissing(['activeSubscription.plan', 'subscriptions.plan']);
        }

        $plan = $tenant?->activeSubscription?->plan;

        if (!$plan && $tenant) {
            $subscription = $tenant->subscriptions()
                ->with('plan')
                ->where('status', 'active')
                ->whereNotNull('plan_id')
                ->orderByDesc('id')
                ->first();

            $plan = $subscription?->plan;
        }

        if (!$plan && $tenant) {
            if (Schema::hasTable('platform_invoices') && Schema::hasTable('subscription_plans')) {
                $plan = DB::table('platform_invoices')
                    ->join('subscription_plans', 'subscription_plans.id', '=', 'platform_invoices.plan_id')
                    ->select('subscription_plans.features', 'subscription_plans.has_cbt', 'subscription_plans.has_sms', 'subscription_plans.has_paystack')
                    ->where('platform_invoices.tenant_id', $tenant->id)
                    ->where('platform_invoices.status', 'paid')
                    ->orderByDesc('platform_invoices.paid_at')
                    ->orderByDesc('platform_invoices.created_at')
                    ->first();
            }
        }

        if (!$plan && $tenant && app()->environment('testing')) {
            return array_values(array_unique(array_keys(self::FEATURE_ROUTE_PREFIXES)));
        }

        // Trial tenants have full feature access — "30 days free, all features"
        if (!$plan && $tenant) {
            $hasTrial = Schema::hasTable('tenant_subscriptions') && DB::table('tenant_subscriptions')
                ->where('tenant_id', $tenant->id)
                ->where('status', 'trial')
                ->exists();
            if ($hasTrial) {
                $this->cachedSubscriptionFeatures = array_values(array_unique(array_keys(self::FEATURE_ROUTE_PREFIXES)));
                return $this->cachedSubscriptionFeatures;
            }
        }

        $features = [];

        if ($plan) {
            $features = is_array($plan->features)
                ? $plan->features
                : (json_decode((string) $plan->features, true) ?: []);

            $legacyFlags = [
                'cbt'             => (bool) ($plan->has_cbt ?? false),
                'sms'             => (bool) ($plan->has_sms ?? false),
                'online_payments' => (bool) ($plan->has_paystack ?? false),
            ];

            foreach ($legacyFlags as $feature => $enabled) {
                if ($enabled && !in_array($feature, $features, true)) {
                    $features[] = $feature;
                }
            }
        }

        $normalized = [];
        foreach ($features as $feature) {
            $normalized[] = $this->normalizeFeatureKey((string) $feature);
        }

        $this->cachedSubscriptionFeatures = array_values(array_unique(array_filter($normalized)));

        return $this->cachedSubscriptionFeatures;
    }

    public function canUseFeature(string $feature): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        $feature = trim($feature);
        if ($feature === '') {
            return true;
        }

        return in_array($this->normalizeFeatureKey($feature), $this->subscriptionFeatureKeys(), true);
    }

    private function featureForModule(string $module): ?string
    {
        $module = trim($module);
        if ($module === '' || $module === 'dashboard' || $module === 'profile') {
            return null;
        }

        foreach (self::FEATURE_ROUTE_PREFIXES as $feature => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($module === $prefix || str_starts_with($module, $prefix . '.')) {
                    return $feature;
                }
            }
        }

        return null;
    }

    private function normalizeFeatureKey(string $feature): string
    {
        $feature = trim(mb_strtolower($feature));
        if ($feature === '') {
            return '';
        }

        $feature = str_replace(['-', ' '], '_', $feature);

        return match ($feature) {
            'academic_cycle', 'academic_session', 'academic_sessions' => 'academic_cycle',
            'school_setup', 'school_settings', 'settings', 'portal_accounts', 'portal-accounts' => 'school_setup',
            'student_transfer', 'student_transfers', 'transfers' => 'student_transfer',
            'student_archive', 'student_archives' => 'student_archive',
            'staff_archive', 'staff_archives' => 'staff_archive',
            'report_cards', 'report_card', 'reports', 'reports_view' => 'report_cards',
            'broadsheet', 'broadsheets' => 'broadsheet',
            'skill_ratings', 'skill_rating' => 'skill_ratings',
            'assessment_types', 'assessment_type' => 'assessment_types',
            'student_attendance', 'attendance' => 'student_attendance',
            'staff_attendance', 'staff_attendance_self' => 'staff_attendance',
            'staff_id_cards', 'staff_id_card' => 'staff_id_cards',
            'online_payments', 'online_payment', 'paystack', 'monnify' => 'online_payments',
            'financial_report', 'financial_reports' => 'financial_report',
            'parent_portal', 'portal_parent' => 'parent_portal',
            'student_portal', 'portal_student' => 'student_portal',
            'health_records', 'health_record' => 'health_records',
            'risk_flags', 'risk_flag' => 'risk_flags',
            'export_data', 'exports' => 'export_data',
            default => $feature,
        };
    }

    private function featureForRoute(string $routeName): ?string
    {
        $routeName = trim($routeName);
        if ($routeName === '' || $routeName === 'dashboard' || str_starts_with($routeName, 'profile')) {
            return null;
        }

        foreach (self::FEATURE_ROUTE_PREFIXES as $feature => $prefixes) {
            foreach ($prefixes as $prefix) {
                if ($routeName === $prefix || str_starts_with($routeName, $prefix . '.')) {
                    return $feature;
                }
            }
        }

        return null;
    }

    // ── Role helpers ──────────────────────────────────────────────────
    public function isSuperAdmin(): bool     { return (bool) $this->is_super_admin; }
    public function isAdmin(): bool          { return $this->roleKey() === 'admin'; }
    public function isPrincipal(): bool      { return $this->roleKey() === 'principal'; }
    public function isAdmissionOfficer(): bool { return $this->roleKey() === 'admission_officer'; }
    public function isTeacher(): bool        { return in_array($this->roleKey(), self::ROLES_TEACHING, true); }
    public function isAccountant(): bool     { return $this->roleKey() === 'accountant'; }
    public function isHealthOfficer(): bool  { return $this->roleKey() === 'health_officer'; }
    public function isLibrarian(): bool      { return $this->roleKey() === 'librarian'; }
    public function isTransportOfficer(): bool { return $this->roleKey() === 'transport_officer'; }
    public function isDriver(): bool         { return in_array($this->roleKey(), ['driver','bus_assistant'], true); }
    public function isCommunicationOfficer(): bool { return $this->roleKey() === 'communication_officer'; }
    public function isParent(): bool         { return $this->roleKey() === 'parent'; }
    public function isStudent(): bool        { return $this->roleKey() === 'student'; }
    public function isStaff(): bool          { return in_array($this->roleKey(), self::ROLES_STAFF, true); }

    public function isTenantStaff(): bool
    {
        return $this->tenant_id
            && !$this->isSuperAdmin()
            && !$this->isParent()
            && !$this->isStudent()
            && $this->isStaff();
    }

    public function employmentStatus(): string
    {
        return $this->employment_status ?: self::STAFF_STATUS_ACTIVE;
    }

    public function isEmploymentActive(): bool
    {
        return $this->employmentStatus() === self::STAFF_STATUS_ACTIVE;
    }

    public function isArchivedStaffStatus(): bool
    {
        return in_array($this->employmentStatus(), self::STAFF_ARCHIVE_STATUSES, true);
    }

    public function wasEmployedOn(string|\DateTimeInterface $date): bool
    {
        $date = \Illuminate\Support\Carbon::parse($date)->toDateString();

        if ($this->exists && Schema::hasTable('staff_work_histories') && $this->workHistories()->exists()) {
            return $this->workHistories()
                ->whereDate('start_date', '<=', $date)
                ->where(function ($query) use ($date) {
                    $query->whereNull('end_date')
                        ->orWhereDate('end_date', '>=', $date);
                })
                ->exists();
        }

        $startedAt = $this->employment_started_at?->toDateString();
        $endedAt = $this->employment_ended_at?->toDateString();

        if (!$startedAt) {
            return $this->isEmploymentActive() && (bool) $this->is_active;
        }

        return $startedAt <= $date && (!$endedAt || $endedAt >= $date);
    }

    public function employmentStatusLabel(): string
    {
        return self::STAFF_STATUS_LABELS[$this->employmentStatus()]
            ?? str($this->employmentStatus())->replace('_', ' ')->title()->toString();
    }

    public function hasFormTeacherDuty(): bool
    {
        return in_array($this->roleKey(), ['form_teacher','asst_form_teacher','form_subject_teacher',
                                       'principal','vice_principal','admin'], true);
    }

    public function hasSubjectTeacherDuty(): bool
    {
        return in_array($this->roleKey(), ['subject_teacher','form_subject_teacher',
                                       'principal','vice_principal','admin'], true);
    }

    // ── Access checks ─────────────────────────────────────────────────
    /**
     * Check if user can access a module key.
     * Resolves '*' wildcard (admin).
     * Used in Blade sidebar @if statements.
     */
    // ── Custom per-staff permissions (admin can grant/deny) ──────────
    public function customPermissions()
    {
        return $this->hasMany(\App\Models\StaffPermission::class, 'user_id');
    }

    public function hasGrantedPermission(string $module): bool
    {
        if (!Schema::hasTable('staff_permissions')) {
            return false;
        }

        return \App\Models\StaffPermission::where('user_id', $this->id)
            ->where('module', $module)->where('type', 'grant')->exists();
    }

    public function hasDeniedPermission(string $module): bool
    {
        if (!Schema::hasTable('staff_permissions')) {
            return false;
        }

        return \App\Models\StaffPermission::where('user_id', $this->id)
            ->where('module', $module)->where('type', 'deny')->exists();
    }

    public function canAccessModule(string $module): bool
    {
        if ($this->isSuperAdmin()) return true;
        if (($feature = $this->featureForModule($module)) && !$this->canUseFeature($feature)) {
            return false;
        }
        // Custom per-staff deny overrides everything
        if ($this->hasDeniedPermission($module)) return false;
        // Custom per-staff grant allows even without role access
        if ($this->hasGrantedPermission($module)) return true;
        $allowed = self::ROLE_ACCESS[$this->roleKey()] ?? [];
        if (in_array('*', $allowed)) return true;

        // Direct match
        if (in_array($module, $allowed)) return true;

        // If asking for a parent module, check if any sub-module is allowed
        // e.g. 'timetable' check passes if 'timetable.view' is in allowed
        foreach ($allowed as $a) {
            if (str_starts_with($a, $module . '.')) return true;
        }

        return false;
    }

    public function canAccessExactModule(string $module): bool
    {
        if ($this->isSuperAdmin()) return true;
        if (($feature = $this->featureForModule($module)) && !$this->canUseFeature($feature)) {
            return false;
        }
        if ($this->hasDeniedPermission($module)) return false;
        if ($this->hasGrantedPermission($module)) return true;

        $allowed = self::ROLE_ACCESS[$this->roleKey()] ?? [];

        return in_array('*', $allowed, true) || in_array($module, $allowed, true);
    }

    /**
     * Check if user can access the current named route.
     * Used in CheckModuleAccess middleware.
     */
    public function canAccessRoute(string $routeName): bool
    {
        if ($this->isSuperAdmin()) return true;
        if (($feature = $this->featureForRoute($routeName)) && !$this->canUseFeature($feature)) {
            return false;
        }
        $allowed = self::ROLE_ACCESS[$this->roleKey()] ?? [];
        if (in_array('*', $allowed)) return true;

        // Profile is always allowed for authenticated staff
        if (str_starts_with($routeName, 'profile')) return true;

        foreach ($allowed as $module) {
            $prefixes = self::MODULE_ROUTES[$module] ?? [$module];
            foreach ($prefixes as $prefix) {
                if ($routeName === $prefix || str_starts_with($routeName, $prefix . '.')) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Shorthand for Blade: can user perform write actions on a feature?
     * Used to show/hide Add/Edit/Delete buttons in views.
     */
    public function canManage(string $module): bool
    {
        if ($this->isSuperAdmin()) return true;
        if (($feature = $this->featureForModule($module)) && !$this->canUseFeature($feature)) {
            return false;
        }
        $allowed = self::ROLE_ACCESS[$this->roleKey()] ?? [];
        return in_array('*', $allowed, true) || in_array($module, $allowed, true);
    }

    public function roleLabel(): string
    {
        $role = $this->roleKey() ?? $this->role;

        return self::ROLE_LABELS[$role] ?? ucfirst(str_replace('_', ' ', (string) $role));
    }

    // ── Scopes ────────────────────────────────────────────────────────
    public function scopeForTenant($q, int $tid) { return $q->where('tenant_id', $tid); }
    public function scopeStaff($q)   { return $q->whereIn('role', self::staffRoleNames()); }
    public function scopeActive($q)  { return $q->where('is_active', true); }
    public function scopeTeachers($q){ return $q->whereIn('role', self::teachingRoleNames()); }

    public function scopeTenantStaff($q, ?int $tenantId = null)
    {
        return $q->when($tenantId, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereNotNull('tenant_id')
            ->where('is_super_admin', false)
            ->whereIn('role', self::staffRoleNames());
    }

    public function scopeActiveStaff($q, ?int $tenantId = null)
    {
        return $q->tenantStaff($tenantId)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('employment_status')
                    ->orWhere('employment_status', self::STAFF_STATUS_ACTIVE);
            });
    }

    public function scopeArchivedStaff($q, ?int $tenantId = null)
    {
        return $q->tenantStaff($tenantId)
            ->whereIn('employment_status', self::STAFF_ARCHIVE_STATUSES);
    }

    public function scopePayrollEligible($q, ?int $tenantId = null)
    {
        return $q->activeStaff($tenantId);
    }

    public function scopeEmployedDuring($q, ?int $tenantId, string|\DateTimeInterface $periodStart, string|\DateTimeInterface $periodEnd)
    {
        $periodStart = \Illuminate\Support\Carbon::parse($periodStart)->toDateString();
        $periodEnd = \Illuminate\Support\Carbon::parse($periodEnd)->toDateString();

        if (!Schema::hasTable('staff_work_histories')) {
            return $q->tenantStaff($tenantId)
                ->where(function ($dates) use ($periodEnd, $periodStart) {
                    $dates->whereDate('employment_started_at', '<=', $periodEnd)
                        ->orWhere(function ($legacy) {
                            $legacy->whereNull('employment_started_at')
                                ->where('is_active', true)
                                ->where(function ($activeStatus) {
                                    $activeStatus->whereNull('employment_status')
                                        ->orWhere('employment_status', self::STAFF_STATUS_ACTIVE);
                                });
                        });
                })
                ->where(function ($dates) use ($periodStart) {
                    $dates->whereNull('employment_ended_at')
                        ->orWhereDate('employment_ended_at', '>=', $periodStart);
                });
        }

        return $q->tenantStaff($tenantId)
            ->where(function ($query) use ($periodEnd, $periodStart) {
                $query->whereHas('workHistories', function ($history) use ($periodEnd, $periodStart) {
                    $history->whereDate('start_date', '<=', $periodEnd)
                        ->where(function ($dates) use ($periodStart) {
                            $dates->whereNull('end_date')
                                ->orWhereDate('end_date', '>=', $periodStart);
                        });
                })->orWhere(function ($fallback) use ($periodEnd, $periodStart) {
                    $fallback->whereDoesntHave('workHistories')
                        ->where(function ($dates) use ($periodEnd) {
                            $dates->whereDate('employment_started_at', '<=', $periodEnd)
                                ->orWhere(function ($legacy) {
                                    $legacy->whereNull('employment_started_at')
                                        ->where('is_active', true)
                                        ->where(function ($activeStatus) {
                                            $activeStatus->whereNull('employment_status')
                                                ->orWhere('employment_status', self::STAFF_STATUS_ACTIVE);
                                        });
                                });
                        })
                        ->where(function ($dates) use ($periodStart) {
                            $dates->whereNull('employment_ended_at')
                                ->orWhereDate('employment_ended_at', '>=', $periodStart);
                        });
                });
            });
    }

    public function scopePayrollEligibleForPeriod($q, ?int $tenantId, string|\DateTimeInterface $periodStart, string|\DateTimeInterface $periodEnd)
    {
        return $q->employedDuring($tenantId, $periodStart, $periodEnd);
    }

    public function scopeAttendanceEligibleOn($q, ?int $tenantId, string|\DateTimeInterface $date)
    {
        return $q->employedDuring($tenantId, $date, $date);
    }
    // ── Staff Personal QR ─────────────────────────────────────────────
    /** Get or generate this staff member's permanent QR secret */
    public function getOrCreateQrSecret(): string
    {
        if (!$this->qr_secret) {
            $secret = bin2hex(random_bytes(16));
            $this->update(['qr_secret' => $secret]);
        }
        return $this->qr_secret;
    }

    /** Generate a permanent personal QR payload for this staff member */
    public function personalQrPayload(): string
    {
        $secret  = $this->getOrCreateQrSecret();
        $payload = ['uid' => $this->id, 'tid' => $this->tenant_id, 'sid' => $this->staff_id ?? ''];
        $sig     = hash_hmac('sha256', json_encode($payload), $secret);
        $payload['sig'] = $sig;
        return base64_encode(json_encode($payload));
    }

    /** Verify a personal QR payload */
    public static function verifyPersonalQr(string $token): ?self
    {
        try {
            $data   = json_decode(base64_decode($token), true);
            $sig    = $data['sig'] ?? '';
            $uid    = $data['uid'] ?? 0;
            unset($data['sig']);
            $user   = self::find($uid);
            if (!$user || !$user->qr_secret) return null;
            $expected = hash_hmac('sha256', json_encode($data), $user->qr_secret);
            return hash_equals($expected, $sig) ? $user : null;
        } catch (\Throwable) {
            return null;
        }
    }

}
