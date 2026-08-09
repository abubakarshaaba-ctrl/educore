<?php

use App\Models\AttendanceRecord;
use App\Models\Guardian;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\Score;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;

return ['entities' => ['student' => Student::class, 'guardian' => Guardian::class, 'staff' => User::class, 'student_enrollment' => StudentEnrollment::class, 'score' => Score::class, 'attendance' => AttendanceRecord::class, 'invoice' => Invoice::class, 'payment' => PaymentTransaction::class]];
