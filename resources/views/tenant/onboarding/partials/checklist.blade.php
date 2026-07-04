<div class="checklist">
    @if($status->blocking_items)
        <div class="check-card blocking">
            <h3>Blocking Items</h3>
            <ul>
                @foreach($status->blocking_items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($status->warning_items)
        <div class="check-card warning">
            <h3>Warnings</h3>
            <ul>
                @foreach($status->warning_items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($status->completed_items)
        <div class="check-card complete">
            <h3>Completed</h3>
            <ul>
                @foreach($status->completed_items as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
