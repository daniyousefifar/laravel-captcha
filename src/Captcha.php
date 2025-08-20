<?php

namespace MyDaniel\Captcha;

use Exception;
use Illuminate\Config\Repository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use MyDaniel\Captcha\Contracts\CaptchaGeneratorContract;
use MyDaniel\Captcha\Contracts\ImageManagerContract;

/**
 * Class Captcha
 *
 * The main class for creating and validating captcha. It acts as an orchestrator
 * between the captcha generator and the image manager.
 */
class Captcha
{
    /**
     * The config repository instance.
     *
     * @var Repository
     */
    protected Repository $config;

    /**
     * The image manager instance.
     *
     * @var ImageManagerContract
     */
    protected ImageManagerContract $imageManager;

    /**
     * Captcha constructor.
     *
     * @param  Repository  $config
     * @param  ImageManagerContract  $imageManager
     */
    public function __construct(Repository $config, ImageManagerContract $imageManager)
    {
        $this->config = $config;

        $this->imageManager = $imageManager;
    }

    /**
     * Create a new captcha instance.
     *
     * @param  string  $configName  The name of the captcha profile from the config file.
     *
     * @return array An array containing the captcha key, image data, and sensitivity flag.
     *
     * @throws Exception
     */
    public function create(string $configName = 'default'): array
    {
        $config = $this->getConfig($configName);

        // 1. Get the generator based on config
        $generator = $this->getGenerator($config['driver']);

        // 2. Generate text and key
        ['text' => $text, 'key' => $plainKey] = $generator->generate($config);

        // 3. Create the hashed key for storage
        $hashedKey = $this->createKey($plainKey, $config['encrypt'] ?? false);

        // 4. Create the image
        $imageData = $this->imageManager->createImage($text, $config);

        // 5. Store in cache
        Cache::put(
            $this->getCacheKey($hashedKey),
            $plainKey, // Store the plain key to check against
            $config['expire'] ?? 60
        );

        return [
            'sensitive' => $config['sensitive'] ?? false,
            'key' => $hashedKey,
            'image' => $imageData,
        ];
    }

    /**
     * Check if the given captcha value is valid.
     *
     * @param  string  $value  The user-provided value.
     * @param  string  $key  The captcha key returned from the create() method.
     * @param  string  $configName  The name of the captcha profile.
     *
     * @return bool True if the value is correct, false otherwise.
     */
    public function check(string $value, string $key, string $configName = 'default'): bool
    {
        $cacheKey = $this->getCacheKey($key);

        if (!Cache::has($cacheKey)) {
            return false;
        }

        $storedKey = Cache::pull($cacheKey);
        $config = $this->getConfig($configName);

        if (!($config['sensitive'] ?? false)) {
            $value = Str::lower($value);
            $storedKey = Str::lower($storedKey);
        }

        return $value === $storedKey;
    }

    /**
     * Retrieves the configuration for a specific profile, merging it with base settings.
     *
     * This method fetches the 'characters' array and the specified profile array
     * from the config file and merges them.
     *
     * @param  string  $name  The name of the captcha profile (e.g., 'default', 'math').
     * @return array The merged configuration array.
     */
    protected function getConfig(string $name): array
    {
        $baseConfig = $this->config->get("captcha", []);
        $profileConfig = $this->config->get("captcha.{$name}", []);

        return array_merge($baseConfig, $profileConfig);
    }

    /**
     * Creates a hashed and optionally encrypted key from a plain text value.
     *
     * @param  string  $plainKey  The plain text answer of the captcha.
     * @param  bool  $encrypt  Determines whether the final hash should be encrypted.
     *
     * @return string The processed key.
     */
    protected function createKey(string $plainKey, bool $encrypt): string
    {
        $hash = Hash::make($plainKey);

        return $encrypt ? Crypt::encrypt($hash) : $hash;
    }

    /**
     * Resolves the appropriate captcha generator instance from the service container.
     *
     * @param  string  $driver  The driver name specified in the config (e.g., 'text', 'math').
     *
     * @return CaptchaGeneratorContract The resolved generator instance.
     */
    protected function getGenerator(string $driver): CaptchaGeneratorContract
    {
        return app('captcha.generator.'.$driver);
    }

    /**
     * Generates a unique cache key string.
     *
     * @param  string  $key  The captcha key.
     *
     * @return string The formatted cache key.
     */
    protected function getCacheKey(string $key): string
    {
        return 'captcha_'.md5($key);
    }
}
