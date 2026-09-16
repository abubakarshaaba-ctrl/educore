<?php

return [
    'highest_qualifications' => [
        'SSCE',
        'NCE',
        'ND',
        'HND',
        'BSc',
        'BEd',
        'BSc/PDE or PGDE',
        'BEng',
        'BEng/PDE or PGDE',
        'BTech',
        'BTech/PDE or PGDE',
        'BA',
        'BA/PDE or PGDE',
        'MSc',
        'MA',
        'MSc/PDE or PGDE',
        'MA/PDE or PGDE',
    ],

    'departments' => [
        'Academics',
        'Administration',
        'Admissions',
        'Finance & Accounts',
        'Human Resources',
        'ICT',
        'Library',
        'Health',
        'Transport',
        'Communication & Media',
        'Security',
        'Maintenance',
        'Other',
    ],

    // Employment type describes the staff member's working-time arrangement.
    'employment_types' => [
        'Full-time',
        'Part-time',
    ],

    // Appointment type describes the administrative basis of the appointment.
    // Employment-history events such as promotion, confirmation and transfer are
    // stored separately in StaffWorkHistory.change_type.
    'appointment_types' => [
        'Permanent',
        'Probationary',
        'Contract',
        'Temporary',
        'Casual',
    ],
];
