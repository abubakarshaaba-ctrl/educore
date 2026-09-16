@extends('layouts.app')
@section('title','ASC — Section '.$section)
@section('page-title','Annual School Census — Section '.$section)

@section('content')
@if(session('success'))
<div style="background:#D1FAE5;color:#065F46;border:1px solid #A7F3D0;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600">✓ {{ session('success') }}</div>
@endif
@if(session('error'))
<div style="background:#FEE2E2;color:#991B1B;border:1px solid #FECACA;border-radius:10px;padding:12px 16px;margin-bottom:16px;font-size:13px;font-weight:600">⚠ {{ session('error') }}</div>
@endif

@php
    $locked = $ascReturn?->isLocked() ?? false;
    $data = old('fields', $record?->data ?? []);
@endphp

<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:18px">
    <div>
        <div style="font-size:19px;font-weight:900;color:var(--midnight)">Section {{ $section }} — {{ $definition['title'] }}</div>
        <div style="font-size:12px;color:var(--slate-light);margin-top:3px">{{ $definition['description'] }}</div>
    </div>
    <div style="display:flex;gap:8px;flex-wrap:wrap">
        <a href="{{ route('asc.infrastructure',['year'=>$year]) }}" class="asc-link secondary">← Census Workspace</a>
        <a href="{{ route('asc.report',['year'=>$year]) }}" class="asc-link">View Report</a>
    </div>
</div>

<div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:18px">
    @foreach(config('asc.manual_sections') as $s)
        <a href="{{ route('asc.section.show',['section'=>strtolower($s),'year'=>$year]) }}"
           style="text-decoration:none;padding:8px 13px;border-radius:9px;font-size:12px;font-weight:800;border:1px solid {{ $s===$section ? '#6366F1' : '#E2E8F0' }};background:{{ $s===$section ? '#EEF2FF' : 'white' }};color:{{ $s===$section ? '#4338CA' : '#475569' }}">
            Section {{ $s }}
        </a>
    @endforeach
</div>

<div style="background:white;border:1px solid var(--border);border-radius:14px;overflow:hidden">
    <div style="padding:14px 18px;border-bottom:1px solid var(--border);background:#F8FAFC;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <div style="font-size:12px;font-weight:800;color:var(--midnight)">{{ $definition['source'] }}</div>
        <div style="font-size:11px;font-weight:800;padding:5px 9px;border-radius:999px;background:{{ $record?->is_complete ? '#D1FAE5' : '#FEF3C7' }};color:{{ $record?->is_complete ? '#065F46' : '#92400E' }}">
            {{ $record?->is_complete ? 'COMPLETE' : 'INCOMPLETE' }}
        </div>
    </div>

    <form method="POST" action="{{ route('asc.section.save',['section'=>strtolower($section)]) }}">
        @csrf
        <input type="hidden" name="census_year" value="{{ $year }}">
        <div style="padding:20px;display:grid;gap:18px">
            @foreach($definition['fields'] as $key => $field)
                @php
                    $type = $field['type'] ?? 'text';
                    $value = data_get($data, $key);
                @endphp
                <div style="border:1px solid #E2E8F0;border-radius:11px;padding:14px">
                    <label style="display:block;font-size:12px;font-weight:800;color:var(--midnight);margin-bottom:8px">
                        {{ $field['label'] }} @if($field['required'] ?? false)<span style="color:#DC2626">*</span>@endif
                    </label>

                    @if($type === 'yes_no')
                        <div style="display:flex;gap:18px">
                            @foreach(['yes'=>'Yes','no'=>'No'] as $v=>$l)
                                <label style="font-size:13px;color:var(--slate);display:flex;gap:6px;align-items:center">
                                    <input type="radio" name="fields[{{ $key }}]" value="{{ $v }}" @checked($value===$v) @disabled($locked)> {{ $l }}
                                </label>
                            @endforeach
                        </div>
                    @elseif($type === 'checkboxes')
                        <div style="display:flex;gap:10px;flex-wrap:wrap">
                            @foreach($field['options'] as $v=>$l)
                                <label style="font-size:12px;color:var(--slate);display:flex;gap:6px;align-items:center;border:1px solid #E2E8F0;border-radius:8px;padding:7px 10px">
                                    <input type="checkbox" name="fields[{{ $key }}][]" value="{{ $v }}" @checked(in_array($v,(array)$value,true)) @disabled($locked)> {{ $l }}
                                </label>
                            @endforeach
                        </div>
                    @elseif($type === 'matrix')
                        <div style="overflow:auto">
                            <table style="width:100%;border-collapse:collapse;font-size:12px;min-width:760px">
                                <thead><tr><th style="text-align:left;padding:8px;border:1px solid #E2E8F0;background:#F8FAFC">Item</th>
                                @foreach($field['columns'] as $colKey=>$colLabel)<th style="padding:8px;border:1px solid #E2E8F0;background:#F8FAFC">{{ $colLabel }}</th>@endforeach
                                </tr></thead>
                                <tbody>
                                @foreach($field['rows'] as $rowKey=>$rowLabel)
                                    <tr>
                                        <td style="padding:8px;border:1px solid #E2E8F0;font-weight:700">{{ $rowLabel }}</td>
                                        @foreach($field['columns'] as $colKey=>$colLabel)
                                            <td style="padding:5px;border:1px solid #E2E8F0;text-align:center">
                                                <input type="number" min="0" name="fields[{{ $key }}][{{ $rowKey }}][{{ $colKey }}]" value="{{ data_get($data, $key.'.'.$rowKey.'.'.$colKey, 0) }}" @disabled($locked)
                                                       style="width:72px;border:1px solid #CBD5E1;border-radius:6px;padding:6px;text-align:center">
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <input type="{{ $type === 'number' ? 'number' : ($type === 'date' ? 'date' : 'text') }}" name="fields[{{ $key }}]" value="{{ $value }}" @disabled($locked)
                               @if($type==='number') min="0" @endif
                               style="width:100%;max-width:620px;border:1px solid #CBD5E1;border-radius:8px;padding:9px 11px;font-size:13px;box-sizing:border-box">
                    @endif
                </div>
            @endforeach
        </div>

        @if(!$locked)
            <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end">
                <button type="submit" style="background:var(--indigo);color:white;border:none;border-radius:9px;padding:10px 20px;font-weight:800;cursor:pointer">Save Section {{ $section }}</button>
            </div>
        @else
            <div style="padding:14px 20px;border-top:1px solid var(--border);background:#EFF6FF;color:#1E40AF;font-size:12px"><strong>Locked:</strong> this finalized census return cannot be edited.</div>
        @endif
    </form>
</div>

@push('styles')
<style>
.asc-link{display:inline-flex;align-items:center;padding:8px 13px;border-radius:9px;background:var(--indigo);color:white;text-decoration:none;font-size:12px;font-weight:800}.asc-link.secondary{background:#F1F5F9;color:#475569}
</style>
@endpush
@endsection
