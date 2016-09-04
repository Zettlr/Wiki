@extends('app.frontend')

@section('content')
        <h2>{{ trans('ui.page.edit') }} <small>{{ $page->title }}</small></h2>
        <form method="post" action="{{ url('/edit') }}">

            {{ csrf_field() }}

            <div>
                <label for="title">{{ trans('ui.page.title') }}:</label>
                <input type="text" placeholder="{{ trans('ui.page.title') }}" name="title" id="title" value="{{ $page->title }}">
            </div>

            <div >
                <label for="slug">{{ trans('ui.page.slug') }}:</label>
                <input type="text" placeholder="{{ trans('ui.page.slugname') }}" name="slug" value="{{ $page->slug }}" readonly>
            </div>

            <div>
                <textarea name="content" id="gfm-code">{{ $page->content }}</textarea>
            </div>

            <div>
                <input type="submit" value="{{ trans('ui.page.submitchanges') }}">
            </div>
        </form>

        @if (count($errors) > 0)
            <div>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
@endsection
