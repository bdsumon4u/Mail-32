<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.bunny.net/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Scripts -->
        <style type="text/css">
            a {
                color: red;
            }
        </style>
    </head>
    <body class="font-sans antialiased">
        <header style="color: red; background: black; padding: 2px 4px;">Header</header>
        <main><img src="{{ $message->embed(public_path('storage/Dharmik-Planet.png'), 'Dharmik Planet.png') }}"></main>
        <main><img src="{{ $message->embed(public_path('storage/Dharmik-Planet.png'), 'Dharmik Planet.png') }}"></main>
        <footer style="color: red; background: black; padding: 2px 4px;">Footer</footer>
    </body>
</html>
