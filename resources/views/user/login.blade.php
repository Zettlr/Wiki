@extends('app.frontend')

@section('content')
    <h1>{{ trans('ui.backend.user.login') }}</h1>
    <form action="{{ url('/login') }}" method="POST">
        {!! csrf_field() !!}
        <div class="input-group">
            <span class="group-title">{{ trans('ui.backend.user.login') }}</span>

            <input type="text" name="name" placeholder="{{ trans('ui.backend.user.name_or_mail') }}">
            <input type="password" name="password" placeholder="{{ trans('ui.backend.user.password') }}">
            <span>{{ trans('ui.backend.user.no_account') }} <a href="{{ url('/register') }}">{{ trans('ui.backend.user.register') }}</a></span>
        </div>
        <input type="submit" value="{{ trans('ui.backend.user.login') }}">
    </form>
@endsection
