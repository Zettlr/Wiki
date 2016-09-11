@extends('app.frontend')

@section('content')
    <h1>{{ trans('ui.backend.user.register') }}</h1>

    @if(count($errors) > 0)
        <div class="alert error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('/register') }}" method="POST">
        {!! csrf_field() !!}
        <div class="input-group">
            <span class="group-title">{{ trans('ui.backend.user.information') }}</span>

            <input type="text" name="name" placeholder="{{ trans('ui.backend.user.name') }}" value="{{ old('name') }}">
            <input type="email" name="email" placeholder="{{ trans('ui.backend.user.email') }}" value="{{ old('email') }}">
            <input type="password" name="password" placeholder="{{ trans('ui.backend.user.password') }}">
            <input type="password" name="password_confirmation" placeholder="{{ trans('ui.backend.user.password_confirm') }}">
            @if(!env('AUTH_REGISTER'))
                <label for="register_token">{{ trans('ui.backend.user.token') }}</label>
                <input type="text" name="register_token" placeholder="{{ trans('ui.backend.user.token_input') }}" value="{{ old('register_token') }}">
            @endif
        </div>
        <input type="submit" value="{{ trans('ui.backend.user.register_submit') }}">
    </form>
@endsection
