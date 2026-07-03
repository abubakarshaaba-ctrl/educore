@php
    $uid            = $uid ?? 'geo_' . uniqid();
    $stateField     = $stateField ?? 'state';
    $lgaField       = $lgaField ?? 'lga';
    $districtField  = $districtField ?? 'senatorial_district';
    $selectedState  = $selectedState ?? '';
    $selectedLga    = $selectedLga ?? '';
    $selectedDistrict = $selectedDistrict ?? '';
    $showLga        = $showLga ?? true;
    $showDistrict   = $showDistrict ?? true;
    $labelClass     = $labelClass ?? 'form-label';
    $inputClass     = $inputClass ?? 'form-control';
    $wrapClass      = $wrapClass ?? 'form-group';
    $stateLabel     = $stateLabel ?? 'State';
    $lgaLabel       = $lgaLabel ?? 'LGA';
    $districtLabel  = $districtLabel ?? 'Senatorial District';
    $geo = \App\Data\NigeriaGeo::all();
@endphp

<div class="{{ $wrapClass }}">
    <label class="{{ $labelClass }}">{{ $stateLabel }}</label>
    <select name="{{ $stateField }}" id="geo_{{ $uid }}_state" class="{{ $inputClass }}"
            onchange="nigeriaGeoUpdate('{{ $uid }}', this.value)">
        <option value="">— Select State —</option>
        @foreach(array_keys($geo) as $state)
        <option value="{{ $state }}" {{ $selectedState === $state ? 'selected' : '' }}>{{ $state }}</option>
        @endforeach
    </select>
</div>

@if($showLga)
<div class="{{ $wrapClass }}">
    <label class="{{ $labelClass }}">{{ $lgaLabel }}</label>
    <select name="{{ $lgaField }}" id="geo_{{ $uid }}_lga" class="{{ $inputClass }}">
        <option value="">— Select LGA —</option>
        @if($selectedState && isset($geo[$selectedState]))
            @foreach($geo[$selectedState]['lgas'] as $lga)
            <option value="{{ $lga }}" {{ $selectedLga === $lga ? 'selected' : '' }}>{{ $lga }}</option>
            @endforeach
        @endif
    </select>
</div>
@endif

@if($showDistrict)
<div class="{{ $wrapClass }}">
    <label class="{{ $labelClass }}">{{ $districtLabel }}</label>
    <select name="{{ $districtField }}" id="geo_{{ $uid }}_district" class="{{ $inputClass }}">
        <option value="">— Select District —</option>
        @if($selectedState && isset($geo[$selectedState]))
            @foreach($geo[$selectedState]['senatorial_districts'] as $district)
            <option value="{{ $district }}" {{ $selectedDistrict === $district ? 'selected' : '' }}>{{ $district }}</option>
            @endforeach
        @endif
    </select>
</div>
@endif

@once
@push('scripts')
<script>
(function(){ window.__nigeriaGeo = window.__nigeriaGeo || {!! json_encode(\App\Data\NigeriaGeo::all()) !!}; })();
function nigeriaGeoUpdate(uid, state) {
    var geo = window.__nigeriaGeo, d = geo[state] || {lgas:[],senatorial_districts:[]};
    var lgaEl = document.getElementById('geo_'+uid+'_lga');
    var dEl   = document.getElementById('geo_'+uid+'_district');
    if (lgaEl) { lgaEl.innerHTML = '<option value="">— Select LGA —</option>'; d.lgas.forEach(function(v){ lgaEl.innerHTML += '<option value="'+v+'">'+v+'</option>'; }); }
    if (dEl)   { dEl.innerHTML   = '<option value="">— Select District —</option>'; (d.senatorial_districts||[]).forEach(function(v){ dEl.innerHTML += '<option value="'+v+'">'+v+'</option>'; }); }
}
</script>
@endpush
@endonce