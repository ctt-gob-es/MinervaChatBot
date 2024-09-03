<!DOCTYPE html>
<html>
<head>
    <base href="/">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @vite(['resources/sass/app.scss', 'resources/js/app.js', 'resources/css/app.css', ])

</head>
<body>
    <div id="app">
        <app color="{{ getColorSetting() }}"></app>
    </div>
</body>

<style>
    html, body{
        background: transparent !important;
        overflow: hidden!important;
    }
</style>

