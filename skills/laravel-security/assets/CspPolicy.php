<?php

declare(strict_types=1);

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * SEC-32 — CSP preset for spatie/laravel-csp (v3 preset API).
 * Register in config/csp.php ('presets' => [CspPolicy::class]) and call Vite::useCspNonce() in AppServiceProvider.
 * The package API changed between v2 and v3 — check the installed version's README.
 * Start with CSP_REPORT_ONLY=true, then enforce.
 */
final class CspPolicy implements Preset
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
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE]) // component libraries inject inline styles
            ->add(Directive::IMG, array_filter([Keyword::SELF, 'data:', 'blob:', config('filesystems.disks.s3.url')]))
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            ->add(Directive::CONNECT, array_filter([Keyword::SELF, $this->websocketOrigin()]));

        if (app()->isLocal()) {
            $policy
                ->add(Directive::SCRIPT, 'http://localhost:5173')
                ->add(Directive::STYLE, 'http://localhost:5173')
                ->add(Directive::CONNECT, ['http://localhost:5173', 'ws://localhost:5173']);
        }
    }

    private function websocketOrigin(): ?string
    {
        $host = config('reverb.apps.apps.0.options.host');

        return is_string($host) && $host !== '' ? "wss://{$host}" : null;
    }
}
