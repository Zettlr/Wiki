@extends('app.frontend')

@section('content')
    <div class="container">
        <h1>{{ trans('ui.search.search') }}: {{ $term }}</h1>
        @if(strlen($searchLink) > 0)
            <p>{{ trans('ui.search.suggest') }} <strong><a href="{{ url('/search') }}/{{ $searchLink }}">{{ $searchSuggestion }}</a></strong>?</p>
        @endif

        @if(count($pages) > 0)
            <p>{{ trans('ui.search.return', ['results' => count($pages) ]) }}:</p>
            @foreach($pages as $page)
                {{-- These links will also highlight the searchterm --}}
                <a class="list-element" href="{{ url('/' . $page->slug . '/' . $term) }}">
                    {{-- There can be span elements inside the title --}}
                    <h4 class="list-element-header">{{ $page->title }} <small>(<strong>{{ $page->relevancy }}%</strong> {{ trans('ui.search.relevance') }})</small></h4>
                    <p class="list-element-content">
                        {{ substr($page->content, 0, 300) }} &hellip;
                    </p>
                </a>
            @endforeach
        @else
            <p>{{ trans('ui.search.noresults') }}</p>
        @endif
    </div>

@endsection
