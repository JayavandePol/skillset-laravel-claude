<?php

// SEC-41 / SEC-70 — paste into AppServiceProvider::boot() (or a dedicated RateLimitServiceProvider).
// Apply with ->middleware('throttle:<name>'). Tune numbers per product.

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

RateLimiter::for('api', fn (Request $request) => Limit::perMinute(60)->by($request->user()?->id ?: $request->ip()));

RateLimiter::for('login', fn (Request $request) => [
    Limit::perMinute(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()),
    Limit::perMinute(30)->by($request->ip()),
]);

RateLimiter::for('password-reset', fn (Request $request) => Limit::perHour(5)->by(Str::lower((string) $request->input('email')).'|'.$request->ip()));

RateLimiter::for('register', fn (Request $request) => Limit::perHour(10)->by($request->ip()));

RateLimiter::for('invites', fn (Request $request) => Limit::perHour(50)->by((string) $request->user()?->id));

RateLimiter::for('uploads', fn (Request $request) => Limit::perMinute(30)->by((string) $request->user()?->id));

RateLimiter::for('exports', fn (Request $request) => Limit::perHour(10)->by((string) $request->user()?->id));

RateLimiter::for('search', fn (Request $request) => Limit::perMinute(120)->by($request->user()?->id ?: $request->ip()));

RateLimiter::for('ai', fn (Request $request) => [
    Limit::perMinute(10)->by((string) $request->user()?->id),
    Limit::perDay(500)->by((string) $request->user()?->id),
]);
