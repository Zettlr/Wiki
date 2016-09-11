@extends('app.backend')

@section('content')
    <div class="container">
        <h1>{{ trans('ui.backend.dashboard') }}</h1>

        @if(!Auth::guest())
            <div class="alert primary">
                {{ trans('ui.backend.user.welcome', ['user' => Auth::user()->name]) }}
                <a href="{{ url('/logout') }}">{{ trans('ui.backend.user.logout') }}</a>
            </div>
        @endif

        <div class="panel">
            <h4>{{ trans('ui.backend.stats.title', ['page' => env('APP_TITLE', '')]) }}</h4>
            <p>{{ trans('ui.backend.stats.exporthtmldesc') }}: <a href="{{ url('/export') }}">{{ trans('ui.backend.stats.exporthtmllink')}}</a>
                <p>{{ trans('ui.backend.stats.pages') }}: {{ $stats->indexedPages }}</p>
                <p>{{ trans('ui.backend.stats.dbsize') }}: {{ $stats->dbSize }}
                    <a href="{{ url('/admin/backupDatabase') }}">{{ trans('ui.backend.stats.dbbackup') }}</a>
                </p>
                <p>{{ trans('ui.backend.stats.cache') }}: {{ $stats->cacheSize }} <a href="{{ url('/admin/flushCache') }}">{{ trans('ui.backend.stats.cacheflush')}}</a></p>
            </div>
        </div>
    @endsection
