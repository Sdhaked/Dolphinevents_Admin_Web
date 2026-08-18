<?php

use Illuminate\Support\Facades\Route;

test('the website home route is registered', function () {
    $route = Route::getRoutes()->getByName('website.home.index');

    expect($route)->not->toBeNull()
        ->and($route->uri())->toBe('/')
        ->and($route->methods())->toContain('GET');
});
