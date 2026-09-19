<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Parallel Attendance Report</title>
<style>
@page{margin:18mm 12mm}body{font-family:DejaVu Sans,sans-serif;color:#172033;font-size:9px}
h1{font-size:16px;margin:0;text-align:center;color:#071E45}h2{font-size:11px;margin:4px 0 14px;text-align:center;font-weight:normal;color:#667085}
.meta{width:100%;border-collapse:collapse;margin-bottom:12px}.meta td{border:1px solid #D0D5DD;padding:6px 8px}.meta strong{color:#071E45}
.metrics{width:100%;border-collapse:collapse;margin-bottom:12px}.metrics td{border:1px solid #D0D5DD;padding:7px;text-align:center}.metrics b{display:block;font-size:14px;color:#071E45}.metrics span{font-size:7.5px;text-transform:uppercase;color:#667085}
table.report{width:100%;border-collapse:collapse}table.report th{background:#071E45;color:#fff;padding:6px 5px;font-size:7.5px;text-transform:uppercase}table.report td{border:1px solid #D0D5DD;padding:5px}table.report td.num{text-align:center}.foot{margin-top:10px;font-size:7.5px;color:#667085;text-align:right}
</style>
</head>
<body>
<h1>{{ $schoolName }}</h1>
<h2>Parallel Curriculum Attendance Report</h2>
<table class="meta">
<tr>
<td><strong>Programme:</strong> {{ $report['arm']->curriculumClass?->curriculum?->name ?? 'Parallel Curriculum' }}</td>
<td><strong>Class:</strong> {{ $report['arm']->curriculumClass?->name ?? '—' }} {{ $report['arm']->name }}</td>
<td><strong>Term:</strong> {{ $report['term']->session?->name }} · {{ $report['term']->name }}</td>
<td><strong>Period:</strong> {{ $report['from'] }} to {{ $report['to'] }}</td>
</tr>
</table>
<table class="metrics">
<tr>
<td><b>{{ $report['summary']['students'] }}</b><span>Learners</span></td>
<td><b>{{ $report['summary']['records'] }}</b><span>Recorded entries</span></td>
<td><b>{{ $report['summary']['present'] }}</b><span>Present</span></td>
<td><b>{{ $report['summary']['absent'] }}</b><span>Absent</span></td>
<td><b>{{ $report['summary']['late'] }}</b><span>Late</span></td>
<td><b>{{ $report['summary']['excused'] }}</b><span>Excused</span></td>
</tr>
</table>
<table class="report">
<thead><tr><th>#</th><th>Admission No.</th><th>Learner</th><th>Present</th><th>Absent</th><th>Late</th><th>Excused</th><th>Recorded Days</th><th>Rate</th></tr></thead>
<tbody>
@forelse($report['rows'] as $index=>$row)
<tr>
<td class="num">{{ $index+1 }}</td>
<td>{{ $row['admission_number'] ?: '—' }}</td>
<td>{{ $row['student_name'] }}</td>
<td class="num">{{ $row['present'] }}</td>
<td class="num">{{ $row['absent'] }}</td>
<td class="num">{{ $row['late'] }}</td>
<td class="num">{{ $row['excused'] }}</td>
<td class="num">{{ $row['total'] }}</td>
<td class="num">{{ number_format((float)$row['rate'],1) }}%</td>
</tr>
@empty
<tr><td colspan="9" style="text-align:center;padding:14px">No learners found for this report.</td></tr>
@endforelse
</tbody>
</table>
<div class="foot">Generated {{ now()->format('d M Y H:i') }}</div>
</body>
</html>