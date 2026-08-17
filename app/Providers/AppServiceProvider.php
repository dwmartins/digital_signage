<?php

namespace App\Providers;

use App\Domains\Setting\Models\EmailSetting;
use App\Domains\Setting\Services\EmailSettingService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        try {
            if (! Schema::hasTable('email_settings')) {
                return;
            }

            $setting = EmailSetting::query()->where('key', 'smtp')->first();
            app(EmailSettingService::class)->apply($setting);
        } catch (Throwable) {
            config(['mail.default' => 'array']);
        }
    }
}
