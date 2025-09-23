<?php

namespace App;

class Normalyzer
{
    public function normalyzer(string $url): string
    {
        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl['scheme'];
        $host = $parsedUrl['host'];

        $normalUrl = $scheme . '://' . $host;

        return $normalUrl;
    }
}
