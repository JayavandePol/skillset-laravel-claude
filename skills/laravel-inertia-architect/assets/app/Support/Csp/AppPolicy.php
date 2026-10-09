<?php

declare(strict_types=1);

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * Template CSP policy for spatie/laravel-csp (v3 preset style).
 * Register in config/csp.php ('presets' => [AppPolicy::class]) and call Vite::useCspNonce().
 * Check the package README for the installed major version — the API changed between v2 and v3.
 */
final class AppPolicy implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::OBJECT, Keyword::NONE)
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT)
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE]) // Radix/shadcn inline styles
            ->add(Directive::IMG, [Keyword::SELF, 'data:', 'blob:', (string) config('filesystems.disks.s3.url')])
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            ->add(Directive::CONNECT, [Keyword::SELF, 'wss://'.config('reverb.apps.apps.0.options.host')]);

        if (app()->isLocal()) {
            // Vite dev server + HMR websocket
            $policy
                ->add(Directive::SCRIPT, 'http://localhost:5173')
                ->add(Directive::STYLE, 'http://localhost:5173')
                ->add(Directive::CONNECT, ['http://localhost:5173', 'ws://localhost:5173']);
        }
    }
}
