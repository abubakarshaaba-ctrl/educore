@if($decision)
    @if($decision->blocking)
        <div class="cycle-alert cycle-alert-danger">
            <strong>Blocking items</strong>
            <ul>
                @foreach($decision->blocking as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($decision->warnings)
        <div class="cycle-alert cycle-alert-warning">
            <strong>Warnings</strong>
            <ul>
                @foreach($decision->warnings as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if($decision->information)
        <div class="cycle-alert">
            <strong>Information</strong>
            <ul>
                @foreach($decision->information as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endif
@endif
