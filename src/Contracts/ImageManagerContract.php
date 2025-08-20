<?php

namespace MyDaniel\Captcha\Contracts;

/**
 * Interface ImageManagerContract
 *
 * Defines the contract for creating captcha images. This allows for swapping
 * different image processing libraries (e.g., GD, Imagick) without changing
 * the core captcha logic.
 */
interface ImageManagerContract
{
    /**
     * Create the captcha image.
     *
     * @param  string|array  $text  The text to be rendered on the image.
     * @param  array  $config  The configuration array for image generation.
     *
     * @return string The generated image as a Base64 encoded data URI.
     */
    public function createImage(string|array $text, array $config = []): string;
}
