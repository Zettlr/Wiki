@extends('app.backend')

@section('content')
    <h1>{{ trans('ui.backend.user.account') }}</h1>
    <div class="alert primary">
        {{ trans('ui.backend.user.accountinfo') }}
    </div>

    @if(count($errors) > 0)
        <div class="alert error">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ url('/admin/account') }}" method="POST">
        {!! csrf_field() !!}

        <div class="input-group">
            <span class="group-title">{{ trans('ui.backend.user.information') }}</span>

            <label for="name">
                {{ trans('ui.backend.user.name') }}
            </label>
            <input type="text" value="{{Auth::user()->name}}" name="name">

            <label for="email">
                {{ trans('ui.backend.user.email') }}
            </label>
            <input type="email" value="{{Auth::user()->email}}" name="email">
        </div>

        <div class="input-group">
            <span class="group-title">{{ trans('ui.backend.user.api_token') }}</span>

            <div class="alert primary">
                {{ trans('ui.backend.user.api_token_info', ['token' => \Auth::user()->api_token]) }}<br>
                <a href="{{ url('/admin/regenerate-api-token') }}">{{ trans('ui.backend.user.api_token_regen') }}</a>
            </div>
        </div>

        <div class="input-group">
            <span class="group-title">{{ trans('ui.settings.save')}}</span>
            <label for="old_password">
                {{ trans('ui.backend.user.pw_verify') }}
            </label>
            <input type="password" name="old_password" placeholder="{{ trans('ui.backend.user.pw_verify') }}">
            <input type="submit" value="{{ trans('ui.settings.save')}}">
        </div>
    </form>
@endsection
