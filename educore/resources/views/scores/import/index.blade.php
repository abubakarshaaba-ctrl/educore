@extends('layouts.app')
@section('title','Score Import')
@section('page-title','Score Import')
@push('styles') @include('partials.simple-page-styles') @endpush
@section('content')
@if(session('success'))<div class="alert alert-ok">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-bad">{{ $errors->first() }}</div>@endif
<div class="grid grid-2">
  <div class="card">
    <div class="card-h">Import History</div>
    <div class="tbl"><table>
      <thead><tr><th>File</th><th>Imported</th><th>Failed</th><th>Status</th><th>Date</th></tr></thead>
      <tbody>
      @forelse($imports as $import)
        <tr><td>{{ $import->filename }}</td><td>{{ $import->rows_imported }}</td><td>{{ $import->rows_failed }}</td><td>{{ ucfirst($import->status) }}</td><td>{{ optional($import->created_at)->format('d M Y') }}</td></tr>
      @empty
        <tr><td colspan="5" class="muted">No imports yet.</td></tr>
      @endforelse
      </tbody>
    </table></div>
    {{ $imports->links() }}
  </div>
  <div class="card">
    <div class="card-h">Upload CSV</div>
    <div class="card-b">
      <a href="{{ route('scores.import.template') }}" class="btn btn-light" style="margin-bottom:12px">Download Template</a>
      <form method="POST" action="{{ route('scores.import.upload') }}" enctype="multipart/form-data">
        @csrf
        <div class="form-row"><label>Class</label><select class="control" name="class_arm_id" required><option value="">Select class</option>@foreach($classArms as $arm)<option value="{{ $arm->id }}">{{ $arm->classLevel->name }} {{ $arm->name }}</option>@endforeach</select></div>
        <div class="form-row"><label>Term</label><select class="control" name="term_id" required><option value="">Select term</option>@foreach($terms as $term)<option value="{{ $term->id }}" {{ $term->is_current ? 'selected' : '' }}>{{ $term->name }} - {{ $term->session->name ?? '' }}</option>@endforeach</select></div>
        <div class="form-row"><label>CSV File</label><input class="control" type="file" name="file" accept=".csv,text/csv" required></div>
        <button class="btn btn-primary" type="submit">Import Scores</button>
      </form>
    </div>
  </div>
</div>
@endsection
