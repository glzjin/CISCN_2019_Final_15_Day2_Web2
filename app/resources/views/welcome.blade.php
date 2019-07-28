<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>File Manager</title>
        <link rel="stylesheet" href="/css/all.css">
        <link rel="stylesheet" href="/css/app.css">
<!--        <script src="{{ asset('vendor/file-manager/js/file-manager.js') }}"></script>-->
    </head>
    <body>
    @if (Request::ip() === '127.0.0.1')
        <div class="container">
            <div style="height: 800px;">
                <div id="fm"></div>
            </div>
        </div>
        <script src="/js/app.js"></script>

    @else
        Only localhost can access it!
    @endif
    </body>
</html>
