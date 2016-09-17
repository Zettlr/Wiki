@extends('app.backend')

@section('content')
    <h1>{{ trans('ui.backend.updates.update') }}</h1>
    <div class="alert primary">
        {{ trans('ui.backend.updates.info') }}
    </div>
    @if(count($diff) > 0)
        <ul>
            @foreach($diff['add'] as $path)
                <li style="background-color:green">I would add {{ $path }}</li>
            @endforeach
            @foreach($diff['change'] as $path)
                <li style="background-color:yellow">I would change {{ $path }}</li>
            @endforeach
            @foreach($diff['remove'] as $path)
                <li style="background-color:red">I would remove {{ $path }}</li>
            @endforeach
        </ul>
    @endif
@endsection
