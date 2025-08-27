<?php

require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;

$app = AppFactory::create();
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Hello, World!');
    return $response;
});

$app->run();
