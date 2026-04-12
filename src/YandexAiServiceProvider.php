<?php

namespace JazzMax\YandexAi;

use Illuminate\Support\ServiceProvider;

class YandexAiServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../config/yandex-ai.php', 'yandex-ai');

        $this->app->singleton(YandexAi::class, fn () => new YandexAi());
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/yandex-ai.php' => config_path('yandex-ai.php'),
        ], 'yandex-ai-config');
    }
}
