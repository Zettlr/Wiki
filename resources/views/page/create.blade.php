@extends('app.frontend')

@section('content')
        <h2>{{ trans('ui.page.create') }}</h2>
        <form method="post" action="{{ url('/create') }}">

            {{ csrf_field() }}

            <div><label for="title">{{ trans('ui.page.title') }}:</label><input type="text" placeholder="Title ..." name="title" id="title" value="{{ old('title') }}"></div>

            <div><label for="slug">{{ trans('ui.page.slug') }}:</label> (<a id="propose-slug">{{ trans('ui.page.slugpropose') }}</a>)
                <input type="text" placeholder="{{ trans('ui.page.slugname') }}" id="slug" name="slug" value="{{ $slug or old('slug') }}"></div>

            <div>
                <textarea name="content" id="gfm-code">{{ old('content') }}</textarea>
            </div>

            <div>
                <input type="submit" value="{{ trans('ui.page.newsubmit') }}" class="page-submit">
            </div>
        </form>

        @if (count($errors) > 0)
            <div class="alert error">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
@endsection
