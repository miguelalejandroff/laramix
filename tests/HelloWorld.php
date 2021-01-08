<?php

use Illuminate\Support\Facades\Route;

Route::get('say', function() {echo
"<html>
    <head>
        <meta charset=\"utf-8\">
        <meta name=\"viewport\" content=\"width=device-width, initial-scale=1, viewport-fit=cover\">
        
        <title>Hello World</title>        
        <link rel=\"canonical\" href=\"https://laravel.com/docs/7.x/installation\">
    
        <!-- Favicon -->
        <link rel=\"apple-touch-icon\" sizes=\"180x180\" href=\"https://laravel.com//img/favicon/apple-touch-icon.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"32x32\" href=\"https://laravel.com//img/favicon/favicon-32x32.png\">
        <link rel=\"icon\" type=\"image/png\" sizes=\"16x16\" href=\"https://laravel.com//img/favicon/favicon-16x16.png\">
    </head>
    <body>
        <h1>This is Good to say \"Hello world!\"</h1>
    </body>
    <footer>
        <!-- footer -->
    </footer>
</html>";
});
