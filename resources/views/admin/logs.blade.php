@extends('app.backend')

@section('content')
    <div class="container">
        <h1>{{ trans('ui.backend.logs') }}</h1>
        <div class="alert primary">
            {{ trans('ui.backend.loginfo') }}
        </div>

        @if(count($logfiles) > 0)
            <label>{{ trans('ui.backend.availablelogs') }}:</label>
            <select>
                @foreach($logfiles as $file)
                    <option>{{ $file }}</option>
                @endforeach
            </select>

            <table class="log">
                @foreach($lines as $line)
                <tr>
                    <td class="line">{{ $line->number }}</td>
                    <td class="content">{{ $line->contents }}</td>
                </tr>
            @endforeach
            </table>
        @else
            <div class="alert primary">
                <p>{{ trans('ui.backend.lognone') }}</p>
            </div>
        @endif
    </div>
@endsection
