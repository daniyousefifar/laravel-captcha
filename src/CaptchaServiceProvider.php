<?php

namespace MyDaniel\Captcha;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ServiceProvider;
use MyDaniel\Captcha\Contracts\ImageManagerContract;
use MyDaniel\Captcha\Generators\MathGenerator;
use MyDaniel\Captcha\Generators\TextGenerator;
use MyDaniel\Captcha\Image\InterventionImage;

class CaptchaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../config/captcha.php' => config_path('captcha.php'),
            __DIR__.'/../lang' => $this->app->langPath('vendor/captcha'),
        ], 'config');

        $this->publishes([
            __DIR__.'/../config/captcha.php' => config_path('captcha.php')
        ], 'captcha-config');

        $this->publishes([
            __DIR__.'/../lang', $this->app->langPath('vendor/captcha')
        ], 'captcha-lang');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'captcha');

        Validator::extend('captcha', function ($attribute, $value, $parameters, $validator) {
            if (empty($parameters[0])) {
                return false;
            }

            $key = $parameters[0];
            $config = $parameters[1] ?? 'default';

            return \MyDaniel\Captcha\Facades\Captcha::check($value, $key, $config);
        });

        Validator::replacer('captcha', function ($message, $attribute, $rule, $parameters) {
            if ($message === 'validation.captcha') {
                return __('captcha::validation.captcha');
            }

            return $message;
        });
    }

    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/captcha.php', 'captcha');

        $this->app->singleton(ImageManagerContract::class, function ($app) {
            return new InterventionImage($app['files']);
        });

        $this->app->bind('captcha.generator.text', TextGenerator::class);
        $this->app->bind('captcha.generator.math', MathGenerator::class);

        $this->app->singleton('captcha', function ($app) {
            return new Captcha(
                $app['config'],
                $app[ImageManagerContract::class],
            );
        });
    }
}
