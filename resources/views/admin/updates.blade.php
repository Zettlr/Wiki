@extends('app.backend')

@section('content')
    <h1>{{ trans('ui.backend.updates.update') }}</h1>
    <div class="alert primary">
        {{ trans('ui.backend.updates.info') }}
    </div>
    @if($update !== 'non')
        @if($update == 'maj')
            <div class="alert warning">
                {{ trans('ui.backend.updates.major', ['current' => env('APP_VERSION', 'v0.0.0'), 'new' => $newVersion, 'published' => $published]) }}
            </div>
        @elseif($update == 'min')
            <div class="alert warning">
                {{ trans('ui.backend.updates.minor', ['current' => env('APP_VERSION', 'v0.0.0'), 'new' => $newVersion, 'published' => $published]) }}
            </div>
        @elseif($update == 'pat')
            <div class="alert warning">
                {{ trans('ui.backend.updates.patch', ['current' => env('APP_VERSION', 'v0.0.0'), 'new' => $newVersion, 'published' => $published]) }}
            </div>
        @endif
        <div>
            {!! $changelog or '' !!}
        </div>
        <p>
            <a href="{{ url('/admin/updates/upgrade') }}">{{ trans('ui.backend.updates.commence') }}</a>
        </p>
    @else
        <div class="alert muted">
            {{ trans('ui.backend.updates.none', ['current' => env('APP_VERSION', 'v0.0.0')]) }}
        </div>
    @endif
@endsection
