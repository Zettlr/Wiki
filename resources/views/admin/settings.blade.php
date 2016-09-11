{{-- The general settings pane (for general settings) --}}
{{-- Under general settings falling is everything in the env-file --}}
@extends('app.backend')

@section('content')
    <h1 class="page_title">{{ trans('ui.settings.settings')}}</h1>

    <div class="alert primary">
        <p>
            {{ trans('ui.settings.info') }}
        </p>
        <p>
            {{ trans('ui.settings.warning') }}
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

    <form method="POST" action="{{ url('admin/settings') }}">
        {{ csrf_field() }}
        <div class="input-group">
            <span class="group-title">{{ trans('ui.settings.application')}}</span>

            <label for="APP_TITLE">
                {{ trans('ui.settings.title') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.titleinfo') }}"></span>
            </label>
            <input type="text" name="APP_TITLE" value="{{ env('APP_TITLE', '') }}" placeholder="{{ trans('ui.settings.titleplaceholder') }}">

            <label for="APP_URL">
                {{ trans('ui.settings.url') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.urlinfo') }}"></span>
            </label>
            <input type="text" name="APP_URL" value="{{ env('APP_URL', '') }}" placeholder="{{ trans('ui.settings.urlplaceholder') }}">

            <label for="APP_DEFAULT_LOCALE">
                {{ trans('ui.settings.locale') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.localeinfo') }}"></span>
            </label>
            <select name="APP_DEFAULT_LOCALE">
                <option value="en" {{ env('APP_DEFAULT_LOCALE') == 'en' ? 'selected' : '' }}>{{ trans('ui.settings.lang.en') }}</option>
                <option value="de" {{ env('APP_DEFAULT_LOCALE') == 'de' ? 'selected' : '' }}>{{ trans('ui.settings.lang.de') }}</option>
            </select>
        </div>

        <div class="input-group">
            <span class="group-title">{{ trans('ui.settings.auth.title') }}</span>

            <label for="AUTH_ACTIVE">
                {{ trans('ui.settings.auth.active') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.auth.activeinfo') }}"></span>
            </label>
            <select name="AUTH_ACTIVE">
                <option value="true" {{ env('AUTH_ACTIVE') ? 'selected' : '' }}>{{ trans('ui.settings.auth.isactive') }}</option>
                <option value="false" {{ (!env('AUTH_ACTIVE')) ? 'selected' : '' }}>{{ trans('ui.settings.auth.isnotactive') }}</option>
            </select>

            <label for="AUTH_REGISTER">
                {{ trans('ui.settings.auth.register') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.auth.registerinfo') }}"></span>
            </label>
            <select name="AUTH_REGISTER">
                <option value="true" {{ env('AUTH_REGISTER') ? 'selected' : '' }}>{{ trans('ui.settings.auth.registeractive') }}</option>
                <option value="false" {{ (!env('AUTH_REGISTER')) ? 'selected' : '' }}>{{ trans('ui.settings.auth.registerinactive') }}</option>
            </select>

            <label for="AUTH_GUEST_EDIT">
                {{ trans('ui.settings.auth.guest_edit') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.auth.guest_editinfo') }}"></span>
            </label>
            <select name="AUTH_GUEST_EDIT">
                <option value="true" {{ env('AUTH_GUEST_EDIT') ? 'selected' : '' }}>{{ trans('ui.settings.auth.guest_editactive') }}</option>
                <option value="false" {{ (!env('AUTH_GUEST_EDIT')) ? 'selected' : '' }}>{{ trans('ui.settings.auth.guest_editinactive') }}</option>
            </select>
        </div>

        <div class="input-group">
            <span class="group-title">{{ trans('ui.settings.database')}}</span>

            <label for="DB_CONNECTION">
                {{ trans('ui.settings.dbtype') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.dbtypeinfo') }}"></span>
            </label>
            <select name="DB_CONNECTION">
                <option value="sqlite" {{ env('DB_CONNECTION') == 'sqlite' ? 'selected' : '' }}>SQLite</option>
                <option value="mysql" {{ env('DB_CONNECTION') == 'mysql' ? 'selected' : '' }}>MySQL</option>
            </select>

            <label for="DB_HOST">
                {{ trans('ui.settings.dbhost') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.dbhostinfo') }}"></span>
            </label>
            <input type="text" name="DB_HOST" value="{{ env('DB_HOST', '') }}" placeholder="{{ trans('ui.settings.dbhostplaceholder') }}">

            <label for="DB_PORT">
                {{ trans('ui.settings.dbport') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.dbportinfo') }}"></span>
            </label>
            <input type="text" name="DB_PORT" value="{{ env('DB_PORT', '') }}" placeholder="{{ trans('ui.settings.dbportplaceholder') }}">

            <label for="DB_DATABASE">
                {{ trans('ui.settings.dbname') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.dbnameinfo') }}"></span>
            </label>
            <input type="text" name="DB_DATABASE" value="{{ env('DB_DATABASE', '') }}" placeholder="{{ trans('ui.settings.dbnameplaceholder') }}">

            <label for="DB_USERNAME">
                {{ trans('ui.settings.dbuser') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.dbuserinfo') }}"></span>
            </label>
            <input type="text" name="DB_USERNAME" value="{{ env('DB_USERNAME', '') }}" placeholder="{{ trans('ui.settings.dbuserplaceholder') }}">

            <label for="DB_PASSWORD">
                {{ trans('ui.settings.dbpass') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.dbpassinfo') }}"></span>
            </label>
            <input type="password" name="DB_PASSWORD" value="{{ env('DB_PASSWORD', '') }}" placeholder="{{ trans('ui.settings.dbpassplaceholder') }}">
        </div>

        <div class="input-group">
            <span class="group-title">{{ trans('ui.settings.storage') }}</span>

            <label for="CACHE_DRIVER">
                {{ trans('ui.settings.cache') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.cacheinfo') }}"></span>
            </label>
            <select name="CACHE_DRIVER">
                <option value="redis" {{ env('CACHE_DRIVER') == 'redis' ? 'selected' : '' }}>Redis</option>
                <option value="array" {{ env('CACHE_DRIVER') == 'array' ? 'selected' : '' }}>Array</option>
                <option value="database" {{ env('CACHE_DRIVER') == 'database' ? 'selected' : '' }}>{{ trans('ui.settings.db') }}</option>
                <option value="file" {{ env('CACHE_DRIVER') == 'file' ? 'selected' : '' }}>{{ trans('ui.settings.filesys') }}</option>
            </select>

            <label for="SESSION_DRIVER">
                {{ trans('ui.settings.session') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.sessioninfo') }}"></span>
            </label>
            <select name="SESSION_DRIVER">
                <option value="file" {{ env('CACHE_DRIVER') == 'file' ? 'selected' : '' }}>{{ trans('ui.settings.filesys') }}</option>
                <option value="cookie" {{ env('CACHE_DRIVER') == 'cookie' ? 'selected' : '' }}>Cookie</option>
                <option value="database" {{ env('CACHE_DRIVER') == 'database' ? 'selected' : '' }}>{{ trans('ui.settings.db') }}</option>
                <option value="redis" {{ env('CACHE_DRIVER') == 'redis' ? 'selected' : '' }}>Redis</option>
                <option value="array" {{ env('CACHE_DRIVER') == 'array' ? 'selected' : '' }}>Array</option>
            </select>
        </div>

        <div class="input-group">
            <span class="group-title">
                {{ trans('ui.settings.cacheserver') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.cacheserverinfo') }}"></span>
            </span>

            <label for="REDIS_HOST">{{ trans('ui.settings.redishost') }}</label>
            <input type="text" name="REDIS_HOST" value="{{ env('REDIS_HOST', '') }}" placeholder="{{ trans('ui.settings.redishostplaceholder') }}">

            <label for="REDIS_PASSWORD">{{ trans('ui.settings.redispass') }}</label>
            <input type="password" name="REDIS_PASSWORD" value="{{ env('REDIS_PASSOWRD', '') }}" placeholder="{{ trans('ui.settings.redispassplaceholder') }}">

            <label for="REDIS_PORT">{{ trans('ui.settings.redisport') }}</label>
            <input type="text" name="REDIS_PORT" value="{{ env('REDIS_PORT', '') }}" placeholder="{{ trans('ui.settings.redisportplaceholder') }}">
        </div>

        <div class="input-group">
            <label>
                {{ trans('ui.settings.rebuildindex') }}
                <span data-display="tooltip" data-content="{{ trans('ui.settings.rebuildinfo') }}"></span>
            </label>
            <input type="button" id="rebuildButton" value="{{ trans('ui.settings.rebuild') }}">
            <div class="alert primary">
                <p>
                    {{ trans('ui.settings.indexed',
                        ['pages' => $settings->indexedPages,
                        'words' => $settings->indexedWords])
                    }}
                </p>
            </div>
        </div>

        <div class="input-group">
            <input type="submit" value="{{ trans('ui.settings.save') }}">
        </div>
    </form>
@endsection
