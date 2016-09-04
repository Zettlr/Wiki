@extends('app.frontend')

@section('content')
    <div id="tabs">
        <h1>{{ trans('ui.page.index') }}</h1>
        <div class="tab-nav">
        <ul>
            <li><a href="#index">{{ trans('ui.page.all') }}</a></li>
            <li><a href="#unreferencedPages">{{ trans('ui.page.unreferenced') }}</a></li>
            <li><a href="#missingLinks">{{ trans('ui.page.missing') }}</a></li>
        </ul>
    </div>
        <div class="tab" id="index">
            @if(count($pages) > 0)
                <table>
                    <tr>
                        <th>{{ trans('ui.page.title') }}</th>
                        <th>{{ trans('ui.page.link') }}</th>
                    </tr>
                    @foreach($pages as $page)
                        <tr>
                            <td><a href="{{ url('/' . $page->slug) }}">{{ $page->title }}</a></td>
                            <td><a href="{{ url('/' . $page->slug) }}">/ {{ $page->slug }}</a></td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div>{{ trans('ui.page.none') }} <a href="{{ url('/create') }}">{{ trans('ui.page.createnew') }}!</a></div>
            @endif
        </div>
        <!-- Second tab: Unreferenced Pages -->
        <div class="tab" id="unreferencedPages">
            @if(count($unreferencedPages) > 0)
                <table>
                    <tr>
                        <th>{{ trans('ui.page.title') }}</th>
                        <th>{{ trans('ui.page.link') }}</th>
                    </tr>
                    @foreach($unreferencedPages as $page)
                        <tr>
                            <td><a href="{{ url('/' . $page->slug) }}">{{ $page->title }}</a></td>
                            <td><a href="{{ url('/' . $page->slug) }}">/ {{ $page->slug }}</a></td>
                        </tr>
                    @endforeach
                </table>
            @else
                <div>{{ trans('ui.page.nounreferenced') }}</div>
            @endif
        </div>
        <!-- Third tab: Missing links -->
        <div class="tab" id="missingLinks">
            <table>
                <tr>
                    <th>{{ trans('ui.page.title') }}</th>
                    <th>{{ trans('ui.page.link') }}</th>
                </tr>
                @foreach($missingPages as $page)
                    <tr>
                        <td><a class="broken" href="{{ url('/') }}/create/{{ $page }}" title="{{ trans('ui.page.createthis') }}">{{ $page }}</a></td>
                        <!-- Todo: Insert a reference to where this link came from -->
                        <td></td>
                    </tr>
                @endforeach
            </table>
        </div>
    </div>
@endsection
