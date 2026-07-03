<div class="onboard-progress">
    <div class="progress-head">
        <strong>Setup progress</strong>
        <span>{{ $status->progress_percentage }}%</span>
    </div>
    <div class="progress-bar"><span style="width:{{ $status->progress_percentage }}%"></span></div>
    <div class="step-grid">
        @foreach($status->steps as $step)
            <a href="{{ route($step['route']) }}" class="step-pill step-{{ $step['status'] }}">
                <span>{{ $step['label'] }}</span>
                <b>{{ ucfirst($step['status']) }}</b>
            </a>
        @endforeach
    </div>
</div>
