<?php

require __DIR__ . '/../vendor/autoload.php';
use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Views\PhpRenderer;

$container = new Container();
$container->set('renderer', function () {
    return new PhpRenderer(__DIR__ . '/../templates');
});

$app = AppFactory::createFromContainer($container);
$app->addErrorMiddleware(true, true, true);

$app->get('/', function ($request, $response) {
    error_log('--- SIMPLE RENDER ---');
    // Просто вернуть пустой ответ, чтобы увидеть, сколько раз это произойдет
    return $response->withStatus(200);
});

$app->run();
