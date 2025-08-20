<?php

namespace MyDaniel\Captcha\Image;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\Geometry\Factories\LineFactory;
use Intervention\Image\Image;
use Intervention\Image\ImageManager;
use Intervention\Image\Typography\FontFactory;
use MyDaniel\Captcha\Contracts\ImageManagerContract;

/**
 * Class InterventionImage
 *
 * An implementation of the ImageManagerContract that uses the Intervention Image library
 * to create the captcha image.
 */
class InterventionImage implements ImageManagerContract
{
    /**
     * The Intervention Image manager instance.
     *
     * @var ImageManager
     */
    protected ImageManager $imageManager;

    /**
     * The Laravel Filesystem instance.
     *
     * @var Filesystem
     */
    protected Filesystem $files;

    /**
     * An array of absolute paths to font files.
     *
     * @var array
     */
    protected array $fonts = [];

    /**
     * An array of hex color codes for the text.
     *
     * @var array
     */
    protected array $fontColors = [];

    /**
     * An array of absolute paths to background image files.
     *
     * @var array
     */
    protected array $backgrounds = [];

    /**
     * The directory path for font files.
     *
     * @var string
     */
    protected string $fontsDirectory;

    /**
     * The directory path for background image files.
     *
     * @var string
     */
    protected string $backgroundsDirectory;

    /**
     * The width of the captcha image in pixels.
     *
     * @var int
     */
    protected int $width = 120;

    /**
     * The height of the captcha image in pixels.
     *
     * @var int
     */
    protected int $height = 36;

    /**
     * The background color of the canvas (used if no background image is set).
     *
     * @var string
     */
    protected string $bgColor = '#ffffff';

    /**
     * Whether to use a background image.
     *
     * @var bool
     */
    protected bool $bgImage = true;

    /**
     * The contrast level to apply to the image (-100 to 100).
     *
     * @var int
     */
    protected int $contrast = 0;

    /**
     * The sharpen level to apply to the image (0 to 100).
     *
     * @var int
     */
    protected int $sharpen = 0;

    /**
     * Whether to invert the image colors.
     *
     * @var bool
     */
    protected bool $invert = false;

    /**
     * The blur level to apply to the image (0 to 100).
     *
     * @var int
     */
    protected int $blur = 0;

    /**
     * The number of random lines to draw over the image.
     *
     * @var int
     */
    protected int $lines = 3;

    /**
     * The maximum angle (in degrees) for character rotation.
     *
     * @var int
     */
    protected int $angle = 15;

    /**
     * The top margin for the text. If 0, it's calculated automatically.
     *
     * @var int
     */
    protected int $marginTop = 0;

    /**
     * The left padding for the first character.
     *
     * @var int
     */
    protected int $textLeftPadding = 4;

    /**
     * The number of characters in the captcha text (used for positioning).
     *
     * @var int
     */
    protected int $length = 5;

    /**
     * The spacing between characters (kerning). Negative values create overlap.
     *
     * @var int
     */
    protected int $kerning = 0;

    /**
     * InterventionImage constructor.
     *
     * @param  Filesystem  $files
     */
    public function __construct(Filesystem $files)
    {
        $this->files = $files;

        $this->imageManager = new ImageManager(Driver::class);
    }

    /**
     * Create the captcha image from the given text and config.
     *
     * @param  array|string  $text  The text to render on the image.
     * @param  array  $config  The 'image' sub-array from the captcha config profile.
     *
     * @return string The data URI of the generated image.
     *
     * @throws FileNotFoundException
     */
    public function createImage(array|string $text, array $config = []): string
    {
        $this->configure($config);

        $image = $this->createCanvas();

        $this->drawText($image, $text);

        $this->drawLines($image);

        $this->applyEffects($image);

        return $image->toPng()->toDataUri();
    }

    /**
     * Apply configuration values to the class properties.
     *
     * @param  array  $config
     *
     * @return void
     */
    protected function configure(array $config): void
    {
        $this->fontsDirectory = $config['fonts_path'] ?? null;
        $this->backgroundsDirectory = $config['backgrounds_path'] ?? null;

        $imageConfig = $config['image'] ?? [];
        foreach ($imageConfig as $key => $value) {
            if (property_exists($this, $key)) {
                $this->{$key} = $value;
            }
        }

        $this->loadResources();
    }

    /**
     * Load font and background file paths.
     *
     * @return void
     */
    protected function loadResources(): void
    {
        if ($this->fontsDirectory && is_dir($this->fontsDirectory)) {
            $this->fonts = array_values(
                array_map(fn($file) => $file->getPathname(), $this->files->files($this->fontsDirectory))
            );
        }

        if ($this->backgroundsDirectory && is_dir($this->backgroundsDirectory)) {
            $this->backgrounds = array_values(
                array_map(fn($file) => $file->getPathname(), $this->files->files($this->backgroundsDirectory))
            );
        }
    }

    /**
     * Create the base image canvas.
     *
     * @return Image
     *
     * @throws FileNotFoundException
     */
    protected function createCanvas(): Image
    {
        $canvas = $this->imageManager->create($this->width, $this->height)->fill($this->bgColor);

        if ($this->bgImage && !empty($this->backgrounds)) {
            $backgroundImage = $this->imageManager
                ->read($this->files->get($this->background()))
                ->resize($this->width, $this->height);

            $canvas->place($backgroundImage);
        }

        return $canvas;
    }

    /**
     * Apply various effects to the image.
     *
     * @param  Image  $image
     *
     * @return void
     */
    protected function applyEffects(Image $image): void
    {
        if ($this->contrast !== 0) {
            $image->contrast($this->contrast);
        }

        if ($this->sharpen > 0) {
            $image->sharpen($this->sharpen);
        }

        if ($this->invert) {
            $image->invert();
        }

        if ($this->blur > 0) {
            $image->blur($this->blur);
        }
    }

    /**
     * Draw the text onto the image.
     *
     * @param  Image  $image
     * @param  string|array  $text
     *
     * @return void
     */
    protected function drawText(Image $image, string|array $text): void
    {
        $marginTop = $this->marginTop !== 0 ? $this->marginTop : $image->height() / $this->length;
        $textChars = is_string($text) ? str_split($text) : $text;

        $charWidth = ($image->width() - ($this->textLeftPadding * 2)) / count($textChars);
        $currentLeft = $this->textLeftPadding;

        foreach ($textChars as $char) {
            $kerningValue = rand($this->kerning, $this->kerning + 3);
            $marginLeft = $currentLeft + $kerningValue;

            $image->text((string) $char, (int) $marginLeft, (int) $marginTop, function (FontFactory $font) {
                $font->filename($this->font());
                $font->size($this->fontSize());
                $font->color($this->fontColor());
                $font->align('left');
                $font->valign('top');
                $font->angle($this->angle());
            });

            $currentLeft += $charWidth;
        }
    }

    /**
     * Draw random lines over the image.
     *
     * @param  Image  $image
     *
     * @return void
     */
    protected function drawLines(Image $image): void
    {
        for ($i = 0; $i < $this->lines; $i++) {
            $image->drawLine(function (LineFactory $line) use ($image, $i) {
                $line->from(
                    rand(0, $image->width()) + $i * rand(0, $image->height()),
                    rand(0, $image->height())
                );

                $line->to(
                    rand(0, $image->width()),
                    rand(0, $image->height())
                );

                $line->color($this->fontColor());
            });
        }
    }

    /**
     * Get a random background image path.
     *
     * @return string
     */
    protected function background(): string
    {
        if (empty($this->backgrounds)) {
            throw new \RuntimeException("No background images found in the configured directory.");
        }

        return $this->backgrounds[array_rand($this->backgrounds)];
    }

    /**
     * Get a random font file path.
     *
     * @return string
     */
    protected function font(): string
    {
        if (empty($this->fonts)) {
            throw new \RuntimeException("No fonts found in the configured directory.");
        }

        return $this->fonts[array_rand($this->fonts)];
    }

    /**
     * Generate a random font size.
     *
     * @return int
     */
    protected function fontSize(): int
    {
        return rand($this->height - 10, $this->height);
    }

    /**
     * Get a random font color.
     *
     * @return string
     */
    protected function fontColor(): string
    {
        if (!empty($this->fontColors)) {
            return $this->fontColors[array_rand($this->fontColors)];
        }

        return '#'.str_pad(dechex(mt_rand(0, 0xFFFFFF)), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate a random angle for the text.
     *
     * @return int
     */
    protected function angle(): int
    {
        return rand((-1 * $this->angle), $this->angle);
    }
}
