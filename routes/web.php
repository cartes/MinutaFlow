<?php

use Illuminate\Support\Facades\Route;

// SPA: todas las rutas web las resuelve Vue Router en el cliente.
Route::view('/{any?}', 'app')->where('any', '^(?!api).*$');
