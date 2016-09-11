@extends('app.backend')

@section('content')
    <h1>{{ trans('ui.backend.token') }}</h1>
    <div class="alert primary">
        {{ trans('ui.backend.tokeninfo') }}
    </div>

    <form action="{{ url('/admin/token') }}" method="POST">
        {!! csrf_field() !!}
    @if(count($token) > 0)
        <table>
            <tr>
                <th>{{ trans('ui.backend.token') }}</th>
                <th>{{ trans('ui.backend.token_remaining') }}</th>
            </tr>
            @foreach($token as $t)
                <tr>
                    <td><input type="hidden" value="{{ $t['token'] }}" name="token[]">{{ $t['token'] }}</td>
                    <td><input type="text" value="{{ $t['uses'] }}" placeholder="Enter an amount of uses" name="uses[]"></td>
                </tr>
            @endforeach
        </table>
    @else
        <div class="alert primary">{{ trans('ui.backend.no_token') }}</div>
    @endif

        <div class="input-group">
            <span class="group-title">{{ trans('ui.backend.create_token') }}</span>

            <label for="reg_token">{{ trans('ui.backend.token_amount') }}</label>
            <input type="text" name="reg_token" placeholder="{{ trans('ui.backend.tokenplaceholder') }}" value="">
            <label for="reg_token_uses">{{ trans('ui.backend.token_uses') }}</label>
            <input type="text" name="reg_token_uses" placeholder="{{ trans('ui.backend.tokenplaceholder') }}" value="">
            </div>
            <input type="submit" value="{{ trans('ui.settings.save') }}">
    </form>
@endsection
