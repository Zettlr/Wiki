@extends('app.frontend')

@section('content')
    <article id="wikitext">
            <h1 class="clearfix page-title"><span data-name="page-title">{{ $page->title }}</span> <small>{{ (strlen($page->slug) > 60) ? substr($page->slug, 0, 60) . "&hellip;" : $page->slug }}</small></h1>
        <div class="task-buttons">
            <a class="button success" href="{{ url('/edit/'.$page->slug) }}">{{ trans('ui.page.edit') }}</a>
            <a class="button success" id="contenttoolsEdit">{{ trans('ui.page.contenttools') }}</a>
            <a class="button error hidden" id="contenttoolsCancel">{{ trans('ui.page.cancel') }}</a>
            @if($page->slug !== "Main_Page")
                <a class="button error" href="{{ url('/remove/' . $page->id) }}">{{ trans('ui.page.trash') }}</a>
            @endif
        </div>
        @if(count($referencingPages) > 0)
            <p class="ref-pages-list">
                {{ trans('ui.page.referencing') }}:
                @foreach($referencingPages as $p)
                    <a href="{{ url('/') }}/{{$p->slug}}">{{ $p->title }}</a> /
                @endforeach
            </p>
        @endif
        {{--The following div wrapper is used for content tools inline editing (WYSIWYG) --}}
        {{-- The page ID will be stored in data-id for retrieval and saving of URLs--}}
        {{-- The page slug will be stored in data-slug as we are using our PageController-function for that --}}
        <div data-editable data-name="page-content" data-id="{{ $page->id }}" data-slug="{{ $page->slug }}">
            {!! $page->content !!}
        </div>
    </article>
@endsection

@section('footer-content')
    @if($page->created_at != null)
        {{ trans('ui.page.createdat') }} <strong>{{ $page->created_at->format("d.m.Y, H:i") }}</strong> &mdash;
    @endif
    @if($page->updated_at != null)
        {{ trans('ui.page.updatedat') }} <strong>{{ $page->updated_at->format("d.m.Y, H:i") }}</strong>
    @endif
@endsection
