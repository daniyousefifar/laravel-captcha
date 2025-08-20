<?php

namespace MyDaniel\Captcha\Generators;

use MyDaniel\Captcha\Contracts\CaptchaGeneratorContract;
use Random\RandomException;

/**
 * Class MathGenerator
 *
 * Generates a simple mathematical problem for the captcha.
 */
class MathGenerator implements CaptchaGeneratorContract
{
    /**
     * Generates a random math problem (addition, subtraction, or multiplication).
     *
     * @param  array  $config  The configuration array, expecting an 'operators' key.
     *
     * @return array An associative array containing the 'text' of the math problem and the 'key' (the correct answer).
     *
     * @throws RandomException
     */
    public function generate(array $config): array
    {
        $operators = $config['operators'] ?? ['+', '-', '*'];
        if (empty($operators)) {
            $operators = ['+'];
        }
        $operator = $operators[array_rand($operators)];

        $x = 0;
        $y = 0;
        $result = 0;

        switch ($operator) {
            case '+':
                $x = random_int(10, 30);
                $y = random_int(1, 9);
                $result = $x + $y;
                break;
            case '-':
                $a = random_int(10, 30);
                $b = random_int(1, 9);
                $x = max($a, $b);
                $y = min($a, $b);
                $result = $x - $y;
                break;
            case '*':
                $x = random_int(2, 9);
                $y = random_int(2, 5);
                $result = $x * $y;
                break;
        }

        return [
            'text' => "$x $operator $y = ",
            'key' => (string) $result,
        ];
    }
}
