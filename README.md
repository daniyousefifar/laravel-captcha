# Laravel Captcha

[![Latest Version on Packagist](https://img.shields.io/packagist/v/mydaniel/laravel-captcha.svg?style=flat-square)](https://packagist.org/packages/your-vendor/laravel-captcha)
[![Total Downloads](https://img.shields.io/packagist/dt/your-vendor/laravel-captcha.svg?style=flat-square)](https://packagist.org/packages/mydaniel/laravel-captcha)
[![License](https://img.shields.io/packagist/l/your-vendor/laravel-captcha.svg?style=flat-square)](https://github.com/daniyousefifar/laravel-captcha/blob/main/LICENSE)

A modern, highly customizable, and secure captcha package for Laravel.

This package provides an easy way to protect your web forms from bots by generating image-based captchas. It's designed to be both user-friendly for humans and challenging for bots.

## Features

- ✅ **SOLID Architecture:** Clean, maintainable, and easily extensible code.
- 🚀 **Multiple Generators:** Comes with `text` and `math` captcha generators out of the box.
- 🎨 **Highly Customizable:** Control everything from image dimensions and colors to character sets and distortion levels.
- 🛡️ **Secure:** Prevents replay attacks with one-time use keys and short expiration times.
- 🔌 **Seamless Laravel Integration:** Includes a Service Provider, Facade, and a custom Validation Rule for easy integration.
- 🌐 **Multi-language Support:** Validation messages can be easily translated.

## Installation

You can install the package via Composer:

```bash
composer require mydaniel/laravel-captcha
````

## Configuration

1.  Publish the configuration and translation files using the following Artisan command:

    ```bash
    php artisan vendor:publish --provider="MyDaniel\Captcha\CaptchaServiceProvider"
    ```

2.  This will create two new files:

    - `config/captcha.php`: Here you can configure all aspects of the captcha, including asset paths, character sets, and image settings for different profiles (`default`, `math`, etc.).
    - `lang/vendor/captcha/`: This directory will contain the translation files for validation messages.

## Usage

Using the captcha involves two steps: displaying it in your form and validating the user's input.

### 1\. Displaying the Captcha

In your Blade view, use the `Captcha::create()` facade to generate the captcha data.

```blade
{{-- Generate the captcha data --}}
@php
    $captcha = \MyDaniel\Captcha\Facades\Captcha::create('default'); // Or 'math', 'inverse', etc.
@endphp

<div class="form-group">
    {{-- Display the captcha image --}}
    <img src="{{ $captcha['image'] }}" alt="Captcha Image">

    {{-- Input for the user to type the captcha code --}}
    <input type="text" id="captcha_code" name="captcha_code" required>

    {{-- Hidden field to store the captcha key --}}
    <input type="hidden" name="captcha_key" value="{{ $captcha['key'] }}">
</div>

@error('captcha_code')
    <span class="text-danger">{{ $message }}</span>
@enderror
```

### 2\. Validating the Input

In your controller or form request, use the provided `Captcha` validation rule.

#### Using the Validation Rule Object (Recommended)

This method is clean and type-safe.

```php
use Illuminate\Http\Request;
use MyDaniel\Captcha\Rules\Captcha;

public function yourControllerMethod(Request $request)
{
    $request->validate([
        // ... other fields
        'captcha_code' => ['required', new Captcha($request->input('captcha_key'), 'default')],
        'captcha_key' => 'required|string',
    ]);

    // Captcha is valid, proceed...
}
```

#### Using the String-based Rule

You can also use the rule as a string, which is useful in some scenarios.

```php
use Illuminate\Http\Request;

public function yourControllerMethod(Request $request)
{
    $request->validate([
        // ... other fields
        'captcha_code' => 'required|captcha:' . $request->input('captcha_key') . ',default',
        'captcha_key' => 'required|string',
    ]);

    // Captcha is valid, proceed...
}
```

## Customization

### Custom Fonts and Backgrounds

You can easily use your own fonts and background images.

1.  Place your `.ttf` font files or `.png`/`.jpg` background images in any directory within your project (e.g., `public/assets/captcha`).
2.  Update the `fonts_path` and `backgrounds_path` in your `config/captcha.php` file to point to these new directories.

### Creating a New Generator

The package is built to be extensible. To create a new captcha type (e.g., a question-based captcha):

1.  Create a new class that implements the `\MyDaniel\Captcha\Contracts\CaptchaGeneratorContract` interface.

2.  Implement the `generate(array $config): array` method.

3.  Register your new generator in a service provider (e.g., `AppServiceProvider`):

    ```php
    public function register()
    {
        $this->app->bind('captcha.generator.question', \App\Captcha\QuestionGenerator::class);
    }
    ```

4.  You can now use `'driver' => 'question'` in a new profile in your `config/captcha.php` file.

## Security

- **One-Time Use Keys:** The package automatically invalidates a captcha key after the first validation attempt to prevent replay attacks.
- **Rate Limiting:** It is highly recommended to protect your forms with Laravel's built-in `throttle` middleware to prevent brute-force attacks.

## License

This package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
