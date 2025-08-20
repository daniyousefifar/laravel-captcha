<?php

namespace MyDaniel\Captcha\Generators;

use Illuminate\Support\Str;
use MyDaniel\Captcha\Contracts\CaptchaGeneratorContract;

/**
 * Class TextGenerator
 *
 * Generates a random alphanumeric string for the captcha.
 */
class TextGenerator implements CaptchaGeneratorContract
{
    /**
     * Generate a random string based on the provided configuration.
     *
     * @param  array  $config  The configuration array, expecting 'characters', 'length', and 'sensitive' keys.
     *
     * @return array An associative array containing the generated 'text' (as an array of characters) and 'key' (as a string).
     */
    public function generate(array $config): array
    {
        $characters = $config['characters'];
        $length = $config['length'];
        $sensitive = $config['sensitive'] ?? false;

        $bag = [];
        for ($i = 0; $i < $length; $i++) {
            $char = $characters[rand(0, count($characters) - 1)];
            $bag[] = $sensitive ? $char : Str::lower($char);
        }

        return [
            'text' => $bag,
            'key' => implode('', $bag),
        ];
    }
}
