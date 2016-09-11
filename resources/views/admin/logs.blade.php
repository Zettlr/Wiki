@extends('app.backend')

@section('content')
    <h1>{{ trans('ui.backend.logs') }} <small>{{ $theFile }}</small></h1>
    <div class="alert primary">
        {{ trans('ui.backend.loginfo') }}
    </div>

    @if(count($logfiles) > 0)
        <div id="logselect">
            <div>{{ trans('ui.backend.availablelogs') }}</div>
            @foreach($logfiles as $file)
                <div>
                    @if($file == $theFile)
                        <strong>
                        @endif
                        <a href="{{ url('/admin/logs/') . '/' . $file . '/' }}tail">{{ $file }}</a>
                        @if($file == $theFile)
                        </strong>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="alert primary">
            <a href="{{ url('/admin/logs/') . '/' . $theFile . '/' }}full">{{ trans('ui.backend.logfull') }}</a>
            <a href="{{ url('/admin/logs/') . '/' . $theFile . '/' }}tail">{{ trans('ui.backend.logtail') }}</a>
        </div>
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
@endsection
