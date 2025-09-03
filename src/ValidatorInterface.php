<?php

namespace App;

interface ValidatorInterface
{
    public function validate(string $url);
    public function normalyzer(string $url);
}
