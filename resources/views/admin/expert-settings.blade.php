{{-- The general settings pane (for general settings) --}}
{{-- Under general settings falling is everything in the env-file --}}
@extends('app.backend')

@section('content')
    <h1 class="page_title">{{ trans('ui.settings.advanced')}}</h1>

    <div class="alert warning">
        <p>
            {{ trans('ui.settings.advancedwarning') }}
        </p>
    </div>

    @if (count($errors) > 0)
        <div class="alert error">
            <p>{{ trans('ui.settings.errors') }}</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ url('admin/advancedSettings') }}">
        {{ csrf_field() }}
        <div class="input-group">
            <span class="group-title">{{ trans('ui.settings.log.log')}}</span>

            <label for="LOG_STORAGE">
                {{ trans('ui.settings.log.storage') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.log.storageinfo') }}"></span>
            </label>
            <select name="LOG_STORAGE">
                <option value="daily" {{ env('LOG_STORAGE') == 'daily' ? 'selected' : '' }}>{{ trans('ui.settings.log.daily') }}</option>
                <option value="single" {{ env('LOG_STORAGE') == 'single' ? 'selected' : '' }}>{{ trans('ui.settings.log.single') }}</option>
            </select>

            <label for="LOG_FILES">
                {{ trans('ui.settings.log.files') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.log.filesinfo') }}"></span>
            </label>
            <input type="text" name="LOG_FILES" value="{{ env('LOG_FILES') }}" placeholder="{{ trans('ui.settings.log.filesplaceholder') }}">

            <label for="LOG_LEVEL">
                {{ trans('ui.settings.log.level') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.log.levelinfo') }}"></span>
            </label>
            <select name="LOG_LEVEL">
                <option value="debug" {{ env('LOG_LEVEL') == 'debug' ? 'selected' : '' }}>{{ trans('ui.settings.log.debug') }}</option>
                <option value="info" {{ env('LOG_LEVEL') == 'info' ? 'selected' : '' }}>{{ trans('ui.settings.log.info') }}</option>
                <option value="notice" {{ env('LOG_LEVEL') == 'notice' ? 'selected' : '' }}>{{ trans('ui.settings.log.notice') }}</option>
                <option value="warning" {{ env('LOG_LEVEL') == 'warning' ? 'selected' : '' }}>{{ trans('ui.settings.log.warning') }}</option>
                <option value="error" {{ env('LOG_LEVEL') == 'error' ? 'selected' : '' }}>{{ trans('ui.settings.log.error') }}</option>
                <option value="critical" {{ env('LOG_LEVEL') == 'critical' ? 'selected' : '' }}>{{ trans('ui.settings.log.critical') }}</option>
                <option value="alert" {{ env('LOG_LEVEL') == 'alert' ? 'selected' : '' }}>{{ trans('ui.settings.log.alert') }}</option>
                <option value="emergency" {{ env('LOG_LEVEL') == 'emergency' ? 'selected' : '' }}>{{ trans('ui.settings.log.emergency') }}</option>
            </select>
        </div>

        <div class="input-group">
            <input type="submit" value="{{ trans('ui.settings.save') }}">
        </div>
    </form>
@endsection
