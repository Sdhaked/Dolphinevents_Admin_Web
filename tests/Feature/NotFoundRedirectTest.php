<?php

it('redirects browser 404 responses home with a notification', function () {
    $response = $this->get('/this-page-does-not-exist', [
        'Accept' => 'text/html',
        'Sec-Fetch-Dest' => 'document',
    ]);

    $response->assertRedirect(route('website.home.index', ['not_found' => 1]));
});

it('renders the 404 notification in the website layout', function () {
    request()->query->set('not_found', '1');

    $this->view('layouts.website')
        ->assertSee('data-site-notification', false)
        ->assertSee('Page not found. You have been redirected to the home page.');
});

it('keeps API 404 responses as JSON', function () {
    $this->getJson('/api/this-endpoint-does-not-exist')
        ->assertNotFound()
        ->assertJsonPath('message', 'The route api/this-endpoint-does-not-exist could not be found.');
});

it('does not redirect non HTML 404 responses', function () {
    $this->get('/missing-image.png', [
        'Accept' => 'image/avif,image/webp,image/*,*/*;q=0.8',
        'Sec-Fetch-Dest' => 'image',
    ])->assertNotFound();
});

it('does not redirect background fetch 404 responses', function () {
    $this->get('/missing-page-fragment', [
        'Accept' => '*/*',
        'Sec-Fetch-Dest' => 'empty',
    ])->assertNotFound();
});
