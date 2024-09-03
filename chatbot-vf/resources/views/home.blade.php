@extends('layouts.app')

@section('content')
<div id="app">
    <app color="{{ getColorSetting() }}" role-user="{{ getLoggedInUserRole() }}"></app>
</div>

@endsection
@section('js')
<script type="module" src="../js/main.js"></script>
@stop
