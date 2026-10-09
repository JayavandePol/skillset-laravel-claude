<?php

declare(strict_types=1);

/*
 * Architecture rules as tests (Pest arch plugin, bundled with Pest 3+).
 * Each expectation maps to a rule ID from the laravel-standards / laravel-security skills.
 * Remove expectations for folders the project doesn't have.
 */

arch()->preset()->php();
arch()->preset()->security()->ignoring(['assert']);
arch()->preset()->laravel();

// STD-10
arch('strict types everywhere')
    ->expect('App')
    ->toUseStrictTypes();

// STD-14
arch('no debugging statements')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r', 'die', 'exit'])
    ->not->toBeUsed();

// STD-01
arch('actions are final, invokable through handle()')
    ->expect('App\Actions')
    ->toBeFinal()
    ->toHaveMethod('handle');

// STD-02
arch('controllers do not touch the DB facade')
    ->expect('App\Http\Controllers')
    ->not->toUse(['Illuminate\Support\Facades\DB']);

arch('controllers have the Controller suffix')
    ->expect('App\Http\Controllers')
    ->toHaveSuffix('Controller');

// STD-05
arch('DTOs are readonly')
    ->expect('App\Data')
    ->toBeReadonly()
    ->ignoring('App\Data\Concerns');

// STD-06
arch('enums are enums')
    ->expect('App\Enums')
    ->toBeEnums();

// STD-20
arch('env() only in config')
    ->expect('env')
    ->not->toBeUsed()
    ->ignoring('config');

// STD-30
arch('form requests extend FormRequest and end with Request')
    ->expect('App\Http\Requests')
    ->toExtend('Illuminate\Foundation\Http\FormRequest')
    ->toHaveSuffix('Request');

arch('policies end with Policy')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

arch('jobs are queueable')
    ->expect('App\Jobs')
    ->toImplement('Illuminate\Contracts\Queue\ShouldQueue');

arch('models only extend Eloquent')
    ->expect('App\Models')
    ->toExtend('Illuminate\Database\Eloquent\Model')
    ->ignoring('App\Models\Concerns');

// STD-03 / layering
arch('domain code does not depend on HTTP')
    ->expect(['App\Actions', 'App\Services', 'App\Queries', 'App\Data'])
    ->not->toUse(['Illuminate\Http\Request', 'request']);
