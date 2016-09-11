@extends('app.frontend')

@section('content')
    <h1>{{ trans('ui.errors.401.title') }}</h1>
    <div class="alert error">
        {{ trans('ui.errors.401.message') }}<br>
        <a href="{{ url('/login') }}">{{ trans('ui.backend.user.login') }}</a>
    </div>
@endsection
