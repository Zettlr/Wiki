@extends('app.backend')

@section('content')
    <div class="container">
        <h1>{{ trans('ui.backend.dashboard') }}</h1>

        <div class="panel">
            <h4>{{ trans('ui.backend.stats.title', ['page' => env('APP_TITLE', '')]) }}</h4>
            <p>{{ trans('ui.backend.stats.exporthtmldesc') }}: <a href="{{ url('/export') }}">{{ trans('ui.backend.stats.exporthtmllink')}}</a>
            <p>{{ trans('ui.backend.stats.pages') }}: {{ $stats->indexedPages }}</p>
            <p>{{ trans('ui.backend.stats.dbsize') }}: {{ $stats->dbSize }}
                <a href="{{ url('/admin/backupDatabase') }}">{{ trans('ui.backend.stats.dbbackup') }}</a>
            </p>
            <p>{{ trans('ui.backend.stats.viewcache') }}: {{ $stats->cacheSize }} <a href="{{ url('/admin/flushViews') }}">{{ trans('ui.backend.stats.viewflush')}}</a></p>
        </div>
    </div>
@endsection
