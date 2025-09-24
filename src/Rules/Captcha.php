<?php

namespace MyDaniel\Captcha\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

/**
 * Class Captcha
 *
 * A custom validation rule to easily validate captcha responses.
 */
class Captcha implements ValidationRule
{
    /**
     * @var string|null
     */
    protected ?string $key;

    /**
     * @var string
     */
    protected string $config;

    /**
     * Create a new rule instance.
     *
     * @param  string|null  $key  The captcha key from the request.
     * @param  string  $config  The captcha configuration profile to use for validation.
     */
    public function __construct(?string $key, string $config = 'default')
    {
        $this->key = $key;
        $this->config = $config;
    }

    /**
     * Run the validation rule.
     *
     * @param  Closure(string): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (empty($this->key)) {
            $fail('captcha::validation.captcha')->translate();

            return;
        }

        if (!app('captcha')->check($value, $this->key, $this->config)) {
            $fail('captcha::validation.captcha')->translate();
        }
    }
}
