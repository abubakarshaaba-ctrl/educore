@extends('layouts.app')
@section('title', 'Invoice Generation History')
@section('page-title', 'Invoice Generation History')

@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
    <a href="{{ route('fees.generate.index') }}" style="font-size:13px;color:var(--indigo);text-decoration:none;display:inline-flex;align-items:center;gap:4px">
        ← Back to Generator
    </a>
</div>

<div style="background:white;border:1px solid var(--border);border-radius:12px;overflow:hidden">
    <div style="padding:13px 18px;border-bottom:1px solid var(--border);background:#F8FAFC">
        <span style="font-size:13px;font-weight:700">All Generation Batches</span>
    </div>
    <div style="overflow-x:auto">
        <table style="width:100%;border-collapse:collapse;font-size:13px">
            <thead>
                <tr>
                    @foreach(['#','Term','Scope','Students','Generated','Skipped','Total Value','By','When','Status',''] as $h)
                    <th style="padding:9px 12px;text-align:left;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:var(--slate-light);border-bottom:1px solid var(--border);background:#F8FAFC;white-space:nowrap">{{ $h }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
            @forelse($batches as $batch)
            <tr>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border);color:var(--slate-light)">#{{ $batch->id }}</td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-weight:600">
                    {{ optional($batch->term)->name }}
                    <div style="font-size:11px;color:var(--slate-light);font-weight:400">{{ optional(optional($batch->term)->session)->name }}</div>
                </td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border)">
                    @if($batch->scope === 'class_level') 📚 {{ optional($batch->classLevel)->name ?? 'All Levels' }}
                    @elseif($batch->scope === 'class_arm') 🏫 {{ optional($batch->classArm)->name ?? '—' }}
                    @else 🌍 All Students
                    @endif
                </td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border)">{{ $batch->total_students }}</td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border);color:#059669;font-weight:700">{{ $batch->generated_count }}</td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border);color:#D97706">{{ $batch->skipped_count }}</td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-weight:600">₦{{ number_format($batch->total_value) }}</td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-size:12px">{{ optional($batch->generatedBy)->name ?? '—' }}</td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border);font-size:12px;white-space:nowrap">{{ $batch->created_at->format('d M Y H:i') }}</td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border)">
                    @if($batch->status === 'completed')
                        <span style="display:inline-flex;font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;background:#ECFDF5;color:#059669">Done</span>
                    @elseif($batch->status === 'partial')
                        <span style="display:inline-flex;font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;background:#FFFBEB;color:#D97706">Partial</span>
                    @else
                        <span style="display:inline-flex;font-size:11px;font-weight:600;padding:2px 9px;border-radius:20px;background:#FEF2F2;color:#DC2626">Voided</span>
                    @endif
                </td>
                <td style="padding:10px 12px;border-bottom:1px solid var(--border)">
                    @if($batch->status === 'completed')
                    <form method="POST" action="{{ route('fees.generate.batch.void', $batch) }}"
                          onsubmit="return confirm('Void batch #{{ $batch->id }}? All unpaid invoices in it will be permanently deleted.')">
                        @csrf @method('DELETE')
                        <button type="submit" style="display:inline-flex;align-items:center;padding:5px 10px;font-size:11px;font-weight:600;font-family:inherit;background:#FEF2F2;color:#DC2626;border:1px solid #FECACA;border-radius:7px;cursor:pointer">
                            Void
                        </button>
                    </form>
                    @endif
                    @if($batch->notes)
                    <div style="font-size:11px;color:var(--slate-light);margin-top:4px">{{ $batch->notes }}</div>
                    @endif
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="11" style="text-align:center;padding:40px;color:var(--slate-light)">
                    No generation batches yet. Go to the generator to create invoices.
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($batches->hasPages())
    <div style="padding:14px 18px;border-top:1px solid var(--border)">
        {{ $batches->links() }}
    </div>
    @endif
</div>
@endsection
