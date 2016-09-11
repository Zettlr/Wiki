@extends('app.frontend')

@section('content')
    <h4>404: File not found</h4>
    <div class="alert error">
        The page you were looking for does not exist!<br>
        <a href="{{ url('/create') . '/' . rawurlencode(substr($_SERVER['REQUEST_URI'], 1)) }}">Create this page</a>
    </div>
@endsection
