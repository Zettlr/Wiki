@extends('app.frontend')

@section('content')
    <div>
        <h2>{{ trans('ui.trash.trash') }} <small><a class="button error" href="{{ url('/trash/empty') }}">{{ trans('ui.trash.empty') }}</a></small></h2>
        @if(count($pages) > 0)
            <table>
                <tr>
                    <th>{{ trans('ui.page.id') }}</th>
                    <th>{{ trans('ui.page.title') }}</th>
                    <th>{{ trans('ui.page.slugname') }}</th>
                    <th>{{ trans('ui.page.restore') }}</th>
                </tr>
            @foreach($pages as $page)
                <tr>
                    <td>{{ $page->id }}</td>
                    <td>{{ $page->title }}</td>
                    <td>{{ $page->slug }}</td>
                    <td><a href="{{ url('/restore/'.$page->id) }}">{{ trans('ui.trash.restore') }}</a></td>
                </tr>
            @endforeach
        </table>
        @else
            <div class="container">{{ trans('ui.trash.none') }}</div>
        @endif


        @if (count($errors) > 0)
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
    </div>
@endsection
