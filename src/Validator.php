<?php

namespace App;

class Validator implements ValidatorInterface
{
    public function validate(string $url): array
    {
        $errors = [];
        $parsedUrl = parse_url($url);

        if (empty($url) || trim($url) === '') {
            $errors['url'] = 'URL не должен быть пустым';
            return $errors;
        }

        if ($parsedUrl === false) {
            $errors['url'] = 'Некорректный URL';
            return $errors;
        }

        if (!isset($parsedUrl['scheme']) || empty($parsedUrl['scheme'])) {
            $errors['scheme'] = 'Неккоректный URL';
        }

        if (!isset($parsedUrl['host']) || empty($parsedUrl['host'])) {
            $errors['host'] = 'Неккоректный URL';
        }

        if (strlen($url) > 255) {
            $errors['symbols'] = 'Неккоректный URL';
        }

        return $errors;
    }

    public function normalyzer(string $url): string
    {
        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl['scheme'];
        $host = $parsedUrl['host'];

        $normalUrl = $scheme . '://' . $host;

        return $normalUrl;
    }
}
