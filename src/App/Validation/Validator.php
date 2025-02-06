<?php

declare(strict_types=1);

namespace App\Validation;

use InvalidArgumentException;

class Validator
{
    public static function validateEmail(string $email, int $maxLength = 120): string
    {
        $email = self::sanitize($email);

        if (empty($email)) {
            throw new InvalidArgumentException('Email не может быть пустым');
        }

        if (iconv_strlen($email, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException("Email не может быть длиннее $maxLength символов");
        }

        $pattern = '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/';
        if (!preg_match($pattern, $email)) {
            throw new InvalidArgumentException('Неверный формат email');
        }

        return $email;
    }

    public static function validateUserName(string $name, int $maxLength = 70): string
    {
        $name = self::sanitize($name);

        if (empty($name)) {
            throw new InvalidArgumentException('Имя не может быть пустым');
        }

        if (iconv_strlen($name, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException("Имя не может быть длиннее $maxLength символов");
        }

        if (!preg_match('/^(?!.*[<>"\'])[\p{L}\s-]+$/u', $name)) {
            throw new InvalidArgumentException('Имя содержит недопустимые символы');
        }

        return $name;
    }

    public static function validateGroupName(string $name, int $maxLength = 80): string
    {
        $name = self::sanitize($name);

        if (empty($name)) {
            throw new InvalidArgumentException('Название группы не может быть пустым');
        }

        if (iconv_strlen($name, 'UTF-8') > $maxLength) {
            throw new InvalidArgumentException("Название группы не может быть длиннее $maxLength символов");
        }

        if (!preg_match('/^(?!.*[<>"\'])[\p{L}\s-]+$/u', $name)) {
            throw new InvalidArgumentException('Название группы содержит недопустимые символы');
        }

        return $name;
    }

    private static function sanitize(string $input): string
    {
        $input = preg_replace('/\p{C}+/u', '', $input);
        $input = trim($input);

        return preg_replace('/\s+/u', ' ', $input);
    }
}
