<?php

namespace MyDaniel\Captcha\Contracts;

/**
 * Interface CaptchaGeneratorContract
 *
 * Defines the contract for captcha text generators. Any class that generates
 * captcha text (e.g., random string, math problem) must implement this interface.
 */
interface CaptchaGeneratorContract
{
    /**
     * Generate the captcha text and the corresponding key.
     *
     * @param  array  $config  The configuration array for the generator.
     *
     * @return array Should return an associative array with two keys:
     *  'text' => (string|array) The text to be displayed on the image.
     *  'key'  => (string) The plain value to be checked against.
     *
     * @throws \Exception
     */
    public function generate(array $config): array;
}
