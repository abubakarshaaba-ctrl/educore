<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
</head>
<body>
@foreach($studentData as $data)
    @php
        $student = $data['student'];
        $summary = $data['summary'];
        $subjectRows = $data['subjectRows'];
        $skillRatings = $data['skillRatings'];
        $attendanceSummary = $data['attendanceSummary'] ?? [];
    @endphp

    @include('reports.pdf', [
        'embedded'          => true,
        'renderStyles'       => $loop->first,
        'student'           => $student,
        'summary'           => $summary,
        'subjectRows'       => $subjectRows,
        'skillRatings'      => $skillRatings,
        'attendanceSummary' => $attendanceSummary,
        'classArm'          => $classArm,
        'term'              => $term,
        'session'           => $session,
        'tenant'            => $tenant,
        'assessmentTypes'   => $assessmentTypes,
        'gradingSystem'     => $gradingSystem,
        'psychomotorSkills' => $psychomotorSkills,
        'affectiveSkills'   => $affectiveSkills,
        'isThirdTerm'       => $isThirdTerm,
    ])

    @if(!$loop->last)<div class="page-break"></div>@endif
@endforeach
</body>
</html>
