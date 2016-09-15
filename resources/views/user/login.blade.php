@extends('app.frontend')

@section('content')
    <h1>{{ trans('ui.backend.user.login') }}</h1>
    <form action="{{ url('/login') }}" method="POST">
        {!! csrf_field() !!}
        <div class="input-group">
            <span class="group-title">{{ trans('ui.backend.user.login') }}</span>

            <input type="text" class="inline" name="name" placeholder="{{ trans('ui.backend.user.name_or_mail') }}" autofocus>
            <input type="password" class="inline" name="password" placeholder="{{ trans('ui.backend.user.password') }}">
            <span>{{ trans('ui.backend.user.no_account') }} <a href="{{ url('/register') }}">{{ trans('ui.backend.user.register') }}</a></span>
            <br />
            <span>
                <input type="checkbox" name="remember" class="inline" id="remember_me">
                <label class="inline" for="remember_me">{{ trans('ui.backend.user.remember') }}</label>
            </span>
            <input type="submit" class="inline" value="{{ trans('ui.backend.user.login') }}">
        </div>
    </form>
@endsection
