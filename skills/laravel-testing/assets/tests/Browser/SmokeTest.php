<?php

declare(strict_types=1);

/*
 * Template smoke tests (pestphp/pest-plugin-browser).
 * Adjust the route list to the app's main pages. Verify method names against the Pest browser docs.
 */

use App\Models\User;

$guestPages = ['/', '/login', '/register', '/forgot-password'];
$appPages = ['/dashboard', '/settings/profile', '/settings/password'];

it('renders public pages without errors', function () use ($guestPages) {
    visit($guestPages)->assertNoSmoke();
});

it('renders app pages without errors', function () use ($appPages) {
    $this->actingAs(User::factory()->create());

    visit($appPages)->assertNoSmoke();
});

it('renders on mobile without JavaScript errors', function () use ($appPages) {
    $this->actingAs(User::factory()->create());

    foreach ($appPages as $page) {
        visit($page)->on()->mobile()->assertNoJavaScriptErrors();
    }
});

it('logs in through the UI', function () {
    $user = User::factory()->create(['password' => bcrypt('password-for-tests')]);

    visit('/login')
        ->fill('email', $user->email)
        ->fill('password', 'password-for-tests')
        ->press('Log in')
        ->assertPathIs('/dashboard')
        ->assertNoJavaScriptErrors();
});
