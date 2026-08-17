<?php

namespace App\Domains\Setting\Services;

use App\Domains\Setting\Models\EmailSetting;

class EmailSettingService
{
    /** Aplica ao Laravel a configuração SMTP armazenada no banco. */
    public function apply(?EmailSetting $setting): void
    {
        if (! $setting?->enabled) {
            config(['mail.default' => 'array']);
            $this->purgeMailers();

            return;
        }

        config([
            'mail.default' => 'smtp',
            'mail.mailers.smtp' => [
                'transport' => 'smtp',
                'scheme' => $setting->encryption === EmailSetting::ENCRYPTION_SSL ? 'smtps' : null,
                'url' => null,
                'host' => $setting->host,
                'port' => $setting->port,
                'username' => $setting->username,
                'password' => $setting->password,
                'timeout' => $setting->timeout,
                'local_domain' => parse_url((string) config('app.url'), PHP_URL_HOST),
            ],
            'mail.from.address' => $setting->from_address,
            'mail.from.name' => $setting->from_name,
        ]);

        $this->purgeMailers();
    }

    private function purgeMailers(): void
    {
        if (app()->resolved('mail.manager')) {
            app('mail.manager')->purge('smtp');
            app('mail.manager')->purge('array');
        }
    }
}
