<?php

namespace MyDaniel\Captcha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Captcha
 *
 * Provides a static interface (Facade) to the captcha service in the container.
 *
 * @method static array create(string $config = 'default')
 * @method static bool check(string $value, string $key, string $config = 'default')
 *
 * @see \MyDaniel\Captcha\Captcha
 */
class Captcha extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor(): string
    {
        return 'captcha';
    }
}
